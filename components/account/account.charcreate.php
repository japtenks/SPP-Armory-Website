<?php
if (INCLUDED !== true) exit;

require_once(__DIR__ . '/account.charcreate.read.php');
require_once(__DIR__ . '/account.charcreate.actions.php');

$pathway_info[] = array('title' => $lang['charcreate'], 'link' => '');

$charcreateSuppressTemplate = spp_account_charcreate_handle_request($user, $MW->getConfig, $realmDbMap ?? array());
$charcreateState = spp_account_charcreate_build_state($user, $MW->getConfig, $realmDbMap ?? array());

$char_points = (int)$charcreateState['char_points'];
$your_points = (int)$charcreateState['your_points'];
$charcreateEnabled = !empty($charcreateState['enabled']);
$charcreateRealmAllowed = !empty($charcreateState['realm_allowed']);
$charcreateUsableRealms = $charcreateState['usable_realms'];
$charcreateSourceCharacters = $charcreateState['source_characters'];
$charcreateCsrfToken = spp_csrf_token('account_charcreate');
