<?php
if (!defined('Armory')) { exit; }

/**
 * character-talents.php
 * - class/tab backgrounds witch icons
 * - hover tooltips from dbc_spell with plain text
 ++*
 * Requires (your current schema):
 *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
 *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
 *   armory.dbc_spell(id, ref_spellicon, name, description, ...)
 *   armory.dbc_spellicon(id, name)
 */


/* -------------------- helpers -------------------- */

/** table exists in given connection */
function tbl_exists($conn, $table) {
	if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
  return (bool) execute_query(
    $conn,
    "SELECT 1 FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = '{$table}'
      LIMIT 1",
    2
  );
}
function get_talent_cap(?int $level): int {
  if (!$level || $level < 10) return 0;
  return max(0, $level - 9);
}


/** tabs (id, name, tab_number) for a class id */
function get_tabs_for_class($classId) {
  $mask = 1 << ((int)$classId - 1);
  return execute_query(
    'armory',
    "SELECT `id`, `name`, `tab_number`
       FROM `dbc_talenttab`
      WHERE (`refmask_chrclasses` & {$mask}) <> 0
      ORDER BY `tab_number` ASC",
    0
  ) ?: [];
}

/** fast learned-spells lookup (cached per guid;) */
function get_learned_spells_map(int $guid): array {
  return _cache("learned:".$guid, function() use ($guid){
    if (!tbl_exists('char', 'character_spell')) return [];
    $rows = execute_query(
      'char',
      "SELECT `spell` FROM `character_spell`
        WHERE `guid`=".(int)$guid." AND `disabled`=0",
      0
    ) ?: [];
    $map = [];
    foreach ($rows as $r) { $map[(int)$r['spell']] = true; }
    return $map;
  });
}

