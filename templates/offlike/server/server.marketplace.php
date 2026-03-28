<?php
$siteDatabaseHandle = $GLOBALS['DB'] ?? null;
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/armory/configuration/settings.php');
if ($siteDatabaseHandle !== null) {
    $GLOBALS['DB'] = $siteDatabaseHandle;
    $DB = $siteDatabaseHandle;
}

if (!function_exists('spp_marketplace_icon_url')) {
    function spp_marketplace_icon_url($iconName)
    {
        $iconName = trim((string)$iconName);
        if ($iconName === '') {
            return '/armory/images/icons/64x64/404.png';
        }

        $basename = preg_replace('/\.(png|jpg|jpeg|gif)$/i', '', $iconName);
        return '/armory/images/icons/64x64/' . strtolower($basename) . '.png';
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
            return '/armory/images/icons/64x64/404.png';
        }

        return '/armory/images/icons/64x64/class-' . $classId . '.' . $extensions[$classId];
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
            return '/armory/images/icons/64x64/404.png';
        }

        return '/armory/images/icons/64x64/' . $icons[$raceId] . '.png';
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
$_mpCacheFile = $_mpCacheDir . '/mp_' . md5('marketplace_' . $realmId) . '.dat';
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

    $craftsBySpell = [];
    $craftsBySkillSpell = [];
    if (!empty($professionSkillIds) && !empty($learnedSpellIds)) {
        $spellIds = array_keys($learnedSpellIds);
        $craftSpellPlaceholders = implode(',', array_fill(0, count($spellIds), '?'));

        $craftSpellStmt = $worldPdo->prepare(
            'SELECT `Id`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3`
             FROM `spell_template`
             WHERE `Id` IN (' . $craftSpellPlaceholders . ')
               AND (`EffectItemType1` > 0 OR `EffectItemType2` > 0 OR `EffectItemType3` > 0)'
        );
        $craftSpellStmt->execute($spellIds);
        $craftSpellRows = $craftSpellStmt->fetchAll(PDO::FETCH_ASSOC);

        $craftedItemIds = [];
        $spellOutputMap = [];
        foreach ($craftSpellRows as $spellRow) {
            $spellId = (int)$spellRow['Id'];
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

        foreach ($spellOutputMap as $spellId => $itemIds) {
            foreach (array_keys($itemIds) as $itemId) {
                if (!isset($craftItemMap[$itemId])) {
                    continue;
                }

                $itemRow = $craftItemMap[$itemId];
                if (!isset($craftsBySpell[$spellId])) {
                    $craftsBySpell[$spellId] = [];
                }
                $craftsBySpell[$spellId][$itemId] = [
                    'entry' => $itemId,
                    'name' => (string)$itemRow['name'],
                    'quality' => (int)$itemRow['Quality'],
                    'required_rank' => 0,
                    'icon' => spp_marketplace_icon_url($craftIcons[(int)$itemRow['displayid']] ?? ''),
                ];
            }
        }
        foreach ($spellOutputMap as $spellId => $itemIds) {
            foreach (array_keys($itemIds) as $itemId) {
                $requiredSkill = (int)($craftItemMap[$itemId]['RequiredSkill'] ?? 0);
                if ($requiredSkill <= 0 || !isset($professionSkillIds[$requiredSkill]) || !isset($craftsBySpell[$spellId][$itemId])) {
                    continue;
                }

                if (!isset($craftsBySkillSpell[$requiredSkill])) {
                    $craftsBySkillSpell[$requiredSkill] = [];
                }
                if (!isset($craftsBySkillSpell[$requiredSkill][$spellId])) {
                    $craftsBySkillSpell[$requiredSkill][$spellId] = [];
                }

                $craft = $craftsBySpell[$spellId][$itemId];
                $craft['required_rank'] = (int)($craftItemMap[$itemId]['RequiredSkillRank'] ?? 0);
                $craftsBySkillSpell[$requiredSkill][$spellId][$itemId] = $craft;
            }
        }

        foreach ($spellOutputMap as $spellId => $itemIds) {
            foreach ($trainerSpellIdsBySkill as $skillId => $trainerSpells) {
                if (!isset($trainerSpells[$spellId])) {
                    continue;
                }

                if (!isset($craftsBySkillSpell[$skillId])) {
                    $craftsBySkillSpell[$skillId] = [];
                }
                if (!isset($craftsBySkillSpell[$skillId][$spellId])) {
                    $craftsBySkillSpell[$skillId][$spellId] = [];
                }

                foreach (array_keys($itemIds) as $itemId) {
                    if (!isset($craftsBySpell[$spellId][$itemId])) {
                        continue;
                    }

                    $craft = $craftsBySpell[$spellId][$itemId];
                    $craft['required_rank'] = (int)$trainerSpells[$spellId];
                    $craftsBySkillSpell[$skillId][$spellId][$itemId] = $craft;
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
        $knownCrafts = [];
        foreach (array_keys($knownSpellIds) as $spellId) {
            if (isset($craftsBySkillSpell[$skillId][$spellId])) {
                foreach ($craftsBySkillSpell[$skillId][$spellId] as $craft) {
                    $knownCrafts[$craft['entry']] = $craft;
                }
                continue;
            }
        }

        uasort($knownCrafts, function ($left, $right) {
            $rankCompare = ((int)$right['required_rank']) <=> ((int)$left['required_rank']);
            if ($rankCompare !== 0) {
                return $rankCompare;
            }
            return strnatcasecmp((string)$left['name'], (string)$right['name']);
        });

        $marketplace[$professionName]['tiers'][$tierLabel][] = [
            'guid' => $guid,
            'name' => (string)$row['name'],
            'race' => (int)$row['race'],
            'class' => (int)$row['class'],
            'gender' => (int)$row['gender'],
            'level' => (int)$row['level'],
            'value' => (int)$row['value'],
            'max' => (int)$row['max'],
            'crafts' => array_values($knownCrafts),
            'craft_count' => count($knownCrafts),
        ];

        $marketplace[$professionName]['total_bots']++;
        $marketplace[$professionName]['total_recipes'] += count($knownCrafts);
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
<style>
.marketplace-shell{display:grid;gap:24px}
.marketplace-hero{position:relative;overflow:hidden;padding:28px 30px;border-radius:24px;border:1px solid rgba(255,204,72,.18);background:radial-gradient(circle at top left,rgba(179,120,17,.24),transparent 34%),linear-gradient(180deg,rgba(12,16,29,.96),rgba(5,8,18,.94));box-shadow:0 18px 42px rgba(0,0,0,.34)}
.marketplace-hero:before{content:'';position:absolute;inset:auto -80px -110px auto;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(255,205,92,.12),transparent 68%)}
.marketplace-kicker{margin:0 0 8px;color:#c9b17b;font-size:.84rem;letter-spacing:.16em;text-transform:uppercase}
.marketplace-title{margin:0;color:#fff1be;font-size:2.2rem;line-height:1.05}
.marketplace-copy{max-width:760px;margin:12px 0 0;color:#ddc89b;font-size:1rem;line-height:1.7}
.marketplace-meta{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
.marketplace-pill{display:inline-flex;align-items:center;min-height:34px;padding:0 14px;border-radius:999px;border:1px solid rgba(255,204,72,.16);background:rgba(255,255,255,.04);color:#f7edd0;font-size:.84rem;font-weight:700}
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
.marketplace-profession-stats{display:grid;gap:8px;text-align:right}
.marketplace-profession-stat strong{display:block;color:#fff1be;font-size:1.15rem}
.marketplace-profession-stat span{color:#bda87a;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase}
.marketplace-profession-body{padding:0 22px 22px;display:grid;gap:18px}
.marketplace-tier-list{display:grid;gap:18px}
.marketplace-tier{border-radius:18px;border:1px solid rgba(255,204,72,.12);background:rgba(255,255,255,.025);overflow:hidden}
.marketplace-tier-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;cursor:pointer;list-style:none}
.marketplace-tier-head::-webkit-details-marker{display:none}
.marketplace-tier-head:after{content:'+';display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;border:1px solid rgba(255,204,72,.18);color:#ffd467;font-size:1rem;font-weight:800;flex:0 0 auto}
.marketplace-tier[open]>.marketplace-tier-head:after{content:'-'}
.marketplace-tier-title{margin:0;color:#ffe39a;font-size:1.08rem}
.marketplace-tier-count{color:#bda87a;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase}
.marketplace-tier-body{padding:0 18px 18px}
.marketplace-bot-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:14px}
.marketplace-bot{padding:16px;border-radius:16px;border:1px solid rgba(255,204,72,.12);background:rgba(4,7,16,.76)}
.marketplace-bot-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.marketplace-bot-link{display:flex;align-items:center;gap:12px;min-width:0;color:#f7edd0;text-decoration:none}
.marketplace-bot-avatars{display:flex;gap:8px;flex:0 0 auto}
.marketplace-bot-avatars img{width:32px;height:32px;border-radius:10px;border:1px solid rgba(255,204,72,.14);background:#090909}
.marketplace-bot-name{display:block;color:#fff1be;font-size:1rem;font-weight:800}
.marketplace-bot-meta{margin-top:3px;color:#bda87a;font-size:.86rem}
.marketplace-bot-rank{color:#ffd467;font-size:.88rem;font-weight:800;white-space:nowrap}
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
.marketplace-empty,.marketplace-error{padding:18px;border-radius:18px}
.marketplace-empty{border:1px dashed rgba(255,204,72,.16);background:rgba(255,255,255,.025);color:#cdb98f}
.marketplace-error{border:1px solid rgba(255,120,120,.25);background:rgba(96,18,18,.4);color:#ffd8d8}
@media (max-width:860px){.marketplace-profession-summary,.marketplace-profession-head{flex-direction:column}.marketplace-profession-stats{grid-template-columns:repeat(2,minmax(0,1fr));text-align:left}}
@media (max-width:560px){.marketplace-hero{padding:24px 20px}.marketplace-title{font-size:1.8rem}.marketplace-bot-grid{grid-template-columns:1fr}.marketplace-bot-head{flex-direction:column}.marketplace-profession{padding:18px}}
</style>

<div class="marketplace-shell">
  <section class="marketplace-hero">
    <p class="marketplace-kicker">Armory Marketplace<?php if ($realmLabel !== ''): ?> • <?php echo htmlspecialchars($realmLabel); ?><?php endif; ?></p>
    <h1 class="marketplace-title">Browse profession bots by craft and training tier.</h1>
    <p class="marketplace-copy">Each profession section shows the crafters for that trade, their rank tier, and the items they can currently make.</p>
    <div class="marketplace-meta">
      <span class="marketplace-pill"><?php echo (int)count($marketplace); ?> professions tracked</span>
      <span class="marketplace-pill"><?php echo (int)$botCount; ?> crafter listings</span>
      <span class="marketplace-pill"><?php echo (int)$craftCount; ?> crafts tracked</span>
    </div>
  </section>

  <section class="marketplace-search-panel">
    <p class="marketplace-search-label">Search Craftable Items</p>
    <p class="marketplace-search-copy">Filter by item name to find crafters who can make what you need.</p>
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
                </div>
              </div>
              <div class="marketplace-profession-stats">
                <div class="marketplace-profession-stat">
                  <strong><?php echo (int)$profession['total_bots']; ?></strong>
                  <span>Crafters</span>
                </div>
                <div class="marketplace-profession-stat">
                  <strong><?php echo (int)$profession['total_recipes']; ?></strong>
                  <span>Crafts</span>
                </div>
              </div>
            </div>
          </summary>

          <div class="marketplace-profession-body">
          <div class="marketplace-tier-list">
            <?php foreach ($tierOrder as $tierName): ?>
              <?php if (empty($profession['tiers'][$tierName])) continue; ?>
              <details class="marketplace-tier">
                <summary class="marketplace-tier-head">
                  <h3 class="marketplace-tier-title"><?php echo htmlspecialchars($tierName); ?></h3>
                  <span class="marketplace-tier-count"><?php echo (int)count($profession['tiers'][$tierName]); ?> crafters</span>
                </summary>
                <div class="marketplace-tier-body">
                <div class="marketplace-bot-grid">
                  <?php foreach ($profession['tiers'][$tierName] as $bot): ?>
                    <?php
                      $searchTerms = [];
                      foreach ($bot['crafts'] as $craft) {
                          $searchTerms[] = strtolower((string)$craft['name']);
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
                            <span class="marketplace-bot-meta">Level <?php echo (int)$bot['level']; ?> crafter</span>
                          </span>
                        </a>
                        <span class="marketplace-bot-rank"><?php echo (int)$bot['value']; ?>/<?php echo (int)$bot['max']; ?></span>
                      </div>

                      <div class="marketplace-progress">
                        <div class="marketplace-progress-track">
                          <div class="marketplace-progress-fill" style="width: <?php echo (int)min(100, max(0, round(($bot['max'] > 0 ? ($bot['value'] / $bot['max']) * 100 : 0)))); ?>%"></div>
                        </div>
                        <div class="marketplace-progress-copy">
                          <span><?php echo htmlspecialchars($tierName); ?></span>
                          <span><?php echo (int)$bot['craft_count']; ?> crafts</span>
                        </div>
                      </div>

                      <details class="marketplace-recipe-box">
                        <summary>
                          <strong>Crafts</strong>
                          <span><?php echo (int)$bot['craft_count']; ?> listed</span>
                        </summary>
                        <?php if (!empty($bot['crafts'])): ?>
                          <div class="marketplace-recipe-list">
                            <?php foreach ($bot['crafts'] as $craft): ?>
                              <a class="marketplace-recipe" href="index.php?n=server&amp;sub=item&amp;realm=<?php echo (int)$realmId; ?>&amp;item=<?php echo (int)$craft['entry']; ?>">
                                <img src="<?php echo htmlspecialchars($craft['icon']); ?>" alt="<?php echo htmlspecialchars($craft['name']); ?>">
                                <span>
                                  <strong style="color: <?php echo htmlspecialchars(spp_marketplace_quality_color($craft['quality'])); ?>;"><?php echo htmlspecialchars($craft['name']); ?></strong>
                                  <small><?php echo (int)$craft['required_rank']; ?> skill</small>
                                </span>
                              </a>
                            <?php endforeach; ?>
                          </div>
                        <?php else: ?>
                          <div class="marketplace-empty">No craftable items were found for this crafter yet.</div>
                        <?php endif; ?>
                      </details>
                    </article>
                  <?php endforeach; ?>
                </div>
                </div>
              </details>
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
      var tierCards = professionCard.querySelectorAll('.marketplace-tier');
      var professionVisible = false;

      tierCards.forEach(function (tierCard) {
        var bots = tierCard.querySelectorAll('.marketplace-bot');
        var tierVisible = false;

        bots.forEach(function (botCard) {
          var haystack = (botCard.getAttribute('data-craft-search') || '').toLowerCase();
          var match = query === '' || haystack.indexOf(query) !== -1;
          botCard.style.display = match ? '' : 'none';
          if (match) {
            tierVisible = true;
          }
        });

        tierCard.style.display = tierVisible ? '' : 'none';
        if (query !== '' && tierVisible) {
          tierCard.open = true;
        }
        if (tierVisible) {
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
