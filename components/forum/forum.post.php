<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;
include('forum.func.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$post_time = time();
$action = $_GET['action'] ?? '';
$this_post = [];
$this_topic = [];
$this_forum = [];
$posts = [];
$canPost = true;
$posting_block_reason = '';

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}
$cookieRealmId = (int)($_COOKIE['cur_selected_realmd'] ?? ($_COOKIE['cur_selected_realm'] ?? 0));
$realmId = ($cookieRealmId > 0 && isset($realmMap[$cookieRealmId]))
    ? $cookieRealmId
    : spp_resolve_realm_id($realmMap);
$charDbName = $realmMap[$realmId]['chars'];
$activeForumCharacter = resolve_forum_character_for_realm($user, $realmId);

if ($activeForumCharacter) {
    $user['character_id'] = (int)$activeForumCharacter['guid'];
    $user['character_name'] = $activeForumCharacter['name'];

    if (!empty($user['id'])) {
        setcookie('cur_selected_character', $user['character_id'], time() + 86400, '/');
        setcookie('cur_selected_realm', $realmId, time() + 86400, '/');
        setcookie('cur_selected_realmd', $realmId, time() + 86400, '/');
        $forumUpdatePdo = spp_get_pdo('realmd', $realmId);
        $stmtWa = $forumUpdatePdo->prepare("UPDATE website_accounts SET character_id=?, character_name=? WHERE account_id=?");
        $stmtWa->execute([$user['character_id'], $user['character_name'], $user['id']]);
        if (function_exists('spp_ensure_char_identity')) {
            spp_ensure_char_identity($realmId, $user['character_id'], $user['id'], $user['character_name']);
        }
    }
}

if (!empty($_GET['fid']) && empty($_GET['f'])) {
    $_GET['f'] = $_GET['fid'];
}

if (!empty($_GET['post'])) {
    $this_post = get_post_byid($_GET['post']);
    if (!empty($this_post['topic_id'])) {
        $_GET['t'] = $this_post['topic_id'];
    }
}
if (!empty($_GET['t'])) {
    $this_topic = get_topic_byid($_GET['t']);
    if (!empty($this_topic['forum_id'])) {
        $_GET['f'] = $this_topic['forum_id'];
    }
}
if (!empty($_GET['f'])) {
    $this_forum = get_forum_byid($_GET['f']);
}

$_newsFid = (int)($MW->getConfig->generic_values->forum->news_forum_id ?? 0);
$_isNewsForum = $_newsFid > 0 && (int)($this_forum['forum_id'] ?? $_GET['f'] ?? 0) === $_newsFid;

if ($user['id'] <= 0) {
    $canPost = false;
    $posting_block_reason = 'You must be logged in to post.';
} elseif ($_isNewsForum && (int)($user['gmlevel'] ?? 0) < 3) {
    $canPost = false;
    $posting_block_reason = 'Only GMs may post in the News forum.';
    output_message('alert', $posting_block_reason);
} elseif (!isValidChar($user, $realmId)) {
    $canPost = false;
    $posting_block_reason = 'You must select a valid character before posting. This account currently has no available character loaded for the selected realm.';
    output_message('alert', $posting_block_reason);
} elseif (!empty($this_forum) && !check_forum_scope($this_forum, $realmId)) {
    $canPost = false;
    $posting_block_reason = 'Your selected character cannot post in this forum. Please switch to the correct realm.';
    output_message('alert', $posting_block_reason);
}

// Detect if this is a guild recruitment forum.
$_isRecruitmentForum = !empty($this_forum) &&
    ($this_forum['scope_type'] ?? '') === 'guild_recruitment';

// For recruitment forums, resolve the guild now (used in both GET and POST paths).
$_recruitmentGuild = null;
if ($_isRecruitmentForum && !empty($user['character_id'])) {
    $_recruitmentGuild = get_char_recruitment_guild($realmId, (int)$user['character_id'], (int)$user['id']);
}

