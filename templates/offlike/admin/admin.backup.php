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
.backup-admin__form { display:grid; grid-template-columns:minmax(180px,220px) minmax(0,1fr); gap:12px 16px; align-items:center; }
.backup-admin__form label { color:#c3a46a; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; }
.backup-admin__form input[type=text] {
  width:100%; box-sizing:border-box; border-radius:10px; border:1px solid rgba(255,206,102,.2);
  background:rgba(12,12,12,.72); color:#f1f1f1; padding:10px 12px;
}
.backup-admin__actions { display:flex; justify-content:flex-end; margin-top:18px; }
.backup-admin__actions input[type=submit] {
  display:inline-flex; align-items:center; justify-content:center; padding:10px 18px; border-radius:10px;
  border:1px solid rgba(255,206,102,.35); background:rgba(255,193,72,.08); color:#ffd27a; font-weight:bold; cursor:pointer;
}
.backup-admin__msg { padding:12px 14px; border-radius:10px; font-size:.95rem; }
.backup-admin__msg.error { background:rgba(170,35,35,.18); border:1px solid rgba(255,110,110,.28); color:#ffb0b0; }
.backup-admin__msg.success { background:rgba(44,127,75,.18); border:1px solid rgba(92,199,129,.28); color:#9ef0b2; }
@media (max-width:820px) { .backup-admin__form { grid-template-columns:1fr; } }
</style>

<div class="backup-admin">
  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Character Copy Backup</p>
    <h2 class="backup-admin__title">Export Copy-Account Characters To SQL</h2>
    <p class="backup-admin__copy">This tool exports the configured Horde/Alliance copy-account characters into a reusable SQL file with remapped character and item GUIDs. It is meant for controlled LAN/server copy workflows, not blind production migrations.</p>
    <div class="backup-admin__grid">
      <div class="backup-admin__mini">
        <strong><?php echo (int)($backupPreview['horde_account'] ?? 0); ?></strong>
        <span>Configured Horde copy account</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo (int)($backupPreview['alliance_account'] ?? 0); ?></strong>
        <span>Configured Alliance copy account</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo (int)($backupPreview['character_count'] ?? 0); ?></strong>
        <span>Characters currently in backup scope</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo !empty($backupPreview['output_dir_writable']) ? 'Writable' : 'Locked'; ?></strong>
        <span><?php echo htmlspecialchars((string)($backupPreview['output_dir'] ?? '')); ?></span>
      </div>
    </div>
  </section>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Export Settings</p>
    <h2 class="backup-admin__title">Create Backup File</h2>
    <p class="backup-admin__note">Pick non-conflicting GUID ranges for the target environment. Character GUIDs and item GUIDs are remapped independently during export.</p>

    <?php if (!empty($backupActionState['notice'])): ?>
      <div class="backup-admin__msg success"><?php echo htmlspecialchars((string)$backupActionState['notice']); ?></div>
    <?php endif; ?>
    <?php if (!empty($backupActionState['error'])): ?>
      <div class="backup-admin__msg error"><?php echo htmlspecialchars((string)$backupActionState['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="index.php?n=admin&amp;sub=backup">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
      <input type="hidden" name="backup_action" value="create_copy_chars_backup">
      <div class="backup-admin__form">
        <label for="starting_char_id">Starting Character GUID</label>
        <input type="text" id="starting_char_id" name="starting_char_id" value="<?php echo htmlspecialchars((string)($_POST['starting_char_id'] ?? '10')); ?>">

        <label for="starting_item_id">Starting Item GUID</label>
        <input type="text" id="starting_item_id" name="starting_item_id" value="<?php echo htmlspecialchars((string)($_POST['starting_item_id'] ?? '100000')); ?>">
      </div>
      <div class="backup-admin__actions">
        <input type="submit" value="Create SQL Backup" <?php if (empty($backupPreview['configured']) || empty($backupPreview['output_dir_writable'])) echo 'disabled="disabled"'; ?>>
      </div>
    </form>
  </section>
</div>
<?php builddiv_end() ?>
