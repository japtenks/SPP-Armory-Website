<?php
declare(strict_types=1);

// ============================================================
// tools/backfill_guild_roster_test.php
// ============================================================
// Injects a guild_roster_update test event for one or all guilds
// that already have an active recruitment thread, without waiting
// for real threshold/cooldown conditions.
//
// Usage:
//   php tools/backfill_guild_roster_test.php [--realm=1] [--guild=N] [--dry-run]
//
// What it does per guild:
//   1. Loads the current roster from guild_member.
//   2. Loads (or creates) the guild summary JSON.
//   3. Temporarily treats all current members as "joined" so the
//      processor has a real payload to render.
//   4. Inserts a guild_roster_update event with a unique backfill
//      dedupe key — safe to re-run; duplicate inserts are ignored.
// ============================================================

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/config/bot_event_config.php');
require_once($siteRoot . '/tools/guild_json.php');

// ---- Parse args ----
$dryRun      = in_array('--dry-run', $argv, true);
$realmId     = 1;
$guildFilter = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--realm=') === 0) $realmId     = (int)substr($arg, 8);
    if (strpos($arg, '--guild=') === 0) $guildFilter = (int)substr($arg, 8);
}

if (!isset($realmDbMap[$realmId])) {
    fwrite(STDERR, "Unknown realm {$realmId}.\n");
    exit(1);
}

function log_line(string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

try {
    $charPdo   = spp_get_pdo('chars',  $realmId);
    $realmPdo  = spp_get_pdo('realmd', $realmId);
    $masterPdo = spp_get_pdo('realmd', 1);
} catch (Exception $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Find guilds that have an active recruitment thread.
$guildSql    = "SELECT DISTINCT guild_id FROM `f_topics` WHERE guild_id IS NOT NULL AND recruitment_status = 'active'";
$guildParams = [];
if ($guildFilter !== null) {
    $guildSql   .= " AND guild_id = ?";
    $guildParams = [$guildFilter];
}
$guildRows = $realmPdo->prepare($guildSql);
$guildRows->execute($guildParams);
$eligibleGuildIds = array_column($guildRows->fetchAll(PDO::FETCH_ASSOC), 'guild_id');

if (empty($eligibleGuildIds)) {
    log_line("No guilds with active recruitment threads found on realm {$realmId}.");
    log_line("Run the guild_created event pipeline first, or create a recruitment thread manually.");
    exit(0);
}

log_line("Found " . count($eligibleGuildIds) . " guild(s) with active recruitment threads.");

$forumTargets = $botEventConfig['forum_targets'][$realmId] ?? [];
$targetForum  = (int)($forumTargets['guild_roster_update'] ?? 0);
if ($targetForum <= 0) {
    fwrite(STDERR, "No forum_target configured for guild_roster_update on realm {$realmId}.\n");
    exit(1);
}

$inserted = 0;
$skipped  = 0;

foreach ($eligibleGuildIds as $guildId) {
    $guildId = (int)$guildId;

    // Load guild info
    try {
        $gStmt = $charPdo->prepare("
            SELECT g.guildid, g.name, g.leaderguid, c.name AS leader_name
            FROM `guild` g
            JOIN `characters` c ON c.guid = g.leaderguid
            WHERE g.guildid = ?
        ");
        $gStmt->execute([$guildId]);
        $guild = $gStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        log_line("  Guild {$guildId}: SKIP — DB error: " . $e->getMessage());
        $skipped++;
        continue;
    }

    if (!$guild) {
        log_line("  Guild {$guildId}: SKIP — not found in characters DB.");
        $skipped++;
        continue;
    }

    $guildName  = $guild['name'];
    $leaderGuid = (int)$guild['leaderguid'];
    $leaderName = $guild['leader_name'];

    // Load current roster
    try {
        $mStmt = $charPdo->prepare("
            SELECT gm.guid AS memberGuid, c.name
            FROM `guild_member` gm
            JOIN `characters` c ON c.guid = gm.guid
            WHERE gm.guildid = ?
        ");
        $mStmt->execute([$guildId]);
        $memberRows   = $mStmt->fetchAll(PDO::FETCH_ASSOC);
        $currentGuids = array_values(array_map('intval', array_column($memberRows, 'memberGuid')));
        $nameByGuid   = array_column($memberRows, 'name', 'memberGuid');
    } catch (Exception $e) {
        log_line("  Guild '{$guildName}': SKIP — roster load failed: " . $e->getMessage());
        $skipped++;
        continue;
    }

    if (empty($currentGuids)) {
        log_line("  Guild '{$guildName}': SKIP — no members.");
        $skipped++;
        continue;
    }

    // Ensure guild summary JSON exists with an empty previous roster so
    // all current members appear as "joined" in the test payload.
    $summary = guild_json_read($realmId, $guildId);
    if ($summary === null) {
        $summary = guild_json_skeleton($realmId, $guildId, $guildName, $leaderGuid, $leaderName, []);
    }

    // Pull thread_topic_id from the DB so the processor can reply immediately.
    $tStmt = $realmPdo->prepare("
        SELECT topic_id FROM `f_topics`
        WHERE guild_id = ? AND recruitment_status = 'active'
        ORDER BY topic_id ASC LIMIT 1
    ");
    $tStmt->execute([$guildId]);
    $threadTopicId = (int)$tStmt->fetchColumn();

    $summary['thread_topic_id'] = $threadTopicId ?: null;

    if (!$dryRun) {
        guild_json_write($realmId, $guildId, $summary);
    }

    // Build payload: treat all current members as "joined" for the test.
    $joinedCount = count($currentGuids);
    $joinedNames = array_values(array_slice(array_values($nameByGuid), 0, 10));

    $dedupeKey = "guild_roster_update:realm{$realmId}:guild{$guildId}:backfill:" . date('YmdHis');

    if ($dryRun) {
        log_line("  [dry-run] Guild '{$guildName}' (id {$guildId}): would queue {$joinedCount} members as joined, thread #{$threadTopicId}.");
        $inserted++;
        continue;
    }

    try {
        $stmt = $masterPdo->prepare("
            INSERT IGNORE INTO `website_bot_events`
              (event_type, realm_id, guild_id, character_guid,
               payload_json, dedupe_key, target_forum_id, occurred_at, status)
            VALUES ('guild_roster_update', ?, ?, ?, ?, ?, ?, NOW(), 'pending')
        ");
        $stmt->execute([
            $realmId,
            $guildId,
            $leaderGuid,
            json_encode([
                'guild_name'      => $guildName,
                'leader_name'     => $leaderName,
                'leader_guid'     => $leaderGuid,
                'joined_count'    => $joinedCount,
                'left_count'      => 0,
                'joined_names'    => $joinedNames,
                'member_count'    => $joinedCount,
                'thread_topic_id' => $threadTopicId ?: null,
            ]),
            $dedupeKey,
            $targetForum,
        ]);

        if ($stmt->rowCount() > 0) {
            log_line("  Guild '{$guildName}' (id {$guildId}): queued {$joinedCount} members as joined, thread #{$threadTopicId}.");
            $inserted++;
        } else {
            log_line("  Guild '{$guildName}': duplicate dedupe key — already queued (try again in 1 second).");
            $skipped++;
        }
    } catch (Exception $e) {
        log_line("  Guild '{$guildName}': FAILED — " . $e->getMessage());
        $skipped++;
    }
}

log_line("=== Done === Queued: {$inserted}  Skipped: {$skipped}");
if ($dryRun) log_line("  (dry-run — nothing was written)");
log_line("Run process_bot_events.php --event=guild_roster_update to post the replies.");
