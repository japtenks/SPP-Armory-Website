<?php

function chartools_playerbot_gear_config_path($realmId)
{
	$basePath = 'C:\\Git\\playerbots\\playerbot\\';

	switch ((int)$realmId) {
		case 2:
			return $basePath . 'aiplayerbot.conf.dist.in.tbc';
		case 3:
			return $basePath . 'aiplayerbot.conf.dist.in.wotlk';
		case 1:
		default:
			return $basePath . 'aiplayerbot.conf.dist.in';
	}
}

function chartools_playerbot_class_name($class)
{
	switch ((int)$class) {
		case 1: return 'Warrior';
		case 2: return 'Paladin';
		case 3: return 'Hunter';
		case 4: return 'Rogue';
		case 5: return 'Priest';
		case 6: return 'Death Knight';
		case 7: return 'Shaman';
		case 8: return 'Mage';
		case 9: return 'Warlock';
		case 11: return 'Druid';
		default: return 'Unknown';
	}
}

function chartools_playerbot_spec_name($class, $spec)
{
	$specs = array(
		1 => array(0 => 'Arms', 1 => 'Fury', 2 => 'Protection'),
		2 => array(0 => 'Holy', 1 => 'Protection', 2 => 'Retribution'),
		3 => array(0 => 'Beast Mastery', 1 => 'Marksmanship', 2 => 'Survival'),
		4 => array(0 => 'Assassination', 1 => 'Combat', 2 => 'Subtlety'),
		5 => array(0 => 'Discipline', 1 => 'Holy', 2 => 'Shadow'),
		6 => array(0 => 'Blood', 1 => 'Frost', 2 => 'Unholy'),
		7 => array(0 => 'Elemental', 1 => 'Enhancement', 2 => 'Restoration'),
		8 => array(0 => 'Arcane', 1 => 'Fire', 2 => 'Frost'),
		9 => array(0 => 'Affliction', 1 => 'Demonology', 2 => 'Destruction'),
		11 => array(0 => 'Balance', 1 => 'Feral', 2 => 'Restoration'),
	);

	return $specs[(int)$class][(int)$spec] ?? ('Spec ' . (int)$spec);
}

function chartools_playerbot_phase_label($phase)
{
	return 'P' . (int)$phase;
}

function chartools_playerbot_level_cap($realmId)
{
	switch ((int)$realmId) {
		case 2:
			return 70;
		case 3:
			return 80;
		case 1:
		default:
			return 60;
	}
}

function chartools_playerbot_profession_cap($realmId)
{
	switch ((int)$realmId) {
		case 2:
			return 375;
		case 3:
			return 450;
		case 1:
		default:
			return 300;
	}
}

function chartools_playerbot_premade_specs($realmId, $classId)
{
	static $cache = array();

	$realmId = (int)$realmId;
	$classId = (int)$classId;
	$cacheKey = $realmId . ':' . $classId;
	if (isset($cache[$cacheKey])) {
		return $cache[$cacheKey];
	}

	$configPath = chartools_playerbot_gear_config_path($realmId);
	if (!is_file($configPath) || !is_readable($configPath)) {
		return $cache[$cacheKey] = array();
	}

	$lines = @file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		return $cache[$cacheKey] = array();
	}

	$specs = array();
	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}

		if (preg_match('/^AiPlayerbot\.PremadeSpecName\.(\d+)\.(\d+)\s*=\s*(.+)$/', $line, $matches)) {
			if ((int)$matches[1] !== $classId) {
				continue;
			}

			$specId = (int)$matches[2];
			if (!isset($specs[$specId])) {
				$specs[$specId] = array(
					'spec_id' => $specId,
					'name' => '',
					'links' => array(),
				);
			}
			$specs[$specId]['name'] = trim((string)$matches[3]);
			continue;
		}

		if (preg_match('/^AiPlayerbot\.PremadeSpecLink\.(\d+)\.(\d+)\.(\d+)\s*=\s*(.+)$/', $line, $matches)) {
			if ((int)$matches[1] !== $classId) {
				continue;
			}

			$specId = (int)$matches[2];
			$level = (int)$matches[3];
			if (!isset($specs[$specId])) {
				$specs[$specId] = array(
					'spec_id' => $specId,
					'name' => '',
					'links' => array(),
				);
			}
			$specs[$specId]['links'][$level] = trim((string)$matches[4]);
		}
	}

	ksort($specs);
	return $cache[$cacheKey] = array_values($specs);
}

