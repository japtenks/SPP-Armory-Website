<?php
declare(strict_types=1);

if (!defined('SPP_BOT_MAINTENANCE_COMMON')) {
    define('SPP_BOT_MAINTENANCE_COMMON', true);
}

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

if (!function_exists('spp_default_realm_id')) {
    function spp_default_realm_id(array $realmDbMap) {
        return 1;
    }
}

require_once(__DIR__ . '/../config/config-helper.php');
require_once(__DIR__ . '/../components/forum/forum.scope.php');

function bot_maintenance_root_path(): string
{
    return dirname(__DIR__);
}

function bot_maintenance_state_path(): string
{
    return bot_maintenance_root_path() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bot_maintenance_state.json';
}

function bot_maintenance_load_state(): array
{
    $path = bot_maintenance_state_path();
    if (!is_file($path)) {
        return array();
    }

    $contents = @file_get_contents($path);
    if (!is_string($contents) || trim($contents) === '') {
        return array();
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : array();
}

function bot_maintenance_save_state(array $state): bool
{
    $path = bot_maintenance_state_path();
    $directory = dirname($path);
    if (!is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }

    return @file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function bot_maintenance_record_status(array $response): void
{
    $state = bot_maintenance_load_state();
    $state['helper_status'] = array(
        'checked_at' => date('c'),
        'ok' => ((string)($response['status'] ?? '') === 'ok'),
        'summary' => (string)($response['summary'] ?? ''),
        'error' => (string)($response['error'] ?? ''),
        'response' => $response,
        'status_code' => ((string)($response['status'] ?? '') === 'ok') ? 200 : 500,
    );
    bot_maintenance_save_state($state);
}

function bot_maintenance_record_action(string $action, array $response): void
{
    $labels = array(
        'status' => 'Refresh Script Status',
        'reset_forum_realm' => 'Reset Selected Realm Forums',
        'fresh_reset' => 'Fresh Bot World Reset',
        'rebuild_site_layers' => 'Rebuild Bot Website Layers',
    );

    $state = bot_maintenance_load_state();
    $state['last_run'] = array(
        'action' => $action,
        'label' => (string)($labels[$action] ?? $action),
        'ran_at' => date('c'),
        'ok' => ((string)($response['status'] ?? '') === 'ok'),
        'summary' => (string)($response['summary'] ?? ''),
        'error' => (string)($response['error'] ?? ''),
        'response' => $response,
    );
    bot_maintenance_save_state($state);
}

function bot_maintenance_json_response(array $payload, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function bot_maintenance_console_line(string $message = ''): void
{
    echo $message . PHP_EOL;
}

function bot_maintenance_console_output(array $response): void
{
    $status = strtolower((string)($response['status'] ?? 'ok'));
    $step = (string)($response['step'] ?? 'task');
    $summary = trim((string)($response['summary'] ?? ''));

    bot_maintenance_console_line('[' . strtoupper($status) . '] ' . $step);
    if ($summary !== '') {
        bot_maintenance_console_line($summary);
    }

    if (!empty($response['error'])) {
        bot_maintenance_console_line('Error: ' . (string)$response['error']);
    }

    if (!empty($response['preview']) && is_array($response['preview'])) {
        bot_maintenance_console_line('');
        bot_maintenance_console_line('Preview:');
        foreach ($response['preview'] as $key => $value) {
            bot_maintenance_console_line('  ' . str_replace('_', ' ', (string)$key) . ': ' . (is_scalar($value) ? (string)$value : json_encode($value)));
        }
    }

    if (!empty($response['preview_after']) && is_array($response['preview_after'])) {
        bot_maintenance_console_line('');
        bot_maintenance_console_line('After:');
        foreach ($response['preview_after'] as $key => $value) {
            bot_maintenance_console_line('  ' . str_replace('_', ' ', (string)$key) . ': ' . (is_scalar($value) ? (string)$value : json_encode($value)));
        }
    }

    if (!empty($response['plan']) && is_array($response['plan'])) {
        $plan = $response['plan'];
        bot_maintenance_console_line('');
        bot_maintenance_console_line('Plan Preview:');
        if (!empty($plan['preview']) && is_array($plan['preview'])) {
            foreach ($plan['preview'] as $key => $value) {
                bot_maintenance_console_line('  ' . str_replace('_', ' ', (string)$key) . ': ' . (string)$value);
            }
        }
        if (!empty($plan['phases']) && is_array($plan['phases'])) {
            bot_maintenance_console_line('');
            bot_maintenance_console_line('Phases:');
            foreach ($plan['phases'] as $phase) {
                $name = (string)($phase['name'] ?? 'phase');
                $phaseSummary = (string)($phase['summary'] ?? '');
                bot_maintenance_console_line('  - ' . $name . ': ' . $phaseSummary);
            }
        }
    }

    if (!empty($response['recommended_commands']) && is_array($response['recommended_commands'])) {
        bot_maintenance_console_line('');
        bot_maintenance_console_line('Recommended Commands:');
        foreach ($response['recommended_commands'] as $command) {
            bot_maintenance_console_line('  ' . (string)$command);
        }
    }

    if (isset($response['seeded_recruitment_topics']) || isset($response['seeded_recruitment_posts'])) {
        bot_maintenance_console_line('');
        bot_maintenance_console_line('Recruitment Seed:');
        if (isset($response['seeded_recruitment_topics'])) {
            bot_maintenance_console_line('  topics created: ' . (int)$response['seeded_recruitment_topics']);
        }
        if (isset($response['seeded_recruitment_posts'])) {
            bot_maintenance_console_line('  posts created: ' . (int)$response['seeded_recruitment_posts']);
        }
    }

    if (!empty($response['capabilities']) && is_array($response['capabilities'])) {
        bot_maintenance_console_line('');
        bot_maintenance_console_line('Capabilities: ' . implode(', ', array_map('strval', $response['capabilities'])));
    }

    if (array_key_exists('allow_execute', $response)) {
        bot_maintenance_console_line('Execute Enabled: ' . (!empty($response['allow_execute']) ? 'yes' : 'no'));
    }
}

function bot_maintenance_config(): array
{
    return array(
        'token' => trim((string)getenv('SPP_BOT_HELPER_TOKEN')),
        'allow_execute' => true,
    );
}

function bot_maintenance_authorize_or_fail(array $config): void
{
    $expectedToken = (string)($config['token'] ?? '');
    if ($expectedToken === '') {
        return;
    }

    $providedToken = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.+)$/i', (string)$_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $providedToken = trim((string)$matches[1]);
    }

    if ($providedToken !== '' && hash_equals($expectedToken, $providedToken)) {
        return;
    }

    bot_maintenance_json_response(array(
        'status' => 'error',
        'step' => 'authorize',
        'error' => 'Unauthorized helper request.',
    ), 401);
}

function bot_maintenance_parse_cli_args(array $argv): array
{
    $options = array(
        'realm_id' => 0,
        'execute' => false,
        'dry_run' => false,
    );

    foreach (array_slice($argv, 1) as $arg) {
        $arg = (string)$arg;
        if (in_array($arg, array('status', 'reset_forum_realm', 'fresh_reset', 'rebuild_site_layers'), true)) {
            continue;
        }
        if (strpos($arg, '--realm=') === 0) {
            $options['realm_id'] = (int)substr($arg, 8);
            continue;
        }
        if ($arg === '--execute') {
            $options['execute'] = true;
            continue;
        }
        if ($arg === '--dry-run') {
            $options['dry_run'] = true;
            continue;
        }
    }

    return $options;
}

function bot_maintenance_realm_forum_scope(int $realmId): array
{
    $map = array(
        1 => array('forum_id' => 2, 'forum_name' => 'Classic'),
        2 => array('forum_id' => 3, 'forum_name' => 'The Burning Crusade'),
        3 => array('forum_id' => 4, 'forum_name' => 'Wrath of the Lich King'),
    );

    return $map[$realmId] ?? array('forum_id' => 0, 'forum_name' => '');
}

function bot_maintenance_forum_ids_for_realm(int $realmId, PDO $pdo): array
{
    $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
    $mainScope = bot_maintenance_realm_forum_scope($realmId);
    $mainForumId = (int)($mainScope['forum_id'] ?? 0);
    $expansion = function_exists('spp_realm_to_expansion') ? spp_realm_to_expansion($realmId) : '';

    $stmt = $pdo->query("SELECT `forum_id`, `forum_name`, `forum_desc`, `scope_type`, `scope_value` FROM `f_forums` ORDER BY `forum_id`");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();

    $forumIds = array();
    foreach ($rows as $forum) {
        $forumId = (int)($forum['forum_id'] ?? 0);
        $scopeType = (string)($forum['scope_type'] ?? 'all');
        $scopeValue = strtolower(trim((string)($forum['scope_value'] ?? '')));

        if ($forumId === $mainForumId) {
            $forumIds[] = $forumId;
            continue;
        }

        if ($scopeType === 'realm' && (int)$scopeValue === $realmId) {
            $forumIds[] = $forumId;
            continue;
        }

        if ($scopeType === 'expansion' && $scopeValue !== '' && $scopeValue === $expansion) {
            $forumIds[] = $forumId;
            continue;
        }

        if ($scopeType === 'guild_recruitment') {
            $hintRealmId = function_exists('spp_detect_forum_realm_hint')
                ? spp_detect_forum_realm_hint($forum, is_array($realmDbMap) ? $realmDbMap : array(), 0)
                : 0;
            if ($hintRealmId === $realmId) {
                $forumIds[] = $forumId;
                continue;
            }
            if ($scopeValue !== '' && ($scopeValue === (string)$realmId || $scopeValue === $expansion)) {
                $forumIds[] = $forumId;
                continue;
            }
        }

        $hintRealmId = function_exists('spp_detect_forum_realm_hint')
            ? spp_detect_forum_realm_hint($forum, is_array($realmDbMap) ? $realmDbMap : array(), 0)
            : 0;
        if ($hintRealmId === $realmId) {
            $forumIds[] = $forumId;
            continue;
        }
    }

    $forumIds = array_values(array_unique(array_filter(array_map('intval', $forumIds))));
    sort($forumIds);
    return $forumIds;
}

function bot_maintenance_recruitment_forum_ids_for_realm(int $realmId, PDO $pdo): array
{
    $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
    $expansion = function_exists('spp_realm_to_expansion') ? spp_realm_to_expansion($realmId) : '';

    $stmt = $pdo->query("SELECT `forum_id`, `forum_name`, `forum_desc`, `scope_type`, `scope_value` FROM `f_forums` ORDER BY `forum_id`");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();

    $forumIds = array();
    foreach ($rows as $forum) {
        $forumId = (int)($forum['forum_id'] ?? 0);
        $scopeType = (string)($forum['scope_type'] ?? '');
        $scopeValue = strtolower(trim((string)($forum['scope_value'] ?? '')));
        $nameDesc = strtolower(trim((string)($forum['forum_name'] ?? '') . ' ' . (string)($forum['forum_desc'] ?? '')));
        $hintRealmId = function_exists('spp_detect_forum_realm_hint')
            ? spp_detect_forum_realm_hint($forum, is_array($realmDbMap) ? $realmDbMap : array(), 0)
            : 0;

        $looksLikeRecruitment = $scopeType === 'guild_recruitment' || strpos($nameDesc, 'guild recruitment') !== false;
        if (!$looksLikeRecruitment) {
            continue;
        }

        if ($hintRealmId === $realmId || ($scopeValue !== '' && ($scopeValue === (string)$realmId || $scopeValue === $expansion))) {
            $forumIds[] = $forumId;
            continue;
        }

        if ($hintRealmId === $realmId) {
            $forumIds[] = $forumId;
        }
    }

    $forumIds = array_values(array_unique(array_filter(array_map('intval', $forumIds))));
    sort($forumIds);
    return $forumIds;
}

function bot_maintenance_seed_recruitment_guidance(PDO $pdo, int $realmId, array $forumIds): array
{
    if (empty($forumIds)) {
        return array('topics_created' => 0, 'posts_created' => 0);
    }

    $createdTopics = 0;
    $createdPosts = 0;
    $now = time();
    $realmLabel = (string)(function_exists('spp_get_armory_realm_name') ? (spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId)) : ('Realm ' . $realmId));

    $topicTitle = $realmLabel . ' Guild Recruitment Rules & Template';
    $topicBody = implode("\n", array(
        "[b]Welcome to {$realmLabel} Guild Recruitment[/b]",
        '',
        "Use this board for [b]one active recruitment thread per guild[/b].",
        '',
        "[b]Who may post[/b]",
        "- Guild leaders",
        "- Officers with invite rights",
        '',
        "[b]Expected format[/b]",
        "- Guild name",
        "- Faction",
        "- Play times / timezone",
        "- Goals and vibe",
        "- Roles or classes needed",
        "- Contact character",
        '',
        "[b]Rules[/b]",
        "- Keep one main thread for your guild",
        "- Use replies to post roster updates or bumps",
        "- Keep the first post clear and current",
        "- Only guild members with recruitment permissions should post for the guild",
        '',
        "Bot event updates may reply into active recruitment threads, so keeping your main post clean helps the board stay readable.",
    ));

    $checkStmt = $pdo->prepare(
        "SELECT `topic_id`
         FROM `f_topics`
         WHERE `forum_id` = ?
           AND `topic_poster_id` = 0
           AND LOWER(TRIM(`topic_poster`)) IN ('web team', 'spp team')
           AND LOWER(TRIM(`topic_name`)) = LOWER(TRIM(?))
         LIMIT 1"
    );
    $insertTopic = $pdo->prepare(
        "INSERT INTO `f_topics`
           (`topic_poster`, `topic_poster_id`, `topic_name`, `topic_posted`, `last_post`, `last_post_id`, `last_poster`, `num_views`, `num_replies`, `closed`, `sticky`, `redirect_url`, `forum_id`)
         VALUES
           ('web Team', 0, ?, ?, ?, 0, 'web Team', 1, 1, 0, 1, NULL, ?)"
    );
    $insertPost = $pdo->prepare(
        "INSERT INTO `f_posts`
           (`poster`, `poster_id`, `poster_ip`, `poster_character_id`, `message`, `posted`, `edited`, `edited_by`, `topic_id`)
         VALUES
           ('web Team', 0, '::1', 0, ?, ?, NULL, NULL, ?)"
    );
    $updateTopic = $pdo->prepare(
        "UPDATE `f_topics`
         SET `last_post` = ?, `last_post_id` = ?, `last_poster` = 'web Team'
         WHERE `topic_id` = ?"
    );
    $recountForum = $pdo->prepare(
        "UPDATE `f_forums`
         SET `num_topics` = (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = ?),
             `num_posts` = (
                 SELECT COUNT(*) FROM `f_posts` p
                 INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
                 WHERE t.`forum_id` = ?
             ),
             `last_topic_id` = COALESCE(
                 (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = ? ORDER BY `sticky` DESC, `last_post` DESC, `topic_id` DESC LIMIT 1),
                 0
             )
         WHERE `forum_id` = ?"
    );

    foreach ($forumIds as $forumId) {
        $checkStmt->execute(array($forumId, $topicTitle));
        $existingTopicId = (int)($checkStmt->fetchColumn() ?: 0);
        if ($existingTopicId > 0) {
            continue;
        }

        $insertTopic->execute(array($topicTitle, $now, $now, $forumId));
        $topicId = (int)$pdo->lastInsertId();
        if ($topicId <= 0) {
            continue;
        }

        $insertPost->execute(array($topicBody, $now, $topicId));
        $postId = (int)$pdo->lastInsertId();
        if ($postId > 0) {
            $updateTopic->execute(array($now, $postId, $topicId));
            $createdPosts++;
        }

        $recountForum->execute(array($forumId, $forumId, $forumId, $forumId));
        $createdTopics++;
    }

    return array(
        'topics_created' => $createdTopics,
        'posts_created' => $createdPosts,
    );
}

