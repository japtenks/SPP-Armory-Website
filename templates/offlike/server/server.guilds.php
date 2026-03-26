<style>
.guild-table .header .col a {
  color: inherit;
  text-decoration: none;
  font-weight: 700;
}
.guild-table .header .col a:hover {
  color: #fff1b0;
}
.guild-table .header .col a.is-active {
  color: #fff1b0;
}
.guild-table .header,
.guild-table .row {
  grid-template-columns: minmax(260px, 2fr) 64px minmax(180px, 1.2fr) 90px 90px 90px 95px 95px;
}
.guild-table .col:nth-child(1),
.guild-table .col:nth-child(3) {
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
    grid-template-columns: minmax(220px, 2fr) 56px minmax(160px, 1.2fr) 80px 80px 80px 90px;
  }
  .guild-table .header .col:nth-child(8),
  .guild-table .row .col:nth-child(8) {
    display: none;
  }
}
@media (max-width: 700px) {
  .guild-table .header,
  .guild-table .row {
    grid-template-columns: minmax(200px, 2fr) 50px 70px 70px 70px;
  }
  .guild-table .header .col:nth-child(2),
  .guild-table .row .col:nth-child(2),
  .guild-table .header .col:nth-child(4),
  .guild-table .row .col:nth-child(4),
  .guild-table .header .col:nth-child(5),
  .guild-table .row .col:nth-child(5),
  .guild-table .header .col:nth-child(7),
  .guild-table .row .col:nth-child(7),
  .guild-table .header .col:nth-child(8),
  .guild-table .row .col:nth-child(8) {
    display: none;
  }
}
</style>

<?php
builddiv_start(1, 'Guilds', 1);

$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');

