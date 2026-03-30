<?php

if (!function_exists('spp_account_chartools_fetch_characters')) {
    function spp_account_chartools_fetch_characters(PDO $charPdo, $accountId) {
        $stmt = $charPdo->prepare("SELECT * FROM `characters` WHERE account=? ORDER BY name ASC");
        $stmt->execute(array((int)$accountId));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('spp_account_chartools_build_state')) {
    function spp_account_chartools_build_state(array $user, $config) {
        $accountId = (int)($user['id'] ?? 0);
        $realmId = (int)($user['cur_selected_realmd'] ?? 1);
        $realmPdo = spp_get_pdo('realmd', $realmId);
        $charPdo = spp_get_pdo('chars', $realmId);

        $stmtPts = $realmPdo->prepare("SELECT `points` FROM `voting_points` WHERE id=?");
        $stmtPts->execute(array($accountId));

        return array(
            'account_id' => $accountId,
            'realm_pdo' => $realmPdo,
            'char_pdo' => $charPdo,
            'show_rename' => (int)$config->character_tools->rename === 1,
            'show_custom' => (int)$config->character_tools->re_customization === 1,
            'show_changer' => (int)$config->character_tools->race_changer === 1,
            'allow_faction_change' => (int)$config->character_tools->faction_change === 1,
            'char_rename_points' => (int)$config->character_tools->rename_points,
            'char_custom_points' => (int)$config->character_tools->customization_points,
            'char_faction_points' => (int)$config->character_tools->faction_points,
            'your_points' => (int)$stmtPts->fetchColumn(),
            'characters' => spp_account_chartools_fetch_characters($charPdo, $accountId),
        );
    }
}
