<style>
.character-table { composes: wow-table; }
.character-table .header,
.character-table .row {
  grid-template-columns: 220px 180px 92px 92px 164px 72px minmax(180px, 1fr);
}
.character-table .header .col {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}
.character-table .header .sortable {
  cursor: pointer;
  user-select: none;
}
.character-table .header .sortable::after {
  content: '';
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: transparent;
  font-size: 0.85em;
  transition: color 0.15s ease;
}
.character-table .header .sortable[data-state="asc"]::after {
  content: '↑';
  color: #f1cf75;
}
.character-table .header .sortable[data-state="desc"]::after {
  content: '↓';
  color: #f1cf75;
}
.character-table .col.name {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-left: 14px;
}
.character-table .col.faction-col,
.character-table .col.location-col {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.character-table .header .col.name-header {
  justify-content: flex-start;
  padding-left: 14px;
}
.character-table .col.faction-filter {
  justify-content: center;
}
.character-table .col.location-filter {
  display: flex;
  align-items: center;
  justify-content: center;
}
.character-table .inline-toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #f1cf75;
  font-size: 0.92rem;
  white-space: nowrap;
  margin-left: auto;
}
.character-table .inline-toggle input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #2f7cf6;
}
.character-table .inline-filter-select {
  min-width: 104px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid rgba(214,170,27,0.45);
  background: rgba(9,12,24,0.92);
  color: #f1cf75;
  padding: 0 10px;
}
.character-table .inline-filter-select:focus {
  outline: none;
  border-color: rgba(214,170,27,0.8);
  box-shadow: 0 0 0 2px rgba(214,170,27,0.18);
}
.character-table .row:nth-child(even) { background: rgba(255,255,255,0.04); }
.character-table .row:hover { background: rgba(255,255,255,0.08); }
.circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #222;
  background-color: #000;
  transition: .3s;
}
.pagination-controls {
  margin-top: 12px !important;
}
.character-table-toolbar {
  display: flex;
  justify-content: flex-end;
  gap: 16px;
  margin: 0 0 6px;
}
@media (max-width: 900px) {
  .character-table .header .col:nth-child(5),
  .character-table .row .col:nth-child(5) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 220px 180px 92px 92px 72px minmax(180px, 1fr);
  }
}
@media (max-width: 760px) {
  .character-table .header .col:nth-child(3),
  .character-table .row .col:nth-child(3) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 220px 180px 92px 72px minmax(170px, 1fr);
  }
}
@media (max-width: 620px) {
  .character-table .header .col:nth-child(4),
  .character-table .row .col:nth-child(4) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 170px 150px 72px minmax(150px, 1fr);
  }
}
@media (max-width: 480px) {
  .character-table .header .col:nth-child(7),
  .character-table .row .col:nth-child(7) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 160px 140px 72px;
  }
}
</style>

<?php
builddiv_start(1, $lang['characters'], 1);

$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/components/forum/forum.func.php');

