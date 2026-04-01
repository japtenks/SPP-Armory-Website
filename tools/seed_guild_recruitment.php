<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$siteRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $siteRoot;

require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/config/bot_event_config.php');
require_once($siteRoot . '/tools/guild_json.php');

$dryRun = in_array('--dry-run', $argv, true);
$realmId = 1;
$targetCoveragePercent = 25;
$minMembers = 5;

foreach ($argv as $arg) {
    if (strpos($arg, '--realm=') === 0) {
        $realmId = (int)substr($arg, 8);
        continue;
    }
    if (strpos($arg, '--coverage=') === 0) {
        $targetCoveragePercent = max(1, min(100, (int)substr($arg, 11)));
        continue;
    }
    if (strpos($arg, '--min-members=') === 0) {
        $minMembers = max(1, (int)substr($arg, 14));
        continue;
    }
}

if (!isset($realmDbMap[$realmId])) {
    fwrite(STDERR, "Unknown realm {$realmId}.\n");
    exit(1);
}

function seed_guild_log(string $message): void
{
    echo '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
}

function seed_guild_bucket_label(int $memberCount): string
{
    if ($memberCount >= 40) {
        return 'flagship';
    }
    if ($memberCount >= 25) {
        return 'large';
    }
    if ($memberCount >= 15) {
        return 'medium';
    }
    if ($memberCount >= 8) {
        return 'small';
    }
    return 'tiny';
}

function seed_guild_bucket_take_count(string $bucket, int $count): int
{
    if ($count <= 0) {
        return 0;
    }

    $ratios = array(
        'flagship' => 1.00,
        'large' => 0.60,
        'medium' => 0.35,
        'small' => 0.18,
        'tiny' => 0.08,
    );

    $ratio = (float)($ratios[$bucket] ?? 0.20);
    $take = (int)ceil($count * $ratio);

    if ($bucket !== 'tiny') {
        $take = max(1, $take);
    }

    return min($count, $take);
}

function seed_guild_sort_candidates(array &$guilds, int $realmId): void
{
    usort($guilds, function (array $left, array $right) use ($realmId): int {
        $leftMembers = (int)($left['member_count'] ?? 0);
        $rightMembers = (int)($right['member_count'] ?? 0);
        if ($leftMembers !== $rightMembers) {
            return $rightMembers <=> $leftMembers;
        }

        $leftHash = sprintf('%u', crc32($realmId . ':' . (int)($left['guild_id'] ?? 0)));
        $rightHash = sprintf('%u', crc32($realmId . ':' . (int)($right['guild_id'] ?? 0)));
        if ($leftHash !== $rightHash) {
            return strcmp($leftHash, $rightHash);
        }

        return strcasecmp((string)($left['guild_name'] ?? ''), (string)($right['guild_name'] ?? ''));
    });
}

try {
    $charPdo = spp_get_pdo('chars', $realmId);
    $realmPdo = spp_get_pdo('realmd', $realmId);
    $masterPdo = spp_get_pdo('realmd', 1);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n");
    exit(1);
}

$targetForum = (int)($botEventConfig['forum_targets'][$realmId]['guild_created'] ?? 0);
if ($targetForum <= 0) {
    fwrite(STDERR, "No guild_created forum target is configured for realm {$realmId}.\n");
    exit(1);
}

