

<?php
$realmId = (int)($_GET['realm'] ?? 1);

$realms = [
    1 => ['classiccharacters','classicmangos','classicarmory','Classic'],
    2 => ['tbccharacters','tbcmangos','tbcarmory','The Burning Crusade'],
    3 => ['wotlkcharacters','wotlkmangos','wotlkarmory','Wrath of the Lich King']
];

if (!isset($realms[$realmId])) {
    die("Invalid realm ID");
}

$db = [
    'host' => '127.0.0.1',
    'port' => 3310,
    'user' => 'root',
    'pass' => '123456'
];

$db['chars']  = $realms[$realmId][0];
$db['world']  = $realms[$realmId][1];
$db['armory'] = $realms[$realmId][2];
$realmName    = $realms[$realmId][3];

$expansion = ($realmId == 3) ? 2 : (($realmId == 2) ? 1 : 0);
