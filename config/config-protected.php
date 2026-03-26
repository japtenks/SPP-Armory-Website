<?php
//cat /var/www/html/config/config-protected.php
// =============================================================
// MASTER CONFIG - edit credentials here only
// =============================================================

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-helper.php');

$db = [
    'host' => '192.168.1.47',
    'port' => 3306,
    'user' => 'mangos',
    'pass' => 'mangos'
];

$clientConnectionHost = '192.168.1.145';

$realmDbMap = [
    1 => [
        'realmd'  => 'classicrealmd',
        'world'   => 'classicmangos',
        'chars'   => 'classiccharacters',
        'armory'  => 'classicarmory',
        'bots'    => 'classicplayerbots',
    ],
    2 => [
        'realmd'  => 'tbcrealmd',
        'world'   => 'tbcmangos',
        'chars'   => 'tbccharacters',
        'armory'  => 'tbcarmory',
        'bots'    => 'tbcplayerbots',
    ],
    3 => [
        'realmd'  => 'wotlkrealmd',
        'world'   => 'wotlkmangos',
        'chars'   => 'wotlkcharacters',
        'armory'  => 'wotlkarmory',
        'bots'    => 'wotlkplayerbots',
    ],
];

$activeRealmId = spp_default_realm_id($realmDbMap);
$activeRealm = $realmDbMap[$activeRealmId];

$realmd = [
    'db_type'        => 'mysql',
    'db_host'        => $db['host'],
    'db_port'        => $db['port'],
    'db_username'    => $db['user'],
    'db_password'    => $db['pass'],
    'db_name'        => $activeRealm['realmd'],
    'db_encoding'    => 'utf8',
    'req_reg_invite' => 0,
];

$worlddb = [
    'db_type'     => 'mysql',
    'db_host'     => $db['host'],
    'db_port'     => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name'     => $activeRealm['world'],
    'db_encoding' => 'utf8',
];

$DB = [
    'db_type'     => 'mysql',
    'db_host'     => $db['host'],
    'db_port'     => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name'     => $activeRealm['world'],
    'db_encoding' => 'utf8',
];
$GLOBALS['db'] = $db;
$GLOBALS['realmDbMap'] = $realmDbMap;
$GLOBALS['activeRealmId'] = $activeRealmId;
$GLOBALS['activeRealm'] = $activeRealm;
$GLOBALS['realmd'] = $realmd;
$GLOBALS['worlddb'] = $worlddb;
$GLOBALS['DB'] = $DB;