if (!function_exists('spp_guilds_sort_compare')) {
    function spp_guilds_sort_compare(array $left, array $right, $sortBy, $sortDir) {
        $direction = strtoupper($sortDir) === 'ASC' ? 1 : -1;

        switch ($sortBy) {
            case 'guild':
                $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                if ($comparison === 0) {
                    $comparison = ((int)($left['guildid'] ?? 0) <=> (int)($right['guildid'] ?? 0));
                }
                break;
            case 'faction':
                $comparison = strcasecmp((string)($left['faction_name'] ?? ''), (string)($right['faction_name'] ?? ''));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'leader':
                $comparison = strcasecmp((string)($left['leader_name'] ?? ''), (string)($right['leader_name'] ?? ''));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'members':
                $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'avg':
                $comparison = ((float)($left['avg_level'] ?? 0) <=> (float)($right['avg_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                }
                break;
            case 'max':
                $comparison = ((int)($left['max_level'] ?? 0) <=> (int)($right['max_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((float)($left['avg_level'] ?? 0) <=> (float)($right['avg_level'] ?? 0));
                }
                break;
            case 'avgilvl':
                $comparison = ((float)($left['avg_item_level'] ?? 0) <=> (float)($right['avg_item_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                }
                break;
            case 'maxilvl':
                $comparison = ((float)($left['max_item_level'] ?? 0) <=> (float)($right['max_item_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((float)($left['avg_item_level'] ?? 0) <=> (float)($right['avg_item_level'] ?? 0));
                }
                break;
            default:
                $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
        }

        return $comparison * $direction;
    }
}

if (!function_exists('spp_guilds_sort_url')) {
    function spp_guilds_sort_url($realmId, $perPage, $search, $sortBy, $currentSortBy, $currentSortDir) {
        $nextSortDir = ($currentSortBy === $sortBy && strtoupper($currentSortDir) === 'ASC') ? 'DESC' : 'ASC';
        $url = 'index.php?n=server&sub=guilds&realm=' . (int)$realmId
            . '&per_page=' . (int)$perPage
            . '&sort=' . rawurlencode($sortBy)
            . '&dir=' . rawurlencode($nextSortDir);
        if ($search !== '') {
            $url .= '&search=' . rawurlencode($search);
        }
        return $url;
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
$realmWorldDB = $realmMap[$realmId]['world'];
$armoryRealm = spp_get_armory_realm_name($realmId) ?? '';

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$items_per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$search = trim($_GET['search'] ?? '');
$sortBy = strtolower(trim($_GET['sort'] ?? 'members'));
$sortDir = strtoupper(trim($_GET['dir'] ?? 'DESC'));
$allowedSorts = array('guild', 'faction', 'leader', 'members', 'avg', 'max', 'avgilvl', 'maxilvl');
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'members';
}
if ($sortDir !== 'ASC' && $sortDir !== 'DESC') {
    $sortDir = 'DESC';
}

$charPdo = spp_get_pdo('chars', $realmId);
$guilds = $charPdo->query("
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
  FROM guild g
  LEFT JOIN guild_member gm ON g.guildid = gm.guildid
  LEFT JOIN characters c ON gm.guid = c.guid
  LEFT JOIN characters leader ON g.leaderguid = leader.guid
  GROUP BY g.guildid, g.name, g.motd, leader.guid, leader.name, leader.race, leader.class
  ORDER BY member_count DESC, g.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$guildIds = array_values(array_filter(array_map(static function ($guild) {
    return (int)($guild['guildid'] ?? 0);
}, is_array($guilds) ? $guilds : array())));
$guildGearStats = array();

if (!empty($guildIds)) {
    $guildIdSql = implode(',', $guildIds);
    try {
        $gearRows = $charPdo->query("
          SELECT
            gm.guildid,
            c.guid,
            ROUND(AVG(it.ItemLevel), 1) AS avg_item_level
          FROM guild_member gm
          INNER JOIN characters c ON c.guid = gm.guid
          INNER JOIN character_inventory ci ON ci.guid = c.guid
          INNER JOIN {$realmWorldDB}.item_template it ON it.entry = ci.item_template
          WHERE gm.guildid IN ({$guildIdSql})
            AND ci.bag = 0
            AND ci.slot BETWEEN 0 AND 18
            AND ci.slot NOT IN (3, 18)
            AND ci.item_template > 0
          GROUP BY gm.guildid, c.guid
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (is_array($gearRows)) {
            foreach ($gearRows as $gearRow) {
                $guildIdKey = (int)($gearRow['guildid'] ?? 0);
                $memberAvg = (float)($gearRow['avg_item_level'] ?? 0);
                if ($guildIdKey <= 0 || $memberAvg <= 0) {
                    continue;
                }

                if (!isset($guildGearStats[$guildIdKey])) {
                    $guildGearStats[$guildIdKey] = array(
                        'total' => 0.0,
                        'count' => 0,
                        'max' => 0.0,
                    );
                }

                $guildGearStats[$guildIdKey]['total'] += $memberAvg;
                $guildGearStats[$guildIdKey]['count']++;
                if ($memberAvg > $guildGearStats[$guildIdKey]['max']) {
                    $guildGearStats[$guildIdKey]['max'] = $memberAvg;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[guilds] Failed loading guild gear stats: ' . $e->getMessage());
    }
}

if (is_array($guilds)) {
    foreach ($guilds as &$guild) {
        $guildIdKey = (int)($guild['guildid'] ?? 0);
        $gearStat = $guildGearStats[$guildIdKey] ?? null;
        $guild['avg_item_level'] = (!empty($gearStat['count'])) ? round($gearStat['total'] / $gearStat['count'], 1) : 0;
        $guild['max_item_level'] = (!empty($gearStat['max'])) ? round($gearStat['max'], 1) : 0;
        $guild['faction_name'] = in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde';
    }
    unset($guild);
}

if ($search !== '') {
    $needle = strtolower($search);
    $guilds = array_values(array_filter($guilds, function ($guild) use ($needle, $allianceRaces, $classNames) {
        $factionName = $guild['faction_name'] ?? (in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde');
        $leaderClass = $classNames[(int)($guild['leader_class'] ?? 0)] ?? 'Unknown';
        $haystack = strtolower(implode(' ', [
            $guild['name'] ?? '',
            $guild['leader_name'] ?? '',
            $guild['motd'] ?? '',
            (string)($guild['member_count'] ?? ''),
            (string)round((float)($guild['avg_level'] ?? 0)),
            (string)($guild['max_level'] ?? ''),
            (string)($guild['avg_item_level'] ?? ''),
            (string)($guild['max_item_level'] ?? ''),
            $factionName,
            $leaderClass,
        ]));
        return strpos($haystack, $needle) !== false;
    }));
}

if (is_array($guilds) && !empty($guilds)) {
    usort($guilds, function ($left, $right) use ($sortBy, $sortDir) {
        return spp_guilds_sort_compare($left, $right, $sortBy, $sortDir);
    });
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
if ($sortBy !== '') $baseUrl .= '&sort=' . urlencode($sortBy) . '&dir=' . urlencode($sortDir);
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
      placeholder="Search guild name, leader, faction, class, or item level..."
      autocomplete="off"
    >
  </form>
</div>

<div class="guild-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo (int)$count; ?> guilds</div>


<div class="wow-table guild-table">
  <div class="header">
    <div class="col sortable"><a class="<?php echo $sortBy === 'guild' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'guild', $sortBy, $sortDir)); ?>">Guild<?php echo $sortBy === 'guild' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'faction' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'faction', $sortBy, $sortDir)); ?>">Faction<?php echo $sortBy === 'faction' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'leader' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'leader', $sortBy, $sortDir)); ?>">Leader<?php echo $sortBy === 'leader' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'members' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'members', $sortBy, $sortDir)); ?>">Members<?php echo $sortBy === 'members' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'avg' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'avg', $sortBy, $sortDir)); ?>">Avg Lvl<?php echo $sortBy === 'avg' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'max' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'max', $sortBy, $sortDir)); ?>">Max Lvl<?php echo $sortBy === 'max' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'avgilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'avgilvl', $sortBy, $sortDir)); ?>">Avg iLvl<?php echo $sortBy === 'avgilvl' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
    <div class="col sortable"><a class="<?php echo $sortBy === 'maxilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guilds_sort_url($realmId, $items_per_page, $search, 'maxilvl', $sortBy, $sortDir)); ?>">Max iLvl<?php echo $sortBy === 'maxilvl' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></div>
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
            <a href="index.php?n=server&sub=character&realm=<?php echo (int)$realmId; ?>&character=<?php echo urlencode($guild['leader_name']); ?>">
              <?php echo htmlspecialchars($guild['leader_name']); ?>
            </a>
          <?php else: ?>
            -
          <?php endif; ?>
        </div>
        <div class="col"><?php echo (int)$guild['member_count']; ?></div>
        <div class="col"><?php echo (int)round((float)$guild['avg_level']); ?></div>
        <div class="col"><?php echo (int)$guild['max_level']; ?></div>
        <div class="col"><?php echo !empty($guild['avg_item_level']) ? number_format((float)$guild['avg_item_level'], 1) : '-'; ?></div>
        <div class="col"><?php echo !empty($guild['max_item_level']) ? number_format((float)$guild['max_item_level'], 1) : '-'; ?></div>
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



