<?php
require_once(dirname(__FILE__, 4).'/core/xfer/bootstrap.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/page_header.php');
require_once(dirname(__FILE__, 4).'/core/xfer/helpers.php');

?>



<?php


$selectedClass = isset($_GET['class']) ? trim($_GET['class']) : '';
$iconBase   = '/templates/offlike/images/icons/';
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
//cloth
$N['WD_Necropile'] = [
	'Mage'=>"Necropile Raiment",
	'Priest'=>"Necropile Raiment",
	'Warlock'=>"Necropile Raiment"];
$N['WD_Postmaster'] = [
	'Mage'=>"The Postmaster",
	'Priest'=>"The Postmaster",
	'Warlock'=>"The Postmaster"];
$N['WD_UndeadCleansing'] = [
	'Mage'=>"Regalia of Undead Cleansing",
	'Priest'=>"Regalia of Undead Cleansing",
	'Warlock'=>"Regalia of Undead Cleansing"];
	//Leather
$N['WD_Defias'] = [
	'Rogue'=>"Defias Leather",
	'Druid'=>"Defias Leather"];
$N['WD_Viper'] = [
	'Rogue'=>"Embrace of the Viper",
	'Druid'=>"Embrace of the Viper"
	];
$N['WD_Cadaverous'] = [
	'Rogue'=>"Cadaverous Garb",
	'Druid'=>"Cadaverous Garb"];
	//mail
		$N['WD_Scarlet'] = [
	'Warrior'=>"Chain of the Scarlet Crusade",
	'Paladin'=>"Chain of the Scarlet Crusade",	
	'Hunter'=>"Chain of the Scarlet Crusade",
	'Shaman'=>"Chain of the Scarlet Crusade"];
	$N['WD_MailUndead'] = [
	'Hunter'=>"Garb of the Undead Slayer",
	'Shaman'=>"Garb of the Undead Slayer"];
	
	
	//plate
$N['WD_Deathbone'] = [
	//'Warrior'=>"Deathbone Guardian",
	'Paladin'=>"Deathbone Guardian"];
$N['WD_PlateUndead'] = [
	'Warrior'=>"Battlegear of Undead Slaying",
	'Paladin'=>"Battlegear of Undead Slaying"];


// Weapons & Trinket combos — split by set
$N['WD_DalRend'] = [
  'Warrior' => "Dal'Rend's Arms",
  'Rogue'   => "Dal'Rend's Arms",
  'Death Knight' => "Dal'Rend's Arms", // WotLK
];

$N['WD_SpiderKiss'] = [
  'Warrior' => "Spider's Kiss",
  'Rogue'   => "Spider's Kiss",
  'Druid'   => "Spider's Kiss",
];

$N['WD_Shard'] = [
  'Mage'    => "Shard of the Gods",
  'Warlock' => "Shard of the Gods",
  'Priest'  => "Shard of the Gods",
  'Paladin' => "Shard of the Gods",
  'Shaman'  => "Shard of the Gods",
  'Druid'   => "Shard of the Gods",
];

$N['WD_Hakkari'] = [
  'Warrior' => "The Twin Blades of Hakkari",
  'Rogue'   => "The Twin Blades of Hakkari",
  'Hunter'  => "The Twin Blades of Hakkari",
  'Shaman'  => "The Twin Blades of Hakkari",
  'Death Knight' => "The Twin Blades of Hakkari",
];

$N['WD_Primal'] = [
  'Warrior' => "Primal Blessing",
  'Rogue'   => "Primal Blessing",
  'Hunter'  => "Primal Blessing",
  'Shaman'  => "Primal Blessing",
  'Druid'   => "Primal Blessing",
  'Death Knight' => "Primal Blessing",
];

$N['WD_Zanzil'] = [
  'Mage'    => "Zanzil's Concentration",
  'Warlock' => "Zanzil's Concentration",
  'Priest'  => "Zanzil's Concentration",
  'Shaman'  => "Zanzil's Concentration",
  'Druid'   => "Zanzil's Concentration",
  'Death Knight' => "Zanzil's Concentration",
];

$N['WD_Overlord'] = [
  'Warrior' => "Overlord's Resolution",
  'Paladin' => "Overlord's Resolution",
  'Druid'   => "Overlord's Resolution",
  'Death Knight' => "Overlord's Resolution",
];

$N['WD_Prayer'] = [
  'Priest'  => "Prayer of the Primal",
  'Paladin' => "Prayer of the Primal",
  'Shaman'  => "Prayer of the Primal",
  'Druid'   => "Prayer of the Primal",
];

