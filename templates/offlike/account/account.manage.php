<?php if($user['id']>0 && isset($profile)){ ?>
<?php $GLOBALS['builddiv_header_actions'] = '<a href="index.php?n=account&sub=userlist" class="btn secondary">Back to User List</a>'; ?>
<?php builddiv_start(1, $lang['accediting']); ?>
<?php
$currentExpansionId = (int)($profile['expansion'] ?? 0);
$nextExpansionLabel = '';
$transferPathLabel = '';
$languageOptions = isset($languages) && is_array($languages) ? $languages : [];
$selectedLanguage = strtolower(trim((string)($GLOBALS['user_cur_lang'] ?? $MW->getConfig->generic->default_lang ?? 'en')));
if ($selectedLanguage === '') {
  $selectedLanguage = 'en';
}
if ($currentExpansionId === 0) {
  $nextExpansionLabel = 'TBC';
  $transferPathLabel = 'Classic -> TBC';
} elseif ($currentExpansionId === 1) {
  $nextExpansionLabel = 'WotLK';
  $transferPathLabel = 'TBC -> WotLK';
}
?>

<div class="modern-content settings-page">
  <section class="settings-hero">
    <div>
      <div class="settings-kicker">Account Center</div>
      <h2><?php echo htmlspecialchars($profile['username']); ?></h2>
      <p class="settings-intro">Update your public profile, account access, recovery settings, and game expansion from one place.</p>
    </div>
    <div class="settings-badges">
      <span>
        <?php
        $currentExpansion = 'Classic';
        if ((int)($profile['expansion'] ?? 0) === 1) $currentExpansion = 'TBC';
        if ((int)($profile['expansion'] ?? 0) === 2) $currentExpansion = 'WotLK';
        echo $currentExpansion;
        ?>
      </span>
    </div>
  </section>

  <div class="settings-grid">
    <section class="settings-card">
      <div class="card-title">Profile Settings</div>
      <form method="post" action="index.php?n=account&sub=manage&action=change" enctype="multipart/form-data" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
        <div class="field">
          <label>Username</label>
          <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled="disabled">
        </div>

        <?php if(!empty($languageOptions)): ?>
        <div class="field">
          <label>Language</label>
          <select onchange="changeLanguage(this.value)">
            <?php foreach($languageOptions as $langCode => $langName): ?>
            <option value="<?php echo htmlspecialchars((string)$langCode); ?>"<?php if(strtolower((string)$langCode) === $selectedLanguage) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$langName); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="help-text">English is the default. Changing this updates the site language for your browser.</div>
        </div>
        <?php endif; ?>

        <div class="toggle-row">
          <?php if((int)($user['gmlevel'] ?? 0) >= 3): ?>
          <label class="field compact">
            <span>Hide profile</span>
            <select name="profile[hideprofile]">
              <option value="0"<?php if((int)$profile['hideprofile']===0)echo' selected';?>><?php echo $lang['no']; ?></option>
              <option value="1"<?php if((int)$profile['hideprofile']===1)echo' selected';?>><?php echo $lang['yes']; ?></option>
            </select>
          </label>
          <?php endif; ?>
        </div>

        <?php if((int)$MW->getConfig->generic->change_template) { ?>
        <div class="field">
          <label><?php echo $lang['theme']; ?></label>
          <select name="profile[theme]">
            <?php
            $i = 0;
            foreach($MW->getConfig->templates->template as $template){ ?>
            <option value="<?php echo $i; ?>"<?php if((int)$profile['theme']===$i)echo' selected';?>><?php echo htmlspecialchars((string)$template); ?></option>
            <?php $i++; } ?>
          </select>
        </div>
        <?php } ?>

        <?php if(!empty($backgroundPreferencesAvailable)): ?>
        <div class="field">
          <label>Site Background Behavior</label>
          <select name="profile[background_mode]" id="background-mode-select">
            <?php foreach($backgroundModeOptions as $backgroundModeValue => $backgroundModeLabel): ?>
            <option value="<?php echo htmlspecialchars($backgroundModeValue); ?>"<?php if(($profile['background_mode'] ?? 'as_is') === $backgroundModeValue) echo ' selected'; ?>>
              <?php echo htmlspecialchars($backgroundModeLabel); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="help-text">As Is keeps the current random-per-page behavior. Once a Day keeps one background all day. By Main Section keeps one image per top-level area like Armory, Workshop, Forums, or Game Guide. Fixed Background locks the site to one image you pick.</div>
        </div>

        <div class="field background-image-picker" id="background-image-picker">
          <label>Fixed Background Image</label>
          <select name="profile[background_image]">
            <?php foreach($availableBackgroundImages as $backgroundFilename => $backgroundPath): ?>
            <option value="<?php echo htmlspecialchars($backgroundFilename); ?>"<?php if(($profile['background_image'] ?? '') === $backgroundFilename) echo ' selected'; ?>>
              <?php echo htmlspecialchars(spp_background_image_label($backgroundFilename)); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="help-text">This image is used when Fixed Background is selected.</div>
        </div>
        <?php endif; ?>

        <div class="avatar-block">
          <div class="avatar-preview">
            <?php if(!empty($profile['selected_character_avatar_url'])) { ?>
              <img id="selected-character-avatar" src="<?php echo htmlspecialchars($profile['selected_character_avatar_url']); ?>" alt="Character Portrait">
            <?php } elseif(!empty($profile['avatar'])) { ?>
              <img src="images/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Avatar">
            <?php } elseif(!empty($profile['avatar_fallback_url'])) { ?>
              <img src="<?php echo htmlspecialchars($profile['avatar_fallback_url']); ?>" alt="Forum Avatar">
            <?php } else { ?>
              <div class="avatar-placeholder"><?php echo strtoupper(substr($profile['username'], 0, 1)); ?></div>
            <?php } ?>
          </div>
          <div class="avatar-controls">
            <label class="field">
              <span>Upload avatar</span>
              <input type="file" name="avatar">
            </label>
            <div class="help-text">
              Max file size: <?php echo (int)$MW->getConfig->generic->max_avatar_file; ?> bytes. Max size: <?php echo htmlspecialchars((string)$MW->getConfig->generic->max_avatar_size); ?> px.
            </div>
            <?php if(!empty($profile['avatar'])) { ?>
            <label class="checkbox-row">
              <input type="checkbox" name="deleteavatar" value="1">
              <span>Delete current avatar</span>
            </label>
            <input type="hidden" name="avatarfile" value="<?php echo htmlspecialchars($profile['avatar']); ?>">
            <?php } ?>
          </div>
        </div>

        <?php if(!empty($accountCharacters)): ?>
        <div class="field">
          <label>Signature Character</label>
          <select id="signature-character-key" name="signature_character_key">
            <?php foreach($accountCharacters as $character): ?>
              <?php
                $sigKey = (int)($character['realm_id'] ?? 0) . ':' . (int)$character['guid'];
                $sigValue = (string)($profile['character_signatures'][$sigKey]['signature'] ?? '');
                $sigRealmName = (string)($character['realm_name'] ?? ('Realm ' . (int)($character['realm_id'] ?? 0)));
              ?>
              <option
                value="<?php echo htmlspecialchars($sigKey); ?>"
                data-signature="<?php echo htmlspecialchars($sigValue); ?>"
                data-avatar-url="<?php echo htmlspecialchars((string)($profile['character_signatures'][$sigKey]['avatar_url'] ?? '')); ?>"
                <?php if((string)($profile['signature_character_key'] ?? '') === $sigKey) echo 'selected'; ?>
              >
                <?php echo htmlspecialchars($character['name']); ?><?php if(!empty($character['level'])) echo ' (Lvl ' . (int)$character['level'] . ')'; ?> - <?php echo htmlspecialchars($sigRealmName); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="help-text">This signature will be used by the selected character when posting on the forum.</div>
        </div>
        <?php endif; ?>

        <div class="field">
          <label><?php echo $lang['signature']; ?></label>
          <textarea id="profile-signature" name="profile[signature]" maxlength="255" rows="4"><?php echo htmlspecialchars(my_previewreverse($profile['signature'])); ?></textarea>
          <div class="help-text">Supports normal BBCode. Keep it short and readable.</div>
        </div>

        <div class="actions">
          <button type="button" class="btn secondary" onclick="document.getElementById('profile-signature').value='';"><?php echo $lang['reset']; ?></button>
          <button type="submit" class="btn primary"><?php echo $lang['dochange']; ?></button>
        </div>
      </form>
    </section>

    <section class="settings-card">
      <div class="card-title">Access Settings</div>

      <div class="expansion-panel section-gap">
        <div class="mini-title">Game Expansion</div>
        <div class="expansion-grid">
          <form method="post" action="index.php?n=account&sub=manage&action=change_gameplay">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
            <input type="hidden" name="switch_wow_type" value="classic">
            <button type="submit" class="expansion-btn<?php if((int)$profile['expansion']===0)echo' active'; ?>">Classic</button>
          </form>
          <form method="post" action="index.php?n=account&sub=manage&action=change_gameplay">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
            <input type="hidden" name="switch_wow_type" value="tbc">
            <button type="submit" class="expansion-btn<?php if((int)$profile['expansion']===1)echo' active'; ?>">TBC</button>
          </form>
          <form method="post" action="index.php?n=account&sub=manage&action=change_gameplay">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
            <input type="hidden" name="switch_wow_type" value="wotlk">
            <button type="submit" class="expansion-btn<?php if((int)$profile['expansion']===2)echo' active'; ?>">WotLK</button>
          </form>
        </div>
      </div>

      <div class="expansion-panel section-gap">
        <div class="mini-title">Expansion Transfer</div>
        <div class="tool-panel tool-panel-soon access-tool-panel">
          <div class="status-pill">Coming Soon</div>
          <div class="help-text">
            <?php if($nextExpansionLabel !== '') { ?>
            Transfer a character forward from your current expansion to <?php echo htmlspecialchars($nextExpansionLabel); ?> when migration opens.
            <?php } else { ?>
            Character transfers open only when a higher expansion is available for your account.
            <?php } ?>
          </div>
          <div class="field">
            <label>Path</label>
            <input type="text" value="<?php echo htmlspecialchars($transferPathLabel !== '' ? $transferPathLabel : 'WotLK is the current cap'); ?>" disabled="disabled">
          </div>
          <div class="help-text">
            <?php if($nextExpansionLabel !== '') { ?>
            This will be limited to forward-only progression and will appear here once the <?php echo htmlspecialchars($nextExpansionLabel); ?> transfer flow is ready.
            <?php } else { ?>
            Your account is already on the highest expansion currently listed here, so there is no next-step transfer yet.
            <?php } ?>
          </div>
          <div class="actions">
            <button type="button" class="btn secondary" disabled="disabled">Character Transfer</button>
          </div>
        </div>
      </div>
    </section>
  </div>

  <section class="settings-card recovery-card">
    <div class="card-title">Account Tools</div>
    <div class="recovery-grid">
      <form id="password-change-form" method="post" action="index.php?n=account&sub=manage&action=changepass" class="stack-form tool-panel">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
        <div class="mini-title">Password</div>
        <div class="help-text">Update your account password here.</div>
        <div class="field">
          <label><?php echo $lang['newpass']; ?></label>
          <input type="password" name="new_pass">
        </div>
        <div class="field">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_new_pass">
        </div>
        <div class="actions">
          <button type="submit" class="btn primary">Change Password</button>
        </div>
      </form>

      <form method="post" action="index.php?n=account&sub=manage&action=renamechar" class="stack-form tool-panel">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
        <div class="mini-title">Character Rename</div>
        <div class="help-text">Rename a character on this account for the currently selected realm: <?php echo htmlspecialchars($manageRealmName ?? 'Current Realm'); ?>.</div>
        <div class="field">
          <label>Character</label>
          <select name="character_guid">
            <?php if(!empty($renameCharacters)): ?>
              <?php foreach($renameCharacters as $character): ?>
              <option value="<?php echo (int)$character['guid']; ?>">
                <?php echo htmlspecialchars($character['name']); ?><?php if(!empty($character['level'])) echo ' (Lvl ' . (int)$character['level'] . ')'; ?>
              </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="0">No characters available</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="field">
          <label>New Character Name</label>
          <input type="text" name="new_character_name" maxlength="20">
        </div>
        <div class="help-text">The character must be offline and the new name must be unused.</div>
        <div class="actions">
          <button type="submit" class="btn primary"<?php if(empty($renameCharacters)) echo ' disabled="disabled"'; ?>>Rename Character</button>
        </div>
      </form>

    </div>
  </section>
