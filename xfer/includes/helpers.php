<!--Helper section for armorset pages-->
<?php

$selectedClass = isset($_GET['class']) ? trim($_GET['class']) : '';
$iconBase   = './armory/shared/icons/';
$iconPref   = 'class_';
$iconExt    = '.jpg';

$classes = [
  ['name'=>'Warrior','slug'=>'warrior','css'=>'is-warrior'],
  ['name'=>'Paladin','slug'=>'paladin','css'=>'is-paladin'],
  ['name'=>'Hunter','slug'=>'hunter','css'=>'is-hunter'],
  ['name'=>'Rogue','slug'=>'rogue','css'=>'is-rogue'],
  ['name'=>'Priest','slug'=>'priest','css'=>'is-priest'],
  ['name'=>'Shaman','slug'=>'shaman','css'=>'is-shaman'],
  ['name'=>'Mage','slug'=>'mage','css'=>'is-mage'],
  ['name'=>'Warlock','slug'=>'warlock','css'=>'is-warlock'],
  ['name'=>'Druid','slug'=>'druid','css'=>'is-druid'],
];
if ($expansion >= 2) { $classes[] = ['name'=>'Death Knight','slug'=>'deathknight','css'=>'is-dk']; }


/* ---------- helpers ---------- */
function _cache($key, callable $fn) {
    static $C = [];
    if (isset($C[$key])) return $C[$key];
    $C[$key] = $fn();
    return $C[$key];
}

function render_rank_bonus_snippet(array $setData, string $rankKey): string {
    if (empty($setData['bonuses'])) return '';

    $needed = pvp_rank_required_pieces($rankKey);
    if ($needed <= 0) return '';

    foreach ($setData['bonuses'] as $b) {
        if ((int)$b['pieces'] === $needed) {
            $desc = replace_spell_tokens($b['desc'], $b['spell']);
            return "<div class='set-note'>(<b>{$needed}</b>) {$desc}</div>";
        }
    }
    return '';
}

function pvp_rank_required_pieces(string $rankKey): int {
    static $map = [
        'PvP_R7'  => 2,  // Boots + Gloves
        'PvP_R8'  => 4,  // +Chest +Legs
        'PvP_R10' => 6,  // +Helm +Shoulders
        'PvP_R12' => 3,  // Epics: Gloves/Legs/Boots
        'PvP_R13' => 6,  // Epics complete set
    ];
    return $map[$rankKey] ?? 0;
}

function slot_order($inv) {
    switch ((int)$inv) {
      case 1:  return 1;  // Head
      case 2:  return 2;  // Neck
      case 3:  return 3;  // Shoulder
      case 5:  return 4;  // Chest
      case 6:  return 5;  // Waist
      case 7:  return 6;  // Legs
      case 8:  return 7;  // Feet
      case 9:  return 8;  // Wrist
      case 10: return 9;  // Hands
      case 11: return 10; // Finger
      case 12: return 11; // Trinket
      case 16: return 12; // Back (cloak)
      case 13: return 13; // Ranged / Thrown / Wand
      case 21: return 14; // Main Hand
      case 22: return 15; // Off Hand
      default: return 99; // Other/unexpected
    }
}

function icon_url($iconBase) { return '/armory/images/icons/64x64/'.$iconBase.'.png'; }

function find_itemset_id_by_name(string $name): int {
    global $DEBUG;
    $name = trim($name);
    if ($name === '') return 0;

    // normalize: lowercase, strip punctuation
    $norm = strtolower(preg_replace('/[^a-z0-9]+/i', '', $name));

    // fetch all possible matches from DB
    $rows = armory_query("SELECT id,name FROM dbc_itemset", 0);
    foreach ($rows as $r) {
        $dbNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '', $r['name']));
        if ($dbNorm === $norm) {
            if ($DEBUG) {
                echo "<div style='color:lime'>[MATCH exact] '$name' → '{$r['name']}' (id={$r['id']})</div>";
            }
            return (int)$r['id'];
        }
    }

    // fallback: loose contains match
    foreach ($rows as $r) {
        $dbNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '', $r['name']));
        if (strpos($dbNorm, $norm) !== false) {
            if ($DEBUG) {
                echo "<div style='color:orange'>[MATCH loose] '$name' → '{$r['name']}' (id={$r['id']})</div>";
            }
            return (int)$r['id'];
        }
    }

    if ($DEBUG) {
        echo "<div style='color:red'>[NO MATCH] '$name' (normalized: $norm)</div>";
    }

    return 0;
}

