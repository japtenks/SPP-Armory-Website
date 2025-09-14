*** a/character-talents.php
--- b/character-talents.php
***************
*** 1,7 ****
  <?php
  if (!defined('Armory')) { exit; }
  
  /**
   * character-talents.php
!  * - class/tab backgrounds witch icons
   * - hover tooltips from dbc_spell with plain text
   *
   * Requires (your current schema):
   *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
   *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
--- 1,10 ----
+ <?php declare(strict_types=1);
+ 
  <?php
  if (!defined('Armory')) { exit; }
  
  /**
   * character-talents.php
!  * - class/tab backgrounds with icons
   * - hover tooltips from dbc_spell with plain text
   *
   * Requires (your current schema):
   *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
   *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
***************
*** 25,36 ****
  /* -------------------- helpers -------------------- */
  
  /** table exists in given connection */
  function tbl_exists($conn, $table) {
-   return (bool) execute_query(
+   // Harden identifier: only allow simple table names
+   if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
+   return (bool) execute_query(
      $conn,
      "SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
-         AND TABLE_NAME = '".addslashes($table)."'
+         AND TABLE_NAME = '{$table}'
        LIMIT 1",
      2
    );
  }
  function get_talent_cap(?int $level): int {
***************
*** 41,51 ****
  }
  
  /** tabs (id, name, tab_number) for a class id */
  function get_tabs_for_class($classId) {
    $mask = 1 << ((int)$classId - 1);
    return execute_query(
      'armory',
      "SELECT `id`, `name`, `tab_number`
         FROM `dbc_talenttab`
!       WHERE `refmask_chrclasses` = {$mask}
        ORDER BY `tab_number` ASC",
      0
    ) ?: [];
  }
  
--- 45,58 ----
  }
  
  /** tabs (id, name, tab_number) for a class id */
  function get_tabs_for_class($classId) {
    $mask = 1 << ((int)$classId - 1);
    return execute_query(
      'armory',
      "SELECT `id`, `name`, `tab_number`
         FROM `dbc_talenttab`
!       WHERE (`refmask_chrclasses` & {$mask}) <> 0
        ORDER BY `tab_number` ASC",
      0
    ) ?: [];
  }
  
***************
*** 52,74 ****
  /** prefer character_talent; else derive from character_spell */
