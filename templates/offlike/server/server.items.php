<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/settings.php');

if (!function_exists('spp_modern_item_search_compare')) {
    function spp_modern_item_search_compare(array $left, array $right, $orderBy, $orderDir) {
        $direction = strtoupper($orderDir) === 'ASC' ? 1 : -1;

        switch ($orderBy) {
            case 'name':
                $cmp = strnatcasecmp((string)$left['name'], (string)$right['name']);
                break;
            case 'level':
                $cmp = ((int)$left['level']) <=> ((int)$right['level']);
                break;
            case 'source':
                $cmp = strnatcasecmp((string)$left['source'], (string)$right['source']);
                break;
            default:
                $cmp = ((int)$left['relevance']) <=> ((int)$right['relevance']);
                break;
        }

        if ($cmp === 0) {
            $cmp = strnatcasecmp((string)$left['name'], (string)$right['name']);
        }

        return $cmp * $direction;
    }
}

if (!function_exists('spp_modern_item_search_sort_url')) {
    function spp_modern_item_search_sort_url($realmId, $search, $perPage, $orderBy, $currentSortBy, $currentSortDir) {
        $nextSortDir = ($currentSortBy === $orderBy && strtoupper($currentSortDir) === 'ASC') ? 'DESC' : 'ASC';
        return 'index.php?n=server&sub=items&realm=' . (int)$realmId
            . '&p=1&per_page=' . (int)$perPage
            . '&sort=' . rawurlencode($orderBy)
            . '&dir=' . rawurlencode($nextSortDir)
            . ($search !== '' ? '&search=' . urlencode($search) : '');
    }
}

if (!function_exists('spp_modern_item_search_normalize')) {
    function spp_modern_item_search_normalize($search) {
        $search = trim((string)$search);
        $search = preg_replace('/\s\s+/', ' ', $search);
        if (preg_match('/[^[:alnum:]\\s]/u', $search)) {
            return '';
        }
        return $search;
    }
}

if (!function_exists('spp_modern_item_cache_source')) {
    function spp_modern_item_cache_source(PDO $worldPdo, PDO $armoryPdo, $itemId, $isPvpReward) {
        $itemId = (int)$itemId;

        $checks = [
            ['SELECT `entry` FROM `quest_template` WHERE `SrcItemId` = ? LIMIT 1', 'Quest Item', 'world'],
            ['SELECT `entry` FROM `npc_vendor` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward (Vendor)' : 'Vendor', 'world'],
            ['SELECT `entry` FROM `npc_vendor_template` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward (Vendor)' : 'Vendor', 'world'],
        ];

        foreach ($checks as $check) {
            $pdo = $check[2] === 'armory' ? $armoryPdo : $worldPdo;
            $stmt = $pdo->prepare($check[0]);
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

        $refStmt = $worldPdo->prepare('SELECT `entry`, `groupid` FROM `reference_loot_template` WHERE `item` = ?');
        $refStmt->execute([$itemId]);
        $refLoot = $refStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($refLoot) {
            if (count($refLoot) > 1) {
                return 'World Drop';
            }

            $bossStmt = $worldPdo->prepare('SELECT `entry` FROM `creature_loot_template` WHERE `mincountOrRef` = ?');
            $bossStmt->execute([-((int)$refLoot[0]['entry'])]);
            $bossIds = $bossStmt->fetchAll(PDO::FETCH_COLUMN);
            if ($bossIds && (count($bossIds) === 1 || count($bossIds) === 2)) {
                $bossId = (int)$bossIds[0];
                $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'npc\' LIMIT 1');
                $instanceStmt->execute([$bossId, $bossId, $bossId, $bossId, $bossId, $bossId]);
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
            }

            return 'World Drop';
        }

        $questRewardStmt = $worldPdo->prepare('SELECT `entry` FROM `quest_template` WHERE `RewItemId1` = ? OR `RewItemId2` = ? OR `RewItemId3` = ? OR `RewItemId4` = ? LIMIT 1');
        $questRewardStmt->execute([$itemId, $itemId, $itemId, $itemId]);
        if ($questRewardStmt->fetchColumn()) {
            return 'Quest Reward';
        }

        return 'Created';
    }
}

