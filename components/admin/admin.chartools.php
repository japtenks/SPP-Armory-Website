<?php
if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>'Character Tools', 'link'=>'index.php?n=admin&sub=chartools');
// ==================== //
include "chartools/charconfig.php";
include "chartools/add.php";
include "chartools/functionstransfer.php";
include "chartools/functionsrename.php";
include "chartools/functionsrace.php";
include "chartools/functionsgear.php";
include "chartools/tabs.php";
require_once(__DIR__ . '/admin.chartools.read.php');
require_once(__DIR__ . '/admin.chartools.actions.php');

$adminChartoolsCsrfToken = spp_csrf_token('admin_chartools');

$chartoolsMessages = array(
    'empty_field' => $empty_field,
    'character_1' => $character_1,
    'doesntexist' => $doesntexist,
    'alreadyexist' => $alreadyexist,
    'isonline' => $isonline,
    'renamesuccess' => $renamesuccess,
);

$chartoolsState = spp_admin_chartools_build_state($DBS, $charcfgPdo);
$chartoolsActionState = spp_admin_chartools_handle_actions($chartoolsState, $DBS, $chartoolsMessages);

$selectedRealmId = $chartoolsState['selectedRealmId'];
$selectedAccountId = $chartoolsState['selectedAccountId'];
$selectedCharacterGuid = $chartoolsState['selectedCharacterGuid'];
$accountOptions = $chartoolsState['accountOptions'];
$characterOptions = $chartoolsState['characterOptions'];
$donationPackOptions = $chartoolsState['donationPackOptions'];
$selectedCharacterName = $chartoolsState['selectedCharacterName'];
$selectedCharacterProfile = $chartoolsActionState['selectedCharacterProfile'];
$availableRaceOptions = !empty($selectedCharacterProfile)
    ? chartools_available_race_options((int)$selectedCharacterProfile['class'], (int)$selectedCharacterProfile['race'])
    : array();
$availableFullPackagePhases = chartools_build_full_package_phases();
$selectedFullPackagePhaseId = trim((string)($_POST['full_package_phase'] ?? ''));
if ($selectedFullPackagePhaseId === '' && !empty($availableFullPackagePhases[0]['id'])) {
    $selectedFullPackagePhaseId = (string)$availableFullPackagePhases[0]['id'];
}
$selectedFullPackageRoleId = trim((string)($_POST['full_package_role'] ?? ''));
$availableFullPackageRoles = chartools_build_full_package_roles($selectedRealmId, $selectedCharacterProfile, $selectedFullPackagePhaseId);
if ($selectedFullPackageRoleId === '' && !empty($availableFullPackageRoles[0]['id'])) {
    $selectedFullPackageRoleId = (string)$availableFullPackageRoles[0]['id'];
}
if ($selectedFullPackageRoleId !== '') {
    $roleStillAvailable = false;
    foreach ($availableFullPackageRoles as $roleOption) {
        if ((string)$roleOption['id'] === $selectedFullPackageRoleId) {
            $roleStillAvailable = true;
            break;
        }
    }
    if (!$roleStillAvailable) {
        $selectedFullPackageRoleId = !empty($availableFullPackageRoles[0]['id']) ? (string)$availableFullPackageRoles[0]['id'] : '';
    }
}
$donationPackOptions = chartools_build_delivery_options($donationPackOptions, $selectedRealmId, $selectedCharacterProfile);
$renameMessageHtml = $chartoolsActionState['renameMessageHtml'];
$raceMessageHtml = $chartoolsActionState['raceMessageHtml'];
$deliveryMessageHtml = $chartoolsActionState['deliveryMessageHtml'];
$fullPackageMessageHtml = $chartoolsActionState['fullPackageMessageHtml'];
