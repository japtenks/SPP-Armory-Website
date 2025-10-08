<?php
/* ---------- Helpers for Armor Sets ---------- */

/* Database Helpers */
function qualify_tables($sql, $schema, array $tables) {
  $pat = '/(?<=\bFROM|\bJOIN)\s+`?(' . implode('|', array_map('preg_quote', $tables)) . ')`?\b/i';
  return preg_replace($pat, ' `'.$schema.'`.`$1`', $sql);
}
function _dbsimple_run($conn, $sql, $mode = 0) {
  switch ($mode) {
    case 1: return $conn->selectRow($sql);
    case 2: return $conn->selectCell($sql);
    default:return $conn->select($sql);
  }
}
function armory_query($sql, $mode = 0) {
  global $DB, $ARMORY_SCHEMA;
  $sql = qualify_tables($sql, $ARMORY_SCHEMA, [
    'dbc_spell','dbc_spellicon','dbc_spellduration','dbc_spellradius',
    'dbc_itemset','dbc_talent','dbc_talenttab','dbc_itemdisplayinfo',
    'dbc_itemsubclass','dbc_itemrandomproperties','dbc_itemrandomsuffix',
    'dbc_randproppoints'
  ]);
  return _dbsimple_run($DB, $sql, $mode);
}
function world_query($sql, $mode = 0) {
  global $WSDB, $WORLD_SCHEMA;
  $sql = qualify_tables($sql, $WORLD_SCHEMA, ['item_template']);
  return _dbsimple_run($WSDB, $sql, $mode);
}

/* General Helpers */
function slot_order($inv) {
  switch ((int)$inv) {
    case 1: return 1; case 3: return 2; case 5: return 3;
    case 6: return 4; case 7: return 5; case 8: return 6;
    case 9: return 7; case 10: return 8; default: return 99;
  }
}
function icon_url($iconBase) { return '/armory/images/icons/64x64/'.$iconBase.'.png'; }

/* Stat + Item Helpers */
function stat_name(int $id): string {
  static $map = [3=>'Agility',4=>'Strength',5=>'Intellect',6=>'Spirit',7=>'Stamina'];
  return $map[$id] ?? '';
}
function inventory_type_name(int $id): string {
  $map = [1=>"Head",3=>"Shoulder",5=>"Chest",6=>"Waist",7=>"Legs",
          8=>"Feet",9=>"Wrist",10=>"Hands",16=>"Back",20=>"Chest (Robe)"];
  return $map[$id] ?? "Slot ".$id;
}
function item_class_name(int $class, int $sub): string {
  if ($class == 4) {
    $armor = [1=>"Cloth",2=>"Leather",3=>"Mail",4=>"Plate"];
    return $armor[$sub] ?? "Armor";
  }
  return "Item";
}
function icon_from_displayid(int $displayId): string {
  if ($displayId <= 0) return 'inv_misc_questionmark';
  $row = armory_query("SELECT name FROM dbc_itemdisplayinfo WHERE id={$displayId} LIMIT 1", 1);
  return ($row && !empty($row['name'])) ? strtolower(pathinfo($row['name'], PATHINFO_FILENAME)) : 'inv_misc_key_02';
}
function icon_base_from_icon_id(int $iconId): string {
  if ($iconId <= 0) return 'inv_misc_key_02';
  $r = armory_query("SELECT `name` FROM `dbc_spellicon` WHERE `id`={$iconId} LIMIT 1", 1);
  return ($r && !empty($r['name'])) ? strtolower(preg_replace('/[^a-z0-9_]/i', '', $r['name'])) : 'inv_misc_key_01';
}

/* Tooltip Helpers */
function fmt_value($v) { return number_format($v, 0, '', ''); }
function spell_duration(int $durId): string {
  if (!$durId) return '';
  $r = armory_query("SELECT * FROM dbc_spellduration WHERE id={$durId} LIMIT 1", 1);
  if (!$r) return '';
  $min = (int)$r['duration1']; $max = (int)$r['duration2'];
  return ($min===$max)?($min/1000).' sec':($min/1000).'–'.($max/1000).' sec';
}
function spell_radius(int $radId): string {
  if (!$radId) return '';
  $r = armory_query("SELECT * FROM dbc_spellradius WHERE id={$radId} LIMIT 1", 1);
  return $r?(float)$r['radius1'].' yd':'';
}
function replace_spell_tokens(string $desc, array $sp): string {
  for ($i=1;$i<=3;$i++){ $bp=(int)($sp["effect_basepoints_{$i}"]??0)+1; $desc=str_replace('$s'.$i,fmt_value($bp),$desc); }
  if (strpos($desc,'$d')!==false) $desc=str_replace('$d',spell_duration((int)($sp['ref_spellduration']??0)),$desc);
  for ($i=1;$i<=3;$i++){ if(strpos($desc,'$t'.$i)!==false) $desc=str_replace('$t'.$i,spell_radius((int)($sp["effect_radius_index_{$i}"]??0)),$desc); }
  return $desc;
}

/* Itemset Data */
function get_itemset_data(int $setId): array {
  $row = armory_query("SELECT * FROM dbc_itemset WHERE id={$setId} LIMIT 1", 1);
  if (!$row) return ['id'=>$setId,'name'=>'Unknown Set','items'=>[],'bonuses'=>[]];
  $items=[]; for ($i=1;$i<=10;$i++){ $itemId=(int)($row["item_$i"]??0); if(!$itemId) continue;
    $sql="SELECT it.entry,it.name,it.InventoryType,it.displayid FROM item_template it WHERE it.entry={$itemId} LIMIT 1";
    $item=world_query($sql,1); if($item){ $items[]=['entry'=>(int)$item['entry'],'slot'=>(int)$item['InventoryType'],'name'=>(string)$item['name'],'icon'=>icon_from_displayid((int)$item['displayid'])]; }}
  $bonuses=[]; for($b=1;$b<=8;$b++){ $bonusId=(int)($row["bonus_$b"]??0); $pieces=(int)($row["pieces_$b"]??0); if(!$bonusId||!$pieces) continue;
    $sp=armory_query("SELECT id,name,description,ref_spellicon FROM dbc_spell WHERE id={$bonusId} LIMIT 1",1);
    if($sp){ $desc=replace_spell_tokens((string)$sp['description'],$sp);
      $bonuses[]=['pieces'=>$pieces,'name'=>(string)($sp['name']??''),'desc'=>$desc?:'No description available.','icon'=>icon_base_from_icon_id((int)($sp['ref_spellicon']??0))]; }}
  return ['id'=>$setId,'name'=>(string)($row['name']??'Unknown Set'),'items'=>$items,'bonuses'=>$bonuses];
}
function render_set_bonus_tip_html(array $setData): string {
  $h='<div class="tt-bonuses"><h5>'.htmlspecialchars($setData['name']??'Unknown Set').'</h5>';
  if(!empty($setData['bonuses'])){ usort($setData['bonuses'],fn($a,$b)=>($a['pieces']<=>$b['pieces']));
    foreach($setData['bonuses'] as $b){ $pieces=(int)$b['pieces']; $desc=htmlspecialchars($b['desc']??'');
      $h.='<div class="tt-bonus-row" style="margin:6px 0;"><b>('.$pieces.')</b> '.$desc.'</div>'; }}
  else $h.='<div class="tt-subtle">No set bonuses found.</div>';
  return $h.'</div>';
}
?>