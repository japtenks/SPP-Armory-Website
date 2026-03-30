<?php

function chartools_check_guild_by_guid($guid, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("SELECT `guildid` FROM `guild_member` WHERE guid=?");
	$stmt->execute([(int)$guid]);
	return $stmt->fetch() ? 1 : 0;
}

function chartools_is_alliance($race)
{
	return $race == 1 || $race == 3 || $race == 4 || $race == 7 || $race == 11;
}

function chartools_race_class_allowed($race, $class)
{
	switch ((int)$race) {
		case 1: return in_array((int)$class, array(1, 2, 4, 5, 6, 8, 9), true);
		case 2: return in_array((int)$class, array(1, 3, 4, 6, 7, 9), true);
		case 3: return in_array((int)$class, array(1, 2, 3, 4, 5, 6), true);
		case 4: return in_array((int)$class, array(1, 3, 4, 5, 6, 11), true);
		case 5: return in_array((int)$class, array(1, 4, 5, 6, 8, 9), true);
		case 6: return in_array((int)$class, array(1, 3, 6, 7, 11), true);
		case 7: return in_array((int)$class, array(1, 4, 6, 8, 9), true);
		case 8: return in_array((int)$class, array(1, 3, 4, 5, 6, 7, 8), true);
		case 10: return in_array((int)$class, array(2, 3, 4, 5, 6, 8, 9), true);
		case 11: return in_array((int)$class, array(1, 2, 3, 5, 6, 7, 8), true);
	}
	return false;
}

function chartools_race_rep($race)
{
	switch ((int)$race) {
		case 1: return 72;
		case 2: return 76;
		case 3: return 47;
		case 4: return 69;
		case 5: return 68;
		case 6: return 81;
		case 7: return 54;
		case 8: return 530;
		case 10: return 911;
		case 11: return 930;
	}
	return 0;
}

function chartools_delete_mounts($guid, $race, $db)
{
	$pdo = get_chartools_pdo($db);
	$guid = (int)$guid;
	switch ((int)$race) {
		case 1:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=472 or spell=6648 or spell=458 or spell=470 or spell=23229 or spell=23228 or spell=23227 or spell=63232 or spell=65640)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=472 or spell=6648 or spell=458 or spell=470 or spell=23229 or spell=23228 or spell=23227 or spell=63232 or spell=65640)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=2414 or item_template=5655 or item_template=5656 or item_template=2411 or item_template=18777 or item_template=18778 or item_template=18776 or item_template=45125 or item_template=46752)");
			break;
		case 2:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=580 or spell=6653 or spell=6654 or spell=64658 or spell=23250 or spell=23252 or spell=23251 or spell=63640 or spell=65646)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=580 or spell=6653 or spell=6654 or spell=64658 or spell=23250 or spell=23252 or spell=23251 or spell=63640 or spell=65646)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=1132 or item_template=5665 or item_template=5668 or item_template=46099 or item_template=18796 or item_template=18798 or item_template=18797 or item_template=45595 or item_template=46749)");
			break;
		case 3:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=6777 or spell=6898 or spell=6899 or spell=23239 or spell=23240 or spell=23238 or spell=63636 or spell=65643)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=6777 or spell=6898 or spell=6899 or spell=23239 or spell=23240 or spell=23238 or spell=63636 or spell=65643)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=5864 or item_template=5873 or item_template=5872 or item_template=18787 or item_template=18785 or item_template=18786 or item_template=45586 or item_template=46748)");
			break;
		case 4:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=8394 or spell=10789 or spell=10793 or spell=66847 or spell=23338 or spell=23219 or spell=23221 or spell=63637 or spell=65638)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=8394 or spell=10789 or spell=10793 or spell=66847 or spell=23338 or spell=23219 or spell=23221 or spell=63637 or spell=65638)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=8631 or item_template=8632 or item_template=8629 or item_template=47100 or item_template=18902 or item_template=18767 or item_template=18766 or item_template=45591 or item_template=46744)");
			break;
		case 5:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=64977 or spell=17464 or spell=17463 or spell=17462 or spell=17465 or spell=23246 or spell=66846 or spell=63643 or spell=65645)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=64977 or spell=17464 or spell=17463 or spell=17462 or spell=17465 or spell=23246 or spell=66846 or spell=63643 or spell=65645)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=46308 or item_template=13333 or item_template=13332 or item_template=13331 or item_template=13334 or item_template=18791 or item_template=47101 or item_template=45597 or item_template=46746)");
			break;
		case 6:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=18990 or spell=18989 or spell=64657 or spell=23249 or spell=23248 or spell=23247 or spell=63641 or spell=65641)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=18990 or spell=18989 or spell=64657 or spell=23249 or spell=23248 or spell=23247 or spell=63641 or spell=65641)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=15290 or item_template=15277 or item_template=46100 or item_template=18794 or item_template=18795 or item_template=18793 or item_template=45592 or item_template=46750)");
			break;
		case 7:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=10969 or spell=17453 or spell=10873 or spell=17454 or spell=23225 or spell=23223 or spell=23222 or spell=63638 or spell=65642)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=10969 or spell=17453 or spell=10873 or spell=17454 or spell=23225 or spell=23223 or spell=23222 or spell=63638 or spell=65642)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=8595 or item_template=13321 or item_template=8563 or item_template=13322 or item_template=18772 or item_template=18773 or item_template=18774 or item_template=45589 or item_template=46747)");
			break;
		case 8:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=8395 or spell=10796 or spell=10799 or spell=23241 or spell=23242 or spell=23243 or spell=63635 or spell=65644)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=8395 or spell=10796 or spell=10799 or spell=23241 or spell=23242 or spell=23243 or spell=63635 or spell=65644)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=8588 or item_template=8591 or item_template=8592 or item_template=18788 or item_template=18789 or item_template=18790 or item_template=45593 or item_template=46743)");
			break;
		case 10:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=35022 or spell=35020 or spell=34795 or spell=35018 or spell=35025 or spell=35027 or spell=33660 or spell=63642 or spell=65639)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=35022 or spell=35020 or spell=34795 or spell=35018 or spell=35025 or spell=35027 or spell=33660 or spell=63642 or spell=65639)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=29221 or item_template=29220 or item_template=28927 or item_template=29222 or item_template=29223 or item_template=29224 or item_template=28936 or item_template=45596 or item_template=46751)");
			break;
		case 11:
			$pdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=34406 or spell=35710 or spell=35711 or spell=35713 or spell=35712 or spell=35714 or spell=63639 or spell=65637)");
			$pdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=34406 or spell=35710 or spell=35711 or spell=35713 or spell=35712 or spell=35714 or spell=63639 or spell=65637)");
			$pdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=28481 or item_template=29744 or item_template=29743 or item_template=29745 or item_template=29746 or item_template=29747 or item_template=45590 or item_template=46745)");
			break;
	}
}

