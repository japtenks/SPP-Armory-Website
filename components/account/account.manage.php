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
