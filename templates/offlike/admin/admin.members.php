<br>
<?php builddiv_start(1, $lang['si_acc']) ?>
<style>
.admin-members {
  color: #ddd;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.admin-panel,
.admin-detail-grid > div,
.admin-character-list,
.admin-form-panel,
.admin-list-shell,
.admin-list-toolbar {
  background: linear-gradient(180deg, rgba(18, 18, 18, 0.92), rgba(9, 9, 9, 0.9));
  border: 1px solid rgba(223, 168, 70, 0.28);
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
}

.admin-panel,
.admin-form-panel,
.admin-character-list,
.admin-list-shell,
.admin-list-toolbar {
  padding: 18px 20px;
}

.admin-heading {
  margin: 0 0 8px;
  color: #ffcc66;
  font-size: 1.35rem;
}

.admin-subheading {
  margin: 0 0 14px;
  color: #c9a86a;
  font-size: 0.82rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.admin-note {
  margin: 0;
  color: #c9c9c9;
}

.admin-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.admin-btn,
.admin-btn:visited,
.admin-form-panel input[type=submit],
.admin-form-panel input[type=reset],
.toolbar-form button,
.toolbar-form .toolbar-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 9px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.35);
  background: rgba(255, 193, 72, 0.08);
  color: #ffd27a;
  text-decoration: none;
  font-weight: bold;
  cursor: pointer;
}

.admin-btn:hover,
.admin-form-panel input[type=submit]:hover,
.admin-form-panel input[type=reset]:hover,
.toolbar-form button:hover,
.toolbar-form .toolbar-link:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
  box-shadow: 0 0 10px rgba(255, 193, 72, 0.18);
}

.admin-btn.danger,
.toolbar-form .danger {
  border-color: rgba(255, 110, 110, 0.38);
  background: rgba(170, 35, 35, 0.18);
  color: #ff9b9b;
}

.admin-btn.danger:hover,
.toolbar-form .danger:hover {
  background: rgba(170, 35, 35, 0.28);
  color: #ffe2e2;
}

.admin-btn[aria-disabled="true"],
.toolbar-form .toolbar-link[aria-disabled="true"] {
  cursor: not-allowed;
  opacity: 0.55;
  color: #b9b9b9;
  background: rgba(90, 90, 90, 0.14);
  border-color: rgba(180, 180, 180, 0.18);
  box-shadow: none;
  pointer-events: none;
}

.admin-detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
  gap: 18px;
}

.admin-detail-grid > div {
  padding: 18px 20px;
}

.admin-meta {
  display: grid;
  grid-template-columns: minmax(160px, 220px) 1fr;
  gap: 10px 18px;
}

.admin-meta-label {
  color: #c3a46a;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.76rem;
}

.admin-meta-value {
  color: #f2f2f2;
}

.admin-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  font-weight: bold;
  margin-bottom: 12px;
}

.admin-status.active {
  background: rgba(44, 127, 75, 0.18);
  border: 1px solid rgba(92, 199, 129, 0.28);
  color: #8ff0a7;
}

.admin-status.banned {
  background: rgba(170, 35, 35, 0.18);
  border: 1px solid rgba(255, 110, 110, 0.28);
  color: #ff9b9b;
}

.admin-character-list ul {
  margin: 0;
  padding-left: 18px;
}

.admin-character-list li {
  margin: 0 0 10px;
  color: #ddd;
}

.admin-character-list a {
  color: #ffcc66;
  text-decoration: none;
}

.admin-character-list a:hover {
  color: #fff;
}

.admin-form-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.admin-form-grid {
  display: grid;
  grid-template-columns: minmax(180px, 220px) minmax(0, 1fr);
  gap: 10px 16px;
  align-items: center;
}

.admin-form-grid label,
.admin-field-label {
  color: #c3a46a;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.admin-form-grid input[type=text],
.admin-form-grid input[type=password],
.admin-form-grid input[type=file],
.admin-form-grid select,
.toolbar-form input[type=text],
.toolbar-form select,
.admin-form-panel textarea {
  width: 100%;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.2);
  background: rgba(12, 12, 12, 0.72);
  color: #f1f1f1;
  padding: 10px 12px;
  box-sizing: border-box;
}

.admin-form-panel textarea {
  min-height: 120px;
  resize: vertical;
}

.admin-form-help {
  color: #a9a9a9;
  font-size: 0.88rem;
}

