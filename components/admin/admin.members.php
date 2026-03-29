<?php
if(INCLUDED !== true)exit;
// ==================== //
$oldInactiveTime = 3600 * 24 * 7;
$deleteInactiveAccountsEnabled = false;
$deleteInactiveCharactersEnabled = false;
$membersPdo = spp_get_pdo('realmd', 1);
$membersCharsPdo = spp_get_pdo('chars', spp_resolve_realm_id($realmDbMap));

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
// ==================== //
if($_POST['search_member'] == TRUE){
    $s_string = trim($_POST['search_member']);
    $stmt = $membersPdo->prepare("SELECT id FROM account WHERE username=?");
    $stmt->execute([$s_string]);
    $st = $stmt->fetchColumn();
    if($st != ''){
        redirect('index.php?n=admin&sub=members&id=' . $st, 0);
    }else{
        output_message('alert', 'No results');
    }
}
if($_GET['id'] > 0){
    if (isset($_GET['xfer'])) {
        if ($_GET['xfer'] === 'success') {
            output_message('notice', '<b>Character transferred to the target account.</b>');
        } elseif ($_GET['xfer'] === 'missing_target') {
            output_message('alert', '<b>Target account was not found.</b>');
        } elseif ($_GET['xfer'] === 'source_online') {
            output_message('alert', '<b>Source account is still online. Log it out before transferring.</b>');
        } elseif ($_GET['xfer'] === 'target_online') {
            output_message('alert', '<b>Target account is still online. Log it out before transferring.</b>');
        } elseif ($_GET['xfer'] === 'char_online') {
            output_message('alert', '<b>The selected character is still online. Log it out before transferring.</b>');
        } elseif ($_GET['xfer'] === 'same_target') {
            output_message('alert', '<b>Target account must be different from the current account.</b>');
        } elseif ($_GET['xfer'] === 'missing_character') {
            output_message('alert', '<b>That character was not found on this account.</b>');
        } elseif ($_GET['xfer'] === 'failed') {
            output_message('alert', '<b>Character transfer failed. No changes were saved.</b>');
        }
    }
    if (isset($_GET['chardelete'])) {
        if ($_GET['chardelete'] === 'success') {
            output_message('notice', '<b>Character deleted from the active realm.</b>');
        } elseif ($_GET['chardelete'] === 'missing') {
            output_message('alert', '<b>That character was not found on this account.</b>');
        } elseif ($_GET['chardelete'] === 'failed') {
            output_message('alert', '<b>Character deletion failed. No changes were saved.</b>');
        }
    }
    if (isset($_GET['pwreset'])) {
        if ($_GET['pwreset'] === '1') {
            output_message('notice', '<b>' . $lang['change_pass_succ'] . '</b>');
        } elseif ($_GET['pwreset'] === 'mismatch') {
            output_message('alert', '<b>New password confirmation does not match.</b>');
        } elseif ($_GET['pwreset'] === 'missing') {
            output_message('alert', '<b>Account not found.</b>');
        } elseif ($_GET['pwreset'] === 'failed') {
            output_message('alert', '<b>Password reset failed: SRP values were not saved.</b>');
        }
    }

    if(!$_GET['action']){
        $profile = $auth->getprofile($_GET['id']);
        spp_ensure_website_account_row($membersPdo, $_GET['id']);
        $eligibleTransferAccounts = array();
        $stmt = $membersPdo->query("SELECT g_id, g_title FROM website_account_groups");
        $allgroups = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $stmt = $membersPdo->prepare("SELECT donator FROM website_accounts WHERE account_id=?");
        $stmt->execute([(int)$_GET['id']]);
        $donator = $stmt->fetchColumn();
        $id = $_GET['id'];
        $stmt = $membersPdo->prepare("SELECT active FROM account_banned WHERE id=? AND active=1");
        $stmt->execute([(int)$id]);
        $act = $stmt->fetchColumn();
        $active = $act;

        $stmt = $membersCharsPdo->prepare("SELECT `guid`, `name`, `race`, `class`, `level`, `online` FROM `characters` WHERE `account` = ? ORDER BY guid");
        $stmt->execute([(int)$_GET['id']]);
        $userchars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $onlineCharacterCount = 0;
        foreach ($userchars as $userchar) {
            if (!empty($userchar['online'])) {
                $onlineCharacterCount++;
            }
        }
        $stmtEligible = $membersPdo->prepare("
            SELECT id, username
            FROM account
            WHERE id <> ?
              AND LOWER(username) NOT LIKE 'rndbot%'
            ORDER BY username ASC, id ASC
        ");
        $stmtEligible->execute([(int)$_GET['id']]);
        $eligibleTransferAccounts = $stmtEligible->fetchAll(PDO::FETCH_ASSOC);
        $profile['is_bot_account'] = stripos((string)($profile['username'] ?? ''), 'rndbot') === 0;
        $profile['character_signatures'] = array();
        if (!empty($userchars)) {
            $activeRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
            foreach ($userchars as $char) {
                $charGuid = (int)($char['guid'] ?? 0);
                $charName = (string)($char['name'] ?? '');
                if ($charGuid <= 0 || $charName === '') {
                    continue;
                }
                $identityId = spp_ensure_char_identity($activeRealmId, $charGuid, (int)$_GET['id'], $charName);
                $profile['character_signatures'][$charGuid] = $identityId > 0 ? str_replace('<br />', '', spp_get_identity_signature($identityId)) : '';
            }
        }
        
        $pathway_info[] = array('title' => $lang['users_manage'], 'link' => $com_links['sub_members']);
        $pathway_info[] = array('title' => $profile['username'], 'link' => '');
        
        $txt['yearlist'] = "\n";
        $txt['monthlist'] = "\n";
        $txt['daylist'] = "\n";
        for($i = 1; $i <= 31; $i++){
            $txt['daylist'] .= "<option value='$i'" . ($i == $profile['bd_day'] ? ' selected' : '') . "> $i </option>\n";
        }
        for($i = 1; $i <= 12; $i++){
            $txt['monthlist'] .= "<option value='$i'" . ($i == $profile['bd_month'] ? ' selected' : '') . "> $i </option>\n";
        }
        for($i = 1950; $i <= date('Y'); $i++){
            $txt['yearlist'] .= "<option value='$i'" . ($i == $profile['bd_year'] ? ' selected' : '') . "> $i </option>\n";
        }
        $profile['signature'] = str_replace('<br />', '', $profile['signature']);
    }elseif($_GET['action'] == 'changepass'){
        $newpass = trim($_POST['new_pass']);
        $confirmPass = trim($_POST['confirm_new_pass'] ?? '');
        if(strlen($newpass) > 3){
            if ($confirmPass === '' || $newpass !== $confirmPass) {
                redirect('index.php?n=admin&sub=members&id=' . $_GET['id'] . '&pwreset=mismatch', 1);
                exit;
            }

            $id = (int)$_GET['id'];
            $stmt = $membersPdo->prepare("SELECT username FROM account WHERE id=?");
            $stmt->execute([$id]);
            $maneresu = $stmt->fetchColumn();
            if (!$maneresu) {
                redirect('index.php?n=admin&sub=members&id=' . $_GET['id'] . '&pwreset=missing', 1);
                exit;
            }
            $stmt = $membersPdo->prepare("UPDATE account SET sessionkey = NULL WHERE id=?");
            $stmt->execute([$id]);
            list($salt, $verifier) = getRegistrationData((string)$maneresu, $newpass);
            $stmt = $membersPdo->prepare("UPDATE account SET s=?, v=? WHERE id=?");
            $stmt->execute([$salt, $verifier, $id]);

            $stmt = $membersPdo->prepare("SELECT s, v FROM account WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $updatedAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($updatedAccount['s']) || empty($updatedAccount['v'])) {
                redirect('index.php?n=admin&sub=members&id=' . $_GET['id'] . '&pwreset=failed', 1);
                exit;
            }

            redirect('index.php?n=admin&sub=members&id=' . $_GET['id'] . '&pwreset=1', 1);
            exit;
        }else{
            output_message('alert', '<b>' . $lang['change_pass_short'] . '</b><meta http-equiv=refresh content="2;url=index.php?n=admin&sub=members&id=' . $_GET['id'] . '">');
        }
    }elseif($_GET['action'] == 'ban'){
        $id = (int)$_GET['id'];
        $stmt = $membersPdo->prepare("INSERT into account_banned (id, bandate, unbandate, bannedby, banreason, active) values (?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()-10, 'WEBSERVER', 'WEBSERVER', 1)");
        $stmt->execute([$id]);
        $stmt = $membersPdo->prepare("SELECT last_ip FROM account WHERE id=?");
        $stmt->execute([$id]);
        $q = $stmt->fetchColumn();
        $stmt = $membersPdo->prepare("INSERT into ip_banned (ip, bandate, unbandate, bannedby, banreason) values (?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()-10, 'WEBSERVER', 'WEBSERVER')");
        $stmt->execute([$q]);
        $stmt = $membersPdo->prepare("UPDATE website_accounts SET g_id=5 WHERE account_id=?");
        $stmt->execute([$id]);
        redirect('index.php?n=admin&sub=members&id=' . $_GET['id'], 1); exit;
    }elseif($_GET['action'] == 'unban'){
        $id = (int)$_GET['id'];
        $stmt = $membersPdo->prepare("UPDATE account_banned SET active=0 WHERE id=?");
        $stmt->execute([$id]);
        $stmt = $membersPdo->prepare("SELECT last_ip FROM account WHERE id=?");
        $stmt->execute([$id]);
        $q = $stmt->fetchColumn();
        $stmt = $membersPdo->prepare("DELETE FROM ip_banned WHERE ip=?");
        $stmt->execute([$q]);
        $stmt = $membersPdo->prepare("UPDATE website_accounts SET g_id=2 WHERE account_id=?");
        $stmt->execute([$id]);
        redirect('index.php?n=admin&sub=members&id=' . $_GET['id'], 1); exit;
    }elseif($_GET['action'] == 'change'){
        $profile = $_POST['profile'];
        $setClause = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($profile)));
        $values = array_values($profile);
        $values[] = (int)$_GET['id'];
        $stmt = $membersPdo->prepare("UPDATE account SET $setClause WHERE id=? LIMIT 1");
        $stmt->execute($values);
        redirect('index.php?n=admin&sub=members&id=' . $_GET['id'], 1); exit;
    }elseif($_GET['action'] == 'change2'){
        spp_ensure_website_account_row($membersPdo, $_GET['id']);
        if(is_uploaded_file($_FILES['avatar']['tmp_name'])){
            if($_FILES['avatar']['size'] <= (int)$MW->getConfig->generic->max_avatar_file){
                $tmp_filenameadd = time();
                if(@move_uploaded_file($_FILES['avatar']['tmp_name'], (string)$MW->getConfig->generic->avatar_path . $tmp_filenameadd . $_FILES['avatar']['name'])){
                    list($width, $height, ,) = getimagesize((string)$MW->getConfig->generic->avatar_path . $tmp_filenameadd . $_FILES['avatar']['name']);
                    $path_parts = pathinfo((string)$MW->getConfig->generic->avatar_path . $tmp_filenameadd . $_FILES['avatar']['name']);
                    $max_avatar_size = explode('x', (string)$MW->getConfig->generic->max_avatar_size);
                    if($width <= $max_avatar_size[0] || $height <= $max_avatar_size[1]){
                        if(@rename((string)$MW->getConfig->generic->avatar_path . $tmp_filenameadd . $_FILES['avatar']['name'], (string)$MW->getConfig->generic->avatar_path . $_GET['id'] . '.' . $path_parts['extension'])){
                            $upl_avatar_name = $_GET['id'] . '.' . $path_parts['extension'];
                        }else{
                            $upl_avatar_name = $tmp_filenameadd . $_FILES['avatar']['name'];
                        }
                        if($upl_avatar_name) {
                            $stmt = $membersPdo->prepare("UPDATE website_accounts SET avatar=? WHERE account_id=? LIMIT 1");
                            $stmt->execute([$upl_avatar_name, (int)$_GET['id']]);
                        }
                    }else{
                        @unlink((string)$MW->getConfig->generic->avatar_path . $tmp_filenameadd . $_FILES['avatar']['name']);
                    }
                }
            }
        }elseif($_POST['deleteavatar'] == 1){
            if(@unlink((string)$MW->getConfig->generic->avatar_path . $_POST['avatarfile'])){
                $stmt = $membersPdo->prepare("UPDATE website_accounts SET avatar=NULL WHERE account_id=? LIMIT 1");
                $stmt->execute([(int)$_GET['id']]);
            }
        }
        $_POST['profile']['signature'] = htmlspecialchars($_POST['profile']['signature']);
        $profile2 = $_POST['profile'];
        $setClause2 = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($profile2)));
        $values2 = array_values($profile2);
        $values2[] = (int)$_GET['id'];
        $stmt = $membersPdo->prepare("UPDATE website_accounts SET $setClause2 WHERE account_id=? LIMIT 1");
        $stmt->execute($values2);
        redirect('index.php?n=admin&sub=members&id=' . $_GET['id'], 1); exit;
    }elseif($_GET['action'] == 'setbotsignatures'){
        $id = (int)$_GET['id'];
        $stmtBot = $membersPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
        $stmtBot->execute([$id]);
        $botUsername = (string)$stmtBot->fetchColumn();
        if (stripos($botUsername, 'rndbot') === 0) {
            $activeRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
            $postedSignatures = $_POST['character_signature'] ?? array();
            foreach ($postedSignatures as $guid => $signature) {
                $characterGuid = (int)$guid;
                if ($characterGuid <= 0) {
                    continue;
                }

                $stmtChar = $membersCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
                $stmtChar->execute([$characterGuid, $id]);
                $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
                if (!$charRow) {
                    continue;
                }

                $cleanSignature = htmlspecialchars((string)$signature);
                $identityId = spp_ensure_char_identity($activeRealmId, (int)$charRow['guid'], $id, (string)$charRow['name']);
                if ($identityId > 0) {
                    spp_update_identity_signature($identityId, $cleanSignature);
                }
            }
        }
        redirect('index.php?n=admin&sub=members&id=' . $id, 1); exit;
    }elseif($_GET['action'] == 'transferchar' || $_GET['action'] == 'transferbotchar'){
        $sourceAccountId = (int)$_GET['id'];
        $stmtBot = $membersPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
        $stmtBot->execute([$sourceAccountId]);
        $sourceUsername = (string)$stmtBot->fetchColumn();

        $characterGuid = (int)($_POST['transfer_character_guid'] ?? 0);
        $targetAccountId = (int)($_POST['target_account_id'] ?? 0);
        if ($characterGuid <= 0) {
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=missing_character', 1); exit;
        }

        $stmtTarget = $membersPdo->prepare("
            SELECT id, username
            FROM account
            WHERE id = ?
              AND LOWER(username) NOT LIKE 'rndbot%'
            LIMIT 1
        ");
        $stmtTarget->execute([$targetAccountId]);
        $targetAccount = $stmtTarget->fetch(PDO::FETCH_ASSOC) ?: null;
        $targetAccountId = (int)($targetAccount['id'] ?? 0);
        $targetUsername = (string)($targetAccount['username'] ?? '');

        if ($targetAccountId <= 0) {
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=missing_target', 1); exit;
        }
        if ($targetAccountId === $sourceAccountId) {
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=same_target', 1); exit;
        }

        $stmtChar = $membersCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
        $stmtChar->execute([$characterGuid, $sourceAccountId]);
        $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
        if (!$charRow) {
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=missing_character', 1); exit;
        }

        $activeRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
        $sourceAccountOnline = spp_admin_account_is_online($membersPdo, $sourceAccountId);
        $targetAccountOnline = spp_admin_account_is_online($membersPdo, $targetAccountId);
        $characterOnline = spp_admin_character_is_online($membersCharsPdo, $characterGuid, $sourceAccountId);

        if ($sourceAccountOnline || $targetAccountOnline || $characterOnline) {
            if ($sourceAccountOnline || $characterOnline) {
                $sourceOnlineCharacters = spp_admin_online_characters_for_account($membersCharsPdo, $sourceAccountId);
                $soapError = '';
                if (!spp_admin_force_characters_offline($activeRealmId, $sourceOnlineCharacters, $soapError) && $soapError !== '') {
                    error_log('[admin.members] Source character kick attempt failed for ' . $sourceUsername . ': ' . $soapError);
                }
            }
            if ($targetAccountOnline) {
                $targetOnlineCharacters = spp_admin_online_characters_for_account($membersCharsPdo, $targetAccountId);
                $soapError = '';
                if (!spp_admin_force_characters_offline($activeRealmId, $targetOnlineCharacters, $soapError) && $soapError !== '') {
                    error_log('[admin.members] Target character kick attempt failed for ' . $targetUsername . ': ' . $soapError);
                }
            }

            for ($i = 0; $i < 5; $i++) {
                usleep(500000);
                $sourceAccountOnline = spp_admin_account_is_online($membersPdo, $sourceAccountId);
                $targetAccountOnline = spp_admin_account_is_online($membersPdo, $targetAccountId);
                $characterOnline = spp_admin_character_is_online($membersCharsPdo, $characterGuid, $sourceAccountId);
                if (!$sourceAccountOnline && !$targetAccountOnline && !$characterOnline) {
                    break;
                }
            }

            if ($characterOnline) {
                redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=char_online', 1); exit;
            }
            if ($sourceAccountOnline) {
                redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=source_online', 1); exit;
            }
            if ($targetAccountOnline) {
                redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=target_online', 1); exit;
            }
        }

        try {
            $membersCharsPdo->beginTransaction();
            $membersPdo->beginTransaction();

            $stmtMove = $membersCharsPdo->prepare("UPDATE characters SET account=? WHERE guid=? AND account=? LIMIT 1");
            $stmtMove->execute([$targetAccountId, $characterGuid, $sourceAccountId]);
            if ($stmtMove->rowCount() <= 0) {
                throw new RuntimeException('Character account update failed.');
            }

            spp_ensure_website_account_row($membersPdo, $targetAccountId);

            $identity = function_exists('spp_get_char_identity') ? spp_get_char_identity($activeRealmId, $characterGuid) : null;
            if (!empty($identity['identity_id'])) {
                $targetIsBot = stripos($targetUsername, 'rndbot') === 0;
                $stmtIdentity = $membersPdo->prepare("
                    UPDATE website_identities
                    SET owner_account_id = ?, identity_type = ?, is_bot = ?, is_active = 1, updated_at = NOW()
                    WHERE identity_id = ?
                    LIMIT 1
                ");
                $stmtIdentity->execute([
                    $targetAccountId,
                    $targetIsBot ? 'bot_character' : 'character',
                    $targetIsBot ? 1 : 0,
                    (int)$identity['identity_id']
                ]);
            }

            $stmtWebsiteSource = $membersPdo->prepare("
                UPDATE website_accounts
                SET character_id = CASE WHEN character_id = ? THEN NULL ELSE character_id END,
                    character_name = CASE WHEN character_id = ? THEN NULL ELSE character_name END
                WHERE account_id = ?
            ");
            $stmtWebsiteSource->execute([$characterGuid, $characterGuid, $sourceAccountId]);

            $stmtWebsiteTarget = $membersPdo->prepare("SELECT character_id FROM website_accounts WHERE account_id=? LIMIT 1");
            $stmtWebsiteTarget->execute([$targetAccountId]);
            $targetSelectedCharacter = (int)$stmtWebsiteTarget->fetchColumn();
            if ($targetSelectedCharacter <= 0) {
                $stmtWebsiteAssign = $membersPdo->prepare("UPDATE website_accounts SET character_id=?, character_name=? WHERE account_id=?");
                $stmtWebsiteAssign->execute([$characterGuid, (string)$charRow['name'], $targetAccountId]);
            }

            $membersCharsPdo->commit();
            $membersPdo->commit();
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=success', 1); exit;
        } catch (Throwable $e) {
            if ($membersCharsPdo->inTransaction()) {
                $membersCharsPdo->rollBack();
            }
            if ($membersPdo->inTransaction()) {
                $membersPdo->rollBack();
            }
            error_log('[admin.members] Bot character transfer failed: ' . $e->getMessage());
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&xfer=failed', 1); exit;
        }
    }elseif($_GET['action'] == 'deletechar'){
        $sourceAccountId = (int)$_GET['id'];
        $characterGuid = (int)($_POST['delete_character_guid'] ?? 0);
        if ($characterGuid <= 0) {
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&chardelete=missing', 1); exit;
        }

        $stmtChar = $membersCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
        $stmtChar->execute([$characterGuid, $sourceAccountId]);
        $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
        if (!$charRow) {
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&chardelete=missing', 1); exit;
        }

        $activeRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
        try {
            $membersCharsPdo->beginTransaction();
            $membersPdo->beginTransaction();

            foreach (spp_admin_character_delete_tables() as $table => $column) {
                $stmtDelete = $membersCharsPdo->prepare("DELETE FROM `$table` WHERE `$column` = ?");
                $stmtDelete->execute([$characterGuid]);
            }

            $identity = function_exists('spp_get_char_identity') ? spp_get_char_identity($activeRealmId, $characterGuid) : null;
            if (!empty($identity['identity_id'])) {
                $stmtIdentity = $membersPdo->prepare("
                    UPDATE website_identities
                    SET is_active = 0, updated_at = NOW()
                    WHERE identity_id = ?
                    LIMIT 1
                ");
                $stmtIdentity->execute([(int)$identity['identity_id']]);
            }

            $stmtWebsiteSource = $membersPdo->prepare("
                UPDATE website_accounts
                SET character_id = CASE WHEN character_id = ? THEN NULL ELSE character_id END,
                    character_name = CASE WHEN character_id = ? THEN NULL ELSE character_name END
                WHERE account_id = ?
            ");
            $stmtWebsiteSource->execute([$characterGuid, $characterGuid, $sourceAccountId]);

            $membersCharsPdo->commit();
            $membersPdo->commit();
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&chardelete=success', 1); exit;
        } catch (Throwable $e) {
            if ($membersCharsPdo->inTransaction()) {
                $membersCharsPdo->rollBack();
            }
            if ($membersPdo->inTransaction()) {
                $membersPdo->rollBack();
            }
            error_log('[admin.members] Character delete failed: ' . $e->getMessage());
            redirect('index.php?n=admin&sub=members&id=' . $sourceAccountId . '&chardelete=failed', 1); exit;
        }
    }elseif($_GET['action'] == 'dodeleteacc'){
        $deleteId = (int)$_GET['id'];
        $deleteRealmId = spp_resolve_realm_id($realmDbMap);
        if (function_exists('spp_deactivate_account_identities')) {
            spp_deactivate_account_identities($deleteRealmId, $deleteId);
        }
        $stmt = $membersPdo->prepare("DELETE FROM account WHERE id=? LIMIT 1");
        $stmt->execute([$deleteId]);
        $stmt = $membersPdo->prepare("DELETE FROM website_accounts WHERE account_id=? LIMIT 1");
        $stmt->execute([$deleteId]);
        $stmt = $membersPdo->prepare("DELETE FROM pms WHERE owner_id=? LIMIT 1");
        $stmt->execute([$deleteId]);
        redirect('index.php?n=admin&sub=members', 1); exit;
    }
}else{
    if($_GET['action'] == 'deleteinactive'){
        if (!$deleteInactiveAccountsEnabled) {
            output_message('alert', 'Inactive account deletion is currently disabled until this maintenance workflow is reviewed.');
        } else {
        $cur_timestamp = date('YmdHis', time() - $oldInactiveTime);
        $stmt = $membersPdo->prepare("
            SELECT account_id FROM website_accounts
            JOIN account ON account.id=website_accounts.account_id
            WHERE activation_code IS NOT NULL AND joindate < ?
        ");
        $stmt->execute([$cur_timestamp]);
        $accids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!empty($accids)) {
            $accPlaceholders = implode(',', array_fill(0, count($accids), '?'));
            $accInts = array_map('intval', $accids);
            $stmt = $membersPdo->prepare("DELETE FROM account WHERE id IN($accPlaceholders)");
            $stmt->execute($accInts);
            $stmt = $membersPdo->prepare("DELETE FROM website_accounts WHERE account_id IN($accPlaceholders)");
            $stmt->execute($accInts);
        }
        redirect('index.php?n=admin&sub=members', 1); exit;
        }
    }elseif($_GET['action'] == 'deleteinactive_characters'){
        if (!$deleteInactiveCharactersEnabled) {
            output_message('alert', 'Inactive character deletion is currently disabled until this maintenance workflow is reviewed.');
        } else {
        // Action to delete all characters that is so and so old. look at $delete_in_days variable beneath.
        $delete_in_days = 90;
        $cur_timestamp = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - $delete_in_days, date('Y')));
        $stmt = $membersPdo->prepare("SELECT id FROM account LEFT JOIN website_accounts ON account.id=website_accounts.account_id WHERE ? >= last_login AND website_accounts.vip=0");
        $stmt->execute([$cur_timestamp]);
        $accountids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $charguids = [];
        if(count($accountids)) {
            $acctPlaceholders = implode(',', array_fill(0, count($accountids), '?'));
            $acctInts = array_map('intval', $accountids);
            $stmt = $membersCharsPdo->prepare("SELECT guid FROM `characters` WHERE account IN ($acctPlaceholders)");
            $stmt->execute($acctInts);
            $charguids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        if(count($charguids)){
            $guidPlaceholders = implode(',', array_fill(0, count($charguids), '?'));
            $guidInts = array_map('intval', $charguids);
            foreach (spp_admin_character_delete_tables() as $table => $col) {
                $stmt = $membersCharsPdo->prepare("DELETE FROM `$table` WHERE `$col` IN ($guidPlaceholders)");
                $stmt->execute($guidInts);
            }
        }
        output_message('alert', 'Accounts checked: ' . count($accountids) . '. Characters deleted: ' . count($charguids) . '.');
        }
    }
    $pathway_info[] = array('title' => $lang['users_manage'], 'link' => '');
    //===== Filter ==========//
    $includeBots = !isset($_GET['show_bots']) || $_GET['show_bots'] === '1';
    $conditions = [];
    $filterParams = [];
    if (!$includeBots) {
        $conditions[] = "LOWER(`username`) NOT LIKE 'rndbot%'";
    }
    if($_GET['char'] && preg_match("/[a-z]/", $_GET['char'])){
        $conditions[] = '`username` LIKE ?';
        $filterParams[] = $_GET['char'] . '%';
    }elseif($_GET['char'] == 1){
        $conditions[] = '`username` REGEXP \'^[^A-Za-z]\'';
    }
    $filter = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    //===== Calc pages =====//
    $items_per_pages = (int)$MW->getConfig->generic->users_per_page;
    $stmt = $membersPdo->prepare("SELECT count(*) FROM account $filter");
    $stmt->execute($filterParams);
    $itemnum = $stmt->fetchColumn();
    $pnum = ceil($itemnum / $items_per_pages);
    $pages_str = default_paginate($pnum, $p, "index.php?n=admin&sub=members&show_bots=" . ($includeBots ? '1' : '0') . "&char=" . $_GET['char']);
    $limit_start = ($p - 1) * $items_per_pages;

    $stmt = $membersPdo->prepare("
        SELECT * FROM account
        LEFT JOIN website_accounts ON account.id=website_accounts.account_id
        $filter
        ORDER BY username
        LIMIT " . (int)$limit_start . "," . (int)$items_per_pages);
    $stmt->execute($filterParams);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
