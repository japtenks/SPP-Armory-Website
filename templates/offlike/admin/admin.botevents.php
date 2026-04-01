<br>
<?php builddiv_start(0, 'Bot Events Pipeline') ?>
<?php
$achievementCategories = $achievementCatalog['categories'] ?? array();
$achievementRows = $achievementCatalog['achievements'] ?? array();
$achievementRealmName = (string)($achievementCatalog['realmName'] ?? '');
$achievementSource = (string)($achievementCatalog['source'] ?? '');
$achievementError = (string)($achievementCatalog['error'] ?? '');
$selectedExcludedCategories = array_fill_keys(array_map('intval', $botConfig['achievement_badge_exclude_categories'] ?? array()), true);
$selectedFeaturedAchievements = array_fill_keys(array_map('intval', $botConfig['achievement_badge_featured_ids'] ?? array()), true);
$selectedExcludedAchievements = array_fill_keys(array_map('intval', $botConfig['achievement_badge_exclude'] ?? array()), true);
?>
<style>
.bot-tabs{display:flex;gap:8px;margin:0 0 16px;flex-wrap:wrap}.bot-tab{display:inline-block;padding:8px 14px;border-radius:4px;border:1px solid #4e5d72;background:#1b2430;color:#dbe9ff;font-size:.92em;font-weight:bold;text-decoration:none}.bot-tab.active{background:#35506e;border-color:#7e9cc1;color:#fff}.bot-card,.bot-panel{background:rgba(15,23,35,.8);border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:14px}.bot-panel{background:rgba(0,0,0,.2);padding:16px}.bot-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}.bot-actions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:flex-start}.bot-actions a,.bot-actions button,.bot-save{padding:6px 14px;border-radius:3px;border:0;font-size:.9em;font-weight:bold;cursor:pointer}.btn-scan{background:#4a7aaa;color:#fff}.btn-process{background:#4a9a4a;color:#fff}.btn-dry{background:#888;color:#fff}.btn-skip{background:#c05030;color:#fff}.bot-stats{display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap}.bot-stat{background:#E8EEFA;border:1px solid #c0c0d0;border-radius:4px;padding:8px 16px;text-align:center;min-width:80px}.bot-stat strong{display:block;font-size:1.6em}.bot-output,.bot-error{font-family:monospace;font-size:.8em;padding:10px;white-space:pre-wrap;border-radius:4px;margin-bottom:12px}.bot-output{background:#111;color:#0f0;max-height:300px;overflow-y:auto}.bot-error{background:#300;color:#f88}.bot-field input,.bot-field textarea,.bot-field select,.bot-search input{width:100%;box-sizing:border-box;padding:8px 10px;border-radius:4px;border:1px solid #5f6d84;background:#111823;color:#fff}.bot-field textarea{min-height:72px;resize:vertical;font-family:monospace;font-size:.85em}.bot-two{display:grid;grid-template-columns:repeat(2,minmax(110px,1fr));gap:10px}.bot-table{width:100%;border-collapse:collapse;margin-top:8px}.bot-table th,.bot-table td{border:1px solid rgba(255,255,255,.08);padding:8px;vertical-align:top}.bot-table th{background:rgba(255,255,255,.05);color:#f0e0b6;text-align:left}.bot-table td input,.bot-table td select{width:100%;box-sizing:border-box;padding:6px 8px;border-radius:4px;border:1px solid #5f6d84;background:#111823;color:#fff}.bot-list{display:grid;gap:10px;max-height:420px;overflow:auto}.bot-list.categories{grid-template-columns:repeat(auto-fit,minmax(240px,1fr));max-height:620px}.bot-check{display:flex;gap:8px;padding:8px 10px;border:1px solid rgba(255,255,255,.08);border-radius:4px;background:rgba(255,255,255,.02)}.bot-meta,.bot-note{color:#c7d6ec;font-size:.88em;line-height:1.45}.bot-alert{margin-bottom:14px;padding:10px 12px;border-radius:4px}.bot-alert.success{background:#17311f;border:1px solid #2f6a40;color:#bbf0c8}.bot-alert.error{background:#341616;border:1px solid #723131;color:#ffb3b3}.bot-ach-row{padding:10px 12px;border:1px solid rgba(255,255,255,.06);border-radius:4px;background:rgba(11,17,26,.8)}.bot-ach-name{font-weight:bold;color:#f0e0b6}.bot-ach-sub{font-size:.8em;color:#9fb0c8}.bot-ach-desc{font-size:.8em;color:#b8c6da;margin-top:4px}.bot-achievement-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;margin-top:14px}.bot-process-form{display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;flex:1 1 640px}.bot-process-controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.bot-event-types{display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:6px 14px;width:100%;padding:10px;border:1px solid rgba(255,255,255,.12);border-radius:4px;background:rgba(0,0,0,.18)}.bot-event-types label{display:flex;gap:6px;align-items:center;color:#f0e0b6;font-size:.9em;white-space:nowrap}
.bot-output.is-collapsed{display:none}
</style>

<div class="sections subsections" style="font-size:.85em;">
  <div class="bot-tabs">
    <a class="bot-tab<?php echo $activeTab === 'pipeline' ? ' active' : ''; ?>" href="index.php?n=admin&sub=botevents&tab=pipeline">Pipeline</a>
    <a class="bot-tab<?php echo $activeTab === 'configure' ? ' active' : ''; ?>" href="index.php?n=admin&sub=botevents&tab=configure">Bot Configure</a>
    <a class="bot-tab<?php echo $activeTab === 'achievements' ? ' active' : ''; ?>" href="index.php?n=admin&sub=botevents&tab=achievements">Achievement Pipeline</a>
  </div>

  <?php if ($configSaved): ?><div class="bot-alert success"><?php echo $activeTab === 'achievements' ? 'Achievement pipeline configuration saved.' : 'Bot event configuration saved.'; ?></div><?php endif; ?>
  <?php if ($configLoadError !== ''): ?><div class="bot-alert error"><?php echo htmlspecialchars($configLoadError); ?></div><?php endif; ?>
  <?php if ($configError !== ''): ?><div class="bot-alert error"><?php echo htmlspecialchars($configError); ?></div><?php endif; ?>
  <?php if ($achievementError !== '' && $activeTab === 'achievements'): ?><div class="bot-alert error"><?php echo htmlspecialchars($achievementError); ?></div><?php endif; ?>

  <?php if ($activeTab === 'configure'): ?>
  <div class="bot-panel">
    <div class="bot-meta">Editing config file: <code><?php echo htmlspecialchars($configPath); ?></code></div>
    <form method="post" action="index.php?n=admin&sub=botevents&tab=configure">
      <input type="hidden" name="action" value="save_config">
      <input type="hidden" name="tab" value="configure">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_botevents_csrf_token); ?>">
      <div class="bot-grid">
        <div class="bot-card">
          <h4>Enabled Realms</h4>
          <div class="bot-list">
            <?php foreach ($realmOptions as $realm): ?>
            <label class="bot-check"><input type="checkbox" name="enabled_realms[]" value="<?php echo (int)$realm['id']; ?>"<?php echo in_array((int)$realm['id'], $botConfig['enabled_realms'] ?? array(), true) ? ' checked' : ''; ?>><span><?php echo htmlspecialchars($realm['name']); ?> (ID <?php echo (int)$realm['id']; ?>)</span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="bot-card">
          <h4>Milestones</h4>
          <div class="bot-field"><label>Classic Levels</label><textarea name="level_milestones[classic]"><?php echo htmlspecialchars(implode(', ', $botConfig['level_milestones']['classic'] ?? array())); ?></textarea></div>
          <div class="bot-field"><label>TBC Levels</label><textarea name="level_milestones[tbc]"><?php echo htmlspecialchars(implode(', ', $botConfig['level_milestones']['tbc'] ?? array())); ?></textarea></div>
          <div class="bot-field"><label>WotLK Levels</label><textarea name="level_milestones[wotlk]"><?php echo htmlspecialchars(implode(', ', $botConfig['level_milestones']['wotlk'] ?? array())); ?></textarea></div>
          <div class="bot-field"><label>Profession Milestones</label><textarea name="profession_milestones"><?php echo htmlspecialchars(implode(', ', $botConfig['profession_milestones'] ?? array())); ?></textarea></div>
        </div>
        <div class="bot-card">
          <h4>Forum Reactions</h4>
          <div class="bot-two"><div class="bot-field"><label>Reaction Min</label><input type="number" min="0" name="reaction_count_min" value="<?php echo (int)($botConfig['reaction_count'][0] ?? 0); ?>"></div><div class="bot-field"><label>Reaction Max</label><input type="number" min="0" name="reaction_count_max" value="<?php echo (int)($botConfig['reaction_count'][1] ?? 0); ?>"></div></div>
          <div class="bot-two"><div class="bot-field"><label>Delay Min (sec)</label><input type="number" min="0" name="reaction_min_delay_sec" value="<?php echo (int)($botConfig['reaction_min_delay_sec'] ?? 0); ?>"></div><div class="bot-field"><label>Delay Max (sec)</label><input type="number" min="0" name="reaction_max_delay_sec" value="<?php echo (int)($botConfig['reaction_max_delay_sec'] ?? 0); ?>"></div></div>
          <div class="bot-two"><div class="bot-field"><label>Guild Min</label><input type="number" min="0" name="guild_reaction_count_min" value="<?php echo (int)($botConfig['guild_reaction_count'][0] ?? 0); ?>"></div><div class="bot-field"><label>Guild Max</label><input type="number" min="0" name="guild_reaction_count_max" value="<?php echo (int)($botConfig['guild_reaction_count'][1] ?? 0); ?>"></div></div>
          <div class="bot-two"><div class="bot-field"><label>Guild Delay Min</label><input type="number" min="0" name="guild_reaction_min_delay_sec" value="<?php echo (int)($botConfig['guild_reaction_min_delay_sec'] ?? 0); ?>"></div><div class="bot-field"><label>Guild Delay Max</label><input type="number" min="0" name="guild_reaction_max_delay_sec" value="<?php echo (int)($botConfig['guild_reaction_max_delay_sec'] ?? 0); ?>"></div></div>
        </div>
        <div class="bot-card">
          <h4>Achievement Summary</h4>
          <div class="bot-note">
            Lookback: <?php echo (int)($botConfig['achievement_lookback_days'] ?? 1); ?> day(s)<br>
            Min threshold: <?php echo (int)($botConfig['achievement_badge_min_points'] ?? 0); ?> points / level <?php echo (int)($botConfig['achievement_badge_min_level'] ?? 0); ?>+<br>
            Excluded categories: <?php echo count($botConfig['achievement_badge_exclude_categories'] ?? array()); ?><br>
            Featured achievements: <?php echo count($botConfig['achievement_badge_featured_ids'] ?? array()); ?><br>
            Excluded achievements: <?php echo count($botConfig['achievement_badge_exclude'] ?? array()); ?>
          </div>
          <p><a class="bot-tab" href="index.php?n=admin&sub=botevents&tab=achievements">Open Achievement Pipeline</a></p>
        </div>
        <div class="bot-card">
          <h4>Guild Roster Updates</h4>
          <div class="bot-two"><div class="bot-field"><label>Min Joins</label><input type="number" min="1" name="guild_roster_min_joins" value="<?php echo (int)($botConfig['guild_roster_thresholds']['min_joins'] ?? 1); ?>"></div><div class="bot-field"><label>Cooldown (sec)</label><input type="number" min="0" name="guild_roster_cooldown_sec" value="<?php echo (int)($botConfig['guild_roster_thresholds']['cooldown_sec'] ?? 0); ?>"></div></div>
        </div>
      </div>
      <div class="bot-card" style="margin-top:14px;">
        <h4>Realm Routing</h4>
        <table class="bot-table"><thead><tr><th>Realm</th><th>Expansion</th><th>Level Up</th><th>Guild Created</th><th>Profession</th><th>Guild Roster</th><th>Achievement</th></tr></thead><tbody>
          <?php foreach ($realmOptions as $realm): $realmId = (int)$realm['id']; ?>
          <tr><td><?php echo htmlspecialchars($realm['name']); ?><br><small>ID <?php echo $realmId; ?></small></td><td><select name="realm_expansion[<?php echo $realmId; ?>]"><?php foreach (array('classic' => 'Classic', 'tbc' => 'TBC', 'wotlk' => 'WotLK') as $value => $label): ?><option value="<?php echo $value; ?>"<?php echo (($botConfig['realm_expansion'][$realmId] ?? '') === $value) ? ' selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></td><td><input type="number" min="1" name="forum_targets[<?php echo $realmId; ?>][level_up]" value="<?php echo htmlspecialchars((string)($botConfig['forum_targets'][$realmId]['level_up'] ?? '')); ?>"></td><td><input type="number" min="1" name="forum_targets[<?php echo $realmId; ?>][guild_created]" value="<?php echo htmlspecialchars((string)($botConfig['forum_targets'][$realmId]['guild_created'] ?? '')); ?>"></td><td><input type="number" min="1" name="forum_targets[<?php echo $realmId; ?>][profession_milestone]" value="<?php echo htmlspecialchars((string)($botConfig['forum_targets'][$realmId]['profession_milestone'] ?? '')); ?>"></td><td><input type="number" min="1" name="forum_targets[<?php echo $realmId; ?>][guild_roster_update]" value="<?php echo htmlspecialchars((string)($botConfig['forum_targets'][$realmId]['guild_roster_update'] ?? '')); ?>"></td><td><input type="number" min="1" name="forum_targets[<?php echo $realmId; ?>][achievement_badge]" value="<?php echo htmlspecialchars((string)($botConfig['forum_targets'][$realmId]['achievement_badge'] ?? '')); ?>"></td></tr>
          <?php endforeach; ?>
        </tbody></table>
      </div>
      <p><button class="bot-save" type="submit"<?php echo !$configWritable ? ' disabled' : ''; ?>>Save Bot Config</button></p>
    </form>
  </div>

  <?php elseif ($activeTab === 'achievements'): ?>
  <div class="bot-panel">
    <div class="bot-meta">Achievement metadata source: <strong><?php echo htmlspecialchars($achievementSource !== '' ? $achievementSource : 'unavailable'); ?></strong><?php if ($achievementRealmName !== ''): ?> from <strong><?php echo htmlspecialchars($achievementRealmName); ?></strong><?php endif; ?>.</div>
    <form method="post" action="index.php?n=admin&sub=botevents&tab=achievements">
      <input type="hidden" name="action" value="save_achievement_config">
      <input type="hidden" name="tab" value="achievements">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_botevents_csrf_token); ?>">
      <div class="bot-grid">
        <div class="bot-card">
          <h4>Scanner Thresholds</h4>
          <div class="bot-field"><label>Lookback Days</label><input type="number" min="1" name="achievement_lookback_days" value="<?php echo (int)($botConfig['achievement_lookback_days'] ?? 1); ?>"></div>
          <div class="bot-two"><div class="bot-field"><label>Min Points</label><input type="number" min="0" name="achievement_badge_min_points" value="<?php echo (int)($botConfig['achievement_badge_min_points'] ?? 0); ?>"></div><div class="bot-field"><label>Min Level</label><input type="number" min="0" name="achievement_badge_min_level" value="<?php echo (int)($botConfig['achievement_badge_min_level'] ?? 0); ?>"></div></div>
        </div>
      </div>
      <div class="bot-card" style="margin-top:14px;">
        <h4>Excluded Categories</h4>
        <div class="bot-note">Excluded categories are broad topic-level filters. Any achievement in a checked category is skipped unless you explicitly mark that achievement as Featured below.</div>
        <div class="bot-list categories">
          <?php foreach ($achievementCategories as $category): $categoryId = (int)($category['id'] ?? 0); ?>
          <label class="bot-check"><input type="checkbox" name="achievement_badge_exclude_categories[]" value="<?php echo $categoryId; ?>"<?php echo isset($selectedExcludedCategories[$categoryId]) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string)($category['name'] ?? ('Category #' . $categoryId))); ?></strong><br><small>ID <?php echo $categoryId; ?><?php echo !empty($category['parent_name']) ? ' | Parent: ' . htmlspecialchars((string)$category['parent_name']) : ''; ?></small></span></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="bot-card bot-search" style="margin-top:14px;">
        <h4>Search Achievements</h4>
        <div class="bot-note">This filter applies to both Featured and Excluded achievement lists below.</div>
        <input type="text" id="achievement-filter-input" placeholder="Search by name, category, ID, or points">
      </div>
      <div class="bot-achievement-columns">
        <div class="bot-card">
          <h4>Featured Achievements</h4>
          <div class="bot-note">Featured means "always allow this achievement through," even if it would normally be filtered out by low points, low level, or excluded category rules.</div>
          <div class="bot-list">
            <?php foreach ($achievementRows as $achievement): $achievementId = (int)($achievement['id'] ?? 0); $searchText = strtolower(trim(($achievement['name'] ?? '') . ' ' . ($achievement['category_name'] ?? '') . ' ' . ($achievement['description'] ?? '') . ' ' . $achievementId . ' ' . (int)($achievement['points'] ?? 0))); ?>
            <div class="bot-ach-row js-achievement-row" data-achievement-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
              <label class="bot-check"><input type="checkbox" name="achievement_badge_featured_ids[]" value="<?php echo $achievementId; ?>"<?php echo isset($selectedFeaturedAchievements[$achievementId]) ? ' checked' : ''; ?>><span><span class="bot-ach-name"><?php echo htmlspecialchars((string)($achievement['name'] ?? ('Achievement #' . $achievementId))); ?></span><br><span class="bot-ach-sub">ID <?php echo $achievementId; ?> | <?php echo (int)($achievement['points'] ?? 0); ?> pts | <?php echo htmlspecialchars((string)($achievement['category_name'] ?? 'Uncategorized')); ?></span><?php if (!empty($achievement['description'])): ?><div class="bot-ach-desc"><?php echo htmlspecialchars((string)$achievement['description']); ?></div><?php endif; ?></span></label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="bot-card">
          <h4>Excluded Achievements</h4>
          <div class="bot-note">Excluded means "never queue this achievement," even if it would normally qualify.</div>
          <div class="bot-list">
            <?php foreach ($achievementRows as $achievement): $achievementId = (int)($achievement['id'] ?? 0); $searchText = strtolower(trim(($achievement['name'] ?? '') . ' ' . ($achievement['category_name'] ?? '') . ' ' . ($achievement['description'] ?? '') . ' ' . $achievementId . ' ' . (int)($achievement['points'] ?? 0))); ?>
            <div class="bot-ach-row js-achievement-row" data-achievement-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
              <label class="bot-check"><input type="checkbox" name="achievement_badge_exclude[]" value="<?php echo $achievementId; ?>"<?php echo isset($selectedExcludedAchievements[$achievementId]) ? ' checked' : ''; ?>><span><span class="bot-ach-name"><?php echo htmlspecialchars((string)($achievement['name'] ?? ('Achievement #' . $achievementId))); ?></span><br><span class="bot-ach-sub">ID <?php echo $achievementId; ?> | <?php echo (int)($achievement['points'] ?? 0); ?> pts | <?php echo htmlspecialchars((string)($achievement['category_name'] ?? 'Uncategorized')); ?></span><?php if (!empty($achievement['description'])): ?><div class="bot-ach-desc"><?php echo htmlspecialchars((string)$achievement['description']); ?></div><?php endif; ?></span></label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <p><button class="bot-save" type="submit"<?php echo !$configWritable ? ' disabled' : ''; ?>>Save Achievement Config</button></p>
    </form>
  </div>

  <?php else: ?>
  <div class="bot-stats">
    <?php foreach (array('pending', 'posted', 'skipped', 'failed', 'processing') as $st): ?>
    <div class="bot-stat <?php echo $st; ?>"><strong><?php echo (int)($botStats[$st] ?? 0); ?></strong><?php echo ucfirst($st); ?><?php if ($st === 'pending' && !empty($pendingTypeBreakdown)): ?><small><?php foreach ($pendingTypeBreakdown as $pendingType): ?><?php echo htmlspecialchars($pendingType['event_type']) . ': ' . (int)$pendingType['count']; ?><br><?php endforeach; ?></small><?php endif; ?></div>
    <?php endforeach; ?>
  </div>
  <div class="bot-actions">
    <a class="btn-scan" href="<?php echo htmlspecialchars(spp_admin_botevents_action_url(array('n' => 'admin', 'sub' => 'botevents', 'tab' => 'pipeline', 'action' => 'scan'))); ?>">Scan Now</a>
    <a class="btn-dry" href="<?php echo htmlspecialchars(spp_admin_botevents_action_url(array('n' => 'admin', 'sub' => 'botevents', 'tab' => 'pipeline', 'action' => 'scan_dry'))); ?>">Scan (dry-run)</a>
    <form method="get" action="index.php" class="bot-process-form"><input type="hidden" name="n" value="admin"><input type="hidden" name="sub" value="botevents"><input type="hidden" name="tab" value="pipeline"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_botevents_csrf_token); ?>"><div class="bot-process-controls"><input type="hidden" name="action" value="process"><input type="text" name="process_limit" value="<?php echo htmlspecialchars($processLimitValue); ?>" placeholder="10"><button type="submit" class="btn-process">Process Now</button><button type="submit" name="action" value="process_dry" class="btn-dry">Process (dry-run)</button></div><?php if (!empty($availableEventTypes)): ?><div class="bot-event-types"><?php foreach ($availableEventTypes as $eventType): ?><label><input type="checkbox" name="event_types[]" value="<?php echo htmlspecialchars($eventType); ?>"<?php echo in_array($eventType, $selectedEventTypes, true) ? ' checked' : ''; ?>><span><?php echo htmlspecialchars($eventType); ?></span></label><?php endforeach; ?></div><?php endif; ?></form>
    <a class="btn-skip" href="<?php echo htmlspecialchars(spp_admin_botevents_action_url(array('n' => 'admin', 'sub' => 'botevents', 'tab' => 'pipeline', 'action' => 'skip_all'))); ?>" onclick="return confirm('Mark ALL pending events as skipped?');">Skip All Pending</a>
  </div>
  <?php if ($botOutput !== ''): ?><div class="bot-output"><?php echo htmlspecialchars($botOutput); ?></div><?php endif; ?>
  <?php if ($botNotice !== '' || $botCommand !== ''): ?>
  <div class="bot-card" style="margin-bottom:12px;">
    <?php if ($botNotice !== ''): ?><div class="bot-meta"><?php echo htmlspecialchars($botNotice); ?></div><?php endif; ?>
    <?php if ($botCommand !== ''): ?>
      <div class="bot-output<?php echo !empty($isWindowsHost) ? ' is-collapsed' : ''; ?>" id="bot-command-box" style="max-height:none;color:#dbe9ff;"><?php echo htmlspecialchars($botCommand); ?></div>
      <p><button type="button" class="bot-save" onclick="copyBotCommand(this)">Copy</button></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($botError !== ''): ?><div class="bot-error"><?php echo htmlspecialchars($botError); ?></div><?php endif; ?>
  <table border="0" cellspacing="1" cellpadding="4" width="100%" class="bordercolor">
    <tr class="catbg3"><td width="40"><b>#</b></td><td><b>Type</b></td><td width="40"><b>Realm</b></td><td width="70"><b>Status</b></td><td><b>Payload</b></td><td><b>Occurred</b></td><td><b>Processed</b></td><td><b>Error</b></td></tr>
    <?php if (empty($recentEvents)): ?><tr><td colspan="8" class="windowbg" align="center"><i>No events yet.</i></td></tr><?php endif; ?>
    <?php foreach ($recentEvents as $ev): $payload = json_decode($ev['payload_json'], true) ?? array(); $summary = $payload['char_name'] ?? $payload['guild_name'] ?? ''; $statusColor = array('pending' => '#d08800', 'posted' => '#3a8a3a', 'skipped' => '#888', 'failed' => '#c00', 'processing' => '#44a')[$ev['status']] ?? '#000'; ?>
    <tr><td class="windowbg2"><?php echo (int)$ev['event_id']; ?></td><td class="windowbg"><?php echo htmlspecialchars($ev['event_type']); ?></td><td class="windowbg2" align="center"><?php echo (int)$ev['realm_id']; ?></td><td class="windowbg2" style="color:<?php echo $statusColor; ?>;font-weight:bold;"><?php echo htmlspecialchars($ev['status']); ?></td><td class="windowbg"><?php echo htmlspecialchars($summary); ?></td><td class="windowbg2" nowrap><?php echo htmlspecialchars($ev['occurred_at']); ?></td><td class="windowbg2" nowrap><?php echo $ev['processed_at'] ? htmlspecialchars($ev['processed_at']) : '-'; ?></td><td class="windowbg" style="color:#c00;"><?php echo htmlspecialchars($ev['error_message'] ?? ''); ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<script>
(function(){var isWindowsHost=<?php echo !empty($isWindowsHost) ? 'true' : 'false'; ?>;window.copyBotCommand=function(button){var box=document.getElementById('bot-command-box');if(!box)return;if(isWindowsHost&&box.classList.contains('is-collapsed')){box.classList.remove('is-collapsed');}if(button){button.setAttribute('aria-expanded','true');}var text=box.textContent||box.innerText||'';if(!text)return;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(text);return;}var temp=document.createElement('textarea');temp.value=text;document.body.appendChild(temp);temp.select();document.execCommand('copy');document.body.removeChild(temp);};})();
(function(){var input=document.getElementById('achievement-filter-input');if(!input)return;var rows=document.querySelectorAll('.js-achievement-row');input.addEventListener('input',function(){var needle=(input.value||'').toLowerCase().trim();rows.forEach(function(row){var hay=row.getAttribute('data-achievement-text')||'';row.style.display=!needle||hay.indexOf(needle)!==-1?'':'none';});});})();
</script>
<?php builddiv_end() ?>
