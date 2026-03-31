<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_cleanup_handle_action(PDO $cleanupPdo, PDO $cleanupCharsPdo)
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === '' || $action === '0') {
        return;
    }

    if (!spp_csrf_check('admin_cleanup', $_POST['csrf_token'] ?? '')) {
        output_message('alert', 'Cleanup action rejected. Please refresh the page and try again.');
        return;
    }

    if ($action === 'clear_invalid_selected_character') {
        try {
            $stmt = $cleanupCharsPdo->exec("
                UPDATE website_accounts wa
                LEFT JOIN characters c
                    ON c.guid = wa.character_id
                   AND c.account = wa.account_id
                SET wa.character_id = 0,
                    wa.character_name = '',
                    wa.character_realm_id = NULL
                WHERE wa.character_id IS NOT NULL
                  AND wa.character_id > 0
                  AND c.guid IS NULL
            ");
            output_message('success', 'Cleared invalid selected-character pointers for ' . (int)$stmt . ' website account rows.');
        } catch (Throwable $e) {
            error_log('[admin.cleanup] clear_invalid_selected_character failed: ' . $e->getMessage());
            output_message('alert', 'Could not clear invalid selected-character pointers.');
        }
        return;
    }

    if ($action === 'remove_missing_account_rows') {
        try {
            $stmt = $cleanupPdo->exec("
                DELETE wa
                FROM website_accounts wa
                LEFT JOIN account a ON a.id = wa.account_id
                WHERE a.id IS NULL
            ");
            output_message('success', 'Removed ' . (int)$stmt . ' orphaned website account rows with no matching account.');
        } catch (Throwable $e) {
            error_log('[admin.cleanup] remove_missing_account_rows failed: ' . $e->getMessage());
            output_message('alert', 'Could not remove orphaned website account rows.');
        }
        return;
    }

    output_message('alert', 'That cleanup action is not enabled yet.');
}
