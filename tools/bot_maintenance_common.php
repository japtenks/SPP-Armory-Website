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
        'clear_bot_web_state' => 'Clear Bot Web State',
        'reset_bot_rotation_realm' => 'Reset Bot Rotation Realm',
        'clear_bot_character_state' => 'Clear Bot Character State',
        'clear_realm_character_state' => 'Clear Realm Character State',
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
        if (in_array($arg, array('status', 'reset_forum_realm', 'clear_bot_web_state', 'reset_bot_rotation_realm', 'clear_bot_character_state', 'clear_realm_character_state', 'fresh_reset', 'rebuild_site_layers'), true)) {
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

function bot_maintenance_table_exists(PDO $pdo, string $table): bool
{
    static $cache = array();
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $key = strtolower($dbName . ':' . $table);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $stmt->execute(array($table));
    $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    return $cache[$key];
}

function bot_maintenance_column_exists(PDO $pdo, string $table, string $column): bool
{
    static $cache = array();
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $key = strtolower($dbName . ':' . $table . ':' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute(array($table, $column));
    $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    return $cache[$key];
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
    $preview = array(
        'bot_accounts' => bot_maintenance_scalar(spp_get_pdo('realmd', 1), "SELECT COUNT(*) FROM `account` WHERE LOWER(`username`) LIKE 'rndbot%'"),
        'bot_characters' => (int)(bot_maintenance_clear_bot_character_state_preview($realmId)['bot_characters'] ?? 0),
        'bot_guild_memberships' => (int)(bot_maintenance_clear_bot_character_state_preview($realmId)['bot_guild_memberships'] ?? 0),
        'bot_db_store_rows' => (int)(bot_maintenance_clear_bot_character_state_preview($realmId)['bot_db_store_rows'] ?? 0),
        'website_bot_events' => (int)(bot_maintenance_clear_bot_web_state_preview($realmId)['website_bot_events'] ?? 0),
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

function bot_maintenance_count_portrait_files(): int
{
    $portraitDir = bot_maintenance_root_path() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'offlike' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'portraits';
    if (!is_dir($portraitDir)) {
        return 0;
    }

    $files = glob($portraitDir . DIRECTORY_SEPARATOR . '*');
    if (!is_array($files)) {
        return 0;
    }

    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            $count++;
        }
    }
    return $count;
}

function bot_maintenance_delete_portrait_files(): int
{
    $portraitDir = bot_maintenance_root_path() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'offlike' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'portraits';
    if (!is_dir($portraitDir)) {
        return 0;
    }

    $files = glob($portraitDir . DIRECTORY_SEPARATOR . '*');
    if (!is_array($files)) {
        return 0;
    }

    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file) && @unlink($file)) {
            $deleted++;
        }
    }

    return $deleted;
}

