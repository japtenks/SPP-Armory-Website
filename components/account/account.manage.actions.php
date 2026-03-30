<?php

function spp_account_manage_handle_action($action, array $ctx): bool
{
    switch ($action) {
        case 'changeemail':
            spp_account_manage_change_email($ctx);
            return true;
        case 'changepass':
            spp_account_manage_change_password($ctx);
            return true;
        case 'change':
            spp_account_manage_change_profile($ctx);
            return true;
        case 'changesecretq':
            spp_account_manage_change_secret_questions($ctx);
            return true;
        case 'resetsecretq':
            spp_account_manage_reset_secret_questions($ctx);
            return true;
        case 'change_gameplay':
            spp_account_manage_change_gameplay($ctx);
            return true;
        case 'renamechar':
            spp_account_manage_rename_character($ctx);
            return true;
        default:
            return false;
    }
}

function spp_account_manage_change_email(array $ctx): void
{
    global $lang;
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');

    $newemail = trim($_POST['new_email']);
    $managePdo = $ctx['managePdo'];
    $auth = $ctx['auth'];
    $user = $ctx['user'];
    $MW = $ctx['MW'];

    if ($auth->isvalidemail($newemail)) {
        if ($auth->isavailableemail($newemail)) {
            $stmt = $managePdo->prepare("UPDATE account SET email=? WHERE id=? LIMIT 1");
            $stmt->execute([$newemail, (int)$user['id']]);
            if ($stmt->rowCount() > 0) {
                if ((int)$MW->getConfig->generic->use_purepass_table) {
                    $stmt = $managePdo->prepare("SELECT count(*) FROM account_pass WHERE id=?");
                    $stmt->execute([(int)$user['id']]);
                    $count_occur = $stmt->fetchColumn();
                    if ($count_occur) {
                        $stmt = $managePdo->prepare("UPDATE account_pass SET email=? WHERE id=? LIMIT 1");
                        $stmt->execute([$newemail, (int)$user['id']]);
                    }
                }
                output_message('notice', '<b>' . $lang['change_mail'] . '</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
            }
        } else {
            output_message('alert', '<b>' . $lang['reg_checkemailex'] . '</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
        }
    } else {
        output_message('alert', '<b>' . $lang['bad_mail'] . '</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
    }
}

function spp_account_manage_change_password(array $ctx): void
{
    global $lang;
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');

    $managePdo = $ctx['managePdo'];
    $user = $ctx['user'];
    $MW = $ctx['MW'];
    $newpass = trim($_POST['new_pass']);
    $confirmPass = trim($_POST['confirm_new_pass'] ?? '');

    if (strlen($newpass) > 3) {
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

        if ((int)$MW->getConfig->generic->use_purepass_table) {
            $stmt = $managePdo->prepare("SELECT count(*) FROM account_pass WHERE id = ?");
            $stmt->execute([(int)$user['id']]);
            $count_occur = $stmt->fetchColumn();
            if ($count_occur) {
                $stmt = $managePdo->prepare("UPDATE account_pass SET password = ? WHERE id = ? LIMIT 1");
                $stmt->execute([$newpass, (int)$user['id']]);
            } else {
                $stmt = $managePdo->prepare("INSERT INTO account_pass SET id=?, username=?, password=?, email=?");
                $stmt->execute([(int)$user['id'], $user['username'], $newpass, $user['email']]);
            }
        }

        redirect('index.php?n=account&sub=manage&pwchange=1', 1);
        exit;
    }

    redirect('index.php?n=account&sub=manage&pwchange=short', 1);
    exit;
}

function spp_account_manage_change_profile(array $ctx): void
{
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');

    $managePdo = $ctx['managePdo'];
    $manageCharPdo = $ctx['manageCharPdo'];
    $user = $ctx['user'];
    $MW = $ctx['MW'];
    $currentRealmId = (int)$ctx['currentRealmId'];

    $backgroundPreferencesAvailable = spp_website_accounts_has_columns(['background_mode', 'background_image']);
    $backgroundModeOptions = spp_background_mode_options();
    $availableBackgroundImages = spp_background_image_catalog();
    $selectedSignatureGuid = (int)($_POST['signature_character_guid'] ?? 0);

    if (is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        if ($_FILES['avatar']['size'] <= (int)$MW->getConfig->generic->max_avatar_file) {
            $ext = strtolower(substr(strrchr($_FILES['avatar']['name'], '.'), 1));
            if (in_array($ext, array('gif', 'jpg', 'png'))) {
                if (@move_uploaded_file($_FILES['avatar']['tmp_name'], (string)$MW->getConfig->generic->avatar_path . $user['id'] . '.' . $ext)) {
                    list($width, $height,) = getimagesize((string)$MW->getConfig->generic->avatar_path . $user['id'] . '.' . $ext);
                    $max_avatar_size = explode('x', (string)$MW->getConfig->generic->max_avatar_size);
                    if ($width <= $max_avatar_size[0] || $height <= $max_avatar_size[1]) {
                        $stmt = $managePdo->prepare("UPDATE website_accounts SET avatar=? WHERE account_id=? LIMIT 1");
                        $stmt->execute([$user['id'] . '.' . $ext, (int)$user['id']]);
                    } else {
                        @unlink((string)$MW->getConfig->generic->avatar_path . $user['id'] . '.' . $ext);
                    }
                }
            }
        }
    } elseif ($_POST['deleteavatar'] == 1 && preg_match("/\d+\.\w+/i", $_POST['avatarfile'])) {
        if (@unlink((string)$MW->getConfig->generic->avatar_path . $_POST['avatarfile'])) {
            $stmt = $managePdo->prepare("UPDATE website_accounts SET avatar=NULL WHERE account_id=? LIMIT 1");
            $stmt->execute([(int)$user['id']]);
        }
    }

    $profileInput = isset($_POST['profile']) && is_array($_POST['profile']) ? $_POST['profile'] : array();
    $allowedProfileFields = spp_manage_allowed_profile_fields(
        $backgroundPreferencesAvailable,
        (int)($user['gmlevel'] ?? 0) >= 3
    );
    $profileInput = spp_filter_allowed_fields($profileInput, $allowedProfileFields);

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
}

function spp_account_manage_change_secret_questions(array $ctx): void
{
    global $lang;
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');

    $managePdo = $ctx['managePdo'];
    $user = $ctx['user'];

    if (check_for_symbols($_POST['secreta1']) == FALSE && check_for_symbols($_POST['secreta2']) == FALSE && $_POST['secretq1'] != '0' && $_POST['secretq2'] != '0' && isset($_POST['secreta1']) &&
        isset($_POST['secreta2']) && strlen($_POST['secreta1']) > 4 && strlen($_POST['secreta2']) > 4 && $_POST['secreta1'] != $_POST['secreta2'] && $_POST['secretq1'] != $_POST['secretq2']) {
        $stmt = $managePdo->prepare("UPDATE website_accounts SET secretq1=?,secretq2=?,secreta1=?,secreta2=? WHERE account_id=? LIMIT 1");
        $stmt->execute([strip_if_magic_quotes($_POST['secretq1']), strip_if_magic_quotes($_POST['secretq2']), strip_if_magic_quotes($_POST['secreta1']), strip_if_magic_quotes($_POST['secreta2']), (int)$user['id']]);
        output_message('notice', '<b>' . $lang['changed_secretq'] . '</b><meta http-equiv=refresh content="4;url=index.php?n=account&sub=manage">');
    } else {
        output_message('alert', '<b>' . $lang['fail_change_secretq'] . '</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=manage">');
    }
}

function spp_account_manage_reset_secret_questions(array $ctx): void
{
    global $lang;
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');

    $managePdo = $ctx['managePdo'];
    $user = $ctx['user'];
    if ($_POST['reset_secretq']) {
        $stmt = $managePdo->prepare("UPDATE website_accounts SET secretq1='0',secretq2='0',secreta1='0',secreta2='0' WHERE account_id=? LIMIT 1");
        $stmt->execute([(int)$user['id']]);
        output_message('notice', '<b>' . $lang['reset_succ_secretq'] . '</b><meta http-equiv=refresh content="4;url=index.php?n=account&sub=manage">');
    }
}

function spp_account_manage_change_gameplay(array $ctx): void
{
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');
    $managePdo = $ctx['managePdo'];
    $user = $ctx['user'];

    if ($_POST['switch_wow_type'] == 'wotlk') {
        $stmt = $managePdo->prepare("UPDATE `account` SET expansion='2' WHERE `id`=?");
        $stmt->execute([(int)$user['id']]);
        redirect('index.php?n=account&sub=manage',1);
    } elseif ($_POST['switch_wow_type'] == 'tbc') {
        $stmt = $managePdo->prepare("UPDATE `account` SET expansion='1' WHERE `id`=?");
        $stmt->execute([(int)$user['id']]);
        redirect('index.php?n=account&sub=manage',1);
    } elseif ($_POST['switch_wow_type'] == 'classic') {
        $stmt = $managePdo->prepare("UPDATE `account` SET expansion='0' WHERE `id`=?");
        $stmt->execute([(int)$user['id']]);
        redirect('index.php?n=account&sub=manage',1);
    }
}

function spp_account_manage_rename_character(array $ctx): void
{
    spp_require_csrf('account_manage', 'Security check failed. Please refresh the page and try again.', 'index.php?n=account&sub=manage');

    $managePdo = $ctx['managePdo'];
    $manageCharPdo = $ctx['manageCharPdo'];
    $user = $ctx['user'];

    $characterGuid = (int)($_POST['character_guid'] ?? 0);
    $newName = ucfirst(strtolower(trim((string)($_POST['new_character_name'] ?? ''))));

    if ($characterGuid <= 0 || $newName === '') {
        output_message('alert','<b>Please choose a character and enter a new name.</b><meta http-equiv=refresh content="2;url=index.php?n=account&sub=manage">');
        return;
    }

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
