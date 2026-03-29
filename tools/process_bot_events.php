<?php
declare(strict_types=1);

// ============================================================
// process_bot_events.php
// ============================================================
// Reads pending rows from website_bot_events and posts them
// as forum topics authored by a bot character identity.
//
// Usage:
//   php tools/process_bot_events.php [--dry-run] [--limit=20]
//   php tools/process_bot_events.php [--event=level_up,guild_created]
//   php tools/process_bot_events.php [--skip-all]  (mark all pending as skipped)
//
// Run after scan_bot_events.php.
// ============================================================

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/config/bot_event_config.php');
require_once($siteRoot . '/components/forum/forum.func.php');
require_once($siteRoot . '/tools/guild_json.php');

// ---- Parse args ----
$dryRun      = in_array('--dry-run', $argv, true);
$skipAll     = in_array('--skip-all', $argv, true);
$limit       = 50;
$eventFilter = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0)  $limit = max(1, (int)substr($arg, 8));
    if (strpos($arg, '--event=') === 0)  $eventFilter = explode(',', substr($arg, 8));
}

$masterPdo = spp_get_pdo('realmd', 1);

function log_line(string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

function get_forum_last_post_time(int $realmId, int $forumId): int {
    static $cache = [];

    $cacheKey = $realmId . ':' . $forumId;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    try {
        $pdo = spp_get_pdo('realmd', $realmId);
        $stmt = $pdo->prepare("
            SELECT last_post
            FROM `f_topics`
            WHERE forum_id = ?
            ORDER BY last_post DESC, topic_id DESC
            LIMIT 1
        ");
        $stmt->execute([$forumId]);
        return $cache[$cacheKey] = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[process_bot_events] Last post lookup failed: ' . $e->getMessage());
        return $cache[$cacheKey] = 0;
    }
}

function build_batch_post_schedule(array $events): array {
    $schedule = [];
    if (empty($events)) {
        return $schedule;
    }

    $grouped = [];
    foreach ($events as $event) {
        $realmId = (int)($event['realm_id'] ?? 0);
        $forumId = (int)($event['target_forum_id'] ?? 0);
        $grouped[$realmId . ':' . $forumId][] = $event;
    }

    $now = time();
    foreach ($grouped as $groupEvents) {
        usort($groupEvents, static function (array $a, array $b): int {
            $aOccurred = strtotime((string)($a['occurred_at'] ?? '')) ?: 0;
            $bOccurred = strtotime((string)($b['occurred_at'] ?? '')) ?: 0;
            if ($aOccurred === $bOccurred) {
                return (int)$a['event_id'] <=> (int)$b['event_id'];
            }
            return $aOccurred <=> $bOccurred;
        });

        $firstEvent = $groupEvents[0];
        $realmId = (int)($firstEvent['realm_id'] ?? 0);
        $forumId = (int)($firstEvent['target_forum_id'] ?? 0);
        $count = count($groupEvents);
        $lastPostTime = get_forum_last_post_time($realmId, $forumId);
        $startTime = ($lastPostTime > 0)
            ? ($lastPostTime + 1)
            : max(1, $now - max(60, $count * 90));
        $endTime = max($now, $startTime);
        $window = max(0, $endTime - $startTime);

        if ($count === 1 || $window <= 1) {
            $cursor = max($startTime, min($endTime, $now));
            foreach ($groupEvents as $event) {
                $schedule[(int)$event['event_id']] = $cursor;
                $cursor++;
            }
            continue;
        }

        $offsets = [];
        for ($i = 0; $i < $count; $i++) {
            $minOffset = (int)floor(($window * $i) / $count);
            $maxOffset = (int)floor(($window * ($i + 1)) / $count) - 1;
            if ($i === $count - 1) {
                $maxOffset = $window;
            }
            if ($maxOffset < $minOffset) {
                $maxOffset = $minOffset;
            }

            $offsets[] = ($maxOffset > $minOffset)
                ? random_int($minOffset, $maxOffset)
                : $minOffset;
        }

        sort($offsets, SORT_NUMERIC);
        foreach ($groupEvents as $index => $event) {
            $schedule[(int)$event['event_id']] = $startTime + $offsets[$index];
        }
    }

    return $schedule;
}

// ---- --skip-all mode ----
if ($skipAll) {
    if (!$dryRun) {
        $masterPdo->exec("UPDATE `website_bot_events` SET status='skipped' WHERE status='pending'");
    }
    log_line($dryRun ? "[dry-run] Would mark all pending events as skipped." : "Marked all pending events as skipped.");
    exit(0);
}

// ---- Load pending events ----
$eventTypeSql = '';
$eventTypeParams = [];
if ($eventFilter !== null) {
    $ph = implode(',', array_fill(0, count($eventFilter), '?'));
    $eventTypeSql   = "AND event_type IN ({$ph})";
    $eventTypeParams = $eventFilter;
}

$stmt = $masterPdo->prepare("
    SELECT * FROM `website_bot_events`
    WHERE status = 'pending'
    {$eventTypeSql}
    ORDER BY occurred_at ASC
    LIMIT " . (int)$limit . "
");
$stmt->execute($eventTypeParams);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

log_line("Found " . count($events) . " pending events.");
$scheduledPostTimes = build_batch_post_schedule($events);

// ---- Herald cache: realm_id => identity row ----
$heraldCache = [];
function get_realm_herald(int $realmId, PDO $masterPdo): ?array {
    global $heraldCache;
    if (isset($heraldCache[$realmId])) return $heraldCache[$realmId];
    $stmt = $masterPdo->prepare("
        SELECT identity_id, character_guid, owner_account_id, display_name
        FROM `website_identities`
        WHERE realm_id = ? AND identity_type = 'bot_character' AND is_active = 1
        ORDER BY identity_id ASC
        LIMIT 1
    ");
    $stmt->execute([$realmId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $heraldCache[$realmId] = $row;
    return $row;
}

// ---- Content templates ----
function build_post_content(array $event): array {
    $payload = json_decode($event['payload_json'], true) ?? [];
    $type    = $event['event_type'];

    if ($type === 'level_up') {
        $name  = htmlspecialchars($payload['char_name'] ?? 'Someone');
        $level = (int)($payload['level'] ?? 0);
        $max   = in_array($payload['expansion'] ?? '', ['classic']) ? 60
               : (($payload['expansion'] ?? '') === 'tbc' ? 70 : 80);
        if ($level >= $max) {
            return [
                'title' => "{$name} has reached the maximum level!",
                'body'  => "{$name} has achieved level {$level} — the pinnacle of power on this realm. A legendary feat!",
            ];
        }
        return [
            'title' => "{$name} reached level {$level}!",
            'body'  => "Congratulations to {$name} for reaching level {$level}! Keep going, adventurer.",
        ];
    }

    if ($type === 'guild_created') {
        $guild  = trim((string)($payload['guild_name']  ?? 'A new guild'));
        $leader = trim((string)($payload['leader_name'] ?? 'Unknown'));
        return [
            'title' => "<{$guild}> is Recruiting!",
            'body'  => "[b]<{$guild}>[/b] is now open for recruitment!\n\n"
                     . "Led by [b]{$leader}[/b], we are looking for dedicated adventurers to join our ranks.\n\n"
                     . "Contact [b]{$leader}[/b] in-game to apply.",
        ];
    }

    if ($type === 'profession_milestone') {
        $name  = htmlspecialchars($payload['char_name']   ?? 'Someone');
        $skill = htmlspecialchars($payload['skill_name']  ?? 'a profession');
        $value = (int)($payload['skill_value'] ?? 0);
        if ($value >= 300) {
            return [
                'title' => "{$name} has mastered {$skill}!",
                'body'  => "{$name} has reached Grand Master level (300) in {$skill}. A true artisan!",
            ];
        }
        return [
            'title' => "{$name} reached {$value} in {$skill}",
            'body'  => "{$name} has reached skill level {$value} in {$skill}.",
        ];
    }

    if ($type === 'guild_roster_update') {
        $guild       = htmlspecialchars($payload['guild_name']  ?? 'Our guild');
        $memberCount = (int)($payload['member_count'] ?? 0);
        $joinedCount = (int)($payload['joined_count'] ?? 0);
        $leftCount   = (int)($payload['left_count']   ?? 0);
        $joinedNames = array_filter((array)($payload['joined_names'] ?? []));

        $lines = ["<b>Guild Roster Update — {$guild}</b>", ''];

        if ($joinedCount > 0) {
            if (!empty($joinedNames) && count($joinedNames) <= 5) {
                $nameList = implode(', ', array_map('htmlspecialchars', array_values($joinedNames)));
                if ($joinedCount > count($joinedNames)) {
                    $nameList .= ' and ' . ($joinedCount - count($joinedNames)) . ' more';
                }
                $lines[] = "New recruits: {$nameList}.";
            } else {
                $lines[] = "Welcome to {$joinedCount} new members who have joined our ranks!";
            }
        }

        if ($leftCount > 0) {
            $lines[] = $leftCount === 1
                ? "1 member has departed since our last update."
                : "{$leftCount} members have departed since our last update.";
        }

        $lines[] = '';
        if ($memberCount > 0) {
            $lines[] = "Current roster: <b>{$memberCount}</b> members.";
        }
        $lines[] = "We are always looking for dedicated adventurers — whisper our leadership in-game to apply!";

        return ['title' => null, 'body' => implode("\n", $lines)];
    }

    return ['title' => 'Server Event', 'body' => json_encode($payload)];
}

// ---- Post a reply into an existing forum topic as a bot identity ----
function post_forum_reply(
    int    $realmId,
    int    $topicId,
    array  $identity,
    string $body,
    string $contentSource,
    int    $postTime,
    bool   $dryRun
): int {
    if ($dryRun) {
        log_line("    [dry-run] Would reply to topic #{$topicId} at " . date('Y-m-d H:i:s', $postTime) . " as {$identity['display_name']}");
        return -1;
    }

    try {
        $pdo        = spp_get_pdo('realmd', $realmId);
        $posterName = $identity['display_name'];
        $posterId   = (int)($identity['owner_account_id'] ?? 0);
        $charGuid   = (int)($identity['character_guid']   ?? 0);
        $identityId = (int)$identity['identity_id'];

        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO `f_posts`
              (poster, poster_id, poster_character_id, poster_identity_id, poster_ip,
               message, posted, topic_id, content_source)
            VALUES (?, ?, ?, ?, '', ?, ?, ?, ?)
        ")->execute([
            $posterName, $posterId, $charGuid, $identityId,
            nl2br(htmlspecialchars($body)),
            $postTime, $topicId,
            $contentSource,
        ]);
        $postId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            UPDATE `f_topics`
            SET last_post = ?, last_post_id = ?, last_poster = ?,
                num_replies = num_replies + 1, last_bumped_at = ?
            WHERE topic_id = ?
        ")->execute([$postTime, $postId, $posterName, $postTime, $topicId]);

        $forumStmt = $pdo->prepare("SELECT forum_id FROM `f_topics` WHERE topic_id = ?");
        $forumStmt->execute([$topicId]);
        $forumId = (int)$forumStmt->fetchColumn();
        if ($forumId > 0) {
            $pdo->prepare("
                UPDATE `f_forums`
                SET num_posts = num_posts + 1, last_topic_id = ?
                WHERE forum_id = ?
            ")->execute([$topicId, $forumId]);

            if (function_exists('spp_increment_forum_unread')) {
                spp_increment_forum_unread($pdo, $forumId, $posterId);
            }
        }

        $pdo->commit();
        return $postId;
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[process_bot_events] Reply failed: ' . $e->getMessage());
        throw $e;
    }
}

// ---- Post a forum topic as a bot identity ----
function post_forum_topic(
    int    $realmId,
    int    $forumId,
    array  $identity,  // website_identities row
    string $title,
    string $body,
    string $contentSource,
    array  $topicExtras, // guild_id, managed_by_account_id, recruitment_status, last_bumped_at
    int    $postTime,
    bool   $dryRun
): ?int {
    if ($dryRun) {
        log_line("    [dry-run] Would post to forum {$forumId} at " . date('Y-m-d H:i:s', $postTime) . ": \"{$title}\" as {$identity['display_name']}");
        return -1;
    }

    try {
        $pdo      = spp_get_pdo('realmd', $realmId);
        $posterName = $identity['display_name'];
        $posterId    = (int)($identity['owner_account_id'] ?? 0);
        $charGuid    = (int)($identity['character_guid']   ?? 0);
        $identityId  = (int)$identity['identity_id'];

        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO `f_topics`
              (topic_poster, topic_poster_id, topic_poster_identity_id, topic_name, topic_posted, forum_id,
               content_source,
               guild_id, managed_by_account_id, recruitment_status, last_bumped_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $posterName, $posterId, $identityId,
            $title, $postTime, $forumId,
            $contentSource,
            $topicExtras['guild_id']              ?? null,
            $topicExtras['managed_by_account_id'] ?? null,
            $topicExtras['recruitment_status']    ?? null,
            $topicExtras['last_bumped_at']        ?? null,
        ]);
        $topicId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO `f_posts`
              (poster, poster_id, poster_character_id, poster_identity_id, poster_ip,
               message, posted, topic_id, content_source)
            VALUES (?, ?, ?, ?, '', ?, ?, ?, ?)
        ")->execute([
            $posterName, $posterId, $charGuid, $identityId,
            $body,
            $postTime, $topicId,
            $contentSource,
        ]);
        $postId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            UPDATE `f_topics`
            SET last_post = ?, last_post_id = ?, last_poster = ?
            WHERE topic_id = ?
        ")->execute([$postTime, $postId, $posterName, $topicId]);

        $pdo->prepare("
            UPDATE `f_forums`
            SET num_topics = num_topics + 1, num_posts = num_posts + 1, last_topic_id = ?
            WHERE forum_id = ?
        ")->execute([$topicId, $forumId]);

        if (function_exists('spp_increment_forum_unread')) {
            spp_increment_forum_unread($pdo, $forumId, $posterId);
        }

        $pdo->commit();
        return $topicId;
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[process_bot_events] Post failed: ' . $e->getMessage());
        throw $e;
    }
}

// ---- Process each event ----
$results = ['posted' => 0, 'skipped' => 0, 'failed' => 0];

foreach ($events as $event) {
    $eventId   = (int)$event['event_id'];
    $realmId   = (int)$event['realm_id'];
    $eventType = $event['event_type'];
    $forumId   = (int)($event['target_forum_id'] ?? 0);
    $payload   = json_decode($event['payload_json'], true) ?? [];

    log_line("Processing event #{$eventId} [{$eventType}] realm {$realmId}...");

    if ($forumId <= 0) {
        log_line("  SKIP: no target forum configured.");
        if (!$dryRun) {
            $masterPdo->prepare("UPDATE `website_bot_events` SET status='skipped', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);
        }
        $results['skipped']++;
        continue;
    }

    // ---- guild_roster_update: reply into an existing recruitment thread ----
    if ($eventType === 'guild_roster_update') {
        $guildId      = (int)($event['guild_id'] ?? 0);
        $guildSummary = $guildId > 0 ? guild_json_read($realmId, $guildId) : null;

        // Resolve posting identity: guild leader → marked officer → realm herald
        $identity   = null;
        $leaderGuid = (int)($payload['leader_guid'] ?? ($guildSummary['posting_identity']['leader_guid'] ?? 0));
        $idStmt     = $masterPdo->prepare("SELECT * FROM `website_identities` WHERE identity_key = ? LIMIT 1");
        if ($leaderGuid > 0) {
            $idStmt->execute(["char:{$realmId}:{$leaderGuid}"]);
            $identity = $idStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$identity && !empty($guildSummary['posting_identity']['officer_guids'])) {
            foreach ($guildSummary['posting_identity']['officer_guids'] as $og) {
                $idStmt->execute(["char:{$realmId}:{$og}"]);
                $identity = $idStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($identity) break;
            }
        }
        if (!$identity) {
            $identity = get_realm_herald($realmId, $masterPdo);
        }
        if (!$identity) {
            log_line("  SKIP: no usable identity for guild {$guildId}.");
            if (!$dryRun) {
                $masterPdo->prepare("UPDATE `website_bot_events` SET status='skipped', error_message='no identity', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);
            }
            $results['skipped']++;
            continue;
        }

        // Find the active recruitment thread (payload hint → DB lookup)
        $threadTopicId = (int)($payload['thread_topic_id'] ?? 0);
        if ($threadTopicId <= 0 && $guildId > 0) {
            try {
                $realmPdo  = spp_get_pdo('realmd', $realmId);
                $tstmt     = $realmPdo->prepare("
                    SELECT topic_id FROM `f_topics`
                    WHERE guild_id = ? AND recruitment_status = 'active'
                    ORDER BY topic_id ASC LIMIT 1
                ");
                $tstmt->execute([$guildId]);
                $threadTopicId = (int)$tstmt->fetchColumn();
            } catch (Throwable $e) {
                log_line("  ERROR looking up recruitment thread: " . $e->getMessage());
            }
        }
        if ($threadTopicId <= 0) {
            log_line("  SKIP: no active recruitment thread for guild {$guildId}.");
            if (!$dryRun) {
                $masterPdo->prepare("UPDATE `website_bot_events` SET status='skipped', error_message='no recruitment thread', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);
            }
            $results['skipped']++;
            continue;
        }

        $content           = build_post_content($event);
        $scheduledPostTime = (int)($scheduledPostTimes[$eventId] ?? time());

        try {
            $postId = post_forum_reply(
                $realmId, $threadTopicId, $identity,
                $content['body'], 'system_event', $scheduledPostTime, $dryRun
            );

            if (!$dryRun) {
                $masterPdo->prepare("UPDATE `website_bot_events` SET status='posted', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);

                // Refresh the guild roster snapshot so the next scan sees a clean baseline.
                if ($guildSummary !== null) {
                    try {
                        $charPdo = spp_get_pdo('chars', $realmId);
                        $mstmt   = $charPdo->prepare("SELECT memberGuid FROM `guild_member` WHERE guildId = ?");
                        $mstmt->execute([$guildId]);
                        $newGuids = array_values(array_map('intval', array_column($mstmt->fetchAll(PDO::FETCH_ASSOC), 'memberGuid')));
                    } catch (Throwable $e) {
                        $newGuids = $guildSummary['roster']['member_guids'] ?? [];
                    }
                    $guildSummary['thread_topic_id']        = $threadTopicId;
                    $guildSummary['roster']['member_guids'] = $newGuids;
                    $guildSummary['roster']['member_count'] = count($newGuids);
                    $guildSummary['roster']['captured_at']  = date('Y-m-d H:i:s');
                    $guildSummary['last_forum_roster_post'] = [
                        'post_id'      => $postId,
                        'posted_at'    => date('Y-m-d H:i:s', $scheduledPostTime),
                        'joined_count' => (int)($payload['joined_count'] ?? 0),
                        'left_count'   => (int)($payload['left_count']   ?? 0),
                    ];
                    $guildSummary['pending_delta'] = ['joined_guids' => [], 'left_guids' => []];
                    guild_json_write($realmId, $guildId, $guildSummary);
                }
            }

            log_line("  Replied to topic #{$threadTopicId} (post #{$postId}) at " . date('Y-m-d H:i:s', $scheduledPostTime) . " as {$identity['display_name']}");
            $results['posted']++;
        } catch (Throwable $e) {
            log_line("  FAILED: " . $e->getMessage());
            if (!$dryRun) {
                $masterPdo->prepare("UPDATE `website_bot_events` SET status='failed', error_message=?, processed_at=NOW() WHERE event_id=?")->execute([substr($e->getMessage(), 0, 255), $eventId]);
            }
            $results['failed']++;
        }
        continue;
    }

    // ---- All other event types: create a new forum topic ----

    // For guild_created: try to use guild leader's character identity as poster.
    // For all others: use the realm herald (bot character).
    $identity = null;
    if ($eventType === 'guild_created' && !empty($event['character_guid'])) {
        $identKey = "char:{$realmId}:{$event['character_guid']}";
        $idStmt = $masterPdo->prepare("SELECT * FROM `website_identities` WHERE identity_key = ? LIMIT 1");
        $idStmt->execute([$identKey]);
        $identity = $idStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$identity) {
        $identity = get_realm_herald($realmId, $masterPdo);
    }

    if (!$identity) {
        log_line("  SKIP: no usable identity found for realm {$realmId}.");
        if (!$dryRun) {
            $masterPdo->prepare("UPDATE `website_bot_events` SET status='skipped', error_message='no identity', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);
        }
        $results['skipped']++;
        continue;
    }

    $content       = build_post_content($event);
    $contentSource = ($eventType === 'guild_created') ? 'system_event' : 'bot_generated';
    $scheduledPostTime = (int)($scheduledPostTimes[$eventId] ?? time());

    // Extra columns for guild_created topics.
    $topicExtras = [];
    if ($eventType === 'guild_created' && !empty($event['guild_id'])) {
        $topicExtras = [
            'guild_id'              => (int)$event['guild_id'],
            'managed_by_account_id' => (int)($event['account_id'] ?? 0) ?: null,
            'recruitment_status'    => 'active',
            'last_bumped_at'        => $scheduledPostTime,
        ];
    }

    try {
        $topicId = post_forum_topic(
            $realmId, $forumId, $identity,
            $content['title'], $content['body'],
            $contentSource, $topicExtras, $scheduledPostTime, $dryRun
        );

        if (!$dryRun) {
            $masterPdo->prepare("
                UPDATE `website_bot_events`
                SET status='posted', processed_at=NOW()
                WHERE event_id=?
            ")->execute([$eventId]);
        }

        log_line("  Posted topic #{$topicId} at " . date('Y-m-d H:i:s', $scheduledPostTime) . ": \"{$content['title']}\" (as {$identity['display_name']})");
        $results['posted']++;
    } catch (Throwable $e) {
        log_line("  FAILED: " . $e->getMessage());
        if (!$dryRun) {
            $masterPdo->prepare("
                UPDATE `website_bot_events`
                SET status='failed', error_message=?, processed_at=NOW()
                WHERE event_id=?
            ")->execute([substr($e->getMessage(), 0, 255), $eventId]);
        }
        $results['failed']++;
    }
}

log_line("=== Done ===");
log_line("  Posted  : " . $results['posted']);
log_line("  Skipped : " . $results['skipped']);
log_line("  Failed  : " . $results['failed']);
if ($dryRun) {
    log_line("  (dry-run — nothing was written)");
}
