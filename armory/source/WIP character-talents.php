<?php
if (!defined('Armory')) { exit; }

// ---------- helpers (local to this file) ----------

/** true if a table exists in current DB */
function tbl_exists($conn, $table) {
    return (bool) execute_query(
        $conn,
        "SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".addslashes($table)."'
         LIMIT 1",
        2
    );
}

/** get the 3 talent tabs (id, name, tab_number) for a class id */
function get_tabs_for_class($classId) {
    $mask = 1 << ((int)$classId - 1);
    return execute_query(
        'armory',
        "SELECT `id`, `name`, `tab_number`
         FROM `dbc_talenttab`
         WHERE `refmask_chrclasses` = {$mask}
         ORDER BY `tab_number` ASC",
        0
    ) ?: [];
}

/** current rank for a talent (prefer character_talent; fall back to character_spell) */
function current_rank_for_talent($guid, $talRow, $rankMap, $hasCharSpell) {
    $tid = (int)$talRow['id'];
    if (isset($rankMap[$tid])) {
        return (int)$rankMap[$tid];
    }
    if ($hasCharSpell) {
        // classic-style approximation: check learned rank spells from 5..1
        for ($r = 5; $r >= 1; $r--) {
            $spell = (int)$talRow["rank{$r}"];
            if ($spell > 0) {
                $has = execute_query(
                    'char',
                    "SELECT 1 FROM `character_spell`
                     WHERE `guid` = {$guid} AND `spell` = {$spell} AND `disabled` = 0
                     LIMIT 1",
                    2
                );
                if ($has) return $r;
            }
        }
    }
    return 0;
}



// ---------- build data ----------

$tabs = get_tabs_for_class($stat['class']);

// optional maps (exist on TBC/WotLK cores)
$rankMap = [];
$hasCharTalent = tbl_exists('char', 'character_talent');
if ($hasCharTalent) {
    $rows = execute_query(
        'char',
        "SELECT `talent_id`, `current_rank`
         FROM `character_talent`
         WHERE `guid` = ".(int)$stat['guid'],
        0
    );
    foreach ((array)$rows as $r) {
        // current_rank is 0-based in many cores; store as 1-based
        $rankMap[(int)$r['talent_id']] = ((int)$r['current_rank']) + 1;
    }
}
$hasCharSpell = tbl_exists('char', 'character_spell');


?>

<div class="parchment-top"><div class="parchment-content">

  <style>
    .talent-trees 	{ display:flex; justify-content:center; gap:30px; margin:10px auto; }
    .talent-tree  	{ flex:1; text-align:center; }
    .talent-h     	{ margin:0 0 10px; font-size:16px; font-weight:bold; color:#fff7d2; }
	
	.talent-flex	{ /* container stays exact width for 4 columns */
					--cell: 40px;   /* or 44 */
					--gap: 3px;
					display:flex; flex-wrap:wrap; gap:var(--gap);
					justify-content:center; margin:0 auto;
					width:calc(var(--cell) * 4 + var(--gap) * 3);
					}

	.talent-cell{ /* visible talent */
	  width:var(--cell); height:var(--cell);
	  background:#2a2a2a; border-radius:6px;
	  box-shadow:inset 0 0 0 1px #444;
	  display:flex; align-items:center; justify-content:center;
	  color:#ddd; font:12px/1.2 Trebuchet MS,Arial,sans-serif;
	}
	.talent-cell.placeholder{	/* placeholder: occupies space but is invisible and non-interactive */
	  visibility:hidden;               /* key: keeps space, hides box */
	  background:transparent;
	  box-shadow:none;
	  pointer-events:none;
	}
	.talent-rank{ padding:2px 6px; border-radius:10px; background:#0007; font-weight:bold; }	
  </style>

  <?php if (empty($tabs)): ?>
    <em>No talent tabs found for this class.</em>
  <?php else: ?>
    <div class="talent-trees">
      <?php foreach ($tabs as $t): ?>
        <?php
          $tabId   = (int)$t['id'];
          $tabName = (string)$t['name'];
          $points  = (int)talentCounting($stat['guid'], $tabId);

          // all talents in this tab
          $talents = execute_query(
              'armory',
              "SELECT `id`, `row`, `col`, `rank1`, `rank2`, `rank3`, `rank4`, `rank5`
               FROM `dbc_talent`
               WHERE `ref_talenttab` = {$tabId}
               ORDER BY `row`, `col`",
              0
          ) ?: [];
        ?>
        <div class="talent-tree">
          <h4 class="talent-h"><?php echo htmlspecialchars($tabName); ?> (<?php echo $points; ?>)</h4>

          <div class="talent-flex" style="--cell: 44px;">
		<?php
  // index talents by position and find deepest used row
  $byPos = []; $maxRow = 0;
  foreach ($talents as $tal) {
    $r = (int)$tal['row']; $c = (int)$tal['col'];
    $byPos["$r:$c"] = $tal;
    if ($r > $maxRow) $maxRow = $r;
  }

  $cols = 4;
  for ($r = 0; $r <= $maxRow; $r++) {
    for ($c = 0; $c < $cols; $c++) {
      if (!isset($byPos["$r:$c"])) {
        // invisible placeholder keeps the grid aligned
        echo '<div class="talent-cell placeholder"></div>';
        continue;
      }

      $found = $byPos["$r:$c"];
      // max ranks 1..5 present in DBC
      $max = 0; for ($x=1; $x<=5; $x++) if (!empty($found["rank$x"])) $max = $x;
      $cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);

      echo '<div class="talent-cell"><span class="talent-rank">'.(int)$cur.'/'.(int)$max.'</span></div>';
    }
  }
?>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div></div>
