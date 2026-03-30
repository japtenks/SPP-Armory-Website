<?php

if (!function_exists('spp_account_chartools_handle_actions')) {
    function spp_account_chartools_handle_actions(array $context) {
        $state = array(
            'unstuck_message' => '',
            'rename_message' => '',
            'customize_message' => '',
            'race_message' => '',
            'race_step' => 1,
            'race_context' => array(),
        );

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $state;
        }

        $action = spp_account_chartools_detect_action();
        if ($action === '') {
            return $state;
        }

        if (!spp_account_chartools_has_valid_csrf()) {
            $message = spp_account_chartools_message('error', 'Security check failed. Please refresh the page and try again.');
            if ($action === 'unstuck') {
                $state['unstuck_message'] = $message;
            } elseif ($action === 'rename') {
                $state['rename_message'] = $message;
            } elseif ($action === 'customize') {
                $state['customize_message'] = $message;
            } else {
                $state['race_message'] = $message;
            }
            return $state;
        }

        if ($action === 'unstuck') {
            $state['unstuck_message'] = spp_account_chartools_handle_unstuck($context);
            return $state;
        }

        if ($action === 'rename') {
            $state['rename_message'] = spp_account_chartools_handle_rename($context);
            return $state;
        }

        if ($action === 'customize') {
            $state['customize_message'] = spp_account_chartools_handle_customize($context);
            return $state;
        }

        if ($action === 'race_step2') {
            $state['race_step'] = 2;
            $state['race_context'] = spp_account_chartools_prepare_race_context($context, $state['race_message']);
            if (empty($state['race_context'])) {
                $state['race_step'] = 1;
            }
            return $state;
        }

        if ($action === 'race_step3') {
            $state['race_message'] = spp_account_chartools_handle_race_change($context);
            return $state;
        }

        return $state;
    }
}

if (!function_exists('spp_account_chartools_detect_action')) {
    function spp_account_chartools_detect_action() {
        if (isset($_POST['unstuck'])) {
            return 'unstuck';
        }
        if (isset($_POST['rename'])) {
            return 'rename';
        }
        if (isset($_POST['customize'])) {
            return 'customize';
        }
        if (isset($_POST['step2'])) {
            return 'race_step2';
        }
        if (isset($_POST['step3'])) {
            return 'race_step3';
        }
        if (isset($_POST['step1'])) {
            return 'race_step1';
        }
        return '';
    }
}

if (!function_exists('spp_account_chartools_has_valid_csrf')) {
    function spp_account_chartools_has_valid_csrf() {
        $submittedToken = (string)($_POST['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens']['account_chartools'] ?? '');

        return $submittedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('spp_account_chartools_message')) {
    function spp_account_chartools_message($type, $text) {
        $color = $type === 'success' ? 'blue' : 'red';
        return "<p align='center'><font color='" . $color . "'>" . htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8') . "</font></p>";
    }
}

if (!function_exists('spp_account_chartools_handle_unstuck')) {
    function spp_account_chartools_handle_unstuck(array $context) {
        $charPdo = $context['char_pdo'];
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            return spp_account_chartools_message('error', 'Please select a character.');
        }

        $stmtRace = $charPdo->prepare("SELECT race FROM characters WHERE name=?");
        $stmtRace->execute(array($name));
        $race = $stmtRace->fetchColumn();
        $isAllianceRace = isAlliance((int)$race);
        $status = check_if_online($name, $charPdo);
        if ($status === -1) {
            return spp_account_chartools_message('error', 'The character does not exist.');
        }
        if ($status === 1) {
            return spp_account_chartools_message('error', 'This character is online. Please try again later.');
        }

        if ($isAllianceRace) {
            $stmt = $charPdo->prepare("UPDATE characters SET position_x = -8913.23, position_y = 554.633, position_z = 93.7944, map = 0, zone = 1519 WHERE name=?");
            $stmt->execute(array($name));
            return spp_account_chartools_message('success', 'Success! Character ' . $name . ' has been teleported to Stormwind.');
        }

        $stmt = $charPdo->prepare("UPDATE characters SET position_x = 1440.45, position_y = -4422.78, position_z = 25.4634, map = 1, zone = 1637 WHERE name=?");
        $stmt->execute(array($name));
        return spp_account_chartools_message('success', 'Success! Character ' . $name . ' has been teleported to Orgrimmar.');
    }
}