$N['WD_Mojo'] = [
  'Warrior' => "Major Mojo Infusion",
  'Rogue'   => "Major Mojo Infusion",
  'Hunter'  => "Major Mojo Infusion",
  'Shaman'  => "Major Mojo Infusion",
  'Druid'   => "Major Mojo Infusion",
  'Paladin' => "Major Mojo Infusion",
  'Death Knight' => "Major Mojo Infusion",
];
$N['WD_Eskhandar'] = [
  'Warrior' => "Spirit of Eskhandar",
  'Rogue'   => "Spirit of Eskhandar",
  'Hunter'  => "Spirit of Eskhandar",
  'Shaman'  => "Spirit of Eskhandar",
  'Druid'   => "Spirit of Eskhandar", // some Ferals used it too
];



if ($expansion >= 2) {
  $N['WD_Weapons']['Death Knight'] = "Dal'Rend's Arms / The Twin Blades of Hakkari / Primal Blessing / Zanzil's Concentration / Overlord's Resolution / Major Mojo Infusion";
}



/* ---------- blurbs ---------- */
/*-----------cloth-------------*/

$BLURB = [
  'WD_Necropile'=>[
    'title'=>"Necropile Raiment",'pieces'=>5,
    'text'=>"A shadow-themed Scholomance set for <b>Mages</b>, <b>Warlocks</b>, and <b>Priests</b>. 
             Popular for its spell damage bonuses and dark aesthetic."
  ],
  'WD_Postmaster'=>[
    'title'=>"The Postmaster",'pieces'=>5,
    'text'=>"A quirky set from Stratholme mail couriers. Provided movement speed and bag space bonuses. 
             Favored by <b>Mages</b> and <b>Priests</b> farming dungeons."
  ],
  'WD_UndeadCleansing'=>[
    'title'=>"Regalia of Undead Cleansing",'pieces'=>4,
    'text'=>"Scourge Invasion event set tailored against undead foes. 
             Used by <b>Mages</b>, <b>Warlocks</b>, and <b>Priests</b>."
  ],
  //-------------------leather
  'WD_Defias'=>[
    'title'=>"Defias Leather",'pieces'=>5,
    'text'=>"The classic Deadmines rogue look. Light stats but iconic appearance."
  ],
    'WD_Viper'=>[
    'title'=>"Embrace of the Viper",'pieces'=>5,
    'text'=>"Wailing Caverns low-level set. A stepping stone for <b>Druids</b> and <b>Rogues</b>, 
             notable as one of WoW’s first ‘mini-sets’."
			 ],
  'WD_Cadaverous'=>[
    'title'=>"Cadaverous Garb",'pieces'=>5,
    'text'=>"Scholomance leather set for <b>Rogues</b> and <b>Druids</b>. 
             Boosted agility and stamina, making it an early dungeon BiS."
  ],
    'WD_LeatherUndead'=>[
    'title'=>"Undead Slayer’s Armor",'pieces'=>4,
    'text'=>"Leather variant of the Scourge Invasion sets. Boosted damage vs undead. 
             Usable by <b>Rogues</b> and <b>Druids</b>."
  ],
  //----------Mail
    'WD_Scarlet'=>[
    'title'=>"Chain of the Scarlet Crusade",'pieces'=>6,
    'text'=>"Dropped in Scarlet Monastery. A stylish thematic mail set worn by 
             <b>Warriors</b>, <b>Paladins</b>, <b>Hunters</b>, and <b>Shamans</b>."
  ],
  'WD_MailUndead'=>[
    'title'=>"Garb of the Undead Slayer",'pieces'=>4,
    'text'=>"Mail variant of the Scourge Invasion event sets. Boosted damage against undead, 
             a solid temporary gearing option."
  ],
  //----------plate
  'WD_Deathbone'=>[
    'title'=>"Deathbone Guardian",'pieces'=>5,
    'text'=>"A tank-oriented Scholomance set. Popular among <b>Warriors</b> and <b>Paladins</b>."
  ],
  'WD_PlateUndead'=>[
    'title'=>"Battlegear of Undead Slaying",'pieces'=>4,
    'text'=>"Scourge Invasion plate set with strong bonuses vs undead. Used by <b>Warriors</b> and <b>Paladins</b>."
  ],

  // -------- Split weapon/trinket combos --------
  'WD_DalRend'=>[
    'title'=>"Dal’Rend’s Arms",'pieces'=>2,
    'text'=>"A dual-wield sword set dropped in Upper Blackrock Spire. 
             Widely considered a pre-raid BiS choice for <b>Fury Warriors</b> and <b>Rogues</b> thanks to its 
             high attack power bonus and accessibility before raids."
  ],
  'WD_SpiderKiss'=>[
    'title'=>"Spider’s Kiss",'pieces'=>2,
    'text'=>"A dagger + trinket set from Lower Blackrock Spire. 
             Though niche, it rewarded <b>Rogues</b> and <b>Feral Druids</b> with extra poison synergy and 
             attack power, making it a flavorful choice in PvP."
  ],
  'WD_Shard'=>[
    'title'=>"Shard of the Gods",'pieces'=>2,
    'text'=>"A caster-oriented set of trinkets from Molten Core. Highly prized by 
             <b>Mages</b>, <b>Warlocks</b>, <b>Priests</b>, and other casters for its unique spellpower 
             bonuses. Owning both was a sign of serious raiding commitment."
  ],
  'WD_Hakkari'=>[
    'title'=>"The Twin Blades of Hakkari",'pieces'=>2,
    'text'=>"Iconic weapons from Zul’Gurub, these twin swords symbolized the power of the Gurubashi trolls. 
             Popular with <b>Rogues</b> and <b>Warriors</b>, they offered solid stats and thematic flair."
  ],
  'WD_Primal'=>[
    'title'=>"Primal Blessing",'pieces'=>2,
    'text'=>"A Zul’Gurub set that granted a chance to transform into a tiger on hit, boosting attack power. 
             Loved by <b>Rogues</b>, <b>Hunters</b>, and <b>Feral Druids</b> for its style and strength."
  ],
  'WD_Zanzil'=>[
    'title'=>"Zanzil’s Concentration",'pieces'=>2,
    'text'=>"Another Zul’Gurub set, offering powerful caster and healer trinket bonuses. 
             <b>Mages</b>, <b>Warlocks</b>, <b>Priests</b>, and <b>Shamans</b> often made use of it."
  ],
  'WD_Overlord'=>[
    'title'=>"Overlord’s Resolution",'pieces'=>2,
    'text'=>"A tank-oriented world set, rarely completed, granting bonuses to survivability. 
             Favored by <b>Warriors</b> and <b>Paladins</b> who specialized in mitigation."
  ],
  'WD_Prayer'=>[
    'title'=>"Prayer of the Primal",'pieces'=>2,
    'text'=>"A Zul’Gurub set aimed at healers. Provided mana efficiency and throughput boosts for 
             <b>Priests</b>, <b>Paladins</b>, <b>Shamans</b>, and <b>Druids</b> in early raid healing."
  ],
  'WD_Mojo'=>[
    'title'=>"Major Mojo Infusion",'pieces'=>2,
    'text'=>"A quirky trinket-based set from Zul’Gurub granting spell crit and utility. 
             While not core progression gear, it was a fun and flavorful bonus for both casters and hybrids."
  ],
  'WD_Eskhandar'=>[
  'title'=>"Spirit of Eskhandar",'pieces'=>4,
  'text'=>"A rare four-piece set from Zul’Gurub and world bosses. 
           It combined <b>Eskhandar’s Right Claw</b>, <b>Eskhandar’s Left Claw</b>, 
           <b>Eskhandar’s Collar</b>, and <b>Eskhandar’s Pelt</b>. 
           Favored by <b>Warriors</b>, <b>Rogues</b>, <b>Hunters</b>, and <b>Shamans</b> 
           for its feral-inspired bonuses."
],


];


