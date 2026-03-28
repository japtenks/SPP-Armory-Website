<?php
if(INCLUDED!==true)exit;
// ==================== //
$oldInactiveTime = 3600*24*7;
// ==================== //
if($_GET['id'] > 0){
    if(!$_GET['action']){
        $profile = $auth->getprofile($_GET['id']);
        $realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
        $allgroups = $realmPdo->query("SELECT g_id, g_title FROM website_account_groups")->fetchAll(PDO::FETCH_KEY_PAIR);

        $pathway_info[] = array('title'=>$lang['users_manage'],'link'=>$com_links['sub_members']);
        $pathway_info[] = array('title'=>$profile['username'],'link'=>'');

        $txt['yearlist'] = "\n";
            $txt['monthlist'] = "\n";
            $txt['daylist'] = "\n";
            for($i=1;$i<=31;$i++){
                $txt['daylist'] .= "<option value='$i'".($i==$profile['bd_day']?' selected':'')."> $i </option>\n";
            }
            for($i=1;$i<=12;$i++){
                $txt['monthlist'] .= "<option value='$i'".($i==$profile['bd_month']?' selected':'')."> $i </option>\n";
            }
            for($i=1950;$i<=date('Y');$i++){
                $txt['yearlist'] .= "<option value='$i'".($i==$profile['bd_year']?' selected':'')."> $i </option>\n";
            }
            $profile['signature'] = str_replace('<br />','',$profile['signature']);
    }
}else{
    $pathway_info[] = array('title'=>$lang['userlist'],'link'=>'');
	//===== Filter ==========//
    $filterParams = [];
    $filters = array(
        "LOWER(account.`username`) NOT LIKE 'rndbot%'",
        "(website_accounts.hideprofile IS NULL OR website_accounts.hideprofile = 0)"
    );
     if($_GET['char'] && preg_match("/[a-z]/",$_GET['char'])){
        $filters[] = "account.`username` LIKE ?";
        $filterParams[] = $_GET['char'] . '%';
     }elseif($_GET['char']==1){
        $filters[] = "account.`username` REGEXP '^[^A-Za-z]'";
    }
    $filter = 'WHERE '.implode(' AND ', $filters);
	//===== Calc pages =====//
    $items_per_pages = (int)$MW->getConfig->generic->users_per_page;
    $realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

    $stmtCount = $realmPdo->prepare("
        SELECT count(*)
        FROM account
        LEFT JOIN website_accounts ON account.id=website_accounts.account_id
        $filter");
    $stmtCount->execute($filterParams);
    $itemnum = $stmtCount->fetchColumn();

    $pnum = ceil($itemnum/$items_per_pages);
    $pages_str = default_paginate($pnum, $p, "index.php?n=account&sub=userlist&char=".$_GET['char']);
    $limit_start = (int)(($p-1)*$items_per_pages);
    $items_per_pages = (int)$items_per_pages;

    $stmtItems = $realmPdo->prepare("
        SELECT * FROM account
        LEFT JOIN website_accounts ON account.id=website_accounts.account_id
        $filter
        ORDER BY username
        LIMIT $limit_start,$items_per_pages");
    $stmtItems->execute($filterParams);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}
##   output_message('alert',$itemnum);
?>
