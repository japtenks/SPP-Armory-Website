<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;
include('forum.func.php');

$post_time = time();
$action = $_GET['action'] ?? '';
$this_post = [];
$this_topic = [];
$this_forum = [];

// Load context
if (!empty($_GET['post'])) {
    $this_post = get_post_byid($_GET['post']);
    if (!empty($this_post['topic_id'])) $_GET['t'] = $this_post['topic_id'];
}
if (!empty($_GET['t'])) {
    $this_topic = get_topic_byid($_GET['t']);
    if (!empty($this_topic['forum_id'])) $_GET['f'] = $this_topic['forum_id'];
}
if (!empty($_GET['f'])) {
    $this_forum = get_forum_byid($_GET['f']);
}

// Security
if (!isValidChar($user)) {
    output_message('alert', 'You must select a character before posting');
    return;
}
if ($user['id'] <= 0) return;

// === NEW TOPIC ===
if ($action === 'donewtopic' && !empty($this_forum['forum_id'])) {
    if (($user['g_post_new_topics'] == 1 && !$this_forum['closed']) || $user['g_forum_moderate'] == 1) {
        if (!empty($_POST['subject']) && !empty($_POST['text'])) {
            $message = my_preview($_POST['text']);

            $DB->query(
                "INSERT INTO tbcrealmd.f_topics (topic_poster, topic_poster_id, topic_name, topic_posted, forum_id)
                 VALUES (?,?,?,?,?)",
                $user['character_name'], $user['id'], htmlspecialchars($_POST['subject']), $post_time, $this_forum['forum_id']
            );
            $new_topic_id = $DB->insert_id();
            if (!$new_topic_id) { output_message('alert', 'Topic creation failed.'); return; }

            $DB->query(
                "INSERT INTO tbcrealmd.f_posts (poster, poster_id, poster_character_id, poster_ip, message, posted, topic_id)
                 VALUES (?,?,?,?,?,?,?)",
                $user['character_name'], $user['id'], $user['character_id'], $user['ip'], $message, $post_time, $new_topic_id
            );
            $new_post_id = $DB->insert_id();
            if (!$new_post_id) { output_message('alert', 'Post creation failed.'); return; }

            $DB->query("UPDATE tbcrealmd.f_topics SET last_post=?, last_post_id=?, last_poster=? WHERE topic_id=?",
                $post_time, $new_post_id, $user['character_name'], $new_topic_id);
            $DB->query("UPDATE tbcrealmd.f_forums SET num_topics=num_topics+1, num_posts=num_posts+1, last_topic_id=? WHERE forum_id=?",
                $new_topic_id, $this_forum['forum_id']);

            redirect("index.php?n=forum&sub=viewtopic&tid={$new_topic_id}", 1);
        }
    }

// === REPLY ===
} elseif ($action === 'donewpost' && !empty($this_forum['forum_id']) && !empty($this_topic['topic_id'])) {
    if (!$user['g_reply_other_topics']) {
        output_message('alert', 'You are not authorized to reply to this topic.');
        return;
    }

    if (!empty($_POST['text'])) {
        $message = my_preview($_POST['text']);

        $DB->query(
            "INSERT INTO tbcrealmd.f_posts (poster, poster_id, poster_character_id, poster_ip, message, posted, topic_id)
             VALUES (?,?,?,?,?,?,?)",
            $user['character_name'], $user['id'], $user['character_id'], $user['ip'], $message, $post_time, $this_topic['topic_id']
        );
        $new_post_id = $DB->insert_id();
        if (!$new_post_id) { output_message('alert', 'Reply failed.'); return; }

        $DB->query("UPDATE tbcrealmd.f_topics SET last_post=?, last_post_id=?, last_poster=?, num_replies=num_replies+1 WHERE topic_id=?",
            $post_time, $new_post_id, $user['character_name'], $this_topic['topic_id']);
        $DB->query("UPDATE tbcrealmd.f_forums SET num_posts=num_posts+1, last_topic_id=? WHERE forum_id=?",
            $this_topic['topic_id'], $this_forum['forum_id']);

        redirect("index.php?n=forum&sub=viewtopic&tid={$this_topic['topic_id']}&to=lastpost", 1);
    }
}

// === LOAD FORM ===
$is_newtopic = ($action === 'newtopic');
require_once('forum/templates/post_form.php');
?>
