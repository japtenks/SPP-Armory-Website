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
?>
<style>
.rot-panel { width:100%; max-width:1100px; margin:28px auto 0; background:rgba(0,0,0,0.5); border:1px solid #333; border-radius:8px; box-shadow:inset 0 0 12px rgba(0,0,0,0.7); padding:20px 24px 24px; color:#ddd; }
.rot-title { font-size:1.15rem; font-weight:700; color:#e8c96a; text-shadow:0 0 8px rgba(232,201,106,0.3); margin-bottom:18px; letter-spacing:0.04em; border-bottom:1px solid #2a2a2a; padding-bottom:10px; }
.rot-error { background:rgba(255,60,60,0.08); border:1px solid #5a1a1a; border-radius:6px; padding:10px 14px; color:#f88; font-size:0.82rem; margin-bottom:12px; font-family:monospace; }
.rot-stats { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.rot-stat { flex:1 1 140px; background:rgba(255,255,255,0.03); border:1px solid #2c2c2c; border-radius:6px; padding:12px 14px; text-align:center; }
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
</style>
<br>
<?php builddiv_start(0, 'Bot Rotation Health') ?>

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
      <div class="meta">Playtime: <?php echo htmlspecialchars($topBotPlaytime); ?></div>
    </div>
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
    Current values reflect bots in their live state right now. Historical values are calculated from completed snapshot-to-snapshot transitions in <code>bot_rotation_state</code>.
  </div>
  <div class="rot-stats">
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($liveAvgOnline); ?></div>
      <div class="lbl">Current Avg In World</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($obsAvgOnline); ?></div>
      <div class="lbl">Historical Avg In World</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo rotFormatSeconds($liveMaxOnline); ?></div>
      <div class="lbl">Longest In World Now</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($liveAvgOffline); ?></div>
      <div class="lbl">Current Avg Offline</div>
    </div>
    <div class="rot-stat good">
      <div class="val"><?php echo rotFormatSeconds($obsAvgOffline); ?></div>
      <div class="lbl">Historical Avg Offline</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo rotFormatSeconds($liveMaxOffline); ?></div>
      <div class="lbl">Longest Offline Now</div>
    </div>
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
      <div class="val"><?php echo (int)$obsOnlineSessions; ?></div>
      <div class="lbl">Historical Logoffs</div>
    </div>
    <div class="rot-stat info">
      <div class="val"><?php echo (int)$obsOfflineSessions; ?></div>
      <div class="lbl">Historical Returns</div>
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
          <th>Time</th><th>Live %</th><th>Ever %</th><th>Cfg %</th>
          <th>Online</th><th>Rotating</th><th>Avg Lvl</th><th>Obs In</th><th>Obs Out</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historyRows as $row): ?>
        <tr>
          <td><?php echo date('M j H:i', strtotime($row['snapshot_time'])); ?></td>
          <td class="<?php echo rotPctClass((float)$row['pct_online_rotating']); ?>"><?php echo $row['pct_online_rotating']; ?>%</td>
          <td class="<?php echo rotPctClass((float)$row['pct_ever_rotated']); ?>"><?php echo $row['pct_ever_rotated']; ?>%</td>
          <td><?php echo ($row['cfg_expected_online_pct'] !== null && $row['cfg_expected_online_pct'] !== '') ? $row['cfg_expected_online_pct'] . '%' : '—'; ?></td>
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
</div>

<?php builddiv_end() ?>
