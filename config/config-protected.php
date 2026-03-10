
<?php
//cat /var/www/html/config/config-protected.php
// =============================================================
// MASTER CONFIG - edit credentials here only
// =============================================================

if (!function_exists('spp_default_realm_id')) {
    function spp_default_realm_id(array $realmDbMap) {
/*         if (file_exists(__DIR__ . '/../wotlk.spp') && isset($realmDbMap[3])) return 3;
        if (file_exists(__DIR__ . '/../tbc.spp') && isset($realmDbMap[2])) return 2;
        if (file_exists(__DIR__ . '/../vanilla.spp') && isset($realmDbMap[1])) return 1; */
        return 1;
    }
}

if (!function_exists('spp_resolve_realm_id')) {
    function spp_resolve_realm_id(array $realmDbMap, $fallback = null) {
        $candidates = [
            $_GET['realm'] ?? null,
            $_COOKIE['cur_selected_realm'] ?? null,
            $GLOBALS['user']['cur_selected_realmd'] ?? null,
            $fallback,
        ];

        foreach ($candidates as $candidate) {
            $realmId = (int)$candidate;
            if ($realmId > 0 && isset($realmDbMap[$realmId])) {
                return $realmId;
            }
        }

        return spp_default_realm_id($realmDbMap);
    }
}

$db = [
    'host' => '127.0.0.1',
    'port' => 3310,
    'user' => 'root',
    'pass' => '123456'
];

$realmDbMap = [
    1 => [
        'realmd'  => 'classicrealmd',
        'world'   => 'classicmangos',
        'chars'   => 'classiccharacters',
        'armory'  => 'classicarmory',
        'bots'    => 'classicplayerbots',
        'label'   => 'Classic',
    ],
    2 => [
        'realmd'  => 'tbcrealmd',
        'world'   => 'tbcmangos',
        'chars'   => 'tbccharacters',
        'armory'  => 'tbcarmory',
        'bots'    => 'tbcplayerbots',
        'label'   => 'The Burning Crusade',
    ],
    3 => [
        'realmd'  => 'wotlkrealmd',
        'world'   => 'wotlkmangos',
        'chars'   => 'wotlkcharacters',
        'armory'  => 'wotlkarmory',
        'bots'    => 'wotlkplayerbots',
        'label'   => 'Wrath of the Lich King',
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