if (!function_exists('spp_modern_item_backfill_search_cache')) {
    function spp_modern_item_backfill_search_cache(PDO $worldPdo, PDO $armoryPdo, $realmDbKey, array $worldRow, $localeField = null) {
        $itemId = (int)$worldRow['entry'];
        $itemName = (string)$worldRow['name'];
        if ($localeField && !empty($worldRow[$localeField])) {
            $itemName = (string)$worldRow[$localeField];
        }

        $quality = (int)$worldRow['Quality'];
        $level = (int)$worldRow['ItemLevel'];
        $flags = (int)$worldRow['Flags'];
        $source = spp_modern_item_cache_source($worldPdo, $armoryPdo, $itemId, (($flags & 32768) === 32768));
        $relevance = ($quality * 25) + $level;

        $insert = $armoryPdo->prepare(
            'INSERT INTO `cache_item_search` (`item_id`, `mangosdbkey`, `item_name`, `item_level`, `item_source`, `item_relevance`) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([$itemId, (int)$realmDbKey, $itemName, $level, $source, $relevance]);

        return [
            'id' => $itemId,
            'name' => $itemName,
            'level' => $level,
            'source' => $source,
            'relevance' => $relevance,
            'quality' => $quality,
        ];
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$realmLabel = $realmMap[$realmId]['label'] ?? 'Realm';
$realmDbKey = $realmId;

$search = trim($_GET['search'] ?? '');
$orderBy = strtolower(trim($_GET['sort'] ?? 'relevance'));
$orderDir = strtoupper(trim($_GET['dir'] ?? 'DESC'));
$allowedSorts = ['name', 'level', 'source', 'relevance'];
if (!in_array($orderBy, $allowedSorts, true)) {
    $orderBy = 'relevance';
}
if ($orderDir !== 'ASC' && $orderDir !== 'DESC') {
    $orderDir = 'DESC';
}

$defaultPerPage = isset($config['results_per_page_items']) ? max(1, (int)$config['results_per_page_items']) : 25;
$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$itemsPerPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : $defaultPerPage;
$minSearchLength = isset($config['min_items_search']) ? (int)$config['min_items_search'] : 2;
$searchError = '';
$validatedSearch = '';
$results = [];
$visibleDetails = [];
$legacyRealmName = '';

$bootstrapOk = is_array($realmMap) && isset($realmMap[$realmId]);
$bootstrapError = $bootstrapOk ? '' : 'The requested realm could not be loaded.';

try {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/core/dbsimple/Generic.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/mysql.php');
    $legacyRealmName = '';
    if (!empty($realms) && is_array($realms)) {
        foreach ($realms as $name => $keys) {
            if ((int)($keys[2] ?? 0) === (int)$realmDbKey) {
                $legacyRealmName = (string)$name;
                break;
            }
        }
    }
} catch (Throwable $e) {
    $legacyRealmName = '';
}

if ($bootstrapOk && $search !== '') {
    $validatedSearch = spp_modern_item_search_normalize($search);
    if ($validatedSearch === '' || strlen($validatedSearch) < $minSearchLength) {
        $searchError = 'Search must be at least ' . $minSearchLength . ' characters and use letters, numbers, or spaces only.';
    } else {
        try {
            $armoryPdo = spp_get_pdo('armory', $realmId);
            $worldPdo = spp_get_pdo('world', $realmId);
            $searchLike = '%' . str_replace(' ', '%', $validatedSearch) . '%';
            $localeId = isset($config['locales']) ? (int)$config['locales'] : 0;
            $localeField = $localeId > 0 ? 'name_loc' . $localeId : null;

            $cacheStmt = $armoryPdo->prepare(
                'SELECT `item_id`, `item_name`, `item_level`, `item_source`, `item_relevance` FROM `cache_item_search` WHERE `item_name` LIKE :term AND `mangosdbkey` = :realmdbkey'
            );
            $cacheStmt->execute([
                'term' => $searchLike,
                'realmdbkey' => (int)$realmDbKey,
            ]);

            $resultMap = [];
            foreach ($cacheStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $itemId = (int)$row['item_id'];
                $resultMap[$itemId] = [
                    'id' => $itemId,
                    'name' => (string)$row['item_name'],
                    'level' => (int)$row['item_level'],
                    'source' => (string)$row['item_source'],
                    'relevance' => (int)$row['item_relevance'],
                    'quality' => null,
                ];
            }

            if ($localeField) {
                $worldStmt = $worldPdo->prepare(
                    'SELECT `item_template`.`entry`, `item_template`.`name`, `item_template`.`ItemLevel`, `item_template`.`Quality`, `item_template`.`Flags`, `locales_item`.`' . $localeField . '` FROM `item_template` LEFT JOIN `locales_item` ON `item_template`.`entry` = `locales_item`.`entry` WHERE `locales_item`.`' . $localeField . '` LIKE :term'
                );
            } else {
                $worldStmt = $worldPdo->prepare(
                    'SELECT `entry`, `name`, `ItemLevel`, `Quality`, `Flags` FROM `item_template` WHERE `name` LIKE :term'
                );
            }
            $worldStmt->execute(['term' => $searchLike]);

            foreach ($worldStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $itemId = (int)$row['entry'];
                if (isset($resultMap[$itemId])) {
                    if ($resultMap[$itemId]['quality'] === null && isset($row['Quality'])) {
                        $resultMap[$itemId]['quality'] = (int)$row['Quality'];
                    }
                    continue;
                }

                $resultMap[$itemId] = spp_modern_item_backfill_search_cache(
                    $worldPdo,
                    $armoryPdo,
                    $realmDbKey,
                    $row,
                    $localeField
                );
            }

            $results = array_values($resultMap);
        } catch (Throwable $e) {
            $searchError = 'Item search failed to query the realm databases.';
        }
    }
}

if ($results) {
    usort($results, function ($left, $right) use ($orderBy, $orderDir) {
        return spp_modern_item_search_compare($left, $right, $orderBy, $orderDir);
    });
}

$totalResults = count($results);
$pageCount = max(1, (int)ceil($totalResults / $itemsPerPage));
if ($p > $pageCount) {
    $p = $pageCount;
}
$offset = ($p - 1) * $itemsPerPage;
$pageResults = array_slice($results, $offset, $itemsPerPage);
$resultStart = $totalResults > 0 ? $offset + 1 : 0;
$resultEnd = min($offset + $itemsPerPage, $totalResults);

if ($bootstrapOk && $pageResults) {
    try {
        $armoryPdo = spp_get_pdo('armory', $realmId);
        $itemIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $pageResults);
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $detailStmt = $armoryPdo->prepare(
            'SELECT `item_id`, `item_quality`, `item_icon` FROM `cache_item` WHERE `item_id` IN (' . $placeholders . ') AND `mangosdbkey` = ?'
        );
        $detailStmt->execute(array_merge($itemIds, [(int)$realmDbKey]));
        foreach ($detailStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $visibleDetails[(int)$row['item_id']] = $row;
        }

        if (count($visibleDetails) < count($pageResults)) {
            $worldPdo = spp_get_pdo('world', $realmId);
            $missingIds = [];
            foreach ($pageResults as $row) {
                if (!isset($visibleDetails[(int)$row['id']])) {
                    $missingIds[] = (int)$row['id'];
                }
            }

            if ($missingIds) {
                $worldPlaceholders = implode(',', array_fill(0, count($missingIds), '?'));
                $worldStmt = $worldPdo->prepare('SELECT `entry`, `name`, `Quality`, `displayid` FROM `item_template` WHERE `entry` IN (' . $worldPlaceholders . ')');
                $worldStmt->execute($missingIds);
                $worldRows = [];
                foreach ($worldStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $worldRows[(int)$row['entry']] = $row;
                }

                $insertStmt = $armoryPdo->prepare('INSERT INTO `cache_item` (`item_id`, `mangosdbkey`, `item_name`, `item_quality`, `item_icon`) VALUES (?, ?, ?, ?, ?)');
                foreach ($missingIds as $itemId) {
                    if (!isset($worldRows[$itemId])) {
                        continue;
                    }
                    $worldRow = $worldRows[$itemId];
                    $icon = 'armory/images/icons/64x64/404.png';
                    if (!empty($worldRow['displayid'])) {
                        $iconStmt = $worldPdo->prepare('SELECT `icon1` FROM `itemdisplayinfo` WHERE `displayid` = ? LIMIT 1');
                        $iconStmt->execute([(int)$worldRow['displayid']]);
                        $iconName = $iconStmt->fetchColumn();
                        if ($iconName) {
                            $icon = 'armory/images/icons/64x64/' . strtolower((string)$iconName) . '.png';
                        }
                    }
                    $insertStmt->execute([$itemId, (int)$realmDbKey, (string)$worldRow['name'], (int)$worldRow['Quality'], $icon]);
                    $visibleDetails[$itemId] = [
                        'item_id' => $itemId,
                        'item_quality' => (int)$worldRow['Quality'],
                        'item_icon' => $icon,
                    ];
                }
            }
        }
    } catch (Throwable $e) {
        $visibleDetails = [];
    }
}

builddiv_start(1, 'Item Search', 1);
?>
<style>
.item-search-shell {
  display: grid;
  gap: 18px;
}
.item-search-intro {
  display: grid;
  gap: 14px;
  padding: 22px 24px;
  border: 1px solid rgba(255, 196, 0, 0.18);
  border-radius: 18px;
  background:
    radial-gradient(circle at top right, rgba(235, 163, 35, 0.14), transparent 34%),
    linear-gradient(180deg, rgba(9, 12, 23, 0.96), rgba(4, 6, 14, 0.98));
}
.item-search-title {
  margin: 0;
  font-size: 2.3rem;
  color: #fff3c2;
}
.item-search-copy {
  margin: 0;
  max-width: 760px;
  color: #d5c7a0;
  line-height: 1.55;
}
.item-search-form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  gap: 12px;
  align-items: center;
}
.item-search-form input[type="text"] {
  width: 100%;
  min-height: 50px;
  padding: 0 16px;
  color: #f9f1d8;
  background: rgba(4, 6, 16, 0.9);
  border: 1px solid rgba(255, 196, 0, 0.72);
  border-radius: 12px;
}
.item-search-form select {
  min-height: 50px;
  padding: 0 14px;
  color: #f9f1d8;
  background: rgba(4, 6, 16, 0.9);
  border: 1px solid rgba(255, 196, 0, 0.45);
  border-radius: 12px;
}
.item-search-button {
  min-height: 50px;
  padding: 0 20px;
  border: 0;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffcf68, #d38a1f);
  color: #231503;
  font-weight: 800;
  cursor: pointer;
}
.item-search-meta,
.item-search-error,
.item-search-empty {
  padding: 16px 18px;
  border-radius: 14px;
}
.item-search-meta {
  color: #ffd56c;
  background: rgba(255, 204, 72, 0.08);
  border: 1px solid rgba(255, 204, 72, 0.16);
}
.item-search-error {
  color: #ffd0c2;
  background: rgba(110, 18, 18, 0.28);
  border: 1px solid rgba(238, 120, 97, 0.28);
}
.item-search-empty {
  color: #d5c7a0;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 196, 0, 0.08);
}
.item-search-table .header,
.item-search-table .row {
  grid-template-columns: 72px minmax(260px, 2.2fr) 110px minmax(220px, 1.7fr) 120px;
}
.item-search-table .col.icon,
.item-search-table .col.level,
.item-search-table .col.relevance {
  justify-content: center;
}
.item-search-table .col.name {
  display: flex;
  align-items: center;
  padding: 0 12px;
}
.item-search-table .col.source {
  padding: 0 12px;
  text-align: left;
}
.item-search-table .row:nth-child(even) {
  background: rgba(255,255,255,0.03);
}
.item-search-table .row:hover {
  background: rgba(255, 215, 120, 0.05);
}
.item-search-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  border: 1px solid rgba(255, 196, 0, 0.28);
  background: rgba(0,0,0,0.5);
}
.item-link {
  text-decoration: none;
  font-weight: 700;
}
.quality-0 { color: #9d9d9d; }
.quality-1 { color: #ffffff; }
.quality-2 { color: #1eff00; }
.quality-3 { color: #0070dd; }
.quality-4 { color: #a335ee; }
.quality-5 { color: #ff8000; }
.quality-6, .quality-7 { color: #e6cc80; }
.item-search-sorting {
  color: #c7b17e;
  font-size: 0.92rem;
}
.item-search-sorting a {
  color: inherit;
  margin-right: 14px;
  text-decoration: none;
}
.item-search-sorting a.active {
  color: #ffd56c;
}
.item-search-helper {
  margin: 0;
  color: #a99a76;
  font-size: 0.92rem;
}
@media (max-width: 980px) {
  .item-search-table .header,
  .item-search-table .row {
    grid-template-columns: 72px minmax(220px, 2fr) 100px minmax(180px, 1.4fr);
  }
  .item-search-table .header .col:nth-child(5),
  .item-search-table .row .col:nth-child(5) {
    display: none;
  }
}
@media (max-width: 760px) {
  .item-search-form {
    grid-template-columns: 1fr;
  }
  .item-search-title {
    font-size: 1.9rem;
  }
  .item-search-table .header,
  .item-search-table .row {
    grid-template-columns: 64px minmax(180px, 1.8fr) 96px;
  }
  .item-search-table .header .col:nth-child(4),
  .item-search-table .row .col:nth-child(4) {
    display: none;
  }
}
</style>

<div class="item-search-shell">
  <section class="item-search-intro">
    <div>
      <h1 class="item-search-title">Modern Armory Item Search</h1>
      <p class="item-search-copy">This page now follows the original armory search flow more closely by reading the per-realm armory cache first, then filling missing matches from the realm world database.</p>
    </div>

    <form method="get" class="item-search-form">
      <input type="hidden" name="n" value="server">
      <input type="hidden" name="sub" value="items">
      <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
      <input type="hidden" name="p" value="1">
      <input type="hidden" name="sort" value="<?php echo htmlspecialchars($orderBy); ?>">
      <input type="hidden" name="dir" value="<?php echo htmlspecialchars($orderDir); ?>">

      <input
        type="text"
        name="search"
        value="<?php echo htmlspecialchars($search); ?>"
        placeholder="Search for an item name..."
        autocomplete="off"
      >

      <select name="per_page" onchange="this.form.submit()">
        <?php foreach ([10, 25, 50, 100] as $opt): ?>
          <option value="<?php echo $opt; ?>"<?php echo $itemsPerPage === $opt ? ' selected' : ''; ?>><?php echo $opt; ?> per page</option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="item-search-button">Search</button>
    </form>

    <p class="item-search-helper">Realm: <?php echo htmlspecialchars($realmLabel); ?>. Minimum search length: <?php echo $minSearchLength; ?>.</p>
  </section>

  <?php if (!$bootstrapOk): ?>
    <div class="item-search-error"><?php echo htmlspecialchars($bootstrapError); ?></div>
  <?php elseif ($searchError !== ''): ?>
    <div class="item-search-error"><?php echo htmlspecialchars($searchError); ?></div>
  <?php elseif ($search !== ''): ?>
    <div class="item-search-meta">
      Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo (int)$totalResults; ?> results
      for <strong><?php echo htmlspecialchars($validatedSearch !== '' ? $validatedSearch : $search); ?></strong>
      in <?php echo htmlspecialchars($realmLabel); ?>.
    </div>
  <?php endif; ?>

  <?php if ($search !== '' && !$searchError && $pageResults): ?>
    <div class="item-search-sorting">
      Sort:
      <?php foreach (['name' => 'Name', 'level' => 'Item Level', 'source' => 'Source', 'relevance' => 'Relevance'] as $sortKey => $sortLabel): ?>
        <a
          href="<?php echo htmlspecialchars(spp_modern_item_search_sort_url($realmId, $search, $itemsPerPage, $sortKey, $orderBy, $orderDir)); ?>"
          class="<?php echo $orderBy === $sortKey ? 'active' : ''; ?>"
        >
          <?php echo $sortLabel; ?><?php echo $orderBy === $sortKey ? ($orderDir === 'ASC' ? ' ?' : ' ?') : ''; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="wow-table item-search-table">
      <div class="header">
        <div class="col icon">Icon</div>
        <div class="col name">Name</div>
        <div class="col level">Item Level</div>
        <div class="col source">Source</div>
        <div class="col relevance">Relevance</div>
      </div>

      <?php foreach ($pageResults as $row): ?>
        <?php
          $detail = $visibleDetails[(int)$row['id']] ?? null;
          $quality = (int)($detail['item_quality'] ?? ($row['quality'] ?? 0));
          $icon = $detail['item_icon'] ?? 'armory/images/icons/64x64/404.png';
          $legacyItemUrl = 'armory/index.php?searchType=iteminfo&item=' . (int)$row['id'];
          if ($legacyRealmName !== '') {
              $legacyItemUrl .= '&realm=' . rawurlencode($legacyRealmName);
          }
        ?>
        <div class="row">
          <div class="col icon">
            <img class="item-search-icon" src="<?php echo htmlspecialchars($icon); ?>" alt="">
          </div>
          <div class="col name">
            <a class="item-link quality-<?php echo $quality; ?>" href="<?php echo htmlspecialchars($legacyItemUrl); ?>">
              <?php echo htmlspecialchars($row['name']); ?>
            </a>
          </div>
          <div class="col level"><?php echo (int)$row['level']; ?></div>
          <div class="col source"><?php echo htmlspecialchars(trim(strip_tags((string)$row['source']))); ?></div>
          <div class="col relevance"><?php echo (int)$row['relevance']; ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pageCount > 1): ?>
      <div class="pagination-controls">
        <div class="page-links">
          <?php
            $baseUrl = 'index.php?n=server&sub=items&realm=' . (int)$realmId
              . '&per_page=' . (int)$itemsPerPage
              . '&sort=' . rawurlencode($orderBy)
              . '&dir=' . rawurlencode($orderDir)
              . ($search !== '' ? '&search=' . urlencode($search) : '');
            echo compact_paginate($p, $pageCount, $baseUrl);
          ?>
        </div>
      </div>
    <?php endif; ?>
  <?php elseif ($search !== '' && !$searchError): ?>
    <div class="item-search-empty">No items matched that search.</div>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>


