<?php
$siteDatabaseHandle = $GLOBALS['DB'] ?? null;
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/settings.php');
if ($siteDatabaseHandle !== null) {
    $GLOBALS['DB'] = $siteDatabaseHandle;
    $DB = $siteDatabaseHandle;
}

if (!function_exists('spp_modern_item_quality_color')) {
    function spp_modern_item_quality_color($quality) {
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

if (!function_exists('spp_modern_item_quality_label')) {
    function spp_modern_item_quality_label($quality) {
        $labels = [0 => 'Poor', 1 => 'Common', 2 => 'Uncommon', 3 => 'Rare', 4 => 'Epic', 5 => 'Legendary'];
        $quality = (int)$quality;
        return $labels[$quality] ?? 'Unknown';
    }
}

if (!function_exists('spp_modern_item_icon_url')) {
    function spp_modern_item_icon_url($iconName) {
        $iconName = trim((string)$iconName);
        if ($iconName === '') {
            return '/armory/images/icons/64x64/404.png';
        }
        if (preg_match('#^https?://#i', $iconName) || strpos($iconName, '//') === 0) {
            return $iconName;
        }
        if ($iconName[0] === '/') {
            return $iconName;
        }
        if (strpos($iconName, 'images/') === 0) {
            return '/armory/' . $iconName;
        }
        if (strpos($iconName, 'armory/') === 0) {
            return '/' . $iconName;
        }
        if (substr($iconName, -4) !== '.png') {
            $iconName .= '.png';
        }
        return '/armory/images/icons/64x64/' . strtolower($iconName);
    }
}

if (!function_exists('spp_modern_item_inventory_type_name')) {
    function spp_modern_item_inventory_type_name($inventoryType) {
        $map = [1 => 'Head', 2 => 'Neck', 3 => 'Shoulder', 5 => 'Chest', 6 => 'Waist', 7 => 'Legs', 8 => 'Feet', 9 => 'Wrist', 10 => 'Hands', 11 => 'Finger', 12 => 'Trinket', 13 => 'One Hand', 14 => 'Shield', 15 => 'Weapon', 16 => 'Back', 17 => 'Two-Hand', 21 => 'Main Hand', 22 => 'Off Hand', 23 => 'Held In Off-hand'];
        $inventoryType = (int)$inventoryType;
        return $map[$inventoryType] ?? ('Slot ' . $inventoryType);
    }
}

if (!function_exists('spp_modern_item_class_name')) {
    function spp_modern_item_class_name($class, $subclass) {
        $class = (int)$class;
        $subclass = (int)$subclass;
        switch ($class) {
            case 2:
                $map = [0 => 'Axe (1H)', 1 => 'Axe (2H)', 2 => 'Bow', 3 => 'Gun', 4 => 'Mace (1H)', 5 => 'Mace (2H)', 6 => 'Polearm', 7 => 'Sword (1H)', 8 => 'Sword (2H)', 10 => 'Staff', 13 => 'Fist Weapon', 15 => 'Dagger', 16 => 'Thrown', 18 => 'Crossbow', 19 => 'Wand', 20 => 'Fishing Pole'];
                return $map[$subclass] ?? 'Weapon';
            case 4:
                $map = [0 => 'Misc', 1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate', 6 => 'Shield', 7 => 'Libram', 8 => 'Idol', 9 => 'Totem', 10 => 'Sigil'];
                return $map[$subclass] ?? 'Armor';
            case 15:
                $map = [0 => 'Junk', 2 => 'Pet', 3 => 'Holiday', 4 => 'Other', 5 => 'Mount'];
                return $map[$subclass] ?? 'Misc';
            default:
                return 'Item';
        }
    }
}

if (!function_exists('spp_modern_item_format_money')) {
    function spp_modern_item_format_money($value) {
        $value = (int)$value;
        $gold = intdiv($value, 10000);
        $silver = intdiv($value % 10000, 100);
        $copper = $value % 100;
        $parts = [];
        if ($gold > 0) $parts[] = $gold . 'g';
        if ($silver > 0) $parts[] = $silver . 's';
        if ($copper > 0 || !$parts) $parts[] = $copper . 'c';
        return implode(' ', $parts);
    }
}

if (!function_exists('spp_modern_item_cache_source')) {
    function spp_modern_item_cache_source(PDO $worldPdo, PDO $armoryPdo, $itemId, $isPvpReward) {
        $itemId = (int)$itemId;
        $checks = [
            ['SELECT `entry` FROM `quest_template` WHERE `SrcItemId` = ? LIMIT 1', 'Quest Item'],
            ['SELECT `entry` FROM `npc_vendor` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward (Vendor)' : 'Vendor'],
            ['SELECT `entry` FROM `npc_vendor_template` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward (Vendor)' : 'Vendor'],
        ];
        foreach ($checks as $check) {
            $stmt = $worldPdo->prepare($check[0]);
            $stmt->execute([$itemId]);
            if ($stmt->fetchColumn()) {
                return $check[1];
            }
        }

        $objectStmt = $worldPdo->prepare('SELECT `entry` FROM `gameobject_loot_template` WHERE `item` = ? LIMIT 1');
        $objectStmt->execute([$itemId]);
        $objectLootId = $objectStmt->fetchColumn();
        if ($objectLootId) {
            $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'object\' LIMIT 1');
            $instanceStmt->execute([$objectLootId, $objectLootId, $objectLootId, $objectLootId, $objectLootId, $objectLootId]);
            $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
            if ($instanceLoot) {
                $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                $templateStmt->execute([(int)$instanceLoot['instance_id']]);
                $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                if ($instanceInfo) {
                    $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                    return trim($instanceLoot['name_en_gb'] . ' - ' . $instanceInfo['name_en_gb'] . $suffix);
                }
            }
            return 'Chest Drop';
        }

        $creatureStmt = $worldPdo->prepare('SELECT `entry` FROM `creature_loot_template` WHERE `item` = ? LIMIT 1');
        $creatureStmt->execute([$itemId]);
        $creatureLootId = $creatureStmt->fetchColumn();
        if ($creatureLootId) {
            $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'npc\' LIMIT 1');
            $instanceStmt->execute([$creatureLootId, $creatureLootId, $creatureLootId, $creatureLootId, $creatureLootId, $creatureLootId]);
            $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
            if ($instanceLoot) {
                $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                $templateStmt->execute([(int)$instanceLoot['instance_id']]);
                $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                if ($instanceInfo) {
                    $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                    return trim($instanceLoot['name_en_gb'] . ' - ' . $instanceInfo['name_en_gb'] . $suffix);
                }
            }
            return 'Drop';
        }

        $questRewardStmt = $worldPdo->prepare('SELECT `entry` FROM `quest_template` WHERE `RewItemId1` = ? OR `RewItemId2` = ? OR `RewItemId3` = ? OR `RewItemId4` = ? LIMIT 1');
        $questRewardStmt->execute([$itemId, $itemId, $itemId, $itemId]);
        if ($questRewardStmt->fetchColumn()) {
            return 'Quest Reward';
        }

        return 'Created';
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$realmLabel = $realmMap[$realmId]['label'] ?? 'Realm';
$itemId = isset($_GET['item']) ? (int)$_GET['item'] : 0;

$classNames = [1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest', 6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'];
$raceNames = [1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead', 6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$item = null;
$itemSet = null;
$owners = [];
$pageError = '';
$legacyRealmName = '';

if (!empty($realms) && is_array($realms)) {
    foreach ($realms as $name => $keys) {
        if ((int)($keys[2] ?? 0) === (int)$realmId) {
            $legacyRealmName = (string)$name;
            break;
        }
    }
}

if ($itemId <= 0) {
    $pageError = 'No item was selected.';
} elseif (!is_array($realmMap) || !isset($realmMap[$realmId])) {
    $pageError = 'The requested realm could not be loaded.';
} else {
    try {
        $worldPdo = spp_get_pdo('world', $realmId);
        $armoryPdo = spp_get_pdo('armory', $realmId);
        $charsPdo = spp_get_pdo('chars', $realmId);

        $localeId = isset($config['locales']) ? (int)$config['locales'] : 0;
        $localeField = $localeId > 0 ? 'name_loc' . $localeId : null;

        if ($localeField) {
            $itemStmt = $worldPdo->prepare('SELECT it.*, li.`' . $localeField . '` AS `localized_name`, li.`description_loc' . $localeId . '` AS `localized_description` FROM `item_template` it LEFT JOIN `locales_item` li ON li.`entry` = it.`entry` WHERE it.`entry` = ? LIMIT 1');
        } else {
            $itemStmt = $worldPdo->prepare('SELECT * FROM `item_template` WHERE `entry` = ? LIMIT 1');
        }
        $itemStmt->execute([$itemId]);
        $itemRow = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$itemRow) {
            $pageError = 'That item could not be found.';
        } else {
            $displayId = (int)($itemRow['displayid'] ?? 0);
            $iconName = '';
            if ($displayId > 0) {
                $iconStmt = $armoryPdo->prepare('SELECT `name` FROM `dbc_itemdisplayinfo` WHERE `id` = ? LIMIT 1');
                $iconStmt->execute([$displayId]);
                $iconName = (string)$iconStmt->fetchColumn();
            }

            $itemName = ($localeField && !empty($itemRow['localized_name'])) ? (string)$itemRow['localized_name'] : (string)$itemRow['name'];
            $itemDescription = ($localeField && !empty($itemRow['localized_description'])) ? (string)$itemRow['localized_description'] : trim((string)($itemRow['description'] ?? ''));
            $quality = (int)($itemRow['Quality'] ?? 0);
            $flags = (int)($itemRow['Flags'] ?? 0);

            $item = [
                'id' => $itemId,
                'name' => $itemName,
                'description' => $itemDescription,
                'quality' => $quality,
                'quality_label' => spp_modern_item_quality_label($quality),
                'quality_color' => spp_modern_item_quality_color($quality),
                'icon' => spp_modern_item_icon_url($iconName),
                'level' => (int)($itemRow['ItemLevel'] ?? 0),
                'required_level' => (int)($itemRow['RequiredLevel'] ?? 0),
                'required_skill' => (int)($itemRow['RequiredDisenchantSkill'] ?? 0),
                'buy_price' => (int)($itemRow['BuyPrice'] ?? 0),
                'sell_price' => (int)($itemRow['SellPrice'] ?? 0),
                'max_durability' => (int)($itemRow['MaxDurability'] ?? 0),
                'slot_name' => spp_modern_item_inventory_type_name((int)($itemRow['InventoryType'] ?? 0)),
                'class_name' => spp_modern_item_class_name((int)($itemRow['class'] ?? 0), (int)($itemRow['subclass'] ?? 0)),
                'source' => spp_modern_item_cache_source($worldPdo, $armoryPdo, $itemId, (($flags & 32768) === 32768)),
            ];

            $itemSetId = (int)($itemRow['itemset'] ?? 0);
            if ($itemSetId > 0) {
                $setStmt = $armoryPdo->prepare('SELECT * FROM `dbc_itemset` WHERE `id` = ? LIMIT 1');
                $setStmt->execute([$itemSetId]);
                $setRow = $setStmt->fetch(PDO::FETCH_ASSOC);
                if ($setRow) {
                    $itemSet = [
                        'name' => (string)($setRow['name'] ?? 'Item Set'),
                        'pieces' => [],
                        'bonuses' => [],
                    ];

                    for ($setIndex = 1; $setIndex <= 10; $setIndex++) {
                        $setItemId = (int)($setRow['item_' . $setIndex] ?? 0);
                        if ($setItemId <= 0) {
                            continue;
                        }
                        $setItemStmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `item_template` WHERE `entry` = ? LIMIT 1');
                        $setItemStmt->execute([$setItemId]);
                        $setItemRow = $setItemStmt->fetch(PDO::FETCH_ASSOC);
                        if ($setItemRow) {
                            $itemSet['pieces'][] = [
                                'entry' => (int)$setItemRow['entry'],
                                'name' => (string)$setItemRow['name'],
                                'active' => ((int)$setItemRow['entry'] === $itemId),
                            ];
                        }
                    }

                    for ($bonusIndex = 1; $bonusIndex <= 8; $bonusIndex++) {
                        $bonusSpellId = (int)($setRow['bonus_' . $bonusIndex] ?? 0);
                        $bonusPieces = (int)($setRow['pieces_' . $bonusIndex] ?? 0);
                        if ($bonusSpellId <= 0 || $bonusPieces <= 0) {
                            continue;
                        }
                        $bonusStmt = $armoryPdo->prepare('SELECT `description` FROM `dbc_spell` WHERE `id` = ? LIMIT 1');
                        $bonusStmt->execute([$bonusSpellId]);
                        $bonusDescription = trim((string)$bonusStmt->fetchColumn());
                        if ($bonusDescription !== '') {
                            $itemSet['bonuses'][] = [
                                'pieces' => $bonusPieces,
                                'description' => $bonusDescription,
                            ];
                        }
                    }
                }
            }

            $ownerStmt = $charsPdo->prepare('SELECT DISTINCT c.`guid`, c.`name`, c.`level`, c.`race`, c.`class`, c.`gender`, gm.`guildid`, g.`name` AS `guild_name` FROM `character_inventory` ci INNER JOIN `characters` c ON c.`guid` = ci.`guid` LEFT JOIN `guild_member` gm ON gm.`guid` = c.`guid` LEFT JOIN `guild` g ON g.`guildid` = gm.`guildid` WHERE ci.`item_template` = ? ORDER BY c.`level` DESC, c.`name` ASC LIMIT 100');
            $ownerStmt->execute([$itemId]);
            foreach ($ownerStmt->fetchAll(PDO::FETCH_ASSOC) as $owner) {
                $raceId = (int)($owner['race'] ?? 0);
                $classId = (int)($owner['class'] ?? 0);
                $className = $classNames[$classId] ?? 'Unknown';
                $isAlliance = in_array($raceId, $allianceRaces, true);
                $owners[] = [
                    'guid' => (int)$owner['guid'],
                    'name' => (string)$owner['name'],
                    'level' => (int)($owner['level'] ?? 0),
                    'race' => $raceId,
                    'class' => $classId,
                    'gender' => (int)($owner['gender'] ?? 0),
                    'race_name' => $raceNames[$raceId] ?? 'Unknown',
                    'class_name' => $className,
                    'class_slug' => strtolower(str_replace(' ', '', $className)),
                    'guild_id' => (int)($owner['guildid'] ?? 0),
                    'guild_name' => (string)($owner['guild_name'] ?? ''),
                    'faction' => $isAlliance ? 'Alliance' : 'Horde',
                    'faction_icon' => $isAlliance ? '/armory/images/icon-alliance.gif' : '/armory/images/icon-horde.gif',
                ];
            }
        }
    } catch (Throwable $e) {
        $pageError = 'Item details could not be loaded from the realm databases.';
    }
}

$searchBackUrl = 'index.php?n=server&sub=items&realm=' . (int)$realmId;
if (!empty($_GET['search'])) $searchBackUrl .= '&search=' . urlencode((string)$_GET['search']);
if (!empty($_GET['p'])) $searchBackUrl .= '&p=' . max(1, (int)$_GET['p']);
if (!empty($_GET['per_page'])) $searchBackUrl .= '&per_page=' . max(1, (int)$_GET['per_page']);
if (!empty($_GET['sort'])) $searchBackUrl .= '&sort=' . urlencode((string)$_GET['sort']);
if (!empty($_GET['dir'])) $searchBackUrl .= '&dir=' . urlencode((string)$_GET['dir']);

builddiv_start(1, 'Item Detail', 1);
?>
<link rel="stylesheet" type="text/css" href="/armory/css/armory-tooltips.css" />
<style>
.item-detail-page { display: grid; gap: 18px; }
.item-detail-hero { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.9fr); gap: 22px; padding: 26px 28px; border: 1px solid rgba(255, 196, 0, 0.2); border-radius: 18px; background: radial-gradient(circle at top right, rgba(255, 176, 61, 0.14), transparent 34%), linear-gradient(180deg, rgba(6, 9, 19, 0.96), rgba(4, 5, 13, 0.98)); }
.item-detail-head { display: flex; gap: 20px; align-items: flex-start; }
.item-detail-icon { width: 86px; height: 86px; border-radius: 18px; border: 1px solid rgba(255, 196, 0, 0.35); background: rgba(0, 0, 0, 0.55); box-shadow: 0 18px 34px rgba(0, 0, 0, 0.28); }
.item-detail-eyebrow { margin: 0 0 8px; color: #c6b07a; letter-spacing: 0.08em; text-transform: uppercase; font-size: 0.82rem; }
.item-detail-title { margin: 0; font-size: 2.5rem; line-height: 1.02; }
.item-detail-subtitle { margin: 10px 0 0; color: #e2d4ae; font-size: 1.1rem; }
.item-detail-description { margin: 14px 0 0; color: #bfae82; line-height: 1.55; font-style: italic; }
.item-detail-actions { margin-top: 18px; display: flex; gap: 12px; flex-wrap: wrap; }
.item-detail-link { display: inline-flex; align-items: center; min-height: 42px; padding: 0 16px; border-radius: 999px; border: 1px solid rgba(255, 196, 0, 0.32); color: #ffe39a; text-decoration: none; font-weight: 700; background: rgba(255, 204, 72, 0.06); }
.item-detail-link:hover { background: rgba(255, 204, 72, 0.12); }
.item-detail-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 18px; align-content: start; }
.item-detail-card { padding: 18px 18px 16px; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255, 204, 72, 0.18); }
.item-detail-card-label { display: block; margin-bottom: 6px; color: #c4b27c; font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase; }
.item-detail-card-value { color: #ffd467; font-size: 1.55rem; font-weight: 700; }
.item-detail-grid { display: grid; grid-template-columns: minmax(320px, 0.9fr) minmax(0, 1.5fr); gap: 18px; }
.item-detail-panel { padding: 22px 24px; border-radius: 18px; border: 1px solid rgba(255, 196, 0, 0.18); background: rgba(5, 8, 18, 0.62); }
.item-detail-panel-title { margin: 0 0 16px; color: #fff4c4; font-size: 1.7rem; }
.item-detail-facts { display: grid; gap: 12px; }
.item-detail-fact { display: grid; gap: 4px; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 204, 72, 0.12); }
.item-detail-fact:last-child { padding-bottom: 0; border-bottom: 0; }
.item-detail-fact span { color: #bda877; font-size: 0.83rem; text-transform: uppercase; letter-spacing: 0.08em; }
.item-detail-fact strong { color: #f7edd0; font-size: 1.08rem; }
.item-detail-tooltip-shell { min-height: 320px; }
.item-detail-tooltip-shell .talent-tt, .item-detail-tooltip-shell .tt-item { max-width: none; }
.item-detail-tooltip-loading { padding: 16px 18px; border-radius: 12px; border: 1px solid rgba(255, 196, 0, 0.24); background: rgba(1, 4, 10, 0.8); color: #f8e8af; }
.item-owner-table { width: 100%; border-collapse: collapse; background: rgba(10, 10, 18, 0.72); }
.item-owner-table thead th { padding: 14px 16px; text-align: left; font-size: 0.95rem; color: #ffc21c; border-bottom: 1px solid rgba(255, 204, 72, 0.28); }
.item-owner-table tbody td { padding: 14px 16px; border-bottom: 1px solid rgba(255, 204, 72, 0.14); vertical-align: middle; }
.item-owner-table tbody tr:nth-child(odd) { background: rgba(255, 255, 255, 0.03); }
.item-owner-link, .item-guild-link { text-decoration: none; font-weight: 700; }
.item-owner-link:hover, .item-guild-link:hover { text-decoration: underline; }
.item-owner-icons { display: flex; align-items: center; gap: 8px; }
.item-owner-icons img { width: 26px; height: 26px; border-radius: 50%; border: 1px solid rgba(255, 196, 0, 0.28); background: #050505; }
.item-owner-faction img { width: 20px; height: 20px; display: block; }
.class-warrior { color: #C79C6E; }
.class-paladin { color: #F58CBA; }
.class-hunter { color: #ABD473; }
.class-rogue { color: #FFF569; }
.class-priest { color: #FFFFFF; }
.class-deathknight { color: #C41F3B; }
.class-shaman { color: #0070DE; }
.class-mage { color: #69CCF0; }
.class-warlock { color: #9482C9; }
.class-druid { color: #FF7D0A; }
@media (max-width: 1080px) { .item-detail-hero, .item-detail-grid { grid-template-columns: 1fr; } }
@media (max-width: 760px) { .item-detail-head { flex-direction: column; } .item-detail-title { font-size: 2rem; } .item-detail-meta { grid-template-columns: 1fr 1fr; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var panel = document.getElementById('item-detail-tooltip-panel');
  if (!panel) return;
  var itemId = panel.getAttribute('data-item-id');
  var realmId = panel.getAttribute('data-realm-id');
  fetch('modern-item-tooltip.php?item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (response) { if (!response.ok) throw new Error('tooltip request failed'); return response.text(); })
    .then(function (html) { panel.innerHTML = html && html.trim() !== '' ? html : '<div class="item-detail-tooltip-loading">The tooltip renderer returned no item details.</div>'; })
    .catch(function () { panel.innerHTML = '<div class="item-detail-tooltip-loading">Unable to load the full item details.</div>'; });
});
</script>

<div class="item-detail-page">
  <?php if ($pageError !== ''): ?>
    <div class="item-detail-error"><?php echo htmlspecialchars($pageError); ?></div>
  <?php elseif ($item): ?>
    <section class="item-detail-hero">
      <div>
        <div class="item-detail-head">
          <img class="item-detail-icon" src="<?php echo htmlspecialchars($item['icon']); ?>" alt="">
          <div>
            <p class="item-detail-eyebrow"><?php echo htmlspecialchars($realmLabel); ?> Item Detail</p>
            <h1 class="item-detail-title" style="color: <?php echo htmlspecialchars($item['quality_color']); ?>;"><?php echo htmlspecialchars($item['name']); ?></h1>
            <p class="item-detail-subtitle"><?php echo htmlspecialchars($item['slot_name']); ?> | <?php echo htmlspecialchars($item['class_name']); ?> | <?php echo htmlspecialchars($item['source']); ?></p>
            <?php if ($item['description'] !== ''): ?>
              <p class="item-detail-description">"<?php echo htmlspecialchars($item['description']); ?>"</p>
            <?php endif; ?>
            <div class="item-detail-actions">
              <a class="item-detail-link" href="<?php echo htmlspecialchars($searchBackUrl); ?>">Back to Item Search</a>
              <?php if ($legacyRealmName !== ''): ?>
                <a class="item-detail-link" href="<?php echo htmlspecialchars('/armory/index.php?searchType=iteminfo&item=' . $item['id'] . '&realm=' . rawurlencode($legacyRealmName)); ?>">Legacy Armory View</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="item-detail-meta">
        <div class="item-detail-card"><span class="item-detail-card-label">Item Level</span><div class="item-detail-card-value"><?php echo (int)$item['level']; ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Quality</span><div class="item-detail-card-value" style="color: <?php echo htmlspecialchars($item['quality_color']); ?>;"><?php echo htmlspecialchars($item['quality_label']); ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Required Level</span><div class="item-detail-card-value"><?php echo $item['required_level'] > 0 ? (int)$item['required_level'] : 'None'; ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Owned By</span><div class="item-detail-card-value"><?php echo count($owners); ?> Players</div></div>
      </div>
    </section>

    <section class="item-detail-grid">
      <div class="item-detail-panel">
        <h2 class="item-detail-panel-title">Quick Facts</h2>
        <div class="item-detail-facts">
          <div class="item-detail-fact"><span>Source</span><strong><?php echo htmlspecialchars($item['source']); ?></strong></div>
          <div class="item-detail-fact"><span>Slot</span><strong><?php echo htmlspecialchars($item['slot_name']); ?></strong></div>
          <div class="item-detail-fact"><span>Type</span><strong><?php echo htmlspecialchars($item['class_name']); ?></strong></div>
          <div class="item-detail-fact"><span>Buy Price</span><strong><?php echo htmlspecialchars(spp_modern_item_format_money($item['buy_price'])); ?></strong></div>
          <div class="item-detail-fact"><span>Sell Price</span><strong><?php echo htmlspecialchars(spp_modern_item_format_money($item['sell_price'])); ?></strong></div>
          <?php if ($item['max_durability'] > 0): ?><div class="item-detail-fact"><span>Durability</span><strong><?php echo (int)$item['max_durability']; ?></strong></div><?php endif; ?>
          <?php if ($item['required_skill'] > 0): ?><div class="item-detail-fact"><span>Disenchant Skill</span><strong><?php echo (int)$item['required_skill']; ?></strong></div><?php endif; ?>
        </div>
      </div>

      <div class="item-detail-panel item-detail-tooltip-shell">
        <h2 class="item-detail-panel-title">Item Stats</h2>
        <div id="item-detail-tooltip-panel" data-item-id="<?php echo (int)$item['id']; ?>" data-realm-id="<?php echo (int)$realmId; ?>"><div class="item-detail-tooltip-loading">Loading full item details...</div></div>
      </div>
    </section>

    <section class="item-detail-panel">
      <h2 class="item-detail-panel-title">Owned By Players</h2>
      <?php if ($owners): ?>
        <table class="item-owner-table">
          <thead>
            <tr>
              <th>Character</th>
              <th>Level</th>
              <th>Race / Class</th>
              <th>Faction</th>
              <th>Guild</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($owners as $owner): ?>
              <tr>
                <td class="class-<?php echo htmlspecialchars($owner['class_slug']); ?>"><a class="item-owner-link" href="<?php echo htmlspecialchars('armory/index.php?searchType=profile&character=' . rawurlencode($owner['name']) . ($legacyRealmName !== '' ? '&realm=' . rawurlencode($legacyRealmName) : '')); ?>"><?php echo htmlspecialchars($owner['name']); ?></a></td>
                <td><?php echo (int)$owner['level']; ?></td>
                <td><div class="item-owner-icons"><img src="/templates/offlike/images/icons/race/<?php echo (int)$owner['race']; ?>-<?php echo (int)$owner['gender']; ?>.gif" alt="<?php echo htmlspecialchars($owner['race_name']); ?>" title="<?php echo htmlspecialchars($owner['race_name']); ?>"><img src="/templates/offlike/images/icons/class/<?php echo (int)$owner['class']; ?>.jpg" alt="<?php echo htmlspecialchars($owner['class_name']); ?>" title="<?php echo htmlspecialchars($owner['class_name']); ?>"></div></td>
                <td class="item-owner-faction"><img src="<?php echo htmlspecialchars($owner['faction_icon']); ?>" alt="<?php echo htmlspecialchars($owner['faction']); ?>" title="<?php echo htmlspecialchars($owner['faction']); ?>"></td>
                <td><?php if ($owner['guild_id'] > 0 && $owner['guild_name'] !== ''): ?><a class="item-guild-link" href="<?php echo htmlspecialchars('index.php?n=server&sub=guild&realm=' . (int)$realmId . '&guildid=' . (int)$owner['guild_id']); ?>"><?php echo htmlspecialchars($owner['guild_name']); ?></a><?php else: ?>None<?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="item-detail-tooltip-loading">No characters currently own this item on <?php echo htmlspecialchars($realmLabel); ?>.</div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>




