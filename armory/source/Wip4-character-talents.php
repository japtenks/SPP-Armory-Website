<?php
if (!defined('Armory')) { exit; }

/**
 * character-talents.php
 * Renders 3 talent trees with class/tab backgrounds behind a strict 4×N grid.
 * Needs tables:
 *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
 *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
 * Uses site helpers already present:
 *   execute_query($whichDb, $sql, $fetchMode, [$params])
 *   talentCounting($guid, $tabId)
 * Expects:
 *   $stat (character info array)
 *   $lang (translations)
 */

// ------------------------- config: asset bases -------------------------

// Browser URL base (absolute, from your working test URL)
if (!defined('TALENTS_ASSET_WEB')) {
  define('TALENTS_ASSET_WEB', '/armory/shared/global/talents');
}
// Filesystem base (this file is in armory/source/, assets are in armory/shared/global/talents)
if (!defined('TALENTS_ASSET_FS')) {
  define('TALENTS_ASSET_FS', realpath(__DIR__ . '/../shared/global/talents'));
}

// ------------------------- local helpers -------------------------

/** table exists in given connection */
function tbl_exists($conn, $table) {
    return (bool) execute_query(
        $conn,
        "SELECT 1
           FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '".addslashes($table)."'
          LIMIT 1",
        2
    );
}

/** tabs (id, name, tab_number) for a class id */
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

/** prefer character_talent; else derive from character_spell */
function current_rank_for_talent($guid, array $talRow, array $rankMap, $hasCharSpell) {
    $tid = (int)$talRow['id'];
    if (isset($rankMap[$tid])) {
        return (int)$rankMap[$tid]; // already 1-based
    }
    if ($hasCharSpell) {
        for ($r = 5; $r >= 1; $r--) {
            $spell = (int)$talRow["rank{$r}"];
            if ($spell > 0) {
                $has = execute_query(
                    'char',
                    "SELECT 1
                       FROM `character_spell`
                      WHERE `guid` = {$guid}
                        AND `spell` = {$spell}
                        AND `disabled` = 0
                      LIMIT 1",
                    2
                );
                if ($has) return $r;
            }
        }
    }
    return 0;
}
/** first non-zero rank spell id for a talent */
function first_rank_spell(array $tal) {
    for ($i = 1; $i <= 5; $i++) {
        $id = (int)$tal["rank{$i}"];
        if ($id) return $id;
    }
    return 0;
}

/** Return spell info for a talent row (pick highest non-zero rank) */
		
		
		function spell_info_for_talent(array $talRow) {
			// find highest non-zero rank spell
			$spellId = 0;
			for ($r = 5; $r >= 1; $r--) {
				if (!empty($talRow["rank{$r}"])) { 
					$spellId = (int)$talRow["rank{$r}"]; 
					break; 
				}
			}
			if (!$spellId) return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];

			// join dbc_spellicon by ref_spellicon
			$sql = "
				SELECT 
					s.`name`        AS sname,
					s.`description` AS sdesc,
					i.`name`        AS sicon
				FROM `dbc_spell` s
				LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
				WHERE s.`id` = {$spellId}
				LIMIT 1
			";

			$row = execute_query('armory', $sql, 1);

			if (!$row) return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_pick_01'];

			$name = (string)($row['sname'] ?? '');
			$desc = (string)($row['sdesc'] ?? '');
			$icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($row['sicon'] ?? '')));

			if ($icon === '') $icon = 'inv_pick_01';
			return ['name' => $name, 'desc' => $desc, 'icon' => $icon];
		}


/** Build a web path to the icon file */
// Example: armory/shared/icons/ability_ambush.jpg
			
function icon_url($iconBase) {
    
    return "armory/shared/icons/{$iconBase}.jpg";
	
}

