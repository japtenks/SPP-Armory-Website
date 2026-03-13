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
$realmId = spp_resolve_realm_id($realmMap);
$charDbName = $realmMap[$realmId]['chars'];
$activeForumCharacter = resolve_forum_character_for_realm($user, $realmId);

if ($activeForumCharacter) {
    $user['character_id'] = (int)$activeForumCharacter['guid'];
    $user['character_name'] = $activeForumCharacter['name'];

    if (!empty($user['id'])) {
        setcookie('cur_selected_character', $user['character_id'], time() + 86400, '/');
        setcookie('cur_selected_realm', $realmId, time() + 86400, '/');
        setcookie('cur_selected_realmd', $realmId, time() + 86400, '/');
        $DB->query(
            "UPDATE website_accounts SET character_id=?d, character_name=? WHERE account_id=?d",
            $user['character_id'],
            $user['character_name'],
            $user['id']
        );
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

if ($user['id'] <= 0) {
    $canPost = false;
    $posting_block_reason = 'You must be logged in to post.';
} elseif (!isValidChar($user, $realmId)) {
    $canPost = false;
    $posting_block_reason = 'You must select a valid character before posting. This account currently has no available character loaded for the selected realm.';
    output_message('alert', $posting_block_reason);
}

if ($canPost && $action === 'donewtopic' && !empty($this_forum['forum_id'])) {
    if (($user['g_post_new_topics'] == 1 && !$this_forum['closed']) || $user['g_forum_moderate'] == 1) {
        if (!empty($_POST['subject']) && !empty($_POST['text'])) {
            $message = my_preview($_POST['text']);

            try {
                $forumPdo = spp_get_pdo('realmd', $realmId);
                $forumPdo->beginTransaction();

                $stmt = $forumPdo->prepare(
                    "INSERT INTO f_topics (topic_poster, topic_poster_id, topic_name, topic_posted, forum_id)
                     VALUES (:poster, :poster_id, :topic_name, :topic_posted, :forum_id)"
                );
                $stmt->execute([
                    ':poster' => $user['character_name'],
                    ':poster_id' => $user['id'],
                    ':topic_name' => htmlspecialchars($_POST['subject']),
                    ':topic_posted' => $post_time,
                    ':forum_id' => $this_forum['forum_id'],
                ]);
                $new_topic_id = (int)$forumPdo->lastInsertId();
                if ($new_topic_id <= 0) {
                    throw new RuntimeException('Topic creation failed.');
                }

                $stmt = $forumPdo->prepare(
                    "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_ip, message, posted, topic_id)
                     VALUES (:poster, :poster_id, :character_id, :poster_ip, :message, :posted, :topic_id)"
                );
                $stmt->execute([
                    ':poster' => $user['character_name'],
                    ':poster_id' => $user['id'],
                    ':character_id' => $user['character_id'],
                    ':poster_ip' => $user['ip'],
                    ':message' => $message,
                    ':posted' => $post_time,
                    ':topic_id' => $new_topic_id,
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

                $stmt = $forumPdo->prepare(
                    "UPDATE f_forums
                     SET num_topics = num_topics + 1, num_posts = num_posts + 1, last_topic_id = :last_topic_id
                     WHERE forum_id = :forum_id"
                );
                $stmt->execute([
                    ':last_topic_id' => $new_topic_id,
                    ':forum_id' => $this_forum['forum_id'],
                ]);

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
        }
    }
} elseif ($canPost && $action === 'donewpost' && !empty($this_forum['forum_id']) && !empty($this_topic['topic_id'])) {
    if (!$user['g_reply_other_topics']) {
        output_message('alert', 'You are not authorized to reply to this topic.');
        return;
    }

    if (!empty($_POST['text'])) {
        $message = my_preview($_POST['text']);

        try {
            $forumPdo = spp_get_pdo('realmd', $realmId);
            $forumPdo->beginTransaction();

            $stmt = $forumPdo->prepare(
                "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_ip, message, posted, topic_id)
                 VALUES (:poster, :poster_id, :character_id, :poster_ip, :message, :posted, :topic_id)"
            );
            $stmt->execute([
                ':poster' => $user['character_name'],
                ':poster_id' => $user['id'],
                ':character_id' => $user['character_id'],
                ':poster_ip' => $user['ip'],
                ':message' => $message,
                ':posted' => $post_time,
                ':topic_id' => $this_topic['topic_id'],
            ]);
            $new_post_id = (int)$forumPdo->lastInsertId();
            if ($new_post_id <= 0) {
                throw new RuntimeException('Reply failed.');
            }

            $stmt = $forumPdo->prepare(
                "UPDATE f_topics
                 SET last_post = :last_post, last_post_id = :last_post_id, last_poster = :last_poster, num_replies = num_replies + 1
                 WHERE topic_id = :topic_id"
            );
            $stmt->execute([
                ':last_post' => $post_time,
                ':last_post_id' => $new_post_id,
                ':last_poster' => $user['character_name'],
                ':topic_id' => $this_topic['topic_id'],
            ]);

            $stmt = $forumPdo->prepare(
                "UPDATE f_forums
                 SET num_posts = num_posts + 1, last_topic_id = :last_topic_id
                 WHERE forum_id = :forum_id"
            );
            $stmt->execute([
                ':last_topic_id' => $this_topic['topic_id'],
                ':forum_id' => $this_forum['forum_id'],
            ]);

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
    $result = $DB->select(
        "SELECT * FROM f_posts
         LEFT JOIN account ON f_posts.poster_id=account.id
         LEFT JOIN website_accounts ON f_posts.poster_id=website_accounts.account_id
         LEFT JOIN website_account_groups ON website_accounts.g_id = website_account_groups.g_id
         WHERE topic_id=?d
         ORDER BY posted",
        $this_topic['topic_id']
    );

    $postIndex = 0;
    foreach ($result as $cur_post) {
        $charinfo = $DB->selectRow(
            "SELECT c.race, c.class, c.level, c.gender, g.name AS guild
             FROM {$charDbName}.characters c
             LEFT JOIN {$charDbName}.guild_member gm ON c.guid = gm.guid
             LEFT JOIN {$charDbName}.guild g ON gm.guildid = g.guildid
             WHERE c.guid = ?d",
            $cur_post['poster_character_id']
        );

        if (!empty($charinfo)) {
            $cur_post['avatar'] = get_character_portrait_path(
                $cur_post['poster_character_id'],
                $charinfo['gender'],
                $charinfo['race'],
                $charinfo['class']
            );
            $cur_post['level'] = $charinfo['level'];
            $cur_post['guild'] = $charinfo['guild'] ?? '';
        } else {
            $cur_post['avatar'] = '/templates/offlike/images/forum/icons/lock-icon.gif';
            $cur_post['level'] = 0;
            $cur_post['guild'] = '';
        }

        $postIndex++;
        $cur_post['pos_num'] = $postIndex;
        $posts[] = $cur_post;
    }
}

$is_newtopic = ($action === 'newtopic');
?>
