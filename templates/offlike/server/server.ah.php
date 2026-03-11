<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$currtmp = '/armory';
$use_itemsite_url = '/armory/index.php?searchType=iteminfo&item=';

/* ---------- Realm Selection ---------- */
$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$db = $db ?? ($GLOBALS['db'] ?? null);

if (!is_array($realmMap) || !is_array($db)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
if (!isset($realmMap[$realmId])) {
    die("Invalid realm ID");
}

$db_chars = $realmMap[$realmId]['chars'];
$db_world = $realmMap[$realmId]['world'];
$realmName = $realmMap[$realmId]['label'];

/* ---------- PDO Connection ---------- */
try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db_chars};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

/* ---------- Lang ---------- */
$lang = [
  'ah_auctionhouse' => 'Auction House',
  'ah_alliance'     => 'Alliance',
  'ah_horde'        => 'Horde',
  'ah_blackwater'   => 'Blackwater',
  'all'             => 'All',
  'ah_itemclass'    => 'Type',
  'ah_itemname'     => 'Item',
  'ah_quantity'     => 'Qty',
  'ah_time'         => 'Time Left',
  'ah_currentbid'   => 'Current Bid',
  'ah_buyout'       => 'Buyout',
  'ah_expired'      => 'Expired'
];

$qualityOptions = [
  -1 => 'Any Quality',
   0 => 'Poor',
   1 => 'Common',
   2 => 'Uncommon',
   3 => 'Rare',
   4 => 'Epic',
   5 => 'Legendary',
   6 => 'Artifact',
   7 => 'Heirloom',
];

$itemClassOptions = [
  -1 => 'Any Type',
   0 => 'Consumable',
   1 => 'Container',
   2 => 'Weapon',
   3 => 'Gem',
   4 => 'Armor',
   5 => 'Reagent',
   6 => 'Projectile',
   7 => 'Trade Goods',
   8 => 'Generic',
   9 => 'Recipe',
  10 => 'Money',
  11 => 'Quiver',
  12 => 'Quest Item',
  13 => 'Key',
  14 => 'Permanent',
  15 => 'Miscellaneous',
];

$classMaskOptions = [
   0 => 'Any Class',
   1 => 'Warrior',
   2 => 'Paladin',
   4 => 'Hunter',
   8 => 'Rogue',
  16 => 'Priest',
  32 => 'Death Knight',
  64 => 'Shaman',
 128 => 'Mage',
 256 => 'Warlock',
1024 => 'Druid',
];

/* ---------- Sorting ---------- */
$validSorts = [
  'type'   => 'i.class',
  'item'   => 'i.name',
  'qty'    => 'a.item_count',
  'time'   => 'a.time',
  'bid'    => 'a.startbid',
  'buyout' => 'a.buyoutprice',
  'req'    => 'i.RequiredLevel',
  'ilvl'   => 'i.ItemLevel'
];

$sort = $_GET['sort'] ?? 'time';
$dir  = strtolower($_GET['dir'] ?? 'desc');
if (!array_key_exists($sort, $validSorts)) $sort = 'time';
if (!in_array($dir, ['asc','desc'])) $dir = 'desc';
$orderBy = "ORDER BY {$validSorts[$sort]} " . strtoupper($dir);

/* ---------- Filter ---------- */
$whereParts = [];
$filter = isset($_GET['filter']) ? strtolower($_GET['filter']) : 'all';
switch ($filter) {
  case 'ally':  $whereParts[] = 'a.houseid IN (6)'; break;
  case 'horde': $whereParts[] = 'a.houseid IN (7)'; break;
  case 'black': $whereParts[] = 'a.houseid IN (1)'; break;
  default:      $whereParts[] = 'a.houseid IN (1,6,7)'; break;
}