if (in_array($action, array('sticktopic', 'unsticktopic', 'closetopic', 'opentopic', 'dodeletetopic'), true) && !empty($this_topic['topic_id'])) {
    if ((int)($user['g_forum_moderate'] ?? 0) !== 1) {
        output_message('alert', 'You are not authorized to moderate this topic.');
        return;
    }

    try {
        $forumPdo = spp_get_pdo('realmd', $realmId);
        if ($action === 'dodeletetopic') {
            $stmt = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE topic_id = ?");
            $stmt->execute([(int)$this_topic['topic_id']]);
            $postCount = (int)$stmt->fetchColumn();

            $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE topic_id = ?");
            $stmt->execute([(int)$this_topic['topic_id']]);

            $stmt = $forumPdo->prepare("DELETE FROM f_topics WHERE topic_id = ? LIMIT 1");
            $stmt->execute([(int)$this_topic['topic_id']]);

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
                (int)$this_forum['forum_id'],
                (int)$this_forum['forum_id']
            ]);

            redirect("index.php?n=forum&sub=viewforum&fid={$this_forum['forum_id']}", 1);
            return;
        } elseif ($action === 'sticktopic' || $action === 'unsticktopic') {
            $stmt = $forumPdo->prepare(
                "UPDATE f_topics SET sticky = ? WHERE topic_id = ? LIMIT 1"
            );
            $stmt->execute([
                $action === 'sticktopic' ? 1 : 0,
                (int)$this_topic['topic_id']
            ]);
        } else {
            $stmt = $forumPdo->prepare(
                "UPDATE f_topics SET closed = ? WHERE topic_id = ? LIMIT 1"
            );
            $stmt->execute([
                $action === 'closetopic' ? 1 : 0,
                (int)$this_topic['topic_id']
            ]);
        }
        redirect("index.php?n=forum&sub=viewtopic&tid={$this_topic['topic_id']}", 1);
        return;
    } catch (Throwable $e) {
        error_log('[forum.post] Topic moderation toggle failed: ' . $e->getMessage());
        output_message('alert', 'Could not update topic state.');
        return;
    }
}