/** pick background for class + tabNumber (0..2) */
function talent_bg_for($classId, $tabNumber) {
    static $classSlug = [
        1=>'warrior', 2=>'paladin', 3=>'hunter', 4=>'rogue',
        5=>'priest',  7=>'shaman',  8=>'mage',   9=>'warlock', 11=>'druid',
    ];
    static $tabSlugs = [
        1=>['arms','fury','protection'],
        2=>['holy','protection','retribution'],
        3=>['beastmastery','marksmanship','survival'],
        4=>['assassination','combat','subtlety'],
        5=>['discipline','holy','shadow'],
        7=>['elemental','enhancement','restoration'],
        8=>['arcane','fire','frost'],
        9=>['affliction','demonology','destruction'],
        11=>['balance','feral','restoration'],
    ];

    $cSlug = $classSlug[(int)$classId] ?? null;
    $tSlug = isset($tabSlugs[(int)$classId][$tabNumber]) ? $tabSlugs[(int)$classId][$tabNumber] : null;

    $defaultWeb = TALENTS_ASSET_WEB . '/default.jpg';
    $defaultFs  = TALENTS_ASSET_FS  . '/default.jpg';

    if (!$cSlug || !$tSlug) return is_file($defaultFs) ? $defaultWeb : '';

    $baseWeb = TALENTS_ASSET_WEB . "/{$cSlug}/images";
    $baseFs  = TALENTS_ASSET_FS  . "/{$cSlug}/images";

    foreach (["{$tSlug}-bg2.jpg","{$tSlug}-bg.jpg","{$tSlug}.jpg"] as $name) {
        if (is_file("{$baseFs}/{$name}")) return "{$baseWeb}/{$name}";
    }
    if (is_file("{$baseFs}/default.jpg")) return "{$baseWeb}/default.jpg";
    return is_file($defaultFs) ? $defaultWeb : '';
}

/** icon endpoint (works with the icon.php you added earlier) */
 function spell_icon_src($spellId) {
    if ($spellId > 0) {
        return TALENTS_ASSET_WEB . "/icon.php?spell={$spellId}";
    }
    return TALENTS_ASSET_WEB . "/icons/inv_pick_01.jpg";
} 



// ------------------------- build data -------------------------

$tabs = get_tabs_for_class($stat['class']);

// rank map from character_talent if present (1-based)
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
        $rankMap[(int)$r['talent_id']] = ((int)$r['current_rank']) + 1; // 0-based -> 1-based
    }
}
$hasCharSpell = tbl_exists('char', 'character_spell');

?>
<div class="parchment-top"><div class="parchment-content">

<style>
  /* container that holds the 3 trees */
  .talent-trees{
    display:flex;
    justify-content:center;   /* center the trio */
    gap:1px;                 /* smaller gap so 3 fit */
    flex-wrap:nowrap;         /* keep in one row */
    max-width: 980px;         /* typical parchment width */
    margin:65px 0 0;
  }

.talent-flex { /* flex 4-column grid inside each tree */
	top: 12px;
  --cell: 48px;             /* bump this up from 36px */
  --gap:  10px;              /* slightly bigger spacing */
  width: calc(var(--cell) * 4 + var(--gap) * 3);
  margin: 0 auto;
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap);
  justify-content: center;
  position: relative;
}

