<?php
if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>$lang['regkeys_manage'],'link'=>'');
// ==================== //

$keysPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

if(!$_GET['action']){
    $allkeys = $keysPdo->query("SELECT * FROM site_regkeys")->fetchAll(PDO::FETCH_ASSOC);
    $num_keys = count($allkeys);
}elseif($_GET['action']=='create'){
    if($_POST['num']<300){
        $keys_arr = $auth->generate_keys($_POST['num']);
        $stmtIk = $keysPdo->prepare('INSERT INTO site_regkeys (`key`) VALUES(?)');
        foreach ($keys_arr as $key) {
            $stmtIk->execute([$key]);
        }
    }
    redirect('index.php?n=admin&sub=keys',1);
}elseif($_GET['action']=='delete'){
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
    $stmtSu = $keysPdo->prepare("UPDATE site_regkeys SET used=1 WHERE `id`=?");
    $stmtSu->execute([(int)$_GET['keyid']]);
    redirect('index.php?n=admin&sub=keys',1);
}elseif($_GET['action']=='deleteall'){
    $keysPdo->exec("TRUNCATE TABLE site_regkeys");
    redirect('index.php?n=admin&sub=keys',1);
}
?>