function chartools_playerbot_normalize_role_key($name)
{
	$value = strtolower(trim((string)$name));
	$value = str_replace(array('(', ')', "'", '"'), '', $value);
	$value = str_replace(array('/', '-', '_'), ' ', $value);
	$value = preg_replace('/\s+/', ' ', $value);

	$replacements = array(
		'pve dps ' => '',
		'pve heal ' => '',
		'pve tank ' => '',
		'pvp dps ' => '',
		'pvp heal ' => '',
		'pvp tank ' => '',
		'pve ' => '',
		'pvp ' => '',
		'dps ' => '',
		'heal ' => '',
		'tank ' => '',
		'assasination' => 'assassination',
		'affli' => 'affliction',
		'destro' => 'destruction',
		'dest' => 'destruction',
		'ret' => 'retribution',
		'prot' => 'protection',
		'elem' => 'elemental',
		'enhan' => 'enhancement',
		'mm' => 'marksmanship',
		'bm' => 'beast mastery',
		'surv' => 'survival',
	);

	foreach ($replacements as $search => $replace) {
		$value = str_replace($search, $replace, $value);
	}

	$value = trim(preg_replace('/\s+/', ' ', $value));
	if ($value === 'furyprotection') {
		$value = 'fury protection';
	}

	return $value;
}

function chartools_playerbot_role_label($classId, $name)
{
	$key = chartools_playerbot_normalize_role_key($name);
	$explicit = array(
		1 => array(
			'arms' => 'Arms',
			'fury' => 'Fury DPS',
			'protection' => 'Protection',
			'fury protection' => 'Fury/Prot',
			'fury slam' => 'Fury Slam',
			'2h fury' => '2H Fury',
		),
		2 => array(
			'holy' => 'Holy',
			'protection' => 'Protection',
			'retribution' => 'Retribution',
		),
		3 => array(
			'marksmanship' => 'Marksmanship',
			'beast mastery farmer' => 'Beast Mastery',
			'beast mastery' => 'Beast Mastery',
			'survival' => 'Survival',
		),
		4 => array(
			'assassination' => 'Assassination',
			'combat swords' => 'Combat Swords',
			'combat daggers' => 'Combat Daggers',
			'combat daggers2' => 'Combat Daggers',
			'combat' => 'Combat',
			'subtlety' => 'Subtlety',
		),
		5 => array(
			'discipline' => 'Discipline',
			'holy' => 'Holy',
			'shadow' => 'Shadow',
		),
		7 => array(
			'elemental elemental mastery' => 'Elemental',
			'elemental natures swiftness' => 'Elemental NS',
			'enhancement 2hand' => 'Enhancement',
			'enhancement' => 'Enhancement',
			'restoration pure' => 'Restoration',
			'restoration melee support restoration' => 'Restoration Support',
			'restoration' => 'Restoration',
		),
		8 => array(
			'arcane' => 'Arcane',
			'fire' => 'Fire',
			'frost winters chill spec' => 'Frost',
			'frost' => 'Frost',
			'frost frost arcane' => 'Frost/Arcane',
		),
		9 => array(
			'affliction' => 'Affliction',
			'demonology ds ruin' => 'Demonology',
			'demonology succubus sacrifice' => 'Demonology',
			'destruction imp lord' => 'Destruction',
			'demonology sm ruin' => 'SM/Ruin',
			'destruction conflagrate' => 'Destruction',
		),
		11 => array(
			'balance' => 'Balance',
			'feral' => 'Feral',
			'feral dps tank hybrid' => 'Feral Hybrid',
			'feral heart of the wild ns' => 'Feral',
			'restoration swiftmend spec' => 'Restoration',
			'restoration regrowth spec bear aoe farm' => 'Restoration',
			'restoration resto balance' => 'Restoration/Balance',
		),
	);

	if (isset($explicit[(int)$classId][$key])) {
		return $explicit[(int)$classId][$key];
	}

	$words = array();
	foreach (explode(' ', $key) as $word) {
		if ($word === '') {
			continue;
		}
		$words[] = ucfirst($word);
	}

	return !empty($words) ? implode(' ', $words) : trim((string)$name);
}

