<style>
.honor-table .header,
.honor-table .row {
  grid-template-columns: 240px 80px 90px 90px 90px minmax(180px, 1.2fr) 90px;
}
.honor-table .col.name {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-left: 14px;
  text-align: left;
}
.honor-table .col.icon-col,
.honor-table .col.level-col,
.honor-table .col.hk-col,
.honor-table .col.dk-col,
.honor-table .col.honor-col {
  text-align: center;
}
.honor-table .row:nth-child(even) { background: rgba(255,255,255,0.04); }
.honor-table .row:hover { background: rgba(255,255,255,0.08); }
.honor-table .rank-name {
  text-align: left;
  padding: 0 12px;
}
.honor-table .faction-icon,
.honor-table .rank-icon {
  width: 34px;
  height: 34px;
  object-fit: contain;
}
.honor-search-wrap {
  margin: 12px auto 16px;
}
.honor-summary {
  color: #ffcc66;
  font-weight: bold;
  margin: 0 0 12px;
}
.honor-table .circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #222;
  background-color: #000;
}
@media (max-width: 980px) {
  .honor-table .header,
  .honor-table .row {
    grid-template-columns: 220px 70px 80px 80px 80px minmax(160px, 1.1fr);
  }
  .honor-table .header .col:nth-child(7),
  .honor-table .row .col:nth-child(7) {
    display: none;
  }
}
@media (max-width: 760px) {
  .honor-table .header,
  .honor-table .row {
    grid-template-columns: 220px 70px 80px 80px minmax(140px, 1fr);
  }
  .honor-table .header .col:nth-child(5),
  .honor-table .row .col:nth-child(5),
  .honor-table .header .col:nth-child(7),
  .honor-table .row .col:nth-child(7) {
    display: none;
  }
}
</style>

<?php
builddiv_start(1, $lang['honor'] ?? 'Honor', 1);

$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/components/forum/forum.func.php');

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
$armoryRealm = spp_get_armory_realm_name($realmId) ?? '';

$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$itemsPerPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$search = trim($_GET['search'] ?? '');
$factionFilter = strtolower(trim((string)($_GET['faction'] ?? 'all')));
if (!in_array($factionFilter, ['all', 'alliance', 'horde'], true)) {
    $factionFilter = 'all';
}

