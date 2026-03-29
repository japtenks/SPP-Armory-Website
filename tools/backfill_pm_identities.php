<?php
declare(strict_types=1);

// ============================================================
// backfill_pm_identities.php
// ============================================================
// Populates sender_identity_id and recipient_identity_id on
// existing website_pms rows that currently have NULL identities.
//
// Usage:
//   php tools/backfill_pm_identities.php [--dry-run] [--realm=1,2,3]
//
// Safe to re-run — only touches rows where identity IDs are NULL.
// ============================================================

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;
require_once($siteRoot . '/config/config-protected.php');

// ---- Parse args ----
$dryRun      = in_array('--dry-run', $argv, true);
$realmFilter = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--realm=') === 0) {
        $realmFilter = array_map('intval', explode(',', substr($arg, 8)));
    }
}

$realms = array_keys($realmDbMap);
if ($realmFilter !== null) {
    $realms = array_values(array_intersect($realms, $realmFilter));
}

if (empty($realms)) {
    fwrite(STDERR, "No matching realms found.\n");
    exit(1);
}

$masterPdo = spp_get_pdo('realmd', 1);

function log_line(string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

// Build account_id → identity_id map for one realm from website_identities.
function load_account_identity_map(PDO $master, int $realmId): array {
    $stmt = $master->prepare("
        SELECT owner_account_id, identity_id
        FROM `website_identities`
        WHERE realm_id = ? AND identity_type = 'account' AND owner_account_id IS NOT NULL
    ");
    $stmt->execute([$realmId]);
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int)$row['owner_account_id']] = (int)$row['identity_id'];
    }
    return $map;
}

$totals = ['updated' => 0, 'missing_identity' => 0];

foreach ($realms as $realmId) {
    log_line("=== Realm {$realmId} ===");

    try {
        $realmPdo = spp_get_pdo('realmd', $realmId);
    } catch (Exception $e) {
        log_line("  SKIP: cannot connect to realmd for realm {$realmId}: " . $e->getMessage());
        continue;
    }

    $identityMap = load_account_identity_map($masterPdo, $realmId);
    log_line("  Loaded " . count($identityMap) . " account identities for realm {$realmId}.");

    // Fetch PMs missing either identity column.
    try {
        $stmt = $realmPdo->query("
            SELECT id, sender_id, owner_id
            FROM `website_pms`
            WHERE sender_identity_id IS NULL OR recipient_identity_id IS NULL
        ");
        $pms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        log_line("  ERROR fetching PMs: " . $e->getMessage());
        continue;
    }

    log_line("  Found " . count($pms) . " PMs to process.");

    $updated = 0;
    $missing = 0;
    foreach ($pms as $pm) {
        $senderId  = (int)$pm['sender_id'];
        $ownerId   = (int)$pm['owner_id'];
        $senderIdentId    = $identityMap[$senderId]  ?? null;
        $recipientIdentId = $identityMap[$ownerId]   ?? null;

        if ($senderIdentId === null && $recipientIdentId === null) {
            $missing++;
            continue;
        }

        if (!$dryRun) {
            $upd = $realmPdo->prepare("
                UPDATE `website_pms`
                SET sender_identity_id    = COALESCE(sender_identity_id, ?),
                    recipient_identity_id = COALESCE(recipient_identity_id, ?)
                WHERE id = ?
                LIMIT 1
            ");
            $upd->execute([$senderIdentId, $recipientIdentId, (int)$pm['id']]);
        }
        $updated++;
    }

    log_line("  PMs updated: {$updated}, missing identity (account not in website_identities): {$missing}.");
    $totals['updated']          += $updated;
    $totals['missing_identity'] += $missing;
}

log_line("=== Done ===");
log_line("  PMs updated         : " . $totals['updated']);
log_line("  Missing identity    : " . $totals['missing_identity']);
if ($dryRun) {
    log_line("  (dry-run — nothing was written)");
}