function chartools_playerbot_full_package_roles($realmId, $classId)
{
	$levelCap = chartools_playerbot_level_cap($realmId);
	$specs = chartools_playerbot_premade_specs($realmId, $classId);
	$preferred = array();
	$fallback = array();

	foreach ($specs as $spec) {
		$linkLevels = array_keys((array)($spec['links'] ?? array()));
		if (empty($linkLevels)) {
			continue;
		}

		sort($linkLevels, SORT_NUMERIC);
		$chosenLevel = 0;
		foreach ($linkLevels as $level) {
			if ((int)$level <= $levelCap) {
				$chosenLevel = (int)$level;
			}
		}
		if ($chosenLevel <= 0) {
			$chosenLevel = (int)end($linkLevels);
		}

		$rawName = trim((string)($spec['name'] ?? ''));
		if ($rawName === '') {
			continue;
		}

		$label = chartools_playerbot_role_label($classId, $rawName);
		$key = strtolower($label);
		$option = array(
			'id' => 'spec:' . (int)$spec['spec_id'],
			'spec_id' => (int)$spec['spec_id'],
			'label' => $label,
			'raw_name' => $rawName,
			'talent_link' => (string)$spec['links'][$chosenLevel],
			'talent_level' => $chosenLevel,
		);

		if (stripos($rawName, 'pve ') === 0 || stripos($rawName, 'furyprot') === 0 || stripos($rawName, 'arms ') === 0 || stripos($rawName, 'fury ') === 0) {
			if (!isset($preferred[$key])) {
				$preferred[$key] = $option;
			}
		}

		if (!isset($fallback[$key])) {
			$fallback[$key] = $option;
		}
	}

	$options = !empty($preferred) ? array_values($preferred) : array_values($fallback);
	usort($options, function ($a, $b) {
		return strcasecmp((string)$a['label'], (string)$b['label']);
	});

	return $options;
}

function chartools_playerbot_full_package_phases()
{
	$phases = array();
	for ($phase = 0; $phase <= 5; $phase++) {
		$phases[] = array(
			'id' => 'phase:' . $phase,
			'phase' => $phase,
			'label' => chartools_playerbot_phase_label($phase),
		);
	}

	return $phases;
}

function chartools_playerbot_phase_has_spec_gear($realmId, $classId, $specId, $phase)
{
	return !empty(chartools_playerbot_gear_items_for_phase($realmId, $classId, $specId, $phase));
}

function chartools_playerbot_phase_filtered_roles($realmId, $classId, $phase)
{
	$roles = chartools_playerbot_full_package_roles($realmId, $classId);
	$filtered = array();
	foreach ($roles as $role) {
		if (chartools_playerbot_phase_has_spec_gear($realmId, $classId, (int)$role['spec_id'], (int)$phase)) {
			$filtered[] = $role;
		}
	}

	return $filtered;
}

function chartools_playerbot_gear_items_for_phase($realmId, $classId, $specId, $phase)
{
	$options = chartools_parse_gear_progression_options($realmId, $classId);
	foreach ($options as $option) {
		$parts = explode(':', (string)($option['id'] ?? ''));
		if (count($parts) !== 5) {
			continue;
		}

		if ((int)$parts[1] !== (int)$realmId || (int)$parts[2] !== (int)$classId || (int)$parts[3] !== (int)$phase || (int)$parts[4] !== (int)$specId) {
			continue;
		}

		return array_values(array_unique(array_map('intval', (array)($option['items'] ?? array()))));
	}

	return array();
}

