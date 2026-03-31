<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.bots.helpers.php');
require_once(__DIR__ . '/admin.bots.actions.php');
require_once(__DIR__ . '/admin.bots.read.php');

$pathway_info[] = array('title' => 'Bot Maintenance', 'link' => 'index.php?n=admin&sub=bots');

$botMaintenanceMasterPdo = spp_get_pdo('realmd', 1);
$botMaintenanceActionState = spp_admin_bots_handle_action($botMaintenanceMasterPdo);
$botMaintenanceView = spp_admin_bots_build_view($botMaintenanceMasterPdo, $realmDbMap ?? array(), $botMaintenanceActionState);
$botMaintenanceView['csrf_token'] = spp_csrf_token('admin_bots');
