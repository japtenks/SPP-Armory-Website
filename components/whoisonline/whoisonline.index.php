<?php
if(INCLUDED!==true)exit;

// ==================== //
 $pathway_info[] = array('title'=>$lang['whoisonline'],'link'=>'');
// ==================== //
$items = array();
$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$result = $realmPdo->query("SELECT * FROM `online` ORDER BY `user_name`")->fetchAll(PDO::FETCH_ASSOC);
foreach($result as $result_row )
{
  parse_str(parse_url($result_row['currenturl'], PHP_URL_QUERY), $tmpurl_arr);
  if(!$result_row['user_name']) $result_row['user_name'] = 'Guest';
  $result_row['currenturl_name'] = substr($result_row['currenturl'],0,50);
  if($tmpurl_arr['n']=='admin'){
    $result_row['currenturl'] = '#';
    $result_row['currenturl_name'] = 'Admin panel';
  }

  $items[] = $result_row;
}
?>
