<?php builddiv_start(0, 'Character Transfer') ?>
<?php
$transferEnabled = false;
$cleanDbEnabled = false;
?>
<style>
.admin-transfer-shell {
  display: flex;
  flex-direction: column;
  gap: 18px;
  color: #ddd;
}

.admin-transfer-card {
  background: linear-gradient(180deg, rgba(18, 18, 18, 0.92), rgba(9, 9, 9, 0.9));
  border: 1px solid rgba(223, 168, 70, 0.28);
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
  padding: 20px 22px;
}

.admin-transfer-kicker {
  margin: 0 0 8px;
  color: #c9a86a;
  font-size: 0.82rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.admin-transfer-title {
  margin: 0 0 10px;
  color: #ffcc66;
  font-size: 1.4rem;
}

.admin-transfer-copy {
  margin: 0;
  color: #d2d2d2;
}

.admin-transfer-form {
  display: grid;
  grid-template-columns: minmax(180px, 220px) minmax(0, 1fr);
  gap: 12px 16px;
  align-items: center;
}

.admin-transfer-form label {
  color: #c3a46a;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.admin-transfer-form select,
.admin-transfer-form input[type=text] {
  width: 100%;
  box-sizing: border-box;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.2);
  background: rgba(12, 12, 12, 0.72);
  color: #f1f1f1;
  padding: 10px 12px;
}

.admin-transfer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 18px;
}

.admin-transfer-actions input[type=submit] {
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

.admin-transfer-actions input[type=submit]:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
  box-shadow: 0 0 10px rgba(255, 193, 72, 0.18);
}

.admin-transfer-actions input[type=submit][disabled] {
  cursor: not-allowed;
  opacity: 0.55;
  color: #b9b9b9;
  background: rgba(90, 90, 90, 0.14);
  border-color: rgba(180, 180, 180, 0.18);
  box-shadow: none;
}

.admin-transfer-actions .danger-submit {
  border-color: rgba(255, 110, 110, 0.38);
  background: rgba(170, 35, 35, 0.18);
  color: #ff9b9b;
}

.admin-transfer-actions .danger-submit:hover {
  background: rgba(170, 35, 35, 0.28);
  color: #ffe2e2;
}

.admin-transfer-msg {
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 0.95rem;
}

.admin-transfer-msg.error {
  background: rgba(170, 35, 35, 0.18);
  border: 1px solid rgba(255, 110, 110, 0.28);
  color: #ffb0b0;
}

.admin-transfer-msg.success {
  background: rgba(44, 127, 75, 0.18);
  border: 1px solid rgba(92, 199, 129, 0.28);
  color: #9ef0b2;
}

@media (max-width: 820px) {
  .admin-transfer-form {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="admin-transfer-shell">
  <div class="admin-transfer-card">
    <p class="admin-transfer-kicker">Character Tools</p>
    <h2 class="admin-transfer-title">Transfer Character</h2>
    <p class="admin-transfer-copy">Move a character between installed realms. The character must be offline, and the target realm cannot already have a character with the same name.</p>
    <div class="admin-transfer-msg error" style="margin-top:14px;">This feature is still a work in progress. Transfer and cleanup actions are currently disabled.</div>
  </div>

  <div class="admin-transfer-card">
    <form action="index.php?n=admin&sub=chartransfer" method="post">
      <div class="admin-transfer-form">
        <label for="transfer_name">Character Name</label>
        <input type="text" id="transfer_name" name="name" maxlength="20" />

        <label for="transfer_realm">Current Realm</label>
        <select id="transfer_realm" name="realm">
          <?php foreach ($DBS as $realm): ?>
            <option value="<?php echo (int)$realm['id']; ?>"><?php echo htmlspecialchars($realm['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="transfer_newrealm">Target Realm</label>
        <select id="transfer_newrealm" name="newrealm">
          <?php foreach ($DBS as $realm): ?>
            <option value="<?php echo (int)$realm['id']; ?>"><?php echo htmlspecialchars($realm['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="admin-transfer-actions">
        <input type="submit" name="move_char" value="<?php echo htmlspecialchars($transfer); ?> (WIP)"<?php if (!$transferEnabled) echo ' disabled="disabled"'; ?> />
        <input type="submit" name="clean_db" class="danger-submit" value="<?php echo htmlspecialchars($cleandb); ?> (Disabled)"<?php if (!$cleanDbEnabled) echo ' disabled="disabled"'; ?> />
      </div>
    </form>

    <?php
    if (check_online($DBS)) {
        echo '<div class="admin-transfer-msg error">' . htmlspecialchars($serveron_1) . '</div>';
    }

    if (isset($_POST['realm'])) {
        $db1 = $DBS[$_POST['realm']];
        $db2 = $DBS[$_POST['newrealm']];
    }

    if (isset($_POST['move_char']) && !$transferEnabled) {
        echo '<div class="admin-transfer-msg error">Character transfer is temporarily disabled while this tool is being finished.</div>';
    } elseif (isset($_POST['clean_db']) && !$cleanDbEnabled) {
        echo '<div class="admin-transfer-msg error">Realm cleanup is temporarily disabled until the workflow is reviewed in detail.</div>';
    } elseif (isset($_POST['clean_db'])) {
        $db1 = $DBS[$_POST['realm']];
        clean_after_delete($db1);
        echo '<div class="admin-transfer-msg success">' . htmlspecialchars($clearDBsuccess) . '</div>';
    }

    if ($transferEnabled && isset($_POST['move_char'])) {
        if (($_POST['name']) == '') {
            echo '<div class="admin-transfer-msg error">' . htmlspecialchars($mustentername) . '</div>';
        } else {
            $name = $_POST['name'];
            $newname = ucfirst(strtolower(trim($_POST['name'])));
            if ($newname != '') {
                $newname_exist = check_if_name_exist($newname, $db2);
                if ($newname_exist == 1) {
                    echo '<div class="admin-transfer-msg error">' . htmlspecialchars($alreadyexist . $newname . '!') . '</div>';
                    goto transfer_done;
                }
            }
            if ($newname != '' || check_if_name_exist($name, $db2) == 0) {
                $char_guid = select_char($name, $db1);
                if ($char_guid > 0) {
                    truncate_db($temp_db);
                    move($char_guid, $db1, $temp_db);
                    if ($newname != '' && $newname_exist != 1) {
                        change_name($name, $newname, $temp_db);
                    }
                    cleanup($temp_db);
                    foreach ($tab_guid_change as $value) {
                        $max_guid = select_max_guid($db2, $value[0], $value[1]);
                        change_guid($temp_db, $max_guid, $value[2], $value[0], $value[1]);
                        if ($value[0] == 'characters') $max_char_guid = $max_guid;
                    }
                    move($max_char_guid + 1, $temp_db, $db2);
                    truncate_db($temp_db);
                    del_char($char_guid, $db1);
                    echo '<div class="admin-transfer-msg success">' . htmlspecialchars($character . $name . $transfersuccess) . '</div>';
                    if ($newname != '' && $newname_exist != 1) {
                        echo '<div class="admin-transfer-msg success">' . htmlspecialchars($character . $name . $renamesuccess . $newname . '!') . '</div>';
                    }
                } else {
                    echo '<div class="admin-transfer-msg error">' . htmlspecialchars($character . $name . $doesntexist) . '</div>';
                }
            } else {
                echo '<div class="admin-transfer-msg error">' . htmlspecialchars($character . $name . $alreadytransfered) . '</div>';
            }
            transfer_done:;
        }
    }
    ?>
  </div>
</div>
<?php builddiv_end() ?>
