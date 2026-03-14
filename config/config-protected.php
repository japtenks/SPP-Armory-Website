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

if (!function_exists('spp_get_db_config')) {
    function spp_get_db_config($target = 'realmd', $realmId = null) {
        $db = $GLOBALS['db'] ?? null;
        $realmDbMap = $GLOBALS['realmDbMap'] ?? null;

        if (!is_array($db) || !is_array($realmDbMap) || empty($realmDbMap)) {
            throw new RuntimeException('Database configuration is not loaded.');
        }

        $resolvedRealmId = spp_resolve_realm_id($realmDbMap, $realmId);
        if (!isset($realmDbMap[$resolvedRealmId])) {
            throw new RuntimeException('Invalid realm selected.');
        }

        $realm = $realmDbMap[$resolvedRealmId];
        $dbKey = $target === 'world' ? 'world' : $target;

        if (!isset($realm[$dbKey])) {
            throw new RuntimeException('Unknown database target: ' . $target);
        }

        return [
            'host' => $db['host'],
            'port' => $db['port'],
            'user' => $db['user'],
            'pass' => $db['pass'],
            'name' => $realm[$dbKey],
            'realm_id' => $resolvedRealmId,
            'charset' => 'utf8mb4',
        ];
    }
}

if (!function_exists('spp_get_pdo')) {
    function spp_get_pdo($target = 'realmd', $realmId = null) {
        static $connections = [];

        $config = spp_get_db_config($target, $realmId);
        $cacheKey = $target . ':' . $config['realm_id'] . ':' . $config['name'];

        if (!isset($connections[$cacheKey])) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            try {
                $connections[$cacheKey] = new PDO(
                    "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']}",
                    $config['user'],
                    $config['pass'],
                    $options
                );
            } catch (Throwable $e) {
                if ($target !== 'armory') {
                    throw $e;
                }

                $realmDbMap = $GLOBALS['realmDbMap'] ?? [];
                $tried = [$config['name'] => true];
                foreach ($realmDbMap as $fallbackRealm) {
                    $fallbackName = $fallbackRealm['armory'] ?? null;
                    if (!$fallbackName || isset($tried[$fallbackName])) {
                        continue;
                    }
                    $tried[$fallbackName] = true;
                    try {
                        $fallbackCacheKey = $target . ':' . $config['realm_id'] . ':' . $fallbackName;
                        if (!isset($connections[$fallbackCacheKey])) {
                            $connections[$fallbackCacheKey] = new PDO(
                                "mysql:host={$config['host']};port={$config['port']};dbname={$fallbackName};charset={$config['charset']}",
                                $config['user'],
                                $config['pass'],
                                $options
                            );
                        }
                        error_log('[config] armory fallback: using ' . $fallbackName . ' for realm ' . (int)$config['realm_id']);
                        return $connections[$fallbackCacheKey];
                    } catch (Throwable $fallbackError) {
                        continue;
                    }
                }

                throw $e;
            }
        }

        return $connections[$cacheKey];
    }
}

if (!function_exists('spp_get_armory_realm_name')) {
    function spp_get_armory_realm_name($realmId = null) {
        static $cache = [];

        $db = $GLOBALS['db'] ?? null;
        $realmDbMap = $GLOBALS['realmDbMap'] ?? null;

        if (!is_array($db) || !is_array($realmDbMap) || empty($realmDbMap)) {
            return null;
        }

        $resolvedRealmId = spp_resolve_realm_id($realmDbMap, $realmId);
        if (isset($cache[$resolvedRealmId])) {
            return $cache[$resolvedRealmId];
        }

        $fallback = null;
        $realmdDb = $realmDbMap[$resolvedRealmId]['realmd'] ?? null;
        if (!$realmdDb) {
            return $cache[$resolvedRealmId] = $fallback;
        }

        try {
            $pdo = new PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$realmdDb};charset=utf8mb4",
                $db['user'],
                $db['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $stmt = $pdo->prepare("SELECT `name` FROM `realmlist` WHERE `id` = ? LIMIT 1");
            $stmt->execute([(int)$resolvedRealmId]);
            $row = $stmt->fetch();

            if (!$row) {
                $row = $pdo->query("SELECT `name` FROM `realmlist` ORDER BY `id` ASC LIMIT 1")->fetch();
            }

            $cache[$resolvedRealmId] = !empty($row['name']) ? $row['name'] : $fallback;
            return $cache[$resolvedRealmId];
        } catch (Throwable $e) {
            error_log('[config] Failed resolving armory realm name: ' . $e->getMessage());
            return $cache[$resolvedRealmId] = $fallback;
        }
    }
}

$db = [
    'host' => '192.168.1.90',
    'port' => 3306,
    'user' => 'mangos',
    'pass' => 'mangos'
];

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
