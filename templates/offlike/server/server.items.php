<?php
$siteDatabaseHandle = $GLOBALS['DB'] ?? null;
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/settings.php');
if ($siteDatabaseHandle !== null) {
    $GLOBALS['DB'] = $siteDatabaseHandle;
    $DB = $siteDatabaseHandle;
}
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
        if (preg_match('/[^[:alnum:]\s]/u', $search)) {
            return '';
        }
        return $search;
    }
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
            $objectNameStmt = $worldPdo->prepare('SELECT `name` FROM `gameobject_template` WHERE `entry` = ? LIMIT 1');
            $objectNameStmt->execute([$objectLootId]);
            $objectName = $objectNameStmt->fetchColumn();
            if ($objectName) {
                return 'Found in ' . (string)$objectName;
            }
            return 'Container Drop';
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
            $creatureNameStmt = $worldPdo->prepare('SELECT `Name` FROM `creature_template` WHERE `entry` = ? LIMIT 1');
            $creatureNameStmt->execute([$creatureLootId]);
            $creatureName = $creatureNameStmt->fetchColumn();
            if ($creatureName) {
                return 'Dropped by ' . (string)$creatureName;
            }
            return 'Dropped Item';
        }

        $referenceStmt = $worldPdo->prepare('SELECT `entry`, `groupid` FROM `reference_loot_template` WHERE `item` = ?');
        $referenceStmt->execute([$itemId]);
        $referenceRows = $referenceStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($referenceRows)) {
            $referenceEntry = (int)($referenceRows[0]['entry'] ?? 0);
            $referenceGroupId = (int)($referenceRows[0]['groupid'] ?? 0);
            if (count($referenceRows) > 1) {
                return 'World Drop';
            }

            if ($referenceEntry > 0) {
                $bossStmt = $worldPdo->prepare('SELECT `entry` FROM `creature_loot_template` WHERE `mincountOrRef` = ?');
                $bossStmt->execute([-1 * $referenceEntry]);
                $bossRows = $bossStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($bossRows) && count($bossRows) <= 2) {
                    $creature = (int)($bossRows[0]['entry'] ?? 0);
                    if ($creature > 0) {
                        $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'npc\' LIMIT 1');
                        $instanceStmt->execute([$creature, $creature, $creature, $creature, $creature, $creature]);
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

                        $creatureNameStmt = $worldPdo->prepare('SELECT `Name` FROM `creature_template` WHERE `entry` = ? LIMIT 1');
                        $creatureNameStmt->execute([$creature]);
                        $creatureName = $creatureNameStmt->fetchColumn();
                        if ($creatureName) {
                            return (string)$creatureName;
                        }
                    }
                }
            }

            return 'Dropped Item';
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
$legacyRealmName = '';

if (!empty($realms) && is_array($realms)) {
    foreach ($realms as $name => $keys) {
        if ((int)($keys[2] ?? 0) === (int)$realmId) {
            $legacyRealmName = (string)$name;
            break;
        }
    }
}


$bootstrapOk = is_array($realmMap) && isset($realmMap[$realmId]);
$bootstrapError = $bootstrapOk ? '' : 'The requested realm could not be loaded.';

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

            if ($localeField) {
                $stmt = $worldPdo->prepare(
                    'SELECT `item_template`.`entry`, `item_template`.`name`, `item_template`.`ItemLevel`, `item_template`.`Quality`, `item_template`.`Flags`, `item_template`.`displayid`, `item_template`.`description`, `locales_item`.`' . $localeField . '` AS `localized_name`, `locales_item`.`description_loc' . $localeId . '` AS `localized_description` FROM `item_template` LEFT JOIN `locales_item` ON `item_template`.`entry` = `locales_item`.`entry` WHERE `locales_item`.`' . $localeField . '` LIKE :term OR `item_template`.`name` LIKE :term'
                );
            } else {
                $stmt = $worldPdo->prepare(
                    'SELECT `entry`, `name`, `ItemLevel`, `Quality`, `Flags`, `displayid`, `description` FROM `item_template` WHERE `name` LIKE :term'
                );
            }
            $stmt->execute(['term' => $searchLike]);
            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $iconMap = [];
            $displayIds = [];
            foreach ($rawRows as $row) {
                if (!empty($row['displayid'])) {
                    $displayIds[(int)$row['displayid']] = (int)$row['displayid'];
                }
            }
            if ($displayIds) {
                $displayPlaceholders = implode(',', array_fill(0, count($displayIds), '?'));
                $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $displayPlaceholders . ')');
                $iconStmt->execute(array_values($displayIds));
                foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                    $iconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
            }

            foreach ($rawRows as $row) {
                $itemId = (int)$row['entry'];
                $displayId = (int)($row['displayid'] ?? 0);
                $quality = (int)$row['Quality'];
                $level = (int)$row['ItemLevel'];
                $flags = (int)$row['Flags'];
                $name = $localeField && !empty($row['localized_name']) ? (string)$row['localized_name'] : (string)$row['name'];
                $description = $localeField && !empty($row['localized_description']) ? (string)$row['localized_description'] : (string)($row['description'] ?? '');

                $results[] = [
                    'id' => $itemId,
                    'name' => $name,
                    'level' => $level,
                    'source' => spp_modern_item_cache_source($worldPdo, $armoryPdo, $itemId, (($flags & 32768) === 32768)),
                    'relevance' => ($quality * 25) + $level,
                    'quality' => $quality,
                    'icon' => spp_modern_item_icon_url($iconMap[$displayId] ?? ''),
                    'description' => $description,
                ];
            }
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

