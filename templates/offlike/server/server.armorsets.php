<img src="<?php echo $currtmp; ?>/images/armorsets.jpg" />
<br />
<?php builddiv_start(1, $lang['armorsets2']) ?>

<style>
  #cnt{width:850px;margin-left:0}

  /* Top class selector (single row) */
  .class-bar{
    display:flex;flex-wrap:wrap;gap:10px;margin:6px 0 16px;justify-content:flex-start
  }
  .class-token{
    --ring:#888;--glow:rgba(136,136,136,.55);
    width:40px;height:40px;border-radius:999px;border:0;background:transparent;cursor:pointer;
    box-shadow:0 0 0 2px rgba(0,0,0,.45) inset,0 0 0 2px rgba(255,255,255,.2);
    transition:transform .12s ease,box-shadow .12s ease,filter .12s ease
  }
  .class-token img{width:100%;height:100%;border-radius:999px;display:block;object-fit:cover}
  .class-token:hover,.class-token:focus{
    transform:translateY(-1px);filter:brightness(1.05);
    box-shadow:0 0 0 2px rgba(0,0,0,.6) inset,0 0 0 2px var(--ring),0 0 16px var(--glow)
  }
  .class-token.is-active{
    box-shadow:0 0 0 2px rgba(0,0,0,.7) inset,0 0 0 2px var(--ring),0 0 18px var(--glow)
  }
  .is-warrior{--ring:#C79C6E;--glow:rgba(199,156,110,.6)}
  .is-paladin{--ring:#F58CBA;--glow:rgba(245,140,186,.6)}
  .is-hunter {--ring:#ABD473;--glow:rgba(171,212,115,.6)}
  .is-rogue  {--ring:#FFF569;--glow:rgba(255,245,105,.55)}
  .is-priest {--ring:#FFFFFF;--glow:rgba(255,255,255,.55)}
  .is-shaman {--ring:#0070DE;--glow:rgba(0,112,222,.55)}
  .is-mage   {--ring:#40C7EB;--glow:rgba(64,199,235,.55)}
  .is-warlock{--ring:#8787ED;--glow:rgba(135,135,237,.55)}
  .is-druid  {--ring:#FF7D0A;--glow:rgba(255,125,10,.55)}
  .is-dk     {--ring:#C41F3B;--glow:rgba(196,31,59,.55)} /* WotLK */

  /* Section headings + copy */
  .set-group{margin:20px 0 10px}
  .set-title{font-size:20px;font-weight:700;color:#6b2d1f;margin:14px 0 6px}
  .set-subtitle{font-weight:700;color:#7a3f28}
  .set-note{color:#7b6a52}
</style>

<?php
// -------- CONFIG / DATA --------
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

/* ---------- CLASS SET NAMES (extend your existing $N) ---------- */
// Tier 0 / Dungeon Set 1 (same as your old DS1 map)
$N['T0'] = [
  'Warrior'=>'Valor','Paladin'=>'Lightforge','Hunter'=>'Beaststalker','Rogue'=>'Shadowcraft',
  'Priest'=>'Devout','Shaman'=>'The Elements','Mage'=>"Magister's",'Warlock'=>'Dreadmist','Druid'=>'Wildheart'
];
// Tier 0.5 / Dungeon Set 2 (same as your old DS2 map)
$N['T0_5'] = [
  'Warrior'=>'Heroism','Paladin'=>'Soulforge','Hunter'=>'Beastmaster','Rogue'=>'Darkmantle',
  'Priest'=>'Virtuous','Shaman'=>'The Five Thunders','Mage'=>"Sorcerer\'s",'Warlock'=>'Deathmist','Druid'=>'Feralheart'
];
// Tier 2.5 / AQ40 token sets (per-class names are well-known)
$N['T2_5'] = [
  'Warrior'=>"Conqueror's",'Paladin'=>"Avenger's",'Hunter'=>"Striker's",'Rogue'=>"Deathdealer's",
  'Priest'=>'Garments of the Oracle','Shaman'=>"Stormcaller's",'Mage'=>'Enigma','Warlock'=>"Doomcaller's",'Druid'=>'Genesis'
];
// T1 – Molten Core
$N['T1'] = [
  'Warrior'=>'Might','Paladin'=>'Lawbringer','Hunter'=>'Giantstalker','Rogue'=>'Nightslayer',
  'Priest'=>'Prophecy','Shaman'=>'Earthfury','Mage'=>'Arcanist','Warlock'=>'Felheart','Druid'=>'Cenarion'
];
// T2 – Blackwing/Ony/Rag
$N['T2'] = [
  'Warrior'=>'Wrath','Paladin'=>'Judgement','Hunter'=>'Dragonstalker','Rogue'=>'Bloodfang',
  'Priest'=>'Transcendence','Shaman'=>'Ten Storms','Mage'=>'Netherwind','Warlock'=>'Nemesis','Druid'=>'Stormrage'
];
// T3 – Naxxramas 40
$N['T3'] = [
  'Warrior'=>'Dreadnaught','Paladin'=>'Redemption','Hunter'=>'Cryptstalker','Rogue'=>'Bonescythe',
  'Priest'=>'Vestments of Faith','Shaman'=>'The Earthshatterer','Mage'=>'Frostfire','Warlock'=>'Plagueheart','Druid'=>'Dreamwalker'
];
// T4 – Karazhan/Gruul/Mag
$N['T4'] = [
  'Warrior'=>'Warbringer','Paladin'=>'Justicar','Hunter'=>'Demon Stalker','Rogue'=>'Netherblade',
  'Priest'=>'Incarnate','Shaman'=>'Cyclone','Mage'=>'Aldor','Warlock'=>'Voidheart','Druid'=>'Malorne'
];
// T5 – SSC/The Eye
$N['T5'] = [
  'Warrior'=>'Destroyer','Paladin'=>'Crystalforge','Hunter'=>'Rift Stalker','Rogue'=>'Deathmantle',
  'Priest'=>'Avatar','Shaman'=>'Cataclysm','Mage'=>'Tirisfal','Warlock'=>'Corruptor','Druid'=>'Nordrassil'
];
// T6 – Hyjal/BT (+Sunwell off-pieces)
$N['T6'] = [
  'Warrior'=>'Onslaught','Paladin'=>'Lightbringer','Hunter'=>'Gronnstalker','Rogue'=>"Slayer's",
  'Priest'=>'Absolution','Shaman'=>'Skyshatter','Mage'=>'Tempest','Warlock'=>'Malefic','Druid'=>'Thunderheart'
];


/* ---------- BLURBS (concise + spicy, with raid + patch tag) ---------- */
$BLURB = [
  // Classic – 5 mans and 20/40 mans
  'T0'   => ['title'=>'Tier 0 (Dungeon Set 1)','pieces'=>8,
             'text'=>"The first full class sets—earned in Strat, Scholo, and Blackrock Spire. Starter endgame for fresh 60s. <span class='set-note'>(Patch 1.05)</span>"],
  'T0_5' => ['title'=>'Tier 0.5 (Dungeon Set 2)','pieces'=>8,
             'text'=>"A lengthy upgrade questline that reforges T0 into tougher kit, ending with Lord Valthalak. Great endgame for non-raiders. <span class='set-note'>(Patch 1.10 “Storms of Azeroth”)</span>"],
  'T1'   => ['title'=>'Tier 1','pieces'=>8,
             'text'=>"All drops from Molten Core—Ragnaros’ forge fire and elemental bosses turn out the first epic raid sets. <span class='set-note'>(Classic launch, Phase 1)</span>"],
  'T1_5' => ['title'=>'Tier 1.5 (Zul’Gurub)','pieces'=>5,
             'text'=>"ZG’s 20-man class sets with 2/3/5-piece bonuses, themed around the Zandalar Tribe. <span class='set-note'>(Patch 1.7 “Rise of the Blood God”)</span>"],
  'T2'   => ['title'=>'Tier 2','pieces'=>8,
             'text'=>"Primarily Blackwing Lair tokens; helm from Onyxia, legs from Ragnaros—keeps MC/Ony relevant into BWL. <span class='set-note'>(BWL in Patch 1.6; MC/Ony Phase 1)</span>"],
  'T2_5' => ['title'=>'Tier 2.5 (Ahn’Qiraj 40)','pieces'=>5,
             'text'=>"AQ40 token sets with spec-flavored itemization and Old-God styling. <span class='set-note'>(Patch 1.9 “The Gates of Ahn’Qiraj” / Phase 5)</span>"],
  'T3'   => ['title'=>'Tier 3 (Naxxramas 40)','pieces'=>9,
             'text'=>"Kel’Thuzad’s necropolis—plague-etched armor including a signature ring; the pinnacle of Classic progression. <span class='set-note'>(Patch 1.11 “Shadow of the Necropolis” / Phase 6)</span>"],

  // The Burning Crusade – 10/25 mans
  'T4'   => ['title'=>'Tier 4','pieces'=>5,
             'text'=>"Tokens from Karazhan, Gruul, and Magtheridon; redeemed in Shattrath to form the first Outland sets. <span class='set-note'>(TBC launch, Patches 2.0–2.0.3)</span>"],
  'T5'   => ['title'=>'Tier 5','pieces'=>5,
             'text'=>"Serpentshrine Cavern & The Eye—Lady Vashj and Kael’thas drop the keys to your next evolution. <span class='set-note'>(Available early TBC; major tuning in Patch 2.1)</span>"],
  'T6'   => ['title'=>'Tier 6','pieces'=>8,
             'text'=>"Hyjal Summit & Black Temple form the core; Sunwell finishes belts/boots/bracers. Face Archimonde and Illidan before the Sunwell finale. <span class='set-note'>(Patch 2.1; additions in 2.4 “Fury of the Sunwell”)</span>"],

  // Wrath of the Lich King – 10/25 mans with normal/heroic tracks
  'T7'   => ['title'=>'Tier 7','pieces'=>5,
             'text'=>"Naxxramas (revamp), Obsidian Sanctum, and Vault of Archavon—Heroes’/Valorous tracks split across 10/25. <span class='set-note'>(Wrath launch, Patch 3.0)</span>"],
  'T8'   => ['title'=>'Tier 8','pieces'=>5,
             'text'=>"Ulduar—Titan halls, hard-modes toggled in-fight; Valorous/Conqueror’s variants. <span class='set-note'>(Patch 3.1 “Secrets of Ulduar”)</span>"],
  'T9'   => ['title'=>'Tier 9','pieces'=>5,
             'text'=>"Trial of the Crusader/Grand Crusader—trophy-based upgrades with faction-flavored visuals, normal/heroic paths. <span class='set-note'>(Patch 3.2 “Call of the Crusade”)</span>"],
  'T10'  => ['title'=>'Tier 10','pieces'=>5,
             'text'=>"Icecrown Citadel—base set from Emblems of Frost; upgrade to Sanctified and Heroic Sanctified via Marks of (Heroic) Sanctification. <span class='set-note'>(Patch 3.3 “Fall of the Lich King”)</span>"],
];

/* ---------- ORDER (Classic → TBC → WotLK; expansion-aware) ---------- */
// ORDER (Classic → TBC → WotLK; expansion-aware)
$order = ['T0','T0_5','T1','T1_5','T2','T2_5','T3'];
if ($maxTier >= 6)  { $order = array_merge($order, ['T4','T5','T6']); }
if ($maxTier >= 10) { $order = array_merge($order, ['T7','T8','T9','T10']); }

// -------- TOP CLASS BAR --------
echo '<div class="class-bar">';
foreach ($classes as $c){
  $href = 'index.php?n=server&sub=armorsets&class='.rawurlencode($c['name']);
  $src  = $iconBase.$iconPref.$c['slug'].$iconExt;
  $active = (strcasecmp($selectedClass,$c['name'])===0) ? ' is-active' : '';
  echo '<a class="class-token '.$c['css'].$active.'" href="'.$href.'" title="'.htmlspecialchars($c['name']).'">'
      .'<img src="'.$src.'" alt="'.htmlspecialchars($c['name']).'"></a>';
}
echo '</div>';

// If no class selected, friendly nudge
if ($selectedClass === '') {
  echo '<div class="set-group"><span class="set-subtitle">'
     .'Choose a class above to see their Dungeon, Tier, and Faction sets.</span></div>';
  builddiv_end(); return;
}

// -------- OUTPUT FOR THE SELECTED CLASS --------
echo '<div class="set-group"><div class="set-subtitle">'
   .htmlspecialchars($selectedClass).' armor sets are grouped below. Click the tokens above to switch classes.'
   .'</div></div>';

// Dungeon + Tier
echo '<div class="set-group"><div class="set-title">Dungeon & Tier Sets</div>';
foreach ($order as $key){
  if (!isset($BLURB[$key])) continue;
  // Show only tiers up to expansion cap
  if (preg_match('/^T(\d+)/',$key,$m) && (int)$m[1] > $maxTier) continue;

  $setName = isset($N[$key][$selectedClass]) ? $N[$key][$selectedClass] : '';
// inside your output loop
$title  = $BLURB[$key]['title'];
$pieces = (int)$BLURB[$key]['pieces'];
$text   = $BLURB[$key]['text'];
echo '<div class="set-subtitle">'.$title
   . (isset($N[$key][$selectedClass]) ? ' — '.htmlspecialchars($N[$key][$selectedClass]) : '')
   . ' <span class="set-note">('.$pieces.' pieces)</span></div>';
echo '<div>'.$text.'</div><br />';

}
echo '</div>';


builddiv_end();
?>