.admin-form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.admin-signature-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.admin-signature-row {
  display: grid;
  grid-template-columns: minmax(110px, 150px) minmax(0, 1fr);
  gap: 10px 14px;
  align-items: center;
}

.admin-signature-row label {
  color: #c3a46a;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.admin-avatar-preview {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-avatar-preview img {
  max-width: 88px;
  max-height: 88px;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.3);
}

.admin-list-toolbar {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.toolbar-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: end;
}

.toolbar-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: end;
}

.toolbar-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 160px;
}

.toolbar-links {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.toolbar-links a {
  color: #ffd27a;
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 193, 72, 0.08);
  border: 1px solid rgba(255, 206, 102, 0.22);
}

.toolbar-links a.active,
.toolbar-links a:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
}

.admin-list-shell {
  padding: 0;
  overflow: hidden;
}

.admin-list-row {
  display: grid;
  grid-template-columns: minmax(220px, 1.4fr) 110px 150px 110px 110px;
  gap: 0;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(15, 15, 15, 0.45);
}

.admin-list-row.header {
  background: linear-gradient(to bottom, rgba(84, 56, 10, 0.9), rgba(37, 24, 5, 0.95));
  color: gold;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.admin-list-row:last-child {
  border-bottom: none;
}

.admin-list-row > div {
  padding: 14px 16px;
}

.admin-list-row .name a,
.admin-list-row .manage a {
  color: #ffcc66;
  text-decoration: none;
  font-weight: bold;
}

.admin-list-row .name a:hover,
.admin-list-row .manage a:hover {
  color: #fff;
}

.admin-list-empty {
  padding: 28px 20px;
  color: #b8b8b8;
  text-align: center;
}

.admin-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: bold;
}

.admin-badge.bot {
  background: rgba(77, 135, 255, 0.14);
  color: #8db9ff;
  border: 1px solid rgba(77, 135, 255, 0.28);
}

.admin-badge.human {
  background: rgba(74, 170, 125, 0.14);
  color: #8ef0b7;
  border: 1px solid rgba(74, 170, 125, 0.28);
}

.admin-badge.good {
  background: rgba(74, 170, 125, 0.14);
  color: #8ef0b7;
  border: 1px solid rgba(74, 170, 125, 0.28);
}

.admin-badge.warn {
  background: rgba(170, 35, 35, 0.18);
  color: #ff9b9b;
  border: 1px solid rgba(255, 110, 110, 0.28);
}

.admin-pagination {
  padding: 16px 20px;
  color: #d6d6d6;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.admin-pagination a {
  color: #ffcc66;
}

@media (max-width: 1100px) {
  .admin-detail-grid {
    grid-template-columns: 1fr;
  }

  .admin-list-row {
    grid-template-columns: minmax(160px, 1.2fr) 100px 140px 105px 100px;
  }
}

@media (max-width: 860px) {
  .admin-form-grid,
  .admin-meta,
  .admin-signature-row {
    grid-template-columns: 1fr;
  }

  .admin-list-row {
    grid-template-columns: 1fr;
  }

  .admin-list-row.header {
    display: none;
  }

  .admin-list-row > div {
    padding-top: 8px;
    padding-bottom: 8px;
  }
}
</style>

