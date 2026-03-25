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

@media (max-width: 860px) {
  .faction-wrapper {
    max-width: 500px;
  }
  .faction-col {
    max-width: 90%;
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
  $charData = $DB->select("
    SELECT race, COUNT(*) AS total
    FROM {$realmDB}.characters
    WHERE NOT (level = 1 AND xp = 0)
    GROUP BY race
  ");
  foreach ($charData as $row) {
    $rc[$row['race']] = $row['total'];
    $num_chars += $row['total'];
  }
} catch (Exception $e) {
  echo "<p style='color:#f66;'>Database error reading from {$realmDB}: " . htmlspecialchars($e->getMessage()) . "</p>";
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

function rotPctClass($v) {
  if ($v >= 30) return 'pct-good';
  if ($v >= 20) return 'pct-ok';
  if ($v >= 10) return 'pct-warn';
  return 'pct-bad';
}

function rotFormatSeconds($seconds) {
  if ($seconds === null || $seconds === '' || !is_numeric($seconds) || $seconds <= 0) {
    return '—';
  }

  $seconds = (int)round((float)$seconds);
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

<?php endif; ?>
</div>
<?php builddiv_end(); ?>

<?php
/* ============================================================
   BOT ROTATION PANEL
   ============================================================ */

/* ---------- Bot rotation query ---------- */
$rotationData    = null;
$rotationError   = null;
$rotationConfig  = null;
$latestHistory   = null;
$realmdDB        = $GLOBALS['realmd']['db_name'] ?? 'classicrealmd';

try {
  $rotRows = $DB->select("
    SELECT
      COUNT(*)                                                                    AS total_bots,
      SUM(CASE WHEN online = 1 THEN 1 ELSE 0 END)                               AS total_online,
      SUM(CASE WHEN online = 1 AND xp > 0 THEN 1 ELSE 0 END)                   AS rotating_active,
      SUM(CASE WHEN online = 1 AND xp = 0 THEN 1 ELSE 0 END)                   AS online_idle,
      SUM(CASE WHEN online = 0 AND xp > 0 THEN 1 ELSE 0 END)                   AS cycled_off_progressed,
      SUM(CASE WHEN online = 0 AND xp = 0 THEN 1 ELSE 0 END)                   AS never_progressed,
      ROUND(
        SUM(CASE WHEN xp > 0 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) * 100
      , 1)                                                                        AS pct_ever_rotated,
      ROUND(
        SUM(CASE WHEN online = 1 AND xp > 0 THEN 1 ELSE 0 END) /
        NULLIF(SUM(CASE WHEN online = 1 THEN 1 ELSE 0 END), 0) * 100
      , 1)                                                                        AS pct_online_rotating,
      ROUND(AVG(CASE WHEN xp > 0 THEN level END), 1)                            AS avg_level_rotating,
      MAX(CASE WHEN xp > 0 THEN level END)                                       AS highest_level
    FROM {$realmDB}.characters
    WHERE account IN (
      SELECT id FROM {$realmdDB}.account WHERE username LIKE 'RNDBOT%'
    )
  ");
  $rotationData = !empty($rotRows) ? $rotRows[0] : null;
} catch (Exception $e) {
  $rotationError = $e->getMessage();
}

try {
  $cfgRows = $DB->select("
    SELECT *
    FROM {$realmdDB}.bot_rotation_config
    WHERE realm = {$realmId}
    LIMIT 1
  ");
  $rotationConfig = !empty($cfgRows) ? $cfgRows[0] : null;
} catch (Exception $e) {
  $rotationConfig = null;
}

/* ---------- History ---------- */
$historyRows = [];
$hasHistory  = false;
try {
  $historyRows = $DB->select("
    SELECT snapshot_time, pct_online_rotating, pct_ever_rotated,
           total_online, rotating_active, avg_level_rotating,
           cfg_expected_online_pct, cfg_avg_in_world_sec, cfg_avg_offline_sec,
           observed_avg_online_sec, observed_avg_offline_sec,
           observed_online_sessions, observed_offline_sessions
    FROM {$realmdDB}.bot_rotation_log
    WHERE realm = {$realmId}
    ORDER BY snapshot_time DESC
    LIMIT 48
  ");
  $hasHistory = !empty($historyRows);
  $latestHistory = $hasHistory ? $historyRows[0] : null;
} catch (Exception $e) {
  /* table doesn't exist yet — silently ignore, show setup hint */
  $hasHistory = false;
}
?>

<style>
.rot-panel {
  width: 100%;
  max-width: 1100px;
  margin: 28px auto 0;
  background: rgba(0,0,0,0.5);
  border: 1px solid #333;
  border-radius: 8px;
  box-shadow: inset 0 0 12px rgba(0,0,0,0.7);
  padding: 20px 24px 24px;
  color: #ddd;
}
.rot-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #e8c96a;
  text-shadow: 0 0 8px rgba(232,201,106,0.3);
  margin-bottom: 18px;
  letter-spacing: 0.04em;
  border-bottom: 1px solid #2a2a2a;
  padding-bottom: 10px;
}
.rot-error {
  background: rgba(255,60,60,0.08);
  border: 1px solid #5a1a1a;
  border-radius: 6px;
  padding: 10px 14px;
  color: #f88;
  font-size: 0.82rem;
  margin-bottom: 12px;
  font-family: monospace;
}
.rot-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}
.rot-stat {
  flex: 1 1 140px;
  background: rgba(255,255,255,0.03);
  border: 1px solid #2c2c2c;
  border-radius: 6px;
  padding: 12px 14px;
  text-align: center;
}
.rot-stat .val {
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1.1;
}
.rot-stat .lbl {
  font-size: 0.72rem;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-top: 4px;
}
.rot-stat.highlight .val { color: #e8c96a; }
.rot-stat.good  .val     { color: #4caf81; }
.rot-stat.info  .val     { color: #79a9ff; }
.rot-stat.warn  .val     { color: #ff8c42; }
.rot-stat.muted .val     { color: #888; }
.rot-gauge-wrap  { margin-bottom: 20px; }
.rot-gauge-label {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #999;
  margin-bottom: 5px;
}
.rot-gauge-label span:last-child { color: #e8c96a; font-weight: 700; }
.rot-gauge-track {
  height: 10px;
  background: #1a1a1a;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #2a2a2a;
}
.rot-gauge-fill {
  height: 100%;
  border-radius: 6px;
  transition: width 0.8s ease;
  background: linear-gradient(90deg, #3a6b4a, #4caf81);
}
.rot-gauge-fill.warn { background: linear-gradient(90deg, #7a4a1a, #ff8c42); }
.rot-breakdown        { margin-bottom: 20px; }
.rot-breakdown-label {
  font-size: 0.78rem;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 6px;
}
.rot-breakdown-bar {
  display: flex;
  height: 22px;
  border-radius: 4px;
  overflow: hidden;
  border: 1px solid #2a2a2a;
  font-size: 0.7rem;
}
.rot-breakdown-bar .seg {
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.85);
  font-weight: 600;
  transition: flex 0.6s ease;
  white-space: nowrap;
  overflow: hidden;
}
.seg.rotating { background: #2e6e47; }
.seg.idle     { background: #5a4a1a; }
.seg.cycled   { background: #1a3d6b; }
.seg.cold     { background: #222; color: #555; }
.rot-history        { margin-top: 4px; }
.rot-history-title {
  font-size: 0.8rem;
  color: #777;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 8px;
}
.rot-history-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
.rot-history-table th {
  color: #666;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.7rem;
  padding: 4px 8px;
  text-align: left;
  border-bottom: 1px solid #222;
}
.rot-history-table td {
  padding: 4px 8px;
  border-bottom: 1px solid #1a1a1a;
  color: #bbb;
}
.rot-history-table tr:hover td { background: rgba(255,255,255,0.02); }
.pct-good { color: #4caf81; font-weight: 600; }
.pct-ok   { color: #e8c96a; font-weight: 600; }
.pct-warn { color: #ff8c42; font-weight: 600; }
.pct-bad  { color: #f44;    font-weight: 600; }
.rot-no-history { font-size: 0.78rem; color: #555; font-style: italic; padding: 6px 0; }
.rot-subtitle {
  margin: 18px 0 10px;
  color: #9f9f9f;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.rot-help {
  margin: -2px 0 12px;
  color: #8a8a8a;
  font-size: 0.74rem;
  line-height: 1.45;
}
</style>

<div class="rot-panel">
  <div class="rot-title">⚙ Bot Rotation Health</div>

  <?php if ($rotationError): ?>
    <div class="rot-error">
      Query error: <?php echo htmlspecialchars($rotationError); ?>
    </div>
  <?php elseif (!$rotationData || (int)$rotationData['total_bots'] === 0): ?>
    <div class="rot-error">
      No bot accounts found. Confirm bot accounts match username pattern
      <strong>RNDBOT%</strong> in <code>classicrealmd.account</code>.
    </div>
  <?php else: ?>

  <?php
    $total    = (int)$rotationData['total_bots'];
    $online   = (int)$rotationData['total_online'];
    $rotating = (int)$rotationData['rotating_active'];
    $idle     = (int)$rotationData['online_idle'];
    $cycled   = (int)$rotationData['cycled_off_progressed'];
    $cold     = (int)$rotationData['never_progressed'];
    $pctLive  = (float)$rotationData['pct_online_rotating'];
    $pctEver  = (float)$rotationData['pct_ever_rotated'];
    $avgLvl   = $rotationData['avg_level_rotating'] ?? '—';
    $maxLvl   = $rotationData['highest_level']      ?? '—';

    $gaugeWarn = $pctLive < 20;

    $wRotating = $total > 0 ? round($rotating / $total * 100, 1) : 0;
    $wIdle     = $total > 0 ? round($idle     / $total * 100, 1) : 0;
    $wCycled   = $total > 0 ? round($cycled   / $total * 100, 1) : 0;
    $wCold     = $total > 0 ? round($cold     / $total * 100, 1) : 0;

    $cfgMinIn      = $rotationConfig['min_in_world_sec']        ?? null;
    $cfgMaxIn      = $rotationConfig['max_in_world_sec']        ?? null;
    $cfgAvgIn      = $rotationConfig['avg_in_world_sec']        ?? ($latestHistory['cfg_avg_in_world_sec'] ?? null);
    $cfgMinOff     = $rotationConfig['min_offline_sec']         ?? null;
    $cfgMaxOff     = $rotationConfig['max_offline_sec']         ?? null;
    $cfgAvgOff     = $rotationConfig['avg_offline_sec']         ?? ($latestHistory['cfg_avg_offline_sec'] ?? null);
    $cfgExpected   = $rotationConfig['expected_online_pct']     ?? ($latestHistory['cfg_expected_online_pct'] ?? null);
    $cfgMinBots    = $rotationConfig['min_random_bots']         ?? null;
    $cfgMaxBots    = $rotationConfig['max_random_bots']         ?? null;
    $cfgAccounts   = $rotationConfig['account_count']           ?? null;
    $cfgRebalMin   = $rotationConfig['rebalance_min_sec']       ?? null;
    $cfgRebalMax   = $rotationConfig['rebalance_max_sec']       ?? null;
    $cfgLogins     = $rotationConfig['max_logins_per_interval'] ?? null;
    $actualOnlineShare = $total > 0 ? round(($online / $total) * 100, 1) : null;

    $obsAvgOnline       = $latestHistory['observed_avg_online_sec']   ?? null;
    $obsAvgOffline      = $latestHistory['observed_avg_offline_sec']  ?? null;
    $obsOnlineSessions  = $latestHistory['observed_online_sessions']  ?? 0;
    $obsOfflineSessions = $latestHistory['observed_offline_sessions'] ?? 0;
  ?>

  <div class="rot-stats">
    <div class="rot-stat highlight">
      <div class="val"><?php echo $pctLive; ?>%</div>
      <div class="lbl">Live Rotation</div>
    </div>
    <div class="rot-stat <?php echo $pctLive >= 20 ? 'good' : 'warn'; ?>">
      <div class="val"><?php echo $rotating; ?></div>
      <div class="lbl">Online + Progressing</div>
    </div>
    <div class="rot-stat muted">
      <div class="val"><?php echo $idle; ?></div>
      <div class="lbl">Online Idle</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo $cycled; ?></div>
      <div class="lbl">Cycled Off</div>
    </div>
    <div class="rot-stat muted">
      <div class="val"><?php echo $cold; ?></div>
      <div class="lbl">Never Progressed</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo $online; ?> / <?php echo $total; ?></div>
      <div class="lbl">Online / Total</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo $avgLvl; ?></div>
      <div class="lbl">Avg Level (rotating)</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo $maxLvl; ?></div>
      <div class="lbl">Highest Level</div>
    </div>
  </div>

  <div class="rot-subtitle">Configured Cycle Targets</div>
  <div class="rot-help">
    Pulled from <code>aiplayerbot.conf</code> via launcher sync. Average in/out times are midpoint values. Expected online share = avg in world / (avg in world + avg offline).
  </div>
  <div class="rot-stats">
    <div class="rot-stat info">
      <div class="val"><?php echo rotFormatSeconds($cfgAvgIn); ?></div>
      <div class="lbl">Avg Time In World</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo rotFormatSeconds($cfgAvgOff); ?></div>
      <div class="lbl">Avg Time Offline</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo ($cfgExpected !== null && $cfgExpected !== '') ? $cfgExpected . '%' : '—'; ?></div>
      <div class="lbl">Expected Online Share</div>
    </div>
    <div class="rot-stat <?php echo ($actualOnlineShare !== null && $cfgExpected !== null && $actualOnlineShare >= $cfgExpected) ? 'good' : 'info'; ?>">
      <div class="val"><?php echo $actualOnlineShare !== null ? $actualOnlineShare . '%' : 'â€”'; ?></div>
      <div class="lbl">Actual Online Share</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php
          echo ($cfgMinIn !== null && $cfgMaxIn !== null)
            ? rotFormatSeconds($cfgMinIn) . ' - ' . rotFormatSeconds($cfgMaxIn)
            : '—';
        ?>
      </div>
      <div class="lbl">Session Window</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php
          echo ($cfgMinOff !== null && $cfgMaxOff !== null)
            ? rotFormatSeconds($cfgMinOff) . ' - ' . rotFormatSeconds($cfgMaxOff)
            : '—';
        ?>
      </div>
      <div class="lbl">Offline Window</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php
          if ($cfgMinBots !== null && $cfgMaxBots !== null) {
            echo $cfgMinBots . ' - ' . $cfgMaxBots;
          } else {
            echo '—';
          }
        ?>
      </div>
      <div class="lbl">Min / Max Online</div>
    </div>
    <div class="rot-stat muted">
      <div class="val"><?php echo ($cfgAccounts !== null && $cfgAccounts !== '') ? $cfgAccounts : '—'; ?></div>
      <div class="lbl">Bot Accounts</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php
          if ($cfgRebalMin !== null && $cfgRebalMax !== null) {
            echo rotFormatSeconds($cfgRebalMin) . ' - ' . rotFormatSeconds($cfgRebalMax);
          } else {
            echo '—';
          }
        ?>
      </div>
      <div class="lbl">Rebalance Interval</div>
    </div>
    <div class="rot-stat muted">
      <div class="val"><?php echo ($cfgLogins !== null && $cfgLogins !== '') ? $cfgLogins : '—'; ?></div>
      <div class="lbl">Max Logins / Interval</div>
    </div>
  </div>

  <div class="rot-subtitle">Observed Timing</div>
  <div class="rot-help">
    Calculated from snapshot-to-snapshot online/offline state changes in <code>bot_rotation_state</code>. These are observed transition averages, not the core's internal timer values.
  </div>
  <div class="rot-stats">
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($obsAvgOnline); ?></div>
      <div class="lbl">Observed Avg In World</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($obsAvgOffline); ?></div>
      <div class="lbl">Observed Avg Offline</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo (int)$obsOnlineSessions; ?></div>
      <div class="lbl">Observed Logoffs</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo (int)$obsOfflineSessions; ?></div>
      <div class="lbl">Observed Returns</div>
    </div>
  </div>

  <div class="rot-gauge-wrap">
    <div class="rot-gauge-label">
      <span>Live Rotation Rate (online bots actively gaining XP)</span>
      <span><?php echo $pctLive; ?>%</span>
    </div>
    <div class="rot-gauge-track">
      <div class="rot-gauge-fill <?php echo $gaugeWarn ? 'warn' : ''; ?>"
           style="width:<?php echo min($pctLive, 100); ?>%"></div>
    </div>
  </div>

  <div class="rot-breakdown">
    <div class="rot-breakdown-label">Bot population breakdown</div>
    <div class="rot-breakdown-bar">
      <?php if ($wRotating > 0): ?>
        <div class="seg rotating" style="flex:<?php echo $wRotating; ?>"
             title="Rotating active: <?php echo $rotating; ?>">
          <?php echo $wRotating >= 8 ? $rotating : ''; ?>
        </div>
      <?php endif; ?>
      <?php if ($wIdle > 0): ?>
        <div class="seg idle" style="flex:<?php echo $wIdle; ?>"
             title="Online idle: <?php echo $idle; ?>">
          <?php echo $wIdle >= 8 ? $idle : ''; ?>
        </div>
      <?php endif; ?>
      <?php if ($wCycled > 0): ?>
        <div class="seg cycled" style="flex:<?php echo $wCycled; ?>"
             title="Cycled off progressed: <?php echo $cycled; ?>">
          <?php echo $wCycled >= 8 ? $cycled : ''; ?>
        </div>
      <?php endif; ?>
      <?php if ($wCold > 0): ?>
        <div class="seg cold" style="flex:<?php echo $wCold; ?>"
             title="Never progressed: <?php echo $cold; ?>">
          <?php echo $wCold >= 8 ? $cold : ''; ?>
        </div>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:16px;margin-top:6px;font-size:0.72rem;color:#666;">
      <span><span style="color:#2e6e47">■</span> Rotating</span>
      <span><span style="color:#5a4a1a">■</span> Online Idle</span>
      <span><span style="color:#1a3d6b">■</span> Cycled Off</span>
      <span><span style="color:#333">■</span> Never Progressed</span>
    </div>
  </div>

  <div class="rot-history">
    <div class="rot-history-title">Recent Snapshots
      <?php if (!$hasHistory): ?>
        — <span style="color:#444;font-style:italic;text-transform:none;letter-spacing:0">
            enable cron logging to see trends
          </span>
      <?php endif; ?>
    </div>

    <?php if ($hasHistory): ?>
    <table class="rot-history-table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Live %</th>
          <th>Ever %</th>
          <th>Cfg %</th>
          <th>Online</th>
          <th>Rotating</th>
          <th>Avg Lvl</th>
          <th>Obs In</th>
          <th>Obs Out</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historyRows as $row): ?>
        <tr>
          <td><?php echo date('M j H:i', strtotime($row['snapshot_time'])); ?></td>
          <td class="<?php echo rotPctClass((float)$row['pct_online_rotating']); ?>">
            <?php echo $row['pct_online_rotating']; ?>%
          </td>
          <td class="<?php echo rotPctClass((float)$row['pct_ever_rotated']); ?>">
            <?php echo $row['pct_ever_rotated']; ?>%
          </td>
          <td>
            <?php
              echo ($row['cfg_expected_online_pct'] !== null && $row['cfg_expected_online_pct'] !== '')
                ? $row['cfg_expected_online_pct'] . '%'
                : '—';
            ?>
          </td>
          <td><?php echo $row['total_online']; ?></td>
          <td><?php echo $row['rotating_active']; ?></td>
          <td><?php echo $row['avg_level_rotating'] ?? '—'; ?></td>
          <td><?php echo rotFormatSeconds($row['observed_avg_online_sec'] ?? null); ?></td>
          <td><?php echo rotFormatSeconds($row['observed_avg_offline_sec'] ?? null); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="rot-no-history">No history yet. Set up the cron job to start tracking over time.</div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div><!-- .rot-panel -->
