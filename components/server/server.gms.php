<?php
if(INCLUDED!==true)exit;

$pathway_info[] = array('title'=>'GM list','link'=>'');

$gmlevel_w = array('Users','Moderators','Game Masters','Administrators');

$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$result = $realmPdo->query("
    SELECT username, gmlevel
    FROM account
    WHERE gmlevel>0
    ORDER BY gmlevel,username
")->fetchAll(PDO::FETCH_ASSOC);
$gm_groups = array();
foreach($result as $r){
    $gm_groups[$r['gmlevel']][] = $r['username'];
}
$gm_groups = array_reverse($gm_groups,true);
unset($result);
?>
