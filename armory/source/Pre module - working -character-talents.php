<?php
if (!defined('Armory')) { exit; }

/**
 * character-talents.php
 * - class/tab backgrounds witch icons
 * - hover tooltips from dbc_spell with plain text
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
  $sec = (int)round($sec); // force integer seconds

  if ($sec < 60) {
    return $sec . ' sec';
  }

  $m = floor($sec / 60);
  $s = $sec % 60;

  if ($s === 0) {
    return $m . ' min';
  }
  return $m . ' min ' . $s . ' sec';
}


/* ---- memoized simple lookups ---- */
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

  // ---------- helpers ----------
  $trimNum = static function($v): string {
    $s = number_format((float)$v, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  };

  $rangeText = static function(int $min, int $max): string {
    return ($max > $min) ? ($min . '–' . $max) : (string)$min; // en dash
  };

// Produces min/max/text for $sN.  If $div==1000 and bp<0, treat as negative ms.
$formatS = static function (int $bp, int $dieSides, int $div = 1): array {
  // Special case: cast-time reductions stored as negative milliseconds
  if ($div === 1000 && $bp < 0) {
    $min = abs($bp) / 1000.0;
    $max = $min + ($dieSides > 0 ? $dieSides / 1000.0 : 0.0);
    // collapse if no range
    $txt = ($max > $min) ? rtrim(rtrim(number_format($min,1,'.',''), '0'),'.')
                           .'–'.
                           rtrim(rtrim(number_format($max,1,'.',''), '0'),'.')
                         : rtrim(rtrim(number_format($min,1,'.',''), '0'),'.');
    return [$min, $max, $txt];
  }

  // Normal scalar (damage/heal/etc.)
  $min = $bp + 1;
  if ($dieSides <= 1) {
    $txt = (string)$min;
    return [$min, $min, $txt];
  }
  $max = $bp + $dieSides;
  if ($max < $min) { [$min, $max] = [$max, $min]; }
  return [$min, $max, $min . '–' . $max];
};




  // ---------- cross-spell tokens ----------
  // $12345sN  → min–max (or single value if no range)
  $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) use ($formatS) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
    if (!$row) return '0';
    $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
    $die = _cache("die:$sid:$idx", function() use ($sid,$idx){ return get_die_sides_n($sid,$idx); });
    [, , $text] = $formatS($bp, $die);
    return $text;
  }, $desc);

  // $12345d  → duration
  $desc = preg_replace_callback('/\$(\d+)d\b/', function ($m) {
    $sid   = (int)$m[1];
    $durId = _cache("durid:$sid", function() use ($sid){ return get_spell_duration_id($sid); });
    $secs  = _cache("dursec:$durId", function() use ($durId){ return duration_secs_from_id($durId); });
    return fmt_secs($secs);
  }, $desc);

  // $12345a1 → radius (yards)
  $desc = preg_replace_callback('/\$(\d+)a1\b/', function ($m) {
    $sid = (int)$m[1];
    $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
    if (!$row) return '0';
    $val = _cache("radiusYds:$sid", function() use ($row){ return getRadiusYdsForSpellRow($row); });
    $s = number_format((float)$val, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  }, $desc);

  // $12345oN → total over-time
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

  // $12345tN → tick time (sec)
  $desc = preg_replace_callback('/\$(\d+)t([1-3])\b/', function ($m) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spellO:$sid", function() use ($sid){ return get_spell_o_row($sid); });
    if (!$row) return '0';
    $amp = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $sec = $amp > 0 ? ($amp / 1000.0) : 0.0;
    $s = number_format($sec, 1, '.', '');
    return rtrim(rtrim($s, '0'), '.') ?: '0';
  }, $desc);

  // $12345u → stacks of another spell id
  $desc = preg_replace_callback('/\$(\d+)u\b/', function ($m) {
    $sid = (int)$m[1];
    $n = _stack_amount_for_spell($sid);
    if ($n <= 0) {
      $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
      if ($row) $n = abs((int)($row['effect_basepoints_1'] ?? 0) + 1);
    }
    return (string)max(1, (int)$n);
  }, $desc);

  // $12345n → proc charges of another spell id
  $desc = preg_replace_callback('/\$(\d+)n\b/', function ($m) {
    $sid = (int)$m[1];
    $n = _cache("procchg:$sid", function() use ($sid){ return get_spell_proc_charges($sid); });
    if ($n <= 0) $n = _stack_amount_for_spell($sid);
    if ($n <= 0) {
      $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
      if ($row) $n = abs((int)($row['effect_basepoints_1'] ?? 0) + 1);
    }
    return (string)max(1, (int)$n);
  }, $desc);

  // $12345xN → chaintargets from another spell
  $desc = preg_replace_callback('/\$(\d+)x([1-3])\b/', function($m){
    $sid = (int)$m[1]; $i = (int)$m[2];
    $row = execute_query('armory',
      "SELECT `effect_chaintarget_{$i}` AS x FROM `dbc_spell` WHERE `id`={$sid} LIMIT 1", 1);
    $val = $row ? (int)$row['x'] : 0;
    return (string)max(1, $val);
  }, $desc);

  // ${$*K;sN%} → (K * sNmin)%
  $desc = preg_replace_callback(
    '/\{\$\s*\*\s*([0-9]+)\s*;\s*\$s([1-3])\s*%\s*\}/i',
    function($m) use (&$s1min,&$s2min,&$s3min){
      $k   = (int)$m[1];
      $idx = (int)$m[2];
      $map = [1=>$s1min, 2=>$s2min, 3=>$s3min];
      $base = isset($map[$idx]) ? abs((int)$map[$idx]) : 0;
      return (string)($k * $base) . '%';
    },
    $desc
  );

  // ---------- current spell derived values ----------
  $currId = isset($sp['id']) ? (int)$sp['id'] : 0;

  $die1 = _cache("die:$currId:1", function() use ($currId){ return $currId?get_die_sides_n($currId,1):0; });
  $die2 = _cache("die:$currId:2", function() use ($currId){ return $currId?get_die_sides_n($currId,2):0; });
  $die3 = _cache("die:$currId:3", function() use ($currId){ return $currId?get_die_sides_n($currId,3):0; });

  $formatSLocal = $formatS;
  list($s1min,$s1max,$s1txt) = $formatS($sp['effect_basepoints_1'], $die1, 1);
  list($s2min,$s2max,$s2txt) = $formatS($sp['effect_basepoints_2'], $die2, 1000);
  list($s3min,$s3max,$s3txt) = $formatSLocal((int)($sp['effect_basepoints_3'] ?? 0), $die3);

  // ---------- divisor form: $/N; $sN  or  $/N; $<id>sN  ----------
  // Now returns a range when dividing sN (min and max both divided).
  $desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',
    function ($m) use ($s1min,$s1max,$s2min,$s2max,$s3min,$s3max,$formatSLocal,$rangeText) {
      $div     = max(1.0, (float)$m[1]);
      $spellId = $m[2] ? (int)$m[2] : 0;
      $type    = strtolower($m[3]);
      $idx     = (int)$m[4];

      if ($type === 's') {
        // ---- scalar range (min–max) ----
        if ($spellId === 0) {
          $mins = [1=>$s1min, 2=>$s2min, 3=>$s3min];
          $maxs = [1=>$s1max, 2=>$s2max, 3=>$s3max];
          $min = abs((int)($mins[$idx] ?? 0));
          $max = abs((int)($maxs[$idx] ?? 0));
        } else {
          $row = _cache("spell:$spellId", function() use ($spellId){ return get_spell_row($spellId); });
          if (!$row) return '0';
          $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
          $die = _cache("die:$spellId:$idx", function() use ($spellId,$idx){ return get_die_sides_n($spellId,$idx); });
          list($min,$max) = $formatSLocal($bp,$die);
        }
        $minOut = $min / $div;
        $maxOut = $max / $div;
        // format like the rest of the tooltips
        $fmt = function($v){
          $s = number_format($v, 1, '.', '');
          return rtrim(rtrim($s, '0'), '.') ?: '0';
        };
        return $rangeText((float)$fmt($minOut), (float)$fmt($maxOut));
      }

      // ---- over-time 'oN' remains scalar total; divide normally ----
      $row = ($spellId === 0) ? null : _cache("spellO:$spellId", function() use ($spellId){ return get_spell_o_row($spellId); });
      if (!$row && $spellId !== 0) return '0';

      $bp   = ($spellId === 0) ? null : abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
      $amp  = ($spellId === 0) ? null : (int)($row["effect_amplitude_{$idx}"] ?? 0);
      $dur  = ($spellId === 0) ? 0 : duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
      $ticks= ($amp && $amp > 0) ? (int)floor(($dur * 1000)/$amp) : 0;
      $val  = ($spellId === 0) ? 0 : ($ticks > 0 ? $bp * $ticks : $bp);

      $out  = ($div > 0) ? ($val / $div) : $val;
      $s    = number_format($out, 1, '.', '');
      return rtrim(rtrim($s, '0'), '.') ?: '0';
    },
    $desc
  );

  // ---------- duration aggregation for $d and totals ----------
  $getDurSecBySpellId = function($sid){
    if ($sid <= 0) return 0;
    $durId = _cache("durid:$sid", function() use ($sid){ return get_spell_duration_id($sid); });
    return _cache("dursec:$durId", function() use ($durId){ return duration_secs_from_id($durId); });
  };

  $currId  = isset($sp['id']) ? (int)$sp['id'] : 0;
  $durSecs = $getDurSecBySpellId($currId);

  if (strpos($desc, '$d') !== false) {
    $seen  = []; $queue = [$currId]; $depth = 0;
    while (!empty($queue) && $depth < 2) {
      $next = [];
      foreach ($queue as $sid) {
        if ($sid <= 0 || isset($seen[$sid])) continue;
        $seen[$sid] = true;

        $ds = $getDurSecBySpellId($sid);
        if ($ds > $durSecs) $durSecs = $ds;

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
      $queue = $next; $depth++;
    }

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

  // over-time totals for current spell (o1..o3)
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

  // headline value fallback
  $h  = (int)($sp['proc_chance'] ?? 0);
  if ($h <= 0) $h = $s1min;

  // radius & tick times for current spell
  $a1 = $trimNum(getRadiusYdsForSpellRow($sp));
  $t1 = $trimNum(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000.0);
  $t2 = $trimNum(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000.0);
  $t3 = $trimNum(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000.0);

  // ${AP*$mN/100} → “(Attack Power * N / 100)”
  $desc = preg_replace_callback(
    '/\{\$\s*(AP|RAP|SP)\s*\*\s*\$m([1-3])\s*\/\s*100\s*\}/i',
    function ($m) use ($s1min, $s2min, $s3min) {
      $idx  = (int)$m[2];
      $map  = [1 => $s1min, 2 => $s2min, 3 => $s3min];
      $pct  = (int)abs($map[$idx] ?? 0);
      $labels = ['AP'=>'Attack Power','RAP'=>'Ranged Attack Power','SP'=>'Spell Power'];
      $label  = $labels[strtoupper($m[1])] ?? strtoupper($m[1]);
      return '(' . $label . ' * ' . $pct . ' / 100)';
    },
    $desc
  );

  // $m1/$m2/$m3 → min only (no ranges)
  $desc = preg_replace_callback('/\$(m[1-3])\b/', function($m) use ($s1min,$s2min,$s3min){
    switch ($m[1]) {
      case 'm1': return (string)$s1min;
      case 'm2': return (string)$s2min;
      case 'm3': return (string)$s3min;
    }
    return $m[0];
  }, $desc);



  // $n (proc charges) – fallback to cached lookup
  $procN = (int)($sp['proc_charges'] ?? 0);
  if ($procN <= 0 && isset($sp['id'])) $procN = (int)get_spell_proc_charges((int)$sp['id']);
  if ($procN > 0) $desc = preg_replace('/\$n\b/i', (string)$procN, $desc);

  // $xN for current spell
  $desc = preg_replace_callback('/\$x([1-3])\b/', function($m) use ($sp){
    $i   = (int)$m[1];
    $val = (int)($sp["effect_chaintarget_{$i}"] ?? 0);
    return (string)max(1, $val);
  }, $desc);

  // $u (max stacks for current spell)
  $u = 1;
  if (!empty($sp['id'])) {
    $u = _stack_amount_for_spell((int)$sp['id']);
    if ($u <= 0) $u = abs((int)($sp['effect_basepoints_1'] ?? 0) + 1);
    if ($u < 1)  $u = 1;
  }

  // Grammar: $l<singular>:<plural>;
  while (preg_match('/\$l([^:;]+):([^;]+);/', $desc, $m, PREG_OFFSET_CAPTURE)) {
    $full     = $m[0][0];
    $offset   = $m[0][1];
    $singular = $m[1][0];
    $plural   = $m[2][0];

    $before = substr($desc, 0, $offset);
    $val = 2;
    if (preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/', $before, $nm)) $val = (float)$nm[1];
    $word = (abs($val - 1.0) < 1e-6) ? $singular : $plural;

    $desc = substr($desc, 0, $offset) . $word . substr($desc, $offset + strlen($full));
  }

  // $*factor; token  (supports s1..s3 & o1..o3 & m1..m3). For sN we multiply the **min** by design.
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
      $s = number_format($val, 1, '.', '');
      $s = rtrim(rtrim($s, '0'), '.');
      return ($s === '') ? '0' : $s;
    }, $desc
  );

// Divisor form: $/N; $sN / $mN / $<id>sN  → divide both min & max; oN stays scalar.
  $desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',
    function ($m) use ($s1min,$s1max,$s2min,$s2max,$s3min,$s3max,$formatSLocal,$rangeText) {
      $div     = max(1.0, (float)$m[1]);
      $spellId = $m[2] ? (int)$m[2] : 0;
      $type    = strtolower($m[3]);
      $idx     = (int)$m[4];

      if ($type === 's') {
        // compute min/max, then divide both
        if ($spellId === 0) {
          $mins = [1=>$s1min, 2=>$s2min, 3=>$s3min];
          $maxs = [1=>$s1max, 2=>$s2max, 3=>$s3max];
          $min = abs((int)($mins[$idx] ?? 0));
          $max = abs((int)($maxs[$idx] ?? 0));
        } else {
          $row = _cache("spell:$spellId", function() use ($spellId){ return get_spell_row($spellId); });
          if (!$row) return '0';
          $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
          $die = _cache("die:$spellId:$idx", function() use ($spellId,$idx){ return get_die_sides_n($spellId,$idx); });
          list($min,$max) = $formatSLocal($bp,$die);
        }

        $fmt = function($v){
          $s = number_format($v, 1, '.', '');
          return rtrim(rtrim($s, '0'), '.') ?: '0';
        };

        $minOut = (float)$fmt($min / $div);
        $maxOut = (float)$fmt($max / $div);
        return ($maxOut > $minOut) ? ($minOut . '–' . $maxOut) : (string)$minOut;
      }

    // ---- over-time 'oN' remains scalar total; divide normally ----
    $row = ($spellId === 0) ? null : _cache("spellO:$spellId", function() use ($spellId){ return get_spell_o_row($spellId); });
    if (!$row && $spellId !== 0) return '0';

    $bp   = ($spellId === 0) ? null : abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
    $amp  = ($spellId === 0) ? null : (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $dur  = ($spellId === 0) ? 0 : duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
    $ticks= ($amp && $amp > 0) ? (int)floor(($dur * 1000)/$amp) : 0;
    $val  = ($spellId === 0) ? 0 : ($ticks > 0 ? $bp * $ticks : $bp);

    $out  = ($div > 0) ? ($val / $div) : $val;
    $s    = number_format($out, 1, '.', '');
    return rtrim(rtrim($s, '0'), '.') ?: '0';
  },
  $desc
);


  // alias: $D == $d
  $desc = str_replace('$D', $d, $desc);

  // Final map for current spell tokens
  $desc = strtr($desc, [
    '$s1' => $s1txt, '$s2' => $s2txt, '$s3' => $s3txt,   // now min–max where applicable
    '$o1' => $o1,    '$o2' => $o2,    '$o3' => $o3,
    '$t1' => $t1,    '$t2' => $t2,    '$t3' => $t3,
    '$a1' => $a1,
    '$d'  => $d,
    '$h'  => (string)max(0,(int)($sp['proc_chance'] ?? 0)),
    '$u'  => (string)$u,
  ]);

  // cleanup
  $desc = preg_replace('/(\d+)1%/', '$1%', $desc);
  $desc = preg_replace('/\$\(/', '(', $desc);
  $desc = preg_replace('/\$\w*sec:secs;/', ' sec', $desc);
  $desc = preg_replace('/\s+%/', '%', $desc);

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
</head>
<body class="show-guides">

	<style>
:root {
  --talent-head-h: 40px; /* header height */
}

/* ===== container for all trees ===== */
.talent-trees {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: nowrap;
  max-width: 980px;
  margin: 0 auto;
}

/* ===== each tree column box ===== */
.talent-tree {
  position: relative;
  flex: 1 0 256px;
  min-height: auto;
  background-position: center;
  background-size: 100% 100%;
  border-radius: 12px;
  background-color: #0b0c10; /* black frame */
  padding: calc(var(--talent-head-h) + 10px) 10px 12px;
  box-shadow: inset 0 0 0 2px rgba(0,0,0,.85),
              0 6px 16px rgba(0,0,0,.45);
}

/* ===== header pill ===== */
.talent-head {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: var(--talent-head-h);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px 0 44px;
  border-radius: 8px;
  background: #151515;
  box-shadow: 0 1px 0 rgba(255,255,255,.05),
              inset 0 0 0 1px rgba(0,0,0,.65);
  z-index: 2;
}

.talent-head-ico {
  position: absolute;
  left: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  box-shadow: 0 0 0 2px rgba(0,0,0,.7),
              0 0 6px rgba(0,0,0,.5) inset;
}

.talent-head-title {
  font: 700 20px/1 "Trebuchet MS", Arial, sans-serif;
  color: #d8e0ea;
  text-shadow: 0 1px 0 #000;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.talent-head-pts {
  font: 700 20px/1 "Trebuchet MS", Arial, sans-serif;
  color: #c9d4df;
  display: flex;
  align-items: center;
  gap: 2px;
}

/* ===== 4-column grid ===== */
.talent-flex {
  --cell: 48px;
  --gap: 10px;
  position: relative;
  margin: 0 auto;
  width: calc(var(--cell) * 4 + var(--gap) * 3);
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap);
  justify-content: center;
}

