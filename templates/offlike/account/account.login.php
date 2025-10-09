<style>
/* === Account Header Styling === */
.header-account {
  border-collapse: collapse;
  width: 100%;
  margin-bottom: 10px;
}
.header-account td { padding: 0; }
.header-left, .header-right { width: 140px; vertical-align: bottom; }
.header-bg {
  height: 180px;
  background: url('templates/offlike/images/headers/account_bg.png') repeat-x center;
  text-align: center;
  position: relative;
}
.header-title {
  position: relative;
  top: 45px;
  max-width: 380px;
}
.header-bottomimg { width: 100%; height: 16px; display: block; }

/* === Modern Login Panel === */
.login-panel {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  padding: 24px 16px;
  background: #1a1a1a;
  border: 1px solid #333;
  border-radius: 8px;
  max-width: 760px;
  margin: 0 auto;
  box-shadow: 0 0 10px rgba(0,0,0,0.5);
}

.login-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.login-field {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.login-field label {
  color: #ccc;
  margin-bottom: 4px;
  font-size: 0.9rem;
}

.login-field input {
  background: #111;
  border: 1px solid #333;
  color: #eee;
  border-radius: 4px;
  padding: 8px;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}
.login-field input:focus {
  border-color: #2c6ac8;
  outline: none;
}

.login-actions {
  text-align: center;
}

.btn-primary {
  background: #2c6ac8;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 8px 16px;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.2s;
}
.btn-primary:hover { background: #3d7cff; }

.login-welcome {
  text-align: center;
  color: #ddd;
  padding: 20px;
}
.login-welcome h3 {
  color: #9cf;
  margin-bottom: 8px;
}


</style>

<style>
.form-flex {
  display: flex;
  align-items: top;
  justify-content: center;
  height:180px;
  gap: 24px;
}
.form-flex img {
  
  border-radius: 8px;
  border: 2px solid #333;
}
.form-group {
  display: flex;
  align-items: center;
  margin: 10px 0;
}
.form-group label {
  flex: 0 0 160px;
  text-align: right;
  color: #ccc;
  font-weight: bold;
  margin-right: 10px;
}
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

.account-note {
  margin-top: 16px;
  font-size: 0.9rem;
  color: #bbb;
  text-align: left;
  line-height: 1.4;
}
</style>

<?php
function header_image_account() {
?>
<table class="header-account" cellspacing="0" cellpadding="0" border="0" width="100%">
  <tbody>
    <tr>
      <td class="header-bg">
        <img src="templates/offlike/images/headers/title_acc_man.gif" alt="Account Management" class="header-title">
      </td>

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





<?php builddiv_start(1, $lang['login']); ?>

<div class="modern-content login-panel">
<?php header_image_account(); ?>
<?php if ($user['id'] <= 0): ?>
<div class="form-flex">
  <img src="templates/tbc/images/twoheaded-ogre.jpg" alt="Orc Warrior">
  <form method="post" action="index.php?n=account&sub=login" class="login-form">
    <input type="hidden" name="action" value="login">

    <div class="login-field">
      <label for="login"><b><?php echo $lang['username']; ?></b></label>
      <input type="text" id="login" name="login" placeholder="<?php echo $lang['username']; ?>" required>
    </div>

    <div class="login-field">
      <label for="pass"><b><?php echo $lang['pass']; ?></b></label>
      <input type="password" id="pass" name="pass" placeholder="<?php echo $lang['pass']; ?>" required>
    </div>

    <div class="login-actions">
      <input type="submit" value="<?php echo $lang['login']; ?>" class="btn-primary">
    </div>
  </form>

<?php else: ?>
  <div class="login-welcome">
    <h3><?php echo $lang['now_logged_in']; ?></h3>
    <p><strong>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</strong></p>
  </div>
<?php endif; ?>

</div>
</div>
<?php builddiv_end(); ?>