! function current_rank_for_talent($guid, array $talRow, array $rankMap, $hasCharSpell) {
    $tid = (int)$talRow['id'];
    if (isset($rankMap[$tid])) return (int)$rankMap[$tid]; // already 1-based
-   if ($hasCharSpell) {
-     for ($r = 5; $r >= 1; $r--) {
-       $spell = (int)$talRow["rank{$r}"];
-       if ($spell > 0) {
-         $has = execute_query(
-           'char',
-           "SELECT 1 FROM `character_spell`
-             WHERE `guid` = ".(int)$guid."
-               AND `spell` = ".(int)$spell."
-               AND `disabled` = 0
-             LIMIT 1",
-           2
-         );
-         if ($has) return $r;
-       }
-     }
-   }
+   // Fast path: check pre-fetched learned spells (no DB calls here)
+   if ($hasCharSpell && !empty($talRow)) {
+     for ($r = 5; $r >= 1; $r--) {
+       $spell = (int)$talRow["rank{$r}"];
+       if ($spell > 0 && !empty($GLOBALS['__LEARNED_SPELLS'][$spell])) return $r;
+     }
+   }
    return 0;
  }
  
  /** first non-zero rank spell id */
  function first_rank_spell(array $tal) {
--- 59,77 ----
  /** prefer character_talent; else derive from character_spell */
! function current_rank_for_talent($guid, array $talRow, array $rankMap, $hasCharSpell, array $learnedSpells = []) {
    $tid = (int)$talRow['id'];
    if (isset($rankMap[$tid])) return (int)$rankMap[$tid]; // already 1-based
+   // Prefer injected set; fall back to global if provided
+   if (empty($learnedSpells) && isset($GLOBALS['__LEARNED_SPELLS'])) {
+     $learnedSpells = (array)$GLOBALS['__LEARNED_SPELLS'];
+   }
+   if ($hasCharSpell && $learnedSpells) {
+     for ($r = 5; $r >= 1; $r--) {
+       $spell = (int)$talRow["rank{$r}"];
+       if ($spell > 0 && !empty($learnedSpells[$spell])) return $r;
+     }
+   }
    return 0;
  }
  
  /** first non-zero rank spell id */
  function first_rank_spell(array $tal) {
***************
*** 78,83 ****
--- 81,104 ----
    return 0;
  }
  
+ /** numeric formatting helper */
+ function num_trim($v): string {
+   $s = number_format((float)$v, 1, '.', '');
+   $s = rtrim(rtrim($s, '0'), '.');
+   return ($s === '') ? '0' : $s;
+ }
+ 
+ /** cached chain-target lookup */
+ function get_spell_chain_targets(int $id, int $n): int {
+   $n = max(1, min(3, $n));
+   return _cache("chain:$id:$n", function() use ($id,$n){
+     $row = execute_query('armory',
+       "SELECT `effect_chaintarget_{$n}` AS x FROM `dbc_spell`
+          WHERE `id`=".(int)$id." LIMIT 1", 1);
+     return $row ? (int)$row['x'] : 0;
+   });
+ }
+ 
  /** Spell info (name/description/icon) for the talent row at a given rank */
  function spell_info_for_talent(array $talRow, int $rank = 0) {
      // find the highest non-zero rank present in DBC (1..5)
      $maxRank = 0;
      for ($r = 5; $r >= 1; $r--) {
***************
*** 117,123 ****
      if (!$sp || !is_array($sp)) {
          return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];
      }
  
-     $desc = build_tooltip_desc($sp);
+     $desc = build_tooltip_desc($sp, $useRank, $maxRank);
  
      $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
      if ($icon === '') $icon = 'inv_misc_questionmark';
  
--- 138,144 ----
***************
*** 185,190 ****
--- 206,212 ----
  }
  
  /* ---- memoized simple lookups ---- */
  function _cache($key, callable $fn) {
      static $C = [];
+     // note: static cache is per-request; safe for PHP-FPM
      if (isset($C[$key])) return $C[$key];
      $C[$key] = $fn();
      return $C[$key];
  }
  
***************
*** 310,322 ****
  /* -------------------- tooltip builder -------------------- */
  // Build a clean tooltip description for one spell row
  
! function build_tooltip_desc(array $sp): string {
    $desc = (string)($sp['description'] ?? '');
  
-   $trimNum = static function($v): string {
-     $s = number_format((float)$v, 1, '.', '');
-     $s = rtrim(rtrim($s, '0'), '.');
-     return ($s === '') ? '0' : $s;
-   };
- 
    $rangeText = static function(int $min, int $max): string {
      return ($max > $min) ? ($min . '–' . $max) : (string)$min; // en dash
    };
  
--- 332,341 ----
  /* -------------------- tooltip builder -------------------- */
  // Build a clean tooltip description for one spell row
  
! function build_tooltip_desc(array $sp, int $curRank = 1, int $maxRank = 1): string {
    $desc = (string)($sp['description'] ?? '');
  
    $rangeText = static function(int $min, int $max): string {
      return ($max > $min) ? ($min . '–' . $max) : (string)$min; // en dash
    };
  
***************
*** 354,359 ****
--- 373,409 ----
  };
  
  
+   // ---- Establish current spell dice and $s1/$s2/$s3 EARLY (so later regexes can use them)
+   $currId = isset($sp['id']) ? (int)$sp['id'] : 0;
+   $die1 = _cache("die:$currId:1", fn() => $currId ? get_die_sides_n($currId,1) : 0);
+   $die2 = _cache("die:$currId:2", fn() => $currId ? get_die_sides_n($currId,2) : 0);
+   $die3 = _cache("die:$currId:3", fn() => $currId ? get_die_sides_n($currId,3) : 0);
+ 
+   $formatSLocal = $formatS;
+   list($s1min,$s1max,$s1txt) = $formatSLocal((int)($sp['effect_basepoints_1'] ?? 0), $die1);
+   list($s2min,$s2max,$s2txt) = $formatSLocal((int)($sp['effect_basepoints_2'] ?? 0), $die2);
+   list($s3min,$s3max,$s3txt) = $formatSLocal((int)($sp['effect_basepoints_3'] ?? 0), $die3);
+ 
+   // ${$*K;sN%} → K * sNmin %
+   $desc = preg_replace_callback(
+     '/\{\$\s*\*\s*([0-9]+)\s*;\s*\$s([1-3])\s*%\s*\}/i',
+     function($m) use ($s1min,$s2min,$s3min){
+       $k   = (int)$m[1];
+       $idx = (int)$m[2];
+       $map = array(1=>$s1min, 2=>$s2min, 3=>$s3min);
+       $base = isset($map[$idx]) ? abs($map[$idx]) : 0;
+       $val  = $k * $base;
+       return (string)$val . '%';
+     },
+     $desc
+   );
+ 
+   // $m1/$m2/$m3 → sNmin
+   $desc = preg_replace_callback('/\$(m[1-3])\b/', function($m) use ($s1min,$s2min,$s3min){
+     return match ($m[1]) { 'm1' => (string)$s1min, 'm2' => (string)$s2min, 'm3' => (string)$s3min, default => $m[0] };
+   }, $desc);
+ 
+ 
    // $12345sN
    $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) use ($formatS) {
      $sid = (int)$m[1]; $idx = (int)$m[2];
      $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
      if (!$row) return '0';
***************
*** 377,383 ****
      $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
      if (!$row) return '0';
      $val = _cache("radiusYds:$sid", function() use ($row){ return getRadiusYdsForSpellRow($row); });
-     $s = number_format((float)$val, 1, '.', '');
-     $s = rtrim(rtrim($s, '0'), '.');
-     return ($s === '') ? '0' : $s;
+     return num_trim($val);
    }, $desc);
  
    // $12345oN
    $desc = preg_replace_callback('/\$(\d+)o([1-3])\b/', function ($m) {
      $sid = (int)$m[1]; $idx = (int)$m[2];
***************
*** 423,438 ****
  
-   
    // $12345xN (total chain targets from another spell's EffectN)
- $desc = preg_replace_callback('/\$(\d+)x([1-3])\b/', function($m){
-   $sid = (int)$m[1]; $i = (int)$m[2];
-   $row = execute_query('armory',
-     "SELECT `effect_chaintarget_{$i}` AS x FROM `dbc_spell` WHERE `id`={$sid} LIMIT 1", 1);
-   $val = $row ? (int)$row['x'] : 0;
-   if ($val <= 0) $val = 1;
-   return (string)$val;
- }, $desc);
+   $desc = preg_replace_callback('/\$(\d+)x([1-3])\b/', function($m){
+     $sid = (int)$m[1]; $i = (int)$m[2];
+     $val = get_spell_chain_targets($sid, $i);
+     if ($val <= 0) $val = 1;
+     return (string)$val;
+   }, $desc);
  
- /* ${$*K;sN%}  →  (K * sNmin)%   e.g., ${$*5;s1%} -> 5 * $s1 = 15% */
- $desc = preg_replace_callback(
-   '/\{\$\s*\*\s*([0-9]+)\s*;\s*\$s([1-3])\s*%\s*\}/i',
-   function($m) use ($s1min,$s2min,$s3min){
-     $k   = (int)$m[1];
-     $idx = (int)$m[2];
-     $map = array(1=>$s1min, 2=>$s2min, 3=>$s3min);
-     $base = isset($map[$idx]) ? abs($map[$idx]) : 0;
-     $val  = $k * $base;
-     return (string)$val . '%';
-   },
-   $desc
- );
  
  
  
    // ---- Current spell values
-   $currId = isset($sp['id']) ? (int)$sp['id'] : 0;
  
-   $die1 = _cache("die:$currId:1", function() use ($currId){ return $currId?get_die_sides_n($currId,1):0; });
-   $die2 = _cache("die:$currId:2", function() use ($currId){ return $currId?get_die_sides_n($currId,2):0; });
-   $die3 = _cache("die:$currId:3", function() use ($currId){ return $currId?get_die_sides_n($currId,3):0; });
- 
-   $formatSLocal = $formatS;
-   list($s1min,$s1max,$s1txt) = $formatSLocal((int)($sp['effect_basepoints_1'] ?? 0), $die1);
-   list($s2min,$s2max,$s2txt) = $formatSLocal((int)($sp['effect_basepoints_2'] ?? 0), $die2);
-   list($s3min,$s3max,$s3txt) = $formatSLocal((int)($sp['effect_basepoints_3'] ?? 0), $die3);
  
    // $/N; $sN and $/N; $<id>sN / $<id>oN
    $desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',
      function ($m) use ($s1min,$s2min,$s3min,$formatSLocal) {
***************
*** 474,479 ****
--- 445,451 ----
        }
        $s = number_format($out, 1, '.', '');
        return rtrim(rtrim($s, '0'), '.') ?: '0';
      },
      $desc
    );
***************
*** 488,493 ****
--- 460,466 ----
  
  $currId  = isset($sp['id']) ? (int)$sp['id'] : 0;
  $durSecs = $getDurSecBySpellId($currId);
  
  if (strpos($desc, '$d') !== false) {
+   // Phase: duration discovery (forward + reverse trigger hops)
    /* ---- forward: follow children triggered by this spell ---- */
    $seen  = array();
    $queue = array($currId);
    $depth = 0;
  
***************
*** 557,562 ****
--- 530,536 ----
    })();
  
    // headline value
    $h  = (int)($sp['proc_chance'] ?? 0);
    if ($h <= 0) $h = $s1min;
