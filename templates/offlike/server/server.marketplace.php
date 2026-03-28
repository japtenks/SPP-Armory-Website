<?php
$siteDatabaseHandle = $GLOBALS['DB'] ?? null;
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/config/armory/settings.php');
if ($siteDatabaseHandle !== null) {
    $GLOBALS['DB'] = $siteDatabaseHandle;
    $DB = $siteDatabaseHandle;
}

if (!function_exists('spp_marketplace_icon_url')) {
    function spp_marketplace_icon_url($iconName)
    {
        $iconName = trim((string)$iconName);
        if ($iconName === '') {
            return '/templates/offlike/images/armory/icons/64x64/404.png';
        }

        $basename = preg_replace('/\.(png|jpg|jpeg|gif)$/i', '', $iconName);
        return '/templates/offlike/images/armory/icons/64x64/' . strtolower($basename) . '.png';
    }
}

if (!function_exists('spp_marketplace_profession_icon_url')) {
    function spp_marketplace_profession_icon_url($skillId, $iconName = '')
    {
        $skillId = (int)$skillId;
        $overrides = [
            129 => 'inv_misc_bandage_15',
            171 => 'trade_alchemy',
            185 => 'inv_misc_food_15',
            393 => 'inv_misc_pelt_wolf_01',
            333 => 'trade_engraving',
        ];

        if (isset($overrides[$skillId])) {
            return spp_marketplace_icon_url($overrides[$skillId]);
        }

        return spp_marketplace_icon_url($iconName);
    }
}

if (!function_exists('spp_marketplace_class_icon_url')) {
    function spp_marketplace_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $extensions = [
            1 => 'jpg',
            2 => 'jpg',
            3 => 'jpg',
            4 => 'jpg',
            5 => 'jpg',
            6 => 'gif',
            7 => 'jpg',
            8 => 'jpg',
            9 => 'jpg',
            11 => 'jpg',
        ];

        if (!isset($extensions[$classId])) {
            return '/templates/offlike/images/armory/icons/64x64/404.png';
        }

        return '/templates/offlike/images/armory/icons/64x64/class-' . $classId . '.' . $extensions[$classId];
    }
}

if (!function_exists('spp_marketplace_race_icon_url')) {
    function spp_marketplace_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = [
            1 => 'achievement_character_human_' . $gender,
            2 => 'achievement_character_orc_' . $gender,
            3 => 'achievement_character_dwarf_' . $gender,
            4 => 'achievement_character_nightelf_' . $gender,
            5 => 'achievement_character_undead_' . $gender,
            6 => 'achievement_character_tauren_' . $gender,
            7 => 'achievement_character_gnome_' . $gender,
            8 => 'achievement_character_troll_' . $gender,
            10 => 'achievement_character_bloodelf_' . $gender,
            11 => 'achievement_character_draenei_' . $gender,
        ];

        if (!isset($icons[$raceId])) {
            return '/templates/offlike/images/armory/icons/64x64/404.png';
        }

        return '/templates/offlike/images/armory/icons/64x64/' . $icons[$raceId] . '.png';
    }
}

if (!function_exists('spp_marketplace_profession_tier_label')) {
    function spp_marketplace_profession_tier_label($maxRank)
    {
        $maxRank = (int)$maxRank;
        if ($maxRank >= 450) return 'Grand Master';
        if ($maxRank >= 375) return 'Master';
        if ($maxRank >= 300) return 'Artisan';
        if ($maxRank >= 225) return 'Expert';
        if ($maxRank >= 150) return 'Journeyman';
        if ($maxRank >= 75) return 'Apprentice';
        return 'Training';
    }
}

if (!function_exists('spp_marketplace_quality_color')) {
    function spp_marketplace_quality_color($quality)
    {
        switch ((int)$quality) {
            case 0: return '#9d9d9d';
            case 1: return '#ffffff';
            case 2: return '#1eff00';
            case 3: return '#0070dd';
            case 4: return '#a335ee';
            case 5: return '#ff8000';
            default: return '#e6cc80';
        }
    }
}

if (!function_exists('spp_marketplace_recipe_display_name')) {
    function spp_marketplace_recipe_display_name($name)
    {
        $display = preg_replace('/^(recipe|pattern|plans|formula|manual|schematic|book|design|tome|technique)\s*:\s*/i', '', (string)$name);
        return trim((string)$display);
    }
}

if (!function_exists('spp_marketplace_profession_spell_matches_skill')) {
    function spp_marketplace_profession_spell_matches_skill($skillId, $spellName, $itemName = '')
    {
        $skillId = (int)$skillId;
        $haystack = strtolower(trim((string)$spellName . ' ' . $itemName));
        if ($haystack === '') return false;

        $containsAny = function ($needles) use ($haystack) {
            foreach ($needles as $needle) {
                if ($needle !== '' && strpos($haystack, strtolower($needle)) !== false) {
                    return true;
                }
            }
            return false;
        };

        switch ($skillId) {
            case 129:
                return $containsAny(array('bandage', 'anti-venom'));
            case 164:
                return $containsAny(array(
                    'sharpening stone', 'weightstone', 'grinding stone', 'copper ', 'bronze ', 'iron ', 'steel ',
                    'silvered ', 'golden ', 'chain ', 'bracers', 'gauntlets', 'leggings', 'belt', 'boots',
                    'helm', 'breastplate', 'cuirass', 'shield spike', 'spurs', 'shortsword', 'battle axe',
                    'mace', 'dagger', 'knife', 'buckler'
                ));
            case 165:
                return $containsAny(array(
                    'leather', 'hide', 'armor kit', 'quiver', 'ammo pouch', 'drums', 'deviate scale',
                    'cured ', 'handstitched', 'embossed', 'fine leather', 'barbaric'
                ));
            case 171:
                return $containsAny(array('potion', 'elixir', 'flask', 'oil', 'transmute', 'philosopher', 'alchemist'));
            case 185:
                return $containsAny(array(
                    'charred ', 'roasted ', 'brilliant smallfish', 'longjaw mud snapper', 'crab cake', 'stew',
                    'soup', 'chowder', 'gumbo', 'omelet', 'sausage', 'kebab', 'delight', 'cooked ', 'spice bread'
                ));
            case 197:
                return $containsAny(array(
                    'linen', 'woolen', 'silk', 'mageweave', 'runecloth', 'bolt of ', 'shirt', 'robe', 'pants',
                    'belt', 'gloves', 'boots', 'shoulders', 'bag', 'cloak', 'hood'
                ));
            case 202:
                return $containsAny(array(
                    'blasting powder', 'dynamite', 'bolts', 'tube', 'bomb', 'scope', 'target dummy', 'seaforium',
                    'goggles', 'blunderbuss', 'rifle', 'mortar', 'fuse', 'dragonling', 'torch', 'modulator',
                    'explosive'
                ));
            case 333:
                return strpos($haystack, 'enchant ') === 0 || $containsAny(array('runed ', 'wand'));
            default:
                return false;
        }
    }
}

