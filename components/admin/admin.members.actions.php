<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_members_handle_action(array $context)
{
    $membersPdo = $context['members_pdo'];
    $membersCharsPdo = $context['members_chars_pdo'];
    $oldInactiveTime = (int)$context['old_inactive_time'];
    $deleteInactiveAccountsEnabled = !empty($context['delete_inactive_accounts_enabled']);
    $deleteInactiveCharactersEnabled = !empty($context['delete_inactive_characters_enabled']);
    $realmDbMap = $context['realm_db_map'];
    $user = $context['user'];
    $mw = $context['mw'];
    $lang = $context['lang'];

    if (!empty($_POST['search_member'])) {
        spp_require_csrf('admin_members');
        $sString = trim((string)$_POST['search_member']);
        $stmt = $membersPdo->prepare("SELECT id FROM account WHERE username=?");
        $stmt->execute([$sString]);
        $accountId = $stmt->fetchColumn();
        if ($accountId !== false && $accountId !== null && $accountId !== '') {
            redirect('index.php?n=admin&sub=members&id=' . (int)$accountId, 0);
        }
        output_message('alert', 'No results');
        return;
    }

    $action = (string)($_GET['action'] ?? '');
    $accountId = (int)($_GET['id'] ?? 0);

    if ($accountId > 0) {
        if ($action === '' || $action === '0') {
            return;
        }

        if ($action === 'changepass') {
            spp_require_csrf('admin_members');
            $newpass = trim((string)($_POST['new_pass'] ?? ''));
            $confirmPass = trim((string)($_POST['confirm_new_pass'] ?? ''));
            if (strlen($newpass) > 3) {
                if ($confirmPass === '' || $newpass !== $confirmPass) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=mismatch', 1);
                    exit;
                }

                $stmt = $membersPdo->prepare("SELECT username FROM account WHERE id=?");
                $stmt->execute([$accountId]);
                $username = $stmt->fetchColumn();
                if (!$username) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=missing', 1);
                    exit;
                }

                $stmt = $membersPdo->prepare("UPDATE account SET sessionkey = NULL WHERE id=?");
                $stmt->execute([$accountId]);
                list($salt, $verifier) = getRegistrationData((string)$username, $newpass);
                $stmt = $membersPdo->prepare("UPDATE account SET s=?, v=? WHERE id=?");
                $stmt->execute([$salt, $verifier, $accountId]);

                $stmt = $membersPdo->prepare("SELECT s, v FROM account WHERE id=? LIMIT 1");
                $stmt->execute([$accountId]);
                $updatedAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                if (empty($updatedAccount['s']) || empty($updatedAccount['v'])) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=failed', 1);
                    exit;
                }

                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&pwreset=1', 1);
                exit;
            }

            output_message('alert', '<b>' . $lang['change_pass_short'] . '</b><meta http-equiv=refresh content="2;url=index.php?n=admin&sub=members&id=' . $accountId . '">');
            return;
        }

        if ($action === 'ban') {
            spp_require_csrf('admin_members');
            $stmt = $membersPdo->prepare("INSERT into account_banned (id, bandate, unbandate, bannedby, banreason, active) values (?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()-10, 'WEBSERVER', 'WEBSERVER', 1)");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("SELECT last_ip FROM account WHERE id=?");
            $stmt->execute([$accountId]);
            $lastIp = $stmt->fetchColumn();
            $stmt = $membersPdo->prepare("INSERT into ip_banned (ip, bandate, unbandate, bannedby, banreason) values (?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()-10, 'WEBSERVER', 'WEBSERVER')");
            $stmt->execute([$lastIp]);
            $stmt = $membersPdo->prepare("UPDATE website_accounts SET g_id=5 WHERE account_id=?");
            $stmt->execute([$accountId]);
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'unban') {
            spp_require_csrf('admin_members');
            $stmt = $membersPdo->prepare("UPDATE account_banned SET active=0 WHERE id=?");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("SELECT last_ip FROM account WHERE id=?");
            $stmt->execute([$accountId]);
            $lastIp = $stmt->fetchColumn();
            $stmt = $membersPdo->prepare("DELETE FROM ip_banned WHERE ip=?");
            $stmt->execute([$lastIp]);
            $stmt = $membersPdo->prepare("UPDATE website_accounts SET g_id=2 WHERE account_id=?");
            $stmt->execute([$accountId]);
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'change') {
            spp_require_csrf('admin_members');
            $profile = isset($_POST['profile']) && is_array($_POST['profile']) ? $_POST['profile'] : array();
            $allowedFields = spp_admin_members_account_fields((int)($user['gmlevel'] ?? 0) === 3);
            $profile = spp_filter_allowed_fields($profile, $allowedFields);
            if (!empty($profile)) {
                $setClause = implode(',', array_map(function ($k) {
                    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
                }, array_keys($profile)));
                $values = array_values($profile);
                $values[] = $accountId;
                $stmt = $membersPdo->prepare("UPDATE account SET $setClause WHERE id=? LIMIT 1");
                $stmt->execute($values);
            }
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'change2') {
            spp_require_csrf('admin_members');
            spp_ensure_website_account_row($membersPdo, $accountId);

            if (is_uploaded_file($_FILES['avatar']['tmp_name'] ?? '')) {
                if ((int)($_FILES['avatar']['size'] ?? 0) <= (int)$mw->getConfig->generic->max_avatar_file) {
                    $tmpFilenameAdd = time();
                    $uploadedName = basename((string)($_FILES['avatar']['name'] ?? ''));
                    $tempAvatarPath = (string)$mw->getConfig->generic->avatar_path . $tmpFilenameAdd . $uploadedName;
                    if (@move_uploaded_file($_FILES['avatar']['tmp_name'], $tempAvatarPath)) {
                        list($width, $height) = getimagesize($tempAvatarPath);
                        $pathParts = pathinfo($tempAvatarPath);
                        $maxAvatarSize = explode('x', (string)$mw->getConfig->generic->max_avatar_size);
                        if ($width <= $maxAvatarSize[0] || $height <= $maxAvatarSize[1]) {
                            if (@rename($tempAvatarPath, (string)$mw->getConfig->generic->avatar_path . $accountId . '.' . $pathParts['extension'])) {
                                $uploadedAvatarName = $accountId . '.' . $pathParts['extension'];
                            } else {
                                $uploadedAvatarName = $tmpFilenameAdd . $uploadedName;
                            }
                            if (!empty($uploadedAvatarName)) {
                                $stmt = $membersPdo->prepare("UPDATE website_accounts SET avatar=? WHERE account_id=? LIMIT 1");
                                $stmt->execute([$uploadedAvatarName, $accountId]);
                            }
                        } else {
                            @unlink($tempAvatarPath);
                        }
                    }
                }
            } elseif ((int)($_POST['deleteavatar'] ?? 0) === 1) {
                $avatarFile = basename((string)($_POST['avatarfile'] ?? ''));
                if ($avatarFile !== '' && @unlink((string)$mw->getConfig->generic->avatar_path . $avatarFile)) {
                    $stmt = $membersPdo->prepare("UPDATE website_accounts SET avatar=NULL WHERE account_id=? LIMIT 1");
                    $stmt->execute([$accountId]);
                }
            }

            $profile = isset($_POST['profile']) && is_array($_POST['profile']) ? $_POST['profile'] : array();
            $allowedWebsiteFields = spp_admin_members_website_fields((int)$mw->getConfig->generic->change_template === 1);
            $profile = spp_filter_allowed_fields($profile, $allowedWebsiteFields);
            if (isset($profile['signature'])) {
                $profile['signature'] = htmlspecialchars((string)$profile['signature']);
            }
            if (!empty($profile)) {
                $setClause = implode(',', array_map(function ($k) {
                    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
                }, array_keys($profile)));
                $values = array_values($profile);
                $values[] = $accountId;
                $stmt = $membersPdo->prepare("UPDATE website_accounts SET $setClause WHERE account_id=? LIMIT 1");
                $stmt->execute($values);
            }
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'setbotsignatures') {
            spp_require_csrf('admin_members');
            $stmtBot = $membersPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
            $stmtBot->execute([$accountId]);
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
                    $stmtChar->execute([$characterGuid, $accountId]);
                    $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
                    if (!$charRow) {
                        continue;
                    }

                    $cleanSignature = htmlspecialchars((string)$signature);
                    $identityId = spp_ensure_char_identity($activeRealmId, (int)$charRow['guid'], $accountId, (string)$charRow['name']);
                    if ($identityId > 0) {
                        spp_update_identity_signature($identityId, $cleanSignature);
                    }
                }
            }
            redirect('index.php?n=admin&sub=members&id=' . $accountId, 1);
            exit;
        }

        if ($action === 'transferchar' || $action === 'transferbotchar') {
            spp_require_csrf('admin_members');
            $stmtBot = $membersPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
            $stmtBot->execute([$accountId]);
            $sourceUsername = (string)$stmtBot->fetchColumn();

            $characterGuid = (int)($_POST['transfer_character_guid'] ?? 0);
            $targetAccountId = (int)($_POST['target_account_id'] ?? 0);
            if ($characterGuid <= 0) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=missing_character', 1);
                exit;
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
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=missing_target', 1);
                exit;
            }
            if ($targetAccountId === $accountId) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=same_target', 1);
                exit;
            }

            $stmtChar = $membersCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmtChar->execute([$characterGuid, $accountId]);
            $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
            if (!$charRow) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=missing_character', 1);
                exit;
            }

            $activeRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
            $sourceAccountOnline = spp_admin_account_is_online($membersPdo, $accountId);
            $targetAccountOnline = spp_admin_account_is_online($membersPdo, $targetAccountId);
            $characterOnline = spp_admin_character_is_online($membersCharsPdo, $characterGuid, $accountId);

            if ($sourceAccountOnline || $targetAccountOnline || $characterOnline) {
                if ($sourceAccountOnline || $characterOnline) {
                    $sourceOnlineCharacters = spp_admin_online_characters_for_account($membersCharsPdo, $accountId);
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
                    $sourceAccountOnline = spp_admin_account_is_online($membersPdo, $accountId);
                    $targetAccountOnline = spp_admin_account_is_online($membersPdo, $targetAccountId);
                    $characterOnline = spp_admin_character_is_online($membersCharsPdo, $characterGuid, $accountId);
                    if (!$sourceAccountOnline && !$targetAccountOnline && !$characterOnline) {
                        break;
                    }
                }

                if ($characterOnline) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=char_online', 1);
                    exit;
                }
                if ($sourceAccountOnline) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=source_online', 1);
                    exit;
                }
                if ($targetAccountOnline) {
                    redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=target_online', 1);
                    exit;
                }
            }

            try {
                $membersCharsPdo->beginTransaction();
                $membersPdo->beginTransaction();

                $stmtMove = $membersCharsPdo->prepare("UPDATE characters SET account=? WHERE guid=? AND account=? LIMIT 1");
                $stmtMove->execute([$targetAccountId, $characterGuid, $accountId]);
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
                $stmtWebsiteSource->execute([$characterGuid, $characterGuid, $accountId]);

                $stmtWebsiteTarget = $membersPdo->prepare("SELECT character_id FROM website_accounts WHERE account_id=? LIMIT 1");
                $stmtWebsiteTarget->execute([$targetAccountId]);
                $targetSelectedCharacter = (int)$stmtWebsiteTarget->fetchColumn();
                if ($targetSelectedCharacter <= 0) {
                    $stmtWebsiteAssign = $membersPdo->prepare("UPDATE website_accounts SET character_id=?, character_name=? WHERE account_id=?");
                    $stmtWebsiteAssign->execute([$characterGuid, (string)$charRow['name'], $targetAccountId]);
                }

                $membersCharsPdo->commit();
                $membersPdo->commit();
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=success', 1);
                exit;
            } catch (Throwable $e) {
                if ($membersCharsPdo->inTransaction()) {
                    $membersCharsPdo->rollBack();
                }
                if ($membersPdo->inTransaction()) {
                    $membersPdo->rollBack();
                }
                error_log('[admin.members] Bot character transfer failed: ' . $e->getMessage());
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&xfer=failed', 1);
                exit;
            }
        }

        if ($action === 'deletechar') {
            spp_require_csrf('admin_members');
            $characterGuid = (int)($_POST['delete_character_guid'] ?? 0);
            if ($characterGuid <= 0) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&chardelete=missing', 1);
                exit;
            }

            $stmtChar = $membersCharsPdo->prepare("SELECT guid, name FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmtChar->execute([$characterGuid, $accountId]);
            $charRow = $stmtChar->fetch(PDO::FETCH_ASSOC);
            if (!$charRow) {
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&chardelete=missing', 1);
                exit;
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
                $stmtWebsiteSource->execute([$characterGuid, $characterGuid, $accountId]);

                $membersCharsPdo->commit();
                $membersPdo->commit();
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&chardelete=success', 1);
                exit;
            } catch (Throwable $e) {
                if ($membersCharsPdo->inTransaction()) {
                    $membersCharsPdo->rollBack();
                }
                if ($membersPdo->inTransaction()) {
                    $membersPdo->rollBack();
                }
                error_log('[admin.members] Character delete failed: ' . $e->getMessage());
                redirect('index.php?n=admin&sub=members&id=' . $accountId . '&chardelete=failed', 1);
                exit;
            }
        }

        if ($action === 'dodeleteacc') {
            spp_require_csrf('admin_members');
            $deleteRealmId = spp_resolve_realm_id($realmDbMap);
            if (function_exists('spp_deactivate_account_identities')) {
                spp_deactivate_account_identities($deleteRealmId, $accountId);
            }
            $stmt = $membersPdo->prepare("DELETE FROM account WHERE id=? LIMIT 1");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("DELETE FROM website_accounts WHERE account_id=? LIMIT 1");
            $stmt->execute([$accountId]);
            $stmt = $membersPdo->prepare("DELETE FROM pms WHERE owner_id=? LIMIT 1");
            $stmt->execute([$accountId]);
            redirect('index.php?n=admin&sub=members', 1);
            exit;
        }

        return;
    }

    if ($action === 'deleteinactive') {
        spp_require_csrf('admin_members');
        if (!$deleteInactiveAccountsEnabled) {
            output_message('alert', 'Inactive account deletion is currently disabled until this maintenance workflow is reviewed.');
            return;
        }

        $curTimestamp = date('YmdHis', time() - $oldInactiveTime);
        $stmt = $membersPdo->prepare("
            SELECT account_id FROM website_accounts
            JOIN account ON account.id=website_accounts.account_id
            WHERE activation_code IS NOT NULL AND joindate < ?
        ");
        $stmt->execute([$curTimestamp]);
        $accountIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!empty($accountIds)) {
            $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
            $accountInts = array_map('intval', $accountIds);
            $stmt = $membersPdo->prepare("DELETE FROM account WHERE id IN($placeholders)");
            $stmt->execute($accountInts);
            $stmt = $membersPdo->prepare("DELETE FROM website_accounts WHERE account_id IN($placeholders)");
            $stmt->execute($accountInts);
        }
        redirect('index.php?n=admin&sub=members', 1);
        exit;
    }

    if ($action === 'deleteinactive_characters') {
        spp_require_csrf('admin_members');
        if (!$deleteInactiveCharactersEnabled) {
            output_message('alert', 'Inactive character deletion is currently disabled until this maintenance workflow is reviewed.');
            return;
        }

        $deleteInDays = 90;
        $curTimestamp = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - $deleteInDays, date('Y')));
        $stmt = $membersPdo->prepare("SELECT id FROM account LEFT JOIN website_accounts ON account.id=website_accounts.account_id WHERE ? >= last_login AND website_accounts.vip=0");
        $stmt->execute([$curTimestamp]);
        $accountIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $characterGuids = array();
        if (count($accountIds)) {
            $acctPlaceholders = implode(',', array_fill(0, count($accountIds), '?'));
            $acctInts = array_map('intval', $accountIds);
            $stmt = $membersCharsPdo->prepare("SELECT guid FROM `characters` WHERE account IN ($acctPlaceholders)");
            $stmt->execute($acctInts);
            $characterGuids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        if (count($characterGuids)) {
            $guidPlaceholders = implode(',', array_fill(0, count($characterGuids), '?'));
            $guidInts = array_map('intval', $characterGuids);
            foreach (spp_admin_character_delete_tables() as $table => $col) {
                $stmt = $membersCharsPdo->prepare("DELETE FROM `$table` WHERE `$col` IN ($guidPlaceholders)");
                $stmt->execute($guidInts);
            }
        }
        output_message('alert', 'Accounts checked: ' . count($accountIds) . '. Characters deleted: ' . count($characterGuids) . '.');
    }
}
