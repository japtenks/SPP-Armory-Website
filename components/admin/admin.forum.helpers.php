<?php
if (INCLUDED !== true) {
    exit;
}

if (!function_exists('spp_admin_forum_action_url')) {
    function spp_admin_forum_action_url(array $params)
    {
        return spp_action_url('index.php', $params, 'admin_forum');
    }
}

if (!function_exists('spp_admin_forum_filter_category_fields')) {
    function spp_admin_forum_filter_category_fields(array $data)
    {
        $allowed = array('cat_name', 'cat_disp_position');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

if (!function_exists('spp_admin_forum_filter_forum_fields')) {
    function spp_admin_forum_filter_forum_fields(array $data)
    {
        $allowed = array('cat_id', 'forum_name', 'forum_desc', 'disp_position');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

function spp_admin_forum_recount(PDO $pdo, int $forumId)
{
    $stmt = $pdo->prepare("SELECT count(*) FROM f_topics WHERE forum_id=?");
    $stmt->execute([$forumId]);
    $topicCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT count(*) FROM f_topics RIGHT JOIN f_posts ON f_topics.topic_id=f_posts.topic_id WHERE forum_id=?");
    $stmt->execute([$forumId]);
    $postCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT topic_id FROM f_topics WHERE forum_id=? ORDER BY last_post DESC LIMIT 1");
    $stmt->execute([$forumId]);
    $lastTopicId = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE f_forums SET num_topics=?,num_posts=?,last_topic_id=? WHERE forum_id=? LIMIT 1");
    $stmt->execute([$topicCount, $postCount, $lastTopicId, $forumId]);
}

function spp_admin_forum_move_up(PDO $pdo, int $catId, int $forumId = 0)
{
    if ($forumId > 0) {
        $stmt = $pdo->prepare("SELECT disp_position FROM f_forums WHERE forum_id=?");
        $stmt->execute([$forumId]);
        $currentPosition = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE disp_position<? AND cat_id=? ORDER BY disp_position DESC LIMIT 1");
        $stmt->execute([$currentPosition, $catId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($target['forum_id'])) {
            return;
        }

        $stmt = $pdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$target['disp_position'], $forumId]);
        $stmt->execute([$currentPosition, (int)$target['forum_id']]);
        return;
    }

    $stmt = $pdo->prepare("SELECT cat_disp_position FROM f_categories WHERE cat_id=?");
    $stmt->execute([$catId]);
    $currentPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM f_categories WHERE cat_disp_position<? ORDER BY cat_disp_position DESC LIMIT 1");
    $stmt->execute([$currentPosition]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($target['cat_id'])) {
        return;
    }

    $stmt = $pdo->prepare("UPDATE f_categories SET cat_disp_position=? WHERE cat_id=? LIMIT 1");
    $stmt->execute([$target['cat_disp_position'], $catId]);
    $stmt->execute([$currentPosition, (int)$target['cat_id']]);
}

function spp_admin_forum_move_down(PDO $pdo, int $catId, int $forumId = 0)
{
    if ($forumId > 0) {
        $stmt = $pdo->prepare("SELECT disp_position FROM f_forums WHERE forum_id=?");
        $stmt->execute([$forumId]);
        $currentPosition = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE disp_position>? AND cat_id=? ORDER BY disp_position ASC LIMIT 1");
        $stmt->execute([$currentPosition, $catId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($target['forum_id'])) {
            return;
        }

        $stmt = $pdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$target['disp_position'], $forumId]);
        $stmt->execute([$currentPosition, (int)$target['forum_id']]);
        return;
    }

    $stmt = $pdo->prepare("SELECT cat_disp_position FROM f_categories WHERE cat_id=?");
    $stmt->execute([$catId]);
    $currentPosition = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM f_categories WHERE cat_disp_position>? ORDER BY cat_disp_position ASC LIMIT 1");
    $stmt->execute([$currentPosition]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($target['cat_id'])) {
        return;
    }

    $stmt = $pdo->prepare("UPDATE f_categories SET cat_disp_position=? WHERE cat_id=? LIMIT 1");
    $stmt->execute([$target['cat_disp_position'], $catId]);
    $stmt->execute([$currentPosition, (int)$target['cat_id']]);
}

function spp_admin_forum_delete_forum(PDO $pdo, int $forumId)
{
    $stmt = $pdo->prepare("SELECT topic_id FROM f_topics WHERE forum_id=?");
    $stmt->execute([$forumId]);
    $forumTopics = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!empty($forumTopics)) {
        $placeholders = implode(',', array_fill(0, count($forumTopics), '?'));
        $stmt = $pdo->prepare("DELETE FROM f_posts WHERE topic_id IN ($placeholders)");
        $stmt->execute(array_map('intval', $forumTopics));
    }

    $stmt = $pdo->prepare("DELETE FROM f_topics WHERE forum_id=?");
    $stmt->execute([$forumId]);

    $stmt = $pdo->prepare("DELETE FROM f_forums WHERE forum_id=?");
    $stmt->execute([$forumId]);
}

function spp_admin_forum_delete_category(PDO $pdo, int $catId)
{
    $stmt = $pdo->prepare("SELECT forum_id FROM f_forums WHERE cat_id=?");
    $stmt->execute([$catId]);
    $forumIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($forumIds as $forumId) {
        spp_admin_forum_delete_forum($pdo, (int)$forumId);
    }

    $stmt = $pdo->prepare("DELETE FROM f_categories WHERE cat_id=?");
    $stmt->execute([$catId]);
}
