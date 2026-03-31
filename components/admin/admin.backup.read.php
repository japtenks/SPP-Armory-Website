<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_backup_build_view(array $realmDbMap, ?array $request = null): array
{
    $request = is_array($request) ? $request : $_POST;
    $realmOptions = spp_admin_backup_realm_options($realmDbMap);
    $xferRouteOptions = spp_admin_backup_xfer_route_options($realmOptions);
    $defaultSourceRealmId = !empty($realmOptions[0]['id']) ? (int)$realmOptions[0]['id'] : 0;
    $sourceRealmId = (int)($request['source_realm_id'] ?? $defaultSourceRealmId);
    if (!isset($realmDbMap[$sourceRealmId])) {
        $sourceRealmId = $defaultSourceRealmId;
    }

    $selectedXferRouteId = (string)($request['xfer_route'] ?? '');
    $selectedXferRoute = null;
    foreach ($xferRouteOptions as $routeOption) {
        if ((string)$routeOption['id'] === $selectedXferRouteId) {
            $selectedXferRoute = $routeOption;
            break;
        }
    }
    if ($selectedXferRoute === null && !empty($xferRouteOptions[0])) {
        $selectedXferRoute = $xferRouteOptions[0];
        $selectedXferRouteId = (string)$selectedXferRoute['id'];
    }
    if ($selectedXferRoute !== null) {
        $sourceRealmId = (int)$selectedXferRoute['source_realm_id'];
        $targetRealmId = (int)$selectedXferRoute['target_realm_id'];
    } else {
        $targetRealmId = 0;
    }

    $backupEntityType = (string)($request['backup_entity_type'] ?? 'character');
    $xferEntityType = (string)($request['xfer_entity_type'] ?? 'character');
    $entityOptions = spp_admin_backup_entity_options();
    if (!isset($entityOptions[$backupEntityType])) {
        $backupEntityType = 'character';
    }
    if (!isset($entityOptions[$xferEntityType])) {
        $xferEntityType = 'character';
    }

    $sourceRealmdPdo = $sourceRealmId > 0 ? spp_get_pdo('realmd', $sourceRealmId) : null;
    $sourceCharsPdo = $sourceRealmId > 0 ? spp_get_pdo('chars', $sourceRealmId) : null;
    $targetRealmdPdo = $targetRealmId > 0 ? spp_get_pdo('realmd', $targetRealmId) : null;
    $targetCharsPdo = $targetRealmId > 0 ? spp_get_pdo('chars', $targetRealmId) : null;

    $sourceAccountOptions = $sourceRealmdPdo ? spp_admin_backup_fetch_accounts($sourceRealmdPdo) : array();
    $selectedAccountId = (int)($request['source_account_id'] ?? 0);
    if ($selectedAccountId <= 0 && !empty($sourceAccountOptions[0]['id'])) {
        $selectedAccountId = (int)$sourceAccountOptions[0]['id'];
    }

    $sourceCharacterOptions = $sourceCharsPdo ? spp_admin_backup_fetch_characters($sourceCharsPdo, $selectedAccountId) : array();
    $selectedCharacterGuid = (int)($request['source_character_guid'] ?? 0);
    if ($selectedCharacterGuid <= 0 && !empty($sourceCharacterOptions[0]['guid'])) {
        $selectedCharacterGuid = (int)$sourceCharacterOptions[0]['guid'];
    }

    $sourceGuildOptions = $sourceCharsPdo ? spp_admin_backup_fetch_guilds($sourceCharsPdo) : array();
    $selectedGuildId = (int)($request['source_guild_id'] ?? 0);
    if ($selectedGuildId <= 0 && !empty($sourceGuildOptions[0]['guildid'])) {
        $selectedGuildId = (int)$sourceGuildOptions[0]['guildid'];
    }

    $targetAccountOptions = $targetRealmdPdo ? spp_admin_backup_fetch_accounts($targetRealmdPdo) : array();
    $targetAccountId = (int)($request['target_account_id'] ?? 0);
    if ($targetAccountId <= 0 && !empty($targetAccountOptions[0]['id'])) {
        $targetAccountId = (int)$targetAccountOptions[0]['id'];
    }

    $selectedAccountRow = null;
    foreach ($sourceAccountOptions as $accountOption) {
        if ((int)$accountOption['id'] === $selectedAccountId) {
            $selectedAccountRow = $accountOption;
            break;
        }
    }

    $selectedCharacterRow = null;
    foreach ($sourceCharacterOptions as $characterOption) {
        if ((int)$characterOption['guid'] === $selectedCharacterGuid) {
            $selectedCharacterRow = $characterOption;
            break;
        }
    }

    $selectedGuildRow = null;
    foreach ($sourceGuildOptions as $guildOption) {
        if ((int)$guildOption['guildid'] === $selectedGuildId) {
            $selectedGuildRow = $guildOption;
            break;
        }
    }

    return array(
        'realm_options' => $realmOptions,
        'xfer_route_options' => $xferRouteOptions,
        'selected_xfer_route_id' => $selectedXferRouteId,
        'entity_options' => $entityOptions,
        'source_realm_id' => $sourceRealmId,
        'target_realm_id' => $targetRealmId,
        'backup_entity_type' => $backupEntityType,
        'xfer_entity_type' => $xferEntityType,
        'source_account_options' => $sourceAccountOptions,
        'source_character_options' => $sourceCharacterOptions,
        'source_guild_options' => $sourceGuildOptions,
        'target_account_options' => $targetAccountOptions,
        'selected_account_id' => $selectedAccountId,
        'selected_character_guid' => $selectedCharacterGuid,
        'selected_guild_id' => $selectedGuildId,
        'selected_target_account_id' => $targetAccountId,
        'selected_account_row' => $selectedAccountRow,
        'selected_character_row' => $selectedCharacterRow,
        'selected_guild_row' => $selectedGuildRow,
        'output_dir' => spp_admin_backup_output_dir(),
        'output_dir_writable' => spp_admin_backup_ensure_output_dir(),
        'recent_files' => spp_admin_backup_list_files(15),
        'target_realm_name' => $targetRealmId > 0 ? (string)(spp_get_armory_realm_name($targetRealmId) ?? ('Realm ' . $targetRealmId)) : '',
        'source_realm_name' => $sourceRealmId > 0 ? (string)(spp_get_armory_realm_name($sourceRealmId) ?? ('Realm ' . $sourceRealmId)) : '',
        'has_target_realm' => $targetRealmId > 0 && isset($realmDbMap[$targetRealmId]),
        'target_chars_pdo' => $targetCharsPdo,
    );
}