$search = trim($_GET['search'] ?? '');
$qualityFilter = isset($_GET['quality']) ? (int)$_GET['quality'] : -1;
$itemClassFilter = isset($_GET['item_class']) ? (int)$_GET['item_class'] : -1;
$classMaskFilter = isset($_GET['usable_class']) ? (int)$_GET['usable_class'] : 0;
$minReqLevel = isset($_GET['min_level']) && $_GET['min_level'] !== '' ? max(0, (int)$_GET['min_level']) : null;
$maxReqLevel = isset($_GET['max_level']) && $_GET['max_level'] !== '' ? max(0, (int)$_GET['max_level']) : null;

$params = [];

if ($search !== '') {
    $whereParts[] = 'i.name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
if ($qualityFilter >= 0) {
    $whereParts[] = 'i.Quality = :quality';
    $params[':quality'] = $qualityFilter;
}
if ($itemClassFilter >= 0) {
    $whereParts[] = 'i.class = :item_class';
    $params[':item_class'] = $itemClassFilter;
}
if ($classMaskFilter > 0) {
    $whereParts[] = '(i.AllowableClass = -1 OR (i.AllowableClass & :class_mask) != 0)';
    $params[':class_mask'] = $classMaskFilter;
}
if ($minReqLevel !== null) {
    $whereParts[] = 'i.RequiredLevel >= :min_level';
    $params[':min_level'] = $minReqLevel;
}
if ($maxReqLevel !== null) {
    $whereParts[] = 'i.RequiredLevel <= :max_level';
    $params[':max_level'] = $maxReqLevel;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereParts);

/* ---------- Pagination ---------- */
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 100;
$offset = ($page - 1) * $limit;

/* ---------- Auction Query ---------- */
$sql = "
SELECT
  a.id, a.houseid, a.item_template,
  a.item_count AS quantity,
  a.buyoutprice AS buyout,
  a.startbid AS currentbid,
  a.time,
  i.class,
  i.subclass,
  i.InventoryType,
  i.Quality AS quality,
  i.RequiredLevel,
  i.ItemLevel,
  i.AllowableClass,
  i.name AS itemname
FROM `{$db_chars}`.`auction` AS a
LEFT JOIN `{$db_world}`.`item_template` AS i ON i.entry = a.item_template
{$whereClause}
AND a.time > UNIX_TIMESTAMP(NOW())
{$orderBy}
LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ah_entry = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Count ---------- */
$count_sql = "
SELECT COUNT(*)
FROM `{$db_chars}`.`auction` AS a
LEFT JOIN `{$db_world}`.`item_template` AS i ON i.entry = a.item_template
{$whereClause}
AND a.time > UNIX_TIMESTAMP(NOW())";
$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total = $count_stmt->fetchColumn();
$numofpgs = max(1, ceil($total / $limit));
$resultStart = $total > 0 ? $offset + 1 : 0;
$resultEnd = min($offset + $limit, $total);

/* ---------- Helpers ---------- */
$current_time = time();
$icon_path = "/templates/tbc/images/ah_system";
function item_manage_class($iclass) {
  $names = ['Consumable','Container','Weapon','Gem','Armor','Reagent','Projectile',
    'Trade Goods','Generic','Recipe','Money','Quiver','Quest Item','Key','Permanent','Miscellaneous'];
  return $names[$iclass] ?? 'Unknown';
}
function parse_gold($n){return ['g'=>intval($n/10000),'s'=>intval(($n%10000)/100),'c'=>$n%100];}
function print_gold($g){global $icon_path;
  echo "<span class='gold-inline'>";
  if ($g['g']>0) echo "{$g['g']}<img src='{$icon_path}/gold.GIF'> ";
  if ($g['s']>0) echo "{$g['s']}<img src='{$icon_path}/silver.GIF'> ";
  if ($g['c']>0 || ($g['g']==0 && $g['s']==0)) echo "{$g['c']}<img src='{$icon_path}/copper.GIF'>";
  echo "</span>";
}
function ah_print_gold($v){echo($v==='---')?$v:print_gold(parse_gold($v));}
function ah_time_left($exp){global $current_time, $lang;
  $left=$exp-$current_time;
  if($left<=0){echo"<span class='expired'>{$lang['ah_expired']}</span>";return;}
  $h=intval($left/3600);$m=intval(($left%3600)/60);
  echo $h>0?"{$h}h {$m}m":"{$m}m";
}
function bind_all_params($stmt, array $params) {
  foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
  }
}

