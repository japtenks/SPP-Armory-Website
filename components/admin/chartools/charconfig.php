<?php
$charcfgPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
foreach ($charcfgPdo->query("SELECT `id`, `name`, `port`, `address` FROM `realmlist`")->fetchAll(PDO::FETCH_ASSOC) as $realm) {
	$ID  = (int)$realm['id'];
	$cfg = spp_get_db_config('chars', $ID);
	if (!$cfg) continue;

	$DBS[$ID] = [
		'realm_id' => $ID,
		'id'       => $ID,
		'name'     => $realm['name'],
		'port'     => $realm['port'],
		'server'   => $realm['address'],
		'db'       => $cfg['name'],
	];
}

$temp_db = "characters_temp";
?>
