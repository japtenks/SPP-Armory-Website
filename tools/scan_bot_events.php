<?php
declare(strict_types=1);

// ============================================================
// scan_bot_events.php
// ============================================================
// Scans game databases for interesting events and inserts
// rows into website_bot_events (INSERT IGNORE — safe to re-run).
//
// Usage:
//   php tools/scan_bot_events.php [--dry-run] [--realm=1,2,3]
//   php tools/scan_bot_events.php [--event=level_up,guild_created,profession_milestone]
//
// Run this on a cron schedule (e.g., every 10 minutes).
// After scanning, run process_bot_events.php to post results.
// ============================================================

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/config/bot_event_config.php');

// ---- Parse args ----
$dryRun      = in_array('--dry-run', $argv, true);
$realmFilter = null;
$eventFilter = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--realm=') === 0) {
        $realmFilter = array_map('intval', explode(',', substr($arg, 8)));
    }
    if (strpos($arg, '--event=') === 0) {
        $eventFilter = explode(',', substr($arg, 8));
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

function should_scan(string $eventType, ?array $filter): bool {
    return $filter === null || in_array($eventType, $filter, true);
}

function insert_event(PDO $master, array $ev, bool $dryRun): bool {
    if ($dryRun) {
        log_line("    [dry-run] Would insert: {$ev['dedupe_key']}");
        return true;
    }
    $stmt = $master->prepare("
        INSERT IGNORE INTO `website_bot_events`
          (event_type, realm_id, account_id, character_guid, guild_id,
           payload_json, dedupe_key, target_forum_id, occurred_at, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $ev['event_type'],
        $ev['realm_id'],
        $ev['account_id']     ?? null,
        $ev['character_guid'] ?? null,
        $ev['guild_id']       ?? null,
        json_encode($ev['payload']),
        $ev['dedupe_key'],
        $ev['target_forum_id'] ?? null,
        $ev['occurred_at']    ?? date('Y-m-d H:i:s'),
    ]);
    return (bool)$stmt->rowCount();
}

$totals = [];

foreach ($realms as $realmId) {
    log_line("=== Realm {$realmId} ===");

    $expansion   = $botEventConfig['realm_expansion'][$realmId] ?? 'classic';
    $forums      = $botEventConfig['forum_targets'][$realmId]   ?? [];
    $realmdDbName = $realmDbMap[$realmId]['realmd'] ?? null;

    try {
        $charPdo  = spp_get_pdo('chars',  $realmId);
        $realmPdo = spp_get_pdo('realmd', $realmId);
    } catch (Exception $e) {
        log_line("  SKIP: cannot connect to realm {$realmId}: " . $e->getMessage());
        continue;
    }

    // ---- Level milestone scan ----
    if (should_scan('level_up', $eventFilter)) {
        $milestones = $botEventConfig['level_milestones'][$expansion] ?? [];
        if (!empty($milestones) && !empty($forums['level_up'])) {
            log_line("  Scanning level milestones: " . implode(', ', $milestones));
            $placeholders = implode(',', array_fill(0, count($milestones), '?'));
            try {
                $stmt = $charPdo->prepare("
                    SELECT c.guid, c.name, c.level, c.account
                    FROM `characters` c
                    JOIN `{$realmdDbName}`.`account` a ON a.id = c.account
                    WHERE c.level IN ({$placeholders})
                      AND LOWER(a.username) NOT LIKE 'rndbot%'
                ");
                $stmt->execute($milestones);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $inserted = 0;
                foreach ($rows as $row) {
                    $dedupeKey = "level_up:realm{$realmId}:char{$row['guid']}:level{$row['level']}";
                    $added = insert_event($masterPdo, [
                        'event_type'     => 'level_up',
                        'realm_id'       => $realmId,
                        'account_id'     => (int)$row['account'],
                        'character_guid' => (int)$row['guid'],
                        'payload'        => ['char_name' => $row['name'], 'level' => (int)$row['level'], 'expansion' => $expansion],
                        'dedupe_key'     => $dedupeKey,
                        'target_forum_id' => (int)$forums['level_up'],
                    ], $dryRun);
                    if ($added) $inserted++;
                }
                log_line("  Level milestones: " . count($rows) . " found, {$inserted} new events inserted.");
                $totals['level_up'] = ($totals['level_up'] ?? 0) + $inserted;
            } catch (Exception $e) {
                log_line("  ERROR (level_up): " . $e->getMessage());
            }
        }
    }

    // ---- Guild creation scan ----
    if (should_scan('guild_created', $eventFilter)) {
        $targetForum = $forums['guild_created'] ?? null;
        if ($targetForum) {
            log_line("  Scanning guild creation...");
            try {
                $stmt = $charPdo->query("
                    SELECT g.guildid, g.name, g.leaderguid,
                           c.name AS leader_name, c.account AS leader_account
                    FROM `guild` g
                    JOIN `characters` c ON c.guid = g.leaderguid
                ");
                $guilds = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $inserted = 0;
                foreach ($guilds as $guild) {
                    $dedupeKey = "guild_created:realm{$realmId}:guild{$guild['guildid']}";
                    $added = insert_event($masterPdo, [
                        'event_type'  => 'guild_created',
                        'realm_id'    => $realmId,
                        'account_id'  => (int)$guild['leader_account'],
                        'character_guid' => (int)$guild['leaderguid'],
                        'guild_id'    => (int)$guild['guildid'],
                        'payload'     => [
                            'guild_name'   => $guild['name'],
                            'leader_name'  => $guild['leader_name'],
                            'leader_guid'  => (int)$guild['leaderguid'],
                        ],
                        'dedupe_key'      => $dedupeKey,
                        'target_forum_id' => (int)$targetForum,
                    ], $dryRun);
                    if ($added) $inserted++;
                }
                log_line("  Guilds: " . count($guilds) . " found, {$inserted} new events inserted.");
                $totals['guild_created'] = ($totals['guild_created'] ?? 0) + $inserted;
            } catch (Exception $e) {
                log_line("  ERROR (guild_created): " . $e->getMessage());
            }
        }
    }

    // ---- Profession milestone scan ----
    if (should_scan('profession_milestone', $eventFilter)) {
        $professions  = $botEventConfig['professions'];
        $milestones   = $botEventConfig['profession_milestones'];
        $targetForum  = $forums['profession_milestone'] ?? null;

        if (!empty($professions) && !empty($milestones) && $targetForum) {
            log_line("  Scanning profession milestones...");
            try {
                $skillIds    = array_keys($professions);
                $skillPh     = implode(',', array_fill(0, count($skillIds),  '?'));
                $milestonePh = implode(',', array_fill(0, count($milestones),'?'));

                $stmt = $charPdo->prepare("
                    SELECT c.guid, c.name, c.account, cs.skill, cs.value
                    FROM `character_skills` cs
                    JOIN `characters` c ON c.guid = cs.guid
                    JOIN `{$realmdDbName}`.`account` a ON a.id = c.account
                    WHERE cs.skill IN ({$skillPh})
                      AND cs.value IN ({$milestonePh})
                      AND LOWER(a.username) NOT LIKE 'rndbot%'
                ");
                $stmt->execute(array_merge($skillIds, $milestones));
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $inserted = 0;
                foreach ($rows as $row) {
                    $profName  = $professions[(int)$row['skill']] ?? ('Skill ' . $row['skill']);
                    $dedupeKey = "profession_milestone:realm{$realmId}:char{$row['guid']}:skill{$row['skill']}:value{$row['value']}";
                    $added = insert_event($masterPdo, [
                        'event_type'     => 'profession_milestone',
                        'realm_id'       => $realmId,
                        'account_id'     => (int)$row['account'],
                        'character_guid' => (int)$row['guid'],
                        'payload'        => [
                            'char_name'    => $row['name'],
                            'skill_id'     => (int)$row['skill'],
                            'skill_name'   => $profName,
                            'skill_value'  => (int)$row['value'],
                        ],
                        'dedupe_key'      => $dedupeKey,
                        'target_forum_id' => (int)$targetForum,
                    ], $dryRun);
                    if ($added) $inserted++;
                }
                log_line("  Profession milestones: " . count($rows) . " found, {$inserted} new events inserted.");
                $totals['profession_milestone'] = ($totals['profession_milestone'] ?? 0) + $inserted;
            } catch (Exception $e) {
                log_line("  ERROR (profession_milestone): " . $e->getMessage());
            }
        }
    }
}

log_line("=== Scan complete ===");
foreach ($totals as $type => $count) {
    log_line("  {$type}: {$count} new events");
}
if ($dryRun) {
    log_line("  (dry-run — nothing was written)");
}
