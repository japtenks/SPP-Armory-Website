<?php
/* ---------- Config for server.armorsets.php ---------- */
if (!isset($expansion)) $expansion = 1;

/* Example schemas */
$SCHEMAS = [
  0 => ['armory' => 'classicarmory', 'world' => 'classicmangos'],
  1 => ['armory' => 'tbcarmory',     'world' => 'tbcmangos'],
  2 => ['armory' => 'wotlkarmory',   'world' => 'wotlkmangos'],
];
$ERA           = isset($SCHEMAS[$expansion]) ? $expansion : 1;
$ARMORY_SCHEMA = $SCHEMAS[$ERA]['armory'];
$WORLD_SCHEMA  = $SCHEMAS[$ERA]['world'];

$maxTier   = ($expansion === 2) ? 10 : (($expansion === 1) ? 6 : 3);

$selectedClass = isset($_GET['class']) ? trim($_GET['class']) : '';
$iconBase   = './armory/shared/icons/';
$iconPref   = 'class_';
$iconExt    = '.jpg';

/* Classes config */
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

/* big arrays */
/* ---------- CLASS SET NAMES  ---------- */

$N['T0'	 ]=['Warrior'=>"Battlegear of Valor",'Paladin'=>"Lightforge Armor",'Hunter'=>"Beaststalker Armor",'Rogue'=>"Shadowcraft Armor",'Priest'=>"Vestments of the Devout",'Shaman'=>"The Elements",'Mage'=>"Magister's Regalia",'Warlock'=>"Dreadmist Raiment",'Druid'=>"Wildheart Raiment"];
$N['T0_5']=['Warrior'=>"Battlegear of Heroism",'Paladin'=>"Soulforge Armor",'Hunter'=>"Beastmaster Armor",'Rogue'=>"Darkmantle Armor",'Priest'=>"Vestments of the Virtuous",'Shaman'=>"The Five Thunders",'Mage'=>"Sorcerer's Regalia",'Warlock'=>"Deathmist Raiment",'Druid'=>"Feralheart Raiment"];
$N['T2_5']=['Warrior'=>"Conqueror's Battlegear",'Paladin'=>"Avenger's Battlegear",'Hunter'=>"Striker's Garb",'Rogue'=>"Deathdealer's Embrace",'Priest'=>"Garments of the Oracle",'Shaman'=>"Stormcaller's Garb",'Mage'=>"Enigma Vestments",'Warlock'=>"Doomcaller's Attire",'Druid'=>"Genesis Raiment"];
$N['T1'	 ]=['Warrior'=>"Battlegear of Might",'Paladin'=>"Lawbringer Armor",'Hunter'=>"Giantstalker Armor",'Rogue'=>"Nightslayer Armor",'Priest'=>"Vestments of Prophecy",'Shaman'=>"The Earthfury",'Mage'=>"Arcanist Regalia",'Warlock'=>"Felheart Raiment",'Druid'=>"Cenarion Raiment"];
$N['T1_5']=['Warrior'=>"Vindicator's Battlegear",'Paladin'=>"Freethinker's Armor",'Hunter'=>"Predator's Armor",'Rogue'=>"Madcap's Outfit",'Priest'=>"Confessor's Raiment",'Shaman'=>"Augur's Regalia",'Mage'=>"Illusionist's Attire",'Warlock'=>"Demoniac's Threads",'Druid'=>"Haruspex's Garb"];
$N['T2'	 ]=['Warrior'=>"Battlegear of Wrath",'Paladin'=>"Judgement Armor",'Hunter'=>"Dragonstalker Armor",'Rogue'=>"Bloodfang Armor",'Priest'=>"Vestments of Transcendence",'Shaman'=>"The Ten Storms",'Mage'=>"Netherwind Regalia",'Warlock'=>"Nemesis Raiment",'Druid'=>"Stormrage Raiment"];
$N['T3'	 ]=['Warrior'=>"Dreadnaught Battlegear",'Paladin'=>"Redemption Armor",'Hunter'=>"Cryptstalker Armor",'Rogue'=>"Bonescythe Armor",'Priest'=>"Vestments of Faith",'Shaman'=>"The Earthshatterer",'Mage'=>"Frostfire Regalia",'Warlock'=>"Plagueheart Raiment",'Druid'=>"Dreamwalker Raiment"];
$N['T2_25']=['Warrior'=>"Battlegear of Unyielding Strength",'Paladin'=>"Battlegear of Eternal Justice",'Hunter'=>"Trappings of the Unseen Path",'Rogue'=>"Emblems of Veiled Shadows",'Priest'=>"Finery of Infinite Wisdom",'Shaman'=>"Gift of the Gathering Storm",'Mage'=>"Trappings of Vaulted Secrets",'Warlock'=>"Implements of Unspoken Names",'Druid'=>"Symbols of Unending Life"];
$N['T4'	 ]=['Druid'=>"Malorne Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Demon Stalker Armor",'Mage'=>"Aldor Regalia",'Paladin'=>"Justicar Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Incarnate Raiment / Regalia (Holy/Disc / Shadow)",'Rogue'=>"Netherblade",'Shaman'=>"Cyclone Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Voidheart Raiment",'Warrior'=>"Warbringer Battlegear / Armor (Arms/Fury / Protection)"];
$N['T5'	 ]=['Druid'=>"Nordrassil Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Rift Stalker Armor",'Mage'=>"Tirisfal Regalia",'Paladin'=>"Crystalforge Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Avatar Raiment / Regalia (Holy/Disc / Shadow)",'Rogue'=>"Deathmantle",'Shaman'=>"Cataclysm Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Corruptor Raiment",'Warrior'=>"Destroyer Battlegear / Armor (Arms/Fury / Protection)"];
$N['T6'  ]=['Druid'=>"Thunderheart Regalia / Harness / Raiment (Balance / Feral / Restoration)",'Hunter'=>"Gronnstalker's Armor",'Mage'=>"Tempest Regalia",'Paladin'=>"Lightbringer Raiment / Armor / Battlegear (Holy / Protection / Retribution)",'Priest'=>"Vestments of Absolution / Absolution Regalia (Holy/Disc / Shadow)",'Rogue'=>"Slayer's Armor",'Shaman'=>"Skyshatter Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",'Warlock'=>"Malefic Raiment",'Warrior'=>"Onslaught Battlegear / Armor (Arms/Fury / Protection)"];
$N['T7'	 ]=['Death Knight'=>"Scourgeborne Battlegear / Scourgeborne Plate (DPS / Tank)",'Druid'=>"Dreamwalker Regalia / Dreamwalker Battlegear / Dreamwalker Garb (Balance / Feral / Restoration)",'Hunter'=>"Cryptstalker Battlegear",'Mage'=>"Frostfire Regalia",'Paladin'=>"Redemption Regalia / Redemption Armor / Redemption Battlegear (Holy / Protection / Retribution)",'Priest'=>"Regalia of Faith / Garb of Faith (Shadow / Holy–Discipline)",'Rogue'=>"Bonescythe Battlegear",'Shaman'=>"Earthshatter Regalia / Earthshatter Battlegear / Earthshatter Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Plagueheart Garb",'Warrior'=>"Dreadnaught Battlegear / Dreadnaught Plate (Arms/Fury / Protection)"];
$N['T8'	 ]=['Death Knight'=>"Darkruned Battlegear / Darkruned Plate (DPS / Tank)",'Druid'=>"Nightsong Regalia / Nightsong Battlegear / Nightsong Garb (Balance / Feral / Restoration)",'Hunter'=>"Scourgestalker Battlegear",'Mage'=>"Kirin Tor Garb",'Paladin'=>"Aegis Regalia / Aegis Armor / Aegis Battlegear (Holy / Protection / Retribution)",'Priest'=>"Sanctification Regalia / Sanctification Garb (Shadow / Holy–Discipline)",'Rogue'=>"Terrorblade Battlegear",'Shaman'=>"Worldbreaker Regalia / Worldbreaker Battlegear / Worldbreaker Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Deathbringer Garb",'Warrior'=>"Siegebreaker Battlegear / Siegebreaker Plate (Arms/Fury / Protection)"];
$N['T9'	 ]=['Death Knight'=>"Thassarian's / Koltira's Battlegear / Plate (A / H)",'Druid'=>"Malfurion's / Runetotem's Regalia / Battlegear / Garb (A / H)",'Hunter'=>"Windrunner's Battlegear (Alliance / Horde)",'Mage'=>"Khadgar's / Sunstrider's Regalia (A / H)",'Paladin'=>"Turalyon's / Liadrin's Regalia / Armor / Battlegear (A / H)",'Priest'=>"Velen's / Zabra's Regalia / Garb (A / H)",'Rogue'=>"VanCleef's / Garona's Battlegear (A / H)",'Shaman'=>"Nobundo's / Thrall's Regalia / Battlegear / Garb (A / H)",'Warlock'=>"Kel'Thuzad's / Gul'dan's Regalia (A / H)",'Warrior'=>"Wrynn's / Hellscream's Battlegear / Plate (A / H)"];
$N['T10' ]=['Death Knight'=>"Scourgelord's Battlegear / Scourgelord's Plate (DPS / Tank)",'Druid'=>"Lasherweave Regalia / Lasherweave Battlegear / Lasherweave Garb (Balance / Feral / Restoration)",'Hunter'=>"Ahn'Kahar Blood Hunter's Battlegear",'Mage'=>"Bloodmage's Regalia",'Paladin'=>"Lightsworn Regalia / Lightsworn Armor / Lightsworn Battlegear (Holy / Protection / Retribution)",'Priest'=>"Crimson Acolyte's Regalia / Crimson Acolyte's Garb (Shadow / Holy–Discipline)",'Rogue'=>"Shadowblade's Battlegear",'Shaman'=>"Frost Witch's Regalia / Frost Witch's Battlegear / Frost Witch's Garb (Elemental / Enhancement / Restoration)",'Warlock'=>"Dark Coven's Regalia",'Warrior'=>"Ymirjar Lord's Battlegear / Ymirjar Lord's Plate (Arms/Fury / Protection)"];