/** prefer character_talent; else derive from character_spell (no per-cell queries) */
function current_rank_for_talent(int $guid, array $talRow, array $rankMap, bool $hasCharSpell): int {
  $tid = (int)$talRow['id'];
  if (isset($rankMap[$tid])) return (int)$rankMap[$tid]; // already 1-based
  if ($hasCharSpell) {
    $learned = get_learned_spells_map($guid);            // cached O(1) lookups
    for ($r = 5; $r >= 1; $r--) {
      $spell = (int)($talRow["rank{$r}"] ?? 0);
      if ($spell > 0 && !empty($learned[$spell])) return $r;
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

 /** numeric formatting helper */
 function num_trim($v): string {
   $s = number_format((float)$v, 1, '.', '');
   $s = rtrim(rtrim($s, '0'), '.');
   return ($s === '') ? '0' : $s;
 }
 
 /** cached chain-target lookup */
 function get_spell_chain_targets(int $id, int $n): int {
  $n = max(1, min(3, $n));
   return _cache("chain:$id:$n", function() use ($id,$n){
     $row = execute_query('armory',
       "SELECT `effect_chaintarget_{$n}` AS x FROM `dbc_spell`
          WHERE `id`=".(int)$id." LIMIT 1", 1);
     return $row ? (int)$row['x'] : 0;
   });
 }
 

/** Spell info (name/description/icon) for the talent row at a given rank */
function spell_info_for_talent(array $talRow, int $rank = 0) {
    // find the highest non-zero rank present in DBC (1..5)
    $maxRank = 0;
    for ($r = 5; $r >= 1; $r--) {
        if (!empty($talRow["rank{$r}"])) { $maxRank = $r; break; }
    }
    if ($maxRank === 0) {
        return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];
    }

    // choose the spell for the requested rank (clamped), with safe fallback
    $useRank = $rank > 0 ? min($rank, $maxRank) : 1; // if unlearned, show rank 1
    $spellId = (int)($talRow["rank{$useRank}"] ?? 0);
    if ($spellId <= 0) {
        // fallback downward until we hit an existing rank
        for ($r = min($useRank, $maxRank); $r >= 1; $r--) {
            $spellId = (int)($talRow["rank{$r}"] ?? 0);
            if ($spellId > 0) break;
        }
    }
    if ($spellId <= 0) {
        return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];
    }

    $sql = "
        SELECT
            s.`id`, s.`name`, s.`description`,
            s.`proc_chance`,
			s.`proc_charges`,
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

    $desc = build_tooltip_desc($sp);

    $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
    if ($icon === '') $icon = 'inv_misc_questionmark';

    return ['name' => (string)($sp['name'] ?? 'Unknown'), 'desc' => $desc, 'icon' => $icon];
}

/** icon web path */
function icon_url($iconBase) {
  return '/armory/shared/icons/' . $iconBase . '.jpg';
}

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
  $sec = (int)round($sec); // integer seconds

  // Auras/buffs with duration 0 in DBC
  if ($sec <= 0) return 'until cancelled';

  if ($sec < 60) return $sec . ' sec';

  $m = floor($sec / 60);
  $s = $sec % 60;

  return $s === 0 ? ($m . ' min') : ($m . ' min ' . $s . ' sec');
}

/* ---- memoized simple lookups ---- */
 // note: static cache is per-request; safe for PHP-FPM
function _cache($key, callable $fn) {
    static $C = [];
    if (isset($C[$key])) return $C[$key];
    $C[$key] = $fn();
    return $C[$key];
}

function get_spell_row($id) {
  return _cache("spell:$id", function() use ($id) {
    return execute_query('armory',
      "SELECT `effect_basepoints_1`,`effect_basepoints_2`,`effect_basepoints_3`,`ref_spellradius_1`
       FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
  });
}

function get_spell_o_row($id) {
  return _cache("spellO:$id", function() use ($id) {
    return execute_query('armory',
      "SELECT `ref_spellduration`,
              `effect_basepoints_1`,`effect_basepoints_2`,`effect_basepoints_3`,
              `effect_amplitude_1`,`effect_amplitude_2`,`effect_amplitude_3`
       FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
  });
}

function get_spell_duration_id($id) {
  return _cache("durid:$id", function() use ($id) {
    $row = execute_query('armory',
      "SELECT `ref_spellduration` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
    return $row ? (int)$row['ref_spellduration'] : 0;
  });
}

function duration_secs_from_id($id) {
  if (!$id) return 0;
  $row = execute_query(
    'armory',
    "SELECT `durationValue` FROM `dbc_spellduration` WHERE `id`=".(int)$id." LIMIT 1",
    1
  );
  if (!$row) return 0;

  $ms = (int)$row['durationValue'];   // always ms in your DB
  return ($ms > 0) ? ($ms / 1000) : 0; // → return pure seconds as float
}

function get_radius_yds_by_id($rid) {
  return _cache("radius:$rid", function() use ($rid){
    $row = execute_query('armory',
      "SELECT `yards_base` FROM `dbc_spellradius` WHERE `id`=".(int)$rid." LIMIT 1", 1);
    return $row ? (float)$row['yards_base'] : 0.0;
  });
}

function get_die_sides_n(int $spellId, int $n): int {
  if ($n < 1 || $n > 3) return 0;
  if (!_has_die_sides_cols()) return 0;
  return _cache("die:$spellId:$n", function() use ($spellId,$n){
    $col = "effect_die_sides_{$n}";
    $row = execute_query('armory', "SELECT `$col` FROM `dbc_spell` WHERE `id`=".(int)$spellId." LIMIT 1", 1);
    return $row ? (int)$row[$col] : 0;
  });
}

function get_spell_proc_charges($id) {
  return _cache("procchg:$id", function() use ($id){
    $row = execute_query('armory',
      "SELECT `proc_charges` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
    return $row ? (int)$row['proc_charges'] : 0;
  });
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

function get_spell_radius_id($id) {
  $row = execute_query(
    'armory',
    "SELECT `ref_spellradius_1` FROM `dbc_spell`
      WHERE `id` = " . (int)$id . " LIMIT 1",
    1
  );
  return $row ? (int)$row['ref_spellradius_1'] : 0;
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

// Which trigger column family exists?  effect_trigger_*  or  effect_trigger_spell_* ?
function _trigger_col_base(){
  static $base = null, $checked = false;
  if ($checked) return $base;
  $checked = true;

  $row = execute_query(
    'armory',
    "SELECT COLUMN_NAME
       FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME   = 'dbc_spell'
        AND COLUMN_NAME IN ('effect_trigger_1','effect_trigger_spell_1')
      LIMIT 1",
    1
  );

  if ($row && isset($row['COLUMN_NAME'])) {
    $base = (strpos($row['COLUMN_NAME'], 'effect_trigger_spell_') === 0)
      ? 'effect_trigger_spell_'
      : 'effect_trigger_';
  } else {
    // safe default for classic/TBC DBCs
    $base = 'effect_trigger_';
  }
  return $base;
}


/* -------------------- tooltip builder -------------------- */
// Build a clean tooltip description for one spell row

function build_tooltip_desc(array $sp): string {
  $desc = (string)($sp['description'] ?? '');

  $trimNum = static function($v): string {
    $s = number_format((float)$v, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  };

  $rangeText = static function(int $min, int $max): string {
    return ($max > $min) ? ($min . ' to ' . $max) : (string)$min; // en dash
  };

// Produces min/max/text for $sN.  If $div==1000 and bp<0, treat as negative ms.
$formatS = static function (int $bp, int $dieSides, int $div = 1): array {
  // Special case: cast-time reductions stored as negative milliseconds
  if ($div === 1000 && $bp < 0) {
    $min = abs($bp) / 1000.0;
    $max = $min + ($dieSides > 0 ? $dieSides / 1000.0 : 0.0);
    // collapse if no range
    $txt = ($max > $min) ? rtrim(rtrim(number_format($min,1,'.',''), '0'),'.')
                           .' to '.
                           rtrim(rtrim(number_format($max,1,'.',''), '0'),'.')
                         : rtrim(rtrim(number_format($min,1,'.',''), '0'),'.');
    return [$min, $max, $txt];
  }

  // Normal scalar (damage/heal/etc.)
  $min = $bp + 1;
  if ($dieSides <= 1) {
    $txt = (string)abs($min);
    return [$min, $min, $txt];
  }
  $max = $bp + $dieSides;
  if ($max < $min) { [$min, $max] = [$max, $min]; }
  return [$min, $max, $min . ' to ' . $max];
};



  // $12345sN
  $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) use ($formatS) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
    if (!$row) return '0';
    $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
    $die = _cache("die:$sid:$idx", function() use ($sid,$idx){ return get_die_sides_n($sid,$idx); });
    [, , $text] = $formatS($bp, $die);
    return $text;
  }, $desc);

  // $12345d
  $desc = preg_replace_callback('/\$(\d+)d\b/', function ($m) {
    $sid   = (int)$m[1];
    $durId = _cache("durid:$sid", function() use ($sid){ return get_spell_duration_id($sid); });
    $secs  = _cache("dursec:$durId", function() use ($durId){ return duration_secs_from_id($durId); });
    return fmt_secs($secs);
  }, $desc);

  // $12345a1
  $desc = preg_replace_callback('/\$(\d+)a1\b/', function ($m) {
    $sid = (int)$m[1];
    $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
    if (!$row) return '0';
    $val = _cache("radiusYds:$sid", function() use ($row){ return getRadiusYdsForSpellRow($row); });
    $s = number_format((float)$val, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  }, $desc);

  // $12345oN
  $desc = preg_replace_callback('/\$(\d+)o([1-3])\b/', function ($m) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spellO:$sid", function() use ($sid){ return get_spell_o_row($sid); });
    if (!$row) return '0';
    $bp   = abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
    $amp  = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $dsec = _cache("dursecBySpell:$sid", function() use ($row){
      return duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
    });
    $ticks = ($amp > 0) ? (int)floor(($dsec * 1000) / $amp) : 0;
    return (string)($ticks > 0 ? $bp * $ticks : $bp);
  }, $desc);

  // $12345tN
  $desc = preg_replace_callback('/\$(\d+)t([1-3])\b/', function ($m) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spellO:$sid", function() use ($sid){ return get_spell_o_row($sid); });
    if (!$row) return '0';
    $amp = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $sec = $amp > 0 ? ($amp / 1000.0) : 0.0;
    $s = number_format($sec, 1, '.', '');
    return rtrim(rtrim($s, '0'), '.') ?: '0';
  }, $desc);

 
  // $12345u  (max stacks from another spell id; common fallback via S1+1)
$desc = preg_replace_callback('/\$(\d+)u\b/', function ($m) {
    $sid = (int)$m[1];

    // prefer explicit stack column if present
    $n = _stack_amount_for_spell($sid);

    // fallback: some DBs encode as S1 (+1)
    if ($n <= 0) {
        $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
        if ($row) {
            $bp = (int)($row['effect_basepoints_1'] ?? 0);
            $n  = abs($bp + 1);
        }
    }

    if ($n < 1) $n = 1;
    return (string)$n;
}, $desc);

  
  // $12345n  (proc charges of another spell id; smart fallbacks)
  $desc = preg_replace_callback('/\$(\d+)n\b/', function ($m) {
    $sid = (int)$m[1];

    // main source: proc_charges (cached)
    $n = _cache("procchg:$sid", function() use ($sid) {
      return get_spell_proc_charges($sid);
    });

    // fallback 1: some auras encode "stacks up to N"
    if ($n <= 0) $n = _stack_amount_for_spell($sid);

    // fallback 2: a few tooltips store N as S1 (+1)
    if ($n <= 0) {
      $row = _cache("spell:$sid", function() use ($sid) {
        return get_spell_row($sid);
      });
      if ($row) {
        $bp = (int)($row['effect_basepoints_1'] ?? 0);
        $n  = abs($bp + 1);
      }
    }

    $n = (int)$n;
    if ($n < 1) $n = 1;
    return (string)$n;
  }, $desc);



  
  // $12345xN (total chain targets from another spell's EffectN)
$desc = preg_replace_callback('/\$(\d+)x([1-3])\b/', function($m){
  $sid = (int)$m[1]; $i = (int)$m[2];
  $row = execute_query('armory',
    "SELECT `effect_chaintarget_{$i}` AS x FROM `dbc_spell` WHERE `id`={$sid} LIMIT 1", 1);
  $val = $row ? (int)$row['x'] : 0;
  if ($val <= 0) $val = 1;
  return (string)$val;
}, $desc);

/* ${$*K;sN%}  →  (K * sNmin)%   e.g., ${$*5;s1%} -> 5 * $s1 = 15% */
$desc = preg_replace_callback(
  '/\{\$\s*\*\s*([0-9]+)\s*;\s*\$s([1-3])\s*%\s*\}/i',
  function($m) use ($s1min,$s2min,$s3min){
    $k   = (int)$m[1];
    $idx = (int)$m[2];
    $map = array(1=>$s1min, 2=>$s2min, 3=>$s3min);
    $base = isset($map[$idx]) ? abs($map[$idx]) : 0;
    $val  = $k * $base;
    return (string)$val . '%';
  },
  $desc
);



  // ---- Current spell values
  $currId = isset($sp['id']) ? (int)$sp['id'] : 0;

  $die1 = _cache("die:$currId:1", function() use ($currId){ return $currId?get_die_sides_n($currId,1):0; });
  $die2 = _cache("die:$currId:2", function() use ($currId){ return $currId?get_die_sides_n($currId,2):0; });
  $die3 = _cache("die:$currId:3", function() use ($currId){ return $currId?get_die_sides_n($currId,3):0; });

  $formatSLocal = $formatS;
  list($s1min,$s1max,$s1txt) = $formatSLocal((int)($sp['effect_basepoints_1'] ?? 0), $die1);
  list($s2min,$s2max,$s2txt) = $formatSLocal((int)($sp['effect_basepoints_2'] ?? 0), $die2);
  list($s3min,$s3max,$s3txt) = $formatSLocal((int)($sp['effect_basepoints_3'] ?? 0), $die3);

// $/N; $sN and $/N; $<id>sN / $<id>oN  (support ranges; round outward)
$desc = preg_replace_callback(
    '/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',
    function ($m) use ($s1min, $s1max, $s2min, $s2max, $s3min, $s3max, $formatSLocal) {
        $div     = (float)$m[1];
        $spellId = $m[2] ? (int)$m[2] : 0;
        $type    = strtolower($m[3]);
        $idx     = (int)$m[4];

        if ($div <= 0) { $div = 1; }

        // Helper to print either a single int or "min to max" after division
        $printRange = static function (float $min, float $max, float $div) : string {
            $min2 = floor($min / $div);
            $max2 = ceil($max / $div);
            if ($max2 > $min2) return (int)$min2 . ' to ' . (int)$max2;
            return (string)(int)$min2;
        };

        if ($spellId === 0) {
            // Use current spell's sN min/max we already computed
            $mapMin = [1 => (float)$s1min, 2 => (float)$s2min, 3 => (float)$s3min];
            $mapMax = [1 => (float)$s1max, 2 => (float)$s2max, 3 => (float)$s3max];
            $min = abs($mapMin[$idx] ?? 0.0);
            $max = abs($mapMax[$idx] ?? 0.0);
            return $printRange($min, $max, $div);
        }

        if ($type === 's') {
            // Use another spell's sN; compute its min & max
            $row = _cache("spell:$spellId", function() use ($spellId){ return get_spell_row($spellId); });
            if (!$row) return '0';
            $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
            $die = _cache("die:$spellId:$idx", function() use ($spellId,$idx){ return get_die_sides_n($spellId,$idx); });
            [$min, $max] = $formatSLocal($bp, $die);   // we only need min & max
            return $printRange($min, $max, $div);
        } else {
            // $...oN are totals; treat as scalar (no range)
            $row = _cache("spellO:$spellId", function() use ($spellId){ return get_spell_o_row($spellId); });
            if (!$row) return '0';
            $bp    = abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
            $amp   = (int)($row["effect_amplitude_{$idx}"] ?? 0);
            $dur   = duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
            $ticks = ($amp > 0) ? (int)floor(($dur * 1000) / $amp) : 0;
            $val   = $ticks > 0 ? $bp * $ticks : $bp;
            return (string)(int)round($val / $div);
        }
    },
    $desc
);




/* -------- Duration / totals for current spell (forward + reverse trigger hops) -------- */
$getDurSecBySpellId = function($sid){
  if ($sid <= 0) return 0;
  $durId = _cache("durid:$sid", function() use ($sid){ return get_spell_duration_id($sid); });
  return _cache("dursec:$durId", function() use ($durId){ return duration_secs_from_id($durId); });
};

$currId  = isset($sp['id']) ? (int)$sp['id'] : 0;
$durSecs = $getDurSecBySpellId($currId);

// Evaluate ${$d-1} sec  → "<durSecs - 1> sec"
$desc = preg_replace_callback(
    '/\$\{\s*\$d\s*([+-])\s*(\d+)\s*\}\s*sec\b/i',
    function ($m) use ($durSecs) {
        $delta = (int)$m[2];
        $v = $durSecs + ($m[1] === '-' ? -$delta : $delta);
        if ($v < 0) $v = 0;
        return $v . ' sec';
    },
    $desc
);

// Also support ${$d-1} (without trailing " sec")
$desc = preg_replace_callback(
    '/\$\{\s*\$d\s*([+-])\s*(\d+)\s*\}/i',
    function ($m) use ($durSecs) {
        $delta = (int)$m[2];
        $v = $durSecs + ($m[1] === '-' ? -$delta : $delta);
        if ($v < 0) $v = 0;
        return (string)$v;
    },
    $desc
);


if (strpos($desc, '$d') !== false) {
  /* ---- forward: follow children triggered by this spell ---- */
  $seen  = array();
  $queue = array($currId);
  $depth = 0;

  while (!empty($queue) && $depth < 2) {
    $next = array();
    foreach ($queue as $sid) {
      if ($sid <= 0 || isset($seen[$sid])) continue;
      $seen[$sid] = true;

      $ds = $getDurSecBySpellId($sid);
      if ($ds > $durSecs) $durSecs = $ds;

      // effect_trigger_* family (or effect_trigger_spell_* if that’s what the DBC has)
      $base = _trigger_col_base();
      $col1 = $base.'1'; $col2 = $base.'2'; $col3 = $base.'3';
      $row = execute_query('armory',
        "SELECT `$col1` AS t1, `$col2` AS t2, `$col3` AS t3
           FROM `dbc_spell` WHERE `id`=".(int)$sid." LIMIT 1", 1);

      if ($row) {
        for ($i = 1; $i <= 3; $i++) {
          $tid = isset($row["t{$i}"]) ? (int)$row["t{$i}"] : 0;
          if ($tid > 0 && !isset($seen[$tid])) $next[] = $tid;
        }
      }
    }
    $queue = $next;
    $depth++;
  }

  /* ---- reverse: if still suspiciously short (<=2 sec), find parents that trigger THIS spell ---- */
  if ($durSecs <= 2) {
    $base = _trigger_col_base();
    $col1 = $base.'1'; $col2 = $base.'2'; $col3 = $base.'3';

    $parents = execute_query(
      'armory',
      "SELECT `id` FROM `dbc_spell`
         WHERE `$col1`=".(int)$currId."
            OR `$col2`=".(int)$currId."
            OR `$col3`=".(int)$currId."
         LIMIT 20",
      0
    );

    if (is_array($parents)) {
      foreach ($parents as $pr) {
        $pid = (int)$pr['id'];
        $pds = $getDurSecBySpellId($pid);
        if ($pds > $durSecs) $durSecs = $pds;
      }
    }
  }
}

$durMs = $durSecs * 1000;
$d     = fmt_secs($durSecs);





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

  // headline value
  $h  = (int)($sp['proc_chance'] ?? 0);
  if ($h <= 0) $h = $s1min;

  // radius & tick times
  $a1 = $trimNum(getRadiusYdsForSpellRow($sp));
  $t1 = $trimNum(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000.0);
  $t2 = $trimNum(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000.0);
  $t3 = $trimNum(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000.0);

 
// ${AP*$mN/100} → "(Attack Power * N / 100)"
$desc = preg_replace_callback(
    '/\{\$\s*(AP|RAP|SP)\s*\*\s*\$m([1-3])\s*\/\s*100\s*\}/i',
    function ($m) use ($s1min, $s2min, $s3min) {
        $idx  = (int)$m[2];
        $map  = [1 => $s1min, 2 => $s2min, 3 => $s3min];
        $pct  = (int)abs($map[$idx] ?? 0);
        $stat = strtoupper($m[1]); // AP, RAP, SP
        $labels = [
            'AP'  => 'Attack Power',
            'RAP' => 'Ranged Attack Power',
            'SP'  => 'Spell Power',
        ];
        $label = $labels[$stat] ?? $stat;
        // return Blizzard-style formula
        return '(' . $label . ' * ' . $pct . ' / 100)';
    },
    $desc
);



  // $m1/$m2/$m3
  $desc = preg_replace_callback('/\$(m[1-3])\b/', function($m) use ($s1min,$s2min,$s3min){
    switch ($m[1]) { case 'm1': return (string)$s1min; case 'm2': return (string)$s2min; case 'm3': return (string)$s3min; }
    return $m[0];
  }, $desc);

  // $n (proc charges) – fallback to cached lookup
  $procN = (int)($sp['proc_charges'] ?? 0);
  if ($procN <= 0 && isset($sp['id'])) $procN = (int)get_spell_proc_charges((int)$sp['id']);
  if ($procN > 0) $desc = preg_replace('/\$n\b/i', (string)$procN, $desc);
  
  // $xN  (total chain targets from current spell's EffectN)
$desc = preg_replace_callback('/\$x([1-3])\b/', function($m) use ($sp){
  $i   = (int)$m[1];
  $val = (int)($sp["effect_chaintarget_{$i}"] ?? 0);
  if ($val <= 0) $val = 1; // safe fallback: at least 1 target
  return (string)$val;
}, $desc);



// $u (max stacks for CURRENT spell)
$u = 1;
if (!empty($sp['id'])) {
    $u = _stack_amount_for_spell((int)$sp['id']); // prefer stack column
    if ($u <= 0) {
        // fallback: S1 (+1) pattern
        $bp = (int)($sp['effect_basepoints_1'] ?? 0);
        $u  = abs($bp + 1);
    }
    if ($u < 1) $u = 1;
}

/* ---------- Grammar: $l<singular>:<plural>; picks based on the number before it ---------- */
while (preg_match('/\$l([^:;]+):([^;]+);/', $desc, $m, PREG_OFFSET_CAPTURE)) {
  $full     = $m[0][0];
  $offset   = $m[0][1];
  $singular = $m[1][0];
  $plural   = $m[2][0];

  // Look left of the token for the nearest number (already-substituted at this stage)
  $before = substr($desc, 0, $offset);
  $val = 2; // default to plural
  if (preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/', $before, $nm)) {
    $val = (float)$nm[1];
  }

  $word = (abs($val - 1.0) < 0.000001) ? $singular : $plural;

  // Replace this one occurrence and continue (handles multiple $l…; tokens)
  $desc = substr($desc, 0, $offset) . $word . substr($desc, $offset + strlen($full));
}
/* ---------- $*<factor>;<token>  (multiply a numeric token) ---------- */
/* supports s1..s3, o1..o3, m1..m3; extend map if you need more */
$__mulMap = array(
  's1' => (float)$s1min, 's2' => (float)$s2min, 's3' => (float)$s3min,
  'o1' => (float)$o1,    'o2' => (float)$o2,    'o3' => (float)$o3,
  'm1' => (float)$s1min, 'm2' => (float)$s2min, 'm3' => (float)$s3min
);

$desc = preg_replace_callback('/\$\*\s*([0-9]+(?:\.[0-9]+)?)\s*;\s*(s[1-3]|o[1-3]|m[1-3])/i',
  function($m) use ($__mulMap) {
    $factor = (float)$m[1];
    $key    = strtolower($m[2]);
    $base   = isset($__mulMap[$key]) ? (float)$__mulMap[$key] : 0.0;
    $val    = $factor * $base;

    // format like the rest of the tooltips: trim trailing .0
    $s = number_format($val, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  },
$desc);


/* -------- ${min-max/divisor} style tokens -------- */
$desc = preg_replace_callback('/\$\{([0-9]+)\s*-\s*([0-9]+)\/([0-9]+)\}/',
  function($m) use ($cur,$max) {
    $min = (int)$m[1];
    $maxVal = (int)$m[2];
    $div = (int)$m[3];
    if ($div <= 0) $div = 1;

    // linear scale based on current rank (1..max)
    $steps = max(1, $max-1);
    $progress = ($max > 1) ? ($cur-1)/$steps : 0;
    $val = $min + ($maxVal - $min) * $progress;
    $val = $val / $div;

    // clean formatting
    $s = number_format($val, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  }, $desc);


 /*  // Cleanup
  $desc = preg_replace('/\bby\s*[\-\x{2212}]\s*([0-9]+(?:\.[0-9]+)?)%/iu', 'by $1%', $desc);
  $desc = preg_replace('/\b(?:by\s*)?[\-\x{2212}]\s*([0-9]+(?:\.[0-9]+)?)\s*sec\b/iu', ' $1 sec', $desc); */

// Alias: $D should behave same as $d
$desc = str_replace('$D', $d, $desc);

// Final substitution
$desc = strtr($desc, [
  '$s1' => $s1txt, '$s2' => $s2txt, '$s3' => $s3txt,
  '$o1' => $o1,    '$o2' => $o2,    '$o3' => $o3,
  '$t1' => $t1,    '$t2' => $t2,    '$t3' => $t3,
  '$a1' => $a1,
  '$d'  => $d,
  '$h'  => (string)$h,
  '$u'  => (string)$u,
]);

// --- post substitutions cleanup ---

$desc = preg_replace('/(\d+)1%/', '$1%', $desc);		  // collapse mistaken "601%" -> "60%"
$desc = preg_replace('/\$\(/', '(', $desc);
$desc = preg_replace('/\$\w*sec:secs;/', ' sec', $desc);  // "$lsec:secs;" -> " sec"
$desc = preg_replace('/\s+%/', '%', $desc);               // tidy space before '%'

return $desc;

}



/* -------------------- build data -------------------- */

$tabs = get_tabs_for_class($stat['class']);

/* rank map from character_talent (normalize to 1-based) */
$rankMap = array();
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Character Talents</title>

  <?php
    $cssPath = $_SERVER['DOCUMENT_ROOT'].'/armory/css/talents.css';
    $jsPath  = $_SERVER['DOCUMENT_ROOT'].'/armory/js/talents.js';
  ?>
  <link rel="stylesheet" href="/armory/css/talents.css<?= is_file($cssPath) ? '?v='.filemtime($cssPath) : '' ?>">
  <script defer src="/armory/js/talents.js<?= is_file($jsPath) ? '?v='.filemtime($jsPath) : '' ?>"></script>
</head>
<body class="show-guides">

<div class="parchment-top">
    <div class="parch-profile-banner" id="banner" style="position: absolute;margin-left: 450px!important;margin-top: -110px!important;">
        <h1 style="padding-top: 12px!important;"><?php echo $lang["talents"] ?></h1>
    </div></div>

<div class="parchment-content">

<?php if (empty($tabs)): ?>
  <!-- If no talent tabs are available for this class, show a fallback message -->
  <em>No talent tabs found for this class.</em>
<?php else: ?>
  
 
  <div class="talent-trees">
   
		  <?php foreach ($tabs as $t): ?>
			<?php
			  // Basic info about the talent tab
			  $tabId   = (int)$t['id'];
			  $tabName = (string)$t['name'];
			  $points  = (int)talentCounting($stat['guid'], $tabId);
			  $bgUrl   = talent_bg_for_tab($tabId);

			  // Fetch all talents for this tab
			  $talents = execute_query(
				'armory',
				"SELECT `id`, `row`, `col`, `rank1`, `rank2`, `rank3`, `rank4`, `rank5`
				   FROM `dbc_talent`
				  WHERE `ref_talenttab` = {$tabId}
				  ORDER BY `row`, `col`",
				0
			  ) ?: [];

			  // Index talents by row/column and track deepest row
			  $byPos = []; 
			  $maxRow = 0;
			  foreach ($talents as $tal) {
				$r = (int)$tal['row'];
				$c = (int)$tal['col'];
				$byPos["$r:$c"] = $tal;
				if ($r > $maxRow) $maxRow = $r;
			  }

			  // Pick a tab icon (first valid talent spell’s icon for now)
			  $tabIconName = (function() use ($talents){
				foreach ($talents as $tal) {
				  $sid = first_rank_spell($tal);
				  if ($sid) {
					$r = execute_query('armory',
					  "SELECT i.`name` AS icon
						 FROM `dbc_spell` s
						 LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
						WHERE s.`id` = ".(int)$sid." LIMIT 1", 1);
					if ($r && !empty($r['icon'])) {
					  return strtolower(preg_replace('/[^a-z0-9_]/i', '', $r['icon']));
					}
				  }
				}
				return 'inv_misc_questionmark';
			  })();

			  $tabIconUrlQ = htmlspecialchars(icon_url($tabIconName), ENT_QUOTES);
			  $talentCap   = get_talent_cap(isset($stat['level']) ? (int)$stat['level'] : null);
			?>

				<!-- One talent tree column -->
				<div class="talent-tree" style="background-image:url('<?= htmlspecialchars($bgUrl, ENT_QUOTES) ?>');">
						  <div class="talent-head">
							<span class="talent-head-ico" style="background-image:url('<?= $tabIconUrlQ ?>')"></span>
							<span class="talent-head-title"><?= htmlspecialchars($tabName) ?></span>
							<span class="talent-head-pts">
							  <b class="num"><?= (int)$points ?></b>
							  <span class="slash"> / </span>
							  <span class="cap"><?= (int)$talentCap ?></span>
							</span>
						  </div>

									  <!-- 4-column grid for talents -->
									  <div class="talent-flex">
										<?php
										  $cols = 4;
										  for ($r = 0; $r <= $maxRow; $r++) {							//row loop
											for ($c = 0; $c < $cols; $c++) {							//col loop	
												if (!isset($byPos["$r:$c"])) {										
												echo '<div class="talent-cell placeholder"></div>';
												continue;
											  }
											  $found = $byPos["$r:$c"];

											$max = 0;													//“current/max” and color the cell correctly green/yellow				
											for ($x = 5; $x >= 1; $x--) {
											  if (!empty($found["rank$x"])) { $max = $x; break; }
}


											  // Current trained rank
											  $cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);

											  // Spell info
											  $sp = spell_info_for_talent($found, $cur > 0 ? $cur : 1);
											  $title = htmlspecialchars($sp['name'], ENT_QUOTES);
											  $desc  = htmlspecialchars($sp['desc'], ENT_QUOTES);
											  $icon  = icon_url($sp['icon']);
											  $iconQ = htmlspecialchars($icon, ENT_QUOTES);

											  // Cell state
											  $cellClass = 'talent-cell';
											  if ($cur >= $max && $max > 0)      $cellClass .= ' maxed';
											  elseif ($cur > 0)                  $cellClass .= ' learned';
											  else                               $cellClass .= ' empty';

											  // Render cell
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
  </div>
<?php endif; ?>


</body>
</html>
