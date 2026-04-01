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

    $realmName = null;
    if (function_exists('spp_get_armory_realm_name')) {
        $realmName = spp_get_armory_realm_name((int)$id);
    }

    if (!is_string($realmName) || trim($realmName) === '') {
        $realmName = 'Realm ' . (int)$id;
    }

    $realmName = trim((string)$realmName);
    $realms[$realmName] = [$id, $id, $id, $id, $id];
    if (!$defaultRealm) {
        $defaultRealm = $realmName;
    }
}

if ($defaultRealm) {
    define("DefaultRealmName", $defaultRealm);
} else {
    die("No realms could be loaded. Check DB connections.");
}

function execute_query($db_name, $query, $method = 0, $error = ""){
    global $realms;
    $realmDbMap = $GLOBALS['realmDbMap'] ?? [];
    $realmId = 0;

    $explicitCandidates = [
        $GLOBALS['talent_calc_realm_id'] ?? null,
        $GLOBALS['armory_realm_id'] ?? null,
        $_GET['realm'] ?? null,
    ];
    foreach ($explicitCandidates as $candidate) {
        $candidateId = (int)$candidate;
        if ($candidateId > 0 && isset($realmDbMap[$candidateId])) {
            $realmId = $candidateId;
            break;
        }
    }

    if ($realmId <= 0 && defined('REALM_NAME') && isset($realms[REALM_NAME])) {
        $realmId = (int)$realms[REALM_NAME][0];
    }

    if ($realmId <= 0 && is_array($realmDbMap) && !empty($realmDbMap)) {
        $realmId = (int)array_key_first($realmDbMap);
    }

    if ($realmId <= 0) {
        $realmId = 1;
    }

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
