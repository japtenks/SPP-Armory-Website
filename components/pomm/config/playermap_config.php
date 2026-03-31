
<?php
//cat /var/www/html/components/pomm/config/playermap_config.php
require_once(__DIR__ . '/../../../config/config-protected.php');

$playermapRealmId = spp_resolve_realm_id($realmDbMap);

$DB_HOST = $db['host'];
$DB_PORT = $db['port'];
$DB_USER = $db['user'];
$DB_PASS = $db['pass'];
$GAME_HOST = !empty($clientConnectionHost) ? $clientConnectionHost : '127.0.0.1';
$REALM_HOSTS = array();
$REALM_PORTS = array();

try {
    $realmdPdo = new PDO(
        'mysql:host=' . $DB_HOST . ';port=' . $DB_PORT . ';dbname=' . $realmDbMap[$playermapRealmId]['realmd'] . ';charset=utf8mb4',
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $realmlistStmt = $realmdPdo->query('SELECT id, address, port FROM realmlist');
    while ($row = $realmlistStmt->fetch(PDO::FETCH_ASSOC)) {
        $realmId = (int)($row['id'] ?? 0);
        if ($realmId <= 0) {
            continue;
        }
        $REALM_HOSTS[$realmId] = trim((string)($row['address'] ?? ''));
        $REALM_PORTS[$realmId] = (int)($row['port'] ?? 8085);
    }
} catch (Throwable $e) {
    $REALM_HOSTS = array();
    $REALM_PORTS = array();
}

$WORLD_NAMES = array_map(function ($r) { return $r['world']; }, $realmDbMap);
$CHAR_NAMES  = array_map(function ($r) { return $r['chars']; }, $realmDbMap);

$site_encoding = "utf-8";
$db_type = "MySQL";
$language = "english";

// add realm info if you want status window info
foreach ($WORLD_NAMES as $id => $name) {
    $world_db[$id]['addr'] = $DB_HOST . ":" . $DB_PORT;
    $world_db[$id]['user'] = $DB_USER;
    $world_db[$id]['pass'] = $DB_PASS;
    $world_db[$id]['name'] = $name;
    $world_db[$id]['encoding'] = "utf8";
}

foreach ($CHAR_NAMES as $id => $name) {
    $characters_db[$id]['addr'] = $DB_HOST . ":" . $DB_PORT;
    $characters_db[$id]['user'] = $DB_USER;
    $characters_db[$id]['pass'] = $DB_PASS;
    $characters_db[$id]['name'] = $name;
    $characters_db[$id]['encoding'] = "utf8";
}

$realm_db = [
    'addr' => $DB_HOST . ":" . $DB_PORT,
    'user' => $DB_USER,
    'pass' => $DB_PASS,
    'name' => $realmDbMap[$playermapRealmId]['realmd'],
    'encoding' => 'utf8'
];

$server = [];
foreach (array_keys($realmDbMap) as $id) {
    $server[$id] = [
        'addr' => (!empty($REALM_HOSTS[$id]) ? $REALM_HOSTS[$id] : $GAME_HOST),
        'game_port' => (!empty($REALM_PORTS[$id]) ? (int)$REALM_PORTS[$id] : 8085)
    ];
}

$gm_online = true;
$gm_online_count = 100;
$map_gm_show_online_only_gmoff = 1;
$map_gm_show_online_only_gmvisible = 1;
$map_gm_add_suffix = 1;
$map_status_gm_include_all = 0;

$map_show_status = 0;
$map_show_time = 1;
$map_time = 10;

$map_time_to_show_uptime = 5000;
$map_time_to_show_maxonline = 5000;
$map_time_to_show_gmonline = 5000;

$developer_test_mode = false;
$multi_realm_mode = true;

// === Player Map configuration === //

// GM online options
$gm_online                         = true;
$gm_online_count                   = 100;

$map_gm_show_online_only_gmoff     = 1; // show GM point only if in '.gm off' [1/0]
$map_gm_show_online_only_gmvisible = 1; // show GM point only if in '.gm visible on' [1/0]
$map_gm_add_suffix                 = 1; // add '{GM}' to name [1/0]
$map_status_gm_include_all         = 0; // include 'all GMs in game'/'who on map' [1/0]

// status window options:
$map_show_status = 0;                   // show server status window [1/0]
$map_show_time   = 0;                   // Show autoupdate timer 1 - on, 0 - off
$map_time        = 0;                   // Map autoupdate time (seconds), 0 - not update.

// all times set in msec (do not set time < 1500 for show), 0 to disable.
$map_time_to_show_uptime    = 5000;     // time to show uptime string
$map_time_to_show_maxonline = 5000;     // time to show max online
$map_time_to_show_gmonline  = 5000;     // time to show GM online

$developer_test_mode = false;
$multi_realm_mode    = true;
