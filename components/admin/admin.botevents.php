<?php
if (INCLUDED !== true) exit;

$siteRoot   = $_SERVER['DOCUMENT_ROOT'];
$masterPdo  = spp_get_pdo('realmd', 1);
$phpBin     = '';
$botOutput  = '';
$botError   = '';
$botNotice  = '';
$botCommand = '';
$botStats   = [];
$recentEvents = [];
$processLimitValue = trim((string)($_GET['process_limit'] ?? ''));
$isWindowsHost = DIRECTORY_SEPARATOR === '\\';
$selectedEventTypes = $_GET['event_types'] ?? [];
$availableEventTypes = [];
$pendingTypeBreakdown = [];

if (!is_array($selectedEventTypes)) {
    $selectedEventTypes = [$selectedEventTypes];
}
$selectedEventTypes = array_values(array_unique(array_filter(array_map('strval', $selectedEventTypes), static function ($value) {
    return $value !== '';
})));

$pathway_info[] = ['title' => 'Bot Events', 'link' => 'index.php?n=admin&sub=botevents'];

function resolve_php_cli_binary(): string {
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

$phpBin = resolve_php_cli_binary();

// ---- Run a tool script and capture output ----
function run_bot_script(string $phpBin, string $scriptPath, array $extraArgs = []): array {
    $args = array_map('escapeshellarg', $extraArgs);
    $cmd  = escapeshellcmd($phpBin) . ' ' . escapeshellarg($scriptPath)
          . (empty($args) ? '' : ' ' . implode(' ', $args));

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, $scriptPath ? dirname($scriptPath) : null);
    if (!is_resource($proc)) {
        return ['stdout' => '', 'stderr' => 'proc_open failed — check PHP disable_functions.', 'code' => -1];
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return ['stdout' => $stdout, 'stderr' => $stderr, 'code' => $code];
}

function build_bot_script_command(string $phpBin, string $scriptPath, array $extraArgs = []): string {
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

function append_event_type_args(array $args, array $selectedEventTypes): array {
    if (!empty($selectedEventTypes)) {
        $args[] = '--event=' . implode(',', $selectedEventTypes);
    }
    return $args;
}

// ---- Handle actions ----
$action   = $_GET['action'] ?? '';

if ($action === 'scan') {
    if ($isWindowsHost) {
        $botNotice = 'Run this command from PowerShell or Command Prompt:';
        $botCommand = build_bot_script_command($phpBin, $siteRoot . '/tools/scan_bot_events.php');
    } else {
        $result    = run_bot_script($phpBin, $siteRoot . '/tools/scan_bot_events.php');
        $botOutput = $result['stdout'];
        $botError  = $result['stderr'];
    }
} elseif ($action === 'process') {
    $processArgs = append_event_type_args([], $selectedEventTypes);
    if ($processLimitValue !== '' && ctype_digit($processLimitValue) && (int)$processLimitValue > 0) {
        $processArgs[] = '--limit=' . (int)$processLimitValue;
    }
    if ($isWindowsHost) {
        $botNotice = 'Run this command from PowerShell or Command Prompt:';
        $botCommand = build_bot_script_command($phpBin, $siteRoot . '/tools/process_bot_events.php', $processArgs);
    } else {
        $result    = run_bot_script($phpBin, $siteRoot . '/tools/process_bot_events.php', $processArgs);
        $botOutput = $result['stdout'];
        $botError  = $result['stderr'];
    }
} elseif ($action === 'scan_dry') {
    if ($isWindowsHost) {
        $botNotice = 'Run this command from PowerShell or Command Prompt:';
        $botCommand = build_bot_script_command($phpBin, $siteRoot . '/tools/scan_bot_events.php', ['--dry-run']);
    } else {
        $result    = run_bot_script($phpBin, $siteRoot . '/tools/scan_bot_events.php', ['--dry-run']);
        $botOutput = $result['stdout'];
        $botError  = $result['stderr'];
    }
} elseif ($action === 'process_dry') {
    $processArgs = append_event_type_args(['--dry-run'], $selectedEventTypes);
    if ($processLimitValue !== '' && ctype_digit($processLimitValue) && (int)$processLimitValue > 0) {
        $processArgs[] = '--limit=' . (int)$processLimitValue;
    }
    if ($isWindowsHost) {
        $botNotice = 'Run this command from PowerShell or Command Prompt:';
        $botCommand = build_bot_script_command($phpBin, $siteRoot . '/tools/process_bot_events.php', $processArgs);
    } else {
        $result    = run_bot_script($phpBin, $siteRoot . '/tools/process_bot_events.php', $processArgs);
        $botOutput = $result['stdout'];
        $botError  = $result['stderr'];
    }
} elseif ($action === 'skip_all') {
    if ($isWindowsHost) {
        $botNotice = 'Run this command from PowerShell or Command Prompt:';
        $botCommand = build_bot_script_command($phpBin, $siteRoot . '/tools/process_bot_events.php', ['--skip-all']);
    } else {
        $result    = run_bot_script($phpBin, $siteRoot . '/tools/process_bot_events.php', ['--skip-all']);
        $botOutput = $result['stdout'];
        $botError  = $result['stderr'];
    }
}

// ---- Load stats ----
try {
    $stmt = $masterPdo->query("
        SELECT DISTINCT event_type
        FROM website_bot_events
        ORDER BY event_type ASC
    ");
    $availableEventTypes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $availableEventTypes = array_values(array_unique(array_filter(array_map('strval', $availableEventTypes))));

    $stmt = $masterPdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM website_bot_events
        GROUP BY status
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $botStats[$row['status']] = (int)$row['cnt'];
    }

    $stmt = $masterPdo->query("
        SELECT event_type, COUNT(*) AS cnt
        FROM website_bot_events
        WHERE status = 'pending'
        GROUP BY event_type
        ORDER BY cnt DESC, event_type ASC
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $pendingTypeBreakdown[] = [
            'event_type' => (string)$row['event_type'],
            'count' => (int)$row['cnt'],
        ];
    }

    $stmt = $masterPdo->query("
        SELECT event_id, event_type, realm_id, status, occurred_at, processed_at, error_message,
               payload_json
        FROM website_bot_events
        ORDER BY event_id DESC
        LIMIT 30
    ");
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $botError .= "\nStats query failed: " . $e->getMessage();
}

if (empty($availableEventTypes)) {
    $availableEventTypes = [
        'achievement_badge',
        'guild_created',
        'guild_roster_update',
        'level_up',
        'profession_milestone',
    ];
}

$selectedEventTypes = array_values(array_intersect($selectedEventTypes, $availableEventTypes));
?>
