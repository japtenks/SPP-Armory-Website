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

function seed_guild_type_weights(): array
{
    return array(
        'leveling_questing' => 40,
        'dungeon_social' => 20,
        'raiding_progression' => 20,
        'pvp_mercenary' => 20,
    );
}

function seed_guild_variants(): array
{
    return array(
        'leveling_questing' => array('welcoming_journey', 'frontier_helpers', 'ragtag_road'),
        'dungeon_social' => array('late_night_delvers', 'steady_five_man', 'casual_keys'),
        'raiding_progression' => array('disciplined_core', 'measured_builders', 'ambitious_roster'),
        'pvp_mercenary' => array('banner_hunters', 'road_wardens', 'battlefield_regulars'),
    );
}

function seed_guild_pick_weighted_type(int $realmId, int $guildId): string
{
    $weights = seed_guild_type_weights();
    $roll = ((int)sprintf('%u', crc32('guild-type:' . $realmId . ':' . $guildId)) % 100) + 1;
    $cursor = 0;
    foreach ($weights as $type => $weight) {
        $cursor += (int)$weight;
        if ($roll <= $cursor) {
            return (string)$type;
        }
    }

    return 'leveling_questing';
}

function seed_guild_pick_variant(string $guildType, int $realmId, int $guildId): string
{
    $variants = seed_guild_variants();
    $pool = array_values($variants[$guildType] ?? array('default'));
    $index = (int)(sprintf('%u', crc32('guild-variant:' . $realmId . ':' . $guildId)) % max(1, count($pool)));
    return (string)($pool[$index] ?? 'default');
}

function seed_guild_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = array();
    $cacheKey = spl_object_hash($pdo) . ':' . strtolower($tableName);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute(array($tableName));
        $cache[$cacheKey] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function seed_guild_fetch_talent_maps(?PDO $armoryPdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = array(
        'talent_to_tab' => array(),
        'tab_meta' => array(),
    );

    if (!$armoryPdo) {
        return $cache;
    }

    try {
        $talentRows = $armoryPdo->query("SELECT `id`, `ref_talenttab` FROM `dbc_talent`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($talentRows as $row) {
            $talentId = (int)($row['id'] ?? 0);
            $tabId = (int)($row['ref_talenttab'] ?? 0);
            if ($talentId > 0 && $tabId > 0) {
                $cache['talent_to_tab'][$talentId] = $tabId;
            }
        }

        $tabRows = $armoryPdo->query("SELECT `id`, `name`, `tab_number` FROM `dbc_talenttab`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tabRows as $row) {
            $tabId = (int)($row['id'] ?? 0);
            if ($tabId <= 0) {
                continue;
            }
            $cache['tab_meta'][$tabId] = array(
                'name' => trim((string)($row['name'] ?? '')),
                'tab_number' => (int)($row['tab_number'] ?? -1),
            );
        }
    } catch (Throwable $e) {
        // Fall back to class-only inference if armory DBC tables are unavailable.
    }

    return $cache;
}