if ($canPost && $action === 'donewtopic' && !empty($this_forum['forum_id'])) {
    if (($user['g_post_new_topics'] == 1 && !$this_forum['closed']) || $user['g_forum_moderate'] == 1) {
        if (!empty($_POST['subject']) && !empty($_POST['text'])) {
            $message = trim((string)$_POST['text']);
            $subject = trim((string)$_POST['subject']);

            // Guild recruitment: enforce one active thread per guild.
            if ($_isRecruitmentForum) {
                if (!$_recruitmentGuild) {
                    $canPost = false;
                    output_message('alert', 'You must be a guild leader or officer with invite rights to post in this forum.');
                } else {
                    $existingThreadId = find_active_recruitment_thread(
                        $realmId,
                        (int)$this_forum['forum_id'],
                        (int)$_recruitmentGuild['guildid']
                    );
                    if ($existingThreadId !== null) {
                        $canPost = false;
                        output_message('alert', 'Your guild already has an active recruitment thread. '
                            . '<a href="index.php?n=forum&sub=viewtopic&tid=' . $existingThreadId . '">View it here</a>.');
                    }
                }
            }

            if (!$canPost) {
                // Block without falling through to the INSERT.
            } else {

            // Resolve (or lazily create) the poster's character identity.
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
                    ':poster'      => $user['character_name'],
                    ':poster_id'   => $user['id'],
                    ':identity_id' => $posterIdentityId,
                    ':topic_name'  => $subject,
                    ':topic_posted' => $post_time,
                    ':forum_id'    => $this_forum['forum_id'],
                    ':guild_id'    => $_recruitmentGuild ? (int)$_recruitmentGuild['guildid'] : null,
                    ':managed_by'  => $_recruitmentGuild ? (int)$user['id'] : null,
                    ':rec_status'  => $_recruitmentGuild ? 'active' : null,
                    ':bumped_at'   => $_recruitmentGuild ? $post_time : null,
                ]);
                $new_topic_id = (int)$forumPdo->lastInsertId();
                if ($new_topic_id <= 0) {
                    throw new RuntimeException('Topic creation failed.');
                }

                $stmt = $forumPdo->prepare(
                    "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                     VALUES (:poster, :poster_id, :character_id, :identity_id, :poster_ip, :message, :posted, :topic_id)"
                );
                $stmt->execute([
                    ':poster'      => $user['character_name'],
                    ':poster_id'   => $user['id'],
                    ':character_id' => $user['character_id'],
                    ':identity_id' => $posterIdentityId,
                    ':poster_ip'   => $user['ip'],
                    ':message'     => $message,
                    ':posted'      => $post_time,
                    ':topic_id'    => $new_topic_id,
                ]);
                $new_post_id = (int)$forumPdo->lastInsertId();
                if ($new_post_id <= 0) {
                    throw new RuntimeException('Post creation failed.');
                }

                $stmt = $forumPdo->prepare(
                    "UPDATE f_topics
                     SET last_post = :last_post, last_post_id = :last_post_id, last_poster = :last_poster
                     WHERE topic_id = :topic_id"
                );
                $stmt->execute([
                    ':last_post' => $post_time,
                    ':last_post_id' => $new_post_id,
                    ':last_poster' => $user['character_name'],
                    ':topic_id' => $new_topic_id,
                ]);
                if (function_exists('spp_enforce_topic_view_floor')) {
                    spp_enforce_topic_view_floor($forumPdo, $new_topic_id, 1);
                }

                $stmt = $forumPdo->prepare(
                    "UPDATE f_forums
                     SET num_topics = num_topics + 1, num_posts = num_posts + 1, last_topic_id = :last_topic_id
                     WHERE forum_id = :forum_id"
                );
                $stmt->execute([
                    ':last_topic_id' => $new_topic_id,
                    ':forum_id' => $this_forum['forum_id'],
                ]);

                if (function_exists('spp_increment_forum_unread')) {
                    spp_increment_forum_unread($forumPdo, (int)$this_forum['forum_id'], (int)$user['id']);
                }

                $forumPdo->commit();
                redirect("index.php?n=forum&sub=viewtopic&tid={$new_topic_id}", 1);
            } catch (Throwable $e) {
                if (isset($forumPdo) && $forumPdo instanceof PDO && $forumPdo->inTransaction()) {
                    $forumPdo->rollBack();
                }
                error_log('[forum.post] Topic creation failed: ' . $e->getMessage());
                output_message('alert', 'Topic creation failed.');
                return;
            }
            } // end else (!$canPost)
        }
    }
} elseif ($canPost && $action === 'donewpost' && !empty($this_forum['forum_id']) && !empty($this_topic['topic_id'])) {
    if (!$user['g_reply_other_topics']) {
        output_message('alert', 'You are not authorized to reply to this topic.');
        return;
    }

    if (!empty($_POST['text'])) {
        $message = trim((string)$_POST['text']);

        // Resolve (or lazily create) the poster's character identity.
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
                ':poster'      => $user['character_name'],
                ':poster_id'   => $user['id'],
                ':character_id' => $user['character_id'],
                ':identity_id' => $replyIdentityId,
                ':poster_ip'   => $user['ip'],
                ':message'     => $message,
                ':posted'      => $post_time,
                ':topic_id'    => $this_topic['topic_id'],
            ]);
            $new_post_id = (int)$forumPdo->lastInsertId();
            if ($new_post_id <= 0) {
                throw new RuntimeException('Reply failed.');
            }

            // For recruitment threads, bump last_bumped_at on each reply.
            $_bumpSql = $_isRecruitmentForum && !empty($this_topic['recruitment_status'])
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
                ':last_post'    => $post_time,
                ':last_post_id' => $new_post_id,
                ':last_poster'  => $user['character_name'],
                ':topic_id'     => $this_topic['topic_id'],
            ];
            if ($_bumpSql) {
                $execParams[':bumped_at'] = $post_time;
            }
            $stmt->execute($execParams);
            if (function_exists('spp_enforce_topic_view_floor')) {
                spp_enforce_topic_view_floor($forumPdo, (int)$this_topic['topic_id'], 2);
            }

            $stmt = $forumPdo->prepare(
                "UPDATE f_forums
                 SET num_posts = num_posts + 1, last_topic_id = :last_topic_id
                 WHERE forum_id = :forum_id"
            );
            $stmt->execute([
                ':last_topic_id' => $this_topic['topic_id'],
                ':forum_id' => $this_forum['forum_id'],
            ]);

            if (function_exists('spp_increment_forum_unread')) {
                spp_increment_forum_unread($forumPdo, (int)$this_forum['forum_id'], (int)$user['id']);
            }

            $forumPdo->commit();
            redirect("index.php?n=forum&sub=viewtopic&tid={$this_topic['topic_id']}&to=lastpost", 1);
        } catch (Throwable $e) {
            if (isset($forumPdo) && $forumPdo instanceof PDO && $forumPdo->inTransaction()) {
                $forumPdo->rollBack();
            }
            error_log('[forum.post] Reply creation failed: ' . $e->getMessage());
            output_message('alert', 'Reply failed.');
            return;
        }
    }
}

