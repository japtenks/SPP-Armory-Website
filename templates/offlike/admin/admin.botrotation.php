<?php
if (!function_exists('rotPctClass')) {
    function rotPctClass($v) {
        if ($v >= 30) return 'pct-good';
        if ($v >= 20) return 'pct-ok';
        if ($v >= 10) return 'pct-warn';
        return 'pct-bad';
    }
}
if (!function_exists('rotFormatSeconds')) {
    function rotFormatSeconds($seconds) {
        if ($seconds === null || $seconds === '' || !is_numeric($seconds) || $seconds <= 0) return '—';
        $seconds = (int)round((float)$seconds);
        if ($seconds >= 86400) return round($seconds / 86400, 1) . 'd';
        if ($seconds >= 3600)  return round($seconds / 3600,  1) . 'h';
        if ($seconds >= 60)    return round($seconds / 60,    1) . 'm';
        return $seconds . 's';
    }
}
if (!function_exists('rotFormatUptimeSeconds')) {
    function rotFormatUptimeSeconds($seconds) {
        if ($seconds === null || $seconds === '' || !is_numeric($seconds) || $seconds <= 0) return 'N/A';
        $seconds = (int)floor((float)$seconds);
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        if ($days > 0)    return $days . 'd ' . $hours . 'h';
        if ($hours > 0)   return $hours . 'h ' . $minutes . 'm';
        if ($minutes > 0) return $minutes . 'm';
        return $seconds . 's';
    }
}
if (!function_exists('rotFormatSnapshotTime')) {
    function rotFormatSnapshotTime($timestamp) {
        $timestamp = trim((string)$timestamp);
        if ($timestamp === '') {
            return '—';
        }

        try {
            $utc = new DateTimeZone('UTC');
            $local = new DateTimeZone((string)date_default_timezone_get());
            $dt = new DateTime($timestamp, $utc);
            $dt->setTimezone($local);
            return $dt->format('M j H:i');
        } catch (Exception $e) {
            return htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<style>
.rot-shell {
  margin: 18px auto 0;
  width: min(100%, 1180px);
  padding: 0 12px 18px;
  box-sizing: border-box;
}
.rot-shell .btn.secondary {
  display: inline-block;
  padding: 10px 18px;
  border-radius: 8px;
  background: #2f3136;
  border: 1px solid #3d4046;
  color: #f0e0b6;
  font-weight: 700;
  text-decoration: none;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 1px 8px rgba(0,0,0,0.25);
  transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}
.rot-shell .btn.secondary:hover {
  background: #3a3d43;
  border-color: #565a63;
  color: #ffcc66;
}
.rot-panel {
  width: 100%;
  max-width: 1180px;
  margin: 0 auto;
  background: rgba(0,0,0,0.5);
  border: 1px solid #333;
  border-radius: 8px;
  box-shadow: inset 0 0 12px rgba(0,0,0,0.7);
  padding: 20px 24px 24px;
  color: #ddd;
  box-sizing: border-box;
}
.rot-panel + .rot-panel { margin-top: 18px; }
.rot-panel-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  margin-top: 18px;
  align-items: stretch;
}
.rot-title { font-size:1.05rem; font-weight:700; color:#e8c96a; text-shadow:0 0 8px rgba(232,201,106,0.3); margin-bottom:18px; letter-spacing:0.04em; border-bottom:1px solid #2a2a2a; padding-bottom:10px; }
.rot-title {
    position: relative;
    color: transparent;
    text-shadow: none;
}
.rot-title::before {
    content: 'Rotation Overview';
    color: #e8c96a;
    text-shadow: 0 0 8px rgba(232,201,106,0.3);
}
.rot-error { background:rgba(255,60,60,0.08); border:1px solid #5a1a1a; border-radius:6px; padding:10px 14px; color:#f88; font-size:0.82rem; margin-bottom:12px; font-family:monospace; }
.rot-stats { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.rot-stat { flex:1 1 140px; background:rgba(255,255,255,0.03); border:1px solid #2c2c2c; border-radius:6px; padding:12px 14px; text-align:center; }
.rot-stat-link {
  display:block;
  color:inherit;
  text-decoration:none;
  transition:border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
}
.rot-stat-link:hover {
  border-color:#565a63;
  background:rgba(255,255,255,0.05);
  transform:translateY(-1px);
}
.rot-stat-link:focus-visible {
  outline:2px solid #e8c96a;
  outline-offset:2px;
}
.rot-stat .val { font-size:1.6rem; font-weight:700; line-height:1.1; }
.rot-stat .lbl { font-size:0.72rem; color:#888; text-transform:uppercase; letter-spacing:0.06em; margin-top:4px; }
.rot-stat .meta { font-size:0.72rem; color:#999; margin-top:6px; line-height:1.35; }
.rot-stat.highlight .val { color:#e8c96a; }
.rot-stat.good  .val { color:#4caf81; }
.rot-stat.info  .val { color:#79a9ff; }
.rot-stat.warn  .val { color:#ff8c42; }
.rot-stat.muted .val { color:#888; }
.rot-gauge-wrap { margin-bottom:20px; }
.rot-gauge-label { display:flex; justify-content:space-between; font-size:0.8rem; color:#999; margin-bottom:5px; }
.rot-gauge-label span:last-child { color:#e8c96a; font-weight:700; }
.rot-gauge-track { height:10px; background:#1a1a1a; border-radius:6px; overflow:hidden; border:1px solid #2a2a2a; }
.rot-gauge-fill { height:100%; border-radius:6px; transition:width 0.8s ease; background:linear-gradient(90deg,#3a6b4a,#4caf81); }
.rot-gauge-fill.warn { background:linear-gradient(90deg,#7a4a1a,#ff8c42); }
.rot-breakdown { margin-bottom:20px; }
.rot-breakdown-label { font-size:0.78rem; color:#888; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px; }
.rot-breakdown-bar { display:flex; height:22px; border-radius:4px; overflow:hidden; border:1px solid #2a2a2a; font-size:0.7rem; }
.rot-breakdown-bar .seg { display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.85); font-weight:600; transition:flex 0.6s ease; white-space:nowrap; overflow:hidden; }
.seg.rotating { background:#2e6e47; } .seg.idle { background:#5a4a1a; } .seg.cycled { background:#1a3d6b; } .seg.cold { background:#222; color:#555; }
.rot-history { margin-top:4px; }
.rot-history-title { font-size:0.8rem; color:#777; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; }
.rot-history-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.rot-history-table th { color:#666; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; font-size:0.7rem; padding:4px 8px; text-align:left; border-bottom:1px solid #222; }
.rot-history-table td { padding:4px 8px; border-bottom:1px solid #1a1a1a; color:#bbb; }
.rot-history-table tr:hover td { background:rgba(255,255,255,0.02); }
.pct-good { color:#4caf81; font-weight:600; } .pct-ok { color:#e8c96a; font-weight:600; } .pct-warn { color:#ff8c42; font-weight:600; } .pct-bad { color:#f44; font-weight:600; }
.rot-no-history { font-size:0.78rem; color:#555; font-style:italic; padding:6px 0; }
.rot-subtitle { margin:18px 0 10px; color:#9f9f9f; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em; }
.rot-help { margin:-2px 0 12px; color:#8a8a8a; font-size:0.74rem; line-height:1.45; }
.rot-toolbox { margin:0 0 20px; padding:16px 18px; border:1px solid #2c2c2c; border-radius:8px; background:rgba(255,255,255,0.03); }
.rot-panel.rot-toolbox {
  display:flex;
  flex-direction:column;
  height:100%;
  margin:0;
}
.rot-toolbox-title { margin:0 0 6px; color:#e8c96a; font-size:0.95rem; font-weight:700; letter-spacing:0.04em; }
.rot-toolbox-note { margin:0 0 14px; color:#9a9a9a; font-size:0.76rem; line-height:1.5; }
.rot-command-stack {
  display:flex;
  flex-direction:column;
  gap:10px;
  margin-top:auto;
}
.rot-command-wrap {
  display:flex;
  flex-direction:column;
  gap:8px;
}
.rot-command {
  margin:0;
  padding:10px 12px;
  border-radius:6px;
  border:1px solid #2a2a2a;
  background:#111;
  color:#dbe9ff;
  font-family:Consolas,Monaco,monospace;
  font-size:0.8rem;
  white-space:pre-wrap;
  word-break:break-word;
  min-height:52px;
  align-content:center;
}
.rot-command.is-collapsed { display:none; }
.rot-command-caption {
  margin:0;
  font-size:0.68rem;
  letter-spacing:0.08em;
  text-transform:uppercase;
  color:#7f7f7f;
}
.rot-command-actions {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:2px;
}
.rot-btn { display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:8px; border:1px solid #3d4046; background:#2f3136; color:#f0e0b6; font-weight:700; cursor:pointer; }
.rot-btn:hover { background:#3a3d43; border-color:#565a63; color:#ffcc66; }
@media (max-width: 860px) {
  .rot-panel-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<?php builddiv_start(1, 'Bot Rotation Health'); ?>

<?php builddiv_end(); ?>
<div class="rot-shell">

<div class="rot-panel">
  <div class="rot-title">Bot Rotation Health</div>

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

    $avgBotIlvl = ($latestHistory && isset($latestHistory['avg_equipped_ilvl_bots'])
        && $latestHistory['avg_equipped_ilvl_bots'] !== '' && $latestHistory['avg_equipped_ilvl_bots'] !== null)
        ? $latestHistory['avg_equipped_ilvl_bots'] : '—';
    $avgServerIlvl = ($latestHistory && isset($latestHistory['avg_equipped_ilvl_server'])
        && $latestHistory['avg_equipped_ilvl_server'] !== '' && $latestHistory['avg_equipped_ilvl_server'] !== null)
        ? $latestHistory['avg_equipped_ilvl_server'] : '—';

    $gaugeWarn = $pctLive < 20;

    $wRotating = $total > 0 ? round($rotating / $total * 100, 1) : 0;
    $wIdle     = $total > 0 ? round($idle     / $total * 100, 1) : 0;
    $wCycled   = $total > 0 ? round($cycled   / $total * 100, 1) : 0;
    $wCold     = $total > 0 ? round($cold     / $total * 100, 1) : 0;

    $cfgMinIn    = $rotationConfig['min_in_world_sec']        ?? null;
    $cfgMaxIn    = $rotationConfig['max_in_world_sec']        ?? null;
    $cfgAvgIn    = $rotationConfig['avg_in_world_sec']        ?? ($latestHistory['cfg_avg_in_world_sec'] ?? null);
    $cfgMinOff   = $rotationConfig['min_offline_sec']         ?? null;
    $cfgMaxOff   = $rotationConfig['max_offline_sec']         ?? null;
    $cfgAvgOff   = $rotationConfig['avg_offline_sec']         ?? ($latestHistory['cfg_avg_offline_sec'] ?? null);
    $cfgExpected = $rotationConfig['expected_online_pct']     ?? ($latestHistory['cfg_expected_online_pct'] ?? null);
    $cfgMinBots  = $rotationConfig['min_random_bots']         ?? null;
    $cfgMaxBots  = $rotationConfig['max_random_bots']         ?? null;
    $cfgAccounts = $rotationConfig['account_count']           ?? null;
    $cfgRebalMin = $rotationConfig['rebalance_min_sec']       ?? null;
    $cfgRebalMax = $rotationConfig['rebalance_max_sec']       ?? null;
    $cfgLogins   = $rotationConfig['max_logins_per_interval'] ?? null;
    $actualOnlineShare = $total > 0 ? round(($online / $total) * 100, 1) : null;

    $obsAvgOnline       = $latestHistory['observed_avg_online_sec']   ?? null;
    $obsAvgOffline      = $latestHistory['observed_avg_offline_sec']  ?? null;
    $obsOnlineSessions  = $latestHistory['observed_online_sessions']  ?? 0;
    $obsOfflineSessions = $latestHistory['observed_offline_sessions'] ?? 0;
    $uptimeSummary = $uptimeSummary ?? array();
    $cleanHistory = $cleanHistory ?? array();
    $medianUptimeSec = $uptimeSummary['median_uptime_sec'] ?? null;
    $stableAvgUptimeHours = $uptimeSummary['stable_avg_uptime_hours'] ?? null;
    $shortRestarts7d = (int)($uptimeSummary['short_restarts'] ?? 0);
    $stableRuns7d = (int)($uptimeSummary['stable_runs'] ?? 0);
    $cleanObsAvgOnline = $cleanHistory['avg_online_sec'] ?? null;
    $cleanObsAvgOffline = $cleanHistory['avg_offline_sec'] ?? null;
    $cleanOnlineSessions = (int)($cleanHistory['online_sessions'] ?? 0);
    $cleanOfflineSessions = (int)($cleanHistory['offline_sessions'] ?? 0);
    $cleanSnapshotCount = (int)($cleanHistory['snapshot_count'] ?? 0);
    $skippedSnapshotCount = (int)($cleanHistory['skipped_snapshot_count'] ?? 0);
    $liveAvgOnline  = $liveOnlineAvg !== null && is_numeric($liveOnlineAvg)  ? (float)$liveOnlineAvg  : null;
    $liveMaxOnline  = $liveOnlineMax !== null && is_numeric($liveOnlineMax)  ? (float)$liveOnlineMax  : null;
    $liveAvgOffline = isset($rotationData['current_avg_offline_sec']) && is_numeric($rotationData['current_avg_offline_sec'])
        ? (float)$rotationData['current_avg_offline_sec'] : null;
    $liveMaxOffline = isset($rotationData['current_max_offline_sec']) && is_numeric($rotationData['current_max_offline_sec'])
        ? (float)$rotationData['current_max_offline_sec'] : null;
    if ($liveAvgOffline !== null && $liveMaxOffline !== null && $liveAvgOffline > $liveMaxOffline) {
        $liveAvgOffline = $liveMaxOffline;
    }
    $topBotPlaytime = $topBotData ? rotFormatUptimeSeconds($topBotData['totaltime'] ?? null) : 'N/A';
    $highestLevelUrl = !empty($topBotData['name']) ? 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode((string)$topBotData['name']) : '';
    $longestOnlineUrl = !empty($longestOnlineBot['bot_name']) ? 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode((string)$longestOnlineBot['bot_name']) : '';
    $longestOfflineUrl = !empty($longestOfflineBot['bot_name']) ? 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode((string)$longestOfflineBot['bot_name']) : '';
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
    <a class="rot-stat rot-stat-link good" href="<?php echo htmlspecialchars($highestLevelUrl !== '' ? $highestLevelUrl : '#'); ?>">
      <div class="val"><?php echo $maxLvl; ?></div>
      <div class="lbl">Highest Level</div>
      <div class="meta">
        <?php if (!empty($topBotData['name'])): ?>
          <?php echo htmlspecialchars((string)$topBotData['name']); ?> &middot; Playtime: <?php echo htmlspecialchars($topBotPlaytime); ?>
        <?php else: ?>
          Playtime: <?php echo htmlspecialchars($topBotPlaytime); ?>
        <?php endif; ?>
      </div>
    </a>
    <div class="rot-stat info" title="Average equipped item level across tracked random bots at snapshot time.">
      <div class="val"><?php echo htmlspecialchars((string)$avgBotIlvl); ?></div>
      <div class="lbl">Avg Bot iLvl</div>
    </div>
    <div class="rot-stat info" title="Average equipped item level across all characters on the realm at snapshot time.">
      <div class="val"><?php echo htmlspecialchars((string)$avgServerIlvl); ?></div>
      <div class="lbl">Avg Server iLvl</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo htmlspecialchars($totalServerUptime); ?></div>
      <div class="lbl">Total Uptime</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo rotFormatSeconds($medianUptimeSec); ?></div>
      <div class="lbl">Median Uptime (7d)</div>
      <div class="meta"><?php echo $stableRuns7d; ?> stable runs kept</div>
    </div>
    <div class="rot-stat <?php echo $shortRestarts7d > 0 ? 'warn' : 'good'; ?>">
      <div class="val"><?php echo $shortRestarts7d; ?></div>
      <div class="lbl">Short Restarts (7d)</div>
      <div class="meta">Runs under 15m</div>
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
      <div class="val"><?php echo $actualOnlineShare !== null ? $actualOnlineShare . '%' : '—'; ?></div>
      <div class="lbl">Actual Online Share</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php echo ($cfgMinIn !== null && $cfgMaxIn !== null)
            ? rotFormatSeconds($cfgMinIn) . ' - ' . rotFormatSeconds($cfgMaxIn) : '—'; ?>
      </div>
      <div class="lbl">Session Window</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php echo ($cfgMinOff !== null && $cfgMaxOff !== null)
            ? rotFormatSeconds($cfgMinOff) . ' - ' . rotFormatSeconds($cfgMaxOff) : '—'; ?>
      </div>
      <div class="lbl">Offline Window</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php echo ($cfgMinBots !== null && $cfgMaxBots !== null) ? $cfgMinBots . ' - ' . $cfgMaxBots : '—'; ?>
      </div>
      <div class="lbl">Min / Max Online</div>
    </div>
    <div class="rot-stat muted">
      <div class="val"><?php echo ($cfgAccounts !== null && $cfgAccounts !== '') ? $cfgAccounts : '—'; ?></div>
      <div class="lbl">Bot Accounts</div>
    </div>
    <div class="rot-stat muted">
      <div class="val">
        <?php echo ($cfgRebalMin !== null && $cfgRebalMax !== null)
            ? rotFormatSeconds($cfgRebalMin) . ' - ' . rotFormatSeconds($cfgRebalMax) : '—'; ?>
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
    Current values reflect bots in their live state right now. Clean historical values ignore snapshots from the first 15 minutes after a restart, which helps keep crash loops from distorting the averages.
  </div>
  <div class="rot-stats">
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($liveAvgOnline); ?></div>
      <div class="lbl">Current Avg In World</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($cleanObsAvgOnline); ?></div>
      <div class="lbl">Clean Historical Avg In World</div>
      <div class="meta"><?php echo $cleanOnlineSessions; ?> sessions across <?php echo $cleanSnapshotCount; ?> snapshots</div>
    </div>
    <a class="rot-stat rot-stat-link info" href="<?php echo htmlspecialchars($longestOnlineUrl !== '' ? $longestOnlineUrl : '#'); ?>">
      <div class="val"><?php echo rotFormatSeconds($liveMaxOnline); ?></div>
      <div class="lbl">Longest In World Now</div>
      <div class="meta"><?php echo !empty($longestOnlineBot['bot_name']) ? htmlspecialchars((string)$longestOnlineBot['bot_name']) : 'No active bot found'; ?></div>
    </a>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($liveAvgOffline); ?></div>
      <div class="lbl">Current Avg Offline</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($cleanObsAvgOffline); ?></div>
      <div class="lbl">Clean Historical Avg Offline</div>
      <div class="meta"><?php echo $cleanOfflineSessions; ?> sessions across <?php echo $cleanSnapshotCount; ?> snapshots</div>
    </div>
    <a class="rot-stat rot-stat-link info" href="<?php echo htmlspecialchars($longestOfflineUrl !== '' ? $longestOfflineUrl : '#'); ?>">
      <div class="val"><?php echo rotFormatSeconds($liveMaxOffline); ?></div>
      <div class="lbl">Longest Offline Now</div>
      <div class="meta">
        <?php if (!empty($longestOfflineBot['bot_name'])): ?>
          <?php echo htmlspecialchars((string)$longestOfflineBot['bot_name']); ?> &middot; <?php echo $skippedSnapshotCount; ?> crash-adjacent snapshots skipped
        <?php else: ?>
          <?php echo $skippedSnapshotCount; ?> crash-adjacent snapshots skipped
        <?php endif; ?>
      </div>
    </a>
  </div>

  <div class="rot-stats">
    <div class="rot-stat info">
      <div class="val"><?php echo $restartsToday !== null ? (int)$restartsToday : '—'; ?></div>
      <div class="lbl">Restarts Today</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo rotFormatSeconds($currentRunSec); ?></div>
      <div class="lbl">Since Last Restart</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo $cleanOnlineSessions; ?></div>
      <div class="lbl">Clean Logoffs</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo $cleanOfflineSessions; ?></div>
      <div class="lbl">Clean Returns</div>
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
        <div class="seg rotating" style="flex:<?php echo $wRotating; ?>" title="Rotating active: <?php echo $rotating; ?>">
          <?php echo $wRotating >= 8 ? $rotating : ''; ?>
        </div>
      <?php endif; ?>
      <?php if ($wIdle > 0): ?>
        <div class="seg idle" style="flex:<?php echo $wIdle; ?>" title="Online idle: <?php echo $idle; ?>">
          <?php echo $wIdle >= 8 ? $idle : ''; ?>
        </div>
      <?php endif; ?>
      <?php if ($wCycled > 0): ?>
        <div class="seg cycled" style="flex:<?php echo $wCycled; ?>" title="Cycled off progressed: <?php echo $cycled; ?>">
          <?php echo $wCycled >= 8 ? $cycled : ''; ?>
        </div>
      <?php endif; ?>
      <?php if ($wCold > 0): ?>
        <div class="seg cold" style="flex:<?php echo $wCold; ?>" title="Never progressed: <?php echo $cold; ?>">
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

  <?php endif; ?>
</div>

<div class="rot-panel-grid">
  <div class="rot-panel rot-toolbox">
    <div class="rot-toolbox-title">Rotation Logging Controls</div>
    <p class="rot-toolbox-note">
      Rotation logging is a cron job in the DB container, not part of <code>mangosd.service</code>.
      <?php if ($isWindowsHost): ?>
        These Linux-host commands stay collapsed on Windows until you click one of the copy buttons below.
      <?php else: ?>
        These pause and resume commands are shown inline on Linux hosts for quick terminal use.
      <?php endif; ?>
    </p>
    <div class="rot-command-stack">
      <div class="rot-command-wrap">
        <p class="rot-command-caption">Pause Logging Command</p>
        <div class="rot-command<?php echo $isWindowsHost ? ' is-collapsed' : ''; ?>" id="rot-pause-command"><?php echo htmlspecialchars((string)($rotationCommands['pause_logging'] ?? '')); ?></div>
      </div>
      <div class="rot-command-wrap">
        <p class="rot-command-caption">Resume Logging Command</p>
        <div class="rot-command<?php echo $isWindowsHost ? ' is-collapsed' : ''; ?>" id="rot-resume-command"><?php echo htmlspecialchars((string)($rotationCommands['resume_logging'] ?? '')); ?></div>
      </div>
      <div class="rot-command-actions">
        <button type="button" class="rot-btn" onclick="copyRotationCommand('rot-pause-command', this)">Copy Pause Logging</button>
        <button type="button" class="rot-btn" onclick="copyRotationCommand('rot-resume-command', this)">Copy Resume Logging</button>
      </div>
    </div>
  </div>

  <div class="rot-panel rot-toolbox">
    <div class="rot-toolbox-title">Standalone Rotation Reset</div>
    <p class="rot-toolbox-note">
      Use this when you only want to clear rotation history for the selected realm without running the broader bot reset.
      <?php if ($isWindowsHost): ?>
        On Windows, the command blocks open only when you click the related copy button.
      <?php else: ?>
        On Linux, the command blocks stay visible so you can copy or run them directly.
      <?php endif; ?>
    </p>
    <div class="rot-command-stack">
      <div class="rot-command-wrap">
        <p class="rot-command-caption">Dry Run Command</p>
        <div class="rot-command<?php echo $isWindowsHost ? ' is-collapsed' : ''; ?>" id="rot-reset-dry"><?php echo htmlspecialchars((string)($rotationCommands['rotation_reset_dry_run'] ?? '')); ?></div>
      </div>
      <div class="rot-command-wrap">
        <p class="rot-command-caption">Reset Command</p>
        <div class="rot-command<?php echo $isWindowsHost ? ' is-collapsed' : ''; ?>" id="rot-reset-run"><?php echo htmlspecialchars((string)($rotationCommands['rotation_reset_run'] ?? '')); ?></div>
      </div>
      <div class="rot-command-actions">
        <button type="button" class="rot-btn" onclick="copyRotationCommand('rot-reset-dry', this)">Copy Rotation Dry Run</button>
        <button type="button" class="rot-btn" onclick="copyRotationCommand('rot-reset-run', this)">Copy Rotation Reset</button>
      </div>
    </div>
  </div>
</div>

<div class="rot-panel">
  <div class="rot-history">
    <div class="rot-history-title">Recent Snapshots
      <?php if (!$hasHistory): ?>
        &mdash; <span style="color:#444;font-style:italic;text-transform:none;letter-spacing:0">
            enable cron logging to see trends
          </span>
      <?php endif; ?>
    </div>
    <?php if ($hasHistory): ?>
    <table class="rot-history-table">
      <thead>
        <tr>
          <th>Time</th><th>Live %</th><th>Ever %</th><th>Cfg %</th>
          <th>Online</th><th>Rotating</th><th>Avg Lvl</th><th>Obs In</th><th>Obs Out</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historyRows as $row): ?>
        <tr>
          <td><?php echo rotFormatSnapshotTime($row['snapshot_time']); ?></td>
          <td class="<?php echo rotPctClass((float)$row['pct_online_rotating']); ?>"><?php echo $row['pct_online_rotating']; ?>%</td>
          <td class="<?php echo rotPctClass((float)$row['pct_ever_rotated']); ?>"><?php echo $row['pct_ever_rotated']; ?>%</td>
          <td><?php echo ($row['cfg_expected_online_pct'] !== null && $row['cfg_expected_online_pct'] !== '') ? $row['cfg_expected_online_pct'] . '%' : '&mdash;'; ?></td>
          <td><?php echo $row['total_online']; ?></td>
          <td><?php echo $row['rotating_active']; ?></td>
          <td><?php echo $row['avg_level_rotating'] ?? '&mdash;'; ?></td>
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
</div>
<script>
(function(){
  var isWindowsHost = <?php echo $isWindowsHost ? 'true' : 'false'; ?>;

  function revealRotationCommand(box, button) {
    if (!isWindowsHost || !box) {
      return;
    }
    if (box.classList.contains('is-collapsed')) {
      box.classList.remove('is-collapsed');
    }
    if (button) {
      button.setAttribute('aria-expanded', 'true');
    }
  }

  window.copyRotationCommand = function(id, button){
    var box = document.getElementById(id);
    if (!box) return;
    revealRotationCommand(box, button || null);
    var text = box.textContent || box.innerText || '';
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text);
      return;
    }
    var temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
  };
})();
</script>
</div>
<?php unset($GLOBALS['builddiv_header_actions']); ?>

