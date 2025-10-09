<?php
if (INCLUDED !== true) exit;

/* -----------------------------
   ACCOUNT CREATION HANDLER
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $DB, $auth, $MW;

    $username = strtoupper(trim($_POST['username']));
    $password = strtoupper(trim($_POST['password']));
    $verify   = strtoupper(trim($_POST['verify']));

    if ($password !== $verify) {
        $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>⚠ Passwords do not match.</div>";
    } elseif (strlen($username) < 3 || strlen($password) < 3) {
        $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>⚠ Username and password must be at least 3 characters long.</div>";
    } else {
        // --- check if username already exists ---
        $exists = $DB->selectCell("SELECT id FROM account WHERE LOWER(username)=LOWER(?)", $username);
        if ($exists) {
            $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>⚠ Username already exists. Please choose another.</div>";
        } else {
            // --- attempt to register using the core AUTH class (SRP6 handled internally) ---
            $result = $auth->register(
                array(
                    'username'      => strtoupper($username),
                    'sha_pass_hash' => sha_password($username, $password),
                    'expansion'     => 2,
                    'password'      => $password
                ),
                array()
            );

            if ($result === true) {
                // Auto-login if activation not required
                if ((int)$MW->getConfig->generic->req_reg_act == 0) {
                    $auth->login(array('username' => $username, 'password' => $password));
                }

                $message = "<div style='color:lime;font-weight:bold;margin-bottom:8px;'>✅ Account <b>$username</b> created successfully!</div>";
            } else {
                // $auth->register() may return false or an array with error info
                $errorDetail = is_array($result) ? implode('<br>', array_map('htmlspecialchars', $result)) : 'Unknown error';
                $message = "<div style='color:#ff5555;font-weight:bold;margin-bottom:8px;'>⚠ Account creation failed.<br><small>$errorDetail</small></div>";
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
        <img src="templates/offlike/images/headers/title_acc_man.gif" alt="Account Management" class="header-title">
      </td>
    </tr>
    <tr>
      <td colspan="3" class="header-bottom">
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
.header-bg { height: 180px; background: url('templates/offlike/images/headers/account_bg.png') repeat-x center; text-align: center; position: relative; }
.header-title { position: relative; top: 45px; max-width: 380px; }
.header-bottomimg { width: 100%; height: 16px; display: block; }

/* === Register Panel === */
.register-panel {
  background: #1a1a1a;
  border: 1px solid #333;
  border-radius: 8px;
  padding: 24px;
  max-width: 760px;
  margin: 0 auto;
  box-shadow: 0 0 10px rgba(0,0,0,0.5);
}
.register-panel h3 { text-align: center; margin-bottom: 18px; color: #9cf; }
.form-flex { display: flex; align-items: center; justify-content: center; gap: 24px; }
.form-flex img { max-width: 180px; border-radius: 8px; border: 2px solid #333; }
.register-form { flex: 1; }
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

</style>

<?php builddiv_start(1, $lang['account_create']); ?>
<div class="modern-content register-panel">
<?php if (!empty($message)) echo $message; ?>
<?php header_image_account(); ?>

<h3><?php echo $lang['create_account']; ?></h3>

<div class="form-flex">
  <img src="templates/tbc/images/orc2.jpg" alt="Orc Warrior">
  <form method="post" action="index.php?n=account&sub=register" class="register-form">
    <input type="hidden" name="step" value="5">

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
