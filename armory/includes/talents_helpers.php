<?php
/** 	
*	character-talents.php helper file
*	functions that support the talents and tooltips 
*	designed by japtenks and gpt-5
**/

if (!defined('Armory')) { exit; }

/* -------------------- helpers -------------------- */
function talent_bg_for_tab($tabId) {
  $webBase = '/armory/shared/icon_talents';
  // Use project root (one level up from /includes)
  $fsBase  = realpath(dirname(__DIR__) . '/shared/icon_talents');
  if (!$fsBase) return '';
  $file = (int)$tabId . '.jpg';
  $fs   = $fsBase . DIRECTORY_SEPARATOR . $file;
  return is_file($fs) ? ($webBase . '/' . $file) : '';
}

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
?>