function bot_maintenance_clear_bot_web_state_preview(int $realmId): array
{
    $masterPdo = spp_get_pdo('realmd', 1);
    $preview = array(
        'realm_id' => $realmId,
        'website_bot_events' => 0,
        'bot_identities' => 0,
        'bot_identity_profiles' => 0,
        'portrait_files' => bot_maintenance_count_portrait_files(),
    );

    try {
        $preview['website_bot_events'] = bot_maintenance_scalar($masterPdo, "SELECT COUNT(*) FROM `website_bot_events` WHERE `realm_id` = ?", array($realmId));
    } catch (Throwable $e) {
        try {
            $preview['website_bot_events'] = bot_maintenance_scalar($masterPdo, "SELECT COUNT(*) FROM `website_bot_events`");
        } catch (Throwable $ignored) {
            $preview['website_bot_events'] = 0;
        }
    }

    try {
        $preview['bot_identities'] = bot_maintenance_scalar(
            $masterPdo,
            "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)",
            array($realmId)
        );
    } catch (Throwable $e) {
        $preview['bot_identities'] = 0;
    }

    try {
        $preview['bot_identity_profiles'] = bot_maintenance_scalar(
            $masterPdo,
            "SELECT COUNT(*)
             FROM `website_identity_profiles` p
             INNER JOIN `website_identities` i ON i.`identity_id` = p.`identity_id`
             WHERE i.`realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)",
            array($realmId)
        );
    } catch (Throwable $e) {
        $preview['bot_identity_profiles'] = 0;
    }

    return $preview;
}

function bot_maintenance_clear_bot_web_state(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $dryRun = !empty($payload['dry_run']);
    $execute = !empty($payload['execute']) && !$dryRun && !empty($config['allow_execute']);
    $preview = bot_maintenance_clear_bot_web_state_preview($realmId);

    $response = array(
        'status' => 'ok',
        'step' => 'clear_bot_web_state',
        'summary' => 'Bot website-state preview prepared for the selected realm.',
        'execute' => $execute,
        'preview' => $preview,
    );

    if ($dryRun) {
        $response['summary'] = 'Dry-run requested. No website rows or cache files were deleted.';
        return $response;
    }

    if (!$execute) {
        $response['status'] = 'error';
        $response['summary'] = 'Execution was not requested. Use --execute to run this script, or add --dry-run to preview only.';
        return $response;
    }

    $masterPdo = spp_get_pdo('realmd', 1);
    $deletedPortraits = 0;

    $masterPdo->beginTransaction();
    try {
        try {
            $stmt = $masterPdo->prepare("DELETE FROM `website_bot_events` WHERE `realm_id` = ?");
            $stmt->execute(array($realmId));
        } catch (Throwable $e) {
            $masterPdo->exec("DELETE FROM `website_bot_events`");
        }

        $identityIdsStmt = $masterPdo->prepare(
            "SELECT `identity_id`
             FROM `website_identities`
             WHERE `realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)"
        );
        $identityIdsStmt->execute(array($realmId));
        $identityIds = array_values(array_filter(array_map('intval', $identityIdsStmt->fetchAll(PDO::FETCH_COLUMN) ?: array())));

        if (!empty($identityIds)) {
            $placeholders = implode(',', array_fill(0, count($identityIds), '?'));
            $deleteProfiles = $masterPdo->prepare("DELETE FROM `website_identity_profiles` WHERE `identity_id` IN ({$placeholders})");
            $deleteProfiles->execute($identityIds);
            $deleteIdentities = $masterPdo->prepare("DELETE FROM `website_identities` WHERE `identity_id` IN ({$placeholders})");
            $deleteIdentities->execute($identityIds);
        }

        $masterPdo->commit();
        $deletedPortraits = bot_maintenance_delete_portrait_files();
    } catch (Throwable $e) {
        if ($masterPdo->inTransaction()) {
            $masterPdo->rollBack();
        }
        return array(
            'status' => 'error',
            'step' => 'clear_bot_web_state',
            'error' => $e->getMessage(),
        );
    }

    $response['summary'] = 'Selected realm bot website state was cleared.';
    $response['deleted_portrait_files'] = $deletedPortraits;
    $response['preview_after'] = bot_maintenance_clear_bot_web_state_preview($realmId);
    return $response;
}

