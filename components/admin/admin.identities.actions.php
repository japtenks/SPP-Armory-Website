<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_identities_handle_action(string $siteRoot, string $phpBin, bool $isWindowsHost, array $realmDbMap): array
{
    $state = [
        'identityOutput' => '',
        'identityError' => '',
        'identityNotice' => '',
        'identityCommand' => '',
    ];

    $action = (string)($_GET['action'] ?? '');
    if ($action !== 'run_backfill') {
        return $state;
    }

    spp_require_csrf('admin_identities');

    $realmId = (int)($_GET['realm'] ?? 0);
    $type = (string)($_GET['type'] ?? 'all');
    if ($realmId <= 0 || empty($realmDbMap[$realmId])) {
        $state['identityError'] = 'That realm is not configured.';
        return $state;
    }

    $validTypes = ['identities', 'posts', 'pms', 'all'];
    if (!in_array($type, $validTypes, true)) {
        $type = 'all';
    }

    $commands = spp_admin_identities_realm_commands($siteRoot, $realmId, $type);
    if ($isWindowsHost) {
        $state['identityNotice'] = 'Run this command from PowerShell or Command Prompt:';
        if (count($commands) > 1) {
            $state['identityNotice'] = 'Run these commands from PowerShell or Command Prompt:';
        }
        $state['identityCommand'] = implode("\n", array_map(function ($command) {
            return spp_admin_identities_build_command($command['script'], $command['args']);
        }, $commands));
        return $state;
    }

    $stdoutParts = [];
    $stderrParts = [];
    foreach ($commands as $command) {
        $result = spp_admin_identities_run_script($phpBin, $command['script'], $command['args']);
        $label = basename((string)$command['script']);
        $stdout = trim((string)($result['stdout'] ?? ''));
        $stderr = trim((string)($result['stderr'] ?? ''));
        if ($stdout !== '') {
            $stdoutParts[] = '[' . $label . ']' . "\n" . $stdout;
        }
        if ($stderr !== '') {
            $stderrParts[] = '[' . $label . ']' . "\n" . $stderr;
        }
    }

    $state['identityOutput'] = implode("\n\n", $stdoutParts);
    $state['identityError'] = implode("\n\n", $stderrParts);
    if ($state['identityOutput'] === '' && $state['identityError'] === '') {
        $state['identityNotice'] = 'Backfill command completed without console output.';
    }

    return $state;
}
