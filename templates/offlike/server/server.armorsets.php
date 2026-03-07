<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/bootstrap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/page_header.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/helpers.php');

?>

<?php

/* ---------- Max Tier by expansion ---------- */
$maxTier = ($expansion === 2) ? 10 : (($expansion === 1) ? 6 : 3);


/* ---------- CLASS SET NAMES (unchanged lists) ---------- */
// ... (keep your $N[...] blocks exactly as you sent)
$N['T0']=['Warrior'=>"Battlegear of Valor",'Paladin'=>"Lightforge Armor",'Hunter'=>"Beaststalker Armor",'Rogue'=>"Shadowcraft Armor",'Priest'=>"Vestments of the Devout",'Shaman'=>"The Elements",'Mage'=>"Magister's Regalia",'Warlock'=>"Dreadmist Raiment",'Druid'=>"Wildheart Raiment"];
$N['T0_5']=['Warrior'=>"Battlegear of Heroism",'Paladin'=>"Soulforge Armor",'Hunter'=>"Beastmaster Armor",'Rogue'=>"Darkmantle Armor",'Priest'=>"Vestments of the Virtuous",'Shaman'=>"The Five Thunders",'Mage'=>"Sorcerer's Regalia",'Warlock'=>"Deathmist Raiment",'Druid'=>"Feralheart Raiment"];
$N['T1']=['Warrior'=>"Battlegear of Might",'Paladin'=>"Lawbringer Armor",'Hunter'=>"Giantstalker Armor",'Rogue'=>"Nightslayer Armor",'Priest'=>"Vestments of Prophecy",'Shaman'=>"The Earthfury",'Mage'=>"Arcanist Regalia",'Warlock'=>"Felheart Raiment",'Druid'=>"Cenarion Raiment"];
$N['T1_5']=['Warrior'=>"Vindicator's Battlegear",'Paladin'=>"Freethinker's Armor",'Hunter'=>"Predator's Armor",'Rogue'=>"Madcap's Outfit",'Priest'=>"Confessor's Raiment",'Shaman'=>"Augur's Regalia",'Mage'=>"Illusionist's Attire",'Warlock'=>"Demoniac's Threads",'Druid'=>"Haruspex's Garb"];
$N['T2']=['Warrior'=>"Battlegear of Wrath",'Paladin'=>"Judgement Armor",'Hunter'=>"Dragonstalker Armor",'Rogue'=>"Bloodfang Armor",'Priest'=>"Vestments of Transcendence",'Shaman'=>"The Ten Storms",'Mage'=>"Netherwind Regalia",'Warlock'=>"Nemesis Raiment",'Druid'=>"Stormrage Raiment"];
$N['T2_25']=['Warrior'=>"Battlegear of Unyielding Strength",'Paladin'=>"Battlegear of Eternal Justice",'Hunter'=>"Trappings of the Unseen Path",'Rogue'=>"Emblems of Veiled Shadows",'Priest'=>"Finery of Infinite Wisdom",'Shaman'=>"Gift of the Gathering Storm",'Mage'=>"Trappings of Vaulted Secrets",'Warlock'=>"Implements of Unspoken Names",'Druid'=>"Symbols of Unending Life"];
$N['T2_5']=['Warrior'=>"Conqueror's Battlegear",'Paladin'=>"Avenger's Battlegear",'Hunter'=>"Striker's Garb",'Rogue'=>"Deathdealer's Embrace",'Priest'=>"Garments of the Oracle",'Shaman'=>"Stormcaller's Garb",'Mage'=>"Enigma Vestments",'Warlock'=>"Doomcaller's Attire",'Druid'=>"Genesis Raiment"];
$N['T3']=['Warrior'=>"Dreadnaught's Battlegear",'Paladin'=>"Redemption Armor",'Hunter'=>"Cryptstalker Armor",'Rogue'=>"Bonescythe Armor",'Priest'=>"Vestments of Faith",'Shaman'=>"The Earthshatterer",'Mage'=>"Frostfire Regalia",'Warlock'=>"Plagueheart Raiment",'Druid'=>"Dreamwalker Raiment"];
$N['T4']=['Druid'=>"Malorne Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Demon Stalker Armor",'Mage'=>"Aldor Regalia",'Paladin'=>"Justicar Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Incarnate Raiment / Regalia (Holy/Disc / Shadow)",'Rogue'=>"Netherblade",'Shaman'=>"Cyclone Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Voidheart Raiment",'Warrior'=>"Warbringer Battlegear / Armor (Arms/Fury / Protection)"];
$N['T5']=['Druid'=>"Nordrassil Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Rift Stalker Armor",'Mage'=>"Tirisfal Regalia",'Paladin'=>"Crystalforge Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Avatar Raiment / Regalia (Holy/Disc / Shadow)",'Rogue'=>"Deathmantle",'Shaman'=>"Cataclysm Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Corruptor Raiment",'Warrior'=>"Destroyer Battlegear / Armor (Arms/Fury / Protection)"];
$N['T6']=['Druid'=>"Thunderheart Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Gronnstalker's Armor",'Mage'=>"Tempest Regalia",'Paladin'=>"Lightbringer Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Vestments of Absolution / Absolution Regalia (Holy/Disc / Shadow)",'Rogue'=>"Slayer's Armor",'Shaman'=>"Skyshatter Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Malefic Raiment",'Warrior'=>"Onslaught Battlegear / Armor (Arms/Fury / Protection)"];
$N['T7']=['Death Knight'=>"Scourgeborne Battlegear / Scourgeborne Plate (DPS / Tank)",'Druid'=>"Dreamwalker Regalia / Dreamwalker Battlegear / Dreamwalker Garb (Balance / Feral / Restoration)",'Hunter'=>"Cryptstalker Battlegear",'Mage'=>"Frostfire Regalia",'Paladin'=>"Redemption Regalia / Redemption Armor / Redemption Battlegear (Holy / Protection / Retribution)",'Priest'=>"Regalia of Faith / Garb of Faith (Shadow / Holy–Discipline)",'Rogue'=>"Bonescythe Battlegear",'Shaman'=>"Earthshatter Regalia / Earthshatter Battlegear / Earthshatter Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Plagueheart Garb",'Warrior'=>"Dreadnaught Battlegear / Dreadnaught Plate (Arms/Fury / Protection)"];
$N['T8']=['Death Knight'=>"Darkruned Battlegear / Darkruned Plate (DPS / Tank)",'Druid'=>"Nightsong Regalia / Nightsong Battlegear / Nightsong Garb (Balance / Feral / Restoration)",'Hunter'=>"Scourgestalker Battlegear",'Mage'=>"Kirin Tor Garb",'Paladin'=>"Aegis Regalia / Aegis Armor / Aegis Battlegear (Holy / Protection / Retribution)",'Priest'=>"Sanctification Regalia / Sanctification Garb (Shadow / Holy–Discipline)",'Rogue'=>"Terrorblade Battlegear",'Shaman'=>"Worldbreaker Regalia / Worldbreaker Battlegear / Worldbreaker Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Deathbringer Garb",'Warrior'=>"Siegebreaker Battlegear / Siegebreaker Plate (Arms/Fury / Protection)"];
$N['T9']=['Death Knight'=>"Thassarian's / Koltira's Battlegear / Plate (A / H)",'Druid'=>"Malfurion's / Runetotem's Regalia / Battlegear / Garb (A / H)",'Hunter'=>"Windrunner's Battlegear (Alliance / Horde)",'Mage'=>"Khadgar's / Sunstrider's Regalia (A / H)",'Paladin'=>"Turalyon's / Liadrin's Regalia / Armor / Battlegear (A / H)",'Priest'=>"Velen's / Zabra's Regalia / Garb (A / H)",'Rogue'=>"VanCleef's / Garona's Battlegear (A / H)",'Shaman'=>"Nobundo's / Thrall's Regalia / Battlegear / Garb (A / H)",'Warlock'=>"Kel'Thuzad's / Gul'dan's Regalia (A / H)",'Warrior'=>"Wrynn's / Hellscream's Battlegear / Plate (A / H)"];
$N['T10']=['Death Knight'=>"Scourgelord's Battlegear / Scourgelord's Plate (DPS / Tank)",'Druid'=>"Lasherweave Regalia / Lasherweave Battlegear / Lasherweave Garb (Balance / Feral / Restoration)",'Hunter'=>"Ahn'Kahar Blood Hunter's Battlegear",'Mage'=>"Bloodmage's Regalia",'Paladin'=>"Lightsworn Regalia / Lightsworn Armor / Lightsworn Battlegear (Holy / Protection / Retribution)",'Priest'=>"Crimson Acolyte's Regalia / Crimson Acolyte's Garb (Shadow / Holy–Discipline)",'Rogue'=>"Shadowblade's Battlegear",'Shaman'=>"Frost Witch's Regalia / Frost Witch's Battlegear / Frost Witch's Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Dark Coven's Regalia",'Warrior'=>"Ymirjar Lord's Battlegear / Ymirjar Lord's Plate (Arms/Fury / Protection)"];
$N['DS3'] = [
  'Mage'    => "Incanter's Regalia / Mana-Etched Regalia (Caster / DPS)",
  'Priest'  => "Hallowed Raiment /Mana-Etched Regalia",
  'Warlock' => "Oblivion Raiment / Mana-Etched Regalia (Caster / DPS)",

  'Druid'   => "Moonglade Raiment / Wastewalker Armor (DPS)", 
  'Rogue'   => "Assassination Armor / Wastewalker Armor (DPS)",

  'Hunter'  => "Beast Lord Armor / Desolation Battlegear (DPS)",
  'Shaman'  => "Tidefury Raiment (Caster / Healer) / Desolation Battlegear (DPS)",

  'Paladin' => "Righteous Armor / Doomplate Battlegear (Tank/ DPS)",	
  'Warrior' => "Bold Armor / Doomplate Battlegear",
];