function bot_maintenance_clear_bot_character_state_preview(int $realmId): array
{
    $charsPdo = spp_get_pdo('chars', $realmId);
    $realmdConfig = spp_get_db_config('realmd', $realmId);
    $realmdDbName = '`' . str_replace('`', '``', (string)$realmdConfig['name']) . '`';
    $botAccountSubquery = "SELECT `id` FROM {$realmdDbName}.`account` WHERE LOWER(`username`) LIKE 'rndbot%'";

    $preview = array(
        'realm_id' => $realmId,
        'bot_characters' => bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `characters` WHERE `account` IN ({$botAccountSubquery})"),
        'bot_guild_memberships' => bot_maintenance_scalar(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `guild_member` gm
             INNER JOIN `characters` c ON c.`guid` = gm.`guid`
             WHERE c.`account` IN ({$botAccountSubquery})"
        ),
        'bot_guilds' => bot_maintenance_scalar(
            $charsPdo,
            "SELECT COUNT(DISTINCT gm.`guildid`)
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
        'bot_auction_rows' => 0,
        'rotation_log_rows' => 0,
        'rotation_ilvl_log_rows' => 0,
        'rotation_state_rows' => 0,
    );

    try {
        $preview['bot_auction_rows'] = bot_maintenance_scalar(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `auctionhouse`
             WHERE `itemowner` IN (SELECT `guid` FROM `characters` WHERE `account` IN ({$botAccountSubquery}))
                OR `buyguid` IN (SELECT `guid` FROM `characters` WHERE `account` IN ({$botAccountSubquery}))"
        );
    } catch (Throwable $e) {
        $preview['bot_auction_rows'] = 0;
    }

    try {
        $realmdPdo = spp_get_pdo('realmd', $realmId);
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_log')) {
            $preview['rotation_log_rows'] = bot_maintenance_scalar($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_log` WHERE `realm` = ?", array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_ilvl_log')) {
            $preview['rotation_ilvl_log_rows'] = bot_maintenance_scalar($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_ilvl_log` WHERE `realm` = ?", array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_state')) {
            $preview['rotation_state_rows'] = bot_maintenance_scalar($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_state` WHERE `realm` = ?", array($realmId));
        }
    } catch (Throwable $e) {
        $preview['rotation_log_rows'] = 0;
        $preview['rotation_ilvl_log_rows'] = 0;
        $preview['rotation_state_rows'] = 0;
    }

    return $preview;
}

function bot_maintenance_clear_realm_character_state_preview(int $realmId): array
{
    $charsPdo = spp_get_pdo('chars', $realmId);

    $preview = array(
        'realm_id' => $realmId,
        'realm_characters' => bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `characters`"),
        'realm_guild_memberships' => bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `guild_member`"),
        'realm_guilds' => bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `guild`"),
        'realm_db_store_rows' => bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `ai_playerbot_db_store`"),
        'realm_auction_rows' => 0,
        'rotation_log_rows' => 0,
        'rotation_ilvl_log_rows' => 0,
        'rotation_state_rows' => 0,
    );

    try {
        if (bot_maintenance_table_exists($charsPdo, 'auctionhouse')) {
            $preview['realm_auction_rows'] = bot_maintenance_scalar($charsPdo, "SELECT COUNT(*) FROM `auctionhouse`");
        }
    } catch (Throwable $e) {
        $preview['realm_auction_rows'] = 0;
    }

    try {
        $realmdPdo = spp_get_pdo('realmd', $realmId);
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_log')) {
            $preview['rotation_log_rows'] = bot_maintenance_scalar($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_log` WHERE `realm` = ?", array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_ilvl_log')) {
            $preview['rotation_ilvl_log_rows'] = bot_maintenance_scalar($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_ilvl_log` WHERE `realm` = ?", array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_state')) {
            $preview['rotation_state_rows'] = bot_maintenance_scalar($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_state` WHERE `realm` = ?", array($realmId));
        }
    } catch (Throwable $e) {
        $preview['rotation_log_rows'] = 0;
        $preview['rotation_ilvl_log_rows'] = 0;
        $preview['rotation_state_rows'] = 0;
    }

    return $preview;
}

function bot_maintenance_reset_bot_rotation_realm(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $dryRun = !empty($payload['dry_run']);
    $execute = !empty($payload['execute']) && !$dryRun && !empty($config['allow_execute']);
    $preview = bot_maintenance_clear_bot_character_state_preview($realmId);

    $rotationPreview = array(
        'realm_id' => $realmId,
        'rotation_log_rows' => (int)($preview['rotation_log_rows'] ?? 0),
        'rotation_ilvl_log_rows' => (int)($preview['rotation_ilvl_log_rows'] ?? 0),
        'rotation_state_rows' => (int)($preview['rotation_state_rows'] ?? 0),
    );

    $response = array(
        'status' => 'ok',
        'step' => 'reset_bot_rotation_realm',
        'summary' => 'Bot rotation reset preview prepared for the selected realm.',
        'execute' => $execute,
        'preview' => $rotationPreview,
    );

    if ($dryRun) {
        $response['summary'] = 'Dry-run requested. No rotation rows were deleted.';
        return $response;
    }

    if (!$execute) {
        $response['status'] = 'error';
        $response['summary'] = 'Execution was not requested. Use --execute to run this script, or add --dry-run to preview only.';
        return $response;
    }

    try {
        $realmdPdo = spp_get_pdo('realmd', $realmId);
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_log')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_log` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_ilvl_log')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_ilvl_log` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_state')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_state` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
    } catch (Throwable $e) {
        return array(
            'status' => 'error',
            'step' => 'reset_bot_rotation_realm',
            'error' => $e->getMessage(),
        );
    }

    return array(
        'status' => 'ok',
        'step' => 'reset_bot_rotation_realm',
        'summary' => 'Selected realm bot rotation history was cleared.',
        'execute' => true,
        'preview' => $rotationPreview,
        'preview_after' => array(
            'realm_id' => $realmId,
            'rotation_log_rows' => 0,
            'rotation_ilvl_log_rows' => 0,
            'rotation_state_rows' => 0,
        ),
    );
}

function bot_maintenance_clear_bot_character_state(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $dryRun = !empty($payload['dry_run']);
    $execute = !empty($payload['execute']) && !$dryRun && !empty($config['allow_execute']);
    $preview = bot_maintenance_clear_bot_character_state_preview($realmId);

    $response = array(
        'status' => 'ok',
        'step' => 'clear_bot_character_state',
        'summary' => 'Bot character-state preview prepared for the selected realm.',
        'execute' => $execute,
        'preview' => $preview,
    );

    if ($dryRun) {
        $response['summary'] = 'Dry-run requested. No bot character rows were deleted.';
        return $response;
    }

    if (!$execute) {
        $response['status'] = 'error';
        $response['summary'] = 'Execution was not requested. Use --execute to run this script, or add --dry-run to preview only.';
        return $response;
    }

    $charsPdo = spp_get_pdo('chars', $realmId);
    $realmdPdo = spp_get_pdo('realmd', $realmId);
    $realmdConfig = spp_get_db_config('realmd', $realmId);
    $realmdDbName = '`' . str_replace('`', '``', (string)$realmdConfig['name']) . '`';
    $botAccountSubquery = "SELECT `id` FROM {$realmdDbName}.`account` WHERE LOWER(`username`) LIKE 'rndbot%'";

    try {
        $charsPdo->beginTransaction();

        $charsPdo->exec("DROP TEMPORARY TABLE IF EXISTS `tmp_bot_guids`");
        $charsPdo->exec("CREATE TEMPORARY TABLE `tmp_bot_guids` (`guid` INT UNSIGNED NOT NULL PRIMARY KEY)");
        $charsPdo->exec(
            "INSERT INTO `tmp_bot_guids` (`guid`)
             SELECT `guid`
             FROM `characters`
             WHERE `account` IN ({$botAccountSubquery})"
        );

        $charsPdo->exec("DROP TEMPORARY TABLE IF EXISTS `tmp_bot_guilds`");
        $charsPdo->exec("CREATE TEMPORARY TABLE `tmp_bot_guilds` (`guildid` INT UNSIGNED NOT NULL PRIMARY KEY)");
        if (bot_maintenance_table_exists($charsPdo, 'guild_member')) {
            $charsPdo->exec(
                "INSERT IGNORE INTO `tmp_bot_guilds` (`guildid`)
                 SELECT DISTINCT gm.`guildid`
                 FROM `guild_member` gm
                 INNER JOIN `tmp_bot_guids` b ON b.`guid` = gm.`guid`"
            );
        }

        $guidLinkedDeletes = array(
            array('table' => 'character_action', 'column' => 'guid'),
            array('table' => 'character_gifts', 'column' => 'guid'),
            array('table' => 'character_homebind', 'column' => 'guid'),
            array('table' => 'character_inventory', 'column' => 'guid'),
            array('table' => 'character_queststatus', 'column' => 'guid'),
            array('table' => 'character_reputation', 'column' => 'guid'),
            array('table' => 'character_spell', 'column' => 'guid'),
            array('table' => 'character_social', 'column' => 'guid'),
            array('table' => 'corpse', 'column' => 'player'),
            array('table' => 'petition', 'column' => 'ownerguid'),
            array('table' => 'petition_sign', 'column' => 'ownerguid'),
            array('table' => 'petition_sign', 'column' => 'playerguid'),
            array('table' => 'mail', 'column' => 'receiver'),
            array('table' => 'mail', 'column' => 'sender'),
            array('table' => 'auctionhouse', 'column' => 'itemowner'),
            array('table' => 'auctionhouse', 'column' => 'buyguid'),
            array('table' => 'character_pet', 'column' => 'owner'),
        );

        foreach ($guidLinkedDeletes as $deleteSpec) {
            $table = (string)$deleteSpec['table'];
            $column = (string)$deleteSpec['column'];
            if (!bot_maintenance_table_exists($charsPdo, $table) || !bot_maintenance_column_exists($charsPdo, $table, $column)) {
                continue;
            }

            $charsPdo->exec(
                "DELETE t
                 FROM `{$table}` t
                 INNER JOIN `tmp_bot_guids` b ON b.`guid` = t.`{$column}`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'group_member')) {
            $groupMemberColumns = array();
            foreach (array('memberGuid', 'leaderGuid') as $column) {
                if (bot_maintenance_column_exists($charsPdo, 'group_member', $column)) {
                    $groupMemberColumns[] = $column;
                }
            }
            foreach ($groupMemberColumns as $column) {
                $charsPdo->exec(
                    "DELETE gm
                     FROM `group_member` gm
                     INNER JOIN `tmp_bot_guids` b ON b.`guid` = gm.`{$column}`"
                );
            }
        }

        if (bot_maintenance_table_exists($charsPdo, 'groups') && bot_maintenance_column_exists($charsPdo, 'groups', 'leaderGuid')) {
            $charsPdo->exec(
                "DELETE g
                 FROM `groups` g
                 INNER JOIN `tmp_bot_guids` b ON b.`guid` = g.`leaderGuid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'group_instance') && bot_maintenance_column_exists($charsPdo, 'group_instance', 'leaderGuid')) {
            $charsPdo->exec(
                "DELETE gi
                 FROM `group_instance` gi
                 INNER JOIN `tmp_bot_guids` b ON b.`guid` = gi.`leaderGuid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'ai_playerbot_db_store')) {
            $charsPdo->exec(
                "DELETE s
                 FROM `ai_playerbot_db_store` s
                 INNER JOIN `tmp_bot_guids` b ON b.`guid` = s.`guid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'guild_member')) {
            $charsPdo->exec(
                "DELETE gm
                 FROM `guild_member` gm
                 INNER JOIN `tmp_bot_guids` b ON b.`guid` = gm.`guid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'guild')) {
            $charsPdo->exec("DROP TEMPORARY TABLE IF EXISTS `tmp_empty_bot_guilds`");
            $charsPdo->exec("CREATE TEMPORARY TABLE `tmp_empty_bot_guilds` (`guildid` INT UNSIGNED NOT NULL PRIMARY KEY)");
            $charsPdo->exec(
                "INSERT IGNORE INTO `tmp_empty_bot_guilds` (`guildid`)
                 SELECT g.`guildid`
                 FROM `guild` g
                 INNER JOIN `tmp_bot_guilds` bg ON bg.`guildid` = g.`guildid`
                 LEFT JOIN `guild_member` gm ON gm.`guildid` = g.`guildid`
                 GROUP BY g.`guildid`
                 HAVING COUNT(gm.`guid`) = 0"
            );

            $guildLinkedTables = array(
                'guild_bank_eventlog',
                'guild_bank_item',
                'guild_bank_right',
                'guild_bank_tab',
                'guild_eventlog',
                'guild_rank',
                'guild',
            );
            foreach ($guildLinkedTables as $table) {
                if (!bot_maintenance_table_exists($charsPdo, $table) || !bot_maintenance_column_exists($charsPdo, $table, 'guildid')) {
                    continue;
                }

                $charsPdo->exec(
                    "DELETE t
                     FROM `{$table}` t
                     INNER JOIN `tmp_empty_bot_guilds` g ON g.`guildid` = t.`guildid`"
                );
            }
        }

        $charsPdo->exec(
            "DELETE c
             FROM `characters` c
             INNER JOIN `tmp_bot_guids` b ON b.`guid` = c.`guid`"
        );

        $orphanCleanupSql = array(
            "DELETE FROM `auctionhouse` WHERE `itemowner` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_gifts` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_homebind` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_inventory` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_pet` WHERE `owner` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_queststatus` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_reputation` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_social` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_spell` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `corpse` WHERE `player` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `guild_member` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `guild_rank` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_eventlog` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_eventlog` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_item` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_right` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_tab` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `mail` WHERE `receiver` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `mail_items` WHERE `mail_id` NOT IN (SELECT `id` FROM `mail`)",
            "DELETE FROM `item_text` WHERE `id` NOT IN (SELECT `itemTextId` FROM `mail`)",
            "DELETE FROM `petition` WHERE `ownerguid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `petition` WHERE `petitionguid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `petition_sign` WHERE `ownerguid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `petition_sign` WHERE `playerguid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `petition_sign` WHERE `petitionguid` NOT IN (SELECT `petitionguid` FROM `petition`)",
            "DELETE FROM `pet_spell` WHERE `guid` NOT IN (SELECT `id` FROM `character_pet`)",
            "DELETE FROM `item_instance` WHERE `guid` NOT IN (SELECT `item` FROM `character_inventory`) AND `guid` NOT IN (SELECT `itemguid` FROM `auctionhouse`) AND `guid` NOT IN (SELECT `item_guid` FROM `guild_bank_item`) AND `guid` NOT IN (SELECT `item_guid` FROM `mail_items`) AND `guid` NOT IN (SELECT `item_guid` FROM `character_gifts`)",
            "DELETE FROM `item_instance` WHERE `owner_guid` NOT IN (SELECT `guid` FROM `characters`) AND `owner_guid` <> '0'",
            "DELETE FROM `character_inventory` WHERE `item` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `auctionhouse` WHERE `itemguid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `guild_bank_item` WHERE `item_guid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `mail_items` WHERE `item_guid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `character_gifts` WHERE `item_guid` NOT IN (SELECT `guid` FROM `item_instance`)"
        );

        foreach ($orphanCleanupSql as $sql) {
            if (preg_match('/DELETE FROM `([^`]+)`/i', $sql, $matches)) {
                $table = (string)$matches[1];
                if (!bot_maintenance_table_exists($charsPdo, $table)) {
                    continue;
                }
            }
            try {
                $charsPdo->exec($sql);
            } catch (Throwable $e) {
                // Keep cleanup best-effort for expansion-specific schema drift.
            }
        }

        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_log')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_log` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_ilvl_log')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_ilvl_log` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_state')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_state` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }

        $charsPdo->commit();
    } catch (Throwable $e) {
        if ($charsPdo->inTransaction()) {
            $charsPdo->rollBack();
        }
        return array(
            'status' => 'error',
            'step' => 'clear_bot_character_state',
            'error' => $e->getMessage(),
        );
    }

    return array(
        'status' => 'ok',
        'step' => 'clear_bot_character_state',
        'summary' => 'Selected realm bot character state was cleared. Keep the world server offline until you finish the host restart and repopulate steps.',
        'execute' => true,
        'preview' => $preview,
        'preview_after' => bot_maintenance_clear_bot_character_state_preview($realmId),
    );
}

