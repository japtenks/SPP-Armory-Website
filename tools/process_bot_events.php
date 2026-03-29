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

function clamp_live_post_time(int $postTime, bool $dryRun): int {
    if ($dryRun) {
        return $postTime;
    }
    return min($postTime, time());
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
        // If earlier runs wrote future timestamps, don't let them drag
        // the whole forum schedule into the future again.
        if ($lastPostTime >= $now) {
            $lastPostTime = max(0, $now - 1);
        }
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

function build_next_scheduled_post_map(array $events, array $scheduledPostTimes): array {
    $nextScheduledPostMap = [];
    $grouped = [];

    foreach ($events as $event) {
        $eventId = (int)($event['event_id'] ?? 0);
        if ($eventId <= 0 || !isset($scheduledPostTimes[$eventId])) {
            continue;
        }

        $realmId = (int)($event['realm_id'] ?? 0);
        $forumId = (int)($event['target_forum_id'] ?? 0);
        $grouped[$realmId . ':' . $forumId][] = [
            'event_id' => $eventId,
            'post_time' => (int)$scheduledPostTimes[$eventId],
        ];
    }

    foreach ($grouped as $groupEvents) {
        usort($groupEvents, static function (array $a, array $b): int {
            if ($a['post_time'] === $b['post_time']) {
                return $a['event_id'] <=> $b['event_id'];
            }
            return $a['post_time'] <=> $b['post_time'];
        });

        $count = count($groupEvents);
        for ($i = 0; $i < $count; $i++) {
            $eventId = (int)$groupEvents[$i]['event_id'];
            $nextScheduledPostMap[$eventId] = ($i + 1 < $count)
                ? (int)$groupEvents[$i + 1]['post_time']
                : null;
        }
    }

    return $nextScheduledPostMap;
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
$nextScheduledPostMap = build_next_scheduled_post_map($events, $scheduledPostTimes);

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
function build_post_content(array $event, bool $selfPost = false): array {
    $payload = json_decode($event['payload_json'], true) ?? [];
    $type    = $event['event_type'];

    if ($type === 'level_up') {
        $name  = htmlspecialchars($payload['char_name'] ?? 'Someone');
        $level = (int)($payload['level'] ?? 0);
        $max   = in_array($payload['expansion'] ?? '', ['classic']) ? 60
               : (($payload['expansion'] ?? '') === 'tbc' ? 70 : 80);
        if ($selfPost) {
            if ($level >= $max) {
                return [
                    'title' => "Finally hit level {$level}!",
                    'body'  => "I finally did it — level {$level}! Max level at last. Time to start gearing up and getting into the real content. See you out there!",
                ];
            }
            return [
                'title' => "Just hit level {$level}!",
                'body'  => "Dinged level {$level} today. Feeling stronger with every fight — can't wait to see what's next!",
            ];
        }
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
        if ($selfPost) {
            if ($value >= 300) {
                return [
                    'title' => "Grand Master {$skill} — finally!",
                    'body'  => "Just hit 300 in {$skill}! Officially a Grand Master now. Took a lot of materials and patience but totally worth it.",
                ];
            }
            return [
                'title' => "Hit {$value} in {$skill}!",
                'body'  => "Reached {$value} in {$skill} today. Still a ways to go but the progress feels great!",
            ];
        }
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

    if ($type === 'achievement_badge') {
        $name    = htmlspecialchars($payload['char_name']   ?? 'Someone');
        $achieve = htmlspecialchars($payload['achievement'] ?? 'a milestone');
        $points  = (int)($payload['points'] ?? 0);
        $ptStr   = $points > 0 ? " (+{$points} pts)" : '';

        if ($selfPost) {
            return [
                'title' => "Earned: {$achieve}",
                'body'  => "Just earned the [{$achieve}] achievement{$ptStr}. Feels great to get that one!",
            ];
        }
        return [
            'title' => "{$name} earned [{$achieve}]",
            'body'  => "{$name} has earned the [{$achieve}] achievement{$ptStr}.",
        ];
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
    $postTime = clamp_live_post_time($postTime, $dryRun);

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
        if (function_exists('spp_enforce_topic_view_floor')) {
            spp_enforce_topic_view_floor($pdo, $topicId, 2);
        }

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
    $postTime = clamp_live_post_time($postTime, $dryRun);

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
        if (function_exists('spp_enforce_topic_view_floor')) {
            spp_enforce_topic_view_floor($pdo, $topicId, 1);
        }

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

function maybe_bump_topic_views(
    int  $realmId,
    int  $topicId,
    bool $dryRun,
    int  $chancePercent = 70,
    int  $minViews = 1,
    int  $maxViews = 3
): void {
    $chancePercent = max(0, min(100, $chancePercent));
    if ($topicId <= 0 || random_int(1, 100) > $chancePercent) {
        return;
    }

    $viewBump = random_int(max(1, $minViews), max($minViews, $maxViews));

    if ($dryRun) {
        log_line("    [dry-run] View bump: +{$viewBump} views");
        return;
    }

    try {
        $pdo = spp_get_pdo('realmd', $realmId);
        $stmt = $pdo->prepare("
            UPDATE `f_topics`
            SET num_views = GREATEST(num_views + ?, num_replies + 1)
            WHERE topic_id = ?
        ");
        $stmt->execute([$viewBump, $topicId]);
    } catch (Throwable $e) {
        error_log('[process_bot_events] View bump failed: ' . $e->getMessage());
    }
}

function build_reaction_offsets(int $count, int $minDelay, int $maxDelay): array {
    if ($count <= 0 || $maxDelay < $minDelay) {
        return [];
    }

    $offsets = [];
    for ($i = 0; $i < $count; $i++) {
        $min = max($minDelay, (int)floor(($maxDelay * $i) / $count));
        $max = (int)floor(($maxDelay * ($i + 1)) / $count) - 1;
        if ($i === $count - 1) {
            $max = $maxDelay;
        }
        if ($max < $min) {
            $max = $min;
        }
        $offsets[] = ($max > $min) ? random_int($min, $max) : $min;
    }

    sort($offsets, SORT_NUMERIC);
    return $offsets;
}

function get_guild_chatter_identities(
    int $realmId,
    int $guildId,
    int $excludeIdentityId,
    int $limit,
    PDO $masterPdo
): array {
    if ($guildId <= 0 || $limit <= 0) {
        return [];
    }

    try {
        $charPdo = spp_get_pdo('chars', $realmId);
        $initiateRankStmt = $charPdo->prepare("
            SELECT MAX(rid)
            FROM `guild_rank`
            WHERE guildid = ?
        ");
        $initiateRankStmt->execute([$guildId]);
        $initiateRank = (int)($initiateRankStmt->fetchColumn() ?? 0);

        $memberSql = "
            SELECT gm.guid
            FROM `guild_member` gm
            WHERE gm.guildid = ?
        ";
        $memberParams = [$guildId];
        if ($initiateRank > 0) {
            $memberSql .= " AND gm.rank < ? ";
            $memberParams[] = $initiateRank;
        }
        $memberSql .= " ORDER BY RAND() LIMIT " . (int)max($limit * 3, 6);

        $memberStmt = $charPdo->prepare($memberSql);
        $memberStmt->execute($memberParams);
        $memberGuids = array_values(array_unique(array_map('intval', $memberStmt->fetchAll(PDO::FETCH_COLUMN))));
        if (empty($memberGuids)) {
            return [];
        }

        $identityKeys = array_map(static function (int $guid) use ($realmId): string {
            return "char:{$realmId}:{$guid}";
        }, $memberGuids);
        $placeholders = implode(',', array_fill(0, count($identityKeys), '?'));

        $identityStmt = $masterPdo->prepare("
            SELECT identity_id, character_guid, owner_account_id, display_name
            FROM `website_identities`
            WHERE realm_id = ?
              AND identity_type = 'bot_character'
              AND is_active = 1
              AND identity_id != ?
              AND identity_key IN ({$placeholders})
        ");
        $identityStmt->execute(array_merge([$realmId, $excludeIdentityId], $identityKeys));
        $identities = $identityStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($identities)) {
            return [];
        }

        shuffle($identities);
        return array_slice($identities, 0, $limit);
    } catch (Throwable $e) {
        error_log('[process_bot_events] Guild chatter identity fetch failed: ' . $e->getMessage());
        return [];
    }
}

// ---- Reaction template pool ----
function pick_reaction(string $eventType, array $payload): string {
    $name    = $payload['char_name']   ?? 'them';
    $level   = (int)($payload['level'] ?? 0);
    $skill   = $payload['skill_name']  ?? 'that';
    $achieve = $payload['achievement'] ?? 'that';

    $byType = [
        'level_up' => [
            "grats!",
            "gratz on the ding!",
            "nice ding",
            "huge",
            "woooooo {$level}!!",
            "finally!! lol",
            "took you long enough",
            "{$level} already, damn",
            "keep grinding!",
            "nice one {$name}",
            "save some mobs for the rest of us",
            "monster",
            "see you at 60",
            "catching up to me i see lol",
            "realm first to press the xp bar",
            "clean work",
        ],
        'profession_milestone' => [
            "grats!",
            "lobster for dinner tonight boy!",
            "nice {$skill} gains",
            "future grandmaster incoming",
            "the grind is real",
            "those skillups hit different",
            "teach me your ways",
            "you selling cooldowns yet",
            "bags full of mats finally paid off",
            "crafting arc in full effect",
            "the economy is about to shift",
            "trade chat about to know your name",
        ],
        'achievement_badge' => [
            "grats!",
            "wicked!",
            "achievement hunter spotted",
            "nice one",
            "worth the grind",
            "carry me next time lol",
            "actual legend",
            "lol nice",
            "okay okay i see you",
            "this server's finest",
            "one more thing to flex in town",
            "earned that one proper",
        ],
    ];

    // Generic realm chatter mixed in regardless of event type.
    $generic = [
        "big day for {$name}",
        "ez",
        "gz",
        "W",
        "not bad not bad",
        "this person is going places",
        "meanwhile I'm still stuck on a flight path",
        "strong showing",
        "unreal",
        "certified moment",
        "doing numbers out here",
        "server's heating up tonight",
        "hall of fame material",
        "i was there",
        "i remember when they were level 1 lol",
        "someone post this on the town board",
        "another local hero rises",
    ];

    $pool = array_merge($byType[$eventType] ?? [], $generic);
    return $pool[array_rand($pool)];
}

function pick_guild_reaction(string $eventType, array $payload): string {
    $name  = $payload['char_name'] ?? ($payload['leader_name'] ?? 'them');
    $guild = $payload['guild_name'] ?? 'the guild';
    $skill = $payload['skill_name'] ?? 'that';

    $byType = [
        'guild_created' => [
            "guild's up, let's build it right",
            "good home for anyone still unguilded",
            "we're live boys",
            "whisper one of us if you want in",
            "banner's up, time to recruit",
            "{$guild} starts now",
        ],
        'guild_roster_update' => [
            "welcome aboard",
            "good to see fresh faces",
            "roster looking healthy",
            "guild bank crying already",
            "more hands for dungeon night",
            "lineup getting serious now",
        ],
        'level_up' => [
            "good stuff {$name}",
            "keep it moving",
            "save some quests for the rest of us",
            "guild run soon then",
            "we'll get you geared",
            "clean ding",
        ],
        'profession_milestone' => [
            "good work {$name}",
            "about time we had a real {$skill} player",
            "guild bank thanks you",
            "you are on consumable duty now",
            "nice, that's useful for all of us",
            "keep skilling that up",
        ],
        'achievement_badge' => [
            "nice one {$name}",
            "good pull",
            "that's going in the guild stories",
            "earned that proper",
            "solid work",
            "grats from the crew",
        ],
    ];

    $generic = [
        "good showing",
        "guild's active tonight",
        "love to see it",
        "that's one of ours",
        "another win for the tabard",
        "we take those",
    ];

    $pool = array_merge($byType[$eventType] ?? [], $generic);
    return $pool[array_rand($pool)];
}

// Post random bot reactions to a newly created public topic.
function post_bot_reactions(
    int    $realmId,
    int    $topicId,
    int    $excludeIdentityId,
    string $eventType,
    array  $payload,
    int    $basePostTime,
    ?int   $nextScheduledPostTime,
    bool   $dryRun,
    PDO    $masterPdo
): void {
    global $botEventConfig;

    $countRange = $botEventConfig['reaction_count']       ?? [1, 2];
    $minDelay   = max(0, (int)($botEventConfig['reaction_min_delay_sec'] ?? 120));
    $maxDelay   = max($minDelay, (int)($botEventConfig['reaction_max_delay_sec'] ?? 900));
    $count      = random_int((int)$countRange[0], (int)$countRange[1]);

    maybe_bump_topic_views($realmId, $topicId, $dryRun);

    if ($nextScheduledPostTime !== null) {
        $maxAllowedDelay = max(0, ($nextScheduledPostTime - $basePostTime) - 1);
        $maxDelay = min($maxDelay, $maxAllowedDelay);
    }

    if ($maxDelay < $minDelay) {
        log_line("    Skipping reactions: next scheduled post leaves no believable gap.");
        return;
    }

    // Fetch random reactor identities (exclude original poster)
    try {
        $rStmt = $masterPdo->prepare("
            SELECT identity_id, character_guid, owner_account_id, display_name
            FROM `website_identities`
            WHERE realm_id = ? AND identity_type = 'bot_character' AND is_active = 1
              AND identity_id != ?
            ORDER BY RAND()
            LIMIT " . (int)($count + 2) . "
        ");
        $rStmt->execute([$realmId, $excludeIdentityId]);
        $reactors = $rStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[process_bot_events] Reactor fetch failed: ' . $e->getMessage());
        return;
    }

    if (empty($reactors)) return;

    // Spread reactions within the available delay window so they stay
    // after the original post but before the next scheduled forum post.
    $count    = min($count, count($reactors));
    $offsets = build_reaction_offsets($count, $minDelay, $maxDelay);

    foreach (array_slice($reactors, 0, $count) as $i => $reactor) {
        $reactionTime = $basePostTime + $offsets[$i];
        $body         = pick_reaction($eventType, $payload);

        if ($dryRun) {
            log_line("    [dry-run] Reaction by {$reactor['display_name']} at " . date('H:i:s', $reactionTime) . ": \"{$body}\"");
            continue;
        }

        try {
            post_forum_reply(
                $realmId, $topicId, $reactor,
                $body, 'bot_generated', $reactionTime, false
            );
            maybe_bump_topic_views($realmId, $topicId, false, 45, 1, 2);
            log_line("    Reaction by {$reactor['display_name']} at " . date('H:i:s', $reactionTime) . ": \"{$body}\"");
        } catch (Throwable $e) {
            error_log('[process_bot_events] Reaction post failed: ' . $e->getMessage());
        }
    }
}

function post_guild_thread_reactions(
    int    $realmId,
    int    $guildId,
    int    $topicId,
    int    $excludeIdentityId,
    string $eventType,
    array  $payload,
    int    $basePostTime,
    ?int   $nextScheduledPostTime,
    bool   $dryRun,
    PDO    $masterPdo
): void {
    global $botEventConfig;

    $countRange = $botEventConfig['guild_reaction_count'] ?? [1, 2];
    $minDelay   = max(0, (int)($botEventConfig['guild_reaction_min_delay_sec'] ?? 180));
    $maxDelay   = max($minDelay, (int)($botEventConfig['guild_reaction_max_delay_sec'] ?? 1200));
    $count      = random_int((int)$countRange[0], (int)$countRange[1]);

    maybe_bump_topic_views($realmId, $topicId, $dryRun, 80, 1, 3);

    if ($nextScheduledPostTime !== null) {
        $maxAllowedDelay = max(0, ($nextScheduledPostTime - $basePostTime) - 1);
        $maxDelay = min($maxDelay, $maxAllowedDelay);
    }

    if ($maxDelay < $minDelay) {
        log_line("    Skipping guild chatter: next scheduled post leaves no believable gap.");
        return;
    }

    $reactors = get_guild_chatter_identities($realmId, $guildId, $excludeIdentityId, $count, $masterPdo);
    if (empty($reactors)) {
        log_line("    Skipping guild chatter: no eligible non-initiate guild identities.");
        return;
    }

    $count   = min($count, count($reactors));
    $offsets = build_reaction_offsets($count, $minDelay, $maxDelay);

    foreach (array_slice($reactors, 0, $count) as $i => $reactor) {
        $reactionTime = $basePostTime + $offsets[$i];
        $body         = pick_guild_reaction($eventType, $payload);

        if ($dryRun) {
            log_line("    [dry-run] Guild chatter by {$reactor['display_name']} at " . date('H:i:s', $reactionTime) . ": \"{$body}\"");
            continue;
        }

        try {
            post_forum_reply(
                $realmId, $topicId, $reactor,
                $body, 'bot_generated', $reactionTime, false
            );
            maybe_bump_topic_views($realmId, $topicId, false, 55, 1, 2);
            log_line("    Guild chatter by {$reactor['display_name']} at " . date('H:i:s', $reactionTime) . ": \"{$body}\"");
        } catch (Throwable $e) {
            error_log('[process_bot_events] Guild chatter post failed: ' . $e->getMessage());
        }
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
                        $mstmt   = $charPdo->prepare("SELECT guid AS memberGuid FROM `guild_member` WHERE guildid = ?");
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
            post_guild_thread_reactions(
                $realmId,
                $guildId,
                $threadTopicId,
                (int)$identity['identity_id'],
                $eventType,
                $payload,
                $scheduledPostTime,
                $nextScheduledPostMap[$eventId] ?? null,
                $dryRun,
                $masterPdo
            );
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

    // Resolve poster identity:
    // - guild_created: guild leader's character identity
    // - level_up / profession_milestone:
    //     unguilded character  → character posts for themselves (self-post, first-person)
    //     guilded character    → guild leader identity → realm herald
    // - everything else: realm herald
    $identity       = null;
    $isSelfPost     = false;
    $guildIdForChar = 0;   // non-zero when char is guilded (used for thread routing below)
    $idStmt         = $masterPdo->prepare("SELECT * FROM `website_identities` WHERE identity_key = ? LIMIT 1");

    if ($eventType === 'guild_created' && !empty($event['character_guid'])) {
        $idStmt->execute(["char:{$realmId}:{$event['character_guid']}"]);
        $identity = $idStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } elseif (in_array($eventType, ['level_up', 'profession_milestone', 'achievement_badge']) && !empty($event['character_guid'])) {
        $charGuidCheck = (int)$event['character_guid'];
        try {
            $charPdo2 = spp_get_pdo('chars', $realmId);
            $gStmt    = $charPdo2->prepare("SELECT guildid FROM `guild_member` WHERE guid = ? LIMIT 1");
            $gStmt->execute([$charGuidCheck]);
            $guildIdForChar = (int)($gStmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            // chars DB unavailable — treat as unguilded
        }

        if ($guildIdForChar > 0) {
            // Guilded: post as guild leader so level/skill news lands in the guild context
            $leaderGuidForChar = 0;
            try {
                $lStmt = $charPdo2->prepare("SELECT leaderguid FROM `guild` WHERE guildid = ? LIMIT 1");
                $lStmt->execute([$guildIdForChar]);
                $leaderGuidForChar = (int)($lStmt->fetchColumn() ?: 0);
            } catch (Throwable $e) {
                // fall through to herald
            }
            if ($leaderGuidForChar > 0) {
                $idStmt->execute(["char:{$realmId}:{$leaderGuidForChar}"]);
                $identity = $idStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } else {
            // Unguilded: character speaks for themselves
            $idStmt->execute(["char:{$realmId}:{$charGuidCheck}"]);
            $charIdentity = $idStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($charIdentity) {
                $identity   = $charIdentity;
                $isSelfPost = true;
            }
        }
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

    $content           = build_post_content($event, $isSelfPost);
    $contentSource     = ($eventType === 'guild_created') ? 'system_event' : 'bot_generated';
    $scheduledPostTime = (int)($scheduledPostTimes[$eventId] ?? time());

    // Guilded level_up / profession_milestone → reply into recruitment thread, not a new general topic.
    if ($guildIdForChar > 0 && in_array($eventType, ['level_up', 'profession_milestone', 'achievement_badge'])) {
        $recruitTopicId = 0;
        try {
            $realmPdoGuild = spp_get_pdo('realmd', $realmId);
            $rtStmt        = $realmPdoGuild->prepare("
                SELECT topic_id FROM `f_topics`
                WHERE guild_id = ? AND recruitment_status = 'active'
                ORDER BY topic_id ASC LIMIT 1
            ");
            $rtStmt->execute([$guildIdForChar]);
            $recruitTopicId = (int)($rtStmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            log_line("  ERROR looking up guild recruitment thread: " . $e->getMessage());
        }

        if ($recruitTopicId > 0) {
            try {
                $postId = post_forum_reply(
                    $realmId, $recruitTopicId, $identity,
                    $content['body'], $contentSource, $scheduledPostTime, $dryRun
                );
                if (!$dryRun) {
                    $masterPdo->prepare("UPDATE `website_bot_events` SET status='posted', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);
                }
                log_line("  → Guild thread #{$recruitTopicId} (post #{$postId}) at " . date('Y-m-d H:i:s', $scheduledPostTime) . ": \"{$content['title']}\" (as {$identity['display_name']})");
                $results['posted']++;
                // Pass null for nextScheduledPostTime — these are guild thread replies whose
                // timing is independent of the batch schedule (which was based on a different forum).
                post_guild_thread_reactions(
                    $realmId,
                    $guildIdForChar,
                    $recruitTopicId,
                    (int)$identity['identity_id'],
                    $eventType,
                    $payload,
                    $scheduledPostTime,
                    null,
                    $dryRun,
                    $masterPdo
                );
            } catch (Throwable $e) {
                log_line("  FAILED: " . $e->getMessage());
                if (!$dryRun) {
                    $masterPdo->prepare("UPDATE `website_bot_events` SET status='failed', error_message=?, processed_at=NOW() WHERE event_id=?")->execute([substr($e->getMessage(), 0, 255), $eventId]);
                }
                $results['failed']++;
            }
            continue;
        }

        // Guild has no active recruitment thread — skip rather than pollute general forum.
        log_line("  SKIP: guild {$guildIdForChar} has no active recruitment thread.");
        if (!$dryRun) {
            $masterPdo->prepare("UPDATE `website_bot_events` SET status='skipped', error_message='no guild thread', processed_at=NOW() WHERE event_id=?")->execute([$eventId]);
        }
        $results['skipped']++;
        continue;
    }

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

        if ($eventType === 'guild_created' && !empty($event['guild_id'])) {
            post_guild_thread_reactions(
                $realmId,
                (int)$event['guild_id'],
                (int)$topicId,
                (int)$identity['identity_id'],
                $eventType,
                $payload,
                $scheduledPostTime,
                $nextScheduledPostMap[$eventId] ?? null,
                $dryRun,
                $masterPdo
            );
        } elseif (in_array($eventType, ['level_up', 'profession_milestone', 'achievement_badge'])) {
            post_bot_reactions(
                $realmId, (int)$topicId,
                (int)$identity['identity_id'],
                $eventType, $payload,
                $scheduledPostTime,
                $nextScheduledPostMap[$eventId] ?? null,
                $dryRun,
                $masterPdo
            );
        }
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
