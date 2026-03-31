<?php
$botMaintenanceView = isset($botMaintenanceView) && is_array($botMaintenanceView) ? $botMaintenanceView : array();
$botFlash = $botMaintenanceView['flash'] ?? array();
$botManualNotice = (string)($botMaintenanceView['manual_notice'] ?? '');
$botManualCommand = (string)($botMaintenanceView['manual_command'] ?? '');
$botPageUrl = (string)($botMaintenanceView['page_url'] ?? 'index.php?n=admin&sub=bots');
$botSelectedRealmId = (int)($botMaintenanceView['selected_realm_id'] ?? 0);
$botSelectedPreview = $botMaintenanceView['selected_preview'] ?? array();
$botRealmOptions = $botMaintenanceView['realm_options'] ?? array();
$botHelperConfig = $botMaintenanceView['helper_config'] ?? array();
$botHelperStatus = $botMaintenanceView['helper_status'] ?? array();
$botLastRun = $botMaintenanceView['last_run'] ?? array();
$botPreviewRows = $botMaintenanceView['preview_rows'] ?? array();
$botAccountCounts = $botMaintenanceView['account_counts'] ?? array();
$botCacheCounts = $botMaintenanceView['cache_counts'] ?? array();
$botEventCounts = $botMaintenanceView['event_counts'] ?? array();
$botTotals = $botMaintenanceView['totals'] ?? array();
$botCsrfToken = (string)($botMaintenanceView['csrf_token'] ?? '');
?>
<?php builddiv_start(1, 'Bot Maintenance'); ?>
<style>
.admin-bots{color:#f4efe2;display:flex;flex-direction:column;gap:18px}
.admin-bots__panel,.admin-bots__table-wrap{padding:18px 20px;border:1px solid rgba(230,193,90,.22);border-radius:14px;background:linear-gradient(180deg,rgba(20,24,34,.82),rgba(10,12,18,.9));box-shadow:0 10px 30px rgba(0,0,0,.22)}
.admin-bots__eyebrow{margin:0 0 10px;color:#c9a45a;font-size:12px;letter-spacing:.18em;text-transform:uppercase}
.admin-bots__title{margin:0 0 8px;color:#ffca5a;font-size:1.4rem}
.admin-bots__body,.admin-bots__note{margin:0;color:#d6d0c4;line-height:1.6}
.admin-bots__list{margin:14px 0 0;padding-left:18px;color:#d6d0c4;line-height:1.6}
.admin-bots__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}
.admin-bots__mini{padding:12px 14px;border-radius:10px;background:rgba(255,198,87,.05);border:1px solid rgba(230,193,90,.12)}
.admin-bots__mini strong{display:block;color:#ffcc66;font-size:1.15rem}
.admin-bots__mini span{display:block;color:#d6d0c4;font-size:.92rem}
.admin-bots__actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.admin-bots__actions form{margin:0}
.admin-bots__select{min-width:220px;box-sizing:border-box;border-radius:10px;border:1px solid rgba(255,206,102,.2);background:rgba(12,12,12,.72);color:#f1f1f1;padding:10px 12px}
.admin-bots__btn,.admin-bots__btn-input{display:inline-flex;align-items:center;justify-content:center;padding:9px 16px;border-radius:10px;border:1px solid rgba(255,206,102,.35);background:rgba(255,193,72,.08);color:#ffd27a;text-decoration:none;font-weight:700;cursor:pointer}
.admin-bots__btn:hover,.admin-bots__btn-input:hover{color:#fff6dc;background:rgba(255,193,72,.18);box-shadow:0 0 10px rgba(255,193,72,.18)}
.admin-bots__btn-danger{border-color:rgba(227,117,117,.4);background:rgba(227,117,117,.12);color:#ffb4a8}
.admin-bots__btn-danger:hover{background:rgba(227,117,117,.2);color:#fff2ef}
.admin-bots__status{display:inline-block;padding:4px 10px;border-radius:999px;background:rgba(121,205,118,.12);color:#a7ef99}
.admin-bots__status--warn{background:rgba(214,102,102,.16);color:#ffaaa1}
.admin-bots__table{width:100%;border-collapse:collapse;margin-top:16px}
.admin-bots__table th,.admin-bots__table td{padding:11px 10px;border-bottom:1px solid rgba(255,255,255,.08);text-align:left;vertical-align:top}
.admin-bots__table th{color:#ffca5a;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase}
.admin-bots__flash{padding:12px 14px;border-radius:10px}
.admin-bots__flash--success{background:#17311f;border:1px solid #2f6a40;color:#bbf0c8}
.admin-bots__flash--error{background:#301010;border:1px solid rgba(210,100,100,.35);color:#ffb0b0}
.admin-bots__mono{font-family:Consolas,Monaco,monospace;font-size:.92rem;word-break:break-word}
.admin-bots__command{margin-top:12px;padding:10px;border-radius:8px;font-family:Consolas,Monaco,monospace;font-size:.82rem;white-space:pre-wrap;word-break:break-word;background:#111;color:#dbe9ff;border:1px solid rgba(255,255,255,.12)}
@media (max-width:900px){.admin-bots__table-wrap{overflow-x:auto}}
</style>

<div class="admin-bots">
  <?php if (!empty($botFlash['message'])): ?>
    <div class="admin-bots__flash <?php echo !empty($botFlash['type']) && $botFlash['type'] === 'success' ? 'admin-bots__flash--success' : 'admin-bots__flash--error'; ?>">
      <?php echo htmlspecialchars((string)$botFlash['message'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>
  <?php if ($botManualNotice !== '' || $botManualCommand !== ''): ?>
    <section class="admin-bots__panel">
      <p class="admin-bots__eyebrow">Manual PowerShell Fallback</p>
      <?php if ($botManualNotice !== ''): ?><p class="admin-bots__body"><?php echo htmlspecialchars($botManualNotice, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
      <?php if ($botManualCommand !== ''): ?>
        <div class="admin-bots__command" id="bot-manual-command-box"><?php echo htmlspecialchars($botManualCommand, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="admin-bots__actions">
          <button type="button" class="admin-bots__btn-input" onclick="copyBotManualCommand()">Copy Command</button>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Bot Maintenance</p>
    <h2 class="admin-bots__title">Fresh bot-world resets without touching player and GM accounts</h2>
    <p class="admin-bots__body">This page is the bot-only maintenance surface. It previews what belongs to the random-bot world, keeps protected human and GM data visible as untouched, and hands the risky drain/reset/restart work to a local trusted helper instead of asking you to shell into the host every time.</p>
    <ul class="admin-bots__list">
      <li>Preserved: player accounts, player characters, GM4 accounts, and normal website users.</li>
      <li>Reset scope: random bot characters, bot guilds, bot event and rotation state, bot DB-store layers, bot-linked identities, portrait/cache artifacts, and optionally the selected realm's forum footprint for a full fresh stack.</li>
      <li>New bot GUIDs are expected after repopulation, so website-facing bot layers should be rebuilt rather than migrated forward.</li>
    </ul>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Selected Realm</p>
    <form action="<?php echo htmlspecialchars($botPageUrl, ENT_QUOTES, 'UTF-8'); ?>" method="get" class="admin-bots__actions" style="margin-top:0;">
      <input type="hidden" name="n" value="admin">
      <input type="hidden" name="sub" value="bots">
      <select class="admin-bots__select" name="realm" onchange="this.form.submit()">
        <?php foreach ($botRealmOptions as $botRealmOption): ?>
          <option value="<?php echo (int)$botRealmOption['realm_id']; ?>"<?php echo (int)$botRealmOption['realm_id'] === $botSelectedRealmId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$botRealmOption['label'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Reset Preview</p>
    <p class="admin-bots__body">These counts show the selected realm's bot-reset footprint before any destructive action is sent to the helper. Protected counts stay visible for context, but the helper payload is realm-scoped.</p>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['bot_accounts'] ?? 0)); ?></strong><span><code>rndbot%</code> accounts in auth</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_characters'] ?? 0)); ?></strong><span>Bot characters on the selected realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_guilds'] ?? 0)); ?></strong><span>Bot guild memberships/guild shells</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_db_store_rows'] ?? 0)); ?></strong><span><code>ai_playerbot_db_store</code> rows tied to bots</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_auction_rows'] ?? 0)); ?></strong><span>Bot auction house rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_identities'] ?? 0)); ?></strong><span>Bot speaking identities</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_identity_profiles'] ?? 0)); ?></strong><span>Bot identity profile rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botEventCounts['website_bot_events'] ?? 0)); ?></strong><span>Bot event pipeline rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['rotation_log_rows'] ?? 0) + (int)($botSelectedPreview['rotation_state_rows'] ?? 0)); ?></strong><span>Bot rotation log/state rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botCacheCounts['portrait_files'] ?? 0)); ?></strong><span>Cached portrait files</span></div>
    </div>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['human_accounts'] ?? 0)); ?></strong><span>Human/player auth accounts left untouched</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['gm_accounts'] ?? 0)); ?></strong><span>GM4+ accounts preserved explicitly</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botTotals['player_characters'] ?? 0)); ?></strong><span>Player characters kept out of reset scope</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['website_users'] ?? 0)); ?></strong><span>Website users preserved</span></div>
    </div>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Forum Reset Preview</p>
    <p class="admin-bots__body">Forum reset is its own realm-scoped action. `Fresh Bot World Reset` still includes it, but you can also run just the selected realm forum reset when you want to rebuild forum-facing bot content without wiping the rest of the bot world. Official seeded posts from <code>SPP Team</code> / <code>web Team</code> are treated as preserved content.</p>
    <?php if (!empty($botSelectedPreview['realm_forum_ids']) && is_array($botSelectedPreview['realm_forum_ids'])): ?>
      <p class="admin-bots__note" style="margin-top:10px;">Included forum IDs for this realm: <span class="admin-bots__mono"><?php echo htmlspecialchars(implode(', ', array_map('intval', $botSelectedPreview['realm_forum_ids'])), ENT_QUOTES, 'UTF-8'); ?></span></p>
    <?php endif; ?>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['forum_topics'] ?? 0)); ?></strong><span>Selected realm forum topics</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['forum_posts'] ?? 0)); ?></strong><span>Selected realm forum posts</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['forum_pms'] ?? 0)); ?></strong><span>Selected realm PM rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_forum_topics'] ?? 0)); ?></strong><span>Bot-authored topics in that realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_forum_posts'] ?? 0)); ?></strong><span>Bot-authored posts in that realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['preserved_forum_topics'] ?? 0)); ?></strong><span>Preserved official topics</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['preserved_forum_posts'] ?? 0)); ?></strong><span>Preserved official posts</span></div>
      <div class="admin-bots__mini"><strong><?php echo htmlspecialchars((string)($botSelectedPreview['realm_name'] ?? ('Realm ' . $botSelectedRealmId)), ENT_QUOTES, 'UTF-8'); ?></strong><span>Forum reset scope stays on the selected realm only</span></div>
    </div>
    <div class="admin-bots__actions">
      <form method="post" action="<?php echo htmlspecialchars($botPageUrl, ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('This requests a forum reset for the selected realm only. Continue?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($botCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="realm" value="<?php echo (int)$botSelectedRealmId; ?>">
        <input type="hidden" name="bots_action" value="reset_forum_realm">
        <input class="admin-bots__btn-input" type="submit" value="Reset Selected Realm Forums">
      </form>
    </div>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Maintenance Actions</p>
    <p class="admin-bots__body">These actions follow the same workflow as the identity backfills and bot-event scripts: the page gives you one dedicated script to run for each action. `Fresh Bot World Reset` includes the selected realm forum reset as one of its phases.</p>
    <div class="admin-bots__actions">
      <form method="post" action="<?php echo htmlspecialchars($botPageUrl, ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('This requests a fresh bot-world reset. Player accounts and GM accounts stay untouched, but bot characters, bot guild state, bot events, caches, and the selected realm forum footprint will be treated as disposable. Continue?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($botCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="realm" value="<?php echo (int)$botSelectedRealmId; ?>">
        <input type="hidden" name="bots_action" value="fresh_reset">
        <input class="admin-bots__btn-input admin-bots__btn-danger" type="submit" value="Fresh Bot World Reset">
      </form>
      <form method="post" action="<?php echo htmlspecialchars($botPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($botCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="realm" value="<?php echo (int)$botSelectedRealmId; ?>">
        <input type="hidden" name="bots_action" value="rebuild_site_layers">
        <input class="admin-bots__btn-input" type="submit" value="Rebuild Bot Website Layers">
      </form>
    </div>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Higher-Risk / Infrastructure</p>
    <p class="admin-bots__body">The host-level drain, config edit, and restart path still needs to happen from the server side, but the website now keeps to one consistent script-driven workflow. Each script writes back into the shared maintenance state file so the page can show what last ran.</p>
    <div class="admin-bots__actions" style="margin-top:12px;">
      <span class="admin-bots__status<?php echo !empty($botHelperStatus['ok']) ? '' : ' admin-bots__status--warn'; ?>">
        <?php echo !empty($botHelperStatus['ok']) ? 'Status script ran' : 'No recent status run'; ?>
      </span>
      <a class="admin-bots__btn" href="<?php echo htmlspecialchars(spp_admin_bots_route_url(array('refresh_helper' => 1)), ENT_QUOTES, 'UTF-8'); ?>">Show Status Script</a>
    </div>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini">
        <strong>Script Workflow</strong>
        <span><?php echo htmlspecialchars((string)($botHelperConfig['display_name'] ?? 'Manual CLI / PowerShell Scripts'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="admin-bots__mini">
        <strong><?php echo htmlspecialchars((string)($botHelperStatus['checked_at'] ?? 'Never'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Last helper status check</span>
      </div>
      <div class="admin-bots__mini">
        <strong><?php echo htmlspecialchars((string)($botLastRun['label'] ?? 'No action yet'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Last requested maintenance action</span>
      </div>
      <div class="admin-bots__mini">
        <strong><?php echo htmlspecialchars((string)($botLastRun['ran_at'] ?? 'Never'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Last action timestamp</span>
      </div>
    </div>
    <p class="admin-bots__note" style="margin-top:14px;">Script contract: dedicated CLI tools named <code>bot_maintenance_status.php</code>, <code>reset_forum_realm.php</code>, <code>fresh_bot_reset.php</code>, and <code>rebuild_bot_site_layers.php</code>. Realm forum resets still preserve seeded official posts like <code>SPP Team</code> / <code>web Team</code>.</p>
    <?php if (!empty($botHelperStatus['summary'])): ?>
      <div class="admin-bots__note admin-bots__mono" style="margin-top:12px;">Helper says: <?php echo htmlspecialchars((string)$botHelperStatus['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($botHelperStatus['error'])): ?>
      <div class="admin-bots__note admin-bots__mono" style="margin-top:12px;color:#ffb0b0;">Helper error: <?php echo htmlspecialchars((string)$botHelperStatus['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($botLastRun['summary'])): ?>
      <div class="admin-bots__note admin-bots__mono" style="margin-top:12px;">Last run summary: <?php echo htmlspecialchars((string)$botLastRun['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($botLastRun['error'])): ?>
      <div class="admin-bots__note admin-bots__mono" style="margin-top:12px;color:#ffb0b0;">Last run error: <?php echo htmlspecialchars((string)$botLastRun['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
  </section>

  <section class="admin-bots__table-wrap">
    <p class="admin-bots__eyebrow">Per-Realm Preview</p>
    <p class="admin-bots__body">Each realm row shows the bot footprint that a fresh reset would clear or rebuild. This is meant as a quick realm comparison now, not a second copy of the identities health audit.</p>
    <table class="admin-bots__table">
      <thead>
        <tr>
          <th>Realm</th>
          <th>Bot Scope</th>
          <th>Forum / Identity</th>
          <th>Events / Rotation</th>
          <th>Protected</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($botPreviewRows as $botRow): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars((string)$botRow['realm_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
              <span class="admin-bots__mono">Realm <?php echo (int)$botRow['realm_id']; ?></span>
              <?php if (!empty($botRow['warning'])): ?><br><span class="admin-bots__mono" style="color:#ffb0b0;"><?php echo htmlspecialchars((string)$botRow['warning'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
            </td>
            <td>
              <?php echo number_format((int)$botRow['bot_characters']); ?> bot chars<br>
              <?php echo number_format((int)$botRow['bot_guilds']); ?> bot guilds<br>
              <?php echo number_format((int)$botRow['bot_db_store_rows']); ?> db-store rows<br>
              <?php echo number_format((int)$botRow['bot_auction_rows']); ?> AH rows
            </td>
            <td>
              <?php echo number_format((int)$botRow['forum_topics']); ?> topics / <?php echo number_format((int)$botRow['forum_posts']); ?> posts / <?php echo number_format((int)$botRow['forum_pms']); ?> PMs<br>
              <?php if (!empty($botRow['realm_forum_ids']) && is_array($botRow['realm_forum_ids'])): ?>forums: <?php echo htmlspecialchars(implode(', ', array_map('intval', $botRow['realm_forum_ids'])), ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
              <?php echo number_format((int)$botRow['bot_identities']); ?> bot identities / <?php echo number_format((int)$botRow['bot_identity_profiles']); ?> profile rows<br>
              <?php echo number_format((int)$botRow['bot_forum_topics']); ?> bot-authored topics / <?php echo number_format((int)$botRow['bot_forum_posts']); ?> bot-authored posts<br>
              <?php echo number_format((int)$botRow['preserved_forum_topics']); ?> preserved official topics / <?php echo number_format((int)$botRow['preserved_forum_posts']); ?> preserved official posts
            </td>
            <td>
              <?php echo number_format((int)$botRow['rotation_config_rows']); ?> rotation config<br>
              <?php echo number_format((int)$botRow['rotation_state_rows']); ?> live state rows<br>
              <?php echo number_format((int)$botRow['rotation_log_rows']); ?> history rows
            </td>
            <td>
              <?php echo number_format((int)$botRow['player_characters']); ?> player chars untouched
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
<script>
(function(){
  window.copyBotManualCommand = function(){
    var box = document.getElementById('bot-manual-command-box');
    if (!box) return;
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
<?php builddiv_end(); ?>
