<?php
if (!defined('Armory')) { exit; }

/**
 * character-talents.php
 * - 3 talent trees side-by-side
 * - strict 4×N grid with invisible placeholders to keep columns aligned
 * - class/tab backgrounds
 * - hover tooltips from dbc_spell + dbc_spellicon
 *
 * Requires (your current schema):
 *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
 *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
 *   armory.dbc_spell(id, ref_spellicon, name, description, ...)
 *   armory.dbc_spellicon(id, name)
 */

// -------------------- asset bases (adjust only if paths change) --------------------
if (!defined('TALENTS_ASSET_WEB')) {
  define('TALENTS_ASSET_WEB', '/armory/shared/global/talents');
}
if (!defined('TALENTS_ASSET_FS')) {
  define('TALENTS_ASSET_FS', realpath(__DIR__ . '/../shared/global/talents'));
}

// -------------------- helpers --------------------

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
        return (int)$rankMap[$tid]; // already 1-based (we normalize below when building $rankMap)
    }
    if ($hasCharSpell) {
        for ($r = 5; $r >= 1; $r--) {
            $spell = (int)$talRow["rank{$r}"];
            if ($spell > 0) {
                $has = execute_query(
                    'char',
                    "SELECT 1
                       FROM `character_spell`
                      WHERE `guid` = ".(int)$guid."
                        AND `spell` = ".(int)$spell."
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

/** first non-zero rank spell id for a talent (not used for tooltip; kept if needed later) */
function first_rank_spell(array $tal) {
    for ($i = 1; $i <= 5; $i++) {
        $id = (int)$tal["rank{$i}"];
        if ($id) return $id;
    }
    return 0;
}

/**
 * Spell info (name/description/icon) for the talent row.
 * Picks the highest non-zero rank to represent the talent.
 * Uses your schema: dbc_spell(id, ref_spellicon, name, description), dbc_spellicon(id, name)
 */
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
			   s.`id`, s.`name`, s.`description`,
               s.`ref_spellduration`,
               s.`effect_basepoints_1`, s.`effect_basepoints_2`, s.`effect_basepoints_3`,
               s.`effect_amplitude_1`,
               i.`name` AS icon
          FROM `dbc_spell` s
          LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
         WHERE s.`id` = {$spellId}
         LIMIT 1
    ";

			$sp = execute_query('armory', $sql, 1);

    if (!$sp || !is_array($sp)) {
        // query failed or returned nothing
        return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];
    }

    // duration (seconds) from dbc_spellduration
    $durSecs = duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));

    // build text with token replacement; if helper missing or error, fall back
    $desc = '';
    try {
        $desc = replace_spell_tokens($sp, $durSecs);
    } catch (\Throwable $e) {
        $desc = (string)($sp['description'] ?? '');
    }

    // normalize icon base (file lookup is done elsewhere)
    $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
    if ($icon === '') $icon = 'inv_misc_questionmark';

    return [
        'name' => (string)($sp['name'] ?? 'Unknown'),
        'desc' => $desc,
        'icon' => $icon,
    ];
}

/** Build a web path to the icon file
    Example: armory/shared/icons/ability_ambush.jpg */
function icon_url($iconBase) {
    // absolute path so it works from any page depth
    return '/armory/shared/icons/' . $iconBase . '.jpg';
}


/** class/tab background by talent tab id (e.g. 161.jpg) */
function talent_bg_for_tab($tabId) {
    // Where the new images live
    $webBase = '/armory/shared/icon_talents';
    $fsBase  = realpath(__DIR__ . '/../shared/icon_talents');

    if (!$fsBase) {
        return ''; // folder missing; render no background
    }

    $file = (int)$tabId . '.jpg';
    $fs   = $fsBase . DIRECTORY_SEPARATOR . $file;

    if (is_file($fs)) {
        return $webBase . '/' . $file;   // e.g. /armory/shared/icon_talents/161.jpg
    }

    // Optional: class-wide default if you add one later:
    // $classDefault = $webBase . '/default.jpg';
    // if (is_file($fsBase . '/default.jpg')) return $classDefault;

    return ''; // no bg found; the tree will just have no background
}


//newest code

// Format seconds like "1.5 sec", "3 min", etc.
function fmt_secs($sec) {
    $sec = (int)$sec;
    if ($sec <= 0) return '';
    if ($sec < 60) return $sec . ' sec';
    $m = floor($sec / 60);
    $s = $sec % 60;
    return $s ? ($m . ' min ' . $s . ' sec') : ($m . ' min');
}

/** Pull duration seconds from dbc_spellduration (your schema) */
function duration_secs_from_id($id) {
    if (!$id) return 0;
    $row = execute_query(
        'armory',
        "SELECT `durationValue`, `ms_mod`, `ms_min`
           FROM `dbc_spellduration`
          WHERE `id` = ".(int)$id." LIMIT 1",
        1
    );
    if (!$row) return 0;

    // Prefer durationValue; if missing use the largest of ms_mod/ms_min
    $ms = (int)$row['durationValue'];
    if ($ms <= 0) {
        $ms = max((int)$row['ms_mod'], (int)$row['ms_min']);
    }
    return (int)round($ms / 1000);   // ms → sec
}


function replace_spell_tokens(array $row, int $durSecs = 0) {
    $desc = (string)($row['description'] ?? '');

    // $s1..$s3 → effect_basepoints + 1
    $map = [
        '$s1' => (int)$row['effect_basepoints_1'] + 1,
        '$s2' => (int)$row['effect_basepoints_2'] + 1,
        '$s3' => (int)$row['effect_basepoints_3'] + 1,
    ];
    foreach ($map as $k => $v) $desc = str_replace($k, (string)$v, $desc);

    // $d → duration seconds
    if ($durSecs > 0) {
        $desc = str_replace('$d', fmt_secs($durSecs), $desc);
    }

    // $m1 → amplitude_1 ms (period) if present
    if (!empty($row['effect_amplitude_1'])) {
        $desc = str_replace('$m1', fmt_secs((int)$row['effect_amplitude_1'] / 1000), $desc);
    }

    return $desc;
}









