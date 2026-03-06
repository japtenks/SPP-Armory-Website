<style>
/* ===============================
   AUCTION HOUSE TABLE
   =============================== */
.ah-table { composes: wow-table; }
.ah-table .header,
.ah-table .row {
  grid-template-columns: 130px 250px 70px 120px 100px 120px 120px 120px;
}
.ah-table .gold-cell { text-align: right; padding-right: 10px; }

/* ===============================
   AUCTION HOUSE FILTER BUTTONS
   =============================== */
.ah-filter-bar {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
}
.pagination-controls {
  margin-top: 200px;
}
.ah-filter {
  color: #ffcc66;
  background: #111;
  border: 1px solid #333;
  border-radius: 6px;
  padding: 4px 10px;
  text-decoration: none;
  font-weight: bold;
  font-size: 0.95rem;
  transition: background 0.2s, color 0.2s, box-shadow 0.2s;
}
.ah-filter:hover {
  background: rgba(255,204,102,0.1);
  box-shadow: 0 0 6px rgba(255,204,102,0.5);
}
.ah-filter.is-active {
  background: linear-gradient(to bottom, #2a2a2a, #1a1a1a);
  border-color: #ffcc66;
  color: #fff3a0;
  box-shadow: 0 0 8px rgba(255,204,102,0.4);
}

/* ===============================
   FACTION COLOR THEMES
   =============================== */
.ah-filter.faction-alliance {
  color: #79a9ff;
  border-color: #3366ff;
  text-shadow: 0 0 6px rgba(120,160,255,0.4);
}
.ah-filter.faction-alliance:hover,
.ah-filter.faction-alliance.is-active {
  background: rgba(60,100,255,0.15);
  box-shadow: 0 0 8px rgba(120,160,255,0.6);
  color: #bcd8ff;
}

.ah-filter.faction-horde {
  color: #ff5c5c;
  border-color: #b30000;
  text-shadow: 0 0 6px rgba(255,60,60,0.4);
}
.ah-filter.faction-horde:hover,
.ah-filter.faction-horde.is-active {
  background: rgba(180,0,0,0.15);
  box-shadow: 0 0 8px rgba(255,60,60,0.6);
  color: #ffc0c0;
}

.ah-filter.faction-blackwater {
  color: #ffcc66;
  border-color: #8b6a2a;
  text-shadow: 0 0 6px rgba(255,204,102,0.4);
}
.ah-filter.faction-blackwater:hover,
.ah-filter.faction-blackwater.is-active {
  background: rgba(255,204,102,0.1);
  box-shadow: 0 0 8px rgba(255,204,102,0.6);
  color: #fff3a0;
}

.ah-filter.faction-neutral {
  color: #ccc;
  border-color: #555;
  text-shadow: 0 0 4px rgba(255,255,255,0.2);
}
.ah-filter.faction-neutral:hover,
.ah-filter.faction-neutral.is-active {
  background: rgba(200,200,200,0.1);
  box-shadow: 0 0 8px rgba(255,255,255,0.4);
  color: #fff;
}

/* === GOLD DISPLAY FIX === */
.gold-cell {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}
.gold-inline {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 2px;
  white-space: nowrap;
}
.gold-inline img {
  height: 14px;
  width: auto;
  vertical-align: middle;
  margin: 0 1px -1px 1px;
}
.ah-table .col.gold-cell {
  padding-right: 8px;
}
.ah-table .header,
.ah-table .row {
  grid-template-columns:
    130px   /* Type */
    280px   /* Item name */
    70px    /* Qty */
    120px   /* Time Left */
    minmax(140px, 1fr)  /* Current Bid */
    minmax(160px, 1fr); /* Buyout */
}

/* keep coin sections aligned and readable */
.gold-cell {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  min-width: 140px;
  gap: 2px;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  font-size: 0.95rem;
}
.gold-inline img {
  height: 14px;
  width: auto;
  vertical-align: middle;
  margin: 0 1px -1px 1px;
}
/* === Base Grid === */
.ah-table .header,
.ah-table .row {
  display: grid;
  grid-template-columns:
    130px  /* Type */
    280px  /* Item */
    70px   /* Qty */
    120px  /* Time Left */
    minmax(140px, 1fr)  /* Current Bid */
    minmax(160px, 1fr); /* Buyout */
  align-items: center;
}
.ah-table .col a {
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  display: inline-block;
  max-width: 100%;
}


/* === Responsive Compression === */

/* Medium screens — hide less relevant info */
@media (max-width: 1100px) {
  .ah-table .header .col:nth-child(1),
  .ah-table .row .col:nth-child(1), /* Type */
  .ah-table .header .col:nth-child(3),
  .ah-table .row .col:nth-child(3), /* Qty */
  .ah-table .header .col:nth-child(4),
  .ah-table .row .col:nth-child(4)  /* Time Left */
  { display: none; }

  .ah-table .header,
  .ah-table .row {
    grid-template-columns:
      300px  /* Item */
      minmax(160px, 1fr) /* Current Bid */
      minmax(160px, 1fr); /* Buyout */
  }
}

/* Small screens — show only Item + Buyout */
@media (max-width: 700px) {
  .ah-table .header .col:nth-child(5),
  .ah-table .row .col:nth-child(5) /* Hide Bid */ 
  { display: none; }

  .ah-table .header,
  .ah-table .row {
    grid-template-columns:
      1fr   /* Item */
      160px /* Buyout */;
  }
}

/* Keep gold layout tight and clean */
.gold-cell {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  min-width: 140px;
  gap: 2px;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  font-size: 0.95rem;
}

</style>

<?php
/* =========================================================
   Auction House Standalone - verified for TBC layout
   ========================================================= */

$currtmp = '/armory';
$use_itemsite_url = '/armory/index.php?searchType=iteminfo&item=';

/* ---------- Realm Selection ---------- */
$realmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 2;
switch ($realmId) {
    case 1: $db_chars = 'classiccharacters'; $db_world = 'classicmangos'; $realmName = 'Classic'; break;
    case 2: $db_chars = 'tbccharacters';     $db_world = 'tbcmangos';     $realmName = 'The Burning Crusade'; break;
    case 3: $db_chars = 'wotlkcharacters';   $db_world = 'wotlkmangos';   $realmName = 'Wrath of the Lich King'; break;
    default: die("Invalid realm ID");
}

/* ---------- PDO Connection ---------- */
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3310;dbname={$db_chars};charset=utf8mb4",
        'root', '123456',
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

/* ---------- Sorting ---------- */
$validSorts = [
  'type'   => 'i.class',
  'item'   => 'i.name',
  'qty'    => 'a.item_count',
  'time'   => 'a.time',
  'bid'    => 'a.startbid',
  'buyout' => 'a.buyoutprice'
];

$sort = $_GET['sort'] ?? 'time';
$dir  = strtolower($_GET['dir'] ?? 'desc');
if (!array_key_exists($sort, $validSorts)) $sort = 'time';
if (!in_array($dir, ['asc','desc'])) $dir = 'desc';
$orderBy = "ORDER BY {$validSorts[$sort]} " . strtoupper($dir);

/* ---------- Filter ---------- */
$filter = isset($_GET['filter']) ? strtolower($_GET['filter']) : 'all';
switch ($filter) {
  case 'ally':  $whereClause = "WHERE a.houseid IN (6)"; break;
  case 'horde': $whereClause = "WHERE a.houseid IN (7)"; break;
  case 'black': $whereClause = "WHERE a.houseid IN (1)"; break;
  default:      $whereClause = "WHERE a.houseid IN (1,6,7)"; break;
}

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
  i.class, i.Quality AS quality, i.name AS itemname
FROM `{$db_chars}`.`auction` AS a
LEFT JOIN `{$db_world}`.`item_template` AS i ON i.entry = a.item_template
{$whereClause}
AND a.time > UNIX_TIMESTAMP(NOW())
{$orderBy}
LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ah_entry = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Count ---------- */
$count_sql = "SELECT COUNT(*) FROM `{$db_chars}`.`auction` AS a {$whereClause}";
$total = $pdo->query($count_sql)->fetchColumn();
$numofpgs = max(1, ceil($total / $limit));

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

/* ---------- Output ---------- */
builddiv_start(1, $lang['ah_auctionhouse'], 1);

$currentRealm  = $realmId;
$currentFilter = $filter;
$baseUrl = "index.php?n=server&sub=ah&realm={$currentRealm}&filter={$currentFilter}";
?>

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
        echo "<a href='index.php?n=server&sub=ah&realm={$currentRealm}&filter={$key}' class='ah-filter {$class} {$active}'>{$label}</a>";
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

<!-- Auction Table -->
<div class="wow-table ah-table">
  <div class="header">
    <div class="col"><?php echo sort_link('type', $lang['ah_itemclass'], $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('item', $lang['ah_itemname'],  $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('qty',  $lang['ah_quantity'],  $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('time', $lang['ah_time'],      $sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('bid',  $lang['ah_currentbid'],$sort, $dir, $baseUrl); ?></div>
    <div class="col"><?php echo sort_link('buyout',$lang['ah_buyout'],   $sort, $dir, $baseUrl); ?></div>
  </div>

  <?php if (!$ah_entry): ?>
    <div class="row empty">No auctions found for this faction.</div>
  <?php else: foreach ($ah_entry as $row): ?>
    <div class="row">
      <div class="col"><?php echo item_manage_class($row['class']); ?></div>
      <div class="col">
        <a class="iqual<?php echo $row['quality']; ?>" href="<?php echo $use_itemsite_url . $row['item_template']; ?>" target="_blank">
          <?php echo htmlspecialchars($row['itemname']); ?>
        </a>
      </div>
      <div class="col"><?php echo $row['quantity']; ?></div>
      <div class="col"><?php ah_time_left($row['time']); ?></div>
      <div class="col gold-cell"><?php ah_print_gold($row['currentbid']); ?></div>
      <div class="col gold-cell"><?php ah_print_gold($row['buyout']); ?></div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php builddiv_end(); ?>