/* ---------- blurbs ---------- */
$BLURB = [
  'T0'=>[
    'title'=>"Tier 0 (Dungeon Set 1)",'pieces'=>8,
    'text'=>"Tier 0 marked the dawn of class sets in World of Warcraft. Forged through drops in the most difficult dungeons of Classic—Stratholme, Scholomance, and Blackrock Spire—these blue-quality sets gave players their first taste of coordinated gearing. While they lacked raid-level stats, their 8-piece bonuses hinted at the specialization that future raid sets would embody. Collecting these pieces was the natural bridge from leveling dungeons into endgame raiding, and many raiders proudly wore them into Molten Core. <span class='set-note'>(Patch 1.05)</span>"
  ],
  'T0_5'=>[
    'title'=>"Tier 0.5 (Dungeon Set 2)",'pieces'=>8,
    'text'=>"The upgrade path to Dungeon Set 1, Tier 0.5 introduced one of the most ambitious questlines in Classic WoW. Players reforged their original sets through long chains involving world events, demon hunts, and even the summoning of the dreadlord Lord Valthalak. This was Blizzard’s first experiment with epic quest-driven gear progression, demanding coordination, gold, and patience. The final reward: a visually upgraded purple set with stronger stats and more powerful bonuses, forever remembered as a community-defining grind. <span class='set-note'>(Patch 1.10 “Storms of Azeroth”)</span>"
  ],
  'T1'=>[
    'title'=>"Tier 1",'pieces'=>8,
    'text'=>"Tier 1 came from the fiery heart of Molten Core, WoW’s first true 40-man raid. These lava-forged epics embodied the essence of raiding: helm and shoulders dropping from bosses like Garr and Golemagg, and the final legs claimed from Ragnaros himself. Attunement to Molten Core required braving Blackrock Depths, further cementing Tier 1 as the gateway to endgame raiding. The sets cemented raid identity, with class-defining looks like the Warrior’s Might and the Mage’s Arcanist Regalia. <span class='set-note'>(Classic launch, Phase 1)</span>"
  ],
  'T1_5'=>[
    'title'=>"Tier 1.5",'pieces'=>5,
    'text'=>"Zul’Gurub introduced a 20-man raid experience distinct from Molten Core. Instead of full 8-piece sets, Tier 1.5 delivered 5-piece collections, offering 2, 3, and 5-piece bonuses. These jungle-themed armor sets tied into the Zandalar Troll empire and required reputation with the Zandalar Tribe. They gave mid-tier guilds a unique progression path and provided catch-up gear for raiders not yet ready for Blackwing Lair. <span class='set-note'>(Patch 1.7 “Rise of the Blood God”)</span>"
  ],
  'T2'=>[
    'title'=>"Tier 2",'pieces'=>8,
    'text'=>"Blackwing Lair’s dragonsteel sets defined Classic WoW’s mid-raid progression. The bulk of the pieces came from Nefarian’s minions, while legs dropped from Ragnaros in Molten Core and helms from Onyxia. Completing Tier 2 meant conquering nearly every endgame raid of the Blackrock era, marking it as the most comprehensive raid tier of Classic. Iconic appearances such as the Paladin’s Judgement Armor and the Warlock’s Nemesis Raiment became enduring symbols of WoW raiding culture. <span class='set-note'>(Patch 1.6; Ony/MC Phase 1)</span>"
  ],
  'T2_25'=>[
    'title'=>"Tier 2.25",'pieces'=>3,
    'text'=>"Ruins of Ahn’Qiraj offered a different take: 3-piece class sets earned through reputation with the Cenarion Circle and raid token turn-ins. While not as iconic as Tier 2 or 2.5, these sets filled important gear gaps and allowed raiders to display their dedication to both Cenarion Circle rep and Ahn’Qiraj itself. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj”)</span>"
  ],
  'T2_5'=>[
    'title'=>"Tier 2.5",'pieces'=>5,
    'text'=>"Temple of Ahn’Qiraj raised the bar with Old God corruption shaping each set. These tokens were turned in for spec-leaning armor sets, giving classes gear that reflected their evolving roles. Warriors claimed the Conqueror’s Battlegear, while Druids earned the Genesis Raiment. The designs embodied Qiraji insectoid aesthetics and eldritch power, making them some of the most alien-looking sets in Classic. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj”)</span>"
  ],
  'T3'=>[
    'title'=>"Tier 3",'pieces'=>9,
    'text'=>"Kel’Thuzad’s floating necropolis, Naxxramas, was Classic’s final raid challenge. Tier 3 was its pinnacle: 8 armor slots plus a unique ring, all themed around plague and death. These sets demanded mastery over the hardest raid in the game, one so punishing that very few guilds ever completed it before Burning Crusade. When Naxxramas left with the pre-Wrath patch, Tier 3 became unobtainable—cementing its status as one of the rarest and most prestigious collections in WoW’s history. <span class='set-note'>(Patch 1.11 “Shadow of the Necropolis”)</span>"
  ],
  'DS3'=>[
    'title'=>"Dungeon Set 3",'pieces'=>5,
    'text'=>"Burning Crusade’s Dungeon Set 3 bridged the gap between leveling gear and Tier 4 raids. Unlike earlier dungeon sets, DS3 came in multiple variants per armor type, catering to casters, healers, melee DPS, and tanks. Players farmed heroic dungeons across Outland for these five-piece sets, earning powerful bonuses and distinct looks. Mana-Etched Regalia empowered spellcasters, Beast Lord Armor defined early hunter identity, and Doomplate Battlegear gave tanks their first taste of Outland survivability. DS3 sets were often a player’s first step into Karazhan attunement and the raid circuit of Outland. <span class='set-note'>(TBC launch / Patch 2.0)</span>"
  ],
  'T4'=>[
    'title'=>"Tier 4",'pieces'=>5,
    'text'=>"Tier 4 represented the first raid tier of Burning Crusade, spread across three entry raids: Karazhan, Gruul’s Lair, and Magtheridon’s Lair. Unlike Classic, loot dropped as tokens—Champion, Hero, and Defender—that were redeemed in Shattrath for class sets. These sets split into different versions for each spec (Raiment, Armor, Battlegear, Regalia), marking the beginning of Blizzard’s design philosophy of tailoring raid gear to multiple class roles. <span class='set-note'>(TBC launch)</span>"
  ],
  'T5'=>[
    'title'=>"Tier 5",'pieces'=>5,
    'text'=>"Tier 5 came from Serpentshrine Cavern and The Eye, where Lady Vashj and Kael’thas guarded the attunement keys to Mount Hyjal and Black Temple. These sets marked a player’s entry into serious raiding progression, and clearing both raids was among the most difficult pre-nerf challenges of WoW. The visuals reflected Outland’s arcane and aquatic motifs, with standout appearances like the Mage’s Tirisfal Regalia and the Priest’s Avatar Raiment. <span class='set-note'>(Available early; retuned in Patch 2.1)</span>"
  ],
  'T6'=>[
    'title'=>"Tier 6",'pieces'=>8,
    'text'=>"Tier 6 embodied the apex of Burning Crusade raiding. Sets came from Mount Hyjal and Black Temple, with Archimonde and Illidan Stormrage as the ultimate gatekeepers. Later, Sunwell Plateau expanded the tier with additional off-slot pieces—belts, bracers, and boots—that pushed players’ power further. Iconic looks like the Warrior’s Onslaught Battlegear and Warlock’s Malefic Raiment became synonymous with high-end raiding prestige. <span class='set-note'>(Patch 2.1–2.4)</span>"
  ],
  'T7'=>[
    'title'=>"Tier 7",'pieces'=>5,
    'text'=>"With Wrath of the Lich King, Naxxramas returned as the launch raid, alongside Obsidian Sanctum and Vault of Archavon. Tier 7 was split into Heroes’ and Valorous versions, accessible through both 10- and 25-man raiding. While less prestigious than the original Tier 3, this tier allowed more players than ever to experience Naxxramas’ design and collect its iconic visuals, repurposed for a new age. <span class='set-note'>(Wrath launch, Patch 3.0)</span>"
  ],
  'T8'=>[
    'title'=>"Tier 8",'pieces'=>5,
    'text'=>"Ulduar delivered one of WoW’s most beloved raids, and its Tier 8 sets matched its grandeur. These sets were themed around Titan constructs and arcane technology, embodying the aesthetics of the Keepers and Yogg-Saron’s creeping corruption. Hard mode bosses dropped upgraded Conqueror’s pieces, pioneering modern raid difficulty splits. <span class='set-note'>(Patch 3.1 “Secrets of Ulduar”)</span>"
  ],
  'T9'=>[
    'title'=>"Tier 9",'pieces'=>5,
    'text'=>"Trial of the Crusader and Grand Crusader offered faction-themed sets: Alliance heroes donned Wrynn’s Regalia, while Horde warriors bore Hellscream’s Battlegear. Unlike other raids, ToC reused the same arena for escalating encounters, but the sets stood out with their factional pride and trophy-based upgrade system. <span class='set-note'>(Patch 3.2 “Call of the Crusade”)</span>"
  ],
  'T10'=>[
    'title'=>"Tier 10",'pieces'=>5,
    'text'=>"Icecrown Citadel capped Wrath of the Lich King with the most thematic tier yet. Tier 10 sets were purchased with Emblems of Frost and upgraded with Marks of Sanctification from raid bosses. Their designs embodied the looming dread of the Scourge, from the Warlock’s Dark Coven Regalia to the Warrior’s Ymirjar Lord’s Plate. These sets carried players into the final confrontation with Arthas, the Lich King himself. <span class='set-note'>(Patch 3.3 “Fall of the Lich King”)</span>"
  ],
];


