<?php builddiv_start(0, 'Forum Identity Coverage') ?>
<style>
.identity-coverage { color:#f4efe2; }
.identity-summary { display:flex; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.identity-stat {
  background: linear-gradient(180deg, rgba(20,24,34,0.92), rgba(10,12,18,0.95));
  border:1px solid rgba(230,193,90,0.18);
  border-radius:12px;
  padding:12px 16px;
  min-width:150px;
  box-shadow:0 10px 24px rgba(0,0,0,0.18);
}
.identity-stat strong {
  display:block;
  color:#ffcc66;
  font-size:1.75em;
}
.identity-stat span {
  display:block;
  color:#d6d0c4;
  font-size:0.88em;
  line-height:1.4;
}
.identity-intro,
.identity-error {
  margin-bottom:16px;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid rgba(230,193,90,0.18);
  background:rgba(10,12,18,0.7);
  line-height:1.6;
}
.identity-error {
  color:#ffd2d2;
  border-color:rgba(210,100,100,0.28);
  background:rgba(48,14,14,0.75);
}
.identity-table {
  width:100%;
  border-collapse:separate;
  border-spacing:0 8px;
}
.identity-table th {
  text-align:left;
  color:#c9a45a;
  font-size:0.82em;
  letter-spacing:0.08em;
  text-transform:uppercase;
  padding:0 10px 4px;
}
.identity-table td {
  padding:12px 10px;
  background:linear-gradient(180deg, rgba(20,24,34,0.82), rgba(10,12,18,0.9));
  border-top:1px solid rgba(230,193,90,0.16);
  border-bottom:1px solid rgba(230,193,90,0.16);
  vertical-align:top;
}
.identity-table td:first-child {
  border-left:1px solid rgba(230,193,90,0.16);
  border-top-left-radius:10px;
  border-bottom-left-radius:10px;
}
.identity-table td:last-child {
  border-right:1px solid rgba(230,193,90,0.16);
  border-top-right-radius:10px;
  border-bottom-right-radius:10px;
}
.identity-health {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:92px;
  padding:6px 10px;
  border-radius:999px;
  font-weight:bold;
  font-size:0.78em;
  text-transform:uppercase;
  letter-spacing:0.08em;
}
.identity-health.ok {
  color:#dff7d9;
  background:rgba(60,140,70,0.2);
  border:1px solid rgba(90,180,100,0.28);
}
.identity-health.attention {
  color:#fff0bf;
  background:rgba(181,126,20,0.2);
  border:1px solid rgba(219,165,60,0.28);
}
.identity-health.error {
  color:#ffd2d2;
  background:rgba(175,55,55,0.22);
  border:1px solid rgba(210,100,100,0.28);
}
.identity-realm {
  font-weight:bold;
  color:#ffca5a;
}
.identity-command-box {
  margin-top:8px;
  padding:10px;
  border-radius:8px;
  background:#101723;
  color:#dbe9ff;
  font-family:monospace;
  font-size:0.78em;
  white-space:pre-wrap;
  border:1px solid rgba(255,255,255,0.12);
  word-break:break-all;
}
.identity-note {
  color:#d6d0c4;
  font-size:0.88em;
  line-height:1.5;
}
.identity-output {
  background:#111;
  color:#0f0;
  font-family:monospace;
  font-size:0.8em;
  padding:10px;
  white-space:pre-wrap;
  border-radius:8px;
  max-height:300px;
  overflow-y:auto;
  margin-bottom:16px;
}
.identity-run-error {
  background:#300;
  color:#f88;
  font-family:monospace;
  font-size:0.8em;
  padding:10px;
  white-space:pre-wrap;
  border-radius:8px;
  margin-bottom:16px;
}
.identity-notice-wrap { margin-bottom:16px; }
.identity-notice-copy {
  display:flex;
  gap:10px;
  align-items:flex-start;
  justify-content:space-between;
  padding:10px;
  border-radius:8px;
  border:1px solid #39506c;
  background:#1b2430;
}
.identity-notice-text { color:#dbe9ff; font-size:0.92em; margin-bottom:8px; }
.identity-copy-btn {
  flex:0 0 auto;
  padding:8px 12px;
  border-radius:4px;
  border:1px solid #5f7ea2;
  background:#35506e;
  color:#fff;
  font-weight:bold;
  cursor:pointer;
}
.identity-copy-btn:hover { opacity:0.9; }
.identity-actions {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:10px;
}
.identity-actions a {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 12px;
  border-radius:8px;
  text-decoration:none;
  font-size:0.85em;
  font-weight:bold;
  border:1px solid rgba(230,193,90,0.18);
  background:rgba(255,193,72,0.08);
  color:#ffd27a;
}
.identity-actions a:hover {
  color:#fff6dc;
  background:rgba(255,193,72,0.18);
}
@media (max-width: 980px) {
  .identity-table {
    display:block;
    overflow-x:auto;
  }
}
</style>

<?php
$identityRows = $identityCoverage['rows'] ?? [];
$identityTotals = $identityCoverage['totals'] ?? [];
$identityErrors = $identityCoverage['errors'] ?? [];
?>

<div class="identity-coverage">
  <div class="identity-intro">
    This page reports forum identity coverage by realm so we can spot where a new realm or imported bot set is missing `website_identities` links for posting, topics, or PM ownership. The counts are read-only and are meant to tell you which backfill script to run next.
  </div>

  <div class="identity-summary">
    <div class="identity-stat">
      <strong><?php echo number_format((int)($identityTotals['missing_account_identities'] ?? 0)); ?></strong>
      <span>Missing forum account identities</span>
    </div>
    <div class="identity-stat">
      <strong><?php echo number_format((int)($identityTotals['missing_character_identities'] ?? 0)); ?></strong>
      <span>Missing forum character identities</span>
    </div>
    <div class="identity-stat">
      <strong><?php echo number_format((int)($identityTotals['posts_missing_identity'] ?? 0)); ?></strong>
      <span>Forum posts missing poster identity</span>
    </div>
    <div class="identity-stat">
      <strong><?php echo number_format((int)($identityTotals['topics_missing_identity'] ?? 0)); ?></strong>
      <span>Topics missing poster identity</span>
    </div>
    <div class="identity-stat">
      <strong><?php echo number_format((int)($identityTotals['pms_missing_identity'] ?? 0)); ?></strong>
      <span>PM rows missing account identity</span>
    </div>
  </div>

  <?php if (!empty($identityErrors)): ?>
  <div class="identity-error">
    <?php echo htmlspecialchars(implode(' | ', $identityErrors)); ?>
  </div>
  <?php endif; ?>

  <?php if ($identityOutput !== ''): ?>
  <div class="identity-output"><?php echo htmlspecialchars($identityOutput); ?></div>
  <?php endif; ?>

  <?php if ($identityNotice !== '' || $identityCommand !== ''): ?>
  <div class="identity-notice-wrap">
    <?php if ($identityNotice !== ''): ?>
    <div class="identity-notice-text"><?php echo htmlspecialchars($identityNotice); ?></div>
    <?php endif; ?>
    <?php if ($identityCommand !== ''): ?>
    <div class="identity-notice-copy">
      <div class="identity-command-box" id="identity-command-box"><?php echo htmlspecialchars($identityCommand); ?></div>
      <button type="button" class="identity-copy-btn" onclick="copyIdentityCommand()">Copy</button>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($identityError !== ''): ?>
  <div class="identity-run-error"><?php echo htmlspecialchars($identityError); ?></div>
  <?php endif; ?>

  <table class="identity-table">
    <tr>
      <th>Realm</th>
      <th>Status</th>
      <th>Missing Accounts</th>
      <th>Missing Characters</th>
      <th>Posts</th>
      <th>Topics</th>
      <th>PMs</th>
      <th>Suggested Commands</th>
    </tr>
    <?php if (empty($identityRows)): ?>
    <tr>
      <td colspan="8">No realms configured.</td>
    </tr>
    <?php endif; ?>
    <?php foreach ($identityRows as $row): ?>
    <tr>
      <td>
        <div class="identity-realm"><?php echo htmlspecialchars((string)$row['realm_name']); ?></div>
        <div class="identity-note">Realm ID <?php echo (int)$row['realm_id']; ?></div>
      </td>
      <td>
        <span class="identity-health <?php echo htmlspecialchars((string)$row['health']); ?>">
          <?php echo $row['health'] === 'ok' ? 'Healthy' : ($row['health'] === 'error' ? 'Error' : 'Needs Backfill'); ?>
        </span>
        <?php if (!empty($row['error'])): ?>
        <div class="identity-note" style="margin-top:8px;"><?php echo htmlspecialchars((string)$row['error']); ?></div>
        <?php endif; ?>
      </td>
      <td><?php echo number_format((int)$row['missing_account_identities']); ?></td>
      <td><?php echo number_format((int)$row['missing_character_identities']); ?></td>
      <td><?php echo number_format((int)$row['posts_missing_identity']); ?></td>
      <td><?php echo number_format((int)$row['topics_missing_identity']); ?></td>
      <td><?php echo number_format((int)$row['pms_missing_identity']); ?></td>
      <td>
        <div class="identity-note">Run the account/character backfill first, then forum posts/topics, then PMs if needed.</div>
        <div class="identity-actions">
          <a href="<?php echo htmlspecialchars(spp_admin_identities_action_url(['n' => 'admin', 'sub' => 'identities', 'action' => 'run_backfill', 'realm' => (int)$row['realm_id'], 'type' => 'identities'])); ?>">Account + Character</a>
          <a href="<?php echo htmlspecialchars(spp_admin_identities_action_url(['n' => 'admin', 'sub' => 'identities', 'action' => 'run_backfill', 'realm' => (int)$row['realm_id'], 'type' => 'posts'])); ?>">Posts + Topics</a>
          <a href="<?php echo htmlspecialchars(spp_admin_identities_action_url(['n' => 'admin', 'sub' => 'identities', 'action' => 'run_backfill', 'realm' => (int)$row['realm_id'], 'type' => 'pms'])); ?>">PMs</a>
          <a href="<?php echo htmlspecialchars(spp_admin_identities_action_url(['n' => 'admin', 'sub' => 'identities', 'action' => 'run_backfill', 'realm' => (int)$row['realm_id'], 'type' => 'all'])); ?>">Run All</a>
        </div>
        <div class="identity-command-box"><?php echo htmlspecialchars(implode("\n", $row['commands'])); ?></div>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<script>
function copyIdentityCommand() {
  var commandBox = document.getElementById('identity-command-box');
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