.talent-tree {/* each tree column */
  position: relative;
  flex: 0 0 276px;          
  min-height: 540px;        
  background-position: center top;
  background-repeat: no-repeat;
  background-size: cover; 

  top:0px;
  border-radius: 10px;
}

  .talent-h{ 
		  margin:0px 0 8px;
		  font-size:18px; 
		  font-weight:bold; 
		  color:#fff7d2; 
		  text-align:center; 
		    position: absolute;
  top: -38px;         /* pull it above the tree box */
  left: 50%;
  transform: translateX(-50%);
  }



  .talent-cell{
    width:var(--cell); height:var(--cell);
    background:#2a2a2a; border-radius:6px;
    box-shadow:inset 0 0 0 1px #444;
    position:relative; overflow:hidden;
    display:flex; align-items:center; justify-content:center;
    color:#ddd; font:12px/1.2 "Trebuchet MS", Arial, sans-serif;
  }
  .talent-cell.placeholder{ visibility:hidden; background:transparent; box-shadow:none; pointer-events:none; }

  .talent-icon{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; filter:grayscale(100%); }
  .talent-rank{ position:absolute; right:4px; bottom:4px; padding:2px 6px; border-radius:10px; background:#000a; font-weight:bold; }
  
 
  .talent-rank{ ... }

  /* Tooltip styles */
  
  .talent-tt {
    position: fixed;
    z-index: 9999;
    max-width: 320px;
    color: #fff;
    background: #1b1b1b;
    border: 1px solid #3a3a3a;
    box-shadow: 0 8px 24px rgba(0,0,0,.45);
    border-radius: 6px;
    font: 13px/1.35 "Trebuchet MS", Arial, sans-serif;
    padding: 10px 12px;
    pointer-events: none;
	
  }
  .talent-tt h5 {
    margin: 0 0 6px 0;
    font-size: 14px;
    font-weight: 700;
    display:flex; align-items:center; gap:8px;
  }
  .talent-tt h5 .ico {
    width: 22px; height: 22px; flex: 0 0 22px;
    border-radius: 4px; background-size: cover; background-position: center;
    box-shadow: inset 0 0 0 1px #555;
  }
  .talent-tt p { margin: 0; white-space: normal; }



  
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
          $bgUrl   = talent_bg_for((int)$stat['class'], (int)$t['tab_number']);

          // all talents in this tab
          $talents = execute_query(
              'armory',
              "SELECT `id`, `row`, `col`, `rank1`, `rank2`, `rank3`, `rank4`, `rank5`
                 FROM `dbc_talent`
                WHERE `ref_talenttab` = {$tabId}
                ORDER BY `row`, `col`",
              0
          ) ?: [];

          // index by row:col and detect deepest used row
          $byPos = []; $maxRow = 0;
          foreach ($talents as $tal) {
              $r = (int)$tal['row']; $c = (int)$tal['col'];
              $byPos["$r:$c"] = $tal;
              if ($r > $maxRow) $maxRow = $r;
          }
        ?>
    
        <div class="talent-tree" style="background-image:url('<?php echo htmlspecialchars($bgUrl); ?>');">
          <h4 class="talent-h"><?php echo htmlspecialchars($tabName); ?> (<?php echo $points; ?>)</h4>

          <div class="talent-flex">
            <?php
              $cols = 4;
              for ($r = 0; $r <= $maxRow; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                  if (!isset($byPos["$r:$c"])) {
                    echo '<div class="talent-cell placeholder"></div>';
                    continue;
                  }
                  $found = $byPos["$r:$c"];
				  
                  // maximum ranks present in DBC (1..5)
                  $max = 0; for ($x=1; $x<=5; $x++) if (!empty($found["rank$x"])) $max = $x;
                  
				  // current rank for this character
				  $cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);
                  
				  // tooltip info
				  $spellId = first_rank_spell($found);
                  $iconSrc = spell_icon_src($spellId);
				  $sp = spell_info_for_talent($found);
				  $title = htmlspecialchars($sp['name']);
				  $desc  = htmlspecialchars($sp['desc']);
				  $icon  = icon_url($sp['icon']); // no escape—used in CSS url(), escape in HTML attribute
		

			echo '<div class="talent-cell"
					data-tt-title="'.$title.'"
					data-tt-desc="'.$desc.'"
					data-tt-icon="'.htmlspecialchars($icon).'">
					<span class="talent-rank">'.(int)$cur.'/'.(int)$max.'</span>
				</div>';
                }
              }
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div></div>

<script>
(function(){
  const tt = document.createElement('div');
  tt.className = 'talent-tt';
  tt.style.display = 'none';
  document.body.appendChild(tt);

  let showTimer = null;

  function show(ev, el) {
    const title = el.getAttribute('data-tt-title') || '';
    const desc  = el.getAttribute('data-tt-desc')  || '';
    const icon  = el.getAttribute('data-tt-icon')  || '';
    tt.innerHTML = `
      <h5><span class="ico" style="background-image:url('${icon}')"></span>${title}</h5>
      <p>${desc}</p>
    `;
    tt.style.display = 'block';
    move(ev);
  }
  function hide() {
    clearTimeout(showTimer);
    tt.style.display = 'none';
  }
  function move(ev){
    const pad = 16;
    let x = ev.clientX + pad, y = ev.clientY + pad;
    const r = tt.getBoundingClientRect(), vw = innerWidth, vh = innerHeight;
    if (x + r.width  > vw) x = ev.clientX - r.width  - pad;
    if (y + r.height > vh) y = ev.clientY - r.height - pad;
    tt.style.left = x + 'px';
    tt.style.top  = y + 'px';
  }

  document.addEventListener('mouseover', e => {
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    clearTimeout(showTimer);
    showTimer = setTimeout(() => show(e, el), 80);
  });
  document.addEventListener('mousemove', e => {
    if (tt.style.display !== 'none') move(e);
  });
  document.addEventListener('mouseout', e => {
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    if (!e.relatedTarget || !el.contains(e.relatedTarget)) hide();
  });
  window.addEventListener('scroll', hide, {passive:true});
})();
</script>

