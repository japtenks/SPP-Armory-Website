<?php
//root@spp-web:~# cat /var/www/html/armory/configuration/mysql.php

require_once(__DIR__ . '/../config-protected.php');

set_time_limit(0);
ini_set("default_charset", "UTF-8");

$hostport = "{$db['host']}:{$db['port']}";

$realmd_DB     = [];
$characters_DB = [];
$mangosd_DB    = [];
$armory_DB     = [];
$playerbot_DB  = [];
$realms        = [];
$defaultRealm  = null;

foreach ($realmDbMap as $id => $dbs) {
    $realmd_DB[$id]     = [$hostport, $db['user'], $db['pass'], $dbs['realmd']];
    $characters_DB[$id] = [$hostport, $db['user'], $db['pass'], $dbs['chars']];
    $mangosd_DB[$id]    = [$hostport, $db['user'], $db['pass'], $dbs['world']];
    $armory_DB[$id]     = [$hostport, $db['user'], $db['pass'], $dbs['armory']];
    $playerbot_DB[$id]  = [$hostport, $db['user'], $db['pass'], $dbs['bots']];

    // Auto-detect realm name from realmlist
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$dbs['realmd']};charset=utf8",
            $db['user'], $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $row = $pdo->query("SELECT `name` FROM `realmlist` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $realmName = $row['name'];
            $realms[$realmName] = [$id, $id, $id, $id, $id];
            if (!$defaultRealm) $defaultRealm = $realmName;
        }
    } catch (PDOException $e) {
        // DB not available, skip
    }
}

if ($defaultRealm) {
    define("DefaultRealmName", $defaultRealm);
} else {
    die("No realms could be loaded. Check DB connections.");
}

function execute_query($db_name, $query, $method = 0, $error = ""){
    global $realms;
    $realmId = defined('REALM_NAME') && isset($realms[REALM_NAME]) ? (int)$realms[REALM_NAME][0] : 1;
    $target_map = [
        'realm' => 'realmd',
        'char'  => 'chars',
        'world' => 'world',
        'armory'=> 'armory',
        'bots'  => 'bots',
    ];
    $target = $target_map[$db_name] ?? null;
    if (!$target) die($error . "Database not chosen");
    try {
        $pdo  = spp_get_pdo($target, $realmId);
        $stmt = $pdo->query($query);
        if (!$stmt) {
            if ($error) die($error);
            return false;
        }
        if ($method == 1) return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        if ($method == 2) return $stmt->fetchColumn();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($error) die($error);
        return false;
    }
}
?>