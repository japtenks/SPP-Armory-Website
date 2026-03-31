<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_botevents_build_view(PDO $masterPdo, array $selectedEventTypes, array $realmDbMap, ?array $configOverride = null): array
{
    $botStats = array();
    $recentEvents = array();
    $availableEventTypes = array();
    $pendingTypeBreakdown = array();
    $statsError = '';
    $configLoad = spp_admin_botevents_load_config();
    $botConfig = $configOverride ?? ($configLoad['config'] ?? spp_admin_botevents_default_config());
    $realmOptions = spp_admin_botevents_realm_options($realmDbMap, $botConfig);
    $botConfig = spp_admin_botevents_normalize_config_for_form($botConfig, $realmOptions);
    $achievementCatalog = spp_admin_botevents_build_achievement_catalog($realmDbMap, $botConfig);

    try {
        $stmt = $masterPdo->query("
            SELECT DISTINCT event_type
            FROM website_bot_events
            ORDER BY event_type ASC
        ");
        $availableEventTypes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: array();
        $availableEventTypes = array_values(array_unique(array_filter(array_map('strval', $availableEventTypes))));

        $stmt = $masterPdo->query("
            SELECT status, COUNT(*) AS cnt
            FROM website_bot_events
            GROUP BY status
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $botStats[$row['status']] = (int)$row['cnt'];
        }

        $stmt = $masterPdo->query("
            SELECT event_type, COUNT(*) AS cnt
            FROM website_bot_events
            WHERE status = 'pending'
            GROUP BY event_type
            ORDER BY cnt DESC, event_type ASC
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pendingTypeBreakdown[] = [
                'event_type' => (string)$row['event_type'],
                'count' => (int)$row['cnt'],
            ];
        }

        $stmt = $masterPdo->query("
            SELECT event_id, event_type, realm_id, status, occurred_at, processed_at, error_message,
                   payload_json
            FROM website_bot_events
            ORDER BY event_id DESC
            LIMIT 30
        ");
        $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $statsError = "\nStats query failed: " . $e->getMessage();
    }

    if (empty($availableEventTypes)) {
        $availableEventTypes = [
            'achievement_badge',
            'guild_created',
            'guild_roster_update',
            'level_up',
            'profession_milestone',
        ];
    }

    return array(
        'botStats' => $botStats,
        'recentEvents' => $recentEvents,
        'availableEventTypes' => $availableEventTypes,
        'selectedEventTypes' => array_values(array_intersect($selectedEventTypes, $availableEventTypes)),
        'pendingTypeBreakdown' => $pendingTypeBreakdown,
        'statsError' => $statsError,
        'botConfig' => $botConfig,
        'realmOptions' => $realmOptions,
        'configPath' => spp_admin_botevents_config_path(),
        'configLoadError' => (string)($configLoad['error'] ?? ''),
        'configWritable' => is_writable(spp_admin_botevents_config_path()),
        'achievementCatalog' => $achievementCatalog,
    );
}
