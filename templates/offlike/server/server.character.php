<?php
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');

function spp_character_table_exists(PDO $pdo, $tableName) {
    static $cache = array();
    $key = spl_object_hash($pdo) . ':' . $tableName;
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    $stmt->execute(array($tableName));
    return $cache[$key] = (bool)$stmt->fetchColumn();
}

function spp_character_pick_dbc_pdo(array $candidates, $tableName) {
    foreach ($candidates as $pdo) {
        if ($pdo instanceof PDO && spp_character_table_exists($pdo, $tableName)) {
            return $pdo;
        }
    }
    return null;
}

function spp_character_talent_points(PDO $charsPdo, PDO $talentPdo, $guid, $tabId) {
    $guid = (int)$guid;
    $tabId = (int)$tabId;
    if ($guid <= 0 || $tabId <= 0) return 0;
    $points = 0;
    if (spp_character_table_exists($charsPdo, 'character_talent')) {
        $stmt = $charsPdo->prepare('SELECT `talent_id`, `current_rank` FROM `character_talent` WHERE `guid` = ?');
        $stmt->execute(array($guid));
        $talentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($talentRows) && spp_character_table_exists($talentPdo, 'dbc_talent')) {
            $talentIds = array();
            $rankByTalent = array();
            foreach ($talentRows as $row) {
                $talentId = (int)($row['talent_id'] ?? 0);
                if ($talentId <= 0) continue;
                $talentIds[$talentId] = true;
                $rankByTalent[$talentId] = (int)($row['current_rank'] ?? 0);
            }
            if (!empty($talentIds)) {
                $placeholders = implode(',', array_fill(0, count($talentIds), '?'));
                $talentStmt = $talentPdo->prepare('SELECT `id`, `ref_talenttab` FROM `dbc_talent` WHERE `id` IN (' . $placeholders . ')');
                $talentStmt->execute(array_keys($talentIds));
                foreach ($talentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ((int)($row['ref_talenttab'] ?? 0) === $tabId) {
                        $talentId = (int)$row['id'];
                        $points += ($rankByTalent[$talentId] ?? 0) + 1;
                    }
                }
                if ($points > 0) return $points;
            }
        }
    }
    if (!spp_character_table_exists($charsPdo, 'character_spell')) return 0;
    $spellRows = $charsPdo->prepare('SELECT `spell` FROM `character_spell` WHERE `guid` = ? AND `disabled` = 0');
    $spellRows->execute(array($guid));
    $learned = array();
    foreach ($spellRows->fetchAll(PDO::FETCH_ASSOC) as $row) $learned[(int)$row['spell']] = true;
    if (empty($learned)) return 0;
    if (!spp_character_table_exists($talentPdo, 'dbc_talent')) return 0;
    $stmt = $talentPdo->prepare('SELECT `rank1`, `rank2`, `rank3`, `rank4`, `rank5` FROM `dbc_talent` WHERE `ref_talenttab` = ?');
    $stmt->execute(array($tabId));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        for ($rank = 5; $rank >= 1; $rank--) {
            $spellId = (int)($row['rank' . $rank] ?? 0);
            if ($spellId > 0 && isset($learned[$spellId])) {
                $points += $rank;
                break;
            }
        }
    }
    return $points;
}

function spp_character_reputation_tier($label) {
    $key = strtolower(trim((string)$label));
    return preg_replace('/[^a-z0-9]+/', '-', $key);
}


function spp_character_columns(PDO $pdo, $tableName) {
    static $cache = array();
    $key = spl_object_hash($pdo) . ':' . $tableName;
    if (isset($cache[$key])) return $cache[$key];
    $columns = array();
    if (!spp_character_table_exists($pdo, $tableName)) return $cache[$key] = $columns;
    foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $tableName) . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $columns[$row['Field']] = true;
    }
    return $cache[$key] = $columns;
}

function spp_character_spellicon_fields(PDO $pdo) {
    static $cache = array();
    $key = spl_object_hash($pdo);
    if (isset($cache[$key])) return $cache[$key];
    $columns = spp_character_columns($pdo, 'dbc_spellicon');
    $idField = isset($columns['id']) ? 'id' : (isset($columns['ID']) ? 'ID' : null);
    $nameField = isset($columns['name']) ? 'name' : (isset($columns['TextureFilename']) ? 'TextureFilename' : null);
    return $cache[$key] = array(
        'id' => $idField,
        'name' => $nameField,
    );
}

function spp_character_portrait_path($level, $gender, $race, $class) {
    if ((int)$level <= 59) $bucket = 'wow-default';
    elseif ((int)$level <= 69) $bucket = 'wow';
    elseif ((int)$level <= 79) $bucket = 'wow-70';
    else $bucket = 'wow-80';
    return '/armory/images/portraits/' . $bucket . '/' . (int)$gender . '-' . (int)$race . '-' . (int)$class . '.gif';
}

function spp_character_icon_url($iconName) {
    $iconName = trim((string)$iconName);
    if ($iconName === '') return '/armory/images/icons/64x64/404.png';
    $basename = preg_replace('/\.(png|jpg|jpeg|gif)$/i', '', $iconName);
    $basename = strtolower($basename);
    foreach (array('jpg', 'jpeg', 'png') as $extension) {
        $xferPath = $siteRoot . '/xfer/assets/images/' . $basename . '.' . $extension;
        if (is_file($xferPath)) {
            return '/xfer/assets/images/' . $basename . '.' . $extension;
        }
    }
    return '/armory/images/icons/64x64/' . strtolower($basename) . '.png';
}

function spp_character_skill_icon_url($skillName, $iconName) {
    $skillName = trim((string)$skillName);
    $overrides = array(
        'Swords' => 'INV_Sword_04',
        'Axes' => 'INV_Axe_04',
        'Two-Handed Axes' => 'INV_Axe_09',
        'Maces' => 'INV_Mace_01',
        'Two-Handed Maces' => 'INV_Mace_04',
        'Bows' => 'INV_Weapon_Bow_08',
        'Guns' => 'INV_Weapon_Rifle_06',
        'Crossbows' => 'INV_Weapon_Crossbow_04',
        'Daggers' => 'INV_Weapon_ShortBlade_05',
        'Two-Handed Swords' => 'INV_Sword_27',
        'Polearms' => 'INV_Weapon_Halberd_09',
        'Staves' => 'INV_Staff_08',
        'Wands' => 'INV_Wand_04',
        'Thrown' => 'INV_ThrowingKnife_02',
        'Defense' => 'Ability_Defend',
        'Dual Wield' => 'Ability_DualWield',
        'Fist Weapons' => 'INV_Gauntlets_04',
        'Unarmed' => 'Ability_MeleeDamage',
        'Fishing' => 'INV_Misc_Fish_08',
        'Cooking' => 'INV_Misc_Food_15',
        'First Aid' => 'INV_Misc_Bandage_15',
        'Riding' => 'Ability_Mount_RidingHorse',
        'Shield' => 'INV_Shield_06',
        'Shields' => 'INV_Shield_06',
        'Cloth' => 'INV_Chest_Cloth_21',
        'Leather' => 'INV_Chest_Leather_08',
        'Mail' => 'INV_Chest_Chain_10',
        'Plate Mail' => 'INV_Chest_Plate01',
        'Plate' => 'INV_Chest_Plate01',
    );
    if (isset($overrides[$skillName])) {
        return spp_character_icon_url($overrides[$skillName]);
    }
    return spp_character_icon_url($iconName);
}

function spp_character_language_icon_url($skillName, $raceId, $gender) {
    $skillName = strtolower(trim((string)$skillName));
    $raceId = (int)$raceId;
    $gender = (int)$gender;
    $genderSlug = $gender === 1 ? 'female' : 'male';
    $raceIconMap = array(
        1 => 'achievement_character_human_' . $genderSlug,
        2 => 'achievement_character_orc_' . $genderSlug,
        3 => 'achievement_character_dwarf_' . $genderSlug,
        4 => 'achievement_character_nightelf_' . $genderSlug,
        5 => 'achievement_character_undead_' . $genderSlug,
        6 => 'achievement_character_tauren_' . $genderSlug,
        7 => 'achievement_character_gnome_' . $genderSlug,
        8 => 'achievement_character_troll_' . $genderSlug,
        10 => 'achievement_character_bloodelf_' . $genderSlug,
        11 => 'achievement_character_draenei_' . $genderSlug,
    );
    $sharedAlliance = array('language: common', 'common');
    $sharedHorde = array('language: orcish', 'orcish');
    if (in_array($skillName, $sharedAlliance, true) || in_array($skillName, $sharedHorde, true)) {
        return isset($raceIconMap[$raceId]) ? '/armory/images/icons/64x64/' . $raceIconMap[$raceId] . '.png' : '/armory/images/icons/64x64/404.png';
    }
    $languageMap = array(
        'language: darnassian' => array(4),
        'darnassian' => array(4),
        'language: dwarven' => array(3),
        'dwarven' => array(3),
        'language: gnomish' => array(7),
        'gnomish' => array(7),
        'language: troll' => array(8),
        'troll' => array(8),
        'language: taurahe' => array(6),
        'taurahe' => array(6),
        'language: gutterspeak' => array(5),
        'gutterspeak' => array(5),
        'language: draconic' => array(1, 2, 3, 4, 5, 6, 7, 8),
        'draconic' => array(1, 2, 3, 4, 5, 6, 7, 8),
        'language: demon tongue' => array(5, 8),
        'demon tongue' => array(5, 8),
        'language: titan' => array(1, 3, 7),
        'titan' => array(1, 3, 7),
        'language: old tongue' => array(4, 8),
        'old tongue' => array(4, 8),
    );
    if (!isset($languageMap[$skillName])) return null;
    $choices = $languageMap[$skillName];
    $pickedRace = $choices[abs(crc32($skillName . ':' . $raceId . ':' . $gender)) % count($choices)];
    return isset($raceIconMap[$pickedRace]) ? '/armory/images/icons/64x64/' . $raceIconMap[$pickedRace] . '.png' : '/armory/images/icons/64x64/404.png';
}

function spp_character_profession_tier_label($max, $name = '') {
    $max = (int)$max;
    $name = trim((string)$name);
    if (strcasecmp($name, 'Riding') === 0) {
        if ($max >= 150) return 'Expert Riding';
        if ($max >= 75) return 'Apprentice Riding';
        return 'No Riding Training';
    }
    if ($max >= 300) return 'Artisan';
    if ($max >= 225) return 'Expert';
    if ($max >= 150) return 'Journeyman';
    if ($max >= 75) return 'Apprentice';
    return $max > 0 ? 'Training' : 'Untrained';
}

function spp_character_binary_skill_label($categoryName, $value, $max) {
    $categoryName = strtolower(trim((string)$categoryName));
    if (strpos($categoryName, 'language') !== false) {
        return (int)$value > 0 ? 'Known' : 'Unknown';
    }
    if (strpos($categoryName, 'armor prof') !== false || strpos($categoryName, 'armor proficiency') !== false) {
        return (int)$value > 0 ? 'Learned' : 'Unlearned';
    }
    return null;
}

function spp_character_skill_entry(array $meta, array $row, array $characterContext = array()) {
    $name = trim((string)($meta['name'] ?? ''));
    $description = trim((string)($meta['description'] ?? ''));
    if ($name === '' || stripos($name, 'racial') !== false) return null;
    $value = (int)($row['value'] ?? 0);
    $max = max(1, (int)($row['max'] ?? 0));
    $displayValue = $value . '/' . $max;
    $rankLabel = $displayValue;
    $binaryLabel = spp_character_binary_skill_label((string)($meta['category_name'] ?? ''), $value, $max);
    if ($binaryLabel !== null) {
        $displayValue = $binaryLabel;
        $rankLabel = $binaryLabel;
    }
    if (strcasecmp($name, 'Riding') === 0) {
        if ($value >= 150 || $max >= 150) {
            $displayValue = '100% Mounts';
            $description = 'Expert riding unlocked.';
        } elseif ($value >= 75 || $max >= 75) {
            $displayValue = '60% Mounts';
            $description = 'Apprentice riding unlocked.';
        } else {
            $displayValue = 'No Mount Training';
            $description = 'Riding training not yet unlocked.';
        }
        $rankLabel = spp_character_profession_tier_label($max, $name);
    } elseif (
        stripos((string)($meta['category_name'] ?? ''), 'profession') !== false ||
        stripos((string)($meta['category_name'] ?? ''), 'secondary') !== false
    ) {
        $rankLabel = spp_character_profession_tier_label($max, $name);
    }
    return array(
        'skill_id' => (int)($row['skill'] ?? 0),
        'name' => $name,
        'description' => $description,
        'value' => $value,
        'max' => $max,
        'rank_label' => $rankLabel,
        'display_value' => $displayValue,
        'percent' => min(100, max(0, round(($value / $max) * 100))),
        'icon' => (
            spp_character_binary_skill_label((string)($meta['category_name'] ?? ''), $value, $max) !== null &&
            stripos((string)($meta['category_name'] ?? ''), 'language') !== false &&
            isset($characterContext['race'], $characterContext['gender'])
        )
            ? (spp_character_language_icon_url($name, (int)$characterContext['race'], (int)$characterContext['gender']) ?: spp_character_skill_icon_url($name, $meta['icon_name'] ?? ''))
            : spp_character_skill_icon_url($name, $meta['icon_name'] ?? ''),
    );
}

function spp_character_recipe_display_name($name) {
    $name = trim((string)$name);
    if ($name === '') return '';
    $display = preg_replace('/^(recipe|pattern|plans|formula|manual|schematic|book|design|tome|technique)\s*:\s*/i', '', $name);
    return trim((string)$display) !== '' ? trim((string)$display) : $name;
}

function spp_character_recipe_filter_labels() {
    return array(
        'all' => 'All',
        'faction' => 'Faction Rep',
        'rare-drop' => 'Rare Drops',
        'endgame' => '300 Skill',
        'flask' => 'Flasks',
    );
}

function spp_character_profession_specializations($professionName, array $knownSpells) {
    $professionName = strtolower(trim((string)$professionName));
    $maps = array(
        'leatherworking' => array(
            10656 => 'Dragonscale',
            10658 => 'Elemental',
            10660 => 'Tribal',
        ),
        'blacksmithing' => array(
            9787 => 'Weaponsmith',
            9788 => 'Armorsmith',
            17039 => 'Master Swordsmith',
            17040 => 'Master Hammersmith',
            17041 => 'Master Axesmith',
        ),
        'engineering' => array(
            20219 => 'Gnomish',
            20222 => 'Goblin',
        ),
    );
    if (!isset($maps[$professionName])) return array();
    $specializations = array();
    foreach ($maps[$professionName] as $spellId => $label) {
        if (!empty($knownSpells[$spellId])) $specializations[] = $label;
    }
    return $specializations;
}

function spp_character_format_playtime($seconds) {
    $seconds = max(0, (int)$seconds);
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $parts = array();
    if ($days > 0) $parts[] = $days . 'd';
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0 || empty($parts)) $parts[] = $minutes . 'm';
    return implode(' ', $parts);
}

function spp_character_faction_name($raceId) {
    return in_array((int)$raceId, array(1, 3, 4, 7, 11, 22, 25, 29), true) ? 'Alliance' : 'Horde';
}

function spp_character_quest_template_fields(PDO $worldPdo) {
    static $cache = array();
    $key = spl_object_hash($worldPdo);
    if (isset($cache[$key])) return $cache[$key];
    $columns = spp_character_columns($worldPdo, 'quest_template');
    $pick = function (array $candidates) use ($columns) {
        foreach ($candidates as $candidate) {
            if (isset($columns[$candidate])) return $candidate;
        }
        return null;
    };
    return $cache[$key] = array(
        'entry' => $pick(array('entry', 'Entry')),
        'title' => $pick(array('Title', 'title', 'LogTitle', 'QuestTitle')),
        'level' => $pick(array('QuestLevel', 'questlevel', 'MinLevel', 'Min_Level')),
        'details' => $pick(array('Details', 'details', 'LogDescription', 'logdescription', 'ObjectiveText1', 'Objectives', 'objectives')),
        'objectives_text' => $pick(array('Objectives', 'objectives', 'QuestDescription', 'questdescription')),
        'objective_text_1' => $pick(array('ObjectiveText1', 'objectiveText1', 'ObjectiveText_1')),
        'objective_text_2' => $pick(array('ObjectiveText2', 'objectiveText2', 'ObjectiveText_2')),
        'objective_text_3' => $pick(array('ObjectiveText3', 'objectiveText3', 'ObjectiveText_3')),
        'objective_text_4' => $pick(array('ObjectiveText4', 'objectiveText4', 'ObjectiveText_4')),
        'req_creature_count_1' => $pick(array('ReqCreatureOrGOCount1', 'ReqCreatureOrGOcount1')),
        'req_creature_count_2' => $pick(array('ReqCreatureOrGOCount2', 'ReqCreatureOrGOcount2')),
        'req_creature_count_3' => $pick(array('ReqCreatureOrGOCount3', 'ReqCreatureOrGOcount3')),
        'req_creature_count_4' => $pick(array('ReqCreatureOrGOCount4', 'ReqCreatureOrGOcount4')),
        'req_creature_or_go_id_1' => $pick(array('ReqCreatureOrGOId1', 'ReqCreatureOrGOid1')),
        'req_creature_or_go_id_2' => $pick(array('ReqCreatureOrGOId2', 'ReqCreatureOrGOid2')),
        'req_creature_or_go_id_3' => $pick(array('ReqCreatureOrGOId3', 'ReqCreatureOrGOid3')),
        'req_creature_or_go_id_4' => $pick(array('ReqCreatureOrGOId4', 'ReqCreatureOrGOid4')),
        'req_item_id_1' => $pick(array('ReqItemId1', 'ReqItemid1')),
        'req_item_id_2' => $pick(array('ReqItemId2', 'ReqItemid2')),
        'req_item_id_3' => $pick(array('ReqItemId3', 'ReqItemid3')),
        'req_item_id_4' => $pick(array('ReqItemId4', 'ReqItemid4')),
        'req_item_count_1' => $pick(array('ReqItemCount1', 'ReqItemcount1')),
        'req_item_count_2' => $pick(array('ReqItemCount2', 'ReqItemcount2')),
        'req_item_count_3' => $pick(array('ReqItemCount3', 'ReqItemcount3')),
        'req_item_count_4' => $pick(array('ReqItemCount4', 'ReqItemcount4')),
        'rew_choice_item_1' => $pick(array('RewChoiceItemId1')),
        'rew_choice_item_2' => $pick(array('RewChoiceItemId2')),
        'rew_choice_item_3' => $pick(array('RewChoiceItemId3')),
        'rew_choice_item_4' => $pick(array('RewChoiceItemId4')),
        'rew_choice_item_5' => $pick(array('RewChoiceItemId5')),
        'rew_choice_item_6' => $pick(array('RewChoiceItemId6')),
        'rew_choice_count_1' => $pick(array('RewChoiceItemCount1')),
        'rew_choice_count_2' => $pick(array('RewChoiceItemCount2')),
        'rew_choice_count_3' => $pick(array('RewChoiceItemCount3')),
        'rew_choice_count_4' => $pick(array('RewChoiceItemCount4')),
        'rew_choice_count_5' => $pick(array('RewChoiceItemCount5')),
        'rew_choice_count_6' => $pick(array('RewChoiceItemCount6')),
        'rew_item_1' => $pick(array('RewItemId1')),
        'rew_item_2' => $pick(array('RewItemId2')),
        'rew_item_3' => $pick(array('RewItemId3')),
        'rew_item_4' => $pick(array('RewItemId4')),
        'rew_item_count_1' => $pick(array('RewItemCount1')),
        'rew_item_count_2' => $pick(array('RewItemCount2')),
        'rew_item_count_3' => $pick(array('RewItemCount3')),
        'rew_item_count_4' => $pick(array('RewItemCount4')),
        'rew_money' => $pick(array('RewOrReqMoney', 'RewMoneyMaxLevel', 'RewardMoney')),
    );
}

