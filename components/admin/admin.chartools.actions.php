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
    $fullPackageMessageHtml = '';
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
            if (strlen($newname) < 2 || strlen($newname) > 12) {
                $renameMessageHtml = '<div class="admin-tool-msg error">Character names must be between 2 and 12 letters.</div>';
            } elseif (!preg_match('/^[a-zA-Z]+$/', $newname)) {
                $renameMessageHtml = '<div class="admin-tool-msg error">Character names can only use letters.</div>';
            } else {
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
    }

    if ($selectedRealmId > 0 && isset($dbs[$selectedRealmId]) && isset($_POST['race_change'])) {
        $db1 = $dbs[$selectedRealmId];
        if (!spp_admin_chartools_validate_csrf()) {
            $raceMessageHtml = '<div class="admin-tool-msg error">Security check failed. Please refresh and try again.</div>';
        } elseif ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0 || (int)($_POST['newrace'] ?? 0) <= 0) {
            $raceMessageHtml = '<div class="admin-tool-msg error">Select a realm, account, character, and new race first.</div>';
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
        $selectedPackId = trim((string)($_POST['donation_pack_id'] ?? ''));
        if (!spp_admin_chartools_validate_csrf()) {
            $deliveryMessageHtml = '<div class="admin-tool-msg error">Security check failed. Please refresh and try again.</div>';
        } elseif ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0) {
            $deliveryMessageHtml = '<div class="admin-tool-msg error">Select a realm, account, and character first.</div>';
        } elseif ($selectedPackId === '' || $selectedPackId === '0') {
            $deliveryMessageHtml = '<div class="admin-tool-msg error">Select an item pack to send.</div>';
        } else {
            $selectedPack = null;
            foreach ($donationPackOptions as $donationPackOption) {
                if ((string)($donationPackOption['id'] ?? '') === $selectedPackId) {
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
                if (($selectedPack['kind'] ?? 'database') === 'gear_progression') {
                    $sendOk = $mangos->mail_item_list((array)($selectedPack['items'] ?? array()), $selectedCharacterGuid, (string)($selectedPack['description'] ?? 'Gear Pack')) === true;
                } else {
                    $sendOk = $mangos->mail_item_donation((int)$selectedPackId, $selectedCharacterGuid, false, true) === true;
                }
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

    if ($selectedRealmId > 0 && isset($dbs[$selectedRealmId]) && isset($_POST['apply_full_package'])) {
        $db1 = $dbs[$selectedRealmId];
        $selectedRoleId = trim((string)($_POST['full_package_role'] ?? ''));
        $selectedPhaseId = trim((string)($_POST['full_package_phase'] ?? ''));

        if (!spp_admin_chartools_validate_csrf()) {
            $fullPackageMessageHtml = '<div class="admin-tool-msg error">Security check failed. Please refresh and try again.</div>';
        } elseif ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0 || empty($selectedCharacterProfile)) {
            $fullPackageMessageHtml = '<div class="admin-tool-msg error">Select a realm, account, and character first.</div>';
        } elseif (strpos($selectedRoleId, 'spec:') !== 0 || strpos($selectedPhaseId, 'phase:') !== 0) {
            $fullPackageMessageHtml = '<div class="admin-tool-msg error">Select both a role package and a phase.</div>';
        } else {
            $classId = (int)($selectedCharacterProfile['class'] ?? 0);
            $specId = (int)substr($selectedRoleId, 5);
            $phase = (int)substr($selectedPhaseId, 6);
            $roles = chartools_build_full_package_roles($selectedRealmId, $selectedCharacterProfile, $selectedPhaseId);
            $roleOption = null;
            foreach ($roles as $role) {
                if ((string)($role['id'] ?? '') === $selectedRoleId) {
                    $roleOption = $role;
                    break;
                }
            }

            $phaseOptions = chartools_build_full_package_phases();
            $phaseValid = false;
            foreach ($phaseOptions as $phaseOption) {
                if ((string)($phaseOption['id'] ?? '') === $selectedPhaseId) {
                    $phaseValid = true;
                    break;
                }
            }

            if (empty($roleOption) || !$phaseValid) {
                $fullPackageMessageHtml = '<div class="admin-tool-msg error">That role or phase is no longer valid for the selected class.</div>';
            } else {
                $status = check_if_online_by_guid($selectedCharacterGuid, $selectedAccountId, $db1);
                if ($status === -1) {
                    $fullPackageMessageHtml = '<div class="admin-tool-msg error">Character could not be found on the selected account.</div>';
                } elseif ($status === 1) {
                    $fullPackageMessageHtml = '<div class="admin-tool-msg error">Full Package only works on offline characters.</div>';
                } else {
                    $gearItems = chartools_playerbot_gear_items_for_phase($selectedRealmId, $classId, $specId, $phase);
                    if (empty($gearItems)) {
                        $fullPackageMessageHtml = '<div class="admin-tool-msg error">No BIS gear list was found for that role and phase.</div>';
                    } else {
                        $charPdo = spp_get_pdo('chars', $selectedRealmId);
                        $levelCap = chartools_playerbot_level_cap($selectedRealmId);
                        $charPdo->prepare("UPDATE characters SET level = ?, xp = 0, at_login = at_login | ? WHERE guid = ? AND account = ?")
                            ->execute(array($levelCap, 6, $selectedCharacterGuid, $selectedAccountId));

                        $requiredProfessions = chartools_required_professions_for_items($selectedRealmId, $gearItems);

                        if (!empty($requiredProfessions)) {
                            chartools_apply_profession_skills($selectedCharacterGuid, $selectedRealmId, $requiredProfessions);
                        }

                        $previousRealm = $_GET['realm'] ?? null;
                        $_GET['realm'] = $selectedRealmId;
                        $mangos = new Mangos;
                        $subject = 'Full Package: ' . (string)$roleOption['label'] . ' ' . chartools_playerbot_phase_label($phase);
                        $sendOk = $mangos->mail_item_list($gearItems, $selectedCharacterGuid, $subject) === true;
                        unset($mangos);

                        if ($previousRealm === null) {
                            unset($_GET['realm']);
                        } else {
                            $_GET['realm'] = $previousRealm;
                        }

                        if ($sendOk) {
                            $professionLabel = '';
                            if (!empty($requiredProfessions)) {
                                $professionNames = array();
                                foreach ($requiredProfessions as $profession) {
                                    $professionNames[] = (string)$profession['name'];
                                }
                                $professionLabel = ' Required professions granted: ' . implode(', ', array_unique($professionNames)) . '.';
                            }

                            $fullPackageMessageHtml = '<div class="admin-tool-msg success">' . htmlspecialchars(
                                'Prepared ' . ($selectedCharacterName !== '' ? $selectedCharacterName : ('GUID ' . $selectedCharacterGuid)) .
                                ' for ' . (string)$roleOption['label'] . ' ' . chartools_playerbot_phase_label($phase) .
                                '. Set level to ' . $levelCap . ', queued spell/talent resets for next login, and mailed the BIS gear set.' .
                                $professionLabel
                            ) . '</div>';
                            $selectedCharacterProfile = chartools_fetch_character_profile($selectedCharacterGuid, $selectedAccountId, $db1);
                        } else {
                            $fullPackageMessageHtml = '<div class="admin-tool-msg error">The character was updated, but the BIS gear package could not be mailed.</div>';
                        }
                    }
                }
            }
        }
    }

    return array(
        'renameMessageHtml' => $renameMessageHtml,
        'raceMessageHtml' => $raceMessageHtml,
        'deliveryMessageHtml' => $deliveryMessageHtml,
        'fullPackageMessageHtml' => $fullPackageMessageHtml,
        'selectedCharacterProfile' => $selectedCharacterProfile,
    );
}
