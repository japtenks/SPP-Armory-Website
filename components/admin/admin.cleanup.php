<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.cleanup.helpers.php');
require_once(__DIR__ . '/admin.cleanup.actions.php');
require_once(__DIR__ . '/admin.cleanup.read.php');

$pathway_info[] = array('title' => 'Site Cleanup', 'link' => '');

$cleanupPdo = spp_get_pdo('realmd', 1);
$cleanupActiveRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
$cleanupCharsPdo = spp_get_pdo('chars', $cleanupActiveRealmId);

spp_admin_cleanup_handle_action();

$cleanupPreview = spp_admin_cleanup_build_preview($cleanupPdo, $cleanupCharsPdo, $cleanupActiveRealmId);
?>