</div>

<style>
.settings-page {
  color: #ddd;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.settings-hero,
.settings-card {
  border-radius: 16px;
  border: 1px solid rgba(255, 214, 120, 0.18);
  background: linear-gradient(180deg, rgba(15,15,15,0.92), rgba(8,8,8,0.82));
  box-shadow: 0 12px 28px rgba(0,0,0,0.22);
}

.settings-hero {
  padding: 20px 22px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
}

.settings-kicker,
.card-title,
.mini-title {
  color: #c7a56a;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.72rem;
}

.settings-hero h2 {
  margin: 6px 0 8px;
  color: #ffcc66;
  font-size: 2rem;
}

.settings-intro {
  margin: 0;
  max-width: 640px;
  color: #c9c9c9;
  line-height: 1.5;
}

.settings-badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.settings-badges span {
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
}

.settings-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 18px;
}

.settings-card {
  padding: 18px 20px;
}

.stack-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-top: 14px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label,
.field span {
  color: #bdbdbd;
  font-size: 0.95rem;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid rgba(255, 214, 120, 0.22);
  background: rgba(15, 18, 22, 0.84);
  color: #f1f1f1;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
}

.field input::placeholder,
.field textarea::placeholder {
  color: rgba(226, 226, 226, 0.45);
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: none;
  border-color: rgba(255, 206, 102, 0.55);
  box-shadow: 0 0 0 3px rgba(216, 158, 57, 0.12);
}