builddiv_start(1, 'Item Search', 1);
?>
<link rel="stylesheet" type="text/css" href="/armory/css/armory-tooltips.css" />
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
.item-search-form input[type="text"],
.item-search-form select {
  min-height: 50px;
  padding: 0 16px;
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
  grid-template-columns: 84px minmax(260px, 2.2fr) 120px minmax(220px, 1.7fr) 120px;
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
.item-search-header-link {
  color: inherit;
  text-decoration: none;
  font-weight: 700;
}
.item-search-header-link.active {
  color: #ffd56c;
}
.item-search-header-link:hover {
  color: #ffe39a;
}
.item-search-helper {
  margin: 0;
  color: #a99a76;
  font-size: 0.92rem;
}
.modern-item-tooltip {
  min-width: 220px;
}
.modern-item-tooltip-loading {
  padding: 14px 16px;
  color: #f5e6b2;
  border: 1px solid rgba(255, 196, 0, 0.35);
  border-radius: 10px;
  background: rgba(5, 8, 18, 0.96);
  box-shadow: 0 16px 38px rgba(0, 0, 0, 0.45);
}
#modern-item-tooltip {
  position: fixed;
  z-index: 9999;
  pointer-events: none;
  display: none;
}
@media (max-width: 980px) {
  .item-search-table .header,
  .item-search-table .row {
    grid-template-columns: 84px minmax(220px, 2fr) 100px minmax(180px, 1.4fr);
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
    grid-template-columns: 72px minmax(180px, 1.8fr) 96px;
  }
  .item-search-table .header .col:nth-child(4),
  .item-search-table .row .col:nth-child(4) {
    display: none;
  }
}
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
  if (tip.style.display === 'none') {
    return;
  }
  const offset = 18;
  let left = event.clientX + offset;
  let top = event.clientY + offset;
  const rect = tip.getBoundingClientRect();
  if (left + rect.width > window.innerWidth - 12) {
    left = event.clientX - rect.width - offset;
  }
  if (top + rect.height > window.innerHeight - 12) {
    top = event.clientY - rect.height - offset;
  }
  tip.style.left = left + 'px';
  tip.style.top = top + 'px';
}

function modernHideTooltip() {
  if (modernTooltipNode) {
    modernTooltipNode.style.display = 'none';
  }
}
</script>

<div class="item-search-shell">
  <section class="item-search-intro">
    <div>
     
      <p class="item-search-copy">This version reads the visible item data directly from the live realm databases and loads the full item tooltip on demand when you hover an item.</p>
    </div>

    <form method="get" class="item-search-form">
      <input type="hidden" name="n" value="server">
      <input type="hidden" name="sub" value="items">
      <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
      <input type="hidden" name="p" value="1">
      <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search for an item name..." autocomplete="off">
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
    <div class="wow-table item-search-table">
      <div class="header">
        <div class="col icon">Icon</div>
        <div class="col name"><a class="item-search-header-link <?php echo $orderBy === 'name' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(spp_modern_item_search_sort_url($realmId, $search, $itemsPerPage, 'name', $orderBy, $orderDir)); ?>">Name<?php echo $orderBy === 'name' ? ($orderDir === 'ASC' ? ' ?' : ' ?') : ''; ?></a></div>
        <div class="col level"><a class="item-search-header-link <?php echo $orderBy === 'level' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(spp_modern_item_search_sort_url($realmId, $search, $itemsPerPage, 'level', $orderBy, $orderDir)); ?>">Item Level<?php echo $orderBy === 'level' ? ($orderDir === 'ASC' ? ' ?' : ' ?') : ''; ?></a></div>
        <div class="col source"><a class="item-search-header-link <?php echo $orderBy === 'source' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(spp_modern_item_search_sort_url($realmId, $search, $itemsPerPage, 'source', $orderBy, $orderDir)); ?>">Source<?php echo $orderBy === 'source' ? ($orderDir === 'ASC' ? ' ?' : ' ?') : ''; ?></a></div>
        <div class="col relevance"><a class="item-search-header-link <?php echo $orderBy === 'relevance' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(spp_modern_item_search_sort_url($realmId, $search, $itemsPerPage, 'relevance', $orderBy, $orderDir)); ?>">Relevance<?php echo $orderBy === 'relevance' ? ($orderDir === 'ASC' ? ' ?' : ' ?') : ''; ?></a></div>
      </div>

      <?php foreach ($pageResults as $row): ?>
        <?php
          $legacyItemUrl = 'index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$row['id'];
          if ($search !== '') {
              $legacyItemUrl .= '&search=' . urlencode($search);
          }
          $legacyItemUrl .= '&p=' . (int)$p . '&per_page=' . (int)$itemsPerPage;
          $legacyItemUrl .= '&sort=' . rawurlencode($orderBy) . '&dir=' . rawurlencode($orderDir);
          $qualityColor = spp_modern_item_quality_color($row['quality']);
          $itemId = (int)$row['id'];
        ?>
        <div class="row">
          <div class="col icon">
            <a href="<?php echo htmlspecialchars($legacyItemUrl); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo $itemId; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()">
              <img class="item-search-icon" src="<?php echo htmlspecialchars($row['icon']); ?>" alt="">
            </a>
          </div>
          <div class="col name">
            <a class="item-link" href="<?php echo htmlspecialchars($legacyItemUrl); ?>" style="color: <?php echo htmlspecialchars($qualityColor); ?>;" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo $itemId; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()">
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













