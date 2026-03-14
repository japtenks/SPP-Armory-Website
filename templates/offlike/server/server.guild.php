<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/components/forum/forum.func.php');

if (!function_exists('spp_class_icon_url')) {
    function spp_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $extensions = [
            1 => 'jpg',
            2 => 'jpg',
            3 => 'jpg',
            4 => 'jpg',
            5 => 'jpg',
            6 => 'gif',
            7 => 'jpg',
            8 => 'jpg',
            9 => 'jpg',
            11 => 'jpg',
        ];

        if (!isset($extensions[$classId])) {
            return '/armory/images/icons/64x64/404.png';
        }

        return '/armory/images/icons/64x64/class-' . $classId . '.' . $extensions[$classId];
    }
}

if (!function_exists('spp_race_icon_url')) {
    function spp_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = [
            1 => 'achievement_character_human_' . $gender,
            2 => 'achievement_character_orc_' . $gender,
            3 => 'achievement_character_dwarf_' . $gender,
            4 => 'achievement_character_nightelf_' . $gender,
            5 => 'achievement_character_undead_' . $gender,
            6 => 'achievement_character_tauren_' . $gender,
            7 => 'achievement_character_gnome_' . $gender,
            8 => 'achievement_character_troll_' . $gender,
            10 => 'achievement_character_bloodelf_' . $gender,
            11 => 'achievement_character_draenei_' . $gender,
        ];

        if (!isset($icons[$raceId])) {
            return '/armory/images/icons/64x64/404.png';
        }

        return '/armory/images/icons/64x64/' . $icons[$raceId] . '.png';
    }
}