if (!function_exists('spp_class_icon_url')) {
    function spp_class_icon_url($classId)
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

if (!function_exists('spp_race_icon_url')) {
    function spp_race_icon_url($raceId, $gender)
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

function parse_character_search($search)
{
    $parsed = [
        'name' => [],
        'guild' => [],
        'zone' => [],
        'class' => [],
        'race' => [],
        'faction' => [],
        'level' => [],
        'generic' => [],
    ];

    $tokens = str_getcsv($search, ' ', '"');
    $currentFlag = null;
    $flagMap = [
        '-n' => 'name',
        '-g' => 'guild',
        '-z' => 'zone',
        '-c' => 'class',
        '-r' => 'race',
        '-f' => 'faction',
        '-l' => 'level',
    ];

    foreach ($tokens as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }

        if (isset($flagMap[strtolower($token)])) {
            $currentFlag = $flagMap[strtolower($token)];
            continue;
        }

        if ($currentFlag !== null) {
            $parsed[$currentFlag][] = $token;
            $currentFlag = null;
        } else {
            $parsed['generic'][] = $token;
        }
    }

    return $parsed;
}

function search_terms_match(array $terms, $value)
{
    if (empty($terms)) {
        return true;
    }

    $value = strtolower((string)$value);
    foreach ($terms as $term) {
        if (stripos($value, strtolower((string)$term)) === false) {
            return false;
        }
    }

    return true;
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
$armoryRealm = spp_get_armory_realm_name($realmId) ?? '';

$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$items_per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$search = trim($_GET['search'] ?? '');
$includeBots = !isset($_GET['show_bots']) || $_GET['show_bots'] === '1';
$onlineOnly = isset($_GET['online']) && $_GET['online'] === '1';
$factionFilter = strtolower(trim((string)($_GET['faction'] ?? 'all')));
if (!in_array($factionFilter, ['all', 'alliance', 'horde'], true)) {
    $factionFilter = 'all';
}
$searchTerms = parse_character_search($search);

$baseWhere = [];
$baseWhere[] = $includeBots ? 'account >= 1' : 'account > 504';
$baseWhere[] = '(xp > 0 OR level > 1)';
if ($onlineOnly) {
    $baseWhere[] = 'online = 1';
}
$baseWhereSql = implode(' AND ', $baseWhere);

$charPdo = spp_get_pdo('chars', $realmId);
$rawCharacters = $charPdo->query("
  SELECT c.guid, c.account, c.name, c.race, c.class, c.gender, c.level, c.zone, c.online, g.guildid AS guild_id, g.name AS guild_name
  FROM characters c
  LEFT JOIN guild_member gm ON c.guid = gm.guid
  LEFT JOIN guild g ON gm.guildid = g.guildid
  WHERE {$baseWhereSql}
  ORDER BY c.level DESC, c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$raceNames = [
  1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
  6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
$allianceRaces = [1,3,4,7,11,22,25,29];
$hordeRaces = [2,5,6,8,10,9,26,27,28];

global $MANG;
if (!isset($MANG)) $MANG = new Mangos();

$filteredCharacters = [];

foreach ($rawCharacters as $item) {
    $location = $MANG->get_zone_name($item['zone']);
    $faction = in_array($item['race'], $allianceRaces) ? 'Alliance' : 'Horde';
    $className = $classNames[$item['class']] ?? 'Unknown';
    $raceName = $raceNames[$item['race']] ?? 'Unknown';
    $guildName = $item['guild_name'] ?? '';
    $levelText = (string)$item['level'];
    $zoneText = $location . ' ' . (string)$item['zone'];

    $genericHaystack = implode(' ', [
        $item['name'],
        $guildName,
        $className,
        $raceName,
        $faction,
        $levelText,
        $zoneText,
    ]);

    if (!search_terms_match($searchTerms['name'], $item['name'])) continue;
    if (!search_terms_match($searchTerms['guild'], $guildName)) continue;
    if (!search_terms_match($searchTerms['zone'], $zoneText)) continue;
    if (!search_terms_match($searchTerms['class'], $className)) continue;
    if (!search_terms_match($searchTerms['race'], $raceName)) continue;
    if (!search_terms_match($searchTerms['faction'], $faction)) continue;
    if (!search_terms_match($searchTerms['level'], $levelText)) continue;
    if (!search_terms_match($searchTerms['generic'], $genericHaystack)) continue;
    if ($factionFilter === 'alliance' && !in_array((int)$item['race'], $allianceRaces, true)) continue;
    if ($factionFilter === 'horde' && !in_array((int)$item['race'], $hordeRaces, true)) continue;

    $item['guild_name'] = $guildName;
    $item['location_name'] = $location;
    $item['faction_name'] = $faction;
    $filteredCharacters[] = $item;
}

$count = count($filteredCharacters);
$filteredBotCount = 0;
foreach ($filteredCharacters as $filteredCharacter) {
    if ((int)$filteredCharacter['account'] <= 504) {
        $filteredBotCount++;
    }
}
$pnum = max(1, (int)ceil($count / $items_per_page));
if ($p > $pnum) $p = $pnum;
if ($p < 1) $p = 1;
$offset = ($p - 1) * $items_per_page;
$characters = array_slice($filteredCharacters, $offset, $items_per_page);
?>

<?php render_character_pagination($p, $pnum, $items_per_page, $realmId, $includeBots, $search, $onlineOnly, $factionFilter, $filteredBotCount); ?>

<form method="get" class="modern-content" id="charsSearchForm" style="margin-bottom:16px;">
  <input type="hidden" name="n" value="server">
  <input type="hidden" name="sub" value="chars">
  <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
  <input type="hidden" name="p" value="1">
  <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">

  <input
    type="text"
    id="commandSearch"
    name="search"
    value="<?php echo htmlspecialchars($search); ?>"
    placeholder="Search: -g guild -z zone -c class -r race -f faction -l level"
    autocomplete="off"
  >
</form>

<div class="character-table-toolbar">
  <label class="inline-toggle" form="charsSearchForm">
    <input
      type="hidden"
      name="show_bots"
      value="0"
      form="charsSearchForm"
    >
    <input
      type="checkbox"
      name="show_bots"
      value="1"
      form="charsSearchForm"
      onchange="this.form.submit()"
      <?php if ($includeBots) echo 'checked'; ?>
    >
    <span>Include bots</span>
  </label>
  <label class="inline-toggle" form="charsSearchForm">
    <input
      type="hidden"
      name="online"
      value="0"
      form="charsSearchForm"
    >
    <input
      type="checkbox"
      name="online"
      value="1"
      form="charsSearchForm"
      onchange="this.form.submit()"
      <?php if ($onlineOnly) echo 'checked'; ?>
    >
    <span>Online</span>
  </label>
</div>

<div class="wow-table character-table">
  <div class="header">
    <div class="col sortable name-header" data-sort="name">Name</div>
    <div class="col sortable" data-sort="guild">Guild</div>
    <div class="col sortable" data-sort="race">Race</div>
    <div class="col sortable" data-sort="class">Class</div>
    <div class="col faction-filter" data-label="Faction">
      <select
        name="faction"
        class="inline-filter-select"
        form="charsSearchForm"
        onchange="this.form.submit()"
        onmousedown="event.stopPropagation()"
        onclick="event.stopPropagation()"
      >
        <option value="all" <?php if ($factionFilter === 'all') echo 'selected'; ?>>All</option>
        <option value="alliance" <?php if ($factionFilter === 'alliance') echo 'selected'; ?>>Alliance</option>
        <option value="horde" <?php if ($factionFilter === 'horde') echo 'selected'; ?>>Horde</option>
      </select>
    </div>
    <div class="col sortable" data-sort="level">Level</div>
    <div class="col sortable location-filter" data-sort="location" data-label="Location">
      <span class="location-label">Location</span>
    </div>
  </div>

<?php if ($characters): ?>
  <?php foreach ($characters as $item): ?>
    <?php
      $portrait = get_character_portrait_path(
        $item['guid'],
        $item['gender'],
        $item['race'],
        $item['class']
      );
      $factionSlug = strtolower($item['faction_name']);
    ?>
    <div class="row">
      <div class="col name class-<?php echo strtolower($classNames[$item['class']] ?? 'unknown'); ?>">
        <a href="index.php?n=server&amp;sub=character&amp;realm=<?php echo (int)$realmId; ?>&amp;character=<?php echo urlencode($item['name']); ?>">
          <img src="<?php echo $portrait; ?>" class="circle portrait" alt="">
          <?php echo htmlspecialchars($item['name']); ?>
        </a>
      </div>
      <div class="col"><?php if (!empty($item['guild_id']) && !empty($item['guild_name'])): ?><a href="index.php?n=server&sub=guild&guildid=<?php echo (int)$item['guild_id']; ?>&realm=<?php echo $realmId; ?>"><?php echo htmlspecialchars($item['guild_name']); ?></a><?php else: ?>-<?php endif; ?></div>
      <div class="col">
        <img src="<?php echo htmlspecialchars(spp_race_icon_url($item['race'], $item['gender'])); ?>"
             class="circle"
             title="<?php echo $raceNames[$item['race']] ?? 'Unknown'; ?>">
      </div>
      <div class="col">
        <img src="<?php echo htmlspecialchars(spp_class_icon_url($item['class'])); ?>"
             class="circle"
             title="<?php echo $classNames[$item['class']] ?? 'Unknown'; ?>">
      </div>
      <div class="col faction-col">
        <img src="templates/offlike/images/modern/logo-<?php echo $factionSlug; ?>.png"
             class="circle"
             title="<?php echo $item['faction_name']; ?>">
      </div>
      <div class="col level"><?php echo (int)$item['level']; ?></div>
      <div class="col location-col"><?php echo htmlspecialchars($item['location_name']); ?></div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="row">
    <div class="col" style="grid-column:1/-1;text-align:center;color:#888;">No characters found.</div>
  </div>
<?php endif; ?>
</div>

<?php render_character_pagination($p, $pnum, $items_per_page, $realmId, $includeBots, $search, $onlineOnly, $factionFilter, $filteredBotCount); ?>
<?php builddiv_end(); ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("commandSearch");
  const controlForms = document.querySelectorAll(".js-char-controls-form");

  controlForms.forEach(form => {
    const hiddenSearch = form.querySelector('input[name="search"]');
    if (hiddenSearch && searchInput) {
      hiddenSearch.value = searchInput.value;
    }

    form.addEventListener("change", () => {
      if (hiddenSearch && searchInput) {
        hiddenSearch.value = searchInput.value;
      }
    });
  });

  const headers = document.querySelectorAll(".header .sortable");
  const table = document.querySelector(".character-table");
  const initialRows = table ? Array.from(table.querySelectorAll(".row")) : [];

  initialRows.forEach((row, index) => {
    row.dataset.originalIndex = String(index);
  });

  const getCellSortValue = (cell, sortKey) => {
    if (!cell) {
      return "";
    }

    if (cell.dataset.sortValue) {
      return cell.dataset.sortValue;
    }

    if (sortKey === "level") {
      return parseInt(cell.textContent.trim(), 10) || 0;
    }

    const link = cell.querySelector("a");
    if (link) {
      return link.textContent.trim().toLowerCase();
    }

    const image = cell.querySelector("img");
    if (image && image.getAttribute("title")) {
      return image.getAttribute("title").trim().toLowerCase();
    }

    return cell.textContent.trim().toLowerCase();
  };

  headers.forEach(header => {
    header.addEventListener("click", e => {
      const table = header.closest(".character-table");
      const rows = Array.from(table.querySelectorAll(".row"));
      const index = Array.from(header.parentNode.children).indexOf(header);
      const sortKey = header.dataset.sort || "";

      let state = header.dataset.state || "none";
      if (state === "none") state = "asc";
      else if (state === "asc") state = "desc";
      else state = "none";

      headers.forEach(h => {
        h.dataset.state = h === header ? state : "none";
      });

      rows.sort((a, b) => {
        if (state === "none") {
          return (parseInt(a.dataset.originalIndex || "0", 10) || 0)
            - (parseInt(b.dataset.originalIndex || "0", 10) || 0);
        }

        const asc = state === "asc";
        const aCell = a.querySelectorAll(".col")[index];
        const bCell = b.querySelectorAll(".col")[index];
        const aValue = getCellSortValue(aCell, sortKey);
        const bValue = getCellSortValue(bCell, sortKey);
        const cmp = sortKey === "level"
          ? aValue - bValue
          : String(aValue).localeCompare(String(bValue), undefined, { numeric: true, sensitivity: "base" });
        if (cmp !== 0) {
          return asc ? cmp : -cmp;
        }
        return (parseInt(a.dataset.originalIndex || "0", 10) || 0)
          - (parseInt(b.dataset.originalIndex || "0", 10) || 0);
      });

      rows.forEach(row => table.appendChild(row));
    });
  });
});
</script>














