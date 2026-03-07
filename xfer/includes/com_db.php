<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/realm_db.php');

$db = [
    'host' => '127.0.0.1',
    'port' => 3310,
    'user' => 'root',
    'pass' => '123456',
    'name' => $realms[$realmId][0]
];

$world_db  = $realms[$realmId][1];
$tpl       = $realms[$realmId][2];
$realmName = $realms[$realmId][3];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("<b>Database connection failed:</b> " . htmlspecialchars($e->getMessage()));
}