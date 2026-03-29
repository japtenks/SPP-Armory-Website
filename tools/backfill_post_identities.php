<?php
declare(strict_types=1);

// ============================================================
// backfill_post_identities.php
// ============================================================
// Populates poster_identity_id on f_posts and
// topic_poster_identity_id on f_topics for all existing rows
// that have a poster_character_id but no identity link yet.
//
// Usage:
//   php tools/backfill_post_identities.php [--dry-run] [--realm=1,2,3]
//
// Safe to re-run — only touches rows where identity_id IS NULL.
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

// Build a char_guid → identity_id map from website_identities for one realm.
function load_identity_map(PDO $master, int $realmId): array {
    $stmt = $master->prepare("
        SELECT character_guid, identity_id
        FROM `website_identities`
        WHERE realm_id = ? AND identity_type IN ('character','bot_character') AND character_guid IS NOT NULL
    ");
    $stmt->execute([$realmId]);
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int)$row['character_guid']] = (int)$row['identity_id'];
    }
    return $map;
}

$totals = ['posts' => 0, 'topics' => 0, 'missing_identity' => 0];

foreach ($realms as $realmId) {
    log_line("=== Realm {$realmId} ===");

    try {
        $realmPdo = spp_get_pdo('realmd', $realmId);
    } catch (Exception $e) {
        log_line("  SKIP: cannot connect to realmd for realm {$realmId}: " . $e->getMessage());
        continue;
    }

    $identityMap = load_identity_map($masterPdo, $realmId);
    log_line("  Loaded " . count($identityMap) . " character identities for realm {$realmId}.");

    // ---- Backfill f_posts ----
    log_line("  Backfilling f_posts...");
    try {
        $stmt = $realmPdo->query("
            SELECT post_id, poster_character_id
            FROM `f_posts`
            WHERE poster_identity_id IS NULL AND poster_character_id IS NOT NULL AND poster_character_id > 0
        ");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updated = 0;
        $missing = 0;
        foreach ($posts as $post) {
            $charGuid   = (int)$post['poster_character_id'];
            $identityId = $identityMap[$charGuid] ?? null;
            if ($identityId === null) {
                $missing++;
                continue;
            }
            if (!$dryRun) {
                $upd = $realmPdo->prepare("UPDATE `f_posts` SET poster_identity_id=? WHERE post_id=? LIMIT 1");
                $upd->execute([$identityId, (int)$post['post_id']]);
            }
            $updated++;
        }
        log_line("  Posts: {$updated} updated, {$missing} missing identity (char not in website_identities).");
        $totals['posts']            += $updated;
        $totals['missing_identity'] += $missing;
    } catch (Exception $e) {
        log_line("  ERROR (f_posts): " . $e->getMessage());
    }

    // ---- Backfill f_topics ----
    log_line("  Backfilling f_topics...");
    try {
        // f_topics has topic_poster_id (account id) but not a direct char guid.
        // Join to f_posts to find the first post's character id for each topic.
        $stmt = $realmPdo->query("
            SELECT t.topic_id, p.poster_character_id
            FROM `f_topics` t
            JOIN `f_posts` p ON p.post_id = (
                SELECT MIN(post_id) FROM `f_posts` WHERE topic_id = t.topic_id
            )
            WHERE t.topic_poster_identity_id IS NULL
              AND p.poster_character_id IS NOT NULL
              AND p.poster_character_id > 0
        ");
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updated = 0;
        $missing = 0;
        foreach ($topics as $topic) {
            $charGuid   = (int)$topic['poster_character_id'];
            $identityId = $identityMap[$charGuid] ?? null;
            if ($identityId === null) {
                $missing++;
                continue;
            }
            if (!$dryRun) {
                $upd = $realmPdo->prepare("UPDATE `f_topics` SET topic_poster_identity_id=? WHERE topic_id=? LIMIT 1");
                $upd->execute([$identityId, (int)$topic['topic_id']]);
            }
            $updated++;
        }
        log_line("  Topics: {$updated} updated, {$missing} missing identity.");
        $totals['topics']           += $updated;
        $totals['missing_identity'] += $missing;
    } catch (Exception $e) {
        log_line("  ERROR (f_topics): " . $e->getMessage());
    }
}

log_line("=== Done ===");
log_line("  Posts updated  : " . $totals['posts']);
log_line("  Topics updated : " . $totals['topics']);
log_line("  Missing identity (char not in website_identities): " . $totals['missing_identity']);
if ($dryRun) {
    log_line("  (dry-run — nothing was written)");
}
