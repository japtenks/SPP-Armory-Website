<?php
$identityHealthView = isset($identityHealthView) && is_array($identityHealthView) ? $identityHealthView : array();
$identityCoverageRows = $identityHealthView['coverage_rows'] ?? array();
$identityCoverageSelected = $identityHealthView['coverage_selected'] ?? array();
$identityCoverageSummary = $identityHealthView['coverage_summary'] ?? array();
$identityMismatches = $identityHealthView['mismatches'] ?? array();
$identityResetPreview = $identityHealthView['reset_preview'] ?? array();
$identitySkippedRealms = $identityHealthView['skipped_realms'] ?? array();
$identityRealmOptions = $identityHealthView['realm_options'] ?? array();
$identitySelectedRealmId = (int)($identityHealthView['selected_realm_id'] ?? 0);
$identityCanonicalUrl = (string)($identityHealthView['canonical_url'] ?? 'index.php?n=admin&sub=identities');
$identityCsrfToken = (string)($identityHealthView['csrf_token'] ?? '');
$identityBackfill = $identityHealthView['backfill'] ?? array();
$identityIsWindowsHost = !empty($identityHealthView['is_windows_host']);

if (!function_exists('spp_admin_identity_health_template_coverage_text')) {
    function spp_admin_identity_health_template_coverage_text($covered, $total) {
        $covered = (int)$covered;
        $total = (int)$total;
        if ($total <= 0) {
            return 'No rows yet';
        }
        return number_format($covered) . ' / ' . number_format($total) . ' covered';
    }
}
?>
<?php builddiv_start(1, 'Identity & Data Health') ?>
<style>
.admin-identity-health{color:#f4efe2;display:flex;flex-direction:column;gap:18px}
.admin-identity-health__panel,.admin-identity-health__grid>section,.admin-identity-health__table-wrap,.admin-identity-health__warning{padding:18px 20px;border:1px solid rgba(230,193,90,.22);border-radius:14px;background:linear-gradient(180deg,rgba(20,24,34,.82),rgba(10,12,18,.9));box-shadow:0 10px 30px rgba(0,0,0,.22)}
.admin-identity-health__eyebrow{margin:0 0 10px;color:#c9a45a;font-size:12px;letter-spacing:.18em;text-transform:uppercase}
.admin-identity-health__title{margin:0 0 8px;color:#ffca5a;font-size:1.4rem}
.admin-identity-health__body,.admin-identity-health__note,.admin-identity-health__hint{margin:0;color:#d6d0c4;line-height:1.6}
.admin-identity-health__list{margin:14px 0 0;padding-left:18px;color:#d6d0c4;line-height:1.6}
.admin-identity-health__grid{display:flex;flex-direction:column;gap:18px}
.admin-identity-health__metric{margin:0 0 6px;color:#ffcc66;font-size:2rem;font-weight:700}
.admin-identity-health__label{margin:0 0 12px;color:#f5f1e7;font-weight:600}
.admin-identity-health__subgrid,.admin-identity-health__summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 14px;margin-top:14px}
.admin-identity-health__summary{grid-template-columns:repeat(3,minmax(0,1fr))}
.admin-identity-health__mini{padding:12px 14px;border-radius:10px;background:rgba(255,198,87,.05);border:1px solid rgba(230,193,90,.12)}
.admin-identity-health__mini strong{display:block;color:#ffcc66;font-size:1.15rem}
.admin-identity-health__mini span{display:block;color:#d6d0c4;font-size:.92rem}
.admin-identity-health__actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.admin-identity-health__actions form{margin:0}
.admin-identity-health__btn,.admin-identity-health__btn-input{display:inline-flex;align-items:center;justify-content:center;padding:9px 16px;border-radius:10px;border:1px solid rgba(255,206,102,.35);background:rgba(255,193,72,.08);color:#ffd27a;text-decoration:none;font-weight:700;cursor:pointer}
.admin-identity-health__btn:hover,.admin-identity-health__btn-input:hover{color:#fff6dc;background:rgba(255,193,72,.18);box-shadow:0 0 10px rgba(255,193,72,.18)}
.admin-identity-health__btn[aria-disabled=true]{pointer-events:none;opacity:.55}
.admin-identity-health__select{min-width:220px;box-sizing:border-box;border-radius:10px;border:1px solid rgba(255,206,102,.2);background:rgba(12,12,12,.72);color:#f1f1f1;padding:10px 12px}
.admin-identity-health__table{width:100%;border-collapse:collapse;margin-top:16px}
.admin-identity-health__table th,.admin-identity-health__table td{padding:11px 10px;border-bottom:1px solid rgba(255,255,255,.08);text-align:left;vertical-align:top}
.admin-identity-health__table th{color:#ffca5a;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase}
.admin-identity-health__status{display:inline-block;padding:4px 10px;border-radius:999px;background:rgba(121,205,118,.12);color:#a7ef99}
.admin-identity-health__status--warn{background:rgba(214,102,102,.16);color:#ffaaa1}
.admin-identity-health__mono{font-family:Consolas,Monaco,monospace;font-size:.92rem}
.admin-identity-health__output,.admin-identity-health__error,.admin-identity-health__command{margin-top:12px;padding:10px;border-radius:8px;font-family:Consolas,Monaco,monospace;font-size:.82rem;white-space:pre-wrap;word-break:break-word}
.admin-identity-health__output{background:#101723;color:#dbe9ff;border:1px solid rgba(95,126,162,.35)}
.admin-identity-health__error{background:#301010;color:#ffb0b0;border:1px solid rgba(210,100,100,.35)}
.admin-identity-health__command{background:#111;color:#dbe9ff;border:1px solid rgba(255,255,255,.12)}
.admin-identity-health__command.is-collapsed{display:none}
.admin-identity-health__command-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px}
@media (max-width:900px){.admin-identity-health__summary,.admin-identity-health__subgrid{grid-template-columns:1fr}.admin-identity-health__table-wrap{overflow-x:auto}}
</style>

<div class="admin-identity-health">
  <section class="admin-identity-health__panel">
    <p class="admin-identity-health__eyebrow">Identity & Data Health</p>
    <h2 class="admin-identity-health__title">One place for ownership, speaking identities, backfills, and repair planning</h2>
    <p class="admin-identity-health__body">This page merges the old cleanup and identity coverage views. <code>website_accounts</code> and account rows represent who owns a login and profile. <code>website_identities</code> represents who appears publicly on forums and PMs. The goal is to confirm those two layers still line up, highlight anything stale or partially migrated, and keep higher-risk reset work clearly separated from routine repair actions.</p>
    <ul class="admin-identity-health__list">
      <li>Humans usually own content through an account, but may speak as an account or as a selected character.</li>
      <li>Bots may not have a normal player-owned account, but still need valid forum and PM identities to speak consistently.</li>
      <li>The safest workflow is: inspect health, run backfills when coverage is broadly missing, then repair only the affected slice.</li>
    </ul>
    <div class="admin-identity-health__actions">
      <a class="admin-identity-health__btn" href="<?php echo htmlspecialchars($identityCanonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">Identity &amp; Data Health</a>
    </div>
    <?php if (!empty($identityBackfill['notice'])) { ?><div class="admin-identity-health__note" style="margin-top:12px;"><?php echo htmlspecialchars((string)$identityBackfill['notice'], ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
    <?php if (!empty($identityBackfill['command'])) { ?>
      <?php if ($identityIsWindowsHost) { ?>
        <div class="admin-identity-health__command-actions">
          <button type="button" class="admin-identity-health__btn-input" onclick="identityHealthRevealAndCopyCommand('identity-backfill-command', this)">Copy Backfill Command<?php echo strpos((string)$identityBackfill['command'], "\n") !== false ? 's' : ''; ?></button>
        </div>
        <div class="admin-identity-health__command is-collapsed" id="identity-backfill-command"><?php echo htmlspecialchars((string)$identityBackfill['command'], ENT_QUOTES, 'UTF-8'); ?></div>
      <?php } else { ?>
        <div class="admin-identity-health__command"><?php echo htmlspecialchars((string)$identityBackfill['command'], ENT_QUOTES, 'UTF-8'); ?></div>
      <?php } ?>
    <?php } ?>
    <?php if (!empty($identityBackfill['output'])) { ?><div class="admin-identity-health__output"><?php echo htmlspecialchars((string)$identityBackfill['output'], ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
    <?php if (!empty($identityBackfill['error'])) { ?><div class="admin-identity-health__error"><?php echo htmlspecialchars((string)$identityBackfill['error'], ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
  </section>

  <section class="admin-identity-health__panel">
    <p class="admin-identity-health__eyebrow">Selected Realm</p>
    <form action="<?php echo htmlspecialchars($identityCanonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" method="get" class="admin-identity-health__actions" style="margin-top:0;">
      <input type="hidden" name="n" value="admin" />
      <input type="hidden" name="sub" value="identities" />
      <select class="admin-identity-health__select" name="identity_realm_id" onchange="this.form.submit()">
        <?php foreach ($identityRealmOptions as $identityRealmId => $identityRealmName) { ?>
          <option value="<?php echo (int)$identityRealmId; ?>"<?php if ((int)$identityRealmId === $identitySelectedRealmId) echo ' selected'; ?>><?php echo htmlspecialchars($identityRealmName, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php } ?>
      </select>
    </form>
    <div class="admin-identity-health__summary">
      <div class="admin-identity-health__mini"><strong><?php echo (int)($identityCoverageSummary['available_realms'] ?? 0); ?></strong><span>Realms scanned successfully</span></div>
      <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityCoverageSummary['total_account_identities'] ?? 0)); ?></strong><span>Account identities across all realms</span></div>
      <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityCoverageSummary['total_character_identities'] ?? 0) + (int)($identityCoverageSummary['total_bot_identities'] ?? 0)); ?></strong><span>Character-facing identities across all realms</span></div>
    </div>
  </section>

  <?php if (!empty($identitySkippedRealms)) { ?>
  <section class="admin-identity-health__warning">
    <p class="admin-identity-health__eyebrow">Skipped Realms</p>
    <p class="admin-identity-health__body">Unavailable realms are shown as warnings instead of breaking the page, so we can still audit what is reachable right now.</p>
    <div class="admin-identity-health__subgrid">
      <?php foreach ($identitySkippedRealms as $identitySkippedRealm) { ?>
      <div class="admin-identity-health__mini"><strong><?php echo htmlspecialchars((string)$identitySkippedRealm['realm_name'], ENT_QUOTES, 'UTF-8'); ?></strong><span class="admin-identity-health__mono"><?php echo htmlspecialchars((string)$identitySkippedRealm['reason'], ENT_QUOTES, 'UTF-8'); ?></span></div>
      <?php } ?>
    </div>
  </section>
  <?php } ?>

  <div class="admin-identity-health__grid">
    <section>
      <p class="admin-identity-health__eyebrow">Identity Coverage</p>
      <p class="admin-identity-health__metric"><?php echo number_format((int)($identityCoverageSelected['account_identities'] ?? 0) + (int)($identityCoverageSelected['character_identities'] ?? 0) + (int)($identityCoverageSelected['bot_identities'] ?? 0)); ?></p>
      <p class="admin-identity-health__label">Visible identity rows known for the selected realm</p>
      <p class="admin-identity-health__note">Coverage answers one question: how much of the site has already been migrated to identity-aware forum and PM data.</p>
      <div class="admin-identity-health__subgrid">
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityCoverageSelected['account_identities'] ?? 0)); ?></strong><span>Account identities</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityCoverageSelected['character_identities'] ?? 0)); ?></strong><span>Human character identities</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityCoverageSelected['bot_identities'] ?? 0)); ?></strong><span>Bot character identities</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo htmlspecialchars(spp_admin_identity_health_format_percent((int)($identityCoverageSelected['posts_covered'] ?? 0), (int)($identityCoverageSelected['posts_total'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong><span>Forum post identity coverage</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo htmlspecialchars(spp_admin_identity_health_format_percent((int)($identityCoverageSelected['topics_covered'] ?? 0), (int)($identityCoverageSelected['topics_total'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong><span>Forum topic identity coverage</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo htmlspecialchars(spp_admin_identity_health_format_percent((int)($identityCoverageSelected['pms_covered'] ?? 0), (int)($identityCoverageSelected['pms_total'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong><span>PM identity coverage</span></div>
      </div>
      <div class="admin-identity-health__actions">
        <a class="admin-identity-health__btn" href="<?php echo htmlspecialchars(spp_admin_identity_health_action_url(['n'=>'admin','sub'=>'identities','action'=>'run_backfill','realm'=>$identitySelectedRealmId,'type'=>'identities']), ENT_QUOTES, 'UTF-8'); ?>">Backfill Accounts + Characters</a>
        <a class="admin-identity-health__btn" href="<?php echo htmlspecialchars(spp_admin_identity_health_action_url(['n'=>'admin','sub'=>'identities','action'=>'run_backfill','realm'=>$identitySelectedRealmId,'type'=>'posts']), ENT_QUOTES, 'UTF-8'); ?>">Backfill Posts + Topics</a>
        <a class="admin-identity-health__btn" href="<?php echo htmlspecialchars(spp_admin_identity_health_action_url(['n'=>'admin','sub'=>'identities','action'=>'run_backfill','realm'=>$identitySelectedRealmId,'type'=>'pms']), ENT_QUOTES, 'UTF-8'); ?>">Backfill PMs</a>
        <a class="admin-identity-health__btn" href="<?php echo htmlspecialchars(spp_admin_identity_health_action_url(['n'=>'admin','sub'=>'identities','action'=>'run_backfill','realm'=>$identitySelectedRealmId,'type'=>'all']), ENT_QUOTES, 'UTF-8'); ?>">Run All Backfills</a>
      </div>
      <?php if (!empty($identityCoverageSelected['commands'])) { ?><div class="admin-identity-health__command"><?php echo htmlspecialchars(implode("\n", $identityCoverageSelected['commands']), ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
      <p class="admin-identity-health__hint" style="margin-top:12px;">Use the backfill tools when coverage is missing broadly. Use the repair buttons below when only a narrow set of rows is stale or broken.</p>
    </section>

    <section>
      <p class="admin-identity-health__eyebrow">Repairable Mismatches</p>
      <p class="admin-identity-health__metric"><?php echo number_format((int)($identityMismatches['invalid_selected_character'] ?? 0) + (int)($identityMismatches['missing_account_rows'] ?? 0) + (int)($identityMismatches['website_only_accounts'] ?? 0)); ?></p>
      <p class="admin-identity-health__label">Rows that look stale, broken, or no longer point at live data</p>
      <p class="admin-identity-health__note">Repair metrics tell you which records are safe to examine first before considering any heavier cleanup or reset plan.</p>
      <div class="admin-identity-health__subgrid">
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityMismatches['invalid_selected_character'] ?? 0)); ?></strong><span>Invalid selected-character pointers</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityMismatches['missing_account_rows'] ?? 0)); ?></strong><span><code>website_accounts</code> rows with no matching account</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityMismatches['website_only_accounts'] ?? 0)); ?></strong><span>Website-linked accounts with no characters anywhere</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityMismatches['accounts_without_identity'] ?? 0)); ?></strong><span>Live accounts still missing account identities</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityMismatches['characters_without_identity'] ?? 0)); ?></strong><span>Characters still missing speaking identities</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityMismatches['posts_without_identity'] ?? 0) + (int)($identityMismatches['topics_without_identity'] ?? 0) + (int)($identityMismatches['pms_without_identity'] ?? 0)); ?></strong><span>Forum and PM rows still missing identity backfill</span></div>
      </div>
      <div class="admin-identity-health__actions">
        <form action="<?php echo htmlspecialchars($identityCanonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($identityCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="identity_realm_id" value="<?php echo $identitySelectedRealmId; ?>" />
          <input type="hidden" name="action" value="clear_invalid_selected_character" />
          <input class="admin-identity-health__btn-input" type="submit" value="Repair Selected Character Pointers" />
        </form>
        <form action="<?php echo htmlspecialchars($identityCanonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($identityCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="identity_realm_id" value="<?php echo $identitySelectedRealmId; ?>" />
          <input type="hidden" name="action" value="remove_missing_account_rows" />
          <input class="admin-identity-health__btn-input" type="submit" value="Repair Orphaned Website Rows" />
        </form>
      </div>
    </section>

    <section>
      <p class="admin-identity-health__eyebrow">Higher-Risk Reset Buckets</p>
      <p class="admin-identity-health__metric"><?php echo htmlspecialchars((string)($identityHealthView['selected_realm_name'] ?? ('Realm ' . $identitySelectedRealmId)), ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="admin-identity-health__label">Preview-only scope for destructive wipe, reseed, or reset planning</p>
      <p class="admin-identity-health__note">Reset buckets answer a different question than repairs: if you intentionally wiped or rebuilt a system, how much data would that affect?</p>
      <div class="admin-identity-health__subgrid">
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['forum']['posts'] ?? 0)); ?></strong><span>Forum posts in reset scope</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['forum']['topics'] ?? 0)); ?></strong><span>Forum topics in reset scope</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['forum']['pms'] ?? 0)); ?></strong><span>PM rows in reset scope</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['forum']['identities'] ?? 0)); ?></strong><span>Identity rows tied to this realm</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['bots']['accounts'] ?? 0)); ?></strong><span>Bot-style accounts</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['bots']['identities'] ?? 0)); ?></strong><span>Bot speaking identities</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['realm']['characters'] ?? 0)); ?></strong><span>Characters in realm reset scope</span></div>
        <div class="admin-identity-health__mini"><strong><?php echo number_format((int)($identityResetPreview['realm']['guilds'] ?? 0)); ?></strong><span>Guilds in realm reset scope</span></div>
      </div>
      <div class="admin-identity-health__actions"><a class="admin-identity-health__btn" href="#" aria-disabled="true">Reset Plan Only</a></div>
    </section>
  </div>

  <section class="admin-identity-health__table-wrap">
    <p class="admin-identity-health__eyebrow">Per-Realm Coverage Matrix</p>
    <p class="admin-identity-health__body">Each row shows how far that realm has made it through the identity migration. Missing coverage does not always mean broken behavior, but it does mean the site is still falling back to older account-bound assumptions in part of the stack.</p>
    <table class="admin-identity-health__table">
      <thead><tr><th>Realm</th><th>Status</th><th>Identity Rows</th><th>Forum Posts</th><th>Topics</th><th>PMs</th></tr></thead>
      <tbody>
        <?php foreach ($identityCoverageRows as $identityCoverageRow) { ?>
        <tr>
          <td><strong><?php echo htmlspecialchars((string)$identityCoverageRow['realm_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br /><span class="admin-identity-health__mono">Realm <?php echo (int)$identityCoverageRow['realm_id']; ?></span></td>
          <td><?php if (!empty($identityCoverageRow['available'])) { ?><span class="admin-identity-health__status<?php echo ($identityCoverageRow['health'] === 'attention' ? ' admin-identity-health__status--warn' : ''); ?>"><?php echo ($identityCoverageRow['health'] === 'attention' ? 'Needs Backfill' : 'Available'); ?></span><?php } else { ?><span class="admin-identity-health__status admin-identity-health__status--warn">Skipped</span><br /><span class="admin-identity-health__mono"><?php echo htmlspecialchars((string)$identityCoverageRow['skip_reason'], ENT_QUOTES, 'UTF-8'); ?></span><?php } ?></td>
          <td><?php echo number_format((int)$identityCoverageRow['account_identities']); ?> account<br /><?php echo number_format((int)$identityCoverageRow['character_identities']); ?> character<br /><?php echo number_format((int)$identityCoverageRow['bot_identities']); ?> bot</td>
          <td><?php echo htmlspecialchars(spp_admin_identity_health_template_coverage_text((int)$identityCoverageRow['posts_covered'], (int)$identityCoverageRow['posts_total']), ENT_QUOTES, 'UTF-8'); ?><br /><span class="admin-identity-health__mono"><?php echo htmlspecialchars(spp_admin_identity_health_format_percent((int)$identityCoverageRow['posts_covered'], (int)$identityCoverageRow['posts_total']), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><?php echo htmlspecialchars(spp_admin_identity_health_template_coverage_text((int)$identityCoverageRow['topics_covered'], (int)$identityCoverageRow['topics_total']), ENT_QUOTES, 'UTF-8'); ?><br /><span class="admin-identity-health__mono"><?php echo htmlspecialchars(spp_admin_identity_health_format_percent((int)$identityCoverageRow['topics_covered'], (int)$identityCoverageRow['topics_total']), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><?php echo htmlspecialchars(spp_admin_identity_health_template_coverage_text((int)$identityCoverageRow['pms_covered'], (int)$identityCoverageRow['pms_total']), ENT_QUOTES, 'UTF-8'); ?><br /><span class="admin-identity-health__mono"><?php echo htmlspecialchars(spp_admin_identity_health_format_percent((int)$identityCoverageRow['pms_covered'], (int)$identityCoverageRow['pms_total']), ENT_QUOTES, 'UTF-8'); ?></span></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </section>
</div>
<script>
(function(){
  var isWindowsHost = <?php echo $identityIsWindowsHost ? 'true' : 'false'; ?>;
  window.identityHealthRevealAndCopyCommand = function(id, button){
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
<?php builddiv_end() ?>
