<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.cleanup.helpers.php');
require_once(__DIR__ . '/admin.cleanup.actions.php');
require_once(__DIR__ . '/admin.cleanup.read.php');

$pathway_info[] = array('title' => 'Site Cleanup', 'link' => '');

$cleanupPdo = spp_get_pdo('realmd', 1);
$cleanupRealmRows = $cleanupPdo->query("SELECT `id`, `name` FROM `realmlist` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
$cleanupRealmOptions = array();
foreach ($cleanupRealmRows as $cleanupRealmRow) {
    $cleanupRealmOptions[(int)$cleanupRealmRow['id']] = (string)$cleanupRealmRow['name'];
}

$cleanupDefaultRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
$cleanupSelectedRealmId = isset($_REQUEST['cleanup_realm_id']) ? (int)$_REQUEST['cleanup_realm_id'] : $cleanupDefaultRealmId;
if ($cleanupSelectedRealmId <= 0 || !isset($cleanupRealmOptions[$cleanupSelectedRealmId])) {
    $cleanupSelectedRealmId = $cleanupDefaultRealmId;
}
if ($cleanupSelectedRealmId <= 0 && !empty($cleanupRealmOptions)) {
    $cleanupSelectedRealmId = (int)array_key_first($cleanupRealmOptions);
}

$cleanupCharsPdo = spp_get_pdo('chars', $cleanupSelectedRealmId);
$cleanupCsrfToken = spp_csrf_token('admin_cleanup');

spp_admin_cleanup_handle_action($cleanupPdo, $cleanupCharsPdo);

$cleanupPreview = spp_admin_cleanup_build_preview($cleanupPdo, $cleanupCharsPdo, $cleanupSelectedRealmId);
?>
