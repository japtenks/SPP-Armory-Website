<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.botevents.helpers.php');
require_once(__DIR__ . '/admin.botevents.actions.php');
require_once(__DIR__ . '/admin.botevents.read.php');

$siteRoot = $_SERVER['DOCUMENT_ROOT'];
$masterPdo = spp_get_pdo('realmd', 1);
$phpBin = spp_admin_botevents_resolve_php_cli_binary();
$processLimitValue = trim((string)($_GET['process_limit'] ?? ''));
$isWindowsHost = DIRECTORY_SEPARATOR === '\\';
$selectedEventTypes = $_GET['event_types'] ?? array();
$pathway_info[] = ['title' => 'Bot Events', 'link' => 'index.php?n=admin&sub=botevents'];

if (!is_array($selectedEventTypes)) {
    $selectedEventTypes = [$selectedEventTypes];
}
$selectedEventTypes = array_values(array_unique(array_filter(array_map('strval', $selectedEventTypes), static function ($value) {
    return $value !== '';
})));

$actionState = spp_admin_botevents_handle_action($siteRoot, $phpBin, $isWindowsHost, $selectedEventTypes, $processLimitValue);
$viewState = spp_admin_botevents_build_view($masterPdo, $selectedEventTypes);

$botOutput = $actionState['botOutput'] ?? '';
$botError = ($actionState['botError'] ?? '') . ($viewState['statsError'] ?? '');
$botNotice = $actionState['botNotice'] ?? '';
$botCommand = $actionState['botCommand'] ?? '';
$botStats = $viewState['botStats'] ?? array();
$recentEvents = $viewState['recentEvents'] ?? array();
$availableEventTypes = $viewState['availableEventTypes'] ?? array();
$selectedEventTypes = $viewState['selectedEventTypes'] ?? array();
$pendingTypeBreakdown = $viewState['pendingTypeBreakdown'] ?? array();
$admin_botevents_csrf_token = spp_csrf_token('admin_botevents');
?>
