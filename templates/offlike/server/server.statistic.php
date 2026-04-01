<style>

.faction-columns {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 24px;
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
  transition: all 0.3s ease;
}

.faction-col {
  margin-top: 30px;
  position: relative;
  flex: 1 1 300px;
  max-width: 260px;
  min-width: 260px;
  min-height: 360px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px 16px;
  border-radius: 8px;
  border: 1px solid #333;
  background: rgba(0,0,0,0.5);
  box-shadow: inset 0 0 12px rgba(0,0,0,0.7);
}

.faction-bg {
  position: absolute;
  inset: 0;
  background-size: 100%;
  background-position: center;
  background-repeat: no-repeat;
  opacity: 0.1;
  filter: saturate(150%) brightness(1.3);
  z-index: 0;
}

.faction-text {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 10px;
  position: relative;
  z-index: 1;
}

.faction-col.alliance .faction-text {
  color: #79a9ff;
  text-shadow: 0 0 6px rgba(255,220,100,0.4);
}

.faction-col.horde .faction-text {
  color: #ff4444;
  text-shadow: 0 0 6px rgba(255,60,60,0.4);
}

.race-line {
  display: flex;
  justify-content:left;
  align-items: center;
  gap: 8px;
  margin: 6px auto;
  width: fit-content;
}

.race-line img {
  width: 42px;
  height: auto;
  border-radius: 4px;
  border: 1px solid #444;
  box-shadow: 0 0 4px rgba(0,0,0,0.4);
}

.race-line span {
  font-size: 0.95rem;
  color: #ddd;
  text-align: left;
  min-width: 100px;
}

.neutral-dk {
  margin-top: 20px;
  text-align: center;
  color: #c41f3b;
  font-weight: bold;
}

.class-breakdown-shell {
  margin: 34px auto 0;
  max-width: 1100px;
  padding: 26px 28px 30px;
  border-radius: 18px;
  border: 1px solid rgba(255, 214, 120, 0.18);
  background: linear-gradient(180deg, rgba(10, 12, 18, 0.94), rgba(5, 7, 12, 0.9));
  box-shadow: inset 0 0 30px rgba(0,0,0,0.45), 0 18px 36px rgba(0,0,0,0.24);
}

.class-breakdown-header {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 18px;
  flex-wrap: wrap;
  margin-bottom: 18px;
}

.class-breakdown-title {
  margin: 0;
  font-size: 2rem;
  color: #ffd777;
  text-shadow: 0 0 14px rgba(255, 196, 84, 0.18);
}

.class-breakdown-note {
  color: #c7c7c7;
  font-size: 0.92rem;
  max-width: 620px;
  line-height: 1.5;
}

.class-breakdown-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

.class-panel {
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(8, 10, 16, 0.74);
  padding: 20px 20px 18px;
}

.class-panel h3 {
  margin: 0 0 6px;
  color: #ffd777;
  font-size: 1.2rem;
}

.class-panel .panel-note {
  margin: 0 0 16px;
  color: #b3b3b3;
  font-size: 0.88rem;
}

.class-bars {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.class-row {
  display: grid;
  grid-template-columns: 150px minmax(140px, 1fr) 42px;
  gap: 10px;
  align-items: center;
}

.class-label {
  font-size: 0.98rem;
  font-weight: 700;
}

.class-bar-track {
  position: relative;
  height: 20px;
  border-radius: 999px;
  overflow: hidden;
  background: rgba(255,255,255,0.08);
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
}

.class-bar-fill {
  height: 100%;
  border-radius: inherit;
  min-width: 10px;
}

.class-value {
  text-align: right;
  font-size: 1.05rem;
  font-weight: 700;
  color: #f2d086;
}

.class-columns {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(40px, 56px));
  justify-content: center;
  gap: 10px;
  align-items: end;
  min-height: 290px;
  padding-top: 10px;
}

.class-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.class-column-value {
  font-size: 0.92rem;
  font-weight: 700;
  color: #f5d892;
}