$classNames = [
    1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
    6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$raceNames = [
    1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
    6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$MANG = new Mangos();

$charPdo = spp_get_pdo('chars', $realmId);
$rows = $charPdo->query("
  SELECT
    c.guid,
    c.name,
    c.race,
    c.class,
    c.gender,
    c.level,
    COALESCE(c.stored_honorable_kills, 0) AS honorable_kills,
    COALESCE(c.stored_dishonorable_kills, 0) AS dishonorable_kills,
    COALESCE(c.stored_honor_rating, 0) AS honor_points,
    COALESCE(c.honor_highest_rank, 0) AS rank_id
  FROM characters c
  WHERE COALESCE(c.stored_honorable_kills, 0) > 0
  ORDER BY honor_points DESC, honorable_kills DESC, level DESC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$characters = [];
foreach ($rows as $row) {
    $faction = in_array((int)$row['race'], $allianceRaces, true) ? 'Alliance' : 'Horde';
    $factionKey = strtolower($faction);
    $rankId = max(0, min(14, (int)$row['rank_id']));
    $rankName = $MANG->characterInfoByID['character_rank'][$factionKey][$rankId] ?? ('Rank ' . $rankId);
    $className = $classNames[(int)$row['class']] ?? 'Unknown';
    $raceName = $raceNames[(int)$row['race']] ?? 'Unknown';

    $row['faction_name'] = $faction;
    $row['faction_icon'] = '/templates/offlike/images/modern/logo-' . $factionKey . '.png';
    $row['rank_name'] = $rankName;
    $row['rank_icon'] = '/templates/offlike/images/icons/pvpranks/rank' . $rankId . '.gif';
    $row['class_name'] = $className;
    $row['race_name'] = $raceName;

    if ($factionFilter === 'alliance' && !in_array((int)$row['race'], $allianceRaces, true)) continue;
    if ($factionFilter === 'horde'    &&  in_array((int)$row['race'], $allianceRaces, true)) continue;

    if ($search !== '') {
        $haystack = strtolower(implode(' ', [
            $row['name'],
            $className,
            $raceName,
            $faction,
            $rankName,
            (string)$row['level'],
            (string)$row['honorable_kills'],
            (string)$row['dishonorable_kills'],
            (string)round((float)$row['honor_points']),
        ]));

        if (strpos($haystack, strtolower($search)) === false) {
            continue;
        }
    }

    $characters[] = $row;
}

$count = count($characters);
$pnum = max(1, (int)ceil($count / $itemsPerPage));
if ($p > $pnum) $p = $pnum;
if ($p < 1) $p = 1;
$offset = ($p - 1) * $itemsPerPage;
$charactersPage = array_slice($characters, $offset, $itemsPerPage);
$resultStart = $count > 0 ? $offset + 1 : 0;
$resultEnd = min($offset + $itemsPerPage, $count);
$baseUrl = "index.php?n=server&sub=honor&realm={$realmId}&per_page={$itemsPerPage}";
if ($factionFilter !== 'all') {
    $baseUrl .= '&faction=' . urlencode($factionFilter);
}
if ($search !== '') {
    $baseUrl .= '&search=' . urlencode($search);
}
?>

<div class="honor-search-wrap">
  <form method="get" class="modern-content" id="honorSearchForm">
    <input type="hidden" name="n" value="server">
    <input type="hidden" name="sub" value="honor">
    <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
    <input type="hidden" name="p" value="1">
    <input type="hidden" name="per_page" value="<?php echo $itemsPerPage; ?>">
    <input type="hidden" name="faction" value="<?php echo htmlspecialchars($factionFilter); ?>">
    <input
      type="text"
      id="commandSearch"
      name="search"
      value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search name, faction, race, class, rank, HK, DK, honor..."
      autocomplete="off"
    >
  </form>
</div>

<div class="honor-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo (int)$count; ?> fighters</div>

<?php if ($pnum > 1): ?>
  <div class="pagination-controls">
    <div class="page-links">
      <?php echo compact_paginate($p, $pnum, $baseUrl); ?>
    </div>
  </div>
<?php endif; ?>

<div class="wow-table honor-table">
  <div class="header">
    <div class="col sortable" data-sort="name">Name</div>
    <div class="col faction-filter" data-label="Faction">
      <select
        name="faction"
        class="inline-filter-select"
        form="honorSearchForm"
        onchange="this.form.submit()"
        onmousedown="event.stopPropagation()"
        onclick="event.stopPropagation()"
      >
        <option value="all"      <?php if ($factionFilter === 'all')      echo 'selected'; ?>>All</option>
        <option value="alliance" <?php if ($factionFilter === 'alliance') echo 'selected'; ?>>Alliance</option>
        <option value="horde"    <?php if ($factionFilter === 'horde')    echo 'selected'; ?>>Horde</option>
      </select>
    </div>
    <div class="col sortable" data-sort="level">Level</div>
    <div class="col sortable" data-sort="hk">HK</div>
    <div class="col sortable" data-sort="dk">DK</div>
    <div class="col sortable" data-sort="rank">Rank</div>
    <div class="col sortable" data-sort="honor">Honor</div>
  </div>

  <?php if ($charactersPage): ?>
    <?php foreach ($charactersPage as $item): ?>
      <?php $portrait = get_character_portrait_path($item['guid'], $item['gender'], $item['race'], $item['class']); ?>
      <div class="row">
        <div class="col name class-<?php echo strtolower($item['class_name']); ?>">
          <a href="index.php?n=server&sub=character&realm=<?php echo (int)$realmId; ?>&character=<?php echo urlencode($item['name']); ?>">
            <img src="<?php echo $portrait; ?>" class="circle portrait" alt="">
            <?php echo htmlspecialchars($item['name']); ?>
          </a>
        </div>
        <div class="col icon-col faction-col">
          <img src="<?php echo $item['faction_icon']; ?>" class="faction-icon" alt="<?php echo htmlspecialchars($item['faction_name']); ?>" title="<?php echo htmlspecialchars($item['faction_name']); ?>">
        </div>
        <div class="col level-col"><?php echo (int)$item['level']; ?></div>
        <div class="col hk-col"><?php echo (int)$item['honorable_kills']; ?></div>
        <div class="col dk-col"><?php echo (int)$item['dishonorable_kills']; ?></div>
        <div class="col rank-name">
          <span style="display:flex;align-items:center;gap:10px;">
            <img src="<?php echo $item['rank_icon']; ?>" class="rank-icon" alt="<?php echo htmlspecialchars($item['rank_name']); ?>">
            <span>
              <?php echo htmlspecialchars($item['rank_name']); ?>
              <span style="font-size:0.75em;opacity:0.55;">R<?php echo (int)$item['rank_id']; ?></span>
            </span>
          </span>
        </div>
        <div class="col honor-col"><?php echo (int)round((float)$item['honor_points']); ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="row">
      <div class="col" style="grid-column:1/-1;text-align:center;color:#888;">No honored characters found.</div>
    </div>
  <?php endif; ?>
</div>

<?php if ($pnum > 1): ?>
  <div class="pagination-controls">
    <div class="page-links">
      <?php echo compact_paginate($p, $pnum, $baseUrl); ?>
    </div>
  </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const table = document.querySelector('.honor-table');
  if (!table) return;

  const headers = table.querySelectorAll('.header .sortable');
  let sortState = { key: 'honor', dir: 'desc' };

  function textValue(row, idx) {
    const col = row.children[idx];
    return (col ? col.textContent : '').trim().toLowerCase();
  }

  function numericValue(row, idx) {
    const raw = textValue(row, idx).replace(/[^0-9.-]/g, '');
    return raw === '' ? 0 : Number(raw);
  }

  function compareRows(a, b, key) {
    switch (key) {
      case 'level': return numericValue(a, 2) - numericValue(b, 2);
      case 'hk': return numericValue(a, 3) - numericValue(b, 3);
      case 'dk': return numericValue(a, 4) - numericValue(b, 4);
      case 'honor': return numericValue(a, 6) - numericValue(b, 6);
      case 'faction': return textValue(a, 1).localeCompare(textValue(b, 1));
      case 'rank': return textValue(a, 5).localeCompare(textValue(b, 5));
      case 'name':
      default: return textValue(a, 0).localeCompare(textValue(b, 0));
    }
  }

  headers.forEach(function (header) {
    header.style.cursor = 'pointer';
    header.addEventListener('click', function () {
      const key = header.dataset.sort;
      sortState.dir = sortState.key === key && sortState.dir === 'asc' ? 'desc' : 'asc';
      sortState.key = key;

      const rows = Array.from(table.querySelectorAll('.row'));
      rows.sort(function (a, b) {
        const result = compareRows(a, b, key);
        return sortState.dir === 'asc' ? result : -result;
      });
      rows.forEach(function (row) { table.appendChild(row); });
    });
  });
});
</script>

<?php builddiv_end(); ?>
