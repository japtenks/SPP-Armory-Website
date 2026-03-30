<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_forum_build_view(PDO $forumPdo, array $lang)
{
    $view = array(
        'items' => array(),
        'this_forum' => null,
        'this_topic' => null,
        'pathway_info' => array(
            array('title' => $lang['forums_manage'], 'link' => 'index.php?n=admin&sub=forum'),
        ),
    );

    $catId = (int)($_GET['cat_id'] ?? 0);
    $forumId = (int)($_GET['forum_id'] ?? 0);
    $topicId = (int)($_GET['topic_id'] ?? 0);

    if ($catId > 0) {
        $stmt = $forumPdo->prepare("
            SELECT * FROM f_forums
            JOIN f_categories ON f_forums.cat_id=f_categories.cat_id
            WHERE f_forums.cat_id=? ORDER BY disp_position,forum_name");
        $stmt->execute([$catId]);
        $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($view['items'])) {
            $view['pathway_info'][] = array('title' => $view['items'][0]['cat_name'], 'link' => '');
        }
        return $view;
    }

    if ($forumId > 0 && $topicId > 0) {
        $stmt = $forumPdo->prepare("SELECT * FROM f_topics WHERE topic_id=? LIMIT 1");
        $stmt->execute([$topicId]);
        $view['this_topic'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $forumPdo->prepare("SELECT * FROM f_forums WHERE forum_id=? LIMIT 1");
        $stmt->execute([$forumId]);
        $view['this_forum'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $forumPdo->prepare("SELECT post_id, poster, posted, LEFT(message,120) AS excerpt FROM f_posts WHERE topic_id=? ORDER BY posted");
        $stmt->execute([$topicId]);
        $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($view['this_forum'])) {
            $view['pathway_info'][] = array('title' => $view['this_forum']['forum_name'], 'link' => 'index.php?n=admin&sub=forum&forum_id=' . $forumId);
        }
        if (!empty($view['this_topic'])) {
            $view['pathway_info'][] = array('title' => $view['this_topic']['topic_name'], 'link' => '');
        }
        return $view;
    }

    if ($forumId > 0) {
        $stmt = $forumPdo->prepare("SELECT * FROM f_forums WHERE forum_id=? LIMIT 1");
        $stmt->execute([$forumId]);
        $view['this_forum'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $forumPdo->prepare("SELECT topic_id, topic_name, topic_poster, topic_posted, num_replies FROM f_topics WHERE forum_id=? ORDER BY topic_posted DESC");
        $stmt->execute([$forumId]);
        $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($view['this_forum'])) {
            $view['pathway_info'][] = array('title' => $view['this_forum']['forum_name'], 'link' => '');
        }
        return $view;
    }

    $view['pathway_info'][] = array('title' => $lang['categories'], 'link' => '');
    $stmt = $forumPdo->query("SELECT * FROM f_categories ORDER BY cat_disp_position,cat_name");
    $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $view;
}