function bot_maintenance_clear_realm_character_state(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $dryRun = !empty($payload['dry_run']);
    $execute = !empty($payload['execute']) && !$dryRun && !empty($config['allow_execute']);
    $preview = bot_maintenance_clear_realm_character_state_preview($realmId);

    $response = array(
        'status' => 'ok',
        'step' => 'clear_realm_character_state',
        'summary' => 'Realm-wide character-state preview prepared for the selected realm.',
        'execute' => $execute,
        'preview' => $preview,
    );

    if ($dryRun) {
        $response['summary'] = 'Dry-run requested. No realm character rows were deleted.';
        return $response;
    }

    if (!$execute) {
        $response['status'] = 'error';
        $response['summary'] = 'Execution was not requested. Use --execute to run this script, or add --dry-run to preview only.';
        return $response;
    }

    $charsPdo = spp_get_pdo('chars', $realmId);
    $realmdPdo = spp_get_pdo('realmd', $realmId);

    try {
        $charsPdo->beginTransaction();

        $charsPdo->exec("DROP TEMPORARY TABLE IF EXISTS `tmp_realm_guids`");
        $charsPdo->exec("CREATE TEMPORARY TABLE `tmp_realm_guids` (`guid` INT UNSIGNED NOT NULL PRIMARY KEY)");
        $charsPdo->exec(
            "INSERT INTO `tmp_realm_guids` (`guid`)
             SELECT `guid`
             FROM `characters`"
        );

        $charsPdo->exec("DROP TEMPORARY TABLE IF EXISTS `tmp_realm_guilds`");
        $charsPdo->exec("CREATE TEMPORARY TABLE `tmp_realm_guilds` (`guildid` INT UNSIGNED NOT NULL PRIMARY KEY)");
        if (bot_maintenance_table_exists($charsPdo, 'guild')) {
            $charsPdo->exec("INSERT IGNORE INTO `tmp_realm_guilds` (`guildid`) SELECT `guildid` FROM `guild`");
        }

        $guidLinkedDeletes = array(
            array('table' => 'character_action', 'column' => 'guid'),
            array('table' => 'character_gifts', 'column' => 'guid'),
            array('table' => 'character_homebind', 'column' => 'guid'),
            array('table' => 'character_inventory', 'column' => 'guid'),
            array('table' => 'character_queststatus', 'column' => 'guid'),
            array('table' => 'character_reputation', 'column' => 'guid'),
            array('table' => 'character_spell', 'column' => 'guid'),
            array('table' => 'character_social', 'column' => 'guid'),
            array('table' => 'corpse', 'column' => 'player'),
            array('table' => 'petition', 'column' => 'ownerguid'),
            array('table' => 'petition_sign', 'column' => 'ownerguid'),
            array('table' => 'petition_sign', 'column' => 'playerguid'),
            array('table' => 'mail', 'column' => 'receiver'),
            array('table' => 'mail', 'column' => 'sender'),
            array('table' => 'auctionhouse', 'column' => 'itemowner'),
            array('table' => 'auctionhouse', 'column' => 'buyguid'),
            array('table' => 'character_pet', 'column' => 'owner'),
        );

        foreach ($guidLinkedDeletes as $deleteSpec) {
            $table = (string)$deleteSpec['table'];
            $column = (string)$deleteSpec['column'];
            if (!bot_maintenance_table_exists($charsPdo, $table) || !bot_maintenance_column_exists($charsPdo, $table, $column)) {
                continue;
            }

            $charsPdo->exec(
                "DELETE t
                 FROM `{$table}` t
                 INNER JOIN `tmp_realm_guids` b ON b.`guid` = t.`{$column}`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'group_member')) {
            $groupMemberColumns = array();
            foreach (array('memberGuid', 'leaderGuid') as $column) {
                if (bot_maintenance_column_exists($charsPdo, 'group_member', $column)) {
                    $groupMemberColumns[] = $column;
                }
            }
            foreach ($groupMemberColumns as $column) {
                $charsPdo->exec(
                    "DELETE gm
                     FROM `group_member` gm
                     INNER JOIN `tmp_realm_guids` b ON b.`guid` = gm.`{$column}`"
                );
            }
        }

        if (bot_maintenance_table_exists($charsPdo, 'groups') && bot_maintenance_column_exists($charsPdo, 'groups', 'leaderGuid')) {
            $charsPdo->exec(
                "DELETE g
                 FROM `groups` g
                 INNER JOIN `tmp_realm_guids` b ON b.`guid` = g.`leaderGuid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'group_instance') && bot_maintenance_column_exists($charsPdo, 'group_instance', 'leaderGuid')) {
            $charsPdo->exec(
                "DELETE gi
                 FROM `group_instance` gi
                 INNER JOIN `tmp_realm_guids` b ON b.`guid` = gi.`leaderGuid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'ai_playerbot_db_store')) {
            $charsPdo->exec(
                "DELETE s
                 FROM `ai_playerbot_db_store` s
                 INNER JOIN `tmp_realm_guids` b ON b.`guid` = s.`guid`"
            );
        }

        if (bot_maintenance_table_exists($charsPdo, 'guild_member')) {
            $charsPdo->exec("DELETE FROM `guild_member`");
        }

        $guildLinkedTables = array(
            'guild_bank_eventlog',
            'guild_bank_item',
            'guild_bank_right',
            'guild_bank_tab',
            'guild_eventlog',
            'guild_rank',
            'guild',
        );
        foreach ($guildLinkedTables as $table) {
            if (!bot_maintenance_table_exists($charsPdo, $table)) {
                continue;
            }
            if (!bot_maintenance_column_exists($charsPdo, $table, 'guildid')) {
                if ($table === 'guild') {
                    $charsPdo->exec("DELETE FROM `guild`");
                }
                continue;
            }

            $charsPdo->exec(
                "DELETE t
                 FROM `{$table}` t
                 INNER JOIN `tmp_realm_guilds` g ON g.`guildid` = t.`guildid`"
            );
        }

        $charsPdo->exec("DELETE FROM `characters`");

        $orphanCleanupSql = array(
            "DELETE FROM `auctionhouse` WHERE `itemowner` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_gifts` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_homebind` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_inventory` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_pet` WHERE `owner` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_queststatus` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_reputation` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_social` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `character_spell` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `corpse` WHERE `player` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `guild_member` WHERE `guid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `guild_rank` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_eventlog` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_eventlog` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_item` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_right` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `guild_bank_tab` WHERE `guildid` NOT IN (SELECT `guildid` FROM `guild`)",
            "DELETE FROM `mail` WHERE `receiver` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `mail_items` WHERE `mail_id` NOT IN (SELECT `id` FROM `mail`)",
            "DELETE FROM `item_text` WHERE `id` NOT IN (SELECT `itemTextId` FROM `mail`)",
            "DELETE FROM `petition` WHERE `ownerguid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `petition` WHERE `petitionguid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `petition_sign` WHERE `ownerguid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `petition_sign` WHERE `playerguid` NOT IN (SELECT `guid` FROM `characters`)",
            "DELETE FROM `petition_sign` WHERE `petitionguid` NOT IN (SELECT `petitionguid` FROM `petition`)",
            "DELETE FROM `pet_spell` WHERE `guid` NOT IN (SELECT `id` FROM `character_pet`)",
            "DELETE FROM `item_instance` WHERE `guid` NOT IN (SELECT `item` FROM `character_inventory`) AND `guid` NOT IN (SELECT `itemguid` FROM `auctionhouse`) AND `guid` NOT IN (SELECT `item_guid` FROM `guild_bank_item`) AND `guid` NOT IN (SELECT `item_guid` FROM `mail_items`) AND `guid` NOT IN (SELECT `item_guid` FROM `character_gifts`)",
            "DELETE FROM `item_instance` WHERE `owner_guid` NOT IN (SELECT `guid` FROM `characters`) AND `owner_guid` <> '0'",
            "DELETE FROM `character_inventory` WHERE `item` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `auctionhouse` WHERE `itemguid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `guild_bank_item` WHERE `item_guid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `mail_items` WHERE `item_guid` NOT IN (SELECT `guid` FROM `item_instance`)",
            "DELETE FROM `character_gifts` WHERE `item_guid` NOT IN (SELECT `guid` FROM `item_instance`)"
        );

        foreach ($orphanCleanupSql as $sql) {
            if (preg_match('/DELETE FROM `([^`]+)`/i', $sql, $matches)) {
                $table = (string)$matches[1];
                if (!bot_maintenance_table_exists($charsPdo, $table)) {
                    continue;
                }
            }
            try {
                $charsPdo->exec($sql);
            } catch (Throwable $e) {
                // Best-effort cleanup for schema drift.
            }
        }

        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_log')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_log` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_ilvl_log')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_ilvl_log` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }
        if (bot_maintenance_table_exists($realmdPdo, 'bot_rotation_state')) {
            $stmt = $realmdPdo->prepare("DELETE FROM `bot_rotation_state` WHERE `realm` = ?");
            $stmt->execute(array($realmId));
        }

        $charsPdo->commit();
    } catch (Throwable $e) {
        if ($charsPdo->inTransaction()) {
            $charsPdo->rollBack();
        }
        return array(
            'status' => 'error',
            'step' => 'clear_realm_character_state',
            'error' => $e->getMessage(),
        );
    }

    return array(
        'status' => 'ok',
        'step' => 'clear_realm_character_state',
        'summary' => 'Selected realm character state was cleared while preserving auth accounts. Keep the world server offline until you finish the next rebuild steps.',
        'execute' => true,
        'preview' => $preview,
        'preview_after' => bot_maintenance_clear_realm_character_state_preview($realmId),
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
            'clear_bot_web_state',
            'reset_bot_rotation_realm',
            'clear_bot_character_state',
            'clear_realm_character_state',
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