/* ---------- Brief descriptions of the Tiers ---------- */
$BLURB = [
  'T0'=>['title'=>"Tier 0 (Dungeon Set 1)",'pieces'=>8,'text'=>"The first full class sets—forged from Stratholme, Scholomance, and Blackrock Spire. Your entry ticket to endgame. <span class='set-note'>(Patch 1.05)</span>"],
  'T0_5'=>['title'=>"Tier 0.5 (Dungeon Set 2)",'pieces'=>8,'text'=>"An epic upgrade questline that reforges T0; seal demons, summon Lord Valthalak, and claim stronger gear. <span class='set-note'>(Patch 1.10 “Storms of Azeroth”)</span>"],
  'T1'=>['title'=>"Tier 1",'pieces'=>8,'text'=>"Molten Core’s lava-forged epics—Garr, Golemagg, and Rag himself hand you your first true raid set. <span class='set-note'>(Classic launch, Phase 1)</span>"],
  'T1_5'=>['title'=>"Tier 1.5",'pieces'=>5,'text'=>"Zandalar’s 20-man sets with 2/3/5-piece bonuses—jungle-themed power from Hakkar’s empire. <span class='set-note'>(Patch 1.7 “Rise of the Blood God”)</span>"],
  'T2'=>['title'=>"Tier 2",'pieces'=>8,'text'=>"Blackwing Lair tokens with helm from Onyxia and legs from Ragnaros—BWL crowns the Blackrock campaign. <span class='set-note'>(BWL Patch 1.6; Ony/MC Phase 1)</span>"],
  'T2_25'=>['title'=>"Tier 2.5",'pieces'=>5,'text'=>"Cenarion Circle quest/token sets from Ruins of Ahn’Qiraj—class-themed 5-pieces earned via CC rep and raid drops. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T2_5'=>['title'=>"Tier 2.5",'pieces'=>5,'text'=>"Qiraji tokens and Old-God motifs—a spec-leaning set from C’Thun’s citadel. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T3'=>['title'=>"Tier 3)",'pieces'=>9,'text'=>"Kel’Thuzad’s necropolis: plague-etched armor plus a signature ring—Classic’s final test. <span class='set-note'>(Patch 1.11 “Shadow of the Necropolis” / Phase 6)</span>"],
  'T4'=>['title'=>"Tier 4",'pieces'=>5,'text'=>"Karazhan, Gruul, and Magtheridon drop Champion/Hero/Defender tokens; redeem them in Shattrath. The first spec-split tier. <span class='set-note'>(TBC launch, Patches 2.0–2.0.3)</span>"],
  'T5'=>['title'=>"Tier 5",'pieces'=>5,'text'=>"Serpentshrine Cavern & The Eye—Lady Vashj and Kael’thas guard the upgrades (and your key to T6 attunements). <span class='set-note'>(Available early; major retune in Patch 2.1)</span>"],
  'T6'=>['title'=>"Tier 6",'pieces'=>8,'text'=>"Battle through Hyjal Summit and Black Temple for the core set; Sunwell adds belts/boots/bracers. Archimonde and Illidan await. <span class='set-note'>(Patch 2.1; 2.4 “Fury of the Sunwell” additions)</span>"],
  'T7'=>['title'=>"Tier 7",'pieces'=>5,'text'=>"Naxxramas (revisited), Obsidian Sanctum, and Vault of Archavon—two tracks (Heroes’/Valorous). <span class='set-note'>(Wrath launch, Patch 3.0)</span>"],
  'T8'=>['title'=>"Tier 8",'pieces'=>5,'text'=>"Ulduar—Titan vaults with in-fight hard modes; Valorous/Conqueror’s variants. <span class='set-note'>(Patch 3.1 “Secrets of Ulduar”)</span>"],
  'T9'=>['title'=>"Tier 9",'pieces'=>5,'text'=>"Trial of the Crusader/Grand Crusader—faction-themed sets with trophy upgrades across normal/heroic. <span class='set-note'>(Patch 3.2 “Call of the Crusade”)</span>"],
  'T10'=>['title'=>"Tier 10",'pieces'=>5,'text'=>"Icecrown Citadel—buy the base set with Emblems of Frost, then upgrade to (Heroic) Sanctified via Marks of Sanctification. <span class='set-note'>(Patch 3.3 “Fall of the Lich King”)</span>"],
];

/* ---------- ORDER ---------- */
$order = ['T0','T0_5','T1','T1_5','T2','T2_25','T2_5','T3'];
if ($maxTier >= 6)  { $order = array_merge($order, ['T4','T5','T6']); }
if ($maxTier >= 10) { $order = array_merge($order, ['T7','T8','T9','T10']); }


echo "<pre style='color:orange;'>CONFIG DEBUG: ".
     "\$BLURB=". (isset($BLURB) ? count($BLURB) : 'NOT SET') . 
     ", \$order=".(isset($order) ? count($order) : 'NOT SET') .
     ", \$classes=".(isset($classes) ? count($classes) : 'NOT SET') .
     "</pre>";

?>