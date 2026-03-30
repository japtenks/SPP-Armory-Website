<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_forum_handle_action(PDO $forumPdo, $mw)
{
    $action = (string)($_GET['action'] ?? '');
    if ($action === '' || $action === '0') {
        return;
    }

    if ($action === 'moveup') {
        spp_require_csrf('admin_forum');
        spp_admin_forum_move_up($forumPdo, (int)($_GET['cat_id'] ?? 0), (int)($_GET['forum_id'] ?? 0));
        redirect($mw->getConfig->temp->site_href . "index.php?n=admin&sub=forum", 1);
        exit;
    }

    if ($action === 'movedown') {
        spp_require_csrf('admin_forum');
        spp_admin_forum_move_down($forumPdo, (int)($_GET['cat_id'] ?? 0), (int)($_GET['forum_id'] ?? 0));
        redirect($mw->getConfig->temp->site_href . "index.php?n=admin&sub=forum", 1);
        exit;
    }

    if ($action === 'open' || $action === 'close') {
        spp_require_csrf('admin_forum');
        $closed = $action === 'close' ? 1 : 0;
        $stmt = $forumPdo->prepare("UPDATE f_forums SET closed=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$closed, (int)($_GET['forum_id'] ?? 0)]);
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'show' || $action === 'hide') {
        spp_require_csrf('admin_forum');
        $hidden = $action === 'hide' ? 1 : 0;
        $stmt = $forumPdo->prepare("UPDATE f_forums SET hidden=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$hidden, (int)($_GET['forum_id'] ?? 0)]);
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'updforumsorder') {
        spp_require_csrf('admin_forum');
        $stmt = $forumPdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
        foreach (($_POST['forumorder'] ?? array()) as $forumId => $order) {
            $stmt->execute([(int)$order, (int)$forumId]);
        }
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'newcat') {
        spp_require_csrf('admin_forum');
        $data = spp_admin_forum_filter_category_fields($_POST);
        if (!empty($data)) {
            $setClause = implode(',', array_map(function ($k) {
                return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
            }, array_keys($data)));
            $stmt = $forumPdo->prepare("INSERT INTO f_categories SET $setClause");
            $stmt->execute(array_values($data));
        }
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'renamecat') {
        spp_require_csrf('admin_forum');
        $catId = (int)($_POST['cat_id'] ?? 0);
        $catName = trim((string)($_POST['cat_name'] ?? ''));
        if ($catId > 0 && $catName !== '') {
            $stmt = $forumPdo->prepare("UPDATE f_categories SET cat_name=? WHERE cat_id=? LIMIT 1");
            $stmt->execute([$catName, $catId]);
        }
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'newforum') {
        spp_require_csrf('admin_forum');
        $data = spp_admin_forum_filter_forum_fields($_POST);
        if (!empty($data)) {
            $setClause = implode(',', array_map(function ($k) {
                return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
            }, array_keys($data)));
            $stmt = $forumPdo->prepare("INSERT INTO f_forums SET $setClause");
            $stmt->execute(array_values($data));
        }
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'renameforum') {
        spp_require_csrf('admin_forum');
        $forumId = (int)($_POST['forum_id'] ?? 0);
        $forumName = trim((string)($_POST['forum_name'] ?? ''));
        if ($forumId > 0 && $forumName !== '') {
            $stmt = $forumPdo->prepare("UPDATE f_forums SET forum_name=? WHERE forum_id=? LIMIT 1");
            $stmt->execute([$forumName, $forumId]);
        }
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'recount') {
        spp_require_csrf('admin_forum');
        spp_admin_forum_recount($forumPdo, (int)($_GET['forum_id'] ?? 0));
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'deleteforum') {
        spp_require_csrf('admin_forum');
        spp_admin_forum_delete_forum($forumPdo, (int)($_GET['forum_id'] ?? 0));
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'deletecat') {
        spp_require_csrf('admin_forum');
        spp_admin_forum_delete_category($forumPdo, (int)($_GET['cat_id'] ?? 0));
        redirect($_SERVER['HTTP_REFERER'], 1);
        exit;
    }

    if ($action === 'deletetopic') {
        spp_require_csrf('admin_forum');
        $topicId = (int)($_GET['topic_id'] ?? 0);
        $forumId = (int)($_GET['forum_id'] ?? 0);
        $stmt = $forumPdo->prepare("SELECT num_replies FROM f_topics WHERE topic_id=? LIMIT 1");
        $stmt->execute([$topicId]);
        $numReplies = (int)$stmt->fetchColumn();

        $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE topic_id=?");
        $stmt->execute([$topicId]);
        $stmt = $forumPdo->prepare("DELETE FROM f_topics WHERE topic_id=? LIMIT 1");
        $stmt->execute([$topicId]);
        $stmt = $forumPdo->prepare("UPDATE f_forums SET num_topics=GREATEST(0,num_topics-1), num_posts=GREATEST(0,num_posts-(?+1)) WHERE forum_id=? LIMIT 1");
        $stmt->execute([$numReplies, $forumId]);
        redirect('index.php?n=admin&sub=forum&forum_id=' . $forumId, 1);
        exit;
    }

    if ($action === 'deletepost') {
        spp_require_csrf('admin_forum');
        $postId = (int)($_GET['post_id'] ?? 0);
        $topicId = (int)($_GET['topic_id'] ?? 0);
        $forumId = (int)($_GET['forum_id'] ?? 0);

        $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE post_id=? LIMIT 1");
        $stmt->execute([$postId]);
        $stmt = $forumPdo->prepare("UPDATE f_topics SET num_replies=GREATEST(0,num_replies-1) WHERE topic_id=? LIMIT 1");
        $stmt->execute([$topicId]);
        $stmt = $forumPdo->prepare("UPDATE f_forums SET num_posts=GREATEST(0,num_posts-1) WHERE forum_id=? LIMIT 1");
        $stmt->execute([$forumId]);
        redirect('index.php?n=admin&sub=forum&forum_id=' . $forumId . '&topic_id=' . $topicId, 1);
        exit;
    }
}