function chartools_profession_spell_map()
{
	return array(
		164 => array('name' => 'Blacksmithing', 'spell' => 2018),
		165 => array('name' => 'Leatherworking', 'spell' => 2108),
		171 => array('name' => 'Alchemy', 'spell' => 2259),
		197 => array('name' => 'Tailoring', 'spell' => 3908),
		202 => array('name' => 'Engineering', 'spell' => 4036),
		333 => array('name' => 'Enchanting', 'spell' => 7411),
	);
}

function chartools_required_professions_for_items($realmId, array $itemIds)
{
	$itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
	if (empty($itemIds)) {
		return array();
	}

	$worldPdo = spp_get_pdo('world', (int)$realmId);
	$placeholders = implode(',', array_fill(0, count($itemIds), '?'));
	$stmt = $worldPdo->prepare("SELECT entry, RequiredSkill FROM item_template WHERE entry IN ($placeholders)");
	$stmt->execute($itemIds);

	$skillMap = chartools_profession_spell_map();
	$required = array();
	foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
		$skillId = (int)($row['RequiredSkill'] ?? 0);
		if ($skillId > 0 && isset($skillMap[$skillId])) {
			$required[$skillId] = array(
				'skill_id' => $skillId,
				'spell_id' => (int)$skillMap[$skillId]['spell'],
				'name' => (string)$skillMap[$skillId]['name'],
			);
		}
	}

	ksort($required);
	return array_values($required);
}

function chartools_apply_profession_skills($guid, $realmId, array $professions)
{
	if ((int)$guid <= 0 || empty($professions)) {
		return;
	}

	$charPdo = spp_get_pdo('chars', (int)$realmId);
	$skillCap = chartools_playerbot_profession_cap($realmId);
	$stmtSkill = $charPdo->prepare("INSERT INTO character_skills (guid, skill, value, max) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), max = VALUES(max)");
	$stmtSpell = $charPdo->prepare("INSERT IGNORE INTO character_spell (guid, spell, active, disabled) VALUES (?, ?, 1, 0)");

	foreach ($professions as $profession) {
		$skillId = (int)($profession['skill_id'] ?? 0);
		$spellId = (int)($profession['spell_id'] ?? 0);
		if ($skillId <= 0 || $spellId <= 0) {
			continue;
		}

		$stmtSkill->execute(array((int)$guid, $skillId, $skillCap, $skillCap));
		$stmtSpell->execute(array((int)$guid, $spellId));
	}
}

function chartools_build_full_package_roles($realmId, $selectedCharacterProfile, $selectedPhaseId = '')
{
	if (empty($selectedCharacterProfile['class'])) {
		return array();
	}

	if (strpos((string)$selectedPhaseId, 'phase:') === 0) {
		return chartools_playerbot_phase_filtered_roles((int)$realmId, (int)$selectedCharacterProfile['class'], (int)substr((string)$selectedPhaseId, 6));
	}

	return chartools_playerbot_full_package_roles((int)$realmId, (int)$selectedCharacterProfile['class']);
}

function chartools_build_full_package_phases()
{
	return chartools_playerbot_full_package_phases();
}

