<?php
if(INCLUDED!==true)exit;
require_once __DIR__ . '/account.helpers.php';
require_once __DIR__ . '/account.view.read.php';
// ==================== //
$pathway_info[] = array('title'=>$lang['view_profile'],'link'=>'');
// ==================== //
if($user['id']<=0){
  redirect('index.php?n=account&sub=login',1);
}else{
  if($_GET['action']=='find' && $_GET['name']){
    $uid = $auth->getid($_GET['name']);
    $profile = $auth->getprofile($uid);
    
        if($profile['hideprofile']==1){
            unset($profile);
            $pathway_info[] = array('title'=>$lang['forbiden'],'link'=>'');
        }else{
            $profile = spp_account_view_build_profile($profile, (int)$uid, $user, $realmDbMap);
            $pathway_info[] = array('title'=>$profile['username'],'link'=>'');
        }
  }
}
?>
