<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_chartools_build_state(array $dbs, PDO $charcfgPdo): array
{
    $realmIds = array_keys($dbs);
    $selectedRealmId = isset($_POST['realm']) ? (int)$_POST['realm'] : (isset($realmIds[0]) ? (int)$realmIds[0] : 0);
    if ($selectedRealmId <= 0 || !isset($dbs[$selectedRealmId])) {
        $selectedRealmId = isset($realmIds[0]) ? (int)$realmIds[0] : 0;
    }

    $selectedAccountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    $selectedCharacterGuid = isset($_POST['character_guid']) ? (int)$_POST['character_guid'] : 0;
    $accountOptions = array();
    $characterOptions = array();
    $donationPackOptions = array();
    $selectedCharacterName = '';
    $selectedCharacterProfile = null;

    try {
        $stmtAccounts = $charcfgPdo->query("SELECT id, username FROM account ORDER BY username ASC, id ASC");
        $accountOptions = $stmtAccounts ? $stmtAccounts->fetchAll(PDO::FETCH_ASSOC) : array();
    } catch (Throwable $e) {
        $accountOptions = array();
    }

    try {
        $stmtDonationPacks = $charcfgPdo->query("SELECT id, description, donation, currency, realm FROM donations_template ORDER BY id ASC");
        $donationPackOptions = $stmtDonationPacks ? $stmtDonationPacks->fetchAll(PDO::FETCH_ASSOC) : array();
    } catch (Throwable $e) {
        $donationPackOptions = array();
    }

    if ($selectedAccountId <= 0 && !empty($accountOptions[0]['id'])) {
        $selectedAccountId = (int)$accountOptions[0]['id'];
    }

    if ($selectedRealmId > 0 && $selectedAccountId > 0 && isset($dbs[$selectedRealmId])) {
        $renamePdo = get_chartools_pdo($dbs[$selectedRealmId]);
        $stmtCharacters = $renamePdo->prepare("SELECT guid, name, level FROM characters WHERE account = ? ORDER BY name ASC, guid ASC");
        $stmtCharacters->execute([$selectedAccountId]);
        $characterOptions = $stmtCharacters->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($selectedCharacterGuid <= 0 && !empty($characterOptions[0]['guid'])) {
        $selectedCharacterGuid = (int)$characterOptions[0]['guid'];
    }

    foreach ($characterOptions as $characterOption) {
        if ((int)$characterOption['guid'] === $selectedCharacterGuid) {
            $selectedCharacterName = (string)$characterOption['name'];
            break;
        }
    }

    if ($selectedRealmId > 0 && isset($dbs[$selectedRealmId]) && $selectedAccountId > 0 && $selectedCharacterGuid > 0) {
        $selectedCharacterProfile = chartools_fetch_character_profile($selectedCharacterGuid, $selectedAccountId, $dbs[$selectedRealmId]);
    }

    return array(
        'realmIds' => $realmIds,
        'selectedRealmId' => $selectedRealmId,
        'selectedAccountId' => $selectedAccountId,
        'selectedCharacterGuid' => $selectedCharacterGuid,
        'accountOptions' => $accountOptions,
        'characterOptions' => $characterOptions,
        'donationPackOptions' => $donationPackOptions,
        'selectedCharacterName' => $selectedCharacterName,
        'selectedCharacterProfile' => $selectedCharacterProfile,
    );
}
