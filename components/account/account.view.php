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
  if($_GET['action']=='find'){
    $requestedName = trim((string)($_GET['name'] ?? ''));
    if($requestedName === ''){
        output_message('alert', 'That profile link is missing a username.');
        $pathway_info[] = array('title' => $lang['users'] ?? 'Users', 'link' => '');
    } else {
        $uid = (int)$auth->getid($requestedName);
        $profile = $uid > 0 ? $auth->getprofile($uid) : null;

        if(!is_array($profile) || empty($profile)){
            output_message('alert', 'That member profile could not be found.');
            $pathway_info[] = array('title' => $lang['users'] ?? 'Users', 'link' => '');
        } elseif((int)($profile['hideprofile'] ?? 0) === 1){
            unset($profile);
            $pathway_info[] = array('title'=>$lang['forbiden'],'link'=>'');
        }else{
            $profile = spp_account_view_build_profile($profile, (int)$uid, $user, $realmDbMap);
            $pathway_info[] = array('title'=>$profile['username'],'link'=>'');
        }
    }
  }
}
?>