function chartools_add_mounts($guid, $race, $db)
{
	$pdo = get_chartools_pdo($db);
	$guid = (int)$guid;
	switch ((int)$race) {
		case 1: $mount1 = 472; $mount2 = 23229; break;
		case 2: $mount1 = 580; $mount2 = 23250; break;
		case 3: $mount1 = 6777; $mount2 = 23239; break;
		case 4: $mount1 = 8394; $mount2 = 23338; break;
		case 5: $mount1 = 64977; $mount2 = 23246; break;
		case 6: $mount1 = 18990; $mount2 = 23249; break;
		case 7: $mount1 = 10969; $mount2 = 23225; break;
		case 8: $mount1 = 8395; $mount2 = 23241; break;
		case 10: $mount1 = 35022; $mount2 = 35025; break;
		case 11: $mount1 = 34406; $mount2 = 35713; break;
		default: return;
	}

	$pop = $pdo->query("SELECT * FROM character_spell WHERE guid='$guid' AND spell=33388")->fetchAll();
	if (count($pop) > 0) {
		$pdo->exec("INSERT INTO character_spell (guid,spell) VALUES ('$guid','$mount1')");
	}
	$pep = $pdo->query("SELECT * FROM character_spell WHERE guid='$guid' AND (spell=33391 or spell=34090 or spell=34091)")->fetchAll();
	if (count($pep) > 0) {
		$pdo->exec("INSERT INTO character_spell (guid,spell) VALUES ('$guid','$mount1')");
		$pdo->exec("INSERT INTO character_spell (guid,spell) VALUES ('$guid','$mount2')");
	}
}