+   if ($h < 0)  $h = abs($h);
  
    // radius & tick times
-   $a1 = $trimNum(getRadiusYdsForSpellRow($sp));
-   $t1 = $trimNum(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000.0);
-   $t2 = $trimNum(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000.0);
-   $t3 = $trimNum(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000.0);
+   $a1 = num_trim(getRadiusYdsForSpellRow($sp));
+   $t1 = num_trim(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000.0);
+   $t2 = num_trim(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000.0);
+   $t3 = num_trim(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000.0);
  
***************
*** 571,587 ****
      },
      $desc
  );
  
  
- 
-   // $m1/$m2/$m3
-   $desc = preg_replace_callback('/\$(m[1-3])\b/', function($m) use ($s1min,$s2min,$s3min){
-     switch ($m[1]) { case 'm1': return (string)$s1min; case 'm2': return (string)$s2min; case 'm3': return (string)$s3min; }
-     return $m[0];
-   }, $desc);
- 
    // $n (proc charges) – fallback to cached lookup
    $procN = (int)($sp['proc_charges'] ?? 0);
    if ($procN <= 0 && isset($sp['id'])) $procN = (int)get_spell_proc_charges((int)$sp['id']);
    if ($procN > 0) $desc = preg_replace('/\$n\b/i', (string)$procN, $desc);
    
    // $xN  (total chain targets from current spell's EffectN)
