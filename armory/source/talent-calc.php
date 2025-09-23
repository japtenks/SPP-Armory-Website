<?php
if (!defined('Armory')) { exit; }

/**
 * Talent calculator (view-only)
 * - class/tab backgrounds with icons
 * - hover tooltips from dbc_spell with plain text
 * - class switch via ?class=
 * - points cap driven by ?level= (1..70)
 */

/* -------------------- helpers -------------------- */

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


function class_icon_for(int $classId): string {
  // TBC classes only (no DK)
  static $map = [
    1  => 'class_warrior',
    2  => 'class_paladin',
    3  => 'class_hunter',
    4  => 'class_rogue',
    5  => 'class_priest',
    7  => 'class_shaman',
    8  => 'class_mage',
    9  => 'class_warlock',
    11 => 'class_druid',
  ];
  $base = $map[$classId] ?? 'inv_misc_questionmark';
  return icon_url($base); // -> /armory/shared/icons/<name>.jpg
}


/** tabs (id, name, tab_number) for a class id */
function get_tabs_for_class($classId) {
  $mask = 1 << ((int)$classId - 1);
  return execute_query(
    'armory',
    "SELECT `id`, `name`, `tab_number`, `SpellIconID`
       FROM `dbc_talenttab`
      WHERE (`refmask_chrclasses` & {$mask}) <> 0
      ORDER BY `tab_number` ASC",
    0
  ) ?: [];
}

/** fast learned-spells lookup (cached) */
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
/** prefer character_talent; else derive from character_spell */
function current_rank_for_talent(int $guid, array $talRow, array $rankMap, bool $hasCharSpell): int {
  $tid = (int)$talRow['id'];
  if (isset($rankMap[$tid])) return (int)$rankMap[$tid]; // 1-based
  if ($hasCharSpell) {
    $learned = get_learned_spells_map($guid);
    for ($r = 5; $r >= 1; $r--) {
      $spell = (int)($talRow["rank{$r}"] ?? 0);
      if ($spell > 0 && !empty($learned[$spell])) return $r;
    }
  }
  return 0;
}
function first_rank_spell(array $tal) { for ($i=1;$i<=5;$i++){ $id=(int)$tal["rank{$i}"]; if($id) return $id; } return 0; }
function num_trim($v): string { $s=number_format((float)$v,1,'.',''); $s=rtrim(rtrim($s,'0'),'.'); return ($s==='')?'0':$s; }
function get_spell_chain_targets(int $id, int $n): int {
  $n = max(1, min(3, $n));
  return _cache("chain:$id:$n", function() use ($id,$n){
    $row = execute_query('armory',"SELECT `effect_chaintarget_{$n}` AS x FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1",1);
    return $row ? (int)$row['x'] : 0;
  });
}
function spell_info_for_talent(array $talRow, int $rank = 0) {
  $maxRank = 0; for ($r=5;$r>=1;$r--) if (!empty($talRow["rank{$r}"])) { $maxRank=$r; break; }
  if ($maxRank===0) return ['name'=>'Unknown','desc'=>'','icon'=>'inv_misc_questionmark'];

  $useRank = $rank>0 ? min($rank,$maxRank) : 1;
  $spellId = (int)($talRow["rank{$useRank}"] ?? 0);
  if ($spellId<=0) { for ($r=min($useRank,$maxRank);$r>=1;$r--){ $spellId=(int)($talRow["rank{$r}"]??0); if($spellId>0)break; } }
  if ($spellId<=0) return ['name'=>'Unknown','desc'=>'','icon'=>'inv_misc_questionmark'];

  $sql = "SELECT s.`id`, s.`name`, s.`description`, s.`proc_chance`, s.`proc_charges`,
                 s.`ref_spellduration`, s.`ref_spellradius_1`,
                 s.`effect_basepoints_1`, s.`effect_basepoints_2`, s.`effect_basepoints_3`,
                 s.`effect_amplitude_1`, s.`effect_amplitude_2`, s.`effect_amplitude_3`,
                 s.`effect_chaintarget_1`, s.`effect_chaintarget_2`, s.`effect_chaintarget_3`,
                 s.`effect_trigger_1`, s.`effect_trigger_2`, s.`effect_trigger_3`,
                 i.`name` AS icon
          FROM `dbc_spell` s
          LEFT JOIN `dbc_spellicon` i ON i.`id`=s.`ref_spellicon`
          WHERE s.`id`={$spellId} LIMIT 1";
  $sp = execute_query('armory', $sql, 1);
  if (!$sp || !is_array($sp)) return ['name'=>'Unknown','desc'=>'','icon'=>'inv_misc_questionmark'];

  $desc = build_tooltip_desc($sp);
  $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
  if ($icon==='') $icon='inv_misc_questionmark';
  return ['name'=>(string)($sp['name'] ?? 'Unknown'), 'desc'=>$desc, 'icon'=>$icon];
}
function icon_url($iconBase) { return '/armory/shared/icons/'.$iconBase.'.jpg'; }
function talent_bg_for_tab($tabId) {
  $webBase = '/armory/shared/icon_talents';
  $fsBase  = realpath(__DIR__ . '/../shared/icon_talents');
  if (!$fsBase) return '';
  $file = (int)$tabId . '.jpg';
  $fs   = $fsBase . DIRECTORY_SEPARATOR . $file;
  return is_file($fs) ? ($webBase . '/' . $file) : '';
}
function fmt_secs($sec) {
  $sec = (int)round($sec);
  if ($sec <= 0) return 'until cancelled';
  if ($sec < 60) return $sec . ' sec';
  $m = floor($sec/60); $s=$sec%60;
  return $s===0 ? ($m.' min') : ($m.' min '.$s.' sec');
}
function _cache($key, callable $fn) { static $C=[]; if(isset($C[$key])) return $C[$key]; $C[$key]=$fn(); return $C[$key]; }
function get_spell_row($id) {
  return _cache("spell:$id", function() use ($id){
    return execute_query('armory',"SELECT `effect_basepoints_1`,`effect_basepoints_2`,`effect_basepoints_3`,`ref_spellradius_1` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1",1);
  });
}
function get_spell_o_row($id) {
  return _cache("spellO:$id", function() use ($id){
    return execute_query('armory',"SELECT `ref_spellduration`,`effect_basepoints_1`,`effect_basepoints_2`,`effect_basepoints_3`,`effect_amplitude_1`,`effect_amplitude_2`,`effect_amplitude_3` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1",1);
  });
}
function get_spell_duration_id($id) {
  return _cache("durid:$id", function() use ($id){
    $row = execute_query('armory',"SELECT `ref_spellduration` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1",1);
    return $row ? (int)$row['ref_spellduration'] : 0;
  });
}

