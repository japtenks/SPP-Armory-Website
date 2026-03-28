<?php
if (INCLUDED !== true) exit;

$pathway_info[] = array('title' => $lang['bot_commands'], 'link' => '');
$userlevel = (int)($user['gmlevel'] != '' ? $user['gmlevel'] : 0);

/* ---------- Load Commands ---------- */
$realmId   = spp_resolve_realm_id($realmDbMap);
$armoryPdo = spp_get_pdo('armory', $realmId);

$stmt = $armoryPdo->prepare("
  SELECT name, security, help, category, subcategory
  FROM bot_command
  WHERE security <= ?
  ORDER BY category, subcategory, name ASC
");
$stmt->execute([$userlevel]);

$botCommands = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