if (!function_exists('spp_guild_roster_sort_compare')) {
    function spp_guild_roster_sort_compare(array $left, array $right, $sortBy, $sortDir, array $classNames, array $raceNames, array $memberAverageItemLevels) {
        $direction = strtoupper($sortDir) === 'ASC' ? 1 : -1;
        $leftGuid = (int)($left['guid'] ?? 0);
        $rightGuid = (int)($right['guid'] ?? 0);
        $leftAvgItemLevel = (float)($memberAverageItemLevels[$leftGuid] ?? 0);
        $rightAvgItemLevel = (float)($memberAverageItemLevels[$rightGuid] ?? 0);

        switch ($sortBy) {
            case 'name':
                $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                break;
            case 'race':
                $comparison = strcasecmp((string)($raceNames[(int)($left['race'] ?? 0)] ?? 'Unknown'), (string)($raceNames[(int)($right['race'] ?? 0)] ?? 'Unknown'));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'class':
                $comparison = strcasecmp((string)($classNames[(int)($left['class'] ?? 0)] ?? 'Unknown'), (string)($classNames[(int)($right['class'] ?? 0)] ?? 'Unknown'));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'level':
                $comparison = ((int)($left['level'] ?? 0) <=> (int)($right['level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'ilvl':
                $comparison = ($leftAvgItemLevel <=> $rightAvgItemLevel);
                if ($comparison === 0) {
                    $comparison = ((int)($left['level'] ?? 0) <=> (int)($right['level'] ?? 0));
                }
                break;
            case 'rank':
                $comparison = ((int)($left['rank'] ?? 0) <=> (int)($right['rank'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['rank_name'] ?? ''), (string)($right['rank_name'] ?? ''));
                }
                break;
            default:
                $comparison = ((int)($left['rank'] ?? 0) <=> (int)($right['rank'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($right['level'] ?? 0) <=> (int)($left['level'] ?? 0));
                }
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
        }

        return $comparison * $direction;
    }
}

if (!function_exists('spp_guild_roster_sort_url')) {
    function spp_guild_roster_sort_url($baseUrl, $sortBy, $currentSortBy, $currentSortDir) {
        $nextSortDir = ($currentSortBy === $sortBy && strtoupper($currentSortDir) === 'ASC') ? 'DESC' : 'ASC';
        return $baseUrl . '&sort=' . rawurlencode($sortBy) . '&dir=' . rawurlencode($nextSortDir) . '&p=1';
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
if (!isset($realmMap[$realmId])) {
    die("Invalid realm ID");
}

$realmDB = $realmMap[$realmId]['chars'];
$realmWorldDB = $realmMap[$realmId]['world'];
$armoryRealm = spp_get_armory_realm_name($realmId) ?? ('Realm ' . (int)$realmId);
$currtmp = '/armory';

$guildId = isset($_GET['guildid']) ? (int)$_GET['guildid'] : 0;
if ($guildId < 1) {
    echo "<div style='padding:24px;color:#f5c46b;'>No guild selected.</div>";
    return;
}

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$raceNames = [
  1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
  6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$guild = $DB->selectRow("SELECT guildid, name, leaderguid, motd FROM {$realmDB}.guild WHERE guildid=?d", $guildId);
if (!$guild) {
    echo "<div style='padding:24px;color:#f5c46b;'>Guild not found.</div>";
    return;
}

$guildEstablishedLabel = 'Unknown';
try {
    $createdColumn = null;
    foreach (array('createdate', 'create_date', 'created_at') as $candidateColumn) {
        $columnExists = $DB->selectCell(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild' AND COLUMN_NAME=?",
            $realmDB,
            $candidateColumn
        );
        if ((int)$columnExists > 0) {
            $createdColumn = $candidateColumn;
            break;
        }
    }

    if ($createdColumn !== null) {
        $createdValue = $DB->selectCell("SELECT `{$createdColumn}` FROM {$realmDB}.guild WHERE guildid=?d", $guildId);
        if (!empty($createdValue) && $createdValue !== '0000-00-00 00:00:00') {
            $createdTs = is_numeric($createdValue) ? (int)$createdValue : strtotime((string)$createdValue);
            if ($createdTs > 0) {
                $guildEstablishedLabel = date('M j, Y', $createdTs);
            }
        }
    }

    if ($guildEstablishedLabel === 'Unknown') {
        $eventTimeColumn = null;
        $eventTableExists = $DB->selectCell(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild_eventlog'",
            $realmDB
        );
        if ((int)$eventTableExists > 0) {
            foreach (array('TimeStamp', 'timestamp', 'time', 'event_time') as $candidateColumn) {
                $columnExists = $DB->selectCell(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild_eventlog' AND COLUMN_NAME=?",
                    $realmDB,
                    $candidateColumn
                );
                if ((int)$columnExists > 0) {
                    $eventTimeColumn = $candidateColumn;
                    break;
                }
            }

            if ($eventTimeColumn !== null) {
                $firstEventValue = $DB->selectCell("SELECT MIN(`{$eventTimeColumn}`) FROM {$realmDB}.guild_eventlog WHERE guildid=?d", $guildId);
                if (!empty($firstEventValue)) {
                    $firstEventTs = is_numeric($firstEventValue) ? (int)$firstEventValue : strtotime((string)$firstEventValue);
                    if ($firstEventTs > 0) {
                        $guildEstablishedLabel = date('M j, Y', $firstEventTs);
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log('[guild] Failed resolving established date: ' . $e->getMessage());
}

$leader = $DB->selectRow("SELECT guid, name, race, class, level, gender FROM {$realmDB}.characters WHERE guid=?d", (int)$guild['leaderguid']);
$members = $DB->select("
    SELECT
      c.guid,
      c.name,
      c.race,
      c.class,
      c.level,
      c.gender,
      gm.rank,
      gr.rname AS rank_name
    FROM {$realmDB}.guild_member gm
    LEFT JOIN {$realmDB}.characters c ON gm.guid = c.guid
    LEFT JOIN {$realmDB}.guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
    WHERE gm.guildid=?d
    ORDER BY gm.rank ASC, c.level DESC, c.name ASC
", $guildId);

if (!is_array($members)) {
    $members = [];
}

$memberAverageItemLevels = [];
if (!empty($members)) {
    $memberIds = array_values(array_unique(array_map(static function ($member) {
        return (int)($member['guid'] ?? 0);
    }, $members)));
    $memberIds = array_values(array_filter($memberIds));

    if (!empty($memberIds)) {
        $memberIdSql = implode(',', $memberIds);
        try {
            $itemLevelRows = $DB->select("
                SELECT
                  ci.guid,
                  ROUND(AVG(it.ItemLevel), 1) AS avg_item_level
                FROM {$realmDB}.character_inventory ci
                INNER JOIN {$realmWorldDB}.item_template it ON it.entry = ci.item_template
                WHERE ci.guid IN ({$memberIdSql})
                  AND ci.bag = 0
                  AND ci.slot BETWEEN 0 AND 18
                  AND ci.slot NOT IN (3, 18)
                  AND ci.item_template > 0
                GROUP BY ci.guid
            ");

            if (is_array($itemLevelRows)) {
                foreach ($itemLevelRows as $itemLevelRow) {
                    $memberAverageItemLevels[(int)$itemLevelRow['guid']] = round((float)$itemLevelRow['avg_item_level'], 1);
                }
            }
        } catch (Throwable $e) {
            error_log('[guild] Failed loading average item levels: ' . $e->getMessage());
        }
    }
}

$guildMembers = count($members);
$avgLevel = 0;
$maxLevel = 0;
$guildAverageItemLevelTotal = 0.0;
$guildAverageItemLevelCount = 0;
$classBreakdown = [];
$rankOptions = [];

foreach ($members as $member) {
    $level = (int)($member['level'] ?? 0);
    $avgLevel += $level;
    if ($level > $maxLevel) $maxLevel = $level;

    $memberItemLevel = (float)($memberAverageItemLevels[(int)($member['guid'] ?? 0)] ?? 0);
    if ($memberItemLevel > 0) {
        $guildAverageItemLevelTotal += $memberItemLevel;
        $guildAverageItemLevelCount++;
    }

    $classId = (int)($member['class'] ?? 0);
    if (!isset($classBreakdown[$classId])) $classBreakdown[$classId] = 0;
    $classBreakdown[$classId]++;

    $rankId = (int)($member['rank'] ?? 0);
    if (!isset($rankOptions[$rankId])) {
        $rankOptions[$rankId] = !empty($member['rank_name']) ? $member['rank_name'] : ('Rank ' . $rankId);
    }
}

$avgLevel = $guildMembers > 0 ? round($avgLevel / $guildMembers, 1) : 0;
$guildAverageItemLevel = $guildAverageItemLevelCount > 0 ? round($guildAverageItemLevelTotal / $guildAverageItemLevelCount, 1) : 0;
$factionName = ($leader && in_array((int)$leader['race'], $allianceRaces, true)) ? 'Alliance' : 'Horde';
$factionSlug = strtolower($factionName);
$crest = 'templates/offlike/images/modern/logo-' . $factionSlug . '.png';
$heroBg = 'templates/offlike/images/modern/' . $factionSlug . '_guild.jpg';
$pageBg = $heroBg;
$motd = trim((string)$guild['motd']) !== '' ? $guild['motd'] : 'No message set.';

$selectedName = trim($_GET['name'] ?? '');
$selectedClass = isset($_GET['class']) ? (int)$_GET['class'] : -1;
$selectedRank = isset($_GET['rank']) ? (int)$_GET['rank'] : -1;
$selectedMax = isset($_GET['maxonly']) ? 1 : 0;
$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$itemsPerPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$sortBy = strtolower(trim($_GET['sort'] ?? 'rank'));
$sortDir = strtoupper(trim($_GET['dir'] ?? 'ASC'));
$allowedSorts = array('name', 'race', 'class', 'level', 'ilvl', 'rank');
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'rank';
}
if ($sortDir !== 'ASC' && $sortDir !== 'DESC') {
    $sortDir = 'ASC';
}

$filteredMembers = [];
foreach ($members as $member) {
    if ($selectedName !== '' && stripos((string)$member['name'], $selectedName) === false) continue;
    if ($selectedClass > 0 && (int)$member['class'] !== $selectedClass) continue;
    if ($selectedRank >= 0 && (int)$member['rank'] !== $selectedRank) continue;
    if ($selectedMax && (int)$member['level'] < $maxLevel) continue;
    $filteredMembers[] = $member;
}

if (!empty($filteredMembers)) {
    usort($filteredMembers, function ($left, $right) use ($sortBy, $sortDir, $classNames, $raceNames, $memberAverageItemLevels) {
        return spp_guild_roster_sort_compare($left, $right, $sortBy, $sortDir, $classNames, $raceNames, $memberAverageItemLevels);
    });
}

$totalMembers = count($filteredMembers);
$pageCount = max(1, (int)ceil($totalMembers / $itemsPerPage));
if ($p > $pageCount) $p = $pageCount;
$offset = ($p - 1) * $itemsPerPage;
$membersPage = array_slice($filteredMembers, $offset, $itemsPerPage);
$resultStart = $totalMembers > 0 ? $offset + 1 : 0;
$resultEnd = min($offset + $itemsPerPage, $totalMembers);

$baseUrl = 'index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId . '&per_page=' . $itemsPerPage;
if ($selectedName !== '') $baseUrl .= '&name=' . urlencode($selectedName);
if ($selectedClass > 0) $baseUrl .= '&class=' . $selectedClass;
if ($selectedRank >= 0) $baseUrl .= '&rank=' . $selectedRank;
if ($selectedMax) $baseUrl .= '&maxonly=1';
?>
<style>



.guild-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
  gap: 24px;
  padding: 28px 30px 22px;
    background: linear-gradient(180deg, rgba(2, 4, 12, 0.92), rgba(1, 2, 8, 0.98));
  border: 1px solid rgba(255, 196, 0, 0.22);
  border: 1px solid rgba(255, 196, 0, 0.22);
  border-radius: 18px 18px 0 0;
}
.guild-hero-main {
  display: flex;
  align-items: center;
  gap: 22px;
}
.guild-crest {
  width: 92px;
  height: 92px;
  object-fit: contain;
  filter: drop-shadow(0 0 12px rgba(255, 193, 7, 0.22));
}
.guild-title {
  margin: 0 0 8px;
  font-size: 3rem;
  line-height: 0.98;
  color: #fff4c9;
}
.guild-subtitle {
  margin: 0 0 8px;
  font-size: 1.3rem;
  color: #d9c99a;
}
.guild-masterline {
    margin: 0;
    font-size: 1rem;
    color: #f2ddb0;
  }
.guild-masterline strong {
    color: #ffd65e;
  }
  .guild-establishedline {
    margin: 4px 0 0;
    font-size: 0.98rem;
    color: #d7c28e;
  }
  .guild-establishedline strong {
    color: #f4d06a;
    font-weight: 700;
  }
  .guild-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 24px;
    align-content: start;
}
.guild-meta-card {
  min-width: 0;
}
.guild-meta-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.82rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #c4b27c;
}
.guild-meta-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #ffd65e;
}
.guild-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.9fr) minmax(340px, 0.95fr);
  gap: 18px;
  align-items: stretch;
  padding: 26px 24px 30px;

  border-top: 0;
  border-radius: 0 0 18px 18px;
}
.guild-side-stack {
  display: flex;
  flex-direction: column;
  min-height: 100%;
}
.guild-section {
  padding: 22px 24px 18px;
  background: rgba(4, 8, 18, 0.34);
  border: 1px solid rgba(255, 204, 72, 0.18);
  border-radius: 16px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
.guild-roster-panel {
  min-height: 100%;
}
.guild-motd-panel {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  padding-bottom: 20px;
}
.guild-insights-panel {
  flex: 1 1 auto;
  border-top: 0;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}
.guild-panel-title,
.guild-side-title {
  margin: 0 0 16px;
  font-size: 1.9rem;
  color: #fff7d1;
}
.guild-side-title {
  font-size: 1.55rem;
}
.guild-filter-grid {
  display: grid;
  grid-template-columns: minmax(240px, 1.7fr) 180px 180px auto;
  gap: 14px;
  margin-bottom: 16px;
}
.guild-input,
.guild-select {
  width: 90%;
  height: 46px;
  padding: 0 14px;
  color: #f8f1d4;
  background: rgba(4, 6, 16, 0.94);
  border: 1px solid rgba(255, 196, 0, 0.75);
}
.guild-check {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #f4d05c;
  font-weight: 700;
}
.guild-summary {
  margin: 4px 0 14px;
  color: #ffcc66;
  font-weight: 700;
}
.guild-roster {
  width: 100%;
  border-collapse: collapse;
  background: rgba(10, 10, 18, 0.72);
}
.guild-roster thead th {
  padding: 14px 16px;
  text-align: left;
  font-size: 0.95rem;
  color: #ffc21c;
  border-bottom: 1px solid rgba(255, 204, 72, 0.28);
}
.guild-roster thead th a {
  color: inherit;
  text-decoration: none;
  font-weight: 700;
}
.guild-roster thead th a:hover,
.guild-roster thead th a.is-active {
  color: #fff1b0;
}
.guild-roster tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255, 204, 72, 0.14);
  vertical-align: middle;
}
.guild-roster tbody tr:nth-child(odd) {
  background: rgba(255, 255, 255, 0.03);
}
.guild-member {
  display: flex;
  align-items: center;
  gap: 12px;
}
.guild-portrait,
.guild-class-icon,
.guild-race-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid rgba(255, 198, 0, 0.45);
  object-fit: cover;
  background: #050505;
}
.guild-rank {
  color: #d6bc7e;
}
.guild-side-copy {
  margin: 0;
  line-height: 1.55;
  color: #d9c99a;
}
.guild-divider {
  margin-top: 18px;
  padding-top: 18px;
  border-top: 1px solid rgba(255, 204, 72, 0.16);
}
.guild-breakdown {
  display: grid;
  gap: 10px;
}
.guild-breakdown-row {
  display: grid;
  grid-template-columns: 110px 1fr 34px;
  gap: 12px;
  align-items: center;
}
.guild-breakdown-label {
  color: #e5d4a0;
}
.guild-breakdown-bar {
  height: 14px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  overflow: hidden;
}
.guild-breakdown-fill {
  height: 100%;
  border-radius: 999px;
}
.class-warrior { --class-color:#C79C6E; }
.class-mage { --class-color:#69CCF0; }
.class-priest { --class-color:#FFFFFF; }
.class-hunter { --class-color:#ABD473; }
.class-rogue { --class-color:#FFF569; }
.class-warlock { --class-color:#9482C9; }
.class-paladin { --class-color:#F58CBA; }
.class-druid { --class-color:#FF7D0A; }
.class-shaman { --class-color:#0070DE; }
.class-deathknight { --class-color:#C41F3B; }
[class*="class-"] a {
  color: var(--class-color);
  text-decoration: none;
  font-weight: 700;
}
@media (max-width: 1080px) {
  .guild-hero,
  .guild-shell {
    grid-template-columns: 1fr;
  }
  .guild-side-stack {
    min-height: auto;
  }
  .guild-motd-panel {
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    padding-bottom: 18px;
  }
  .guild-insights-panel {
    border-top: 1px solid rgba(255, 204, 72, 0.18);
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    margin-top: 18px;
  }
}
@media (max-width: 760px) {
  .guild-filter-grid {
    grid-template-columns: 1fr;
  }
  .guild-title {
    font-size: 2.2rem;
  }
  .guild-meta {
    grid-template-columns: 1fr 1fr;
  }
}
</style>

<?php $maxBreakdown = $classBreakdown ? max($classBreakdown) : 1; ?>
<div class="guild-page">
<div class="guild-detail">
  <div class="guild-hero">
      <div class="guild-hero-main">
        <img class="guild-crest" src="<?php echo $crest; ?>" alt="<?php echo htmlspecialchars($factionName); ?>">
        <div>
          <h1 class="guild-title"><?php echo htmlspecialchars($guild['name']); ?></h1>
          <p class="guild-subtitle"><?php echo htmlspecialchars($armoryRealm); ?></p>
          <p class="guild-masterline">Guild Master <strong><?php echo $leader ? htmlspecialchars($leader['name']) : 'Unknown'; ?></strong></p>
          <p class="guild-establishedline">Established <strong><?php echo htmlspecialchars($guildEstablishedLabel); ?></strong></p>
        </div>
      </div>
      <div class="guild-meta">
        <div class="guild-meta-card">
          <span class="guild-meta-label">Faction</span>
        <span class="guild-meta-value"><?php echo htmlspecialchars($factionName); ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Members</span>
        <span class="guild-meta-value"><?php echo $guildMembers; ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Average Level</span>
        <span class="guild-meta-value"><?php echo $avgLevel; ?></span>
      </div>
        <div class="guild-meta-card">
          <span class="guild-meta-label">Average iLvl</span>
          <span class="guild-meta-value"><?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?></span>
        </div>
      </div>
    </div>

  <div class="guild-shell">
    <section class="guild-section guild-roster-panel">
      <h2 class="guild-panel-title">Guild Roster</h2>
      <form method="get" class="guild-filter-grid">
        <input type="hidden" name="n" value="server">
        <input type="hidden" name="sub" value="guild">
        <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
        <input type="hidden" name="guildid" value="<?php echo $guildId; ?>">
        <input type="hidden" name="p" value="1">
        <input type="hidden" name="per_page" value="<?php echo $itemsPerPage; ?>">

        <input class="guild-input" type="text" name="name" value="<?php echo htmlspecialchars($selectedName); ?>" placeholder="Search member name...">
        <select class="guild-select" name="class">
          <option value="-1">All Classes</option>
          <?php foreach($classNames as $classId => $className): ?>
            <option value="<?php echo $classId; ?>"<?php echo $selectedClass === $classId ? ' selected' : ''; ?>><?php echo htmlspecialchars($className); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="guild-select" name="rank">
          <option value="-1">All Ranks</option>
          <?php foreach($rankOptions as $rankId => $rankName): ?>
            <option value="<?php echo (int)$rankId; ?>"<?php echo $selectedRank === (int)$rankId ? ' selected' : ''; ?>><?php echo htmlspecialchars($rankName); ?></option>
          <?php endforeach; ?>
        </select>
        <label class="guild-check"><input type="checkbox" name="maxonly" value="1"<?php echo $selectedMax ? ' checked' : ''; ?> onchange="this.form.submit()"> Max Level Only</label>
        </form>

      <div class="guild-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo $totalMembers; ?> members</div>



      <table class="guild-roster">
        <thead>
          <tr>
            <th><a class="<?php echo $sortBy === 'name' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'name', $sortBy, $sortDir)); ?>">Name<?php echo $sortBy === 'name' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'race' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'race', $sortBy, $sortDir)); ?>">Race<?php echo $sortBy === 'race' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'class' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'class', $sortBy, $sortDir)); ?>">Class<?php echo $sortBy === 'class' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'level' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'level', $sortBy, $sortDir)); ?>">Level<?php echo $sortBy === 'level' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'ilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'ilvl', $sortBy, $sortDir)); ?>">Avg iLvl<?php echo $sortBy === 'ilvl' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'rank' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'rank', $sortBy, $sortDir)); ?>">Guild Rank<?php echo $sortBy === 'rank' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($membersPage)): ?>
            <?php foreach($membersPage as $member): ?>
              <?php
                $memberClassName = $classNames[(int)$member['class']] ?? 'Unknown';
                $memberClassSlug = strtolower(str_replace(' ', '', $memberClassName));
                $memberRaceName = $raceNames[(int)$member['race']] ?? 'Unknown';
                $portrait = get_character_portrait_path($member['guid'], $member['gender'], $member['race'], $member['class']);
              ?>
              <tr>
                <td>
                  <div class="guild-member class-<?php echo $memberClassSlug; ?>">
                    <img class="guild-portrait" src="<?php echo $portrait; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                    <a href="index.php?n=server&sub=character&realm=<?php echo (int)$realmId; ?>&character=<?php echo urlencode($member['name']); ?>"><?php echo htmlspecialchars($member['name']); ?></a>
                  </div>
                </td>
                <td><img class="guild-race-icon" src="<?php echo htmlspecialchars(spp_race_icon_url($member['race'], $member['gender'])); ?>" alt="<?php echo htmlspecialchars($memberRaceName); ?>" title="<?php echo htmlspecialchars($memberRaceName); ?>"></td>
                <td><img class="guild-class-icon" src="<?php echo htmlspecialchars(spp_class_icon_url($member['class'])); ?>" alt="<?php echo htmlspecialchars($memberClassName); ?>" title="<?php echo htmlspecialchars($memberClassName); ?>"></td>
                <td><?php echo (int)$member['level']; ?></td>
                <td><?php echo !empty($memberAverageItemLevels[(int)$member['guid']]) ? number_format((float)$memberAverageItemLevels[(int)$member['guid']], 1) : '-'; ?></td>
                <td class="guild-rank"><?php echo htmlspecialchars(!empty($member['rank_name']) ? $member['rank_name'] : ('Rank ' . (int)$member['rank'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6">No roster members matched the current filter.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($pageCount > 1): ?>
        <div class="pagination-controls"><div class="page-links"><?php echo compact_paginate($p, $pageCount, $baseUrl); ?></div></div>
      <?php endif; ?>
    </section>

    <div class="guild-side-stack">
      <section class="guild-section guild-motd-panel">
        <h3 class="guild-side-title">Message Of The Day</h3>
        <p class="guild-side-copy"><?php echo htmlspecialchars($motd); ?></p>
      </section>

      <section class="guild-section guild-insights-panel">
        <h3 class="guild-side-title">Roster Overview</h3>
        <p class="guild-side-copy"><?php echo $guildMembers; ?> members, average level <?php echo $avgLevel; ?>, guild average iLvl <?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?>, max level <?php echo $maxLevel; ?>.</p>

        <div class="guild-divider">
          <h3 class="guild-side-title">Class Breakdown</h3>
          <div class="guild-breakdown">
            <?php foreach($classBreakdown as $classId => $classCount): ?>
              <?php
                $breakClassName = $classNames[$classId] ?? ('Class ' . $classId);
                $breakClassSlug = strtolower(str_replace(' ', '', $breakClassName));
                $breakWidth = $maxBreakdown > 0 ? round(($classCount / $maxBreakdown) * 100, 1) : 0;
              ?>
              <div class="guild-breakdown-row class-<?php echo $breakClassSlug; ?>">
                <div class="guild-breakdown-label"><?php echo htmlspecialchars($breakClassName); ?></div>
                <div class="guild-breakdown-bar"><div class="guild-breakdown-fill" style="width: <?php echo $breakWidth; ?>%; background: var(--class-color);"></div></div>
                <div><?php echo (int)$classCount; ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
</div>