if (!empty($this_topic['topic_id'])) {
    $forumReadPdo = spp_get_pdo('realmd', $realmId);
    $stmtPosts = $forumReadPdo->prepare(
        "SELECT
            f_posts.*,
            account.*,
            website_accounts.*,
            website_accounts.avatar AS website_avatar,
            website_accounts.signature AS website_signature,
            website_account_groups.*
         FROM f_posts
         LEFT JOIN account ON f_posts.poster_id=account.id
         LEFT JOIN website_accounts ON f_posts.poster_id=website_accounts.account_id
         LEFT JOIN website_account_groups ON website_accounts.g_id = website_account_groups.g_id
         WHERE topic_id=?
         ORDER BY posted"
    );
    $stmtPosts->execute([(int)$this_topic['topic_id']]);
    $result = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

    $charReadPdo = spp_get_pdo('chars', $realmId);
    $posterPostCountCache = array();
    $posterIdentityMeta = array();
    if (!empty($result)) {
        $identityIds = array();
        foreach ($result as $row) {
            $identityId = (int)($row['poster_identity_id'] ?? 0);
            if ($identityId > 0) {
                $identityIds[] = $identityId;
            }
        }

        $identityIds = array_values(array_unique($identityIds));
        if (!empty($identityIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($identityIds), '?'));
                $stmtIdentityMeta = $forumReadPdo->prepare("
                    SELECT identity_id, identity_type, is_bot
                    FROM website_identities
                    WHERE identity_id IN ({$placeholders})
                ");
                $stmtIdentityMeta->execute($identityIds);
                foreach ($stmtIdentityMeta->fetchAll(PDO::FETCH_ASSOC) as $identityRow) {
                    $posterIdentityMeta[(int)$identityRow['identity_id']] = array(
                        'identity_type' => (string)($identityRow['identity_type'] ?? ''),
                        'is_bot' => (int)($identityRow['is_bot'] ?? 0),
                    );
                }
            } catch (Throwable $e) {
                error_log('[forum.post] Identity meta lookup failed: ' . $e->getMessage());
            }
        }
    }
    $postIndex = 0;
    foreach ($result as $cur_post) {
        $cur_post['avatar'] = '';
        $stmtChar = $charReadPdo->prepare(
            "SELECT c.race, c.class, c.level, c.gender, g.name AS guild
             FROM characters c
             LEFT JOIN guild_member gm ON c.guid = gm.guid
             LEFT JOIN guild g ON gm.guildid = g.guildid
             WHERE c.guid = ?"
        );
        $stmtChar->execute([(int)$cur_post['poster_character_id']]);
        $charinfo = $stmtChar->fetch(PDO::FETCH_ASSOC);

        $uploadedAvatar = '';
        if (!empty($cur_post['website_avatar'])) {
            $uploadedAvatar = (string)$cur_post['website_avatar'];
        }

        if ($uploadedAvatar !== '') {
            $cur_post['avatar'] = 'images/avatars/' . rawurlencode(basename($uploadedAvatar));
        }

        if (!empty($cur_post['website_signature'])) {
            $cur_post['signature'] = $cur_post['website_signature'];
        }

        if ($cur_post['avatar'] === '' && !empty($charinfo)) {
            $cur_post['avatar'] = get_character_portrait_path(
                $cur_post['poster_character_id'],
                $charinfo['gender'],
                $charinfo['race'],
                $charinfo['class']
            );
        }

        if (!empty($charinfo)) {
            $cur_post['level'] = $charinfo['level'];
            $cur_post['guild'] = $charinfo['guild'] ?? '';
        } else {
            if ($cur_post['avatar'] === '') {
                $cur_post['avatar'] = get_forum_avatar_fallback($cur_post['poster'] ?? '');
            }
            $cur_post['level'] = 0;
            $cur_post['guild'] = '';
        }

        $posterId = (int)($cur_post['poster_id'] ?? 0);
        $posterIdentityId = (int)($cur_post['poster_identity_id'] ?? 0);
        $identityMeta = $posterIdentityMeta[$posterIdentityId] ?? null;
        $countByIdentity = $posterIdentityId > 0 && !empty($identityMeta)
            && (((int)$identityMeta['is_bot']) === 1 || ($identityMeta['identity_type'] ?? '') === 'bot_character');

        if ($countByIdentity) {
            $cacheKey = 'identity:' . $posterIdentityId;
            if (!isset($posterPostCountCache[$cacheKey])) {
                $stmtPostCount = $forumReadPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_identity_id = ?");
                $stmtPostCount->execute([$posterIdentityId]);
                $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
            }
            $cur_post['forum_post_count'] = $posterPostCountCache[$cacheKey];
        } elseif ($posterId > 0) {
            $cacheKey = 'account:' . $posterId;
            if (!isset($posterPostCountCache[$cacheKey])) {
                $stmtPostCount = $forumReadPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_id = ?");
                $stmtPostCount->execute([$posterId]);
                $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
            }
            $cur_post['forum_post_count'] = $posterPostCountCache[$cacheKey];
        } else {
            $cur_post['forum_post_count'] = 0;
        }

        $postIndex++;
    $cur_post['pos_num'] = $postIndex;

    $rawMessage = (string)($cur_post['message'] ?? '');
    $normalizedMessage = str_replace(
        array('<br />', '<br/>', '<br>'),
        "\n",
        html_entity_decode($rawMessage, ENT_QUOTES, 'UTF-8')
    );
    $cur_post['rendered_message'] = bbcode($normalizedMessage, true, true, true, false);

    $posts[] = $cur_post;
}
}

$is_newtopic = ($action === 'newtopic');
?>