/* ---------- ORDER ---------- */
$order = ['T0','T0_5','T1','T1_5','T2','T2_25','T2_5','T3'];
if ($maxTier >= 6)  { $order = array_merge($order, ['DS3','T4','T5','T6']); }
if ($maxTier >= 10) { $order = array_merge($order, ['T7','T8','T9','T10']); }
?>

<!-- Main functions -->
<?php

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

<?php builddiv_start(1, $lang['pvpsets'],1); ?>
<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/armorsets.jpg" alt="PVP sets" class="banner"/>
  
  

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


/* ---------- Render sets ---------- */
echo '<div class="set-group"><div class="set-title">Dungeon & Tier Sets</div>';

foreach ($order as $key) {
  if (preg_match('/^T(\d+)/',$key,$m) && (int)$m[1] > $maxTier) continue;

  $title   = $BLURB[$key]['title'];
  $pieces  = (int)$BLURB[$key]['pieces'];
  $text    = $BLURB[$key]['text'];
  $pairs   = (!empty($N[$key][$selectedClass])) ? armor_set_variants($N[$key][$selectedClass]) : [];

  if (empty($pairs)) echo "<div class='set-desc set-note'>No itemset variants for $selectedClass at $key.</div>";

  echo '<div class="set-group"><div class="set-title">'.htmlspecialchars($title).'</div>';

  if (!$pairs) {
    $nmRaw = $N[$key][$selectedClass] ?? '';
    if ($nmRaw !== '') $pairs = [['name' => $nmRaw, 'role' => '']];
  }

  foreach ($pairs as $p) {
    $nm       = trim($p['name']);
    $setId    = find_itemset_id_by_name($nm);
    $tipHtml  = '';
    $itemsHtml= '';

    if ($setId) {
      $data    = get_itemset_data($setId);
      $tipHtml = render_set_bonus_tip_html($data);

      if (!empty($data['items'])) {
        $icons = [];
        foreach ($data['items'] as $it) {
          $tipItemHtml = render_item_tip_html($it);
          $icons[] =
            '<a href="' . htmlspecialchars(item_href((int)$it['entry'])) . '" class="js-item-tip" data-item-id="' . (int)$it['entry'] . '" data-tip-html="' . htmlspecialchars($tipItemHtml, ENT_QUOTES) . '">'
          . '<img src="/armory/images/icons/64x64/' . htmlspecialchars($it['icon']) . '.png" alt="' . htmlspecialchars($it['name']) . '" width="32" height="32"></a>';
        }
        $itemsHtml = '<span class="set-icons">' . implode('', $icons) . '</span>';
      }
    }

    if ($itemsHtml === '' && $pieces > 0) $itemsHtml = build_placeholder_chips($pieces);

    echo '<div class="set-row">'
       .   '<span class="set-name">'
       .     '<b class="js-set-tip" data-tip-html="' . htmlspecialchars($tipHtml, ENT_QUOTES) . '">' . htmlspecialchars($nm) . '</b>'
       .   '</span>'
       .   '<span class="set-icons">' . $itemsHtml . '</span>'
       . '</div>';
  }


/* ---------- clean + colorize class names ---------- */
echo "<div class='set-title'>".htmlspecialchars($title)."</div>";

// Remove any hard-coded <b> or </b> tags safely
$text = str_replace(['<b>', '</b>'], '', $text);

// Map WoW class names to CSS color classes (matches your .is-* scheme)
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

// Automatically wrap class names in <span class="is-class">
$text = preg_replace_callback(
  '/\b(' . implode('|', array_map('preg_quote', array_keys($classesToCSS))) . ')\b/i',
  function($m) use ($classesToCSS) {
    $name = ucfirst(strtolower($m[1]));
    $css  = $classesToCSS[$name] ?? '';
    return "<span class='{$css}'><b>{$name}</b></span>";
  },
  $text
);

echo "<div class='set-desc'>{$text}</div>";

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
