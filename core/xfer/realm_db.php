<?php
//cat /var/www/html/xfer/includes/realm_db.php

require_once(dirname(__FILE__, 3).'/config/config-protected.php');

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$db = $db ?? ($GLOBALS['db'] ?? null);

if (!is_array($realmMap) || !is_array($db)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);

$db['chars']  = $realmMap[$realmId]['chars'];
$db['world']  = $realmMap[$realmId]['world'];
$db['armory'] = $realmMap[$realmId]['armory'];
$db['bots']   = $realmMap[$realmId]['bots'];
$realmName    = function_exists('spp_get_armory_realm_name')
    ? (spp_get_armory_realm_name($realmId) ?? '')
    : '';
$expansion    = ($realmId == 3) ? 2 : (($realmId == 2) ? 1 : 0);