// -------------------- build data --------------------

$tabs = get_tabs_for_class($stat['class']);

// rank map from character_talent if present (normalize to 1-based)
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
/* ====== layout ====== */
.talent-trees{
  display:flex;
  justify-content:center;
  gap:12px;                
  flex-wrap:nowrap;
  max-width:980px;
  margin:65px auto 0;
}
.talent-tree{
  position:relative;
  flex:0 0 276px;
  min-height:540px;
  background-position:center;
  background-size:276px 540px; 
  border-radius:10px;
}
.talent-h{
  position:absolute;
  top:-38px;
  left:50%;
  transform:translateX(-50%);
  margin:0;
  font-size:18px;
  font-weight:bold;
  color:#fff7d2;
  text-align:center;
}
.talent-flex{
  --cell:48px;
  --gap:10px;
  position:relative;
  margin:0 auto;
  width:calc(var(--cell)*4 + var(--gap)*3);
  display:flex; flex-wrap:wrap; gap:var(--gap); justify-content:center;
  top:12px;
}

/* ====== cell, icon, states ====== */
.talent-cell{
  position:relative;
  width:var(--cell); height:var(--cell);
  border-radius:6px;
  background:#2a2a2a;                     /* fallback under icon */
  background-image:var(--icon);           /* icon is a CSS var from inline style */
  background-position:center;
  background-repeat:no-repeat;
  background-size:cover;
  box-shadow:inset 0 0 0 1px #555;        /* default gray border */
  display:flex; align-items:center; justify-content:center;
  overflow:hidden;
  font:12px/1.2 "Trebuchet MS", Arial, sans-serif;
  color:#ddd;
}
.talent-cell.placeholder{ visibility:hidden; box-shadow:none; pointer-events:none; }

/* rank badge */
.talent-rank{
  position:absolute; right:2px; bottom:2px;
  padding:0 6px;
  border-radius:8px;
  background:#000a;
  font-weight:bold;
  font-size:12px;
  line-height:1;
  color:#999; /* default gray */
}

/* states */
.talent-cell.empty{                      /* 0/x */
  filter:grayscale(100%) brightness(.8); /* gray icon */
  box-shadow:inset 0 0 0 1px #555;
}
.talent-cell.empty .talent-rank{ color:#999; }

.talent-cell.learned{                    /* 1..(max-1) */
  filter:none;
  box-shadow:inset 0 0 0 2px #00ff00;    /* green border */
}
.talent-cell.learned .talent-rank{ color:#00ff00; }

.talent-cell.maxed{                      /* max/max */
  filter:none;
  box-shadow:inset 0 0 0 2px #ffd700;    /* gold border */
}
.talent-cell.maxed .talent-rank{ color:#ffd700; }

/* ====== tooltip ====== */
.talent-tt{
  position:fixed;
  z-index:9999;
  max-width:320px;
  color:#fff;
  background:#1b1b1b;
  border:1px solid #3a3a3a;
  box-shadow:0 8px 24px rgba(0,0,0,.45);
  border-radius:6px;
  font:13px/1.35 "Trebuchet MS", Arial, sans-serif;
  padding:10px 12px;
  pointer-events:none;
}
.talent-tt::before{
  content:"";
  position:absolute; top:-7px; left:50%; transform:translateX(-50%);
  border:7px solid transparent; border-bottom-color:#213a6b;
}
.talent-tt::after{
  content:"";
  position:absolute; top:-6px; left:50%; transform:translateX(-50%);
  border:6px solid transparent; border-bottom-color:#0e1b36;
}
.talent-tt h5{ margin:0 0 6px; font-size:14px; font-weight:700; }
.talent-tt p{ margin:0; white-space:normal; }
.talent-cell:hover {
  transform: scale(1.1);                 											
  z-index: 10;                           									
  box-shadow: 0 0 8px 2px rgba(255,255,200,.7),inset 0 0 0 2px #fff;       
}
.talent-cell.learned:hover {
  box-shadow: 0 0 8px 2px rgba(0,255,0,.7),
              inset 0 0 0 2px #00ff00;
}
.talent-cell.maxed:hover {
  box-shadow: 0 0 8px 2px rgba(255,215,0,.8),
              inset 0 0 0 2px #ffd700;
}



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
          $bgUrl = talent_bg_for_tab($tabId);   // $tabId already set from $t['id']


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

                  // max ranks present in DBC (1..5)
                  // current rank for this character
				$max = 0; for ($x = 1; $x <= 5; $x++) if (!empty($found["rank$x"])) $max = $x;
				$cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);

// icon + tooltip content
$sp    = spell_info_for_talent($found);
$title = htmlspecialchars($sp['name'], ENT_QUOTES);
$desc  = htmlspecialchars($sp['desc'], ENT_QUOTES);
$icon  = icon_url($sp['icon']);                 // absolute URL
$iconQ = htmlspecialchars($icon, ENT_QUOTES);

// state class
$cellClass = 'talent-cell';
if ($cur >= $max && $max > 0) {
    $cellClass .= ' maxed';
} elseif ($cur > 0) {
    $cellClass .= ' learned';
} else {
    $cellClass .= ' empty';
}

echo '<div class="'.$cellClass.'" style="--icon:url(\''.$iconQ.'\')"
          data-tt-title="'.$title.'"
          data-tt-desc="'.$desc.'">
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
  function hide() { clearTimeout(showTimer); tt.style.display = 'none'; }
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
