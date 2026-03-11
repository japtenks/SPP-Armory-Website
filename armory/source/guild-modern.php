<?php
if(!defined("Armory"))
{
    header("Location: ../error.php");
    exit();
}

if(!isset($_GET["guildid"]))
{
    showerror("Guild", "You must provide a guild id.");
    return;
}

$guildId = (int)$_GET["guildid"];
$guild = execute_query("char", "SELECT * FROM `guild` WHERE `guildid` = ".$guildId." LIMIT 1", 1);
if(!$guild)
{
    showerror("Guild does not exist", "The requested guild could not be found on realm ".REALM_NAME.".");
    return;
}

$leader = execute_query("char", "SELECT `guid`, `name`, `race`, `class`, `level`, `gender` FROM `characters` WHERE `guid` = ".(int)$guild["leaderguid"]." LIMIT 1", 1);
$members = execute_query("char", "
    SELECT
        c.guid,
        c.name,
        c.race,
        c.class,
        c.level,
        c.gender,
        gm.rank,
        gr.rname AS rank_name
    FROM `guild_member` gm
    LEFT JOIN `characters` c ON gm.guid = c.guid
    LEFT JOIN `guild_rank` gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
    WHERE gm.guildid = ".$guildId.exclude_GMs()."
    ORDER BY gm.rank ASC, c.level DESC, c.name ASC
", 0);

if(!$members)
{
    $members = array();
}

$faction = $leader ? GetFaction($leader["race"]) : "alliance";
$guildMembers = count($members);
$avgLevel = 0;
$maxLevel = 0;
$classBreakdown = array();
$rankOptions = array();

foreach($members as $member)
{
    $avgLevel += (int)$member["level"];
    if((int)$member["level"] > $maxLevel)
        $maxLevel = (int)$member["level"];

    $classId = (int)$member["class"];
    if(!isset($classBreakdown[$classId]))
        $classBreakdown[$classId] = 0;
    $classBreakdown[$classId]++;

    $rankId = (int)$member["rank"];
    if(!isset($rankOptions[$rankId]))
        $rankOptions[$rankId] = $member["rank_name"] ? $member["rank_name"] : ("Rank ".$rankId);
}

if($guildMembers > 0)
    $avgLevel = round($avgLevel / $guildMembers, 1);

$classNames = array(
    1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
    6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
);
$raceNames = array(
    1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
    6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
);

$selectedName = isset($_GET['name']) ? trim($_GET['name']) : '';
$selectedClass = isset($_GET['class']) ? (int)$_GET['class'] : -1;
$selectedRank = isset($_GET['rank']) ? (int)$_GET['rank'] : -1;
$selectedMax = isset($_GET['maxonly']) ? 1 : 0;

$filteredMembers = array();
foreach($members as $member)
{
    if($selectedName !== '' && stripos($member['name'], $selectedName) === false)
        continue;
    if($selectedClass > 0 && (int)$member['class'] !== $selectedClass)
        continue;
    if($selectedRank >= 0 && (int)$member['rank'] !== $selectedRank)
        continue;
    if($selectedMax && (int)$member['level'] < $maxLevel)
        continue;
    $filteredMembers[] = $member;
}

$crest = '/templates/offlike/images/modern/logo-' . $faction . '.png';
$motd = trim((string)$guild['motd']) !== '' ? $guild['motd'] : 'No message set.';
?>
<style>
.guild-modern {
  max-width: 1560px;
  margin: 24px auto 32px;
  color: #f3e7c3;
}
.guild-hero {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 32px;
  padding: 28px 32px 24px;
  background:
    linear-gradient(180deg, rgba(5, 8, 22, 0.62), rgba(2, 3, 12, 0.88)),
    url('images/guild-bg.jpg') center/cover no-repeat;
  border: 1px solid rgba(255, 204, 72, 0.22);
  border-radius: 20px 20px 0 0;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
}
.guild-hero-main {
  display: flex;
  align-items: center;
  gap: 22px;
}
.guild-crest {
  width: 96px;
  height: 96px;
  object-fit: contain;
  filter: drop-shadow(0 0 12px rgba(255, 193, 7, 0.25));
}
.guild-name {
  font-size: 3rem;
  line-height: 1;
  margin: 0 0 8px;
  color: #fff5cf;
}
.guild-realm {
  font-size: 1.4rem;
  margin: 0;
  color: #d9c99a;
}
.guild-meta {
  display: flex;
  gap: 28px;
  flex-wrap: wrap;
  justify-content: flex-start;
  align-items: flex-start;
  padding-top: 10px;
}
.guild-meta-card {
  min-width: 120px;
}
.guild-meta-label {
  display: block;
  font-size: 0.82rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #c4b27c;
  margin-bottom: 6px;
}
.guild-meta-value {
  font-size: 1.7rem;
  font-weight: 700;
  color: #ffd65e;
}
.guild-tabs {
  display: flex;
  gap: 10px;
  padding: 0 32px;
  background: rgba(2, 3, 12, 0.9);
  border-left: 1px solid rgba(255, 204, 72, 0.22);
  border-right: 1px solid rgba(255, 204, 72, 0.22);
  border-bottom: 1px solid rgba(255, 204, 72, 0.18);
}
.guild-tab {
  display: inline-flex;
  align-items: center;
  padding: 14px 18px;
  font-size: 0.95rem;
  font-weight: 700;
  color: #d8c58f;
  text-decoration: none;
  border-bottom: 3px solid transparent;
}
.guild-tab.is-active {
  color: #111;
  background: #ffc21c;
}
.guild-shell {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(320px, 0.95fr);
  gap: 32px;
  padding: 28px 32px 36px;
  background: linear-gradient(180deg, rgba(1, 2, 10, 0.92), rgba(1, 2, 8, 0.98));
  border: 1px solid rgba(255, 204, 72, 0.22);
  border-top: 0;
  border-radius: 0 0 20px 20px;
}
.guild-panel-title {
  margin: 0 0 16px;
  font-size: 1.9rem;
  color: #fff7d1;
}
.guild-filter-grid {
  display: grid;
  grid-template-columns: minmax(220px, 1.4fr) 180px 220px auto;
  gap: 14px;
  margin-bottom: 18px;
}
.guild-input,
.guild-select {
  width: 100%;
  height: 48px;
  padding: 0 14px;
  color: #f8f1d4;
  background: rgba(4, 6, 16, 0.94);
  border: 1px solid rgba(255, 196, 0, 0.75);
  border-radius: 0;
}
.guild-check {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #f4d05c;
  font-weight: 700;
}
.guild-roster {
  width: 100%;
  border-collapse: collapse;
  background: rgba(10, 10, 18, 0.72);
}
.guild-roster thead th {
  padding: 14px 16px;
  text-align: left;
  font-size: 0.95rem;
  color: #ffc21c;
  border-bottom: 1px solid rgba(255, 204, 72, 0.28);
}
.guild-roster tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255, 204, 72, 0.14);
  vertical-align: middle;
}
.guild-roster tbody tr:nth-child(odd) {
  background: rgba(255, 255, 255, 0.03);
}
.guild-member {
  display: flex;
  align-items: center;
  gap: 12px;
}
.guild-portrait {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 2px solid rgba(255, 198, 0, 0.45);
  object-fit: cover;
  background: #050505;
}
.guild-class-icon,
.guild-race-icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255, 198, 0, 0.45);
}
.guild-rank {
  color: #c9b27b;
}
.guild-sidebar section {
  padding: 0 0 22px;
  margin-bottom: 24px;
  border-bottom: 1px solid rgba(255, 204, 72, 0.18);
}
.guild-sidebar h3 {
  margin: 0 0 14px;
  font-size: 1.55rem;
  color: #fff7d1;
}
.guild-sidebar p {
  margin: 0;
  color: #d9c99a;
  line-height: 1.5;
}
.guild-breakdown {
  display: grid;
  gap: 10px;
}
.guild-breakdown-row {
  display: grid;
  grid-template-columns: 110px 1fr 36px;
  gap: 12px;
  align-items: center;
}
.guild-breakdown-label {
  color: #e5d4a0;
}
.guild-breakdown-bar {
  height: 14px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  overflow: hidden;
}
.guild-breakdown-fill {
  height: 100%;
  border-radius: 999px;
}
.class-warrior { --class-color:#C79C6E; }
.class-mage { --class-color:#69CCF0; }
.class-priest { --class-color:#FFFFFF; }
.class-hunter { --class-color:#ABD473; }
.class-rogue { --class-color:#FFF569; }
.class-warlock { --class-color:#9482C9; }
.class-paladin { --class-color:#F58CBA; }
.class-druid { --class-color:#FF7D0A; }
.class-shaman { --class-color:#0070DE; }
.class-deathknight { --class-color:#C41F3B; }
[class*="class-"] a { color: var(--class-color); text-decoration: none; font-weight: 700; }
@media (max-width: 1100px) {
  .guild-hero,
  .guild-shell {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 760px) {
  .guild-filter-grid {
    grid-template-columns: 1fr;
  }
  .guild-name {
    font-size: 2.2rem;
  }
}
</style>
<?php
$maxBreakdown = $classBreakdown ? max($classBreakdown) : 1;
?>
<div class="guild-modern">
  <div class="guild-hero">
    <div class="guild-hero-main">
      <img class="guild-crest" src="<?php echo $crest; ?>" alt="<?php echo ucfirst($faction); ?>">
      <div>
        <h1 class="guild-name"><?php echo htmlspecialchars($guild['name']); ?></h1>
        <p class="guild-realm"><?php echo htmlspecialchars(REALM_NAME); ?></p>
      </div>
    </div>
    <div class="guild-meta">
      <div class="guild-meta-card">
        <span class="guild-meta-label">Faction</span>
        <span class="guild-meta-value"><?php echo ucfirst($faction); ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Members</span>
        <span class="guild-meta-value"><?php echo $guildMembers; ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Average Level</span>
        <span class="guild-meta-value"><?php echo $avgLevel; ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Guild Master</span>
        <span class="guild-meta-value"><?php echo $leader ? htmlspecialchars($leader['name']) : 'Unknown'; ?></span>
      </div>
    </div>
  </div>
  <div class="guild-tabs">
    <a class="guild-tab is-active" href="index.php?searchType=guildmodern&guildid=<?php echo $guildId; ?>&realm=<?php echo urlencode(REALM_NAME); ?>">Guild</a>
    <a class="guild-tab" href="index.php?searchType=guildinfo&guildid=<?php echo $guildId; ?>&realm=<?php echo urlencode(REALM_NAME); ?>">Legacy View</a>
  </div>
  <div class="guild-shell">
    <div class="guild-main">
      <h2 class="guild-panel-title">Guild Roster</h2>
      <form method="get" class="guild-filter-grid">
        <input type="hidden" name="searchType" value="guildmodern">
        <input type="hidden" name="guildid" value="<?php echo $guildId; ?>">
        <input type="hidden" name="realm" value="<?php echo htmlspecialchars(REALM_NAME); ?>">
        <input class="guild-input" type="text" name="name" value="<?php echo htmlspecialchars($selectedName); ?>" placeholder="Search member name...">
        <select class="guild-select" name="class">
          <option value="-1">All Classes</option>
          <?php foreach($classNames as $classId => $className): ?>
            <option value="<?php echo $classId; ?>"<?php echo $selectedClass === $classId ? ' selected' : ''; ?>><?php echo htmlspecialchars($className); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="guild-select" name="rank">
          <option value="-1">All Ranks</option>
          <?php foreach($rankOptions as $rankId => $rankName): ?>
            <option value="<?php echo (int)$rankId; ?>"<?php echo $selectedRank === (int)$rankId ? ' selected' : ''; ?>><?php echo htmlspecialchars($rankName); ?></option>
          <?php endforeach; ?>
        </select>
        <label class="guild-check"><input type="checkbox" name="maxonly" value="1"<?php echo $selectedMax ? ' checked' : ''; ?>> Max Level Only</label>
      </form>

      <table class="guild-roster">
        <thead>
          <tr>
            <th>Name</th>
            <th>Race</th>
            <th>Class</th>
            <th>Level</th>
            <th>Guild Rank</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($filteredMembers)): ?>
            <?php foreach($filteredMembers as $member): ?>
              <?php
                $memberClassName = isset($classNames[(int)$member['class']]) ? $classNames[(int)$member['class']] : 'Unknown';
                $memberClassSlug = strtolower(str_replace(' ', '', $memberClassName));
                $memberRaceName = isset($raceNames[(int)$member['race']]) ? $raceNames[(int)$member['race']] : 'Unknown';
                $portrait = 'images/portraits/' . GetCharacterPortrait($member['level'], $member['gender'], $member['race'], $member['class']);
              ?>
              <tr>
                <td>
                  <div class="guild-member class-<?php echo $memberClassSlug; ?>">
                    <img class="guild-portrait" src="<?php echo $portrait; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                    <a href="index.php?searchType=profile&character=<?php echo urlencode($member['name']); ?>&realm=<?php echo urlencode(REALM_NAME); ?>"><?php echo htmlspecialchars($member['name']); ?></a>
                  </div>
                </td>
                <td><img class="guild-race-icon" src="images/icons/race/<?php echo $member['race']; ?>-<?php echo $member['gender']; ?>.gif" alt="<?php echo htmlspecialchars($memberRaceName); ?>" title="<?php echo htmlspecialchars($memberRaceName); ?>"></td>
                <td><img class="guild-class-icon" src="images/icons/class/<?php echo $member['class']; ?>.gif" alt="<?php echo htmlspecialchars($memberClassName); ?>" title="<?php echo htmlspecialchars($memberClassName); ?>"></td>
                <td><?php echo (int)$member['level']; ?></td>
                <td class="guild-rank"><?php echo htmlspecialchars($member['rank_name'] ? $member['rank_name'] : ('Rank ' . (int)$member['rank'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5">No roster members matched the current filter.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <aside class="guild-sidebar">
      <section>
        <h3>Message Of The Day</h3>
        <p><?php echo htmlspecialchars($motd); ?></p>
      </section>
      <section>
        <h3>Roster Overview</h3>
        <p><?php echo $guildMembers; ?> members, average level <?php echo $avgLevel; ?>, max level <?php echo $maxLevel; ?>.</p>
      </section>
      <section>
        <h3>Class Breakdown</h3>
        <div class="guild-breakdown">
          <?php foreach($classBreakdown as $classId => $classCount): ?>
            <?php
              $breakClassName = isset($classNames[$classId]) ? $classNames[$classId] : ('Class ' . $classId);
              $breakClassSlug = strtolower(str_replace(' ', '', $breakClassName));
              $breakWidth = $maxBreakdown > 0 ? round(($classCount / $maxBreakdown) * 100, 1) : 0;
            ?>
            <div class="guild-breakdown-row class-<?php echo $breakClassSlug; ?>">
              <div class="guild-breakdown-label"><?php echo htmlspecialchars($breakClassName); ?></div>
              <div class="guild-breakdown-bar"><div class="guild-breakdown-fill" style="width: <?php echo $breakWidth; ?>%; background: var(--class-color);"></div></div>
              <div><?php echo (int)$classCount; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </aside>
  </div>
</div>
