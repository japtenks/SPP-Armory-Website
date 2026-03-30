<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_backup_build_preview(PDO $charsPdo, array $copyAccounts): array
{
    $preview = array(
        'configured' => false,
        'horde_account' => (int)$copyAccounts['horde'],
        'alliance_account' => (int)$copyAccounts['alliance'],
        'character_count' => 0,
        'characters' => array(),
        'output_dir' => spp_admin_backup_output_dir(),
        'output_dir_writable' => is_dir(spp_admin_backup_output_dir()) && is_writable(spp_admin_backup_output_dir()),
    );

    $accountIds = array_values(array_filter(array_unique(array_map('intval', $copyAccounts))));
    if (count($accountIds) < 1) {
        return $preview;
    }

    $preview['configured'] = true;
    $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
    $stmt = $charsPdo->prepare("
        SELECT guid, account, name, race, class, level
        FROM characters
        WHERE account IN ($placeholders)
        ORDER BY account ASC, level DESC, name ASC
    ");
    $stmt->execute($accountIds);
    $preview['characters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $preview['character_count'] = count($preview['characters']);

    return $preview;
}
