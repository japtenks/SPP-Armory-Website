<?php
function test_realm($server, $port)
{
	$s = @fsockopen("$server", $port, $ERROR_NO, $ERROR_STR, (float)0.5);
	if ($s) {
		@fclose($s);
		return true;
	} else
		return false;
}

function check_online($db)
{
	foreach ($db as $realm) {
		if (test_realm($realm['server'], $realm['port'])) {
			$online = true;
		}
	}
	if ($online) {
		return $online;
	}
}

function get_chartools_pdo($db)
{
	return spp_get_pdo('chars', $db['realm_id']);
}

function check_if_name_exist($name, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("SELECT COUNT(*) FROM `characters` WHERE `name` = ?");
	$stmt->execute([$name]);
	return (int)$stmt->fetchColumn() === 0 ? 0 : 1;
}

function select_char($char_name, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("SELECT `guid` FROM `characters` WHERE `name` = ?");
	$stmt->execute([$char_name]);
	$row  = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row ? $row['guid'] : null;
}

function move($char_guid, $fist_db, $second_db)
{
	include "tabs.php";
	$char_guid = (int)$char_guid;
	$src_db    = $fist_db['db'];
	$pdo       = get_chartools_pdo($second_db);
	foreach ($tab_characters as $value) {
		$pdo->exec("INSERT INTO `$value[0]` SELECT * FROM `$src_db`.`$value[0]` WHERE `$value[1]` = $char_guid");
	}
	$pdo->exec("INSERT INTO `pet_spell` SELECT * FROM `$src_db`.`pet_spell`
		WHERE `guid` IN (SELECT `id` FROM `$src_db`.`character_pet` WHERE `owner` = $char_guid)");
	$pdo->exec("INSERT INTO `item_text` SELECT * FROM `$src_db`.`item_text`
		WHERE `id` IN (SELECT `itemTextId` FROM `$src_db`.`mail` WHERE `receiver` = $char_guid)");
}

function cleanup($db)
{
	$pdo = get_chartools_pdo($db);
	$pdo->exec("DELETE FROM `mail_items` WHERE (mail_id) NOT IN (SELECT id FROM `mail`)");
}

function select_max_guid($db, $table, $field)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->query("SELECT MAX(`$field`) AS max_guid FROM `$table`");
	$row  = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row['max_guid'];
}

function change_guid($db, $max_guid, $tab, $table, $field)
{
	include "tabs.php";
	$pdo = get_chartools_pdo($db);
	$pdo->exec("ALTER TABLE `$table` ADD `guid_temp` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
	$pdo->exec("ALTER TABLE `$table` ADD `guid_new` INT(11) UNSIGNED NOT NULL FIRST");
	$pdo->exec("UPDATE `$table` SET `guid_new` = `guid_temp`");
	$pdo->exec("ALTER TABLE `$table` DROP `guid_temp`");
	if (!($max_guid > 0)) $max_guid = 0;
	$pdo->exec("UPDATE `$table` SET `guid_new` = `guid_new` + " . (int)$max_guid);
	if ($table === 'characters' || $table === 'item_instance') {
		$pdo->exec("UPDATE `$table` SET `data` = CONCAT(`guid_new`, ' ', RIGHT(`data`, LENGTH(`data`) - LENGTH(SUBSTRING_INDEX(`data`, ' ', 1)) - 1))");
	}
	foreach ($tab as $value) {
		if ($value[0] !== 'characters') {
			$pdo->exec("UPDATE `$value[0]`, `$table` SET `$value[0]`.`$value[1]` = `$table`.`guid_new` WHERE `$value[0]`.`$value[1]` = `$table`.`$field`");
		}
	}
	if ($table === 'characters') {
		$pdo->exec("UPDATE `mail` SET `sender` = `receiver`, `stationery` = '61'");
	}
	$pdo->exec("ALTER TABLE `$table` DROP `$field`");
	$pdo->exec("ALTER TABLE `$table` CHANGE `guid_new` `$field` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
}

function truncate_db($db)
{
	include "tabs.php";
	$pdo = get_chartools_pdo($db);
	foreach ($tab_characters as $value) {
		$pdo->exec("TRUNCATE `$value[0]`");
	}
	$pdo->exec("TRUNCATE `pet_spell`");
	$pdo->exec("TRUNCATE `item_text`");
}

function del_char($char_guid, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("DELETE FROM `characters` WHERE `guid` = ?");
	$stmt->execute([(int)$char_guid]);
}

function clean_after_delete($db)
{
	$pdo = get_chartools_pdo($db);
	set_time_limit(200);
	$file = fopen("clean_after_delete.sql", 'r');
	while (!feof($file)) {
		$getquery = trim(fgets($file));
		if ($getquery !== '') {
			$pdo->exec($getquery);
		}
	}
}
?>
