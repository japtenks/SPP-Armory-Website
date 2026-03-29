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
require_once($siteRoot . '/tools/guild_json.php');

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

$realms = $botEventConfig['enabled_realms'] ?? array_keys($realmDbMap);
$realms = array_values(array_intersect(array_map('intval', $realms), array_keys($realmDbMap)));
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
    // ---- Guild roster update scan ----
    if (should_scan('guild_roster_update', $eventFilter)) {
        $targetForum  = $forums['guild_roster_update'] ?? null;
        $thresholds   = $botEventConfig['guild_roster_thresholds'] ?? [];
        $minJoins     = (int)($thresholds['min_joins']    ?? 8);
        $cooldownSec  = (int)($thresholds['cooldown_sec'] ?? 43200);

        if ($targetForum) {
            log_line("  Scanning guild roster updates...");
            try {
                $guildsStmt = $charPdo->query("
                    SELECT g.guildid, g.name, g.leaderguid,
                           c.name AS leader_name
                    FROM `guild` g
                    JOIN `characters` c ON c.guid = g.leaderguid
                ");
                $guilds  = $guildsStmt->fetchAll(PDO::FETCH_ASSOC);
                $inserted = 0;

                foreach ($guilds as $guild) {
                    $guildId    = (int)$guild['guildid'];
                    $guildName  = $guild['name'];
                    $leaderGuid = (int)$guild['leaderguid'];
                    $leaderName = $guild['leader_name'];

                    // Load current roster
                    $memberStmt = $charPdo->prepare("
                        SELECT gm.guid AS memberGuid, c.name, c.level
                        FROM `guild_member` gm
                        JOIN `characters` c ON c.guid = gm.guid
                        WHERE gm.guildid = ?
                    ");
                    $memberStmt->execute([$guildId]);
                    $memberRows   = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
                    $currentGuids = array_values(array_map('intval', array_column($memberRows, 'memberGuid')));
                    $nameByGuid   = array_column($memberRows, 'name', 'memberGuid');
                    $currentMemberDetails = [];
                    foreach ($memberRows as $memberRow) {
                        $memberGuid = (int)($memberRow['memberGuid'] ?? 0);
                        if ($memberGuid <= 0) {
                            continue;
                        }
                        $currentMemberDetails[$memberGuid] = [
                            'guid' => $memberGuid,
                            'name' => (string)($memberRow['name'] ?? ('Player #' . $memberGuid)),
                            'level' => (int)($memberRow['level'] ?? 0),
                        ];
                    }

                    $summary = guild_json_read($realmId, $guildId);

                    if ($summary === null) {
                        // First scan: write baseline snapshot, don't fire an event yet.
                        $skeleton = guild_json_skeleton($realmId, $guildId, $guildName, $leaderGuid, $leaderName, $currentGuids, $currentMemberDetails);
                        if (!$dryRun) {
                            guild_json_write($realmId, $guildId, $skeleton);
                        } else {
                            log_line("    [dry-run] Guild '{$guildName}': wrote baseline (" . count($currentGuids) . " members).");
                        }
                        continue;
                    }

                    // Refresh mutable fields in case they changed in-game.
                    $summary['guild_name']                            = $guildName;
                    $summary['posting_identity']['leader_guid']       = $leaderGuid;
                    $summary['posting_identity']['leader_name']       = $leaderName;

                    // Delta vs the roster snapshot at time of last forum post.
                    $prevGuids   = array_values(array_map('intval', $summary['roster']['member_guids'] ?? []));
                    $prevMemberDetails = is_array($summary['roster']['member_details'] ?? null) ? $summary['roster']['member_details'] : [];
                    $joinedGuids = array_values(array_diff($currentGuids, $prevGuids));
                    $leftGuids   = array_values(array_diff($prevGuids, $currentGuids));
                    $joinedCount = count($joinedGuids);
                    $leftCount   = count($leftGuids);
                    $changeDate  = date('M j, Y');

                    // Always update pending_delta so admins can see accumulated drift.
                    $summary['pending_delta'] = [
                        'joined_guids' => $joinedGuids,
                        'left_guids'   => $leftGuids,
                    ];
                    if (!$dryRun) {
                        guild_json_write($realmId, $guildId, $summary);
                    }

                    // Threshold: enough joins OR any leaves
                    $shouldPost = ($joinedCount >= $minJoins || $leftCount > 0);

                    // Cooldown: last post must be far enough in the past
                    $lastPostAt   = $summary['last_forum_roster_post']['posted_at'] ?? null;
                    $lastPostTime = $lastPostAt ? (strtotime($lastPostAt) ?: 0) : 0;
                    $cooldownOk   = (time() - $lastPostTime) >= $cooldownSec;

                    if (!$shouldPost || !$cooldownOk) {
                        if ($dryRun && ($joinedCount > 0 || $leftCount > 0)) {
                            $reason = !$cooldownOk ? 'cooldown not met' : 'threshold not met';
                            log_line("    [dry-run] Guild '{$guildName}': +{$joinedCount}/-{$leftCount} — {$reason}.");
                        }
                        continue;
                    }

                    $joinedNames = array_values(array_filter(
                        array_map(function ($g) use ($nameByGuid) { return $nameByGuid[$g] ?? null; }, array_slice($joinedGuids, 0, 10))
                    ));
                    $joinedMembers = [];
                    foreach ($joinedGuids as $joinedGuid) {
                        $joinedGuid = (int)$joinedGuid;
                        $memberDetail = $currentMemberDetails[$joinedGuid] ?? ['guid' => $joinedGuid, 'name' => 'Unknown', 'level' => 0];
                        $memberDetail['changed_at'] = $changeDate;
                        $joinedMembers[] = $memberDetail;
                    }
                    $leftMembers = [];
                    foreach ($leftGuids as $leftGuid) {
                        $leftGuid = (int)$leftGuid;
                        $memberDetail = $prevMemberDetails[$leftGuid] ?? ['guid' => $leftGuid, 'name' => 'Unknown', 'level' => 0];
                        $memberDetail['changed_at'] = $changeDate;
                        $leftMembers[] = $memberDetail;
                    }
                    $currentMembers = array_values($currentMemberDetails);
                    usort($currentMembers, function ($a, $b) {
                        $levelDiff = (int)($b['level'] ?? 0) <=> (int)($a['level'] ?? 0);
                        if ($levelDiff !== 0) {
                            return $levelDiff;
                        }
                        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
                    });

                    // Dedupe key scoped to one cooldown window so INSERT IGNORE prevents
                    // duplicates if the scanner runs multiple times in the same window.
                    $windowTs  = (int)floor(time() / $cooldownSec) * $cooldownSec;
                    $dedupeKey = "guild_roster_update:realm{$realmId}:guild{$guildId}:w{$windowTs}";

                    $added = insert_event($masterPdo, [
                        'event_type'     => 'guild_roster_update',
                        'realm_id'       => $realmId,
                        'guild_id'       => $guildId,
                        'character_guid' => $leaderGuid,
                        'payload'        => [
                            'guild_name'      => $guildName,
                            'leader_name'     => $leaderName,
                            'leader_guid'     => $leaderGuid,
                            'joined_count'    => $joinedCount,
                            'left_count'      => $leftCount,
                            'joined_names'    => $joinedNames,
                            'joined_members'  => $joinedMembers,
                            'left_members'    => $leftMembers,
                            'current_members' => $currentMembers,
                            'member_count'    => count($currentGuids),
                            'change_date'     => $changeDate,
                            'thread_topic_id' => $summary['thread_topic_id'] ?? null,
                            'roster_post_id'  => $summary['roster_post_id'] ?? null,
                        ],
                        'dedupe_key'      => $dedupeKey,
                        'target_forum_id' => (int)$targetForum,
                    ], $dryRun);
                    if ($added) $inserted++;
                }

                log_line("  Guild roster: " . count($guilds) . " guilds checked, {$inserted} new events queued.");
                $totals['guild_roster_update'] = ($totals['guild_roster_update'] ?? 0) + $inserted;
            } catch (Exception $e) {
                log_line("  ERROR (guild_roster_update): " . $e->getMessage());
            }
        }
    }
    // ---- Achievement badge scan (character_achievement table) ----
    if (should_scan('achievement_badge', $eventFilter)) {
        $targetForum  = $forums['achievement_badge'] ?? null;
        $lookbackDays = (int)($botEventConfig['achievement_lookback_days'] ?? 30);
        $excludeIds   = $botEventConfig['achievement_badge_exclude'] ?? [];
        $featuredIds  = array_map('intval', (array)($botEventConfig['achievement_badge_featured_ids'] ?? []));
        $excludeCategories = array_map('intval', (array)($botEventConfig['achievement_badge_exclude_categories'] ?? []));
        $minPoints = (int)($botEventConfig['achievement_badge_min_points'] ?? 10);
        $minLevel = (int)($botEventConfig['achievement_badge_min_level'] ?? 20);

        if ($targetForum) {
            log_line("  Scanning achievement badges ({$lookbackDays}d window)...");
            try {
                $cutoff    = time() - ($lookbackDays * 86400);
                $excludeSql = '';
                $excludeParams = [];
                if (!empty($excludeIds)) {
                    $exPh        = implode(',', array_fill(0, count($excludeIds), '?'));
                    $excludeSql  = "AND ca.achievement NOT IN ({$exPh})";
                    $excludeParams = $excludeIds;
                }

                $stmt = $charPdo->prepare("
                    SELECT ca.guid, ca.achievement, ca.date, c.name, c.account, c.level
                    FROM `character_achievement` ca
                    JOIN `characters` c ON c.guid = ca.guid
                    WHERE ca.date >= ?
                    {$excludeSql}
                    ORDER BY ca.date ASC
                ");
                $stmt->execute(array_merge([$cutoff], $excludeParams));
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($rows)) {
                    log_line("  No achievement rows found in window.");
                } else {
                    // Build achievement name/category map.
                    // Use the chars PDO with a cross-DB join to achievement_dbc in the world DB
                    // (same DB instance, so cross-schema joins work — matches how manual queries run).
                    $achIds     = array_values(array_unique(array_column($rows, 'achievement')));
                    $achPh      = implode(',', array_fill(0, count($achIds), '?'));
                    $achMeta    = []; // [id => ['name' => ..., 'points' => ..., 'category' => ...]]
                    $worldDbName = $realmDbMap[$realmId]['world'] ?? null;

                    if ($worldDbName) {
                        try {
                            $mStmt = $charPdo->prepare("
                                SELECT a.`ID`, a.`Title_Lang_enUS` AS name,
                                       a.`Points` AS points, a.`Category` AS category
                                FROM `{$worldDbName}`.`achievement_dbc` a
                                WHERE a.`ID` IN ({$achPh})
                            ");
                            $mStmt->execute($achIds);
                            foreach ($mStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                                $achMeta[(int)$r['ID']] = [
                                    'name'     => $r['name'],
                                    'points'   => (int)$r['points'],
                                    'category' => (int)$r['category'],
                                ];
                            }
                        } catch (Throwable $e) {
                            log_line("  WARN: achievement name lookup failed (" . $e->getMessage() . ") — IDs will be used as fallback.");
                        }
                    } else {
                        log_line("  WARN: world DB name not found in realmDbMap — achievement names will fall back to IDs.");
                    }

                    $inserted = 0;
                    $filteredOut = 0;
                    foreach ($rows as $row) {
                        $achId   = (int)$row['achievement'];
                        $meta    = $achMeta[$achId] ?? [];
                        $achName = $meta['name'] ?? ('Achievement #' . $achId);
                        $achPoints = (int)($meta['points'] ?? 0);
                        $achCategory = (int)($meta['category'] ?? 0);
                        $charLevel = (int)($row['level'] ?? 0);
                        $isFeatured = in_array($achId, $featuredIds, true);

                        if (!$isFeatured) {
                            if ($charLevel < $minLevel || $achPoints < $minPoints || in_array($achCategory, $excludeCategories, true)) {
                                $filteredOut++;
                                continue;
                            }
                        }

                        $dedupeKey = "achievement_badge:realm{$realmId}:char{$row['guid']}:achieve{$achId}";
                        $added = insert_event($masterPdo, [
                            'event_type'     => 'achievement_badge',
                            'realm_id'       => $realmId,
                            'account_id'     => (int)$row['account'],
                            'character_guid' => (int)$row['guid'],
                            'payload'        => [
                                'char_name'      => $row['name'],
                                'achievement'    => $achName,
                                'achievement_id' => $achId,
                                'points'         => $achPoints,
                                'category'       => $achCategory,
                                'character_level'=> $charLevel,
                                'badge_type'     => 'achievement',
                            ],
                            'dedupe_key'      => $dedupeKey,
                            'target_forum_id' => (int)$targetForum,
                            'occurred_at'     => date('Y-m-d H:i:s', (int)$row['date']),
                        ], $dryRun);
                        if ($added) $inserted++;
                    }
                    log_line("  Achievement badges: " . count($rows) . " rows found, {$filteredOut} filtered out, {$inserted} new events queued.");
                    $totals['achievement_badge'] = ($totals['achievement_badge'] ?? 0) + $inserted;
                }
            } catch (Exception $e) {
                log_line("  ERROR (achievement_badge): " . $e->getMessage());
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
