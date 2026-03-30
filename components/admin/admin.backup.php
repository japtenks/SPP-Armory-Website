<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.backup.helpers.php');
require_once(__DIR__ . '/admin.backup.actions.php');
require_once(__DIR__ . '/admin.backup.read.php');

$pathway_info[] = array('title' => 'Backup', 'link' => 'index.php?n=admin&sub=backup');

$backupCharsPdo = spp_get_pdo('chars', spp_resolve_realm_id($realmDbMap));
$backupCopyAccounts = spp_admin_backup_character_copy_accounts($MW);
$backupActionState = spp_admin_backup_handle_action($backupCharsPdo, $backupCopyAccounts);
$backupPreview = spp_admin_backup_build_preview($backupCharsPdo, $backupCopyAccounts);
$admin_backup_csrf_token = spp_csrf_token('admin_backup');
