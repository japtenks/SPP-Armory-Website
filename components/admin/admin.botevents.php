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
$activeTab = (string)($_REQUEST['tab'] ?? 'pipeline');
$selectedEventTypes = $_GET['event_types'] ?? array();
$pathway_info[] = ['title' => 'Bot Events', 'link' => 'index.php?n=admin&sub=botevents'];

if (!is_array($selectedEventTypes)) {
    $selectedEventTypes = [$selectedEventTypes];
}
$selectedEventTypes = array_values(array_unique(array_filter(array_map('strval', $selectedEventTypes), static function ($value) {
    return $value !== '';
})));

$actionState = spp_admin_botevents_handle_action($siteRoot, $phpBin, $isWindowsHost, $selectedEventTypes, $processLimitValue, $realmDbMap ?? array());
$viewState = spp_admin_botevents_build_view($masterPdo, $selectedEventTypes, $realmDbMap ?? array(), $actionState['configDraft'] ?? null);

$botOutput = $actionState['botOutput'] ?? '';
$botError = trim(($actionState['botError'] ?? '') . ($viewState['statsError'] ?? ''));
$botNotice = $actionState['botNotice'] ?? '';
$botCommand = $actionState['botCommand'] ?? '';
$activeTab = $actionState['activeTab'] ?? $activeTab;
$botStats = $viewState['botStats'] ?? array();
$recentEvents = $viewState['recentEvents'] ?? array();
$availableEventTypes = $viewState['availableEventTypes'] ?? array();
$selectedEventTypes = $viewState['selectedEventTypes'] ?? array();
$pendingTypeBreakdown = $viewState['pendingTypeBreakdown'] ?? array();
$botConfig = $viewState['botConfig'] ?? array();
$realmOptions = $viewState['realmOptions'] ?? array();
$configPath = $viewState['configPath'] ?? '';
$configLoadError = $viewState['configLoadError'] ?? '';
$configWritable = !empty($viewState['configWritable']);
$achievementCatalog = $viewState['achievementCatalog'] ?? array();
$configError = $actionState['configError'] ?? '';
$configSaved = isset($_GET['saved']) && (string)$_GET['saved'] === '1';
$admin_botevents_csrf_token = spp_csrf_token('admin_botevents');
?>
