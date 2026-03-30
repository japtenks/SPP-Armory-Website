<?php
if(INCLUDED!==true)exit;

if (!function_exists('spp_admin_keys_csrf_token')) {
    function spp_admin_keys_csrf_token($formName = 'admin_keys') {
        if (!isset($_SESSION['spp_csrf_tokens']) || !is_array($_SESSION['spp_csrf_tokens'])) {
            $_SESSION['spp_csrf_tokens'] = array();
        }
        if (empty($_SESSION['spp_csrf_tokens'][$formName])) {
            $_SESSION['spp_csrf_tokens'][$formName] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['spp_csrf_tokens'][$formName];
    }
}

if (!function_exists('spp_admin_keys_require_csrf')) {
    function spp_admin_keys_require_csrf($formName = 'admin_keys') {
        $submittedToken = (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens'][$formName] ?? '');
        if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            output_message('alert', 'Security check failed. Please refresh the page and try again.');
            exit;
        }
    }
}

if (!function_exists('spp_admin_keys_action_url')) {
    function spp_admin_keys_action_url(array $params) {
        $params['csrf_token'] = spp_admin_keys_csrf_token();
        return 'index.php?' . http_build_query($params);
    }
}
// ==================== //
$pathway_info[] = array('title'=>$lang['regkeys_manage'],'link'=>'');
// ==================== //

$keysPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

if(!$_GET['action']){
    $allkeys = $keysPdo->query("SELECT * FROM site_regkeys")->fetchAll(PDO::FETCH_ASSOC);
    $num_keys = count($allkeys);
}elseif($_GET['action']=='create'){
    spp_admin_keys_require_csrf();
    if($_POST['num']<300){
        $keys_arr = $auth->generate_keys($_POST['num']);
        $stmtIk = $keysPdo->prepare('INSERT INTO site_regkeys (`key`) VALUES(?)');
        foreach ($keys_arr as $key) {
            $stmtIk->execute([$key]);
        }
    }
    redirect('index.php?n=admin&sub=keys',1);
}elseif($_GET['action']=='delete'){
    spp_admin_keys_require_csrf();
    if($_POST['keyid'] || $_GET['keyid']){
        $_GET['keyid']?$keyid=$_GET['keyid']:$keyid=$_POST['keyid'];
        $stmtDk = $keysPdo->prepare("DELETE FROM site_regkeys WHERE `id`=?");
        $stmtDk->execute([(int)$keyid]);
    }elseif($_POST['keyname']){
        $stmtDkn = $keysPdo->prepare("DELETE FROM site_regkeys WHERE `key`=?");
        $stmtDkn->execute([$_POST['keyname']]);
    }
    redirect('index.php?n=admin&sub=keys',1);
}elseif($_GET['action']=='setused'){
    spp_admin_keys_require_csrf();
    $stmtSu = $keysPdo->prepare("UPDATE site_regkeys SET used=1 WHERE `id`=?");
    $stmtSu->execute([(int)$_GET['keyid']]);
    redirect('index.php?n=admin&sub=keys',1);
}elseif($_GET['action']=='deleteall'){
    spp_admin_keys_require_csrf();
    $keysPdo->exec("TRUNCATE TABLE site_regkeys");
    redirect('index.php?n=admin&sub=keys',1);
}
?>
