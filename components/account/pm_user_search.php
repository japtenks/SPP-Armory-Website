<?php
require_once(__DIR__ . '/../../config/config-protected.php');
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

try {
    $realmId = spp_resolve_realm_id($realmDbMap);
    $REALMD = spp_get_pdo('realmd', $realmId);
} catch (Throwable $e) {
    error_log('[pm_user_search] Failed opening realmd connection: ' . $e->getMessage());
    echo json_encode([]);
    exit;
}

// Query player accounts (excluding bots)
$stmt = $REALMD->prepare("
    SELECT username
    FROM account
    WHERE username LIKE ?
      AND username NOT LIKE 'RNDBOT%%'
      AND username NOT LIKE 'AIBOT%%'
      AND username NOT LIKE 'NPC%%'
    ORDER BY username ASC
    LIMIT 10
");
$stmt->execute([$q . '%']);
$names = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($names);
?>
