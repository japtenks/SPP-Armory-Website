<?php
$siteDb = $DB;
$siteWorldDb = $WSDB ?? null;
$siteCharDb = $CHDB ?? null;
$siteArmoryDb = $ARDB ?? null;
$siteConfig = $config ?? null;
$siteRealms = $realms ?? null;
$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');
$DB = $siteDb;
$WSDB = $siteWorldDb;
$CHDB = $siteCharDb;
if ($siteArmoryDb !== null) {
    $ARDB = $siteArmoryDb;
}
if ($siteConfig !== null) {
    $config = $siteConfig;
}
if ($siteRealms !== null) {
    $realms = $siteRealms;
}

if (!function_exists('spp_modern_item_search_with_legacy_env')) {
    function spp_modern_item_search_with_legacy_env(callable $callback) {
        global $DB, $WSDB, $CHDB, $ARDB, $config, $realms;

        $env = $GLOBALS['spp_modern_item_search_legacy_env'] ?? null;
        if (!is_array($env) || empty($env)) {
            return null;
        }

        $original = [
            'DB' => $DB,
            'WSDB' => $WSDB ?? null,
            'CHDB' => $CHDB ?? null,
            'ARDB' => $ARDB ?? null,
            'config' => $config ?? null,
            'realms' => $realms ?? null,
        ];

        $DB = $env['DB'];
        $WSDB = $env['WSDB'];
        $CHDB = $env['CHDB'];
        $ARDB = $env['ARDB'];
        $config = $env['config'];
        $realms = $env['realms'];

        try {
            return $callback();
        } finally {
            $DB = $original['DB'];
            $WSDB = $original['WSDB'];
            $CHDB = $original['CHDB'];
            $ARDB = $original['ARDB'];
            $config = $original['config'];
            $realms = $original['realms'];
        }
    }
}