if (!function_exists('spp_marketplace_faction_name')) {
    function spp_marketplace_faction_name($raceId)
    {
        return in_array((int)$raceId, [1, 3, 4, 7, 11, 22, 25, 29], true) ? 'Alliance' : 'Horde';
    }
}

if (!function_exists('spp_marketplace_is_specialization_recipe')) {
    function spp_marketplace_is_specialization_recipe($expansion, $skillId, $spellId, $spellName = '', $itemName = '')
    {
        $expansion = (int)$expansion;
        $skillId = (int)$skillId;
        $spellId = (int)$spellId;
        $haystack = strtolower(trim((string)$spellName . ' ' . $itemName));

        $exactSpellIds = [
            0 => [
                164 => [9787, 9788],
                165 => [10656, 10658, 10660],
                202 => [20219, 20222],
            ],
            1 => [
                164 => [9787, 9788, 17039, 17040, 17041],
                165 => [10656, 10658, 10660],
                202 => [20219, 20222],
            ],
            2 => [
                164 => [9787, 9788, 17039, 17040, 17041],
                165 => [10656, 10658, 10660],
                202 => [20219, 20222],
            ],
        ];

        if (!empty($exactSpellIds[$expansion][$skillId]) && in_array($spellId, $exactSpellIds[$expansion][$skillId], true)) {
            return true;
        }

        $containsAny = function ($needles) use ($haystack) {
            foreach ($needles as $needle) {
                if ($needle !== '' && strpos($haystack, strtolower($needle)) !== false) {
                    return true;
                }
            }
            return false;
        };

        $keywordFamilies = [
            0 => [
                165 => ['dragonscale', 'tribal', 'elemental'],
                202 => ['gnomish', 'goblin'],
            ],
            1 => [
                164 => ['armorsmith', 'weaponsmith', 'swordsmith', 'hammersmith', 'axesmith'],
                165 => ['dragonscale', 'tribal', 'elemental'],
                202 => ['gnomish', 'goblin'],
            ],
            2 => [
                164 => ['armorsmith', 'weaponsmith', 'swordsmith', 'hammersmith', 'axesmith'],
                165 => ['dragonscale', 'tribal', 'elemental'],
                202 => ['gnomish', 'goblin'],
            ],
        ];

        return !empty($keywordFamilies[$expansion][$skillId]) && $containsAny($keywordFamilies[$expansion][$skillId]);
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$realmLabel = spp_get_armory_realm_name($realmId) ?? '';
$expansion = isset($GLOBALS['expansion']) ? (int)$GLOBALS['expansion'] : 0;

$craftProfessionIds = [164, 165, 171, 197, 202, 333];
if ($expansion >= 1) {
    $craftProfessionIds[] = 755;
}
if ($expansion >= 2) {
    $craftProfessionIds[] = 773;
}

$tierOrder = ['Grand Master', 'Master', 'Artisan', 'Expert', 'Journeyman', 'Apprentice', 'Training'];
$marketplace = [];
$botCount = 0;
$craftCount = 0;
$pageError = '';

// --- data cache (avoids 3–4 s of DB queries on every load) ---
$_mpCacheDir  = $siteRoot . '/core/cache/sites';
$_mpCacheFile = $_mpCacheDir . '/mp_' . md5('marketplace_v8_' . $realmId) . '.dat';
$_mpCacheTTL  = 600; // 10 minutes

$_mpFromCache = false;
if (is_file($_mpCacheFile) && (time() - filemtime($_mpCacheFile)) < $_mpCacheTTL) {
    $_mpData = @unserialize(file_get_contents($_mpCacheFile));
    if (is_array($_mpData)) {
        $marketplace = $_mpData['marketplace'];
        $botCount    = $_mpData['botCount'];
        $craftCount  = $_mpData['craftCount'];
        $_mpFromCache = true;
    }
    unset($_mpData);
}

if (!$_mpFromCache):
try {
    $charsPdo = spp_get_pdo('chars', $realmId);
    $worldPdo = spp_get_pdo('world', $realmId);
    $armoryPdo = spp_get_pdo('armory', $realmId);

    $skillPlaceholders = implode(',', array_fill(0, count($craftProfessionIds), '?'));
    $skillMetaStmt = $armoryPdo->prepare(
        'SELECT sl.`id`, sl.`name`, sl.`description`, si.`name` AS `icon_name`
         FROM `dbc_skillline` sl
         LEFT JOIN `dbc_spellicon` si ON si.`id` = sl.`ref_spellicon`
         WHERE sl.`id` IN (' . $skillPlaceholders . ')
         ORDER BY sl.`name`'
    );
    $skillMetaStmt->execute($craftProfessionIds);
    $skillMeta = [];
    foreach ($skillMetaStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $skillMeta[(int)$row['id']] = $row;
    }

    $botSkillStmt = $charsPdo->prepare(
        'SELECT c.`guid`, c.`name`, c.`race`, c.`class`, c.`level`, c.`gender`, cs.`skill`, cs.`value`, cs.`max`
         FROM `characters` c
         INNER JOIN `character_skills` cs ON cs.`guid` = c.`guid`
         LEFT JOIN `ai_playerbot_names` apn ON apn.`name` = c.`name`
         WHERE cs.`skill` IN (' . $skillPlaceholders . ')
           AND (c.`account` <= 504 OR apn.`name_id` IS NOT NULL)
         ORDER BY cs.`skill`, cs.`max` DESC, cs.`value` DESC, c.`name` ASC'
    );
    $botSkillStmt->execute($craftProfessionIds);
    $botSkillRows = $botSkillStmt->fetchAll(PDO::FETCH_ASSOC);

    $botGuids = [];
    $professionSkillIds = [];
    $professionSkillsByGuid = [];
    foreach ($botSkillRows as $row) {
        $guid = (int)$row['guid'];
        $skillId = (int)$row['skill'];
        $botGuids[$guid] = true;
        $professionSkillIds[$skillId] = true;
        if (!isset($professionSkillsByGuid[$guid])) {
            $professionSkillsByGuid[$guid] = [];
        }
        $professionSkillsByGuid[$guid][$skillId] = true;
    }

    $learnedSpellsByGuid = [];
    $learnedSpellIds = [];
    if (!empty($botGuids)) {
        $guidIds = array_keys($botGuids);
        $guidPlaceholders = implode(',', array_fill(0, count($guidIds), '?'));
        $spellStmt = $charsPdo->prepare(
            'SELECT `guid`, `spell`
             FROM `character_spell`
             WHERE `disabled` = 0 AND `guid` IN (' . $guidPlaceholders . ')'
        );
        $spellStmt->execute($guidIds);
        foreach ($spellStmt->fetchAll(PDO::FETCH_ASSOC) as $spellRow) {
            $guid = (int)$spellRow['guid'];
            $spellId = (int)$spellRow['spell'];
            if (!isset($learnedSpellsByGuid[$guid])) {
                $learnedSpellsByGuid[$guid] = [];
            }
            $learnedSpellsByGuid[$guid][$spellId] = true;
            $learnedSpellIds[$spellId] = true;
        }
    }

    $trainerSpellIdsBySkill = [];
    if (!empty($professionSkillIds)) {
        $professionIds = array_keys($professionSkillIds);
        $trainerSkillPlaceholders = implode(',', array_fill(0, count($professionIds), '?'));
        $trainerSql = '
            SELECT `spell`, `reqskill`, `reqskillvalue`
            FROM `npc_trainer`
            WHERE `reqskill` IN (' . $trainerSkillPlaceholders . ')
            UNION
            SELECT `spell`, `reqskill`, `reqskillvalue`
            FROM `npc_trainer_template`
            WHERE `reqskill` IN (' . $trainerSkillPlaceholders . ')';
        $trainerStmt = $worldPdo->prepare($trainerSql);
        $trainerStmt->execute(array_merge($professionIds, $professionIds));
        foreach ($trainerStmt->fetchAll(PDO::FETCH_ASSOC) as $trainerRow) {
            $skillId = (int)$trainerRow['reqskill'];
            $spellId = (int)$trainerRow['spell'];
            $requiredValue = (int)$trainerRow['reqskillvalue'];
            if ($skillId <= 0 || $spellId <= 0) {
                continue;
            }
            if (!isset($trainerSpellIdsBySkill[$skillId])) {
                $trainerSpellIdsBySkill[$skillId] = [];
            }
            $existingRequired = $trainerSpellIdsBySkill[$skillId][$spellId] ?? null;
            if ($existingRequired === null || $requiredValue < $existingRequired) {
                $trainerSpellIdsBySkill[$skillId][$spellId] = $requiredValue;
            }
            $learnedSpellIds[$spellId] = true;
        }
    }

    $professionRecipesByGuidSkill = [];
    if (!empty($professionSkillIds) && !empty($learnedSpellIds)) {
        $spellIds = array_keys($learnedSpellIds);
        $spellPlaceholders = implode(',', array_fill(0, count($spellIds), '?'));

        $spellStmt = $worldPdo->prepare(
            'SELECT `Id`, `SpellName`, `SpellIconID`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3`
             FROM `spell_template`
             WHERE `Id` IN (' . $spellPlaceholders . ')'
        );
        $spellStmt->execute($spellIds);
        $spellRows = $spellStmt->fetchAll(PDO::FETCH_ASSOC);

        $craftedItemIds = [];
        $spellOutputMap = [];
        $spellMetaMap = [];
        $spellIconIds = [];
        foreach ($spellRows as $spellRow) {
            $spellId = (int)$spellRow['Id'];
            if ($spellId <= 0) {
                continue;
            }
            $spellMetaMap[$spellId] = $spellRow;
            $spellIconId = (int)($spellRow['SpellIconID'] ?? 0);
            if ($spellIconId > 0) {
                $spellIconIds[$spellIconId] = true;
            }
            foreach (['EffectItemType1', 'EffectItemType2', 'EffectItemType3'] as $field) {
                $itemId = (int)($spellRow[$field] ?? 0);
                if ($itemId <= 0) {
                    continue;
                }

                if (!isset($spellOutputMap[$spellId])) {
                    $spellOutputMap[$spellId] = [];
                }
                $spellOutputMap[$spellId][$itemId] = true;
                $craftedItemIds[$itemId] = true;
            }
        }

        $craftItemMap = [];
        $craftIcons = [];
        $spellIconMap = [];
        if (!empty($spellIconIds)) {
            $iconIdList = array_keys($spellIconIds);
            $iconPlaceholders = implode(',', array_fill(0, count($iconIdList), '?'));
            $spellIconStmt = $armoryPdo->prepare(
                'SELECT `id`, `name`
                 FROM `dbc_spellicon`
                 WHERE `id` IN (' . $iconPlaceholders . ')'
            );
            $spellIconStmt->execute($iconIdList);
            foreach ($spellIconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                $spellIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
            }
        }

        if (!empty($craftedItemIds)) {
            $craftedItemList = array_keys($craftedItemIds);
            $itemPlaceholders = implode(',', array_fill(0, count($craftedItemList), '?'));
            $craftItemStmt = $worldPdo->prepare(
                'SELECT `entry`, `name`, `Quality`, `RequiredSkill`, `RequiredSkillRank`, `displayid`
                 FROM `item_template`
                 WHERE `entry` IN (' . $itemPlaceholders . ')'
            );
            $craftItemStmt->execute($craftedItemList);

            $displayIds = [];
            foreach ($craftItemStmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
                $entry = (int)$itemRow['entry'];
                $craftItemMap[$entry] = $itemRow;
                $displayId = (int)($itemRow['displayid'] ?? 0);
                if ($displayId > 0) {
                    $displayIds[$displayId] = true;
                }
            }

            if (!empty($displayIds)) {
                $displayIdList = array_keys($displayIds);
                $displayPlaceholders = implode(',', array_fill(0, count($displayIdList), '?'));
                $iconStmt = $armoryPdo->prepare(
                    'SELECT `id`, `name`
                     FROM `dbc_itemdisplayinfo`
                     WHERE `id` IN (' . $displayPlaceholders . ')'
                );
                $iconStmt->execute($displayIdList);
                foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                    $craftIcons[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
            }
        }

        foreach ($professionSkillsByGuid as $guid => $botSkillMap) {
            foreach (array_keys($botSkillMap) as $skillId) {
                foreach (array_keys($learnedSpellsByGuid[$guid] ?? []) as $spellId) {
                    if (!isset($spellMetaMap[$spellId])) {
                        continue;
                    }

                    $assignedSkills = [];
                    if (isset($trainerSpellIdsBySkill[$skillId][$spellId])) {
                        $assignedSkills[$skillId] = true;
                    }

                    if (empty($assignedSkills) && !empty($spellOutputMap[$spellId])) {
                        $itemRequiredSkills = [];
                        foreach (array_keys($spellOutputMap[$spellId]) as $itemId) {
                            $requiredSkill = (int)($craftItemMap[$itemId]['RequiredSkill'] ?? 0);
                            if ($requiredSkill > 0 && isset($botSkillMap[$requiredSkill])) {
                                $itemRequiredSkills[$requiredSkill] = true;
                            }
                        }
                        if (count($itemRequiredSkills) === 1 && !empty($itemRequiredSkills[$skillId])) {
                            $assignedSkills = $itemRequiredSkills;
                        }
                    }

                    if (empty($assignedSkills)) {
                        $spellRow = $spellMetaMap[$spellId];
                        $spellName = trim((string)($spellRow['SpellName'] ?? ''));
                        $primaryItemName = '';
                        foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                            if (!empty($craftItemMap[$itemId]['name'])) {
                                $primaryItemName = (string)$craftItemMap[$itemId]['name'];
                                break;
                            }
                        }
                        if (spp_marketplace_profession_spell_matches_skill($skillId, $spellName, $primaryItemName)) {
                            $assignedSkills[$skillId] = true;
                        }
                    }

                    if (empty($assignedSkills[$skillId])) {
                        continue;
                    }

                    $spellRow = $spellMetaMap[$spellId];
                    $spellName = trim((string)($spellRow['SpellName'] ?? ''));
                    if ($spellName === '') {
                        $spellName = 'Spell #' . $spellId;
                    }

                    $craftedItems = [];
                    foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                        if (!isset($craftItemMap[$itemId])) {
                            continue;
                        }
                        $itemRow = $craftItemMap[$itemId];
                        $craftedItems[] = [
                            'entry' => $itemId,
                            'name' => (string)($itemRow['name'] ?? ('Item #' . $itemId)),
                            'quality' => (int)($itemRow['Quality'] ?? 1),
                            'required_rank' => (int)($itemRow['RequiredSkillRank'] ?? 0),
                            'icon' => spp_marketplace_icon_url($craftIcons[(int)($itemRow['displayid'] ?? 0)] ?? ''),
                        ];
                    }

                    $spellIcon = spp_marketplace_icon_url($spellIconMap[(int)($spellRow['SpellIconID'] ?? 0)] ?? '');
                    $requiredRank = isset($trainerSpellIdsBySkill[$skillId][$spellId])
                        ? (int)$trainerSpellIdsBySkill[$skillId][$spellId]
                        : (!empty($craftedItems[0]['required_rank']) ? (int)$craftedItems[0]['required_rank'] : 0);
                    $isGeneralTrainerRecipe = isset($trainerSpellIdsBySkill[$skillId][$spellId]);
                    $isSpecializationRecipe = spp_marketplace_is_specialization_recipe(
                        $expansion,
                        $skillId,
                        $spellId,
                        $spellName,
                        !empty($craftedItems[0]['name']) ? (string)$craftedItems[0]['name'] : ''
                    );

                    $professionRecipesByGuidSkill[$guid][$skillId][$spellId] = [
                        'spell_id' => $spellId,
                        'spell_name' => $spellName,
                        'item_entry' => !empty($craftedItems[0]['entry']) ? (int)$craftedItems[0]['entry'] : 0,
                        'item_name' => !empty($craftedItems[0]['name']) ? (string)$craftedItems[0]['name'] : '',
                        'quality' => !empty($craftedItems[0]['quality']) ? (int)$craftedItems[0]['quality'] : 1,
                        'icon' => !empty($craftedItems[0]['icon']) ? $craftedItems[0]['icon'] : $spellIcon,
                        'required_rank' => $requiredRank,
                        'is_trainer' => $isGeneralTrainerRecipe,
                        'is_special' => $isSpecializationRecipe || (!$isGeneralTrainerRecipe && $requiredRank > 0),
                        'created_items' => $craftedItems,
                    ];
                }
            }
        }
    }

    foreach ($botSkillRows as $row) {
        $skillId = (int)$row['skill'];
        if (!isset($skillMeta[$skillId])) {
            continue;
        }

        $guid = (int)$row['guid'];
        $tierLabel = spp_marketplace_profession_tier_label($row['max']);
        $professionName = (string)$skillMeta[$skillId]['name'];

        if (!isset($marketplace[$professionName])) {
            $marketplace[$professionName] = [
                'skill_id' => $skillId,
                'description' => trim((string)($skillMeta[$skillId]['description'] ?? '')),
                'icon' => spp_marketplace_profession_icon_url($skillId, $skillMeta[$skillId]['icon_name'] ?? ''),
                'tiers' => [],
                'total_bots' => 0,
                'total_recipes' => 0,
                'total_special_recipes' => 0,
                'total_possible' => 0,
            ];
        }

        if (!isset($marketplace[$professionName]['tiers'][$tierLabel])) {
            $marketplace[$professionName]['tiers'][$tierLabel] = [];
        }

        $knownSpellIds = [];
        foreach (($learnedSpellsByGuid[$guid] ?? []) as $spellId => $_unused) {
            $knownSpellIds[(int)$spellId] = true;
        }
        $knownCrafts = $professionRecipesByGuidSkill[$guid][$skillId] ?? [];
        uasort($knownCrafts, function ($left, $right) {
            $rankCompare = ((int)$right['required_rank']) <=> ((int)$left['required_rank']);
            if ($rankCompare !== 0) {
                return $rankCompare;
            }
            return strnatcasecmp((string)$left['spell_name'], (string)$right['spell_name']);
        });

        $specialCrafts = [];
        foreach ($knownCrafts as $spellId => $craft) {
            if (!empty($craft['is_special'])) {
                $specialCrafts[$spellId] = $craft;
            }
        }

        $marketplace[$professionName]['tiers'][$tierLabel][] = [
            'guid' => $guid,
            'name' => (string)$row['name'],
            'race' => (int)$row['race'],
            'class' => (int)$row['class'],
            'gender' => (int)$row['gender'],
            'faction' => spp_marketplace_faction_name((int)$row['race']),
            'level' => (int)$row['level'],
            'value' => (int)$row['value'],
            'max' => (int)$row['max'],
            'tier' => $tierLabel,
            'crafts' => array_values($knownCrafts),
            'special_crafts' => array_values($specialCrafts),
            'craft_count' => count($knownCrafts),
            'special_craft_count' => count($specialCrafts),
        ];

        $marketplace[$professionName]['total_bots']++;
        $marketplace[$professionName]['total_recipes'] += count($knownCrafts);
        $marketplace[$professionName]['total_special_recipes'] += count($specialCrafts);
        $botCount++;
        $craftCount += count($knownCrafts);
    }

    uasort($marketplace, function ($left, $right) {
        $botCompare = ((int)$right['total_bots']) <=> ((int)$left['total_bots']);
        if ($botCompare !== 0) {
            return $botCompare;
        }
        return ((int)$left['skill_id']) <=> ((int)$right['skill_id']);
    });

    foreach ($marketplace as &$profession) {
        $allBots = [];
        foreach ($profession['tiers'] as $tierBots) {
            foreach ($tierBots as $bot) {
                $allBots[] = $bot;
            }
        }

        usort($allBots, function ($left, $right) {
            if ((int)$left['value'] !== (int)$right['value']) {
                return (int)$right['value'] <=> (int)$left['value'];
            }
            if ((int)$left['special_craft_count'] !== (int)$right['special_craft_count']) {
                return (int)$right['special_craft_count'] <=> (int)$left['special_craft_count'];
            }
            if ((int)$left['craft_count'] !== (int)$right['craft_count']) {
                return (int)$right['craft_count'] <=> (int)$left['craft_count'];
            }
            if ((int)$left['level'] !== (int)$right['level']) {
                return (int)$right['level'] <=> (int)$left['level'];
            }
            return strnatcasecmp((string)$left['name'], (string)$right['name']);
        });

        $profession['top_by_faction'] = ['Alliance' => [], 'Horde' => []];
        foreach ($allBots as $bot) {
            $faction = $bot['faction'] ?? 'Horde';
            if (!isset($profession['top_by_faction'][$faction])) {
                $profession['top_by_faction'][$faction] = [];
            }
            if (count($profession['top_by_faction'][$faction]) >= 3) {
                continue;
            }
            $profession['top_by_faction'][$faction][] = $bot;
        }
        $profession['featured_count'] = count($profession['top_by_faction']['Alliance']) + count($profession['top_by_faction']['Horde']);
        $profession['special_holders'] = [];
        foreach ($allBots as $bot) {
            if ((int)($bot['special_craft_count'] ?? 0) <= 0) {
                continue;
            }
            $profession['special_holders'][] = [
                'name' => (string)$bot['name'],
                'count' => (int)$bot['special_craft_count'],
                'faction' => (string)($bot['faction'] ?? 'Horde'),
            ];
        }
    }
    unset($profession);
    // save to cache on success
    if (!empty($marketplace) && is_writable($_mpCacheDir)) {
        @file_put_contents(
            $_mpCacheFile,
            serialize(['marketplace' => $marketplace, 'botCount' => $botCount, 'craftCount' => $craftCount]),
            LOCK_EX
        );
    }
} catch (Throwable $e) {
    $pageError = 'The marketplace could not be loaded right now.';
}
endif; // !$_mpFromCache

builddiv_start(1, 'Marketplace', 1);
?>
<link rel="stylesheet" type="text/css" href="/templates/offlike/css/armory-tooltips.css" />
<style>
.modern-item-tooltip{min-width:220px;max-width:min(420px,calc(100vw - 24px));max-height:calc(100vh - 24px);overflow:auto}
.modern-item-tooltip-loading{padding:14px 16px;color:#f5e6b2;border:1px solid rgba(255,196,0,.35);border-radius:10px;background:rgba(5,8,18,.96);box-shadow:0 16px 38px rgba(0,0,0,.45)}
#modern-item-tooltip{position:fixed;z-index:9999;pointer-events:none;display:none}
.marketplace-shell{display:grid;gap:24px}
.marketplace-search-panel{display:grid;gap:12px;padding:18px 20px;border-radius:18px;border:1px dashed rgba(255,204,72,.2);background:rgba(255,255,255,.025)}
.marketplace-search-label{margin:0;color:#ffe39a;font-size:1rem;font-weight:800}
.marketplace-search-copy{margin:0;color:#cfbb91;line-height:1.55}
.marketplace-search-input{width:100%;height:48px;padding:0 16px;border-radius:14px;border:1px solid rgba(255,204,72,.22);background:rgba(6,10,22,.92);color:#fff1be;font-size:1rem;outline:none}
.marketplace-search-input:focus{border-color:rgba(255,204,72,.46);box-shadow:0 0 0 3px rgba(255,204,72,.08)}
.marketplace-search-empty{display:none;padding:18px;border-radius:18px;border:1px dashed rgba(255,204,72,.16);background:rgba(255,255,255,.025);color:#cdb98f}
.marketplace-profession-grid{display:grid;gap:22px}
.marketplace-profession{border-radius:22px;border:1px solid rgba(255,204,72,.16);background:linear-gradient(180deg,rgba(11,15,28,.94),rgba(5,8,18,.88));box-shadow:0 14px 34px rgba(0,0,0,.26);overflow:hidden}
.marketplace-profession-summary{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:22px;cursor:pointer;list-style:none}
.marketplace-profession-summary::-webkit-details-marker{display:none}
.marketplace-profession-summary:after{content:'+';display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:999px;border:1px solid rgba(255,204,72,.18);color:#ffd467;font-size:1.05rem;font-weight:800;flex:0 0 auto}
.marketplace-profession[open]>.marketplace-profession-summary:after{content:'-'}
.marketplace-profession-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex:1 1 auto}
.marketplace-profession-id{display:flex;align-items:center;gap:14px;min-width:0}
.marketplace-profession-icon{width:52px;height:52px;border-radius:14px;border:1px solid rgba(255,204,72,.18);background:#080808}
.marketplace-profession-title{margin:0;color:#fff1be;font-size:1.45rem}
.marketplace-profession-desc{margin:6px 0 0;color:#bda87a;line-height:1.5}
.marketplace-profession-note{margin:10px 0 0;color:#cdb98f;font-size:.9rem;line-height:1.55}
.marketplace-profession-stats{display:grid;gap:8px;text-align:right}
.marketplace-profession-stat{position:relative}
.marketplace-profession-stat strong{display:block;color:#fff1be;font-size:1.15rem}
.marketplace-profession-stat span{color:#bda87a;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}
.marketplace-profession-stat.is-hoverable{cursor:default}
.marketplace-profession-stat-flyout{position:absolute;top:100%;right:0;margin-top:10px;min-width:240px;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,204,72,.18);background:rgba(6,10,22,.98);box-shadow:0 18px 34px rgba(0,0,0,.34);display:none;text-align:left;z-index:20}
.marketplace-profession-stat.is-hoverable:hover .marketplace-profession-stat-flyout{display:block}
.marketplace-profession-stat-flyout-title{margin:0 0 8px;color:#ffe39a;font-size:.82rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.marketplace-profession-holder-list{display:grid;gap:6px}
.marketplace-profession-holder-link{color:#f7edd0;text-decoration:none;font-size:.92rem}
.marketplace-profession-holder-link:hover{color:#ffe39a}
.marketplace-profession-body{padding:0 22px 22px;display:grid;gap:18px}
.marketplace-featured-board{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.marketplace-faction-panel{border-radius:18px;border:1px solid rgba(255,204,72,.12);background:rgba(255,255,255,.025);padding:18px}
.marketplace-faction-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.marketplace-faction-title{margin:0;color:#ffe39a;font-size:1.05rem}
.marketplace-faction-copy{color:#bda87a;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase}
.marketplace-bot-grid{display:grid;gap:14px}
.marketplace-bot{padding:16px;border-radius:16px;border:1px solid rgba(255,204,72,.12);background:rgba(4,7,16,.76)}
.marketplace-bot-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.marketplace-bot-link{display:flex;align-items:center;gap:12px;min-width:0;color:#f7edd0;text-decoration:none}
.marketplace-bot-avatars{display:flex;gap:8px;flex:0 0 auto}
.marketplace-bot-avatars img{width:32px;height:32px;border-radius:10px;border:1px solid rgba(255,204,72,.14);background:#090909}
.marketplace-bot-name{display:block;color:#fff1be;font-size:1rem;font-weight:800}
.marketplace-bot-meta{margin-top:3px;color:#bda87a;font-size:.86rem}
.marketplace-bot-rank{color:#ffd467;font-size:.88rem;font-weight:800;white-space:nowrap}
.marketplace-bot-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
.marketplace-bot-stat{padding:10px 12px;border-radius:12px;border:1px solid rgba(255,204,72,.1);background:rgba(255,255,255,.025)}
.marketplace-bot-stat strong{display:block;color:#fff1be;font-size:1rem}
.marketplace-bot-stat span{display:block;margin-top:4px;color:#bda87a;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase}
.marketplace-progress{margin-top:12px}
.marketplace-progress-track{height:12px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.08)}
.marketplace-progress-fill{height:100%;background:linear-gradient(90deg,#8e6200,#ffd467)}
.marketplace-progress-copy{display:flex;justify-content:space-between;gap:10px;margin-top:6px;color:#bda87a;font-size:.8rem}
.marketplace-recipe-box{margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,204,72,.12)}
.marketplace-recipe-box summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;list-style:none;color:#ffe39a;font-weight:800}
.marketplace-recipe-box summary::-webkit-details-marker{display:none}
.marketplace-recipe-box summary span{color:#bda87a;font-size:.85rem}
.marketplace-recipe-list{display:grid;gap:8px;margin-top:12px}
.marketplace-recipe{display:grid;grid-template-columns:40px minmax(0,1fr);gap:10px;align-items:center;padding:10px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.025);text-decoration:none}
.marketplace-recipe img{width:40px;height:40px;border-radius:12px;border:1px solid rgba(255,204,72,.14);background:#090909}
.marketplace-recipe strong{display:block;font-size:.93rem;line-height:1.25}
.marketplace-recipe small{display:block;margin-top:3px;color:#bda87a}
.marketplace-bot-empty{padding:14px;border-radius:14px;border:1px dashed rgba(255,204,72,.14);background:rgba(255,255,255,.02);color:#bda87a}
.marketplace-empty,.marketplace-error{padding:18px;border-radius:18px}
.marketplace-empty{border:1px dashed rgba(255,204,72,.16);background:rgba(255,255,255,.025);color:#cdb98f}
.marketplace-error{border:1px solid rgba(255,120,120,.25);background:rgba(96,18,18,.4);color:#ffd8d8}
@media (max-width:860px){.marketplace-profession-summary,.marketplace-profession-head{flex-direction:column}.marketplace-profession-stats{grid-template-columns:repeat(2,minmax(0,1fr));text-align:left}.marketplace-featured-board{grid-template-columns:1fr}}
@media (max-width:560px){.marketplace-bot-head{flex-direction:column}.marketplace-profession{padding:18px}.marketplace-bot-summary{grid-template-columns:1fr}}
</style>
<script>
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

function modernRequestTooltip(event, itemId, realmId, itemGuid) {
  const cacheKey = realmId + ':' + itemId;
  if (modernTooltipCache.has(cacheKey)) {
    modernShowTooltip(event, modernTooltipCache.get(cacheKey));
    return;
  }

  modernShowTooltip(event, modernTooltipLoadingHtml());
  modernTooltipRequestToken += 1;
  const token = modernTooltipRequestToken;
  let url = 'modern-item-tooltip.php?item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId);
  if (itemGuid) {
    url += '&guid=' + encodeURIComponent(itemGuid);
  }

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
  modernTooltipRequestToken += 1;
  if (modernTooltipNode) modernTooltipNode.style.display = 'none';
}
</script>

<div class="marketplace-shell">
  <section class="marketplace-search-panel">
    <p class="marketplace-search-label">Search Craftable Items</p>
    <p class="marketplace-search-copy">Known Recipes show everything a crafter has learned. Special Recipes highlight the subset earned outside normal trainer progression, like drops, reputation rewards, vendor recipes, or quest unlocks.</p>
    <input id="marketplace-craft-search" class="marketplace-search-input" type="search" placeholder="Search for a craft, like Copper Chain Pants" autocomplete="off">
  </section>

  <?php if ($pageError !== ''): ?>
    <div class="marketplace-error"><?php echo htmlspecialchars($pageError); ?></div>
  <?php elseif (empty($marketplace)): ?>
    <div class="marketplace-empty">No bot profession data was available for this realm.</div>
  <?php else: ?>
    <div id="marketplace-search-empty" class="marketplace-search-empty">No crafters matched that item search.</div>
    <section class="marketplace-profession-grid">
      <?php foreach ($marketplace as $professionName => $profession): ?>
        <details class="marketplace-profession">
          <summary class="marketplace-profession-summary">
            <div class="marketplace-profession-head">
              <div class="marketplace-profession-id">
                <img class="marketplace-profession-icon" src="<?php echo htmlspecialchars($profession['icon']); ?>" alt="<?php echo htmlspecialchars($professionName); ?>">
                <div>
                  <h2 class="marketplace-profession-title"><?php echo htmlspecialchars($professionName); ?></h2>
                  <?php if ($profession['description'] !== ''): ?>
                    <p class="marketplace-profession-desc"><?php echo htmlspecialchars($profession['description']); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($profession['special_holders'])): ?>
                    <p class="marketplace-profession-note">
                      Special recipe holders:
                      <?php
                        $holderLinks = [];
                        foreach ($profession['special_holders'] as $holder) {
                            $holderUrl = 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . urlencode((string)$holder['name']) . '&tab=professions';
                            $holderLinks[] = '<a class="marketplace-profession-holder-link" href="' . htmlspecialchars($holderUrl) . '">' . htmlspecialchars((string)$holder['name']) . ' (' . (int)$holder['count'] . ')</a>';
                        }
                        echo implode(', ', $holderLinks);
                      ?>
                    </p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="marketplace-profession-stats">
                <div class="marketplace-profession-stat">
                  <strong><?php echo (int)$profession['total_bots']; ?></strong>
                  <span>Crafters</span>
                </div>
                <div class="marketplace-profession-stat<?php echo !empty($profession['special_holders']) ? ' is-hoverable' : ''; ?>">
                  <strong><?php echo (int)($profession['total_special_recipes'] ?? 0); ?></strong>
                  <span>Special</span>
                  <?php if (!empty($profession['special_holders'])): ?>
                    <div class="marketplace-profession-stat-flyout">
                      <p class="marketplace-profession-stat-flyout-title">Special Recipe Holders</p>
                      <div class="marketplace-profession-holder-list">
                        <?php foreach ($profession['special_holders'] as $holder): ?>
                          <a class="marketplace-profession-holder-link" href="index.php?n=server&amp;sub=character&amp;realm=<?php echo (int)$realmId; ?>&amp;character=<?php echo urlencode((string)$holder['name']); ?>&amp;tab=professions">
                            <?php echo htmlspecialchars((string)$holder['name']); ?> (<?php echo (int)$holder['count']; ?>)
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </summary>

          <div class="marketplace-profession-body">
            <div class="marketplace-featured-board">
              <?php foreach (['Alliance', 'Horde'] as $factionName): ?>
                <section class="marketplace-faction-panel">
                  <div class="marketplace-faction-head">
                    <h3 class="marketplace-faction-title"><?php echo htmlspecialchars($factionName); ?></h3>
                    <span class="marketplace-faction-copy">Top <?php echo (int)count($profession['top_by_faction'][$factionName] ?? []); ?> featured</span>
                  </div>
                  <div class="marketplace-bot-grid">
                    <?php if (!empty($profession['top_by_faction'][$factionName])): ?>
                      <?php foreach ($profession['top_by_faction'][$factionName] as $bot): ?>
                        <?php
                          $searchTerms = [];
                          foreach ($bot['crafts'] as $craft) {
                              $searchTerms[] = strtolower(trim((string)($craft['spell_name'] ?? '') . ' ' . (string)($craft['item_name'] ?? '')));
                          }
                          foreach ($bot['special_crafts'] as $craft) {
                              $searchTerms[] = strtolower(trim((string)($craft['spell_name'] ?? '') . ' ' . (string)($craft['item_name'] ?? '')));
                          }
                        ?>
                        <article class="marketplace-bot" data-craft-search="<?php echo htmlspecialchars(implode(' ', $searchTerms)); ?>">
                          <div class="marketplace-bot-head">
                            <a class="marketplace-bot-link" href="index.php?n=server&amp;sub=character&amp;realm=<?php echo (int)$realmId; ?>&amp;character=<?php echo urlencode($bot['name']); ?>">
                              <span class="marketplace-bot-avatars">
                                <img src="<?php echo htmlspecialchars(spp_marketplace_race_icon_url($bot['race'], $bot['gender'])); ?>" alt="">
                                <img src="<?php echo htmlspecialchars(spp_marketplace_class_icon_url($bot['class'])); ?>" alt="">
                              </span>
                              <span>
                                <strong class="marketplace-bot-name"><?php echo htmlspecialchars($bot['name']); ?></strong>
                                <span class="marketplace-bot-meta">Level <?php echo (int)$bot['level']; ?> · <?php echo htmlspecialchars($bot['tier']); ?></span>
                              </span>
                            </a>
                            <span class="marketplace-bot-rank"><?php echo (int)$bot['value']; ?>/<?php echo (int)$bot['max']; ?></span>
                          </div>

                          <div class="marketplace-progress">
                            <div class="marketplace-progress-track">
                              <div class="marketplace-progress-fill" style="width: <?php echo (int)min(100, max(0, round(($bot['max'] > 0 ? ($bot['value'] / $bot['max']) * 100 : 0)))); ?>%"></div>
                            </div>
                            <div class="marketplace-progress-copy">
                              <span>Profession skill</span>
                              <span><?php echo (int)$bot['special_craft_count']; ?> special</span>
                            </div>
                          </div>

                          <div class="marketplace-bot-summary">
                            <div class="marketplace-bot-stat">
                              <strong><?php echo (int)$bot['value']; ?></strong>
                              <span>Skill Rank</span>
                            </div>
                            <div class="marketplace-bot-stat">
                              <strong><?php echo (int)$bot['special_craft_count']; ?></strong>
                              <span>Special Recipes</span>
                            </div>
                            <div class="marketplace-bot-stat">
                              <strong><?php echo (int)$bot['craft_count']; ?></strong>
                              <span>Known Recipes</span>
                            </div>
                          </div>

                          <details class="marketplace-recipe-box">
                            <summary>
                              <strong>Special Recipes</strong>
                              <span><?php echo (int)$bot['special_craft_count']; ?> listed</span>
                            </summary>
                            <?php if (!empty($bot['special_crafts'])): ?>
                              <div class="marketplace-recipe-list">
                                <?php foreach ($bot['special_crafts'] as $craft): ?>
                                  <?php $recipeTag = !empty($craft['item_entry']) ? 'a' : 'div'; ?>
                                  <<?php echo $recipeTag; ?> class="marketplace-recipe"<?php if ($recipeTag === 'a'): ?> href="index.php?n=server&amp;sub=item&amp;realm=<?php echo (int)$realmId; ?>&amp;item=<?php echo (int)$craft['item_entry']; ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$craft['item_entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"<?php endif; ?>>
                                    <img src="<?php echo htmlspecialchars($craft['icon']); ?>" alt="<?php echo htmlspecialchars($craft['spell_name']); ?>">
                                    <span>
                                      <strong style="color: <?php echo htmlspecialchars(spp_marketplace_quality_color($craft['quality'])); ?>;"><?php echo htmlspecialchars($craft['spell_name']); ?></strong>
                                      <small>
                                        <?php if (!empty($craft['item_name'])): ?>Creates <?php echo htmlspecialchars($craft['item_name']); ?> · <?php endif; ?><?php echo (int)$craft['required_rank']; ?> skill
                                      </small>
                                    </span>
                                  </<?php echo $recipeTag; ?>>
                                <?php endforeach; ?>
                              </div>
                            <?php else: ?>
                              <div class="marketplace-bot-empty">No special non-trainer recipes were recorded for this crafter yet.</div>
                            <?php endif; ?>
                          </details>

                          <details class="marketplace-recipe-box">
                            <summary>
                              <strong>Known Recipes</strong>
                              <span><?php echo (int)$bot['craft_count']; ?> listed</span>
                            </summary>
                            <?php if (!empty($bot['crafts'])): ?>
                              <div class="marketplace-recipe-list">
                                <?php foreach ($bot['crafts'] as $craft): ?>
                                  <?php $recipeTag = !empty($craft['item_entry']) ? 'a' : 'div'; ?>
                                  <<?php echo $recipeTag; ?> class="marketplace-recipe"<?php if ($recipeTag === 'a'): ?> href="index.php?n=server&amp;sub=item&amp;realm=<?php echo (int)$realmId; ?>&amp;item=<?php echo (int)$craft['item_entry']; ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$craft['item_entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"<?php endif; ?>>
                                    <img src="<?php echo htmlspecialchars($craft['icon']); ?>" alt="<?php echo htmlspecialchars($craft['spell_name']); ?>">
                                    <span>
                                      <strong style="color: <?php echo htmlspecialchars(spp_marketplace_quality_color($craft['quality'])); ?>;"><?php echo htmlspecialchars($craft['spell_name']); ?></strong>
                                      <small>
                                        <?php if (!empty($craft['item_name'])): ?>Creates <?php echo htmlspecialchars($craft['item_name']); ?> · <?php endif; ?><?php echo (int)$craft['required_rank']; ?> skill
                                      </small>
                                    </span>
                                  </<?php echo $recipeTag; ?>>
                                <?php endforeach; ?>
                              </div>
                            <?php else: ?>
                              <div class="marketplace-bot-empty">No craftable items were found for this crafter yet.</div>
                            <?php endif; ?>
                          </details>
                        </article>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <div class="marketplace-bot-empty">No featured <?php echo strtolower($factionName); ?> crafters were available for this profession.</div>
                    <?php endif; ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>
          </div>
        </details>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</div>
<script>
(function () {
  var input = document.getElementById('marketplace-craft-search');
  var emptyState = document.getElementById('marketplace-search-empty');
  var professionCards = document.querySelectorAll('.marketplace-profession');
  if (!input || !emptyState || !professionCards.length) {
    return;
  }

  function applyFilter() {
    var query = input.value.toLowerCase().trim();
    var anyVisible = false;

    professionCards.forEach(function (professionCard) {
      var factionPanels = professionCard.querySelectorAll('.marketplace-faction-panel');
      var professionVisible = query === '';

      factionPanels.forEach(function (factionPanel) {
        var bots = factionPanel.querySelectorAll('.marketplace-bot');
        var factionVisible = false;

        bots.forEach(function (botCard) {
          var haystack = (botCard.getAttribute('data-craft-search') || '').toLowerCase();
          var match = query === '' || haystack.indexOf(query) !== -1;
          botCard.style.display = match ? '' : 'none';
          if (match) {
            factionVisible = true;
          }
        });

        factionPanel.style.display = factionVisible || query === '' ? '' : 'none';
        if (factionVisible) {
          professionVisible = true;
        }
      });

      professionCard.style.display = professionVisible ? '' : 'none';
      if (query !== '' && professionVisible) {
        professionCard.open = true;
      }
      if (professionVisible) {
        anyVisible = true;
      }
    });

    emptyState.style.display = anyVisible ? 'none' : 'block';
  }

  input.addEventListener('input', applyFilter);
})();
</script>
<?php builddiv_end(); ?>
