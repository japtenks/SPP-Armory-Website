

<?php
//cat /var/www/html/xfer/includes/realm_db.php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$realmId = (int)($_GET['realm'] ?? 1);
if (!isset($realmDbMap[$realmId])) die("Invalid realm ID");

$db['chars']  = $realmDbMap[$realmId]['chars'];
$db['world']  = $realmDbMap[$realmId]['world'];
$db['armory'] = $realmDbMap[$realmId]['armory'];
$realmName    = $realmDbMap[$realmId]['label'];
$expansion    = ($realmId == 3) ? 2 : (($realmId == 2) ? 1 : 0);