function bot_maintenance_scalar(PDO $pdo, string $sql, array $params = array()): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function bot_maintenance_forum_reset_preview(int $realmId): array
{
    $forumScope = bot_maintenance_realm_forum_scope($realmId);
    $pdo = spp_get_pdo('realmd', 1);
    $forumIds = bot_maintenance_forum_ids_for_realm($realmId, $pdo);

    if (empty($forumIds)) {
        return array(
            'realm_id' => $realmId,
            'forum_id' => 0,
            'forum_ids' => array(),
            'forum_name' => '',
            'topics_total' => 0,
            'posts_total' => 0,
            'preserved_topics' => 0,
            'preserved_posts' => 0,
            'topics_to_delete' => 0,
            'posts_to_delete' => 0,
        );
    }

    $forumId = (int)($forumScope['forum_id'] ?? 0);
    $placeholders = implode(',', array_fill(0, count($forumIds), '?'));

    $topicsTotal = bot_maintenance_scalar($pdo, "SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` IN ({$placeholders})", $forumIds);
    $postsTotal = bot_maintenance_scalar(
        $pdo,
        "SELECT COUNT(*)
         FROM `f_posts` p
         INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
         WHERE t.`forum_id` IN ({$placeholders})",
        $forumIds
    );
    $preservedTopics = bot_maintenance_scalar(
        $pdo,
        "SELECT COUNT(*)
         FROM `f_topics`
         WHERE `forum_id` IN ({$placeholders})
           AND `topic_poster_id` = 0
           AND LOWER(TRIM(`topic_poster`)) IN ('web team', 'spp team')",
        $forumIds
    );
    $preservedPosts = bot_maintenance_scalar(
        $pdo,
        "SELECT COUNT(*)
         FROM `f_posts`
         WHERE `topic_id` IN (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` IN ({$placeholders}))
           AND `poster_id` = 0
           AND (`poster_character_id` IS NULL OR `poster_character_id` = 0)
           AND LOWER(TRIM(`poster`)) IN ('web team', 'spp team')",
        $forumIds
    );

    return array(
        'realm_id' => $realmId,
        'forum_id' => $forumId,
        'forum_ids' => $forumIds,
        'forum_name' => (string)($forumScope['forum_name'] ?? ''),
        'topics_total' => $topicsTotal,
        'posts_total' => $postsTotal,
        'preserved_topics' => $preservedTopics,
        'preserved_posts' => $preservedPosts,
        'topics_to_delete' => max(0, $topicsTotal - $preservedTopics),
        'posts_to_delete' => max(0, $postsTotal - $preservedPosts),
    );
}

function bot_maintenance_reset_forum_realm(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $dryRun = !empty($payload['dry_run']);
    $execute = !empty($payload['execute']) && !$dryRun && !empty($config['allow_execute']);

    $preview = bot_maintenance_forum_reset_preview($realmId);
    $response = array(
        'status' => 'ok',
        'step' => 'reset_forum_realm',
        'summary' => 'Forum reset preview prepared for the selected realm.',
        'execute' => $execute,
        'preview' => $preview,
        'preserve_rules' => array(
            'authors' => array('SPP Team', 'web Team'),
            'require_zero_owner' => true,
        ),
    );

    if ($dryRun) {
        $response['summary'] = 'Dry-run requested. No rows were deleted.';
        return $response;
    }

    if (!$execute) {
        $response['status'] = 'error';
        $response['summary'] = 'Execution was not requested. Use --execute to run this script, or add --dry-run to preview only.';
        return $response;
    }

    $pdo = spp_get_pdo('realmd', 1);
    $forumIds = array_values(array_filter(array_map('intval', (array)($preview['forum_ids'] ?? array()))));
    if (empty($forumIds)) {
        return array(
            'status' => 'error',
            'step' => 'reset_forum_realm',
            'error' => 'No forum mapping exists for that realm.',
        );
    }
    $forumId = (int)($preview['forum_id'] ?? 0);

    $pdo->beginTransaction();
    try {
        $placeholders = implode(',', array_fill(0, count($forumIds), '?'));
        $topicStmt = $pdo->prepare(
            "SELECT `topic_id`
             FROM `f_topics`
             WHERE `forum_id` IN ({$placeholders})
               AND NOT (`topic_poster_id` = 0 AND LOWER(TRIM(`topic_poster`)) IN ('web team', 'spp team'))"
        );
        $topicStmt->execute($forumIds);
        $topicIds = array_map('intval', $topicStmt->fetchAll(PDO::FETCH_COLUMN) ?: array());

        if (!empty($topicIds)) {
            $placeholders = implode(',', array_fill(0, count($topicIds), '?'));
            $deletePosts = $pdo->prepare("DELETE FROM `f_posts` WHERE `topic_id` IN ({$placeholders})");
            $deletePosts->execute($topicIds);
            $deleteTopics = $pdo->prepare("DELETE FROM `f_topics` WHERE `topic_id` IN ({$placeholders})");
            $deleteTopics->execute($topicIds);
        }

        $recount = $pdo->prepare(
            "UPDATE `f_forums`
             SET `num_topics` = (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = ?),
                 `num_posts` = (
                     SELECT COUNT(*) FROM `f_posts` p
                     INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
                     WHERE t.`forum_id` = ?
                 ),
                 `last_topic_id` = COALESCE(
                     (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = ? ORDER BY `last_post` DESC, `topic_id` DESC LIMIT 1),
                     0
                 )
             WHERE `forum_id` = ?"
        );
        foreach ($forumIds as $recountForumId) {
            $recount->execute(array($recountForumId, $recountForumId, $recountForumId, $recountForumId));
        }

        $seededRecruitment = bot_maintenance_seed_recruitment_guidance(
            $pdo,
            $realmId,
            bot_maintenance_recruitment_forum_ids_for_realm($realmId, $pdo)
        );
        $pdo->commit();

        $response['summary'] = 'Selected realm forum reset completed.';
        $response['deleted_topics'] = count($topicIds);
        $response['seeded_recruitment_topics'] = (int)($seededRecruitment['topics_created'] ?? 0);
        $response['seeded_recruitment_posts'] = (int)($seededRecruitment['posts_created'] ?? 0);
        $response['preview_after'] = bot_maintenance_forum_reset_preview($realmId);
        return $response;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return array(
            'status' => 'error',
            'step' => 'reset_forum_realm',
            'error' => $e->getMessage(),
        );
    }
}

function bot_maintenance_fresh_reset_plan(int $realmId): array
{
    $charsPdo = spp_get_pdo('chars', $realmId);
    $masterPdo = spp_get_pdo('realmd', 1);
    $realmdConfig = spp_get_db_config('realmd', $realmId);
    $realmdDbName = '`' . str_replace('`', '``', (string)$realmdConfig['name']) . '`';
    $botAccountSubquery = "SELECT `id` FROM {$realmdDbName}.`account` WHERE LOWER(`username`) LIKE 'rndbot%'";

    $preview = array(
        'bot_accounts' => bot_maintenance_scalar($masterPdo, "SELECT COUNT(*) FROM `account` WHERE LOWER(`username`) LIKE 'rndbot%'"),
        'bot_characters' => bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `characters` WHERE `account` IN ({$botAccountSubquery})"),
        'bot_guild_memberships' => bot_maintenance_scalar(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `guild_member` gm
             INNER JOIN `characters` c ON c.`guid` = gm.`guid`
             WHERE c.`account` IN ({$botAccountSubquery})"
        ),
        'bot_db_store_rows' => bot_maintenance_scalar(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `ai_playerbot_db_store` s
             INNER JOIN `characters` c ON c.`guid` = s.`guid`
             WHERE c.`account` IN ({$botAccountSubquery})"
        ),
        'website_bot_events' => bot_maintenance_scalar($masterPdo, "SELECT COUNT(*) FROM `website_bot_events`"),
    );

    return array(
        'realm_id' => $realmId,
        'preview' => $preview,
        'phases' => array(
            array(
                'name' => 'reset_forum_realm',
                'summary' => 'Delete selected realm forum topics/posts except preserved official seed content.',
                'preview' => bot_maintenance_forum_reset_preview($realmId),
            ),
            array(
                'name' => 'clear_bot_web_state',
                'summary' => 'Clear website bot event pipeline rows, bot-linked identities/profile rows, and bot-facing caches.',
                'tables' => array('website_bot_events', 'website_identities', 'website_identity_profiles', 'templates/offlike/cache/portraits'),
            ),
            array(
                'name' => 'clear_bot_character_state',
                'summary' => 'Clear bot DB-store rows and realm-side bot character/guild data keyed by rndbot accounts.',
                'tables' => array('ai_playerbot_db_store', 'guild_member', 'guild', 'characters'),
            ),
            array(
                'name' => 'host_repopulate',
                'summary' => 'Host restart/repopulation still needs the server-side bridge or manual host steps.',
                'host_steps' => array(
                    'Reset bot-side world state for the target realm.',
                    'Restart world service for the target realm.',
                    'Allow bot auto-create to repopulate new GUIDs.',
                ),
            ),
        ),
    );
}

function bot_maintenance_fresh_reset(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $dryRun = !empty($payload['dry_run']);
    $execute = !empty($payload['execute']) && !$dryRun && !empty($config['allow_execute']);

    return array(
        'status' => 'ok',
        'step' => 'fresh_reset',
        'summary' => $dryRun
            ? 'Dry-run requested. Full execution still needs the host restart / repopulation bridge.'
            : ($execute
                ? 'Fresh reset execution is still partially scaffolded. Review the phase plan, then handle the host restart / repopulation steps separately.'
                : 'Execution was not requested. Use --execute to run this script, or add --dry-run to preview only.'),
        'execute' => $execute,
        'plan' => bot_maintenance_fresh_reset_plan($realmId),
    );
}

function bot_maintenance_rebuild_site_layers(array $payload): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);

    return array(
        'status' => 'ok',
        'step' => 'rebuild_site_layers',
        'summary' => 'Recommended rebuild commands prepared for the selected realm.',
        'recommended_commands' => array(
            'php tools/backfill_identities.php --realm=' . $realmId,
            'php tools/backfill_post_identities.php --realm=' . $realmId,
            'php tools/backfill_pm_identities.php --realm=' . $realmId,
        ),
    );
}

function bot_maintenance_status(array $config): array
{
    return array(
        'status' => 'ok',
        'step' => 'status',
        'summary' => 'Bot maintenance scripts are reachable.',
        'capabilities' => array(
            'status',
            'reset_forum_realm',
            'fresh_reset',
            'rebuild_site_layers',
        ),
        'allow_execute' => !empty($config['allow_execute']),
        'forum_mapping' => array(
            'classic' => 2,
            'tbc' => 3,
            'wotlk' => 4,
        ),
    );
}
