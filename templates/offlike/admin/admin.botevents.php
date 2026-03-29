<br>
<?php builddiv_start(0, 'Bot Events Pipeline') ?>
<style>
.bot-stats { display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
.bot-stat  { background:#E8EEFA; border:1px solid #c0c0d0; border-radius:4px; padding:8px 16px; text-align:center; min-width:80px; }
.bot-stat strong { display:block; font-size:1.6em; }
.bot-stat.pending  strong { color:#d08800; }
.bot-stat.posted   strong { color:#3a8a3a; }
.bot-stat.skipped  strong { color:#888; }
.bot-stat.failed   strong { color:#c00; }
.bot-output { background:#111; color:#0f0; font-family:monospace; font-size:0.8em; padding:10px; white-space:pre-wrap; border-radius:4px; max-height:300px; overflow-y:auto; margin-bottom:12px; }
.bot-error  { background:#300; color:#f88; font-family:monospace; font-size:0.8em; padding:10px; white-space:pre-wrap; border-radius:4px; margin-bottom:12px; }
.bot-actions { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.bot-actions a { padding:6px 14px; border-radius:3px; text-decoration:none; font-size:0.9em; font-weight:bold; }
.btn-scan    { background:#4a7aaa; color:#fff; }
.btn-process { background:#4a9a4a; color:#fff; }
.btn-dry     { background:#888;    color:#fff; }
.btn-skip    { background:#c05030; color:#fff; }
.bot-actions a:hover { opacity:0.85; }
</style>

<div class="sections subsections" style="font-size:0.85em;">

  <div class="bot-stats">
    <?php foreach (['pending'=>'#d08800','posted'=>'#3a8a3a','skipped'=>'#888','failed'=>'#c00','processing'=>'#44a'] as $st => $col): ?>
    <div class="bot-stat <?php echo $st; ?>">
      <strong><?php echo (int)($botStats[$st] ?? 0); ?></strong>
      <?php echo ucfirst($st); ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bot-actions">
    <a class="btn-scan"    href="index.php?n=admin&sub=botevents&action=scan">Scan Now</a>
    <a class="btn-process" href="index.php?n=admin&sub=botevents&action=process">Process Now</a>
    <a class="btn-dry"     href="index.php?n=admin&sub=botevents&action=scan_dry">Scan (dry-run)</a>
    <a class="btn-dry"     href="index.php?n=admin&sub=botevents&action=process_dry">Process (dry-run)</a>
    <a class="btn-skip"    href="index.php?n=admin&sub=botevents&action=skip_all"
       onclick="return confirm('Mark ALL pending events as skipped?');">Skip All Pending</a>
  </div>

  <?php if ($botOutput !== ''): ?>
  <div class="bot-output"><?php echo htmlspecialchars($botOutput); ?></div>
  <?php endif; ?>

  <?php if ($botError !== ''): ?>
  <div class="bot-error"><?php echo htmlspecialchars($botError); ?></div>
  <?php endif; ?>

  <table border="0" cellspacing="1" cellpadding="4" width="100%" class="bordercolor">
    <tr class="catbg3">
      <td width="40"><b>#</b></td>
      <td><b>Type</b></td>
      <td width="40"><b>Realm</b></td>
      <td width="70"><b>Status</b></td>
      <td><b>Payload</b></td>
      <td><b>Occurred</b></td>
      <td><b>Processed</b></td>
      <td><b>Error</b></td>
    </tr>
    <?php if (empty($recentEvents)): ?>
    <tr><td colspan="8" class="windowbg" align="center"><i>No events yet.</i></td></tr>
    <?php endif; ?>
    <?php foreach ($recentEvents as $ev):
        $payload = json_decode($ev['payload_json'], true) ?? [];
        $summary = $payload['char_name'] ?? $payload['guild_name'] ?? '';
        $statusColor = [
            'pending'    => '#d08800',
            'posted'     => '#3a8a3a',
            'skipped'    => '#888',
            'failed'     => '#c00',
            'processing' => '#44a',
        ][$ev['status']] ?? '#000';
    ?>
    <tr>
      <td class="windowbg2"><?php echo (int)$ev['event_id']; ?></td>
      <td class="windowbg"><?php echo htmlspecialchars($ev['event_type']); ?></td>
      <td class="windowbg2" align="center"><?php echo (int)$ev['realm_id']; ?></td>
      <td class="windowbg2" style="color:<?php echo $statusColor; ?>;font-weight:bold;"><?php echo htmlspecialchars($ev['status']); ?></td>
      <td class="windowbg"><?php echo htmlspecialchars($summary); ?></td>
      <td class="windowbg2" nowrap><?php echo htmlspecialchars($ev['occurred_at']); ?></td>
      <td class="windowbg2" nowrap><?php echo $ev['processed_at'] ? htmlspecialchars($ev['processed_at']) : '—'; ?></td>
      <td class="windowbg" style="color:#c00;"><?php echo htmlspecialchars($ev['error_message'] ?? ''); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

</div>
<?php builddiv_end() ?>