function seed_guild_infer_member_role(int $classId, ?string $primaryTabName, ?int $tabNumber): array
{
    $tab = strtolower(trim((string)$primaryTabName));
    $tabNumber = $tabNumber === null ? -1 : (int)$tabNumber;
    $result = array(
        'role' => 'support_hybrid',
        'spec' => $tab !== '' ? $primaryTabName : 'Unknown',
    );

    switch ($classId) {
        case 1: // Warrior
            if ($tab === 'protection' || $tabNumber === 2) {
                return array('role' => 'tanks', 'spec' => $primaryTabName ?: 'Protection');
            }
            return array('role' => 'melee_dps', 'spec' => $primaryTabName ?: 'Warrior DPS');
        case 2: // Paladin
            if ($tab === 'protection' || $tabNumber === 1) {
                return array('role' => 'tanks', 'spec' => $primaryTabName ?: 'Protection');
            }
            if ($tab === 'holy' || $tabNumber === 0) {
                return array('role' => 'healers', 'spec' => $primaryTabName ?: 'Holy');
            }
            return array('role' => 'support_hybrid', 'spec' => $primaryTabName ?: 'Retribution');
        case 3: // Hunter
            return array('role' => 'ranged_dps', 'spec' => $primaryTabName ?: 'Hunter');
        case 4: // Rogue
            return array('role' => 'melee_dps', 'spec' => $primaryTabName ?: 'Rogue');
        case 5: // Priest
            if ($tab === 'shadow' || $tabNumber === 2) {
                return array('role' => 'ranged_dps', 'spec' => $primaryTabName ?: 'Shadow');
            }
            return array('role' => 'healers', 'spec' => $primaryTabName ?: 'Priest Healer');
        case 7: // Shaman
            if ($tab === 'restoration' || $tabNumber === 2) {
                return array('role' => 'healers', 'spec' => $primaryTabName ?: 'Restoration');
            }
            if ($tab === 'elemental' || $tabNumber === 0) {
                return array('role' => 'ranged_dps', 'spec' => $primaryTabName ?: 'Elemental');
            }
            return array('role' => 'support_hybrid', 'spec' => $primaryTabName ?: 'Enhancement');
        case 8: // Mage
            return array('role' => 'ranged_dps', 'spec' => $primaryTabName ?: 'Mage');
        case 9: // Warlock
            return array('role' => 'ranged_dps', 'spec' => $primaryTabName ?: 'Warlock');
        case 11: // Druid
            if ($tab === 'restoration' || $tabNumber === 2) {
                return array('role' => 'healers', 'spec' => $primaryTabName ?: 'Restoration');
            }
            if ($tab === 'balance' || $tabNumber === 0) {
                return array('role' => 'ranged_dps', 'spec' => $primaryTabName ?: 'Balance');
            }
            if ($tab === 'feral combat' || $tab === 'feral' || $tabNumber === 1) {
                return array('role' => 'tanks', 'spec' => $primaryTabName ?: 'Feral');
            }
            return array('role' => 'support_hybrid', 'spec' => $primaryTabName ?: 'Druid');
    }

    return $result;
}

function seed_guild_level_band(float $averageLevel): string
{
    if ($averageLevel >= 55) {
        return 'endgame';
    }
    if ($averageLevel >= 40) {
        return 'high';
    }
    if ($averageLevel >= 25) {
        return 'mid';
    }
    if ($averageLevel > 0) {
        return 'low';
    }

    return 'unknown';
}

function seed_guild_pick_role_needs(array $roleProfile, string $guildType): array
{
    $weightsByType = array(
        'leveling_questing' => array('tanks' => 1.35, 'healers' => 1.35, 'ranged_dps' => 1.00, 'melee_dps' => 0.90, 'support_hybrid' => 0.80),
        'dungeon_social' => array('tanks' => 1.60, 'healers' => 1.50, 'ranged_dps' => 0.95, 'melee_dps' => 0.85, 'support_hybrid' => 0.90),
        'raiding_progression' => array('tanks' => 1.20, 'healers' => 1.35, 'ranged_dps' => 1.30, 'melee_dps' => 1.00, 'support_hybrid' => 0.90),
        'pvp_mercenary' => array('tanks' => 0.65, 'healers' => 1.25, 'ranged_dps' => 1.20, 'melee_dps' => 1.15, 'support_hybrid' => 0.85),
    );
    $labels = array(
        'tanks' => 'tanks',
        'healers' => 'healers',
        'melee_dps' => 'melee',
        'ranged_dps' => 'ranged',
        'support_hybrid' => 'hybrids',
    );

    $total = max(1, array_sum(array_map('intval', $roleProfile)));
    $scores = array();
    foreach ($labels as $bucket => $label) {
        $count = (int)($roleProfile[$bucket] ?? 0);
        $ratio = $count / $total;
        $weight = (float)($weightsByType[$guildType][$bucket] ?? 1.0);
        $scores[$bucket] = $weight - $ratio;
    }

    arsort($scores, SORT_NUMERIC);
    $needs = array();
    foreach ($scores as $bucket => $score) {
        if ($score <= 0.12 && !empty($needs)) {
            continue;
        }
        $needs[] = $labels[$bucket];
        if (count($needs) >= 2) {
            break;
        }
    }

    return $needs;
}

