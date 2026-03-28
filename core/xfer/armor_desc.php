
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



/* ---------- Classic PvP Honor Ranks ---------- */
{ $BLURB = [
  // R1 – Tabard only
  'PvP_R1' => [
    'title'=>"Rank 1 — Private / Scout",'pieces'=>1,
    'text'=>"The very first rank in the Classic Honor system. Earned by achieving 15 Honorable Kills in a week. 
             Rewarded with the <b>Private’s Tabard</b> (Alliance) or <b>Scout’s Tabard</b> (Horde). 
             A simple cosmetic, but the first step on the long road of the PvP grind."
  ],
  // R2 – PvP Trinket
  'PvP_R2' => [
    'title'=>"Rank 2 — Corporal / Grunt",'pieces'=>1,
    'text'=>"The first meaningful PvP reward. Players unlocked the <b>Insignia of the Alliance</b> or 
             <b>Insignia of the Horde</b>, a trinket that removes movement impairing effects. 
             This became a staple PvP item throughout Classic."
  ],
  // R3 – Cloaks + 10% NPC discount
  'PvP_R3' => [
    'title'=>"Rank 3 — Sergeant / Sergeant",'pieces'=>1,
    'text'=>"Grants access to rare-quality cloaks like the <b>Sergeant’s Cape</b>, along with 
             a permanent 10% discount on goods and repairs from your faction’s vendors."
  ],
  // R4 – Neckpieces
  'PvP_R4' => [
    'title'=>"Rank 4 — Master Sergeant / Senior Sergeant",'pieces'=>1,
    'text'=>"Unlocks the <b>Master Sergeant’s Insignia</b> or <b>Senior Sergeant’s Insignia</b>, 
             a powerful blue-quality neckpiece. A badge of growing prestige within your faction."
  ],
  // R5 – Bracers
  'PvP_R5' => [
    'title'=>"Rank 5 — Sergeant Major / First Sergeant",'pieces'=>1,
    'text'=>"Grants access to blue-quality bracers tailored to your class archetype. 
             These filled gaps in pre-raid gearing and were valued for their stats."
  ],
  // R6 – Officer’s barracks, heraldry, potions
  'PvP_R6' => [
    'title'=>"Rank 6 — Knight / Stone Guard",'pieces'=>1,
    'text'=>"A social milestone rank that granted access to the <b>Officer’s Barracks</b> in Stormwind or Orgrimmar. 
             Players also unlocked <b>Knight’s Colors</b> or <b>Stone Guard’s Herald</b>, 
             and could purchase <b>Combat Mana Potions</b> or <b>Combat Healing Potions</b> for PvP use."
  ],
  // R7 – Boots & Gloves (rare-quality set)
  'PvP_R7' => [
    'title'=>"Rank 7 — Knight-Lieutenant / Blood Guard",'pieces'=>2,
    'text'=>"The first step into class-specific PvP gear. Unlocks <b>rare-quality gloves and boots</b> 
             for each class. These pieces were PvP-focused, offering stamina and resilience-style stats 
             (before resilience was formalized), making them excellent for battleground play."
  ],
  // R8 – Chest & Legs (rare-quality set)
  'PvP_R8' => [
    'title'=>"Rank 8 — Knight-Captain / Legionnaire",'pieces'=>2,
    'text'=>"Adds <b>rare-quality chest and legs</b> to the PvP set. 
             At this point, players could start fielding 4-piece bonuses, cementing their class PvP look."
  ],
  // R9 – Battle Standards
  'PvP_R9' => [
    'title'=>"Rank 9 — Knight-Champion / Centurion",'pieces'=>1,
    'text'=>"This milestone rank granted access to faction <b>battle standards</b>, 
             powerful placeable banners that boosted allies’ health and morale in battlegrounds."
  ],
  // R10 – Helm & Shoulders (completing the blue set)
  'PvP_R10' => [
    'title'=>"Rank 10 — Lieutenant Commander / Champion",'pieces'=>2,
    'text'=>"The final upgrade for the <b>rare-quality PvP set</b>. Unlocks the <b>helm and shoulders</b>, 
             giving players the complete 6-piece blue set unique to their class. 
             These were often used in PvE as filler gear until raid epics were earned."
  ],
  // R11 – Epic mounts + WorldDefense
  'PvP_R11' => [
    'title'=>"Rank 11 — Commander / Lieutenant General",'pieces'=>1,
    'text'=>"Beyond gear, Rank 11 granted access to <b>epic PvP mounts</b> — 
             the <b>Stormpike Battle Charger</b> (Alliance) and <b>Frostwolf Howler</b> (Horde). 
             Players could also speak in the <b>WorldDefense channel</b>, rallying their faction to battle."
  ],
  // R12 – Epic Gloves, Legs, Boots
  'PvP_R12' => [
    'title'=>"Rank 12 — Marshal / General",'pieces'=>3,
    'text'=>"The first tier of <b>epic-quality class PvP sets</b>. Unlocks gloves, legs, and boots. 
             These came with high stamina, crit, and resilience-style stats, 
             instantly recognizable with upgraded visuals over the R7–10 blues."
  ],
  // R13 – Helm, Chest, Shoulders (epic set complete)
  'PvP_R13' => [
    'title'=>"Rank 13 — Field Marshal / Warlord",'pieces'=>3,
    'text'=>"The apex of PvP armor progression. Unlocks <b>epic helm, chest, and shoulders</b>, 
             completing the 6-piece epic PvP set. These were endgame BiS for battleground and world PvP 
             players, and a symbol of incredible time investment."
  ],
  // R14 – Epic Weapons
  'PvP_R14' => [
    'title'=>"Rank 14 — Grand Marshal / High Warlord",'pieces'=>10,
    'text'=>"The ultimate PvP achievement of Classic WoW. Grants access to the legendary 
             <b>epic-quality PvP weapons</b>, including swords, maces, daggers, axes, staves, and bows, 
             each themed uniquely for Alliance or Horde. 
             Owning these weapons was a symbol of unmatched grind and prestige."
  ]
];
}