/* ---------- display order ---------- */
$order = [
  // Cloth
  'WD_Necropile','WD_Postmaster','WD_UndeadCleansing',

  // Leather
  'WD_Defias','WD_Viper','WD_Cadaverous','WD_LeatherUndead',

  // Mail
  'WD_Scarlet','WD_MailUndead',

  // Plate
  'WD_Deathbone','WD_PlateUndead',

  // Weapons/Trinkets
  'WD_SpiderKiss','WD_DalRend','WD_Eskhandar','WD_Shard','WD_Hakkari',
  'WD_Primal','WD_Zanzil','WD_Overlord','WD_Prayer','WD_Mojo'
];


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
// find highest item quality in this set to colorize the name
$qualityColors = [
  0 => '#9d9d9d',  // poor
  1 => '#ffffff',  // common
  2 => '#1eff00',  // uncommon
  3 => '#0070dd',  // rare (blue)
  4 => '#a335ee',  // epic (purple)
  5 => '#ff8000',  // legendary (orange)
  6 => '#e6cc80',  // artifact
  7 => '#e6cc80'
];

$maxQ = 0;
if (!empty($setData['items'])) {
  foreach ($setData['items'] as $it) {
    $q = (int)($it['q'] ?? 0);
    if ($q > $maxQ) $maxQ = $q;
  }
}
$nameColor = $qualityColors[$maxQ] ?? '#ffffff';

