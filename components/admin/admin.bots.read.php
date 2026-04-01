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
        'rotation_ilvl_log_rows' => 0,
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
        if (!empty($row['available'])) {
            $previewRows[] = $row;
        }
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

    $realmOptions = array();
    foreach ($previewRows as $previewRow) {
        $realmOptions[] = array(
            'realm_id' => (int)($previewRow['realm_id'] ?? 0),
            'label' => (string)($previewRow['realm_name'] ?? ('Realm ' . (int)($previewRow['realm_id'] ?? 0))),
        );
    }

    $selectedRealmId = (int)($selectedPreview['realm_id'] ?? $selectedRealmId);
    $scriptCommands = spp_admin_bots_script_commands_for_realm($selectedRealmId);
    $stepPreviews = array(
        'forum_reset' => array(
            'topics' => (int)($selectedPreview['forum_topics'] ?? 0),
            'posts' => (int)($selectedPreview['forum_posts'] ?? 0),
            'pms' => (int)($selectedPreview['forum_pms'] ?? 0),
            'bot_topics' => (int)($selectedPreview['bot_forum_topics'] ?? 0),
            'bot_posts' => (int)($selectedPreview['bot_forum_posts'] ?? 0),
            'preserved_topics' => (int)($selectedPreview['preserved_forum_topics'] ?? 0),
            'preserved_posts' => (int)($selectedPreview['preserved_forum_posts'] ?? 0),
            'forum_ids' => (array)($selectedPreview['realm_forum_ids'] ?? array()),
        ),
        'web_state' => array(
            'bot_events' => (int)($eventCounts['website_bot_events'] ?? 0),
            'bot_identities' => (int)($selectedPreview['bot_identities'] ?? 0),
            'bot_identity_profiles' => (int)($selectedPreview['bot_identity_profiles'] ?? 0),
            'portrait_files' => (int)($cacheCounts['portrait_files'] ?? 0),
        ),
        'character_state' => array(
            'bot_characters' => (int)($selectedPreview['bot_characters'] ?? 0),
            'bot_guilds' => (int)($selectedPreview['bot_guilds'] ?? 0),
            'bot_db_store_rows' => (int)($selectedPreview['bot_db_store_rows'] ?? 0),
            'bot_auction_rows' => (int)($selectedPreview['bot_auction_rows'] ?? 0),
            'rotation_state_rows' => (int)($selectedPreview['rotation_state_rows'] ?? 0),
            'rotation_log_rows' => (int)($selectedPreview['rotation_log_rows'] ?? 0),
            'rotation_ilvl_log_rows' => (int)($selectedPreview['rotation_ilvl_log_rows'] ?? 0),
        ),
        'rotation_only' => array(
            'rotation_state_rows' => (int)($selectedPreview['rotation_state_rows'] ?? 0),
            'rotation_log_rows' => (int)($selectedPreview['rotation_log_rows'] ?? 0),
            'rotation_ilvl_log_rows' => (int)($selectedPreview['rotation_ilvl_log_rows'] ?? 0),
        ),
    );

    return array(
        'page_url' => spp_admin_bots_route_url(),
        'selected_realm_id' => $selectedRealmId,
        'selected_preview' => $selectedPreview,
        'realm_options' => !empty($realmOptions) ? $realmOptions : spp_admin_bots_realm_options($realmDbMap),
        'helper_config' => $helperConfig,
        'helper_status' => $helperStatus,
        'last_run' => $state['last_run'] ?? array(),
        'flash' => $actionState['flash'] ?? array(),
        'manual_notice' => (string)($actionState['manual_notice'] ?? ''),
        'manual_command' => (string)($actionState['manual_command'] ?? ''),
        'script_commands' => $scriptCommands,
        'step_previews' => $stepPreviews,
        'preview_rows' => $previewRows,
        'account_counts' => $accountCounts,
        'cache_counts' => $cacheCounts,
        'event_counts' => $eventCounts,
        'totals' => $totals,
    );
}