function seed_guild_build_recruitment_profile(
    int $realmId,
    int $guildId,
    array $memberRows,
    array $talentTabPoints,
    array $talentMaps
): array {
    $guildType = seed_guild_pick_weighted_type($realmId, $guildId);
    $guildVariant = seed_guild_pick_variant($guildType, $realmId, $guildId);
    $roleProfile = array(
        'tanks' => 0,
        'healers' => 0,
        'melee_dps' => 0,
        'ranged_dps' => 0,
        'support_hybrid' => 0,
    );
    $memberRoleHints = array();
    $levelTotal = 0;
    $memberCount = 0;

    foreach ($memberRows as $memberRow) {
        $memberGuid = (int)($memberRow['memberGuid'] ?? 0);
        $classId = (int)($memberRow['class'] ?? 0);
        $level = (int)($memberRow['level'] ?? 0);
        if ($memberGuid <= 0 || $classId <= 0) {
            continue;
        }

        $primaryTabId = 0;
        $primaryTabPoints = -1;
        foreach (($talentTabPoints[$memberGuid] ?? array()) as $tabId => $points) {
            $points = (int)$points;
            if ($points > $primaryTabPoints) {
                $primaryTabId = (int)$tabId;
                $primaryTabPoints = $points;
            }
        }

        $tabMeta = $primaryTabId > 0 ? ($talentMaps['tab_meta'][$primaryTabId] ?? array()) : array();
        $roleHint = seed_guild_infer_member_role(
            $classId,
            !empty($tabMeta['name']) ? (string)$tabMeta['name'] : null,
            isset($tabMeta['tab_number']) ? (int)$tabMeta['tab_number'] : null
        );

        $roleBucket = (string)($roleHint['role'] ?? 'support_hybrid');
        if (!isset($roleProfile[$roleBucket])) {
            $roleBucket = 'support_hybrid';
        }
        $roleProfile[$roleBucket]++;
        $levelTotal += max(0, $level);
        $memberCount++;

        $memberRoleHints[$memberGuid] = array(
            'class_id' => $classId,
            'role' => $roleBucket,
            'spec' => (string)($roleHint['spec'] ?? 'Unknown'),
        );
    }

    $averageLevel = $memberCount > 0 ? round($levelTotal / $memberCount, 1) : 0;

    return array(
        'guild_type' => $guildType,
        'guild_variant' => $guildVariant,
        'role_profile' => $roleProfile,
        'role_needs' => seed_guild_pick_role_needs($roleProfile, $guildType),
        'average_level' => $averageLevel,
        'level_band' => seed_guild_level_band((float)$averageLevel),
        'captured_at' => date('Y-m-d H:i:s'),
        'member_role_hints' => $memberRoleHints,
    );
}

try {
    $charPdo = spp_get_pdo('chars', $realmId);
    $realmPdo = spp_get_pdo('realmd', $realmId);
    $masterPdo = spp_get_pdo('realmd', 1);
    $armoryPdo = spp_get_pdo('armory', $realmId);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connect failed: " . $e->getMessage() . "\n");
    exit(1);
}

