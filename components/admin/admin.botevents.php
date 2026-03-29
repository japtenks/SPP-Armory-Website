<?php
if (INCLUDED !== true) exit;

$siteRoot   = $_SERVER['DOCUMENT_ROOT'];
$masterPdo  = spp_get_pdo('realmd', 1);
$phpBin     = PHP_BINARY;
$botOutput  = '';
$botError   = '';
$botStats   = [];
$recentEvents = [];

$pathway_info[] = ['title' => 'Bot Events', 'link' => 'index.php?n=admin&sub=botevents'];

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

// ---- Handle actions ----
$action   = $_GET['action'] ?? '';
$isWindows = (DIRECTORY_SEPARATOR === '\\');

if ($isWindows && in_array($action, ['scan','process','scan_dry','process_dry','skip_all'], true)) {
    $botError = "Script execution is disabled on Windows to prevent Apache crashes.\n"
              . "Use the cron commands directly from a command prompt:\n"
              . "  php tools/scan_bot_events.php\n"
              . "  php tools/process_bot_events.php";
    $action = '';
}

if ($action === 'scan') {
    $result    = run_bot_script($phpBin, $siteRoot . '/tools/scan_bot_events.php');
    $botOutput = $result['stdout'];
    $botError  = $result['stderr'];
} elseif ($action === 'process') {
    $result    = run_bot_script($phpBin, $siteRoot . '/tools/process_bot_events.php');
    $botOutput = $result['stdout'];
    $botError  = $result['stderr'];
} elseif ($action === 'scan_dry') {
    $result    = run_bot_script($phpBin, $siteRoot . '/tools/scan_bot_events.php', ['--dry-run']);
    $botOutput = $result['stdout'];
    $botError  = $result['stderr'];
} elseif ($action === 'process_dry') {
    $result    = run_bot_script($phpBin, $siteRoot . '/tools/process_bot_events.php', ['--dry-run']);
    $botOutput = $result['stdout'];
    $botError  = $result['stderr'];
} elseif ($action === 'skip_all') {
    $result    = run_bot_script($phpBin, $siteRoot . '/tools/process_bot_events.php', ['--skip-all']);
    $botOutput = $result['stdout'];
    $botError  = $result['stderr'];
}

// ---- Load stats ----
try {
    $stmt = $masterPdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM website_bot_events
        GROUP BY status
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $botStats[$row['status']] = (int)$row['cnt'];
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
?>