<div class="admin-members">
<?php if (isset($_GET['id']) && $_GET['id'] > 0 && $profile) { ?>
  <div class="admin-panel">
    <p class="admin-subheading">Member Profile</p>
    <h2 class="admin-heading"><?php echo htmlspecialchars($profile['username']); ?></h2>
    <div class="admin-status <?php echo $act ? 'banned' : 'active'; ?>">
      <?php echo $act ? 'Banned' : 'Active'; ?>
    </div>
    <div class="admin-meta">
      <div class="admin-meta-label">Forum Posts</div>
      <div class="admin-meta-value"><?php echo (int)$profile['forum_posts']; ?></div>

      <div class="admin-meta-label">Registered</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($profile['joindate']); ?></div>

      <div class="admin-meta-label">Registration IP</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($profile['registration_ip']); ?></div>

      <div class="admin-meta-label">Last Login (Game)</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($profile['last_login']); ?></div>

      <div class="admin-meta-label">Forum Group</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($allgroups[$profile['g_id']] ?? 'Unassigned'); ?></div>

      <div class="admin-meta-label">Expansion</div>
      <div class="admin-meta-value">
        <?php
        $expansionNames = array(0 => 'Classic', 1 => 'TBC', 2 => 'WotLK');
        echo htmlspecialchars($expansionNames[(int)$profile['expansion']] ?? 'Classic');
        ?>
      </div>
    </div>

    <div class="admin-actions">
      <a class="admin-btn danger" href="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=dodeleteacc" onclick="return confirm('Are you sure?');">Delete Account</a>
      <?php if ($act == 1) { ?>
        <a class="admin-btn" href="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=unban">Unban Account</a>
      <?php } else { ?>
        <a class="admin-btn danger" href="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=ban">Ban Account</a>
      <?php } ?>
      <a class="admin-btn" href="index.php?n=admin&sub=members">Back to Members</a>
    </div>
  </div>

  <div class="admin-detail-grid">
    <div class="admin-character-list">
      <p class="admin-subheading">Realm Characters</p>
      <h3 class="admin-heading" style="font-size:1.1rem;">Characters</h3>
      <?php if (!empty($userchars)) { ?>
        <ul>
          <?php
          $MANG = new Mangos;
          foreach ($userchars as $char) {
              $profileRealm = (int)($GLOBALS['activeRealmId'] ?? 1);
              $charUrl = 'index.php?n=server&sub=character&realm=' . $profileRealm . '&character=' . urlencode($char['name']);
              echo '<li><a href="' . $charUrl . '">' . htmlspecialchars($char['name']) . '</a> &middot; Level ' . (int)$char['level'] . ' &middot; ' .
                  htmlspecialchars($MANG->characterInfoByID['character_race'][$char['race']] ?? '') . ' ' .
                  htmlspecialchars($MANG->characterInfoByID['character_class'][$char['class']] ?? '') . '</li>';
          }
          unset($MANG);
          ?>
        </ul>
      <?php } else { ?>
        <p class="admin-note">This account does not have any characters on the active realm.</p>
      <?php } ?>
    </div>

    <div class="admin-form-panel">
      <?php if (!empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Bot Profiles</p>
        <h3 class="admin-heading" style="font-size:1.1rem;">Character Signatures</h3>
        <form method="post" action="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=setbotsignatures" class="admin-form-stack">
          <div class="admin-signature-stack">
            <?php if (!empty($userchars)) { ?>
              <?php foreach ($userchars as $char) { ?>
                <?php $charGuid = (int)($char['guid'] ?? 0); ?>
                <div class="admin-signature-row">
                  <label for="character_signature_<?php echo $charGuid; ?>"><?php echo htmlspecialchars($char['name']); ?></label>
                  <input
                    type="text"
                    id="character_signature_<?php echo $charGuid; ?>"
                    name="character_signature[<?php echo $charGuid; ?>]"
                    maxlength="255"
                    value="<?php echo htmlspecialchars((string)($profile['character_signatures'][$charGuid] ?? '')); ?>"
                  />
                </div>
              <?php } ?>
            <?php } else { ?>
              <div class="admin-form-help">This bot account has no characters on the active realm.</div>
            <?php } ?>
          </div>
          <div class="admin-form-help">Set one forum signature line per bot character. These signatures follow the character that posts.</div>
          <div class="admin-form-actions">
            <input type="submit" value="Set Signatures" />
          </div>
        </form>
      <?php } ?>
      <?php if (empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Security</p>
        <h3 class="admin-heading" style="font-size:1.1rem;">Change Password</h3>
        <form method="post" action="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=changepass" class="admin-form-stack">
          <div class="admin-form-grid">
            <label for="member_new_pass">New Password</label>
            <input type="password" id="member_new_pass" name="new_pass" />

            <label for="member_confirm_new_pass">Confirm Password</label>
            <input type="password" id="member_confirm_new_pass" name="confirm_new_pass" />
          </div>
          <div class="admin-form-actions">
            <input type="submit" value="Change Password" />
          </div>
        </form>
      <?php } ?>
    </div>
  </div>

  <div class="admin-detail-grid">
    <div class="admin-form-panel">
      <p class="admin-subheading">Account Controls</p>
      <h3 class="admin-heading" style="font-size:1.1rem;">Game Account</h3>
      <form method="post" action="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=change" class="admin-form-stack">
        <div class="admin-form-grid">
          <?php if ($user['gmlevel'] == 3) { ?>
            <label for="profile_gmlevel">GM Level</label>
            <input type="text" id="profile_gmlevel" name="profile[gmlevel]" value="<?php echo htmlspecialchars($profile['gmlevel']); ?>" />
          <?php } ?>

          <label for="profile_expansion">Account Expansion</label>
          <select id="profile_expansion" name="profile[expansion]">
            <option value="0"<?php if ((int)$profile['expansion'] === 0) echo ' selected'; ?>>Classic</option>
            <option value="1"<?php if ((int)$profile['expansion'] === 1) echo ' selected'; ?>>TBC</option>
            <option value="2"<?php if ((int)$profile['expansion'] === 2) echo ' selected'; ?>>WotLK</option>
          </select>
        </div>
        <div class="admin-form-actions">
          <input type="reset" value="Reset" />
          <input type="submit" value="Save Changes" />
        </div>
      </form>
    </div>

    <div class="admin-form-panel">
      <?php if (!empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Security</p>
        <h3 class="admin-heading" style="font-size:1.1rem;">Change Password</h3>
        <form method="post" action="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=changepass" class="admin-form-stack">
          <div class="admin-form-grid">
            <label for="member_new_pass_bot">New Password</label>
            <input type="password" id="member_new_pass_bot" name="new_pass" />

            <label for="member_confirm_new_pass_bot">Confirm Password</label>
            <input type="password" id="member_confirm_new_pass_bot" name="confirm_new_pass" />
          </div>
          <div class="admin-form-actions">
            <input type="submit" value="Change Password" />
          </div>
        </form>
      <?php } ?>
      <?php if (empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Website Profile</p>
        <h3 class="admin-heading" style="font-size:1.1rem;">Forum Settings</h3>
        <form method="post" action="index.php?n=admin&sub=members&id=<?php echo (int)$_GET['id']; ?>&action=change2" enctype="multipart/form-data" class="admin-form-stack">
          <div class="admin-form-grid">
            <label for="profile_gid">Group</label>
            <select id="profile_gid" name="profile[g_id]">
              <?php foreach ($allgroups as $group_id => $group_name) { ?>
                <option value="<?php echo (int)$group_id; ?>"<?php if ((int)$profile['g_id'] === (int)$group_id) echo ' selected'; ?>><?php echo htmlspecialchars($group_name); ?></option>
              <?php } ?>
            </select>

            <?php if ((int)$MW->getConfig->generic->change_template) { ?>
              <label for="profile_theme">Theme</label>
              <select id="profile_theme" name="profile[theme]">
                <?php
                $i = 0;
                foreach ($MW->getConfig->templates->template as $template) {
                    echo '<option value="' . (int)$i . '"' . ((int)$profile['theme'] === $i ? ' selected' : '') . '>' . htmlspecialchars($template) . '</option>';
                    $i++;
                }
                ?>
              </select>
            <?php } ?>

            <label for="profile_hideprofile">Hide Profile</label>
            <select id="profile_hideprofile" name="profile[hideprofile]">
              <option value="0"<?php if ((int)$profile['hideprofile'] === 0) echo ' selected'; ?>>No</option>
              <option value="1"<?php if ((int)$profile['hideprofile'] === 1) echo ' selected'; ?>>Yes</option>
            </select>

            <label for="profile_signature">Signature</label>
            <textarea id="profile_signature" name="profile[signature]" maxlength="255"><?php echo htmlspecialchars($profile['signature']); ?></textarea>

            <div class="admin-field-label">Avatar</div>
            <div>
              <?php if ($profile['avatar']) { ?>
                <div class="admin-avatar-preview">
                  <img src="images/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Avatar" />
                  <div>
                    <input type="hidden" name="avatarfile" value="<?php echo htmlspecialchars($profile['avatar']); ?>">
                    <label><input type="checkbox" name="deleteavatar" value="1"> Delete current avatar</label>
                  </div>
                </div>
              <?php } else { ?>
                <div class="admin-form-help">Max file size: <?php echo (int)$MW->getConfig->generic->max_avatar_file; ?> bytes. Max resolution: <?php echo htmlspecialchars((string)$MW->getConfig->generic->max_avatar_size); ?>.</div>
                <input type="file" name="avatar" />
              <?php } ?>
            </div>
          </div>
          <div class="admin-form-actions">
            <input type="reset" value="Reset" />
            <input type="submit" value="Save Profile" />
          </div>
        </form>
      <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="admin-list-toolbar">
    <div class="toolbar-row">
      <div class="toolbar-group" style="min-width:240px;">
        <span class="admin-subheading" style="margin:0;">Maintenance</span>
        <div class="admin-note">Bulk account and character deletion is disabled until the cleanup flow is reviewed in detail.</div>
        <div class="toolbar-links">
          <a class="danger" href="#" aria-disabled="true">Delete Inactive Accounts</a>
          <a class="danger" href="#" aria-disabled="true">Delete Old Characters</a>
        </div>
      </div>

      <form action="index.php?n=admin&sub=members" method="post" class="toolbar-form">
        <div class="toolbar-group">
          <label class="admin-field-label" for="search_member">Search Username</label>
          <input type="text" id="search_member" name="search_member" placeholder="Account name" />
        </div>
        <button type="submit">Search</button>
      </form>
    </div>

    <div class="toolbar-row">
      <form method="get" action="index.php" class="toolbar-form">
        <input type="hidden" name="n" value="admin" />
        <input type="hidden" name="sub" value="members" />

        <div class="toolbar-group">
          <label class="admin-field-label" for="show_bots">Account Scope</label>
          <select id="show_bots" name="show_bots">
            <option value="1"<?php if ($includeBots) echo ' selected'; ?>>Humans + Bots</option>
            <option value="0"<?php if (!$includeBots) echo ' selected'; ?>>Humans Only</option>
          </select>
        </div>

        <div class="toolbar-group">
          <label class="admin-field-label" for="char_filter">Letter Filter</label>
          <select id="char_filter" name="char">
            <option value=""><?php echo htmlspecialchars($lang['all']); ?></option>
            <option value="1"<?php if (($_GET['char'] ?? '') === '1') echo ' selected'; ?>>#</option>
            <?php foreach (range('a', 'z') as $letter) { ?>
              <option value="<?php echo $letter; ?>"<?php if (($_GET['char'] ?? '') === $letter) echo ' selected'; ?>><?php echo strtoupper($letter); ?></option>
            <?php } ?>
          </select>
        </div>

        <button type="submit">Apply Filters</button>
      </form>
    </div>

    <div class="toolbar-row">
      <div class="toolbar-group" style="min-width:100%;">
        <span class="admin-subheading" style="margin:0;">Browse</span>
        <div class="toolbar-links">
          <?php
          $baseMembers = 'index.php?n=admin&sub=members&show_bots=' . ($includeBots ? '1' : '0');
          $currentChar = (string)($_GET['char'] ?? '');
          ?>
          <a href="<?php echo $baseMembers; ?>"<?php if ($currentChar === '') echo ' class="active"'; ?>><?php echo htmlspecialchars($lang['all']); ?></a>
          <a href="<?php echo $baseMembers; ?>&char=1"<?php if ($currentChar === '1') echo ' class="active"'; ?>>#</a>
          <?php foreach (range('a', 'z') as $letter) { ?>
            <a href="<?php echo $baseMembers; ?>&char=<?php echo $letter; ?>"<?php if ($currentChar === $letter) echo ' class="active"'; ?>><?php echo strtoupper($letter); ?></a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <div class="admin-list-shell">
    <div class="admin-list-row header">
      <div>User Name</div>
      <div>Type</div>
      <div>Joined</div>
      <div>Status</div>
      <div>Manage</div>
    </div>

    <?php if (is_array($items) && count($items)) { ?>
      <?php foreach ($items as $item) { ?>
        <?php
        $isBot = stripos((string)$item['username'], 'rndbot') === 0;
        $isLocked = isset($item['locked']) && (int)$item['locked'] === 1;
        $isBanned = isset($item['g_id']) && (int)$item['g_id'] === 5;
        ?>
        <div class="admin-list-row">
          <div class="name">
            <a href="index.php?n=admin&sub=members&id=<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['username']); ?></a>
          </div>
          <div>
            <span class="admin-badge <?php echo $isBot ? 'bot' : 'human'; ?>"><?php echo $isBot ? 'Bot' : 'Human'; ?></span>
          </div>
          <div><?php echo htmlspecialchars($item['joindate']); ?></div>
          <div>
            <?php if ($isLocked || $isBanned) { ?>
              <span class="admin-badge warn"><?php echo $isBanned ? 'Banned' : 'Locked'; ?></span>
            <?php } else { ?>
              <span class="admin-badge good">Active</span>
            <?php } ?>
          </div>
          <div class="manage">
            <a href="index.php?n=admin&sub=members&id=<?php echo (int)$item['id']; ?>">Manage</a>
          </div>
        </div>
      <?php } ?>
    <?php } else { ?>
      <div class="admin-list-empty">No members found for the current filter.</div>
    <?php } ?>

    <div class="admin-pagination">
      <?php echo $lang['post_pages']; ?>: <?php echo $pages_str; ?>
    </div>
  </div>
<?php } ?>
</div>
<?php builddiv_end() ?>