function icon_from_displayid(int $displayId): string {
    if ($displayId <= 0) return 'inv_misc_questionmark';

    // Use the 'name' column instead of 'inventoryIcon'
    $row = armory_query("SELECT name FROM dbc_itemdisplayinfo WHERE id={$displayId} LIMIT 1", 1);

    if ($row && !empty($row['name'])) {
        // Drop file extension and lowercase
        return strtolower(pathinfo($row['name'], PATHINFO_FILENAME));
    }

    return 'inv_misc_key_02';
}

function get_spell_row(int $id): ?array {
  if ($id <= 0) return null;
  return armory_query("SELECT * FROM dbc_spell WHERE id={$id} LIMIT 1", 1) ?: null;
}

function get_die_sides_n(int $spellId, int $n): int {
  if ($spellId <= 0 || $n < 1 || $n > 3) return 0;
  $col = "effect_die_sides_{$n}";
  $row = armory_query("SELECT {$col} FROM dbc_spell WHERE id={$spellId} LIMIT 1", 1);
  return $row ? (int)$row[$col] : 0;
}

function get_spell_duration_id(int $spellId): int {
  if ($spellId <= 0) return 0;
  $row = armory_query("SELECT ref_spellduration FROM dbc_spell WHERE id={$spellId} LIMIT 1", 1);
  return $row ? (int)$row['ref_spellduration'] : 0;
}

function duration_secs_from_id(int $durId): int {
  if ($durId <= 0) return 0;
  $row = armory_query("SELECT duration1,duration2 FROM dbc_spellduration WHERE id={$durId} LIMIT 1", 1);
  if (!$row) return 0;
  $min = (int)$row['duration1']; $max = (int)$row['duration2'];
  return max($min,$max) / 1000;
}

function fmt_secs(int $secs): string {
  if ($secs >= 60) {
    $m = floor($secs / 60); $s = $secs % 60;
    return $m.' min'.($s>0?' '.$s.' sec':'');
  }
  return $secs.' sec';
}

function getRadiusYdsForSpellRow(array $sp): float {
  $rid = (int)($sp['effect_radius_index_1'] ?? 0);
  if ($rid <= 0) return 0;
  $row = armory_query("SELECT radius1 FROM dbc_spellradius WHERE id={$rid} LIMIT 1", 1);
  return $row ? (float)$row['radius1'] : 0;
}

function get_spell_o_row(int $id): ?array {
  if ($id <= 0) return null;
  return armory_query("SELECT * FROM dbc_spell WHERE id={$id} LIMIT 1", 1) ?: null;
}

function get_spell_proc_charges(int $id): int {
  if ($id <= 0) return 0;
  $row = armory_query("SELECT proc_charges FROM dbc_spell WHERE id={$id} LIMIT 1", 1);
  return $row ? (int)$row['proc_charges'] : 0;
}

function _stack_amount_for_spell(int $id): int {
  if ($id <= 0) return 0;
  $row = armory_query("SELECT stack_amount FROM dbc_spell WHERE id={$id} LIMIT 1", 1);
  return $row ? (int)$row['stack_amount'] : 0;
}

function num_trim($v): string {
  $s = number_format((float)$v,1,'.','');
  return rtrim(rtrim($s,'0'),'.');
}

function _trigger_col_base(): string {
  return "effect_trigger_spell_id_";
}

function fmt_value($v) {
    return number_format($v, 0, '', ''); // simple no-commas
  }

function spell_duration(int $durId): string {
    if (!$durId) return '';
    $r = armory_query("SELECT * FROM dbc_spellduration WHERE id={$durId} LIMIT 1", 1);
    if (!$r) return '';
    $min = (int)$r['duration1']; 
    $max = (int)$r['duration2'];
    if ($min === $max) return ($min/1000).' sec';
    return ($min/1000).'–'.($max/1000).' sec';
  }

function spell_radius(int $radId): string {
    if (!$radId) return '';
    $r = armory_query("SELECT * FROM dbc_spellradius WHERE id={$radId} LIMIT 1", 1);
    if (!$r) return '';
    return (float)$r['radius1'].' yd';
  }

function class_mask_to_names(int $mask): array {
    $map = [
        1   => 'Warrior',
        2   => 'Paladin',
        4   => 'Hunter',
        8   => 'Rogue',
        16  => 'Priest',
        64  => 'Shaman',
        128 => 'Mage',
        256 => 'Warlock',
        1024=> 'Druid',
        32  => 'Death Knight', // adjust for WotLK
    ];
    $names = [];
    foreach ($map as $bit => $name) {
        if ($mask & $bit) $names[] = $name;
    }
    return $names ?: ['All'];
}

