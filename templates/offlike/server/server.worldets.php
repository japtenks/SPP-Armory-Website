<?php
/* ---------- DEBUG MODE ---------- */
$DEBUG = on;
$DEBUG = isset($_GET['debug']) ? strtolower($_GET['debug']) : '';
if ($DEBUG) {
  echo "<div style='background:black;color:lime;padding:6px;font-weight:bold;'>DEBUG MODE: "
     . htmlspecialchars($DEBUG) . "</div>";
}
?>
<img src="<?php echo $currtmp; ?>/images/armorsets.jpg" /><br/>
<?php builddiv_start(1, $lang['armorsets2']); ?>

<!--Armor page (tier/dungeon sets) solid code-->
<?php


/* ---------- read expansion from the parent index ---------- */
$expansion = isset($GLOBALS['expansion']) ? (int)$GLOBALS['expansion'] : 1; // 0 Classic, 1 TBC, 2 WotLK


/* Uses the connections created in index.php: $DB (site/armory), $WSDB (world), $CHDB (chars) */

// Adjust names here if your schema names differ
$SCHEMAS = [
  0 => ['armory' => 'classicarmory', 'world' => 'classicmangos'],
  1 => ['armory' => 'tbcarmory',     'world' => 'tbcmangos'],
  2 => ['armory' => 'wotlkarmory',   'world' => 'wotlkmangos'],
];

$ERA           = isset($SCHEMAS[$expansion]) ? $expansion : 1;
$ARMORY_SCHEMA = $SCHEMAS[$ERA]['armory'];
$WORLD_SCHEMA  = $SCHEMAS[$ERA]['world'];

/* Qualify bare table names with a schema (only the tables we use). */
function qualify_tables($sql, $schema, array $tables) {
  $pat = '/(?<=\bFROM|\bJOIN)\s+`?(' . implode('|', array_map('preg_quote', $tables)) . ')`?\b/i';
  return preg_replace($pat, ' `'.$schema.'`.`$1`', $sql);
}

/* DbSimple dispatcher that mimics your execute_query modes: 0=list, 1=row, 2=cell */
function _dbsimple_run($conn, $sql, $mode = 0) {
  switch ($mode) {
    case 1: return $conn->selectRow($sql);
    case 2: return $conn->selectCell($sql);
    default:return $conn->select($sql);
  }
}

/* DBC (armory) query helper – runs against <era>armory schema */
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

/* WORLD query helper – runs against <era>mangos schema */
function world_query($sql, $mode = 0) {
  global $WSDB, $WORLD_SCHEMA;
  $sql = qualify_tables($sql, $WORLD_SCHEMA, ['item_template']);
  return _dbsimple_run($WSDB, $sql, $mode);
}

/* ---------- tiny cache ---------- */
if (!function_exists('_cache')) {
  function _cache($k, $fn) { static $C=[]; return $C[$k] ?? ($C[$k]=$fn()); }
}

/* ---------- style mode (classic | floating) ---------- */
$styleParam    = isset($_GET['style']) ? strtolower($_GET['style']) : '';
$MODE_FLOATING = in_array($styleParam, ['floating','float','1','on'], true);
$styleQS       = $MODE_FLOATING ? '&style=floating' : '';


/* ---------- config/data ---------- */
$maxTier   = ($expansion === 2) ? 10 : (($expansion === 1) ? 6 : 3);

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

//mappings
$N['WD_Cloth'] = [
  'Mage'    => "Necropile Raiment / The Postmaster / Twilight Trappings / Regalia of Undead Cleansing",
  'Priest'  => "Necropile Raiment / The Postmaster / Twilight Trappings / Regalia of Undead Cleansing",
  'Warlock' => "Necropile Raiment / The Postmaster / Twilight Trappings / Regalia of Undead Cleansing",
];

$N['WD_Leather'] = [
  'Rogue'   => "Cadaverous Garb / Spirit of Eskhandar / Embrace of the Viper / Defias Leather",
  'Druid'   => "Cadaverous Garb / Spirit of Eskhandar / Embrace of the Viper",
];

$N['WD_Mail'] = [
  'Warrior'  => "Chain of the Scarlet Crusade",
  'Paladin'  => "Chain of the Scarlet Crusade",
  'Hunter'  => "Chain of the Scarlet Crusade / Garb of the Undead Slayer",
  'Shaman'  => "Chain of the Scarlet Crusade / Garb of the Undead Slayer",
];

$N['WD_Plate'] = [
  'Warrior' => "Deathbone Guardian / Battlegear of Undead Slaying / Undead Slayer's Armor",
  'Paladin' => "Deathbone Guardian / Battlegear of Undead Slaying / Undead Slayer's Armor",
];

$N['WD_Weapons'] = [
  'Warrior'     => "Dal'Rend's Arms / Spider's Kiss / Shard of the Gods / The Twin Blades of Hakkari / 
                Primal Blessing / Zanzil's Concentration / Overlord's Resolution / 
                Prayer of the Primal / Major Mojo Infusion",
];


