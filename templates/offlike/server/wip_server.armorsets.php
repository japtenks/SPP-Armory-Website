<?php
/* ---------- DEBUG MODE ---------- */
$DEBUG = isset($_GET['debug']) ? strtolower($_GET['debug']) : '';
if ($DEBUG) {
  echo "<div style='background:black;color:lime;padding:6px;font-weight:bold;'>DEBUG MODE: "
     . htmlspecialchars($DEBUG) . "</div>";
}
?>


<img src="<?php echo $currtmp; ?>/images/armorsets.jpg" />
<br />
<?php builddiv_start(1, $lang['armorsets2']); ?>

<?php
/* =========================
   Armor page (tier/dungeon sets)
   ========================= */

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

/* ---------- helpers ---------- */
if (!function_exists('slot_order')) {
  function slot_order($inv) {
    switch ((int)$inv) {
      case 1:  return 1;  // Head
      case 3:  return 2;  // Shoulder
      case 5:  return 3;  // Chest/Robe
      case 6:  return 4;  // Waist
      case 7:  return 5;  // Legs
      case 8:  return 6;  // Feet
      case 9:  return 7;  // Wrist
      case 10: return 8;  // Hands
      default: return 99;
    }
  }
}


if (!function_exists('icon_url')) {
  function icon_url($iconBase) { return '/armory/images/icons/64x64/'.$iconBase.'.png'; }
}

/* Find an itemset id by name (exact -> loose) */
if (!function_exists('find_itemset_id_by_name')) {
function find_itemset_id_by_name(string $name): int {
    $name = trim($name);
    if ($name === '') return 0;

    // Try exact
    $row = armory_query("SELECT id,name FROM dbc_itemset WHERE name='{$name}' LIMIT 1", 1);
    if ($row) return (int)$row['id'];
    // Try loose: LIKE
    $like = addslashes($name);
    $row = armory_query("SELECT id,name FROM dbc_itemset WHERE name LIKE '{$like} %' LIMIT 1", 1);
    if ($row) return (int)$row['id'];
    // Try contains
    $row = armory_query("SELECT id,name FROM dbc_itemset WHERE name LIKE '%{$like}%' LIMIT 1", 1);
    return $row ? (int)$row['id'] : 0;
}	}

