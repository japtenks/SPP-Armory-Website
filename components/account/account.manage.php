<?php
if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>$lang['accediting'],'link'=>'');

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

if (!function_exists('spp_account_avatar_fallback_url')) {
    function spp_account_avatar_fallback_url(PDO $charsPdo, array $profile, array $accountCharacters = []) {
        $selectedGuid = (int)($profile['character_id'] ?? 0);
        if ($selectedGuid <= 0 && !empty($accountCharacters[0]['guid'])) {
            $selectedGuid = (int)$accountCharacters[0]['guid'];
        }
        if ($selectedGuid <= 0) {
            return '';
        }

        try {
            $stmt = $charsPdo->prepare("SELECT guid, race, class, gender FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmt->execute([$selectedGuid, (int)($profile['id'] ?? 0)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return '';
            }

            if (!function_exists('get_character_portrait_path')) {
                require_once(dirname(__DIR__) . '/forum/forum.func.php');
            }

            if (function_exists('get_character_portrait_path')) {
                return (string)get_character_portrait_path(
                    (int)$row['guid'],
                    (int)$row['gender'],
                    (int)$row['race'],
                    (int)$row['class']
                );
            }
        } catch (Throwable $e) {
            error_log('[account.manage] Avatar fallback lookup failed: ' . $e->getMessage());
        }

        return '';
    }
}