if (!function_exists('spp_modern_item_search_compare')) {
    function spp_modern_item_search_compare(array $left, array $right, $orderBy, $orderDir) {
        $direction = strtoupper($orderDir) === 'ASC' ? 1 : -1;

        switch ($orderBy) {
            case 'name':
                $cmp = strnatcasecmp($left['name'], $right['name']);
                break;
            case 'level':
                $cmp = ((int)$left['level']) <=> ((int)$right['level']);
                break;
            case 'source':
                $cmp = strnatcasecmp($left['source'], $right['source']);
                break;
            default:
                $cmp = ((int)$left['relevance']) <=> ((int)$right['relevance']);
                break;
        }

        if ($cmp === 0) {
            $cmp = strnatcasecmp($left['name'], $right['name']);
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

$realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
$bootstrapOk = false;
$bootstrapError = '';

if (is_array($realmMap) && isset($realmMap[$realmId])) {
    $bootstrapState = [
        'DB' => $DB,
        'WSDB' => $WSDB ?? null,
        'CHDB' => $CHDB ?? null,
        'ARDB' => $ARDB ?? null,
        'config' => $config ?? null,
        'realms' => $realms ?? null,
    ];

    require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/settings.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/mysql.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/armory/configuration/functions.php');

    $legacyRealmName = null;
    foreach ($realms as $realmName => $realmInfo) {
        if ((int)$realmInfo[0] === $realmId) {
            $legacyRealmName = $realmName;
            break;
        }
    }

    if ($legacyRealmName === null && isset($realmMap[$realmId]['label'])) {
        $normalizedLabel = strtolower(preg_replace('/[^a-z0-9]+/', '', $realmMap[$realmId]['label']));
        foreach ($realms as $realmName => $realmInfo) {
            $normalizedRealmName = strtolower(preg_replace('/[^a-z0-9]+/', '', $realmName));
            if ($normalizedRealmName === $normalizedLabel || $normalizedRealmName === 'spp' . $normalizedLabel) {
                $legacyRealmName = $realmName;
                break;
            }
        }
    }

    if ($legacyRealmName !== null) {
        initialize_realm($legacyRealmName);
        $GLOBALS['spp_modern_item_search_legacy_env'] = [
            'DB' => $DB,
            'WSDB' => $WSDB,
            'CHDB' => $CHDB,
            'ARDB' => $ARDB,
            'config' => $config,
            'realms' => $realms,
            'legacyRealmName' => $legacyRealmName,
        ];
        $bootstrapOk = true;
    } else {
        $bootstrapError = 'The legacy armory realm mapping could not be resolved for this realm.';
    }

    $DB = $bootstrapState['DB'];
    $WSDB = $bootstrapState['WSDB'];
    $CHDB = $bootstrapState['CHDB'];
    $ARDB = $bootstrapState['ARDB'];
    $config = $bootstrapState['config'];
    $realms = $bootstrapState['realms'];
} else {
    $bootstrapError = 'The requested realm could not be loaded.';
}

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

$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$itemsPerPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$searchError = '';
$validatedSearch = '';
$results = [];
$visibleDetails = [];
$legacyRealmName = $GLOBALS['spp_modern_item_search_legacy_env']['legacyRealmName'] ?? '';
$realmLabel = $realmMap[$realmId]['label'] ?? 'Realm';

if ($bootstrapOk && $search !== '') {
    $searchPayload = spp_modern_item_search_with_legacy_env(function () use ($search) {
        global $config, $realms;

        $validated = validate_string($search);
        $minSearchLength = (int)($config['min_items_search'] ?? 2);

        if ($validated === '' || strlen($validated) < $minSearchLength) {
            return [
                'error' => 'Search must be at least ' . $minSearchLength . ' characters and use letters, numbers, or spaces only.',
                'validated' => $validated,
                'results' => [],
            ];
        }

        $searchLike = change_whitespace($validated);
        $realmKey = (int)$realms[REALM_NAME][2];
        $items = [];
        $cacheMap = [];

        $cachedRows = execute_query(
            'armory',
            "SELECT * FROM `cache_item_search` WHERE `item_name` LIKE '%" . $searchLike . "%' AND `mangosdbkey` = " . $realmKey
        );

        if (is_array($cachedRows)) {
            foreach ($cachedRows as $row) {
                $itemId = (int)$row['item_id'];
                $cacheMap[$itemId] = $row;
                $items[$itemId] = [
                    'id' => $itemId,
                    'name' => $row['item_name'],
                    'level' => (int)$row['item_level'],
                    'source' => $row['item_source'],
                    'relevance' => (int)$row['item_relevance'],
                ];
            }
        }

        if (!empty($config['locales'])) {
            $localeColumn = 'name_loc' . (int)$config['locales'];
            $worldRows = execute_query(
                'world',
                "SELECT `entry` FROM `locales_item` WHERE `" . $localeColumn . "` LIKE '%" . $searchLike . "%'"
            );
        } else {
            $worldRows = execute_query(
                'world',
                "SELECT `entry` FROM `item_template` WHERE `name` LIKE '%" . $searchLike . "%'"
            );
        }

        if (is_array($worldRows)) {
            foreach ($worldRows as $row) {
                $itemId = (int)$row['entry'];
                if (!isset($cacheMap[$itemId])) {
                    $cacheMap[$itemId] = cache_item_search($itemId);
                }

                $items[$itemId] = [
                    'id' => $itemId,
                    'name' => $cacheMap[$itemId]['item_name'],
                    'level' => (int)$cacheMap[$itemId]['item_level'],
                    'source' => $cacheMap[$itemId]['item_source'],
                    'relevance' => (int)$cacheMap[$itemId]['item_relevance'],
                ];
            }
        }

        return [
            'error' => '',
            'validated' => $validated,
            'results' => array_values($items),
        ];
    });

    if (is_array($searchPayload)) {
        $searchError = $searchPayload['error'];
        $validatedSearch = $searchPayload['validated'];
        $results = $searchPayload['results'];
    } else {
        $searchError = 'The legacy armory search could not be started.';
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
    $visibleIds = array_map(static function ($row) {
        return (int)$row['id'];
    }, $pageResults);

    $visibleDetails = spp_modern_item_search_with_legacy_env(function () use ($visibleIds) {
        global $realms;

        $realmKey = (int)$realms[REALM_NAME][2];
        $detailMap = [];
        $ids = implode(',', array_map('intval', $visibleIds));

        if ($ids === '') {
            return [];
        }

        $cacheRows = execute_query(
            'armory',
            "SELECT * FROM `cache_item` WHERE `item_id` IN (" . $ids . ") AND `mangosdbkey` = " . $realmKey
        );

        if (is_array($cacheRows)) {
            foreach ($cacheRows as $row) {
                $detailMap[(int)$row['item_id']] = $row;
            }
        }

        foreach ($visibleIds as $itemId) {
            if (!isset($detailMap[$itemId])) {
                $detailMap[$itemId] = cache_item($itemId);
            }
        }

        return $detailMap;
    });
}

$DB = $siteDb;
$WSDB = $siteWorldDb;
$CHDB = $siteCharDb;
if ($siteArmoryDb !== null) {
    $ARDB = $siteArmoryDb;
}
if ($siteConfig !== null) {
    $config = $siteConfig;
}
if ($siteRealms !== null) {
    $realms = $siteRealms;
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
      <p class="item-search-copy">This page uses the original armory item lookup rules and cache tables, but presents the results in the current site layout. Search by item name and it will query the selected realm's armory and world databases the same way the legacy armory did.</p>
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

    <p class="item-search-helper">Realm: <?php echo htmlspecialchars($realmLabel); ?>. Minimum search length matches the old armory rules.</p>
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
          $quality = (int)($detail['item_quality'] ?? 0);
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

