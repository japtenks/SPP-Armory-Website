<?php

function spp_forum_handle_topic_moderation(
    string $action,
    array $user,
    int $realmId,
    array $thisTopic,
    array $thisForum
): bool {
    if (!in_array($action, array('sticktopic', 'unsticktopic', 'closetopic', 'opentopic', 'dodeletetopic'), true) || empty($thisTopic['topic_id'])) {
        return false;
    }

    spp_require_csrf('forum_actions');
    if ((int)($user['g_forum_moderate'] ?? 0) !== 1) {
        output_message('alert', 'You are not authorized to moderate this topic.');
        return true;
    }

    try {
        $forumPdo = spp_get_pdo('realmd', $realmId);
        if ($action === 'dodeletetopic') {
            $stmt = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE topic_id = ?");
            $stmt->execute([(int)$thisTopic['topic_id']]);
            $postCount = (int)$stmt->fetchColumn();

            $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE topic_id = ?");
            $stmt->execute([(int)$thisTopic['topic_id']]);

            $stmt = $forumPdo->prepare("DELETE FROM f_topics WHERE topic_id = ? LIMIT 1");
            $stmt->execute([(int)$thisTopic['topic_id']]);

            $stmt = $forumPdo->prepare("
                UPDATE f_forums
                SET num_topics = GREATEST(0, num_topics - 1),
                    num_posts = GREATEST(0, num_posts - ?),
                    last_topic_id = COALESCE(
                        (SELECT topic_id FROM f_topics WHERE forum_id = ? ORDER BY sticky DESC, last_post DESC, topic_id DESC LIMIT 1),
                        0
                    )
                WHERE forum_id = ? LIMIT 1
            ");
            $stmt->execute([
                $postCount,
                (int)$thisForum['forum_id'],
                (int)$thisForum['forum_id']
            ]);

            redirect("index.php?n=forum&sub=viewforum&fid={$thisForum['forum_id']}", 1);
            return true;
        }

        if ($action === 'sticktopic' || $action === 'unsticktopic') {
            $stmt = $forumPdo->prepare("UPDATE f_topics SET sticky = ? WHERE topic_id = ? LIMIT 1");
            $stmt->execute([
                $action === 'sticktopic' ? 1 : 0,
                (int)$thisTopic['topic_id']
            ]);
        } else {
            $stmt = $forumPdo->prepare("UPDATE f_topics SET closed = ? WHERE topic_id = ? LIMIT 1");
            $stmt->execute([
                $action === 'closetopic' ? 1 : 0,
                (int)$thisTopic['topic_id']
            ]);
        }

        redirect("index.php?n=forum&sub=viewtopic&tid={$thisTopic['topic_id']}", 1);
        return true;
    } catch (Throwable $e) {
        error_log('[forum.post.actions] Topic moderation toggle failed: ' . $e->getMessage());
        output_message('alert', 'Could not update topic state.');
        return true;
    }
}

function spp_forum_handle_new_topic_submission(
    string $action,
    bool $canPost,
    array $user,
    int $realmId,
    int $postTime,
    array $thisForum,
    array $forumPostForm,
    array $forumPostErrors,
    bool $isRecruitmentForum,
    $recruitmentGuild
): array {
    $result = array(
        'handled' => false,
        'stop' => false,
        'action' => $action,
        'forum_post_mode' => 'newtopic',
        'forum_post_errors' => $forumPostErrors,
    );

    if (!$canPost || $action !== 'donewtopic' || empty($thisForum['forum_id'])) {
        return $result;
    }

    $result['handled'] = true;
    spp_require_csrf('forum_actions');

    if (!(($user['g_post_new_topics'] == 1 && empty($thisForum['closed'])) || $user['g_forum_moderate'] == 1)) {
        $result['stop'] = true;
        return $result;
    }

    $message = $forumPostForm['text'];
    $subject = $forumPostForm['subject'];
    $forumPostErrors = spp_forum_validate_submission($subject, $message, true);

    if ($isRecruitmentForum) {
        if (!$recruitmentGuild) {
            $canPost = false;
            $forumPostErrors[] = 'You must be a guild leader or officer with invite rights to post in this forum.';
        } else {
            $existingThreadId = find_active_recruitment_thread(
                $realmId,
                (int)$thisForum['forum_id'],
                (int)$recruitmentGuild['guildid']
            );
            if ($existingThreadId !== null) {
                $canPost = false;
                $forumPostErrors[] = 'Your guild already has an active recruitment thread. View it at index.php?n=forum&sub=viewtopic&tid=' . $existingThreadId . '.';
            }
        }
    }

    if ((int)($user['g_forum_moderate'] ?? 0) !== 1) {
        $guardPdo = spp_get_pdo('realmd', $realmId);
        $forumPostErrors = array_merge(
            $forumPostErrors,
            spp_forum_guard_against_repeat_posts($guardPdo, (int)$user['id'], (int)$thisForum['forum_id'], 0, $subject, $message, true)
        );
    }

    if (!$canPost || !empty($forumPostErrors)) {
        foreach ($forumPostErrors as $forumPostError) {
            output_message('alert', $forumPostError);
        }
        $result['action'] = 'newtopic';
        $result['forum_post_errors'] = $forumPostErrors;
        return $result;
    }

    $posterIdentityId = null;
    if (function_exists('spp_ensure_char_identity') && !empty($user['character_id'])) {
        $posterIdentityId = spp_ensure_char_identity(
            $realmId,
            $user['character_id'],
            $user['id'],
            $user['character_name']
        ) ?: null;
    }

    try {
        $forumPdo = spp_get_pdo('realmd', $realmId);
        $forumPdo->beginTransaction();

        $stmt = $forumPdo->prepare(
            "INSERT INTO f_topics
               (topic_poster, topic_poster_id, topic_poster_identity_id, topic_name, topic_posted, forum_id,
                guild_id, managed_by_account_id, recruitment_status, last_bumped_at)
             VALUES
               (:poster, :poster_id, :identity_id, :topic_name, :topic_posted, :forum_id,
                :guild_id, :managed_by, :rec_status, :bumped_at)"
        );
        $stmt->execute([
            ':poster' => $user['character_name'],
            ':poster_id' => $user['id'],
            ':identity_id' => $posterIdentityId,
            ':topic_name' => $subject,
            ':topic_posted' => $postTime,
            ':forum_id' => $thisForum['forum_id'],
            ':guild_id' => $recruitmentGuild ? (int)$recruitmentGuild['guildid'] : null,
            ':managed_by' => $recruitmentGuild ? (int)$user['id'] : null,
            ':rec_status' => $recruitmentGuild ? 'active' : null,
            ':bumped_at' => $recruitmentGuild ? $postTime : null,
        ]);
        $newTopicId = (int)$forumPdo->lastInsertId();
        if ($newTopicId <= 0) {
            throw new RuntimeException('Topic creation failed.');
        }

        $stmt = $forumPdo->prepare(
            "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
             VALUES (:poster, :poster_id, :character_id, :identity_id, :poster_ip, :message, :posted, :topic_id)"
        );
        $stmt->execute([
            ':poster' => $user['character_name'],
            ':poster_id' => $user['id'],
            ':character_id' => $user['character_id'],
            ':identity_id' => $posterIdentityId,
            ':poster_ip' => $user['ip'],
            ':message' => $message,
            ':posted' => $postTime,
            ':topic_id' => $newTopicId,
        ]);
        $newPostId = (int)$forumPdo->lastInsertId();
        if ($newPostId <= 0) {
            throw new RuntimeException('Post creation failed.');
        }

        $stmt = $forumPdo->prepare(
            "UPDATE f_topics
             SET last_post = :last_post, last_post_id = :last_post_id, last_poster = :last_poster
             WHERE topic_id = :topic_id"
        );
        $stmt->execute([
            ':last_post' => $postTime,
            ':last_post_id' => $newPostId,
            ':last_poster' => $user['character_name'],
            ':topic_id' => $newTopicId,
        ]);
        spp_enforce_topic_view_floor($forumPdo, $newTopicId, 1);

        $stmt = $forumPdo->prepare(
            "UPDATE f_forums
             SET num_topics = num_topics + 1, num_posts = num_posts + 1, last_topic_id = :last_topic_id
             WHERE forum_id = :forum_id"
        );
        $stmt->execute([
            ':last_topic_id' => $newTopicId,
            ':forum_id' => $thisForum['forum_id'],
        ]);

        spp_increment_forum_unread($forumPdo, (int)$thisForum['forum_id'], (int)$user['id']);
        $forumPdo->commit();
        redirect("index.php?n=forum&sub=viewtopic&tid={$newTopicId}", 1);
        $result['stop'] = true;
    } catch (Throwable $e) {
        if (isset($forumPdo) && $forumPdo instanceof PDO && $forumPdo->inTransaction()) {
            $forumPdo->rollBack();
        }
        error_log('[forum.post.actions] Topic creation failed: ' . $e->getMessage());
        output_message('alert', 'Topic creation failed.');
        $result['stop'] = true;
    }

    return $result;
}

function spp_forum_handle_new_reply_submission(
    string $action,
    bool $canPost,
    array $user,
    int $realmId,
    int $postTime,
    array $thisForum,
    array $thisTopic,
    array $forumPostForm,
    array $forumPostErrors,
    bool $isRecruitmentForum
): array {
    $result = array(
        'handled' => false,
        'stop' => false,
        'action' => $action,
        'forum_post_mode' => 'reply',
        'forum_post_errors' => $forumPostErrors,
    );

    if (!$canPost || $action !== 'donewpost' || empty($thisForum['forum_id']) || empty($thisTopic['topic_id'])) {
        return $result;
    }

    $result['handled'] = true;
    spp_require_csrf('forum_actions');
    if (!$user['g_reply_other_topics']) {
        output_message('alert', 'You are not authorized to reply to this topic.');
        $result['stop'] = true;
        return $result;
    }

    if (!empty($thisTopic['closed']) && (int)($user['g_forum_moderate'] ?? 0) !== 1) {
        $forumPostErrors[] = 'This topic is closed and cannot accept new replies.';
    }

    $message = $forumPostForm['text'];
    $forumPostErrors = array_merge($forumPostErrors, spp_forum_validate_submission('', $message, false));

    if ((int)($user['g_forum_moderate'] ?? 0) !== 1) {
        $guardPdo = spp_get_pdo('realmd', $realmId);
        $forumPostErrors = array_merge(
            $forumPostErrors,
            spp_forum_guard_against_repeat_posts($guardPdo, (int)$user['id'], (int)$thisForum['forum_id'], (int)$thisTopic['topic_id'], '', $message, false)
        );
    }

    if (!empty($forumPostErrors)) {
        foreach ($forumPostErrors as $forumPostError) {
            output_message('alert', $forumPostError);
        }
        $result['action'] = 'newpost';
        $result['forum_post_errors'] = $forumPostErrors;
        return $result;
    }

    $replyIdentityId = null;
    if (function_exists('spp_ensure_char_identity') && !empty($user['character_id'])) {
        $replyIdentityId = spp_ensure_char_identity(
            $realmId,
            $user['character_id'],
            $user['id'],
            $user['character_name']
        ) ?: null;
    }

    try {
        $forumPdo = spp_get_pdo('realmd', $realmId);
        $forumPdo->beginTransaction();

        $stmt = $forumPdo->prepare(
            "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
             VALUES (:poster, :poster_id, :character_id, :identity_id, :poster_ip, :message, :posted, :topic_id)"
        );
        $stmt->execute([
            ':poster' => $user['character_name'],
            ':poster_id' => $user['id'],
            ':character_id' => $user['character_id'],
            ':identity_id' => $replyIdentityId,
            ':poster_ip' => $user['ip'],
            ':message' => $message,
            ':posted' => $postTime,
            ':topic_id' => $thisTopic['topic_id'],
        ]);
        $newPostId = (int)$forumPdo->lastInsertId();
        if ($newPostId <= 0) {
            throw new RuntimeException('Reply failed.');
        }

        $_bumpSql = $isRecruitmentForum && !empty($thisTopic['recruitment_status'])
            ? ', last_bumped_at = :bumped_at'
            : '';
        $stmt = $forumPdo->prepare(
            "UPDATE f_topics
             SET last_post = :last_post, last_post_id = :last_post_id,
                 last_poster = :last_poster, num_replies = num_replies + 1
                 {$_bumpSql}
             WHERE topic_id = :topic_id"
        );
        $execParams = [
            ':last_post' => $postTime,
            ':last_post_id' => $newPostId,
            ':last_poster' => $user['character_name'],
            ':topic_id' => $thisTopic['topic_id'],
        ];
        if ($_bumpSql) {
            $execParams[':bumped_at'] = $postTime;
        }
        $stmt->execute($execParams);
        spp_enforce_topic_view_floor($forumPdo, (int)$thisTopic['topic_id'], 2);

        $stmt = $forumPdo->prepare(
            "UPDATE f_forums
             SET num_posts = num_posts + 1, last_topic_id = :last_topic_id
             WHERE forum_id = :forum_id"
        );
        $stmt->execute([
            ':last_topic_id' => $thisTopic['topic_id'],
            ':forum_id' => $thisForum['forum_id'],
        ]);

        spp_increment_forum_unread($forumPdo, (int)$thisForum['forum_id'], (int)$user['id']);
        $forumPdo->commit();
        redirect("index.php?n=forum&sub=viewtopic&tid={$thisTopic['topic_id']}&to=lastpost", 1);
        $result['stop'] = true;
    } catch (Throwable $e) {
        if (isset($forumPdo) && $forumPdo instanceof PDO && $forumPdo->inTransaction()) {
            $forumPdo->rollBack();
        }
        error_log('[forum.post.actions] Reply creation failed: ' . $e->getMessage());
        output_message('alert', 'Reply failed.');
        $result['stop'] = true;
    }

    return $result;
}
