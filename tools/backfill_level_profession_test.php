<?php
declare(strict_types=1);

// ============================================================
// tools/backfill_level_profession_test.php
// ============================================================
// Injects level_up and profession_milestone test events drawn
// from actual character data, including bot (RNDBOT) accounts.
//
// On SPP servers bots are the entire population, so the normal
// scanner's RNDBOT filter produces nothing. This script samples
// a small number of real characters per milestone for testing.
//
// Usage:
//   php tools/backfill_level_profession_test.php [--realm=1] [--dry-run]
//   php tools/backfill_level_profession_test.php [--per-milestone=3]
//   php tools/backfill_level_profession_test.php [--event=level_up]
//   php tools/backfill_level_profession_test.php [--event=profession_milestone]
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
$dryRun       = in_array('--dry-run', $argv, true);
$realmId      = 1;
$perMilestone = 3;
$eventFilter  = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--realm=')         === 0) $realmId      = (int)substr($arg, 8);
    if (strpos($arg, '--per-milestone=') === 0) $perMilestone = max(1, (int)substr($arg, 16));
    if (strpos($arg, '--event=')         === 0) $eventFilter  = explode(',', substr($arg, 8));
}

if (!isset($realmDbMap[$realmId])) {
    fwrite(STDERR, "Unknown realm {$realmId}.\n");
    exit(1);
}

function log_line(string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

function should_run(string $type, ?array $filter): bool {
    return $filter === null || in_array($type, $filter, true);
}

$expansion      = $botEventConfig['realm_expansion'][$realmId]  ?? 'classic';
$forums         = $botEventConfig['forum_targets'][$realmId]     ?? [];
$realmdDbName   = $realmDbMap[$realmId]['realmd'] ?? null;

try {
    $charPdo   = spp_get_pdo('chars',  $realmId);
    $masterPdo = spp_get_pdo('realmd', 1);
} catch (Exception $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n");
    exit(1);
}

$totals = ['level_up' => 0, 'profession_milestone' => 0];

// ----------------------------------------------------------------
// Level milestones
// ----------------------------------------------------------------
if (should_run('level_up', $eventFilter)) {
    $milestones  = $botEventConfig['level_milestones'][$expansion] ?? [];
    $targetForum = $forums['level_up'] ?? null;

    if (empty($milestones) || !$targetForum) {
        log_line("level_up: no milestones or forum target configured — skipping.");
    } else {
        log_line("Backfilling level_up milestones: " . implode(', ', $milestones) . " (up to {$perMilestone} per level)...");

        foreach ($milestones as $level) {
            try {
                $stmt = $charPdo->prepare("
                    SELECT c.guid, c.name, c.level
                    FROM `characters` c
                    WHERE c.level = ?
                      AND NOT (c.level = 1 AND c.xp = 0)
                    ORDER BY RAND()
                    LIMIT " . (int)$perMilestone . "
                ");
                $stmt->execute([$level]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $inserted = 0;
                foreach ($rows as $row) {
                    $dedupeKey = "level_up:realm{$realmId}:char{$row['guid']}:level{$row['level']}:backfill";
                    if ($dryRun) {
                        log_line("  [dry-run] Would queue: {$row['name']} (level {$row['level']})");
                        $inserted++;
                        continue;
                    }
                    $stmt2 = $masterPdo->prepare("
                        INSERT IGNORE INTO `website_bot_events`
                          (event_type, realm_id, character_guid,
                           payload_json, dedupe_key, target_forum_id, occurred_at, status)
                        VALUES ('level_up', ?, ?, ?, ?, ?, NOW(), 'pending')
                    ");
                    $stmt2->execute([
                        $realmId,
                        (int)$row['guid'],
                        json_encode([
                            'char_name' => $row['name'],
                            'level'     => (int)$row['level'],
                            'expansion' => $expansion,
                        ]),
                        $dedupeKey,
                        (int)$targetForum,
                    ]);
                    if ($stmt2->rowCount() > 0) $inserted++;
                }
                log_line("  Level {$level}: " . count($rows) . " found, {$inserted} queued.");
                $totals['level_up'] += $inserted;
            } catch (Exception $e) {
                log_line("  Level {$level}: ERROR — " . $e->getMessage());
            }
        }
    }
}

// ----------------------------------------------------------------
// Profession milestones
// ----------------------------------------------------------------
if (should_run('profession_milestone', $eventFilter)) {
    $professions  = $botEventConfig['professions'];
    $milestones   = $botEventConfig['profession_milestones'];
    $targetForum  = $forums['profession_milestone'] ?? null;

    if (empty($professions) || empty($milestones) || !$targetForum) {
        log_line("profession_milestone: no professions or forum target configured — skipping.");
    } else {
        log_line("Backfilling profession milestones (up to {$perMilestone} per skill/value pair)...");

        $skillIds    = array_keys($professions);
        $skillPh     = implode(',', array_fill(0, count($skillIds),  '?'));
        $milestonePh = implode(',', array_fill(0, count($milestones), '?'));

        foreach ($milestones as $milestoneValue) {
            try {
                $stmt = $charPdo->prepare("
                    SELECT c.guid, c.name, cs.skill, cs.value
                    FROM `character_skills` cs
                    JOIN `characters` c ON c.guid = cs.guid
                    WHERE cs.skill IN ({$skillPh})
                      AND cs.value = ?
                    ORDER BY RAND()
                    LIMIT " . ((int)$perMilestone * count($skillIds)) . "
                ");
                $stmt->execute(array_merge($skillIds, [$milestoneValue]));
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Cap to $perMilestone per skill across the result set
                $countBySkill = [];
                $inserted = 0;
                foreach ($rows as $row) {
                    $skillId = (int)$row['skill'];
                    if (($countBySkill[$skillId] ?? 0) >= $perMilestone) continue;
                    $countBySkill[$skillId] = ($countBySkill[$skillId] ?? 0) + 1;

                    $profName  = $professions[$skillId] ?? ('Skill ' . $skillId);
                    $dedupeKey = "profession_milestone:realm{$realmId}:char{$row['guid']}:skill{$skillId}:value{$row['value']}:backfill";

                    if ($dryRun) {
                        log_line("  [dry-run] Would queue: {$row['name']} — {$profName} {$row['value']}");
                        $inserted++;
                        continue;
                    }
                    $stmt2 = $masterPdo->prepare("
                        INSERT IGNORE INTO `website_bot_events`
                          (event_type, realm_id, character_guid,
                           payload_json, dedupe_key, target_forum_id, occurred_at, status)
                        VALUES ('profession_milestone', ?, ?, ?, ?, ?, NOW(), 'pending')
                    ");
                    $stmt2->execute([
                        $realmId,
                        (int)$row['guid'],
                        json_encode([
                            'char_name'   => $row['name'],
                            'skill_id'    => $skillId,
                            'skill_name'  => $profName,
                            'skill_value' => (int)$row['value'],
                        ]),
                        $dedupeKey,
                        (int)$targetForum,
                    ]);
                    if ($stmt2->rowCount() > 0) $inserted++;
                }
                log_line("  Skill value {$milestoneValue}: " . count($rows) . " found, {$inserted} queued.");
                $totals['profession_milestone'] += $inserted;
            } catch (Exception $e) {
                log_line("  Skill value {$milestoneValue}: ERROR — " . $e->getMessage());
            }
        }
    }
}

log_line("=== Done ===");
log_line("  level_up:             " . $totals['level_up']);
log_line("  profession_milestone: " . $totals['profession_milestone']);
if ($dryRun) log_line("  (dry-run — nothing was written)");
else         log_line("  Run process_bot_events.php to post them.");
