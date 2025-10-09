<?php
global $use_itemsite_url, $current_time;
$use_itemsite_url = "/armory/index.php?searchType=iteminfo&item=";
$current_time = time();

/* ---------- helpers ---------- */
function item_manage_class($iclass) {
  $iclass_names = [
    'Consumable',      // 0
    'Container',       // 1
    'Weapon',          // 2
    'Gem',             // 3
    'Armor',           // 4
    'Reagent',         // 5
    'Projectile',      // 6
    'Trade Goods',     // 7
    'Generic',         // 8 (unused)
    'Recipe',          // 9
    'Money',           // 10 (unused)
    'Quiver',          // 11
    'Quest Item',      // 12
    'Key',             // 13
    'Permanent',       // 14
    'Miscellaneous'    // 15
  ];
  return $iclass_names[$iclass] ?? 'Unknown';
}

function parse_gold($n){return ['g'=>intval($n/10000),'s'=>intval(($n%10000)/100),'c'=>$n%100];}
function print_gold($g){
  global $currtmp;
  echo "<span class='gold-inline'>";
  if($g['g'])echo"{$g['g']}<img src='{$currtmp}/images/ah_system/gold.GIF' alt='g'> ";
  if($g['s'])echo"{$g['s']}<img src='{$currtmp}/images/ah_system/silver.GIF' alt='s'> ";
  if($g['c'])echo"{$g['c']}<img src='{$currtmp}/images/ah_system/copper.GIF' alt='c'>";
  echo"</span>";
}
function ah_print_gold($v){echo($v==='---')?$v:print_gold(parse_gold($v));}
function parse_time($n){return['h'=>intval($n/3600),'m'=>intval(($n%3600)/60),'s'=>$n%60];}
function ah_time_left($exp){
  global $current_time,$lang;
  $left=$exp-$current_time;
  if($left>0){$t=parse_time($left);
    if($t['h'])echo"{$t['h']}h ";if($t['m'])echo"{$t['m']}m ";if($t['s'])echo"{$t['s']}s";
  }else echo"<span class='expired'>{$lang['ah_expired']}</span>";
}
?>


<style>
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

/* Base look inherited from .ah-filter */
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


</style>


<?php builddiv_start(1, $lang['ah_auctionhouse']); ?>

<div class="modern-content">
  <!--<img src="<?php// echo $currtmp; ?>/images/banner1.jpg" alt="Auction House" class="ah-banner"/>-->

	<!--paganation-->
<?php if (!empty($numofpgs) && $numofpgs > 1): ?>
  <div class="pagination-controls">
    <div class="page-links">
      <?php
        // Build current base URL without &p= fragment
        $urlstring = preg_replace('/(&?p=\d+)/', '', $_SERVER['REQUEST_URI']);
        echo compact_paginate($p, $numofpgs, $urlstring);
      ?>
    </div>
    <div class="page-size-form">
  <?php render_page_size_form($items_per_page, ['filter'], false, false); ?>

    </div>
<div class="ah-filter-bar">
  <?php
    $filter = $_GET['filter'] ?? 'all';
    $filters = [
      'ally'  => $lang['ah_alliance'],
      'horde' => $lang['ah_horde'],
      'black' => $lang['ah_blackwater'],
      'all'   => $lang['all']
    ];

    foreach ($filters as $key => $label) {
      $active = ($filter === $key) ? 'is-active' : '';

      // PHP 7.x compatible faction class logic
      if ($key === 'ally')       $class = 'faction-alliance';
      elseif ($key === 'horde')  $class = 'faction-horde';
      elseif ($key === 'black')  $class = 'faction-blackwater';
      else                       $class = 'faction-neutral';

      echo "<a href='?n=server&sub=ah&filter={$key}' class='ah-filter {$class} {$active}'>{$label}</a>";
    }
  ?>
</div>


  </div>
<?php endif; ?>

