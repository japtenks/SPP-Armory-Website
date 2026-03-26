<?php if(INCLUDED!==true)exit;
$pathway_info[] = array('title'=>$lang['commands'],'link'=>'');
$userlevel = (int)($user['gmlevel'] != '' ? $user['gmlevel'] : 0);

$realmId   = spp_resolve_realm_id($realmDbMap);
$alltopics  = [];
$botCommands = [];

try {
    $worldPdo = spp_get_pdo('world', $realmId);
    $stmt = $worldPdo->prepare("SELECT * FROM command WHERE security <= ? ORDER BY name ASC");
    $stmt->execute([$userlevel]);
    $alltopics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* world DB unavailable or table missing */ }

try {
    $realmPdo = spp_get_pdo('realmd', $realmId);
    $stmt = $realmPdo->prepare("SELECT * FROM bot_command WHERE security <= ? ORDER BY name ASC");
    $stmt->execute([$userlevel]);
    $botCommands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* bot_command table may not exist in this realm */ }
?>