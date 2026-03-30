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
$renameMessageHtml = $chartoolsActionState['renameMessageHtml'];
$raceMessageHtml = $chartoolsActionState['raceMessageHtml'];
$deliveryMessageHtml = $chartoolsActionState['deliveryMessageHtml'];
