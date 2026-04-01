<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.identities.helpers.php');
require_once(__DIR__ . '/admin.identities.read.php');
require_once(__DIR__ . '/admin.identities.actions.php');

$pathway_info[] = array('title' => 'Identity & Data Health', 'link' => '');

$identityHealthPdo = spp_get_pdo('realmd', 1);
$identityHealthRequestedRealmId = isset($_REQUEST['identity_realm_id']) ? (int)$_REQUEST['identity_realm_id'] : (int)($_REQUEST['cleanup_realm_id'] ?? 0);
$identityHealthSiteRoot = $_SERVER['DOCUMENT_ROOT'];
$identityHealthPhpBin = spp_admin_identity_health_resolve_php_cli_binary();
$identityHealthIsWindowsHost = DIRECTORY_SEPARATOR === '\\';
$identityHealthBackfillState = spp_admin_identity_health_handle_backfill_action($identityHealthSiteRoot, $identityHealthPhpBin, $identityHealthIsWindowsHost, $realmDbMap);

$identityHealthView = spp_admin_identity_health_build_view($identityHealthPdo, $identityHealthRequestedRealmId, $identityHealthSiteRoot, $identityHealthBackfillState);
$identityHealthCsrfToken = spp_admin_identity_health_csrf_token('admin_identity_health');

spp_admin_identity_health_handle_repair_action($identityHealthPdo, (int)$identityHealthView['selected_realm_id']);
$identityHealthView = spp_admin_identity_health_build_view($identityHealthPdo, (int)$identityHealthView['selected_realm_id'], $identityHealthSiteRoot, $identityHealthBackfillState);
$identityHealthView['csrf_token'] = $identityHealthCsrfToken;
$identityHealthView['is_windows_host'] = $identityHealthIsWindowsHost;
