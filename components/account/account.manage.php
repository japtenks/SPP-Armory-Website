<?php
if(INCLUDED!==true)exit;
require_once __DIR__ . '/account.helpers.php';
require_once __DIR__ . '/account.manage.actions.php';
// ==================== //
$pathway_info[] = array('title'=>$lang['accediting'],'link'=>'');
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
        $renameCharacters = $stmtChars->fetchAll(PDO::FETCH_ASSOC);
        $accountCharacters = [];
        if (!empty($GLOBALS['characters']) && is_array($GLOBALS['characters'])) {
            foreach ($GLOBALS['characters'] as $character) {
                if ((int)($character['account'] ?? $user['id']) !== (int)$user['id']) {
                    continue;
                }
                $accountCharacters[] = array(
                    'guid' => (int)($character['guid'] ?? 0),
                    'name' => (string)($character['name'] ?? ''),
                    'level' => (int)($character['level'] ?? 0),
                    'online' => 0,
                    'realm_id' => (int)($character['realm_id'] ?? 0),
                    'realm_name' => (string)($character['realm_name'] ?? ('Realm ' . (int)($character['realm_id'] ?? 0))),
                );
            }
        }
        if (empty($accountCharacters)) {
            foreach ($renameCharacters as $character) {
                $accountCharacters[] = array(
                    'guid' => (int)($character['guid'] ?? 0),
                    'name' => (string)($character['name'] ?? ''),
                    'level' => (int)($character['level'] ?? 0),
                    'online' => (int)($character['online'] ?? 0),
                    'realm_id' => $currentRealmId,
                    'realm_name' => $manageRealmName,
                );
            }
        }
        $profile['character_signatures'] = array();
        $profile['signature_character_key'] = '';
        $profile['signature_character_name'] = '';
        $profile['selected_character_avatar_url'] = '';

        $availableCharacterKeys = array();
        foreach ($accountCharacters as $character) {
            $characterRealmId = (int)($character['realm_id'] ?? $currentRealmId);
            $characterKey = $characterRealmId . ':' . (int)$character['guid'];
            $availableCharacterKeys[$characterKey] = array(
                'guid' => (int)$character['guid'],
                'name' => (string)$character['name'],
                'realm_id' => $characterRealmId,
                'realm_name' => (string)($character['realm_name'] ?? ('Realm ' . $characterRealmId)),
            );
        }

        $requestedSignatureKey = trim((string)($_GET['sigchar'] ?? ''));
        if ($requestedSignatureKey === '' && !empty($profile['character_id'])) {
            $requestedSignatureRealmId = (int)($profile['character_realm_id'] ?? $currentRealmId);
            $requestedSignatureKey = $requestedSignatureRealmId . ':' . (int)$profile['character_id'];
        }
        if ($requestedSignatureKey === '' && !empty($accountCharacters[0]['guid'])) {
            $requestedSignatureKey = (int)($accountCharacters[0]['realm_id'] ?? $currentRealmId) . ':' . (int)$accountCharacters[0]['guid'];
        }
        if (!isset($availableCharacterKeys[$requestedSignatureKey])) {
            $requestedSignatureKey = '';
        }

        foreach ($accountCharacters as $character) {
            $characterGuid = (int)$character['guid'];
            $characterRealmId = (int)($character['realm_id'] ?? $currentRealmId);
            $characterKey = $characterRealmId . ':' . $characterGuid;
            $identityId = spp_ensure_char_identity($characterRealmId, $characterGuid, $user['id'], (string)$character['name']);
            $profile['character_signatures'][$characterKey] = array(
                'name' => (string)$character['name'],
                'realm_name' => (string)($character['realm_name'] ?? ('Realm ' . $characterRealmId)),
                'avatar_url' => spp_character_portrait_url($characterRealmId, $characterGuid, (int)$user['id']),
                'signature' => $identityId > 0 ? str_replace('<br />', '', spp_get_identity_signature($identityId)) : '',
            );
        }

        if ($requestedSignatureKey !== '') {
            $profile['signature_character_key'] = $requestedSignatureKey;
            $profile['signature_character_name'] = (string)($availableCharacterKeys[$requestedSignatureKey]['name'] ?? '');
            $profile['selected_character_avatar_url'] = (string)($profile['character_signatures'][$requestedSignatureKey]['avatar_url'] ?? '');
            $profile['signature'] = (string)($profile['character_signatures'][$requestedSignatureKey]['signature'] ?? $profile['signature']);
        } elseif (!empty($profile['character_signatures'])) {
            $firstSignatureKey = (string)spp_array_first_key($profile['character_signatures']);
            $profile['signature_character_key'] = $firstSignatureKey;
            $profile['selected_character_avatar_url'] = (string)($profile['character_signatures'][$firstSignatureKey]['avatar_url'] ?? '');
            $profile['signature'] = (string)($profile['character_signatures'][$firstSignatureKey]['signature'] ?? $profile['signature']);
        }
        $profile['avatar_fallback_url'] = '';
        if (empty($profile['avatar'])) {
            $profile['avatar_fallback_url'] = spp_account_avatar_fallback_url($manageCharPdo, $profile, $accountCharacters);
        }
        $manage_csrf_token = spp_csrf_token('account_manage');
    } else {
        spp_account_manage_handle_action((string)$_GET['action'], array(
            'managePdo' => $managePdo,
            'manageCharPdo' => $manageCharPdo,
            'auth' => $auth,
            'user' => $user,
            'MW' => $MW,
            'currentRealmId' => $currentRealmId,
        ));
    }
}
?>