$guildStmt = $charPdo->query("
    SELECT
        g.`guildid`,
        g.`name`,
        g.`leaderguid`,
        leader.`name` AS leader_name,
        COUNT(gm.`guid`) AS member_count
    FROM `guild` g
    LEFT JOIN `guild_member` gm ON gm.`guildid` = g.`guildid`
    LEFT JOIN `characters` leader ON leader.`guid` = g.`leaderguid`
    GROUP BY g.`guildid`, g.`name`, g.`leaderguid`, leader.`name`
    ORDER BY g.`guildid` ASC
");

$guildRows = $guildStmt ? $guildStmt->fetchAll(PDO::FETCH_ASSOC) : array();
$eligible = array();
$alreadyActive = 0;
$tooSmall = 0;

foreach ($guildRows as $guildRow) {
    $guildId = (int)($guildRow['guildid'] ?? 0);
    $guildName = trim((string)($guildRow['name'] ?? ''));
    $leaderGuid = (int)($guildRow['leaderguid'] ?? 0);
    $leaderName = trim((string)($guildRow['leader_name'] ?? ''));
    $memberCount = (int)($guildRow['member_count'] ?? 0);

    if ($guildId <= 0 || $guildName === '' || $leaderGuid <= 0 || $memberCount < $minMembers) {
        $tooSmall++;
        continue;
    }

    $topicStmt = $realmPdo->prepare("
        SELECT `topic_id`
        FROM `f_topics`
        WHERE `guild_id` = ? AND `recruitment_status` = 'active'
        ORDER BY `topic_id` ASC
        LIMIT 1
    ");
    $topicStmt->execute(array($guildId));
    $activeTopicId = (int)($topicStmt->fetchColumn() ?: 0);
    if ($activeTopicId > 0) {
        $alreadyActive++;
        continue;
    }

    $summary = guild_json_read($realmId, $guildId);
    if ($summary === null) {
        $memberStmt = $charPdo->prepare("
            SELECT gm.`guid` AS memberGuid, c.`name`, c.`level`
            FROM `guild_member` gm
            JOIN `characters` c ON c.`guid` = gm.`guid`
            WHERE gm.`guildid` = ?
            ORDER BY c.`level` DESC, c.`name` ASC
        ");
        $memberStmt->execute(array($guildId));
        $memberRows = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
        $memberGuids = array_values(array_map('intval', array_column($memberRows, 'memberGuid')));
        $memberDetails = array();
        foreach ($memberRows as $memberRow) {
            $memberGuid = (int)($memberRow['memberGuid'] ?? 0);
            if ($memberGuid <= 0) {
                continue;
            }
            $memberDetails[$memberGuid] = array(
                'guid' => $memberGuid,
                'name' => (string)($memberRow['name'] ?? ('Player #' . $memberGuid)),
                'level' => (int)($memberRow['level'] ?? 0),
            );
        }

        $summary = guild_json_skeleton($realmId, $guildId, $guildName, $leaderGuid, $leaderName, $memberGuids, $memberDetails);
        if (!$dryRun) {
            guild_json_write($realmId, $guildId, $summary);
        }
    }

    $eligible[] = array(
        'guild_id' => $guildId,
        'guild_name' => $guildName,
        'leader_guid' => $leaderGuid,
        'leader_name' => $leaderName !== '' ? $leaderName : ('Leader #' . $leaderGuid),
        'member_count' => $memberCount,
        'bucket' => seed_guild_bucket_label($memberCount),
        'summary' => $summary,
    );
}

if (empty($eligible)) {
    seed_guild_log("No eligible guilds found for seeding on realm {$realmId}.");
    seed_guild_log("Already active: {$alreadyActive}; filtered by size/shape: {$tooSmall}.");
    exit(0);
}

$totalEligibleMembers = array_sum(array_map(static function (array $guild): int {
    return (int)($guild['member_count'] ?? 0);
}, $eligible));
$targetMembers = max(1, (int)ceil($totalEligibleMembers * ($targetCoveragePercent / 100)));

$buckets = array(
    'flagship' => array(),
    'large' => array(),
    'medium' => array(),
    'small' => array(),
    'tiny' => array(),
);

foreach ($eligible as $guild) {
    $buckets[$guild['bucket']][] = $guild;
}

foreach ($buckets as &$bucketGuilds) {
    seed_guild_sort_candidates($bucketGuilds, $realmId);
}
unset($bucketGuilds);

$selected = array();
$selectedByGuildId = array();
$selectedMembers = 0;
$breakdown = array();

foreach ($buckets as $bucketName => $bucketGuilds) {
    $takeCount = seed_guild_bucket_take_count($bucketName, count($bucketGuilds));
    $taken = array_slice($bucketGuilds, 0, $takeCount);
    $breakdown[$bucketName] = array(
        'eligible' => count($bucketGuilds),
        'seeded' => count($taken),
        'members' => array_sum(array_map(static function (array $guild): int {
            return (int)($guild['member_count'] ?? 0);
        }, $taken)),
    );

    foreach ($taken as $guild) {
        $guildId = (int)$guild['guild_id'];
        if (isset($selectedByGuildId[$guildId])) {
            continue;
        }
        $selected[] = $guild;
        $selectedByGuildId[$guildId] = true;
        $selectedMembers += (int)$guild['member_count'];
    }
}

$remaining = array_values(array_filter($eligible, static function (array $guild) use ($selectedByGuildId): bool {
    return !isset($selectedByGuildId[(int)$guild['guild_id']]);
}));
seed_guild_sort_candidates($remaining, $realmId);

foreach ($remaining as $guild) {
    if ($selectedMembers >= $targetMembers) {
        break;
    }
    $selected[] = $guild;
    $selectedByGuildId[(int)$guild['guild_id']] = true;
    $selectedMembers += (int)$guild['member_count'];
    $breakdown[$guild['bucket']]['seeded']++;
    $breakdown[$guild['bucket']]['members'] += (int)$guild['member_count'];
}

seed_guild_log("Realm {$realmId}: " . count($eligible) . " eligible guilds, {$totalEligibleMembers} eligible members.");
seed_guild_log("Target flavor coverage: {$targetCoveragePercent}% => {$targetMembers} members.");
foreach ($breakdown as $bucketName => $bucketData) {
    seed_guild_log(sprintf(
        "  %-8s eligible=%d seeded=%d members=%d",
        $bucketName,
        (int)$bucketData['eligible'],
        (int)$bucketData['seeded'],
        (int)$bucketData['members']
    ));
}
seed_guild_log("Selected " . count($selected) . " guilds covering {$selectedMembers} members.");

$queued = 0;
$skipped = 0;

foreach ($selected as $guild) {
    $guildId = (int)$guild['guild_id'];
    $payload = array(
        'guild_name' => (string)$guild['guild_name'],
        'leader_name' => (string)$guild['leader_name'],
        'leader_guid' => (int)$guild['leader_guid'],
        'member_count' => (int)$guild['member_count'],
        'seed_reason' => 'rebuild_guild_seed',
        'seed_bucket' => (string)$guild['bucket'],
        'seed_target_coverage_percent' => $targetCoveragePercent,
    );
    $dedupeKey = 'guild_created:realm' . $realmId . ':guild' . $guildId . ':seed-v1';

    if ($dryRun) {
        seed_guild_log("  [dry-run] Would queue <{$guild['guild_name']}> (#{$guildId}, {$guild['member_count']} members, {$guild['bucket']}).");
        $queued++;
        continue;
    }

    $stmt = $masterPdo->prepare("
        INSERT IGNORE INTO `website_bot_events`
          (`event_type`, `realm_id`, `guild_id`, `account_id`, `character_guid`, `payload_json`, `dedupe_key`, `target_forum_id`, `occurred_at`, `status`)
        VALUES
          ('guild_created', ?, ?, NULL, ?, ?, ?, ?, NOW(), 'pending')
    ");
    $stmt->execute(array(
        $realmId,
        $guildId,
        (int)$guild['leader_guid'],
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $dedupeKey,
        $targetForum,
    ));

    if ($stmt->rowCount() > 0) {
        $queued++;
        seed_guild_log("  Queued <{$guild['guild_name']}> (#{$guildId}, {$guild['member_count']} members, {$guild['bucket']}).");
    } else {
        $skipped++;
        seed_guild_log("  Skipped <{$guild['guild_name']}> (#{$guildId}) because a seed event already exists.");
    }
}

seed_guild_log("Done. queued={$queued} skipped={$skipped} already-active={$alreadyActive}");
if ($dryRun) {
    seed_guild_log("(dry-run only; nothing was written)");
}
