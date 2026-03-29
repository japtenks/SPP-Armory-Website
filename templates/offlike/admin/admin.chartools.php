<?php builddiv_start(0, 'Character Rename') ?>
<style>
.admin-tool-shell {
  display: flex;
  flex-direction: column;
  gap: 18px;
  color: #ddd;
}

.admin-tool-card {
  background: linear-gradient(180deg, rgba(18, 18, 18, 0.92), rgba(9, 9, 9, 0.9));
  border: 1px solid rgba(223, 168, 70, 0.28);
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
  padding: 20px 22px;
}

.admin-tool-kicker {
  margin: 0 0 8px;
  color: #c9a86a;
  font-size: 0.82rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.admin-tool-title {
  margin: 0 0 10px;
  color: #ffcc66;
  font-size: 1.4rem;
}

.admin-tool-copy {
  margin: 0;
  color: #d2d2d2;
}

.admin-tool-form {
  display: grid;
  grid-template-columns: minmax(180px, 220px) minmax(0, 1fr);
  gap: 12px 16px;
  align-items: center;
}

.admin-tool-form label {
  color: #c3a46a;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.admin-tool-form select,
.admin-tool-form input[type=text] {
  width: 100%;
  box-sizing: border-box;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.2);
  background: rgba(12, 12, 12, 0.72);
  color: #f1f1f1;
  padding: 10px 12px;
}

.admin-tool-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 18px;
}

.admin-tool-actions input[type=submit] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 18px;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.35);
  background: rgba(255, 193, 72, 0.08);
  color: #ffd27a;
  font-weight: bold;
  cursor: pointer;
}

.admin-tool-actions input[type=submit]:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
  box-shadow: 0 0 10px rgba(255, 193, 72, 0.18);
}

.admin-tool-msg {
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 0.95rem;
}

.admin-tool-msg.error {
  background: rgba(170, 35, 35, 0.18);
  border: 1px solid rgba(255, 110, 110, 0.28);
  color: #ffb0b0;
}

.admin-tool-msg.success {
  background: rgba(44, 127, 75, 0.18);
  border: 1px solid rgba(92, 199, 129, 0.28);
  color: #9ef0b2;
}

@media (max-width: 820px) {
  .admin-tool-form {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="admin-tool-shell">
  <div class="admin-tool-card">
    <p class="admin-tool-kicker">Character Tools</p>
    <h2 class="admin-tool-title">Rename Character</h2>
    <p class="admin-tool-copy">Rename a character directly from the admin panel. The character must exist on the selected realm and must be offline before the rename can be applied.</p>
  </div>

  <div class="admin-tool-card">
    <form action="index.php?n=admin&sub=chartools" method="post">
      <div class="admin-tool-form">
        <label for="rename_realm">Realm</label>
        <select id="rename_realm" name="realm">
          <?php foreach ($DBS as $realm): ?>
            <option value="<?php echo (int)$realm['id']; ?>"><?php echo htmlspecialchars($realm['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="rename_current_name">Current Character Name</label>
        <input type="text" id="rename_current_name" name="name" maxlength="20" />

        <label for="rename_new_name">New Character Name</label>
        <input type="text" id="rename_new_name" name="newname" maxlength="20" />
      </div>

      <div class="admin-tool-actions">
        <input type="submit" name="rename" value="Rename Character" />
      </div>
    </form>

    <?php
    if (isset($_POST['realm'])) {
        $db1 = $DBS[$_POST['realm']];
        if (isset($_POST['rename'])) {
            if (($_POST['name']) == '' || ($_POST['newname']) == '') {
                echo '<div class="admin-tool-msg error">' . htmlspecialchars($empty_field) . '</div>';
            } else {
                $name = $_POST['name'];
                $newname = ucfirst(strtolower(trim($_POST['newname'])));
                $status = check_if_online($name, $db1);
                $newname_exist = check_if_name_exist($newname, $db1);
                if ($status == -1) {
                    echo '<div class="admin-tool-msg error">' . htmlspecialchars($character_1 . $name . $doesntexist) . '</div>';
                } elseif ($newname_exist == 1) {
                    echo '<div class="admin-tool-msg error">' . htmlspecialchars($alreadyexist . $newname . '!') . '</div>';
                } elseif ($status == 1) {
                    echo '<div class="admin-tool-msg error">' . htmlspecialchars($character_1 . $name . $isonline) . '</div>';
                } else {
                    change_name($name, $newname, $db1);
                    echo '<div class="admin-tool-msg success">' . htmlspecialchars($character_1 . $name . $renamesuccess . $newname . '!') . '</div>';
                }
            }
        }
    }
    ?>
  </div>
</div>
<?php builddiv_end() ?>
