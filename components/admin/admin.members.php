<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.members.helpers.php');
require_once(__DIR__ . '/admin.members.actions.php');
require_once(__DIR__ . '/admin.members.read.php');

$oldInactiveTime = 3600 * 24 * 7;
$deleteInactiveAccountsEnabled = false;
$deleteInactiveCharactersEnabled = false;
$membersPdo = spp_get_pdo('realmd', 1);
$membersCharsPdo = spp_get_pdo('chars', spp_resolve_realm_id($realmDbMap));

spp_admin_members_handle_action(array(
    'members_pdo' => $membersPdo,
    'members_chars_pdo' => $membersCharsPdo,
    'old_inactive_time' => $oldInactiveTime,
    'delete_inactive_accounts_enabled' => $deleteInactiveAccountsEnabled,
    'delete_inactive_characters_enabled' => $deleteInactiveCharactersEnabled,
    'realm_db_map' => $realmDbMap,
    'user' => $user,
    'mw' => $MW,
    'lang' => $lang,
));

$accountId = (int)($_GET['id'] ?? 0);
$selectedToolRealmId = (int)($_POST['character_realm_id'] ?? ($_GET['character_realm_id'] ?? 0));
if ($accountId > 0) {
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

    $detailView = spp_admin_members_build_detail_view($membersPdo, $membersCharsPdo, $auth, $lang, $com_links, $realmDbMap, $accountId, $selectedToolRealmId);
    extract($detailView, EXTR_OVERWRITE);
    $admin_members_csrf_token = spp_csrf_token('admin_members');
} else {
    $listView = spp_admin_members_build_list_view($membersPdo, $MW, (int)$p, $lang);
    extract($listView, EXTR_OVERWRITE);
    if (isset($_GET['botexp']) && $_GET['botexp'] === 'normalized') {
        $normalizedCount = (int)($_GET['count'] ?? 0);
        $normalizedTarget = spp_admin_members_expansion_label((int)($_GET['to'] ?? 0));
        output_message('notice', '<b>Normalized ' . $normalizedCount . ' bot account(s) to ' . htmlspecialchars($normalizedTarget) . '.</b>');
    }
}
?>
