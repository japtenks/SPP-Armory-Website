<?php

if (!function_exists('spp_account_charcreate_build_state')) {
    function spp_account_charcreate_build_state(array $user, $config, array $realmDbMap) {
        $state = array(
            'logged_in' => (int)($user['id'] ?? 0) > 0,
            'enabled' => (int)$config->character_copy_config->enable === 1,
            'char_points' => (int)$config->character_copy_config->points,
            'your_points' => 0,
            'realm_allowed' => true,
            'usable_realms' => array(),
            'source_characters' => array(),
        );

        if (!$state['logged_in']) {
            return $state;
        }

        $accountId = (int)$user['id'];
        $currentRealmId = (int)($user['cur_selected_realmd'] ?? 0);
        $realmdPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
        $stmtPts = $realmdPdo->prepare("SELECT `points` FROM `voting_points` WHERE id=?");
        $stmtPts->execute(array($accountId));
        $state['your_points'] = (int)$stmtPts->fetchColumn();

        $allowedRealmIds = array();
        foreach ($config->character_copy_config->work_on_realms->realm as $realmId) {
            $allowedRealmIds[] = (int)$realmId;
        }
        if (!in_array($currentRealmId, $allowedRealmIds, true)) {
            $state['realm_allowed'] = false;
            foreach ($allowedRealmIds as $realmId) {
                $stmtRealm = $realmdPdo->prepare("SELECT name FROM `realmlist` WHERE id=?");
                $stmtRealm->execute(array($realmId));
                $realmName = $stmtRealm->fetchColumn();
                if ($realmName !== false) {
                    $state['usable_realms'][] = array(
                        'id' => $realmId,
                        'name' => (string)$realmName,
                    );
                }
            }
            return $state;
        }

        $allianceSource = (int)$config->character_copy_config->accounts->alliance;
        $hordeSource = (int)$config->character_copy_config->accounts->horde;
        $charPdo = spp_get_pdo('chars', spp_resolve_realm_id($realmDbMap));
        $stmtChars = $charPdo->prepare("SELECT * FROM `characters` WHERE account=? OR account=? ORDER BY account");
        $stmtChars->execute(array($allianceSource, $hordeSource));
        $rows = $stmtChars->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return $state;
        }

        $MANG = new Mangos;
        foreach ($rows as $row) {
            $charData = explode(' ', $row['data']);
            $level = $charData[$MANG->charDataField['UNIT_FIELD_LEVEL']] ?? $row['level'];
            $raceId = (int)$row['race'];
            $classId = (int)$row['class'];
            $state['source_characters'][] = array(
                'guid' => (int)$row['guid'],
                'class_label' => (string)$MANG->characterInfoByID['character_class'][$classId],
                'faction_label' => ($raceId == 1 || $raceId == 3 || $raceId == 4 || $raceId == 7 || $raceId == 11) ? 'alliance' : 'horde',
                'race_label' => (string)$MANG->characterInfoByID['character_race'][$raceId],
                'level' => (int)$level,
            );
        }

        return $state;
    }
}
