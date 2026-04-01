<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.identities.helpers.php');

if (!function_exists('spp_admin_botrotation_resolve_php_cli_binary')) {
    function spp_admin_botrotation_resolve_php_cli_binary(): string
    {
        $candidates = array_filter(array_unique(array(
            (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php.exe' : ''),
            (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php' : ''),
            (string)(PHP_BINARY ?? ''),
            '/usr/bin/php',
            '/usr/local/bin/php',
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
}

if (!function_exists('rotFormatUptimeSeconds')) {
    function rotFormatUptimeSeconds($seconds) {
        if ($seconds === null || $seconds === '' || !is_numeric($seconds) || $seconds <= 0) return 'N/A';
        $seconds = (int)floor((float)$seconds);
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        if ($days > 0)    return $days . 'd ' . $hours . 'h';
        if ($hours > 0)   return $hours . 'h ' . $minutes . 'm';
        if ($minutes > 0) return $minutes . 'm';
        return $seconds . 's';
    }
}

if (!function_exists('spp_admin_botrotation_fetch_uptime_windows')) {
    function spp_admin_botrotation_fetch_uptime_windows(PDO $realmPdo, int $realmId, int $days = 7): array
    {
        $stmt = $realmPdo->prepare("
            SELECT `uptime`, `starttime`
            FROM `uptime`
            WHERE `realmid` = ?
              AND `starttime` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$days} DAY))
            ORDER BY `starttime` DESC
        ");
        $stmt->execute([$realmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
    }
}

if (!function_exists('spp_admin_botrotation_calculate_uptime_summary')) {
    function spp_admin_botrotation_calculate_uptime_summary(array $uptimeRows, int $stableThresholdSec = 900): array
    {
        $summary = array(
            'sample_count' => count($uptimeRows),
            'median_uptime_sec' => null,
            'stable_avg_uptime_hours' => null,
            'short_restarts' => 0,
            'stable_runs' => 0,
            'stable_threshold_sec' => $stableThresholdSec,
        );

        if (empty($uptimeRows)) {
            return $summary;
        }

        $all = array();
        $stable = array();
        foreach ($uptimeRows as $row) {
            $uptime = isset($row['uptime']) ? (int)$row['uptime'] : 0;
            if ($uptime <= 0) {
                $summary['short_restarts']++;
                continue;
            }
            $all[] = $uptime;
            if ($uptime < $stableThresholdSec) {
                $summary['short_restarts']++;
            } else {
                $stable[] = $uptime;
            }
        }

        if (!empty($all)) {
            sort($all, SORT_NUMERIC);
            $mid = (int)floor(count($all) / 2);
            $summary['median_uptime_sec'] = (count($all) % 2 === 0)
                ? (int)round(($all[$mid - 1] + $all[$mid]) / 2)
                : (int)$all[$mid];
        }

        if (!empty($stable)) {
            $summary['stable_runs'] = count($stable);
            $summary['stable_avg_uptime_hours'] = round((array_sum($stable) / count($stable)) / 3600, 2);
        }

        return $summary;
    }
}

if (!function_exists('spp_admin_botrotation_calculate_clean_history')) {
    function spp_admin_botrotation_calculate_clean_history(array $historyRows, int $stableThresholdSec = 900): array
    {
        $result = array(
            'avg_online_sec' => null,
            'avg_offline_sec' => null,
            'online_sessions' => 0,
            'offline_sessions' => 0,
            'snapshot_count' => 0,
            'skipped_snapshot_count' => 0,
            'stable_threshold_sec' => $stableThresholdSec,
        );

        $onlineWeighted = 0.0;
        $offlineWeighted = 0.0;

        foreach ($historyRows as $row) {
            $uptimeSec = isset($row['server_uptime_sec']) ? (int)$row['server_uptime_sec'] : 0;
            if ($uptimeSec < $stableThresholdSec) {
                $result['skipped_snapshot_count']++;
                continue;
            }

            $result['snapshot_count']++;

            $rowOnlineSessions = isset($row['observed_online_sessions']) ? (int)$row['observed_online_sessions'] : 0;
            $rowOfflineSessions = isset($row['observed_offline_sessions']) ? (int)$row['observed_offline_sessions'] : 0;
            $rowOnlineAvg = isset($row['observed_avg_online_sec']) && $row['observed_avg_online_sec'] !== '' ? (float)$row['observed_avg_online_sec'] : null;
            $rowOfflineAvg = isset($row['observed_avg_offline_sec']) && $row['observed_avg_offline_sec'] !== '' ? (float)$row['observed_avg_offline_sec'] : null;

            if ($rowOnlineAvg !== null && $rowOnlineSessions > 0) {
                $onlineWeighted += ($rowOnlineAvg * $rowOnlineSessions);
                $result['online_sessions'] += $rowOnlineSessions;
            }
            if ($rowOfflineAvg !== null && $rowOfflineSessions > 0) {
                $offlineWeighted += ($rowOfflineAvg * $rowOfflineSessions);
                $result['offline_sessions'] += $rowOfflineSessions;
            }
        }

        if ($result['online_sessions'] > 0) {
            $result['avg_online_sec'] = round($onlineWeighted / $result['online_sessions'], 1);
        }
        if ($result['offline_sessions'] > 0) {
            $result['avg_offline_sec'] = round($offlineWeighted / $result['offline_sessions'], 1);
        }

        return $result;
    }
}

if (!function_exists('spp_admin_botrotation_fill_character_name')) {
    function spp_admin_botrotation_fill_character_name(PDO $charPdo, ?array $row): ?array
    {
        if (empty($row) || empty($row['bot_guid'])) {
            return $row;
        }

        if (!empty($row['bot_name'])) {
            return $row;
        }

        try {
            $stmt = $charPdo->prepare('SELECT `name` FROM `characters` WHERE `guid` = ? LIMIT 1');
            $stmt->execute(array((int)$row['bot_guid']));
            $name = $stmt->fetchColumn();
            if ($name !== false && $name !== null && $name !== '') {
                $row['bot_name'] = (string)$name;
            }
        } catch (Exception $e) {
            return $row;
        }

        return $row;
    }
}

function spp_admin_botrotation_build_view(array $realmDbMap)
{
    $realmId = spp_resolve_realm_id($realmDbMap);
    $siteRoot = dirname(__DIR__, 2);
    $phpBin = spp_admin_botrotation_resolve_php_cli_binary();
    $isWindowsHost = DIRECTORY_SEPARATOR === '\\';
    $phpCommand = strtolower((string)$phpBin) === 'php'
        ? 'php'
        : ('"' . str_replace('"', '""', (string)$phpBin) . '"');
    $rotationResetScript = $siteRoot . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'reset_bot_rotation_realm.php';

    $view = array(
        'realmId' => $realmId,
        'rotationData' => null,
        'rotationError' => null,
        'rotationConfig' => null,
        'latestHistory' => null,
        'topBotData' => null,
        'longestOnlineBot' => null,
        'longestOfflineBot' => null,
        'totalServerUptime' => 'N/A',
        'currentRunSec' => null,
        'restartsToday' => null,
        'historyRows' => array(),
        'hasHistory' => false,
        'liveOnlineAvg' => null,
        'liveOnlineMax' => null,
        'uptimeSummary' => array(),
        'cleanHistory' => array(),
        'isWindowsHost' => $isWindowsHost,
        'commands' => array(
            'rotation_reset_dry_run' => $phpCommand . ' "' . str_replace('"', '""', $rotationResetScript) . '" --realm=' . $realmId . ' --execute --dry-run',
            'rotation_reset_run' => $phpCommand . ' "' . str_replace('"', '""', $rotationResetScript) . '" --realm=' . $realmId . ' --execute',
            'pause_logging' => "mv /etc/cron.d/spp-bot-rotation-log /etc/cron.d/spp-bot-rotation-log.disabled && systemctl restart cron",
            'resume_logging' => "mv /etc/cron.d/spp-bot-rotation-log.disabled /etc/cron.d/spp-bot-rotation-log && systemctl restart cron",
        ),
    );

    $realmdDbName = $realmDbMap[$realmId]['realmd'] ?? 'classicrealmd';

    try {
        $statCharPdo = spp_get_pdo('chars', $realmId);

        $stmtRot = $statCharPdo->prepare("
            SELECT
              COUNT(*)                                                                    AS total_bots,
              SUM(CASE WHEN online = 1 THEN 1 ELSE 0 END)                               AS total_online,
              SUM(CASE WHEN online = 1 AND xp > 0 THEN 1 ELSE 0 END)                   AS rotating_active,
              SUM(CASE WHEN online = 1 AND xp = 0 THEN 1 ELSE 0 END)                   AS online_idle,
              SUM(CASE WHEN online = 0 AND xp > 0 THEN 1 ELSE 0 END)                   AS cycled_off_progressed,
              SUM(CASE WHEN online = 0 AND xp = 0 THEN 1 ELSE 0 END)                   AS never_progressed,
              ROUND(
                SUM(CASE WHEN xp > 0 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) * 100
              , 1)                                                                        AS pct_ever_rotated,
              ROUND(
                SUM(CASE WHEN online = 1 AND xp > 0 THEN 1 ELSE 0 END) /
                NULLIF(SUM(CASE WHEN online = 1 THEN 1 ELSE 0 END), 0) * 100
              , 1)                                                                        AS pct_online_rotating,
              ROUND(AVG(CASE WHEN xp > 0 THEN level END), 1)                            AS avg_level_rotating,
              MAX(CASE WHEN xp > 0 THEN level END)                                       AS highest_level,
              ROUND(AVG(CASE
                WHEN online = 0
                 AND logout_time > 0
                 AND NOT (level = 1 AND xp = 0)
                THEN UNIX_TIMESTAMP() - logout_time
              END), 1)                                                                   AS current_avg_offline_sec,
              MAX(CASE
                WHEN online = 0
                 AND logout_time > 0
                 AND NOT (level = 1 AND xp = 0)
                THEN UNIX_TIMESTAMP() - logout_time
              END)                                                                       AS current_max_offline_sec
            FROM characters
            WHERE account IN (
              SELECT id FROM {$realmdDbName}.account WHERE username LIKE 'RNDBOT%'
            )
        ");
        $stmtRot->execute();
        $rotRows = $stmtRot->fetchAll(PDO::FETCH_ASSOC);
        $view['rotationData'] = !empty($rotRows) ? $rotRows[0] : null;

        $stmtTopBot = $statCharPdo->prepare("
            SELECT name, level, xp, totaltime
            FROM characters
            WHERE xp > 0
              AND account IN (
                SELECT id FROM {$realmdDbName}.account WHERE username LIKE 'RNDBOT%'
              )
            ORDER BY level DESC, xp DESC, totaltime DESC, name ASC
            LIMIT 1
        ");
        $stmtTopBot->execute();
        $view['topBotData'] = $stmtTopBot->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        $view['rotationError'] = $e->getMessage();
    }

    $statRealmPdo = spp_get_pdo('realmd', $realmId);

    try {
        $stmtCfg = $statRealmPdo->prepare("SELECT * FROM bot_rotation_config WHERE realm = ? LIMIT 1");
        $stmtCfg->execute([$realmId]);
        $view['rotationConfig'] = $stmtCfg->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        $view['rotationConfig'] = null;
    }

    try {
        $uptimeRows = spp_admin_botrotation_fetch_uptime_windows($statRealmPdo, (int)$realmId);
        $view['uptimeSummary'] = spp_admin_botrotation_calculate_uptime_summary($uptimeRows);

        $stmtUptime = $statRealmPdo->prepare("
            SELECT COALESCE(SUM(uptime), 0) AS stored_uptime, MAX(starttime) AS latest_starttime
            FROM uptime WHERE realmid = ?
        ");
        $stmtUptime->execute([$realmId]);
        $uptimeRow = $stmtUptime->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($uptimeRow) {
            $storedUptime = (int)($uptimeRow['stored_uptime'] ?? 0);
            $latestStart = (int)($uptimeRow['latest_starttime'] ?? 0);
            $currentRun = $latestStart > 0 ? max(0, time() - $latestStart) : 0;
            $view['currentRunSec'] = $currentRun;
            $view['totalServerUptime'] = rotFormatUptimeSeconds($storedUptime + $currentRun);
        }

        $stmtRestartsToday = $statRealmPdo->prepare("
            SELECT COUNT(*) AS restarts_today FROM uptime
            WHERE realmid = ? AND FROM_UNIXTIME(starttime) >= CURDATE()
        ");
        $stmtRestartsToday->execute([$realmId]);
        $restartRow = $stmtRestartsToday->fetch(PDO::FETCH_ASSOC) ?: null;
        $view['restartsToday'] = isset($restartRow['restarts_today']) ? (int)$restartRow['restarts_today'] : null;
    } catch (Exception $e) {
        $view['totalServerUptime'] = 'N/A';
    }

    try {
        $stmtHist = $statRealmPdo->prepare("
            SELECT snapshot_time, server_uptime_sec, pct_online_rotating, pct_ever_rotated,
                   total_online, rotating_active, avg_level_rotating,
                   avg_equipped_ilvl_bots, avg_equipped_ilvl_server,
                   cfg_expected_online_pct, cfg_avg_in_world_sec, cfg_avg_offline_sec,
                   observed_avg_online_sec, observed_avg_offline_sec,
                   observed_online_sessions, observed_offline_sessions
            FROM bot_rotation_log
            WHERE realm = ?
            ORDER BY snapshot_time DESC
            LIMIT 48
        ");
        $stmtHist->execute([$realmId]);
        $view['historyRows'] = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
        $view['hasHistory'] = !empty($view['historyRows']);
        $view['latestHistory'] = $view['hasHistory'] ? $view['historyRows'][0] : null;
        $view['cleanHistory'] = spp_admin_botrotation_calculate_clean_history($view['historyRows']);
    } catch (Exception $e) {
        $view['hasHistory'] = false;
    }

    try {
        $stmtLiveOnline = $statRealmPdo->prepare("
            SELECT
              ROUND(AVG(TIMESTAMPDIFF(SECOND, last_online_start, NOW())), 1) AS live_avg_online_sec,
              MAX(TIMESTAMPDIFF(SECOND, last_online_start, NOW()))           AS live_max_online_sec
            FROM bot_rotation_state
            WHERE realm = ? AND last_online = 1 AND last_online_start IS NOT NULL
        ");
        $stmtLiveOnline->execute([$realmId]);
        $liveOnlineRow = $stmtLiveOnline->fetch(PDO::FETCH_ASSOC) ?: null;
        $view['liveOnlineAvg'] = $liveOnlineRow['live_avg_online_sec'] ?? null;
        $view['liveOnlineMax'] = $liveOnlineRow['live_max_online_sec'] ?? null;

        $stmtLongestOnline = $statRealmPdo->prepare("
            SELECT bot_guid, '' AS bot_name,
                   TIMESTAMPDIFF(SECOND, last_online_start, NOW()) AS live_online_sec
            FROM bot_rotation_state
            WHERE realm = ?
              AND last_online = 1
              AND last_online_start IS NOT NULL
            ORDER BY live_online_sec DESC, bot_guid ASC
            LIMIT 1
        ");
        $stmtLongestOnline->execute([$realmId]);
        $view['longestOnlineBot'] = spp_admin_botrotation_fill_character_name($statCharPdo, $stmtLongestOnline->fetch(PDO::FETCH_ASSOC) ?: null);

        $stmtLongestOffline = $statRealmPdo->prepare("
            SELECT bot_guid, '' AS bot_name,
                   TIMESTAMPDIFF(SECOND, last_offline_start, NOW()) AS live_offline_sec
            FROM bot_rotation_state
            WHERE realm = ?
              AND last_online = 0
              AND last_offline_start IS NOT NULL
            ORDER BY live_offline_sec DESC, bot_guid ASC
            LIMIT 1
        ");
        $stmtLongestOffline->execute([$realmId]);
        $view['longestOfflineBot'] = spp_admin_botrotation_fill_character_name($statCharPdo, $stmtLongestOffline->fetch(PDO::FETCH_ASSOC) ?: null);
    } catch (Exception $e) {
        // table may not exist yet
    }

    return $view;
}
