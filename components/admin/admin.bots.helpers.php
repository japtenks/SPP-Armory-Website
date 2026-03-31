<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.identities.helpers.php');
require_once(dirname(__DIR__) . '/forum/forum.scope.php');

function spp_admin_bots_route_url(array $params = array()): string
{
    $base = array(
        'n' => 'admin',
        'sub' => 'bots',
    );

    return 'index.php?' . http_build_query(array_merge($base, $params), '', '&');
}

function spp_admin_bots_root_path(): string
{
    return dirname(__DIR__, 2);
}

function spp_admin_bots_state_path(): string
{
    return spp_admin_bots_root_path() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bot_maintenance_state.json';
}

function spp_admin_bots_resolve_php_cli_binary(): string
{
    $candidates = array_filter(array_unique(array(
        (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php.exe' : ''),
        (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php' : ''),
        (string)(PHP_BINARY ?? ''),
        'php',
    )));

    foreach ($candidates as $candidate) {
        $normalized = strtolower(str_replace('\\', '/', (string)$candidate));
        if ($normalized !== '' && (strpos($normalized, 'httpd') !== false || strpos($normalized, 'apache') !== false)) {
            continue;
        }
        if ($candidate === 'php') {
            return $candidate;
        }
        if (@is_file($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function spp_admin_bots_tool_script_name(string $action): string
{
    $map = array(
        'status' => 'bot_maintenance_status.php',
        'reset_forum_realm' => 'reset_forum_realm.php',
        'fresh_reset' => 'fresh_bot_reset.php',
        'rebuild_site_layers' => 'rebuild_bot_site_layers.php',
    );

    return (string)($map[$action] ?? 'bot_maintenance_status.php');
}

function spp_admin_bots_tool_script_path(string $action): string
{
    return spp_admin_bots_root_path() . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . spp_admin_bots_tool_script_name($action);
}

function spp_admin_bots_build_manual_command(string $action, array $payload = array()): string
{
    $phpBin = spp_admin_bots_resolve_php_cli_binary();
    $scriptPath = spp_admin_bots_tool_script_path($action);
    $escapedPhp = strtolower((string)$phpBin) === 'php'
        ? 'php'
        : ('"' . str_replace('"', '""', $phpBin) . '"');
    $escapedScript = '"' . str_replace('"', '""', $scriptPath) . '"';
    $parts = array($escapedPhp, $escapedScript);
    if (!empty($payload['realm_id'])) {
        $parts[] = '--realm=' . (int)$payload['realm_id'];
    }
    if (!empty($payload['execute'])) {
        $parts[] = '--execute';
    }
    if (!empty($payload['dry_run'])) {
        $parts[] = '--dry-run';
    }

    return implode(' ', $parts);
}

function spp_admin_bots_load_state(): array
{
    $path = spp_admin_bots_state_path();
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

function spp_admin_bots_save_state(array $state): bool
{
    $path = spp_admin_bots_state_path();
    $directory = dirname($path);
    if (!is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }

    return @file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function spp_admin_bots_helper_config(): array
{
    return array(
        'configured' => false,
        'transport' => 'cli',
        'url' => '',
        'token' => '',
        'timeout_sec' => 0,
        'display_name' => 'Manual CLI / PowerShell Scripts',
    );
}

function spp_admin_bots_count_files(string $path, string $pattern = '*'): int
{
    if (!is_dir($path)) {
        return 0;
    }

    $files = glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $pattern);
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

function spp_admin_bots_preview_cache_counts(): array
{
    $root = spp_admin_bots_root_path();
    $portraitDir = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'offlike' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'portraits';
    $coreCacheDir = $root . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'cache';
    $siteCacheDir = $coreCacheDir . DIRECTORY_SEPARATOR . 'sites';

    return array(
        'portrait_files' => spp_admin_bots_count_files($portraitDir, '*'),
        'core_cache_files' => spp_admin_bots_count_files($coreCacheDir, '*'),
        'site_cache_files' => spp_admin_bots_count_files($siteCacheDir, '*'),
        'portrait_dir' => $portraitDir,
        'core_cache_dir' => $coreCacheDir,
    );
}

function spp_admin_bots_realm_options(array $realmDbMap): array
{
    $options = array();
    foreach ($realmDbMap as $realmId => $realmInfo) {
        $realmId = (int)$realmId;
        $options[] = array(
            'realm_id' => $realmId,
            'label' => (string)(spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId)),
        );
    }

    usort($options, function (array $left, array $right): int {
        return (int)($left['realm_id'] ?? 0) <=> (int)($right['realm_id'] ?? 0);
    });

    return $options;
}

function spp_admin_bots_realm_forum_scope(int $realmId): array
{
    $map = array(
        1 => array('forum_id' => 2, 'forum_name' => 'Classic'),
        2 => array('forum_id' => 3, 'forum_name' => 'The Burning Crusade'),
        3 => array('forum_id' => 4, 'forum_name' => 'Wrath of the Lich King'),
    );

    return $map[$realmId] ?? array('forum_id' => 0, 'forum_name' => '');
}

function spp_admin_bots_forum_ids_for_realm(int $realmId, PDO $pdo): array
{
    $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
    $mainScope = spp_admin_bots_realm_forum_scope($realmId);
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

function spp_admin_bots_account_counts(PDO $masterPdo): array
{
    $counts = array(
        'bot_accounts' => 0,
        'human_accounts' => 0,
        'gm_accounts' => 0,
        'website_users' => 0,
    );

    if (!spp_admin_identity_health_table_exists($masterPdo, 'account')) {
        return $counts;
    }

    $counts['bot_accounts'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `account` WHERE LOWER(`username`) LIKE 'rndbot%'");
    $counts['human_accounts'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `account` WHERE LOWER(`username`) NOT LIKE 'rndbot%'");

    if (spp_admin_identity_health_table_exists($masterPdo, 'account_access')) {
        $counts['gm_accounts'] = spp_admin_identity_health_scalar(
            $masterPdo,
            "SELECT COUNT(DISTINCT `id`) FROM `account_access` WHERE `gmlevel` >= 4"
        );
    }

    if (spp_admin_identity_health_table_exists($masterPdo, 'website_accounts')) {
        $counts['website_users'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_accounts`");
    }

    return $counts;
}

function spp_admin_bots_scalar_safe(?PDO $pdo, string $sql, array $params = array()): int
{
    if (!$pdo instanceof PDO) {
        return 0;
    }

    try {
        return spp_admin_identity_health_scalar($pdo, $sql, $params);
    } catch (Throwable $e) {
        return 0;
    }
}

function spp_admin_bots_realm_preview_row(PDO $masterPdo, int $realmId, ?PDO $charsPdo, ?PDO $forumPdo, ?PDO $realmdPdo): array
{
    $forumScope = spp_admin_bots_realm_forum_scope($realmId);
    $realmForumId = (int)($forumScope['forum_id'] ?? 0);
    $realmForumIds = $realmdPdo instanceof PDO ? spp_admin_bots_forum_ids_for_realm($realmId, $realmdPdo) : array();

    $row = array(
        'realm_id' => $realmId,
        'realm_name' => (string)(spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId)),
        'available' => $charsPdo instanceof PDO && $realmdPdo instanceof PDO,
        'realm_forum_id' => $realmForumId,
        'realm_forum_ids' => $realmForumIds,
        'realm_forum_name' => (string)($forumScope['forum_name'] ?? ''),
        'bot_characters' => 0,
        'player_characters' => 0,
        'bot_guilds' => 0,
        'bot_db_store_rows' => 0,
        'bot_auction_rows' => 0,
        'forum_topics' => 0,
        'forum_posts' => 0,
        'forum_pms' => 0,
        'bot_forum_posts' => 0,
        'bot_forum_topics' => 0,
        'preserved_forum_posts' => 0,
        'preserved_forum_topics' => 0,
        'bot_identities' => 0,
        'bot_identity_profiles' => 0,
        'rotation_log_rows' => 0,
        'rotation_state_rows' => 0,
        'rotation_config_rows' => 0,
        'warning' => '',
    );

    if (!$charsPdo instanceof PDO || !$realmdPdo instanceof PDO) {
        $row['warning'] = 'Realm databases are not currently reachable.';
        return $row;
    }

    $realmdConfig = spp_get_db_config('realmd', $realmId);
    $realmdDbName = '`' . str_replace('`', '``', (string)$realmdConfig['name']) . '`';
    $botAccountSubquery = "SELECT `id` FROM {$realmdDbName}.`account` WHERE LOWER(`username`) LIKE 'rndbot%'";

    $row['bot_characters'] = spp_admin_bots_scalar_safe(
        $charsPdo,
        "SELECT COUNT(*) FROM `characters` WHERE `account` IN ({$botAccountSubquery})"
    );
    $row['player_characters'] = spp_admin_bots_scalar_safe(
        $charsPdo,
        "SELECT COUNT(*) FROM `characters` WHERE `account` NOT IN ({$botAccountSubquery})"
    );
    $row['bot_guilds'] = spp_admin_bots_scalar_safe(
        $charsPdo,
        "SELECT COUNT(DISTINCT gm.`guildid`)
         FROM `guild_member` gm
         INNER JOIN `characters` c ON c.`guid` = gm.`guid`
         WHERE c.`account` IN ({$botAccountSubquery})"
    );
    $row['bot_db_store_rows'] = spp_admin_bots_scalar_safe(
        $charsPdo,
        "SELECT COUNT(*)
         FROM `ai_playerbot_db_store` s
         INNER JOIN `characters` c ON c.`guid` = s.`guid`
         WHERE c.`account` IN ({$botAccountSubquery})"
    );
    if (spp_admin_identity_health_table_exists($charsPdo, 'auctionhouse')) {
        $row['bot_auction_rows'] = spp_admin_bots_scalar_safe(
            $charsPdo,
            "SELECT COUNT(*)
             FROM `auctionhouse`
             WHERE `itemowner` IN (SELECT `guid` FROM `characters` WHERE `account` IN ({$botAccountSubquery}))
                OR `buyguid` IN (SELECT `guid` FROM `characters` WHERE `account` IN ({$botAccountSubquery}))"
        );
    }

    if ($realmdPdo instanceof PDO && !empty($realmForumIds)) {
        $placeholders = implode(',', array_fill(0, count($realmForumIds), '?'));
        if (spp_admin_identity_health_table_exists($realmdPdo, 'f_topics')) {
            $row['forum_topics'] = spp_admin_bots_scalar_safe($realmdPdo, "SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` IN ({$placeholders})", $realmForumIds);
            $row['preserved_forum_topics'] = spp_admin_bots_scalar_safe(
                $realmdPdo,
                "SELECT COUNT(*)
                 FROM `f_topics`
                 WHERE `topic_poster_id` = 0
                   AND LOWER(TRIM(`topic_poster`)) IN ('web team', 'spp team')
                   AND `forum_id` IN ({$placeholders})",
                $realmForumIds
            );
            if (spp_admin_identity_health_column_exists($realmdPdo, 'f_topics', 'topic_poster_identity_id')
                && spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
                $row['bot_forum_topics'] = spp_admin_bots_scalar_safe(
                    $realmdPdo,
                    "SELECT COUNT(*)
                     FROM `f_topics` t
                     INNER JOIN `website_identities` i ON i.`identity_id` = t.`topic_poster_identity_id`
                     WHERE t.`forum_id` IN ({$placeholders})
                       AND i.`realm_id` = ? AND (i.`identity_type` = 'bot_character' OR i.`is_bot` = 1)",
                    array_merge($realmForumIds, array($realmId))
                );
            }
        }

        if (spp_admin_identity_health_table_exists($realmdPdo, 'f_posts')
            && spp_admin_identity_health_table_exists($realmdPdo, 'f_topics')) {
            $row['forum_posts'] = spp_admin_bots_scalar_safe(
                $realmdPdo,
                "SELECT COUNT(*)
                 FROM `f_posts` p
                 INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
                 WHERE t.`forum_id` IN ({$placeholders})",
                $realmForumIds
            );
            $row['preserved_forum_posts'] = spp_admin_bots_scalar_safe(
                $realmdPdo,
                "SELECT COUNT(*)
                 FROM `f_posts`
                 WHERE `poster_id` = 0
                   AND (`poster_character_id` IS NULL OR `poster_character_id` = 0)
                   AND LOWER(TRIM(`poster`)) IN ('web team', 'spp team')
                   AND `topic_id` IN (SELECT `topic_id` FROM `f_topics` WHERE `forum_id` IN ({$placeholders}))",
                $realmForumIds
            );
            if (spp_admin_identity_health_column_exists($realmdPdo, 'f_posts', 'poster_identity_id')
                && spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
                $row['bot_forum_posts'] = spp_admin_bots_scalar_safe(
                    $realmdPdo,
                    "SELECT COUNT(*)
                     FROM `f_posts` p
                     INNER JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
                     INNER JOIN `website_identities` i ON i.`identity_id` = p.`poster_identity_id`
                     WHERE t.`forum_id` IN ({$placeholders})
                       AND i.`realm_id` = ? AND (i.`identity_type` = 'bot_character' OR i.`is_bot` = 1)",
                    array_merge($realmForumIds, array($realmId))
                );
            }
        }
    }

    if (spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
        $row['bot_identities'] = spp_admin_bots_scalar_safe(
            $masterPdo,
            "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)",
            array($realmId)
        );
    }

    if (spp_admin_identity_health_table_exists($masterPdo, 'website_identity_profiles')
        && spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
        $row['bot_identity_profiles'] = spp_admin_bots_scalar_safe(
            $masterPdo,
            "SELECT COUNT(*)
             FROM `website_identity_profiles` p
             INNER JOIN `website_identities` i ON i.`identity_id` = p.`identity_id`
             WHERE i.`realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)",
            array($realmId)
        );
    }

    if ($realmdPdo instanceof PDO) {
        if (spp_admin_identity_health_table_exists($realmdPdo, 'bot_rotation_log')) {
            $row['rotation_log_rows'] = spp_admin_bots_scalar_safe($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_log` WHERE `realm` = ?", array($realmId));
        }
        if (spp_admin_identity_health_table_exists($realmdPdo, 'bot_rotation_state')) {
            $row['rotation_state_rows'] = spp_admin_bots_scalar_safe($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_state` WHERE `realm` = ?", array($realmId));
        }
        if (spp_admin_identity_health_table_exists($realmdPdo, 'bot_rotation_config')) {
            $row['rotation_config_rows'] = spp_admin_bots_scalar_safe($realmdPdo, "SELECT COUNT(*) FROM `bot_rotation_config` WHERE `realm` = ?", array($realmId));
        }
    }

    return $row;
}
