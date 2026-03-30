<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_botevents_action_url(array $params)
{
    return spp_action_url('index.php', $params, 'admin_botevents');
}

function spp_admin_botevents_resolve_php_cli_binary(): string
{
    $candidates = array_filter(array_unique([
        (string)(PHP_BINARY ?? ''),
        (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php' : ''),
        '/usr/bin/php',
        '/usr/local/bin/php',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/opt/cpanel/ea-php81/root/usr/bin/php',
        '/opt/cpanel/ea-php80/root/usr/bin/php',
        '/opt/cpanel/ea-php74/root/usr/bin/php',
        'php',
    ]));

    foreach ($candidates as $candidate) {
        if ($candidate === 'php') {
            return $candidate;
        }
        if (@is_file($candidate) && @is_executable($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function spp_admin_botevents_run_script(string $phpBin, string $scriptPath, array $extraArgs = []): array
{
    $args = array_map('escapeshellarg', $extraArgs);
    $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($scriptPath)
         . (empty($args) ? '' : ' ' . implode(' ', $args));

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, $scriptPath ? dirname($scriptPath) : null);
    if (!is_resource($proc)) {
        return ['stdout' => '', 'stderr' => 'proc_open failed - check PHP disable_functions.', 'code' => -1];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return ['stdout' => $stdout, 'stderr' => $stderr, 'code' => $code];
}

function spp_admin_botevents_build_command(string $scriptPath, array $extraArgs = []): string
{
    $parts = ['php'];

    if (is_string($scriptPath) && $scriptPath !== '') {
        $relativeScript = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scriptPath);
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (is_string($docRoot) && $docRoot !== '' && stripos($relativeScript, $docRoot) === 0) {
            $relativeScript = ltrim(substr($relativeScript, strlen($docRoot)), '/\\');
        }
        $parts[] = $relativeScript;
    }

    foreach ($extraArgs as $arg) {
        $parts[] = $arg;
    }

    return implode(' ', $parts);
}

function spp_admin_botevents_append_event_type_args(array $args, array $selectedEventTypes): array
{
    if (!empty($selectedEventTypes)) {
        $args[] = '--event=' . implode(',', $selectedEventTypes);
    }
    return $args;
}
