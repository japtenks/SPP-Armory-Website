<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;
include('forum.func.php');
require_once __DIR__ . '/forum.post.actions.php';
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$post_time = time();
$action = $_GET['action'] ?? '';
$this_post = [];
$this_topic = [];
$this_forum = [];
$posts = [];
$canPost = true;
$posting_block_reason = '';
$forum_post_errors = [];
$forum_post_form = [
    'subject' => trim((string)($_POST['subject'] ?? '')),
    'text' => trim((string)($_POST['text'] ?? '')),
    'posting_character_id' => (int)($_POST['posting_character_id'] ?? 0),
];
$forum_post_mode = ($action === 'newtopic' || $action === 'donewtopic') ? 'newtopic' : 'reply';
$posting_context = [
    'forum_name' => '',
    'forum_scope_label' => '',
    'realm_id' => 0,
    'realm_name' => '',
    'character_name' => '',
    'character_level' => 0,
    'guild_name' => '',
];
$posting_character_options = [];

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}
$cookieRealmId = (int)($_COOKIE['cur_selected_realmd'] ?? ($_COOKIE['cur_selected_realm'] ?? 0));
$realmId = ($cookieRealmId > 0 && isset($realmMap[$cookieRealmId]))
    ? $cookieRealmId
    : spp_resolve_realm_id($realmMap);

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

$realmId = spp_forum_target_realm_id($this_forum, $realmMap, $realmId);
$charDbName = $realmMap[$realmId]['chars'];
$selectedPostingCharacterId = (int)($forum_post_form['posting_character_id'] ?? 0);

foreach (($GLOBALS['characters'] ?? array()) as $character) {
    if ((int)($character['realm_id'] ?? 0) !== $realmId) {
        continue;
    }

    $posting_character_options[] = array(
        'guid' => (int)($character['guid'] ?? 0),
        'name' => (string)($character['name'] ?? ''),
        'level' => (int)($character['level'] ?? 0),
        'guild' => (string)($character['guild_name'] ?? ''),
        'class' => (int)($character['class'] ?? 0),
    );
}

$activeForumCharacter = null;
if ($selectedPostingCharacterId > 0) {
    foreach ($posting_character_options as $postingCharacterOption) {
        if ((int)$postingCharacterOption['guid'] === $selectedPostingCharacterId) {
            $activeForumCharacter = $postingCharacterOption;
            break;
        }
    }
}

if ($activeForumCharacter === null) {
    $activeForumCharacter = resolve_forum_character_for_realm($user, $realmId);
}

if ($activeForumCharacter) {
    $user['character_id'] = (int)$activeForumCharacter['guid'];
    $user['character_name'] = $activeForumCharacter['name'];
    $user['character_realm_id'] = $realmId;
    $forum_post_form['posting_character_id'] = (int)$activeForumCharacter['guid'];
}

$posting_context['forum_name'] = (string)($this_forum['forum_name'] ?? '');
$posting_context['realm_id'] = $realmId;
$posting_context['realm_name'] = (string)(spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId));
$posting_context['character_name'] = (string)($user['character_name'] ?? '');
$posting_context['character_level'] = (int)($activeForumCharacter['level'] ?? 0);

if (($this_forum['scope_type'] ?? '') === 'guild_recruitment') {
    $posting_context['forum_scope_label'] = 'Guild Recruitment';
} elseif (($this_forum['scope_type'] ?? '') === 'expansion') {
    $posting_context['forum_scope_label'] = strtoupper((string)($this_forum['scope_value'] ?? ''));
} elseif (($this_forum['scope_type'] ?? '') === 'realm') {
    $posting_context['forum_scope_label'] = 'Realm ' . (string)($this_forum['scope_value'] ?? $realmId);
}

if ($forum_post_mode === 'newtopic' && empty($this_forum['forum_id'])) {
    $canPost = false;
    $posting_block_reason = 'The forum you are trying to post in was not found.';
    output_message('alert', $posting_block_reason);
} elseif ($forum_post_mode === 'reply' && empty($this_topic['topic_id'])) {
    $canPost = false;
    $posting_block_reason = 'The topic you are trying to reply to was not found.';
    output_message('alert', $posting_block_reason);
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
if (!empty($_recruitmentGuild['name'])) {
    $posting_context['guild_name'] = (string)$_recruitmentGuild['name'];
}

if (spp_forum_handle_topic_moderation($action, $user, $realmId, $this_topic, $this_forum)) {
    return;
}

$topicSubmitResult = spp_forum_handle_new_topic_submission(
    $action,
    $canPost,
    $user,
    $realmId,
    $post_time,
    $this_forum,
    $forum_post_form,
    $forum_post_errors,
    $_isRecruitmentForum,
    $_recruitmentGuild
);
if (!empty($topicSubmitResult['handled'])) {
    $action = $topicSubmitResult['action'];
    $forum_post_mode = $topicSubmitResult['forum_post_mode'];
    $forum_post_errors = $topicSubmitResult['forum_post_errors'];
    if (!empty($topicSubmitResult['stop'])) {
        return;
    }
}

$replySubmitResult = spp_forum_handle_new_reply_submission(
    $action,
    $canPost,
    $user,
    $realmId,
    $post_time,
    $this_forum,
    $this_topic,
    $forum_post_form,
    $forum_post_errors,
    $_isRecruitmentForum
);
if (!empty($replySubmitResult['handled'])) {
    $action = $replySubmitResult['action'];
    $forum_post_mode = $replySubmitResult['forum_post_mode'];
    $forum_post_errors = $replySubmitResult['forum_post_errors'];
    if (!empty($replySubmitResult['stop'])) {
        return;
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
            website_identity_profiles.signature AS identity_signature,
            website_account_groups.*
         FROM f_posts
         LEFT JOIN account ON f_posts.poster_id=account.id
         LEFT JOIN website_accounts ON f_posts.poster_id=website_accounts.account_id
         LEFT JOIN website_identity_profiles ON f_posts.poster_identity_id=website_identity_profiles.identity_id
         LEFT JOIN website_account_groups ON website_accounts.g_id = website_account_groups.g_id
         WHERE topic_id=?
         ORDER BY posted"
    );
    $stmtPosts->execute([(int)$this_topic['topic_id']]);
    $result = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    $charReadPdo = spp_get_pdo('chars', $realmId);
    $posts = spp_forum_hydrate_topic_posts(
        $forumReadPdo,
        $charReadPdo,
        $result,
        $realmId,
        'index.php?n=forum&sub=viewtopic&tid=' . (int)$this_topic['topic_id'],
        0,
        false
    );
}

$is_newtopic = ($action === 'newtopic');
?>