.class-column-track {
  display: flex;
  align-items: end;
  justify-content: center;
  width: 100%;
  height: 220px;
  border-radius: 14px 14px 10px 10px;
  padding: 8px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
    repeating-linear-gradient(
      to top,
      rgba(255,255,255,0.06) 0,
      rgba(255,255,255,0.06) 1px,
      transparent 1px,
      transparent 20%
    );
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
}

.class-column-fill {
  width: 100%;
  min-height: 8px;
  border-radius: 10px 10px 8px 8px;
}

.class-column-label {
  font-size: 0.8rem;
  text-align: center;
  line-height: 1.2;
  font-weight: 700;
}

.class-mini-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.class-mini-card {
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.03);
  padding: 14px 16px;
}

.class-mini-card h4 {
  margin: 0 0 4px;
  color: #f2d086;
  font-size: 1rem;
}

.class-mini-note {
  margin: 0 0 12px;
  color: #9f9f9f;
  font-size: 0.8rem;
}

.class-bucket-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.class-bucket-row {
  display: grid;
  grid-template-columns: 92px minmax(100px, 1fr) 48px;
  gap: 10px;
  align-items: center;
}

.class-bucket-label {
  color: #ddd;
  font-size: 0.9rem;
  font-weight: 700;
}

.class-bucket-value {
  text-align: right;
  color: #f2d086;
  font-size: 0.95rem;
  font-weight: 700;
}

.class-split-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.class-split-stat {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  color: #ddd;
  font-size: 0.95rem;
}

.class-split-stat strong {
  color: #f2d086;
}

@media (max-width: 860px) {
  .faction-wrapper {
    max-width: 500px;
  }
  .faction-col {
    max-width: 90%;
  }
  .class-breakdown-grid {
    grid-template-columns: 1fr;
  }
  .class-row {
    grid-template-columns: 110px minmax(120px, 1fr) 38px;
  }
  .class-columns {
    min-height: 250px;
    grid-template-columns: repeat(auto-fit, minmax(34px, 46px));
    gap: 8px;
  }
  .class-column-track {
    height: 180px;
  }
  .class-mini-grid {
    grid-template-columns: 1fr;
  }
  .class-bucket-row {
    grid-template-columns: 82px minmax(90px, 1fr) 42px;
  }
}
select {
  background:#111;
  color:#ccc;
  border:1px solid #333;
  border-radius:6px;
  padding:4px 8px;
}
</style>


<?php
builddiv_start(1, $lang['statistic'],1); ?>

<div class="modern-content">

<?php
function spp_stat_median(array $values) {
  $values = array_values(array_filter($values, static function ($value) {
    return is_numeric($value);
  }));
  if (empty($values)) {
    return 0;
  }
  sort($values, SORT_NUMERIC);
  $count = count($values);
  $middle = (int)floor(($count - 1) / 2);
  if ($count % 2 === 0) {
    return (int)round(($values[$middle] + $values[$middle + 1]) / 2);
  }
  return (int)$values[$middle];
}

function spp_stat_format_playtime($seconds) {
  if (!is_numeric($seconds) || (int)$seconds <= 0) {
    return '—';
  }
  $seconds = (int)$seconds;
  if ($seconds >= 86400) {
    return round($seconds / 86400, 1) . 'd';
  }
  if ($seconds >= 3600) {
    return round($seconds / 3600, 1) . 'h';
  }
  if ($seconds >= 60) {
    return round($seconds / 60, 1) . 'm';
  }
  return $seconds . 's';
}

function spp_stat_table_exists(PDO $pdo, $tableName) {
  static $cache = [];
  $key = spl_object_hash($pdo) . ':' . $tableName;
  if (isset($cache[$key])) {
    return $cache[$key];
  }
  $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
  $stmt->execute([$tableName]);
  return $cache[$key] = (bool)$stmt->fetchColumn();
}

function spp_stat_columns(PDO $pdo, $tableName) {
  static $cache = [];
  $key = spl_object_hash($pdo) . ':' . $tableName;
  if (isset($cache[$key])) {
    return $cache[$key];
  }
  $columns = [];
  if (!spp_stat_table_exists($pdo, $tableName)) {
    return $cache[$key] = $columns;
  }
  foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $tableName) . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $columns[$row['Field']] = true;
  }
  return $cache[$key] = $columns;
}

