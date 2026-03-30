<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_botevents_handle_action(string $siteRoot, string $phpBin, bool $isWindowsHost, array $selectedEventTypes, string $processLimitValue): array
{
    $state = array(
        'botOutput' => '',
        'botError' => '',
        'botNotice' => '',
        'botCommand' => '',
    );

    $action = (string)($_GET['action'] ?? '');
    if ($action === '' || $action === '0') {
        return $state;
    }

    spp_require_csrf('admin_botevents');

    if ($action === 'scan' || $action === 'scan_dry') {
        $args = $action === 'scan_dry' ? array('--dry-run') : array();
        $scriptPath = $siteRoot . '/tools/scan_bot_events.php';
        if ($isWindowsHost) {
            $state['botNotice'] = 'Run this command from PowerShell or Command Prompt:';
            $state['botCommand'] = spp_admin_botevents_build_command($scriptPath, $args);
            return $state;
        }

        $result = spp_admin_botevents_run_script($phpBin, $scriptPath, $args);
        $state['botOutput'] = $result['stdout'];
        $state['botError'] = $result['stderr'];
        return $state;
    }

    if ($action === 'process' || $action === 'process_dry') {
        $args = $action === 'process_dry' ? array('--dry-run') : array();
        $args = spp_admin_botevents_append_event_type_args($args, $selectedEventTypes);
        if ($processLimitValue !== '' && ctype_digit($processLimitValue) && (int)$processLimitValue > 0) {
            $args[] = '--limit=' . (int)$processLimitValue;
        }
        $scriptPath = $siteRoot . '/tools/process_bot_events.php';
        if ($isWindowsHost) {
            $state['botNotice'] = 'Run this command from PowerShell or Command Prompt:';
            $state['botCommand'] = spp_admin_botevents_build_command($scriptPath, $args);
            return $state;
        }

        $result = spp_admin_botevents_run_script($phpBin, $scriptPath, $args);
        $state['botOutput'] = $result['stdout'];
        $state['botError'] = $result['stderr'];
        return $state;
    }

    if ($action === 'skip_all') {
        $scriptPath = $siteRoot . '/tools/process_bot_events.php';
        $args = array('--skip-all');
        if ($isWindowsHost) {
            $state['botNotice'] = 'Run this command from PowerShell or Command Prompt:';
            $state['botCommand'] = spp_admin_botevents_build_command($scriptPath, $args);
            return $state;
        }

        $result = spp_admin_botevents_run_script($phpBin, $scriptPath, $args);
        $state['botOutput'] = $result['stdout'];
        $state['botError'] = $result['stderr'];
        return $state;
    }

    return $state;
}
