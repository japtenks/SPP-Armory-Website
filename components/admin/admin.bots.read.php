<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_bots_build_view(PDO $masterPdo, array $realmDbMap, array $actionState = array()): array
{
    $selectedRealmId = spp_resolve_realm_id($realmDbMap);
    $state = spp_admin_bots_load_state();
    $helperConfig = spp_admin_bots_helper_config();
    $helperStatus = $state['helper_status'] ?? array();
    $refreshStatus = !empty($_GET['refresh_helper']) || !empty($actionState['refresh_status']);
    if ($refreshStatus && !empty($helperConfig['configured'])) {
        $helperStatus = spp_admin_bots_ping_helper_status();
    }

    $accountCounts = spp_admin_bots_account_counts($masterPdo);
    $cacheCounts = spp_admin_bots_preview_cache_counts();
    $previewRows = array();
    $totals = array(
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
    );

    foreach ($realmDbMap as $realmId => $realmInfo) {
        $realmId = (int)$realmId;
        $charsPdo = null;
        $forumPdo = null;
        $realmdPdo = null;

        try {
            $charsPdo = spp_get_pdo('chars', $realmId);
        } catch (Throwable $e) {
            $charsPdo = null;
        }

        try {
            $forumPdo = spp_get_pdo('realmd', 1);
        } catch (Throwable $e) {
            $forumPdo = null;
        }

        try {
            $realmdPdo = spp_get_pdo('realmd', $realmId);
        } catch (Throwable $e) {
            $realmdPdo = null;
        }

        $row = spp_admin_bots_realm_preview_row($masterPdo, $realmId, $charsPdo, $forumPdo, $realmdPdo);
        $previewRows[] = $row;
        foreach ($totals as $key => $value) {
            $totals[$key] += (int)($row[$key] ?? 0);
        }
    }

    $selectedPreview = array();
    foreach ($previewRows as $previewRow) {
        if ((int)($previewRow['realm_id'] ?? 0) === (int)$selectedRealmId) {
            $selectedPreview = $previewRow;
            break;
        }
    }
    if (empty($selectedPreview) && !empty($previewRows[0])) {
        $selectedPreview = $previewRows[0];
        $selectedRealmId = (int)($selectedPreview['realm_id'] ?? $selectedRealmId);
    }

    $eventCounts = array(
        'website_bot_events' => 0,
    );
    if (spp_admin_identity_health_table_exists($masterPdo, 'website_bot_events')) {
        $eventCounts['website_bot_events'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_bot_events`");
    }

    return array(
        'page_url' => spp_admin_bots_route_url(),
        'selected_realm_id' => $selectedRealmId,
        'selected_preview' => $selectedPreview,
        'realm_options' => spp_admin_bots_realm_options($realmDbMap),
        'helper_config' => $helperConfig,
        'helper_status' => $helperStatus,
        'last_run' => $state['last_run'] ?? array(),
        'flash' => $actionState['flash'] ?? array(),
        'manual_notice' => (string)($actionState['manual_notice'] ?? ''),
        'manual_command' => (string)($actionState['manual_command'] ?? ''),
        'preview_rows' => $previewRows,
        'account_counts' => $accountCounts,
        'cache_counts' => $cacheCounts,
        'event_counts' => $eventCounts,
        'totals' => $totals,
    );
}
