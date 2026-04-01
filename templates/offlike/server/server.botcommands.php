<?php
require_once(dirname(__FILE__, 4).'/core/xfer/com_db.php');
require_once(dirname(__FILE__, 4).'/core/xfer/com_search.php');
$botCommands = loadCommands($pdo,$world_db,'bot');
$gmCommands = loadCommands($pdo,$world_db,'gm');
$userGmLevel = (int)($user['gmlevel'] ?? 0);
if (($user['id'] ?? 0) > 0) {
  $gmCommands = array_values(array_filter($gmCommands, function ($cmd) use ($userGmLevel) {
    return (int)($cmd['security'] ?? 0) <= $userGmLevel;
  }));
}
$commandTabs = array('bot', 'commands', 'strategies', 'builder');
$activeCommandTab = strtolower(trim((string)($_GET['tab'] ?? (($sub ?? '') === 'commands' ? 'commands' : 'bot'))));
if (!in_array($activeCommandTab, $commandTabs, true)) {
  $activeCommandTab = 'bot';
}
?>


<?php builddiv_start(1, '(Bot) Commands'); ?>

<style>
.sref-tabs { display:flex; gap:4px; margin-bottom:12px; }
.sref-tab-btn {
    padding:6px 16px; cursor:pointer; border:none; border-radius:4px 4px 0 0;
    background:#2a2a2a; color:#aaa; font-size:13px; font-weight:600;
}
.sref-tab-btn.active { background:#444; color:#f0c070; border-bottom:2px solid #f0c070; }
.sref-panel { display:none; }
.sref-panel.active { display:block; }
.sref-panel table { width:100%; border-collapse:collapse; margin-bottom:16px; font-size:13px; }
.sref-panel th { background:#2a2a2a; color:#f0c070; padding:6px 8px; text-align:left; }
.sref-panel td { padding:5px 8px; border-bottom:1px solid #333; vertical-align:top; }
.sref-panel td code, .sref-panel th code { background:#1a1a1a; padding:1px 4px; border-radius:3px; font-size:12px; color:#7ec8e3; }
.sref-panel h3 { color:#f0c070; margin:20px 0 6px; font-size:14px; border-bottom:1px solid #444; padding-bottom:4px; }
.sref-panel h4 { color:#ccc; margin:12px 0 4px; font-size:13px; }
.sref-panel pre { background:#1a1a1a; padding:10px; border-radius:4px; font-size:12px; color:#aed6a0; overflow-x:auto; margin:6px 0 12px; }
.sref-panel p { color:#bbb; font-size:13px; margin:4px 0 10px; }
.sref-flavor-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
@media(max-width:700px){ .sref-flavor-grid { grid-template-columns:1fr; } }
.sref-flavor-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:10px 12px; }
.sref-flavor-card h4 { color:#f0c070; margin:0 0 6px; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }
.sref-flavor-card p { color:#999; font-size:12px; margin:0 0 8px; font-style:italic; }
.sref-flavor-card table { font-size:12px; }
.sref-flavor-card td { padding:3px 6px; border-bottom:1px solid #2a2a2a; }
.sref-flavor-card td:first-child { color:#7ec8e3; width:40px; font-weight:600; }

/* Custom Strategy Builder */
.csb-section { margin-bottom:18px; }
.csb-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px; }
.csb-label { color:#aaa; font-size:12px; min-width:54px; }
.csb-input { background:#1a1a1a; border:1px solid #444; color:#ddd; padding:4px 8px; border-radius:4px; font-size:13px; }
.csb-input:focus { outline:none; border-color:#f0c070; }
.csb-select { background:#1a1a1a; border:1px solid #444; color:#ddd; padding:4px 6px; border-radius:4px; font-size:12px; }
.csb-select:focus { outline:none; border-color:#f0c070; }
.csb-btn { padding:4px 10px; border:none; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600; }
.csb-btn-add  { background:#2a4a2a; color:#7ec87e; }
.csb-btn-add:hover  { background:#3a5a3a; }
.csb-btn-del  { background:#4a2a2a; color:#e07e7e; }
.csb-btn-del:hover  { background:#5a3a3a; }
.csb-btn-copy { background:#2a3a4a; color:#7ec8e3; padding:5px 14px; }
.csb-btn-copy:hover { background:#3a4a5a; }
.csb-line-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:10px 12px; margin-bottom:10px; }
.csb-line-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.csb-line-num { color:#888; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.csb-action-row { display:flex; align-items:center; gap:6px; margin-bottom:5px; flex-wrap:wrap; }
.csb-priority { width:52px; }
.csb-qual { min-width:140px; }
.csb-output { background:#111; border:1px solid #333; border-radius:6px; padding:12px 14px; font-family:monospace; font-size:12px; color:#aed6a0; white-space:pre-wrap; word-break:break-all; margin-bottom:8px; min-height:40px; }
.csb-output-label { color:#888; font-size:11px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
.csb-copy-row { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
.csb-scope-row { display:flex; gap:16px; align-items:center; }
.csb-radio { accent-color:#f0c070; }
.csb-sep { border:none; border-top:1px solid #333; margin:14px 0; }
</style>

<div class="modern-content">

  <!-- Tab buttons -->
  <div class="sref-tabs">
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'bot' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-bot')">Bot Commands</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'commands' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-commands')">Commands</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'strategies' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-strategies')">Strategy Reference</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'builder' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-builder')">Custom Builder</button>
  </div>

  <!-- Tab 1: Bot Commands table -->
  <div id="tab-bot" class="sref-panel<?php echo $activeCommandTab === 'bot' ? ' active' : ''; ?>">
    <input type="text" id="botCommandSearch" onkeyup="filterTable('botCommandSearch','botCommandTable')" placeholder="Search bot commands...">
    <table id="botCommandTable" class="sortable">
      <thead>
        <tr>
          <th><?php echo $lang['command_name'] ?? 'Command Name'; ?></th>
          <th><?php echo $lang['category'] ?? 'Category'; ?></th>
          <th><?php echo $lang['subcategory'] ?? 'Subcategory'; ?></th>
          <th><?php echo $lang['security_level'] ?? 'Security'; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($botCommands as $topic):
          $catClass = strtolower(str_replace([' ', '/'], '-', $topic['category']));
          $subClass = strtolower(str_replace([' ', '/'], '-', $topic['subcategory'] ?: 'none'));
        ?>
        <tr class="cat-<?php echo $catClass; ?> sub-<?php echo $subClass; ?>">
          <td>
            <details>
              <summary><?php echo htmlspecialchars($topic['name']); ?></summary>
              <p><?php echo nl2br(htmlspecialchars($topic['help'])); ?></p>
            </details>
          </td>
          <td><?php echo htmlspecialchars($topic['category']); ?></td>
          <td><?php echo htmlspecialchars($topic['subcategory'] ?: '-'); ?></td>
          <td align="center"><b><?php echo htmlspecialchars($topic['security']); ?></b></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($botCommands)): ?>
        <tr><td colspan="4" style="text-align:center;color:#888;">No Bot commands found :(.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tab 2: GM Commands table -->
  <div id="tab-commands" class="sref-panel<?php echo $activeCommandTab === 'commands' ? ' active' : ''; ?>">
    <input type="text" id="gmCommandSearch" onkeyup="filterTable('gmCommandSearch','gmCommandTable')" placeholder="Search GM commands...">
    <table id="gmCommandTable" class="sortable">
      <thead>
        <tr>
          <th><?php echo $lang['command_name'] ?? 'Command Name'; ?></th>
          <th><?php echo $lang['security_level'] ?? 'Security'; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($gmCommands as $cmd): ?>
        <tr>
          <td>
            <details>
              <summary><?php echo htmlspecialchars($cmd['name']); ?></summary>
              <p><?php echo nl2br(htmlspecialchars($cmd['help'])); ?></p>
            </details>
          </td>
          <td align="center"><b><?php echo htmlspecialchars($cmd['security']); ?></b></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($gmCommands)): ?>
        <tr><td colspan="2" style="text-align:center;color:#888;">No GM commands found for this account level.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Tab 3: Strategy Reference -->
  <div id="tab-strategies" class="sref-panel<?php echo $activeCommandTab === 'strategies' ? ' active' : ''; ?>">

    <h3>How Strategies Work</h3>
    <p>Each bot runs three independent strategy engines simultaneously. Strategies are additive — multiple run at once, each contributing triggers and actions resolved by a priority queue (1–100).</p>
    <table>
      <thead><tr><th>Key</th><th>Bot State</th><th>When Active</th></tr></thead>
      <tbody>
        <tr><td><code>co</code></td><td>Combat</td><td>While the bot is in a fight</td></tr>
        <tr><td><code>nc</code></td><td>Non-combat</td><td>Wandering, questing, traveling, idle</td></tr>
        <tr><td><code>react</code></td><td>Reaction</td><td>Parallel to combat — instant interrupt-style responses</td></tr>
        <tr><td><code>dead</code></td><td>Dead</td><td>While the bot is a ghost</td></tr>
      </tbody>
    </table>
    <table>
      <thead><tr><th>Priority Band</th><th>Examples</th></tr></thead>
      <tbody>
        <tr><td>90 — Emergency</td><td>Critical health shout, emergency heal</td></tr>
        <tr><td>80 — Critical heal</td><td></td></tr>
        <tr><td>60–70 — Heal</td><td>Light / medium heals</td></tr>
        <tr><td>50 — Dispel</td><td>Remove curse, abolish poison</td></tr>
        <tr><td>40 — Interrupt</td><td>Kick, counterspell</td></tr>
        <tr><td>30 — Move</td><td>Charge, disengage</td></tr>
        <tr><td>20 — High</td><td>Major cooldowns</td></tr>
        <tr><td>10 — Normal</td><td>Standard rotation</td></tr>
        <tr><td>1 — Idle</td><td>Fallback (melee, wand)</td></tr>
      </tbody>
    </table>
    <p>Strategy changes use <code>+add</code> / <code>-remove</code> prefix syntax, comma-separated: <code>+dps,+dps assist,-threat</code></p>

    <h3>Guild Flavor Profiles</h3>
    <p>Four archetypes applied to guilded bots via the guild flavor system. Strings are written verbatim to <code>ai_playerbot_db_store</code>.</p>
    <div class="sref-flavor-grid">
      <div class="sref-flavor-card">
        <h4>Leveling</h4>
        <p>Picks up quests, grinds between objectives, repairs and trains.</p>
        <table><tbody>
          <tr><td>co</td><td><code>+dps,+dps assist,-threat,+custom::say</code></td></tr>
          <tr><td>nc</td><td><code>+rpg,+quest,+grind,+loot,+wander,+custom::say</code></td></tr>
          <tr><td>react</td><td><em>global defaults</em></td></tr>
        </tbody></table>
      </div>
      <div class="sref-flavor-card">
        <h4>Quest</h4>
        <p>NPC-interaction focused. Moves purposefully between quest hubs, fishes while traveling.</p>
        <table><tbody>
          <tr><td>co</td><td><code>+dps,+dps assist,-threat,+custom::say</code></td></tr>
          <tr><td>nc</td><td><code>+rpg,+rpg quest,+loot,+tfish,+wander,+custom::say</code></td></tr>
          <tr><td>react</td><td><em>global defaults</em></td></tr>
        </tbody></table>
      </div>
      <div class="sref-flavor-card">
        <h4>PvP</h4>
        <p>Aggressive. Queues for BGs, roams for enemy players, duels, uses burst cooldowns.</p>
        <table><tbody>
          <tr><td>co</td><td><code>+dps,+dps assist,+threat,+boost,+pvp,+duel,+custom::say</code></td></tr>
          <tr><td>nc</td><td><code>+rpg,+wander,+bg,+custom::say</code></td></tr>
          <tr><td>react</td><td><code>+pvp</code></td></tr>
        </tbody></table>
      </div>
      <div class="sref-flavor-card">
        <h4>Farming</h4>
        <p>Silent resource gatherers. Mining, herbing, skinning, fishing. No questing, no chat.</p>
        <table><tbody>
          <tr><td>co</td><td><code>+dps,-threat</code></td></tr>
          <tr><td>nc</td><td><code>+gather,+grind,+loot,+tfish,+wander,+rpg maintenance</code></td></tr>
          <tr><td>react</td><td><em>global defaults</em></td></tr>
        </tbody></table>
      </div>
    </div>

    <h3>Combat Strategies (<code>co</code>)</h3>

    <h4>DPS &amp; Targeting</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>dps</code></td><td>Activates the bot's class/spec rotation. Required for any bot that fights.</td></tr>
        <tr><td><code>dps assist</code></td><td>Bot attacks the same target as the group leader or tank.</td></tr>
        <tr><td><code>dps aoe</code></td><td>Bot uses AoE abilities when multiple enemies are nearby.</td></tr>
        <tr><td><code>tank assist</code></td><td>Bot assists the designated tank's target. Used for off-tanks.</td></tr>
        <tr><td><code>passive</code></td><td>Bot does not attack at all.</td></tr>
      </tbody>
    </table>

    <h4>Threat</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>threat</code></td><td>Bot actively generates and maintains threat. Tanks use this.</td></tr>
        <tr><td><code>-threat</code></td><td>Removes threat strategy. Bot does not hold back DPS to manage aggro.</td></tr>
      </tbody>
    </table>

    <h4>Positioning</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>behind</code></td><td>Positions behind target before attacking. Increases crit for rogues/ferals.</td></tr>
        <tr><td><code>close</code></td><td>Stays at melee range.</td></tr>
        <tr><td><code>ranged</code></td><td>Stays at ranged distance.</td></tr>
        <tr><td><code>kite</code></td><td>Maintains distance while attacking.</td></tr>
        <tr><td><code>pull back</code></td><td>Tactical retreat during combat.</td></tr>
      </tbody>
    </table>

    <h4>Survival &amp; Mitigation</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>avoid aoe</code></td><td>Bot moves out of AoE damage zones.</td></tr>
        <tr><td><code>avoid mobs</code></td><td>Bot routes around mobs it shouldn't pull.</td></tr>
        <tr><td><code>flee</code></td><td>Bot runs when health is critically low.</td></tr>
        <tr><td><code>preheal</code></td><td>Bot heals proactively based on predicted incoming damage.</td></tr>
        <tr><td><code>cast time</code></td><td>Manages cast timing to avoid pushback and interrupts.</td></tr>
      </tbody>
    </table>

    <h4>Support</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>offheal</code></td><td>Hybrid classes cast heals on the side while DPSing. Requires <code>EnableOffSpecStrategies = 1</code>.</td></tr>
        <tr><td><code>offdps</code></td><td>Heal-spec hybrids deal some damage while primarily healing.</td></tr>
        <tr><td><code>boost</code></td><td>Class-specific burst cooldown (Recklessness, Adrenaline Rush, Icy Veins, etc.).</td></tr>
      </tbody>
    </table>

    <h4>PvP &amp; Dueling</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>pvp</code></td><td>Bot attacks enemy players on sight with PvP-oriented ability choices.</td></tr>
        <tr><td><code>duel</code></td><td>Bot accepts duel requests.</td></tr>
        <tr><td><code>start duel</code></td><td>Bot initiates duels with nearby players during RPG mode.</td></tr>
        <tr><td><code>attack tagged</code></td><td>Bot attacks mobs tagged by players outside the group.</td></tr>
      </tbody>
    </table>

    <h4>Chat</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>custom::say</code></td><td>Bot says flavor text during combat events. Text pulled from <code>ai_playerbot_text</code> DB table.</td></tr>
        <tr><td><code>silent</code></td><td>Suppresses all bot chat. Overrides <code>custom::say</code>.</td></tr>
      </tbody>
    </table>

    <h3>Non-Combat Strategies (<code>nc</code>)</h3>

    <h4>Questing &amp; Leveling</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>rpg</code></td><td>Full RPG mode — the core "living player" strategy. Activates all RPG sub-strategies. Bot visits NPCs, does quests, repairs, crafts, socializes.</td></tr>
        <tr><td><code>rpg quest</code></td><td>RPG sub: actively targets quest NPCs, prioritises handins and new pickups. More directed than <code>quest</code>.</td></tr>
        <tr><td><code>rpg vendor</code></td><td>RPG sub: visits vendors, AH, mailbox. Buys items, sells grays.</td></tr>
        <tr><td><code>rpg explore</code></td><td>RPG sub: explores unmapped areas of the zone.</td></tr>
        <tr><td><code>rpg maintenance</code></td><td>RPG sub: visits repair NPCs and class trainers. Can be used standalone without full <code>+rpg</code>.</td></tr>
        <tr><td><code>rpg guild</code></td><td>RPG sub: guild-related RPG activities.</td></tr>
        <tr><td><code>rpg bg</code></td><td>RPG sub: queues for battlegrounds as part of the RPG activity loop.</td></tr>
        <tr><td><code>rpg craft</code></td><td>RPG sub: crafts useful items. Casts random spells on nearby players/NPCs.</td></tr>
        <tr><td><code>quest</code></td><td>Standard quest strategy. Picks up quests and tracks objectives.</td></tr>
        <tr><td><code>grind</code></td><td>When idle with no objective, finds and attacks nearest mob for XP/loot.</td></tr>
        <tr><td><code>maintenance</code></td><td>Standalone: repairs and sells without full RPG NPC-visiting behavior.</td></tr>
      </tbody>
    </table>

    <h4>Looting &amp; Gathering</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>loot</code></td><td>Moves to and loots nearby corpses and objects after combat.</td></tr>
        <tr><td><code>gather</code></td><td>Scans for and routes to mining nodes, herb patches, and gathering resources within <code>GatheringDistance</code> (default 15yd).</td></tr>
        <tr><td><code>roll</code></td><td>Auto-rolls on group loot immediately.</td></tr>
        <tr><td><code>delayed roll</code></td><td>Waits for the group leader to roll first. Prevents bots from stealing loot.</td></tr>
        <tr><td><code>fish</code></td><td>Fishes at nearby water when idle. Requires fishing skill and rod.</td></tr>
        <tr><td><code>tfish</code></td><td>Travel fishing — fishes at water sources encountered while traveling.</td></tr>
      </tbody>
    </table>

    <h4>Movement &amp; Following</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>wander</code></td><td>Within ~30yd of a player: bot roams freely. Beyond ~30yd: auto-follows back.</td></tr>
        <tr><td><code>follow</code></td><td>Always follows the master/group leader.</td></tr>
        <tr><td><code>stay</code></td><td>Bot does not move from its current position.</td></tr>
        <tr><td><code>guard</code></td><td>Bot guards the master's position, engaging threats in range.</td></tr>
        <tr><td><code>free</code></td><td>Bot moves independently with no follow behavior.</td></tr>
      </tbody>
    </table>

    <h4>Battleground &amp; PvP</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>bg</code></td><td>Queues for battlegrounds and accepts BG invites. Generic.</td></tr>
        <tr><td><code>warsong</code></td><td>Warsong Gulch: flag capture and defense logic.</td></tr>
        <tr><td><code>arathi</code></td><td>Arathi Basin: node capture and defense.</td></tr>
        <tr><td><code>alterac</code></td><td>Alterac Valley: objectives, towers, boss.</td></tr>
        <tr><td><code>eye</code></td><td>Eye of the Storm (TBC+).</td></tr>
        <tr><td><code>isle</code></td><td>Isle of Conquest (WotLK).</td></tr>
      </tbody>
    </table>

    <h4>Buffs &amp; Consumables</h4>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>wbuff</code></td><td>Bot seeks and maintains world buffs appropriate to its level.</td></tr>
        <tr><td><code>food</code></td><td>Uses food and drink to regenerate between fights.</td></tr>
        <tr><td><code>consumables</code></td><td>Uses potions, bandages, etc. proactively.</td></tr>
        <tr><td><code>conserve mana</code></td><td>Reduces mana-heavy spell usage to avoid going OOM.</td></tr>
      </tbody>
    </table>

    <h3>Reaction Strategies (<code>react</code>)</h3>
    <p>Run in a parallel engine alongside combat. Can interrupt ongoing casts or movement for immediate response. Use sparingly.</p>
    <table>
      <thead><tr><th>Strategy</th><th>What it does</th></tr></thead>
      <tbody>
        <tr><td><code>pvp</code></td><td>Immediately reacts to enemy players entering range, even mid-cast.</td></tr>
        <tr><td><code>flee</code></td><td>Reacts to critical health by running immediately.</td></tr>
        <tr><td><code>avoid aoe</code></td><td>Reacts to AoE indicators (void zones, fire) by moving immediately.</td></tr>
        <tr><td><code>preheal</code></td><td>Reacts to incoming damage patterns with a heal.</td></tr>
      </tbody>
    </table>

    <h3>Class-Specific Strategy Names</h3>
    <p>Class strategies follow a consistent naming pattern: <code>[spec]</code>, <code>[spec] pve/pvp/raid</code>, <code>aoe/buff/boost/cc/cure [spec] pve/pvp/raid</code></p>
    <table>
      <thead><tr><th>Class</th><th>Specs &amp; Variants</th></tr></thead>
      <tbody>
        <tr><td>Warrior</td><td><code>arms</code>, <code>fury</code>, <code>protection</code> + pve/pvp/raid; aoe/buff/boost variants</td></tr>
        <tr><td>Rogue</td><td><code>combat</code>, <code>assassination</code>, <code>subtlety</code>; <code>stealth</code>, <code>stealthed</code>, <code>poisons</code></td></tr>
        <tr><td>Mage</td><td><code>frost</code>, <code>fire</code>, <code>arcane</code> + pve/pvp/raid; aoe/cure/buff/boost/cc variants</td></tr>
        <tr><td>Priest</td><td><code>discipline</code>, <code>shadow</code>, <code>holy</code>; <code>offheal</code>, <code>offdps</code></td></tr>
        <tr><td>Paladin</td><td><code>retribution</code>, <code>protection</code>, <code>holy</code>; <code>offheal</code></td></tr>
        <tr><td>Hunter</td><td><code>beast mastery</code>, <code>marksmanship</code>, <code>survival</code>; <code>sting</code>, <code>aspect</code>, <code>pet</code></td></tr>
        <tr><td>Warlock</td><td><code>demonology</code>, <code>affliction</code>, <code>destruction</code>; <code>pet</code>, <code>pet [demon type]</code></td></tr>
        <tr><td>Druid</td><td><code>balance</code>, <code>tank feral</code>, <code>dps feral</code>, <code>restoration</code>; <code>stealth</code>, <code>powershift</code>, <code>offheal</code></td></tr>
        <tr><td>Shaman</td><td><code>elemental</code>, <code>enhancement</code>, <code>restoration</code>; <code>totems</code>, <code>offheal</code></td></tr>
        <tr><td>Death Knight</td><td><code>blood</code>, <code>frost</code>, <code>unholy</code>; <code>tank</code>, <code>frost aoe</code>, <code>unholy aoe</code> (WotLK)</td></tr>
      </tbody>
    </table>

    <h3>Quick Reference — Common Combinations</h3>
    <pre>Solo leveling bot
co: +dps,-threat,+custom::say
nc: +rpg,+quest,+grind,+loot,+wander,+custom::say

Group DPS bot (player-led)
co: +dps,+dps assist,-threat,+boost
nc: +follow,+loot,+delayed roll,+food

Group tank bot
co: +dps,+tank assist,+threat,+boost
nc: +follow,+loot,+delayed roll,+food

Group healer bot
co: +offheal,+dps assist,+cast time
nc: +follow,+loot,+delayed roll,+food,+conserve mana

BG farmer (PvP guild)
co: +dps,+dps assist,+threat,+boost,+pvp,+duel
nc: +bg,+wander,+rpg
react: +pvp

Node farmer (Farming guild)
co: +dps,-threat
nc: +gather,+grind,+loot,+tfish,+wander,+rpg maintenance</pre>

    <h3>In-Game Bot Commands</h3>
    <table>
      <thead><tr><th>Command</th><th>Effect</th></tr></thead>
      <tbody>
        <tr><td><code>.bot co &lt;strategies&gt;</code></td><td>Change combat strategies</td></tr>
        <tr><td><code>.bot nc &lt;strategies&gt;</code></td><td>Change non-combat strategies</td></tr>
        <tr><td><code>.bot react &lt;strategies&gt;</code></td><td>Change reaction strategies</td></tr>
        <tr><td><code>.bot dead &lt;strategies&gt;</code></td><td>Change dead strategies</td></tr>
        <tr><td><code>.bot save ai</code></td><td>Save current strategies to DB (persists across relogs)</td></tr>
        <tr><td><code>.bot save ai &lt;preset&gt;</code></td><td>Save to a named preset</td></tr>
        <tr><td><code>.bot load ai &lt;preset&gt;</code></td><td>Load a named preset</td></tr>
        <tr><td><code>.bot list ai</code></td><td>List saved presets</td></tr>
        <tr><td><code>.bot reset ai</code></td><td>Reset to default class/spec strategies</td></tr>
      </tbody>
    </table>
    <p>Strategy syntax: <code>+strategy</code> add &nbsp;|&nbsp; <code>-strategy</code> remove &nbsp;|&nbsp; <code>~strategy</code> toggle &nbsp;|&nbsp; comma-separated for multiple</p>

  </div><!-- end tab-strategies -->

  <!-- Tab 3: Custom Strategy Builder -->
  <div id="tab-builder" class="sref-panel<?php echo $activeCommandTab === 'builder' ? ' active' : ''; ?>">

    <h3>Custom Strategies</h3>
    <p>
      <code>custom::&lt;name&gt;</code> is a database-driven trigger→action pipeline you define yourself.
      Each line maps one trigger to one or more actions. Lines are checked every bot tick — the first
      matching trigger fires its actions. Add the strategy to a bot with <code>+custom::&lt;name&gt;</code>
      in its <code>co</code> or <code>nc</code> config string.
    </p>
    <p>
      <strong>Syntax:</strong> <code>trigger&gt;action1!priority,action2!priority</code> &nbsp;—&nbsp;
      higher priority wins when multiple actions are ready. Use <code>say::text_name</code> to speak
      text from <code>ai_playerbot_texts</code>, and <code>emote::emote_name</code> to perform an emote.
    </p>
    <p>
      <strong>In-game editing</strong> (live, no recompile): whisper the bot <code>cs &lt;name&gt; &lt;idx&gt; &lt;action_line&gt;</code>
      to set a line, <code>cs &lt;name&gt; &lt;idx&gt;</code> to delete, <code>cs &lt;name&gt; ?</code> to list.
    </p>
    <hr class="csb-sep">

    <!-- Builder controls -->
    <div class="csb-section">
      <div class="csb-row">
        <span class="csb-label">Name</span>
        <input id="csb-name" class="csb-input" type="text" placeholder="e.g. pvpcall" value="mysay" oninput="csbUpdateOutput()">
        <span style="color:#666;font-size:12px;">→ activated as <code id="csb-activation-preview">+custom::mysay</code></span>
      </div>
      <div class="csb-row">
        <span class="csb-label">Scope</span>
        <div class="csb-scope-row">
          <label style="font-size:13px;color:#bbb;cursor:pointer;">
            <input class="csb-radio" type="radio" name="csb-owner" value="0" checked onchange="csbToggleGuid()"> Global (all bots)
          </label>
          <label style="font-size:13px;color:#bbb;cursor:pointer;">
            <input class="csb-radio" type="radio" name="csb-owner" value="guid" onchange="csbToggleGuid()"> Specific bot (GUID)
          </label>
          <input id="csb-guid" class="csb-input csb-priority" type="text" placeholder="GUID" style="display:none;" oninput="csbUpdateOutput()">
        </div>
      </div>
    </div>

    <div id="csb-lines"></div>

    <button class="csb-btn csb-btn-add" onclick="csbAddLine()" style="margin-bottom:16px;">+ Add Line</button>

    <hr class="csb-sep">
    <h3>Output</h3>

    <div class="csb-output-label">Activation string</div>
    <div class="csb-copy-row">
      <div class="csb-output" id="csb-out-activation" style="flex:1;min-height:unset;padding:6px 10px;"></div>
      <button class="csb-btn csb-btn-copy" onclick="csbCopy('csb-out-activation')">Copy</button>
    </div>

    <div class="csb-output-label">SQL INSERT (paste into MariaDB)</div>
    <div class="csb-copy-row">
      <div class="csb-output" id="csb-out-sql" style="flex:1;"></div>
      <button class="csb-btn csb-btn-copy" onclick="csbCopy('csb-out-sql')">Copy</button>
    </div>

    <div class="csb-output-label">In-game cs commands (whisper bot)</div>
    <div class="csb-copy-row">
      <div class="csb-output" id="csb-out-cs" style="flex:1;"></div>
      <button class="csb-btn csb-btn-copy" onclick="csbCopy('csb-out-cs')">Copy</button>
    </div>

  </div><!-- end tab-builder -->

</div><!-- end modern-content -->

<datalist id="csb-say-list">
  <option value="critical health"><option value="low health"><option value="low mana">
  <option value="aoe"><option value="taunt"><option value="attacking"><option value="fleeing">
  <option value="fleeing_far"><option value="following"><option value="staying"><option value="guarding">
  <option value="grinding"><option value="loot"><option value="hello"><option value="goodbye">
  <option value="join_group"><option value="join_raid"><option value="no ammo"><option value="low ammo">
  <option value="reply"><option value="suggest_trade"><option value="suggest_something">
  <option value="broadcast_levelup_generic"><option value="broadcast_killed_player">
  <option value="broadcast_killed_elite"><option value="broadcast_killed_worldboss">
  <option value="broadcast_quest_turned_in"><option value="broadcast_looting_item_epic">
  <option value="broadcast_looting_item_legendary"><option value="broadcast_looting_item_rare">
  <option value="quest_accept"><option value="quest_remove"><option value="quest_status_completed">
  <option value="quest_error_bag_full"><option value="use_command"><option value="equip_command">
  <option value="error_far"><option value="wait_travel_close"><option value="wait_travel_far">
</datalist>

<datalist id="csb-emote-list">
  <option value="helpme"><option value="healme"><option value="flee"><option value="charge">
  <option value="danger"><option value="oom"><option value="openfire"><option value="wait">
  <option value="follow"><option value="train"><option value="joke"><option value="silly">
  <option value="hug"><option value="kneel"><option value="kiss"><option value="point">
  <option value="roar"><option value="rude"><option value="chicken"><option value="flirt">
  <option value="introduce"><option value="anecdote"><option value="dance"><option value="bow">
  <option value="cheer"><option value="cry"><option value="laugh"><option value="wave">
  <option value="salute"><option value="flex"><option value="no"><option value="yes">
  <option value="beg"><option value="applaud"><option value="sleep"><option value="shy"><option value="talk">
</datalist>

<script src="/templates/offlike/js/commands.js"></script>
<script>
function srefTab(btn, panelId) {
    document.querySelectorAll('.sref-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.sref-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
    if (panelId === 'tab-builder') csbRender();
}

/* ── Custom Strategy Builder ─────────────────────────────────── */

const CSB_TRIGGERS = {
  'Health & Resources': [
    'critical health','low health','medium health','almost full health',
    'no mana','low mana','medium mana','high mana','almost full mana',
    'no energy available','light energy available','medium energy available','high energy available',
    'light rage available','medium rage available','high rage available'
  ],
  'Combat': [
    'combat start','combat end','death',
    'no target','target in sight','target changed','invalid target','not facing target',
    'has aggro','lose aggro','high threat','medium threat','some threat','no threat',
    'multiple attackers','has attackers','no attackers','possible adds',
    'enemy is close','enemy player near','enemy player ten yards',
    'enemy out of melee','enemy out of spell',
    'behind target','not behind target','panic','outnumbered'
  ],
  'Party': [
    'party member critical health','party member low health','party member medium health',
    'party member almost full health','party member dead','protect party member','no pet'
  ],
  'Movement & Position': [
    'far from master','not near master',
    'wander far','wander medium','wander near',
    'swimming','move stuck','move long stuck','falling','falling far',
    'can loot','loot available','far from loot target'
  ],
  'Battleground & PvP': [
    'in battleground','in pvp','in pve',
    'bg active','bg ended','bg waiting','bg invite active',
    'player has flag','player has no flag','team has flag','enemy team has flag',
    'enemy flagcarrier near','in battleground without flag'
  ],
  'Status Effects': [
    'dead','corpse near','mounted','rooted','party member rooted',
    'feared','stunned','charmed'
  ],
  'RPG': [
    'no rpg target','has rpg target','far from rpg target','near rpg target',
    'rpg wander','rpg start quest','rpg end quest','rpg buy',
    'rpg sell','rpg repair','rpg train'
  ],
  'Timing': [
    'random','timer','seldom','often','very often',
    'random bot update','no non bot players around','new player nearby'
  ],
  'Buffs & Items': [
    'potion cooldown','use trinket','need world buff',
    'give food','give water',
    'has blessing of salvation','has greater blessing of salvation'
  ]
};

const CSB_ACTIONS = {
  '── Qualified ──': ['say::','emote::'],
  'Communication': ['talk','suggest what to do','greet'],
  'Combat': [
    'attack','melee','dps assist','tank assist','dps aoe',
    'flee','flee with pet','shoot',
    'interrupt current spell','attack enemy player','attack least hp target'
  ],
  'Survival & Healing': [
    'healing potion','healthstone','mana potion','food','drink',
    'use bandage','try emergency','whipper root tuber',
    'fire protection potion','free action potion'
  ],
  'Movement': [
    'follow','stay','return','runaway','flee to master',
    'mount','hearthstone','move random','guard'
  ],
  'Loot': [
    'loot','move to loot','add loot','release loot','auto loot roll','reveal gathering item'
  ],
  'Battleground': [
    'free bg join','bg tactics','bg move to objective',
    'bg move to start','attack enemy flag carrier'
  ],
  'Racials': [
    'war stomp','berserking','blood fury','shadowmeld','stoneform',
    'arcane torrent','will of the forsaken','cannibalize','mana tap',
    'escape artist','perception','every_man_for_himself','gift of the naaru'
  ],
  'Misc': [
    'delay','reset','random bot update',
    'xp gain','honor gain','invite nearby','check mail','update gear'
  ]
};

// State
let csbLines = [];
let csbInitialized = false;

function csbInit() {
  if (csbInitialized) return;
  csbInitialized = true;
  csbLines = [
    {
      trigger: 'critical health',
      actions: [
        { type: 'emote::', qualifier: 'helpme', priority: 99 },
        { type: 'say::',   qualifier: 'critical health', priority: 98 }
      ]
    }
  ];
  csbRender();
}

function csbToggleGuid() {
  const isGuid = document.querySelector('input[name="csb-owner"]:checked').value === 'guid';
  document.getElementById('csb-guid').style.display = isGuid ? '' : 'none';
  csbUpdateOutput();
}

function csbAddLine() {
  csbLines.push({ trigger: 'low health', actions: [{ type: 'say::', qualifier: 'low health', priority: 98 }] });
  csbRender();
}

function csbRemoveLine(idx) {
  csbLines.splice(idx, 1);
  csbRender();
}

function csbAddAction(lineIdx) {
  csbLines[lineIdx].actions.push({ type: 'emote::', qualifier: 'helpme', priority: 99 });
  csbRender();
}

function csbRemoveAction(lineIdx, actionIdx) {
  csbLines[lineIdx].actions.splice(actionIdx, 1);
  csbRender();
}

function csbSet(lineIdx, field, value) {
  csbLines[lineIdx][field] = value;
  csbUpdateOutput();
}

function csbSetAction(lineIdx, actionIdx, field, value) {
  if (field === 'type') {
    csbLines[lineIdx].actions[actionIdx].type = value;
    if (value === 'say::')   csbLines[lineIdx].actions[actionIdx].qualifier = 'critical health';
    else if (value === 'emote::') csbLines[lineIdx].actions[actionIdx].qualifier = 'helpme';
    else                     csbLines[lineIdx].actions[actionIdx].qualifier = '';
    csbRender();
  } else {
    csbLines[lineIdx].actions[actionIdx][field] = value;
    csbUpdateOutput();
  }
}

function csbBuildTriggerSelect(lineIdx, selected) {
  let html = '<select class="csb-select" style="min-width:180px;" onchange="csbSet(' + lineIdx + ',\'trigger\',this.value)">';
  for (const [group, triggers] of Object.entries(CSB_TRIGGERS)) {
    html += '<optgroup label="' + group + '">';
    for (const t of triggers) {
      html += '<option value="' + t + '"' + (t === selected ? ' selected' : '') + '>' + t + '</option>';
    }
    html += '</optgroup>';
  }
  html += '</select>';
  return html;
}

function csbBuildActionSelect(lineIdx, actionIdx, selected) {
  let html = '<select class="csb-select" style="min-width:130px;" onchange="csbSetAction(' + lineIdx + ',' + actionIdx + ',\'type\',this.value)">';
  for (const [group, actions] of Object.entries(CSB_ACTIONS)) {
    html += '<optgroup label="' + group + '">';
    for (const a of actions) {
      html += '<option value="' + a + '"' + (a === selected ? ' selected' : '') + '>' + a + '</option>';
    }
    html += '</optgroup>';
  }
  html += '</select>';
  return html;
}

function csbRender() {
  const container = document.getElementById('csb-lines');
  let html = '';
  csbLines.forEach(function(line, li) {
    html += '<div class="csb-line-card">';
    html += '<div class="csb-line-header"><span class="csb-line-num">Line ' + (li+1) + '</span>';
    html += '<button class="csb-btn csb-btn-del" onclick="csbRemoveLine(' + li + ')">× Remove</button></div>';

    // Trigger row
    html += '<div class="csb-row"><span class="csb-label">Trigger</span>';
    html += csbBuildTriggerSelect(li, line.trigger);
    html += '</div>';

    // Actions
    html += '<div style="margin-left:62px;">';
    line.actions.forEach(function(act, ai) {
      const needsQual = act.type === 'say::' || act.type === 'emote::';
      const listAttr  = act.type === 'say::' ? 'list="csb-say-list"' : act.type === 'emote::' ? 'list="csb-emote-list"' : '';
      html += '<div class="csb-action-row">';
      html += csbBuildActionSelect(li, ai, act.type);
      if (needsQual) {
        html += '<input class="csb-input csb-qual" type="text" ' + listAttr + ' placeholder="qualifier" value="' + act.qualifier.replace(/"/g,'&quot;') + '" oninput="csbSetAction(' + li + ',' + ai + ',\'qualifier\',this.value)">';
      }
      html += '!<input class="csb-input csb-priority" type="number" min="1" max="100" value="' + act.priority + '" oninput="csbSetAction(' + li + ',' + ai + ',\'priority\',this.value)">';
      html += '<button class="csb-btn csb-btn-del" onclick="csbRemoveAction(' + li + ',' + ai + ')">×</button>';
      html += '</div>';
    });
    html += '<button class="csb-btn csb-btn-add" style="margin-top:4px;" onclick="csbAddAction(' + li + ')">+ Action</button>';
    html += '</div>';

    html += '</div>';
  });
  container.innerHTML = html;
  csbUpdateOutput();
}

function csbBuildActionLine(line) {
  const actStr = line.actions.map(function(a) {
    const name = (a.type === 'say::' || a.type === 'emote::') ? a.type + a.qualifier : a.type;
    return name + '!' + a.priority;
  }).join(',');
  return line.trigger + '>' + actStr;
}

function csbUpdateOutput() {
  const name  = (document.getElementById('csb-name') || {}).value || 'mysay';
  const ownerRadio = document.querySelector('input[name="csb-owner"]:checked');
  const ownerVal   = ownerRadio && ownerRadio.value === 'guid'
    ? ((document.getElementById('csb-guid') || {}).value || '0')
    : '0';

  // Update activation preview
  const preview = document.getElementById('csb-activation-preview');
  if (preview) preview.textContent = '+custom::' + name;

  const actionLines = csbLines.map(csbBuildActionLine);

  // Activation
  const elAct = document.getElementById('csb-out-activation');
  if (elAct) elAct.textContent = '+custom::' + name;

  // SQL
  const sqlRows = actionLines.map(function(al, i) {
    return "  ('" + name + "', " + (i+1) + ", " + ownerVal + ", '" + al + "')";
  }).join(',\n');
  const sql = 'INSERT INTO ai_playerbot_custom_strategy (name, idx, owner, action_line) VALUES\n' + sqlRows + ';';
  const elSql = document.getElementById('csb-out-sql');
  if (elSql) elSql.textContent = actionLines.length ? sql : '(add at least one line)';

  // cs commands
  const csLines = actionLines.map(function(al, i) {
    return 'cs ' + name + ' ' + (i+1) + ' ' + al;
  }).join('\n');
  const elCs = document.getElementById('csb-out-cs');
  if (elCs) elCs.textContent = actionLines.length ? csLines : '(add at least one line)';
}

function csbCopy(elementId) {
  const text = document.getElementById(elementId).textContent;
  navigator.clipboard.writeText(text).then(function() {
    const btn = document.querySelector('[onclick="csbCopy(\'' + elementId + '\')"]');
    if (btn) { const orig = btn.textContent; btn.textContent = 'Copied!'; setTimeout(function(){ btn.textContent = orig; }, 1500); }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  csbInit();
});
</script>

<?php builddiv_end(); ?>
