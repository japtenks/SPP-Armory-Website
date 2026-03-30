<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_chartools_validate_csrf(): bool
{
    $submittedToken = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = (string)($_SESSION['spp_csrf_tokens']['admin_chartools'] ?? '');
    return $submittedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
}

function spp_admin_chartools_handle_actions(array $state, array $dbs, array $messages): array
{
    $renameMessageHtml = '';
    $raceMessageHtml = '';
    $deliveryMessageHtml = '';
    $selectedCharacterProfile = $state['selectedCharacterProfile'] ?? null;

    $selectedRealmId = (int)($state['selectedRealmId'] ?? 0);
    $selectedAccountId = (int)($state['selectedAccountId'] ?? 0);
    $selectedCharacterGuid = (int)($state['selectedCharacterGuid'] ?? 0);
    $selectedCharacterName = (string)($state['selectedCharacterName'] ?? '');
    $donationPackOptions = $state['donationPackOptions'] ?? array();

    if ($selectedRealmId > 0 && isset($dbs[$selectedRealmId]) && isset($_POST['rename'])) {
        $db1 = $dbs[$selectedRealmId];
        if (!spp_admin_chartools_validate_csrf()) {
            $renameMessageHtml = '<div class="admin-tool-msg error">Security check failed. Please refresh and try again.</div>';
        } elseif ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0 || trim((string)($_POST['newname'] ?? '')) === '') {
            $renameMessageHtml = '<div class="admin-tool-msg error">' . htmlspecialchars($messages['empty_field']) . '</div>';
        } else {
            $newname = ucfirst(strtolower(trim((string)$_POST['newname'])));
            $name = $selectedCharacterName;
            $status = check_if_online_by_guid($selectedCharacterGuid, $selectedAccountId, $db1);
            $newname_exist = check_if_name_exist($newname, $db1);
            if ($status == -1) {
                $renameMessageHtml = '<div class="admin-tool-msg error">' . htmlspecialchars($messages['character_1'] . ($name ?: 'Unknown') . $messages['doesntexist']) . '</div>';
            } elseif ($newname_exist == 1) {
                $renameMessageHtml = '<div class="admin-tool-msg error">' . htmlspecialchars($messages['alreadyexist'] . $newname . '!') . '</div>';
            } elseif ($status == 1) {
                $kickError = '';
                force_character_offline($selectedRealmId, $name, $kickError);
                for ($i = 0; $i < 5; $i++) {
                    usleep(500000);
                    $status = check_if_online_by_guid($selectedCharacterGuid, $selectedAccountId, $db1);
                    if ($status !== 1) {
                        break;
                    }
                }

                if ($status == 1) {
                    $message = $messages['character_1'] . $name . $messages['isonline'];
                    if ($kickError !== '') {
                        $message .= ' SOAP: ' . $kickError;
                    }
                    $renameMessageHtml = '<div class="admin-tool-msg error">' . htmlspecialchars($message) . '</div>';
                } else {
                    change_name_by_guid($selectedCharacterGuid, $selectedAccountId, $newname, $db1);
                    $renameMessageHtml = '<div class="admin-tool-msg success">' . htmlspecialchars($messages['character_1'] . $name . $messages['renamesuccess'] . $newname . '!') . '</div>';
                }
            } else {
                change_name_by_guid($selectedCharacterGuid, $selectedAccountId, $newname, $db1);
                $renameMessageHtml = '<div class="admin-tool-msg success">' . htmlspecialchars($messages['character_1'] . $name . $messages['renamesuccess'] . $newname . '!') . '</div>';
            }
        }
    }

    if ($selectedRealmId > 0 && isset($dbs[$selectedRealmId]) && isset($_POST['race_change'])) {
        $db1 = $dbs[$selectedRealmId];
        if (!spp_admin_chartools_validate_csrf()) {
            $raceMessageHtml = '<div class="admin-tool-msg error">Security check failed. Please refresh and try again.</div>';
        } elseif ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0) {
            $raceMessageHtml = '<div class="admin-tool-msg error">Select a realm, account, and character first.</div>';
        } else {
            $raceChangeMessage = '';
            $changeOk = chartools_change_race_by_guid(
                $selectedCharacterGuid,
                $selectedAccountId,
                (int)($_POST['newrace'] ?? 0),
                $db1,
                $raceChangeMessage
            );

            if ($changeOk) {
                $raceMessageHtml = '<div class="admin-tool-msg success">' . htmlspecialchars($raceChangeMessage) . '</div>';
                $selectedCharacterProfile = chartools_fetch_character_profile($selectedCharacterGuid, $selectedAccountId, $db1);
            } else {
                $raceMessageHtml = '<div class="admin-tool-msg error">' . htmlspecialchars($raceChangeMessage) . '</div>';
            }
        }
    }

    if ($selectedRealmId > 0 && isset($dbs[$selectedRealmId]) && isset($_POST['send_pack'])) {
        $selectedPackId = (int)($_POST['donation_pack_id'] ?? 0);
        if (!spp_admin_chartools_validate_csrf()) {
            $deliveryMessageHtml = '<div class="admin-tool-msg error">Security check failed. Please refresh and try again.</div>';
        } elseif ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0) {
            $deliveryMessageHtml = '<div class="admin-tool-msg error">Select a realm, account, and character first.</div>';
        } elseif ($selectedPackId <= 0) {
            $deliveryMessageHtml = '<div class="admin-tool-msg error">Select an item pack to send.</div>';
        } else {
            $selectedPack = null;
            foreach ($donationPackOptions as $donationPackOption) {
                if ((int)($donationPackOption['id'] ?? 0) === $selectedPackId) {
                    $selectedPack = $donationPackOption;
                    break;
                }
            }

            if (empty($selectedPack)) {
                $deliveryMessageHtml = '<div class="admin-tool-msg error">That item pack could not be found.</div>';
            } else {
                $previousRealm = $_GET['realm'] ?? null;
                $_GET['realm'] = $selectedRealmId;
                $mangos = new Mangos;
                $sendOk = $mangos->mail_item_donation($selectedPackId, $selectedCharacterGuid, false, true) === true;
                unset($mangos);

                if ($previousRealm === null) {
                    unset($_GET['realm']);
                } else {
                    $_GET['realm'] = $previousRealm;
                }

                if ($sendOk) {
                    $packLabel = trim((string)($selectedPack['description'] ?? 'Pack #' . $selectedPackId));
                    $characterLabel = $selectedCharacterName !== '' ? $selectedCharacterName : ('GUID ' . $selectedCharacterGuid);
                    $deliveryMessageHtml = '<div class="admin-tool-msg success">' . htmlspecialchars('Sent "' . $packLabel . '" to ' . $characterLabel . '.') . '</div>';
                } else {
                    $deliveryMessageHtml = '<div class="admin-tool-msg error">The item pack could not be mailed. Check the donation template and world/character tables for that realm.</div>';
                }
            }
        }
    }

    return array(
        'renameMessageHtml' => $renameMessageHtml,
        'raceMessageHtml' => $raceMessageHtml,
        'deliveryMessageHtml' => $deliveryMessageHtml,
        'selectedCharacterProfile' => $selectedCharacterProfile,
    );
}
