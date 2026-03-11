<style>
.guild-table .header,
.guild-table .row {
  grid-template-columns: minmax(260px, 2fr) 64px minmax(180px, 1.2fr) 90px 90px 90px minmax(220px, 1.8fr);
}
.guild-table .col:nth-child(1),
.guild-table .col:nth-child(3),
.guild-table .col:nth-child(7) {
  text-align: left;
  padding: 0 12px;
}
.guild-table .guild-name a,
.guild-table .leader a {
  font-weight: bold;
}
.guild-table .faction-col {
  display: flex;
  justify-content: center;
  align-items: center;
}
.guild-table .faction-col img {
  width: 34px;
  height: 34px;
  object-fit: contain;
}
.guild-table .motd {
  color: #b6a57c;
  font-size: 0.95rem;
  white-space: normal;
  line-height: 1.2;
}
.guild-search-wrap {
  max-width: 1000px;
  margin: 12px auto 16px;
}
.guild-summary {
  max-width: 1000px;
  margin: 0 auto 8px;
  color: #ffcc66;
  font-weight: bold;
}
@media (max-width: 900px) {
  .guild-table .header,
  .guild-table .row {
    grid-template-columns: minmax(220px, 2fr) 56px minmax(160px, 1.2fr) 80px 80px minmax(200px, 1.6fr);
  }
  .guild-table .header .col:nth-child(6),
  .guild-table .row .col:nth-child(6) {
    display: none;
  }
}
@media (max-width: 700px) {
  .guild-table .header,
  .guild-table .row {
    grid-template-columns: minmax(200px, 2fr) 50px 70px 70px minmax(180px, 1.4fr);
  }
  .guild-table .header .col:nth-child(2),
  .guild-table .row .col:nth-child(2),
  .guild-table .header .col:nth-child(4),
  .guild-table .row .col:nth-child(4),
  .guild-table .header .col:nth-child(5),
  .guild-table .row .col:nth-child(5) {
    display: none;
  }
}
</style>

<?php
builddiv_start(1, 'Guilds', 1);

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
$realmDB = $realmMap[$realmId]['chars'];
$armoryRealm = $realmMap[$realmId]['label'];

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$items_per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$search = trim($_GET['search'] ?? '');

