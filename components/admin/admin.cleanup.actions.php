<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_cleanup_handle_action()
{
    $action = (string)($_GET['action'] ?? '');
    if ($action === '' || $action === '0') {
        return;
    }

    output_message('alert', 'Cleanup actions are still in preview mode. No destructive maintenance action has been enabled yet.');
}
