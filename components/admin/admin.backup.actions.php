<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_backup_export_copy_chars(PDO $charsPdo, array $copyAccounts, int $startCharacterId, int $startItemId): array
{
    $outputDir = spp_admin_backup_output_dir();
    if (!is_dir($outputDir) || !is_writable($outputDir)) {
        return array('ok' => false, 'message' => 'The backup output directory is not writable: ' . $outputDir);
    }

    $accountIds = array_values(array_filter(array_unique(array_map('intval', $copyAccounts))));
    if (empty($accountIds)) {
        return array('ok' => false, 'message' => 'No copy-account IDs are configured for the backup tool.');
    }

    $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
    $stmtChars = $charsPdo->prepare("SELECT * FROM `characters` WHERE account IN ($placeholders) ORDER BY account ASC, guid ASC");
    $stmtChars->execute($accountIds);
    $characters = $stmtChars->fetchAll(PDO::FETCH_ASSOC);
    if (empty($characters)) {
        return array('ok' => false, 'message' => 'No characters were found on the configured copy accounts.');
    }

    $characterGuidMap = array();
    $nextCharacterGuid = $startCharacterId;
    foreach ($characters as $characterRow) {
        $characterGuidMap[(int)$characterRow['guid']] = $nextCharacterGuid++;
    }

    $stmtItems = $charsPdo->prepare("SELECT * FROM `item_instance` WHERE owner_guid = ?");
    $itemGuidMap = array();
    $nextItemGuid = $startItemId;
    foreach ($characters as $characterRow) {
        $stmtItems->execute([(int)$characterRow['guid']]);
        foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
            $oldItemGuid = (int)$itemRow['guid'];
            if (!isset($itemGuidMap[$oldItemGuid])) {
                $itemGuidMap[$oldItemGuid] = $nextItemGuid++;
            }
        }
    }

    $lines = array(
        '-- Character copy backup export',
        '-- Generated: ' . date('Y-m-d H:i:s'),
        '-- Starting character GUID: ' . $startCharacterId,
        '-- Starting item GUID: ' . $startItemId,
        '',
    );

    $simpleCharacterTables = array(
        'character_action' => 'guid',
        'character_homebind' => 'guid',
        'character_reputation' => 'guid',
        'character_spell' => 'guid',
        'character_tutorial' => 'guid',
    );

    foreach ($characters as $characterRow) {
        $oldCharacterGuid = (int)$characterRow['guid'];
        $newCharacterGuid = (int)$characterGuidMap[$oldCharacterGuid];

        $exportCharacterRow = $characterRow;
        $exportCharacterRow['guid'] = $newCharacterGuid;
        if (isset($exportCharacterRow['data'])) {
            $exportCharacterRow['data'] = spp_admin_backup_update_character_data_field((string)$exportCharacterRow['data'], $newCharacterGuid);
        }
        $lines[] = spp_admin_backup_insert_sql('characters', $exportCharacterRow);

        foreach ($simpleCharacterTables as $table => $guidColumn) {
            $stmtRelated = $charsPdo->prepare("SELECT * FROM `$table` WHERE `$guidColumn` = ?");
            $stmtRelated->execute([$oldCharacterGuid]);
            foreach ($stmtRelated->fetchAll(PDO::FETCH_ASSOC) as $relatedRow) {
                $relatedRow[$guidColumn] = $newCharacterGuid;
                $lines[] = spp_admin_backup_insert_sql($table, $relatedRow);
            }
        }

        $stmtInventory = $charsPdo->prepare("SELECT * FROM `character_inventory` WHERE guid = ?");
        $stmtInventory->execute([$oldCharacterGuid]);
        foreach ($stmtInventory->fetchAll(PDO::FETCH_ASSOC) as $inventoryRow) {
            $inventoryRow['guid'] = $newCharacterGuid;
            if (!empty($inventoryRow['item']) && isset($itemGuidMap[(int)$inventoryRow['item']])) {
                $inventoryRow['item'] = (int)$itemGuidMap[(int)$inventoryRow['item']];
            }
            if (!empty($inventoryRow['bag']) && isset($itemGuidMap[(int)$inventoryRow['bag']])) {
                $inventoryRow['bag'] = (int)$itemGuidMap[(int)$inventoryRow['bag']];
            }
            $lines[] = spp_admin_backup_insert_sql('character_inventory', $inventoryRow);
        }

        $stmtCharacterItems = $charsPdo->prepare("SELECT * FROM `item_instance` WHERE owner_guid = ?");
        $stmtCharacterItems->execute([$oldCharacterGuid]);
        foreach ($stmtCharacterItems->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
            $oldItemGuid = (int)$itemRow['guid'];
            $newItemGuid = (int)($itemGuidMap[$oldItemGuid] ?? $oldItemGuid);
            $itemRow['guid'] = $newItemGuid;
            $itemRow['owner_guid'] = $newCharacterGuid;
            if (isset($itemRow['data'])) {
                $itemRow['data'] = spp_admin_backup_update_item_data_field((string)$itemRow['data'], $newItemGuid, $newCharacterGuid);
            }
            $lines[] = spp_admin_backup_insert_sql('item_instance', $itemRow);
        }

        $lines[] = '';
    }

    $filename = 'copy_chars_' . date('Ymd_His') . '.sql';
    $outputPath = $outputDir . DIRECTORY_SEPARATOR . $filename;
    $writeOk = @file_put_contents($outputPath, implode(PHP_EOL, $lines) . PHP_EOL);
    if ($writeOk === false) {
        return array('ok' => false, 'message' => 'The backup export file could not be written.');
    }

    return array(
        'ok' => true,
        'message' => 'Character copy backup created successfully.',
        'output_path' => $outputPath,
        'character_count' => count($characters),
        'item_count' => count($itemGuidMap),
    );
}

function spp_admin_backup_handle_action(PDO $charsPdo, array $copyAccounts): array
{
    $state = array(
        'notice' => '',
        'error' => '',
    );

    if (($_POST['backup_action'] ?? '') !== 'create_copy_chars_backup') {
        return $state;
    }

    spp_require_csrf('admin_backup');

    $startCharacterId = (int)($_POST['starting_char_id'] ?? 0);
    $startItemId = (int)($_POST['starting_item_id'] ?? 0);
    if ($startCharacterId <= 0 || $startItemId <= 0) {
        $state['error'] = 'Starting character and item GUIDs must both be positive integers.';
        return $state;
    }

    $result = spp_admin_backup_export_copy_chars($charsPdo, $copyAccounts, $startCharacterId, $startItemId);
    if (!empty($result['ok'])) {
        $state['notice'] = $result['message']
            . ' Characters: ' . (int)($result['character_count'] ?? 0)
            . '. Items: ' . (int)($result['item_count'] ?? 0)
            . '. File: ' . (string)($result['output_path'] ?? '');
    } else {
        $state['error'] = (string)($result['message'] ?? 'Backup export failed.');
    }

    return $state;
}