/* ---------- blurbs ---------- */
$BLURB = [
  'WD_Cloth'=>[
    'title'=>"World Drop Cloth Sets",'pieces'=>5,
    'text'=>"Rare cloth sets found outside the normal raid tiers. Includes dungeon-only drops and event sets 
             like <b>Necropile</b>, <b>The Postmaster</b>, <b>Twilight Cultist</b> regalia, and <b>Undead Cleansing</b> outfits. 
             <span class='set-note'>(Classic–TBC era)</span>"
  ],
  'WD_Leather'=>[
    'title'=>"World Drop Leather Sets",'pieces'=>5,
    'text'=>"Leather armor sets earned through rare dungeon drops and mini-events. 
             <b>Cadaverous Garb</b>, <b>Defias Leather</b>, and <b>Spirit of Eskhandar</b> provided rogues and druids 
             with unique bonuses outside raid progression."
  ],
  'WD_Mail'=>[
    'title'=>"World Drop Mail Sets",'pieces'=>5,
    'text'=>"Mail mini-sets such as the <b>Chain of the Scarlet Crusade</b> and <b>Undead Slaying</b> gear. 
             Often pieced together through world events or special dungeon encounters."
  ],
  'WD_Plate'=>[
    'title'=>"World Drop Plate Sets",'pieces'=>5,
    'text'=>"Plate sets like <b>Deathbone Guardian</b> from Scholomance and <b>Undead Slaying</b> gear from 
             the Scourge Invasion offered defensive bonuses and unique visuals for tanks and paladins."
  ],
  'WD_Weapons'=>[
    'title'=>"World Drop Weapon & Trinket Combos",'pieces'=>2,
    'text'=>"Some ‘sets’ weren’t armor at all, but paired weapons or trinkets with hidden power. 
             <b>Dal’Rend’s Arms</b>, <b>Spider’s Kiss</b>, <b>Shard of the Gods</b>, and <b>Zul’Gurub’s Primal Blessing</b> 
             gave lasting flavor to Classic gearing."
  ],
];


/* ---------- display order ---------- */
$order = ['WD_Cloth','WD_Leather','WD_Mail','WD_Plate','WD_Weapons'];
?>

<!--Helper section-->
<?php

