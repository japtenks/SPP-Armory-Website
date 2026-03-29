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
  <?php
  $realmIds = array_keys($DBS);
  $selectedRealmId = isset($_POST['realm']) ? (int)$_POST['realm'] : (isset($realmIds[0]) ? (int)$realmIds[0] : 0);
  if ($selectedRealmId <= 0 || !isset($DBS[$selectedRealmId])) {
      $selectedRealmId = isset($realmIds[0]) ? (int)$realmIds[0] : 0;
  }

  $selectedAccountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
  $selectedCharacterGuid = isset($_POST['character_guid']) ? (int)$_POST['character_guid'] : 0;
  $accountOptions = array();
  $characterOptions = array();
  $selectedCharacterName = '';

  try {
      $stmtAccounts = $charcfgPdo->query("SELECT id, username FROM account ORDER BY username ASC, id ASC");
      $accountOptions = $stmtAccounts ? $stmtAccounts->fetchAll(PDO::FETCH_ASSOC) : array();
  } catch (Throwable $e) {
      $accountOptions = array();
  }

  if ($selectedAccountId <= 0 && !empty($accountOptions[0]['id'])) {
      $selectedAccountId = (int)$accountOptions[0]['id'];
  }

  if ($selectedRealmId > 0 && $selectedAccountId > 0 && isset($DBS[$selectedRealmId])) {
      $renamePdo = get_chartools_pdo($DBS[$selectedRealmId]);
      $stmtCharacters = $renamePdo->prepare("SELECT guid, name, level FROM characters WHERE account = ? ORDER BY name ASC, guid ASC");
      $stmtCharacters->execute([$selectedAccountId]);
      $characterOptions = $stmtCharacters->fetchAll(PDO::FETCH_ASSOC);
  }

  if ($selectedCharacterGuid <= 0 && !empty($characterOptions[0]['guid'])) {
      $selectedCharacterGuid = (int)$characterOptions[0]['guid'];
  }

  foreach ($characterOptions as $characterOption) {
      if ((int)$characterOption['guid'] === $selectedCharacterGuid) {
          $selectedCharacterName = (string)$characterOption['name'];
          break;
      }
  }
  ?>
  <div class="admin-tool-card">
    <p class="admin-tool-kicker">Character Tools</p>
    <h2 class="admin-tool-title">Rename Character</h2>
    <p class="admin-tool-copy">Rename a character directly from the admin panel. The character must exist on the selected realm and must be offline before the rename can be applied.</p>
  </div>

  <div class="admin-tool-card">
    <form action="index.php?n=admin&sub=chartools" method="post">
      <div class="admin-tool-form">
        <label for="rename_realm">Realm</label>
        <select id="rename_realm" name="realm" onchange="this.form.submit()">
          <?php foreach ($DBS as $realm): ?>
            <option value="<?php echo (int)$realm['id']; ?>"<?php if ((int)$realm['id'] === $selectedRealmId) echo ' selected'; ?>><?php echo htmlspecialchars($realm['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="rename_account_id">Account</label>
        <select id="rename_account_id" name="account_id" onchange="this.form.submit()">
          <?php if (!empty($accountOptions)) { ?>
            <?php foreach ($accountOptions as $accountOption) { ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === $selectedAccountId) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No accounts available</option>
          <?php } ?>
        </select>

        <label for="rename_character_guid">Character</label>
        <select id="rename_character_guid" name="character_guid">
          <?php if (!empty($characterOptions)) { ?>
            <?php foreach ($characterOptions as $characterOption) { ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === $selectedCharacterGuid) echo ' selected'; ?>>
                <?php
                echo htmlspecialchars((string)$characterOption['name']);
                if (!empty($characterOption['level'])) {
                    echo ' (Lvl ' . (int)$characterOption['level'] . ')';
                }
                ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No characters on this account</option>
          <?php } ?>
        </select>

        <label for="rename_new_name">New Character Name</label>
        <input type="text" id="rename_new_name" name="newname" maxlength="20" value="<?php echo htmlspecialchars((string)($_POST['newname'] ?? '')); ?>" />
      </div>

      <div class="admin-tool-actions">
        <input type="submit" name="rename" value="Rename Character" <?php if (empty($characterOptions)) echo 'disabled="disabled"'; ?> />
      </div>
    </form>

    <?php
    if ($selectedRealmId > 0 && isset($DBS[$selectedRealmId])) {
        $db1 = $DBS[$selectedRealmId];
        if (isset($_POST['rename'])) {
            if ($selectedAccountId <= 0 || $selectedCharacterGuid <= 0 || trim((string)($_POST['newname'] ?? '')) === '') {
                echo '<div class="admin-tool-msg error">' . htmlspecialchars($empty_field) . '</div>';
            } else {
                $newname = ucfirst(strtolower(trim($_POST['newname'])));
                $name = $selectedCharacterName;
                $status = check_if_online_by_guid($selectedCharacterGuid, $selectedAccountId, $db1);
                $newname_exist = check_if_name_exist($newname, $db1);
                if ($status == -1) {
                    echo '<div class="admin-tool-msg error">' . htmlspecialchars($character_1 . ($name ?: 'Unknown') . $doesntexist) . '</div>';
                } elseif ($newname_exist == 1) {
                    echo '<div class="admin-tool-msg error">' . htmlspecialchars($alreadyexist . $newname . '!') . '</div>';
                } elseif ($status == 1) {
                    $kickError = '';
                    force_character_offline((int)$selectedRealmId, $name, $kickError);
                    for ($i = 0; $i < 5; $i++) {
                        usleep(500000);
                        $status = check_if_online_by_guid($selectedCharacterGuid, $selectedAccountId, $db1);
                        if ($status !== 1) {
                            break;
                        }
                    }

                    if ($status == 1) {
                        $message = $character_1 . $name . $isonline;
                        if ($kickError !== '') {
                            $message .= ' SOAP: ' . $kickError;
                        }
                        echo '<div class="admin-tool-msg error">' . htmlspecialchars($message) . '</div>';
                    } else {
                        change_name_by_guid($selectedCharacterGuid, $selectedAccountId, $newname, $db1);
                        echo '<div class="admin-tool-msg success">' . htmlspecialchars($character_1 . $name . $renamesuccess . $newname . '!') . '</div>';
                    }
                } else {
                    change_name_by_guid($selectedCharacterGuid, $selectedAccountId, $newname, $db1);
                    echo '<div class="admin-tool-msg success">' . htmlspecialchars($character_1 . $name . $renamesuccess . $newname . '!') . '</div>';
                }
            }
        }
    }
    ?>
  </div>
</div>
<?php builddiv_end() ?>
