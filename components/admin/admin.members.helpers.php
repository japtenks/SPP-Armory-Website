<?php

if (!function_exists('spp_admin_members_action_url')) {
    function spp_admin_members_action_url(array $params) {
        return spp_action_url('index.php', $params, 'admin_members');
    }
}

if (!function_exists('spp_ensure_website_account_row')) {
    function spp_ensure_website_account_row(PDO $pdo, $accountId) {
        $accountId = (int)$accountId;
        if ($accountId <= 0) {
            return;
        }

        $stmtEnsure = $pdo->prepare("
            INSERT INTO website_accounts (account_id)
            SELECT ?
            WHERE NOT EXISTS (
                SELECT 1 FROM website_accounts WHERE account_id = ?
            )
        ");
        $stmtEnsure->execute([$accountId, $accountId]);
    }
}

if (!function_exists('spp_admin_character_delete_tables')) {
    function spp_admin_character_delete_tables() {
        return array(
            'characters'                  => 'guid',
            'character_inventory'         => 'guid',
            'character_action'            => 'guid',
            'character_aura'              => 'guid',
            'character_gifts'             => 'guid',
            'character_homebind'          => 'guid',
            'character_instance'          => 'guid',
            'character_queststatus_daily' => 'guid',
            'character_kill'              => 'guid',
            'character_pet'               => 'owner',
            'character_queststatus'       => 'guid',
            'character_reputation'        => 'guid',
            'character_social'            => 'guid',
            'character_spell'             => 'guid',
            'character_spell_cooldown'    => 'guid',
            'character_ticket'            => 'guid',
            'character_tutorial'          => 'guid',
            'corpse'                      => 'guid',
            'item_instance'               => 'owner_guid',
            'petition'                    => 'ownerguid',
            'petition_sign'               => 'ownerguid',
        );
    }
}

if (!function_exists('spp_admin_account_is_online')) {
    function spp_admin_account_is_online(PDO $realmPdo, $accountId) {
        $stmt = $realmPdo->prepare("SELECT online FROM account WHERE id=? LIMIT 1");
        $stmt->execute([(int)$accountId]);
        return (int)$stmt->fetchColumn() === 1;
    }
}

if (!function_exists('spp_admin_character_is_online')) {
    function spp_admin_character_is_online(PDO $charsPdo, $characterGuid, $accountId = 0) {
        if ((int)$accountId > 0) {
            $stmt = $charsPdo->prepare("SELECT online FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmt->execute([(int)$characterGuid, (int)$accountId]);
        } else {
            $stmt = $charsPdo->prepare("SELECT online FROM characters WHERE guid=? LIMIT 1");
            $stmt->execute([(int)$characterGuid]);
        }
        return (int)$stmt->fetchColumn() === 1;
    }
}

if (!function_exists('spp_admin_online_characters_for_account')) {
    function spp_admin_online_characters_for_account(PDO $charsPdo, $accountId) {
        $stmt = $charsPdo->prepare("
            SELECT guid, name
            FROM characters
            WHERE account=? AND online=1
            ORDER BY name ASC, guid ASC
        ");
        $stmt->execute([(int)$accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
    }
}

if (!function_exists('spp_admin_force_characters_offline')) {
    function spp_admin_force_characters_offline($realmId, array $characters, &$errorMessage = '') {
        if (empty($characters)) {
            $errorMessage = '';
            return true;
        }
        if (!function_exists('spp_mangos_soap_execute_command')) {
            $errorMessage = 'SOAP helper is unavailable.';
            return false;
        }

        $errors = array();
        $attemptedKick = false;
        foreach ($characters as $character) {
            $characterName = trim((string)($character['name'] ?? ''));
            if ($characterName === '') {
                continue;
            }
            $attemptedKick = true;
            $soapError = '';
            $soapResult = spp_mangos_soap_execute_command((int)$realmId, 'kick ' . $characterName, $soapError);
            if ($soapResult !== false) {
                continue;
            }
            if ($soapError !== '') {
                $errors[] = $characterName . ': ' . $soapError;
            }
        }

        if (!$attemptedKick) {
            $errorMessage = '';
            return true;
        }

        if (!empty($errors)) {
            $errorMessage = implode(' | ', array_unique($errors));
            return false;
        }

        $errorMessage = '';
        return true;
    }
}

if (!function_exists('spp_admin_members_account_fields')) {
    function spp_admin_members_account_fields($isSuperAdmin) {
        $allowed = array('expansion');
        if ($isSuperAdmin) {
            $allowed[] = 'gmlevel';
        }
        return spp_allowed_field_map($allowed);
    }
}

if (!function_exists('spp_admin_members_website_fields')) {
    function spp_admin_members_website_fields($allowThemeChange) {
        $allowed = array('g_id', 'hideprofile', 'signature');
        if ($allowThemeChange) {
            $allowed[] = 'theme';
        }
        return spp_allowed_field_map($allowed);
    }
}

if (!function_exists('spp_admin_members_highest_installed_expansion')) {
    function spp_admin_members_highest_installed_expansion(array $realmDbMap) {
        $highestExpansion = 0;
        foreach (array_keys($realmDbMap) as $realmId) {
            $realmId = (int)$realmId;
            if ($realmId >= 3) {
                $highestExpansion = max($highestExpansion, 2);
            } elseif ($realmId >= 2) {
                $highestExpansion = max($highestExpansion, 1);
            }
        }
        return $highestExpansion;
    }
}

if (!function_exists('spp_admin_members_expansion_label')) {
    function spp_admin_members_expansion_label($expansionId) {
        $expansionMap = array(
            0 => 'Classic',
            1 => 'TBC',
            2 => 'WotLK',
        );
        $expansionId = (int)$expansionId;
        return $expansionMap[$expansionId] ?? 'Classic';
    }
}

if (!function_exists('spp_admin_members_expansion_slug_to_id')) {
    function spp_admin_members_expansion_slug_to_id($slug) {
        $slug = strtolower(trim((string)$slug));
        if ($slug === 'wotlk') {
            return 2;
        }
        if ($slug === 'tbc') {
            return 1;
        }
        return 0;
    }
}
