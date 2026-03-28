<?php
if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>$lang['activation'],'link'=>'');
// ==================== //

$activatePdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

$stmtLock = $activatePdo->prepare("SELECT locked FROM account WHERE id=?");
$stmtLock->execute([(int)$user['id']]);
$lock = (int)$stmtLock->fetchColumn();

if($user['id']>0 && $lock == 0){
    redirect('index.php?n=account&sub=manage',1);
}

$key = $_REQUEST['key'];
if($key){
  if($user['id'] = $auth->isvalidactkey($key)){
    $stmtUl = $activatePdo->prepare("UPDATE account SET locked=0 WHERE id=? LIMIT 1");
    $stmtUl->execute([(int)$user['id']]);
    $stmtUac = $activatePdo->prepare("UPDATE website_accounts SET activation_code=NULL WHERE account_id=? LIMIT 1");
    $stmtUac->execute([(int)$user['id']]);
    if($realmd['req_reg_invite'] > 0 && $realmd['req_reg_invite'] < 10){
        $keys_arr = $auth->generate_keys($realmd['req_reg_invite']);
        $email_text  = '';
        $stmtIk = $activatePdo->prepare('INSERT INTO site_regkeys (`key`,`used`) VALUES(?,1)');
        foreach ($keys_arr as $invkey){
            $stmtIk->execute([$invkey]);
            $email_text .= ' - '.$invkey."\n";
        }
        $email_text = sprintf($lang['emailtext_inv_keys'],$email_text);
        $accinfo = $auth->getprofile($act_accid);
        send_email($accinfo['email'],$accinfo['username'],'== '.(string)$MW->getConfig->generic->site_title.' invitation keys ==',$email_text);
        output_message('notice',sprintf($lang['email_sent_keys'],(int)$MW->getConfig->generic->req_reg_invite));
    }
    output_message('notice','<b>'.$lang['act_succ'].'.</b>');
  }else{
    $stmtLock2 = $activatePdo->prepare("SELECT locked FROM account WHERE id=?");
    $stmtLock2->execute([(int)$user['id']]);
    $lock = (int)$stmtLock2->fetchColumn();

    if($lock == 1)
    {
        output_message('alert',$lang['bad_act_key']);
        redirect('index.php?n=account&sub=activate',0,2);
    }
  }
}

?>