function chartools_parse_gear_progression_options($realmId, $classId)
{
	static $cache = array();

	$realmId = (int)$realmId;
	$classId = (int)$classId;
	$cacheKey = $realmId . ':' . $classId;
	if (isset($cache[$cacheKey])) {
		return $cache[$cacheKey];
	}

	$configPath = chartools_playerbot_gear_config_path($realmId);
	if (!is_file($configPath) || !is_readable($configPath)) {
		return $cache[$cacheKey] = array();
	}

	$lines = @file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		return $cache[$cacheKey] = array();
	}

	$packs = array();
	foreach ($lines as $line) {
		if (!preg_match('/^AiPlayerbot\.GearProgressionSystem\.(\d+)\.(\d+)\.(\d+)\.(\d+)\s*=\s*(\d+)/', trim($line), $matches)) {
			continue;
		}

		$phase = (int)$matches[1];
		$lineClass = (int)$matches[2];
		$spec = (int)$matches[3];
		$slot = (int)$matches[4];
		$itemId = (int)$matches[5];

		if ($phase < 0 || $phase > 5 || $lineClass !== $classId || $slot < 0 || $slot > 18 || $itemId <= 0) {
			continue;
		}

		if (!isset($packs[$phase])) {
			$packs[$phase] = array();
		}
		if (!isset($packs[$phase][$spec])) {
			$packs[$phase][$spec] = array();
		}

		$packs[$phase][$spec][$slot] = $itemId;
	}

	$options = array();
	ksort($packs);
	foreach ($packs as $phase => $specs) {
		ksort($specs);
		foreach ($specs as $spec => $slots) {
			ksort($slots);
			$itemIds = array_values(array_unique(array_filter(array_map('intval', $slots))));
			if (empty($itemIds)) {
				continue;
			}

			$phaseLabel = chartools_playerbot_phase_label($phase);
			$specLabel = chartools_playerbot_spec_name($classId, $spec);
			$options[] = array(
				'id' => 'gear:' . $realmId . ':' . $classId . ':' . $phase . ':' . $spec,
				'description' => $phaseLabel . ' ' . $specLabel . ' Gear',
				'donation' => '0',
				'currency' => 'GM',
				'kind' => 'gear_progression',
				'items' => $itemIds,
				'realm' => $realmId,
			);
		}
	}

	return $cache[$cacheKey] = $options;
}

