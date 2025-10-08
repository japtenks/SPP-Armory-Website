<?php
/* ---------- Race Page: Dark Iron Dwarf ---------- */
if (!defined('Armory')) { exit; }

// Minimal Armory-style race template

$race = [
  'name' => 'Dark Iron Dwarf',
  'faction' => 'Alliance',
  'icon' => 'achievement_character_dwarf_male', // example icon from wow icons
  'description' => "Known for their fiery tempers and iron will, the Dark Iron dwarves are a proud and
                   resilient people who have emerged from the shadows of their tumultuous past.",
  'history' => "The Dark Iron clan long warred with their Bronzebeard and Wildhammer kin. After
                centuries of strife and subjugation to Ragnaros, they were finally freed during
                the Cataclysm and pledged themselves to the Alliance. Led by Moira Thaurissan,
                they now seek redemption and a place among their kin.",
  'racial_traits' => [
    ['name'=>'Fireblood', 'desc'=>'Removes poison, disease, curse, magic, and bleed effects and increases primary stat based on effects removed.'],
    ['name'=>'Forged in Flames', 'desc'=>'Takes reduced Physical damage.'],
    ['name'=>'Mass Production', 'desc'=>'Blacksmithing skill increased. Blacksmithing speed slightly increased.'],
    ['name'=>'Dungeon Delver', 'desc'=>'Increased movement speed indoors.'],
    ['name'=>'Mole Machine', 'desc'=>'Can summon a Mole Machine to travel to explored areas.']
  ],
  'available_classes' => [
    'Death Knight','Hunter','Mage','Monk','Paladin','Priest','Rogue','Shaman','Warlock','Warrior'
  ]
];

?>

<div class="race-page">
  <div class="race-header">
    <img src="armory/images/icons/64x64/<?php echo $race['icon']; ?>.png" alt="<?php echo $race['name']; ?>">
    <h2><?php echo $race['name']; ?> <span class="faction faction-alliance"><?php echo $race['faction']; ?></span></h2>
  </div>

  <div class="race-description"><?php echo $race['description']; ?></div>
  <div class="race-history"><h3>History</h3><p><?php echo $race['history']; ?></p></div>

  <div class="race-racials">
    <h3>Racial Traits</h3>
    <ul>
      <?php foreach ($race['racial_traits'] as $rt): ?>
        <li><b><?php echo $rt['name']; ?>:</b> <?php echo $rt['desc']; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="race-classes">
    <h3>Available Classes</h3>
    <ul class="class-list">
      <?php foreach ($race['available_classes'] as $cls): ?>
        <li><?php echo $cls; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<style>
.race-page { margin:20px; font-family:Tahoma, sans-serif; color:#ddd; }
.race-header { display:flex; align-items:center; gap:12px; margin-bottom:10px; }
.race-header img { width:48px; height:48px; border-radius:6px; }
.race-header h2 { font-size:22px; color:#f1f6ff; }
.faction { font-size:14px; margin-left:6px; padding:2px 6px; border-radius:4px; }
.faction-alliance { background:#2b4c9a; color:#fff; }
.race-description { font-size:14px; margin:6px 0 12px; color:#ccc; }
.race-history h3, .race-racials h3, .race-classes h3 { color:#ffd100; margin-bottom:4px; }
.race-racials ul, .race-classes ul { list-style:none; padding-left:0; }
.race-racials li, .race-classes li { margin:4px 0; }
</style>