.field input:disabled {
  opacity: 0.8;
}

.toggle-row,
.recovery-grid {
  display: grid;
  gap: 14px;
}

.toggle-row {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.recovery-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.tool-panel {
  margin-top: 12px;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.03);
}

.tool-panel-soon {
  position: relative;
  overflow: hidden;
}

.tool-panel-soon::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 214, 120, 0.05), transparent 42%);
  pointer-events: none;
}

.access-tool-panel {
  margin-top: 0;
}

.status-pill {
  display: inline-flex;
  align-self: flex-start;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid rgba(255, 214, 120, 0.22);
  background: rgba(216, 158, 57, 0.12);
  color: #ffcc66;
  font-size: 0.72rem;
  font-weight: bold;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.compact select {
  width: 100%;
}

.avatar-block {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 16px;
  align-items: start;
}

.avatar-preview img,
.avatar-placeholder {
  width: 104px;
  height: 104px;
  border-radius: 18px;
  border: 1px solid rgba(255, 214, 120, 0.2);
  object-fit: cover;
  background: rgba(255,255,255,0.04);
}

.avatar-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #ffd27a;
  font-weight: bold;
}

.help-text {
  color: #9d9d9d;
  font-size: 0.9rem;
  line-height: 1.5;
}

.background-image-picker.is-hidden {
  display: none;
}

