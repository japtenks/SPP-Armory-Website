<?php
//root@spp-web:~# cat /var/www/html/xfer/includes/com_db.php

require_once($_SERVER['DOCUMENT_ROOT'] . '/xfer/includes/realm_db.php');

// $db already has host/port/user/pass/chars from realm_db.php
$db['name'] = $db['chars'];
$world_db   = $db['world'];
$realmName  = $realmDbMap[$realmId]['label'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
        $db['user'], $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("<b>Database connection failed:</b> " . htmlspecialchars($e->getMessage()));
}