$talentMaps = seed_guild_fetch_talent_maps(isset($armoryPdo) ? $armoryPdo : null);
$hasCharacterTalent = seed_guild_table_exists($charPdo, 'character_talent');

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
    $memberRows = array();
    $memberGuids = array();
    $memberDetails = array();
    $talentTabPoints = array();
    if ($summary === null) {
        $memberStmt = $charPdo->prepare("
            SELECT gm.`guid` AS memberGuid, c.`name`, c.`level`, c.`class`
            FROM `guild_member` gm
            JOIN `characters` c ON c.`guid` = gm.`guid`
            WHERE gm.`guildid` = ?
            ORDER BY c.`level` DESC, c.`name` ASC
        ");
        $memberStmt->execute(array($guildId));
        $memberRows = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $memberStmt = $charPdo->prepare("
            SELECT gm.`guid` AS memberGuid, c.`name`, c.`level`, c.`class`
            FROM `guild_member` gm
            JOIN `characters` c ON c.`guid` = gm.`guid`
            WHERE gm.`guildid` = ?
            ORDER BY c.`level` DESC, c.`name` ASC
        ");
        $memberStmt->execute(array($guildId));
        $memberRows = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $memberGuids = array_values(array_map('intval', array_column($memberRows, 'memberGuid')));
    foreach ($memberRows as $memberRow) {
        $memberGuid = (int)($memberRow['memberGuid'] ?? 0);
        if ($memberGuid <= 0) {
            continue;
        }
        $memberDetails[$memberGuid] = array(
            'guid' => $memberGuid,
            'name' => (string)($memberRow['name'] ?? ('Player #' . $memberGuid)),
            'level' => (int)($memberRow['level'] ?? 0),
            'class_id' => (int)($memberRow['class'] ?? 0),
        );
    }

    if ($hasCharacterTalent && !empty($memberGuids) && !empty($talentMaps['talent_to_tab'])) {
        try {
            $chunks = array_chunk($memberGuids, 200);
            foreach ($chunks as $guidChunk) {
                $placeholders = implode(',', array_fill(0, count($guidChunk), '?'));
                $talentStmt = $charPdo->prepare("
                    SELECT `guid`, `talent_id`, `current_rank`
                    FROM `character_talent`
                    WHERE `guid` IN ({$placeholders})
                ");
                $talentStmt->execute($guidChunk);
                foreach ($talentStmt->fetchAll(PDO::FETCH_ASSOC) as $talentRow) {
                    $memberGuid = (int)($talentRow['guid'] ?? 0);
                    $talentId = (int)($talentRow['talent_id'] ?? 0);
                    $tabId = (int)($talentMaps['talent_to_tab'][$talentId] ?? 0);
                    if ($memberGuid <= 0 || $tabId <= 0) {
                        continue;
                    }
                    $points = ((int)($talentRow['current_rank'] ?? -1)) + 1;
                    if ($points <= 0) {
                        continue;
                    }
                    if (!isset($talentTabPoints[$memberGuid])) {
                        $talentTabPoints[$memberGuid] = array();
                    }
                    if (!isset($talentTabPoints[$memberGuid][$tabId])) {
                        $talentTabPoints[$memberGuid][$tabId] = 0;
                    }
                    $talentTabPoints[$memberGuid][$tabId] += $points;
                }
            }
        } catch (Throwable $e) {
            $talentTabPoints = array();
        }
    }

    $recruitmentProfile = seed_guild_build_recruitment_profile(
        $realmId,
        $guildId,
        $memberRows,
        $talentTabPoints,
        $talentMaps
    );

    if ($summary === null) {
        $summary = guild_json_skeleton($realmId, $guildId, $guildName, $leaderGuid, $leaderName, $memberGuids, $memberDetails);
    }
    $summary['recruitment_profile'] = $recruitmentProfile;
    $summary['roster']['member_count'] = count($memberGuids);
    $summary['roster']['member_guids'] = $memberGuids;
    $summary['roster']['member_details'] = $memberDetails;
    $summary['roster']['captured_at'] = date('Y-m-d H:i:s');
    if (!$dryRun) {
        guild_json_write($realmId, $guildId, $summary);
    }

    $eligible[] = array(
        'guild_id' => $guildId,
        'guild_name' => $guildName,
        'leader_guid' => $leaderGuid,
        'leader_name' => $leaderName !== '' ? $leaderName : ('Leader #' . $leaderGuid),
        'member_count' => $memberCount,
        'bucket' => seed_guild_bucket_label($memberCount),
        'summary' => $summary,
        'recruitment_profile' => $recruitmentProfile,
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
        'guild_type' => (string)($guild['recruitment_profile']['guild_type'] ?? ''),
        'guild_variant' => (string)($guild['recruitment_profile']['guild_variant'] ?? ''),
        'role_profile' => (array)($guild['recruitment_profile']['role_profile'] ?? array()),
        'role_needs' => array_values((array)($guild['recruitment_profile']['role_needs'] ?? array())),
        'average_level' => (float)($guild['recruitment_profile']['average_level'] ?? 0),
        'level_band' => (string)($guild['recruitment_profile']['level_band'] ?? 'unknown'),
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
