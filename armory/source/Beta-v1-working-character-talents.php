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

/* -------------------- asset bases -------------------- */
if (!defined('TALENTS_ASSET_WEB')) define('TALENTS_ASSET_WEB', '/armory/shared/global/talents');
if (!defined('TALENTS_ASSET_FS'))  define('TALENTS_ASSET_FS', realpath(__DIR__ . '/../shared/global/talents'));

/* -------------------- helpers -------------------- */
/** table exists in given connection */
function tbl_exists($conn, $table) {
    return (bool) execute_query(
        $conn,
        "SELECT 1 FROM information_schema.TABLES
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
    if (isset($rankMap[$tid])) return (int)$rankMap[$tid]; // already 1-based
    if ($hasCharSpell) {
        for ($r = 5; $r >= 1; $r--) {
            $spell = (int)$talRow["rank{$r}"];
            if ($spell > 0) {
                $has = execute_query(
                    'char',
                    "SELECT 1 FROM `character_spell`
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

/** first non-zero rank spell id */
function first_rank_spell(array $tal) {
    for ($i = 1; $i <= 5; $i++) {
        $id = (int)$tal["rank{$i}"];
        if ($id) return $id;
    }
    return 0;
}

/** Spell info (name/description/icon) for the talent row (highest rank) */
function spell_info_for_talent(array $talRow) {
    // highest non-zero rank
    $spellId = 0;
    for ($r = 5; $r >= 1; $r--) {
        if (!empty($talRow["rank{$r}"])) { $spellId = (int)$talRow["rank{$r}"]; break; }
    }
    if (!$spellId) return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];

    $sql = "
        SELECT
            s.`id`, s.`name`, s.`description`,
            s.`proc_chance`,
            s.`ref_spellduration`,
            s.`ref_spellradius_1`,
            s.`effect_basepoints_1`, s.`effect_basepoints_2`, s.`effect_basepoints_3`,
            s.`effect_amplitude_1`,  s.`effect_amplitude_2`,  s.`effect_amplitude_3`,
            s.`effect_chaintarget_1`, s.`effect_chaintarget_2`, s.`effect_chaintarget_3`,
            s.`effect_trigger_1`, s.`effect_trigger_2`, s.`effect_trigger_3`,
            i.`name` AS icon
        FROM `dbc_spell` s
        LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
        WHERE s.`id` = {$spellId}
        LIMIT 1
    ";
    $sp = execute_query('armory', $sql, 1);
    if (!$sp || !is_array($sp)) {
        return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];
    }

    // description (tokens -> text)
    $desc = build_tooltip_desc($sp);

    // icon base
    $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
    if ($icon === '') $icon = 'inv_misc_questionmark';

    return ['name' => (string)($sp['name'] ?? 'Unknown'), 'desc' => $desc, 'icon' => $icon];
}

/** icon web path */
function icon_url($iconBase) { return '/armory/shared/icons/' . $iconBase . '.jpg'; }

/** class/tab background by talent tab id (e.g. 161.jpg) */
function talent_bg_for_tab($tabId) {
    $webBase = '/armory/shared/icon_talents';
    $fsBase  = realpath(__DIR__ . '/../shared/icon_talents');
    if (!$fsBase) return '';
    $file = (int)$tabId . '.jpg';
    $fs   = $fsBase . DIRECTORY_SEPARATOR . $file;
    return is_file($fs) ? ($webBase . '/' . $file) : '';
}

/* ---- time helpers ---- */

function fmt_secs($sec) {
    $sec = (float)$sec;
    if ($sec < 0.0001) return '0 sec';
    if ($sec < 60) {
        $dp = ($sec < 10 && abs($sec - round($sec)) > 0.0001) ? 1 : 0;
        return rtrim(rtrim(number_format($sec, $dp), '0'), '.') . ' sec';
    }
    $m = floor($sec / 60);
    $s = $sec - $m * 60;
    if ($s < 0.0001) return $m . ' min';
    $dp = ($s < 10 && abs($s - round($s)) > 0.0001) ? 1 : 0;
    return $m . ' min ' . rtrim(rtrim(number_format($s, $dp), '0'), '.') . ' sec';
}

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
    $ms = (int)$row['durationValue'];
    if ($ms <= 0) $ms = max((int)$row['ms_mod'], (int)$row['ms_min']);
    return (int)round($ms / 1000);
}

/* ---- simple lookups ---- */

function get_spell_row($id) {
    return execute_query(
        'armory',
        "SELECT `effect_basepoints_1`, `effect_basepoints_2`, `effect_basepoints_3`,
                `ref_spellradius_1`
           FROM `dbc_spell`
          WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
}

// minimal fields needed to compute cross-spell $oN
function get_spell_o_row($id) {
    return execute_query(
        'armory',
        "SELECT
            `ref_spellduration`,
            `effect_basepoints_1`, `effect_basepoints_2`, `effect_basepoints_3`,
            `effect_amplitude_1`,  `effect_amplitude_2`,  `effect_amplitude_3`
         FROM `dbc_spell`
         WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
}

/* cache whether effect_die_sides_* columns exist */
function _has_die_sides_cols(): bool {
    static $has = null;
    if ($has !== null) return $has;
    $rows = execute_query(
        'armory',
        "SELECT COLUMN_NAME
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME   = 'dbc_spell'
            AND COLUMN_NAME IN ('effect_die_sides_1','effect_die_sides_2','effect_die_sides_3')",
        0
    );
    $has = !empty($rows);
    return $has;
}

/* effect_die_sides_N for a spell id; returns 0 if cols missing */
function get_die_sides_n(int $spellId, int $n): int {
    if ($n < 1 || $n > 3) return 0;
    if (!_has_die_sides_cols()) return 0;
    $col = "effect_die_sides_{$n}";
    $row = execute_query(
        'armory',
        "SELECT `$col` FROM `dbc_spell` WHERE `id` = " . (int)$spellId . " LIMIT 1",
        1
    );
    return $row ? (int)$row[$col] : 0;
}

function get_spell_duration_id($id) {
    $row = execute_query(
        'armory',
        "SELECT `ref_spellduration` FROM `dbc_spell`
          WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
    return $row ? (int)$row['ref_spellduration'] : 0;
}

function get_spell_radius_id($id) {
    $row = execute_query(
        'armory',
        "SELECT `ref_spellradius_1` FROM `dbc_spell`
          WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
    return $row ? (int)$row['ref_spellradius_1'] : 0;
}

function get_radius_yds_by_id($rid) {
    $row = execute_query(
        'armory',
        "SELECT `yards_base` FROM `dbc_spellradius`
          WHERE `id` = " . (int)$rid . " LIMIT 1",
        1
    );
    return $row ? (float)$row['yards_base'] : 0.0;
}

function getRadiusYdsForSpellRow(array $sp) {
    $rid = (int)($sp['ref_spellradius_1'] ?? 0);
    if ($rid <= 0) return 0.0;
    return get_radius_yds_by_id($rid);
}


// pick the column name that exists for stacks
function _stack_col_name(): ?string {
    static $col = null, $checked = false;
    if ($checked) return $col;
    $checked = true;
    $row = execute_query(
        'armory',
        "SELECT COLUMN_NAME
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME   = 'dbc_spell'
            AND COLUMN_NAME IN ('stack_amount','StackAmount','max_stack','MaxStack') LIMIT 1",
        1
    );
    $col = $row ? $row['COLUMN_NAME'] : null;
    return $col;
}
function _stack_amount_for_spell(int $id): int {
    $col = _stack_col_name();
    if (!$col) return 0;
    $r = execute_query('armory', "SELECT `$col` AS st FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
    return $r ? (int)$r['st'] : 0;
}
// Normalize "by -X%" or "by −X%" → "by X%". Runs only when needed.
function _fix_by_negative_percent(string $desc): string {
    // Normalize: replace Unicode minus, en dash, em dash with plain '-'
    $desc = str_replace(
        ["−", "–", "—"], // U+2212, U+2013, U+2014
        "-",
        $desc
    );

    // Now strip the minus sign only if it appears after "by"
    $desc = preg_replace(
        '/\bby\s*-\s*([0-9]+(?:\.[0-9]+)?)%/i',
        'by $1%',
        $desc
    );

    return $desc;
}




/* -------------------- tooltip builder -------------------- */
// --- Build a clean tooltip description for one spell row ---
function build_tooltip_desc(array $sp): string {
    $desc = (string)($sp['description'] ?? '');

    $trimNum = static function($v): string {
        $s = number_format((float)$v, 1, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return ($s === '') ? '0' : $s;
    };

    // basepoints(+1) + dice → text; ABS() so reductions show positive values
    $formatS = static function(int $bp, int $dieSides): array {
        $min  = abs($bp + 1);                     // 👈 key change
        $max  = $min + max(0, $dieSides);
        $text = ($max > $min) ? "{$min} to {$max}" : (string)$min;
        return [$min, $max, $text];
    };

    /* ---------- Cross-spell replacements ---------- */

    // $12345sN  (range if dice available)
    $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) use ($formatS) {
        $sid = (int)$m[1]; $idx = (int)$m[2];
        $row = get_spell_row($sid);
        if (!$row) return '0';
        $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
        $die = get_die_sides_n($sid, $idx);
        [, , $text] = $formatS($bp, $die);
        return $text;
    }, $desc);

    // $12345d  (duration of another spell id)
    $desc = preg_replace_callback('/\$(\d+)d\b/', function ($m) {
        $sid   = (int)$m[1];
        $durId = get_spell_duration_id($sid);
        $secs  = duration_secs_from_id($durId);
        return fmt_secs($secs);
    }, $desc);

    // $12345a1 — radius (yards) of another spell id
    $desc = preg_replace_callback('/\$(\d+)a1\b/', function ($m) use ($trimNum) {
        $sid = (int)$m[1];
        $row = get_spell_row($sid);
        if (!$row) return '0';
        $a1  = getRadiusYdsForSpellRow($row);
        return $trimNum($a1);
    }, $desc);

    // $12345oN — total over time for another spell id
    $desc = preg_replace_callback('/\$(\d+)o([1-3])\b/', function ($m) {
        $sid = (int)$m[1]; $idx = (int)$m[2];
        $row = get_spell_o_row($sid);
        if (!$row) return '0';
        $bp   = abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1); // ABS
        $amp  = (int)($row["effect_amplitude_{$idx}"] ?? 0);           // ms
        $dsec = duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
        $ticks = ($amp > 0) ? (int)floor(($dsec * 1000) / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    }, $desc);

    // $12345u — “stacks up to N times” (use S1 of that spell)
    $desc = preg_replace_callback('/\$(\d+)u\b/', function ($m) {
        $sid = (int)$m[1];
        $row = get_spell_row($sid);
        if (!$row) return '0';
        $bp = (int)($row['effect_basepoints_1'] ?? 0);
        return (string)abs($bp + 1); // ABS
    }, $desc);

    /* ---------- Current-spell values ---------- */

    $currId = isset($sp['id']) ? (int)$sp['id'] : 0;

    [$s1min,$s1max,$s1txt] = $formatS((int)($sp['effect_basepoints_1'] ?? 0), $currId ? get_die_sides_n($currId,1) : 0);
    [$s2min,$s2max,$s2txt] = $formatS((int)($sp['effect_basepoints_2'] ?? 0), $currId ? get_die_sides_n($currId,2) : 0);
    [$s3min,$s3max,$s3txt] = $formatS((int)($sp['effect_basepoints_3'] ?? 0), $currId ? get_die_sides_n($currId,3) : 0);

// Math tokens: $/N;[ $ ]sN  (handles $/1000;$s1 and $/1000;s1)
$desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?s([1-3])/i', function ($m) use ($s1min,$s2min,$s3min) {
    $div = (float)$m[1]; $i = (int)$m[2];
    $map = [1=>$s1min,2=>$s2min,3=>$s3min];
    $val = abs($map[$i] ?? 0.0);
    $out = ($div > 0) ? ($val / $div) : $val;
    $s = number_format($out, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return $s === '' ? '0' : $s;
}, $desc);


    $d  = fmt_secs(duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0)));

    // Total-over-time tokens $o1..$o3 (current spell) — ABS on bp
    $durSecs = (int)duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));
    $durMs   = $durSecs * 1000;

    $o1 = (function() use ($sp, $durMs) {
        $bp  = abs((int)($sp['effect_basepoints_1'] ?? 0) + 1);
        $amp = (int)($sp['effect_amplitude_1'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    })();
    $o2 = (function() use ($sp, $durMs) {
        $bp  = abs((int)($sp['effect_basepoints_2'] ?? 0) + 1);
        $amp = (int)($sp['effect_amplitude_2'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    })();
    $o3 = (function() use ($sp, $durMs) {
        $bp  = abs((int)($sp['effect_basepoints_3'] ?? 0) + 1);
        $amp = (int)($sp['effect_amplitude_3'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    })();

    // headline number: prefer proc_chance; else min of s1
    $h  = (int)($sp['proc_chance'] ?? 0);
    if ($h <= 0) $h = $s1min;

    // radius ($a1)
    $a1 = $trimNum(getRadiusYdsForSpellRow($sp));

    /* ---------- Cosmetic cleanup (safety net) ---------- */
    // "by -X%" or "by −X%" → "by X%"
    $desc = preg_replace('/\bby\s*[\-\x{2212}]\s*([0-9]+(?:\.[0-9]+)?)%/iu', 'by $1%', $desc);
    // "-X sec" (with or without "by") → "X sec"
    $desc = preg_replace('/\b(?:by\s*)?[\-\x{2212}]\s*([0-9]+(?:\.[0-9]+)?)\s*sec\b/iu', ' $1 sec', $desc);

    /* ---------- Final substitution ---------- */
    return strtr($desc, [
        '$s1' => $s1txt, '$s2' => $s2txt, '$s3' => $s3txt,
        '$o1' => $o1,    '$o2' => $o2,    '$o3' => $o3,
        '$a1' => $a1,
        '$d'  => $d,
        '$h'  => (string)$h,
    ]);
}



/* -------------------- build data -------------------- */

$tabs = get_tabs_for_class($stat['class']);

/* rank map from character_talent (normalize to 1-based) */
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
.talent-trees{ display:flex; justify-content:center; gap:12px; flex-wrap:nowrap; max-width:980px; margin:65px auto 0; }
.talent-tree{ position:relative; flex:0 0 276px; min-height:540px; background-position:center; background-size:276px 540px; border-radius:10px; }
.talent-h{ position:absolute; top:-38px; left:50%; transform:translateX(-50%); margin:0; font-size:18px; font-weight:bold; color:#fff7d2; text-align:center; }
.talent-flex{ --cell:48px; --gap:10px; position:relative; margin:0 auto; width:calc(var(--cell)*4 + var(--gap)*3); display:flex; flex-wrap:wrap; gap:var(--gap); justify-content:center; top:12px; }

/* ====== cell, icon, states ====== */
.talent-cell{ position:relative; width:var(--cell); height:var(--cell); border-radius:6px; background:#2a2a2a; background-image:var(--icon); background-position:center; background-repeat:no-repeat; background-size:cover; box-shadow:inset 0 0 0 1px #555; display:flex; align-items:center; justify-content:center; overflow:hidden; font:12px/1.2 "Trebuchet MS", Arial, sans-serif; color:#ddd; }
.talent-cell.placeholder{ visibility:hidden; box-shadow:none; pointer-events:none; }

/* rank badge */
.talent-rank{ position:absolute; right:2px; bottom:2px; padding:0 6px; border-radius:8px; background:#000a; font-weight:bold; font-size:12px; line-height:1; color:#999; }

/* states */
.talent-cell.empty{ filter:grayscale(100%) brightness(.8); box-shadow:inset 0 0 0 1px #555; }
.talent-cell.empty .talent-rank{ color:#999; }
.talent-cell.learned{ filter:none; box-shadow:inset 0 0 0 2px #00ff00; }
.talent-cell.learned .talent-rank{ color:#00ff00; }
.talent-cell.maxed{ filter:none; box-shadow:inset 0 0 0 2px #ffd700; }
.talent-cell.maxed .talent-rank{ color:#ffd700; }

/* ====== tooltip ====== */
.talent-tt{ position:fixed; z-index:9999; max-width:320px; color:#fff; background:#1b1b1b; border:1px solid #3a3a3a; box-shadow:0 8px 24px rgba(0,0,0,.45); border-radius:6px; font:13px/1.35 "Trebuchet MS", Arial, sans-serif; padding:10px 12px; pointer-events:none; }
.talent-tt::before{ content:""; position:absolute; top:-7px; left:50%; transform:translateX(-50%); border:7px solid transparent; border-bottom-color:#213a6b; }
.talent-tt::after{ content:""; position:absolute; top:-6px; left:50%; transform:translateX(-50%); border:6px solid transparent; border-bottom-color:#0e1b36; }
.talent-tt h5{ margin:0 0 6px; font-size:14px; font-weight:700; }
.talent-tt p{ margin:0; white-space:normal; }

/* hover flair */
.talent-cell:hover { transform: scale(1.1); z-index: 10; box-shadow: 0 0 8px 2px rgba(255,255,200,.7), inset 0 0 0 2px #fff; }
.talent-cell.learned:hover { box-shadow: 0 0 8px 2px rgba(0,255,0,.7), inset 0 0 0 2px #00ff00; }
.talent-cell.maxed:hover { box-shadow: 0 0 8px 2px rgba(255,215,0,.8), inset 0 0 0 2px #ffd700; }
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
        $bgUrl   = talent_bg_for_tab($tabId);

        // talents in this tab
        $talents = execute_query(
            'armory',
            "SELECT `id`, `row`, `col`, `rank1`, `rank2`, `rank3`, `rank4`, `rank5`
               FROM `dbc_talent`
              WHERE `ref_talenttab` = {$tabId}
              ORDER BY `row`, `col`",
            0
        ) ?: [];

        // index by row:col and deepest row
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

                // max ranks present in DBC
                $max = 0; for ($x = 1; $x <= 5; $x++) if (!empty($found["rank$x"])) $max = $x;
                $cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);

                // icon + tooltip content
                $sp    = spell_info_for_talent($found);
                $title = htmlspecialchars($sp['name'], ENT_QUOTES);
                $desc  = htmlspecialchars($sp['desc'], ENT_QUOTES);
                $icon  = icon_url($sp['icon']);
                $iconQ = htmlspecialchars($icon, ENT_QUOTES);

                // state class
                $cellClass = 'talent-cell';
                if ($cur >= $max && $max > 0)      $cellClass .= ' maxed';
                elseif ($cur > 0)                  $cellClass .= ' learned';
                else                                $cellClass .= ' empty';

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