/* $BLURB['PvP_R14'] = [
  'title'=>"Rank 14 — Grand Marshal / High Warlord",'pieces'=>10,
  'text'=>"The ultimate reward of the Classic Honor grind — <b>epic PvP weapons</b>, unlocked at Rank 14. 
           These were the most prestigious items in Classic WoW, with unique Alliance (Grand Marshal’s) 
           and Horde (High Warlord’s) designs.  

           • <b>Warriors</b> gained access to the full arsenal — two-handed swords, axes, maces, dual wield weapons, and shields.  
           • <b>Paladins</b> and <b>Shamans</b> received massive 2H weapons, crushing maces, and sturdy shields for frontline combat.  
           • <b>Hunters</b> unlocked ranged weapons — bows, crossbows, and rifles — along with melee support options.  
           • <b>Rogues</b> could choose from deadly daggers, swords, axes, and the iconic paired claws (Left & Right Rippers).  
           • <b>Mages</b>, <b>Warlocks</b>, and <b>Priests</b> wielded staves, spellblades, and tomes, blending raw power with utility.  
           • <b>Druids</b> were offered versatile staves, spellblades, and shields to match their hybrid roles.  

           These weapons were instantly recognizable in battlegrounds and the open world — a symbol that 
           the wielder had survived the grueling weekly grind to the very top of the Honor ladder. 
           Few ever achieved them, making R14 weapons one of the rarest and most respected rewards in the game."
]; */


