<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_botevents_handle_action(string $siteRoot, string $phpBin, bool $isWindowsHost, array $selectedEventTypes, string $processLimitValue, array $realmDbMap): array
{
    $state = array(
        'botOutput' => '',
        'botError' => '',
        'botNotice' => '',
        'botCommand' => '',
        'activeTab' => (string)($_REQUEST['tab'] ?? 'pipeline'),
        'configError' => '',
        'configDraft' => null,
    );

    $action = (string)($_REQUEST['action'] ?? '');
    if ($action === '' || $action === '0') {
        return $state;
    }

    spp_require_csrf('admin_botevents');

    if ($action === 'save_config') {
        $loadResult = spp_admin_botevents_load_config();
        $currentConfig = $loadResult['config'] ?? spp_admin_botevents_default_config();
        $realmOptions = spp_admin_botevents_realm_options($realmDbMap, $currentConfig);
        $config = spp_admin_botevents_extract_config_from_request($_POST, $currentConfig, $realmOptions);
        $state['activeTab'] = 'configure';
        $state['configDraft'] = $config;

        $writeResult = spp_admin_botevents_write_config($config);
        if (!empty($writeResult['ok'])) {
            redirect('index.php?n=admin&sub=botevents&tab=configure&saved=1');
        }

        $state['configError'] = (string)($writeResult['message'] ?? 'Unable to save bot event config.');
        return $state;
    }

    if ($action === 'save_achievement_config') {
        $loadResult = spp_admin_botevents_load_config();
        $currentConfig = $loadResult['config'] ?? spp_admin_botevents_default_config();
        $realmOptions = spp_admin_botevents_realm_options($realmDbMap, $currentConfig);
        $config = spp_admin_botevents_extract_achievement_config_from_request($_POST, $currentConfig, $realmOptions);
        $state['activeTab'] = 'achievements';
        $state['configDraft'] = $config;

        $writeResult = spp_admin_botevents_write_config($config);
        if (!empty($writeResult['ok'])) {
            redirect('index.php?n=admin&sub=botevents&tab=achievements&saved=1');
        }

        $state['configError'] = (string)($writeResult['message'] ?? 'Unable to save achievement config.');
        return $state;
    }

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