function chartools_build_delivery_options(array $databasePacks, $realmId, $selectedCharacterProfile)
{
	$options = array();

	$professionPacks = array(
		array(
			'id' => 'profession:engineering',
			'description' => 'Engineering 1-300 Care Package',
			'donation' => '0',
			'currency' => 'GM',
			'kind' => 'profession_pack',
			'items' => array(
				array('id' => 2835, 'count' => 60),
				array('id' => 2840, 'count' => 66),
				array('id' => 2836, 'count' => 60),
				array('id' => 2589, 'count' => 50),
				array('id' => 2842, 'count' => 5),
				array('id' => 2841, 'count' => 110),
				array('id' => 2838, 'count' => 30),
				array('id' => 1206, 'count' => 10),
				array('id' => 2592, 'count' => 60),
				array('id' => 2319, 'count' => 15),
				array('id' => 3859, 'count' => 4),
				array('id' => 7912, 'count' => 120),
				array('id' => 3860, 'count' => 170),
				array('id' => 4338, 'count' => 20),
				array('id' => 12365, 'count' => 60),
				array('id' => 12359, 'count' => 225),
				array('id' => 14047, 'count' => 35),
			),
		),
		array(
			'id' => 'profession:alchemy',
			'description' => 'Alchemy 1-300 Care Package',
			'donation' => '0',
			'currency' => 'GM',
			'kind' => 'profession_pack',
			'items' => array(
				array('id' => 2447, 'count' => 65),
				array('id' => 765, 'count' => 65),
				array('id' => 3371, 'count' => 85),
				array('id' => 2450, 'count' => 100),
				array('id' => 2453, 'count' => 35),
				array('id' => 3372, 'count' => 105),
				array('id' => 785, 'count' => 20),
				array('id' => 3820, 'count' => 50),
				array('id' => 3357, 'count' => 35),
				array('id' => 3356, 'count' => 35),
				array('id' => 3821, 'count' => 35),
				array('id' => 3355, 'count' => 5),
				array('id' => 8838, 'count' => 75),
				array('id' => 3358, 'count' => 15),
				array('id' => 8925, 'count' => 120),
				array('id' => 8836, 'count' => 45),
				array('id' => 8839, 'count' => 60),
				array('id' => 13464, 'count' => 75),
				array('id' => 13465, 'count' => 20),
			),
		),
		array(
			'id' => 'profession:blacksmithing',
			'description' => 'Blacksmithing 1-300 Care Package',
			'donation' => '0',
			'currency' => 'GM',
			'kind' => 'profession_pack',
			'items' => array(
				array('id' => 2835, 'count' => 150),
				array('id' => 2840, 'count' => 150),
				array('id' => 2836, 'count' => 95),
				array('id' => 2842, 'count' => 5),
				array('id' => 2841, 'count' => 140),
				array('id' => 2838, 'count' => 105),
				array('id' => 3577, 'count' => 5),
				array('id' => 3575, 'count' => 230),
				array('id' => 2605, 'count' => 35),
				array('id' => 3859, 'count' => 190),
				array('id' => 7912, 'count' => 480),
				array('id' => 4338, 'count' => 60),
				array('id' => 3860, 'count' => 220),
				array('id' => 12365, 'count' => 20),
				array('id' => 12359, 'count' => 730),
				array('id' => 7910, 'count' => 30),
				array('id' => 7909, 'count' => 5),
			),
		),
		array(
			'id' => 'profession:enchanting',
			'description' => 'Enchanting 1-300 Care Package',
			'donation' => '0',
			'currency' => 'GM',
			'kind' => 'profession_pack',
			'items' => array(
				array('id' => 10940, 'count' => 125),
				array('id' => 10938, 'count' => 1),
				array('id' => 10939, 'count' => 12),
				array('id' => 10998, 'count' => 25),
				array('id' => 11083, 'count' => 130),
				array('id' => 11082, 'count' => 2),
				array('id' => 11137, 'count' => 250),
				array('id' => 11135, 'count' => 2),
				array('id' => 11174, 'count' => 5),
				array('id' => 11176, 'count' => 360),
				array('id' => 8831, 'count' => 40),
				array('id' => 16204, 'count' => 40),
				array('id' => 16203, 'count' => 4),
				array('id' => 14343, 'count' => 4),
				array('id' => 14344, 'count' => 2),
				array('id' => 6338, 'count' => 1),
				array('id' => 1210, 'count' => 1),
				array('id' => 11128, 'count' => 1),
				array('id' => 5500, 'count' => 1),
				array('id' => 11144, 'count' => 1),
				array('id' => 7971, 'count' => 1),
				array('id' => 16206, 'count' => 1),
				array('id' => 13926, 'count' => 1),
			),
		),
		array(
			'id' => 'profession:leatherworking',
			'description' => 'Leatherworking 1-300 Care Package',
			'donation' => '0',
			'currency' => 'GM',
			'kind' => 'profession_pack',
			'items' => array(
				array('id' => 2934, 'count' => 57),
				array('id' => 2318, 'count' => 470),
				array('id' => 2319, 'count' => 335),
				array('id' => 4235, 'count' => 20),
				array('id' => 4234, 'count' => 195),
				array('id' => 4304, 'count' => 650),
				array('id' => 8170, 'count' => 400),
				array('id' => 14047, 'count' => 100),
			),
		),
		array(
			'id' => 'profession:tailoring',
			'description' => 'Tailoring 1-300 Care Package',
			'donation' => '0',
			'currency' => 'GM',
			'kind' => 'profession_pack',
			'items' => array(
				array('id' => 2589, 'count' => 204),
				array('id' => 2592, 'count' => 135),
				array('id' => 4306, 'count' => 804),
				array('id' => 4338, 'count' => 470),
				array('id' => 14047, 'count' => 1195),
				array('id' => 8170, 'count' => 110),
			),
		),
	);

	foreach ($professionPacks as $pack) {
		$options[] = $pack;
	}

	foreach ($databasePacks as $pack) {
		$pack['kind'] = $pack['kind'] ?? 'database';
		$options[] = $pack;
	}

	return $options;
}