/* ---------- Realm selection ---------- */
$realmId = (int)($_GET['realm'] ?? 1);
switch ($realmId) {
  case 1: $realmDB = "classiccharacters"; $realmName = "Classic"; break;
  case 2: $realmDB = "tbccharacters";     $realmName = "The Burning Crusade"; break;
  case 3: $realmDB = "wotlkcharacters";   $realmName = "Wrath of the Lich King"; break;
  default:$realmDB = "classiccharacters"; $realmName = "Classic";
}

/* ---------- Character data ---------- */
$rc = [];
$num_chars = 0;
$statCharPdo = null;

try {
  $statCharPdo = spp_get_pdo('chars', $realmId);
  $charData = $statCharPdo->query("
    SELECT race, COUNT(*) AS total
    FROM characters
    WHERE NOT (level = 1 AND xp = 0)
    GROUP BY race
  ")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($charData as $row) {
    $rc[$row['race']] = $row['total'];
    $num_chars += $row['total'];
  }
} catch (Exception $e) {
  echo "<p style='color:#f66;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
  $num_chars = 0;
}

/* ---------- Faction breakdown ---------- */
$alliance_races = [1,3,4,7,11];
$horde_races    = [2,5,6,8,10];

$num_ally  = array_sum(array_intersect_key($rc, array_flip($alliance_races)));
$num_horde = array_sum(array_intersect_key($rc, array_flip($horde_races)));

if ($num_chars == 0) {
  $pc_ally = $pc_horde = 0;
} else {
  $pc_ally  = round(($num_ally  / $num_chars) * 100, 1);
  $pc_horde = round(($num_horde / $num_chars) * 100, 1);
}

foreach ($rc as $race => $count) {
  ${'pc_'.$race} = $num_chars > 0 ? round(($count / $num_chars) * 100, 1) : 0;
}

$classMeta = [
  1  => ['name' => 'Warrior',      'color' => '#c79c6e'],
  2  => ['name' => 'Paladin',      'color' => '#f58cba'],
  3  => ['name' => 'Hunter',       'color' => '#abd473'],
  4  => ['name' => 'Rogue',        'color' => '#fff569'],
  5  => ['name' => 'Priest',       'color' => '#f8f6ef'],
  6  => ['name' => 'Shaman',       'color' => '#0070de'],
  7  => ['name' => 'Mage',         'color' => '#69ccf0'],
  8  => ['name' => 'Warlock',      'color' => '#9482c9'],
  9  => ['name' => 'Druid',        'color' => '#ff7d0a'],
  10 => ['name' => 'Death Knight', 'color' => '#c41f3b'],
];

$availableClassOrder = [1,2,3,4,5,7,8,9];
if ($realmId >= 2) {
  $availableClassOrder[] = 6;
}
if ($realmId >= 3) {
  $availableClassOrder[] = 10;
}
$realmdDbName = $realmDbMap[$realmId]['realmd'] ?? 'classicrealmd';
$botAccountIds = [];
try {
  $botAccountRows = $statCharPdo instanceof PDO
    ? $statCharPdo->query("SELECT `id` FROM `{$realmdDbName}`.`account` WHERE LOWER(`username`) LIKE 'rndbot%'")->fetchAll(PDO::FETCH_COLUMN)
    : [];
  foreach (($botAccountRows ?: []) as $botAccountId) {
    $botAccountIds[(int)$botAccountId] = true;
  }
} catch (Exception $e) {
  $botAccountIds = [];
}