- $desc = preg_replace_callback('/\$x([1-3])\b/', function($m) use ($sp){
+ $desc = preg_replace_callback('/\$x([1-3])\b/', function($m) use ($sp){
    $i   = (int)$m[1];
-   $val = (int)($sp["effect_chaintarget_{$i}"] ?? 0);
+   $val = get_spell_chain_targets((int)($sp['id'] ?? 0), $i);
    if ($val <= 0) $val = 1; // safe fallback: at least 1 target
    return (string)$val;
  }, $desc);
***************
*** 630,642 ****
  $desc = preg_replace_callback('/\$\{([0-9]+)\s*-\s*([0-9]+)\/([0-9]+)\}/',
-   function($m) use ($cur,$max) {
+   function($m) use ($curRank,$maxRank) {
      $min = (int)$m[1];
      $maxVal = (int)$m[2];
      $div = (int)$m[3];
      if ($div <= 0) $div = 1;
  
      // linear scale based on current rank (1..max)
-     $steps = max(1, $max-1);
-     $progress = ($max > 1) ? ($cur-1)/$steps : 0;
+     $steps = max(1, $maxRank-1);
+     $progress = ($maxRank > 1) ? ($curRank-1)/$steps : 0;
      $val = $min + ($maxVal - $min) * $progress;
      $val = $val / $div;
  
      // clean formatting
      $s = number_format($val, 1, '.', '');
***************
*** 656,668 ****
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
--- 509,526 ----
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
***************
*** 684,689 ****
--- 542,595 ----
  
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
+ 
+ // Prefetch learned spells once to avoid N+1 queries in rank resolver
+ $learnedSpells = [];
+ if ($hasCharSpell) {
+   $rows = execute_query(
+     'char',
+     "SELECT `spell` FROM `character_spell`
+       WHERE `guid`=".(int)$stat['guid']." AND `disabled`=0",
+     0
+   );
+   foreach ((array)$rows as $r) $learnedSpells[(int)$r['spell']] = true;
+ }
+ // Also expose globally for safety if a downstream call doesn’t thread it
+ $GLOBALS['__LEARNED_SPELLS'] = $learnedSpells;
  
  ?>
  
  <!DOCTYPE html>
  <html lang="en">
***************
*** 724,730 ****
--- 630,636 ----
  			  $talentCap   = get_talent_cap(isset($stat['level']) ? (int)$stat['level'] : null);
  			?>
  
  				<!-- One talent tree column -->
  				<div class="talent-tree" style="background-image:url('<?= htmlspecialchars($bgUrl, ENT_QUOTES) ?>');">
  						  <div class="talent-head">
  							<span class="talent-head-ico" style="background-image:url('<?= $tabIconUrlQ ?>')"></span>
  							<span class="talent-head-title"><?= htmlspecialchars($tabName) ?></span>
  							<span class="talent-head-pts">
***************
*** 757,779 ****
  											  $found = $byPos["$r:$c"];
  
  											$max = 0;													//“current/max” and color the cell correctly green/yellow				
  											for ($x = 5; $x >= 1; $x--) {
  											  if (!empty($found["rank$x"])) { $max = $x; break; }
  }
  
  
  											  // Current trained rank
! 											  $cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);
  
  											  // Spell info
! 											  $sp = spell_info_for_talent($found, $cur > 0 ? $cur : 1);
  											  $title = htmlspecialchars($sp['name'], ENT_QUOTES);
! 											  $desc  = htmlspecialchars($sp['desc'], ENT_QUOTES);
  											  $icon  = icon_url($sp['icon']);
  											  $iconQ = htmlspecialchars($icon, ENT_QUOTES);
  
  											  // Cell state
  											  $cellClass = 'talent-cell';
--- 663,689 ----
  											  $found = $byPos["$r:$c"];
  
  											$max = 0;													//“current/max” and color the cell correctly green/yellow				
  											for ($x = 5; $x >= 1; $x--) {
  											  if (!empty($found["rank$x"])) { $max = $x; break; }
  }
  
  
  											  // Current trained rank
! 											  $cur = current_rank_for_talent(
! 												  (int)$stat['guid'],
! 												  $found,
! 												  $rankMap,
! 												  $hasCharSpell,
! 												  $learnedSpells
! 											  );
  
  											  // Spell info
! 											  $sp = spell_info_for_talent($found, $cur > 0 ? $cur : 1);
  											  $title = htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8');
! 											  $desc  = htmlspecialchars($sp['desc'], ENT_QUOTES, 'UTF-8');
  											  $icon  = icon_url($sp['icon']);
  											  $iconQ = htmlspecialchars($icon, ENT_QUOTES);
  
  											  // Cell state
  											  $cellClass = 'talent-cell';