function icon_base_from_icon_id(int $iconId): string {
  if ($iconId <= 0) return 'inv_misc_questionmark';
  $r = execute_query('armory', "SELECT `name` FROM `dbc_spellicon` WHERE `id`={$iconId} LIMIT 1", 1);
  if ($r && !empty($r['name'])) {
    return strtolower(preg_replace('/[^a-z0-9_]/i', '', $r['name']));
  }
  return 'inv_misc_questionmark';
}

function duration_secs_from_id($id) {
  if (!$id) return 0;
  $row = execute_query('armory', "SELECT `durationValue` FROM `dbc_spellduration` WHERE `id`=".(int)$id." LIMIT 1", 1);
  if (!$row) return 0;
  $ms = (int)$row['durationValue'];
  return ($ms > 0) ? ($ms / 1000) : 0;
}
function get_radius_yds_by_id($rid) {
  return _cache("radius:$rid", function() use ($rid){
    $row = execute_query('armory', "SELECT `yards_base` FROM `dbc_spellradius` WHERE `id`=".(int)$rid." LIMIT 1", 1);
    return $row ? (float)$row['yards_base'] : 0.0;
  });
}
function get_die_sides_n(int $spellId, int $n): int {
  if ($n<1||$n>3) return 0;
  if (!_has_die_sides_cols()) return 0;
  return _cache("die:$spellId:$n", function() use ($spellId,$n){
    $col = "effect_die_sides_{$n}";
    $row = execute_query('armory', "SELECT `$col` FROM `dbc_spell` WHERE `id`=".(int)$spellId." LIMIT 1", 1);
    return $row ? (int)$row[$col] : 0;
  });
}
function get_spell_proc_charges($id) {
  return _cache("procchg:$id", function() use ($id){
    $row = execute_query('armory', "SELECT `proc_charges` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
    return $row ? (int)$row['proc_charges'] : 0;
  });
}
function _has_die_sides_cols(): bool {
  static $has=null; if($has!==null) return $has;
  $rows = execute_query('armory',"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='dbc_spell' AND COLUMN_NAME IN ('effect_die_sides_1','effect_die_sides_2','effect_die_sides_3')",0);
  $has = !empty($rows); return $has;
}
function get_spell_radius_id($id) {
  $row = execute_query('armory', "SELECT `ref_spellradius_1` FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
  return $row ? (int)$row['ref_spellradius_1'] : 0;
}
function getRadiusYdsForSpellRow(array $sp) {
  $rid = (int)($sp['ref_spellradius_1'] ?? 0);
  if ($rid <= 0) return 0.0;
  return get_radius_yds_by_id($rid);
}
function _stack_col_name(): ?string {
  static $col=null,$checked=false; if($checked) return $col; $checked=true;
  $row = execute_query('armory',"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dbc_spell' AND COLUMN_NAME IN ('stack_amount','StackAmount','max_stack','MaxStack') LIMIT 1",1);
  $col = $row ? $row['COLUMN_NAME'] : null; return $col;
}
function _stack_amount_for_spell(int $id): int {
  $col = _stack_col_name(); if(!$col) return 0;
  $r = execute_query('armory', "SELECT `$col` AS st FROM `dbc_spell` WHERE `id`=".(int)$id." LIMIT 1", 1);
  return $r ? (int)$r['st'] : 0;
}
function _trigger_col_base(){
  static $base=null,$checked=false; if($checked) return $base; $checked=true;
  $row = execute_query('armory',"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dbc_spell' AND COLUMN_NAME IN ('effect_trigger_1','effect_trigger_spell_1') LIMIT 1",1);
  if ($row && isset($row['COLUMN_NAME'])) {
    $base = (strpos($row['COLUMN_NAME'],'effect_trigger_spell_')===0) ? 'effect_trigger_spell_' : 'effect_trigger_';
  } else {
    $base = 'effect_trigger_';
  }
  return $base;
}

/* -------------------- VIEW-ONLY / SELECTION -------------------- */
// -------------------- CALCULATOR SETTINGS (no prefill) --------------------
$CALC_MODE    = true;         // interactive calc (ignore learned ranks)
$MAX_POINTS   = 61;           // TBC max
$talentCap    = $MAX_POINTS;  // start with 61 left
$pointsSpent  = 0;            // initial
// Class selection (calc mode doesn't depend on level)
$CLASS_NAMES = [
  1=>'Warrior', 2=>'Paladin', 3=>'Hunter', 4=>'Rogue', 5=>'Priest',
  7=>'Shaman',  8=>'Mage',    9=>'Warlock', 11=>'Druid'
];

$charClassId = isset($_GET['class'], $CLASS_NAMES[(int)$_GET['class']])
  ? (int)$_GET['class']
  : (int)($stat['class'] ?? 1);

$charClass = $CLASS_NAMES[$charClassId] ?? 'Class';
// ---- class slugs (used by CSS) + container theme ----
$CLASS_SLUGS = [
  1=>'warrior', 2=>'paladin', 3=>'hunter', 4=>'rogue', 5=>'priest',
  7=>'shaman',  8=>'mage',    9=>'warlock', 11=>'druid'
];
$classSlug = $CLASS_SLUGS[$charClassId] ?? 'warrior';




$tabs = get_tabs_for_class($charClassId); // use selected class

/* -------------------- tooltip builder (unchanged core) -------------------- */
function build_tooltip_desc(array $sp): string {
  // (… your existing long tooltip builder exactly as before …)
  // Keeping content identical to your provided version for brevity.
  // BEGIN pasted from your message
  $desc = (string)($sp['description'] ?? '');
  $trimNum = static function($v): string { $s=number_format((float)$v,1,'.',''); $s=rtrim(rtrim($s,'0'),'.'); return ($s==='')?'0':$s; };
  $rangeText = static function(int $min, int $max): string { return ($max > $min) ? ($min . ' to ' . $max) : (string)$min; };
  $formatS = static function (int $bp, int $dieSides, int $div = 1): array {
    if ($div === 1000 && $bp < 0) {
      $min = abs($bp) / 1000.0; $max = $min + ($dieSides > 0 ? $dieSides / 1000.0 : 0.0);
      $txt = ($max > $min) ? rtrim(rtrim(number_format($min,1,'.',''), '0'),'.').' to '.rtrim(rtrim(number_format($max,1,'.',''), '0'),'.')
                           : rtrim(rtrim(number_format($min,1,'.',''), '0'),'.');
      return [$min,$max,$txt];
    }
    $min = $bp + 1; if ($dieSides <= 1) return [$min,$min,(string)abs($min)];
    $max = $bp + $dieSides; if ($max < $min) { [$min,$max] = [$max,$min]; }
    return [$min,$max,$min.' to '.$max];
  };
  $desc = preg_replace_callback('/\$\s*\/1000;(\d+)S1\b/', function($m){ $sid=(int)$m[1]; $row=get_spell_row($sid); if(!$row) return '0 sec'; $bp=(int)($row['effect_basepoints_1']??0); $val=abs($bp+1)/1000.0; $s=number_format($val,1,'.',''); $s=rtrim(rtrim($s,'0'),'.'); return $s.' sec'; }, $desc);
  $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function($m) use($formatS){ $sid=(int)$m[1]; $idx=(int)$m[2]; $row=_cache("spell:$sid",function()use($sid){return get_spell_row($sid);}); if(!$row)return'0'; $bp=(int)($row["effect_basepoints_{$idx}"]??0); $die=_cache("die:$sid:$idx",function()use($sid,$idx){return get_die_sides_n($sid,$idx);}); [,, $text]=$formatS($bp,$die); return $text; }, $desc);
  $desc = preg_replace_callback('/\$(\d+)d\b/', function($m){ $sid=(int)$m[1]; $durId=_cache("durid:$sid",function()use($sid){return get_spell_duration_id($sid);}); $secs=_cache("dursec:$durId",function()use($durId){return duration_secs_from_id($durId);}); return fmt_secs($secs); }, $desc);
  $desc = preg_replace_callback('/\$(\d+)a1\b/', function($m){ $sid=(int)$m[1]; $row=_cache("spell:$sid",function()use($sid){return get_spell_row($sid);}); if(!$row)return'0'; $val=_cache("radiusYds:$sid",function()use($row){return getRadiusYdsForSpellRow($row);}); $s=number_format((float)$val,1,'.',''); $s=rtrim(rtrim($s,'0'),'.'); return ($s==='')?'0':$s; }, $desc);
  $desc = preg_replace_callback('/\$(\d+)o([1-3])\b/', function($m){ $sid=(int)$m[1]; $idx=(int)$m[2]; $row=_cache("spellO:$sid",function()use($sid){return get_spell_o_row($sid);}); if(!$row)return'0'; $bp=abs((int)($row["effect_basepoints_{$idx}"]??0)+1); $amp=(int)($row["effect_amplitude_{$idx}"]??0); $dsec=_cache("dursecBySpell:$sid",function()use($row){return duration_secs_from_id((int)($row['ref_spellduration']??0));}); $ticks=($amp>0)?(int)floor(($dsec*1000)/$amp):0; return (string)($ticks>0?$bp*$ticks:$bp); }, $desc);
  $desc = preg_replace_callback('/\$(\d+)t([1-3])\b/', function($m){ $sid=(int)$m[1]; $idx=(int)$m[2]; $row=_cache("spellO:$sid",function()use($sid){return get_spell_o_row($sid);}); if(!$row)return'0'; $amp=(int)($row["effect_amplitude_{$idx}"]??0); $sec=$amp>0?($amp/1000.0):0.0; $s=number_format($sec,1,'.',''); return rtrim(rtrim($s,'0'),'.')?:'0'; }, $desc);
  $desc = preg_replace_callback('/\$(\d+)u\b/', function($m){ $sid=(int)$m[1]; $n=_stack_amount_for_spell($sid); if($n<=0){ $row=_cache("spell:$sid",function()use($sid){return get_spell_row($sid);}); if($row){ $bp=(int)($row['effect_basepoints_1']??0); $n=abs($bp+1); } } if($n<1)$n=1; return (string)$n; }, $desc);
  $desc = preg_replace_callback('/\$(\d+)n\b/', function($m){ $sid=(int)$m[1]; $n=_cache("procchg:$sid",function()use($sid){return get_spell_proc_charges($sid);}); if($n<=0)$n=_stack_amount_for_spell($sid); if($n<=0){ $row=_cache("spell:$sid",function()use($sid){return get_spell_row($sid);}); if($row){ $bp=(int)($row['effect_basepoints_1']??0); $n=abs($bp+1);} } $n=(int)$n; if($n<1)$n=1; return (string)$n; }, $desc);
  $desc = preg_replace_callback('/\$(\d+)x([1-3])\b/', function($m){ $sid=(int)$m[1]; $i=(int)$m[2]; $row=execute_query('armory',"SELECT `effect_chaintarget_{$i}` AS x FROM `dbc_spell` WHERE `id`={$sid} LIMIT 1",1); $val=$row?(int)$row['x']:0; if($val<=0)$val=1; return (string)$val; }, $desc);
  $currId = isset($sp['id'])?(int)$sp['id']:0;
  $die1=_cache("die:$currId:1",function()use($currId){return $currId?get_die_sides_n($currId,1):0;});
  $die2=_cache("die:$currId:2",function()use($currId){return $currId?get_die_sides_n($currId,2):0;});
  $die3=_cache("die:$currId:3",function()use($currId){return $currId?get_die_sides_n($currId,3):0;});
  $formatSLocal=$formatS;
  list($s1min,$s1max,$s1txt)=$formatSLocal((int)($sp['effect_basepoints_1']??0),$die1);
  list($s2min,$s2max,$s2txt)=$formatSLocal((int)($sp['effect_basepoints_2']??0),$die2);
  list($s3min,$s3max,$s3txt)=$formatSLocal((int)($sp['effect_basepoints_3']??0),$die3);
  $desc=preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',function($m)use($s1min,$s1max,$s2min,$s2max,$s3min,$s3max,$formatSLocal){$div=(float)$m[1];$spellId=$m[2]?(int)$m[2]:0;$type=strtolower($m[3]);$idx=(int)$m[4];$fmt=static function($v){$s=number_format((float)$v,1,'.','');return rtrim(rtrim($s,'0'),'.')?:'0';};if($type==='s'){if($spellId===0){$mapMin=[1=>$s1min,2=>$s2min,3=>$s3min];$mapMax=[1=>$s1max,2=>$s2max,3=>$s3max];$min=abs((float)($mapMin[$idx]??0.0));$max=abs((float)($mapMax[$idx]??$min));}else{$row=_cache("spell:$spellId",function()use($spellId){return get_spell_row($spellId);});if(!$row)return'0';$bp=(int)($row["effect_basepoints_{$idx}"]??0);$die=_cache("die:$spellId:$idx",function()use($spellId,$idx){return get_die_sides_n($spellId,$idx);});list($min,$max)=$formatSLocal($bp,$die);}if($div>0){$min/=$div;$max/=$div;}if($max>$min){$lo=(int)floor($min);$hi=(int)ceil($max);return $lo.' to '.$hi;}return $fmt($min);}if($spellId===0){$map=[1=>$s1min,2=>$s2min,3=>$s3min];$val=abs((float)($map[$idx]??0.0));}else{$row=_cache("spellO:$spellId",function()use($spellId){return get_spell_o_row($spellId);});if(!$row)return'0';$bp=abs((int)($row["effect_basepoints_{$idx}"]??0)+1);$amp=(int)($row["effect_amplitude_{$idx}"]??0);$dur=duration_secs_from_id((int)($row['ref_spellduration']??0));$ticks=($amp>0)?(int)floor(($dur*1000)/$amp):0;$val=$ticks>0?$bp*$ticks:$bp;}if($div>0)$val/=$div;return $fmt($val);},$desc);
  $getDurSecBySpellId=function($sid){ if($sid<=0)return 0; $durId=_cache("durid:$sid",function()use($sid){return get_spell_duration_id($sid);}); return _cache("dursec:$durId",function()use($durId){return duration_secs_from_id($durId);}); };
  $currId = isset($sp['id']) ? (int)$sp['id'] : 0; $durSecs = $getDurSecBySpellId($currId);
  $desc=preg_replace_callback('/\$\{\s*\$d\s*([+-])\s*(\d+)\s*\}\s*sec\b/i',function($m)use($durSecs){$delta=(int)$m[2];$v=$durSecs+($m[1]==='-'?- $delta:$delta); if($v<0)$v=0; return $v.' sec';},$desc);
  $desc=preg_replace_callback('/\$\{\s*\$d\s*([+-])\s*(\d+)\s*\}/i',function($m)use($durSecs){$delta=(int)$m[2];$v=$durSecs+($m[1]==='-'?- $delta:$delta); if($v<0)$v=0; return (string)$v;},$desc);
  if(strpos($desc,'$d')!==false){ $seen=[];$queue=[$currId];$depth=0; while(!empty($queue)&&$depth<2){$next=[];foreach($queue as $sid){if($sid<=0||isset($seen[$sid]))continue;$seen[$sid]=true;$ds=$getDurSecBySpellId($sid); if($ds>$durSecs)$durSecs=$ds; $base=_trigger_col_base();$col1=$base.'1';$col2=$base.'2';$col3=$base.'3';$row=execute_query('armory',"SELECT `$col1` AS t1, `$col2` AS t2, `$col3` AS t3 FROM `dbc_spell` WHERE `id`=".(int)$sid." LIMIT 1",1); if($row){ for($i=1;$i<=3;$i++){ $tid=isset($row["t{$i}"])?(int)$row["t{$i}"]:0; if($tid>0&&!isset($seen[$tid]))$next[]=$tid; } } } $queue=$next;$depth++; }
    if($durSecs<=2){$base=_trigger_col_base();$col1=$base.'1';$col2=$base.'2';$col3=$base.'3';$parents=execute_query('armory',"SELECT `id` FROM `dbc_spell` WHERE `$col1`=".(int)$currId." OR `$col2`=".(int)$currId." OR `$col3`=".(int)$currId." LIMIT 20",0); if(is_array($parents)){foreach($parents as $pr){$pid=(int)$pr['id'];$pds=$getDurSecBySpellId($pid); if($pds>$durSecs)$durSecs=$pds;}}}
  }
  $durMs=$durSecs*1000; $d=fmt_secs($durSecs);
  $o1=(function()use($sp,$durMs){$bp=abs((int)($sp['effect_basepoints_1']??0)+1);$amp=(int)($sp['effect_amplitude_1']??0);$ticks=($amp>0)?(int)floor($durMs/$amp):0;return (string)($ticks>0?$bp*$ticks:$bp);})();
  $o2=(function()use($sp,$durMs){$bp=abs((int)($sp['effect_basepoints_2']??0)+1);$amp=(int)($sp['effect_amplitude_2']??0);$ticks=($amp>0)?(int)floor($durMs/$amp):0;return (string)($ticks>0?$bp*$ticks:$bp);})();
  $o3=(function()use($sp,$durMs){$bp=abs((int)($sp['effect_basepoints_3']??0)+1);$amp=(int)($sp['effect_amplitude_3']??0);$ticks=($amp>0)?(int)floor($durMs/$amp):0;return (string)($ticks>0?$bp*$ticks:$bp);})();
  $h=(int)($sp['proc_chance']??0); if($h<=0)$h=$s1min;
  $a1=num_trim(getRadiusYdsForSpellRow($sp));
  $t1=num_trim(((int)($sp['effect_amplitude_1']??0))/1000.0);
  $t2=num_trim(((int)($sp['effect_amplitude_2']??0))/1000.0);
  $t3=num_trim(((int)($sp['effect_amplitude_3']??0))/1000.0);
  $desc=preg_replace_callback('/\{\$\s*(AP|RAP|SP)\s*\*\s*\$m([1-3])\s*\/\s*100\s*\}/i',function($m)use($s1min,$s2min,$s3min){$idx=(int)$m[2];$map=[1=>$s1min,2=>$s2min,3=>$s3min];$pct=(int)abs($map[$idx]??0);$stat=strtoupper($m[1]);$labels=['AP'=>'Attack Power','RAP'=>'Ranged Attack Power','SP'=>'Spell Power'];$label=$labels[$stat]??$stat;return '(' . $label . ' * ' . $pct . ' / 100)';},$desc);
  $desc=preg_replace_callback('/\$(m[1-3])\b/',function($m)use($s1min,$s2min,$s3min){switch($m[1]){case'm1':return (string)$s1min;case'm2':return (string)$s2min;case'm3':return (string)$s3min;}return $m[0];},$desc);
  $procN=(int)($sp['proc_charges']??0); if($procN<=0 && isset($sp['id'])) $procN=(int)get_spell_proc_charges((int)$sp['id']); if($procN>0) $desc=preg_replace('/\$n\b/i',(string)$procN,$desc);
  $desc=preg_replace_callback('/\$x([1-3])\b/',function($m)use($sp){$i=(int)$m[1];$val=(int)($sp["effect_chaintarget_{$i}"]??0); if($val<=0)$val=1; return (string)$val;},$desc);
  $u=1; if(!empty($sp['id'])){ $u=_stack_amount_for_spell((int)$sp['id']); if($u<=0){$bp=(int)($sp['effect_basepoints_1']??0); $u=abs($bp+1);} if($u<1)$u=1; }
  while(preg_match('/\$l([^:;]+):([^;]+);/',$desc,$m,PREG_OFFSET_CAPTURE)){ $full=$m[0][0];$offset=$m[0][1];$sing=$m[1][0];$plu=$m[2][0];$before=substr($desc,0,$offset);$val=2; if(preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/',$before,$nm)) $val=(float)$nm[1]; $word=(abs($val-1.0)<0.000001)?$sing:$plu; $desc=substr($desc,0,$offset).$word.substr($desc,$offset+strlen($full)); }
  $__mulMap=['s1'=>(float)$s1min,'s2'=>(float)$s2min,'s3'=>(float)$s3min,'o1'=>(float)$o1,'o2'=>(float)$o2,'o3'=>(float)$o3,'m1'=>(float)$s1min,'m2'=>(float)$s2min,'m3'=>(float)$s3min];
  $desc=preg_replace_callback('/\$\*\s*([0-9]+(?:\.[0-9]+)?)\s*;\s*(s[1-3]|o[1-3]|m[1-3])/i',function($m)use($__mulMap){$factor=(float)$m[1];$key=strtolower($m[2]);$base=isset($__mulMap[$key])?(float)$__mulMap[$key]:0.0;$val=$factor*$base;$s=number_format($val,1,'.','');$s=rtrim(rtrim($s,'0'),'.');return ($s==='')?'0':$s;},$desc);
  $desc=preg_replace_callback('/\$\{([0-9]+)\s*-\s*([0-9]+)\/([0-9]+)\}/',function($m) use ($cur,$max){$min=(int)$m[1];$maxVal=(int)$m[2];$div=(int)$m[3];if($div<=0)$div=1;$steps=max(1,$max-1);$progress=($max>1)?($cur-1)/$steps:0;$val=$min+($maxVal-$min)*$progress;$val=$val/$div;$s=number_format($val,1,'.','');$s=rtrim(rtrim($s,'0'),'.');return ($s==='')?'0':$s;},$desc);
  $desc=str_replace('$D',$d,$desc);
  $desc=strtr($desc,['$s1'=>$s1txt,'$s2'=>$s2txt,'$s3'=>$s3txt,'$o1'=>$o1,'$o2'=>$o2,'$o3'=>$o3,'$t1'=>$t1,'$t2'=>$t2,'$t3'=>$t3,'$a1'=>$a1,'$d'=>$d,'$h'=>(string)$h,'$u'=>(string)$u]);
  $desc=preg_replace('/(\d+)1%/','$1%',$desc);
  $desc=preg_replace('/\$\(/','(',$desc);
  $desc=preg_replace('/\$\w*sec:secs;/',' sec',$desc);
  $desc=preg_replace('/\s+%/','%',$desc);
  return $desc;
  // END pasted section
}

/* -------------------- build data -------------------- */

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
<?php
$talentCalcStandalone = defined('REQUESTED_ACTION') && REQUESTED_ACTION === 'talentscalc';
$talentCalcHeading = $lang['talentscalc'] ?? 'Talents Calculator';
?>
<?php if ($talentCalcStandalone): ?>
<div class="parch-profile-banner" id="banner" style="position: absolute;margin-left: 450px!important;margin-top: -110px!important;">
  <h1 style="padding-top: 12px!important;"><?php echo $talentCalcHeading; ?></h1>
</div>
<?php endif; ?>

<?php if (empty($tabs)): ?>
  <em>No talent tabs found for this class.</em>

<?php else: ?>
<div id="tc-root" class="tc-container is-<?= htmlspecialchars($classSlug) ?>">
<!-- Header block -->
<div class="tc-header is-<?= htmlspecialchars($classSlug) ?>">

<div class="tc-head-left">
  <div class="tc-leftpanel">
    <div class="tc-subtitle">Talent Calculator</div>
    <div class="tc-classcolor">
      <?= htmlspecialchars($charClass) ?>:
      <span class="tc-splits" id="tcSplits">0 / 0 / 0</span>
    </div>

    <div class="tc-summary-inline">
      <span class="tc-req">Required level: <strong id="tcReqLvl">10</strong></span>
    </div>
    <div class="tc-summary-inline">
      <span class="tc-pointsleft">Points left: <strong id="tcLeft"><?= (int)$talentCap ?></strong></span>
    </div>
	

    <div class="tc-share">
      <div class="tc-token">
        <input id="tcTokenBox" type="text" readonly>
        <button id="tcCopyToken" class="tc-share-btn">Share build</button>
      </div>

      <!-- whisper hint (updated by JS refreshShareUI) -->
      <div id="tcWhisperText" class="tc-whisper"></div>
    </div>
	
	


  </div>
</div>


  
  <div class="tc-head-right">
    <div class="tc-classgrid">
      <?php foreach ($CLASS_NAMES as $cid => $cname): ?>
        <?php
          $href = "index.php?searchType=profile&charPage=talentcalc"
                . "&character=" . rawurlencode($stat['name'])
                . "&realm="     . rawurlencode(REALM_NAME)
                . "&class="     . $cid;

          $ico   = class_icon_for($cid);
          $slug  = $CLASS_SLUGS[$cid] ?? 'warrior';
          $active = ($cid === $charClassId) ? ' active' : '';
        ?>
		     <a class="tc-class class-<?= htmlspecialchars($slug) ?><?= $active ?>"
				href="<?= $href ?>"
				data-name="<?= htmlspecialchars($cname, ENT_QUOTES) ?>">
          <img src="<?= htmlspecialchars($ico) ?>" alt="<?= htmlspecialchars($cname) ?>">
        </a>
      <?php endforeach; ?>
	  <!-- reset-all icon (same size cell) -->
<button
  type="button"
  id="tcResetAllBtn"
  class="tc-class class-reset"
  data-name="Reset all"
  aria-label="Reset all">
  <span class="tc-reset-ico" aria-hidden="true"></span>
</button>

    </div>
  </div>
</div>
 <!-- Trees -->
  <div class="talent-trees">
    <?php foreach ($tabs as $t): ?>
      <?php
        $tabId   = (int)$t['id'];
        $tabName = (string)$t['name'];
        $points  = 0;                 // view-only
        $bgUrl   = talent_bg_for_tab($tabId);

$talents = execute_query(
  'armory',
  "SELECT `id`, `row`, `col`,
          `rank1`, `rank2`, `rank3`, `rank4`, `rank5`,
          `prereq_talent_1` AS req_tid,
          `prereq_rank_1`   AS req_rank
     FROM `dbc_talent`
    WHERE `ref_talenttab` = {$tabId}
    ORDER BY `row`, `col`",
  0
) ?: [];


        $byPos = []; $maxRow = 0;
        foreach ($talents as $tal) {
          $r = (int)$tal['row']; $c = (int)$tal['col'];
          $byPos["$r:$c"] = $tal;
          if ($r > $maxRow) $maxRow = $r;
        }

// Pick tab icon directly from dbc_talenttab.SpellIconID
$tabIconName = icon_base_from_icon_id((int)($t['SpellIconID'] ?? 0));

// Fallback: try the first-rank spell in this tab
if ($tabIconName === 'inv_misc_questionmark') {
  foreach ($talents as $tal) {
    $sid = first_rank_spell($tal);
    if ($sid) {
      $rr = execute_query(
        'armory',
        "SELECT i.`name`
           FROM `dbc_spell` s
           LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
          WHERE s.`id` = {$sid}
          LIMIT 1",
        1
      );
      if ($rr && !empty($rr['name'])) {
        $tabIconName = strtolower(preg_replace('/[^a-z0-9_]/i', '', $rr['name']));
        break;
      }
    }
  }
}

$tabIconUrlQ  = htmlspecialchars(icon_url($tabIconName), ENT_QUOTES);
$capForHeader = (int)$talentCap; // from dropdown level

      ?>

      <div class="talent-tree" style="background-image:url('<?= htmlspecialchars($bgUrl, ENT_QUOTES) ?>');">
        <div class="talent-head">
          <span class="talent-head-ico" style="background-image:url('<?= $tabIconUrlQ ?>')"></span>
          <span class="talent-head-title"><?= htmlspecialchars($tabName) ?></span>
          <span class="talent-head-pts">
            <b class="num"><?= (int)$points ?></b>
            <span class="slash"> / </span>
            <span class="cap"><?= $capForHeader ?></span>
          </span>
		  <!-- per tree reset
		  <button type="button" class="tree-reset tc-btn tc-btn--sm" title="Reset this tree" aria-label="Reset this tree">↺</button>
		  -->

        </div>

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

    // max ranks available for this talent
    
	$max = 0; for ($x=5;$x>=1;$x--) { if (!empty($found["rank$x"])) { $max=$x; break; } }
$cur = $CALC_MODE ? 0 : current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);

// title/icon from rank 1
$sp1   = spell_info_for_talent($found, 1);
$title = htmlspecialchars($sp1['name'], ENT_QUOTES);
$iconQ = htmlspecialchars(icon_url($sp1['icon']), ENT_QUOTES);

// build per-rank tooltip descriptions: data-tt-desc1..5
$descAttrs = '';
for ($ri = 1; $ri <= $max; $ri++) {
  $spi  = spell_info_for_talent($found, $ri);
  $desc = htmlspecialchars($spi['desc'], ENT_QUOTES);
  $descAttrs .= ' data-tt-desc' . $ri . '="' . $desc . '"';
}
// optional generic fallback (rank 1)
$descFirst = htmlspecialchars($sp1['desc'], ENT_QUOTES);

$tid     = (int)$found['id'];
$reqTid  = (int)($found['req_tid']  ?? 0);
$reqRank = (int)($found['req_rank'] ?? 0);

$cellClass = 'talent-cell';
if     ($cur >= $max && $max > 0) $cellClass .= ' maxed';
elseif ($cur > 0)                 $cellClass .= ' learned';
else                              $cellClass .= ' empty';

echo '<div class="'.$cellClass.'" style="--icon:url(\''.$iconQ.'\')"'
   . ' data-tt-title="'.$title.'"'
   . ' data-tt-desc="'.$descFirst.'"'
   . $descAttrs
   . ' data-talent-id="'.$tid.'"'
   . ' data-prereq-id="'.$reqTid.'"'
   . ' data-prereq-rank="'.$reqRank.'"'
   . ' data-row="'.$r.'"'
   . ' data-col="'.$c.'"'            // <-- add this
   . ' data-current="'.$cur.'"'
   . ' data-max="'.(int)$max.'">'
   . '  <span class="talent-rank">'.(int)$cur.'/'.(int)$max.'</span>'
   . '</div>';


	
  }
}
	  ?>
		  
		  
        </div>
      </div>
    <?php endforeach; ?>
  </div><!-- /.talent-trees -->

