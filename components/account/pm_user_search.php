<?php
require_once(__DIR__ . '/../../core/common.php');
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

// Connect to tbcrealmd for account list
$REALMD = DbSimple_Generic::connect("mysqli://root:eltnub@192.168.1.13:3310/tbcrealmd");

// Query player accounts (excluding bots)
$names = $REALMD->selectCol("
    SELECT username
    FROM account
    WHERE username LIKE ?
      AND username NOT LIKE 'RNDBOT%%'
      AND username NOT LIKE 'AIBOT%%'
      AND username NOT LIKE 'NPC%%'
    ORDER BY username ASC
    LIMIT 10
", $q.'%');

echo json_encode($names);
?>