function spp_character_fetch_quest_meta(PDO $worldPdo, array $questIds) {
    $questIds = array_values(array_unique(array_filter(array_map('intval', $questIds))));
    if (empty($questIds) || !spp_character_table_exists($worldPdo, 'quest_template')) return array();
    $fields = spp_character_quest_template_fields($worldPdo);
    if (!$fields['entry'] || !$fields['title']) return array();
    $select = array(
        '`' . $fields['entry'] . '` AS `entry`',
        '`' . $fields['title'] . '` AS `title`',
    );
    $aliases = array(
        'level' => 'quest_level',
        'details' => 'quest_description',
        'objectives_text' => 'objectives_text',
        'objective_text_1' => 'objective_text_1',
        'objective_text_2' => 'objective_text_2',
        'objective_text_3' => 'objective_text_3',
        'objective_text_4' => 'objective_text_4',
        'req_creature_count_1' => 'req_creature_count_1',
        'req_creature_count_2' => 'req_creature_count_2',
        'req_creature_count_3' => 'req_creature_count_3',
        'req_creature_count_4' => 'req_creature_count_4',
        'req_creature_or_go_id_1' => 'req_creature_or_go_id_1',
        'req_creature_or_go_id_2' => 'req_creature_or_go_id_2',
        'req_creature_or_go_id_3' => 'req_creature_or_go_id_3',
        'req_creature_or_go_id_4' => 'req_creature_or_go_id_4',
        'req_item_id_1' => 'req_item_id_1',
        'req_item_id_2' => 'req_item_id_2',
        'req_item_id_3' => 'req_item_id_3',
        'req_item_id_4' => 'req_item_id_4',
        'req_item_count_1' => 'req_item_count_1',
        'req_item_count_2' => 'req_item_count_2',
        'req_item_count_3' => 'req_item_count_3',
        'req_item_count_4' => 'req_item_count_4',
        'rew_choice_item_1' => 'rew_choice_item_1',
        'rew_choice_item_2' => 'rew_choice_item_2',
        'rew_choice_item_3' => 'rew_choice_item_3',
        'rew_choice_item_4' => 'rew_choice_item_4',
        'rew_choice_item_5' => 'rew_choice_item_5',
        'rew_choice_item_6' => 'rew_choice_item_6',
        'rew_choice_count_1' => 'rew_choice_count_1',
        'rew_choice_count_2' => 'rew_choice_count_2',
        'rew_choice_count_3' => 'rew_choice_count_3',
        'rew_choice_count_4' => 'rew_choice_count_4',
        'rew_choice_count_5' => 'rew_choice_count_5',
        'rew_choice_count_6' => 'rew_choice_count_6',
        'rew_item_1' => 'rew_item_1',
        'rew_item_2' => 'rew_item_2',
        'rew_item_3' => 'rew_item_3',
        'rew_item_4' => 'rew_item_4',
        'rew_item_count_1' => 'rew_item_count_1',
        'rew_item_count_2' => 'rew_item_count_2',
        'rew_item_count_3' => 'rew_item_count_3',
        'rew_item_count_4' => 'rew_item_count_4',
        'rew_money' => 'rew_money',
    );
    foreach ($aliases as $fieldKey => $alias) {
        if (!empty($fields[$fieldKey])) $select[] = '`' . $fields[$fieldKey] . '` AS `' . $alias . '`';
    }
    $placeholders = implode(',', array_fill(0, count($questIds), '?'));
    $stmt = $worldPdo->prepare('SELECT ' . implode(', ', $select) . ' FROM `quest_template` WHERE `' . $fields['entry'] . '` IN (' . $placeholders . ')');
    $stmt->execute($questIds);
    $meta = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $questId = (int)($row['entry'] ?? 0);
        if ($questId <= 0) continue;
        $meta[$questId] = array(
            'title' => trim((string)($row['title'] ?? ('Quest #' . $questId))),
            'quest_level' => isset($row['quest_level']) ? (int)$row['quest_level'] : null,
            'description' => trim((string)($row['quest_description'] ?? '')),
            'objectives_text' => trim((string)($row['objectives_text'] ?? '')),
            'objective_texts' => array(
                trim((string)($row['objective_text_1'] ?? '')),
                trim((string)($row['objective_text_2'] ?? '')),
                trim((string)($row['objective_text_3'] ?? '')),
                trim((string)($row['objective_text_4'] ?? '')),
            ),
            'required_counts' => array(
                max((int)($row['req_creature_count_1'] ?? 0), (int)($row['req_item_count_1'] ?? 0)),
                max((int)($row['req_creature_count_2'] ?? 0), (int)($row['req_item_count_2'] ?? 0)),
                max((int)($row['req_creature_count_3'] ?? 0), (int)($row['req_item_count_3'] ?? 0)),
                max((int)($row['req_creature_count_4'] ?? 0), (int)($row['req_item_count_4'] ?? 0)),
            ),
            'required_entity_ids' => array(
                (int)($row['req_creature_or_go_id_1'] ?? 0),
                (int)($row['req_creature_or_go_id_2'] ?? 0),
                (int)($row['req_creature_or_go_id_3'] ?? 0),
                (int)($row['req_creature_or_go_id_4'] ?? 0),
            ),
            'required_item_ids' => array(
                (int)($row['req_item_id_1'] ?? 0),
                (int)($row['req_item_id_2'] ?? 0),
                (int)($row['req_item_id_3'] ?? 0),
                (int)($row['req_item_id_4'] ?? 0),
            ),
            'reward_choice_ids' => array((int)($row['rew_choice_item_1'] ?? 0), (int)($row['rew_choice_item_2'] ?? 0), (int)($row['rew_choice_item_3'] ?? 0), (int)($row['rew_choice_item_4'] ?? 0), (int)($row['rew_choice_item_5'] ?? 0), (int)($row['rew_choice_item_6'] ?? 0)),
            'reward_choice_counts' => array((int)($row['rew_choice_count_1'] ?? 0), (int)($row['rew_choice_count_2'] ?? 0), (int)($row['rew_choice_count_3'] ?? 0), (int)($row['rew_choice_count_4'] ?? 0), (int)($row['rew_choice_count_5'] ?? 0), (int)($row['rew_choice_count_6'] ?? 0)),
            'reward_item_ids' => array((int)($row['rew_item_1'] ?? 0), (int)($row['rew_item_2'] ?? 0), (int)($row['rew_item_3'] ?? 0), (int)($row['rew_item_4'] ?? 0)),
            'reward_item_counts' => array((int)($row['rew_item_count_1'] ?? 0), (int)($row['rew_item_count_2'] ?? 0), (int)($row['rew_item_count_3'] ?? 0), (int)($row['rew_item_count_4'] ?? 0)),
            'reward_money' => (int)($row['rew_money'] ?? 0),
        );
    }
    return $meta;
}

function spp_character_build_quest_progress(array $row) {
    $parts = array();
    for ($i = 1; $i <= 4; $i++) {
        $mob = (int)($row['mobcount' . $i] ?? 0);
        $item = (int)($row['itemcount' . $i] ?? 0);
        if ($mob > 0) $parts[] = 'Mob ' . $i . ': ' . $mob;
        if ($item > 0) $parts[] = 'Item ' . $i . ': ' . $item;
    }
    if (!empty($row['explored'])) $parts[] = 'Explored';
    if (!empty($row['timer'])) $parts[] = 'Timed';
    return $parts;
}

function spp_character_fetch_item_summaries(PDO $worldPdo, PDO $armoryPdo, array $itemIds) {
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
    if (empty($itemIds) || !spp_character_table_exists($worldPdo, 'item_template')) return array();
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stmt = $worldPdo->prepare('SELECT `entry`, `name`, `Quality`, `displayid` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
    $stmt->execute($itemIds);
    $itemMap = array();
    $displayIds = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
        $itemMap[(int)$itemRow['entry']] = $itemRow;
        if (!empty($itemRow['displayid'])) $displayIds[(int)$itemRow['displayid']] = true;
    }
    $iconMap = array();
    if (!empty($displayIds) && spp_character_table_exists($armoryPdo, 'dbc_itemdisplayinfo')) {
        $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
        $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
        $stmt->execute(array_keys($displayIds));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $displayRow) {
            $iconMap[(int)$displayRow['id']] = (string)$displayRow['name'];
        }
    }
    $items = array();
    foreach ($itemMap as $entry => $itemRow) {
        $items[$entry] = array(
            'entry' => (int)$entry,
            'name' => (string)$itemRow['name'],
            'quality' => (int)$itemRow['Quality'],
            'icon' => spp_character_icon_url($iconMap[(int)($itemRow['displayid'] ?? 0)] ?? ''),
        );
    }
    return $items;
}

function spp_character_fetch_quest_objective_names(PDO $worldPdo, array $entityIds, array $itemIds) {
    $names = array('entities' => array(), 'items' => array());
    $entityIds = array_values(array_unique(array_filter(array_map('intval', $entityIds))));
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));

    $creatureIds = array();
    $gameobjectIds = array();
    foreach ($entityIds as $entityId) {
        if ($entityId > 0) $creatureIds[] = $entityId;
        if ($entityId < 0) $gameobjectIds[] = abs($entityId);
    }

    if (!empty($creatureIds) && spp_character_table_exists($worldPdo, 'creature_template')) {
        $placeholders = implode(',', array_fill(0, count($creatureIds), '?'));
        $stmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `creature_template` WHERE `entry` IN (' . $placeholders . ')');
        $stmt->execute($creatureIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names['entities'][(int)$row['entry']] = trim((string)($row['name'] ?? ''));
        }
    }

    if (!empty($gameobjectIds) && spp_character_table_exists($worldPdo, 'gameobject_template')) {
        $placeholders = implode(',', array_fill(0, count($gameobjectIds), '?'));
        $stmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `gameobject_template` WHERE `entry` IN (' . $placeholders . ')');
        $stmt->execute($gameobjectIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names['entities'][-(int)$row['entry']] = trim((string)($row['name'] ?? ''));
        }
    }

    if (!empty($itemIds) && spp_character_table_exists($worldPdo, 'item_template')) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
        $stmt->execute($itemIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names['items'][(int)$row['entry']] = trim((string)($row['name'] ?? ''));
        }
    }

    return $names;
}

function spp_character_build_quest_objectives(array $meta, array $row) {
    $objectives = array();
    $objectiveTexts = $meta['objective_texts'] ?? array();
    $requiredCounts = $meta['required_counts'] ?? array();
    $requiredEntityIds = $meta['required_entity_ids'] ?? array();
    $requiredItemIds = $meta['required_item_ids'] ?? array();
    $objectiveNames = $meta['objective_names'] ?? array('entities' => array(), 'items' => array());
    for ($i = 0; $i < 4; $i++) {
        $label = trim((string)($objectiveTexts[$i] ?? ''));
        $target = (int)($requiredCounts[$i] ?? 0);
        $current = max((int)($row['mobcount' . ($i + 1)] ?? 0), (int)($row['itemcount' . ($i + 1)] ?? 0));
        if ($label !== '') {
            $objectives[] = $target > 0 ? ($label . ': ' . $current . '/' . $target) : $label;
            continue;
        }
        $itemId = (int)($requiredItemIds[$i] ?? 0);
        if ($itemId > 0) {
            $itemName = trim((string)($objectiveNames['items'][$itemId] ?? ''));
            if ($itemName !== '') {
                $objectives[] = ($target > 0 ? 'Collect ' . $itemName . ': ' . $current . '/' . $target : 'Collect ' . $itemName);
                continue;
            }
        }
        $entityId = (int)($requiredEntityIds[$i] ?? 0);
        if ($entityId !== 0) {
            $entityName = trim((string)($objectiveNames['entities'][$entityId] ?? ''));
            if ($entityName !== '') {
                $verb = $entityId < 0 ? 'Use' : 'Kill';
                $objectives[] = ($target > 0 ? $verb . ' ' . $entityName . ': ' . $current . '/' . $target : $verb . ' ' . $entityName);
                continue;
            }
        }
        if ($target > 0) {
            $objectives[] = 'Objective ' . ($i + 1) . ': ' . $current . '/' . $target;
        }
    }
    if (empty($objectives) && !empty($meta['objectives_text'])) {
        $objectives[] = trim((string)$meta['objectives_text']);
    }
    if (empty($objectives)) {
        $objectives = spp_character_build_quest_progress($row);
    }
    return array_values(array_filter(array_map('trim', $objectives)));
}

function spp_character_build_quest_rewards(array $meta, array $itemSummaries) {
    $rewards = array('choice' => array(), 'guaranteed' => array(), 'money' => 0);
    foreach (($meta['reward_choice_ids'] ?? array()) as $index => $itemId) {
        $itemId = (int)$itemId;
        if ($itemId <= 0 || empty($itemSummaries[$itemId])) continue;
        $rewards['choice'][] = $itemSummaries[$itemId] + array('count' => max(1, (int)(($meta['reward_choice_counts'][$index] ?? 0))));
    }
    foreach (($meta['reward_item_ids'] ?? array()) as $index => $itemId) {
        $itemId = (int)$itemId;
        if ($itemId <= 0 || empty($itemSummaries[$itemId])) continue;
        $rewards['guaranteed'][] = $itemSummaries[$itemId] + array('count' => max(1, (int)(($meta['reward_item_counts'][$index] ?? 0))));
    }
    $rewards['money'] = (int)($meta['reward_money'] ?? 0);
    return $rewards;
}

function spp_character_format_quest_status(array $row) {
    if (!empty($row['rewarded'])) return 'Completed';
    $status = (int)($row['status'] ?? 0);
    if ($status >= 1) return 'In Progress';
    return 'Accepted';
}

function spp_character_render_quest_text($text, $fallbackName = 'adventurer') {
    $text = (string)$text;
    if (trim($text) === '') return '';
    $replacements = array(
        '$B' => "\n\n",
        '$b' => "\n",
        '$N' => $fallbackName,
        '$n' => $fallbackName,
        '$C' => 'adventurer',
        '$c' => 'adventurer',
        '$R' => '',
        '$r' => '',
    );
    $text = strtr($text, $replacements);
    $text = preg_replace('/\|c[0-9a-fA-F]{8}/', '', $text);
    $text = str_replace('|r', '', $text);
    $text = preg_replace("/\r\n?/", "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", trim($text));
    return nl2br(htmlspecialchars($text), false);
}