.checkbox-row {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #d5d5d5;
}

.checkbox-row input {
  width: auto;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn,
.expansion-btn {
  border: 0;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: bold;
  cursor: pointer;
}

.btn.primary,
.expansion-btn.active {
  background: linear-gradient(180deg, #ffd27a, #d89e39);
  color: #17120a;
}

.btn.secondary,
.expansion-btn {
  background: rgba(255,255,255,0.07);
  color: #eee;
  border: 1px solid rgba(255,255,255,0.12);
}

/* Match forum header button style */
.builddiv-actions .btn {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: .7em;
  text-decoration: none;
  display: inline-block;
  border: 0;
}
.builddiv-actions .btn.secondary {
  background: #333;
  color: #ccc;
  border: 0;
}
.builddiv-actions .btn:hover { opacity: 0.9; }

.section-gap {
  margin-top: 18px;
}

.expansion-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.expansion-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.expansion-grid form {
  margin: 0;
}

.expansion-btn {
  width: 100%;
}

.recovery-card {
  grid-column: 1 / -1;
}

@media (max-width: 920px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }

  .recovery-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 720px) {
  .settings-hero {
    flex-direction: column;
  }

  .settings-badges {
    justify-content: flex-start;
  }

  .toggle-row,
  .avatar-block,
  .expansion-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<?php if(!empty($accountCharacters)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var signatureSelect = document.getElementById('signature-character-key');
  var signatureField = document.getElementById('profile-signature');
  var avatarImage = document.getElementById('selected-character-avatar');
  if (!signatureSelect || !signatureField) {
    return;
  }

  var syncSelectedCharacter = function () {
    var selectedOption = signatureSelect.options[signatureSelect.selectedIndex];
    signatureField.value = selectedOption ? (selectedOption.getAttribute('data-signature') || '') : '';
    if (avatarImage && selectedOption) {
      var avatarUrl = selectedOption.getAttribute('data-avatar-url') || '';
      if (avatarUrl !== '') {
        avatarImage.setAttribute('src', avatarUrl);
      }
    }
  };

  signatureSelect.addEventListener('change', syncSelectedCharacter);
  syncSelectedCharacter();
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const backgroundModeSelect = document.getElementById('background-mode-select');
  const backgroundImagePicker = document.getElementById('background-image-picker');

  if (!backgroundModeSelect || !backgroundImagePicker) {
    return;
  }

  const syncBackgroundPicker = () => {
    backgroundImagePicker.classList.toggle('is-hidden', backgroundModeSelect.value !== 'fixed');
  };

  backgroundModeSelect.addEventListener('change', syncBackgroundPicker);
  syncBackgroundPicker();
});
</script>

<?php builddiv_end() ?>
<?php } ?>
