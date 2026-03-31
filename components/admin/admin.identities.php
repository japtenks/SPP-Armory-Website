<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.identities.read.php');
require_once(__DIR__ . '/admin.identities.helpers.php');
require_once(__DIR__ . '/admin.identities.actions.php');

$pathway_info[] = ['title' => 'Forum Identity Coverage', 'link' => ''];
$siteRoot = $_SERVER['DOCUMENT_ROOT'];
$phpBin = spp_admin_identities_resolve_php_cli_binary();
$isWindowsHost = DIRECTORY_SEPARATOR === '\\';
$identityActionState = spp_admin_identities_handle_action($siteRoot, $phpBin, $isWindowsHost, $realmDbMap);
$identityCoverage = spp_admin_identities_build_view($realmDbMap);
$identityOutput = $identityActionState['identityOutput'] ?? '';
$identityError = $identityActionState['identityError'] ?? '';
$identityNotice = $identityActionState['identityNotice'] ?? '';
$identityCommand = $identityActionState['identityCommand'] ?? '';
$admin_identities_csrf_token = spp_csrf_token('admin_identities');