if (!function_exists('spp_manage_csrf_token')) {
    function spp_manage_csrf_token($formName = 'account_manage') {
        if (!isset($_SESSION['spp_csrf_tokens']) || !is_array($_SESSION['spp_csrf_tokens'])) {
            $_SESSION['spp_csrf_tokens'] = array();
        }

        if (empty($_SESSION['spp_csrf_tokens'][$formName])) {
            $_SESSION['spp_csrf_tokens'][$formName] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['spp_csrf_tokens'][$formName];
    }
}

if (!function_exists('spp_manage_require_csrf')) {
    function spp_manage_require_csrf($formName = 'account_manage') {
        $submittedToken = (string)($_POST['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens'][$formName] ?? '');

        if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            output_message('alert','<b>Security check failed. Please refresh the page and try again.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
            exit;
        }
    }
}

if (!function_exists('spp_manage_allowed_profile_fields')) {
    function spp_manage_allowed_profile_fields($backgroundPreferencesAvailable, $canHideProfile) {
        $allowedFields = array(
            'theme',
            'display_name',
            'fname',
            'lname',
            'city',
            'location',
            'hidelocation',
            'gmt',
            'msn',
            'icq',
            'aim',
            'yahoo',
            'skype',
            'homepage',
            'gender',
            'signature',
        );

        if ($canHideProfile) {
            $allowedFields[] = 'hideprofile';
        }

        if ($backgroundPreferencesAvailable) {
            $allowedFields[] = 'background_mode';
            $allowedFields[] = 'background_image';
        }

        return array_fill_keys($allowedFields, true);
    }
}
// ==================== //
if($user['id']<=0){
    redirect('index.php?n=account&sub=login',1);
}else{
    $managePdo = spp_get_pdo('realmd', 1);
    spp_ensure_website_account_row($managePdo, $user['id']);
    $currentRealmId = (int)($_COOKIE['cur_selected_realmd'] ?? $_COOKIE['cur_selected_realm'] ?? spp_resolve_realm_id($realmDbMap));
    if (!isset($realmDbMap[$currentRealmId])) {
        $currentRealmId = spp_resolve_realm_id($realmDbMap);
    }
    $manageCharPdo = spp_get_pdo('chars', $currentRealmId);
    $manageRealmName = 'Realm ' . $currentRealmId;
    try {
        $manageRealmPdo = spp_get_pdo('realmd', $currentRealmId);
        $stmtRealmName = $manageRealmPdo->prepare("SELECT name FROM realmlist WHERE id = ? LIMIT 1");
        $stmtRealmName->execute([$currentRealmId]);
        $realmName = $stmtRealmName->fetchColumn();
        if (!empty($realmName)) {
            $manageRealmName = (string)$realmName;
        }
    } catch (Throwable $e) {
        error_log('[account.manage] Realm name lookup failed: ' . $e->getMessage());
    }
    if (isset($_GET['pwchange'])) {
        if ($_GET['pwchange'] === '1') {
            output_message('notice', '<b>' . $lang['change_pass_succ'] . '</b>');
        } elseif ($_GET['pwchange'] === 'mismatch') {
            output_message('alert', '<b>New password confirmation does not match.</b>');
        } elseif ($_GET['pwchange'] === 'failed') {
            output_message('alert', '<b>Password change failed: SRP values were not saved.</b>');
        } elseif ($_GET['pwchange'] === 'short') {
            output_message('alert', '<b>' . $lang['change_pass_short'] . '</b>');
        }
    }
    if(!$_GET['action']){
        $profile = $auth->getprofile($user['id']);
        $profile['signature'] = str_replace('<br />','',$profile['signature']);
        $backgroundPreferencesAvailable = spp_website_accounts_has_columns(['background_mode', 'background_image']);
        $backgroundModeOptions = spp_background_mode_options();
        $availableBackgroundImages = spp_background_image_catalog();
        $profile['background_mode'] = isset($profile['background_mode']) && isset($backgroundModeOptions[$profile['background_mode']])
            ? (string)$profile['background_mode']
            : 'as_is';
        $defaultBackgroundImage = (string)spp_array_first_key($availableBackgroundImages);
        $profile['background_image'] = !empty($profile['background_image']) && isset($availableBackgroundImages[$profile['background_image']])
            ? (string)$profile['background_image']
            : $defaultBackgroundImage;
        $stmtChars = $manageCharPdo->prepare("SELECT guid, name, level, online FROM characters WHERE account=? ORDER BY name ASC");
        $stmtChars->execute([(int)$user['id']]);
        $accountCharacters = $stmtChars->fetchAll(PDO::FETCH_ASSOC);
        $profile['character_signatures'] = array();
        $profile['signature_character_guid'] = 0;
        $profile['signature_character_name'] = '';

        $availableCharacterGuids = array();
        foreach ($accountCharacters as $character) {
            $availableCharacterGuids[(int)$character['guid']] = (string)$character['name'];
        }

        $requestedSignatureGuid = (int)($_GET['sigchar'] ?? 0);
        if ($requestedSignatureGuid <= 0) {
            $requestedSignatureGuid = (int)($profile['character_id'] ?? 0);
        }
        if ($requestedSignatureGuid <= 0 && !empty($accountCharacters[0]['guid'])) {
            $requestedSignatureGuid = (int)$accountCharacters[0]['guid'];
        }
        if (!isset($availableCharacterGuids[$requestedSignatureGuid])) {
            $requestedSignatureGuid = 0;
        }

        foreach ($accountCharacters as $character) {
            $characterGuid = (int)$character['guid'];
            $identityId = spp_ensure_char_identity($currentRealmId, $characterGuid, $user['id'], (string)$character['name']);
            $profile['character_signatures'][$characterGuid] = array(
                'name' => (string)$character['name'],
                'signature' => $identityId > 0 ? str_replace('<br />', '', spp_get_identity_signature($identityId)) : '',
            );
        }

        if ($requestedSignatureGuid > 0) {
            $profile['signature_character_guid'] = $requestedSignatureGuid;
            $profile['signature_character_name'] = $availableCharacterGuids[$requestedSignatureGuid] ?? '';
            $profile['signature'] = (string)($profile['character_signatures'][$requestedSignatureGuid]['signature'] ?? $profile['signature']);
        }
        $profile['avatar_fallback_url'] = '';
        if (empty($profile['avatar'])) {
            $profile['avatar_fallback_url'] = spp_account_avatar_fallback_url($manageCharPdo, $profile, $accountCharacters);
        }
        $manage_csrf_token = spp_manage_csrf_token();
    }elseif($_GET['action']=='changeemail'){
        spp_manage_require_csrf();
        $newemail = trim($_POST['new_email']);
        if($auth->isvalidemail($newemail)){
            if($auth->isavailableemail($newemail)){
                $stmt = $managePdo->prepare("UPDATE account SET email=? WHERE id=? LIMIT 1");
                $stmt->execute([$newemail, (int)$user['id']]);
                if($stmt->rowCount() > 0){
                    if((int)$MW->getConfig->generic->use_purepass_table) {
                        $stmt = $managePdo->prepare("SELECT count(*) FROM account_pass WHERE id=?");
                        $stmt->execute([(int)$user['id']]);
                        $count_occur = $stmt->fetchColumn();
                        if($count_occur) {
                            $stmt = $managePdo->prepare("UPDATE account_pass SET email=? WHERE id=? LIMIT 1");
                            $stmt->execute([$newemail, (int)$user['id']]);
                        }
                    }
                    output_message('notice','<b>'.$lang['change_mail'].'</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
                }
            }else{
                output_message('alert','<b>'.$lang['reg_checkemailex'].'</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
            }
        }else{
            output_message('alert','<b>'.$lang['bad_mail'].'</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
        }
    }elseif($_GET['action']=='changepass'){
        spp_manage_require_csrf();
        $newpass = trim($_POST['new_pass']);
        $confirmPass = trim($_POST['confirm_new_pass'] ?? '');
        if(strlen($newpass)>3){
            if ($confirmPass === '' || $newpass !== $confirmPass) {
                redirect('index.php?n=account&sub=manage&pwchange=mismatch', 1);
                exit;
            }

            $stmt = $managePdo->prepare("UPDATE account SET sessionkey = NULL WHERE id = ?");
            $stmt->execute([(int)$user['id']]);
            list($salt, $verifier) = getRegistrationData((string)$user['username'], $newpass);
            $stmt = $managePdo->prepare("UPDATE account SET s = ?, v = ? WHERE id = ?");
            $stmt->execute([$salt, $verifier, (int)$user['id']]);

            $stmt = $managePdo->prepare("SELECT s, v FROM account WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$user['id']]);
            $updatedAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($updatedAccount['s']) || empty($updatedAccount['v'])) {
                redirect('index.php?n=account&sub=manage&pwchange=failed', 1);
                exit;
            }

            if((int)$MW->getConfig->generic->use_purepass_table) {
                $stmt = $managePdo->prepare("SELECT count(*) FROM account_pass WHERE id = ?");
                $stmt->execute([(int)$user['id']]);
                $count_occur = $stmt->fetchColumn();
                if($count_occur) {
                    $stmt = $managePdo->prepare("UPDATE account_pass SET password = ? WHERE id = ? LIMIT 1");
                    $stmt->execute([$newpass, (int)$user['id']]);
                } else {
                    $stmt = $managePdo->prepare("INSERT INTO account_pass SET id=?, username=?, password=?, email=?");
                    $stmt->execute([(int)$user['id'], $user['username'], $newpass, $user['email']]);
                }
            }
            
            //$uservars_hash_new = serialize(array($user['id'], sha1(base64_encode(md5(utf8_encode($sha_pass))))));
            //setcookie((string)$MW->getConfig->generic->site_cookie, $uservars_hash_new, time()+(60*60*24*365),$MW->getConfig->temp->site_href,$MW->getConfig->temp->site_domain); // expires in 365 days

            redirect('index.php?n=account&sub=manage&pwchange=1', 1);
            exit;
        }else{
            redirect('index.php?n=account&sub=manage&pwchange=short', 1);
            exit;
        }
    }elseif($_GET['action']=='change'){
        spp_manage_require_csrf();
        $backgroundPreferencesAvailable = spp_website_accounts_has_columns(['background_mode', 'background_image']);
        $backgroundModeOptions = spp_background_mode_options();
        $availableBackgroundImages = spp_background_image_catalog();
        $selectedSignatureGuid = (int)($_POST['signature_character_guid'] ?? 0);

        if(is_uploaded_file($_FILES['avatar']['tmp_name'])){
            if($_FILES['avatar']['size'] <= (int)$MW->getConfig->generic->max_avatar_file){
                $ext = strtolower(substr(strrchr($_FILES['avatar']['name'],'.'), 1));
                if(in_array($ext,array('gif','jpg','png'))){
                    if(@move_uploaded_file($_FILES['avatar']['tmp_name'], (string)$MW->getConfig->generic->avatar_path.$user['id'].'.'.$ext)){
                        list($width, $height, ,) = getimagesize((string)$MW->getConfig->generic->avatar_path.$user['id'].'.'.$ext);
                        $max_avatar_size = explode('x',(string)$MW->getConfig->generic->max_avatar_size);
                        if($width <= $max_avatar_size[0] || $height <= $max_avatar_size[1]){
                            $stmt = $managePdo->prepare("UPDATE website_accounts SET avatar=? WHERE account_id=? LIMIT 1");
                            $stmt->execute([$user['id'].'.'.$ext, (int)$user['id']]);
                        }else{
                            @unlink((string)$MW->getConfig->generic->avatar_path.$user['id'].'.'.$ext);
                        }
                    }
                }
            }
        }elseif($_POST['deleteavatar']==1 && preg_match("/\d+\.\w+/i",$_POST['avatarfile'])){
            if(@unlink((string)$MW->getConfig->generic->avatar_path.$_POST['avatarfile'])){
                $stmt = $managePdo->prepare("UPDATE website_accounts SET avatar=NULL WHERE account_id=? LIMIT 1");
                $stmt->execute([(int)$user['id']]);
            }
        }
        $profileInput = isset($_POST['profile']) && is_array($_POST['profile']) ? $_POST['profile'] : array();
        $allowedProfileFields = spp_manage_allowed_profile_fields(
            $backgroundPreferencesAvailable,
            (int)($user['gmlevel'] ?? 0) >= 3
        );

        foreach (array_keys($profileInput) as $profileKey) {
            if (!isset($allowedProfileFields[$profileKey])) {
                unset($profileInput[$profileKey]);
            }
        }

        if ($backgroundPreferencesAvailable) {
            $requestedBackgroundMode = strtolower(trim((string)($profileInput['background_mode'] ?? 'as_is')));
            if (!isset($backgroundModeOptions[$requestedBackgroundMode])) {
                $requestedBackgroundMode = 'as_is';
            }
            $profileInput['background_mode'] = $requestedBackgroundMode;

            $defaultBackgroundImage = (string)spp_array_first_key($availableBackgroundImages);
            $requestedBackgroundImage = basename(trim((string)($profileInput['background_image'] ?? '')));
            if (!isset($availableBackgroundImages[$requestedBackgroundImage])) {
                $requestedBackgroundImage = $defaultBackgroundImage;
            }
            $profileInput['background_image'] = $requestedBackgroundImage;
        } else {
            unset($profileInput['background_mode'], $profileInput['background_image']);
        }
        if (isset($profileInput['signature'])) {
            $profileInput['signature'] = htmlspecialchars((string)$profileInput['signature']);
        }

        if ($selectedSignatureGuid > 0) {
            $stmtOwnedChar = $manageCharPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmtOwnedChar->execute([$selectedSignatureGuid, (int)$user['id']]);
            $ownedCharacter = $stmtOwnedChar->fetch(PDO::FETCH_ASSOC);
            if ($ownedCharacter) {
                $identityId = spp_ensure_char_identity(
                    $currentRealmId,
                    (int)$ownedCharacter['guid'],
                    (int)$user['id'],
                    (string)$ownedCharacter['name']
                );
                if ($identityId > 0) {
                    spp_update_identity_signature($identityId, $_POST['profile']['signature']);
                }
            }
            unset($profileInput['signature']);
        }
        
        $profile = RemoveXSS($profileInput);
        if (!empty($profile) && is_array($profile)) {
            $setClause = implode(',', array_map(
                function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; },
                array_keys($profile)
            ));
            $values = array_values($profile);
            $values[] = (int)$user['id'];
            $stmt = $managePdo->prepare("UPDATE website_accounts SET $setClause WHERE account_id=? LIMIT 1");
            $stmt->execute($values);
        }
        
        redirect('index.php?n=account&sub=manage',1);
    }elseif($_GET['action']=='changesecretq'){
        spp_manage_require_csrf();
        if(check_for_symbols($_POST['secreta1']) == FALSE && check_for_symbols($_POST['secreta2']) == FALSE && $_POST[secretq1] != '0' && $_POST[secretq2]!= '0' && isset($_POST[secreta1]) &&
        isset($_POST[secreta2]) && strlen($_POST[secreta1])>4 && strlen($_POST[secreta2])>4 && $_POST['secreta1'] != $_POST['secreta2'] && $_POST['secretq1'] != $_POST['secretq2']){
            $stmt = $managePdo->prepare("UPDATE website_accounts SET secretq1=?,secretq2=?,secreta1=?,secreta2=? WHERE account_id=? LIMIT 1");
            $stmt->execute([strip_if_magic_quotes($_POST['secretq1']), strip_if_magic_quotes($_POST['secretq2']), strip_if_magic_quotes($_POST['secreta1']), strip_if_magic_quotes($_POST['secreta2']), (int)$user['id']]);
            output_message('notice','<b>'.$lang['changed_secretq'].'</b><meta http-equiv=refresh content="4;url=index.php?n=account&sub=manage">');

        }else{
            output_message('alert','<b>'.$lang['fail_change_secretq'].'</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=manage">');
        }
    }elseif($_GET['action']=='resetsecretq'){
        spp_manage_require_csrf();
        if ($_POST['reset_secretq']){
          $stmt = $managePdo->prepare("UPDATE website_accounts SET secretq1='0',secretq2='0',secreta1='0',secreta2='0' WHERE account_id=? LIMIT 1");
          $stmt->execute([(int)$user['id']]);
          output_message('notice','<b>'.$lang['reset_succ_secretq'].'</b><meta http-equiv=refresh content="4;url=index.php?n=account&sub=manage">');
        }
    }elseif($_GET['action'] == 'change_gameplay'){
       spp_manage_require_csrf();
       if($_POST['switch_wow_type']=='wotlk'){
               $stmt = $managePdo->prepare("UPDATE `account` SET expansion='2' WHERE `id`=?");
               $stmt->execute([(int)$user['id']]);
               redirect('index.php?n=account&sub=manage',1);
         }
         elseif($_POST['switch_wow_type']=='tbc'){
               $stmt = $managePdo->prepare("UPDATE `account` SET expansion='1' WHERE `id`=?");
               $stmt->execute([(int)$user['id']]);
               redirect('index.php?n=account&sub=manage',1);
         }
         elseif($_POST['switch_wow_type']=='classic'){
               $stmt = $managePdo->prepare("UPDATE `account` SET expansion='0' WHERE `id`=?");
               $stmt->execute([(int)$user['id']]);
               redirect('index.php?n=account&sub=manage',1);
         }
   }elseif($_GET['action'] == 'renamechar'){
       spp_manage_require_csrf();
       $characterGuid = (int)($_POST['character_guid'] ?? 0);
       $newName = ucfirst(strtolower(trim((string)($_POST['new_character_name'] ?? ''))));

       if ($characterGuid <= 0 || $newName === '') {
           output_message('alert','<b>Please choose a character and enter a new name.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
       } else {
           $stmtCharacter = $manageCharPdo->prepare("SELECT guid, name, online FROM characters WHERE guid=? AND account=? LIMIT 1");
           $stmtCharacter->execute([$characterGuid, (int)$user['id']]);
           $characterRow = $stmtCharacter->fetch(PDO::FETCH_ASSOC);

           if (!$characterRow) {
           output_message('alert','<b>Character not found on this account.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
           } elseif ((int)$characterRow['online'] === 1) {
           output_message('alert','<b>This character is online. Please log out before renaming.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
           } elseif (check_for_symbols($newName, 1) == TRUE) {
           output_message('alert','<b>Character names can only use valid letters.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
           } else {
               $stmtNameCheck = $manageCharPdo->prepare("SELECT COUNT(*) FROM characters WHERE name=?");
               $stmtNameCheck->execute([$newName]);
               if ((int)$stmtNameCheck->fetchColumn() > 0) {
           output_message('alert','<b>That character name is already taken.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
               } else {
                   $stmtRename = $manageCharPdo->prepare("UPDATE characters SET name=? WHERE guid=? AND account=? LIMIT 1");
                   $stmtRename->execute([$newName, $characterGuid, (int)$user['id']]);

                   if (!empty($user['character_id']) && (int)$user['character_id'] === $characterGuid) {
                       $stmtSelected = $managePdo->prepare("UPDATE website_accounts SET character_name=? WHERE account_id=? LIMIT 1");
                       $stmtSelected->execute([$newName, (int)$user['id']]);
                   }

                   redirect('index.php?n=account&sub=manage',1);
               }
           }
       }
   }
}
?>
