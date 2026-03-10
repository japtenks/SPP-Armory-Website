
<style>
.character-table { composes: wow-table; }
.character-table .header,
.character-table .row {
  grid-template-columns: 230px 80px 80px 80px 60px auto;
}
.character-table .col.name {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-left: 14px;
}
.character-table .row:nth-child(even) { background: rgba(255,255,255,0.04); }
.character-table .row:hover { background: rgba(255,255,255,0.08); }
/* === ICONS === */
.circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #222;
  background-color: #000;
  transition: .3s;
}

/* Responsive column hiding for small screens */
@media (max-width: 750px) {
  .character-table .header .col:nth-child(4),
  .character-table .row .col:nth-child(4) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 230px 80px 80px 60px 1fr;
  }
}
@media (max-width: 670px) {
  .character-table .header .col:nth-child(3),
  .character-table .row .col:nth-child(3) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 120px 80px 60px 1fr;
  }
}
@media (max-width: 390px) {
  .character-table .header .col:nth-child(2),
  .character-table .row .col:nth-child(2) { display: none; }
  .character-table .header, .character-table .row {
    grid-template-columns: 120px 60px 1fr;
  }
}
</style>

<?php
builddiv_start(1, $lang['characters'], 1);

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
$realmDB = $realmMap[$realmId]['chars'];


$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$items_per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$includeBots = !empty($_GET['show_bots']);

$where = $includeBots
  ? 'account >= 1 AND zone > 0'
  : 'account > 504 AND zone > 0';

$count = $DB->selectCell("SELECT COUNT(*) FROM {$realmDB}.characters WHERE {$where}");
$pnum = ceil($count / $items_per_page);
if ($p > $pnum && $pnum > 0) $p = $pnum;
if ($p < 1) $p = 1;
$offset = ($p - 1) * $items_per_page;

$urlstring = '?n=server&sub=chars&realm=' . $realmId . ($includeBots ? '&show_bots=1' : '');


$characters = $DB->select("
  SELECT guid, account, name, race, class, gender, level, zone
  FROM {$realmDB}.characters
  WHERE {$where}
  ORDER BY level DESC, name ASC
  LIMIT ?d OFFSET ?d",
  $items_per_page, $offset
);

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$raceNames = [
  1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
  6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
?>

<?php render_character_pagination($p, $pnum, $items_per_page, $realmId, $includeBots); ?>

<div class="wow-table character-table">
  <div class="header">
    <div class="col sortable" data-sort="name">Name</div>
    <div class="col sortable" data-sort="race">Race</div>
    <div class="col sortable" data-sort="class">Class</div>
    <div class="col sortable" data-sort="faction">Faction</div>
    <div class="col sortable" data-sort="level">Level</div>
    <div class="col sortable" data-sort="location">Location</div>
  </div>

<?php 
if ($characters):
  foreach ($characters as $item): 
     $portrait = get_character_portrait_path(
        $item['guid'],
        $item['gender'],
        $item['race'],
        $item['class']
    );


    $faction = in_array($item['race'], [1,3,4,7,11,22,25,29]) ? 'alliance' : 'horde';

    // --- Location ---
global $MANG;
if (!isset($MANG)) $MANG = new Mangos();

$location = $MANG->get_zone_name($item['zone']);

?>
  <div class="row">
    <div class="col name class-<?php echo strtolower($classNames[$item['class']] ?? 'unknown'); ?>">
      <a href="armory/index.php?searchType=profile&character=<?php echo urlencode($item['name']); ?>&realm=<?php echo $realmId; ?>">
        <img src="<?php echo $portrait; ?>" class="circle portrait" alt="">
        <?php echo htmlspecialchars($item['name']); ?>
      </a>
    </div>
    <div class="col">
      <img src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $item['race'].'-'.$item['gender']; ?>.jpg"
           class="circle"
           title="<?php echo $raceNames[$item['race']] ?? 'Unknown'; ?>">
    </div>
    <div class="col">
      <img src="<?php echo $currtmp; ?>/images/icon/class/<?php echo $item['class']; ?>.jpg"
           class="circle"
           title="<?php echo $classNames[$item['class']] ?? 'Unknown'; ?>">
    </div>
    <div class="col">
      <img src="templates/offlike/images/modern/logo-<?php echo $faction; ?>.png"
           class="circle"
           title="<?php echo ucfirst($faction); ?>">
    </div>
    <div class="col level"><?php echo (int)$item['level']; ?></div>
    <div class="col"><?php echo htmlspecialchars($location); ?></div>
  </div>
<?php endforeach; else: ?>
  <div class="row">
    <div class="col" style="grid-column:1/-1;text-align:center;color:#888;">No characters found.</div>
  </div>
<?php endif; ?>
</div>

<?php render_character_pagination($p, $pnum, $items_per_page, $realmId, $includeBots); ?>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const headers = document.querySelectorAll(".header .sortable");
  let sortStack = [];

  headers.forEach(h => h.dataset.label = h.textContent.replace(/[???]/g, "").trim());

  headers.forEach(header => {
    header.addEventListener("click", e => {
      const table = header.closest(".character-table");
      const rows = Array.from(table.querySelectorAll(".row"));
      const index = Array.from(header.parentNode.children).indexOf(header);

      let state = header.dataset.state || "none";
      if (!e.shiftKey) sortStack = [];
      sortStack = sortStack.filter(s => s.index !== index);
      if (state === "none") state = "asc";
      else if (state === "asc") state = "desc";
      else state = "none";
      if (state !== "none") sortStack.push({ index, state });
      header.dataset.state = state;

      headers.forEach(h => {
        const s = sortStack.find(x => x.index === Array.from(headers).indexOf(h));
        const label = h.dataset.label;
        h.textContent = label + (s ? (s.state === "asc" ? " ?" : " ?") : "");
      });

      rows.sort((a, b) => {
        for (const s of sortStack) {
          const asc = s.state === "asc";
          const aCell = a.querySelectorAll(".col")[s.index];
          const bCell = b.querySelectorAll(".col")[s.index];
          const aImg = aCell?.querySelector("img");
          const bImg = bCell?.querySelector("img");
          const aLink = aCell?.querySelector("a");
          const bLink = bCell?.querySelector("a");

          let aText = aLink ? aLink.textContent.trim() :
                       aImg ? (aImg.title || aImg.alt || "") :
                       (aCell?.innerText.trim() || "");
          let bText = bLink ? bLink.textContent.trim() :
                       bImg ? (bImg.title || bImg.alt || "") :
                       (bCell?.innerText.trim() || "");

          aText = aText.toLowerCase();
          bText = bText.toLowerCase();

          if (aText.includes("alliance")) aText = "1";
          else if (aText.includes("horde")) aText = "2";
          if (bText.includes("alliance")) bText = "1";
          else if (bText.includes("horde")) bText = "2";

          const cmp = aText.localeCompare(bText, undefined, { numeric: true });
          if (cmp !== 0) return asc ? cmp : -cmp;
        }
        return 0;
      });

      rows.forEach(r => table.appendChild(r));
    });
  });
});
</script>