$guilds = $DB->select("
  SELECT
    g.guildid,
    g.name,
    g.motd,
    leader.guid AS leader_guid,
    leader.name AS leader_name,
    leader.race AS leader_race,
    leader.class AS leader_class,
    COUNT(gm.guid) AS member_count,
    COALESCE(AVG(c.level), 0) AS avg_level,
    COALESCE(MAX(c.level), 0) AS max_level
  FROM {$realmDB}.guild g
  LEFT JOIN {$realmDB}.guild_member gm ON g.guildid = gm.guildid
  LEFT JOIN {$realmDB}.characters c ON gm.guid = c.guid
  LEFT JOIN {$realmDB}.characters leader ON g.leaderguid = leader.guid
  GROUP BY g.guildid, g.name, g.motd, leader.guid, leader.name, leader.race, leader.class
  ORDER BY member_count DESC, g.name ASC
");

if ($search !== '') {
    $needle = strtolower($search);
    $guilds = array_values(array_filter($guilds, function ($guild) use ($needle, $allianceRaces, $classNames) {
        $factionName = in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde';
        $leaderClass = $classNames[(int)($guild['leader_class'] ?? 0)] ?? 'Unknown';
        $haystack = strtolower(implode(' ', [
            $guild['name'] ?? '',
            $guild['leader_name'] ?? '',
            $guild['motd'] ?? '',
            (string)($guild['member_count'] ?? ''),
            (string)round((float)($guild['avg_level'] ?? 0)),
            (string)($guild['max_level'] ?? ''),
            $factionName,
            $leaderClass,
        ]));
        return strpos($haystack, $needle) !== false;
    }));
}

$count = count($guilds);
$pnum = max(1, (int)ceil($count / $items_per_page));
if ($p > $pnum) $p = $pnum;
if ($p < 1) $p = 1;
$offset = ($p - 1) * $items_per_page;
$guildsPage = array_slice($guilds, $offset, $items_per_page);
$resultStart = $count > 0 ? $offset + 1 : 0;
$resultEnd = min($offset + $items_per_page, $count);
$baseUrl = "index.php?n=server&sub=guilds&realm={$realmId}&per_page={$items_per_page}";
if ($search !== '') $baseUrl .= '&search=' . urlencode($search);
?>

<div class="guild-search-wrap">
  <form method="get" class="modern-content">
    <input type="hidden" name="n" value="server">
    <input type="hidden" name="sub" value="guilds">
    <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
    <input type="hidden" name="p" value="1">
    <input type="hidden" name="per_page" value="<?php echo $items_per_page; ?>">
    <input
      type="text"
      id="commandSearch"
      name="search"
      value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search guild name, leader, faction, class, MOTD..."
      autocomplete="off"
    >
  </form>
</div>

<div class="guild-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo (int)$count; ?> guilds</div>

<?php if ($pnum > 1): ?>
  <div class="pagination-controls">
    <div class="page-links">
      <?php echo compact_paginate($p, $pnum, $baseUrl); ?>
    </div>
  </div>
<?php endif; ?>

<div class="wow-table guild-table">
  <div class="header">
    <div class="col sortable" data-sort="guild">Guild</div>
    <div class="col sortable" data-sort="faction">Faction</div>
    <div class="col sortable" data-sort="leader">Leader</div>
    <div class="col sortable" data-sort="members">Members</div>
    <div class="col sortable" data-sort="avg">Avg Lvl</div>
    <div class="col sortable" data-sort="max">Max Lvl</div>
    <div class="col sortable" data-sort="motd">Message Of The Day</div>
  </div>

  <?php if ($guildsPage): ?>
    <?php foreach ($guildsPage as $guild): ?>
      <?php
        $factionName = in_array((int)$guild['leader_race'], $allianceRaces, true) ? 'Alliance' : 'Horde';
        $factionSlug = strtolower($factionName);
        $leaderClassSlug = strtolower(str_replace(' ', '', $classNames[(int)$guild['leader_class']] ?? 'unknown'));
      ?>
      <div class="row">
        <div class="col guild-name">
          <a href="index.php?n=server&sub=guild&guildid=<?php echo (int)$guild['guildid']; ?>&realm=<?php echo $realmId; ?>">
            <?php echo htmlspecialchars($guild['name']); ?>
          </a>
        </div>
        <div class="col faction-col">
          <img src="templates/offlike/images/modern/logo-<?php echo $factionSlug; ?>.png" alt="<?php echo $factionName; ?>" title="<?php echo $factionName; ?>">
        </div>
        <div class="col leader class-<?php echo $leaderClassSlug; ?>">
          <?php if (!empty($guild['leader_name'])): ?>
            <a href="armory/index.php?searchType=profile&character=<?php echo urlencode($guild['leader_name']); ?>&realm=<?php echo urlencode($armoryRealm); ?>">
              <?php echo htmlspecialchars($guild['leader_name']); ?>
            </a>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
        <div class="col"><?php echo (int)$guild['member_count']; ?></div>
        <div class="col"><?php echo (int)round((float)$guild['avg_level']); ?></div>
        <div class="col"><?php echo (int)$guild['max_level']; ?></div>
        <div class="col motd"><?php echo trim((string)$guild['motd']) !== '' ? htmlspecialchars($guild['motd']) : '-'; ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="row">
      <div class="col" style="grid-column:1/-1;text-align:center;color:#888;">No guilds found.</div>
    </div>
  <?php endif; ?>
</div>

<?php if ($pnum > 1): ?>
  <div class="pagination-controls">
    <div class="page-links">
      <?php echo compact_paginate($p, $pnum, $baseUrl); ?>
    </div>
  </div>
<?php endif; ?>

<?php builddiv_end(); ?>



