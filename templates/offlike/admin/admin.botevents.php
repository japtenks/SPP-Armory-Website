<br>
<?php builddiv_start(0, 'Bot Events Pipeline') ?>
<style>
.bot-stats { display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
.bot-stat  { background:#E8EEFA; border:1px solid #c0c0d0; border-radius:4px; padding:8px 16px; text-align:center; min-width:80px; }
.bot-stat strong { display:block; font-size:1.6em; }
.bot-stat small { display:block; margin-top:4px; color:#cbb896; font-size:0.78em; line-height:1.4; text-align:left; }
.bot-stat.pending  strong { color:#d08800; }
.bot-stat.posted   strong { color:#3a8a3a; }
.bot-stat.skipped  strong { color:#888; }
.bot-stat.failed   strong { color:#c00; }
.bot-output { background:#111; color:#0f0; font-family:monospace; font-size:0.8em; padding:10px; white-space:pre-wrap; border-radius:4px; max-height:300px; overflow-y:auto; margin-bottom:12px; }
.bot-error  { background:#300; color:#f88; font-family:monospace; font-size:0.8em; padding:10px; white-space:pre-wrap; border-radius:4px; margin-bottom:12px; }
.bot-notice-wrap { margin-bottom:12px; }
.bot-notice-copy {
  display:flex;
  gap:10px;
  align-items:flex-start;
  justify-content:space-between;
  padding:10px;
  border-radius:4px;
  border:1px solid #39506c;
  background:#1b2430;
}
.bot-notice-text { color:#dbe9ff; font-size:0.92em; margin-bottom:8px; }
.bot-command-box {
  flex:1 1 auto;
  background:#101723;
  color:#dbe9ff;
  font-family:monospace;
  font-size:0.8em;
  padding:10px;
  white-space:pre-wrap;
  border-radius:4px;
  border:1px solid rgba(255,255,255,0.12);
  word-break:break-all;
}
.bot-copy-btn {
  flex:0 0 auto;
  padding:8px 12px;
  border-radius:4px;
  border:1px solid #5f7ea2;
  background:#35506e;
  color:#fff;
  font-weight:bold;
  cursor:pointer;
}
.bot-copy-btn:hover { opacity:0.9; }
.bot-actions { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.bot-actions a { padding:6px 14px; border-radius:3px; text-decoration:none; font-size:0.9em; font-weight:bold; }
.bot-actions form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:0; }
.bot-process-form { display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; }
.bot-process-controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.bot-event-types {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
  gap:6px 12px;
  width:100%;
  padding:10px;
  border:1px solid rgba(255,255,255,0.12);
  border-radius:4px;
  background:rgba(0,0,0,0.18);
}
.bot-event-types label {
  display:flex;
  gap:6px;
  align-items:center;
  color:#f0e0b6;
  font-size:0.9em;
  white-space:nowrap;
}
.bot-actions input[type="text"] {
  padding:6px 10px;
  border-radius:3px;
  border:1px solid #666;
  background:#111;
  color:#fff;
  width:84px;
}
.bot-actions input[type="text"]::placeholder { color:#888; }
.bot-actions button {
  padding:6px 14px;
  border-radius:3px;
  border:0;
  text-decoration:none;
  font-size:0.9em;
  font-weight:bold;
  cursor:pointer;
}
.btn-scan    { background:#4a7aaa; color:#fff; }
.btn-process { background:#4a9a4a; color:#fff; }
.btn-dry     { background:#888;    color:#fff; }
.btn-skip    { background:#c05030; color:#fff; }
.bot-actions a:hover,
.bot-actions button:hover { opacity:0.85; }
</style>

<div class="sections subsections" style="font-size:0.85em;">

  <div class="bot-stats">
    <?php foreach (['pending'=>'#d08800','posted'=>'#3a8a3a','skipped'=>'#888','failed'=>'#c00','processing'=>'#44a'] as $st => $col): ?>
    <div class="bot-stat <?php echo $st; ?>">
      <strong><?php echo (int)($botStats[$st] ?? 0); ?></strong>
      <?php echo ucfirst($st); ?>
      <?php if ($st === 'pending' && !empty($pendingTypeBreakdown)): ?>
      <small>
        <?php foreach ($pendingTypeBreakdown as $pendingType): ?>
        <?php echo htmlspecialchars($pendingType['event_type']) . ': ' . (int)$pendingType['count']; ?><br>
        <?php endforeach; ?>
      </small>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bot-actions">
    <a class="btn-scan" href="<?php echo htmlspecialchars(spp_admin_botevents_action_url(array('n' => 'admin', 'sub' => 'botevents', 'action' => 'scan'))); ?>">Scan Now</a>
    <a class="btn-dry" href="<?php echo htmlspecialchars(spp_admin_botevents_action_url(array('n' => 'admin', 'sub' => 'botevents', 'action' => 'scan_dry'))); ?>">Scan (dry-run)</a>
    <form method="get" action="index.php" class="bot-process-form">
      <input type="hidden" name="n" value="admin">
      <input type="hidden" name="sub" value="botevents">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_botevents_csrf_token ?? spp_csrf_token('admin_botevents')); ?>">
      <div class="bot-process-controls">
        <input type="hidden" name="action" value="process">
        <input type="text" name="process_limit" value="<?php echo htmlspecialchars($processLimitValue); ?>" placeholder="10">
        <button type="submit" class="btn-process">Process Now</button>
        <button type="submit" name="action" value="process_dry" class="btn-dry">Process (dry-run)</button>
      </div>
      <?php if (!empty($availableEventTypes)): ?>
      <div class="bot-event-types">
        <?php foreach ($availableEventTypes as $eventType): ?>
        <label>
          <input type="checkbox" name="event_types[]" value="<?php echo htmlspecialchars($eventType); ?>"<?php echo in_array($eventType, $selectedEventTypes, true) ? ' checked' : ''; ?>>
          <span><?php echo htmlspecialchars($eventType); ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </form>
    <a class="btn-skip" href="<?php echo htmlspecialchars(spp_admin_botevents_action_url(array('n' => 'admin', 'sub' => 'botevents', 'action' => 'skip_all'))); ?>"
       onclick="return confirm('Mark ALL pending events as skipped?');">Skip All Pending</a>
  </div>

  <?php if ($botOutput !== ''): ?>
  <div class="bot-output"><?php echo htmlspecialchars($botOutput); ?></div>
  <?php endif; ?>

  <?php if ($botNotice !== '' || $botCommand !== ''): ?>
  <div class="bot-notice-wrap">
    <?php if ($botNotice !== ''): ?>
    <div class="bot-notice-text"><?php echo htmlspecialchars($botNotice); ?></div>
    <?php endif; ?>
    <?php if ($botCommand !== ''): ?>
    <div class="bot-notice-copy">
      <div class="bot-command-box" id="bot-command-box"><?php echo htmlspecialchars($botCommand); ?></div>
      <button type="button" class="bot-copy-btn" onclick="copyBotCommand()">Copy</button>
    </div>
    <?php endif; ?>
  </div>
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
<script>
function copyBotCommand() {
  var commandBox = document.getElementById('bot-command-box');
  if (!commandBox) return;
  var command = commandBox.textContent || commandBox.innerText || '';
  if (!command) return;

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(command);
    return;
  }

  var temp = document.createElement('textarea');
  temp.value = command;
  document.body.appendChild(temp);
  temp.select();
  document.execCommand('copy');
  document.body.removeChild(temp);
}
</script>
<?php builddiv_end() ?>