/* Pull items + bonuses for an itemset id */
function get_itemset_data(int $setId): array {
  $row = armory_query("SELECT * FROM dbc_itemset WHERE id={$setId} LIMIT 1", 1);
  if (!$row) return ['id'=>$setId,'name'=>'Unknown Set','items'=>[],'bonuses'=>[]];

  // --- Items ---
  $items = [];
  for ($i = 1; $i <= 10; $i++) {
    $itemId = (int)($row["item_$i"] ?? 0);
    if (!$itemId) continue;

			$sql = "
			  SELECT it.entry, it.name, it.InventoryType,
					 di.name AS icon
			  FROM item_template it
			  LEFT JOIN dbc_itemdisplayinfo di
				ON di.id = it.displayid
			  WHERE it.entry = {$itemId}
			  LIMIT 1
			";

    $item = world_query($sql, 1);
	echo "<pre style='color:cyan'>DEBUG ITEM for entry {$itemId}:\n" . print_r($item, true) . "</pre>";
    if ($item) {
      $items[] = [
        'entry' => $item['entry'],
        'slot'  => (int)$item['InventoryType'],
        'name'  => $item['name'],
        'icon'  => strtolower($item['icon'] ?? 'inv_misc_questionmark'),
      ];
	 // echo "<pre style='color:cyan'>DEBUG ITEM for entry {$items}:\n" . print_r($item, true) . "</pre>";
    }
  }


  // --- Bonuses ---
  $bonuses = [];
  for ($b = 1; $b <= 8; $b++) {
    $bonusId = (int)($row["bonus_$b"] ?? 0);
    $pieces  = (int)($row["pieces_$b"] ?? 0);
    if (!$bonusId || !$pieces) continue;

    $sp = armory_query("SELECT id,name,description,ref_spellicon FROM dbc_spell WHERE id={$bonusId} LIMIT 1", 1);
    if ($sp) {
		$desc = function_exists('replace_spell_tokens')
  ? replace_spell_tokens((string)$sp['description'], $sp)
  : (string)$sp['description'];

      $bonuses[] = [
        'pieces' => $pieces,
        'name'   => (string)($sp['name'] ?? ''),
        'desc'   => $desc !== '' ? $desc : 'No description available.',
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

/* Tooltip HTML for bonuses */
//todo add in tooltip parser
if (!function_exists('render_set_bonus_tip_html')) {
  function render_set_bonus_tip_html(array $setData): string {
    $h  = '<div class="tt-bonuses">';
    $h .= '<h5>'.htmlspecialchars($setData['name'] ?? 'Unknown Set').'</h5>';

    if (!empty($setData['bonuses'])) {
      // Sort bonuses by pieces required (2,4,6,8…)
      usort($setData['bonuses'], function($a,$b){
        return ($a['pieces'] <=> $b['pieces']);
      });

      foreach ($setData['bonuses'] as $b) {
        $pieces = (int)$b['pieces'];
        $desc   = htmlspecialchars($b['desc'] ?? '');
            $h .= '<div class="tt-bonus-row" style="display:flex;gap:8px;align-items:flex-start;margin:6px 0;">'
           .  '<div><div><b>('.$pieces.')</b> '.$desc.'</div>'
           .  '</div></div>';
      }
    } else {
      $h .= '<div class="tt-subtle">No set bonuses found.</div>';
    }

    $h .= '</div>';
    return $h;
  }
}

/* Link to your item page */
if (!function_exists('item_href')) {
  function item_href(int $entry): string { return 'armory/index.php?searchType=iteminfo&item='.$entry; }
}

/* Variants parser (e.g., "Raiment / Regalia (Holy/Shadow)") */
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

/* Placeholder chips when DB is off/empty */
function default_slot_names(int $pieces): array {
  if ($pieces >= 9) return ['H','S','C','W','L','F','W','H','R'];
  if ($pieces >= 8) return ['H','S','C','W','L','F','W','H'];
  return ['H','S','C','L','H'];
}
if (!function_exists('icon_base_from_icon_id')) {
  function icon_base_from_icon_id(int $iconId): string {
    if ($iconId <= 0) return 'inv_misc_key_02';
    $r = armory_query("SELECT `name` FROM `dbc_spellicon` WHERE `id`={$iconId} LIMIT 1", 1);
    if ($r && !empty($r['name'])) {
      return strtolower(preg_replace('/[^a-z0-9_]/i', '', $r['name']));
    }
    return 'inv_misc_key_01';
  }
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
function build_placeholder_chips1(int $pieces): string {
  $icon  = icon_url('inv_misc_key_04');
  $chips = [];
  foreach (default_slot_names($pieces) as $slot) {
    $chips[] = '<span class="set-item ghost">'
             . '<img src="'.htmlspecialchars($icon).'" alt="" width="14" height="14"> '
             . htmlspecialchars($slot)
             . '</span>';
  }
  return ' <span class="set-items">— '.implode('', $chips).'</span>';
}

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

/* ---------- CLASS SET NAMES (unchanged lists) ---------- */
// ... (keep your $N[...] blocks exactly as you sent)
$N['T0'	 ]=['Warrior'=>"Valor",'Paladin'=>"Lightforge",'Hunter'=>"Beaststalker",'Rogue'=>"Shadowcraft",'Priest'=>"Devout",'Shaman'=>"The Elements",'Mage'=>"Magister's",'Warlock'=>"Dreadmist",'Druid'=>"Wildheart"];
$N['T0_5']=['Warrior'=>"Heroism",'Paladin'=>"Soulforge",'Hunter'=>"Beastmaster",'Rogue'=>"Darkmantle",'Priest'=>"Virtuous",'Shaman'=>"The Five Thunders",'Mage'=>"Sorcerer's",'Warlock'=>"Deathmist",'Druid'=>"Feralheart"];
$N['T2_5']=['Warrior'=>"Conqueror's",'Paladin'=>"Avenger's",'Hunter'=>"Striker's",'Rogue'=>"Deathdealer's",'Priest'=>"Garments of the Oracle",'Shaman'=>"Stormcaller's",'Mage'=>"Enigma",'Warlock'=>"Doomcaller's",'Druid'=>"Genesis"];
$N['T1'	 ]=['Warrior'=>"Battlegear of Might",'Paladin'=>"Lawbringer Armor",'Hunter'=>"Giantstalker Armor",'Rogue'=>"Nightslayer Armor",'Priest'=>"Vestments of Prophecy",'Shaman'=>"The Earthfury",'Mage'=>"Arcanist Regalia",'Warlock'=>"Felheart Raiment",'Druid'=>"Cenarion Raiment"];
$N['T1_5']=['Warrior'=>"Vindicator's Battlegear",'Paladin'=>"Freethinker's Armor",'Hunter'=>"Predator's Armor",'Rogue'=>"Madcap's Outfit",'Priest'=>"Confessor's Raiment",'Shaman'=>"Augur's Regalia",'Mage'=>"Illusionist's Attire",'Warlock'=>"Demoniac's Threads",'Druid'=>"Haruspex's Garb"];
$N['T2'	 ]=['Warrior'=>"Battlegear of Wrath",'Paladin'=>"Judgement Armor",'Hunter'=>"Dragonstalker Armor",'Rogue'=>"Bloodfang Armor",'Priest'=>"Vestments of Transcendence",'Shaman'=>"The Ten Storms",'Mage'=>"Netherwind Regalia",'Warlock'=>"Nemesis Raiment",'Druid'=>"Stormrage Raiment"];
$N['T3'	 ]=['Warrior'=>"Dreadnaught Battlegear",'Paladin'=>"Redemption Armor",'Hunter'=>"Cryptstalker Armor",'Rogue'=>"Bonescythe Armor",'Priest'=>"Vestments of Faith",'Shaman'=>"The Earthshatterer",'Mage'=>"Frostfire Regalia",'Warlock'=>"Plagueheart Raiment",'Druid'=>"Dreamwalker Raiment"];
$N['T225']=['Warrior'=>"Battlegear of Unyielding Strength",'Paladin'=>"Battlegear of Eternal Justice",'Hunter'=>"Trappings of the Unseen Path",'Rogue'=>"Emblems of Veiled Shadows",'Priest'=>"Finery of Infinite Wisdom",'Shaman'=>"Gift of the Gathering Storm",'Mage'=>"Trappings of Vaulted Secrets",'Warlock'=>"Implements of Unspoken Names",'Druid'=>"Symbols of Unending Life"];
$N['T4'	 ]=['Druid'=>"Malorne Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Demon Stalker Armor",'Mage'=>"Aldor Regalia",'Paladin'=>"Justicar Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Incarnate Raiment / Regalia (Holy/Disc / Shadow)",'Rogue'=>"Netherblade",'Shaman'=>"Cyclone Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Voidheart Raiment",'Warrior'=>"Warbringer Battlegear / Armor (Arms/Fury / Protection)"];
$N['T5'	 ]=['Druid'=>"Nordrassil Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Rift Stalker Armor",'Mage'=>"Tirisfal Regalia",'Paladin'=>"Crystalforge Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Avatar Raiment / Regalia (Holy/Disc / Shadow)",'Rogue'=>"Deathmantle",'Shaman'=>"Cataclysm Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Corruptor Raiment",'Warrior'=>"Destroyer Battlegear / Armor (Arms/Fury / Protection)"];
$N['T6'  ]=['Druid'=>"Thunderheart Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Gronnstalker's Armor",'Mage'=>"Tempest Regalia",'Paladin'=>"Lightbringer Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Vestments of Absolution / Absolution Regalia (Holy/Disc / Shadow)",'Rogue'=>"Slayer's Armor",'Shaman'=>"Skyshatter Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Malefic Raiment",'Warrior'=>"Onslaught Battlegear / Armor (Arms/Fury / Protection)"];
$N['T7'	 ]=['Death Knight'=>"Scourgeborne Battlegear / Scourgeborne Plate (DPS / Tank)",'Druid'=>"Dreamwalker Regalia / Dreamwalker Battlegear / Dreamwalker Garb (Balance / Feral / Restoration)",'Hunter'=>"Cryptstalker Battlegear",'Mage'=>"Frostfire Regalia",'Paladin'=>"Redemption Regalia / Redemption Armor / Redemption Battlegear (Holy / Protection / Retribution)",'Priest'=>"Regalia of Faith / Garb of Faith (Shadow / Holy–Discipline)",'Rogue'=>"Bonescythe Battlegear",'Shaman'=>"Earthshatter Regalia / Earthshatter Battlegear / Earthshatter Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Plagueheart Garb",'Warrior'=>"Dreadnaught Battlegear / Dreadnaught Plate (Arms/Fury / Protection)"];
$N['T8'	 ]=['Death Knight'=>"Darkruned Battlegear / Darkruned Plate (DPS / Tank)",'Druid'=>"Nightsong Regalia / Nightsong Battlegear / Nightsong Garb (Balance / Feral / Restoration)",'Hunter'=>"Scourgestalker Battlegear",'Mage'=>"Kirin Tor Garb",'Paladin'=>"Aegis Regalia / Aegis Armor / Aegis Battlegear (Holy / Protection / Retribution)",'Priest'=>"Sanctification Regalia / Sanctification Garb (Shadow / Holy–Discipline)",'Rogue'=>"Terrorblade Battlegear",'Shaman'=>"Worldbreaker Regalia / Worldbreaker Battlegear / Worldbreaker Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Deathbringer Garb",'Warrior'=>"Siegebreaker Battlegear / Siegebreaker Plate (Arms/Fury / Protection)"];
$N['T9'	 ]=['Death Knight'=>"Thassarian's / Koltira's Battlegear / Plate (A / H)",'Druid'=>"Malfurion's / Runetotem's Regalia / Battlegear / Garb (A / H)",'Hunter'=>"Windrunner's Battlegear (Alliance / Horde)",'Mage'=>"Khadgar's / Sunstrider's Regalia (A / H)",'Paladin'=>"Turalyon's / Liadrin's Regalia / Armor / Battlegear (A / H)",'Priest'=>"Velen's / Zabra's Regalia / Garb (A / H)",'Rogue'=>"VanCleef's / Garona's Battlegear (A / H)",'Shaman'=>"Nobundo's / Thrall's Regalia / Battlegear / Garb (A / H)",'Warlock'=>"Kel'Thuzad's / Gul'dan's Regalia (A / H)",'Warrior'=>"Wrynn's / Hellscream's Battlegear / Plate (A / H)"];
$N['T10' ]=['Death Knight'=>"Scourgelord's Battlegear / Scourgelord's Plate (DPS / Tank)",'Druid'=>"Lasherweave Regalia / Lasherweave Battlegear / Lasherweave Garb (Balance / Feral / Restoration)",'Hunter'=>"Ahn'Kahar Blood Hunter's Battlegear",'Mage'=>"Bloodmage's Regalia",'Paladin'=>"Lightsworn Regalia / Lightsworn Armor / Lightsworn Battlegear (Holy / Protection / Retribution)",'Priest'=>"Crimson Acolyte's Regalia / Crimson Acolyte's Garb (Shadow / Holy–Discipline)",'Rogue'=>"Shadowblade's Battlegear",'Shaman'=>"Frost Witch's Regalia / Frost Witch's Battlegear / Frost Witch's Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Dark Coven's Regalia",'Warrior'=>"Ymirjar Lord's Battlegear / Ymirjar Lord's Plate (Arms/Fury / Protection)"];

/* ---------- blurbs ---------- */
$BLURB = [
  'T0'=>['title'=>"Tier 0 (Dungeon Set 1)",'pieces'=>8,'text'=>"The first full class sets—forged from Stratholme, Scholomance, and Blackrock Spire. Your entry ticket to endgame. <span class='set-note'>(Patch 1.05)</span>"],
  'T0_5'=>['title'=>"Tier 0.5 (Dungeon Set 2)",'pieces'=>8,'text'=>"An epic upgrade questline that reforges T0; seal demons, summon Lord Valthalak, and claim stronger gear. <span class='set-note'>(Patch 1.10 “Storms of Azeroth”)</span>"],
  'T1'=>['title'=>"Tier 1",'pieces'=>8,'text'=>"Molten Core’s lava-forged epics—Garr, Golemagg, and Rag himself hand you your first true raid set. <span class='set-note'>(Classic launch, Phase 1)</span>"],
  'T1_5'=>['title'=>"Tier 1.5 (Zul’Gurub)",'pieces'=>5,'text'=>"Zandalar’s 20-man sets with 2/3/5-piece bonuses—jungle-themed power from Hakkar’s empire. <span class='set-note'>(Patch 1.7 “Rise of the Blood God”)</span>"],
  'T2'=>['title'=>"Tier 2",'pieces'=>8,'text'=>"Blackwing Lair tokens with helm from Onyxia and legs from Ragnaros—BWL crowns the Blackrock campaign. <span class='set-note'>(BWL Patch 1.6; Ony/MC Phase 1)</span>"],
  'T225'=>['title'=>"Tier 2.25 (Ahn’Qiraj 20)",'pieces'=>5,'text'=>"Cenarion Circle quest/token sets from Ruins of Ahn’Qiraj—class-themed 5-pieces earned via CC rep and raid drops. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T2_5'=>['title'=>"Tier 2.5 (Ahn’Qiraj 40)",'pieces'=>5,'text'=>"Qiraji tokens and Old-God motifs—a spec-leaning set from C’Thun’s citadel. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T3'=>['title'=>"Tier 3 (Naxxramas 40)",'pieces'=>9,'text'=>"Kel’Thuzad’s necropolis: plague-etched armor plus a signature ring—Classic’s final test. <span class='set-note'>(Patch 1.11 “Shadow of the Necropolis” / Phase 6)</span>"],
  'T4'=>['title'=>"Tier 4",'pieces'=>5,'text'=>"Karazhan, Gruul, and Magtheridon drop Champion/Hero/Defender tokens; redeem them in Shattrath. The first spec-split tier. <span class='set-note'>(TBC launch, Patches 2.0–2.0.3)</span>"],
  'T5'=>['title'=>"Tier 5",'pieces'=>5,'text'=>"Serpentshrine Cavern & The Eye—Lady Vashj and Kael’thas guard the upgrades (and your key to T6 attunements). <span class='set-note'>(Available early; major retune in Patch 2.1)</span>"],
  'T6'=>['title'=>"Tier 6",'pieces'=>8,'text'=>"Battle through Hyjal Summit and Black Temple for the core set; Sunwell adds belts/boots/bracers. Archimonde and Illidan await. <span class='set-note'>(Patch 2.1; 2.4 “Fury of the Sunwell” additions)</span>"],
  'T7'=>['title'=>"Tier 7",'pieces'=>5,'text'=>"Naxxramas (revisited), Obsidian Sanctum, and Vault of Archavon—two tracks (Heroes’/Valorous). <span class='set-note'>(Wrath launch, Patch 3.0)</span>"],
  'T8'=>['title'=>"Tier 8",'pieces'=>5,'text'=>"Ulduar—Titan vaults with in-fight hard modes; Valorous/Conqueror’s variants. <span class='set-note'>(Patch 3.1 “Secrets of Ulduar”)</span>"],
  'T9'=>['title'=>"Tier 9",'pieces'=>5,'text'=>"Trial of the Crusader/Grand Crusader—faction-themed sets with trophy upgrades across normal/heroic. <span class='set-note'>(Patch 3.2 “Call of the Crusade”)</span>"],
  'T10'=>['title'=>"Tier 10",'pieces'=>5,'text'=>"Icecrown Citadel—buy the base set with Emblems of Frost, then upgrade to (Heroic) Sanctified via Marks of Sanctification. <span class='set-note'>(Patch 3.3 “Fall of the Lich King”)</span>"],
];

/* ---------- ORDER ---------- */
$order = ['T0','T0_5','T1','T1_5','T2','T225','T2_5','T3'];
if ($maxTier >= 6)  { $order = array_merge($order, ['T4','T5','T6']); }
if ($maxTier >= 10) { $order = array_merge($order, ['T7','T8','T9','T10']); }

/* ---------- class bar ---------- */
/* Canonicalize incoming ?class= to exact key used in $N[...] */
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






/* ---------- Render set rows ---------- */
foreach ($order as $key) {
  if (!isset($BLURB[$key])) continue;
  if (preg_match('/^T(\d+)/',$key,$m) && (int)$m[1] > $maxTier) continue;

  $title   = $BLURB[$key]['title'];
  $pieces  = (int)$BLURB[$key]['pieces'];
  $text    = $BLURB[$key]['text'];
  $pairs   = (!empty($N[$key][$selectedClass])) ? armor_set_variants($N[$key][$selectedClass]) : [];
  $tierLbl = trim(preg_replace('/\s*\(.*\)$/','',$title));

  echo '<div class="set-group">';
  echo '<div class="set-title">'.htmlspecialchars($title).'</div>';

  // Multiple variants (e.g. Regalia / Harness / Raiment)
  if ($pairs) {
    foreach ($pairs as $p) {
      $nm    = trim($p['name']);
      $setId = find_itemset_id_by_name($nm);
      $tipHtml   = '';
      $itemsHtml = '';

      if ($setId) {
        $data    = get_itemset_data($setId);
        $tipHtml = render_set_bonus_tip_html($data);

        if (!empty($data['items'])) {
          $icons = [];
          foreach ($data['items'] as $it) {
	
            $icons[] =
              '<a href="' . htmlspecialchars(item_href((int)$it['entry'])) . '" '
            . 'class="js-item-tip" data-item-id="' . (int)$it['entry'] . '">'
            . '<img src="/armory/images/icons/64x64/' . htmlspecialchars($it['icon']) . '.png" '
            . 'alt="' . htmlspecialchars($it['name']) . '" width="32" height="32">'
            . '</a>';
          }
          $itemsHtml = '<span class="set-icons">' . implode('', $icons) . '</span>';
        }
      }

      if ($itemsHtml === '' && $pieces > 0) {
        $itemsHtml = build_placeholder_chips($pieces);
      }

      echo '<div class="set-row">'
         .   '<b class="js-set-tip" data-tip-html="' . htmlspecialchars($tipHtml, ENT_QUOTES) . '">'
         .     htmlspecialchars($nm)
         .   '</b>'
         .   $itemsHtml
         . '</div>';
    }
  }
  // Single variant (normal case)
  else {
    $nmRaw = $N[$key][$selectedClass] ?? '';
    $nm    = htmlspecialchars($nmRaw);
    $tipHtml   = '';
    $itemsHtml = '';

    if ($nmRaw !== '') {
      $setId = find_itemset_id_by_name($nmRaw);
      if ($setId) {
        $data    = get_itemset_data($setId);
        $tipHtml = render_set_bonus_tip_html($data);

        if (!empty($data['items'])) {
          $icons = [];
          foreach ($data['items'] as $it) {
            $icons[] =
              '<a href="' . htmlspecialchars(item_href((int)$it['entry'])) . '" '
            . 'class="js-item-tip" data-item-id="' . (int)$it['entry'] . '">'
            . '<img src="/armory/images/icons/64x64/' . htmlspecialchars($it['icon']) . '.png" '
            . 'alt="' . htmlspecialchars($it['name']) . '" width="32" height="32">'
            . '</a>';
          }
          $itemsHtml = '<span class="set-icons">' . implode('', $icons) . '</span>';
        }
      }
    }

    if ($itemsHtml === '' && $pieces > 0) {
      $itemsHtml = build_placeholder_chips($pieces);
    }

    echo '<div class="set-row">'
       .   '<b class="js-set-tip" data-tip-html="' . htmlspecialchars($tipHtml, ENT_QUOTES) . '">'
       .     $nm
       .   '</b>'
       .   $itemsHtml
       . '</div>';
  }

  echo '<div class="set-desc">'.$text.'</div>';
  echo '</div>';
}












echo '</div>'; // .set-group
if ($MODE_FLOATING) echo '</div>'; // close .mode-floating wrapper
builddiv_end();
?>

<style>
  #cnt{width:850px;margin-left:0}
  /* Top class selector (classic) */
  .class-bar{position:sticky;top:0;z-index:20;display:flex;flex-wrap:wrap;gap:10px;margin:6px 0 16px;justify-content:flex-start;padding:8px 6px;background:rgba(244,230,198,.92);border:1px solid #c3a779;border-radius:8px;backdrop-filter:saturate(160%) blur(2px)}
  .class-token{--ring:#888;--glow:rgba(136,136,136,.55);width:40px;height:40px;border-radius:999px;border:0;background:transparent;cursor:pointer;box-shadow:0 0 0 2px rgba(0,0,0,.45) inset,0 0 0 2px rgba(255,255,255,.2);transition:transform .12s ease,box-shadow .12s ease,filter .12s ease}
  .class-token img{width:100%;height:100%;border-radius:999px;display:block;object-fit:cover}
  .class-token:hover,.class-token:focus{transform:translateY(-1px);filter:brightness(1.05);box-shadow:0 0 0 2px rgba(0,0,0,.6) inset,0 0 0 2px var(--ring),0 0 16px var(--glow)}
  .class-token.is-active{box-shadow:0 0 0 2px rgba(0,0,0,.7) inset,0 0 0 2px var(--ring),0 0 18px var(--glow)}
  .is-warrior{--ring:#C79C6E;--glow:rgba(199,156,110,.6)} .is-paladin{--ring:#F58CBA;--glow:rgba(245,140,186,.6)}
  .is-hunter{--ring:#ABD473;--glow:rgba(171,212,115,.6)} .is-rogue{--ring:#FFF569;--glow:rgba(255,245,105,.55)}
  .is-priest{--ring:#FFF;--glow:rgba(255,255,255,.55)} .is-shaman{--ring:#0070DE;--glow:rgba(0,112,222,.55)}
  .is-mage{--ring:#40C7EB;--glow:rgba(64,199,235,.55)} .is-warlock{--ring:#8787ED;--glow:rgba(135,135,237,.55)}
  .is-druid{--ring:#FF7D0A;--glow:rgba(255,125,10,.55)} .is-dk{--ring:#C41F3B;--glow:rgba(196,31,59,.55)}

  /* Tier rows */
  .set-group{margin:20px 0 10px}
  .set-title{font-size:20px;font-weight:700;color:#6b2d1f;margin:14px 0 6px}
  .set-subtitle{font-weight:700;color:#7a3f28}
  .set-desc{margin:2px 0 10px}
  .set-note{color:#7b6a52;display:block;margin-top:2px}
  .note-inline{display:inline;color:#7b6a52;margin-left:6px}

  .variant-tight{margin:4px 0 10px 14px;padding:0;list-style:none}
  .variant-tight .v-row{display:grid;grid-template-columns:50px auto max-content;column-gap:8px;align-items:baseline;margin:2px 0}
  .variant-tight .v-prefix{font-weight:700;color:#6b2d1f;white-space:nowrap}
  .variant-tight .v-name b{color:#6b2d1f}
  .variant-tight .v-spec{white-space:nowrap;color:#3a2a12}

  /* Chips */
  .set-items{display:inline;margin-left:8px;color:#2b2114}
  .set-item{display:inline-block;padding:1px 6px;margin:0 3px;border-radius:7px;background:rgba(0,0,0,.06);box-shadow:0 1px 0 rgba(255,255,255,.2) inset, 0 1px 2px rgba(0,0,0,.08);font-weight:700;font-size:12px;white-space:nowrap}
  .set-item a{color:#2b2114;text-decoration:none}
  .set-item a:hover{text-decoration:underline}
  .set-item img{width:14px;height:14px;vertical-align:-2px;margin-right:4px;border-radius:3px;box-shadow:0 0 0 1px rgba(255,255,255,.22)}
  .set-item.ghost{background:rgba(0,0,0,.05);color:#7b6a52;font-weight:700;opacity:.95}

  /* Tooltip frame */
  .talent-tt{position:fixed;z-index:9999;min-width:220px;max-width:360px;padding:14px;background:rgba(16,24,48,.78);border:1px solid rgba(200,220,255,.18);border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.04);color:#e9eefb;font:14px/1.45 "Trebuchet MS", Arial, sans-serif;pointer-events:none;backdrop-filter:blur(2px)}
  .talent-tt h5{margin:0 0 6px;font-size:18px;font-weight:800;color:#f1f6ff}
  .talent-tt .tt-subtle{font-size:13px;opacity:.9}

  /* Hover label for class tokens (classic) */
  .class-token::before{
    content: attr(data-name);
    position:absolute; top:100%; left:100%;
    --tip-gap:6px;
    transform: translate(var(--tip-gap), var(--tip-gap)) scale(.98);
    padding:4px 8px; white-space:nowrap; font-weight:700; font-size:12px;
    color: var(--ring); background: rgba(30,26,20,.96);
    border: 1px solid currentColor; border-radius:6px; text-shadow:0 1px 0 #000;
    box-shadow:0 6px 16px rgba(0,0,0,.35);
    opacity:0; pointer-events:none; z-index:50; transition:.15s ease;
  }
  .class-token:hover::before,.class-token:focus::before{
    opacity:1; transform: translate(var(--tip-gap), var(--tip-gap)) scale(1);
  }

  /* ========== Floating tokens mode (scoped) ========== */
  .mode-floating .class-bar{
    position: sticky; top: 6px; z-index: 40;
    display: flex; flex-wrap: wrap; gap: 12px;
    margin: 6px 0 18px; padding: 0;
    background: transparent; border: 0; border-radius: 0;
    backdrop-filter: none;
  }
  .mode-floating .class-token{
    width:44px; height:44px; border:0; border-radius:999px; background:transparent;
    cursor:pointer; position:relative; display:block;
    box-shadow: 0 0 0 2px rgba(0,0,0,.45) inset, 0 0 0 2px rgba(255,255,255,.25), 0 8px 16px rgba(0,0,0,.18);
    transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
  }
  .mode-floating .class-token::after{
    content:""; position:absolute; left:10%; right:10%; bottom:-6px; height:10px;
    background: radial-gradient(ellipse at center, rgba(0,0,0,.25), rgba(0,0,0,0) 70%);
    filter: blur(2px); opacity:.6; pointer-events:none;
    transition: opacity .15s ease, transform .15s ease;
  }
  .mode-floating .class-token:hover,
  .mode-floating .class-token:focus{
    transform: translateY(-4px); filter: brightness(1.05);
    box-shadow: 0 0 0 2px rgba(0,0,0,.6) inset, 0 0 0 2px var(--ring), 0 0 18px var(--glow), 0 14px 24px rgba(0,0,0,.22);
  }
  .mode-floating .class-token:hover::after,
  .mode-floating .class-token:focus::after{ opacity:.9; transform: translateY(2px); }
  .mode-floating .class-token.is-active{
    box-shadow: 0 0 0 2px rgba(0,0,0,.7) inset, 0 0 0 2px var(--ring), 0 0 20px var(--glow), 0 12px 22px rgba(0,0,0,.22);
  }
  .mode-floating .class-token::before{
    content: attr(data-name);
    position: absolute; top: 100%; left: 100%;
    --tip-gap: 6px;
    transform: translate(var(--tip-gap), var(--tip-gap)) scale(.98);
    padding: 4px 8px; white-space: nowrap; font-weight: 700; font-size: 12px;
    color: var(--ring); background: rgba(30,26,20,.96);
    border: 1px solid currentColor; border-radius: 6px; text-shadow: 0 1px 0 #000;
    box-shadow: 0 6px 16px rgba(0,0,0,.35); opacity: 0; pointer-events: none; z-index: 50;
    transition: opacity .15s ease, transform .15s ease;
  }
  .mode-floating .class-token:hover::before,
  .mode-floating .class-token:focus::before{ opacity:1; transform: translate(var(--tip-gap), var(--tip-gap)) scale(1); }

.set-icons {
  display: inline-flex;
  gap: 6px;
  margin-left: 10px;
  vertical-align: middle;
}
.set-icons img {
  border-radius: 4px;
  box-shadow: 0 0 0 1px rgba(255,255,255,.2), 0 1px 2px rgba(0,0,0,.4);
  transition: transform .12s ease, box-shadow .12s ease;
}
.set-icons img:hover {
  transform: translateY(-2px) scale(1.08);
  box-shadow: 0 0 0 1px rgba(255,255,255,.35), 0 2px 6px rgba(0,0,0,.6);
}

</style>

<script>
(function(){
  // single tooltip node for the whole page
  const tip = document.createElement('div');
  tip.className = 'talent-tt';
  tip.style.display = 'none';
  document.body.appendChild(tip);

  let anchor = null;

  function place(el){
    const pad = 8;
    const r = el.getBoundingClientRect();
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    const t = tip.getBoundingClientRect();
    let left = Math.round(r.left + (r.width - t.width)/2);
    let top  = Math.round(r.top - t.height - pad);
    left = Math.max(6, Math.min(left, innerWidth - t.width - 6));
    top  = Math.max(6, top);
    tip.style.left = left + 'px';
    tip.style.top  = top + 'px';
    tip.style.visibility = 'visible';
  }

  function show(el){
    anchor = el;
    const raw = el.getAttribute('data-tip-html') || '';
    const ta  = document.createElement('textarea');
    ta.innerHTML = raw;         // decode &lt; &gt; &amp; etc.
    tip.innerHTML = ta.value;   // now real HTML
    place(el);
  }
  function hide(){ tip.style.display = 'none'; anchor = null; }
  function nudge(){ if(anchor && tip.style.display !== 'none') place(anchor); }

  document.addEventListener('mouseover', e=>{
    const el = e.target.closest('.js-set-tip');
    if (!el) return;
    show(el);
  });
  document.addEventListener('mouseout', e=>{
    const el = e.target.closest('.js-set-tip');
    if (!el) return;
    if (e.relatedTarget && el.contains(e.relatedTarget)) return;
    hide();
  });
  addEventListener('scroll', nudge, {passive:true});
  addEventListener('resize', nudge);
})();
</script>
