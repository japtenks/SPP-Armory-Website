<img src="<?php echo $currtmp; ?>/images/armorsets.jpg" />
<br />
<?php builddiv_start(1, $lang['armorsets2']) ?>

<style>
  #cnt{width:850px;margin-left:0}

  /* Top class selector (single row, sticky) */
  .class-bar{
    position:sticky;top:0;z-index:20;
    display:flex;flex-wrap:wrap;gap:10px;
    margin:6px 0 16px;justify-content:flex-start;
    padding:8px 6px;background:rgba(244,230,198,.92);
    border:1px solid #c3a779;border-radius:8px;
    backdrop-filter:saturate(160%) blur(2px)
  }
  .class-token{
    --ring:#888;--glow:rgba(136,136,136,.55);
    width:40px;height:40px;border-radius:999px;border:0;background:transparent;cursor:pointer;
    box-shadow:0 0 0 2px rgba(0,0,0,.45) inset,0 0 0 2px rgba(255,255,255,.2);
    transition:transform .12s ease,box-shadow .12s ease,filter .12s ease
  }
  .class-token img{width:100%;height:100%;border-radius:999px;display:block;object-fit:cover}
  .class-token:hover,.class-token:focus{transform:translateY(-1px);filter:brightness(1.05);
    box-shadow:0 0 0 2px rgba(0,0,0,.6) inset,0 0 0 2px var(--ring),0 0 16px var(--glow)}
  .class-token.is-active{box-shadow:0 0 0 2px rgba(0,0,0,.7) inset,0 0 0 2px var(--ring),0 0 18px var(--glow)}
  .is-warrior{--ring:#C79C6E;--glow:rgba(199,156,110,.6)}
  .is-paladin{--ring:#F58CBA;--glow:rgba(245,140,186,.6)}
  .is-hunter {--ring:#ABD473;--glow:rgba(171,212,115,.6)}
  .is-rogue  {--ring:#FFF569;--glow:rgba(255,245,105,.55)}
  .is-priest {--ring:#FFF;--glow:rgba(255,255,255,.55)}
  .is-shaman {--ring:#0070DE;--glow:rgba(0,112,222,.55)}
  .is-mage   {--ring:#40C7EB;--glow:rgba(64,199,235,.55)}
  .is-warlock{--ring:#8787ED;--glow:rgba(135,135,237,.55)}
  .is-druid  {--ring:#FF7D0A;--glow:rgba(255,125,10,.55)}
  .is-dk     {--ring:#C41F3B;--glow:rgba(196,31,59,.55)}

  /* Section headings + copy */
  .set-group{margin:20px 0 10px}
  .set-title{font-size:20px;font-weight:700;color:#6b2d1f;margin:14px 0 6px}
  .set-subtitle{font-weight:700;color:#7a3f28}
  .set-desc{margin:2px 0 10px}
  .set-note{color:#7b6a52;display:block;margin-top:2px}
  .note-inline{display:inline;color:#7b6a52;margin-left:6px}

/* Tier rows */
.variant-tight{margin:4px 0 10px 14px;padding:0;list-style:none}
.variant-tight .v-row{
  display:grid;
  grid-template-columns:50px auto max-content; /* fixed prefix | name | spec */
  column-gap:8px;
  align-items:baseline;
  margin:2px 0;
}
.variant-tight .v-prefix{font-weight:700;color:#6b2d1f;white-space:nowrap}
.variant-tight .v-name b{color:#6b2d1f}
.variant-tight .v-spec{white-space:nowrap;color:#3a2a12}


/* Top class selector: floating icons, no bar */
.class-bar{
  position: sticky; top:6px; z-index:40;
  display:flex; flex-wrap:wrap; gap:12px;
  margin:6px 0 18px; padding:0;
  background:transparent; border:0; border-radius:0;
  backdrop-filter:none;
}

/* Floating token */
.class-token{
  --ring:#888; --glow:rgba(136,136,136,.45);
  width:44px; height:44px; border:0; border-radius:999px;
  background:transparent; cursor:pointer; position:relative; display:block;
  /* ring + inner bevel + soft drop shadow */
  box-shadow:
    0 0 0 2px rgba(0,0,0,.45) inset,
    0 0 0 2px rgba(255,255,255,.25),
    0 8px 16px rgba(0,0,0,.18);
  transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
}
.class-token img{width:100%;height:100%;border-radius:999px;display:block;object-fit:cover}

/* soft oval shadow beneath each icon */
.class-token::after{
  content:""; position:absolute; left:10%; right:10%; bottom:-6px; height:10px;
  background:radial-gradient(ellipse at center, rgba(0,0,0,.25), rgba(0,0,0,0) 70%);
  filter:blur(2px); opacity:.6; pointer-events:none;
  transition:opacity .15s ease, transform .15s ease;
}

/* hover/active lift + colored ring/glow */
.class-token:hover,.class-token:focus{
  transform:translateY(-4px); filter:brightness(1.05);
  box-shadow:
    0 0 0 2px rgba(0,0,0,.6) inset,
    0 0 0 2px var(--ring),
    0 0 18px var(--glow),
    0 14px 24px rgba(0,0,0,.22);
}
.class-token:hover::after,.class-token:focus::after{opacity:.9; transform:translateY(2px)}

.class-token.is-active{
  box-shadow:
    0 0 0 2px rgba(0,0,0,.7) inset,
    0 0 0 2px var(--ring),
    0 0 20px var(--glow),
    0 12px 22px rgba(0,0,0,.22);
}



/* custom name tooltip: icon bottom-right -> tooltip top-left */
.class-token::before{
  content: attr(data-name);
  position: absolute;
  /* anchor to the icon's bottom-right corner */
  top: 100%;
  left: 100%;

  /* small gap; tweak if you want them touching */
  --tip-gap: 4px;
  transform: translate(var(--tip-gap), var(--tip-gap)) scale(.98);

  padding: 4px 8px;
  white-space: nowrap;
  font-weight: 700;
  font-size: 12px;
  color: var(--ring);
  background: rgba(30,26,20,.96);
  border: 3px solid currentColor;
  border-radius: 6px;
  text-shadow: 0 1px 0 #000;
  box-shadow: 0 6px 16px rgba(0,0,0,.35);
  opacity: 0;
  pointer-events: none;
  transition: opacity .15s ease, transform .15s ease;
  z-index: 50;
}
.class-token:hover::before,
.class-token:focus::before{
  opacity: 1;
  transform: translate(var(--tip-gap), var(--tip-gap)) scale(1);
}

/* 1) Optional global fallbacks */
:root { --ring:#888; --glow:rgba(136,136,136,.45) }

/* 2) Your floating token styles — NO custom-property assignments here */
.class-token{
  /* REMOVE: --ring: ... ; --glow: ... */
  width:44px; height:44px; border:0; border-radius:999px;
  background:transparent; cursor:pointer; position:relative; display:block;
  box-shadow:
    0 0 0 2px rgba(0,0,0,.45) inset,
    0 0 0 2px rgba(255,255,255,.25),
    0 8px 16px rgba(0,0,0,.18);
  transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
}

.class-token:hover,.class-token:focus{
  transform:translateY(-4px); filter:brightness(1.05);
  box-shadow:
    0 0 0 2px rgba(0,0,0,.6) inset,
    0 0 0 2px var(--ring),          /* <- uses class color */
    0 0 18px var(--glow),
    0 14px 24px rgba(0,0,0,.22);
}

.class-token.is-active{
  box-shadow:
    0 0 0 2px rgba(0,0,0,.7) inset,
    0 0 0 2px var(--ring),
    0 0 20px var(--glow),
    0 12px 22px rgba(0,0,0,.22);
}

/* Tooltip uses the same variables */
.class-token::before{
  content: attr(data-name);
  position: absolute;
  top: 100%; left: 100%;
  --tip-gap: 6px;
  transform: translate(var(--tip-gap), var(--tip-gap)) scale(.98);
  padding: 4px 8px; white-space: nowrap; font-weight:700; font-size:12px;
  color: var(--ring);                 /* class-colored text */
  background: rgba(30,26,20,.96);
  border: 1px solid currentColor;     /* class-colored border */
  border-radius: 6px; text-shadow: 0 1px 0 #000;
  box-shadow: 0 6px 16px rgba(0,0,0,.35);
  opacity: 0; pointer-events:none; z-index:50;
  transition: opacity .15s ease, transform .15s ease;
}
.class-token:hover::before,.class-token:focus::before{
  opacity:1; transform: translate(var(--tip-gap), var(--tip-gap)) scale(1);
}

/* 3) Per-class colors — PLACE THESE AFTER the rules above */
.class-token.is-warrior{ --ring:#C79C6E; --glow:rgba(199,156,110,.60) }
.class-token.is-paladin{ --ring:#F58CBA; --glow:rgba(245,140,186,.60) }
.class-token.is-hunter { --ring:#ABD473; --glow:rgba(171,212,115,.60) }
.class-token.is-rogue  { --ring:#FFF569; --glow:rgba(255,245,105,.55) }
.class-token.is-priest { --ring:#FFFFFF; --glow:rgba(255,255,255,.55) }
.class-token.is-shaman { --ring:#0070DE; --glow:rgba(0,112,222,.55) }
.class-token.is-mage   { --ring:#40C7EB; --glow:rgba(64,199,235,.55) }
.class-token.is-warlock{ --ring:#8787ED; --glow:rgba(135,135,237,.55) }
.class-token.is-druid  { --ring:#FF7D0A; --glow:rgba(255,125,10,.55) }
.class-token.is-dk     { --ring:#C41F3B; --glow:rgba(196,31,59,.55) }



</style>

<?php
/* ----------------- helpers ----------------- */
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

/* ----------------- config/data ----------------- */
$expansion = isset($GLOBALS['expansion']) ? (int)$GLOBALS['expansion'] : 0; // 0 Classic, 1 TBC, 2 WotLK
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

/* ---------- CLASS SET NAMES ---------- */
/* T0 / Dungeon Set 1 */
$N['T0'] = [
  'Warrior'=>"Valor",'Paladin'=>"Lightforge",'Hunter'=>"Beaststalker",'Rogue'=>"Shadowcraft",
  'Priest'=>"Devout",'Shaman'=>"The Elements",'Mage'=>"Magister's",'Warlock'=>"Dreadmist",'Druid'=>"Wildheart"
];
/* T0.5 / Dungeon Set 2 */
$N['T0_5'] = [
  'Warrior'=>"Heroism",'Paladin'=>"Soulforge",'Hunter'=>"Beastmaster",'Rogue'=>"Darkmantle",
  'Priest'=>"Virtuous",'Shaman'=>"The Five Thunders",'Mage'=>"Sorcerer's",'Warlock'=>"Deathmist",'Druid'=>"Feralheart"
];
/* AQ40 (Tier 2.5) */
$N['T2_5'] = [
  'Warrior'=>"Conqueror's",'Paladin'=>"Avenger's",'Hunter'=>"Striker's",'Rogue'=>"Deathdealer's",
  'Priest'=>"Garments of the Oracle",'Shaman'=>"Stormcaller's",'Mage'=>"Enigma",'Warlock'=>"Doomcaller's",'Druid'=>"Genesis"
];

/* Classic raid tiers */
$N['T1'] = [
  'Warrior'=>"Battlegear of Might",'Paladin'=>"Lawbringer Armor",'Hunter'=>"Giantstalker Armor",
  'Rogue'=>"Nightslayer Armor",'Priest'=>"Vestments of Prophecy",'Shaman'=>"The Earthfury",
  'Mage'=>"Arcanist Regalia",'Warlock'=>"Felheart Raiment",'Druid'=>"Cenarion Raiment",
];
$N['T1_5'] = [
  'Warrior'=>"Vindicator's Battlegear",'Paladin'=>"Freethinker's Armor",'Hunter'=>"Predator's Armor",
  'Rogue'=>"Madcap's Outfit",'Priest'=>"Confessor's Raiment",'Shaman'=>"Augur's Regalia",
  'Mage'=>"Illusionist's Attire",'Warlock'=>"Demoniac's Threads",'Druid'=>"Haruspex's Garb",
];
$N['T2'] = [
  'Warrior'=>"Battlegear of Wrath",'Paladin'=>"Judgement Armor",'Hunter'=>"Dragonstalker Armor",
  'Rogue'=>"Bloodfang Armor",'Priest'=>"Vestments of Transcendence",'Shaman'=>"The Ten Storms",
  'Mage'=>"Netherwind Regalia",'Warlock'=>"Nemesis Raiment",'Druid'=>"Stormrage Raiment",
];
$N['T3'] = [
  'Warrior'=>"Dreadnaught Battlegear",'Paladin'=>"Redemption Armor",'Hunter'=>"Cryptstalker Armor",
  'Rogue'=>"Bonescythe Armor",'Priest'=>"Vestments of Faith",'Shaman'=>"The Earthshatterer",
  'Mage'=>"Frostfire Regalia",'Warlock'=>"Plagueheart Raiment",'Druid'=>"Dreamwalker Raiment",
];
$N['T2_25'] = [
  'Warrior'=>"Battlegear of Unyielding Strength",'Paladin'=>"Battlegear of Eternal Justice",
  'Hunter'=>"Trappings of the Unseen Path",'Rogue'=>"Emblems of Veiled Shadows",
  'Priest'=>"Finery of Infinite Wisdom",'Shaman'=>"Gift of the Gathering Storm",
  'Mage'=>"Trappings of Vaulted Secrets",'Warlock'=>"Implements of Unspoken Names",
  'Druid'=>"Symbols of Unending Life",
];

/* TBC T4–T6 (with spec splits) */
$N['T4'] = [
  'Druid'=>"Malorne Regalia / Harness / Raiment (Balance / Feral / Restoration)",
  'Hunter'=>"Demon Stalker Armor",
  'Mage'=>"Aldor Regalia",
  'Paladin'=>"Justicar Raiment / Armor / Battlegear (Holy / Protection / Retribution)",
  'Priest'=>"Incarnate Raiment / Regalia (Holy/Disc / Shadow)",
  'Rogue'=>"Netherblade",
  'Shaman'=>"Cyclone Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",
  'Warlock'=>"Voidheart Raiment",
  'Warrior'=>"Warbringer Battlegear / Armor (Arms/Fury / Protection)",
];
$N['T5'] = [
  'Druid'=>"Nordrassil Regalia / Harness / Raiment (Balance / Feral / Restoration)",
  'Hunter'=>"Rift Stalker Armor",
  'Mage'=>"Tirisfal Regalia",
  'Paladin'=>"Crystalforge Raiment / Armor / Battlegear (Holy / Protection / Retribution)",
  'Priest'=>"Avatar Raiment / Regalia (Holy/Disc / Shadow)",
  'Rogue'=>"Deathmantle",
  'Shaman'=>"Cataclysm Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",
  'Warlock'=>"Corruptor Raiment",
  'Warrior'=>"Destroyer Battlegear / Armor (Arms/Fury / Protection)",
];
$N['T6'] = [
  'Druid'=>"Thunderheart Regalia / Harness / Raiment (Balance / Feral / Restoration)",
  'Hunter'=>"Gronnstalker's Armor",
  'Mage'=>"Tempest Regalia",
  'Paladin'=>"Lightbringer Raiment / Armor / Battlegear (Holy / Protection / Retribution)",
  'Priest'=>"Vestments of Absolution / Absolution Regalia (Holy/Disc / Shadow)",
  'Rogue'=>"Slayer's Armor",
  'Shaman'=>"Skyshatter Regalia / Harness / Raiment (Elemental / Enhancement / Restoration)",
  'Warlock'=>"Malefic Raiment",
  'Warrior'=>"Onslaught Battlegear / Armor (Arms/Fury / Protection)",
];

/* WotLK T7–T10 */
$N['T7'] = [
  'Death Knight'=>"Scourgeborne Battlegear / Scourgeborne Plate (DPS / Tank)",
  'Druid'=>"Dreamwalker Regalia / Dreamwalker Battlegear / Dreamwalker Garb (Balance / Feral / Restoration)",
  'Hunter'=>"Cryptstalker Battlegear",'Mage'=>"Frostfire Regalia",
  'Paladin'=>"Redemption Regalia / Redemption Armor / Redemption Battlegear (Holy / Protection / Retribution)",
  'Priest'=>"Regalia of Faith / Garb of Faith (Shadow / Holy–Discipline)",
  'Rogue'=>"Bonescythe Battlegear",
  'Shaman'=>"Earthshatter Regalia / Earthshatter Battlegear / Earthshatter Garb (Elemental / Enhancement / Restoration)",
  'Warlock'=>"Plagueheart Garb",
  'Warrior'=>"Dreadnaught Battlegear / Dreadnaught Plate (Arms/Fury / Protection)",
];
$N['T8'] = [
  'Death Knight'=>"Darkruned Battlegear / Darkruned Plate (DPS / Tank)",
  'Druid'=>"Nightsong Regalia / Nightsong Battlegear / Nightsong Garb (Balance / Feral / Restoration)",
  'Hunter'=>"Scourgestalker Battlegear",'Mage'=>"Kirin Tor Garb",
  'Paladin'=>"Aegis Regalia / Aegis Armor / Aegis Battlegear (Holy / Protection / Retribution)",
  'Priest'=>"Sanctification Regalia / Sanctification Garb (Shadow / Holy–Discipline)",
  'Rogue'=>"Terrorblade Battlegear",
  'Shaman'=>"Worldbreaker Regalia / Worldbreaker Battlegear / Worldbreaker Garb (Elemental / Enhancement / Restoration)",
  'Warlock'=>"Deathbringer Garb",
  'Warrior'=>"Siegebreaker Battlegear / Siegebreaker Plate (Arms/Fury / Protection)",
];
$N['T9'] = [
  'Death Knight'=>"Thassarian's / Koltira's Battlegear / Plate (A / H)",
  'Druid'=>"Malfurion's / Runetotem's Regalia / Battlegear / Garb (A / H)",
  'Hunter'=>"Windrunner's Battlegear (Alliance / Horde)", 'Mage'=>"Khadgar's / Sunstrider's Regalia (A / H)",
  'Paladin'=>"Turalyon's / Liadrin's Regalia / Armor / Battlegear (A / H)",
  'Priest'=>"Velen's / Zabra's Regalia / Garb (A / H)",
  'Rogue'=>"VanCleef's / Garona's Battlegear (A / H)",
  'Shaman'=>"Nobundo's / Thrall's Regalia / Battlegear / Garb (A / H)",
  'Warlock'=>"Kel'Thuzad's / Gul'dan's Regalia (A / H)",
  'Warrior'=>"Wrynn's / Hellscream's Battlegear / Plate (A / H)",
];
$N['T10'] = [
  'Death Knight'=>"Scourgelord's Battlegear / Scourgelord's Plate (DPS / Tank)",
  'Druid'=>"Lasherweave Regalia / Lasherweave Battlegear / Lasherweave Garb (Balance / Feral / Restoration)",
  'Hunter'=>"Ahn'Kahar Blood Hunter's Battlegear",'Mage'=>"Bloodmage's Regalia",
  'Paladin'=>"Lightsworn Regalia / Lightsworn Armor / Lightsworn Battlegear (Holy / Protection / Retribution)",
  'Priest'=>"Crimson Acolyte's Regalia / Crimson Acolyte's Garb (Shadow / Holy–Discipline)",
  'Rogue'=>"Shadowblade's Battlegear",
  'Shaman'=>"Frost Witch's Regalia / Frost Witch's Battlegear / Frost Witch's Garb (Elemental / Enhancement / Restoration)",
  'Warlock'=>"Dark Coven's Regalia",
  'Warrior'=>"Ymirjar Lord's Battlegear / Ymirjar Lord's Plate (Arms/Fury / Protection)",
];

/* ---------- BLURBS (flavor + patch tag on its own line) ---------- */
$BLURB = [
  'T0'   => ['title'=>"Tier 0 (Dungeon Set 1)",'pieces'=>8,
             'text'=>"The first full class sets—forged from Stratholme, Scholomance, and Blackrock Spire. Your entry ticket to endgame. <span class='set-note'>(Patch 1.05)</span>"],
  'T0_5' => ['title'=>"Tier 0.5 (Dungeon Set 2)",'pieces'=>8,
             'text'=>"An epic upgrade questline that reforges T0; seal demons, summon Lord Valthalak, and claim stronger gear. <span class='set-note'>(Patch 1.10 “Storms of Azeroth”)</span>"],
  'T1'   => ['title'=>"Tier 1",'pieces'=>8,
             'text'=>"Molten Core’s lava-forged epics—Garr, Golemagg, and Rag himself hand you your first true raid set. <span class='set-note'>(Classic launch, Phase 1)</span>"],
  'T1_5' => ['title'=>"Tier 1.5 (Zul’Gurub)",'pieces'=>5,
             'text'=>"Zandalar’s 20-man sets with 2/3/5-piece bonuses—jungle-themed power from Hakkar’s empire. <span class='set-note'>(Patch 1.7 “Rise of the Blood God”)</span>"],
  'T2'   => ['title'=>"Tier 2",'pieces'=>8,
             'text'=>"Blackwing Lair tokens with helm from Onyxia and legs from Ragnaros—BWL crowns the Blackrock campaign. <span class='set-note'>(BWL Patch 1.6; Ony/MC Phase 1)</span>"],
  'T2_25' => ['title'=>"Tier 2.25 (Ahn’Qiraj 20)",'pieces'=>5,
             'text'=>"Cenarion Circle quest/token sets from Ruins of Ahn’Qiraj—class-themed 5-pieces earned via CC rep and raid drops. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T2_5' => ['title'=>"Tier 2.5 (Ahn’Qiraj 40)",'pieces'=>5,
             'text'=>"Qiraji tokens and Old-God motifs—a spec-leaning set from C’Thun’s citadel. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T3'   => ['title'=>"Tier 3 (Naxxramas 40)",'pieces'=>9,
             'text'=>"Kel’Thuzad’s necropolis: plague-etched armor plus a signature ring—Classic’s final test. <span class='set-note'>(Patch 1.11 “Shadow of the Necropolis” / Phase 6)</span>"],
  'T4'   => ['title'=>"Tier 4",'pieces'=>5,
             'text'=>"Karazhan, Gruul, and Magtheridon drop Champion/Hero/Defender tokens; redeem them in Shattrath. The first spec-split tier. <span class='set-note'>(TBC launch, Patches 2.0–2.0.3)</span>"],
  'T5'   => ['title'=>"Tier 5",'pieces'=>5,
             'text'=>"Serpentshrine Cavern & The Eye—Lady Vashj and Kael’thas guard the upgrades (and your key to T6 attunements). <span class='set-note'>(Available early; major retune in Patch 2.1)</span>"],
  'T6'   => ['title'=>"Tier 6",'pieces'=>8,
             'text'=>"Battle through Hyjal Summit and Black Temple for the core set; Sunwell adds belts/boots/bracers. Archimonde and Illidan await. <span class='set-note'>(Patch 2.1; 2.4 “Fury of the Sunwell” additions)</span>"],
  'T7'   => ['title'=>"Tier 7",'pieces'=>5,
             'text'=>"Naxxramas (revisited), Obsidian Sanctum, and Vault of Archavon—two tracks (Heroes’/Valorous). <span class='set-note'>(Wrath launch, Patch 3.0)</span>"],
  'T8'   => ['title'=>"Tier 8",'pieces'=>5,
             'text'=>"Ulduar—Titan vaults with in-fight hard modes; Valorous/Conqueror’s variants. <span class='set-note'>(Patch 3.1 “Secrets of Ulduar”)</span>"],
  'T9'   => ['title'=>"Tier 9",'pieces'=>5,
             'text'=>"Trial of the Crusader/Grand Crusader—faction-themed sets with trophy upgrades across normal/heroic. <span class='set-note'>(Patch 3.2 “Call of the Crusade”)</span>"],
  'T10'  => ['title'=>"Tier 10",'pieces'=>5,
             'text'=>"Icecrown Citadel—buy the base set with Emblems of Frost, then upgrade to (Heroic) Sanctified via Marks of Sanctification. <span class='set-note'>(Patch 3.3 “Fall of the Lich King”)</span>"],
];

/* ---------- ORDER ---------- */
$order = ['T0','T0_5','T1','T1_5','T2','T2_25','T2_5','T3'];
if ($maxTier >= 6)  { $order = array_merge($order, ['T4','T5','T6']); }
if ($maxTier >= 10) { $order = array_merge($order, ['T7','T8','T9','T10']); }

/* ---------- class bar ---------- */
echo '<div class="class-bar">';
foreach ($classes as $c){
  $href = 'index.php?n=server&sub=armorsets&class='.rawurlencode($c['name']);
  $src  = $iconBase.$iconPref.$c['slug'].$iconExt;
  $active = (strcasecmp($selectedClass,$c['name'])===0) ? ' is-active' : '';
echo '<a class="class-token '.$c['css'].$active.'" '.
     'href="'.$href.'" '.
     'aria-label="'.htmlspecialchars($c['name']).'" '.
     'data-name="'.htmlspecialchars($c['name']).'">'.
     '<img src="'.$src.'" alt="'.htmlspecialchars($c['name']).'"></a>';



}
echo '</div>';

/* If no class, nudge and stop */
if ($selectedClass === '') {
  echo '<div class="set-group"><div class="set-subtitle">Choose a class above to see their Dungeon & Tier sets.</div></div>';
  builddiv_end(); return;
}

/* ---------- content ---------- */
echo '<div class="set-group"><div class="set-title">Dungeon & Tier Sets</div>';

foreach ($order as $key) {
  if (!isset($BLURB[$key])) {
    echo '<div class="set-desc set-note">No data block found for '.$key.'.</div>';
    continue;
  }
  if (preg_match('/^T(\d+)/',$key,$m) && (int)$m[1] > $maxTier) continue;

  $title   = $BLURB[$key]['title'];
  $pieces  = (int)$BLURB[$key]['pieces'];
  $text    = $BLURB[$key]['text'];
  $pairs   = (!empty($N[$key][$selectedClass])) ? armor_set_variants($N[$key][$selectedClass]) : [];
  $tierLbl = trim(preg_replace('/\s*\(.*\)$/','',$title));

  echo '<ul class="variant-tight">';
  if ($pairs) {
    $first = true;
    foreach ($pairs as $p) {
      $nm = htmlspecialchars($p['name']);
      $rl = $p['role'] !== '' ? ' — '.htmlspecialchars($p['role']) : '';
      $prefix = $first ? htmlspecialchars($tierLbl) : '';
      $suffix = $first ? ' <span class="note-inline">('.$pieces.' pieces)</span>' : '';
      echo '<li class="v-row"><span class="v-prefix">'.$prefix.'</span>'
         . '<span class="v-body"><b>'.$nm.'</b>'.$rl.$suffix.'</span></li>';
      $first = false;
    }
  } else {
    $nm = htmlspecialchars($N[$key][$selectedClass] ?? '');
    echo '<li class="v-row"><span class="v-prefix">'.htmlspecialchars($tierLbl).'</span>'
       . '<span class="v-body"><b>'.$nm.'</b> <span class="note-inline">('.$pieces.' pieces)</span></span></li>';
  }
  echo '</ul>';

  echo '<div class="set-desc">'.$text.'</div><br />';
}
echo '</div>';

builddiv_end();
?>
