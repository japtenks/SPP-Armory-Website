<?php
if (INCLUDED !== true) {
    exit;
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

function spp_admin_botrotation_build_view(array $realmDbMap)
{
    $realmId = spp_resolve_realm_id($realmDbMap);

    $view = array(
        'realmId' => $realmId,
        'rotationData' => null,
        'rotationError' => null,
        'rotationConfig' => null,
        'latestHistory' => null,
        'topBotData' => null,
        'totalServerUptime' => 'N/A',
        'currentRunSec' => null,
        'restartsToday' => null,
        'historyRows' => array(),
        'hasHistory' => false,
        'liveOnlineAvg' => null,
        'liveOnlineMax' => null,
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
            SELECT snapshot_time, pct_online_rotating, pct_ever_rotated,
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
    } catch (Exception $e) {
        // table may not exist yet
    }

    return $view;
}
