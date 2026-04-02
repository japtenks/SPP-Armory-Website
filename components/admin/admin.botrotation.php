<?php
if (INCLUDED !== true) exit;

$pathway_info[] = ['title' => 'Bot Rotation Health', 'link' => 'index.php?n=admin&sub=botrotation'];
require_once(__DIR__ . '/admin.botrotation.read.php');

$botRotationView = spp_admin_botrotation_build_view($realmDbMap);
$realmId = $botRotationView['realmId'];
$realmName = $botRotationView['realmName'] ?? ('Realm #' . (int)$realmId);
$rotationData = $botRotationView['rotationData'];
$rotationError = $botRotationView['rotationError'];
$rotationConfig = $botRotationView['rotationConfig'];
$latestHistory = $botRotationView['latestHistory'];
$topBotData = $botRotationView['topBotData'];
$totalServerUptime = $botRotationView['totalServerUptime'];
$currentRunSec = $botRotationView['currentRunSec'];
$restartsToday = $botRotationView['restartsToday'];
$historyRows = $botRotationView['historyRows'];
$hasHistory = $botRotationView['hasHistory'];
$liveOnlineAvg = $botRotationView['liveOnlineAvg'];
$liveOnlineMax = $botRotationView['liveOnlineMax'];
$uptimeSummary = $botRotationView['uptimeSummary'] ?? array();
$cleanHistory = $botRotationView['cleanHistory'] ?? array();
$longestOnlineBot = $botRotationView['longestOnlineBot'] ?? null;
$longestOfflineBot = $botRotationView['longestOfflineBot'] ?? null;
$rotationCommands = $botRotationView['commands'] ?? array();
$isWindowsHost = !empty($botRotationView['isWindowsHost']);