if (!function_exists('spp_account_chartools_handle_rename')) {
    function spp_account_chartools_handle_rename(array $context) {
        $name = trim((string)($_POST['name'] ?? ''));
        $newname = ucfirst(strtolower(trim((string)($_POST['newname'] ?? ''))));
        if ($name === '' || $newname === '') {
            return spp_account_chartools_message('error', 'Please enter a new name.');
        }
        if ((int)$context['char_rename_points'] > (int)$context['your_points']) {
            return spp_account_chartools_message('error', 'You do not have enough points to rename a character.');
        }

        $status = check_if_online($name, $context['char_pdo']);
        $newnameExists = check_if_name_exist($newname, $context['char_pdo']);
        if ($status === -1) {
            return spp_account_chartools_message('error', 'The character does not exist.');
        }
        if ($newnameExists === 1) {
            return spp_account_chartools_message('error', 'The character already exists, please choose a different name.');
        }
        if ($status === 1) {
            return spp_account_chartools_message('error', 'This character is online. Please try again later.');
        }

        change_name($name, $newname, $context['account_id'], $context['char_pdo']);
        $stmt = $context['realm_pdo']->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
        $stmt->execute(array((int)$context['char_rename_points'], (int)$context['char_rename_points'], (int)$context['account_id']));
        return spp_account_chartools_message('success', 'Success! Character ' . $name . ' renamed to ' . $newname . '.');
    }
}

if (!function_exists('spp_account_chartools_handle_customize')) {
    function spp_account_chartools_handle_customize(array $context) {
        $name = trim((string)($_POST['char_c_name'] ?? ''));
        if ($name === '') {
            return spp_account_chartools_message('error', 'Please select a character.');
        }
        if ((int)$context['char_custom_points'] > (int)$context['your_points']) {
            return spp_account_chartools_message('error', 'You do not have enough points to re-customize a character.');
        }

        $status = check_if_online($name, $context['char_pdo']);
        if ($status === -1) {
            return spp_account_chartools_message('error', 'The character does not exist.');
        }
        if ($status === 1) {
            return spp_account_chartools_message('error', 'This character is online. Please try again later.');
        }

        customize($name, $context['char_pdo'], $context['account_id']);
        $stmt = $context['realm_pdo']->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
        $stmt->execute(array((int)$context['char_custom_points'], (int)$context['char_custom_points'], (int)$context['account_id']));
        return spp_account_chartools_message('success', 'Success! You will be able to customize your character at next login.');
    }
}

if (!function_exists('spp_account_chartools_prepare_race_context')) {
    function spp_account_chartools_prepare_race_context(array $context, &$message) {
        $name = trim((string)($_POST['char_f_name'] ?? ''));
        if ($name === '') {
            $message = spp_account_chartools_message('error', 'No character was selected. Please try again.');
            return array();
        }

        $stmt = $context['char_pdo']->prepare("SELECT `guid`, `race`, `class`, `gender`, `level`, `zone` FROM characters WHERE name=?");
        $stmt->execute(array($name));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $message = spp_account_chartools_message('error', 'The selected character could not be found.');
            return array();
        }

        return array(
            'guid' => (int)$row['guid'],
            'name' => $name,
            'oldrace' => (int)$row['race'],
            'oldclass' => (int)$row['class'],
            'oldgender' => (int)$row['gender'],
            'level' => (int)$row['level'],
            'zone_name' => (string)$context['MANG']->get_zone_name($row['zone']),
        );
    }
}

