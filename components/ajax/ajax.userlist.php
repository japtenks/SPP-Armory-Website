<?php
if(INCLUDED!==true)exit;

$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$stmt = $realmPdo->query('SELECT id, username FROM account ORDER BY username');
$q = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$res = "<select onchange=\"selectClick(this.value)\" style=\"margin:1px;font-size:1.2em;\"> \n <option value=''>$lang[select_name]</option> \n";
foreach($q as $uid => $uname){
  if($_REQUEST['insid']) $res .= "<option value=\"".$uid."\">$uname</option> \n";
  else $res .= "<option value=\"".htmlspecialchars($uname)."\">$uname</option> \n";
}
$res .= "</select>";
echo $res;
?>
