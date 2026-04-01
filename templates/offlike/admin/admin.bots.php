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
$botScriptCommands = $botMaintenanceView['script_commands'] ?? array();
$botStepPreviews = $botMaintenanceView['step_previews'] ?? array();
$botPreviewRows = $botMaintenanceView['preview_rows'] ?? array();
$botAccountCounts = $botMaintenanceView['account_counts'] ?? array();
$botCacheCounts = $botMaintenanceView['cache_counts'] ?? array();
$botEventCounts = $botMaintenanceView['event_counts'] ?? array();
$botTotals = $botMaintenanceView['totals'] ?? array();
$botCsrfToken = (string)($botMaintenanceView['csrf_token'] ?? '');
$botIsWindowsHost = !empty($botMaintenanceView['is_windows_host']);
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
.admin-bots__command.is-collapsed{display:none}
@media (max-width:900px){.admin-bots__table-wrap{overflow-x:auto}}
</style>

<div class="admin-bots">
  <?php if (!empty($botFlash['message'])): ?>
    <div class="admin-bots__flash <?php echo !empty($botFlash['type']) && $botFlash['type'] === 'success' ? 'admin-bots__flash--success' : 'admin-bots__flash--error'; ?>">
      <?php echo htmlspecialchars((string)$botFlash['message'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>
  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Bot Maintenance</p>
    <h2 class="admin-bots__title">Three-step bot reset flow without touching player and GM accounts</h2>
    <p class="admin-bots__body">This page now follows the same script-first workflow as the identity backfills and bot-event tools. Pick a realm, review the three reset steps, run the dry-run command if you want a preview, then run the real command when you are ready.</p>
    <ul class="admin-bots__list">
      <li>Preserved: player accounts, player characters, GM4 accounts, and normal website users.</li>
      <li>Step 1 resets only the selected realm forums while preserving the official seed posts from <code>SPP Team</code> and <code>web Team</code>.</li>
      <li>Step 2 clears the website-side bot layer: bot events, bot identities, bot identity profiles, and portrait cache.</li>
      <li>Step 3 clears the realm-side bot layer: bot DB-store rows plus the character-side data you are preparing to rebuild from a fresh bot stack.</li>
      <li>New bot GUIDs are expected after repopulation, so website-facing bot layers should be rebuilt rather than migrated forward.</li>
    </ul>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini">
        <strong><?php echo htmlspecialchars((string)($botLastRun['label'] ?? 'No action yet'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Last script action</span>
      </div>
      <div class="admin-bots__mini">
        <strong><?php echo htmlspecialchars((string)($botLastRun['ran_at'] ?? 'Never'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Last action timestamp</span>
      </div>
      <div class="admin-bots__mini">
        <strong><?php echo !empty($botHelperStatus['ok']) ? 'Status script ran' : 'No recent status run'; ?></strong>
        <span>Workflow state</span>
      </div>
      <div class="admin-bots__mini">
        <strong>Server Note</strong>
        <span>Only do host repopulate while the game server is shut down.</span>
      </div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-status-command"><?php echo htmlspecialchars((string)($botScriptCommands['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-status-command', this)">Copy Status Command</button>
    </div>
    <?php if (!empty($botLastRun['summary'])): ?>
      <div class="admin-bots__note admin-bots__mono" style="margin-top:12px;">Last run summary: <?php echo htmlspecialchars((string)$botLastRun['summary'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($botLastRun['error'])): ?>
      <div class="admin-bots__note admin-bots__mono" style="margin-top:12px;color:#ffb0b0;">Last run error: <?php echo htmlspecialchars((string)$botLastRun['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
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
    <p class="admin-bots__body">These counts show the selected realm's overall bot-reset footprint before you run the three scripts below. Protected counts stay visible so it is obvious what is not part of the reset.</p>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['bot_accounts'] ?? 0)); ?></strong><span><code>rndbot%</code> accounts in auth</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_characters'] ?? 0)); ?></strong><span>Bot characters on the selected realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_guilds'] ?? 0)); ?></strong><span>Bot guild memberships/guild shells</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_db_store_rows'] ?? 0)); ?></strong><span><code>ai_playerbot_db_store</code> rows tied to bots</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_auction_rows'] ?? 0)); ?></strong><span>Bot auction house rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_identities'] ?? 0)); ?></strong><span>Bot speaking identities</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_identity_profiles'] ?? 0)); ?></strong><span>Bot identity profile rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botEventCounts['website_bot_events'] ?? 0)); ?></strong><span>Bot event pipeline rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['rotation_log_rows'] ?? 0) + (int)($botSelectedPreview['rotation_ilvl_log_rows'] ?? 0) + (int)($botSelectedPreview['rotation_state_rows'] ?? 0)); ?></strong><span>Bot rotation log/state rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botCacheCounts['portrait_files'] ?? 0)); ?></strong><span>Cached portrait files</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['guild_json_files'] ?? 0)); ?></strong><span>Guild summary JSON files on this realm</span></div>
    </div>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['human_accounts'] ?? 0)); ?></strong><span>Human/player auth accounts left untouched</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['gm_accounts'] ?? 0)); ?></strong><span>GM4+ accounts preserved explicitly</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botTotals['player_characters'] ?? 0)); ?></strong><span>Player characters kept out of reset scope</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['website_users'] ?? 0)); ?></strong><span>Website users preserved</span></div>
    </div>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Step 1</p>
    <h3 class="admin-bots__title" style="font-size:1.18rem;">Reset Selected Realm Forums</h3>
    <p class="admin-bots__body">Delete selected realm forum topics and posts except preserved official seed content. This includes the selected realm discussion board and its recruitment board when one exists.</p>
    <?php if (!empty($botSelectedPreview['realm_forum_ids']) && is_array($botSelectedPreview['realm_forum_ids'])): ?>
      <p class="admin-bots__note" style="margin-top:10px;">Included forum IDs for this realm: <span class="admin-bots__mono"><?php echo htmlspecialchars(implode(', ', array_map('intval', $botSelectedPreview['realm_forum_ids'])), ENT_QUOTES, 'UTF-8'); ?></span></p>
    <?php endif; ?>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['topics'] ?? 0)); ?></strong><span>Selected realm forum topics</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['posts'] ?? 0)); ?></strong><span>Selected realm forum posts</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['pms'] ?? 0)); ?></strong><span>Selected realm PM rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['bot_topics'] ?? 0)); ?></strong><span>Bot-authored topics in that realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['bot_posts'] ?? 0)); ?></strong><span>Bot-authored posts in that realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['preserved_topics'] ?? 0)); ?></strong><span>Preserved official topics</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['preserved_posts'] ?? 0)); ?></strong><span>Preserved official posts</span></div>
      <div class="admin-bots__mini"><strong><?php echo htmlspecialchars((string)($botSelectedPreview['realm_name'] ?? ('Realm ' . $botSelectedRealmId)), ENT_QUOTES, 'UTF-8'); ?></strong><span>Forum reset scope stays on the selected realm only</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step1-dry"><?php echo htmlspecialchars((string)($botScriptCommands['reset_forum_realm']['dry_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step1-run"><?php echo htmlspecialchars((string)($botScriptCommands['reset_forum_realm']['run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step1-dry', this)">Copy Dry Run</button>
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step1-run', this)">Copy Run Command</button>
    </div>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Step 2</p>
    <h3 class="admin-bots__title" style="font-size:1.18rem;">Clear Bot Web State</h3>
    <p class="admin-bots__body">Clear website bot event pipeline rows, bot-linked identities/profile rows, and bot-facing portrait cache. This is the website-side cleanup pass after the forum reset.</p>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['bot_events'] ?? 0)); ?></strong><span>Website bot event rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['bot_identities'] ?? 0)); ?></strong><span>Bot identities in this realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['bot_identity_profiles'] ?? 0)); ?></strong><span>Bot identity profiles in this realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['portrait_files'] ?? 0)); ?></strong><span>Portrait cache files to clear</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step2-dry"><?php echo htmlspecialchars((string)($botScriptCommands['clear_bot_web_state']['dry_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step2-run"><?php echo htmlspecialchars((string)($botScriptCommands['clear_bot_web_state']['run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step2-dry', this)">Copy Dry Run</button>
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step2-run', this)">Copy Run Command</button>
    </div>
  </section>

  <section class="admin-bots__panel">
    <p class="admin-bots__eyebrow">Step 3</p>
    <h3 class="admin-bots__title" style="font-size:1.18rem;">Clear Bot Character State</h3>
    <p class="admin-bots__body">Clear bot DB-store rows and prepare the selected realm for a fresh bot stack. This is the realm-side cleanup pass before host shutdown, restart, and repopulation.</p>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_characters'] ?? 0)); ?></strong><span>Bot characters on this realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_guilds'] ?? 0)); ?></strong><span>Bot guild shells / memberships</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_db_store_rows'] ?? 0)); ?></strong><span>Bot DB-store rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_auction_rows'] ?? 0)); ?></strong><span>Bot auction rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['guild_json_files'] ?? 0)); ?></strong><span>Guild summary JSON files to clear</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['rotation_state_rows'] ?? 0)); ?></strong><span>Rotation live-state rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['rotation_log_rows'] ?? 0)); ?></strong><span>Rotation history rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['rotation_ilvl_log_rows'] ?? 0)); ?></strong><span>Rotation ilvl history rows</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step3-dry"><?php echo htmlspecialchars((string)($botScriptCommands['clear_bot_character_state']['dry_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step3-run"><?php echo htmlspecialchars((string)($botScriptCommands['clear_bot_character_state']['run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step3-dry', this)">Copy Dry Run</button>
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step3-run', this)">Copy Run Command</button>
    </div>
    <p class="admin-bots__note" style="margin-top:14px;color:#ffbfa8;">Danger zone: this next variant clears every character on the selected realm, including player characters, while leaving auth accounts in place.</p>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['realm_characters'] ?? 0)); ?></strong><span>All realm characters to clear</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['player_characters'] ?? 0)); ?></strong><span>Player characters included</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['bot_characters'] ?? 0)); ?></strong><span>Bot characters included</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['realm_guilds'] ?? 0)); ?></strong><span>All guild rows on this realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['realm_db_store_rows'] ?? 0)); ?></strong><span>All <code>ai_playerbot_db_store</code> rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['realm_auction_rows'] ?? 0)); ?></strong><span>All auction house rows on this realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['guild_json_files'] ?? 0)); ?></strong><span>Guild summary JSON files to clear</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['rotation_state_rows'] ?? 0)); ?></strong><span>Rotation live-state rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['realm_character_state']['rotation_log_rows'] ?? 0) + (int)($botStepPreviews['realm_character_state']['rotation_ilvl_log_rows'] ?? 0)); ?></strong><span>Rotation history rows</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step3-all-dry"><?php echo htmlspecialchars((string)($botScriptCommands['clear_realm_character_state']['dry_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step3-all-run"><?php echo htmlspecialchars((string)($botScriptCommands['clear_realm_character_state']['run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input admin-bots__btn-danger" onclick="copyBotCommand('bot-step3-all-dry', this)">Copy Full-Realm Dry Run</button>
      <button type="button" class="admin-bots__btn-input admin-bots__btn-danger" onclick="copyBotCommand('bot-step3-all-run', this)">Copy Full-Realm Run</button>
    </div>
    <p class="admin-bots__note" style="margin-top:12px;">Use this only when you want a realm character wipe that keeps login accounts. Characters, guilds, auctions, and character-side bot store data on the selected realm are included.</p>
    <p class="admin-bots__note" style="margin-top:12px;">If you only want to clear the rotation history during a restart window, use the dedicated rotation reset instead of the full character-state reset.</p>
    <div class="admin-bots__grid" style="margin-top:14px;">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['rotation_only']['rotation_state_rows'] ?? 0)); ?></strong><span>Rotation live-state rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['rotation_only']['rotation_log_rows'] ?? 0)); ?></strong><span>Rotation history rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['rotation_only']['rotation_ilvl_log_rows'] ?? 0)); ?></strong><span>Rotation ilvl history rows</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-rotation-dry"><?php echo htmlspecialchars((string)($botScriptCommands['reset_bot_rotation_realm']['dry_run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-rotation-run"><?php echo htmlspecialchars((string)($botScriptCommands['reset_bot_rotation_realm']['run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-rotation-dry', this)">Copy Rotation Dry Run</button>
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-rotation-run', this)">Copy Rotation Run</button>
    </div>
    <p class="admin-bots__note" style="margin-top:12px;">Host repopulate comes after this step. Only perform the shutdown/restart/repopulate portion while the world server is offline.</p>
    <p class="admin-bots__note" style="margin-top:12px;">The rebuild command set now includes identity backfills plus a guild recruitment seed pass, so unknown guild summary JSONs can become active recruitment threads again after a wipe.</p>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step4-run"><?php echo htmlspecialchars((string)($botScriptCommands['rebuild_site_layers']['run'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" onclick="copyBotCommand('bot-step4-run', this)">Copy Rebuild Layers Command</button>
    </div>
  </section>

  <section class="admin-bots__table-wrap">
    <p class="admin-bots__eyebrow">Per-Realm Preview</p>
    <p class="admin-bots__body">Each available realm row shows the bot footprint that a fresh reset would clear or rebuild. Unreachable realms are hidden here so this stays focused on the realms you can act on right now.</p>
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
              <?php echo number_format((int)$botRow['bot_auction_rows']); ?> AH rows<br>
              <?php echo number_format((int)$botRow['guild_json_files']); ?> guild json files
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
              <?php echo number_format((int)$botRow['rotation_log_rows']); ?> history rows<br>
              <?php echo number_format((int)$botRow['rotation_ilvl_log_rows']); ?> ilvl history rows
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
  var isWindowsHost = <?php echo $botIsWindowsHost ? 'true' : 'false'; ?>;
  window.copyBotCommand = function(id, button){
    var box = document.getElementById(id);
    if (!box) return;
    if (isWindowsHost && box.classList.contains('is-collapsed')) {
      box.classList.remove('is-collapsed');
    }
    if (button) {
      button.setAttribute('aria-expanded', 'true');
    }
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
