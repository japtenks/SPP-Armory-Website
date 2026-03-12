<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

function spp_character_table_exists(PDO $pdo, $tableName) {
    static $cache = array();
    $key = spl_object_hash($pdo) . ':' . $tableName;
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    $stmt->execute(array($tableName));
    return $cache[$key] = (bool)$stmt->fetchColumn();
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
    if (substr($iconName, -4) !== '.png') $iconName .= '.png';
    return '/armory/images/icons/64x64/' . strtolower($iconName);
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
$tabs = array('overview', 'talents', 'reputation', 'skills', 'achievements');
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
$skillsByCategory = array();
$achievementSummary = array('supported' => false, 'count' => 0, 'points' => 0, 'recent' => array());

builddiv_start(1, 'Character Profile', 1);

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
        $selectColumns = array('guid', 'name', 'race', 'class', 'gender', 'level', 'zone', 'online', 'totaltime');
        foreach (array('health', 'power1', 'stored_honorable_kills', 'stored_honor_rating', 'honor_highest_rank', 'totalKills', 'totalHonorPoints') as $columnName) {
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
            $stmt = $charsPdo->prepare('SELECT `slot`, `item_template` FROM `character_inventory` WHERE `guid` = ? AND `bag` = 0 AND `slot` BETWEEN 0 AND 18 ORDER BY `slot` ASC');
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
                    'entry' => $entry,
                    'name' => (string)$itemRow['name'],
                    'quality' => (int)$itemRow['Quality'],
                    'item_level' => (int)$itemRow['ItemLevel'],
                    'required_level' => (int)$itemRow['RequiredLevel'],
                    'icon' => spp_character_icon_url($iconMap[(int)$itemRow['displayid']] ?? ''),
                );
            }
        }

        $stmt = $armoryPdo->prepare('SELECT tt.`id`, tt.`name`, tt.`tab_number`, si.`name` AS `icon_name` FROM `dbc_talenttab` tt LEFT JOIN `dbc_spellicon` si ON si.`id` = tt.`SpellIconID` WHERE (tt.`refmask_chrclasses` & ?) <> 0 ORDER BY tt.`tab_number` ASC');
        $stmt->execute(array(1 << ((int)$character['class'] - 1)));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tabRow) {
            $tabId = (int)$tabRow['id'];
            $talentTabs[$tabId] = array('name' => (string)$tabRow['name'], 'points' => 0, 'icon' => spp_character_icon_url($tabRow['icon_name'] ?? ''));
        }
        if (!empty($talentTabs) && spp_character_table_exists($charsPdo, 'character_talent')) {
            $stmt = $charsPdo->prepare('SELECT `talent_id`, `current_rank` FROM `character_talent` WHERE `guid` = ?');
            $stmt->execute(array($characterGuid));
            $talentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $talentIds = array();
            foreach ($talentRows as $row) $talentIds[(int)$row['talent_id']] = true;
            if (!empty($talentIds)) {
                $placeholders = implode(',', array_fill(0, count($talentIds), '?'));
                $stmt = $armoryPdo->prepare('SELECT `id`, `ref_talenttab` FROM `dbc_talent` WHERE `id` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($talentIds));
                $talentMap = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $talentMap[(int)$row['id']] = (int)$row['ref_talenttab'];
                foreach ($talentRows as $row) {
                    $talentId = (int)$row['talent_id'];
                    $tabId = $talentMap[$talentId] ?? 0;
                    if ($tabId > 0 && isset($talentTabs[$tabId])) $talentTabs[$tabId]['points'] += (int)$row['current_rank'] + 1;
                }
            }
        }
        if (spp_character_table_exists($charsPdo, 'character_reputation')) {
            $stmt = $charsPdo->prepare('SELECT `faction`, `standing`, `flags` FROM `character_reputation` WHERE `guid` = ? AND (`flags` & 1 = 1)');
            $stmt->execute(array($characterGuid));
            $repRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $factionIds = array();
            foreach ($repRows as $row) $factionIds[(int)$row['faction']] = true;
            $factionMap = array();
            if (!empty($factionIds)) {
                $placeholders = implode(',', array_fill(0, count($factionIds), '?'));
                $factionColumns = spp_character_columns($armoryPdo, 'dbc_faction');
                $selectParts = array('`id`', '`name`', '`description`');
                for ($idx = 0; $idx <= 4; $idx++) {
                    $raceField = 'base_ref_chrraces_' . $idx;
                    $modifierField = 'base_modifier_' . $idx;
                    if (isset($factionColumns[$raceField])) $selectParts[] = '`' . $raceField . '`';
                    if (isset($factionColumns[$modifierField])) $selectParts[] = '`' . $modifierField . '`';
                }
                $stmt = $armoryPdo->prepare('SELECT ' . implode(', ', $selectParts) . ' FROM `dbc_faction` WHERE `id` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($factionIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $factionMap[(int)$row['id']] = $row;
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
                $reputations[] = array('name' => (string)$faction['name'], 'description' => trim((string)($faction['description'] ?? '')), 'label' => $rank['label'], 'value' => $rank['value'], 'max' => $rank['max'], 'standing' => $standing, 'percent' => $rank['max'] > 0 ? min(100, max(0, round(($rank['value'] / $rank['max']) * 100))) : 0);
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
                $stmt = $armoryPdo->prepare('SELECT sl.`id`, sl.`name`, sl.`description`, sc.`name` AS `category_name`, si.`name` AS `icon_name` FROM `dbc_skillline` sl LEFT JOIN `dbc_skilllinecategory` sc ON sc.`id` = sl.`ref_skilllinecategory` LEFT JOIN `dbc_spellicon` si ON si.`id` = sl.`ref_spellicon` WHERE sl.`id` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($skillIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $skillMap[(int)$row['id']] = $row;
            }
            foreach ($skillRows as $row) {
                $skillId = (int)$row['skill'];
                if (!isset($skillMap[$skillId])) continue;
                $meta = $skillMap[$skillId];
                $category = trim((string)($meta['category_name'] ?? 'Other'));
                if ($category === '') $category = 'Other';
                if (!isset($skillsByCategory[$category])) $skillsByCategory[$category] = array();
                $value = (int)$row['value'];
                $max = max(1, (int)$row['max']);
                $skillsByCategory[$category][] = array('name' => (string)$meta['name'], 'description' => trim((string)($meta['description'] ?? '')), 'value' => $value, 'max' => $max, 'percent' => min(100, max(0, round(($value / $max) * 100))), 'icon' => spp_character_icon_url($meta['icon_name'] ?? ''));
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_achievement') && spp_character_table_exists($armoryPdo, 'dbc_achievement')) {
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
                $stmt = $armoryPdo->prepare('SELECT `id`, `name`, `description`, `points` FROM `dbc_achievement` WHERE `id` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($achievementIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $achievementMap[(int)$row['id']] = $row;
            }
            foreach ($achievementRows as $index => $row) {
                $achievement = $achievementMap[(int)$row['achievement']] ?? array('id' => (int)$row['achievement'], 'name' => 'Achievement #' . (int)$row['achievement'], 'description' => '', 'points' => 0);
                $achievementSummary['points'] += (int)($achievement['points'] ?? 0);
                if ($index < 12) $achievementSummary['recent'][] = $achievement;
            }
        }
    } catch (Throwable $e) {
        error_log('[character-profile] ' . $e->getMessage());
        $pageError = ($e->getMessage() === 'Character not found.') ? 'Character not found.' : 'Character details could not be loaded.';
    }
}

$realmLabel = $realmMap[$realmId]['label'] ?? 'Realm';
$zoneName = isset($character['zone']) && isset($GLOBALS['MANG']) && $GLOBALS['MANG'] instanceof Mangos ? $GLOBALS['MANG']->get_zone_name((int)$character['zone']) : 'Unknown zone';
$portraitUrl = $character ? spp_character_portrait_path($character['level'], $character['gender'], $character['race'], $character['class']) : '';
$factionName = $character ? spp_character_faction_name($character['race']) : '';
$classSlug = $character ? strtolower(str_replace(' ', '', $classNames[(int)$character['class']] ?? 'unknown')) : 'unknown';
$characterUrl = 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . urlencode((string)$characterName);
$talentCalculatorUrl = 'index.php?n=server&sub=talents&realm=' . (int)$realmId . '&character=' . urlencode((string)$characterName);
$guildId = (int)($character['guildid'] ?? 0);
$guildName = (string)($character['guild_name'] ?? '');
$talentList = array_values($talentTabs);
usort($talentList, function ($a, $b) { return $b['points'] <=> $a['points']; });
$reputationHighlights = $reputations;
usort($reputationHighlights, function ($a, $b) { return $b['standing'] <=> $a['standing']; });
$reputationHighlights = array_slice($reputationHighlights, 0, 5);
$professionHighlights = !empty($skillsByCategory['Professions']) ? array_slice($skillsByCategory['Professions'], 0, 2) : array();
?>
<style>
.character-page{display:grid;gap:18px;color:#f4ead0}.character-hero{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,.9fr);gap:22px;padding:28px;border-radius:22px;border:1px solid rgba(255,196,0,.22);background:radial-gradient(circle at top right,rgba(255,178,54,.15),transparent 36%),linear-gradient(180deg,rgba(8,10,20,.98),rgba(5,6,14,1))}.character-identity{display:flex;gap:20px;align-items:flex-start}.character-portrait{width:118px;height:118px;border-radius:26px;border:1px solid rgba(255,196,0,.38);background:#050505;object-fit:cover}.character-eyebrow{margin:0 0 8px;color:#c7b07b;letter-spacing:.08em;text-transform:uppercase;font-size:.8rem}.character-title{margin:0;font-size:2.7rem;line-height:1}.character-title a{color:inherit;text-decoration:none}.class-warrior{color:#C79C6E}.class-paladin{color:#F58CBA}.class-hunter{color:#ABD473}.class-rogue{color:#FFF569}.class-priest{color:#FFFFFF}.class-deathknight{color:#C41F3B}.class-shaman{color:#0070DE}.class-mage{color:#69CCF0}.class-warlock{color:#9482C9}.class-druid{color:#FF7D0A}.character-subtitle{margin:10px 0 0;color:#e2d4ae;font-size:1.05rem}.character-meta-strip,.character-actions,.character-tabs{display:flex;gap:10px;flex-wrap:wrap}.character-meta-strip{margin-top:18px}.character-pill,.character-tab,.character-link{display:inline-flex;align-items:center;min-height:40px;padding:0 14px;border-radius:999px;border:1px solid rgba(255,204,72,.16);background:rgba(255,255,255,.04);color:#f2dfb1;text-decoration:none;font-weight:700}.character-link{border-color:rgba(255,196,0,.34);color:#ffe39a;background:rgba(255,204,72,.06)}.character-tab.is-active{color:#120d03;background:linear-gradient(180deg,#ffd87a,#d9a63d);border-color:rgba(255,204,72,.45)}.character-hero-grid,.character-grid{display:grid;gap:18px}.character-hero-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.character-grid{grid-template-columns:minmax(300px,.9fr) minmax(0,1.4fr)}.character-stat-card,.character-panel,.character-item,.character-skill-item,.character-achievement-item{border-radius:18px;border:1px solid rgba(255,196,0,.18);background:rgba(5,8,18,.72)}.character-stat-card{padding:18px}.character-stat-label,.character-item-slot{display:block;margin-bottom:6px;color:#c4b27c;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase}.character-stat-value{color:#ffd467;font-size:1.55rem;font-weight:700}.character-panel{padding:22px 24px}.character-panel-title{margin:0 0 16px;color:#fff4c4;font-size:1.55rem}.character-facts,.character-bars,.character-skill-list,.character-achievement-list{display:grid;gap:12px}.character-fact{display:grid;gap:4px;padding-bottom:12px;border-bottom:1px solid rgba(255,204,72,.12)}.character-fact:last-child{padding-bottom:0;border-bottom:0}.character-fact span{color:#bda877;font-size:.83rem;text-transform:uppercase;letter-spacing:.08em}.character-fact strong{color:#f7edd0;font-size:1.08rem}.character-equip-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.character-item{display:grid;grid-template-columns:54px minmax(0,1fr);gap:12px;align-items:center;padding:14px}.character-item img,.character-skill-head img{border-radius:12px;border:1px solid rgba(255,204,72,.22);background:#090909}.character-item img{width:54px;height:54px}.character-item-name{margin-top:4px;font-weight:700}.character-item-name a{color:inherit;text-decoration:none}.quality-0{color:#9d9d9d}.quality-1{color:#fff}.quality-2{color:#1eff00}.quality-3{color:#0070dd}.quality-4{color:#a335ee}.quality-5{color:#ff8000}.character-item-meta,.character-skill-meta{margin-top:4px;color:#aa9870;font-size:.88rem}.character-bar-label{display:flex;align-items:center;justify-content:space-between;color:#d8c89f;font-size:.92rem}.character-bar-track{height:12px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.08)}.character-bar-fill{height:100%;background:linear-gradient(90deg,#ffd45f,#ffeab0)}.character-skill-item,.character-achievement-item{padding:14px 16px}.character-skill-head{display:flex;align-items:center;gap:12px}.character-skill-head img{width:34px;height:34px}.character-empty,.character-error{padding:18px;border-radius:16px}.character-empty{background:rgba(255,255,255,.03);color:#c8b78c;border:1px dashed rgba(255,204,72,.16)}.character-error{background:rgba(95,16,16,.4);border:1px solid rgba(255,122,122,.25);color:#ffd5d5}.character-achievement-points{color:#ffd467;font-weight:700}@media (max-width:1100px){.character-hero,.character-grid{grid-template-columns:1fr}}@media (max-width:720px){.character-identity{flex-direction:column}.character-title{font-size:2.1rem}.character-hero-grid{grid-template-columns:1fr 1fr}}@media (max-width:560px){.character-hero-grid{grid-template-columns:1fr}.character-item{grid-template-columns:46px minmax(0,1fr)}.character-item img{width:46px;height:46px}}
</style>
<div class="character-page">
<?php if ($pageError !== ''): ?>
  <div class="character-error"><?php echo htmlspecialchars($pageError); ?></div>
<?php elseif ($character): ?>
  <section class="character-hero">
    <div>
      <div class="character-identity">
        <img class="character-portrait" src="<?php echo htmlspecialchars($portraitUrl); ?>" alt="">
        <div>
          <p class="character-eyebrow"><?php echo htmlspecialchars($realmLabel); ?> Character Profile</p>
          <h1 class="character-title class-<?php echo htmlspecialchars($classSlug); ?>"><a href="<?php echo htmlspecialchars($characterUrl); ?>"><?php echo htmlspecialchars($characterName); ?></a></h1>
          <p class="character-subtitle">Level <?php echo (int)$character['level']; ?> <?php echo htmlspecialchars($raceNames[(int)$character['race']] ?? 'Unknown'); ?> <?php echo htmlspecialchars($classNames[(int)$character['class']] ?? 'Unknown'); ?><?php if ($guildId > 0 && $guildName !== ''): ?> | <a href="<?php echo htmlspecialchars('index.php?n=server&sub=guild&realm=' . (int)$realmId . '&guildid=' . $guildId); ?>" style="color:#ffe39a;text-decoration:none;">&lt;<?php echo htmlspecialchars($guildName); ?>&gt;</a><?php endif; ?></p>
          <div class="character-meta-strip"><span class="character-pill"><?php echo htmlspecialchars($factionName); ?></span><span class="character-pill"><?php echo htmlspecialchars($zoneName); ?></span><span class="character-pill"><?php echo !empty($character['online']) ? 'Online Now' : 'Offline'; ?></span></div>
          <div class="character-actions"><a class="character-link" href="index.php?n=server&sub=chars&realm=<?php echo (int)$realmId; ?>">Back to Characters</a><a class="character-link" href="<?php echo htmlspecialchars($talentCalculatorUrl); ?>">Open Talent Calculator</a></div>
        </div>
      </div>
    </div>
    <div class="character-hero-grid">
      <div class="character-stat-card"><span class="character-stat-label">Play Time</span><div class="character-stat-value"><?php echo htmlspecialchars(spp_character_format_playtime($character['totaltime'] ?? 0)); ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Achievement Points</span><div class="character-stat-value"><?php echo (int)$achievementSummary['points']; ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Total Kills</span><div class="character-stat-value"><?php echo (int)($character['stored_honorable_kills'] ?? $character['totalKills'] ?? 0); ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Best Talent Tree</span><div class="character-stat-value"><?php echo !empty($talentList) ? (int)$talentList[0]['points'] . ' pts' : '0'; ?></div></div>
    </div>
  </section>

  <nav class="character-tabs">
    <?php foreach ($tabs as $tabName): ?>
      <a class="character-tab<?php echo $tab === $tabName ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($characterUrl . '&tab=' . urlencode($tabName)); ?>"><?php echo htmlspecialchars(ucfirst($tabName)); ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($tab === 'overview'): ?>
    <section class="character-grid">
      <div class="character-panel"><h2 class="character-panel-title">Quick Facts</h2><div class="character-facts"><div class="character-fact"><span>Realm</span><strong><?php echo htmlspecialchars($realmLabel); ?></strong></div><div class="character-fact"><span>Location</span><strong><?php echo htmlspecialchars($zoneName); ?></strong></div><div class="character-fact"><span>Guild</span><strong><?php echo $guildName !== '' ? htmlspecialchars($guildName) : 'Unaffiliated'; ?></strong></div><div class="character-fact"><span>Health</span><strong><?php echo (int)($stats['maxhealth'] ?? $character['health'] ?? 0); ?></strong></div><div class="character-fact"><span>Primary Power</span><strong><?php echo (int)($stats['maxpower1'] ?? $character['power1'] ?? 0); ?></strong></div><div class="character-fact"><span>Visible Reputations</span><strong><?php echo count($reputations); ?></strong></div></div></div>
      <div class="character-panel"><h2 class="character-panel-title">Stat Snapshot</h2><div class="character-facts"><?php foreach (array('strength' => 'Strength', 'agility' => 'Agility', 'stamina' => 'Stamina', 'intellect' => 'Intellect', 'spirit' => 'Spirit', 'armor' => 'Armor', 'critPct' => 'Crit', 'dodgePct' => 'Dodge', 'parryPct' => 'Parry', 'blockPct' => 'Block', 'attackPower' => 'Attack Power', 'healBonus' => 'Healing') as $statKey => $label): ?><?php if (isset($stats[$statKey])): ?><div class="character-fact"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars((string)$stats[$statKey]); ?></strong></div><?php endif; ?><?php endforeach; ?></div></div>
    </section>

    <section class="character-grid">
      <div class="character-panel"><h2 class="character-panel-title">Talents & Professions</h2><div class="character-bars"><?php $talentCap = max(1, (int)$character['level'] - 9); ?><?php if (!empty($talentList)): foreach ($talentList as $talentTab): ?><div><div class="character-bar-label"><span><?php echo htmlspecialchars($talentTab['name']); ?></span><strong><?php echo (int)$talentTab['points']; ?> pts</strong></div><div class="character-bar-track"><div class="character-bar-fill" style="width: <?php echo min(100, max(0, round(($talentTab['points'] / $talentCap) * 100))); ?>%"></div></div></div><?php endforeach; else: ?><div class="character-empty">Talent data is not available for this character yet.</div><?php endif; ?><?php foreach ($professionHighlights as $profession): ?><div><div class="character-bar-label"><span><?php echo htmlspecialchars($profession['name']); ?></span><strong><?php echo (int)$profession['value']; ?>/<?php echo (int)$profession['max']; ?></strong></div><div class="character-bar-track"><div class="character-bar-fill" style="width: <?php echo (int)$profession['percent']; ?>%"></div></div></div><?php endforeach; ?></div></div>
      <div class="character-panel"><h2 class="character-panel-title">Standing Highlights</h2><?php if (!empty($reputationHighlights)): ?><div class="character-bars"><?php foreach ($reputationHighlights as $reputation): ?><div><div class="character-bar-label"><span><?php echo htmlspecialchars($reputation['name']); ?></span><strong><?php echo htmlspecialchars($reputation['label']); ?></strong></div><div class="character-bar-track"><div class="character-bar-fill" style="width: <?php echo (int)$reputation['percent']; ?>%"></div></div></div><?php endforeach; ?></div><?php else: ?><div class="character-empty">No visible reputations were found.</div><?php endif; ?></div>
    </section>

    <section class="character-panel"><h2 class="character-panel-title">Equipped Items</h2><?php if (!empty($equipment)): ?><div class="character-equip-grid"><?php foreach ($equipment as $item): ?><div class="character-item"><img src="<?php echo htmlspecialchars($item['icon']); ?>" alt=""><div><div class="character-item-slot"><?php echo htmlspecialchars($item['slot_name']); ?></div><div class="character-item-name quality-<?php echo (int)$item['quality']; ?>"><a href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>"><?php echo htmlspecialchars($item['name']); ?></a></div><div class="character-item-meta">Item level <?php echo (int)$item['item_level']; ?><?php if ($item['required_level'] > 0): ?> | Req. <?php echo (int)$item['required_level']; ?><?php endif; ?></div></div></div><?php endforeach; ?></div><?php else: ?><div class="character-empty">No equipped items could be read from the realm database.</div><?php endif; ?></section>
  <?php endif; ?>

  <?php if ($tab === 'talents'): ?><div style="margin-top:4px;"><?php $__savedGet = $_GET; $_GET['realm'] = (string)$realmId; $_GET['character'] = (string)$characterName; $_GET['mode'] = 'profile'; $_GET['embed'] = '1'; unset($_GET['class']); include($_SERVER['DOCUMENT_ROOT'] . '/templates/offlike/server/server.talents.php'); $_GET = $__savedGet; ?></div><?php endif; ?>
  <?php if ($tab === 'reputation'): ?><section class="character-panel"><h2 class="character-panel-title">Reputation</h2><?php if (!empty($reputations)): ?><div class="character-skill-list"><?php foreach ($reputations as $reputation): ?><div class="character-skill-item"><div class="character-bar-label"><span><?php echo htmlspecialchars($reputation['name']); ?></span><strong><?php echo htmlspecialchars($reputation['label']); ?></strong></div><div class="character-bar-track" style="margin-top:10px;"><div class="character-bar-fill" style="width: <?php echo (int)$reputation['percent']; ?>%"></div></div><div class="character-skill-meta"><?php echo (int)$reputation['value']; ?>/<?php echo (int)$reputation['max']; ?><?php if ($reputation['description'] !== ''): ?> | <?php echo htmlspecialchars($reputation['description']); ?><?php endif; ?></div></div><?php endforeach; ?></div><?php else: ?><div class="character-empty">No visible reputations were found for this character.</div><?php endif; ?></section><?php endif; ?>
  <?php if ($tab === 'skills'): ?><section class="character-panel"><h2 class="character-panel-title">Skills</h2><?php if (!empty($skillsByCategory)): ?><?php foreach ($skillsByCategory as $categoryName => $categorySkills): ?><div style="margin-top:18px;"><h3 style="margin:0 0 12px;color:#ffd467;"><?php echo htmlspecialchars($categoryName); ?></h3><div class="character-skill-list"><?php foreach ($categorySkills as $skill): ?><div class="character-skill-item"><div class="character-skill-head"><img src="<?php echo htmlspecialchars($skill['icon']); ?>" alt=""><div><strong><?php echo htmlspecialchars($skill['name']); ?></strong><div class="character-skill-meta"><?php echo (int)$skill['value']; ?>/<?php echo (int)$skill['max']; ?><?php if ($skill['description'] !== ''): ?> | <?php echo htmlspecialchars($skill['description']); ?><?php endif; ?></div></div></div><div class="character-bar-track" style="margin-top:12px;"><div class="character-bar-fill" style="width: <?php echo (int)$skill['percent']; ?>%"></div></div></div><?php endforeach; ?></div></div><?php endforeach; ?><?php else: ?><div class="character-empty">No skills could be read from the realm database.</div><?php endif; ?></section><?php endif; ?>
  <?php if ($tab === 'achievements'): ?><section class="character-panel"><h2 class="character-panel-title">Achievements</h2><?php if ($achievementSummary['supported']): ?><div class="character-hero-grid" style="margin-bottom:18px;"><div class="character-stat-card"><span class="character-stat-label">Completed</span><div class="character-stat-value"><?php echo (int)$achievementSummary['count']; ?></div></div><div class="character-stat-card"><span class="character-stat-label">Points</span><div class="character-stat-value"><?php echo (int)$achievementSummary['points']; ?></div></div></div><?php if (!empty($achievementSummary['recent'])): ?><div class="character-achievement-list"><?php foreach ($achievementSummary['recent'] as $achievement): ?><div class="character-achievement-item"><strong><?php echo htmlspecialchars($achievement['name']); ?></strong><div class="character-skill-meta"><?php echo htmlspecialchars(trim((string)($achievement['description'] ?? ''))); ?></div><div class="character-skill-meta">+<span class="character-achievement-points"><?php echo (int)($achievement['points'] ?? 0); ?></span> points</div></div><?php endforeach; ?></div><?php else: ?><div class="character-empty">This character has no recorded achievements yet.</div><?php endif; ?><?php else: ?><div class="character-empty">Achievements are not available for this realm ruleset or database layout yet.</div><?php endif; ?></section><?php endif; ?>
<?php endif; ?>
</div>
<?php builddiv_end(); ?>










