<?php
if(INCLUDED!==true)exit;

$pathway_info[] = array('title'=>$lang['commands'],'link'=>'');
$userlevel = ($user['gmlevel'] != '' ? $user['gmlevel'] : 0);


if ($DB) {
    $botCommands = $DB->select("
        SELECT *
        FROM bot_command
        WHERE security <= $userlevel
        ORDER BY `name` ASC
    ");
}
if (!isset($DB)) {
  echo "<div style='color:red;padding:8px;'>[ERROR] No DB connection.</div>";
} else {
  $test = $DB->select("SELECT name, security, help FROM tbcarmory.bot_command ORDER BY name ASC");
  echo "<pre style='color:lime;background:#111;padding:8px;'>".print_r($test, true)."</pre>";
}


?>

