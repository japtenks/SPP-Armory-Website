<?php
if (INCLUDED !== true) exit;

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$registerRealmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$registerExpansion = 2;
$registerRealmlistHost = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
if ($registerRealmlistHost === '') {
    $registerRealmlistHost = (string)($_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
}

if (function_exists('spp_get_pdo')) {
    try {
        $realmdPdo = spp_get_pdo('realmd', $registerRealmId);
        $realmRow = $realmdPdo->prepare('SELECT `address` FROM `realmlist` WHERE `id` = ? LIMIT 1');
        $realmRow->execute([(int)$registerRealmId]);
        $realmInfo = $realmRow->fetch(PDO::FETCH_ASSOC);
        if (!empty($realmInfo['address'])) {
            $registerRealmlistHost = (string)$realmInfo['address'];
        }
    } catch (Throwable $e) {
        // Fall back to current host if the realm DB cannot be queried here.
    }
}

/* -----------------------------
   ACCOUNT CREATION HANDLER
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $DB, $auth, $MW;

    $rawUsername = trim($_POST['username']);
    $username = strtoupper($rawUsername);
    $password = trim($_POST['password']);
    $verify   = trim($_POST['verify']);

    if ($password !== $verify) {
        $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>Password fields do not match.</div>";
    } elseif (strlen($username) < 3 || strlen($password) < 3) {
        $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>Username and password must be at least 3 characters long.</div>";
    } else {
        $realmdPdo2 = spp_get_pdo('realmd', $registerRealmId);
        $stmtEx = $realmdPdo2->prepare("SELECT id FROM account WHERE LOWER(username)=LOWER(?)");
        $stmtEx->execute([$username]);
        $exists = $stmtEx->fetchColumn();

        if ($exists) {
            $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>Username already exists. Please choose another.</div>";
        } else {
            $result = $auth->register(
                array(
                    'username'  => $username,
                    'password'  => $password,
                    'expansion' => $registerExpansion
                ),
                false
            );

            if ($result === true) {
                if ((int)$MW->getConfig->generic->req_reg_act == 0) {
                    $auth->login(array('username' => $username, 'password' => $password));
                }

                $message = '<strong>Account <b>' . htmlspecialchars($username) . '</b> created successfully.</strong>'
                    . '<br>Next step: open your game client and log in to create your first character.'
                    . '<br>Set your realmlist to: <code>set realmlist ' . htmlspecialchars($registerRealmlistHost) . '</code>';
            } else {
                $errorDetail = is_array($result)
                    ? implode('<br>', array_map('htmlspecialchars', $result))
                    : 'Unknown error';

                $message = '<strong>Account creation failed.</strong><br><small>' . $errorDetail . '</small>';
            }
        }
    }
}


/* -----------------------------
   HEADER IMAGE
----------------------------- */
function header_image_account() {
?>
<table class="header-account" cellspacing="0" cellpadding="0" border="0" width="100%">
  <tbody>
    <tr>
      <td class="header-bg">
        <img src="templates/offlike/images/headers/title_acc_man.gif" alt="Account Management" class="account-header-title">
      </td>
    </tr>
    <tr>
      <td class="header-bottom">
        <img src="templates/offlike/images/headers/bottom.gif" alt="Bottom border" class="header-bottomimg">
      </td>
    </tr>
  </tbody>
</table>
<?php
}
?>

<style>
/* === Account Header Styling === */
.header-account { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
.header-account td { padding: 0; }
.header-bg { height: 180px; background: url('templates/offlike/images/headers/account_bg.jpg') repeat-x center; text-align: center; position: relative; }
.account-header-title { position: relative; top: 45px; max-width: 380px; }
.header-bottomimg { width: 100%; height: 16px; display: block; }

/* === Register Panel === */
.register-panel {
  width: min(100%, 1080px);
  margin: 0 auto;
}
.register-message {
  margin: 0 0 14px;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 196, 0, 0.18);
  background: rgba(6, 10, 18, 0.88);
  line-height: 1.45;
}
.register-message.is-success {
  color: #8ef7a7;
  border-color: rgba(80, 220, 120, 0.4);
  box-shadow: inset 0 0 0 1px rgba(80, 220, 120, 0.12);
}
.register-message code {
  display: inline-block;
  margin-top: 4px;
  padding: 3px 8px;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.35);
  color: #fff3c2;
}
.register-message-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 12px;
}
.register-message-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 0 14px;
  border-radius: 8px;
  border: 1px solid rgba(255, 196, 0, 0.22);
  background: rgba(22, 34, 58, 0.88);
  color: #e7f0ff;
  text-decoration: none;
  font-weight: 700;
}
.register-message-link:hover {
  background: rgba(40, 64, 102, 0.95);
}
.register-message.is-error {
  color: #ff9d9d;
  border-color: rgba(255, 90, 90, 0.35);
  box-shadow: inset 0 0 0 1px rgba(255, 90, 90, 0.1);
}
.form-flex {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  padding: 18px 24px 24px;
  border: 1px solid #333;
  border-top: 0;
  border-radius: 0 0 8px 8px;
  background: #111318;
}
.form-flex img { max-width: 180px; border-radius: 8px; border: 2px solid #333; }
.register-form { flex: 1; max-width: 760px; }
.form-group { display: flex; align-items: center; margin: 10px 0; }
.form-group label { flex: 0 0 160px; text-align: right; color: #ccc; font-weight: bold; margin-right: 10px; }
.form-group input {
  flex: 1;
  background: #111;
  border: 1px solid #333;
  color: #eee;
  border-radius: 4px;
  padding: 8px;
  font-size: 0.9rem;
}
.form-group input:focus { border-color: #2c6ac8; outline: none; }
.form-actions { text-align: center; margin-top: 16px; }
.btn-primary {
  background: linear-gradient(#2c6ac8, #1b4e94);
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 8px 20px;
  font-size: 1rem;
  cursor: pointer;
  transition: 0.2s;
}
.btn-primary:hover { background: linear-gradient(#3b7cff, #295fb5); }
.account-note { margin-top: 16px; font-size: 0.9rem; color: #bbb; text-align: left; line-height: 1.4; }
@media (max-width: 820px) {
  .form-flex {
    flex-direction: column;
    align-items: stretch;
  }
  .form-flex img {
    margin: 0 auto;
  }
  .form-group {
    flex-direction: column;
    align-items: stretch;
  }
  .form-group label {
    flex: none;
    margin: 0 0 6px;
    text-align: left;
  }
}

</style>

<?php
$registerMessageClass = '';
if (!empty($message)) {
  $registerMessageClass = (stripos($message, 'created successfully') !== false)
    ? ' is-success'
    : ' is-error';
}
builddiv_start(1, 'Create Account');
?>
<div class="register-panel">
<?php if (!empty($message)): ?>
  <div class="register-message<?php echo $registerMessageClass; ?>">
    <?php echo $message; ?>
    <?php if ($registerMessageClass === ' is-success'): ?>
      <div class="register-message-actions">
        <a class="register-message-link" href="download-realmlist.php?realm=<?php echo (int)$registerRealmId; ?>">Download `realmlist.wtf`</a>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php header_image_account(); ?>

<div class="form-flex">
  <img src="templates/offlike/images/orc2.jpg" alt="Orc Warrior">
  <form method="post" action="index.php?n=account&sub=register" class="register-form">


    <div class="form-group">
      <label for="username">Username:</label>
      <input type="text" id="username" name="username" maxlength="16" required>
    </div>

    <div class="form-group">
      <label for="password">Password:</label>
      <input type="password" id="password" name="password" maxlength="16" required>
    </div>

    <div class="form-group">
      <label for="verify">Confirm Password:</label>
      <input type="password" id="verify" name="verify" maxlength="16" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Create Account</button>
    </div>
  </form>
</div>

<div class="account-note">
  You will be asked for this Account Name and Password each time you log in to play the game.
  Keep them safe and private. If you ever forget your credentials, contact the administrator directly.<br><br>
  Your Account Name is <b>not</b> your Character Name—you’ll choose a Character Name in-game after logging in.
</div>

</div>
<?php builddiv_end(); ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".register-form");
  const pass1 = form.querySelector("#password");
  const pass2 = form.querySelector("#verify");

  function validatePasswords() {
    if (pass1.value && pass2.value && pass1.value !== pass2.value) {
      pass2.setCustomValidity("Passwords do not match");
    } else {
      pass2.setCustomValidity("");
    }
  }

  pass1.addEventListener("input", validatePasswords);
  pass2.addEventListener("input", validatePasswords);
});
</script>
