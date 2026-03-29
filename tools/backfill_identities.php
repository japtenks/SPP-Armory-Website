<?php
declare(strict_types=1);

// ============================================================
// backfill_identities.php
// ============================================================
// Populates classicrealmd.website_identities from all realms.
//
// Usage:
//   php tools/backfill_identities.php [--dry-run] [--realm=1,2,3]
//
// Options:
//   --dry-run       Print counts without writing anything.
//   --realm=1,2,3   Comma-separated realm IDs to process.
//                   Defaults to all realms in config.
//
// Safe to re-run — uses INSERT IGNORE on identity_key.
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

// ---- Connect to master realmd (realm 1 = Classic) ----
$masterPdo = spp_get_pdo('realmd', 1);
$masterPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ---- Helpers ----
function log_line(string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

function insert_identity(PDO $master, array $row, bool $dryRun): bool {
    if ($dryRun) return true;
    $stmt = $master->prepare("
        INSERT IGNORE INTO `website_identities`
          (identity_type, owner_account_id, realm_id, character_guid,
           display_name, identity_key, guild_id, is_bot, is_active)
        VALUES
          (:type, :owner, :realm, :guid, :name, :key, :guild, :bot, 1)
    ");
    $stmt->execute([
        ':type'  => $row['identity_type'],
        ':owner' => $row['owner_account_id'],
        ':realm' => $row['realm_id'],
        ':guid'  => $row['character_guid'],
        ':name'  => $row['display_name'],
        ':key'   => $row['identity_key'],
        ':guild' => $row['guild_id'],
        ':bot'   => $row['is_bot'],
    ]);
    return (bool)$stmt->rowCount();
}

// ---- Main loop ----
$totals = ['account' => 0, 'character' => 0, 'bot_character' => 0, 'skipped' => 0];

foreach ($realms as $realmId) {
    log_line("=== Realm {$realmId} ===");

    try {
        $realmPdo = spp_get_pdo('realmd', $realmId);
        $realmPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        log_line("  SKIP: cannot connect to realmd for realm {$realmId}: " . $e->getMessage());
        continue;
    }

    // ---- Account identities ----
    log_line("  Backfilling account identities...");
    try {
        $accounts = $realmPdo->query("
            SELECT a.id, a.username,
                   COALESCE(NULLIF(TRIM(wa.display_name), ''), a.username) AS display_name
            FROM `account` a
            LEFT JOIN `website_accounts` wa ON wa.account_id = a.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $inserted = 0;
        foreach ($accounts as $acc) {
            $added = insert_identity($masterPdo, [
                'identity_type'    => 'account',
                'owner_account_id' => (int)$acc['id'],
                'realm_id'         => $realmId,
                'character_guid'   => null,
                'display_name'     => $acc['display_name'],
                'identity_key'     => "account:{$realmId}:{$acc['id']}",
                'guild_id'         => null,
                'is_bot'           => 0,
            ], $dryRun);
            if ($added) $inserted++;
        }
        $skipped = count($accounts) - $inserted;
        log_line("  Accounts: " . count($accounts) . " found, {$inserted} inserted, {$skipped} already existed.");
        $totals['account']  += $inserted;
        $totals['skipped']  += $skipped;
    } catch (Exception $e) {
        log_line("  ERROR (accounts): " . $e->getMessage());
    }

    // ---- Character identities ----
    log_line("  Backfilling character identities...");
    try {
        $charPdo = spp_get_pdo('chars', $realmId);
        $charPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $characters = $charPdo->query("
            SELECT c.guid, c.account, c.name,
                   gm.guildid
            FROM `characters` c
            LEFT JOIN `guild_member` gm ON gm.guid = c.guid
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Build account->username map for bot detection (avoid per-row queries)
        $accountIds    = array_values(array_unique(array_column($characters, 'account')));
        $placeholders  = implode(',', array_fill(0, count($accountIds), '?'));
        $usernameMap   = [];
        if (!empty($accountIds)) {
            $stmt = $realmPdo->prepare(
                "SELECT id, username FROM `account` WHERE id IN ({$placeholders})"
            );
            $stmt->execute($accountIds);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $usernameMap[(int)$row['id']] = strtolower($row['username']);
            }
        }

        $insertedHuman = $insertedBot = $skippedChar = 0;
        foreach ($characters as $char) {
            $username = $usernameMap[(int)$char['account']] ?? '';
            $isBot    = (strpos($username, 'rndbot') === 0) ? 1 : 0;
            $type     = $isBot ? 'bot_character' : 'character';

            $added = insert_identity($masterPdo, [
                'identity_type'    => $type,
                'owner_account_id' => (int)$char['account'],
                'realm_id'         => $realmId,
                'character_guid'   => (int)$char['guid'],
                'display_name'     => $char['name'],
                'identity_key'     => "char:{$realmId}:{$char['guid']}",
                'guild_id'         => isset($char['guildid']) ? (int)$char['guildid'] : null,
                'is_bot'           => $isBot,
            ], $dryRun);

            if ($added) {
                $isBot ? $insertedBot++ : $insertedHuman++;
            } else {
                $skippedChar++;
            }
        }
        log_line("  Characters: {$insertedHuman} human inserted, {$insertedBot} bot inserted, {$skippedChar} already existed.");
        $totals['character']    += $insertedHuman;
        $totals['bot_character'] += $insertedBot;
        $totals['skipped']      += $skippedChar;
    } catch (Exception $e) {
        log_line("  ERROR (characters): " . $e->getMessage());
    }
}

// ---- Summary ----
log_line("=== Done ===");
log_line("  Account identities inserted : " . $totals['account']);
log_line("  Human character identities  : " . $totals['character']);
log_line("  Bot character identities    : " . $totals['bot_character']);
log_line("  Already existed (skipped)   : " . $totals['skipped']);
if ($dryRun) {
    log_line("  (dry-run — nothing was written)");
}