function spp_character_rep_rank($standing) {
    $lengths = array(36000, 3000, 3000, 3000, 6000, 12000, 21000, 1000);
    $labels = array('Hated', 'Hostile', 'Unfriendly', 'Neutral', 'Friendly', 'Honored', 'Revered', 'Exalted');
    $limit = 42999;
    $standing = (int)$standing;
    for ($rank = count($lengths) - 1; $rank >= 0; --$rank) {
        $limit -= $lengths[$rank];
        if ($standing >= $limit) {
            return array('label' => $labels[$rank], 'value' => $standing - $limit, 'max' => $lengths[$rank]);
        }
    }
    return array('label' => 'Hated', 'value' => 0, 'max' => $lengths[0]);
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$tab = strtolower(trim((string)($_GET['tab'] ?? 'overview')));
$tabs = array('overview', 'talents', 'reputation', 'skills', 'professions', 'quest log', 'achievements');
if (!in_array($tab, $tabs, true)) $tab = 'overview';

$classNames = array(1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest', 6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid');
$raceNames = array(1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead', 6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei');
$slotNames = array(0 => 'Head', 1 => 'Neck', 2 => 'Shoulder', 3 => 'Shirt', 4 => 'Chest', 5 => 'Waist', 6 => 'Legs', 7 => 'Feet', 8 => 'Wrist', 9 => 'Hands', 10 => 'Finger 1', 11 => 'Finger 2', 12 => 'Trinket 1', 13 => 'Trinket 2', 14 => 'Back', 15 => 'Main Hand', 16 => 'Off Hand', 17 => 'Ranged', 18 => 'Tabard');
$characterName = trim((string)($_GET['character'] ?? ''));
$characterGuid = isset($_GET['guid']) ? (int)$_GET['guid'] : 0;
$pageError = '';
$character = null;
$stats = array();
$equipment = array();
$talentTabs = array();
$reputations = array();
$reputationSections = array();
$skillsByCategory = array();
$professionsByCategory = array();
$professionRecipesBySkillId = array();
$knownCharacterSpells = array();
$achievementSummary = array('supported' => false, 'count' => 0, 'points' => 0, 'recent' => array(), 'groups' => array());
$recentGear = array();
$activeQuestLog = array();
$completedQuestHistory = array();
$lastInstance = '';
$lastInstanceDate = 0;
$currentMapName = 'Unknown zone';
$displayLocation = 'Unknown zone';
$combatHighlights = array();
$factionIcon = '';

builddiv_start(1, 'Character Profile', 0);

if (!is_array($realmMap) || !isset($realmMap[$realmId])) {
    $pageError = 'The requested realm is unavailable.';
} elseif ($characterName === '' && $characterGuid <= 0) {
    $pageError = 'No character was selected.';
} else {
    try {
        $charsPdo = spp_get_pdo('chars', $realmId);
        $worldPdo = spp_get_pdo('world', $realmId);
        $armoryPdo = spp_get_pdo('armory', $realmId);
        $characterColumns = spp_character_columns($charsPdo, 'characters');
        $selectColumns = array('guid', 'name', 'race', 'class', 'gender', 'level', 'zone', 'map', 'online', 'totaltime', 'leveltime');
        foreach (array('health', 'power1', 'power2', 'stored_honorable_kills', 'stored_honor_rating', 'honor_highest_rank', 'totalKills', 'totalHonorPoints') as $columnName) {
            if (isset($characterColumns[$columnName])) $selectColumns[] = $columnName;
        }
        $sql = 'SELECT c.`' . implode('`, c.`', $selectColumns) . '`, gm.`guildid`, g.`name` AS `guild_name` FROM `characters` c LEFT JOIN `guild_member` gm ON gm.`guid` = c.`guid` LEFT JOIN `guild` g ON g.`guildid` = gm.`guildid`';
        $stmt = $charsPdo->prepare($sql . ($characterGuid > 0 ? ' WHERE c.`guid` = ?' : ' WHERE c.`name` = ?') . ' LIMIT 1');
        $stmt->execute(array($characterGuid > 0 ? $characterGuid : $characterName));
        $character = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$character) throw new RuntimeException('Character not found.');

        $characterGuid = (int)$character['guid'];
        $characterName = (string)$character['name'];

        if (spp_character_table_exists($charsPdo, 'character_stats')) {
            $stmt = $charsPdo->prepare('SELECT * FROM `character_stats` WHERE `guid` = ? LIMIT 1');
            $stmt->execute(array($characterGuid));
            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
        }

        if (spp_character_table_exists($charsPdo, 'character_inventory')) {
            $stmt = $charsPdo->prepare('SELECT `slot`, `item`, `item_template` FROM `character_inventory` WHERE `guid` = ? AND `bag` = 0 AND `slot` BETWEEN 0 AND 18 ORDER BY `slot` ASC');
            $stmt->execute(array($characterGuid));
            $inventoryRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $itemIds = array();
            foreach ($inventoryRows as $row) $itemIds[(int)$row['item_template']] = true;
            $itemMap = array();
            $iconMap = array();
            if (!empty($itemIds)) {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                $stmt = $worldPdo->prepare('SELECT `entry`, `name`, `Quality`, `ItemLevel`, `RequiredLevel`, `displayid` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($itemIds));
                $displayIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
                    $itemMap[(int)$itemRow['entry']] = $itemRow;
                    if (!empty($itemRow['displayid'])) $displayIds[(int)$itemRow['displayid']] = true;
                }
                if (!empty($displayIds)) {
                    $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
                    $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
                    $stmt->execute(array_keys($displayIds));
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) $iconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
            }
            foreach ($inventoryRows as $row) {
                $entry = (int)$row['item_template'];
                if (!isset($itemMap[$entry])) continue;
                $slotId = (int)$row['slot'];
                $itemRow = $itemMap[$entry];
                $equipment[$slotId] = array(
                    'slot_name' => $slotNames[$slotId] ?? ('Slot ' . $slotId),
                    'item_guid' => isset($row['item']) ? (int)$row['item'] : 0,
                    'entry' => $entry,
                    'name' => (string)$itemRow['name'],
                    'quality' => (int)$itemRow['Quality'],
                    'item_level' => (int)$itemRow['ItemLevel'],
                    'required_level' => (int)$itemRow['RequiredLevel'],
                    'icon' => spp_character_icon_url($iconMap[(int)$itemRow['displayid']] ?? ''),
                );
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_queststatus')) {
            $stmt = $charsPdo->prepare('SELECT * FROM `character_queststatus` WHERE `guid` = ? ORDER BY `rewarded` ASC, `quest` ASC');
            $stmt->execute(array($characterGuid));
            $questRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $questIds = array();
            foreach ($questRows as $questRow) {
                $questId = (int)($questRow['quest'] ?? 0);
                if ($questId > 0) $questIds[] = $questId;
            }
            $questMeta = spp_character_fetch_quest_meta($worldPdo, $questIds);
            $questRewardItemIds = array();
            $questObjectiveEntityIds = array();
            $questObjectiveItemIds = array();
            foreach ($questMeta as $meta) {
                foreach (($meta['reward_choice_ids'] ?? array()) as $itemId) {
                    if ((int)$itemId > 0) $questRewardItemIds[(int)$itemId] = true;
                }
                foreach (($meta['reward_item_ids'] ?? array()) as $itemId) {
                    if ((int)$itemId > 0) $questRewardItemIds[(int)$itemId] = true;
                }
                foreach (($meta['required_entity_ids'] ?? array()) as $entityId) {
                    if ((int)$entityId !== 0) $questObjectiveEntityIds[(int)$entityId] = true;
                }
                foreach (($meta['required_item_ids'] ?? array()) as $itemId) {
                    if ((int)$itemId > 0) $questObjectiveItemIds[(int)$itemId] = true;
                }
            }
            $questRewardItems = spp_character_fetch_item_summaries($worldPdo, $armoryPdo, array_keys($questRewardItemIds));
            $questObjectiveNames = spp_character_fetch_quest_objective_names($worldPdo, array_keys($questObjectiveEntityIds), array_keys($questObjectiveItemIds));
            foreach ($questRows as $questRow) {
                $questId = (int)($questRow['quest'] ?? 0);
                if ($questId <= 0) continue;
                $meta = $questMeta[$questId] ?? array();
                $meta['objective_names'] = $questObjectiveNames;
                $entry = array(
                    'quest' => $questId,
                    'title' => $meta['title'] ?? ('Quest #' . $questId),
                    'quest_level' => $meta['quest_level'] ?? null,
                    'description' => $meta['description'] ?? '',
                    'status_label' => spp_character_format_quest_status($questRow),
                    'progress_parts' => spp_character_build_quest_objectives($meta, $questRow),
                    'rewards' => spp_character_build_quest_rewards($meta, $questRewardItems),
                );
                if (!empty($questRow['rewarded'])) {
                    $completedQuestHistory[] = $entry;
                } else {
                    $activeQuestLog[] = $entry;
                }
            }
            $completedQuestHistory = array_slice(array_reverse($completedQuestHistory), 0, 8);
            $activeQuestLog = array_slice($activeQuestLog, 0, 50);
        }

        $talentMetaPdo = spp_character_pick_dbc_pdo(array($worldPdo, $armoryPdo), 'dbc_talenttab') ?: $armoryPdo;
        $talentDataPdo = spp_character_pick_dbc_pdo(array($worldPdo, $armoryPdo), 'dbc_talent') ?: $talentMetaPdo;
        $spellIconPdo = spp_character_pick_dbc_pdo(array($talentMetaPdo, $worldPdo, $armoryPdo), 'dbc_spellicon') ?: $talentMetaPdo;
        $spellIconFields = spp_character_spellicon_fields($spellIconPdo);
        $talentTabSql = 'SELECT tt.`id`, tt.`name`, tt.`tab_number`, NULL AS `icon_name` FROM `dbc_talenttab` tt';
        if ($spellIconFields['id'] && $spellIconFields['name']) {
            $talentTabSql = 'SELECT tt.`id`, tt.`name`, tt.`tab_number`, si.`' . $spellIconFields['name'] . '` AS `icon_name` ' .
                'FROM `dbc_talenttab` tt LEFT JOIN `dbc_spellicon` si ON si.`' . $spellIconFields['id'] . '` = tt.`SpellIconID` ' .
                'WHERE (tt.`refmask_chrclasses` & ?) <> 0 ORDER BY tt.`tab_number` ASC';
        } else {
            $talentTabSql .= ' WHERE (tt.`refmask_chrclasses` & ?) <> 0 ORDER BY tt.`tab_number` ASC';
        }
        $stmt = $talentMetaPdo->prepare($talentTabSql);
        $stmt->execute(array(1 << ((int)$character['class'] - 1)));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tabRow) {
            $tabId = (int)$tabRow['id'];
            $talentTabs[$tabId] = array('name' => (string)$tabRow['name'], 'points' => 0, 'icon' => spp_character_icon_url($tabRow['icon_name'] ?? ''));
        }
        if (!empty($talentTabs)) {
            foreach ($talentTabs as $tabId => $tabMeta) {
                $talentTabs[$tabId]['points'] = spp_character_talent_points($charsPdo, $talentDataPdo, $characterGuid, $tabId);
            }
        }
        if (spp_character_table_exists($charsPdo, 'character_reputation')) {
            $reputationSectionIds = array(
                1118 => 'Classic',
                469 => 'Alliance',
                891 => 'Alliance Forces',
                1037 => 'Classic',
                67 => 'Horde',
                892 => 'Horde Forces',
                1052 => 'Classic',
                936 => 'Shattrath City',
                1117 => 'Classic',
                169 => 'Steamwheedle Cartel',
                980 => 'Outland',
                1097 => 'Classic',
                0 => 'Other',
            );
            $sectionFactionIds = array_keys($reputationSectionIds);
            $stmt = $charsPdo->prepare('SELECT `faction`, `standing`, `flags` FROM `character_reputation` WHERE `guid` = ? AND (`flags` & 1 = 1)' . (!empty($sectionFactionIds) ? ' AND `faction` NOT IN (' . implode(', ', array_map('intval', $sectionFactionIds)) . ')' : ''));
            $stmt->execute(array($characterGuid));
            $repRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $factionIds = array();
            foreach ($repRows as $row) $factionIds[(int)$row['faction']] = true;
            $factionMap = array();
            $sectionNameMap = $reputationSectionIds;
            if (!empty($factionIds)) {
                $placeholders = implode(',', array_fill(0, count($factionIds), '?'));
                $factionColumns = spp_character_columns($armoryPdo, 'dbc_faction');
                $selectParts = array('`id`', '`name`', '`description`');
                if (isset($factionColumns['ref_faction'])) $selectParts[] = '`ref_faction`';
                for ($idx = 0; $idx <= 4; $idx++) {
                    $raceField = 'base_ref_chrraces_' . $idx;
                    $modifierField = 'base_modifier_' . $idx;
                    if (isset($factionColumns[$raceField])) $selectParts[] = '`' . $raceField . '`';
                    if (isset($factionColumns[$modifierField])) $selectParts[] = '`' . $modifierField . '`';
                }
                $stmt = $armoryPdo->prepare('SELECT ' . implode(', ', $selectParts) . ' FROM `dbc_faction` WHERE `id` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($factionIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $factionMap[(int)$row['id']] = $row;
                $sectionLookupIds = array();
                foreach ($factionMap as $row) {
                    $sectionId = (int)($row['ref_faction'] ?? 0);
                    if ($sectionId > 0) $sectionLookupIds[$sectionId] = true;
                }
                $missingSectionIds = array();
                foreach (array_keys($sectionLookupIds) as $sectionId) {
                    if (!isset($sectionNameMap[$sectionId])) $missingSectionIds[] = $sectionId;
                }
                if (!empty($missingSectionIds)) {
                    $sectionPlaceholders = implode(',', array_fill(0, count($missingSectionIds), '?'));
                    $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_faction` WHERE `id` IN (' . $sectionPlaceholders . ')');
                    $stmt->execute($missingSectionIds);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sectionRow) {
                        $sectionNameMap[(int)$sectionRow['id']] = trim((string)$sectionRow['name']) !== '' ? (string)$sectionRow['name'] : ('Group ' . (int)$sectionRow['id']);
                    }
                }
            }
            foreach ($repRows as $row) {
                $faction = $factionMap[(int)$row['faction']] ?? null;
                if (!$faction) continue;
                $standing = (int)$row['standing'];
                for ($idx = 0; $idx <= 4; $idx++) {
                    $raceField = 'base_ref_chrraces_' . $idx;
                    $modifierField = 'base_modifier_' . $idx;
                    if (!isset($faction[$raceField], $faction[$modifierField])) continue;
                    if (((int)$faction[$raceField]) & (1 << ((int)$character['race'] - 1))) {
                        $standing += (int)$faction[$modifierField];
                        break;
                    }
                }
                $rank = spp_character_rep_rank($standing);
                $sectionId = (int)($faction['ref_faction'] ?? 0);
                $sectionLabel = $sectionNameMap[$sectionId] ?? ($sectionId > 0 ? ('Group ' . $sectionId) : 'Other');
                $entry = array(
                    'name' => (string)$faction['name'],
                    'description' => trim((string)($faction['description'] ?? '')),
                    'label' => $rank['label'],
                    'tier' => spp_character_reputation_tier($rank['label']),
                    'value' => $rank['value'],
                    'max' => $rank['max'],
                    'standing' => $standing,
                    'percent' => $rank['max'] > 0 ? min(100, max(0, round(($rank['value'] / $rank['max']) * 100))) : 0,
                    'section_id' => $sectionId,
                    'section' => $sectionLabel,
                );
                $reputations[] = $entry;
                if (!isset($reputationSections[$sectionLabel])) $reputationSections[$sectionLabel] = array();
                $reputationSections[$sectionLabel][] = $entry;
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_skills')) {
            $stmt = $charsPdo->prepare('SELECT `skill`, `value`, `max` FROM `character_skills` WHERE `guid` = ?');
            $stmt->execute(array($characterGuid));
            $skillRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $skillIds = array();
            foreach ($skillRows as $row) $skillIds[(int)$row['skill']] = true;
            $skillMap = array();
            if (!empty($skillIds)) {
                $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
                $spellIconFields = spp_character_spellicon_fields($armoryPdo);
                $skillSql = 'SELECT sl.`id`, sl.`name`, sl.`description`, sc.`name` AS `category_name`, NULL AS `icon_name` ' .
                    'FROM `dbc_skillline` sl LEFT JOIN `dbc_skilllinecategory` sc ON sc.`id` = sl.`ref_skilllinecategory` ';
                if ($spellIconFields['id'] && $spellIconFields['name']) {
                    $skillSql = 'SELECT sl.`id`, sl.`name`, sl.`description`, sc.`name` AS `category_name`, si.`' . $spellIconFields['name'] . '` AS `icon_name` ' .
                        'FROM `dbc_skillline` sl LEFT JOIN `dbc_skilllinecategory` sc ON sc.`id` = sl.`ref_skilllinecategory` ' .
                        'LEFT JOIN `dbc_spellicon` si ON si.`' . $spellIconFields['id'] . '` = sl.`ref_spellicon` ';
                }
                $skillSql .= 'WHERE sl.`id` IN (' . $placeholders . ')';
                $stmt = $armoryPdo->prepare($skillSql);
                $stmt->execute(array_keys($skillIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $skillMap[(int)$row['id']] = $row;
            }
            foreach ($skillRows as $row) {
                $skillId = (int)$row['skill'];
                if (!isset($skillMap[$skillId])) continue;
                $meta = $skillMap[$skillId];
                $category = trim((string)($meta['category_name'] ?? 'Other'));
                if ($category === '') $category = 'Other';
                $categoryKey = strtolower($category);
                if (strpos($categoryKey, 'class skill') !== false) continue;
                $target =& $skillsByCategory;
                if (strpos($categoryKey, 'profession') !== false || strpos($categoryKey, 'secondary') !== false) {
                    $target =& $professionsByCategory;
                }
                $entry = spp_character_skill_entry($meta, $row, array(
                    'race' => (int)$character['race'],
                    'gender' => (int)$character['gender'],
                ));
                if ($entry === null) {
                    unset($target);
                    continue;
                }
                if (!isset($target[$category])) $target[$category] = array();
                $target[$category][] = $entry;
                unset($target);
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_spell') && !empty($professionsByCategory)) {
            $professionSkillIds = array();
            foreach ($professionsByCategory as $categorySkills) {
                foreach ($categorySkills as $skill) {
                    $skillId = (int)($skill['skill_id'] ?? 0);
                    if ($skillId > 0) $professionSkillIds[$skillId] = true;
                }
            }

            if (!empty($professionSkillIds)) {
                $stmt = $charsPdo->prepare('SELECT `spell` FROM `character_spell` WHERE `guid` = ? AND `disabled` = 0');
                $stmt->execute(array($characterGuid));
                $learnedSpellIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $spellId = (int)($row['spell'] ?? 0);
                    if ($spellId > 0) $learnedSpellIds[$spellId] = true;
                }
                $knownCharacterSpells = $learnedSpellIds;

                if (!empty($learnedSpellIds)) {
                    $spellPlaceholders = implode(',', array_fill(0, count($learnedSpellIds), '?'));
                    $skillPlaceholders = implode(',', array_fill(0, count($professionSkillIds), '?'));
                    $recipeStmt = $worldPdo->prepare(
                        'SELECT `entry`, `name`, `Quality`, `RequiredSkill`, `RequiredSkillRank`, `RequiredReputationFaction`, `RequiredReputationRank`, `displayid`, `spellid_1` ' .
                        'FROM `item_template` ' .
                        'WHERE `class` = 9 AND `RequiredSkill` IN (' . $skillPlaceholders . ') ' .
                        'AND `spellid_1` IN (' . $spellPlaceholders . ')'
                    );
                    $recipeStmt->execute(array_merge(array_keys($professionSkillIds), array_keys($learnedSpellIds)));
                    $recipeRows = $recipeStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($recipeRows)) {
                        $recipeIds = array();
                        $displayIds = array();
                        $repFactionIds = array();
                        foreach ($recipeRows as $recipeRow) {
                            $entryId = (int)$recipeRow['entry'];
                            $recipeIds[$entryId] = true;
                            if (!empty($recipeRow['displayid'])) $displayIds[(int)$recipeRow['displayid']] = true;
                            if (!empty($recipeRow['RequiredReputationFaction'])) $repFactionIds[(int)$recipeRow['RequiredReputationFaction']] = true;
                        }

                        $recipeIconMap = array();
                        if (!empty($displayIds)) {
                            $displayPlaceholders = implode(',', array_fill(0, count($displayIds), '?'));
                            $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $displayPlaceholders . ')');
                            $iconStmt->execute(array_keys($displayIds));
                            foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                                $recipeIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                            }
                        }

                        $repFactionNames = array();
                        if (!empty($repFactionIds)) {
                            $repPlaceholders = implode(',', array_fill(0, count($repFactionIds), '?'));
                            $repStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_faction` WHERE `id` IN (' . $repPlaceholders . ')');
                            $repStmt->execute(array_keys($repFactionIds));
                            foreach ($repStmt->fetchAll(PDO::FETCH_ASSOC) as $repRow) {
                                $repFactionNames[(int)$repRow['id']] = trim((string)$repRow['name']);
                            }
                        }

                        $recipeIdList = array_keys($recipeIds);
                        $recipePlaceholders = implode(',', array_fill(0, count($recipeIdList), '?'));

                        $vendorRecipeIds = array();
                        $vendorStmt = $worldPdo->prepare(
                            'SELECT DISTINCT `item` FROM `npc_vendor` WHERE `item` IN (' . $recipePlaceholders . ') ' .
                            'UNION SELECT DISTINCT `item` FROM `npc_vendor_template` WHERE `item` IN (' . $recipePlaceholders . ')'
                        );
                        $vendorStmt->execute(array_merge($recipeIdList, $recipeIdList));
                        foreach ($vendorStmt->fetchAll(PDO::FETCH_COLUMN) as $vendorId) $vendorRecipeIds[(int)$vendorId] = true;

                        $lootRecipeIds = array();
                        $lootTables = array('creature_loot_template', 'reference_loot_template', 'gameobject_loot_template', 'fishing_loot_template', 'disenchant_loot_template');
                        foreach ($lootTables as $lootTable) {
                            if (!spp_character_table_exists($worldPdo, $lootTable)) continue;
                            $lootStmt = $worldPdo->prepare('SELECT DISTINCT `item` FROM `' . $lootTable . '` WHERE `item` IN (' . $recipePlaceholders . ')');
                            $lootStmt->execute($recipeIdList);
                            foreach ($lootStmt->fetchAll(PDO::FETCH_COLUMN) as $lootId) $lootRecipeIds[(int)$lootId] = true;
                        }

                        $questRecipeIds = array();
                        if (spp_character_table_exists($worldPdo, 'quest_template')) {
                            $questRewardFields = array(
                                'RewChoiceItemId1', 'RewChoiceItemId2', 'RewChoiceItemId3', 'RewChoiceItemId4', 'RewChoiceItemId5', 'RewChoiceItemId6',
                                'RewItemId1', 'RewItemId2', 'RewItemId3', 'RewItemId4',
                            );
                            $questConditions = array();
                            $questParams = array();
                            foreach ($questRewardFields as $field) {
                                $questConditions[] = '`' . $field . '` IN (' . $recipePlaceholders . ')';
                                $questParams = array_merge($questParams, $recipeIdList);
                            }
                            $questStmt = $worldPdo->prepare('SELECT ' . implode(', ', array_map(function ($field) {
                                return '`' . $field . '`';
                            }, $questRewardFields)) . ' FROM `quest_template` WHERE ' . implode(' OR ', $questConditions));
                            $questStmt->execute($questParams);
                            foreach ($questStmt->fetchAll(PDO::FETCH_ASSOC) as $questRow) {
                                foreach ($questRewardFields as $field) {
                                    $itemId = (int)($questRow[$field] ?? 0);
                                    if ($itemId > 0) $questRecipeIds[$itemId] = true;
                                }
                            }
                        }

                        foreach ($recipeRows as $recipeRow) {
                            $skillId = (int)$recipeRow['RequiredSkill'];
                            if ($skillId <= 0) continue;
                            $entryId = (int)$recipeRow['entry'];
                            $quality = (int)$recipeRow['Quality'];
                            $requiredRank = (int)$recipeRow['RequiredSkillRank'];
                            $repFactionId = (int)$recipeRow['RequiredReputationFaction'];
                            $repRank = (int)$recipeRow['RequiredReputationRank'];
                            $isFactionRecipe = $repFactionId > 0 || $repRank > 0;
                            $isLootRecipe = isset($lootRecipeIds[$entryId]);
                            $isVendorRecipe = isset($vendorRecipeIds[$entryId]);
                            $isQuestRecipe = isset($questRecipeIds[$entryId]);
                            $isRareDrop = $isLootRecipe && !$isVendorRecipe && !$isQuestRecipe;
                            $displayName = spp_character_recipe_display_name((string)$recipeRow['name']);
                            $tags = array('all');
                            if ($isFactionRecipe) $tags[] = 'faction';
                            if ($isRareDrop) $tags[] = 'rare-drop';
                            if ($requiredRank >= 300) $tags[] = 'endgame';
                            if (stripos($displayName, 'flask') !== false || stripos((string)$recipeRow['name'], 'flask') !== false) $tags[] = 'flask';

                            $sourceParts = array();
                            if ($isFactionRecipe) {
                                $repLabel = $repFactionNames[$repFactionId] ?? ('Faction #' . $repFactionId);
                                $sourceParts[] = 'Rep: ' . $repLabel;
                            }
                            if ($isRareDrop) {
                                $sourceParts[] = 'Rare Drop';
                            } elseif ($isVendorRecipe) {
                                $sourceParts[] = 'Vendor';
                            } elseif ($isQuestRecipe) {
                                $sourceParts[] = 'Quest';
                            }
                            if ($requiredRank > 0) $sourceParts[] = 'Req ' . $requiredRank;

                            if (!isset($professionRecipesBySkillId[$skillId])) $professionRecipesBySkillId[$skillId] = array();
                            $professionRecipesBySkillId[$skillId][] = array(
                                'entry' => $entryId,
                                'name' => $displayName,
                                'full_name' => (string)$recipeRow['name'],
                                'quality' => $quality,
                                'icon' => spp_character_icon_url($recipeIconMap[(int)$recipeRow['displayid']] ?? ''),
                                'required_rank' => $requiredRank,
                                'source' => implode(' • ', $sourceParts),
                                'tags' => $tags,
                                'tag_map' => array_fill_keys($tags, true),
                            );
                        }

                        foreach ($professionRecipesBySkillId as &$recipeList) {
                            usort($recipeList, function ($left, $right) {
                                $leftWeight = (isset($left['tag_map']['faction']) ? 5 : 0) + (isset($left['tag_map']['rare-drop']) ? 4 : 0) + (isset($left['tag_map']['flask']) ? 3 : 0) + (isset($left['tag_map']['endgame']) ? 2 : 0);
                                $rightWeight = (isset($right['tag_map']['faction']) ? 5 : 0) + (isset($right['tag_map']['rare-drop']) ? 4 : 0) + (isset($right['tag_map']['flask']) ? 3 : 0) + (isset($right['tag_map']['endgame']) ? 2 : 0);
                                if ($leftWeight !== $rightWeight) return $rightWeight <=> $leftWeight;
                                if ((int)$left['required_rank'] !== (int)$right['required_rank']) return (int)$right['required_rank'] <=> (int)$left['required_rank'];
                                return strcasecmp((string)$left['name'], (string)$right['name']);
                            });
                        }
                        unset($recipeList);

                        foreach ($professionsByCategory as &$categorySkills) {
                            foreach ($categorySkills as &$skill) {
                                $skillId = (int)($skill['skill_id'] ?? 0);
                                $skill['recipes'] = $professionRecipesBySkillId[$skillId] ?? array();
                                $skill['specializations'] = spp_character_profession_specializations((string)($skill['name'] ?? ''), $knownCharacterSpells);
                                $skill['recipe_filters'] = array();
                                if (!empty($skill['recipes'])) {
                                    $filterCounts = array('all' => count($skill['recipes']), 'faction' => 0, 'rare-drop' => 0, 'endgame' => 0, 'flask' => 0);
                                    foreach ($skill['recipes'] as $recipe) {
                                        foreach (array('faction', 'rare-drop', 'endgame', 'flask') as $filterKey) {
                                            if (isset($recipe['tag_map'][$filterKey])) $filterCounts[$filterKey]++;
                                        }
                                    }
                                    foreach (spp_character_recipe_filter_labels() as $filterKey => $filterLabel) {
                                        if ($filterKey === 'all' || !empty($filterCounts[$filterKey])) {
                                            $skill['recipe_filters'][] = array(
                                                'key' => $filterKey,
                                                'label' => $filterLabel,
                                                'count' => $filterCounts[$filterKey] ?? 0,
                                            );
                                        }
                                    }
                                }
                            }
                            unset($skill);
                        }
                        unset($categorySkills);
                    }
                }
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_achievement')) {
            $achievementSourcePdo = null;
            $achievementQuerySql = '';
            $achievementIconIdField = '';
            $achievementIdField = 'id';
            $achievementNameField = 'name';
            $achievementDescriptionField = 'description';
            $achievementPointsField = 'points';
            $achievementCategoryField = 'category_id';
            $achievementCategoryMap = array();

            if (spp_character_table_exists($worldPdo, 'achievement_dbc')) {
                $achievementSourcePdo = $worldPdo;
                $achievementQuerySql =
                    'SELECT a.`ID` AS `id`, a.`Title_Lang_enUS` AS `name`, a.`Description_Lang_enUS` AS `description`, ' .
                    'a.`Points` AS `points`, a.`Category` AS `category_id`, a.`IconID` AS `icon_id` ' .
                    'FROM `achievement_dbc` a WHERE a.`ID` IN (%s)';
                $achievementIconIdField = 'icon_id';

                if (spp_character_table_exists($worldPdo, 'achievement_category_dbc')) {
                    foreach ($worldPdo->query('SELECT `ID`, `Name_Lang_enUS`, `Parent` FROM `achievement_category_dbc`')->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
                        $achievementCategoryMap[(int)$categoryRow['ID']] = array(
                            'name' => trim((string)($categoryRow['Name_Lang_enUS'] ?? '')),
                            'parent' => (int)($categoryRow['Parent'] ?? -1),
                        );
                    }
                }
            } elseif (spp_character_table_exists($armoryPdo, 'dbc_achievement')) {
                $achievementSourcePdo = $armoryPdo;
                $achievementQuerySql =
                    'SELECT a.`id`, a.`name`, a.`description`, a.`points`, a.`ref_achievement_category` AS `category_id`, ' .
                    'a.`ref_spellicon` AS `icon_id` FROM `dbc_achievement` a WHERE a.`id` IN (%s)';
                $achievementIconIdField = 'icon_id';

                if (spp_character_table_exists($armoryPdo, 'dbc_achievement_category')) {
                    foreach ($armoryPdo->query('SELECT `id`, `name`, `ref_achievement_category` FROM `dbc_achievement_category`')->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
                        $achievementCategoryMap[(int)$categoryRow['id']] = array(
                            'name' => trim((string)($categoryRow['name'] ?? '')),
                            'parent' => (int)($categoryRow['ref_achievement_category'] ?? -1),
                        );
                    }
                }
            }

            if ($achievementSourcePdo) {
            $achievementSummary['supported'] = true;
            $stmt = $charsPdo->prepare('SELECT `achievement`, `date` FROM `character_achievement` WHERE `guid` = ? ORDER BY `date` DESC');
            $stmt->execute(array($characterGuid));
            $achievementRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $achievementSummary['count'] = count($achievementRows);
            $achievementIds = array();
            foreach ($achievementRows as $row) $achievementIds[(int)$row['achievement']] = true;
            $achievementMap = array();
            if (!empty($achievementIds)) {
                $placeholders = implode(',', array_fill(0, count($achievementIds), '?'));
                $stmt = $achievementSourcePdo->prepare(sprintf($achievementQuerySql, $placeholders));
                $stmt->execute(array_keys($achievementIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $achievementMap[(int)$row[$achievementIdField]] = $row;
            }
            $achievementIconMap = array();
            $achievementIconIds = array();
            foreach ($achievementMap as $achievementRow) {
                $iconId = (int)($achievementRow[$achievementIconIdField] ?? 0);
                if ($iconId > 0) $achievementIconIds[$iconId] = true;
            }
            $achievementIconPdo = spp_character_pick_dbc_pdo(array($achievementSourcePdo, $worldPdo, $armoryPdo), 'dbc_spellicon') ?: $armoryPdo;
            if (!empty($achievementIconIds) && $achievementIconPdo && spp_character_table_exists($achievementIconPdo, 'dbc_spellicon')) {
                $spellIconFields = spp_character_spellicon_fields($achievementIconPdo);
                if ($spellIconFields['id'] && $spellIconFields['name']) {
                    $placeholders = implode(',', array_fill(0, count($achievementIconIds), '?'));
                    $stmt = $achievementIconPdo->prepare('SELECT `' . $spellIconFields['id'] . '` AS `id`, `' . $spellIconFields['name'] . '` AS `name` FROM `dbc_spellicon` WHERE `' . $spellIconFields['id'] . '` IN (' . $placeholders . ')');
                    $stmt->execute(array_keys($achievementIconIds));
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) $achievementIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
            }
            foreach ($achievementRows as $index => $row) {
                $achievement = $achievementMap[(int)$row['achievement']] ?? array(
                    'id' => (int)$row['achievement'],
                    'name' => 'Achievement #' . (int)$row['achievement'],
                    'description' => '',
                    'points' => 0,
                    'icon_id' => 0,
                    'category_id' => 0,
                );
                $categoryId = (int)($achievement[$achievementCategoryField] ?? 0);
                $categoryName = trim((string)($achievementCategoryMap[$categoryId]['name'] ?? ''));
                $parentCategoryId = (int)($achievementCategoryMap[$categoryId]['parent'] ?? -1);
                $parentCategoryName = trim((string)($achievementCategoryMap[$parentCategoryId]['name'] ?? ''));
                if ($categoryName === '') $categoryName = 'Other';
                $groupName = $parentCategoryName !== '' ? $parentCategoryName : $categoryName;
                $subgroupName = $parentCategoryName !== '' ? $categoryName : '';
                $iconId = (int)($achievement[$achievementIconIdField] ?? 0);
                $achievementEntry = array(
                    'id' => (int)($achievement[$achievementIdField] ?? $row['achievement']),
                    'name' => (string)($achievement[$achievementNameField] ?? ('Achievement #' . (int)$row['achievement'])),
                    'description' => trim((string)($achievement[$achievementDescriptionField] ?? '')),
                    'points' => (int)($achievement[$achievementPointsField] ?? 0),
                    'date' => (int)($row['date'] ?? 0),
                    'date_label' => !empty($row['date']) ? gmdate('M j, Y', (int)$row['date']) : '',
                    'icon' => spp_character_icon_url($achievementIconMap[$iconId] ?? 'INV_Misc_QuestionMark'),
                    'category' => $categoryName,
                    'group' => $groupName,
                    'subgroup' => $subgroupName,
                );
                $achievementSummary['points'] += (int)($achievement[$achievementPointsField] ?? 0);
                if ($index < 12) $achievementSummary['recent'][] = $achievementEntry;
                if (!isset($achievementSummary['groups'][$groupName])) $achievementSummary['groups'][$groupName] = array();
                if (!isset($achievementSummary['groups'][$groupName][$subgroupName])) $achievementSummary['groups'][$groupName][$subgroupName] = array();
                $achievementSummary['groups'][$groupName][$subgroupName][] = $achievementEntry;
            }
            }
        }
        if (spp_character_table_exists($charsPdo, 'character_armory_feed')) {
            $stmt = $charsPdo->prepare('SELECT `type`, `data`, `date` FROM `character_armory_feed` WHERE `guid` = ? AND `type` IN (2, 3) ORDER BY `date` DESC LIMIT 25');
            $stmt->execute(array($characterGuid));
            $feedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $recentGearFeed = array();
            foreach ($feedRows as $feedRow) {
                $feedType = (int)$feedRow['type'];
                $feedData = (int)$feedRow['data'];
                if ($feedType === 2 && $feedData > 0 && !isset($recentGearFeed[$feedData]) && count($recentGearFeed) < 5) {
                    $recentGearFeed[$feedData] = (int)$feedRow['date'];
                }
                if ($lastInstance === '' && $feedType === 3 && $feedData > 0) {
                    $instanceStmt = $armoryPdo->prepare("SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = 'npc' LIMIT 1");
                    $instanceStmt->execute(array($feedData, $feedData, $feedData, $feedData, $feedData, $feedData));
                    $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
                    if ($instanceLoot) {
                        $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                        $templateStmt->execute(array((int)$instanceLoot['instance_id']));
                        $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                        if ($instanceInfo) {
                            $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                            $bossName = trim((string)($instanceLoot['name_en_gb'] ?? ''));
                            $instanceName = trim((string)($instanceInfo['name_en_gb'] ?? '')) . $suffix;
                            $lastInstance = trim($bossName . ' - ' . $instanceName, ' -');
                            $lastInstanceDate = (int)$feedRow['date'];
                        }
                    }
                }
            }
            if (!empty($recentGearFeed)) {
                $recentGearIds = array_keys($recentGearFeed);
                $placeholders = implode(',', array_fill(0, count($recentGearIds), '?'));
                $recentItemMap = array();
                $recentIconMap = array();
                $stmt = $worldPdo->prepare('SELECT `entry`, `name`, `Quality`, `ItemLevel`, `RequiredLevel`, `displayid` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
                $stmt->execute($recentGearIds);
                $displayIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
                    $recentItemMap[(int)$itemRow['entry']] = $itemRow;
                    if (!empty($itemRow['displayid'])) $displayIds[(int)$itemRow['displayid']] = true;
                }
                if (!empty($displayIds)) {
                    $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
                    $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
                    $stmt->execute(array_keys($displayIds));
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) $recentIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
                foreach ($recentGearFeed as $entry => $feedDate) {
                    if (!isset($recentItemMap[$entry])) continue;
                    $itemRow = $recentItemMap[$entry];
                    $recentGear[] = array(
                        'entry' => (int)$entry,
                        'name' => (string)$itemRow['name'],
                        'quality' => (int)$itemRow['Quality'],
                        'item_level' => (int)$itemRow['ItemLevel'],
                        'icon' => spp_character_icon_url($recentIconMap[(int)$itemRow['displayid']] ?? ''),
                        'date' => (int)$feedDate,
                    );
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[character-profile] ' . $e->getMessage());
        $pageError = ($e->getMessage() === 'Character not found.') ? 'Character not found.' : 'Character details could not be loaded.';
    }
}

$realmLabel = spp_get_armory_realm_name($realmId) ?? '';
$zoneName = isset($character['zone']) && isset($GLOBALS['MANG']) && $GLOBALS['MANG'] instanceof Mangos ? $GLOBALS['MANG']->get_zone_name((int)$character['zone']) : 'Unknown zone';
$currentMapName = isset($character['map']) && isset($GLOBALS['MANG']) && $GLOBALS['MANG'] instanceof Mangos ? $GLOBALS['MANG']->get_zone_name((int)$character['map']) : 'Unknown zone';
$normalizedZoneName = $zoneName !== 'Unknown zone' ? trim((string)$zoneName) : '';
$normalizedMapName = $currentMapName !== 'Unknown zone' ? trim((string)$currentMapName) : '';
$continentNames = array('Azeroth', 'Eastern Kingdoms', 'Kalimdor', 'Outland', 'Northrend');
if ($normalizedZoneName !== '' && $normalizedMapName !== '' && strcasecmp($normalizedZoneName, $normalizedMapName) !== 0 && !in_array($normalizedMapName, $continentNames, true)) {
    $displayLocation = $normalizedZoneName . ', ' . $normalizedMapName;
} elseif ($normalizedZoneName !== '') {
    $displayLocation = $normalizedZoneName;
} elseif ($normalizedMapName !== '') {
    $displayLocation = $normalizedMapName;
} else {
    $displayLocation = 'Unknown location';
}
if ($lastInstance === '' && $currentMapName !== 'Unknown zone' && strpos($currentMapName, ':' ) !== false) $lastInstance = $currentMapName;
if (empty($recentGear) && !empty($equipment)) {
    foreach (array_slice(array_values($equipment), 0, 5) as $fallbackItem) {
        $recentGear[] = array(
            'entry' => (int)$fallbackItem['entry'],
            'name' => (string)$fallbackItem['name'],
            'quality' => (int)$fallbackItem['quality'],
            'item_level' => (int)$fallbackItem['item_level'],
            'icon' => (string)$fallbackItem['icon'],
            'date' => 0,
        );
    }
}
$portraitUrl = $character ? spp_character_portrait_path($character['level'], $character['gender'], $character['race'], $character['class']) : '';
$factionName = $character ? spp_character_faction_name($character['race']) : '';
$factionIcon = $factionName === 'Horde' ? '/armory/images/icon-horde.gif' : '/armory/images/icon-alliance.gif';
$factionHeroLogo = $factionName === 'Horde' ? 'templates/offlike/images/modern/logo-horde.png' : 'templates/offlike/images/modern/logo-alliance.png';
$classSlug = $character ? strtolower(str_replace(' ', '', $classNames[(int)$character['class']] ?? 'unknown')) : 'unknown';
$characterUrl = 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . urlencode((string)$characterName);
$talentCalculatorUrl = 'index.php?n=server&sub=talents&realm=' . (int)$realmId . '&character=' . urlencode((string)$characterName);
$guildId = (int)($character['guildid'] ?? 0);
$guildName = (string)($character['guild_name'] ?? '');
$honorableKills = (int)($character['stored_honorable_kills'] ?? $character['totalKills'] ?? 0);
if ($honorableKills <= 0 && $character && spp_character_table_exists($charsPdo, 'character_honor_cp')) {
    $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM `character_honor_cp` WHERE `guid` = ? AND `victim_type` > 0 AND `type` = 1");
    $stmt->execute(array((int)$character['guid']));
    $honorableKills = (int)$stmt->fetchColumn();
}
$honorPoints = (int)($character['totalHonorPoints'] ?? $character['stored_honor_rating'] ?? 0);
$gearScoreSlots = array_diff_key($equipment, array(3 => true, 18 => true));
$gearItemLevels = array();
foreach ($gearScoreSlots as $gearItem) {
    $itemLevel = (int)($gearItem['item_level'] ?? 0);
    if ($itemLevel > 0) $gearItemLevels[] = $itemLevel;
}
$averageItemLevel = !empty($gearItemLevels) ? round(array_sum($gearItemLevels) / count($gearItemLevels), 1) : 0;
$gearRank = 'Unranked';
if ($averageItemLevel >= 88) $gearRank = 'Tier 3';
elseif ($averageItemLevel >= 76) $gearRank = 'Tier 2';
elseif ($averageItemLevel >= 66) $gearRank = 'Tier 1';
elseif ($averageItemLevel > 0) $gearRank = 'N00b!';
$talentList = array_values($talentTabs);
usort($talentList, function ($a, $b) { return $b['points'] <=> $a['points']; });
$reputationHighlights = $reputations;
usort($reputationHighlights, function ($a, $b) { return $b['standing'] <=> $a['standing']; });
$reputationHighlights = array_slice($reputationHighlights, 0, 5);
$primaryPowerLabelMap = array(1 => 'Rage', 2 => 'Mana', 3 => 'Mana', 4 => 'Energy', 5 => 'Mana', 6 => 'Runic Power', 7 => 'Mana', 8 => 'Mana', 9 => 'Mana', 11 => 'Mana');
$primaryPowerLabel = $primaryPowerLabelMap[(int)$character['class']] ?? 'Power';
$specName = !empty($talentList) ? (string)$talentList[0]['name'] : 'No Specialization';
$specBreakdown = !empty($talentTabs) ? implode(' / ', array_map(function ($tab) { return (string)(int)$tab['points']; }, array_values($talentTabs))) : '0 / 0 / 0';
$resistanceStats = array(
    'Arcane' => (int)($stats['resArcane'] ?? 0),
    'Fire' => (int)($stats['resFire'] ?? 0),
    'Nature' => (int)($stats['resNature'] ?? 0),
    'Frost' => (int)($stats['resFrost'] ?? 0),
    'Shadow' => (int)($stats['resShadow'] ?? 0),
);
$baseStatsView = array(
    'Strength' => (int)($stats['strength'] ?? 0),
    'Agility' => (int)($stats['agility'] ?? 0),
    'Stamina' => (int)($stats['stamina'] ?? 0),
    'Intellect' => (int)($stats['intellect'] ?? 0),
    'Spirit' => (int)($stats['spirit'] ?? 0),
    'Armor' => (int)($stats['armor'] ?? 0),
);
$meleeStatsView = array(
    'Damage' => ((float)($stats['mainHandDamageMin'] ?? 0) > 0 || (float)($stats['mainHandDamageMax'] ?? 0) > 0) ? number_format((float)($stats['mainHandDamageMin'] ?? 0), 0, '.', ',') . ' - ' . number_format((float)($stats['mainHandDamageMax'] ?? 0), 0, '.', ',') : '0 - 0',
    'Speed' => (((float)($stats['mainHandSpeed'] ?? 0) > 0) || ((float)($stats['offHandSpeed'] ?? 0) > 0)) ? rtrim(rtrim(number_format((float)($stats['mainHandSpeed'] ?? 0), 2, '.', ''), '0'), '.') . ' / ' . rtrim(rtrim(number_format((float)($stats['offHandSpeed'] ?? 0), 2, '.', ''), '0'), '.') : '0 / 0',
    'Power' => number_format((float)($stats['attackPower'] ?? 0), 0, '.', ','),
    'Hit Rating' => number_format((float)($stats['meleeHitRating'] ?? 0), 0, '.', ','),
    'Crit Chance' => rtrim(rtrim(number_format((float)($stats['critPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Expertise' => number_format((float)($stats['expertise'] ?? $stats['expertiseRating'] ?? 0), 0, '.', ','),
);
$gearShowcaseLeft = array(0, 2, 14, 4, 8, 15, 17);
$gearShowcaseRight = array(9, 1, 3, 5, 6, 7, 10, 11, 12, 13);
$gearShowcaseBottom = array(16, 18);
$resourceMap = array(
    1 => array('label' => 'Rage', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 100), 'class' => 'is-rage'),
    2 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    3 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    4 => array('label' => 'Energy', 'current' => (int)($character['power1'] ?? 0), 'max' => max(100, (int)($stats['maxpower1'] ?? 0)), 'class' => 'is-energy'),
    5 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    7 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    8 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    9 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    11 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
);
$primaryResource = $resourceMap[(int)($character['class'] ?? 0)] ?? array('label' => 'Power', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 100), 'class' => 'is-mana');
$healthCurrent = (int)($character['health'] ?? 0);
$healthMax = max($healthCurrent, (int)($stats['maxhealth'] ?? 0));
$resourceCurrent = (int)$primaryResource['current'];
$resourceMax = max($resourceCurrent, (int)$primaryResource['max']);
$paperdollLeftSlots = array(0, 1, 2, 14, 4, 3, 18, 8);
$paperdollRightSlots = array(9, 5, 6, 7, 10, 11, 12, 13);
$paperdollBottomSlots = array(15, 16, 17);
$talentBarCap = max(1, (int)($character['level'] ?? 0) - 9);
$talentTreesView = array_values($talentTabs);
$defenseStatsView = array(
    'Armor' => number_format((float)($stats['armor'] ?? 0), 0, '.', ','),
    'Defense' => number_format((float)($stats['defenseRating'] ?? 0), 0, '.', ','),
    'Dodge' => rtrim(rtrim(number_format((float)($stats['dodgePct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Parry' => rtrim(rtrim(number_format((float)($stats['parryPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Block' => rtrim(rtrim(number_format((float)($stats['blockPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Resilience' => number_format((float)($stats['resilience'] ?? 0), 0, '.', ','),
);
$spellStatsView = array(
    'Bonus Damage' => number_format((float)($stats['spellPower'] ?? 0), 0, '.', ','),
    'Bonus Healing' => number_format((float)($stats['healBonus'] ?? 0), 0, '.', ','),
    'Hit Rating' => number_format((float)($stats['spellHitRating'] ?? 0), 0, '.', ','),
    'Crit Chance' => rtrim(rtrim(number_format((float)($stats['spellCritPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Haste Rating' => number_format((float)($stats['spellHasteRating'] ?? 0), 0, '.', ','),
    'Mana Regen' => number_format((float)($stats['manaRegen'] ?? 0), 0, '.', ','),
);
$rangedStatsView = array(
    'Damage' => ((float)($stats['rangedDamageMin'] ?? 0) > 0 || (float)($stats['rangedDamageMax'] ?? 0) > 0) ? number_format((float)($stats['rangedDamageMin'] ?? 0), 0, '.', ',') . ' - ' . number_format((float)($stats['rangedDamageMax'] ?? 0), 0, '.', ',') : '0 - 0',
    'Speed' => ((float)($stats['rangedSpeed'] ?? 0) > 0) ? rtrim(rtrim(number_format((float)($stats['rangedSpeed'] ?? 0), 2, '.', ''), '0'), '.') : '0',
    'Power' => number_format((float)($stats['rangedAttackPower'] ?? 0), 0, '.', ','),
    'Hit Rating' => number_format((float)($stats['rangedHitRating'] ?? 0), 0, '.', ','),
    'Crit Chance' => rtrim(rtrim(number_format((float)($stats['rangedCritPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Haste Rating' => number_format((float)($stats['rangedHasteRating'] ?? 0), 0, '.', ','),
);
$paperdollLeftPanels = array(
    'base' => array('label' => 'Base Stats', 'rows' => $baseStatsView),
    'defense' => array('label' => 'Defense', 'rows' => $defenseStatsView),
);
$paperdollRightPanels = array(
    'melee' => array('label' => 'Melee', 'rows' => $meleeStatsView),
    'spell' => array('label' => 'Spell', 'rows' => $spellStatsView),
    'ranged' => array('label' => 'Ranged', 'rows' => $rangedStatsView),
);
$paperdollRightDefault = in_array((int)($character['class'] ?? 0), array(3), true) ? 'ranged' : (in_array((int)($character['class'] ?? 0), array(5, 8, 9), true) ? 'spell' : 'melee');
?>

<style>
.character-page{display:grid;gap:18px;color:#f4ead0}.character-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,.9fr);gap:22px;padding:28px;border-radius:22px;border:1px solid rgba(255,196,0,.22);background:radial-gradient(circle at top right,rgba(255,178,54,.15),transparent 36%),linear-gradient(180deg,rgba(8,10,20,.98),rgba(5,6,14,1))}.character-hero>*{position:relative;z-index:1}.character-hero-mark{position:absolute;right:320px;top:34px;bottom:34px;width:min(360px,26vw);display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:0}.character-hero-mark img{width:100%;max-width:340px;max-height:100%;object-fit:contain;opacity:.14;filter:drop-shadow(0 10px 24px rgba(0,0,0,.3))}.character-identity{display:flex;gap:20px;align-items:flex-start}.character-portrait{width:118px;height:118px;border-radius:26px;border:1px solid rgba(255,196,0,.38);background:#050505;object-fit:cover}.character-eyebrow{margin:0 0 8px;color:#c7b07b;letter-spacing:.08em;text-transform:uppercase;font-size:.8rem}.character-title{margin:0;font-size:2.7rem;line-height:1}.character-title a{color:inherit;text-decoration:none}.class-warrior{color:#C79C6E}.class-paladin{color:#F58CBA}.class-hunter{color:#ABD473}.class-rogue{color:#FFF569}.class-priest{color:#FFFFFF}.class-deathknight{color:#C41F3B}.class-shaman{color:#0070DE}.class-mage{color:#69CCF0}.class-warlock{color:#9482C9}.class-druid{color:#FF7D0A}.character-subtitle{margin:10px 0 0;color:#e2d4ae;font-size:1.05rem}.character-tabs{display:flex;gap:10px;flex-wrap:wrap}.character-guildline{display:flex;align-items:center;gap:12px;margin-top:10px;flex-wrap:wrap;color:#e2d4ae;font-size:1.05rem}.character-tab,.character-link{display:inline-flex;align-items:center;min-height:40px;padding:0 14px;border-radius:999px;border:1px solid rgba(255,204,72,.16);background:rgba(255,255,255,.04);color:#f2dfb1;text-decoration:none;font-weight:700}.character-link{border-color:rgba(255,196,0,.34);color:#ffe39a;background:rgba(255,204,72,.06)}.character-tab.is-active{color:#120d03;background:linear-gradient(180deg,#ffd87a,#d9a63d);border-color:rgba(255,204,72,.45)}.character-hero-grid,.character-grid{display:grid;gap:18px}.character-hero-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.character-grid{grid-template-columns:minmax(300px,.9fr) minmax(0,1.4fr)}.character-stat-card,.character-panel,.character-item,.character-skill-item,.character-achievement-item{border-radius:18px;border:1px solid rgba(255,196,0,.18);background:rgba(5,8,18,.72)}.character-stat-card{padding:18px}.character-stat-label,.character-item-slot{display:block;margin-bottom:6px;color:#c4b27c;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase}.character-stat-value{color:#ffd467;font-size:1.55rem;font-weight:700}.character-panel{padding:22px 24px}.character-panel-title{margin:0 0 16px;color:#fff4c4;font-size:1.55rem}.character-facts,.character-bars,.character-skill-list,.character-achievement-list{display:grid;gap:12px}.character-fact{display:grid;gap:4px;padding-bottom:12px;border-bottom:1px solid rgba(255,204,72,.12)}.character-fact:last-child{padding-bottom:0;border-bottom:0}.character-fact span{color:#bda877;font-size:.83rem;text-transform:uppercase;letter-spacing:.08em}.character-fact strong{color:#f7edd0;font-size:1.08rem}.character-snapshot-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.character-snapshot-card{position:relative;overflow:hidden;padding:14px 16px;border-radius:16px;border:1px solid rgba(255,204,72,.16);background:linear-gradient(180deg,rgba(14,19,34,.92),rgba(7,10,18,.9))}.character-snapshot-card::after{content:'';position:absolute;inset:auto -20% -40% auto;width:120px;height:120px;border-radius:50%;background:radial-gradient(circle,rgba(255,196,0,.14),transparent 68%);pointer-events:none}.character-snapshot-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.character-snapshot-label{display:block;color:#c7b07b;font-size:.76rem;letter-spacing:.12em;text-transform:uppercase}.character-snapshot-value{display:block;margin-top:6px;color:#fff3c4;font-size:1.6rem;font-weight:800;line-height:1}.character-snapshot-meta{margin-top:10px;color:#9ea8c7;font-size:.82rem}.character-snapshot-accent{width:36px;height:36px;border-radius:12px;border:1px solid rgba(255,204,72,.18);background:linear-gradient(180deg,rgba(255,217,90,.22),rgba(255,217,90,.05));box-shadow:inset 0 1px 0 rgba(255,255,255,.12)}.character-equip-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.character-item{display:grid;grid-template-columns:54px minmax(0,1fr);gap:12px;align-items:center;padding:14px}.character-item img,.character-skill-head img,.character-achievement-icon{border-radius:12px;border:1px solid rgba(255,204,72,.22);background:#090909}.character-item img{width:54px;height:54px}.character-item-name{margin-top:4px;font-weight:700}.character-item-name a{color:inherit;text-decoration:none}.quality-0{color:#9d9d9d}.quality-1{color:#fff}.quality-2{color:#1eff00}.quality-3{color:#0070dd}.quality-4{color:#a335ee}.quality-5{color:#ff8000}.character-item-meta,.character-skill-meta,.character-fact-sub,.character-achievement-meta{margin-top:4px;color:#aa9870;font-size:.88rem}.character-fact-list{display:grid;gap:8px}.character-fact-link{display:flex;align-items:center;gap:10px;color:#f7edd0;text-decoration:none;font-weight:700}.character-fact-link img{width:24px;height:24px;border-radius:8px;border:1px solid rgba(255,204,72,.18);background:#090909}.character-combat-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}.character-combat-card{padding:14px 16px;border-radius:16px;border:1px solid rgba(255,204,72,.16);background:linear-gradient(180deg,rgba(14,19,34,.92),rgba(7,10,18,.9))}.character-combat-card strong{display:block;color:#fff3c4;font-size:1.35rem;line-height:1.1}.character-combat-card span{display:block;margin-bottom:6px;color:#c7b07b;font-size:.76rem;letter-spacing:.12em;text-transform:uppercase}.character-bar-label{display:flex;align-items:center;justify-content:space-between;color:#d8c89f;font-size:.92rem}.character-bar-track{height:12px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.08)}.character-bar-fill{height:100%;background:linear-gradient(90deg,#ffd45f,#ffeab0)}.character-skill-item,.character-achievement-item{padding:14px 16px}.character-skill-head{display:flex;align-items:center;gap:12px}.character-skill-head img{width:34px;height:34px}.character-empty,.character-error{padding:18px;border-radius:16px}.character-empty{background:rgba(255,255,255,.03);color:#c8b78c;border:1px dashed rgba(255,204,72,.16)}.character-error{background:rgba(95,16,16,.4);border:1px solid rgba(255,122,122,.25);color:#ffd5d5}.character-achievement-points{color:#ffd467;font-weight:700}.character-achievement-list{gap:14px}.character-achievement-item{display:grid;grid-template-columns:48px minmax(0,1fr) auto;gap:14px;align-items:start}.character-achievement-icon{width:48px;height:48px;display:block}.character-achievement-title{color:#f7edd0;font-size:1.02rem;font-weight:800;line-height:1.2}.character-achievement-points-badge{display:inline-flex;align-items:center;justify-content:center;min-width:46px;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,204,72,.18);background:rgba(255,204,72,.08);color:#ffd467;font-weight:800}.character-achievement-sections{display:grid;gap:18px}.character-achievement-section{display:grid;gap:12px}.character-achievement-disclosure{border:1px solid rgba(255,196,0,.16);border-radius:18px;background:rgba(5,8,18,.38)}.character-achievement-disclosure[open]{background:rgba(5,8,18,.5)}.character-achievement-summary{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px;cursor:pointer;list-style:none}.character-achievement-summary::-webkit-details-marker{display:none}.character-achievement-summary::after{content:'+';display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;border:1px solid rgba(255,204,72,.18);color:#ffd467;font-size:1rem;font-weight:800;flex:0 0 auto}.character-achievement-disclosure[open]>.character-achievement-summary::after{content:'-'}.character-achievement-summary-text{display:flex;flex-direction:column;gap:4px;min-width:0}.character-achievement-section-title{margin:0;color:#ffe39a;font-size:1.15rem}.character-achievement-count{color:#aa9870;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase}.character-achievement-disclosure-body{display:grid;gap:12px;padding:0 18px 18px}.character-achievement-subtitle{margin:0 0 4px;color:#c7b07b;font-size:.88rem;letter-spacing:.08em;text-transform:uppercase}.character-achievement-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}@media (max-width:1100px){.character-hero,.character-grid{grid-template-columns:1fr}.character-hero-mark{right:24px;top:auto;bottom:18px;width:min(300px,52vw);height:180px;justify-content:flex-end}.character-hero-mark img{max-width:220px;opacity:.1}}@media (max-width:720px){.character-identity{flex-direction:column}.character-title{font-size:2.1rem}.character-hero-grid{grid-template-columns:1fr 1fr}.character-hero-mark{display:none}.character-achievement-item{grid-template-columns:40px minmax(0,1fr)}.character-achievement-points-badge{grid-column:2}.character-achievement-summary{padding:12px 14px}.character-achievement-disclosure-body{padding:0 14px 14px}}@media (max-width:560px){.character-hero-grid,.character-snapshot-grid,.character-combat-grid,.character-achievement-grid{grid-template-columns:1fr}.character-item{grid-template-columns:46px minmax(0,1fr)}.character-item img{width:46px;height:46px}}
.character-grid.character-grid-overview{grid-template-columns:minmax(300px,.86fr) minmax(0,1.5fr)}
.character-grid.character-grid-sheet{grid-template-columns:minmax(0,1.35fr) minmax(280px,.8fr)}
.character-achievement-section.is-collapsible{padding:0;border:1px solid rgba(255,196,0,.16);background:rgba(5,8,18,.38);overflow:hidden}
.character-achievement-section.is-collapsible.is-open{background:rgba(5,8,18,.5)}
.character-achievement-section.is-collapsible > .character-achievement-section-title{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px;margin:0;cursor:pointer;user-select:none}
.character-achievement-section.is-collapsible > .character-achievement-section-title::after{content:'+';display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;border:1px solid rgba(255,204,72,.18);color:#ffd467;font-size:1rem;font-weight:800;flex:0 0 auto}
.character-achievement-section.is-collapsible.is-open > .character-achievement-section-title::after{content:'-'}
.character-achievement-section-body{display:grid;gap:12px;padding:0 18px 18px}
.character-achievement-section.is-collapsible:not(.is-open) > .character-achievement-section-body{display:none}
.character-achievement-section-pinned > .character-achievement-section-title{display:flex;align-items:center;justify-content:space-between;gap:14px}
.character-achievement-section-pinned > .character-achievement-section-title::after{content:none}
@media (max-width:720px){.character-achievement-section.is-collapsible > .character-achievement-section-title{padding:12px 14px}.character-achievement-section-body{padding:0 14px 14px}}
.character-gear-showcase{padding:24px 20px 30px;background:radial-gradient(circle at center,rgba(66,49,154,.18),transparent 30%),linear-gradient(180deg,rgba(2,6,18,.98),rgba(2,4,12,1));overflow:hidden}
.character-gear-showcase .character-panel-title{text-align:center}
.character-gear-stage{position:relative;width:540px;max-width:100%;min-height:880px;margin:0 auto}
.character-gear-stage::before{content:"";position:absolute;inset:18% 19% 12%;background:radial-gradient(circle at center,rgba(92,68,214,.2),transparent 58%);pointer-events:none}
.character-gear-column,.character-gear-bottom{position:absolute;z-index:1;display:grid;gap:18px}
.character-gear-stage > .character-gear-column:first-child{top:118px;left:10px}
.character-gear-stage > .character-gear-column:nth-child(3){top:44px;right:10px}
.character-gear-slot{display:flex;justify-content:center;align-items:center}
.character-gear-card{position:relative;display:block;width:64px;height:64px;border-radius:14px;border:1px solid rgba(255,204,72,.3);background:linear-gradient(180deg,rgba(21,25,38,.96),rgba(8,10,16,.98));box-shadow:0 0 0 1px rgba(255,255,255,.03) inset,0 10px 25px rgba(0,0,0,.4);overflow:hidden}
.character-gear-card img{display:block;width:100%;height:100%;object-fit:cover}
.character-gear-card.is-empty{opacity:.42}
.character-gear-card.is-empty::after{content:attr(data-slot);position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:8px;color:#88754b;font-size:.67rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;text-align:center}
.character-gear-center{position:absolute;inset:122px 120px 140px;z-index:1;display:flex;justify-content:center;align-items:center}
.character-gear-portrait-wrap{position:relative;width:100%;max-width:280px;padding:0;border-radius:0;background:none}
.character-gear-portrait-wrap::before{content:"";position:absolute;inset:-26px -34px;background:radial-gradient(circle at center,rgba(42,24,111,.28),transparent 62%);pointer-events:none}
.character-gear-portrait{display:block;width:100%;max-width:280px;aspect-ratio:1/1;margin:0 auto;border-radius:28px;object-fit:cover;filter:drop-shadow(0 20px 44px rgba(0,0,0,.6))}
.character-gear-bottom{left:50%;bottom:16px;grid-template-columns:repeat(2,64px);transform:translateX(-50%)}
.character-sheet-spec{display:flex;gap:12px;align-items:center}
.character-sheet-spec-icon{width:42px;height:42px;border-radius:999px;border:1px solid rgba(255,204,72,.28);background:rgba(0,0,0,.35);padding:6px}
.character-sheet-spec-icon img{display:block;width:100%;height:100%;object-fit:cover}
.character-sheet-spec-name{display:block;color:#ffd200;font-size:1.85rem;font-weight:800;line-height:1}
.character-sheet-spec-breakdown{display:block;margin-top:4px;color:#f5ead1;font-size:1.15rem;font-weight:700}
.character-sheet-resists{display:grid;gap:10px}
.character-sheet-resist{display:flex;justify-content:space-between;align-items:center;gap:12px;color:#ffd200;font-size:1.1rem;font-weight:800;text-transform:uppercase}
.character-sheet-resist strong{min-width:44px;padding:4px 8px;border-radius:10px;border:1px solid rgba(255,204,72,.24);background:rgba(0,0,0,.35);color:#fff}
.character-sheet-bars{padding:18px 20px;background:linear-gradient(180deg,rgba(37,27,6,.96),rgba(20,15,5,.98))}
.character-sheet-bar-row{display:grid;grid-template-columns:90px minmax(0,1fr);align-items:center;gap:14px}
.character-sheet-bar-row + .character-sheet-bar-row{margin-top:10px}
.character-sheet-bar-row span{color:#fff4c4;font-size:1.05rem;font-weight:800;text-transform:uppercase}
.character-sheet-bar-track{position:relative;height:22px;border-radius:0;overflow:hidden;border:1px solid rgba(0,0,0,.55);background:rgba(3,5,10,.55)}
.character-sheet-bar-fill{height:100%}
.character-sheet-bar-fill.is-health{background:linear-gradient(90deg,#1d7600,#4fd500)}
.character-sheet-bar-fill.is-mana{background:linear-gradient(90deg,#004fba,#5ea8ff)}
.character-sheet-bar-fill.is-rage{background:linear-gradient(90deg,#7d0808,#dd2d2d)}
.character-sheet-bar-fill.is-energy{background:linear-gradient(90deg,#9d8400,#f2db3d)}
.character-sheet-bar-value{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;font-weight:800;text-shadow:0 1px 2px rgba(0,0,0,.9);letter-spacing:.02em}
.character-sheet-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px;background:rgba(255,205,97,.12)}
.character-sheet-statbox{background:linear-gradient(180deg,rgba(17,14,10,.98),rgba(8,7,5,.98))}
.character-sheet-statbox-head{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid rgba(255,205,97,.12);background:linear-gradient(180deg,rgba(15,20,26,.98),rgba(5,7,10,.98));color:#fff;font-size:1rem;font-weight:800}
.character-sheet-statbox-body{padding:14px}
.character-sheet-statlist{display:grid;gap:10px}
.character-sheet-statrow{display:flex;justify-content:space-between;align-items:baseline;gap:14px;color:#ffd200;font-size:1.1rem;font-weight:800}
.character-sheet-statrow strong{color:#fff}
.character-fact-link{align-items:flex-start}
.character-fact-link .character-fact-sub{margin-top:2px}
.character-fact-list{gap:10px}
.character-fact-link{padding:10px 12px;border-radius:14px;border:1px solid rgba(255,204,72,.1);background:rgba(255,255,255,.03);font-size:1rem;font-weight:800;letter-spacing:.02em}
.character-fact-link img{width:26px;height:26px}
.character-fact-link.is-quest{padding:6px 0;border:0;background:transparent;color:#ffe6af;font-size:1.04rem;line-height:1.35}
@media (max-width:1100px){.character-grid.character-grid-overview,.character-grid.character-grid-sheet{grid-template-columns:1fr}}
@media (max-width:720px){.character-sheet-grid,.character-sheet-stats{grid-template-columns:1fr}.character-sheet-bar-row{grid-template-columns:1fr}.character-gear-stage{width:100%;min-height:auto;display:grid;gap:18px}.character-gear-stage > .character-gear-column:first-child,.character-gear-stage > .character-gear-column:nth-child(3),.character-gear-center,.character-gear-bottom{position:relative;inset:auto;left:auto;right:auto;bottom:auto;transform:none}.character-gear-center{order:-1}.character-gear-column,.character-gear-bottom{grid-template-columns:repeat(4,64px);justify-content:center}.character-gear-portrait-wrap{max-width:220px}}
</style>
<style>
.character-grid.character-grid-overview{grid-template-columns:minmax(300px,.84fr) minmax(0,1.6fr)}
.character-paperdoll-shell{padding:10px 28px 16px;overflow:visible}
.legacy-paperdoll{width:405px;max-width:none;margin:0 auto;padding:4px 0 0;background:url('/armory/images/profile-bg.jpg') no-repeat 50% 0;position:relative;color:#fff;font-family:'Trebuchet MS',Arial,Helvetica,sans-serif}
.legacy-paperdoll ul{margin:0;padding:0;list-style:none}
.legacy-paperdoll li{width:60px;height:55px;margin:1px;position:relative}
.legacy-paperdoll .stack1,.legacy-paperdoll .stack2,.legacy-paperdoll .stack3,.legacy-paperdoll .stack4{width:405px;margin:0 auto;position:relative}
.legacy-paperdoll .stack1{height:189px;padding-top:9px;margin-bottom:3px;z-index:3}
.legacy-paperdoll .stack2{height:54px}
.legacy-paperdoll .stack3{height:134px}
.legacy-paperdoll .stack4{height:70px}
.legacy-paperdoll .stack4 li{float:left;width:54px;height:55px}
.legacy-paperdoll .items-left,.legacy-paperdoll .items-right,.legacy-paperdoll .items-bot{position:absolute}
.legacy-paperdoll .items-left{width:60px;top:3px;left:-67px}
.legacy-paperdoll .items-right{width:60px;top:3px;left:412px}
.legacy-paperdoll .items-bot{width:200px;top:11px;left:94px}
.legacy-paperdoll .items-left a,.legacy-paperdoll .items-right a,.legacy-paperdoll .items-bot a{cursor:pointer;z-index:4;display:block;position:absolute}
.legacy-paperdoll .items-left a,.legacy-paperdoll .items-right a{width:75px;height:60px;top:-4px}
.legacy-paperdoll .items-left a{background:url('/armory/images/icon-glass-left.gif') no-repeat -75px 0;left:-14px}
.legacy-paperdoll .items-right a{background:url('/armory/images/icon-glass-right.gif') no-repeat 0 0;left:-2px}
.legacy-paperdoll .items-bot a{width:60px;height:75px;background:url('/armory/images/icon-glass-bot.gif') no-repeat 0 0;top:-6px;left:-1px}
.legacy-paperdoll .items-left a:hover{background-position:-1px 0}
.legacy-paperdoll .items-right a:hover{background-position:-74px 0}
.legacy-paperdoll .items-bot a:hover{background-position:0 -74px}
.legacy-paperdoll .items-left img,.legacy-paperdoll .items-right img,.legacy-paperdoll .items-bot img{height:52px;width:52px;position:relative;left:4px;top:1px}
.legacy-paperdoll #slot0x,.legacy-paperdoll #slot1x,.legacy-paperdoll #slot2x,.legacy-paperdoll #slot3x,.legacy-paperdoll #slot4x,.legacy-paperdoll #slot18x,.legacy-paperdoll #slot8x{left:3px}
.legacy-paperdoll .spec,.legacy-paperdoll .resists,.legacy-paperdoll .profs,.legacy-paperdoll .dropdown1,.legacy-paperdoll .dropdown2,.legacy-paperdoll .stats1,.legacy-paperdoll .stats2{background:url('/armory/images/cpbg.png');position:relative;cursor:default}
.legacy-paperdoll .spec{width:261px;height:69px;float:left}
.legacy-paperdoll .resists{width:141px;height:189px;float:right}
.legacy-paperdoll .profs{width:261px;height:117px;margin-top:3px;float:left}
.legacy-paperdoll .spec h4,.legacy-paperdoll .profs h4,.legacy-paperdoll .stack2 h4{margin:0;padding:0 8px;color:#fff;text-transform:uppercase;font-size:10px}
.legacy-paperdoll .resists > h4{margin:0;padding:0 8px;color:#fff;text-transform:uppercase;font-size:10px}
.legacy-paperdoll .spec-wrapper,.legacy-paperdoll .talent-tree-row{padding:7px 0 0 50px}
.legacy-paperdoll .spec-wrapper h5,.legacy-paperdoll .talent-tree-row h5{margin:0;color:#ffd200;font-size:14px;line-height:1.1;text-transform:uppercase}
.legacy-paperdoll .spec-wrapper span{display:block;color:#fff;padding:0 0 0 3px;font-size:12px}
.legacy-paperdoll .spec-icon,.legacy-paperdoll .tree-icon{position:absolute;left:15px}
.legacy-paperdoll .spec-icon img,.legacy-paperdoll .tree-icon img{position:relative;top:10px;width:27px;height:27px}
.legacy-paperdoll .bar-container{margin:3px 0 0;padding:0;height:16px;width:180px;border:1px solid #000;background:url('/armory/images/bar-grey.gif') repeat-x;position:relative;text-align:center;color:#fff}
.legacy-paperdoll .bar-container b{height:16px;margin:0;padding:0;float:left;background:url('/armory/images/bar-mana.gif') repeat-x}
.legacy-paperdoll .bar-container span{position:absolute;top:-1px;left:0;width:180px;text-align:center;font-size:12px}
.legacy-paperdoll .resists ul{padding:10px 0 0}
.legacy-paperdoll .resists li{height:29px !important;text-align:right;padding:0 33px 0 0;width:90px;position:relative}
.legacy-paperdoll .resists li:hover{background-position:100% 100%}
.legacy-paperdoll li.fire{background:url('/armory/images/res-fire.gif') no-repeat 100% 0}
.legacy-paperdoll li.nature{background:url('/armory/images/res-nature.gif') no-repeat 100% 0}
.legacy-paperdoll li.arcane{background:url('/armory/images/res-arcane.gif') no-repeat 100% 0}
.legacy-paperdoll li.frost{background:url('/armory/images/res-frost.gif') no-repeat 100% 0}
.legacy-paperdoll li.shadow{background:url('/armory/images/res-shadow.gif') no-repeat 100% 0}
.legacy-paperdoll .resists b{position:absolute;right:4px;width:20px;text-align:center;top:6px;color:#000;font-size:12px !important}
.legacy-paperdoll .resists span{position:absolute;right:6px;width:20px;text-align:center;top:5px;color:#fff;font-size:12px !important}
.legacy-paperdoll .resists h5{margin:0;padding:5px 0 0;color:#ffd200;font-size:12px;text-transform:uppercase;font-weight:700}
.legacy-paperdoll .health-stat,.legacy-paperdoll .mana-stat,.legacy-paperdoll .rage-stat,.legacy-paperdoll .energy-stat{width:380px;height:15px;padding:5px 6px 0 5px}
.legacy-paperdoll .health-stat p,.legacy-paperdoll .mana-stat p,.legacy-paperdoll .rage-stat p,.legacy-paperdoll .energy-stat p{float:right;width:75%;margin:0;text-align:center;color:#fff;height:16px;border:1px solid #000}
.legacy-paperdoll .health-stat p{background:url('/armory/images/bar-life.gif') repeat-x}
.legacy-paperdoll .mana-stat p{background:url('/armory/images/bar-mana.gif') repeat-x}
.legacy-paperdoll .rage-stat p{background:url('/armory/images/bar-rage.gif') repeat-x}
.legacy-paperdoll .energy-stat p{background:url('/armory/images/bar-energy.gif') repeat-x}
.legacy-paperdoll .health-stat p span,.legacy-paperdoll .mana-stat p span,.legacy-paperdoll .rage-stat p span,.legacy-paperdoll .energy-stat p span{position:relative;top:-1px}
.legacy-paperdoll .health-stat h4,.legacy-paperdoll .mana-stat h4,.legacy-paperdoll .rage-stat h4,.legacy-paperdoll .energy-stat h4{text-align:right;width:55px;margin:0}
.legacy-paperdoll .dropdown1,.legacy-paperdoll .dropdown2{position:relative;z-index:99;height:27px;width:201px;background:#000;margin-top:3px}
.legacy-paperdoll .dropdown1,.legacy-paperdoll .stats1{float:left}
.legacy-paperdoll .dropdown2,.legacy-paperdoll .stats2{float:right}
.legacy-paperdoll .stats1,.legacy-paperdoll .stats2{position:relative;height:96px;width:201px;margin-top:3px}
.legacy-paperdoll .stats-select{width:201px;height:23px;background:url('/armory/images/profile-dd.gif') no-repeat 0 0;border:0;color:#fff;padding:2px 8px 0;font-size:12px;appearance:none;-webkit-appearance:none;-moz-appearance:none;background-size:100% 100%}
.legacy-paperdoll .stats-select:focus{outline:none}
.legacy-paperdoll .stats-panel{position:absolute;left:5px;right:6px;top:30px;bottom:6px;display:none}
.legacy-paperdoll .stats-panel.is-active{display:block}
.legacy-paperdoll .character-stats{margin:0;padding:0;list-style:none}
.legacy-paperdoll .character-stats li{height:13px !important;width:100%;line-height:10px;display:flex;justify-content:space-between;gap:10px}
.legacy-paperdoll .character-stats span{color:#ffd800;padding-left:4px;line-height:10px;font-size:10px}
.legacy-paperdoll .character-stats i{line-height:10px;font-size:10px;color:#fff;font-style:normal}


@media (max-width:1100px){.character-grid.character-grid-overview{grid-template-columns:1fr}}
@media (max-width:760px){.character-paperdoll-shell{padding:10px 6px 8px;overflow-x:auto}.legacy-paperdoll{transform:scale(.88);transform-origin:top center;margin-bottom:-48px}}
</style>
<style>
.character-gear-showcase{padding:20px 18px 18px;background:linear-gradient(180deg,rgba(4,8,18,.94),rgba(6,11,22,.98));overflow:hidden}
.character-gear-showcase .character-panel-title{text-align:center;margin-bottom:20px}
.character-paperdoll-modern{position:relative;width:660px;max-width:100%;min-height:760px;margin:0 auto}
.character-paperdoll-modern::before{content:"";position:absolute;inset:14% 18% 12%;border-radius:34px;background:radial-gradient(circle at center,rgba(74,129,255,.16),transparent 58%),radial-gradient(circle at center,rgba(255,194,76,.08),transparent 72%);pointer-events:none}
.character-paperdoll-modern > .character-gear-column:first-child{top:14px;left:6px}
.character-paperdoll-modern > .character-gear-column:nth-child(3){top:14px;right:6px}
.character-paperdoll-modern .character-gear-column,.character-paperdoll-modern .character-gear-bottom{display:grid;gap:12px}
.character-paperdoll-modern .character-gear-bottom{left:50%;bottom:8px;grid-template-columns:repeat(3,68px);transform:translateX(-50%)}
.character-paperdoll-modern .character-gear-card{width:68px;height:68px;border-radius:18px;border:1px solid rgba(255,208,97,.32);background:linear-gradient(180deg,rgba(18,25,42,.96),rgba(7,10,17,.98));box-shadow:0 10px 30px rgba(0,0,0,.4),inset 0 0 0 1px rgba(255,255,255,.04)}
.character-paperdoll-modern .character-gear-card:hover{transform:translateY(-1px);border-color:rgba(255,221,131,.58)}
.character-paperdoll-modern .character-gear-card.is-empty{background:linear-gradient(180deg,rgba(13,18,30,.75),rgba(7,10,17,.82));border-style:dashed}
.character-paperdoll-modern .character-gear-card.is-empty::after{color:#8d7c55;font-size:.62rem;padding:6px}
.character-paperdoll-core{position:absolute;inset:18px 104px 118px;z-index:1;display:grid;gap:12px;align-content:start;padding:14px;border-radius:26px;border:1px solid rgba(255,204,72,.18);background:linear-gradient(180deg,rgba(10,16,30,.86),rgba(6,11,22,.92));backdrop-filter:blur(10px);box-shadow:0 18px 46px rgba(0,0,0,.42)}
.character-paperdoll-top{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(150px,.75fr);gap:12px}
.character-paperdoll-card{padding:14px 16px;border-radius:18px;border:1px solid rgba(255,204,72,.14);background:linear-gradient(180deg,rgba(16,22,38,.96),rgba(9,12,22,.96))}
.character-paperdoll-card h3{margin:0 0 10px;color:#c8b27c;font-size:.75rem;letter-spacing:.12em;text-transform:uppercase}
.character-paperdoll-spec{display:flex;gap:12px;align-items:center}
.character-paperdoll-spec-icon{width:44px;height:44px;border-radius:14px;border:1px solid rgba(255,204,72,.22);background:rgba(0,0,0,.32);padding:6px;flex:none}
.character-paperdoll-spec-icon img{display:block;width:100%;height:100%;object-fit:cover}
.character-paperdoll-spec-name{color:#fff3c4;font-size:1.2rem;font-weight:800;line-height:1.05}
.character-paperdoll-spec-breakdown{margin-top:4px;color:#ffd467;font-size:1rem;font-weight:700}
.character-paperdoll-trees{display:grid;gap:8px;margin-top:12px}
.character-paperdoll-tree{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center}
.character-paperdoll-tree strong{color:#f7edd0;font-size:.95rem}
.character-paperdoll-tree span{color:#ffd467;font-weight:800}
.character-paperdoll-tree-bar{grid-column:1 / -1;height:10px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.08)}
.character-paperdoll-tree-bar b{display:block;height:100%;background:linear-gradient(90deg,#ffc44d,#ffd976)}
.character-paperdoll-resists{display:grid;gap:8px}
.character-paperdoll-resist{display:flex;justify-content:space-between;align-items:center;padding:9px 10px;border-radius:12px;background:rgba(255,255,255,.03);color:#f6e5b2;font-weight:700}
.character-paperdoll-resist span{color:#c8b27c;text-transform:uppercase;letter-spacing:.08em;font-size:.75rem}
.character-paperdoll-resist strong{min-width:34px;text-align:center;padding:4px 8px;border-radius:999px;background:rgba(255,204,72,.12);border:1px solid rgba(255,204,72,.2);color:#fff}
.character-sheet-bars{padding:14px 16px;border-radius:18px;border:1px solid rgba(255,204,72,.14);background:linear-gradient(180deg,rgba(27,20,7,.95),rgba(12,10,6,.98))}
.character-sheet-stats{gap:12px;background:none}
.character-sheet-statbox{border-radius:18px;border:1px solid rgba(255,204,72,.14);overflow:hidden}
.character-sheet-statbox-head{padding:12px 14px}
.character-paperdoll-select{width:100%;height:36px;padding:0 12px;border-radius:12px;border:1px solid rgba(255,204,72,.18);background:rgba(8,12,22,.92);color:#f7edd0;font-size:.95rem;font-weight:700}
.character-paperdoll-select:focus{outline:none;border-color:rgba(255,221,131,.4)}
.character-paperdoll-select-wrap{padding:12px 14px 0}
.character-sheet-statbox-body{padding:12px 14px 14px}
.character-sheet-statlist{gap:8px}
.character-sheet-statrow{font-size:.98rem}.character-paperdoll-stats-wide{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.character-paperdoll-stats-wide .stats-panel{display:none}.character-paperdoll-stats-wide .stats-panel.is-active{display:block}
.character-reputation-sections{display:grid;gap:18px}
.character-reputation-section{padding:18px;border-radius:18px;border:1px solid rgba(255,204,72,.12);background:linear-gradient(180deg,rgba(10,14,26,.88),rgba(5,8,18,.9))}
.character-reputation-section-title{margin:0 0 14px;color:#fff3c4;font-size:1.5rem}
.character-reputation-list{display:grid;gap:12px}
.character-reputation-item{padding:16px 18px;border-radius:16px;border:1px solid rgba(255,204,72,.1);background:rgba(255,255,255,.02)}
.character-reputation-head{display:flex;justify-content:space-between;align-items:center;gap:16px}
.character-reputation-name{margin:0;color:#f7edd0;font-size:1.1rem;font-weight:800}
.character-reputation-rank{display:inline-flex;align-items:center;min-height:32px;padding:0 12px;border-radius:999px;border:1px solid rgba(255,255,255,.08);font-size:.82rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.character-reputation-meta{margin-top:10px;color:#bca87a;font-size:.96rem;line-height:1.45}
.character-reputation-track{position:relative;height:18px;margin-top:12px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.08)}
.character-reputation-fill{height:100%;background:linear-gradient(90deg,#b68b00,#ffd467)}
.character-reputation-value{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.88rem;font-weight:800;letter-spacing:.03em;text-shadow:0 1px 2px rgba(0,0,0,.85)}
.rep-hated .character-reputation-rank,.rep-hated .character-reputation-fill{background:linear-gradient(90deg,#6b0000,#b50f0f);color:#ffe4e4}
.rep-hostile .character-reputation-rank,.rep-hostile .character-reputation-fill{background:linear-gradient(90deg,#8b2500,#d14a00);color:#fff0dc}
.rep-unfriendly .character-reputation-rank,.rep-unfriendly .character-reputation-fill{background:linear-gradient(90deg,#8b5f00,#d19a00);color:#fff4d8}
.rep-neutral .character-reputation-rank,.rep-neutral .character-reputation-fill{background:linear-gradient(90deg,#827200,#c1ae00);color:#fffbd6}
.rep-friendly .character-reputation-rank,.rep-friendly .character-reputation-fill{background:linear-gradient(90deg,#587d00,#90b900);color:#eefbd4}
.rep-honored .character-reputation-rank,.rep-honored .character-reputation-fill{background:linear-gradient(90deg,#00735d,#00a884);color:#d8fff5}
.rep-revered .character-reputation-rank,.rep-revered .character-reputation-fill{background:linear-gradient(90deg,#005f96,#2397df);color:#e0f5ff}
.rep-exalted .character-reputation-rank,.rep-exalted .character-reputation-fill{background:linear-gradient(90deg,#0084a8,#20d0e8);color:#e1fdff}
.character-quest-list{display:grid;gap:12px}
.character-quest-item{padding:16px 18px;border-radius:16px;border:1px solid rgba(255,204,72,.1);background:rgba(255,255,255,.02)}
.character-quest-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px}
.character-quest-title{margin:0;color:#f7edd0;font-size:1.08rem;font-weight:800}
.character-quest-status{display:inline-flex;align-items:center;min-height:30px;padding:0 12px;border-radius:999px;border:1px solid rgba(255,255,255,.08);font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#ffe39a;background:rgba(255,204,72,.08)}
.character-quest-meta{margin-top:6px;color:#aa9870;font-size:.9rem}
.character-quest-progress{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.character-quest-chip{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;border:1px solid rgba(255,204,72,.14);background:rgba(255,204,72,.06);color:#f6e5b2;font-size:.8rem;font-weight:700}
.character-quest-disclosure{border:1px solid rgba(255,196,0,.16);border-radius:18px;background:rgba(5,8,18,.38);overflow:hidden}
.character-quest-disclosure[open]{background:rgba(5,8,18,.5)}
.character-quest-summary{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 18px;cursor:pointer;list-style:none;color:#ffe39a;font-size:1.15rem;font-weight:800}
.character-quest-summary::-webkit-details-marker{display:none}
.character-quest-summary::after{content:'+';display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;border:1px solid rgba(255,204,72,.18);color:#ffd467;font-size:1rem;font-weight:800;flex:0 0 auto}
.character-quest-disclosure[open] > .character-quest-summary::after{content:'-'}
.character-quest-disclosure-body{padding:0 18px 18px;display:grid;gap:12px}
.character-questlog-shell{display:grid;grid-template-columns:minmax(280px,.82fr) minmax(0,1.35fr);gap:18px;align-items:start}
.character-questlog-sidebar,.character-questlog-detail{border-radius:18px;border:1px solid rgba(255,196,0,.16);background:rgba(5,8,18,.45)}
.character-questlog-sidebar{padding:14px}
.character-questlog-detail{padding:20px 22px;background:linear-gradient(180deg,rgba(52,35,14,.32),rgba(13,11,9,.92)),radial-gradient(circle at top,rgba(255,215,140,.12),transparent 58%)}
.character-questlog-heading{margin:0 0 12px;color:#ffe39a;font-size:.86rem;letter-spacing:.12em;text-transform:uppercase}
.character-questlog-list{display:grid;gap:8px}
.character-questlog-entry{display:grid;gap:4px;width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,204,72,.1);background:rgba(255,255,255,.02);color:#f7edd0;text-align:left;cursor:pointer}
.character-questlog-entry.is-active{border-color:rgba(255,204,72,.34);background:linear-gradient(180deg,rgba(255,212,95,.18),rgba(255,212,95,.05));box-shadow:inset 0 1px 0 rgba(255,255,255,.06)}
.character-questlog-entry-title{font-size:1rem;font-weight:800;color:#f7edd0}
.character-questlog-entry-meta{color:#bca87a;font-size:.85rem}
.character-questlog-entry-status{color:#ffe39a;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;font-weight:800}
.character-questlog-panel{display:none}
.character-questlog-panel.is-active{display:block}
.character-questlog-status{display:inline-flex;align-items:center;min-height:32px;padding:0 12px;border-radius:999px;border:1px solid rgba(255,255,255,.08);font-size:.8rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#ffe39a;background:rgba(255,204,72,.08)}
.character-questlog-title{margin:12px 0 10px;color:#fff0c8;font-size:1.7rem;line-height:1.1}
.character-questlog-level{color:#c8b78c;font-size:.9rem;letter-spacing:.08em;text-transform:uppercase}
.character-questlog-body{margin-top:18px;padding:18px;border-radius:16px;border:1px solid rgba(255,204,72,.1);background:rgba(255,245,220,.05);color:#f2e4bf;font-size:1.02rem;line-height:1.7}
.character-questlog-section{margin-top:18px}
.character-questlog-section-title{margin:0 0 10px;color:#ffe39a;font-size:.84rem;letter-spacing:.12em;text-transform:uppercase}
.character-questlog-objectives{display:grid;gap:8px}
.character-questlog-objective{padding:10px 12px;border-radius:12px;border:1px solid rgba(255,204,72,.12);background:rgba(255,255,255,.03);color:#f6e5b2}
.character-questlog-empty{color:#bda877;font-style:italic}
.character-questlog-rewards{display:grid;gap:10px}
.character-questlog-reward-group{display:grid;gap:8px}
.character-questlog-reward-label{color:#c8b78c;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase}
.character-questlog-reward-items{display:grid;gap:8px}
.character-questlog-reward-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,204,72,.12);background:rgba(255,255,255,.03);color:#f7edd0;text-decoration:none}
.character-questlog-reward-item img{width:28px;height:28px;border-radius:8px;border:1px solid rgba(255,204,72,.18);background:#090909}
.character-questlog-reward-money{padding:10px 12px;border-radius:12px;border:1px solid rgba(255,204,72,.12);background:rgba(255,255,255,.03);color:#f6e5b2}
@media (max-width:900px){.character-questlog-shell{grid-template-columns:1fr}.character-questlog-detail{padding:18px}}
.character-skill-sections{display:grid;gap:18px}
.character-skill-section{padding:18px 22px;border-radius:18px;border:1px solid rgba(255,196,0,.16);background:rgba(5,8,18,.5)}
.character-skill-section-title{margin:0 0 14px;color:#ffe39a;font-size:1.28rem}
.character-skill-grid{display:grid;gap:12px}
.character-skill-card{padding:16px 18px;border-radius:16px;border:1px solid rgba(255,204,72,.14);background:rgba(4,7,16,.72)}
.character-skill-card-head{display:flex;align-items:center;justify-content:space-between;gap:16px}
.character-skill-card-title{display:flex;align-items:center;gap:12px}
.character-skill-card-title img{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,204,72,.22);background:#090909}
.character-skill-card-title strong{display:block;color:#f7edd0;font-size:1.08rem}
.character-skill-rank{color:#ffd467;font-weight:800;font-size:1rem}
.character-skill-specialization{margin-top:4px;color:#9fd2ff;font-size:.86rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.character-skill-card .character-bar-track{margin-top:12px;height:16px;position:relative}
.character-skill-value{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.86rem;font-weight:800;text-shadow:0 1px 2px rgba(0,0,0,.85)}
.character-profession-recipes{margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,204,72,.12)}
.character-profession-recipes summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;list-style:none;color:#ffe39a;font-weight:800}
.character-profession-recipes summary::-webkit-details-marker{display:none}
.character-profession-recipes summary span{color:#bda87a;font-size:.9rem;font-weight:700}
.character-profession-recipes[open] summary{margin-bottom:12px}
.character-recipe-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
.character-recipe-filter{height:32px;padding:0 12px;border-radius:999px;border:1px solid rgba(255,204,72,.18);background:rgba(8,12,22,.88);color:#f7edd0;font-size:.84rem;font-weight:700;cursor:pointer}
.character-recipe-filter.is-active{background:linear-gradient(90deg,#8b6500,#c99a00);border-color:rgba(255,217,118,.4);color:#fff8e0}
.character-recipe-list{display:grid;gap:10px}
.character-recipe-row{display:grid;grid-template-columns:42px minmax(0,1fr);gap:12px;align-items:flex-start;padding:12px;border-radius:14px;border:1px solid rgba(255,204,72,.1);background:rgba(255,255,255,.02);text-decoration:none}
.character-recipe-row.is-hidden{display:none}
.character-recipe-row img{width:42px;height:42px;border-radius:12px;border:1px solid rgba(255,204,72,.18);background:#080808}
.character-recipe-row strong{display:block;color:#f7edd0;font-size:.98rem}
.character-recipe-row .character-recipe-source{margin-top:4px;color:#bca87a;font-size:.88rem;line-height:1.35}
.character-recipe-badges{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.character-recipe-badge{display:inline-flex;align-items:center;min-height:24px;padding:0 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.character-recipe-badge.is-faction{background:rgba(45,124,209,.16);color:#9fd2ff}
.character-recipe-badge.is-rare-drop{background:rgba(162,76,232,.16);color:#d6b4ff}
.character-recipe-badge.is-endgame{background:rgba(255,204,72,.16);color:#ffe39a}
.character-recipe-badge.is-flask{background:rgba(63,169,92,.16);color:#b7ffcd}
.character-recipe-empty{padding:12px 14px;border-radius:14px;border:1px dashed rgba(255,204,72,.18);color:#bca87a;background:rgba(255,255,255,.02)}
@media (max-width:760px){.character-gear-showcase{padding:16px 10px 14px}.character-paperdoll-modern{width:100%;min-height:auto;display:grid;gap:18px}.character-paperdoll-modern > .character-gear-column:first-child,.character-paperdoll-modern > .character-gear-column:nth-child(3),.character-paperdoll-modern .character-paperdoll-core,.character-paperdoll-modern .character-gear-bottom{position:relative;inset:auto;left:auto;right:auto;bottom:auto;transform:none}.character-paperdoll-modern .character-paperdoll-core{order:-1}.character-paperdoll-modern .character-gear-column,.character-paperdoll-modern .character-gear-bottom{grid-template-columns:repeat(4,68px);justify-content:center}.character-paperdoll-top,.character-sheet-stats,.character-paperdoll-stats-wide{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" type="text/css" href="/armory/css/armory-tooltips.css" />
<style>
.modern-item-tooltip{min-width:220px;max-width:min(420px,calc(100vw - 24px));max-height:calc(100vh - 24px);overflow:auto}
.modern-item-tooltip-loading{padding:14px 16px;color:#f5e6b2;border:1px solid rgba(255,196,0,.35);border-radius:10px;background:rgba(5,8,18,.96);box-shadow:0 16px 38px rgba(0,0,0,.45)}
#modern-item-tooltip{position:fixed;z-index:9999;pointer-events:none;display:none}
</style><script>
let modernTooltipNode = null;
const modernTooltipCache = new Map();
let modernTooltipRequestToken = 0;

function modernTooltipEnsure() {
  if (!modernTooltipNode) {
    modernTooltipNode = document.createElement('div');
    modernTooltipNode.id = 'modern-item-tooltip';
    modernTooltipNode.className = 'talent-tt';
    document.body.appendChild(modernTooltipNode);
  }
  return modernTooltipNode;
}

function modernShowTooltip(event, html) {
  const tip = modernTooltipEnsure();
  tip.innerHTML = html;
  tip.style.display = 'block';
  modernMoveTooltip(event);
}

function modernTooltipLoadingHtml() {
  return '<div class="modern-item-tooltip modern-item-tooltip-loading">Loading item tooltip...</div>';
}

function modernTooltipErrorHtml() {
  return '<div class="modern-item-tooltip modern-item-tooltip-loading">Unable to load item tooltip.</div>';
}

function modernRequestTooltip(event, itemId, realmId) {
  const cacheKey = realmId + ':' + itemId;
  if (modernTooltipCache.has(cacheKey)) {
    modernShowTooltip(event, modernTooltipCache.get(cacheKey));
    return;
  }

  modernShowTooltip(event, modernTooltipLoadingHtml());
  modernTooltipRequestToken += 1;
  const token = modernTooltipRequestToken;
  const url = 'modern-item-tooltip.php?item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId);

  fetch(url, {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('tooltip request failed');
      }
      return response.text();
    })
    .then(function (html) {
      const safeHtml = html && html.trim() !== '' ? html : modernTooltipErrorHtml();
      modernTooltipCache.set(cacheKey, safeHtml);
      if (token === modernTooltipRequestToken) {
        modernShowTooltip(event, safeHtml);
      }
    })
    .catch(function () {
      if (token === modernTooltipRequestToken) {
        modernShowTooltip(event, modernTooltipErrorHtml());
      }
    });
}

function modernMoveTooltip(event) {
  const tip = modernTooltipEnsure();
  if (tip.style.display === 'none') return;
  const offset = 18;
  const rect = tip.getBoundingClientRect();
  const spaceRight = window.innerWidth - event.clientX - offset - 12;
  const spaceLeft = event.clientX - offset - 12;
  const spaceBelow = window.innerHeight - event.clientY - offset - 12;
  const spaceAbove = event.clientY - offset - 12;
  let left = spaceRight >= rect.width || spaceRight >= spaceLeft
    ? event.clientX + offset
    : event.clientX - rect.width - offset;
  let top = spaceBelow >= rect.height || spaceBelow >= spaceAbove
    ? event.clientY + offset
    : event.clientY - rect.height - offset;
  left = Math.max(12, Math.min(left, window.innerWidth - rect.width - 12));
  top = Math.max(12, Math.min(top, window.innerHeight - rect.height - 12));
  tip.style.left = left + 'px';
  tip.style.top = top + 'px';
}

function modernHideTooltip() {
  if (modernTooltipNode) modernTooltipNode.style.display = 'none';
}

function sppPaperdollSwap(selectEl, targetId) {
  var wrap = document.getElementById(targetId);
  if (!wrap) return;
  var key = selectEl.value;
  var panels = wrap.querySelectorAll('[data-panel]');
  for (var i = 0; i < panels.length; i++) {
    panels[i].classList.toggle('is-active', panels[i].getAttribute('data-panel') === key);
  }
}

function sppRecipeFilter(buttonEl, listId, filterKey) {
  var list = document.getElementById(listId);
  if (!list) return;
  var buttons = buttonEl.parentNode ? buttonEl.parentNode.querySelectorAll('.character-recipe-filter') : [];
  for (var i = 0; i < buttons.length; i++) {
    buttons[i].classList.toggle('is-active', buttons[i] === buttonEl);
  }
  var rows = list.querySelectorAll('.character-recipe-row');
  for (var r = 0; r < rows.length; r++) {
    var row = rows[r];
    var tags = row.getAttribute('data-tags') || '';
    var visible = filterKey === 'all' || tags.indexOf('|' + filterKey + '|') !== -1;
    row.classList.toggle('is-hidden', !visible);
  }
}
</script>
<div class="character-page">
<?php if ($pageError !== ''): ?>
  <div class="character-error"><?php echo htmlspecialchars($pageError); ?></div>
<?php elseif ($character): ?>
  <section class="character-hero">
    <div class="character-hero-mark" aria-hidden="true"><img src="<?php echo htmlspecialchars($factionHeroLogo); ?>" alt=""></div>
    <div>
      <div class="character-identity">
        <img class="character-portrait" src="<?php echo htmlspecialchars($portraitUrl); ?>" alt="">
        <div>
          <p class="character-eyebrow"><?php echo htmlspecialchars($realmLabel); ?> Character Profile</p>
          <h1 class="character-title class-<?php echo htmlspecialchars($classSlug); ?>"><a href="<?php echo htmlspecialchars($characterUrl); ?>"><?php echo htmlspecialchars($characterName); ?></a></h1>
          <p class="character-subtitle">Level <?php echo (int)$character['level']; ?> <?php echo htmlspecialchars($raceNames[(int)$character['race']] ?? 'Unknown'); ?> <?php echo htmlspecialchars($classNames[(int)$character['class']] ?? 'Unknown'); ?></p>
          <div class="character-guildline"><?php if ($guildId > 0 && $guildName !== ''): ?><a href="<?php echo htmlspecialchars('index.php?n=server&sub=guild&realm=' . (int)$realmId . '&guildid=' . $guildId); ?>" style="color:#ffe39a;text-decoration:none;font-weight:700;">&lt;<?php echo htmlspecialchars($guildName); ?>&gt;</a><?php else: ?><span style="color:#aa9870;">Unaffiliated</span><?php endif; ?></div>
        </div>
      </div>
    </div>
    <div class="character-hero-grid">
      <div class="character-stat-card"><span class="character-stat-label">Play Time</span><div class="character-stat-value"><?php echo htmlspecialchars(spp_character_format_playtime($character['totaltime'] ?? 0)); ?></div><div class="character-fact-sub"><?php echo !empty($character['online']) ? 'Online' : 'Offline'; ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Achievement Points</span><div class="character-stat-value"><?php echo (int)$achievementSummary['points']; ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Honorable Kills</span><div class="character-stat-value"><?php echo number_format($honorableKills, 0, '.', ','); ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Honor Points</span><div class="character-stat-value"><?php echo number_format($honorPoints, 0, '.', ','); ?></div></div>
    </div>
  </section>

  <nav class="character-tabs">
    <?php foreach ($tabs as $tabName): ?>
      <a class="character-tab<?php echo $tab === $tabName ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($characterUrl . '&tab=' . urlencode($tabName)); ?>"><?php echo htmlspecialchars(ucfirst($tabName)); ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($tab === 'overview'): ?>
    <section class="character-grid character-grid-overview">
      <div class="character-panel"><div class="character-facts"><div class="character-fact"><span>Realm</span><strong><?php echo htmlspecialchars($realmLabel); ?></strong></div><div class="character-fact"><span>Guild</span><strong><?php echo $guildName !== "" ? htmlspecialchars($guildName) : "Unaffiliated"; ?></strong></div><div class="character-fact"><span>Time At Level</span><strong><?php echo htmlspecialchars(spp_character_format_playtime((int)($character['leveltime'] ?? 0))); ?></strong></div><div class="character-fact"><span>Gear Rank</span><strong><?php echo htmlspecialchars($gearRank); ?></strong><?php if ($averageItemLevel > 0): ?><div class="character-fact-sub">Average item level <?php echo number_format($averageItemLevel, 1); ?></div><?php endif; ?></div><div class="character-fact"><span>Recent Gear</span><?php if (!empty($recentGear)): ?><div class="character-fact-list"><?php foreach ($recentGear as $item): ?><a class="character-fact-link quality-<?php echo (int)$item['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($item['icon']); ?>" alt=""><span><?php echo htmlspecialchars($item['name']); ?></span></a><?php endforeach; ?></div><?php else: ?><strong>No recent gear recorded.</strong><?php endif; ?></div><div class="character-fact"><span>Recently Completed Quests</span><?php if (!empty($completedQuestHistory)): ?><div class="character-fact-list"><?php foreach (array_slice($completedQuestHistory, 0, 5) as $quest): ?><div class="character-fact-link is-quest"><?php echo htmlspecialchars($quest['title']); ?></div><?php endforeach; ?></div><?php else: ?><strong>No completed quests recorded.</strong><?php endif; ?></div><div class="character-fact"><span>Last Instance</span><strong><?php echo $lastInstance !== "" ? htmlspecialchars($lastInstance) : "No recorded run"; ?></strong><?php if ($lastInstanceDate > 0): ?><div class="character-fact-sub"><?php echo gmdate('M j, Y', $lastInstanceDate); ?></div><?php endif; ?></div></div></div>
      <section class="character-panel character-gear-showcase">
         <div class="character-gear-stage character-paperdoll-modern">
          <div class="character-gear-column character-gear-column-left">
            <?php foreach ($paperdollLeftSlots as $slotId): $item = $equipment[$slotId] ?? null; ?>
              <div class="character-gear-slot">
                <?php if ($item): ?>
                                <a class="character-gear-card" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()">
                    <img src="<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  </a>
                <?php else: ?>
                  <div class="character-gear-card is-empty" data-slot="<?php echo htmlspecialchars($slotNames[$slotId] ?? ('Slot ' . $slotId)); ?>"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="character-gear-center character-paperdoll-core">
            <div class="character-paperdoll-top">
              <div class="character-paperdoll-card">
                <h3>Talent Specialization</h3>
                <div class="character-paperdoll-spec">
                  <?php if (!empty($talentList[0]['icon'])): ?><div class="character-paperdoll-spec-icon"><img src="<?php echo htmlspecialchars($talentList[0]['icon']); ?>" alt=""></div><?php endif; ?>
                  <div>
                    <div class="character-paperdoll-spec-name"><?php echo htmlspecialchars($specName); ?></div>
                    <div class="character-paperdoll-spec-breakdown"><?php echo htmlspecialchars($specBreakdown); ?></div>
                  </div>
                </div>
                <div class="character-paperdoll-trees">
                  <?php if (!empty($talentTreesView)): ?>
                    <?php foreach ($talentTreesView as $tree): ?>
                      <div class="character-paperdoll-tree">
                        <strong><?php echo htmlspecialchars($tree['name']); ?></strong>
                        <span><?php echo (int)$tree['points']; ?> / <?php echo (int)$talentBarCap; ?></span>
                        <div class="character-paperdoll-tree-bar"><b style="width: <?php echo min(100, max(0, round(((int)$tree['points'] / $talentBarCap) * 100))); ?>%"></b></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="character-empty">No talent data yet.</div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="character-paperdoll-card">
                <h3>Resistances</h3>
                <div class="character-paperdoll-resists">
                  <?php foreach (array('Arcane', 'Fire', 'Nature', 'Frost', 'Shadow') as $label): ?>
                    <div class="character-paperdoll-resist"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo (int)$resistanceStats[$label]; ?></strong></div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="character-sheet-bars">
              <div class="character-sheet-bar-row"><span>Health</span><div class="character-sheet-bar-track"><div class="character-sheet-bar-fill is-health" style="width: <?php echo $healthMax > 0 ? min(100, max(0, round(($healthCurrent / $healthMax) * 100))) : 0; ?>%;"></div><div class="character-sheet-bar-value"><?php echo number_format($healthCurrent, 0, '.', ','); ?> / <?php echo number_format($healthMax, 0, '.', ','); ?></div></div></div>
              <div class="character-sheet-bar-row"><span><?php echo htmlspecialchars($primaryResource['label']); ?></span><div class="character-sheet-bar-track"><div class="character-sheet-bar-fill <?php echo htmlspecialchars($primaryResource['class']); ?>" style="width: <?php echo $resourceMax > 0 ? min(100, max(0, round(($resourceCurrent / $resourceMax) * 100))) : 0; ?>%;"></div><div class="character-sheet-bar-value"><?php echo number_format($resourceCurrent, 0, '.', ','); ?> / <?php echo number_format($resourceMax, 0, '.', ','); ?></div></div></div>
            </div>
          </div>
          <div class="character-gear-column character-gear-column-right">
            <?php foreach ($paperdollRightSlots as $slotId): $item = $equipment[$slotId] ?? null; ?>
              <div class="character-gear-slot">
                <?php if ($item): ?>
                                <a class="character-gear-card" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()">
                    <img src="<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  </a>
                <?php else: ?>
                  <div class="character-gear-card is-empty" data-slot="<?php echo htmlspecialchars($slotNames[$slotId] ?? ('Slot ' . $slotId)); ?>"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="character-gear-bottom">
            <?php foreach ($paperdollBottomSlots as $slotId): $item = $equipment[$slotId] ?? null; ?>
              <div class="character-gear-slot">
                <?php if ($item): ?>
                                <a class="character-gear-card" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()">
                    <img src="<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  </a>
                <?php else: ?>
                  <div class="character-gear-card is-empty" data-slot="<?php echo htmlspecialchars($slotNames[$slotId] ?? ('Slot ' . $slotId)); ?>"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </section>
      <section class="character-panel">
        <div class="character-paperdoll-stats-wide">
          <div class="character-sheet-statbox">
            <div class="character-paperdoll-select-wrap"><select class="character-paperdoll-select" onchange="sppPaperdollSwap(this, 'paperdoll-left-panels')"><?php foreach ($paperdollLeftPanels as $key => $panel): ?><option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($panel['label']); ?></option><?php endforeach; ?></select></div>
            <div class="character-sheet-statbox-body"><div id="paperdoll-left-panels"><?php foreach ($paperdollLeftPanels as $key => $panel): ?><div class="stats-panel<?php echo $key === 'base' ? ' is-active' : ''; ?>" data-panel="<?php echo htmlspecialchars($key); ?>"><ul class="character-sheet-statlist"><?php foreach ($panel['rows'] as $label => $value): ?><li class="character-sheet-statrow"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars((string)$value); ?></strong></li><?php endforeach; ?></ul></div><?php endforeach; ?></div></div>
          </div>
          <div class="character-sheet-statbox">
            <div class="character-paperdoll-select-wrap"><select class="character-paperdoll-select" onchange="sppPaperdollSwap(this, 'paperdoll-right-panels')"><?php foreach ($paperdollRightPanels as $key => $panel): ?><option value="<?php echo htmlspecialchars($key); ?>"<?php echo $key === $paperdollRightDefault ? ' selected' : ''; ?>><?php echo htmlspecialchars($panel['label']); ?></option><?php endforeach; ?></select></div>
            <div class="character-sheet-statbox-body"><div id="paperdoll-right-panels"><?php foreach ($paperdollRightPanels as $key => $panel): ?><div class="stats-panel<?php echo $key === $paperdollRightDefault ? ' is-active' : ''; ?>" data-panel="<?php echo htmlspecialchars($key); ?>"><ul class="character-sheet-statlist"><?php foreach ($panel['rows'] as $label => $value): ?><li class="character-sheet-statrow"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars((string)$value); ?></strong></li><?php endforeach; ?></ul></div><?php endforeach; ?></div></div>
          </div>
        </div>
      </section>
  <?php endif; ?>

  <?php if ($tab === 'talents'): ?><div style="margin-top:4px;"><?php $__savedGet = $_GET; $_GET['realm'] = (string)$realmId; $_GET['character'] = (string)$characterName; $_GET['mode'] = 'profile'; $_GET['embed'] = '1'; unset($_GET['class']); include($siteRoot . '/templates/offlike/server/server.talents.php'); $_GET = $__savedGet; ?></div><?php endif; ?>
  <?php if ($tab === 'reputation'): ?><section class="character-panel"><h2 class="character-panel-title">Reputation</h2><?php if (!empty($reputationSections)): ?><div class="character-reputation-sections"><?php foreach ($reputationSections as $sectionLabel => $sectionReputations): ?><section class="character-reputation-section"><h3 class="character-reputation-section-title"><?php echo htmlspecialchars($sectionLabel); ?></h3><div class="character-reputation-list"><?php foreach ($sectionReputations as $reputation): ?><article class="character-reputation-item rep-<?php echo htmlspecialchars($reputation['tier']); ?>"><div class="character-reputation-head"><h4 class="character-reputation-name"><?php echo htmlspecialchars($reputation['name']); ?></h4><span class="character-reputation-rank"><?php echo htmlspecialchars($reputation['label']); ?></span></div><div class="character-reputation-track"><div class="character-reputation-fill" style="width: <?php echo (int)$reputation['percent']; ?>%"></div><div class="character-reputation-value"><?php echo (int)$reputation['value']; ?>/<?php echo (int)$reputation['max']; ?></div></div><div class="character-reputation-meta"><?php if ($reputation['description'] !== ''): ?><?php echo htmlspecialchars($reputation['description']); ?><?php endif; ?></div></article><?php endforeach; ?></div></section><?php endforeach; ?></div><?php elseif (!empty($reputations)): ?><div class="character-reputation-list"><?php foreach ($reputations as $reputation): ?><article class="character-reputation-item rep-<?php echo htmlspecialchars($reputation['tier'] ?? spp_character_reputation_tier($reputation['label'] ?? 'neutral')); ?>"><div class="character-reputation-head"><h4 class="character-reputation-name"><?php echo htmlspecialchars($reputation['name']); ?></h4><span class="character-reputation-rank"><?php echo htmlspecialchars($reputation['label']); ?></span></div><div class="character-reputation-track"><div class="character-reputation-fill" style="width: <?php echo (int)$reputation['percent']; ?>%"></div><div class="character-reputation-value"><?php echo (int)$reputation['value']; ?>/<?php echo (int)$reputation['max']; ?></div></div><div class="character-reputation-meta"><?php if ($reputation['description'] !== ''): ?><?php echo htmlspecialchars($reputation['description']); ?><?php endif; ?></div></article><?php endforeach; ?></div><?php else: ?><div class="character-empty">No visible reputations were found for this character.</div><?php endif; ?></section><?php endif; ?>
  <?php if ($tab === 'skills'): ?><section class="character-panel"><h2 class="character-panel-title">Skills</h2><?php if (!empty($skillsByCategory)): ?><div class="character-skill-sections"><?php foreach ($skillsByCategory as $categoryName => $categorySkills): ?><section class="character-skill-section"><h3 class="character-skill-section-title"><?php echo htmlspecialchars($categoryName); ?></h3><div class="character-skill-grid"><?php foreach ($categorySkills as $skill): ?><article class="character-skill-card"><div class="character-skill-card-head"><div class="character-skill-card-title"><img src="<?php echo htmlspecialchars($skill['icon']); ?>" alt=""><div><strong><?php echo htmlspecialchars($skill['name']); ?></strong><?php if ($skill['description'] !== ''): ?><div class="character-skill-meta"><?php echo htmlspecialchars($skill['description']); ?></div><?php endif; ?></div></div><span class="character-skill-rank"><?php echo htmlspecialchars($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max'])); ?></span></div><div class="character-bar-track"><div class="character-bar-fill" style="width: <?php echo (int)$skill['percent']; ?>%"></div><div class="character-skill-value"><?php echo htmlspecialchars($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max'])); ?></div></div></article><?php endforeach; ?></div></section><?php endforeach; ?></div><?php else: ?><div class="character-empty">No non-class skills could be read from the realm database.</div><?php endif; ?></section><?php endif; ?>
<?php if ($tab === 'professions'): ?><section class="character-panel"><h2 class="character-panel-title">Professions</h2><?php if (!empty($professionsByCategory)): ?><div class="character-skill-sections"><?php foreach ($professionsByCategory as $categoryName => $categorySkills): ?><section class="character-skill-section"><h3 class="character-skill-section-title"><?php echo htmlspecialchars($categoryName); ?></h3><div class="character-skill-grid"><?php foreach ($categorySkills as $skill): ?><article class="character-skill-card"><div class="character-skill-card-head"><div class="character-skill-card-title"><img src="<?php echo htmlspecialchars($skill['icon']); ?>" alt=""><div><strong><?php echo htmlspecialchars($skill['name']); ?></strong><?php if (!empty($skill['specializations'])): ?><div class="character-skill-specialization"><?php echo htmlspecialchars(implode(' / ', $skill['specializations'])); ?></div><?php endif; ?><?php if ($skill['description'] !== ''): ?><div class="character-skill-meta"><?php echo htmlspecialchars($skill['description']); ?></div><?php endif; ?></div></div><span class="character-skill-rank"><?php echo htmlspecialchars($skill['rank_label'] ?? ($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max']))); ?></span></div><div class="character-bar-track"><div class="character-bar-fill" style="width: <?php echo (int)$skill['percent']; ?>%"></div><div class="character-skill-value"><?php echo htmlspecialchars($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max'])); ?></div></div><?php if (!empty($skill['recipes'])): ?><?php $recipeListId = 'recipe-list-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower(($skill['name'] ?? 'skill') . '-' . ($skill['skill_id'] ?? 0))); ?><details class="character-profession-recipes"><summary><strong>Known Special Recipes</strong><span><?php echo (int)count($skill['recipes']); ?> tracked</span></summary><?php if (!empty($skill['recipe_filters'])): ?><div class="character-recipe-filters"><?php foreach ($skill['recipe_filters'] as $filterIndex => $filter): ?><button type="button" class="character-recipe-filter<?php echo $filterIndex === 0 ? ' is-active' : ''; ?>" onclick="sppRecipeFilter(this, '<?php echo htmlspecialchars($recipeListId); ?>', '<?php echo htmlspecialchars($filter['key']); ?>')"><?php echo htmlspecialchars($filter['label']); ?><?php if (!empty($filter['count']) && $filter['key'] !== 'all'): ?> <span>(<?php echo (int)$filter['count']; ?>)</span><?php endif; ?></button><?php endforeach; ?></div><?php endif; ?><div class="character-recipe-list" id="<?php echo htmlspecialchars($recipeListId); ?>"><?php foreach ($skill['recipes'] as $recipe): ?><a class="character-recipe-row quality-<?php echo (int)$recipe['quality']; ?>" data-tags="|<?php echo htmlspecialchars(implode('|', $recipe['tags'] ?? array('all'))); ?>|" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$recipe['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$recipe['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($recipe['icon']); ?>" alt="<?php echo htmlspecialchars($recipe['name']); ?>"><div><strong><?php echo htmlspecialchars($recipe['name']); ?></strong><?php if (($recipe['source'] ?? '') !== ''): ?><div class="character-recipe-source"><?php echo htmlspecialchars($recipe['source']); ?></div><?php endif; ?><div class="character-recipe-badges"><?php if (!empty($recipe['tag_map']['faction'])): ?><span class="character-recipe-badge is-faction">Faction</span><?php endif; ?><?php if (!empty($recipe['tag_map']['rare-drop'])): ?><span class="character-recipe-badge is-rare-drop">Rare Drop</span><?php endif; ?><?php if (!empty($recipe['tag_map']['endgame'])): ?><span class="character-recipe-badge is-endgame">300 Skill</span><?php endif; ?><?php if (!empty($recipe['tag_map']['flask'])): ?><span class="character-recipe-badge is-flask">Flask</span><?php endif; ?></div></div></a><?php endforeach; ?></div></details><?php elseif (stripos((string)$categoryName, 'profession') !== false || stripos((string)$categoryName, 'secondary') !== false): ?><div class="character-profession-recipes"><div class="character-recipe-empty">No tracked special recipes yet. Trainer-learned basics are still covered by the profession rank above.</div></div><?php endif; ?></article><?php endforeach; ?></div></section><?php endforeach; ?></div><?php else: ?><div class="character-empty">No professions or secondary skills could be read from the realm database.</div><?php endif; ?></section><?php endif; ?>
<?php if ($tab === 'quest log'): ?>
  <?php
    $questLogItems = array();
    foreach ($activeQuestLog as $index => $quest) {
        $quest['panel_id'] = 'quest-panel-active-' . $index;
        $quest['entry_id'] = 'quest-entry-active-' . $index;
        $quest['group_label'] = 'Active';
        $questLogItems[] = $quest;
    }
    foreach ($completedQuestHistory as $index => $quest) {
        $quest['panel_id'] = 'quest-panel-completed-' . $index;
        $quest['entry_id'] = 'quest-entry-completed-' . $index;
        $quest['group_label'] = 'Completed';
        $questLogItems[] = $quest;
    }
    $initialQuestPanel = $questLogItems[0]['panel_id'] ?? '';
  ?>
  <section class="character-panel">
    <h2 class="character-panel-title">Quest Log</h2>
    <?php if (!empty($questLogItems)): ?>
      <div class="character-questlog-shell" data-questlog>
        <aside class="character-questlog-sidebar">
          <h3 class="character-questlog-heading">Active Quests</h3>
          <?php if (!empty($activeQuestLog)): ?>
            <div class="character-questlog-list">
              <?php foreach ($activeQuestLog as $index => $quest): $panelId = 'quest-panel-active-' . $index; ?>
                <button type="button" class="character-questlog-entry<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-target="<?php echo htmlspecialchars($panelId); ?>">
                  <span class="character-questlog-entry-title"><?php echo htmlspecialchars($quest['title']); ?></span>
                  <span class="character-questlog-entry-meta"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Quest'; ?></span>
                  <span class="character-questlog-entry-status"><?php echo htmlspecialchars($quest['status_label']); ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="character-questlog-empty">No active quests were found for this character.</div>
          <?php endif; ?>

          <?php if (!empty($completedQuestHistory)): ?>
            <details class="character-quest-disclosure" style="margin-top:16px;">
              <summary class="character-quest-summary">Completed</summary>
              <div class="character-quest-disclosure-body">
                <div class="character-questlog-list">
                  <?php foreach ($completedQuestHistory as $index => $quest): $panelId = 'quest-panel-completed-' . $index; ?>
                    <button type="button" class="character-questlog-entry<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-target="<?php echo htmlspecialchars($panelId); ?>">
                      <span class="character-questlog-entry-title"><?php echo htmlspecialchars($quest['title']); ?></span>
                      <span class="character-questlog-entry-status">Completed</span>
                    </button>
                  <?php endforeach; ?>
                </div>
                <div class="character-fact-sub">Completion timestamps are not available in this table, so this uses rewarded quest history.</div>
              </div>
            </details>
          <?php endif; ?>
        </aside>

        <section class="character-questlog-detail">
          <?php foreach ($activeQuestLog as $index => $quest): $panelId = 'quest-panel-active-' . $index; ?>
            <article class="character-questlog-panel<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-panel="<?php echo htmlspecialchars($panelId); ?>">
              <span class="character-questlog-status"><?php echo htmlspecialchars($quest['status_label']); ?></span>
              <h3 class="character-questlog-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
              <div class="character-questlog-level"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Active Quest'; ?></div>
              <div class="character-questlog-body"><?php echo spp_character_render_quest_text($quest['description'] ?? '', $character['name'] ?? 'adventurer') ?: '<span class="character-questlog-empty">No quest text is available for this quest entry.</span>'; ?></div>
              <div class="character-questlog-section">
                <h4 class="character-questlog-section-title">Objectives</h4>
                <?php if (!empty($quest['progress_parts'])): ?>
                  <div class="character-questlog-objectives">
                    <?php foreach ($quest['progress_parts'] as $part): ?>
                      <div class="character-questlog-objective"><?php echo htmlspecialchars($part); ?></div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="character-questlog-empty">No tracked objective counters are available yet.</div>
                <?php endif; ?>
              </div>
              <?php if (!empty($quest['rewards']['choice']) || !empty($quest['rewards']['guaranteed']) || !empty($quest['rewards']['money'])): ?>
                <div class="character-questlog-section">
                  <h4 class="character-questlog-section-title">Rewards</h4>
                  <div class="character-questlog-rewards">
                    <?php if (!empty($quest['rewards']['choice'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Choose One</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['choice'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['guaranteed'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">You Will Receive</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['guaranteed'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['money']) && (int)$quest['rewards']['money'] > 0): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Money</div>
                        <div class="character-questlog-reward-money"><?php echo number_format((int)$quest['rewards']['money']); ?> copper</div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>

          <?php foreach ($completedQuestHistory as $index => $quest): $panelId = 'quest-panel-completed-' . $index; ?>
            <article class="character-questlog-panel<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-panel="<?php echo htmlspecialchars($panelId); ?>">
              <span class="character-questlog-status">Completed</span>
              <h3 class="character-questlog-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
              <div class="character-questlog-level"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Completed Quest'; ?></div>
              <div class="character-questlog-body"><?php echo spp_character_render_quest_text($quest['description'] ?? '', $character['name'] ?? 'adventurer') ?: '<span class="character-questlog-empty">No quest text is available for this quest entry.</span>'; ?></div>
              <?php if (!empty($quest['rewards']['choice']) || !empty($quest['rewards']['guaranteed']) || !empty($quest['rewards']['money'])): ?>
                <div class="character-questlog-section">
                  <h4 class="character-questlog-section-title">Rewards</h4>
                  <div class="character-questlog-rewards">
                    <?php if (!empty($quest['rewards']['choice'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Choose One</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['choice'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['guaranteed'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">You Will Receive</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['guaranteed'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['money']) && (int)$quest['rewards']['money'] > 0): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Money</div>
                        <div class="character-questlog-reward-money"><?php echo number_format((int)$quest['rewards']['money']); ?> copper</div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </section>
      </div>
    <?php else: ?>
      <div class="character-empty">No quest data was found for this character.</div>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php if ($tab === 'achievements'): ?><section class="character-panel"><h2 class="character-panel-title">Achievements</h2><?php if ($achievementSummary['supported']): ?><div class="character-hero-grid" style="margin-bottom:18px;"><div class="character-stat-card"><span class="character-stat-label">Completed</span><div class="character-stat-value"><?php echo (int)$achievementSummary['count']; ?></div></div><div class="character-stat-card"><span class="character-stat-label">Points</span><div class="character-stat-value"><?php echo (int)$achievementSummary['points']; ?></div></div></div><?php if (!empty($achievementSummary['recent'])): ?><section class="character-achievement-section" style="margin-bottom:22px;"><h3 class="character-achievement-section-title">Recent Earned</h3><div class="character-achievement-list"><?php foreach ($achievementSummary['recent'] as $achievement): ?><article class="character-achievement-item"><img class="character-achievement-icon" src="<?php echo htmlspecialchars($achievement['icon']); ?>" alt=""><div><div class="character-achievement-title"><?php echo htmlspecialchars($achievement['name']); ?></div><?php if (($achievement['description'] ?? '') !== ''): ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['description']); ?></div><?php endif; ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['category'] ?? ''); ?><?php if (($achievement['date_label'] ?? '') !== ''): ?> • <?php echo htmlspecialchars($achievement['date_label']); ?><?php endif; ?></div></div><div class="character-achievement-points-badge">+<?php echo (int)($achievement['points'] ?? 0); ?></div></article><?php endforeach; ?></div></section><?php endif; ?><?php if (!empty($achievementSummary['groups'])): ?><div class="character-achievement-sections"><?php foreach ($achievementSummary['groups'] as $groupName => $subgroups): ?><section class="character-achievement-section"><h3 class="character-achievement-section-title"><?php echo htmlspecialchars($groupName !== '' ? $groupName : 'Other'); ?></h3><?php foreach ($subgroups as $subgroupName => $achievements): ?><?php if ($subgroupName !== ''): ?><h4 class="character-achievement-subtitle"><?php echo htmlspecialchars($subgroupName); ?></h4><?php endif; ?><div class="character-achievement-grid"><?php foreach ($achievements as $achievement): ?><article class="character-achievement-item"><img class="character-achievement-icon" src="<?php echo htmlspecialchars($achievement['icon']); ?>" alt=""><div><div class="character-achievement-title"><?php echo htmlspecialchars($achievement['name']); ?></div><?php if (($achievement['description'] ?? '') !== ''): ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['description']); ?></div><?php endif; ?><?php if (($achievement['date_label'] ?? '') !== ''): ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['date_label']); ?></div><?php endif; ?></div><div class="character-achievement-points-badge">+<?php echo (int)($achievement['points'] ?? 0); ?></div></article><?php endforeach; ?></div><?php endforeach; ?></section><?php endforeach; ?></div><?php elseif (empty($achievementSummary['recent'])): ?><div class="character-empty">This character has no recorded achievements yet.</div><?php endif; ?><?php else: ?><div class="character-empty">Achievements are not available for this realm ruleset or database layout yet.</div><?php endif; ?></section><?php endif; ?>
<?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-questlog]').forEach(function (questLog) {
    var entries = questLog.querySelectorAll('[data-quest-target]');
    var panels = questLog.querySelectorAll('[data-quest-panel]');
    if (!entries.length || !panels.length) return;

    var activate = function (targetId) {
      entries.forEach(function (entry) {
        entry.classList.toggle('is-active', entry.getAttribute('data-quest-target') === targetId);
      });
      panels.forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-quest-panel') === targetId);
      });
    };

    entries.forEach(function (entry) {
      entry.addEventListener('click', function () {
        activate(entry.getAttribute('data-quest-target'));
      });
    });
  });

  document.querySelectorAll('.character-panel').forEach(function (panel) {
    var heading = panel.querySelector('.character-panel-title');
    if (!heading || heading.textContent.trim() !== 'Achievements') return;

    var sections = panel.querySelectorAll('.character-achievement-section');
    sections.forEach(function (section) {
      var title = section.querySelector(':scope > .character-achievement-section-title');
      if (!title) return;

      var body = document.createElement('div');
      body.className = 'character-achievement-section-body';

      while (title.nextSibling) {
        body.appendChild(title.nextSibling);
      }

      if (!body.children.length) return;

      section.appendChild(body);
      section.classList.add('is-collapsible');

      title.tabIndex = 0;
      title.setAttribute('role', 'button');
      title.setAttribute('aria-expanded', 'false');

      var toggle = function () {
        var isOpen = section.classList.toggle('is-open');
        title.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      };

      title.addEventListener('click', toggle);
      title.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          toggle();
        }
      });
    });
  });
});
</script>
<?php builddiv_end(); ?>




