function chartools_fetch_character_profile($guid, $accountId, $db)
{
	$pdo  = get_chartools_pdo($db);
	$stmt = $pdo->prepare("SELECT guid, account, name, race, class, gender, level FROM characters WHERE guid=? AND account=? LIMIT 1");
	$stmt->execute([(int)$guid, (int)$accountId]);
	return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function chartools_change_race_by_guid($guid, $accountId, $newrace, $db, &$message = '')
{
	$guid = (int)$guid;
	$accountId = (int)$accountId;
	$newrace = (int)$newrace;

	$profile = chartools_fetch_character_profile($guid, $accountId, $db);
	if (!$profile) {
		$message = 'Character could not be found on the selected account.';
		return false;
	}

	$oldrace = (int)$profile['race'];
	$class = (int)$profile['class'];
	$name = (string)$profile['name'];

	if ($newrace < 1 || $newrace > 11 || $newrace == 9) {
		$message = 'Race code invalid.';
		return false;
	}
	if ($newrace === $oldrace) {
		$message = 'The new race and the original race are the same.';
		return false;
	}
	if (!chartools_race_class_allowed($newrace, $class)) {
		$message = 'That class cannot use the selected race.';
		return false;
	}

	$status = check_if_online_by_guid($guid, $accountId, $db);
	if ($status === -1) {
		$message = 'Character could not be found on the selected account.';
		return false;
	}
	if ($status === 1) {
		$kickError = '';
		force_character_offline((int)$db['realm_id'], $name, $kickError);
		for ($i = 0; $i < 5; $i++) {
			usleep(500000);
			$status = check_if_online_by_guid($guid, $accountId, $db);
			if ($status !== 1) {
				break;
			}
		}
		if ($status === 1) {
			$message = 'This character is still online.';
			if ($kickError !== '') {
				$message .= ' SOAP: ' . $kickError;
			}
			return false;
		}
	}

	$guildCheck = chartools_check_guild_by_guid($guid, $db);
	$changingFaction = ((chartools_is_alliance($newrace) && !chartools_is_alliance($oldrace)) || (!chartools_is_alliance($newrace) && chartools_is_alliance($oldrace)));
	if ($changingFaction && $guildCheck != 0) {
		$message = 'When changing factions, the character must leave its guild first.';
		return false;
	}

	$pdo = get_chartools_pdo($db);
	chartools_delete_mounts($guid, $oldrace, $db);
	chartools_add_mounts($guid, $newrace, $db);

	$stmtRep = $pdo->prepare("SELECT `standing` FROM `character_reputation` WHERE guid=? AND faction=?");
	$stmtRep->execute([$guid, 72]); $aone = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 47]); $atwo = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 69]); $athree = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 54]); $afour = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 930]); $afive = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 76]); $hone = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 68]); $htwo = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 81]); $hthree = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 530]); $hfour = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, 911]); $hfive = $stmtRep->fetchColumn();

	$oldRepFaction = chartools_race_rep($oldrace);
	$newRepFaction = chartools_race_rep($newrace);
	$stmtRep->execute([$guid, $oldRepFaction]); $oldRep = $stmtRep->fetchColumn();
	$stmtRep->execute([$guid, $newRepFaction]); $newRep = $stmtRep->fetchColumn();

	if (chartools_is_alliance($oldrace)) {
		$stmtAch = $pdo->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=2030 or criteria=2031 or criteria=2032 or criteria=2033 or criteria=2034)");
	} else {
		$stmtAch = $pdo->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=992 or criteria=993 or criteria=994 or criteria=995 or criteria=996)");
	}
	$stmtAch->execute([$guid]);

	if (chartools_is_alliance($newrace) && !chartools_is_alliance($oldrace)) {
		$stmtPos = $pdo->prepare("UPDATE characters SET position_x = -8913.23, position_y = 554.633, position_z = 93.7944, map = 0 WHERE guid=?");
		$stmtPos->execute([$guid]);
	}
	if (!chartools_is_alliance($newrace) && chartools_is_alliance($oldrace)) {
		$stmtPos = $pdo->prepare("UPDATE characters SET position_x = 1440.45, position_y = -4422.78, position_z = 25.4634, map = 1 WHERE guid=?");
		$stmtPos->execute([$guid]);
	}

	$stmtUpdate = $pdo->prepare("UPDATE character_reputation SET standing=? WHERE guid=? AND faction=?");
	if (!$changingFaction) {
		$stmtUpdate->execute([$oldRep, $guid, $newRepFaction]);
		$stmtUpdate->execute([$newRep, $guid, $oldRepFaction]);
	} elseif (chartools_is_alliance($newrace)) {
		$stmtRu = $pdo->prepare("UPDATE `character_reputation` SET `standing`=?, `flags`=17 WHERE guid=? AND faction=?");
		foreach (array(array(72, $hone), array(47, $htwo), array(69, $hthree), array(54, $hfour), array(930, $hfive)) as $row) {
			$stmtRu->execute([$row[1], $guid, $row[0]]);
		}
		$stmtLow = $pdo->prepare("UPDATE `character_reputation` SET `standing`=150, `flags`=6 WHERE guid=? AND faction=?");
		foreach (array(76, 68, 81, 530, 911) as $factionId) {
			$stmtLow->execute([$guid, $factionId]);
		}
	} else {
		$stmtRu = $pdo->prepare("UPDATE `character_reputation` SET `standing`=?, `flags`=17 WHERE guid=? AND faction=?");
		foreach (array(array(76, $aone), array(68, $atwo), array(81, $athree), array(530, $afour), array(911, $afive)) as $row) {
			$stmtRu->execute([$row[1], $guid, $row[0]]);
		}
		$stmtLow = $pdo->prepare("UPDATE `character_reputation` SET `standing`=150, `flags`=6 WHERE guid=? AND faction=?");
		foreach (array(72, 47, 69, 54, 930) as $factionId) {
			$stmtLow->execute([$guid, $factionId]);
		}
	}

	$stmtChar = $pdo->prepare("UPDATE characters SET race=? ,at_login=8 ,playerBytes=1 WHERE guid=?");
	$stmtChar->execute([$newrace, $guid]);

	$message = 'Race/faction change applied to ' . $name . '.';
	return true;
}