/* ===== talent cells ===== */
.talent-cell {
  position: relative;
  width: var(--cell);
  height: var(--cell);
  border-radius: 6px;
  background: #2a2a2a;
  background-image: var(--icon);
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  box-shadow: inset 0 0 0 1px #555;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  font: 12px/1.2 "Trebuchet MS", Arial, sans-serif;
  color: #ddd;
}

.talent-cell.placeholder {
  visibility: hidden;
  box-shadow: none;
  pointer-events: none;
}

/* ===== rank badge ===== */
.talent-rank {
  position: absolute;
  right: 2px;
  bottom: 2px;
  padding: 0 6px;
  border-radius: 8px;
  background: #000;
  font-weight: bold;
  font-size: 12px;
  line-height: 1;
  color: #999;
}

/* ===== states ===== */
.talent-cell.empty {
  filter: grayscale(100%) brightness(.8);
  box-shadow: inset 0 0 0 1px #555;
}
.talent-cell.empty .talent-rank { color: #999; }

.talent-cell.learned {
  filter: none;
  box-shadow: inset 0 0 0 2px #00ff00;
}
.talent-cell.learned .talent-rank { color: #00ff00; }

.talent-cell.maxed {
  filter: none;
  box-shadow: inset 0 0 0 2px #ffd700;
}
.talent-cell.maxed .talent-rank { color: #ffd700; }

/* ===== hover flair ===== */
.talent-cell:hover {
  transform: scale(1.1);
  z-index: 10;
  box-shadow: 0 0 8px 2px rgba(255,255,200,.7),
              inset 0 0 0 2px #fff;
}
.talent-cell.learned:hover {
  box-shadow: 0 0 8px 2px rgba(0,255,0,.7),
              inset 0 0 0 2px #00ff00;
}
.talent-cell.maxed:hover {
  box-shadow: 0 0 8px 2px rgba(255,215,0,.8),
              inset 0 0 0 2px #ffd700;
}
/* ===== tooltip (top-right anchored) ===== */
		.talent-tt{
		  position:fixed; z-index:9999; min-width:180px; max-width:280px;
		  padding:14px; background:rgba(16,24,48,.78);
		  border:1px solid rgba(200,220,255,.18); border-radius:10px;
		  box-shadow:0 10px 30px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.04);
		  color:#e9eefb; font:14px/1.45 "Trebuchet MS", Arial, sans-serif;
		  pointer-events:none; backdrop-filter:blur(2px);
		}
		.talent-tt::before, .talent-tt::after{ content:none !important; }
		.talent-tt h5{ margin:0 0 8px; font-size:22px; font-weight:800; letter-spacing:.2px; color:#f1f6ff; }
		.talent-tt p{ margin:0; white-space:normal; color:#f3e0b3; text-shadow:0 1px 0 rgba(0,0,0,.25); }

</style>

<div class="parchment-top"></div>

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
										  for ($r = 0; $r <= $maxRow; $r++) {
											for ($c = 0; $c < $cols; $c++) {
											  if (!isset($byPos["$r:$c"])) {
												echo '<div class="talent-cell placeholder"></div>';
												continue;
											  }
											  $found = $byPos["$r:$c"];

											  // Count max ranks
											  $max = 0;
											  for ($x = 1; $x <= 5; $x++) {
												if (!empty($found["rank$x"])) $max = $x;
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
											  else                                $cellClass .= ' empty';

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

</div>



<script>
(function(){
  const tt = document.createElement('div');
  tt.className = 'talent-tt';
  tt.style.display = 'none';
  document.body.appendChild(tt);

  let showTimer = null;
  let anchorEl = null;

  function render(el){
    const title = el.getAttribute('data-tt-title') || '';
    const desc  = el.getAttribute('data-tt-desc')  || '';
    tt.innerHTML = '<h5>'+title+'</h5><p>'+desc+'</p>';
  }

  function placeToTopRight(el){
    const pad = 8;
    const vw = innerWidth;

    const rEl = el.getBoundingClientRect();
    const rTT = tt.getBoundingClientRect();

    let left = rEl.right + pad;
    let top  = rEl.top - rTT.height - pad;

    if (left + rTT.width > vw - 6) left = vw - rTT.width - 6;
    if (left < 6) left = 6;
    if (top < 6) top = rEl.bottom + pad;

    tt.style.left = left + 'px';
    tt.style.top  = top + 'px';
  }

  function show(el){
    anchorEl = el;
    render(el);
    tt.style.display = 'block';
    placeToTopRight(el);
  }

  function hide(){
    clearTimeout(showTimer);
    tt.style.display = 'none';
    anchorEl = null;
  }

  document.addEventListener('mouseover', function(e){
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    clearTimeout(showTimer);
    showTimer = setTimeout(function(){ show(el); }, 60);
  });

  document.addEventListener('mouseout', function(e){
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    if (!e.relatedTarget || !el.contains(e.relatedTarget)) hide();
  });

  document.addEventListener('scroll', function(){
    if (tt.style.display !== 'none' && anchorEl) placeToTopRight(anchorEl);
  }, {passive:true});

  window.addEventListener('resize', function(){
    if (tt.style.display !== 'none' && anchorEl) placeToTopRight(anchorEl);
  });
})();
</script>

</body>
</html>