echo "<div class='set-name js-set-tip' data-tip-html='{$bonusTip}' style='color:{$nameColor};'>"
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

		$h .= '<div class="tt-bonus-row" style="display:flex;gap:8px;align-items:flex-start;margin:6px 0;">'
			.   '<div>'
			.     '<span style="color:#ffffff">('.$pieces.')</span> '  // white for (N)
			.     '<span style="color:#1eff00">'.$desc.'</span>'       // green for bonus text
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
$playerLevel = ($expansion == 2) ? 80 : (($expansion == 1) ? 70 : 60);

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

<?php builddiv_start(1, $lang['worldsets'],1); ?>
<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/armorsets.jpg" alt="world sets" class="banner"/>
  
<?php
/* ---------- class bar ---------- */
foreach ($classes as $c) {
  if (strcasecmp($selectedClass, $c['name']) === 0) { $selectedClass = $c['name']; break; }
}

/* ---------- Detect current subpage (armorsets, pvpsets, worldsets) ---------- */
$currentSub = isset($_GET['sub']) ? $_GET['sub'] : 'armorsets';

/* ---------- Class bar ---------- */
echo '<div class="class-bar">';

foreach ($classes as $c) {
    $className = $c['name'];  // ensure variable exists
    $slug      = $c['slug'];
    $href = "index.php?n=server&sub={$currentSub}&class={$className}&realm={$realmId}";
    $src  = $iconBase . $iconPref . $slug . $iconExt;
    $active = (strcasecmp($selectedClass, $className) === 0) ? ' is-active' : '';
    echo '<a class="class-token ' . $c['css'] . $active . '" href="' . $href . '"'
       . ' aria-label="' . htmlspecialchars($className) . '"'
       . ' data-name="' . htmlspecialchars($className) . '">'
       . '<img src="' . $src . '" alt="' . htmlspecialchars($className) . '">'
       . '</a>';
}

echo '</div>';

/* If no class, pick one at random */
if ($selectedClass === '') {
  $rand = $classes[array_rand($classes)];
  $selectedClass = $rand['name'];
}


/* ---------- Render set rows ---------- */
echo '<div class="set-group"><div class="set-title">World Drop Sets</div>';

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

  if (empty($pairs)) continue;

  echo "<div class='set-block'>";
/* ---------- render title + description with class color highlights ---------- */
echo "<div class='set-title'>".htmlspecialchars($title)."</div>";


$text = str_replace(['<b>', '</b>'], '', $text);


$classesToCSS = [
  'Warrior'      => 'is-warrior',
  'Paladin'      => 'is-paladin',
  'Hunter'       => 'is-hunter',
  'Rogue'        => 'is-rogue',
  'Priest'       => 'is-priest',
  'Shaman'       => 'is-shaman',
  'Mage'         => 'is-mage',
  'Warlock'      => 'is-warlock',
  'Druid'        => 'is-druid',
  'Death Knight' => 'is-dk'
];

$text = preg_replace_callback(
  '/\b(' . implode('|', array_map('preg_quote', array_keys($classesToCSS))) . ')(s)?\b/i',
  function($m) use ($classesToCSS) {
    $name = ucfirst(strtolower($m[1]));
    $css  = $classesToCSS[$name] ?? '';
    $suffix = $m[2] ?? ''; // capture plural 's'
    return "<span class='{$css}'><b>{$name}{$suffix}</b></span>";
  },
  $text
);

echo "<div class='set-desc'>{$text}</div>";


  foreach ($pairs as $nm) {
    $setName = $nm['name'];
    $setRole = $nm['role'] ?? '';

    if ($DEBUG) {
        echo "<div style='color:cyan'>[PAIR] ".htmlspecialchars(print_r($nm,true))."</div>";
    }

    $setId = find_itemset_id_by_name($setName);
    if ($DEBUG) {
        echo "<div style='color:orange'>[LOOKUP] '$setName' → setId=$setId</div>";
    }

    $items = ($setId) ? get_itemset_data($setId) : [];

    if ($DEBUG) {
        $count = is_array($items) && !empty($items['items']) ? count($items['items']) : 0;
        echo "<div style='color:yellow'>[ITEMS] setId=$setId → {$count} items</div>";
    }

    render_armor_set($setName, $pieces, $items, $setId);
  }

  echo "</div>";
}
?>
</div> <!-- closes .modern-content -->

<?php builddiv_end(); ?>

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
