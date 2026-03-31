<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.backup.helpers.php');
require_once(__DIR__ . '/admin.backup.read.php');
require_once(__DIR__ . '/admin.backup.actions.php');

$pathway_info[] = array('title' => 'Backup', 'link' => 'index.php?n=admin&sub=backup');

$backupView = spp_admin_backup_build_view($realmDbMap);
$backupActionState = spp_admin_backup_handle_action($backupView);
$admin_backup_csrf_token = spp_csrf_token('admin_backup');
