
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



<?php builddiv_start(1, $lang['characters']); ?>
<div class="modern-content">


<!-- Pagination Controls -->
<div class="pagination-controls">
  <div class="page-links">
    <?php echo compact_paginate($p, $pnum, $urlstring); ?>
  </div>
  <div class="page-size-form">
    <?php render_page_size_form($items_per_page);  ?>
  </div>
</div>


<div class="wow-table character-table">

  <div class="header">
    <div class="col sortable" data-sort="name">Name</div>
    <div class="col sortable" data-sort="race">Race</div>
    <div class="col sortable" data-sort="class">Class</div>
    <div class="col sortable" data-sort="faction">Faction</div>
    <div class="col sortable" data-sort="level">Level</div>
    <div class="col sortable" data-sort="location">Location</div>
  </div>

  <?php foreach ($item_res as $item): ?>
    <div class="row">
      <div class="col name class-<?php echo strtolower($MANG->characterInfoByID['character_class'][$item['class']]); ?>">

        <?php
        $portraitDir = "templates/offlike/images/portraits/wow-70/";
        $cacheDir    = "templates/offlike/cache/portraits/";
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

        $race   = $item['race'];
        $class  = $item['class'];
        $gender = $item['gender'];
        $guid   = $item['guid'];

        $pattern = sprintf("%s%d-%d-%d*.gif", $portraitDir, $gender, $race, $class);
        $matches = glob($pattern);

        if ($matches && file_exists($matches[0])) {
            $selected = $matches[array_rand($matches)];
            $cache_file = $cacheDir . "portrait_{$guid}.gif";
            if (!file_exists($cache_file)) copy($selected, $cache_file);
            $portrait_path = $cache_file;
        } else {
            $portrait_path = sprintf("%s%d-%d-0.gif", $portraitDir, $gender, $race);
        }
        ?>

        <a href="armory/index.php?searchType=profile&character=<?php echo $item['name']; ?>&realm=<?php echo $realm_info_new['name']; ?>">
          <img src="<?php echo $portrait_path; ?>"
               class="circle portrait"
               alt="portrait"
               title="<?php echo htmlspecialchars($item['name']); ?>">
          <?php echo htmlspecialchars($item['name']); ?>
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
        <img src="templates/offlike/images/Modern/logo-<?php echo $faction; ?>.png"
             class="circle"
             title="<?php echo ucfirst($faction); ?>"
             alt="<?php echo ucfirst($faction); ?>">
      </div>

      <div class="col"><?php echo (int)$item['level']; ?></div>
      <div class="col"><?php echo htmlspecialchars($item['pos']); ?></div>
    </div>
  <?php endforeach; ?>

</div>

<!-- Pagination Controls -->
<div class="pagination-controls">
  <div class="page-links">
    <?php echo compact_paginate($p, $pnum, $urlstring); ?>
  </div>
  <div class="page-size-form">
    <?php render_page_size_form($items_per_page);  ?>
  </div>
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