/* ---------- helpers ---------- */
function _cache($key, callable $fn) {
    static $C = [];
    if (isset($C[$key])) return $C[$key];
    $C[$key] = $fn();
    return $C[$key];
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
    if ($class == 4) {
        $armor = [1=>"Cloth",2=>"Leather",3=>"Mail",4=>"Plate"];
        return $armor[$sub] ?? "Armor";
    }
    return "Item";
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
        1=>"Head", 2=>"Neck", 3=>"Shoulder",
        5=>"Chest", 6=>"Waist", 7=>"Legs",
        8=>"Feet", 9=>"Wrist", 10=>"Hands",
        11=>"Finger", 12=>"Trinket",
        16=>"Back",
        20=>"Chest (Robe)" // robe = chest variant
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

?>

<!-- Main functions -->
<?php

function render_armor_set($nm, $pieces, $setData, $setId) {
    global $DEBUG;

    if ($DEBUG) {
        echo "<div style='color:aqua'>[RENDER] name='".htmlspecialchars(is_array($nm)?json_encode($nm):$nm)."'</div>";
        echo "<div style='color:yellow'>[RENDER] setId={$setId}, pieces={$pieces}</div>";
        echo "<div style='color:orange'>[RENDER] items=".count($setData['items'] ?? [])."</div>";
    }

    if (empty($setData) || empty($setData['items'])) {
        if ($DEBUG) echo "<div style='color:red'>[RENDER FAIL] No items for setId {$setId}</div>";
        return;
    }

    // --- build tooltip with set bonuses ---
    $bonusTip = '';
    if (!empty($setData['bonuses'])) {
        $bonusTip = htmlspecialchars(render_set_bonus_tip_html($setData), ENT_QUOTES);
    }

    echo "<div class='set-row'>";
    echo "<div class='set-name js-set-tip' data-tip-html='{$bonusTip}'>"
       . htmlspecialchars($nm)
       . "</div>";

    // item icons
    echo "<div class='set-icons'>";
    foreach ($setData['items'] as $it) {
        if ($DEBUG) {
            echo "<div style='color:lime'>[ITEM ICON] {$it['entry']} - {$it['name']} (slot={$it['slot']}, icon={$it['icon']})</div>";
        }
        $icon = icon_url($it['icon']);
        echo "<a href='".item_href($it['entry'])."' class='js-item-tip' "
            ."data-tip-html='".htmlspecialchars(render_item_tip_html($it),ENT_QUOTES)."'>"
            ."<img src='{$icon}' alt='' width='32' height='32'>"
            ."</a>";
    }
    echo "</div>"; // .set-icons

    echo "</div>"; // .set-row
}



function get_itemset_data(int $setId): array {
    global $DB;

    // --- Pull the set row ---
    $row = armory_query("SELECT * FROM dbc_itemset WHERE id={$setId} LIMIT 1", 1);
    if (!$row) {
        return [
            'id'      => $setId,
            'name'    => 'Unknown Set',
            'items'   => [],
            'bonuses' => [],
        ];
    }

    // --- Items loop ---
    $items = [];
    for ($i = 1; $i <= 10; $i++) {
        $itemId = (int)($row["item_$i"] ?? 0);
        if (!$itemId) continue;

        // Query item_template for basic info
        $sql = "
            SELECT it.entry, it.name, it.InventoryType, it.displayid, it.Quality
            FROM item_template it
            WHERE it.entry = {$itemId}
            LIMIT 1
        ";
        $item = world_query($sql, 1);
		// echo "<div style='color:red;'>Missing item_template for ID: {$itemId} (set {$setId})</div>";

        if ($item) {
            // Resolve icon from displayid using helper
            $iconBase = icon_from_displayid((int)$item['displayid']);

            $items[] = [
                'entry' => (int)$item['entry'],
                'slot'  => (int)$item['InventoryType'],
                'name'  => (string)$item['name'],
                'icon'  => $iconBase,
				 'q'=>(int)$item['Quality'],
            ];
        }
    }
		// --- Sort items into slot order ---
	usort($items, function($a, $b) {
		return slot_order($a['slot']) <=> slot_order($b['slot']);
	});

    // --- Bonuses loop ---
    $bonuses = [];
    for ($b = 1; $b <= 8; $b++) {
        $bonusId = (int)($row["bonus_$b"] ?? 0);
        $pieces  = (int)($row["pieces_$b"] ?? 0);
        if (!$bonusId || !$pieces) continue;

        $sp = armory_query("SELECT * FROM dbc_spell WHERE id={$bonusId} LIMIT 1", 1);
        if ($sp) {
            $bonuses[] = [
                'pieces' => $pieces,
                'name'   => (string)($sp['name'] ?? ''),
                'desc'   => (string)($sp['description'] ?? ''),
                'icon'   => icon_base_from_icon_id((int)($sp['ref_spellicon'] ?? 0)),
                'spell'  => $sp, // keep raw spell row for token replacement
            ];
        }
    }

    return [
        'id'      => $setId,
        'name'    => (string)($row['name'] ?? 'Unknown Set'),
        'items'   => $items,
        'bonuses' => $bonuses,
    ];
}

function render_set_bonus_tip_html(array $setData): string {
	$qualityColors=[0=>'#9d9d9d',1=>'#ffffff',2=>'#1eff00',3=>'#0070dd',4=>'#a335ee',5=>'#ff8000',6=>'#e6cc80',7=>'#e6cc80'];
	$maxQ=0;
	if (!empty($setData['items'])) { foreach ($setData['items'] as $it){ $q=(int)($it['q']??0); if($q>$maxQ)$maxQ=$q; } }
	$nameColor=$qualityColors[$maxQ] ?? '#f1f6ff';
	$h  = '<div class="tt-bonuses">';
	$h .= '<h5 style="color:'.$nameColor.'">'.htmlspecialchars($setData['name'] ?? 'Unknown Set').'</h5>';


    if (!empty($setData['bonuses'])) {
      // Sort by pieces needed (lowest first: 2, 4, 6…)
      usort($setData['bonuses'], function($a,$b){
        return ($a['pieces'] <=> $b['pieces']);
      });

      foreach ($setData['bonuses'] as $b) {
        $pieces = (int)$b['pieces'];

        // Run description through spell token replacer if possible
        $descRaw = (string)($b['desc'] ?? '');
        $desc    = ($descRaw !== '' && isset($b['spell']))
                   ? replace_spell_tokens($descRaw, $b['spell'])
                   : $descRaw;

        // Escape for HTML output
        $desc = htmlspecialchars($desc);

        $h .= '<div class="tt-bonus-row" '
            . 'style="display:flex;gap:8px;align-items:flex-start;margin:6px 0;color:#1eff00">'
            .   '<div><div><b>('.$pieces.')</b> '.$desc.'</div>'
            .   '</div>'
            . '</div>';
      }
    } else {
      $h .= '<div class="tt-subtle">No set bonuses found.</div>';
    }

    $h .= '</div>';
    return $h;
  }

function render_rating_lines(array $row): string {
    $ratingMap = [
      12=>'Defense Rating', 13=>'Dodge Rating', 14=>'Parry Rating',
      15=>'Block Rating', 16=>'Hit Rating (Melee)', 17=>'Hit Rating (Ranged)',
      18=>'Hit Rating (Spell)', 19=>'Crit Rating (Melee)', 20=>'Crit Rating (Ranged)',
      21=>'Crit Rating (Spell)', 25=>'Resilience Rating', 28=>'Haste Rating (Melee)',
      29=>'Haste Rating (Ranged)', 30=>'Haste Rating (Spell)', 31=>'Hit Rating',
      32=>'Crit Rating', 35=>'Resilience Rating', 36=>'Haste Rating',
      37=>'Expertise Rating', 44=>'Armor Penetration Rating',
      47=>'Spell Penetration', 48=>'Block Value'
    ];
    $out = '';
    for ($i=1; $i<=10; $i++) {
        $t = (int)($row["stat_type{$i}"] ?? 0);
        $v = (int)($row["stat_value{$i}"] ?? 0);
        if ($t && $v && isset($ratingMap[$t])) {
            $out .= '<div style="color:#00ff00">Equip: Improves '.$ratingMap[$t].' by '.$v.'.</div>';
        }
    }
    return $out;
}

function render_spell_effect(int $spellId, int $trigger, array $row = []): string {
    if ($spellId <= 0) return '';
    $sp = armory_query("SELECT * FROM dbc_spell WHERE id={$spellId} LIMIT 1", 1);
    if (!$sp) return '';
    $desc = replace_spell_tokens((string)$sp['description'], $sp);
    if ($desc === '') return '';
    $prefix = ($trigger == 1) ? 'Equip: '
            : (($trigger == 2) ? 'Use: '
            : (($trigger == 4) ? 'Chance on hit: ' : ''));
    return '<div style="color:#00ff00">'.$prefix.htmlspecialchars($desc).'</div>';
}

function render_item_tip_html(array $item): string {
    $sql = "
        SELECT Quality, ItemLevel, InventoryType, class, subclass,
               RequiredLevel, Armor, MaxDurability, AllowableClass,
               stat_type1, stat_value1,
               stat_type2, stat_value2,
               stat_type3, stat_value3,
               stat_type4, stat_value4,
               stat_type5, stat_value5,
               stat_type6, stat_value6,
               stat_type7, stat_value7,
               stat_type8, stat_value8,
               stat_type9, stat_value9,
               stat_type10, stat_value10,
               spellid_1, spelltrigger_1,
               spellid_2, spelltrigger_2,
               spellid_3, spelltrigger_3,
               spellid_4, spelltrigger_4,
               spellid_5, spelltrigger_5,
               holy_res, fire_res,
               nature_res, frost_res,
               shadow_res, arcane_res
        FROM item_template
        WHERE entry = {$item['entry']}
        LIMIT 1
    ";
    $row = world_query($sql, 1);
    if (!$row) return '<div class="tt-item"><h5>Unknown Item</h5></div>';

    // Quality color
    $qualityColors = [
        0 => '#9d9d9d', 1 => '#ffffff', 2 => '#1eff00',
        3 => '#0070dd', 4 => '#a335ee', 5 => '#ff8000'
    ];
    $qColor = $qualityColors[(int)$row['Quality']] ?? '#ffffff';

    // Build tooltip
    $h  = '<div class="tt-item">';
    $h .= '<h5 style="color:'.$qColor.'">'.htmlspecialchars($item['name']).'</h5>';
    $h .= '<div style="color:#ffd100">Item Level '.(int)$row['ItemLevel'].'</div>';

    // Slot + class name
    $slotName  = inventory_type_name((int)$row['InventoryType']);
    $className = item_class_name((int)$row['class'], (int)$row['subclass']);
    $h .= '<div style="display:flex;justify-content:space-between;">'
        . '<div>'.$slotName.'</div>'
        . '<div style="text-align:right;">'.$className.'</div>'
        . '</div>';

    // Armor
    if ($row['Armor'] > 0) {
        $h .= '<div>'.(int)$row['Armor'].' Armor</div>';
    }

    // Primary stats
    for ($i=1; $i<=10; $i++) {
        $t = (int)($row["stat_type{$i}"] ?? 0);
        $v = (int)($row["stat_value{$i}"] ?? 0);
        if ($t && $v) {
            $label = stat_name($t);
            if ($label !== '') {
                $h .= '<div>+'.$v.' '.$label.'</div>';
            }
        }
    }

    // Resistances
    $resMap = [
        'holy_res'   => 'Holy Resistance',
        'fire_res'   => 'Fire Resistance',
        'nature_res' => 'Nature Resistance',
        'frost_res'  => 'Frost Resistance',
        'shadow_res' => 'Shadow Resistance',
        'arcane_res' => 'Arcane Resistance'
    ];
    foreach ($resMap as $col=>$name) {
        $val = (int)($row[$col] ?? 0);
        if ($val > 0) {
            $h .= '<div style="color:#00ccff">+'.$val.' '.$name.'</div>';
        }
    }

    // Spells (Equip/Use/Chance on Hit)
    for ($i=1; $i<=5; $i++) {
        $sid = (int)($row["spellid_{$i}"] ?? 0);
        $trg = (int)($row["spelltrigger_{$i}"] ?? 0);
        $h  .= render_spell_effect($sid, $trg, $row);
    }

    // Durability
    if ($row['MaxDurability'] > 0) {
        $h .= '<div>Durability '.$row['MaxDurability'].' / '.$row['MaxDurability'].'</div>';
    }

    // Class restrictions
   /*  if (!empty($row['AllowableClass']) && $row['AllowableClass'] > 0) {
        $classList = class_mask_to_names((int)$row['AllowableClass']);
        $h .= '<div>Classes: '.implode(', ', $classList).'</div>';
    } */

    // Required level
    if ($row['RequiredLevel'] > 0) {
        $h .= '<div>Requires Level '.$row['RequiredLevel'].'</div>';
    }

    $h .= '</div>';
    return $h;
}

/**
 * Replace Blizzard-style tooltip tokens.
 * Requires helpers already in your file:
 *   _cache(), get_spell_row(), get_die_sides_n(), get_spell_duration_id(),
 *   duration_secs_from_id(), fmt_secs(), getRadiusYdsForSpellRow(),
 *   get_spell_proc_charges(), _stack_amount_for_spell().
 */
function replace_spell_tokens(string $desc, array $sp): string {
  /* ---------- tiny formatters ---------- */
  $fmt = static function($v): string {
    $s = number_format((float)$v, 1, '.', '');
    return rtrim(rtrim($s, '0'), '.') ?: '0';
  };
  $fmtInt = static function($v): string { return (string)((int)round($v)); };

  /* ---------- current spell quick facts ---------- */
  $currId = (int)($sp['id'] ?? 0);
  $die1 = _cache("die:".$currId.":1", function() use ($currId) { return $currId ? get_die_sides_n($currId,1) : 0; });
  $die2 = _cache("die:".$currId.":2", function() use ($currId) { return $currId ? get_die_sides_n($currId,2) : 0; });
  $die3 = _cache("die:".$currId.":3", function() use ($currId) { return $currId ? get_die_sides_n($currId,3) : 0; });

  // bp+1 with optional dice range text
  $formatS = static function (int $bp, int $die): array {
    $min = $bp + 1;
    if ($die <= 1) return [$min, $min, (string)abs($min)];
    $max = $bp + $die;
    if ($max < $min) { $t=$min; $min=$max; $max=$t; }
    return [$min, $max, $min.' to '.$max];
  };

  list($s1min,$s1max,$s1txt) = $formatS((int)($sp['effect_basepoints_1'] ?? 0), $die1);
  list($s2min,$s2max,$s2txt) = $formatS((int)($sp['effect_basepoints_2'] ?? 0), $die2);
  list($s3min,$s3max,$s3txt) = $formatS((int)($sp['effect_basepoints_3'] ?? 0), $die3);

  // over-time totals for current spell (O tokens)
  $durId  = (int)($sp['ref_spellduration'] ?? 0);
  $durSec = duration_secs_from_id($durId);
  $durMs  = $durSec * 1000;

  $oN = static function(array $sp, int $idx, int $durMs): int {
    $bp  = abs((int)($sp["effect_basepoints_{$idx}"] ?? 0) + 1);
    $amp = (int)($sp["effect_amplitude_{$idx}"] ?? 0);
    $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
    return $ticks > 0 ? ($bp * $ticks) : $bp;
  };
  $o1 = $oN($sp,1,$durMs); $o2 = $oN($sp,2,$durMs); $o3 = $oN($sp,3,$durMs);

  // headline defaults ($h)
  $h = (int)($sp['proc_chance'] ?? 0);
  if ($h <= 0) $h = (int)$s1min;

  // radius & tick periods (current spell)
  $a1 = $fmt(getRadiusYdsForSpellRow($sp));
  $t1 = $fmt(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000);
  $t2 = $fmt(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000);
  $t3 = $fmt(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000);

  // stacks for current spell ($u)
  $u = _stack_amount_for_spell($currId);
  if ($u <= 0) { $bp = (int)($sp['effect_basepoints_1'] ?? 0); $u = max(1, abs($bp + 1)); }

  // duration shorthand ($d / $D)
  $d = fmt_secs($durSec);

  /* ---------- helpers for external spell reads ---------- */
  $extS = static function(int $sid, int $idx) use ($formatS){
    $row = _cache("spell:".$sid, function() use ($sid) { return get_spell_row($sid); });
    if (!$row) return [0,0,'0'];
    return $formatS((int)($row["effect_basepoints_{$idx}"] ?? 0), get_die_sides_n($sid,$idx));
  };
  $extO = static function(int $sid, int $idx): int {
    $row = _cache("spellO:".$sid, function() use ($sid) { return get_spell_row($sid); });
    if (!$row) return 0;
    $bp  = abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
    $amp = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $sec = duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
    $ticks = ($amp > 0) ? (int)floor(($sec*1000)/$amp) : 0;
    return $ticks > 0 ? $bp * $ticks : $bp;
  };
$extD = static function ($sid) {
  $sid = (int)$sid;
  $secs = duration_secs_from_id(get_spell_duration_id($sid));
  if ($secs <= 0) {
    $seen = array(); $q = array($sid);
    for ($d=0; !empty($q) && $d<2; $d++) {
      $next = array();
      foreach ($q as $x) {
        if (isset($seen[$x])) continue; $seen[$x]=1;
        $cols = "COALESCE(effect_trigger_spell_id_1, effect_trigger_spell_1, 0) AS t1,
                 COALESCE(effect_trigger_spell_id_2, effect_trigger_spell_2, 0) AS t2,
                 COALESCE(effect_trigger_spell_id_3, effect_trigger_spell_3, 0) AS t3";
        $r = armory_query("SELECT $cols FROM dbc_spell WHERE id=".$x." LIMIT 1", 1);
        for ($i=1;$i<=3;$i++){
          $k='t'.$i; $t = isset($r[$k])?(int)$r[$k]:0; if($t<=0) continue;
          $s = duration_secs_from_id(get_spell_duration_id($t));
          if ($s>$secs) $secs=$s; if (!isset($seen[$t])) $next[]=$t;
        }
      } $q=$next;
    }
  }
  if ($secs <= 0) {
    $cond = "COALESCE(effect_trigger_spell_id_1,0)=$sid OR COALESCE(effect_trigger_spell_1,0)=$sid OR ".
            "COALESCE(effect_trigger_spell_id_2,0)=$sid OR COALESCE(effect_trigger_spell_2,0)=$sid OR ".
            "COALESCE(effect_trigger_spell_id_3,0)=$sid OR COALESCE(effect_trigger_spell_3,0)=$sid";
    $rows = armory_query("SELECT id FROM dbc_spell WHERE $cond LIMIT 20", 0);
    if (is_array($rows)) foreach ($rows as $row) {
      $s = duration_secs_from_id(get_spell_duration_id((int)$row['id']));
      if ($s>$secs) $secs=$s;
    }
  }
  return fmt_secs($secs);
};



  /* ---------- 1) id-based simple forms ---------- */
  $desc = preg_replace_callback('/\$(\d+)s([1-3])\b/', function($m) use($extS){
    $tmp = $extS((int)$m[1], (int)$m[2]); return $tmp[2];
  }, $desc);
  $desc = preg_replace_callback('/\$(\d+)s\b/', function($m) use($extS){
    $tmp = $extS((int)$m[1], 1); return $tmp[2];
  }, $desc);
  $desc = preg_replace_callback('/\$(\d+)o([1-3])\b/', function($m) use($extO){
    return (string)$extO((int)$m[1], (int)$m[2]);
  }, $desc);
  $desc = preg_replace_callback('/\$(\d+)d\b/', function($m) use($extD){
    return $extD((int)$m[1]);
  }, $desc);
  $desc = preg_replace_callback('/\$(\d+)a1\b/', function($m){
    $sid = (int)$m[1];
    $row = _cache("spell:".$sid, function() use ($sid) { return get_spell_row($sid); });
    if(!$row) return '0';
    return (string)(float)getRadiusYdsForSpellRow($row);
  }, $desc);
  $desc = preg_replace_callback('/\$(\d+)t([1-3])\b/', function($m){
    $sid = (int)$m[1]; $idx=(int)$m[2];
    $row = _cache("spellO:".$sid, function() use ($sid) { return get_spell_row($sid); });
    if(!$row) return '0';
    $amp = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    return $amp > 0 ? rtrim(rtrim(number_format($amp/1000,1,'.',''),'0'),'.') : '0';
  }, $desc);

  /* ---------- 2) divide forms ---------- */
// replace the pattern of the “divide forms” block with this:
$desc = preg_replace_callback(
  '/\$\s*\/\s*(-?\d+)\s*;\s*(?:\$?(\d+))?([sS]|o)([1-3])\b/',
  function($m) use($s1min,$s1max,$s2min,$s2max,$s3min,$s3max,$extS,$extO,$fmt,$fmtInt,$o1,$o2,$o3){
    $div=(int)$m[1] ?: 1; $sid = isset($m[2]) ? (int)$m[2] : 0; $type=strtolower($m[3]); $idx=(int)$m[4];
    if($type==='s'){ if($sid===0){$minMap=[1=>$s1min,2=>$s2min,3=>$s3min];$maxMap=[1=>$s1max,2=>$s2max,3=>$s3max];}
      else{$tmp=$extS($sid,$idx);$minMap=[$idx=>$tmp[0]];$maxMap=[$idx=>$tmp[1]];}
      $min=abs((float)($minMap[$idx]??0))/$div; $max=abs((float)($maxMap[$idx]??$min))/$div;
      return ($max>$min)?$fmtInt(floor($min)).' to '.$fmtInt(ceil($max)):$fmt($min);
    }
    $v=($sid===0)?([1=>$o1,2=>$o2,3=>$o3][$idx]??0):$extO($sid,$idx);
    return $fmt($v/$div);
  },
  $desc
);

  $desc = preg_replace_callback('/\$\s*\/\s*(-?\d+)\s*;\s*S([1-3])\b/',
    function($m) use($s1min,$s2min,$s3min,$fmt){
      $div=(int)$m[1]?:1; $idx=(int)$m[2]; $map=[1=>$s1min,2=>$s2min,3=>$s3min];
      return $fmt(abs((float)($map[$idx]??0))/$div);
    }, $desc
  );

// put this BEFORE your other ${...} handlers
$playerLevel = isset($GLOBALS['expansion'])
  ? (($GLOBALS['expansion']==2)?80:(($GLOBALS['expansion']==1)?70:60))
  : 60;

$desc = preg_replace_callback(
  '/\$\{\s*\(\s*300\s*-\s*10\s*\*\s*\$max\s*\(\s*0\s*,\s*\$PL\s*-\s*60\s*\)\s*\)\s*\/\s*10\s*\}/i',
  function() use ($playerLevel, $fmt) {
    $pl   = (int)$playerLevel;           // default cap by era
    $rage = (300 - 10 * max(0, $pl - 60)) / 10; // equals 30 - max(0, PL-60)
    return $fmt($rage);
  },
  $desc
);

  /* ---------- 3) ${...} math blocks ---------- */
  $desc = preg_replace_callback('/\$\{\s*\$m([1-3])\s*\/\s*(-?\d+)\s*\}/i',
    function($m) use($s1min,$s2min,$s3min,$fmt){
      $map=[1=>$s1min,2=>$s2min,3=>$s3min];
      return $fmt(abs((float)($map[(int)$m[1]]??0))/((int)$m[2]?:1));
    }, $desc
  );
  $desc = preg_replace_callback('/\$\{\s*\$(\d+)m([1-3])\s*\/\s*(-?\d+)\s*\}/i',
    function($m) use($extS,$fmt){
      $tmp = $extS((int)$m[1], (int)$m[2]);
      return $fmt(abs((float)$tmp[0])/((int)$m[3]?:1));
    }, $desc
  );
  $desc = preg_replace_callback('/\$\{\s*(-?\d+)\s*\/\s*(-?\d+)\s*\}\b/',
    function($m) use($fmt){
      $a=(int)$m[1]; $b=(int)$m[2]; $v=(abs($a)>=abs($b))?$a:$b;
      return $fmt(abs($v)/10.0);
    }, $desc
  );
  $desc = preg_replace_callback('/\{\$\s*(AP|RAP|SP)\s*\*\s*\$m([1-3])\s*\/\s*100\s*\}/i',
    function($m) use($s1min,$s2min,$s3min){
      $pct=[1=>$s1min,2=>$s2min,3=>$s3min][(int)$m[2]] ?? 0;
      $label=['AP'=>'Attack Power','RAP'=>'Ranged Attack Power','SP'=>'Spell Power'][strtoupper($m[1])] ?? strtoupper($m[1]);
      return "({$label} * ".(int)abs($pct)." / 100)";
    }, $desc
  );

  /* ---------- 4) plain tokens ---------- */
  $desc = strtr($desc, [
    '$s1'=>$s1txt, '$s2'=>$s2txt, '$s3'=>$s3txt,
    '$o1'=>(string)$o1, '$o2'=>(string)$o2, '$o3'=>(string)$o3,
    '$t1'=>$t1, '$t2'=>$t2, '$t3'=>$t3,
    '$a1'=>$a1, '$d'=>$d, '$D'=>$d, '$h'=>(string)$h, '$u'=>(string)$u,
  ]);
  $desc = preg_replace_callback('/\$(m[1-3]|m)\b/i', function($m) use($s1min,$s2min,$s3min){
    if (strtolower($m[1]) === 'm') return (string)$s1min;
    $map=['m1'=>$s1min,'m2'=>$s2min,'m3'=>$s3min];
    $k=strtolower($m[1]); return (string)($map[$k] ?? 0);
  }, $desc);
  $desc = preg_replace('/\$h1\b/', (string)$h, $desc);

  /* ---------- 5) grammar helpers ---------- */
  while (preg_match('/\$l([^:;]+):([^;]+);/', $desc, $m, PREG_OFFSET_CAPTURE)) {
    $full=$m[0][0]; $off=$m[0][1]; $sg=$m[1][0]; $pl=$m[2][0];
    $before = substr($desc,0,$off);
    $val = 2; if (preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/',$before,$nm)) $val=(float)$nm[1];
    $word = (abs($val-1.0)<1e-6)?$sg:$pl;
    $desc = substr($desc,0,$off).$word.substr($desc,$off+strlen($full));
  }
  $mulMap = [
    's1'=>(float)$s1min, 's2'=>(float)$s2min, 's3'=>(float)$s3min,
    'o1'=>(float)$o1,    'o2'=>(float)$o2,    'o3'=>(float)$o3,
    'm1'=>(float)$s1min, 'm2'=>(float)$s2min, 'm3'=>(float)$s3min,
  ];
  $desc = preg_replace_callback('/\$\*\s*([0-9]+(?:\.[0-9]+)?)\s*;\s*(s[1-3]|o[1-3]|m[1-3])\b/i',
    function($m) use($mulMap,$fmt){
      $k=strtolower($m[2]); $factor=(float)$m[1]; $base=$mulMap[$k] ?? 0.0;
      return $fmt($factor * $base);
    }, $desc
  );

  /* ---------- 6) cleanup ---------- */
  $desc = preg_replace('/\s+%/', '%', $desc);
  $desc = preg_replace('/\$\(/', '(', $desc);
  $desc = preg_replace('/(\d+)1%/', '$1%', $desc);
  $desc = preg_replace('/\$(?=\d)(\d+(?:\.\d+)?)\b/', '$1', $desc);
  $desc = preg_replace('/(-?\d+(?:\.\d+)?)\.(?:1|2)(?=\s*sec\b)/', '$1', $desc);


  return $desc;
}



?>

<!--Main body of page HTML -->
<?php
/* ---------- class bar ---------- */
foreach ($classes as $c) {
  if (strcasecmp($selectedClass, $c['name']) === 0) { $selectedClass = $c['name']; break; }
}

/* Wrap everything in .mode-floating when requested */
if ($MODE_FLOATING) echo '<div class="mode-floating">';

echo '<div class="class-bar">';
foreach ($classes as $c){
  $href   = 'index.php?n=server&sub=armorsets&class='.rawurlencode($c['name']).$styleQS;
  $src    = $iconBase.$iconPref.$c['slug'].$iconExt;
  $active = (strcasecmp($selectedClass,$c['name'])===0) ? ' is-active' : '';
  echo '<a class="class-token '.$c['css'].$active.'" href="'.$href.'" aria-label="'.htmlspecialchars($c['name']).'" data-name="'.htmlspecialchars($c['name']).'"><img src="'.$src.'" alt="'.htmlspecialchars($c['name']).'"></a>';
}
// style switcher pill
$base = 'index.php?n=server&sub=armorsets' . ($selectedClass ? '&class='.rawurlencode($selectedClass) : '');
echo '<span class="style-toggle" style="margin-left:auto;display:flex;gap:8px;align-items:center;">'
   . '<a href="'.$base.'&style=classic"  class="pill'.($MODE_FLOATING?'':' is-on').'"  style="padding:4px 10px;border-radius:999px;background:rgba(0,0,0,.08);text-decoration:none;color:#3a2a12;font-weight:700;">Classic</a>'
   . '<a href="'.$base.'&style=floating" class="pill'.($MODE_FLOATING?' is-on':'').'"  style="padding:4px 10px;border-radius:999px;background:rgba(0,0,0,.08);text-decoration:none;color:#3a2a12;font-weight:700;">Floating</a>'
   . '</span>';
echo '</div>'; // end .class-bar


/* If no class, nudge and stop */
if ($selectedClass === '') {
  echo '<div class="set-group"><div class="set-subtitle">Choose a class above to see their Dungeon & Tier sets.</div></div>';
  if ($MODE_FLOATING) echo '</div>';
  builddiv_end(); return;
}

/* ---------- web page body content - armor descriptions, etc---------- */
echo '<div class="set-group"><div class="set-title">Dungeon & Tier Sets</div>';






// ---------- Render set rows ----------
foreach ($order as $key) {
  if (!isset($BLURB[$key])) {
    echo '<div class="set-desc set-note">No data block found for '.$key.'.</div>';
    continue;
  }

  $title   = $BLURB[$key]['title'];
  $pieces  = (int)$BLURB[$key]['pieces'];
  $text    = $BLURB[$key]['text'];
  $pairs   = (!empty($N[$key][$selectedClass])) 
               ? armor_set_variants($N[$key][$selectedClass]) : [];

  echo "<div class='set-block'>";
  echo "<div class='set-title'>{$title}</div>";
  echo "<div class='set-desc'>{$text}</div>";

foreach ($pairs as $nm) {
    $setName = $nm['name'];         // use the actual set name string
    $setRole = $nm['role'] ?? '';   // optional role/spec info (if present)
	
	    // Debug: show raw pair data
    if ($DEBUG) {
        echo "<div style='color:cyan'>[PAIR] ".htmlspecialchars(print_r($nm,true))."</div>";
    }

    
    $setId   = find_itemset_id_by_name($setName);
	    // Debug: show name lookup → setId
    if ($DEBUG) {
        echo "<div style='color:orange'>[LOOKUP] '$setName' → setId=$setId</div>";
    }
	
    $items   = ($setId) ? get_itemset_data($setId) : [];
	
	    // Debug: show how many items found
    if ($DEBUG) {
        $count = is_array($items) && !empty($items['items']) ? count($items['items']) : 0;
        echo "<div style='color:yellow'>[ITEMS] setId=$setId → {$count} items</div>";
    }

    render_armor_set($setName, $pieces, $items, $setId);
}


  echo "</div>";
}

builddiv_end();
?>

<style>
/* ---------- Armor Sets Styles ---------- */


/* ---------- Class Bar (Classic Mode) ---------- */
.class-bar {
  position: sticky; top: 0; z-index: 20;
  display: flex; flex-wrap: wrap; gap: 10px;
  margin: 6px 0 16px; padding: 8px 6px;
  background: rgba(244,230,198,.92);
  border: 1px solid #c3a779; border-radius: 8px;
  backdrop-filter: saturate(160%) blur(2px);
}
/* Class icons (circle buttons) */
.class-token {
  --ring:#888; --glow:rgba(136,136,136,.55);
  width: 40px; height: 40px; border-radius: 999px;
  background: transparent; cursor: pointer;
  box-shadow: 0 0 0 2px rgba(0,0,0,.45) inset,
              0 0 0 2px rgba(255,255,255,.2);
  transition: transform .12s, box-shadow .12s, filter .12s;
}
.class-token img { width: 100%; height: 100%; border-radius: 999px; }
/* Hover + active effects */
.class-token:hover,
.class-token:focus {
  transform: translateY(-1px);
  filter: brightness(1.05);
  box-shadow: 0 0 0 2px rgba(0,0,0,.6) inset,
              0 0 0 2px var(--ring),
              0 0 16px var(--glow);
}
.class-token.is-active {
  box-shadow: 0 0 0 2px rgba(0,0,0,.7) inset,
              0 0 0 2px var(--ring),
              0 0 18px var(--glow);
}

/* Class-specific colors */
.is-warrior{--ring:#C79C6E;--glow:rgba(199,156,110,.6)}
.is-paladin{--ring:#F58CBA;--glow:rgba(245,140,186,.6)}
.is-hunter{--ring:#ABD473;--glow:rgba(171,212,115,.6)}
.is-rogue {--ring:#FFF569;--glow:rgba(255,245,105,.55)}
.is-priest{--ring:#FFF;    --glow:rgba(255,255,255,.55)}
.is-shaman{--ring:#0070DE; --glow:rgba(0,112,222,.55)}
.is-mage  {--ring:#40C7EB; --glow:rgba(64,199,235,.55)}
.is-warlock{--ring:#8787ED;--glow:rgba(135,135,237,.55)}
.is-druid {--ring:#FF7D0A; --glow:rgba(255,125,10,.55)}
.is-dk    {--ring:#C41F3B; --glow:rgba(196,31,59,.55)}

/* ---------- Tier Group Rows ---------- */
.set-group { margin: 20px 0 10px; }
.set-title { font-size: 20px; font-weight: 700; color: #6b2d1f; margin: 14px 0 6px; }
.set-subtitle { font-weight: 700; color: #7a3f28; }
.set-desc { margin: 2px 0 10px; }
.set-note { color: #7b6a52; display: block; margin-top: 2px; }
.note-inline { display: inline; color: #7b6a52; margin-left: 6px; }

/* ---------- Chips (placeholder slots) ---------- */
.set-items { display: inline; margin-left: 8px; color: #2b2114; }
.set-item {
  display: inline-block; padding: 1px 6px; margin: 0 3px;
  border-radius: 7px; background: rgba(0,0,0,.06);
  box-shadow: 0 1px 0 rgba(255,255,255,.2) inset,
              0 1px 2px rgba(0,0,0,.08);
  font-weight: 700; font-size: 12px; white-space: nowrap;
}
.set-item img {
  width: 14px; height: 14px; margin-right: 4px;
  border-radius: 3px; vertical-align: -2px;
}
.set-item.ghost { background: rgba(0,0,0,.05); color: #7b6a52; opacity: .95; }

/* ---------- Tooltip Frame ---------- */
.talent-tt {
  position: fixed; z-index: 9999;
  min-width: 220px; max-width: 360px; padding: 14px;
  background: rgba(16,24,48,.78);
  border: 1px solid rgba(200,220,255,.18); border-radius: 10px;
  color: #e9eefb; font: 14px/1.45 "Trebuchet MS", Arial, sans-serif;
  pointer-events: none; backdrop-filter: blur(2px);
}
.talent-tt h5 { margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #f1f6ff; }
.talent-tt .tt-subtle { font-size: 13px; opacity: .9; }

/* ---------- Item Icons inside Sets ---------- */
.set-row { display: flex; align-items: center; margin: 6px 0; }
.set-name { flex: 0 0 240px; font-weight: bold; }
.set-icons { display: flex; gap: 6px; margin-left: 10px; }
.set-icons img {
  border-radius: 4px;
  box-shadow: 0 0 0 1px rgba(255,255,255,.2),
              0 1px 2px rgba(0,0,0,.4);
  transition: transform .12s, box-shadow .12s;
}
.set-icons img:hover {
  transform: translateY(-2px) scale(1.08);
  box-shadow: 0 0 0 1px rgba(255,255,255,.35),
              0 2px 6px rgba(0,0,0,.6);
}
</style>

<script>
// ---------- Armor Sets Tooltip Scripts ----------


(function(){
  // Create tooltip div once
  const tip = document.createElement('div');
  tip.className = 'talent-tt';
  tip.style.display = 'none';
  document.body.appendChild(tip);

  let anchor = null;

  // Position tooltip above element
  function place(el){
    const pad = 8;
    const r = el.getBoundingClientRect();
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    const t = tip.getBoundingClientRect();
    let left = Math.max(6, Math.min(r.left + (r.width - t.width)/2, innerWidth - t.width - 6));
    let top  = Math.max(6, r.top - t.height - pad);
    tip.style.left = left+'px';
    tip.style.top  = top+'px';
    tip.style.visibility = 'visible';
  }

  // Show tooltip (decode HTML safely)
  function show(el){
    anchor = el;
    const raw = el.getAttribute('data-tip-html') || '';
    const ta  = document.createElement('textarea');
    ta.innerHTML = raw;
    tip.innerHTML = ta.value;
    place(el);
  }

  // Hide tooltip
  function hide(){ tip.style.display = 'none'; anchor=null; }

  // Re-position on scroll/resize
  function nudge(){ if(anchor && tip.style.display!=='none') place(anchor); }

  // Mouse events for both set tips + item tips
  document.addEventListener('mouseover', e=>{
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if(el) show(el);
  });
  document.addEventListener('mouseout', e=>{
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if(el && !(e.relatedTarget && el.contains(e.relatedTarget))) hide();
  });

  // Keep tooltip stuck to screen when user scrolls or resizes
  addEventListener('scroll', nudge, {passive:true});
  addEventListener('resize', nudge);
})();

</script>