</div><!-- /.tc-container -->
<?php endif; ?>

<script>
(function(){
  const root   = document.getElementById('tc-root');
  const header = root?.querySelector('.tc-header');
  const trees  = root?.querySelector('.talent-trees');
  if (!root || !header || !trees) return;

  function sync(){
    const w = Math.round(trees.getBoundingClientRect().width);
    header.style.setProperty('--tc-measured', w + 'px');
    root.style.width = w + 'px';                    // keeps stack perfectly centered
  }

  // Run at safe times
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', sync, { once:true });
  else
    sync();

  // Font/layout changes & resizes
  document.fonts?.ready.then(sync);
  addEventListener('resize', () => requestAnimationFrame(sync));

  // If anything inside the trees changes width (glows, scrollbars, etc.)
  new ResizeObserver(sync).observe(trees);
})();
</script>

<script>
(function () {
  // WoW class colors
  const CLASS_COLORS = {
    Warrior:  '#C79C6E',
    Paladin:  '#F58CBA',
    Hunter:   '#ABD473',
    Rogue:    '#FFF569',
    Priest:   '#FFFFFF',
    Shaman:   '#0070DE',
    Mage:     '#69CCF0',
    Warlock:  '#9482C9',
    Druid:    '#FF7D0A',
  };

  // Create one tooltip for all class icons
  const tip = document.createElement('div');
  tip.className = 'tc-class-tip';
  document.body.appendChild(tip);

  let anchor = null;

  function placeAbove(el){
    const pad = 8;
    const rEl = el.getBoundingClientRect();
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    const rTT = tip.getBoundingClientRect();

    let left = Math.round(rEl.left + (rEl.width - rTT.width)/2);
    let top  = Math.round(rEl.top - rTT.height - pad);

    // keep on screen
    left = Math.max(6, Math.min(left, innerWidth - rTT.width - 6));
    top  = Math.max(6, top);

    tip.style.left = left + 'px';
    tip.style.top  = top  + 'px';
    tip.style.visibility = 'visible';
    tip.classList.add('show');
  }

  function showFor(el){
    anchor = el;
    // preferred: data-name, fallback: title
    const name = el.getAttribute('data-name') || el.getAttribute('title') || '';
    // optional: remove native tooltip so it doesn't clash
    if (el.getAttribute('title')) { el.setAttribute('data-title', el.getAttribute('title')); el.removeAttribute('title'); }
    tip.textContent = name;

    // color ring -> class color text
    const color = CLASS_COLORS[name] || '#ffd48a';
    tip.style.color = color;
    tip.style.boxShadow = `0 10px 24px rgba(0,0,0,.45), 0 0 10px ${color}33`;
    placeAbove(el);
  }

  function hide(){
    tip.classList.remove('show');
    tip.style.display = 'none';
    if (anchor && anchor.getAttribute('data-title')) {
      anchor.setAttribute('title', anchor.getAttribute('data-title'));
      anchor.removeAttribute('data-title');
    }
    anchor = null;
  }

  function nudge(){ if (anchor && tip.classList.contains('show')) placeAbove(anchor); }

  // Attach to your existing class icons.
  // They already have the class `.tc-class` in your UI.
  document.addEventListener('mouseover', (e)=>{
    const el = e.target.closest('.tc-class');
    if (!el) return;
    showFor(el);
  });
  document.addEventListener('mouseout', (e)=>{
    const el = e.target.closest('.tc-class');
    if (!el) return;
    if (e.relatedTarget && el.contains(e.relatedTarget)) return;
    hide();
  });

  addEventListener('scroll', nudge, { passive:true });
  addEventListener('resize', nudge);
})();
</script>

<script>window.tcClassId = <?= (int)$charClassId ?>;</script>
<script>
(function () {
  var q = new URLSearchParams(location.search);
  var build = q.get('build');
  if (build && /^\d+-[0-5-]+$/.test(build)) {
    // move it to the hash then fall into the redirect logic
    history.replaceState(null, '', location.pathname + location.search.replace(/([?&])build=[^&]*/,'$1').replace(/[?&]$/,'') + '#' + build);
  }
})();
</script>