<div class="wow-table ah-table">

  <div class="header">
    <div class="col sortable has-dropdown" data-sort="type">
      <?php echo $lang['ah_itemclass']; ?>
    </div>
    <div class="col sortable" data-sort="item">
      <?php echo $lang['ah_itemname']; ?>
    </div>
    <div class="col sortable" data-sort="qty">
      <?php echo $lang['ah_quantity']; ?>
    </div>
    <div class="col sortable" data-sort="time">
      <?php echo $lang['ah_time']; ?>
    </div>
    <div class="col sortable" data-sort="bid">
      <?php echo $lang['ah_currentbid']; ?>
    </div>
    <div class="col sortable" data-sort="buyout">
      <?php echo $lang['ah_buyout']; ?>
    </div>
  </div>

  <?php if (empty($ah_entry)): ?>
    <div class="row empty">AHBot failed to load!</div>

  <?php else: foreach ($ah_entry as $row): ?>
    <div class="row">
      <div class="col">
        <?php echo item_manage_class($row['class']); ?>
      </div>

      <div class="col">
        <a class="iqual<?php echo $row['quality']; ?>"
           href="<?php echo '/' . ltrim($use_itemsite_url, '/') . $row['item_entry']; ?>"
           target="_blank">
          <?php echo htmlspecialchars($row['itemname']); ?>
        </a>
      </div>

      <div class="col">
        <?php echo $row['quantity']; ?>
      </div>

      <div class="col">
        <?php ah_time_left($row['time']); ?>
      </div>

      <div class="col gold-cell">
        <?php ah_print_gold($row['currentbid']); ?>
      </div>

      <div class="col gold-cell">
        <?php ah_print_gold($row['buyout']); ?>
      </div>
    </div>
  <?php endforeach; endif; ?>

</div>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const headers = document.querySelectorAll(".wow-table .sortable");
  let sortStack = [];

  headers.forEach(header => {
    header.addEventListener("click", e => {
      // Skip clicks inside dropdowns
      if (e.target.closest(".dropdown")) return;

      const table = header.closest(".wow-table");
      const rows = Array.from(table.querySelectorAll(".row"))
        .filter(r => !r.classList.contains("header") && !r.classList.contains("empty"));

      // Determine column index relative to header
      const index = [...header.parentNode.children].indexOf(header);
      let state = header.dataset.state || "none";

      // Reset if shift not held
      if (!e.shiftKey) sortStack = [];
      sortStack = sortStack.filter(s => s.index !== index);

      // Toggle sort direction
      if (state === "none") state = "asc";
      else if (state === "asc") state = "desc";
      else state = "none";

      if (state !== "none") sortStack.push({ index, state });
      header.dataset.state = state;

      // Update header arrows (preserve dropdown titles)
      headers.forEach(h => {
        const s = sortStack.find(x => x.index === [...h.parentNode.children].indexOf(h));
        const arrow = s ? (s.state === "asc" ? " ▲" : " ▼") : "";

        if (h.classList.contains("has-dropdown")) {
          const title = h.querySelector(".sort-title");
          if (title) title.textContent = (h.dataset.sort || "").toUpperCase() + arrow;
        } else {
          const text = (h.dataset.sort || "").toUpperCase();
          // Strip existing arrows before reapplying
          h.textContent = text + arrow;
        }
      });

      // Sort rows
      rows.sort((a, b) => {
        for (const s of sortStack) {
          const asc = s.state === "asc";
          const aCell = a.querySelectorAll(".col")[s.index];
          const bCell = b.querySelectorAll(".col")[s.index];

          const aVal = (aCell?.innerText || "").trim().toLowerCase();
          const bVal = (bCell?.innerText || "").trim().toLowerCase();

          const cmp = aVal.localeCompare(bVal, undefined, { numeric: true });
          if (cmp !== 0) return asc ? cmp : -cmp;
        }
        return 0;
      });

      // Re-append sorted rows
      const frag = document.createDocumentFragment();
      rows.forEach(r => frag.appendChild(r));
      table.appendChild(frag);
    });
  });
});


// ==========================
// FILTER DROPDOWN (AH only)
// ==========================
document.addEventListener("click", e => {
  const opt = e.target.closest(".option[data-filter]");
  if (!opt) return;

  const filter = opt.dataset.filter;
  const table = opt.closest(".wow-table") || document.querySelector(".wow-table.ah-table");
  if (!table) return;

  const rows = table.querySelectorAll(".row");
  rows.forEach(row => {
    if (row.classList.contains("header") || row.classList.contains("empty")) return;
    const typeCol = row.querySelector(".col:first-child");
    const classIndex = typeCol ? typeCol.dataset.classIndex : "";
    row.style.display = (filter === "all" || classIndex == filter) ? "" : "none";
  });
});
</script>
