<?php
//cat /var/www/html/xfer/includes/realm_db.php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

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
$realmName    = $realmMap[$realmId]['label'];
$expansion    = ($realmId == 3) ? 2 : (($realmId == 2) ? 1 : 0);