if (!function_exists('spp_account_chartools_handle_race_change')) {
    function spp_account_chartools_handle_race_change(array $context) {
        $newrace = (int)($_POST['newrace'] ?? 0);
        $oldrace = (int)($_POST['oldrace'] ?? 0);
        $class = (int)($_POST['oldclass'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $guid = (int)($_POST['guid'] ?? 0);

        if ($newrace <= 0 || $oldrace <= 0 || $class <= 0 || $guid <= 0 || $name === '') {
            return spp_account_chartools_message('error', 'Race change request is incomplete. Please start again.');
        }
        if ((int)$context['char_faction_points'] > (int)$context['your_points']) {
            return spp_account_chartools_message('error', 'You do not have enough points to continue.');
        }

        $charPdo = $context['char_pdo'];
        $realmPdo = $context['realm_pdo'];
        $accountId = (int)$context['account_id'];
        $onlineStatus = check_if_online($name, $charPdo);
        $guildCheck = check_guild($guid, $charPdo);

        if ($newrace < 1 || $newrace > 11 || $newrace == 9) {
            return spp_account_chartools_message('error', 'Race code invalid.');
        }
        if ($newrace === $oldrace) {
            return spp_account_chartools_message('error', 'The new race and the original race are the same.');
        }
        if ($onlineStatus !== 0) {
            return spp_account_chartools_message('error', 'This character is online. Please try again later.');
        }
        if (!isGood($newrace, $class)) {
            return spp_account_chartools_message('error', 'Your class cannot be the chosen race. Please try again.');
        }

        if (!$context['allow_faction_change']) {
            if (!((isAlliance($newrace) && isAlliance($oldrace)) || (!isAlliance($newrace) && !isAlliance($oldrace)))) {
                return spp_account_chartools_message('error', 'Faction changes are disabled. Please select a race within your current faction.');
            }

            delMounts($guid, $oldrace, $charPdo);
            addMounts($guid, $newrace, $charPdo);
            $oldRepFaction = rep($oldrace);
            $newRepFaction = rep($newrace);
            $stmt = $charPdo->prepare("SELECT `standing` FROM character_reputation WHERE guid=? AND faction=?");
            $stmt->execute(array($guid, $oldRepFaction));
            $oldRep = $stmt->fetchColumn();
            $stmt->execute(array($guid, $newRepFaction));
            $newRep = $stmt->fetchColumn();

            if (isAlliance($oldrace)) {
                $stmtAch = $charPdo->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=2030 or criteria=2031 or criteria=2032 or criteria=2033 or criteria=2034)");
            } else {
                $stmtAch = $charPdo->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=992 or criteria=993 or criteria=994 or criteria=995 or criteria=996)");
            }
            $stmtAch->execute(array($guid));

            $stmtUp = $charPdo->prepare("UPDATE character_reputation SET standing=? WHERE guid=? AND faction=?");
            $stmtUp->execute(array($oldRep, $guid, $newRepFaction));
            $stmtUp->execute(array($newRep, $guid, $oldRepFaction));
            $stmtChar = $charPdo->prepare("UPDATE characters SET race=? ,at_login=8 ,playerBytes=1 WHERE guid=?");
            $stmtChar->execute(array($newrace, $guid));
        } else {
            $changingFaction = ((isAlliance($newrace) && !isAlliance($oldrace)) || (!isAlliance($newrace) && isAlliance($oldrace)));
            if ($changingFaction && $guildCheck != 0) {
                return spp_account_chartools_message('error', 'When changing factions, you must first leave your guild.');
            }

            delMounts($guid, $oldrace, $charPdo);
            addMounts($guid, $newrace, $charPdo);
            $stmtRep = $charPdo->prepare("SELECT `standing` FROM `character_reputation` WHERE guid=? AND faction=?");
            $stmtRep->execute(array($guid, 72)); $aone = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 47)); $atwo = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 69)); $athree = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 54)); $afour = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 930)); $afive = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 76)); $hone = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 68)); $htwo = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 81)); $hthree = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 530)); $hfour = $stmtRep->fetchColumn();
            $stmtRep->execute(array($guid, 911)); $hfive = $stmtRep->fetchColumn();

            $oldRepFaction = rep($oldrace);
            $newRepFaction = rep($newrace);
            $stmt = $charPdo->prepare("SELECT `standing` FROM character_reputation WHERE guid=? AND faction=?");
            $stmt->execute(array($guid, $oldRepFaction));
            $oldRep = $stmt->fetchColumn();
            $stmt->execute(array($guid, $newRepFaction));
            $newRep = $stmt->fetchColumn();

            if (isAlliance($oldrace)) {
                $stmtAch = $charPdo->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=2030 or criteria=2031 or criteria=2032 or criteria=2033 or criteria=2034)");
            } else {
                $stmtAch = $charPdo->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=992 or criteria=993 or criteria=994 or criteria=995 or criteria=996)");
            }
            $stmtAch->execute(array($guid));

            if (isAlliance($newrace) && !isAlliance($oldrace)) {
                $stmtPos = $charPdo->prepare("UPDATE characters SET position_x = -8913.23, position_y = 554.633, position_z = 93.7944, map = 0 WHERE guid=?");
                $stmtPos->execute(array($guid));
            }
            if (!isAlliance($newrace) && isAlliance($oldrace)) {
                $stmtPos = $charPdo->prepare("UPDATE characters SET position_x = 1440.45, position_y = -4422.78, position_z = 25.4634, map = 1 WHERE guid=?");
                $stmtPos->execute(array($guid));
            }

            $stmtUpdate = $charPdo->prepare("UPDATE character_reputation SET standing=? WHERE guid=? AND faction=?");
            if (!$changingFaction) {
                $stmtUpdate->execute(array($oldRep, $guid, $newRepFaction));
                $stmtUpdate->execute(array($newRep, $guid, $oldRepFaction));
            } elseif (isAlliance($newrace)) {
                $stmtRu = $charPdo->prepare("UPDATE `character_reputation` SET `standing`=?, `flags`=17 WHERE guid=? AND faction=?");
                foreach (array(array(72, $hone), array(47, $htwo), array(69, $hthree), array(54, $hfour), array(930, $hfive)) as $row) {
                    $stmtRu->execute(array($row[1], $guid, $row[0]));
                }
                $stmtLow = $charPdo->prepare("UPDATE `character_reputation` SET `standing`=150, `flags`=6 WHERE guid=? AND faction=?");
                foreach (array(76, 68, 81, 530, 911) as $factionId) {
                    $stmtLow->execute(array($guid, $factionId));
                }
            } else {
                $stmtRu = $charPdo->prepare("UPDATE `character_reputation` SET `standing`=?, `flags`=17 WHERE guid=? AND faction=?");
                foreach (array(array(76, $aone), array(68, $atwo), array(81, $athree), array(530, $afour), array(911, $afive)) as $row) {
                    $stmtRu->execute(array($row[1], $guid, $row[0]));
                }
                $stmtLow = $charPdo->prepare("UPDATE `character_reputation` SET `standing`=150, `flags`=6 WHERE guid=? AND faction=?");
                foreach (array(72, 47, 69, 54, 930) as $factionId) {
                    $stmtLow->execute(array($guid, $factionId));
                }
            }

            $stmtChar = $charPdo->prepare("UPDATE characters SET race=? ,at_login=8 ,playerBytes=1 WHERE guid=?");
            $stmtChar->execute(array($newrace, $guid));
        }

        $stmtPts = $realmPdo->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
        $stmtPts->execute(array((int)$context['char_faction_points'], (int)$context['char_faction_points'], $accountId));

        return spp_account_chartools_message('success', 'Success! Race successfully changed.');
    }
}
