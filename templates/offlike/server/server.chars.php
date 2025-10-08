<style>

.character-table {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 1000px;
  margin: 20px auto;
  background: #0d0d0d url('<?php echo $currtmp; ?>/images/stone-dark.jpg') repeat;
  border: 1px solid #222;
  border-radius: 10px;
  overflow: hidden;
  box-sizing: border-box;
  font-family: 'Trebuchet MS', sans-serif;
  color: #ccc;
}
html, body {
  overflow-x: hidden;
}

.char-header, .char-row {
  display: grid;
  grid-template-columns: 230px 80px 80px 80px 60px auto;
  align-items: center;
  gap: 6px;
  text-align: center;
  padding: 10px 0;
  width: 100%;
}
.char-header {
  background: linear-gradient(to bottom, #1a1a1a 0%, #101010 100%);
  color: #ffcc66;
  font-weight: bold;
  text-transform: uppercase;
  border-bottom: 2px solid #2a2a2a;
}
.char-row:nth-child(even) { background: rgba(255,255,255,0.04); }
.char-row:hover { background: rgba(255,255,255,0.08); }
.char-header .col:last-child,
.char-row .col:last-child {
  text-align: left;
  padding-left: 12px;
}
.char-header .col:nth-child(1)
{  text-align: left;
  padding-left: 18px;	
}



/* === NAME COLUMN === */
.col.name {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  padding-left: 14px;
}



.col.name a {
  text-decoration: none;
  font-weight: bold;
  transition: color .2s, text-shadow .2s;
}
.col.name a:hover { color: #fff3a0; text-shadow: 0 0 8px #ffcc66; }

/* === ICONS === */
.circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #222;
  background-color: #000;
  transition: .3s;
}
.circle:hover {
  transform: scale(1.08);
  box-shadow: 0 0 12px currentColor, 0 0 24px rgba(255,255,255,0.2);
}

/* === CLASS COLOR VARIABLES === */
.class-warrior { --class-color:#C79C6E; }
.class-mage { --class-color:#69CCF0; }
.class-priest { --class-color:#FFFFFF; }
.class-hunter { --class-color:#ABD473; }
.class-rogue { --class-color:#FFF569; }
.class-warlock { --class-color:#9482C9; }
.class-paladin { --class-color:#F58CBA; }
.class-druid { --class-color:#FF7D0A; }
.class-shaman { --class-color:#0070DE; }
.class-deathknight { --class-color:#C41F3B; }

/* === APPLY CLASS COLOR (subtle accent) === */
[class*="class-"] .portrait,
[class*="class-"] .class-icon {
  color: var(--class-color);
  border-color: var(--class-color);
  box-shadow: 0 0 4px var(--class-color, #888);
  transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

/* Slight hover pop only */
[class*="class-"]:hover .portrait,
[class*="class-"]:hover .class-icon {
  box-shadow: 0 0 10px var(--class-color, #aaa);
  filter: brightness(1.1);
}

/*name in class color*/
[class*="class-"] a {
  color: var(--class-color);
  text-shadow: none;
  border: none;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Alliance border */
.col img[title="Alliance"] {
  border: 2px solid #0055ff;
  border-radius: 50%;
  box-shadow: 0 0 8px #0055ff;
}

/* Horde border */
.col img[title="Horde"] {
  border: 2px solid #aa0000;
  border-radius: 50%;
  box-shadow: 0 0 8px #aa0000;
}

/* Class colors */
.col img[title="Warrior"] { border: 2px solid #C79C6E; }
.col img[title="Paladin"] { border: 2px solid #F58CBA; }
.col img[title="Hunter"]  { border: 2px solid #ABD473; }
.col img[title="Rogue"]   { border: 2px solid #FFF569; }
.col img[title="Priest"]  { border: 2px solid #FFFFFF; }
.col img[title="Shaman"]  { border: 2px solid #0070DE; }
.col img[title="Mage"]    { border: 2px solid #69CCF0; }
.col img[title="Warlock"] { border: 2px solid #9482C9; }
.col img[title="Druid"]   { border: 2px solid #FF7D0A; }

/* Alliance race icons */
img.circle[alt="race"][src*="race/1-"],
img.circle[alt="race"][src*="race/3-"],
img.circle[alt="race"][src*="race/4-"],
img.circle[alt="race"][src*="race/7-"],
img.circle[alt="race"][src*="race/11-"] {
  border: 1px solid #0055ff;
  box-shadow: 0 0 4px #0055ff;
  border-radius: 50%;
}

/* Horde race icons */
img.circle[alt="race"][src*="race/2-"],
img.circle[alt="race"][src*="race/5-"],
img.circle[alt="race"][src*="race/6-"],
img.circle[alt="race"][src*="race/8-"], 
img.circle[alt="race"][src*="race/10-"] {
  border: 1px solid #aa0000;
  box-shadow: 0 0 4px #aa0000;
  border-radius: 50%;
}



/* === RESPONSIVE DROPS === */
@media (max-width: 750px) {
  .char-header .col:nth-child(4), .char-row .col:nth-child(4) { display: none; }
  .char-header, .char-row { grid-template-columns: 230px 80px 80px 60px 1fr; }
}
@media (max-width: 670px) {
  .char-header .col:nth-child(3), .char-row .col:nth-child(3) { display: none; }
  .char-header, .char-row { grid-template-columns: 120px 80px 60px 1fr; }

}
@media (max-width: 390px) {
  .char-header .col:nth-child(2), .char-row .col:nth-child(2) { display: none; }
  .char-header, .char-row { grid-template-columns: 120px 60px 1fr; }

}





</style>
<!--Pagination CSS Block -->
<style>
/* === Pagination Wrapper === */
.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  padding: 12px 18px;
  background: #111;
  border: 1px solid #222;
  border-radius: 8px;
  color: #ccc;
  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;
  margin-left: auto;
  margin-right: auto;
}


/* === Page Links === */
.page-links {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.page-links a,
.page-links span {
  color: #ffcc66;
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 6px;
  font-weight: bold;
  transition: background 0.2s, color 0.2s;
}

.page-links a:hover {
  background: #ffcc66;
  color: #000;
}

.page-links .current {
  background: #ffcc66;
  color: #000;
  font-weight: bold;
  box-shadow: 0 0 6px #ffcc66;
}

.dots {
  color: #ffcc66;
  padding: 0 6px;
  user-select: none;
}

/* === Page Buttons === */
.page-btn {
  color: #ffcc66;
  background: transparent;
  border: 1px solid transparent;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.2s;
  font-weight: bold;
  text-decoration: none;
}

.page-btn:hover {
  background: #ffcc66;
  color: #000;
  border-color: #ffcc66;
}

.page-btn.active {
  background: #ffcc66;
  color: #000;
  border-color: #ffcc66;
  cursor: default;
}

.page-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* === Per Page Form === */
.page-size-form {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #ccc;
  font-family: 'Trebuchet MS', sans-serif;
  font-size: 14px;
}

.page-size-form label {
  color: #ffcc66;
  font-weight: bold;
}

.page-size-form select {
  background: #1a1a1a;
  color: #ffcc66;
  border: 1px solid #444;
  border-radius: 4px;
  padding: 4px 8px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.2s ease;
}

.page-size-form select:hover {
  border-color: #ffcc66;
  box-shadow: 0 0 6px #ffcc66;
}

.page-size-form span {
  color: #aaa;
}

.pagination-controls,
.character-table {
  max-width: 1000px;
  margin: 0 auto;
}

.character-table {
  width: 100%;
  border-collapse: collapse;
}

</style>

<?php builddiv_start(1, $lang['characters']); ?>
<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/banner1.jpg" alt="Auction House" class="ah-banner"/>

<!-- Pagination Controls -->
<div class="pagination-controls">
  <div class="page-links">
    <?php
    function compact_paginate($current, $total, $base) {
      $html = '';
      $range = 2;
      $show_first = $current > $range + 2;
      $show_last  = $current < $total - ($range + 1);

      if ($current > 1)
        $html .= '<a href="'.$base.'&p='.($current-1).'">« Prev</a> ';

      if ($show_first)
        $html .= '<a href="'.$base.'&p=1">1</a> … ';

      for ($i = max(1, $current-$range); $i <= min($total, $current+$range); $i++) {
        $active = $i == $current ? 'class="active"' : '';
        $html .= '<a '.$active.' href="'.$base.'&p='.$i.'">'.$i.'</a> ';
      }

      if ($show_last)
        $html .= '… <a href="'.$base.'&p='.$total.'">'.$total.'</a> ';

      if ($current < $total)
        $html .= '<a href="'.$base.'&p='.($current+1).'">Next »</a>';

      return $html;
    }

    echo compact_paginate($p, $pnum, $urlstring);
    ?>
  </div>

  <form method="get" class="page-size-form">
    <input type="hidden" name="n" value="server">
    <input type="hidden" name="sub" value="chars">

    <?php
    $persist = ['char', 'lvl', 'minlvl', 'maxlvl', 'class', 'race', 'p', 'show_bots'];
    foreach ($persist as $param) {
      if (isset($_GET[$param])) {
        echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($_GET[$param]) . '">';
      }
    }
    ?>

    <label for="per_page">Show:</label>
    <select id="per_page" name="per_page" onchange="this.form.submit()">
      <option value="10"  <?php if($items_per_page==10)  echo 'selected'; ?>>10</option>
      <option value="25"  <?php if($items_per_page==25)  echo 'selected'; ?>>25</option>
      <option value="50"  <?php if($items_per_page==50)  echo 'selected'; ?>>50</option>
      <option value="100" <?php if($items_per_page==100) echo 'selected'; ?>>100</option>
    </select>
    <span>characters per page</span>

    <label style="margin-left:10px;">
<input type="hidden" name="show_bots" value="0">
<label style="margin-left:10px;">
  <input type="checkbox" name="show_bots" value="1"
    onchange="this.form.submit()" <?php if(!empty($_GET['show_bots'])) echo 'checked'; ?>>
  Include bots
    </label>
  </form>
</div>



<div class="character-table">
  <div class="char-header">
    <div class="col sortable" data-sort="name">Name</div>
    <div class="col sortable" data-sort="race">Race</div>
    <div class="col sortable" data-sort="class">Class</div>
    <div class="col sortable" data-sort="faction">Faction</div>
    <div class="col sortable" data-sort="level">Level</div>
    <div class="col sortable" data-sort="location">Location</div>
  </div>

  <div class="char-body">
    <?php foreach ($item_res as $item): ?>
      <div class="char-row">
        <div class="col name class-<?php echo strtolower($MANG->characterInfoByID['character_class'][$item['class']]); ?>">
          <?php
          $portraitDir = "templates/offlike/images/portraits/wow-70/";
          $cacheDir    = "templates/offlike/cache/portraits/";
          if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

          $race   = $item['race'];
          $class  = $item['class'];
          $gender = $item['gender'];
          $guid   = $item['guid'];

          $pattern = sprintf("templates/offlike/images/portraits/wow-70/%d-%d-%d*.gif", $gender, $race, $class);
          $matches = glob($pattern);

          if ($matches && file_exists($matches[0])) {
              $selected = $matches[array_rand($matches)];
              $cache_file = $cacheDir . "portrait_{$guid}.gif";
              if (!file_exists($cache_file)) copy($selected, $cache_file);
              $portrait_path = "templates/offlike/cache/portraits/portrait_{$guid}.gif";
          } else {
              $portrait_path = sprintf("templates/offlike/images/portraits/wow-70/%d-%d-0.gif", $gender, $race);
          }
          ?><a href="armory/index.php?searchType=profile&character=<?php echo $item['name']; ?>&realm=<?php echo $realm_info_new['name']; ?>">
          <img src="<?php echo $portrait_path; ?>" 
			   class="circle portrait" 
			   alt="portrait"
			   title=<?php echo $item['name']; ?>
			   >
          
            <?php echo $item['name']; ?>
          </a>
        </div>

        <div class="col">
          <img src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $item['race'].'-'.$item['gender']; ?>.jpg"
               alt="race"
               title="<?php echo $MANG->characterInfoByID['character_race'][$item['race']]; ?>"
               class="circle">
        </div>

        <div class="col">
          <img src="<?php echo $currtmp; ?>/images/icon/class/<?php echo $item['class']; ?>.jpg"
               alt="class"
               title="<?php echo $MANG->characterInfoByID['character_class'][$item['class']]; ?>"
               class="circle">
        </div>

        <div class="col">
          <?php
          $alliance_races = [1, 3, 4, 7, 11, 22, 25, 29];
          $faction = in_array($item['race'], $alliance_races) ? 'alliance' : 'horde';
          ?>
          <img src="/templates/offlike/images/Modern/logo-<?php echo $faction; ?>.png"
               class="circle"
			   title="<?php echo ucfirst($faction); ?>"
               alt="<?php echo ucfirst($faction); ?>">
        </div>

        <div class="col"><?php echo $item['level']; ?></div>
        <div class="col"><?php echo htmlspecialchars($item['pos']); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Pagination Controls -->
<div class="pagination-controls">
  <div class="page-links">
    <?php
echo compact_paginate($p, $pnum, $urlstring);
?>

  </div>

  <form method="get" class="page-size-form">
    <input type="hidden" name="n" value="server">
    <input type="hidden" name="sub" value="chars">
    <?php
    $persist = ['char', 'lvl', 'minlvl', 'maxlvl', 'class', 'race', 'p'];
    foreach ($persist as $param) {
      if (!empty($_GET[$param])) {
        echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($_GET[$param]) . '">';
      }
    }
    ?>
    <label for="per_page">Show:</label>
    <select id="per_page" name="per_page" onchange="this.form.submit()">
      <option value="10"  <?php if($items_per_pages==10)  echo 'selected'; ?>>10</option>
      <option value="25"  <?php if($items_per_pages==25)  echo 'selected'; ?>>25</option>
      <option value="50"  <?php if($items_per_pages==50)  echo 'selected'; ?>>50</option>
      <option value="100" <?php if($items_per_pages==100) echo 'selected'; ?>>100</option>
    </select>
    <span>characters per page</span>
  </form>
</div>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const headers = document.querySelectorAll(".char-header .sortable");
  let sortStack = [];

  headers.forEach(header => {
    header.addEventListener("click", e => {
      const table = header.closest(".character-table");
      const body = table.querySelector(".char-body");
      const index = [...header.parentNode.children].indexOf(header);
      const rows = Array.from(body.querySelectorAll(".char-row"));

      // Find or create entry
      let state = header.dataset.state || "none";
      if (!e.shiftKey) sortStack = []; // reset if not holding Shift
      sortStack = sortStack.filter(s => s.index !== index);
      if (state === "none") state = "asc";
      else if (state === "asc") state = "desc";
      else state = "none";
      if (state !== "none") sortStack.push({ index, state });
      header.dataset.state = state;

      // Update header arrows
      headers.forEach(h => {
        const s = sortStack.find(x => x.index === [...headers].indexOf(h));
        h.textContent = h.dataset.sort.toUpperCase() + (s ? (s.state === "asc" ? " ▲" : " ▼") : "");
      });

      // Sort rows by priority
      rows.sort((a, b) => {
        for (const s of sortStack) {
          const asc = s.state === "asc";
          const aCell = a.querySelectorAll(".col")[s.index];
          const bCell = b.querySelectorAll(".col")[s.index];
          const aImg = aCell?.querySelector("img");
          const bImg = bCell?.querySelector("img");
          const aLink = aCell?.querySelector("a");
          const bLink = bCell?.querySelector("a");

          let aText = aImg ? (aImg.title || aImg.alt || "") :
                     aLink ? aLink.textContent.trim() :
                     (aCell?.innerText.trim() || "");
          let bText = bImg ? (bImg.title || bImg.alt || "") :
                     bLink ? bLink.textContent.trim() :
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

      rows.forEach(row => body.appendChild(row));
    });
  });
});
</script>



