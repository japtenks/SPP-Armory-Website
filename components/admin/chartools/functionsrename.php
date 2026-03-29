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

function check_if_online_by_guid($guid, $accountId, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("SELECT `online` FROM `characters` WHERE `guid` = ? AND `account` = ?");
	$stmt->execute([(int)$guid, (int)$accountId]);
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

function change_name_by_guid($guid, $accountId, $newname, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("UPDATE `characters` SET `name` = ? WHERE `guid` = ? AND `account` = ?");
	$stmt->execute([$newname, (int)$guid, (int)$accountId]);
}

function force_character_offline($realmId, $characterName, &$errorMessage = '')
{
	$characterName = trim((string)$characterName);
	if ($characterName === '') {
		$errorMessage = 'Missing character name.';
		return false;
	}
	if (!function_exists('spp_mangos_soap_execute_command')) {
		$errorMessage = 'SOAP helper is unavailable.';
		return false;
	}

	$soapError = '';
	$soapResult = spp_mangos_soap_execute_command((int)$realmId, 'kick ' . $characterName, $soapError);
	if ($soapResult === false) {
		$errorMessage = $soapError !== '' ? $soapError : 'Kick command failed.';
		return false;
	}

	$errorMessage = '';
	return true;
}
?>
