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
  grid-template-columns: minmax(320px, 1.15fr) minmax(320px, 1fr);
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
  5  => ['name' => 'Priest',       'color' => '#f4f4f4'],
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

$classCounts = [];
$classLevels = [];
try {
  $classData = $statCharPdo->query("
    SELECT class, level
    FROM characters
    WHERE NOT (level = 1 AND xp = 0)
      AND class IS NOT NULL
      AND level > 0
  ")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($classData as $row) {
    $classId = (int)($row['class'] ?? 0);
    $level = (int)($row['level'] ?? 0);
    if (!isset($classMeta[$classId]) || $level <= 0) {
      continue;
    }
    if (!isset($classCounts[$classId])) {
      $classCounts[$classId] = 0;
      $classLevels[$classId] = [];
    }
    $classCounts[$classId]++;
    $classLevels[$classId][] = $level;
  }
} catch (Exception $e) {
  $classCounts = [];
  $classLevels = [];
}

$classCountMax = !empty($classCounts) ? max($classCounts) : 0;
$classMedianMax = 0;
$classCards = [];
foreach ($availableClassOrder as $classId) {
  $levels = $classLevels[$classId] ?? [];
  sort($levels, SORT_NUMERIC);
  $count = (int)($classCounts[$classId] ?? 0);
  $median = 0;
  if ($count > 0) {
    $middle = (int)floor(($count - 1) / 2);
    if ($count % 2 === 0) {
      $median = (int)round(($levels[$middle] + $levels[$middle + 1]) / 2);
    } else {
      $median = (int)$levels[$middle];
    }
  }
  $classMedianMax = max($classMedianMax, $median);
  $classCards[$classId] = [
    'name' => $classMeta[$classId]['name'],
    'color' => $classMeta[$classId]['color'],
    'count' => $count,
    'median_level' => $median,
  ];
}
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
      </div>
    </section>
  <?php endif; ?>

<?php endif; ?>
</div>
<?php builddiv_end(); ?>

<?php /* Bot Rotation Health has moved to index.php?n=admin&sub=botrotation */ ?>