{ $N['PvP_R1'] = [
  'Warrior' => "Private's Tabard / Scout's Tabard",
  'Paladin' => "Private's Tabard / Scout's Tabard",
  'Hunter'  => "Private's Tabard / Scout's Tabard",
  'Rogue'   => "Private's Tabard / Scout's Tabard",
  'Priest'  => "Private's Tabard / Scout's Tabard",
  'Shaman'  => "Private's Tabard / Scout's Tabard",
  'Mage'    => "Private's Tabard / Scout's Tabard",
  'Warlock' => "Private's Tabard / Scout's Tabard",
  'Druid'   => "Private's Tabard / Scout's Tabard"
];
$N['PvP_R2'] = [
  'Warrior' => "Insignia of the Alliance / Insignia of the Horde",
  'Paladin' => "Insignia of the Alliance / Insignia of the Horde",
  'Hunter'  => "Insignia of the Alliance / Insignia of the Horde",
  'Rogue'   => "Insignia of the Alliance / Insignia of the Horde",
  'Priest'  => "Insignia of the Alliance / Insignia of the Horde",
  'Shaman'  => "Insignia of the Alliance / Insignia of the Horde",
  'Mage'    => "Insignia of the Alliance / Insignia of the Horde",
  'Warlock' => "Insignia of the Alliance / Insignia of the Horde",
  'Druid'   => "Insignia of the Alliance / Insignia of the Horde"
];
$N['PvP_R3'] = [
  'Warrior' => "Sergeant's Cape / Sergeant's Cloak",
  'Paladin' => "Sergeant's Cape / Sergeant's Cloak",
  'Hunter'  => "Sergeant's Cape / Sergeant's Cloak",
  'Rogue'   => "Sergeant's Cape / Sergeant's Cloak",
  'Priest'  => "Sergeant's Cape / Sergeant's Cloak",
  'Shaman'  => "Sergeant's Cape / Sergeant's Cloak",
  'Mage'    => "Sergeant's Cape / Sergeant's Cloak",
  'Warlock' => "Sergeant's Cape / Sergeant's Cloak",
  'Druid'   => "Sergeant's Cape / Sergeant's Cloak"
];
$N['PvP_R4'] = [
  'Warrior' => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Paladin' => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Hunter'  => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Rogue'   => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Priest'  => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Shaman'  => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Mage'    => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Warlock' => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia",
  'Druid'   => "Master Sergeant’s Insignia / Senior Sergeant’s Insignia"
];

$N['PvP_R5'] = [
  'Warrior' => "Sergeant Major’s Plate Bracers / First Sergeant’s Plate Bracers",
  'Paladin' => "Sergeant Major’s Plate Bracers / First Sergeant’s Plate Bracers",
  'Hunter'  => "Sergeant Major’s Chain Bracers / First Sergeant’s Chain Bracers",
  'Rogue'   => "Sergeant Major’s Leather Bracers / First Sergeant’s Leather Bracers",
  'Priest'  => "Sergeant Major’s Silk Bracers / First Sergeant’s Silk Bracers",
  'Shaman'  => "Sergeant Major’s Mail Bracers / First Sergeant’s Mail Bracers",
  'Mage'    => "Sergeant Major’s Silk Bracers / First Sergeant’s Silk Bracers",
  'Warlock' => "Sergeant Major’s Silk Bracers / First Sergeant’s Silk Bracers",
  'Druid'   => "Sergeant Major’s Leather Bracers / First Sergeant’s Leather Bracers"
];

$N['PvP_R6'] = [
  'Warrior' => "Knight’s Colors / Stone Guard’s Herald",
  'Paladin' => "Knight’s Colors / Stone Guard’s Herald",
  'Hunter'  => "Knight’s Colors / Stone Guard’s Herald",
  'Rogue'   => "Knight’s Colors / Stone Guard’s Herald",
  'Priest'  => "Knight’s Colors / Stone Guard’s Herald",
  'Shaman'  => "Knight’s Colors / Stone Guard’s Herald",
  'Mage'    => "Knight’s Colors / Stone Guard’s Herald",
  'Warlock' => "Knight’s Colors / Stone Guard’s Herald",
  'Druid'   => "Knight’s Colors / Stone Guard’s Herald"
];
//2 piece Knight-Lieutenant / Blood Guard
$N['PvP_R7'] = [
  'Warrior' => "Knight-Lieutenant’s Battlearmor / Blood Guard’s Battlearmor",
  'Paladin' => "Knight-Lieutenant’s Redoubt / Blood Guard’s Redoubt",
  'Hunter'  => "Knight-Lieutenant’s Pursuit / Blood Guard’s Pursuit",
  'Rogue'   => "Knight-Lieutenant’s Vestments / Blood Guard’s Vestments",
  'Priest'  => "Knight-Lieutenant’s Raiment / Blood Guard’s Raiment",
  'Shaman'  => "Knight-Lieutenant’s Earthshaker / Blood Guard’s Earthshaker",
  'Mage'    => "Knight-Lieutenant’s Regalia / Blood Guard’s Regalia",
  'Warlock' => "Knight-Lieutenant’s Threads / Blood Guard’s Threads",
  'Druid'   => "Knight-Lieutenant’s Sanctuary / Blood Guard’s Sanctuary"
];
//4 piece
$N['PvP_R8'] = [
  'Warrior' => "Knight-Captain’s Battlearmor / Legionnaire’s Battlearmor",
  'Paladin' => "Knight-Captain’s Redoubt / Legionnaire’s Redoubt",
  'Hunter'  => "Knight-Captain’s Pursuit / Legionnaire’s Pursuit",
  'Rogue'   => "Knight-Captain’s Vestments / Legionnaire’s Vestments",
  'Priest'  => "Knight-Captain’s Raiment / Legionnaire’s Raiment",
  'Shaman'  => "Knight-Captain’s Earthshaker / Legionnaire’s Earthshaker",
  'Mage'    => "Knight-Captain’s Regalia / Legionnaire’s Regalia",
  'Warlock' => "Knight-Captain’s Threads / Legionnaire’s Threads",
  'Druid'   => "Knight-Captain’s Sanctuary / Legionnaire’s Sanctuary"
]
//6 piece
;$N['PvP_R10'] = [
  'Warrior' => "Lieutenant Commander’s Battlearmor / Champion’s Battlearmor",
  'Paladin' => "Lieutenant Commander’s Redoubt / Champion’s Redoubt",
  'Hunter'  => "Lieutenant Commander’s Pursuit / Champion’s Pursuit",
  'Rogue'   => "Lieutenant Commander’s Vestments / Champion’s Vestments",
  'Priest'  => "Lieutenant Commander’s Raiment / Champion’s Raiment",
  'Shaman'  => "Lieutenant Commander’s Earthshaker / Champion’s Earthshaker",
  'Mage'    => "Lieutenant Commander’s Regalia / Champion’s Regalia",
  'Warlock' => "Lieutenant Commander’s Threads / Champion’s Threads",
  'Druid'   => "Lieutenant Commander’s Sanctuary / Champion’s Sanctuary"
];
$N['PvP_R12'] = [
  'Warrior' => "Marshaal’sal’s Battlegear / General’s Battlegear",
  'Paladin' => "Marsahal’shal’s Aegis / General’s Aegis",
  'Hunter'  => "Marshasl’sl’s Pursuit / General’s Pursuit",
  'Rogue'   => "Marshasl’s Vestments / General’s Vestments",
  'Priest'  => "Marshaql’s Raiment / General’s Raiment",
  'Shaman'  => "Marshaql’s Earthshaker / General’s Earthshaker",
  'Mage'    => "Marshaql’sl’s Regalia / General’s Regalia",
  'Warlock' => "Marshaql’s Threads / General’s Threads",
  'Druid'   => "Marshal’s Sanctuary / General’s Sanctuary"
];
$N['PvP_R13'] = [
  'Warrior' => "Field Marshal’s Battlegear / Warlord’s Battlegear",
  'Paladin' => "Field Marshal’s Aegis / Warlord’s Aegis",
  'Hunter'  => "Field Marshal’s Pursuit / Warlord’s Pursuit",
  'Rogue'   => "Field Marshal’s Vestments / Warlord’s Vestments",
  'Priest'  => "Field Marshal’s Raiment / Warlord’s Raiment",
  'Shaman'  => "Field Marshal’s Earthshaker / Warlord’s Earthshaker",
  'Mage'    => "Field Marshal’s Regalia / Warlord’s Regalia",
  'Warlock' => "Field Marshal’s Threads / Warlord’s Threads",
  'Druid'   => "Field Marshal’s Sanctuary / Warlord’s Sanctuary"
];
$N['PvP_R14'] = [
  'Warrior' => "
    Grand Marshal's Claymore / High Warlord's Greatsword,
    Grand Marshal's Battle Hammer / High Warlord's Battle Mace,
    Grand Marshal's Glaive / High Warlord's Battle Axe,
    Grand Marshal's Longsword / High Warlord's Blade,
    Grand Marshal's Handaxe / High Warlord's Cleaver,
    Grand Marshal's Left Ripper / High Warlord's Left Claw,
    Grand Marshal's Right Ripper / High Warlord's Right Claw,
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Shield / High Warlord's Shield Wall
  ",

  'Paladin' => "
    Grand Marshal's Claymore / High Warlord's Greatsword,
    Grand Marshal's Battle Hammer / High Warlord's Battle Mace,
    Grand Marshal's Longsword / High Warlord's Blade,
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Shield / High Warlord's Shield Wall
  ",

  'Hunter' => "
    Grand Marshal's Longsword / High Warlord's Blade,
    Grand Marshal's Handaxe / High Warlord's Cleaver,
    Grand Marshal's Repeater / High Warlord's Crossbow,
    Grand Marshal's Longbow / High Warlord's Bow,
    Grand Marshal's Rifle / High Warlord's Rifle
  ",

  'Rogue' => "
    Grand Marshal's Dirk / High Warlord's Dagger,
    Grand Marshal's Longsword / High Warlord's Blade,
    Grand Marshal's Handaxe / High Warlord's Cleaver,
    Grand Marshal's Left Ripper / High Warlord's Left Claw,
    Grand Marshal's Right Ripper / High Warlord's Right Claw,
    Grand Marshal's Stave / High Warlord's War Staff
  ",

  'Priest' => "
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Tome of Power / High Warlord's Tome of Destruction,
    Grand Marshal's Tome of Restoration / High Warlord's Tome of Healing,
    Grand Marshal's Gavel / High Warlord's Spellblade
  ",

  'Shaman' => "
    Grand Marshal's Battle Hammer / High Warlord's Battle Mace,
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Glaive / High Warlord's Battle Axe,
    Grand Marshal's Shield / High Warlord's Shield Wall
  ",

  'Mage' => "
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Tome of Power / High Warlord's Tome of Destruction,
    Grand Marshal's Tome of Restoration / High Warlord's Tome of Healing,
    Grand Marshal's Gavel / High Warlord's Spellblade,
    Grand Marshal's Dirk / High Warlord's Dagger
  ",

  'Warlock' => "
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Tome of Power / High Warlord's Tome of Destruction,
    Grand Marshal's Tome of Restoration / High Warlord's Tome of Healing,
    Grand Marshal's Gavel / High Warlord's Spellblade,
    Grand Marshal's Dirk / High Warlord's Dagger
  ",

  'Druid' => "
    Grand Marshal's Stave / High Warlord's War Staff,
    Grand Marshal's Gavel / High Warlord's Spellblade,
    Grand Marshal's Shield / High Warlord's Shield Wall
  "
];





}

/* ---------- PvP display order ---------- */
$order = [
  'PvP_R1',   // Tabards
  'PvP_R2',   // Trinkets
  'PvP_R3',   // Cloaks
  'PvP_R4',   // Neckpieces
  'PvP_R5',   // Bracers
  'PvP_R6',   // Officer’s barracks / heraldry
  'PvP_R7',   // Gloves + Boots (2p bonus)
  'PvP_R8',   // Chest + Legs (4p bonus)
  'PvP_R9',   // Battle Standards
  'PvP_R10',  // Helm + Shoulders (6p full rare set)
  'PvP_R11',  // Epic Mounts + WorldDefense channel
  'PvP_R12',  // Gloves, Legs, Boots (epic tier start, 3 pieces)
  'PvP_R13',  // Helm, Chest, Shoulders (epic tier complete, 6 pieces)
  'PvP_R14',  // Weapons (ultimate prestige)
];








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