function item_href(int $entry): string { return 'armory/index.php?searchType=iteminfo&item='.$entry; }

function default_slot_names(int $pieces): array {
  if ($pieces >= 9) return ['H','S','C','W','L','F','W','H','R'];
  if ($pieces >= 8) return ['H','S','C','W','L','F','W','H'];
  return ['H','S','C','L','H'];
}

function icon_base_from_icon_id(int $iconId): string {
    if ($iconId <= 0) return 'inv_misc_key_02';
    $r = armory_query("SELECT `name` FROM `dbc_spellicon` WHERE `id`={$iconId} LIMIT 1", 1);
    if ($r && !empty($r['name'])) {
      return strtolower(preg_replace('/[^a-z0-9_]/i', '', $r['name']));
    }
    return 'inv_misc_key_01';
  }

function build_placeholder_chips(int $pieces): string {
  $icon  = icon_url('inv_misc_key_07');
  $chips = [];
  foreach (default_slot_names($pieces) as $slot) {
    $chips[] = '<span class="set-item ghost">'
             . '<img src="'.htmlspecialchars($icon).'" alt="" width="14" height="14"> '
             . htmlspecialchars($slot)
             . '</span>';
  }
  return ' <span class="set-items">— '.implode('', $chips).'</span>';
}

function item_class_name(int $class, int $sub): string {
    switch ($class) {
        case 2: // Weapon
            $weapons = [
                0=>"Axe (1H)", 1=>"Axe (2H)",
                2=>"Bow", 3=>"Gun",
                4=>"Mace (1H)", 5=>"Mace (2H)",
                6=>"Polearm", 7=>"Sword (1H)", 8=>"Sword (2H)",
                10=>"Staff", 13=>"Fist Weapon",
                15=>"Dagger", 16=>"Thrown",
                18=>"Crossbow", 19=>"Wand",
                20=>"Fishing Pole"
            ];
            return $weapons[$sub] ?? "Weapon";

        case 4: // Armor
            $armor = [
                0=>"Misc", 1=>"Cloth", 2=>"Leather",
                3=>"Mail", 4=>"Plate", 6=>"Shield",
                7=>"Libram", 8=>"Idol", 9=>"Totem", 10=>"Sigil"
            ];
            return $armor[$sub] ?? "Armor";

        case 15: // Misc
            $misc = [
                0=>"Junk", 2=>"Pet", 3=>"Holiday", 
                4=>"Other", 5=>"Mount"
            ];
            return $misc[$sub] ?? "Misc";

        default:
            return "Item";
    }
}

function stat_name(int $id): string {
    static $map = [
      3 => 'Agility',
      4 => 'Strength',
      5 => 'Intellect',
      6 => 'Spirit',
      7 => 'Stamina',
    ];
    return $map[$id] ?? '';
}

function inventory_type_name(int $id): string {
    $map = [
        1  => "Head",
        2  => "Neck",
        3  => "Shoulder",
        5  => "Chest",
        6  => "Waist",
        7  => "Legs",
        8  => "Feet",
        9  => "Wrist",
        10 => "Hands",
        11 => "Finger",
        12 => "Trinket",
        13 => "One Hand",          
        14 => "Shield",          // Off-hand shield
        15 => "Weapon",          // Holdable (books, orbs, off-hand frills)
        16 => "Back",            // Cloak
        17 => "Two-Hand",        // 2H weapons
        21 => "Main Hand",       // Main hand only
        22 => "Off Hand",        // Off hand only
        23 => "Held In Off-hand" // Non-weapon, frills
    ];
    return $map[$id] ?? "Slot ".$id;
}

function armor_set_variants($raw) {
  $raw = (string)$raw;
  if ($raw === '') return [];
  $namesPart = $raw; $rolesPart = '';
  if (preg_match('/^(.*?)(?:\(([^()]*)\))\s*$/', $raw, $m)) {
    $namesPart = trim($m[1]); $rolesPart = trim($m[2]);
  }
  $names = array_map('trim', preg_split('/\s*\/\s*/', $namesPart));
  $roles = $rolesPart !== '' ? array_map('trim', preg_split('/\s*\/\s*/', $rolesPart)) : [];
  $generics = array('Armor','Battlegear','Regalia','Raiment','Harness','Garb','Plate');
  $firstWord = explode(' ', $names[0], 2)[0];
  $out = [];
  foreach ($names as $i => $n) {
    if (strpos($n, ' ') === false && in_array($n, $generics, true)) $n = $firstWord.' '.$n;
    $out[] = ['name'=>$n, 'role'=>$roles[$i] ?? ''];
  }
  return $out;
}









































