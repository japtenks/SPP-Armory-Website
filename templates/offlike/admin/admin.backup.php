<br>
<?php builddiv_start(1, 'Backup') ?>
<style>
.backup-admin { color:#f4efe2; display:flex; flex-direction:column; gap:18px; }
.backup-admin__panel {
  padding:18px 20px;
  border:1px solid rgba(230,193,90,0.22);
  border-radius:14px;
  background:linear-gradient(180deg, rgba(20,24,34,0.82), rgba(10,12,18,0.9));
  box-shadow:0 10px 30px rgba(0,0,0,0.22);
}
.backup-admin__eyebrow { margin:0 0 8px; color:#c9a45a; font-size:12px; letter-spacing:.18em; text-transform:uppercase; }
.backup-admin__title { margin:0 0 8px; color:#ffca5a; font-size:1.35rem; }
.backup-admin__copy, .backup-admin__note { margin:0; color:#d6d0c4; line-height:1.6; }
.backup-admin__grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:14px; }
.backup-admin__mini {
  padding:12px 14px; border-radius:10px; background:rgba(255,198,87,0.05);
  border:1px solid rgba(230,193,90,0.12);
}
.backup-admin__mini strong { display:block; color:#ffcc66; font-size:1.1rem; }
.backup-admin__form { display:grid; grid-template-columns:minmax(180px,220px) minmax(0,1fr); gap:12px 16px; align-items:center; margin-top:16px; }
.backup-admin__form label { color:#c3a46a; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; }
.backup-admin__form input[type=text], .backup-admin__form select {
  width:100%; box-sizing:border-box; border-radius:10px; border:1px solid rgba(255,206,102,.2);
  background:rgba(12,12,12,.72); color:#f1f1f1; padding:10px 12px;
}
.backup-admin__actions { display:flex; justify-content:flex-end; margin-top:18px; }
.backup-admin__pair { display:contents; }
.backup-admin__pair.is-hidden { display:none; }
.backup-admin__actions input[type=submit] {
  display:inline-flex; align-items:center; justify-content:center; padding:10px 18px; border-radius:10px;
  border:1px solid rgba(255,206,102,.35); background:rgba(255,193,72,.08); color:#ffd27a; font-weight:bold; cursor:pointer;
}
.backup-admin__actions input[type=submit][disabled] {
  cursor:not-allowed; opacity:.55; color:#b9b9b9; background:rgba(90,90,90,.14); border-color:rgba(180,180,180,.18);
}
.backup-admin__msg { padding:12px 14px; border-radius:10px; font-size:.95rem; margin-top:14px; }
.backup-admin__msg.error { background:rgba(170,35,35,.18); border:1px solid rgba(255,110,110,.28); color:#ffb0b0; }
.backup-admin__msg.success { background:rgba(44,127,75,.18); border:1px solid rgba(92,199,129,.28); color:#9ef0b2; }
.backup-admin__msg a {
  display:inline-flex; align-items:center; justify-content:center; margin-left:10px; padding:7px 12px;
  border-radius:999px; text-decoration:none; color:#f6f0da; border:1px solid rgba(255,255,255,.18); background:rgba(255,255,255,.08);
}
.backup-admin__msg a:hover { background:rgba(255,255,255,.14); }
.backup-admin__files { display:flex; flex-direction:column; gap:10px; margin-top:14px; }
.backup-admin__file {
  display:flex; justify-content:space-between; align-items:center; gap:14px; padding:12px 14px; border-radius:10px;
  border:1px solid rgba(230,193,90,0.12); background:rgba(255,198,87,0.05);
}
.backup-admin__file-meta { display:flex; flex-direction:column; gap:4px; min-width:0; }
.backup-admin__file-name { color:#ffcc66; font-weight:bold; word-break:break-all; }
.backup-admin__file-sub { color:#cfc7b8; font-size:.92rem; }
.backup-admin__file-link {
  display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:999px; white-space:nowrap;
  text-decoration:none; color:#f6f0da; border:1px solid rgba(255,206,102,.35); background:rgba(255,193,72,.08);
}
.backup-admin__file-link:hover { background:rgba(255,193,72,.14); }
@media (max-width:820px) { .backup-admin__form { grid-template-columns:1fr; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var endpoint = 'components/admin/admin.backup.lookup.php';

  function populateSelect(select, items, valueKey, labelBuilder, selectedValue, emptyLabel) {
    if (!select) return;
    select.innerHTML = '';

    if (!Array.isArray(items) || items.length === 0) {
      var emptyOption = document.createElement('option');
      emptyOption.value = '0';
      emptyOption.textContent = emptyLabel;
      select.appendChild(emptyOption);
      return;
    }

    items.forEach(function (item, index) {
      var option = document.createElement('option');
      option.value = String(item[valueKey] || 0);
      option.textContent = labelBuilder(item);
      if (String(selectedValue || '') !== '') {
        option.selected = String(selectedValue) === option.value;
      } else if (index === 0) {
        option.selected = true;
      }
      select.appendChild(option);
    });
  }

  function fetchOptions(params) {
    var url = endpoint + '?' + new URLSearchParams(params).toString();
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Lookup failed');
      }
      return response.json();
    });
  }

  function bindBackupForm() {
    var sourceRealm = document.getElementById('backup_source_realm_id');
    var sourceAccount = document.getElementById('backup_source_account_id');
    var sourceCharacter = document.getElementById('backup_source_character_guid');
    var sourceGuild = document.getElementById('backup_source_guild_id');

    if (!sourceRealm || !sourceAccount || !sourceCharacter || !sourceGuild) return;

    function refresh() {
      fetchOptions({
        source_realm_id: sourceRealm.value,
        source_account_id: sourceAccount.value,
        source_character_guid: sourceCharacter.value,
        source_guild_id: sourceGuild.value
      }).then(function (data) {
        populateSelect(sourceAccount, data.source_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_account_id, 'No accounts found on this realm');

        populateSelect(sourceCharacter, data.source_character_options, 'guid', function (item) {
          return item.name + ' (Lvl ' + item.level + ')';
        }, data.selected_character_guid, 'No characters on this account');

        populateSelect(sourceGuild, data.source_guild_options, 'guildid', function (item) {
          return item.name + (item.leader_name ? ' (Leader: ' + item.leader_name + ')' : '');
        }, data.selected_guild_id, 'No guilds found on this realm');
      }).catch(function () {});
    }

    sourceRealm.addEventListener('change', refresh);
    sourceAccount.addEventListener('change', refresh);
  }

  function bindXferForm() {
    var entityType = document.getElementById('xfer_entity_type');
    var xferRoute = document.getElementById('xfer_route');
    var sourceAccount = document.getElementById('xfer_source_account_id');
    var sourceCharacter = document.getElementById('xfer_source_character_guid');
    var sourceGuild = document.getElementById('xfer_source_guild_id');
    var targetAccount = document.getElementById('xfer_target_account_id');

    if (!entityType || !xferRoute || !sourceAccount || !sourceCharacter || !sourceGuild || !targetAccount) return;

    function applyVisibility() {
      var selectedEntity = entityType.value || 'character';
      document.querySelectorAll('.xfer-field').forEach(function (row) {
        var allowed = (row.getAttribute('data-entities') || '').split(',');
        var shouldShow = allowed.indexOf(selectedEntity) !== -1;
        row.classList.toggle('is-hidden', !shouldShow);
      });
    }

    function refresh() {
      fetchOptions({
        xfer_route: xferRoute.value,
        xfer_entity_type: entityType.value,
        source_account_id: sourceAccount.value,
        source_character_guid: sourceCharacter.value,
        source_guild_id: sourceGuild.value,
        target_account_id: targetAccount.value
      }).then(function (data) {
        populateSelect(xferRoute, data.xfer_route_options, 'id', function (item) {
          return item.label;
        }, data.selected_xfer_route_id, 'No transfer routes available');

        populateSelect(sourceAccount, data.source_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_account_id, 'No accounts found on this realm');

        populateSelect(sourceCharacter, data.source_character_options, 'guid', function (item) {
          return item.name + ' (Lvl ' + item.level + ')';
        }, data.selected_character_guid, 'No characters on this account');

        populateSelect(sourceGuild, data.source_guild_options, 'guildid', function (item) {
          return item.name + (item.leader_name ? ' (Leader: ' + item.leader_name + ')' : '');
        }, data.selected_guild_id, 'No guilds found on this realm');

        populateSelect(targetAccount, data.target_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_target_account_id, 'No accounts found on target realm');

        applyVisibility();
      }).catch(function () {});
    }

    entityType.addEventListener('change', function () {
      applyVisibility();
      refresh();
    });
    xferRoute.addEventListener('change', refresh);
    sourceAccount.addEventListener('change', refresh);
    applyVisibility();
  }

  bindBackupForm();
  bindXferForm();
});
</script>

<div class="backup-admin">
  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Backup Tooling</p>
    <h2 class="backup-admin__title">Backup And Xfer Packages</h2>
    <p class="backup-admin__copy">This page now builds SQL packages for controlled backups and cross-realm transfers. It models the character side after CMaNGOS `pdump` ideas, but writes website-generated SQL files into a local cache folder so you can review and apply them deliberately.</p>
    <div class="backup-admin__grid">
      <div class="backup-admin__mini">
        <strong><?php echo htmlspecialchars((string)($backupView['source_realm_name'] ?? '')); ?></strong>
        <span>Current source realm</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo htmlspecialchars((string)($backupView['target_realm_name'] ?? 'Not selected')); ?></strong>
        <span>Current target realm</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo !empty($backupView['output_dir_writable']) ? 'Writable' : 'Locked'; ?></strong>
        <span><?php echo htmlspecialchars((string)($backupView['output_dir'] ?? '')); ?></span>
      </div>
    </div>
  </section>

  <?php if (!empty($backupActionState['notice'])): ?>
    <div class="backup-admin__msg success">
      <?php echo htmlspecialchars((string)$backupActionState['notice']); ?>
      <?php if (!empty($backupActionState['download_url'])): ?>
        <a href="<?php echo htmlspecialchars((string)$backupActionState['download_url']); ?>">Download SQL</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($backupActionState['error'])): ?>
    <div class="backup-admin__msg error"><?php echo htmlspecialchars((string)$backupActionState['error']); ?></div>
  <?php endif; ?>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Recent Packages</p>
    <h2 class="backup-admin__title">Cache Files</h2>
    <p class="backup-admin__note">Generated backup and xfer packages are stored in the local cache folder and can be downloaded again here.</p>

    <div class="backup-admin__files">
      <?php if (!empty($backupView['recent_files'])): ?>
        <?php foreach ((array)$backupView['recent_files'] as $backupFile): ?>
          <div class="backup-admin__file">
            <div class="backup-admin__file-meta">
              <div class="backup-admin__file-name"><?php echo htmlspecialchars((string)$backupFile['filename']); ?></div>
              <div class="backup-admin__file-sub">
                <?php echo date('Y-m-d H:i:s', (int)($backupFile['mtime'] ?? time())); ?>
                <?php echo ' | ' . number_format(((int)($backupFile['size'] ?? 0)) / 1024, 1) . ' KB'; ?>
              </div>
            </div>
            <a class="backup-admin__file-link" href="<?php echo htmlspecialchars((string)$backupFile['download_url']); ?>">Download</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="backup-admin__file">
          <div class="backup-admin__file-meta">
            <div class="backup-admin__file-name">No cached SQL packages yet</div>
            <div class="backup-admin__file-sub"><?php echo htmlspecialchars((string)($backupView['output_dir'] ?? '')); ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Backup Export</p>
    <h2 class="backup-admin__title">Create Backup SQL</h2>
    <p class="backup-admin__note">Backup exports preserve the selected entity as-is from the source realm. Use this for safe snapshots of a single character, full account, or guild before doing maintenance or promotion work.</p>

    <form method="post" action="index.php?n=admin&amp;sub=backup">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
      <input type="hidden" name="backup_action" value="create_backup_package">
      <div class="backup-admin__form">
        <label for="backup_source_realm_id">Source Realm</label>
        <select id="backup_source_realm_id" name="source_realm_id">
          <?php foreach ((array)$backupView['realm_options'] as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php if ((int)$realmOption['id'] === (int)$backupView['source_realm_id']) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$realmOption['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="backup_entity_type">Entity Type</label>
        <select id="backup_entity_type" name="backup_entity_type">
          <?php foreach ((array)$backupView['entity_options'] as $entityKey => $entityLabel): ?>
            <option value="<?php echo htmlspecialchars((string)$entityKey); ?>"<?php if ((string)$entityKey === (string)$backupView['backup_entity_type']) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$entityLabel); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="backup_source_account_id">Source Account</label>
        <select id="backup_source_account_id" name="source_account_id">
          <?php foreach ((array)$backupView['source_account_options'] as $accountOption): ?>
            <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_account_id']) echo ' selected'; ?>>
              <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="backup_source_character_guid">Character</label>
        <select id="backup_source_character_guid" name="source_character_guid">
          <?php if (!empty($backupView['source_character_options'])): ?>
            <?php foreach ((array)$backupView['source_character_options'] as $characterOption): ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === (int)$backupView['selected_character_guid']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$characterOption['name'] . ' (Lvl ' . (int)$characterOption['level'] . ')'); ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="0">No characters on this account</option>
          <?php endif; ?>
        </select>

        <label for="backup_source_guild_id">Guild</label>
        <select id="backup_source_guild_id" name="source_guild_id">
          <?php if (!empty($backupView['source_guild_options'])): ?>
            <?php foreach ((array)$backupView['source_guild_options'] as $guildOption): ?>
              <option value="<?php echo (int)$guildOption['guildid']; ?>"<?php if ((int)$guildOption['guildid'] === (int)$backupView['selected_guild_id']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$guildOption['name'] . (!empty($guildOption['leader_name']) ? ' (Leader: ' . $guildOption['leader_name'] . ')' : '')); ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="0">No guilds found on this realm</option>
          <?php endif; ?>
        </select>
      </div>

      <div class="backup-admin__actions">
        <input type="submit" value="Create Backup SQL" <?php if (empty($backupView['output_dir_writable'])) echo 'disabled="disabled"'; ?>>
      </div>
    </form>
  </section>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Realm Xfer</p>
    <h2 class="backup-admin__title">Create Xfer SQL</h2>
    <p class="backup-admin__note">Xfer packages are target-ready SQL bundles for `Classic -> TBC -> WotLK` style promotion. Characters are remapped to new GUID ranges on the target realm, accounts reuse an existing username when possible, and guild packages assume the member characters have already been transferred with the same names.</p>

    <form method="post" action="index.php?n=admin&amp;sub=backup">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
      <input type="hidden" name="backup_action" value="create_xfer_package">
      <div class="backup-admin__form">
        <div class="backup-admin__pair xfer-field" data-entities="character,account,guild">
          <label for="xfer_entity_type">Type</label>
          <select id="xfer_entity_type" name="xfer_entity_type">
            <?php foreach ((array)$backupView['entity_options'] as $entityKey => $entityLabel): ?>
              <option value="<?php echo htmlspecialchars((string)$entityKey); ?>"<?php if ((string)$entityKey === (string)$backupView['xfer_entity_type']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$entityLabel); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character,account,guild">
          <label for="xfer_route">Realm Route</label>
          <select id="xfer_route" name="xfer_route">
          <?php foreach ((array)$backupView['xfer_route_options'] as $routeOption): ?>
            <option value="<?php echo htmlspecialchars((string)$routeOption['id']); ?>"<?php if ((string)$routeOption['id'] === (string)$backupView['selected_xfer_route_id']) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$routeOption['label']); ?>
            </option>
          <?php endforeach; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character,account">
          <label for="xfer_source_account_id">Source Account</label>
          <select id="xfer_source_account_id" name="source_account_id">
          <?php foreach ((array)$backupView['source_account_options'] as $accountOption): ?>
            <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_account_id']) echo ' selected'; ?>>
              <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
            </option>
          <?php endforeach; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character">
          <label for="xfer_source_character_guid">Character</label>
          <select id="xfer_source_character_guid" name="source_character_guid">
          <?php if (!empty($backupView['source_character_options'])): ?>
            <?php foreach ((array)$backupView['source_character_options'] as $characterOption): ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === (int)$backupView['selected_character_guid']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$characterOption['name'] . ' (Lvl ' . (int)$characterOption['level'] . ')'); ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="0">No characters on this account</option>
          <?php endif; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="guild">
          <label for="xfer_source_guild_id">Guild</label>
          <select id="xfer_source_guild_id" name="source_guild_id">
          <?php if (!empty($backupView['source_guild_options'])): ?>
            <?php foreach ((array)$backupView['source_guild_options'] as $guildOption): ?>
              <option value="<?php echo (int)$guildOption['guildid']; ?>"<?php if ((int)$guildOption['guildid'] === (int)$backupView['selected_guild_id']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$guildOption['name'] . (!empty($guildOption['leader_name']) ? ' (Leader: ' . $guildOption['leader_name'] . ')' : '')); ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="0">No guilds found on this realm</option>
          <?php endif; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character">
          <label for="xfer_target_account_id">Target Account</label>
          <select id="xfer_target_account_id" name="target_account_id">
          <?php if (!empty($backupView['target_account_options'])): ?>
            <?php foreach ((array)$backupView['target_account_options'] as $accountOption): ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_target_account_id']) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="0">No accounts found on target realm</option>
          <?php endif; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character">
          <label for="target_character_name">Target Character Name</label>
          <input type="text" id="target_character_name" name="target_character_name" value="<?php echo htmlspecialchars((string)($_POST['target_character_name'] ?? '')); ?>" maxlength="12">
        </div>
      </div>

      <div class="backup-admin__actions">
        <input type="submit" value="Create Xfer SQL" <?php if (empty($backupView['output_dir_writable']) || empty($backupView['has_target_realm'])) echo 'disabled="disabled"'; ?>>
      </div>
    </form>
  </section>
</div>
<?php builddiv_end() ?>
