<?php
declare(strict_types=1);

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

if (!function_exists('spp_default_realm_id')) {
    function spp_default_realm_id(array $realmDbMap) {
        return 1;
    }
}

require_once(__DIR__ . '/../config/config-helper.php');

function bot_helper_json_response(array $payload, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function bot_helper_config(): array
{
    return array(
        'token' => trim((string)getenv('SPP_BOT_HELPER_TOKEN')),
        'allow_execute' => is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bot_maintenance_execute_enabled.flag'),
    );
}

function bot_helper_request_payload(): array
{
    if (PHP_SAPI === 'cli') {
        global $argv;
        if (!empty($argv[1]) && strpos((string)$argv[1], '{') !== 0) {
            $action = strtolower(trim((string)$argv[1]));
            $payload = array();
            foreach (array_slice($argv, 2) as $arg) {
                $arg = (string)$arg;
                if (strpos($arg, '--realm=') === 0) {
                    $payload['realm_id'] = (int)substr($arg, 8);
                    continue;
                }
                if ($arg === '--execute') {
                    $payload['execute'] = true;
                    continue;
                }
            }

            return array(
                'action' => $action,
                'payload' => $payload,
            );
        }

        $json = '';
        if (!empty($argv[1]) && is_file($argv[1])) {
            $json = (string)file_get_contents($argv[1]);
        } elseif (!empty($argv[1])) {
            $json = (string)$argv[1];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : array();
    }

    $raw = file_get_contents('php://input');
    $decoded = json_decode((string)$raw, true);
    return is_array($decoded) ? $decoded : array();
}

function bot_helper_authorize_or_fail(array $config): void
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

    bot_helper_json_response(array(
        'status' => 'error',
        'step' => 'authorize',
        'error' => 'Unauthorized helper request.',
    ), 401);
}

function bot_helper_realm_forum_scope(int $realmId): array
{
    $map = array(
        1 => array('forum_id' => 2, 'forum_name' => 'Classic'),
        2 => array('forum_id' => 3, 'forum_name' => 'The Burning Crusade'),
        3 => array('forum_id' => 4, 'forum_name' => 'Wrath of the Lich King'),
    );

    return $map[$realmId] ?? array('forum_id' => 0, 'forum_name' => '');
}

function bot_helper_scalar(PDO $pdo, string $sql, array $params = array()): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function bot_helper_forum_reset_preview(int $realmId): array
{
    $forumScope = bot_helper_realm_forum_scope($realmId);
    $forumId = (int)($forumScope['forum_id'] ?? 0);
    $pdo = spp_get_pdo('realmd', 1);

    if ($forumId <= 0) {
        return array(
            'realm_id' => $realmId,
            'forum_id' => 0,
            'forum_name' => '',
            'topics_total' => 0,
            'posts_total' => 0,
            'preserved_topics' => 0,
            'preserved_posts' => 0,
            'topics_to_delete' => 0,
            'posts_to_delete' => 0,
        );
    }

    $topicsTotal = bot_helper_scalar($pdo, "SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = ?", array($forumId));
    $postsTotal = bot_helper_scalar(
        $pdo,
        "SELECT COUNT(*)
         FROM `f_posts` p
         INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
         WHERE t.`forum_id` = ?",
        array($forumId)
    );
    $preservedTopics = bot_helper_scalar(
        $pdo,
        "SELECT COUNT(*)
         FROM `f_topics`
         WHERE `forum_id` = ?
           AND `topic_poster_id` = 0
           AND LOWER(TRIM(`topic_poster`)) IN ('web team', 'spp team')",
        array($forumId)
    );
    $preservedPosts = bot_helper_scalar(
        $pdo,
        "SELECT COUNT(*)
         FROM `f_posts`
         WHERE `topic_id` IN (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = ?)
           AND `poster_id` = 0
           AND (`poster_character_id` IS NULL OR `poster_character_id` = 0)
           AND LOWER(TRIM(`poster`)) IN ('web team', 'spp team')",
        array($forumId)
    );

    return array(
        'realm_id' => $realmId,
        'forum_id' => $forumId,
        'forum_name' => (string)($forumScope['forum_name'] ?? ''),
        'topics_total' => $topicsTotal,
        'posts_total' => $postsTotal,
        'preserved_topics' => $preservedTopics,
        'preserved_posts' => $preservedPosts,
        'topics_to_delete' => max(0, $topicsTotal - $preservedTopics),
        'posts_to_delete' => max(0, $postsTotal - $preservedPosts),
    );
}

function bot_helper_bot_reset_plan(int $realmId): array
{
    $charsPdo = spp_get_pdo('chars', $realmId);
    $masterPdo = spp_get_pdo('realmd', 1);
    $realmdConfig = spp_get_db_config('realmd', $realmId);
    $realmdDbName = '`' . str_replace('`', '``', (string)$realmdConfig['name']) . '`';
    $botAccountSubquery = "SELECT `id` FROM {$realmdDbName}.`account` WHERE LOWER(`username`) LIKE 'rndbot%'";

    $preview = array(
        'bot_accounts' => bot_helper_scalar($masterPdo, "SELECT COUNT(*) FROM `account` WHERE LOWER(`username`) LIKE 'rndbot%'"),
        'bot_characters' => bot_helper_scalar($charsPdo, "SELECT COUNT(*) FROM `characters` WHERE `account` IN ({$botAccountSubquery})"),
        'bot_guild_memberships' => bot_helper_scalar(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `guild_member` gm
             INNER JOIN `characters` c ON c.`guid` = gm.`guid`
             WHERE c.`account` IN ({$botAccountSubquery})"
        ),
        'bot_db_store_rows' => bot_helper_scalar(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `ai_playerbot_db_store` s
             INNER JOIN `characters` c ON c.`guid` = s.`guid`
             WHERE c.`account` IN ({$botAccountSubquery})"
        ),
        'website_bot_events' => bot_helper_scalar($masterPdo, "SELECT COUNT(*) FROM `website_bot_events`"),
    );

    return array(
        'realm_id' => $realmId,
        'preview' => $preview,
        'phases' => array(
            array(
                'name' => 'reset_forum_realm',
                'summary' => 'Delete selected realm forum topics/posts except preserved official seed content.',
                'preview' => bot_helper_forum_reset_preview($realmId),
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
                'summary' => 'Host helper must restart/repopulate the world after DB cleanup. This script reports the need but does not restart services yet.',
                'host_steps' => array(
                    'Set DeleteRandomBotAccounts or equivalent fresh-population mode as desired.',
                    'Restart world service for the target realm.',
                    'Allow RandomBotAutoCreate to repopulate bots with new GUIDs.',
                ),
            ),
        ),
    );
}

function bot_helper_handle_status(array $config): array
{
    return array(
        'status' => 'ok',
        'step' => 'status',
        'summary' => 'Bot maintenance helper is reachable.',
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

function bot_helper_handle_reset_forum_realm(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    $execute = !empty($payload['execute']) && !empty($config['allow_execute']);

    $preview = bot_helper_forum_reset_preview($realmId);
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

    if (!$execute) {
        $response['summary'] = 'Dry-run only. Create core/cache/bot_maintenance_execute_enabled.flag and pass execute=true to perform the forum reset.';
        return $response;
    }

    $pdo = spp_get_pdo('realmd', 1);
    $forumId = (int)($preview['forum_id'] ?? 0);
    if ($forumId <= 0) {
        return array(
            'status' => 'error',
            'step' => 'reset_forum_realm',
            'error' => 'No forum mapping exists for that realm.',
        );
    }

    $pdo->beginTransaction();
    try {
        $topicStmt = $pdo->prepare(
            "SELECT `topic_id`
             FROM `f_topics`
             WHERE `forum_id` = ?
               AND NOT (`topic_poster_id` = 0 AND LOWER(TRIM(`topic_poster`)) IN ('web team', 'spp team'))"
        );
        $topicStmt->execute(array($forumId));
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
        $recount->execute(array($forumId, $forumId, $forumId, $forumId));
        $pdo->commit();

        $response['summary'] = 'Selected realm forum reset completed.';
        $response['deleted_topics'] = count($topicIds);
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

function bot_helper_handle_fresh_reset(array $payload, array $config): array
{
    $realmId = (int)($payload['realm_id'] ?? 0);
    return array(
        'status' => 'ok',
        'step' => 'fresh_reset',
        'summary' => !empty($config['allow_execute'])
            ? 'Fresh reset execution is still partially scaffolded; review the phase plan before enabling full execution.'
            : 'Dry-run fresh reset plan prepared. Full execution still needs the host-restart bridge.',
        'execute' => false,
        'plan' => bot_helper_bot_reset_plan($realmId),
    );
}

function bot_helper_handle_rebuild_site_layers(array $payload): array
{
    return array(
        'status' => 'ok',
        'step' => 'rebuild_site_layers',
        'summary' => 'Site-layer rebuild is scaffolded only. Recommended follow-up tools: backfill_identities.php, backfill_post_identities.php, backfill_pm_identities.php.',
        'recommended_commands' => array(
            'php tools/backfill_identities.php --realm=' . (int)($payload['realm_id'] ?? 0),
            'php tools/backfill_post_identities.php --realm=' . (int)($payload['realm_id'] ?? 0),
            'php tools/backfill_pm_identities.php --realm=' . (int)($payload['realm_id'] ?? 0),
        ),
    );
}

$config = bot_helper_config();
if (PHP_SAPI !== 'cli') {
    bot_helper_authorize_or_fail($config);
}

$request = bot_helper_request_payload();
$action = strtolower(trim((string)($request['action'] ?? 'status')));
$payload = isset($request['payload']) && is_array($request['payload']) ? $request['payload'] : array();

switch ($action) {
    case 'status':
        bot_helper_json_response(bot_helper_handle_status($config));
        break;
    case 'reset_forum_realm':
        bot_helper_json_response(bot_helper_handle_reset_forum_realm($payload, $config));
        break;
    case 'fresh_reset':
        bot_helper_json_response(bot_helper_handle_fresh_reset($payload, $config));
        break;
    case 'rebuild_site_layers':
        bot_helper_json_response(bot_helper_handle_rebuild_site_layers($payload));
        break;
    default:
        bot_helper_json_response(array(
            'status' => 'error',
            'step' => 'dispatch',
            'error' => 'Unknown helper action: ' . $action,
        ), 400);
}