/* ---------- Output ---------- */
builddiv_start(1, $lang['ah_auctionhouse'], 1);

$currentRealm  = $realmId;
$currentFilter = $filter;
$baseUrl = "index.php?n=server&sub=ah&realm={$currentRealm}&filter={$currentFilter}";
if ($search !== '') $baseUrl .= '&search=' . urlencode($search);
if ($qualityFilter >= 0) $baseUrl .= '&quality=' . $qualityFilter;
if ($itemClassFilter >= 0) $baseUrl .= '&item_class=' . $itemClassFilter;
if ($classMaskFilter > 0) $baseUrl .= '&usable_class=' . $classMaskFilter;
if ($minReqLevel !== null) $baseUrl .= '&min_level=' . $minReqLevel;
if ($maxReqLevel !== null) $baseUrl .= '&max_level=' . $maxReqLevel;

?>

<style>
.ah-search-grid {
  max-width: 1000px;
  margin: 18px auto 12px;
  padding-top: 6px;
  display: grid;
  grid-template-columns: minmax(260px, 2.4fr) repeat(5, minmax(90px, 1fr)) auto;
  gap: 10px;
  align-items: end;
  clear: both;
}
.ah-search-grid > div {
  min-width: 0;
}
.ah-search-grid label {
  display: block;
  color: #ffcc66;
  font-weight: bold;
  margin-bottom: 4px;
}
.ah-results-summary {
  max-width: 1000px;
  margin: 0 auto 8px;
  color: #ffcc66;
  font-weight: bold;
  text-align: left;
}
.pagination-controls {
  margin: 0 auto 14px !important;
  padding: 10px 18px;
}
.page-links {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.ah-filter-bar {
  justify-content: flex-end;
}
.ah-table .header,
.ah-table .row {
  grid-template-columns: 150px minmax(320px, 2.2fr) 55px 80px 90px 110px 190px !important;
}
.ah-table .col:nth-child(1),
.ah-table .col:nth-child(2) {
  text-align: left;
  padding: 0 12px;
}.ah-table .col:nth-child(2) a {
  white-space: normal;
  line-height: 1.2;
}.ah-price-stack {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}.ah-price-stack .price-line {
  display: block;
}
@media (max-width: 980px) {
  .ah-search-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
@media (max-width: 820px) {
  .ah-table .header,
  .ah-table .row {
    grid-template-columns: 130px minmax(240px, 1.8fr) 50px 70px 80px 100px 170px !important;
  }
}
@media (max-width: 640px) {
  .ah-search-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<form method="get" class="modern-content ah-search-grid">
  <input type="hidden" name="n" value="server">
  <input type="hidden" name="sub" value="ah">
  <input type="hidden" name="realm" value="<?php echo $currentRealm; ?>">
  <input type="hidden" name="filter" value="<?php echo htmlspecialchars($currentFilter); ?>">

  <div>
    <input
      type="text"
      id="commandSearch"
      name="search"
      value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search auction items..."

      style="margin-bottom:0;"
    >
  </div>
  <div>
    <label>Quality</label>
    <select name="quality" style="width:100%;">
      <?php foreach ($qualityOptions as $value => $label): ?>
        <option value="<?php echo $value; ?>" <?php if ($qualityFilter === $value) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>Type</label>
    <select name="item_class" style="width:100%;">
      <?php foreach ($itemClassOptions as $value => $label): ?>
        <option value="<?php echo $value; ?>" <?php if ($itemClassFilter === $value) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>Usable By</label>
    <select name="usable_class" style="width:100%;">
      <?php foreach ($classMaskOptions as $value => $label): ?>
        <option value="<?php echo $value; ?>" <?php if ($classMaskFilter === $value) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>Min Req</label>
    <input type="number" name="min_level" min="0" value="<?php echo $minReqLevel !== null ? (int)$minReqLevel : ''; ?>" style="width:100%;">
  </div>
  <div>
    <label>Max Req</label>
    <input type="number" name="max_level" min="0" value="<?php echo $maxReqLevel !== null ? (int)$maxReqLevel : ''; ?>" style="width:100%;">
  </div>
  <div>
    <button type="submit" class="ah-filter faction-neutral">Search</button>
  </div>
</form>

<div class="ah-results-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo (int)$total; ?> auctions</div>

<!-- Pagination + Filters -->
<?php if ($numofpgs > 1): ?>
  <div class="pagination-controls">
    <div class="page-links">
      <?php echo compact_paginate($page, $numofpgs, $baseUrl); ?>
    </div>

    <div class="ah-filter-bar">
      <?php
      $filters = [
        'ally'  => $lang['ah_alliance'],
        'horde' => $lang['ah_horde'],
        'black' => $lang['ah_blackwater'],
        'all'   => $lang['all']
      ];
      foreach ($filters as $key => $label) {
        $active = ($currentFilter === $key) ? 'is-active' : '';
        if ($key === 'ally')       $class = 'faction-alliance';
        elseif ($key === 'horde')  $class = 'faction-horde';
        elseif ($key === 'black')  $class = 'faction-blackwater';
        else                       $class = 'faction-neutral';
        $filterUrl = "index.php?n=server&sub=ah&realm={$currentRealm}&filter={$key}";
        if ($search !== '') $filterUrl .= '&search=' . urlencode($search);
        if ($qualityFilter >= 0) $filterUrl .= '&quality=' . $qualityFilter;
        if ($itemClassFilter >= 0) $filterUrl .= '&item_class=' . $itemClassFilter;
        if ($classMaskFilter > 0) $filterUrl .= '&usable_class=' . $classMaskFilter;
        if ($minReqLevel !== null) $filterUrl .= '&min_level=' . $minReqLevel;
        if ($maxReqLevel !== null) $filterUrl .= '&max_level=' . $maxReqLevel;
        echo "<a href='{$filterUrl}' class='ah-filter {$class} {$active}'>{$label}</a>";
      }
      ?>
    </div>
  </div>
<?php endif; ?>

<?php
function sort_link($key, $label, $currentSort, $currentDir, $baseUrl) {
  $newDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
  $arrow  = '';
  if ($currentSort === $key) $arrow = $currentDir === 'asc' ? ' ?' : ' ?';
  return "<a href='{$baseUrl}&sort={$key}&dir={$newDir}'>{$label}{$arrow}</a>";
}
?>

<div class="wow-table ah-table">
  <div class="header">
    <div class="col"><?php echo sort_link('type', $lang['ah_itemclass'], $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('item', $lang['ah_itemname'],  $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('qty',  $lang['ah_quantity'],  $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('req',  'Req Lvl',            $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('ilvl', 'Item Lvl',           $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('time', $lang['ah_time'],      $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('bid',  $lang['ah_currentbid'],$sort, $dir, $baseUrl); ?><br><?php echo sort_link('buyout',$lang['ah_buyout'], $sort, $dir, $baseUrl); ?></div>
  </div>

  <?php if (!$ah_entry): ?>
    <div class="row empty">No auctions found for this search.</div>
  <?php else: foreach ($ah_entry as $row): ?>
    <div class="row">
      <div class="col"><?php echo item_manage_class($row['class']); ?></div>
      <div class="col">
        <a class="iqual<?php echo $row['quality']; ?>" href="<?php echo $use_itemsite_url . $row['item_template']; ?>" target="_blank">
          <?php echo htmlspecialchars($row['itemname']); ?>
        </a>
      </div>
      <div class="col"><?php echo $row['quantity']; ?></div>
      <div class="col"><?php echo (int)$row['RequiredLevel']; ?></div>
      <div class="col"><?php echo (int)$row['ItemLevel']; ?></div>
      <div class="col"><?php ah_time_left($row['time']); ?></div>
      <div class="col gold-cell">
        <div class="ah-price-stack">
          <span class="price-line"><?php ah_print_gold($row['currentbid']); ?></span>
          <span class="price-line"><?php ah_print_gold($row['buyout']); ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php builddiv_end(); ?>









