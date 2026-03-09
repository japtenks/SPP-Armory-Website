<?php
//root@spp-web:~# cat /var/www/html/armory/configuration/mysql.php

require_once(__DIR__ . '/../../config/config-protected.php');

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
    global $DB, $WSDB, $CHDB, $ARDB, $PBDB;
    $query_result = false;
    if ($method == 0) {
        if ($db_name == "realm")      $query_result = $DB->query($query);
        elseif ($db_name == "armory") $query_result = $ARDB->query($query);
        elseif ($db_name == "char")   $query_result = $CHDB->query($query);
        elseif ($db_name == "world")  $query_result = $WSDB->query($query);
        elseif ($db_name == "bots")   $query_result = $PBDB->query($query);
    } elseif ($method == 1) {
        if ($db_name == "realm")      $query_result = $DB->selectRow($query);
        elseif ($db_name == "armory") $query_result = $ARDB->selectRow($query);
        elseif ($db_name == "char")   $query_result = $CHDB->selectRow($query);
        elseif ($db_name == "world")  $query_result = $WSDB->selectRow($query);
        elseif ($db_name == "bots")   $query_result = $PBDB->selectRow($query);
    } elseif ($method == 2) {
        if ($db_name == "realm")      $query_result = $DB->selectCell($query);
        elseif ($db_name == "armory") $query_result = $ARDB->selectCell($query);
        elseif ($db_name == "char")   $query_result = $CHDB->selectCell($query);
        elseif ($db_name == "world")  $query_result = $WSDB->selectCell($query);
        elseif ($db_name == "bots")   $query_result = $PBDB->selectCell($query);
    }
    if (!$db_name) die($error . "Database not chosen");
    if ($query_result) return $query_result;
    elseif (!$query_result && $error) { die($error); return false; }
    return false;
}
?>