<?php
require_once(__DIR__ . '/config/config-protected.php');
require_once(__DIR__ . '/armory/configuration/settings.php');
require_once(__DIR__ . '/core/dbsimple/Generic.php');
require_once(__DIR__ . '/armory/configuration/mysql.php');
require_once(__DIR__ . '/armory/configuration/functions.php');
require_once(__DIR__ . '/armory/configuration/tooltipmgr.php');

header('Content-Type: text/html; charset=UTF-8');

$itemId = isset($_GET['item']) ? (int)$_GET['item'] : 0;
$realmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 0;

if ($itemId <= 0 || $realmId <= 0) {
    http_response_code(400);
    exit('');
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || !isset($realmMap[$realmId])) {
    http_response_code(404);
    exit('');
}

$legacyRealmName = '';
if (!empty($realms) && is_array($realms)) {
    foreach ($realms as $name => $keys) {
        if ((int)($keys[2] ?? 0) === $realmId) {
            $legacyRealmName = (string)$name;
            break;
        }
    }
}

if ($legacyRealmName === '' || !function_exists('initialize_realm')) {
    http_response_code(500);
    exit('');
}

if (!defined('REALM_NAME')) {
    initialize_realm($legacyRealmName);
}

$tooltip = outputTooltip($itemId);
if (!is_array($tooltip) || empty($tooltip[0])) {
    http_response_code(404);
    exit('');
}

echo $tooltip[0];
