
<?php
//cat /var/www/html/config/config-protected.php
// =============================================================
// MASTER CONFIG - edit credentials here only
// =============================================================
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

// =============================================================
// AUTO-DETECT ACTIVE REALM
// =============================================================
$activeRealm = null;
foreach ($realmDbMap as $id => $dbs) {
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$dbs['realmd']};charset=utf8",
            $db['user'], $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $row = $pdo->query("SELECT `name` FROM `realmlist` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $activeRealm = ['id' => $id, 'dbs' => $dbs, 'name' => $row['name']];
            break;
        }
    } catch (PDOException $e) {
        // DB not available, try next
    }
}

if (!$activeRealm) {
    die("No realms could be loaded. Check DB connections.");
}

// =============================================================
// LEGACY COMPAT - $realmd, $worlddb, $DB arrays
// =============================================================
$realmd = [
    'db_type'        => 'mysql',
    'db_host'        => $db['host'],
    'db_port'        => $db['port'],
    'db_username'    => $db['user'],
    'db_password'    => $db['pass'],
    'db_name'        => $activeRealm['dbs']['realmd'],
    'db_encoding'    => 'utf8',
    'req_reg_invite' => 0,
];

$worlddb = [
    'db_type'     => 'mysql',
    'db_host'     => $db['host'],
    'db_port'     => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name'     => $activeRealm['dbs']['world'],
    'db_encoding' => 'utf8',
];

$DB = [
    'db_type'     => 'mysql',
    'db_host'     => $db['host'],
    'db_port'     => $db['port'],
    'db_username' => $db['user'],
    'db_password' => $db['pass'],
    'db_name'     => $activeRealm['dbs']['world'],
    'db_encoding' => 'utf8',
];