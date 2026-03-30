<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.realms.helpers.php');
require_once(__DIR__ . '/admin.realms.actions.php');
require_once(__DIR__ . '/admin.realms.read.php');

$realm_type_def = spp_admin_realms_type_definitions();
$realm_timezone_def = spp_admin_realms_timezone_definitions();
$realmsPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

spp_admin_realms_handle_action($realmsPdo);

$realmsView = spp_admin_realms_build_view($realmsPdo, $lang);
extract($realmsView, EXTR_OVERWRITE);
$admin_realms_csrf_token = spp_csrf_token('admin_realms');
?>
