<?php
if(INCLUDED!==true)exit;
require_once __DIR__ . '/account.helpers.php';
// ==================== //
$pathway_info[] = array('title'=>$lang['login'],'link'=>'');
// ==================== //

$login_message = '';
$login_message_class = '';
$login_form_username = trim((string)($_REQUEST['login'] ?? ''));

if($_REQUEST['action']=='login'){
  $login = $_REQUEST['login'];
  $pass = $_REQUEST['pass'];
  $returnTo = spp_account_login_redirect_target(
    $_REQUEST['returnto'] ?? '',
    'index.php?n=forum'
  );
  if($auth->login(array('username'=>$login,'password'=>$pass)))
  {
    redirect($returnTo,1);
  } else {
    if ($login === '' || $pass === '') {
      $login_message = 'Please enter both your username and password.';
    } else {
      $login_message = 'Login failed. Check your username and password and try again.';
    }
    $login_message_class = ' is-error';
  }
}elseif($_REQUEST['action']=='logout'){
  $auth->logout();
  $returnTo = spp_account_login_redirect_target(
    $_REQUEST['returnto'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
    'index.php'
  );
  redirect($returnTo,1);
}
?>
