<?php

if (!function_exists('spp_account_charcreate_handle_request')) {
    function spp_account_charcreate_handle_request(array $user, $config, array $realmDbMap) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return false;
        }

        if ((string)($_GET['action'] ?? '') !== 'createchar') {
            return false;
        }

        $submittedToken = (string)($_POST['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens']['account_charcreate'] ?? '');
        if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            output_message('alert', '<b>Security check failed.<br/>' . $GLOBALS['lang']['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }

        $lang = $GLOBALS['lang'];
        $accountId = (int)$user['id'];
        $rid = (int)$user['cur_selected_realmd'];
        $charPoints = (int)$config->character_copy_config->points;
        $realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
        $charPdo = spp_get_pdo('chars', $rid);
        $MANG = new Mangos;

        $class = $_POST['createchar_class'] ?? null;
        $faction = $_POST['createchar_faction'] ?? null;
        $characterCopyTo = (int)($_POST['character_copy_char'] ?? 0);
        $name = $checknamestring = ucfirst(strtolower(escape_string((string)($_POST['createchar_name'] ?? ''))));

        $newGuidInfo = $MANG->mangos_newguid('character');
        $guid = $newGuidInfo['new_guid'];

        $stmtNc = $charPdo->prepare("SELECT guid FROM `characters` WHERE name=?");
        $stmtNc->execute(array($name));
        $classexists = $stmtNc->fetchColumn() !== false;

        $stmtLi = $realmPdo->prepare("SELECT online FROM account WHERE id=?");
        $stmtLi->execute(array($accountId));
        $loggedin = (string)$stmtLi->fetchColumn();
        if ($loggedin === '0') {
            $stmtLi2 = $charPdo->prepare("SELECT online FROM `characters` WHERE account=?");
            $stmtLi2->execute(array($accountId));
            $loggedin = (string)$stmtLi2->fetchColumn();
        }

        $stmtNc2 = $realmPdo->prepare("SELECT numchars FROM realmcharacters WHERE acctid=? AND realmid=?");
        $stmtNc2->execute(array($accountId, $rid));
        $numchars = (int)$stmtNc2->fetchColumn();

        if (!$class || !$faction) {
            output_message('alert', '<b>' . $lang['charcreate_invalidname'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }
        if ($classexists) {
            output_message('alert', '<b>' . $lang['charcreate_nameinuse'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }
        if ($name === '') {
            output_message('alert', '<b>' . $lang['charcreate_invalidname'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }
        if ($loggedin === '1') {
            output_message('alert', '<b>' . $lang['charcreate_loggedin'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }
        if (check_for_symbols($checknamestring, 1) == true) {
            output_message('alert', '<b>' . $lang['charcreate_nameissymbols'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }
        if ($numchars >= 9) {
            output_message('alert', '<b>' . $lang['charcreate_tomanychars'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
            return true;
        }

        $s = $charPdo->prepare("SELECT * FROM `characters` WHERE guid=?"); $s->execute(array($characterCopyTo));
        $COPY_character = $s->fetch(PDO::FETCH_ASSOC);
        $s = $charPdo->prepare("SELECT * FROM `character_action` WHERE guid=?"); $s->execute(array($characterCopyTo));
        $COPY_character_action = $s->fetchAll(PDO::FETCH_ASSOC);
        $s = $charPdo->prepare("SELECT * FROM `character_homebind` WHERE guid=?"); $s->execute(array($characterCopyTo));
        $COPY_character_homebind = $s->fetchAll(PDO::FETCH_ASSOC);
        $s = $charPdo->prepare("SELECT * FROM `character_reputation` WHERE guid=?"); $s->execute(array($characterCopyTo));
        $COPY_character_reputation = $s->fetchAll(PDO::FETCH_ASSOC);
        $s = $charPdo->prepare("SELECT * FROM `character_skills` WHERE guid=?"); $s->execute(array($characterCopyTo));
        $COPY_character_skills = $s->fetchAll(PDO::FETCH_ASSOC);
        $s = $charPdo->prepare("SELECT * FROM `character_spell` WHERE guid=?"); $s->execute(array($characterCopyTo));
        $COPY_character_spell = $s->fetchAll(PDO::FETCH_ASSOC);
        $s = $charPdo->prepare("SELECT * FROM `item_instance` WHERE owner_guid=?"); $s->execute(array($characterCopyTo));
        $MAIN_COPY_item_instance = $s->fetchAll(PDO::FETCH_ASSOC);

        $ROUNDS = 0;
        $ARRAY_INCREMENT = 0;
        $ARRAY_BAG_INCREMENT = 0;
        $COPY_item_instance = array();
        $COPY_BAG_item_instance = array();
        $COPY_character_inventory = array();

        foreach ($MAIN_COPY_item_instance as $MAIN_COPY_SUB_item_instance) {
            $stmtCi = $charPdo->prepare("SELECT * FROM `character_inventory` WHERE item=?");
            $stmtCi->execute(array((int)$MAIN_COPY_SUB_item_instance['guid']));
            $character_inventory = $stmtCi->fetch(PDO::FETCH_ASSOC);
            $bag = $character_inventory['bag'];
            $slot = $character_inventory['slot'];
            $item_template = $character_inventory['item_template'];

            $ROUNDS++;
            if ($ROUNDS == 1) {
                $new_guid = $MANG->mangos_newguid('item_instance');
                $item = $new_guid['new_guid'];
            } else {
                $item = $item + 1;
            }

            $data = explode(" ", $MAIN_COPY_SUB_item_instance['data']);
            $data['0'] = $item;
            $data['6'] = $guid;
            $data['8'] = $guid;
            $update_implode_data_field = implode(" ", $data);

            if (count($data) > 90) {
                $COPY_BAG_item_instance[$ARRAY_BAG_INCREMENT] = array(
                    'guid' => $item,
                    'owner_guid' => $guid,
                    'data' => $update_implode_data_field,
                    'old_guid' => $MAIN_COPY_SUB_item_instance['guid'],
                );
                $ARRAY_BAG_INCREMENT++;
            } else {
                $COPY_item_instance[$ARRAY_INCREMENT] = array(
                    'guid' => $item,
                    'owner_guid' => $guid,
                    'data' => $update_implode_data_field,
                    'old_guid' => $MAIN_COPY_SUB_item_instance['guid'],
                );
            }

            $COPY_character_inventory[$ARRAY_INCREMENT] = array(
                'guid' => $guid,
                'bag' => $bag,
                'slot' => $slot,
                'item' => $item,
                'item_template' => $item_template,
            );

            $COPY_character['data'] = str_replace($MAIN_COPY_SUB_item_instance['guid'], $item, $COPY_character['data']);
            $ARRAY_INCREMENT++;
        }

        foreach ($COPY_BAG_item_instance as $COPY_BAG_SUB_item_instance) {
            foreach ($COPY_item_instance as $COPY_WORK_item_instance) {
                if (strstr($COPY_BAG_SUB_item_instance['data'], $COPY_WORK_item_instance['old_guid']) == true) {
                    $COPY_BAG_SUB_item_instance['data'] = str_replace(" " . $COPY_WORK_item_instance['old_guid'] . " ", " " . $COPY_WORK_item_instance['guid'] . " ", $COPY_BAG_SUB_item_instance['data']);
                    foreach ($COPY_character_inventory as $i => $check) {
                        if ($check['bag'] != 0 && $check['bag'] == $COPY_BAG_SUB_item_instance['old_guid']) {
                            $COPY_character_inventory[$i]['bag'] = $COPY_BAG_SUB_item_instance['guid'];
                        }
                    }
                }
            }

            $charPdo->exec("INSERT INTO `item_instance` (`guid`,`owner_guid`,`data`) VALUES ('" . $COPY_BAG_SUB_item_instance['guid'] . "', '" . $COPY_BAG_SUB_item_instance['owner_guid'] . "', '" . $COPY_BAG_SUB_item_instance['data'] . "')");
        }

        foreach ($COPY_item_instance as $COPY_SUB_item_instance) {
            $charPdo->exec("INSERT INTO `item_instance` (`guid`,`owner_guid`,`data`) VALUES ('" . $COPY_SUB_item_instance['guid'] . "', '" . $COPY_SUB_item_instance['owner_guid'] . "', '" . $COPY_SUB_item_instance['data'] . "')");
        }

        foreach ($COPY_character_inventory as $COPY_SUB_character_inventory) {
            $charPdo->exec("INSERT INTO `character_inventory` (`guid`,`bag`,`slot`,`item`,`item_template`) VALUES ('" . $COPY_SUB_character_inventory['guid'] . "', '" . $COPY_SUB_character_inventory['bag'] . "', '" . $COPY_SUB_character_inventory['slot'] . "', '" . $COPY_SUB_character_inventory['item'] . "', '" . $COPY_SUB_character_inventory['item_template'] . "')");
        }

        $COPY_character['data'] = explode(' ', $COPY_character['data']);
        $COPY_character['data']['0'] = $guid;
        $COPY_character['data'] = implode(' ', $COPY_character['data']);
        $genderValue = $COPY_character['gender'];
        $knownCurrencies = $COPY_character['knownCurrencies'] ?? ($COPY_character['knownCurrenies'] ?? '');

        $charPdo->exec("INSERT INTO `characters` (`guid`,`account`,`name`,`race`,`class`,`gender`,`level`,`xp`,`money`,`playerBytes`,`playerBytes2`,`playerFlags`,`position_x`,`position_y`,`position_z`,`map`,`dungeon_difficulty`,`orientation`,`taximask`,`online`,`cinematic`,`totaltime`,`leveltime`,`logout_time`,`is_logout_resting`,`rest_bonus`,`resettalents_cost`,`resettalents_time`,`trans_x`,`trans_y`,`trans_z`,`trans_o`,`transguid`,`extra_flags`,`stable_slots`,`at_login`,`zone`,`death_expire_time`,`taxi_path`,`arenaPoints`,`totalHonorPoints`,`todayHonorPoints`,`yesterdayHonorPoints`,`totalKills`,`todayKills`,`yesterdayKills`,`chosenTitle`,`knownCurrencies`,`watchedFaction`,`drunk`,`health`,`power1`,`power2`,`power3`,`power4`,`power5`,`power6`,`power7`,`specCount`,`activeSpec`,`exploredZones`,`equipmentCache`,`ammoId`,`knownTitles`,`actionBars`) VALUES ('" . $guid . "', '" . $accountId . "', '" . $name . "', '" . $COPY_character['race'] . "', '" . $COPY_character['class'] . "', '" . $genderValue . "', '" . $COPY_character['level'] . "', '" . $COPY_character['xp'] . "', '" . $COPY_character['money'] . "', '" . $COPY_character['playerBytes'] . "', '" . $COPY_character['playerBytes2'] . "', '" . $COPY_character['playerFlags'] . "', '" . $COPY_character['position_x'] . "', '" . $COPY_character['position_y'] . "', '" . $COPY_character['position_z'] . "', '" . $COPY_character['map'] . "', '" . $COPY_character['dungeon_difficulty'] . "', '" . $COPY_character['orientation'] . "', '" . $COPY_character['taximask'] . "', '" . $COPY_character['online'] . "', '" . $COPY_character['cinematic'] . "', '" . $COPY_character['totaltime'] . "', '" . $COPY_character['leveltime'] . "', '" . $COPY_character['logout_time'] . "', '" . $COPY_character['is_logout_resting'] . "', '" . $COPY_character['rest_bonus'] . "', '" . $COPY_character['resettalents_cost'] . "', '" . $COPY_character['resettalents_time'] . "', '" . $COPY_character['trans_x'] . "', '" . $COPY_character['trans_y'] . "', '" . $COPY_character['trans_z'] . "', '" . $COPY_character['trans_o'] . "', '" . $COPY_character['transguid'] . "', '" . $COPY_character['extra_flags'] . "', '" . $COPY_character['stable_slots'] . "', '" . $COPY_character['at_login'] . "', '" . $COPY_character['zone'] . "', '" . $COPY_character['death_expire_time'] . "', '" . $COPY_character['taxi_path'] . "', '" . $COPY_character['arenaPoints'] . "', '" . $COPY_character['totalHonorPoints'] . "', '" . $COPY_character['todayHonorPoints'] . "', '" . $COPY_character['yesterdayHonorPoints'] . "', '" . $COPY_character['totalKills'] . "', '" . $COPY_character['todayKills'] . "', '" . $COPY_character['yesterdayKills'] . "', '" . $COPY_character['chosenTitle'] . "', '" . $knownCurrencies . "', '" . $COPY_character['watchedFaction'] . "', '" . $COPY_character['drunk'] . "', '" . $COPY_character['health'] . "', '" . $COPY_character['power1'] . "', '" . $COPY_character['power2'] . "', '" . $COPY_character['power3'] . "', '" . $COPY_character['power4'] . "', '" . $COPY_character['power5'] . "', '" . $COPY_character['power6'] . "', '" . $COPY_character['power7'] . "', '" . $COPY_character['specCount'] . "', '" . $COPY_character['activeSpec'] . "','" . $COPY_character['exploredZones'] . "', '" . $COPY_character['equipmentCache'] . "', '" . $COPY_character['ammoId'] . "', '" . $COPY_character['knownTitles'] . "', '" . $COPY_character['actionBars'] . "')");

        foreach ($COPY_character_skills as $COPY_SUB_character_skills) {
            $charPdo->exec("INSERT INTO `character_skills` (`guid`,`skill`,`value`,`max`) VALUES ('" . $guid . "', '" . $COPY_SUB_character_skills['skill'] . "', '" . $COPY_SUB_character_skills['value'] . "', '" . $COPY_SUB_character_skills['max'] . "')");
        }
        foreach ($COPY_character_action as $COPY_SUB_character_action) {
            $charPdo->exec("INSERT INTO `character_action` (`guid`,`spec`,`button`,`action`,`type`) VALUES ('" . $guid . "' , '" . $COPY_SUB_character_action['spec'] . "', '" . $COPY_SUB_character_action['button'] . "', '" . $COPY_SUB_character_action['action'] . "', '" . $COPY_SUB_character_action['type'] . "')");
        }
        foreach ($COPY_character_homebind as $COPY_SUB_character_homebind) {
            $charPdo->exec("INSERT INTO `character_homebind` (`guid`,`map`,`zone`,`position_x`,`position_y`,`position_z`) VALUES ('" . $guid . "' , '" . $COPY_SUB_character_homebind['map'] . "', '" . $COPY_SUB_character_homebind['zone'] . "', '" . $COPY_SUB_character_homebind['position_x'] . "', '" . $COPY_SUB_character_homebind['position_y'] . "', '" . $COPY_SUB_character_homebind['position_z'] . "')");
        }
        foreach ($COPY_character_reputation as $COPY_SUB_character_reputation) {
            $charPdo->exec("INSERT INTO `character_reputation` (`guid`,`faction`,`standing`,`flags`) VALUES ('" . $guid . "', '" . $COPY_SUB_character_reputation['faction'] . "', '" . $COPY_SUB_character_reputation['standing'] . "', '" . $COPY_SUB_character_reputation['flags'] . "')");
        }
        foreach ($COPY_character_spell as $COPY_SUB_character_spell) {
            $charPdo->exec("INSERT INTO `character_spell` (`guid`,`spell`,`active`,`disabled`) VALUES ('" . $guid . "', '" . $COPY_SUB_character_spell['spell'] . "', '" . $COPY_SUB_character_spell['active'] . "', '" . $COPY_SUB_character_spell['disabled'] . "')");
        }

        $stmtUpd = $realmPdo->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
        $stmtUpd->execute(array($charPoints, $charPoints, $accountId));

        output_message('notice', '<b><h1>' . $lang['congratulations'] . '</h1>, ' . $lang['charcreate_charcreated'] . '<br/>' . $lang['redirecting_wait'] . '</b><meta http-equiv=refresh content="5;url=index.php?n=account&sub=charcreate">');
        return true;
    }
}