$classCounts = [];
$classLevels = [];
$classPlaytimes = [];
$classItemLevels = [];
$classOnlineCounts = [];
$classGuildedCounts = [];
$classQuestCompletions = [];
$classHonorableKills = [];
$realmQuestCompletions = [];
$botQuestCompletions = [];
$playerQuestCompletions = [];
$totalBots = 0;
$totalPlayers = 0;
$playtimeBuckets = [
  'Under 2h' => 0,
  '2h - 10h' => 0,
  '10h - 24h' => 0,
  '1d - 3d' => 0,
  '3d+' => 0,
];
$characterColumns = $statCharPdo instanceof PDO ? spp_stat_columns($statCharPdo, 'characters') : [];
$honorableKillsSql = '0';
if (isset($characterColumns['stored_honorable_kills'])) {
  $honorableKillsSql = 'COALESCE(c.stored_honorable_kills, 0)';
} elseif (isset($characterColumns['totalKills'])) {
  $honorableKillsSql = 'COALESCE(c.totalKills, 0)';
}
try {
  $classData = $statCharPdo instanceof PDO ? $statCharPdo->query("
    SELECT c.class, c.level, c.totaltime, c.account, c.online,
           {$honorableKillsSql} AS honorable_kills,
           gm.guildid
    FROM characters c
    LEFT JOIN guild_member gm ON gm.guid = c.guid
    WHERE NOT (level = 1 AND xp = 0)
      AND c.class IS NOT NULL
      AND c.level > 0
  ")->fetchAll(PDO::FETCH_ASSOC) : [];
  foreach ($classData as $row) {
    $classId = (int)($row['class'] ?? 0);
    $level = (int)($row['level'] ?? 0);
    $totaltime = (int)($row['totaltime'] ?? 0);
    $accountId = (int)($row['account'] ?? 0);
    $online = !empty($row['online']) ? 1 : 0;
    $honorableKills = (int)($row['honorable_kills'] ?? 0);
    $guildId = (int)($row['guildid'] ?? 0);
    if (!isset($classMeta[$classId]) || $level <= 0) {
      continue;
    }
    if (!isset($classCounts[$classId])) {
      $classCounts[$classId] = 0;
      $classLevels[$classId] = [];
      $classPlaytimes[$classId] = [];
      $classOnlineCounts[$classId] = 0;
      $classGuildedCounts[$classId] = 0;
      $classHonorableKills[$classId] = [];
    }
    $classCounts[$classId]++;
    $classLevels[$classId][] = $level;
    $classPlaytimes[$classId][] = max(0, $totaltime);
    $classOnlineCounts[$classId] += $online;
    if ($guildId > 0) {
      $classGuildedCounts[$classId]++;
    }
    $classHonorableKills[$classId][] = max(0, $honorableKills);

    if ($totaltime < 7200) {
      $playtimeBuckets['Under 2h']++;
    } elseif ($totaltime < 36000) {
      $playtimeBuckets['2h - 10h']++;
    } elseif ($totaltime < 86400) {
      $playtimeBuckets['10h - 24h']++;
    } elseif ($totaltime < 259200) {
      $playtimeBuckets['1d - 3d']++;
    } else {
      $playtimeBuckets['3d+']++;
    }

    if ($accountId > 0) {
      if (isset($botAccountIds[$accountId])) {
        $totalBots++;
      } else {
        $totalPlayers++;
      }
    }
  }
} catch (Exception $e) {
  $classCounts = [];
  $classLevels = [];
  $classPlaytimes = [];
}

try {
  if ($statCharPdo instanceof PDO && spp_stat_table_exists($statCharPdo, 'character_queststatus')) {
    $questRows = $statCharPdo->query("
      SELECT c.class, c.account, COUNT(*) AS completed_quests
      FROM character_queststatus qs
      INNER JOIN characters c ON c.guid = qs.guid
      WHERE NOT (c.level = 1 AND c.xp = 0)
        AND c.class IS NOT NULL
        AND c.level > 0
        AND qs.rewarded <> 0
      GROUP BY qs.guid, c.class, c.account
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($questRows as $row) {
      $classId = (int)($row['class'] ?? 0);
      $completed = (int)($row['completed_quests'] ?? 0);
      $accountId = (int)($row['account'] ?? 0);
      if (!isset($classMeta[$classId])) {
        continue;
      }
      if (!isset($classQuestCompletions[$classId])) {
        $classQuestCompletions[$classId] = [];
      }
      $classQuestCompletions[$classId][] = $completed;
      $realmQuestCompletions[] = $completed;
      if ($accountId > 0 && isset($botAccountIds[$accountId])) {
        $botQuestCompletions[] = $completed;
      } else {
        $playerQuestCompletions[] = $completed;
      }
    }
  }
} catch (Exception $e) {
  $classQuestCompletions = [];
  $realmQuestCompletions = [];
  $botQuestCompletions = [];
  $playerQuestCompletions = [];
}

try {
  $itemLevelRows = $statCharPdo instanceof PDO ? $statCharPdo->query("
    SELECT c.class, ROUND(AVG(it.ItemLevel), 1) AS avg_item_level
    FROM characters c
    INNER JOIN character_inventory ci ON ci.guid = c.guid
    INNER JOIN " . (($realmId === 1) ? 'classicmangos' : (($realmId === 2) ? 'tbcmangos' : 'wotlkmangos')) . ".item_template it ON it.entry = ci.item_template
    WHERE NOT (c.level = 1 AND c.xp = 0)
      AND c.class IS NOT NULL
      AND c.level > 0
      AND ci.bag = 0
      AND ci.slot BETWEEN 0 AND 18
      AND ci.slot NOT IN (3, 18)
      AND ci.item_template > 0
      AND it.ItemLevel > 0
    GROUP BY c.guid, c.class
  ")->fetchAll(PDO::FETCH_ASSOC) : [];
  foreach ($itemLevelRows as $row) {
    $classId = (int)($row['class'] ?? 0);
    $avgItemLevel = (float)($row['avg_item_level'] ?? 0);
    if (!isset($classMeta[$classId]) || $avgItemLevel <= 0) {
      continue;
    }
    if (!isset($classItemLevels[$classId])) {
      $classItemLevels[$classId] = [];
    }
    $classItemLevels[$classId][] = $avgItemLevel;
  }
} catch (Exception $e) {
  $classItemLevels = [];
}

$classCountMax = !empty($classCounts) ? max($classCounts) : 0;
$classMedianMax = 0;
$classPlaytimeMedianMax = 0;
$classGearMedianMax = 0;
$classOnlineShareMax = 0;
$classGuildedShareMax = 0;
$classHonorMedianMax = 0;
$playtimeBucketMax = !empty($playtimeBuckets) ? max($playtimeBuckets) : 0;
$classCards = [];
foreach ($availableClassOrder as $classId) {
  $levels = $classLevels[$classId] ?? [];
  $count = (int)($classCounts[$classId] ?? 0);
  $median = spp_stat_median($levels);
  $playtimes = $classPlaytimes[$classId] ?? [];
  $medianPlaytime = spp_stat_median($playtimes);
  $avgPlaytime = !empty($playtimes) ? (int)round(array_sum($playtimes) / count($playtimes)) : 0;
  $gearLevels = $classItemLevels[$classId] ?? [];
  $medianGear = spp_stat_median($gearLevels);
  $avgGear = !empty($gearLevels) ? round(array_sum($gearLevels) / count($gearLevels), 1) : 0;
  $onlineCount = (int)($classOnlineCounts[$classId] ?? 0);
  $onlineShare = $count > 0 ? round(($onlineCount / $count) * 100, 1) : 0;
  $guildedCount = (int)($classGuildedCounts[$classId] ?? 0);
  $guildedShare = $count > 0 ? round(($guildedCount / $count) * 100, 1) : 0;
  $honorMedian = spp_stat_median($classHonorableKills[$classId] ?? []);
  $classMedianMax = max($classMedianMax, $median);
  $classPlaytimeMedianMax = max($classPlaytimeMedianMax, $medianPlaytime);
  $classGearMedianMax = max($classGearMedianMax, $medianGear);
  $classOnlineShareMax = max($classOnlineShareMax, $onlineShare);
  $classGuildedShareMax = max($classGuildedShareMax, $guildedShare);
  $classHonorMedianMax = max($classHonorMedianMax, $honorMedian);
  $classCards[$classId] = [
    'name' => $classMeta[$classId]['name'],
    'color' => $classMeta[$classId]['color'],
    'count' => $count,
    'median_level' => $median,
    'median_playtime' => $medianPlaytime,
    'avg_playtime' => $avgPlaytime,
    'median_gear' => $medianGear,
    'avg_gear' => $avgGear,
    'online_share' => $onlineShare,
    'guilded_share' => $guildedShare,
    'median_honorable_kills' => $honorMedian,
  ];
}
$accountSplitTotal = $totalBots + $totalPlayers;
$questOverview = [
  'realm' => [
    'max' => !empty($realmQuestCompletions) ? max($realmQuestCompletions) : 0,
    'avg' => !empty($realmQuestCompletions) ? round(array_sum($realmQuestCompletions) / count($realmQuestCompletions), 1) : 0,
    'median' => spp_stat_median($realmQuestCompletions),
  ],
  'bots' => [
    'max' => !empty($botQuestCompletions) ? max($botQuestCompletions) : 0,
    'avg' => !empty($botQuestCompletions) ? round(array_sum($botQuestCompletions) / count($botQuestCompletions), 1) : 0,
    'median' => spp_stat_median($botQuestCompletions),
  ],
  'players' => [
    'max' => !empty($playerQuestCompletions) ? max($playerQuestCompletions) : 0,
    'avg' => !empty($playerQuestCompletions) ? round(array_sum($playerQuestCompletions) / count($playerQuestCompletions), 1) : 0,
    'median' => spp_stat_median($playerQuestCompletions),
  ],
];
?>

<?php if ($num_chars == 0): ?>
  <p class="no-chars">0 <?php echo $lang['characters'] ?? 'Characters'; ?></p>
<?php else: ?>

  <?php
  $allianceMap = [1=>'human',3=>'dwarf',4=>'nightelf',7=>'gnome'];
  $hordeMap    = [2=>'orc',5=>'undead',6=>'tauren',8=>'troll'];

  if ($realmId >= 2) {
    $allianceMap[11] = 'draenei';
    $hordeMap[10]    = 'be';
  }

  $allianceRaces = [];
  $hordeRaces    = [];

  foreach ($allianceMap as $id => $key)
    $allianceRaces[$id] = ['count' => $rc[$id] ?? 0, 'pc' => ${'pc_'.$id} ?? 0];
  foreach ($hordeMap as $id => $key)
    $hordeRaces[$id]    = ['count' => $rc[$id] ?? 0, 'pc' => ${'pc_'.$id} ?? 0];

  $hasDK = !empty($rc[12]);
  ?>

  <div class="faction-columns">
    <!-- Horde -->
    <div class="faction-col horde">
      <div class="faction-bg" style="background-image:url('<?php echo $currtmp; ?>/images/icon/faction/horde.png');"></div>
      <div class="faction-text">
        Horde: <strong><?php echo $num_horde; ?></strong> (<?php echo $pc_horde; ?>%)
      </div>
      <?php foreach ($hordeRaces as $id => $data): ?>
        <div class="race-line">
          <img src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $id; ?>-0.jpg" alt="">
          <span><?php echo $data['count']; ?> (<?php echo $data['pc']; ?>%)</span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Alliance -->
    <div class="faction-col alliance">
      <div class="faction-bg" style="background-image:url('<?php echo $currtmp; ?>/images/icon/faction/alliance.png');"></div>
      <div class="faction-text">
        Alliance: <strong><?php echo $num_ally; ?></strong> (<?php echo $pc_ally; ?>%)
      </div>
      <?php foreach ($allianceRaces as $id => $data): ?>
        <div class="race-line">
          <img src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $id; ?>-0.jpg" alt="">
          <span><?php echo $data['count']; ?> (<?php echo $data['pc']; ?>%)</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($hasDK): ?>
    <div class="neutral-dk">
      <img src="<?php echo $currtmp; ?>/images/stat/12-0.gif" alt="Death Knight">
      <span>Death Knights: <strong><?php echo $rc[12]; ?></strong>
      (<?php echo ${'pc_12'} ?? number_format(($rc[12] / $num_chars) * 100, 2); ?>%)</span>
    </div>
  <?php endif; ?>

  <?php if (!empty($classCards)): ?>
    <section class="class-breakdown-shell">
      <div class="class-breakdown-header">
        <div>
          <h2 class="class-breakdown-title">Class Breakdown</h2>
          <div class="class-breakdown-note">
            Horizontal bars show how many characters each class has. Vertical bars show median level by class, which is a stronger snapshot than average when there are lots of low-level alts.
          </div>
        </div>
      </div>

      <div class="class-breakdown-grid">
        <div class="class-panel">
          <h3>Class Population</h3>
          <p class="panel-note">Character count by class on this realm.</p>
          <div class="class-bars">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $width = $classCountMax > 0 ? max(4, (int)round(($class['count'] / $classCountMax) * 100)) : 0; ?>
              <div class="class-row">
                <div class="class-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
                <div class="class-bar-track">
                  <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                </div>
                <div class="class-value"><?php echo (int)$class['count']; ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>Typical Class Level</h3>
          <p class="panel-note">Median level by class on this realm.</p>
          <div class="class-columns">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $height = $classMedianMax > 0 ? max(4, (int)round(($class['median_level'] / $classMedianMax) * 100)) : 0; ?>
              <div class="class-column">
                <div class="class-column-value"><?php echo (int)$class['median_level']; ?></div>
                <div class="class-column-track">
                  <div class="class-column-fill" style="height: <?php echo $height; ?>%; background: linear-gradient(180deg, <?php echo htmlspecialchars($class['color']); ?>, rgba(255,255,255,0.08));"></div>
                </div>
                <div class="class-column-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>Typical Class Play Time</h3>
          <p class="panel-note">Median play time by class, with average as a quick comparison.</p>
          <div class="class-bars">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $width = $classPlaytimeMedianMax > 0 ? max(4, (int)round(($class['median_playtime'] / $classPlaytimeMedianMax) * 100)) : 0; ?>
              <div class="class-row">
                <div class="class-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
                <div class="class-bar-track" title="Avg: <?php echo htmlspecialchars(spp_stat_format_playtime($class['avg_playtime'])); ?>">
                  <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                </div>
                <div class="class-value"><?php echo htmlspecialchars(spp_stat_format_playtime($class['median_playtime'])); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>Typical Class Gear</h3>
          <p class="panel-note">Median equipped item level by class, using per-character average equipped gear.</p>
          <div class="class-bars">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $width = $classGearMedianMax > 0 ? max(4, (int)round(($class['median_gear'] / $classGearMedianMax) * 100)) : 0; ?>
              <div class="class-row">
                <div class="class-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
                <div class="class-bar-track" title="Avg: <?php echo $class['avg_gear'] > 0 ? htmlspecialchars(number_format((float)$class['avg_gear'], 1)) : '—'; ?>">
                  <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                </div>
                <div class="class-value"><?php echo $class['median_gear'] > 0 ? htmlspecialchars(number_format((float)$class['median_gear'], 1)) : '—'; ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>Online Share by Class</h3>
          <p class="panel-note">How much of each class is online right now.</p>
          <div class="class-bars">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $width = $classOnlineShareMax > 0 ? max(4, (int)round(($class['online_share'] / $classOnlineShareMax) * 100)) : 0; ?>
              <div class="class-row">
                <div class="class-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
                <div class="class-bar-track">
                  <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                </div>
                <div class="class-value"><?php echo htmlspecialchars(number_format((float)$class['online_share'], 1)); ?>%</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>Guilded Share by Class</h3>
          <p class="panel-note">How much of each class is currently attached to a guild.</p>
          <div class="class-bars">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $width = $classGuildedShareMax > 0 ? max(4, (int)round(($class['guilded_share'] / $classGuildedShareMax) * 100)) : 0; ?>
              <div class="class-row">
                <div class="class-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
                <div class="class-bar-track">
                  <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                </div>
                <div class="class-value"><?php echo htmlspecialchars(number_format((float)$class['guilded_share'], 1)); ?>%</div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>PvP Tendency</h3>
          <p class="panel-note">Median honorable kills by class for a quick PvP-flavor read.</p>
          <div class="class-bars">
            <?php foreach ($availableClassOrder as $classId): ?>
              <?php $class = $classCards[$classId]; ?>
              <?php $width = $classHonorMedianMax > 0 ? max(4, (int)round(($class['median_honorable_kills'] / $classHonorMedianMax) * 100)) : 0; ?>
              <div class="class-row">
                <div class="class-label" style="color: <?php echo htmlspecialchars($class['color']); ?>;">
                  <?php echo htmlspecialchars($class['name']); ?>
                </div>
                <div class="class-bar-track">
                  <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: <?php echo htmlspecialchars($class['color']); ?>;"></div>
                </div>
                <div class="class-value"><?php echo (int)$class['median_honorable_kills']; ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="class-panel">
          <h3>Realm Play Time Mix</h3>
          <p class="panel-note">Overall time investment buckets and a rough bot/player split.</p>
          <div class="class-mini-grid">
            <div class="class-mini-card">
              <h4>Play Time Buckets</h4>
              <p class="class-mini-note">A quick feel for how alt-heavy or main-heavy the realm is.</p>
              <div class="class-bucket-list">
                <?php foreach ($playtimeBuckets as $label => $count): ?>
                  <?php $width = $playtimeBucketMax > 0 ? max(4, (int)round(($count / $playtimeBucketMax) * 100)) : 0; ?>
                  <div class="class-bucket-row">
                    <div class="class-bucket-label"><?php echo htmlspecialchars($label); ?></div>
                    <div class="class-bar-track">
                      <div class="class-bar-fill" style="width: <?php echo $width; ?>%; background: linear-gradient(90deg, #d69c3f, rgba(255,255,255,0.12));"></div>
                    </div>
                    <div class="class-bucket-value"><?php echo (int)$count; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="class-mini-card">
              <h4>Bot vs Player Split</h4>
              <p class="class-mini-note">Pulled from auth accounts matching the <code>rndbot</code> naming pattern.</p>
              <div class="class-split-card">
                <div class="class-split-stat">
                  <span>Players</span>
                  <strong><?php echo (int)$totalPlayers; ?><?php echo $accountSplitTotal > 0 ? ' (' . round(($totalPlayers / $accountSplitTotal) * 100, 1) . '%)' : ''; ?></strong>
                </div>
                <div class="class-split-stat">
                  <span>Bots</span>
                  <strong><?php echo (int)$totalBots; ?><?php echo $accountSplitTotal > 0 ? ' (' . round(($totalBots / $accountSplitTotal) * 100, 1) . '%)' : ''; ?></strong>
                </div>
                <div class="class-split-stat">
                  <span>Total tracked</span>
                  <strong><?php echo (int)$accountSplitTotal; ?></strong>
                </div>
              </div>
            </div>

            <div class="class-mini-card">
              <h4>Quest Completion Overview</h4>
              <p class="class-mini-note">Rewarded quest completions split by realm, bots, and players.</p>
              <div class="class-split-card">
                <div class="class-split-stat">
                  <span>Realm max / avg / median</span>
                  <strong><?php echo (int)$questOverview['realm']['max']; ?> / <?php echo htmlspecialchars(number_format((float)$questOverview['realm']['avg'], 1)); ?> / <?php echo (int)$questOverview['realm']['median']; ?></strong>
                </div>
                <div class="class-split-stat">
                  <span>Bots max / avg / median</span>
                  <strong><?php echo (int)$questOverview['bots']['max']; ?> / <?php echo htmlspecialchars(number_format((float)$questOverview['bots']['avg'], 1)); ?> / <?php echo (int)$questOverview['bots']['median']; ?></strong>
                </div>
                <div class="class-split-stat">
                  <span>Players max / avg / median</span>
                  <strong><?php echo (int)$questOverview['players']['max']; ?> / <?php echo htmlspecialchars(number_format((float)$questOverview['players']['avg'], 1)); ?> / <?php echo (int)$questOverview['players']['median']; ?></strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

<?php endif; ?>
</div>
<?php builddiv_end(); ?>

<?php /* Bot Rotation Health has moved to index.php?n=admin&sub=botrotation */ ?>
