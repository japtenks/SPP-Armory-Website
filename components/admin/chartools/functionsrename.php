<?php
function check_if_online($name, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("SELECT `online` FROM `characters` WHERE `name` = ?");
	$stmt->execute([$name]);
	$row  = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row) return -1;
	return (int)$row['online'] === 1 ? 1 : 0;
}

function change_name($name, $newname, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("UPDATE `characters` SET `name` = ? WHERE `name` = ?");
	$stmt->execute([$newname, $name]);
}
?>
