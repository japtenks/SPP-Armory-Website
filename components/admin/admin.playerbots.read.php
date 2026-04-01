<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.playerbots.helpers.php');
require_once(__DIR__ . '/admin.backup.helpers.php');

function spp_admin_playerbots_build_view(array $realmDbMap): array
{
    $requestedRealmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 0;
    $invalidRealmRequested = $requestedRealmId > 0 && !isset($realmDbMap[$requestedRealmId]);
    $realmId = spp_resolve_realm_id($realmDbMap);
    if ($realmId <= 0 || !isset($realmDbMap[$realmId])) {
        $realmKeys = array_keys($realmDbMap);
        $realmId = !empty($realmKeys[0]) ? (int)$realmKeys[0] : 0;
    }

    $realmInfo = isset($realmDbMap[$realmId]) && is_array($realmDbMap[$realmId]) ? $realmDbMap[$realmId] : array();
    $charsPdo = spp_get_pdo('chars', $realmId);
    $worldPdo = spp_get_pdo('world', $realmId);
    $realmOptions = spp_admin_playerbots_build_realm_options($realmDbMap);
    $guildOptions = spp_admin_backup_fetch_guilds($charsPdo);
    $selectedGuildId = isset($_REQUEST['guildid']) ? (int)$_REQUEST['guildid'] : 0;
    if ($selectedGuildId <= 0 && !empty($guildOptions[0]['guildid'])) {
        $selectedGuildId = (int)$guildOptions[0]['guildid'];
    }

    $selectedGuild = spp_admin_backup_fetch_guild_row($charsPdo, $selectedGuildId);
    $selectedCharacterGuid = isset($_REQUEST['character_guid']) ? (int)$_REQUEST['character_guid'] : 0;
    $guildMembers = array();
    $selectedCharacter = null;
    $selectedPersonality = '';
    $meetingPreview = array(
        'found' => false,
        'valid' => false,
        'location' => '',
        'normalized_start' => '',
        'normalized_end' => '',
        'display' => '',
        'error' => '',
    );
    $shareBlock = '';
    $sharePreview = array('errors' => array(), 'entries' => array());
    $orderPreview = array();
    $guildStrategyState = array(
        'values' => array_fill_keys(spp_admin_playerbots_strategy_keys(), ''),
        'consistent' => true,
        'member_count' => 0,
        'profile_key' => 'default',
        'mixed_count' => 0,
    );
    $characterStrategyState = array(
        'values' => array_fill_keys(spp_admin_playerbots_strategy_keys(), ''),
        'consistent' => true,
        'member_count' => 0,
        'profile_key' => 'custom',
        'mixed_count' => 0,
    );
    $forumToneGroups = spp_admin_playerbots_forum_tone_groups();
    $forumToneState = spp_admin_playerbots_fetch_forum_tone_state($worldPdo);

    if (!empty($selectedGuild)) {
        $stmt = $charsPdo->prepare("
            SELECT
              gm.guildid,
              gm.guid,
              gm.rank,
              gm.pnote,
              gm.offnote,
              c.name,
              c.level,
              c.class,
              c.account
            FROM guild_member gm
            INNER JOIN characters c ON c.guid = gm.guid
            WHERE gm.guildid = ?
            ORDER BY gm.rank ASC, c.level DESC, c.name ASC
        ");
        $stmt->execute(array((int)$selectedGuildId));
        $guildMembers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

        if ($selectedCharacterGuid <= 0 && !empty($guildMembers[0]['guid'])) {
            $selectedCharacterGuid = (int)$guildMembers[0]['guid'];
        }

        foreach ($guildMembers as $member) {
            $guid = (int)($member['guid'] ?? 0);
            if ($guid === $selectedCharacterGuid) {
                $selectedCharacter = $member;
            }

            $orderPreview[] = array(
                'guid' => $guid,
                'name' => (string)($member['name'] ?? ''),
                'offnote' => (string)($member['offnote'] ?? ''),
                'parsed' => spp_admin_playerbots_validate_order_note((string)($member['offnote'] ?? '')),
            );
        }

        $meetingPreview = spp_admin_playerbots_parse_meeting_directive((string)($selectedGuild['motd'] ?? ''));
        $shareBlock = spp_admin_playerbots_extract_share_block((string)($selectedGuild['info'] ?? ''));
        $sharePreview = spp_admin_playerbots_validate_share_block($shareBlock);
        $guildStrategyState = spp_admin_playerbots_fetch_guild_strategy_state($charsPdo, $selectedGuildId);
    }

    if ($selectedCharacter === null && $selectedCharacterGuid > 0) {
        $stmt = $charsPdo->prepare("
            SELECT c.guid, c.name, c.level, c.class, c.account
            FROM characters c
            WHERE c.guid = ?
            LIMIT 1
        ");
        $stmt->execute(array($selectedCharacterGuid));
        $selectedCharacter = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if ($selectedCharacterGuid > 0) {
        $characterStrategyState = spp_admin_playerbots_fetch_character_strategy_state($charsPdo, $selectedCharacterGuid);
        $stmt = $charsPdo->prepare("
            SELECT value
            FROM ai_playerbot_db_store
            WHERE guid = ?
              AND preset = ''
              AND `key` = 'value'
              AND value LIKE 'manual saved string::llmdefaultprompt>%'
            ORDER BY value ASC
            LIMIT 1
        ");
        $stmt->execute(array($selectedCharacterGuid));
        $storedValue = $stmt->fetchColumn();
        $selectedPersonality = spp_admin_playerbots_decode_personality_value($storedValue !== false ? (string)$storedValue : '');
    }

    $realmName = 'Realm ' . $realmId;
    foreach ($realmOptions as $realmOption) {
        if ((int)($realmOption['realm_id'] ?? 0) === $realmId) {
            $realmName = (string)($realmOption['label'] ?? $realmName);
            break;
        }
    }

    return array(
        'realmId' => $realmId,
        'realmName' => $realmName,
        'realmOptions' => $realmOptions,
        'guildOptions' => $guildOptions,
        'selectedGuildId' => $selectedGuildId,
        'selectedGuild' => $selectedGuild,
        'guildMembers' => $guildMembers,
        'selectedCharacterGuid' => $selectedCharacterGuid,
        'selectedCharacter' => $selectedCharacter,
        'selectedPersonality' => $selectedPersonality,
        'meetingPreview' => $meetingPreview,
        'meetingLocationOptions' => spp_admin_playerbots_meeting_location_options($realmInfo, (string)($meetingPreview['location'] ?? '')),
        'shareBlock' => $shareBlock,
        'sharePreview' => $sharePreview,
        'orderPreview' => $orderPreview,
        'randomBotBaselineProfile' => spp_admin_playerbots_random_bot_baseline_profile(),
        'guildStrategyProfiles' => spp_admin_playerbots_guild_strategy_profiles(),
        'botStrategyProfiles' => spp_admin_playerbots_bot_strategy_profiles(),
        'strategyBuilderOptions' => spp_admin_playerbots_strategy_builder_options(),
        'guildStrategyState' => $guildStrategyState,
        'characterStrategyState' => $characterStrategyState,
        'forumToneGroups' => $forumToneGroups,
        'forumToneState' => $forumToneState,
        'invalidRealmRequested' => $invalidRealmRequested,
        'requestedRealmId' => $requestedRealmId,
    );
}
