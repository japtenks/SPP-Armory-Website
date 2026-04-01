<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.playerbots.helpers.php');
require_once(__DIR__ . '/admin.backup.helpers.php');

function spp_admin_playerbots_handle_action(PDO $charsPdo, int $realmId): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = trim((string)($_POST['playerbots_action'] ?? ''));
    if ($action === '') {
        return;
    }

    spp_require_csrf('admin_playerbots');

    $guildId = (int)($_POST['guildid'] ?? 0);
    $characterGuid = (int)($_POST['character_guid'] ?? 0);

    if ($action === 'save_meeting') {
        $guildRow = $guildId > 0 ? spp_admin_backup_fetch_guild_row($charsPdo, $guildId) : null;
        if (empty($guildRow)) {
            output_message('alert', 'That guild could not be found.');
            return;
        }

        $location = trim((string)($_POST['meeting_location'] ?? ''));
        $start = spp_admin_playerbots_parse_time_token(trim((string)($_POST['meeting_start'] ?? '')));
        $end = spp_admin_playerbots_parse_time_token(trim((string)($_POST['meeting_end'] ?? '')));

        if ($location === '') {
            output_message('alert', 'Meeting location is required.');
            return;
        }
        if ($start === null || $end === null) {
            output_message('alert', 'Meeting times must use HH:MM 24-hour format.');
            return;
        }

        $updatedMotd = spp_admin_playerbots_upsert_meeting_directive((string)($guildRow['motd'] ?? ''), $location, $start['normalized'], $end['normalized']);
        $stmt = $charsPdo->prepare("UPDATE guild SET motd = ? WHERE guildid = ? LIMIT 1");
        $stmt->execute(array(substr($updatedMotd, 0, 255), $guildId));
        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array('meeting_saved' => 1)), 1);
        exit;
    }

    if ($action === 'save_share') {
        $guildRow = $guildId > 0 ? spp_admin_backup_fetch_guild_row($charsPdo, $guildId) : null;
        if (empty($guildRow)) {
            output_message('alert', 'That guild could not be found.');
            return;
        }

        $shareBlock = trim(str_replace("\r\n", "\n", (string)($_POST['share_block'] ?? '')));
        $validation = spp_admin_playerbots_validate_share_block($shareBlock);
        if (!empty($validation['errors'])) {
            output_message('alert', implode('<br>', array_map('htmlspecialchars', $validation['errors'])));
            return;
        }

        $updatedInfo = spp_admin_playerbots_replace_share_block((string)($guildRow['info'] ?? ''), $shareBlock);
        $stmt = $charsPdo->prepare("UPDATE guild SET info = ? WHERE guildid = ? LIMIT 1");
        $stmt->execute(array(substr($updatedInfo, 0, 5000), $guildId));
        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array('share_saved' => 1)), 1);
        exit;
    }

    if ($action === 'save_notes') {
        if ($guildId <= 0) {
            output_message('alert', 'Choose a guild before saving officer notes.');
            return;
        }

        $officerNotes = isset($_POST['offnote']) && is_array($_POST['offnote']) ? $_POST['offnote'] : array();
        $noteGuids = array_values(array_unique(array_filter(array_map('intval', array_keys($officerNotes)))));
        if (empty($noteGuids)) {
            output_message('alert', 'No officer notes were submitted.');
            return;
        }

        $errors = array();
        foreach ($noteGuids as $noteGuid) {
            $validation = spp_admin_playerbots_validate_order_note((string)($officerNotes[$noteGuid] ?? ''));
            if (empty($validation['valid'])) {
                $errors[] = 'Member ' . $noteGuid . ': ' . (string)($validation['error'] ?? 'Invalid note.');
            }
        }
        if (!empty($errors)) {
            output_message('alert', implode('<br>', array_map('htmlspecialchars', $errors)));
            return;
        }

        $placeholders = implode(',', array_fill(0, count($noteGuids), '?'));
        $stmt = $charsPdo->prepare("SELECT guid FROM guild_member WHERE guildid = ? AND guid IN ($placeholders)");
        $stmt->execute(array_merge(array($guildId), $noteGuids));
        $validGuids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: array());

        foreach ($validGuids as $validGuid) {
            $noteValue = substr(trim((string)($officerNotes[$validGuid] ?? '')), 0, 31);
            $stmt = $charsPdo->prepare("UPDATE guild_member SET offnote = ? WHERE guildid = ? AND guid = ?");
            $stmt->execute(array($noteValue, $guildId, $validGuid));
        }

        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array('notes_saved' => 1)), 1);
        exit;
    }

    if ($action === 'save_personality') {
        if ($characterGuid <= 0) {
            output_message('alert', 'Choose a character before saving personality text.');
            return;
        }

        $personality = trim((string)($_POST['personality_text'] ?? ''));
        $deleteStmt = $charsPdo->prepare("
            DELETE FROM ai_playerbot_db_store
            WHERE guid = ?
              AND preset = ''
              AND `key` = 'value'
              AND value LIKE 'manual saved string::llmdefaultprompt>%'
        ");
        $deleteStmt->execute(array($characterGuid));

        if ($personality !== '') {
            $insertStmt = $charsPdo->prepare("
                INSERT INTO ai_playerbot_db_store (`guid`, `preset`, `key`, `value`)
                VALUES (?, '', 'value', ?)
            ");
            $insertStmt->execute(array($characterGuid, 'manual saved string::llmdefaultprompt>' . $personality));
        }

        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array('personality_saved' => 1)), 1);
        exit;
    }

    if ($action === 'save_forum_tone') {
        $worldPdo = spp_get_pdo('world', $realmId);
        $keyMap = spp_admin_playerbots_forum_tone_key_map();
        $toneValues = array();

        foreach ($keyMap as $toneKey => $meta) {
            $fieldName = 'forum_tone_' . md5($toneKey);
            $toneValues[$toneKey] = spp_admin_playerbots_normalize_forum_tone_lines((string)($_POST[$fieldName] ?? ''));
        }

        try {
            $worldPdo->beginTransaction();

            $keys = array_keys($keyMap);
            if (!empty($keys)) {
                $placeholders = implode(',', array_fill(0, count($keys), '?'));
                $deleteStmt = $worldPdo->prepare("
                    DELETE FROM `ai_playerbot_texts`
                    WHERE `name` IN ($placeholders)
                ");
                $deleteStmt->execute($keys);
            }

            $insertStmt = $worldPdo->prepare("
                INSERT INTO `ai_playerbot_texts`
                    (`name`, `text`, `say_type`, `reply_type`, `text_loc1`, `text_loc2`, `text_loc3`, `text_loc4`, `text_loc5`, `text_loc6`, `text_loc7`, `text_loc8`)
                VALUES
                    (?, ?, 0, 0, '', '', '', '', '', '', '', '')
            ");

            foreach ($toneValues as $toneKey => $lines) {
                foreach ($lines as $line) {
                    $insertStmt->execute(array($toneKey, $line));
                }
            }

            $worldPdo->commit();
        } catch (Throwable $e) {
            if ($worldPdo->inTransaction()) {
                $worldPdo->rollBack();
            }
            error_log('[admin.playerbots] Failed saving forum tone rows: ' . $e->getMessage());
            output_message('alert', 'Saving forum chatter tone failed.');
            return;
        }

        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array('forum_tone_saved' => 1)), 1);
        exit;
    }

    if ($action === 'save_bot_strategy') {
        if ($characterGuid <= 0) {
            output_message('alert', 'Choose a character before saving bot strategies.');
            return;
        }

        $effectiveStrategyValues = array();
        foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
            $effectiveStrategyValues[$strategyKey] = spp_admin_playerbots_normalize_strategy_value((string)($_POST['strategy_' . $strategyKey] ?? ''));
        }

        $baseRows = spp_admin_playerbots_fetch_strategy_rows_for_guids($charsPdo, array($characterGuid), 'default');
        $baseValues = $baseRows[$characterGuid] ?? array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
        $strategyValues = spp_admin_playerbots_build_strategy_override_set($baseValues, $effectiveStrategyValues);

        try {
            $charsPdo->beginTransaction();

            $strategyKeys = spp_admin_playerbots_strategy_keys();
            $keyPlaceholders = implode(',', array_fill(0, count($strategyKeys), '?'));
            $deleteStmt = $charsPdo->prepare("
                DELETE FROM ai_playerbot_db_store
                WHERE guid = ?
                  AND preset = ''
                  AND `key` IN ($keyPlaceholders)
            ");
            $deleteStmt->execute(array_merge(array($characterGuid), $strategyKeys));

            $insertStmt = $charsPdo->prepare("
                INSERT INTO ai_playerbot_db_store (`guid`, `preset`, `key`, `value`)
                VALUES (?, '', ?, ?)
            ");
            foreach ($strategyValues as $strategyKey => $strategyValue) {
                if ($strategyValue === '') {
                    continue;
                }
                $insertStmt->execute(array($characterGuid, $strategyKey, $strategyValue));
            }

            $charsPdo->commit();
        } catch (Throwable $e) {
            if ($charsPdo->inTransaction()) {
                $charsPdo->rollBack();
            }
            error_log('[admin.playerbots] Failed saving bot strategies: ' . $e->getMessage());
            output_message('alert', 'Saving bot strategy overrides failed.');
            return;
        }

        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array('bot_strategy_saved' => 1)), 1);
        exit;
    }

    if ($action === 'save_strategy') {
        if ($guildId <= 0) {
            output_message('alert', 'Choose a guild before saving strategy overrides.');
            return;
        }

        $stmt = $charsPdo->prepare("
            SELECT gm.guid, c.name
            FROM guild_member gm
            INNER JOIN characters c ON c.guid = gm.guid
            WHERE gm.guildid = ?
            ORDER BY c.name ASC
        ");
        $stmt->execute(array($guildId));
        $memberRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
        $memberGuids = array_values(array_unique(array_filter(array_map(static function (array $row): int {
            return (int)($row['guid'] ?? 0);
        }, $memberRows))));
        if (empty($memberGuids)) {
            output_message('alert', 'No guild members were found for the selected guild.');
            return;
        }

        $strategyValues = array();
        foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
            $strategyValues[$strategyKey] = spp_admin_playerbots_normalize_strategy_value((string)($_POST['strategy_' . $strategyKey] ?? ''));
        }
        $seededMembers = 0;
        $seedFailedMembers = 0;

        try {
            $charsPdo->beginTransaction();

            $memberPlaceholders = implode(',', array_fill(0, count($memberGuids), '?'));
            $strategyKeys = spp_admin_playerbots_strategy_keys();
            $keyPlaceholders = implode(',', array_fill(0, count($strategyKeys), '?'));
            $deleteStmt = $charsPdo->prepare("
                DELETE FROM ai_playerbot_db_store
                WHERE guid IN ($memberPlaceholders)
                  AND preset = 'default'
                  AND `key` IN ($keyPlaceholders)
            ");
            $deleteStmt->execute(array_merge($memberGuids, $strategyKeys));

            $insertStmt = $charsPdo->prepare("
                INSERT INTO ai_playerbot_db_store (`guid`, `preset`, `key`, `value`)
                VALUES (?, 'default', ?, ?)
            ");
            foreach ($memberGuids as $memberGuid) {
                foreach ($strategyValues as $strategyKey => $strategyValue) {
                    if ($strategyValue === '') {
                        continue;
                    }
                    $insertStmt->execute(array($memberGuid, $strategyKey, $strategyValue));
                }
            }

            $charsPdo->commit();
        } catch (Throwable $e) {
            if ($charsPdo->inTransaction()) {
                $charsPdo->rollBack();
            }
            error_log('[admin.playerbots] Failed saving guild strategies: ' . $e->getMessage());
            output_message('alert', 'Saving guild strategy overrides failed.');
            return;
        }

        redirect(spp_admin_playerbots_redirect_url($realmId, $guildId, $characterGuid, array(
            'strategy_saved' => 1,
            'seeded_members' => $seededMembers,
            'seed_failed_members' => $seedFailedMembers,
        )), 1);
        exit;
    }
}
