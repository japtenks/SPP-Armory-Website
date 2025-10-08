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

.ah-filter-bar{text-align:center;margin:10px 0 20px;}
.ah-filter{color:#aaa;text-decoration:none;margin:0 8px;font-weight:bold;}
.ah-filter.is-active,.ah-filter:hover{color:#ffcc66;}
.ah-table-header,.ah-row{
  display:grid;
  grid-template-columns:130px 250px 70px 120px 100px 120px 120px 120px;
  align-items:center;text-align:center;padding:8px 0;border-bottom:1px solid #222;
}
.ah-table-header{background:linear-gradient(to bottom,#1a1a1a,#101010);color:#ffcc66;font-weight:bold;text-transform:uppercase;}
.ah-row:nth-child(even){background:rgba(255,255,255,0.04);}
.ah-row:hover{background:rgba(255,255,255,0.08);}
.ah-row.empty{text-align:center;padding:12px;color:#ff6666;}
.ah-row .col:first-child{text-align:left;padding-left:10px;}
.gold-cell{text-align:right;padding-right:10px;}
.expired{color:#c33;font-weight:bold;}
a.iqual0{color:#9e9e9e;}a.iqual1{color:#eee;}a.iqual2{color:#00ff10;}
a.iqual3{color:#0070dd;}a.iqual4{color:#a335ee;}a.iqual5{color:#ff8000;}
a.iqual6{color:#e60000;}a[class^="iqual"]:hover{color:#fff;}
.pagination-controls{text-align:center;margin-top:12px;color:#ccc;}
.has-dropdown { position: relative; cursor: pointer; }
.ah-dropdown {
  display: none;
  position: absolute;
  top: 100%; left: 0;
  background: #181818;
  border: 1px solid #333;
  border-radius: 4px;
  min-width: 160px;
  z-index: 20;
}
.has-dropdown:hover .ah-dropdown { display: block; }
.ah-option {
  padding: 5px 10px;
  color: #ddd;
  white-space: nowrap;
}
.ah-option:hover {
  background: #333;
  color: #ffcc66;
}

</style>

<?php builddiv_start(1, $lang['ah_auctionhouse']); ?>

<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/banner1.jpg" alt="Auction House" class="ah-banner"/>


    <div class="ah-filter-bar">
      <?php
        $filter=$_GET['filter']??'all';
        $filters=['ally'=>$lang['ah_alliance'],'horde'=>$lang['ah_horde'],'black'=>$lang['ah_blackwater'],'all'=>$lang['all']];
        foreach($filters as $key=>$label){
          $active=($filter===$key)?'is-active':'';
          echo"<a href='?n=server&sub=ah&filter={$key}' class='ah-filter {$active}'>{$label}</a>";
        }
      ?>
    </div>

    <div class="ah-table">
<div class="ah-table-header">
  <div class="col sortable has-dropdown" data-sort="type">
    <?php echo $lang['ah_itemclass']; ?>

  </div>
  <div class="col sortable" data-sort="item"><?php echo $lang['ah_itemname']; ?></div>
  <div class="col sortable" data-sort="qty"><?php echo $lang['ah_quantity']; ?></div>
  <div class="col sortable" data-sort="time"><?php echo $lang['ah_time']; ?></div>
  <div class="col sortable" data-sort="bid"><?php echo $lang['ah_currentbid']; ?></div>
  <div class="col sortable" data-sort="buyout"><?php echo $lang['ah_buyout']; ?></div>
</div>



      <?php if (empty($ah_entry)): ?>
        <div class="ah-row empty">AHBot failed to load!</div>
      <?php else: foreach ($ah_entry as $row): ?>
        <div class="ah-row">
          <div class="col"><?php echo item_manage_class($row['class']); ?></div>
          <div class="col">
            <a class="iqual<?php echo $row['quality']; ?>"
               href="<?php echo '/' . ltrim($use_itemsite_url, '/') . $row['item_entry']; ?>"
               target="_blank"><?php echo htmlspecialchars($row['itemname']); ?></a>
          </div>
          <div class="col"><?php echo $row['quantity']; ?></div>
          <!--<div class="col"><?php// echo htmlspecialchars($row['seller']); ?></div>-->
          <div class="col"><?php ah_time_left($row['time']); ?></div>
          <!--<div class="col"><?php// echo htmlspecialchars($row['buyer']); ?></div>-->
          <div class="col gold-cell"><?php ah_print_gold($row['currentbid']); ?></div>
          <div class="col gold-cell"><?php ah_print_gold($row['buyout']); ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <?php if (!empty($numofpgs) && $numofpgs > 1): ?>
    <div class="pagination-controls">
      <?php echo $lang['page']; ?>:&nbsp;
      <?php
        for ($pnum=1;$pnum<=$numofpgs;$pnum++){
          $url=preg_replace('/(&?pid=\d+)/','',$_SERVER['REQUEST_URI']);
          echo(isset($_GET["pid"])&&$_GET["pid"]==$pnum)?'['.$pnum.'] ':'<a href="'.$url.'&pid='.$pnum.'">'.$pnum.'</a> ';
        }
      ?>
    </div>
    <?php endif; ?>
  </div>


</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const headers = document.querySelectorAll(".ah-table-header .sortable");
  let sortStack = [];

  headers.forEach(header => {
    header.addEventListener("click", e => {
      // Skip clicks inside dropdown
      if (e.target.closest(".ah-dropdown")) return;

      const table = header.closest(".ah-table");
      const rows = Array.from(table.querySelectorAll(".ah-row"));

      const index = [...header.parentNode.children].indexOf(header);
      let state = header.dataset.state || "none";

      if (!e.shiftKey) sortStack = [];
      sortStack = sortStack.filter(s => s.index !== index);

      if (state === "none") state = "asc";
      else if (state === "asc") state = "desc";
      else state = "none";

      if (state !== "none") sortStack.push({ index, state });
      header.dataset.state = state;

      // Update header arrows (preserve dropdown HTML)
      headers.forEach(h => {
        const s = sortStack.find(x => x.index === [...headers].indexOf(h));
        if (!h.classList.contains("has-dropdown")) {
          h.textContent = h.dataset.sort.toUpperCase() + (s ? (s.state === "asc" ? " ▲" : " ▼") : "");
        } else {
          const title = h.querySelector(".sort-title");
          if (title) title.textContent = h.dataset.sort.toUpperCase() + (s ? (s.state === "asc" ? " ▲" : " ▼") : "");
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

      // Re-append
      const frag = document.createDocumentFragment();
      rows.forEach(r => frag.appendChild(r));
      table.appendChild(frag);
    });
  });
});

// Dropdown filter
document.addEventListener("click", e => {
  const opt = e.target.closest(".ah-option");
  if (!opt) return;
  const filter = opt.dataset.filter;
  document.querySelectorAll(".ah-row").forEach(row => {
    const typeCol = row.querySelector(".col:first-child");
    const classIndex = typeCol ? typeCol.dataset.classIndex : "";
    row.style.display = (filter === "all" || classIndex == filter) ? "" : "none";
  });
});
</script>
