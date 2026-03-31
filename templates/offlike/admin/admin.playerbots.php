<?php builddiv_start(1, 'Playerbots Control'); ?>
<?php
$meetingSaved = isset($_GET['meeting_saved']) && (string)$_GET['meeting_saved'] === '1';
$shareSaved = isset($_GET['share_saved']) && (string)$_GET['share_saved'] === '1';
$notesSaved = isset($_GET['notes_saved']) && (string)$_GET['notes_saved'] === '1';
$personalitySaved = isset($_GET['personality_saved']) && (string)$_GET['personality_saved'] === '1';
$botStrategySaved = isset($_GET['bot_strategy_saved']) && (string)$_GET['bot_strategy_saved'] === '1';
$strategySaved = isset($_GET['strategy_saved']) && (string)$_GET['strategy_saved'] === '1';
$seededMembers = isset($_GET['seeded_members']) ? max(0, (int)$_GET['seeded_members']) : 0;
$seedFailedMembers = isset($_GET['seed_failed_members']) ? max(0, (int)$_GET['seed_failed_members']) : 0;

$guildStrategyValues = is_array($guildStrategyState['values'] ?? null) ? $guildStrategyState['values'] : array();
$guildStrategyProfileKey = (string)($guildStrategyState['profile_key'] ?? 'custom');
$guildStrategyConsistent = !empty($guildStrategyState['consistent']);
$guildStrategyMemberCount = (int)($guildStrategyState['member_count'] ?? 0);
$guildStrategyMixedCount = (int)($guildStrategyState['mixed_count'] ?? 0);
$randomBotBaselineProfile = is_array($randomBotBaselineProfile ?? null) ? $randomBotBaselineProfile : array();
$characterStrategyValues = is_array($characterStrategyState['values'] ?? null) ? $characterStrategyState['values'] : array();
$characterStrategyProfileKey = (string)($characterStrategyState['profile_key'] ?? 'custom');
?>
<style>
.playerbots-shell{display:grid;gap:16px;font-size:.9em}
.playerbots-card{background:rgba(11,17,26,.85);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:16px}
.playerbots-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.playerbots-wide{display:grid;gap:16px}
.playerbots-field label{display:block;color:#d7b968;font-weight:bold;margin-bottom:6px}
.playerbots-field input,.playerbots-field select,.playerbots-field textarea{width:100%;box-sizing:border-box;padding:8px 10px;border-radius:4px;border:1px solid #5f6d84;background:#111823;color:#fff}
.playerbots-field textarea{min-height:96px;resize:vertical;font-family:monospace;font-size:.9em}
.playerbots-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
.playerbots-button{padding:8px 14px;border:0;border-radius:4px;background:#41658a;color:#fff;font-weight:bold;cursor:pointer}
.playerbots-note{color:#b8c6da;font-size:.9em;line-height:1.45}
.playerbots-success{background:#17311f;border:1px solid #2f6a40;color:#bbf0c8;padding:10px 12px;border-radius:4px}
.playerbots-list{display:grid;gap:8px;max-height:360px;overflow:auto}
.playerbots-row{padding:10px 12px;border:1px solid rgba(255,255,255,.06);border-radius:4px;background:rgba(255,255,255,.02)}
.playerbots-preview{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:6px;padding:12px}
.playerbots-preview code{white-space:pre-wrap}
.playerbots-table{width:100%;border-collapse:collapse;margin-top:8px}
.playerbots-table th,.playerbots-table td{padding:8px;border:1px solid rgba(255,255,255,.08);vertical-align:top}
.playerbots-table th{text-align:left;color:#f0e0b6;background:rgba(255,255,255,.04)}
.playerbots-table.is-compact th,.playerbots-table.is-compact td{padding:6px 8px;font-size:.92em}
.playerbots-inline{display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:10px}
.playerbots-empty{color:#8ea0bb;font-style:italic}
.playerbots-section-title{margin:0 0 8px}
.playerbots-profile-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
.playerbots-profile-card{padding:10px 12px;border:1px solid rgba(255,255,255,.08);border-radius:6px;background:rgba(255,255,255,.03)}
.playerbots-profile-card strong{display:block;color:#f0e0b6;margin-bottom:4px}
.playerbots-strategy-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
.playerbots-status{display:inline-block;padding:4px 8px;border-radius:999px;background:rgba(255,255,255,.08);color:#d7e4f7;font-size:.85em}
@media (max-width:700px){.playerbots-inline{grid-template-columns:1fr}}
</style>

<div class="playerbots-shell">
  <?php if ($meetingSaved): ?><div class="playerbots-success">Guild meeting directive saved.</div><?php endif; ?>
  <?php if ($shareSaved): ?><div class="playerbots-success">Guild share block saved.</div><?php endif; ?>
  <?php if ($notesSaved): ?><div class="playerbots-success">Officer order notes saved.</div><?php endif; ?>
  <?php if ($personalitySaved): ?><div class="playerbots-success">Bot personality saved.</div><?php endif; ?>
  <?php if ($botStrategySaved): ?><div class="playerbots-success">Bot strategy override saved.</div><?php endif; ?>
  <?php if ($strategySaved): ?><div class="playerbots-success">Guild flavor applied to member bots.</div><?php endif; ?>
  <?php if (!empty($invalidRealmRequested)): ?><div class="playerbots-success" style="background:#3a2612;border-color:#8d6130;color:#f5d8a8;">Realm <?php echo (int)$requestedRealmId; ?> is not configured here. Showing the nearest valid configured realm instead.</div><?php endif; ?>

  <div class="playerbots-card">
    <div class="playerbots-grid">
      <div class="playerbots-field">
        <label>Realm</label>
        <select onchange="window.location.href='index.php?n=admin&sub=playerbots&realm='+this.value;">
          <?php foreach (($realmOptions ?? array()) as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['realm_id']; ?>"<?php echo (int)$realmOption['realm_id'] === (int)$realmId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$realmOption['label']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="playerbots-field">
        <label>Guild</label>
        <select onchange="window.location.href='index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid='+this.value;">
          <?php foreach ($guildOptions as $guildOption): ?>
            <option value="<?php echo (int)$guildOption['guildid']; ?>"<?php echo (int)$guildOption['guildid'] === (int)$selectedGuildId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$guildOption['name']); ?><?php if (!empty($guildOption['leader_name'])): ?> - <?php echo htmlspecialchars((string)$guildOption['leader_name']); ?><?php endif; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php if (!empty($selectedGuild)): ?>
      <p class="playerbots-note">Managing <strong><?php echo htmlspecialchars((string)$selectedGuild['name']); ?></strong> on <strong><?php echo htmlspecialchars((string)$realmName); ?></strong>. Guild leader GUID: <?php echo (int)($selectedGuild['leaderguid'] ?? 0); ?>.</p>
    <?php else: ?>
      <p class="playerbots-note">No guild was found for the selected realm.</p>
    <?php endif; ?>
  </div>

  <div class="playerbots-grid">
    <div class="playerbots-card">
      <h3 class="playerbots-section-title">Guild Meetings</h3>
      <?php if (!empty($selectedGuild)): ?>
      <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="playerbots_action" value="save_meeting">
        <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
        <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
        <div class="playerbots-field">
          <label>Meeting Location</label>
          <select name="meeting_location">
            <option value="">Choose a named travel location...</option>
            <?php foreach (($meetingLocationOptions ?? array()) as $meetingLocation): ?>
              <option value="<?php echo htmlspecialchars((string)$meetingLocation); ?>"<?php echo (string)$meetingLocation === (string)($meetingPreview['location'] ?? '') ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$meetingLocation); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="playerbots-inline">
          <div class="playerbots-field">
            <label>Start</label>
            <input type="text" name="meeting_start" value="<?php echo htmlspecialchars((string)($meetingPreview['normalized_start'] ?? '')); ?>" placeholder="15:00">
          </div>
          <div class="playerbots-field">
            <label>End</label>
            <input type="text" name="meeting_end" value="<?php echo htmlspecialchars((string)($meetingPreview['normalized_end'] ?? '')); ?>" placeholder="18:00">
          </div>
        </div>
        <div class="playerbots-actions">
          <button class="playerbots-button" type="submit">Save Meeting</button>
          <span class="playerbots-note">Saved into guild MOTD as <code>Meeting: location HH:MM HH:MM</code>. The dropdown is expansion-aware and comes from the selected realm's playerbot travel node names.</span>
        </div>
      </form>
      <?php endif; ?>
      <div class="playerbots-preview" style="margin-top:12px;">
        <strong>Decoded State Preview</strong><br>
        <?php if (!empty($meetingPreview['found'])): ?>
          <?php if (!empty($meetingPreview['valid'])): ?>
            <?php echo htmlspecialchars((string)$meetingPreview['display']); ?>
          <?php else: ?>
            <span class="playerbots-empty"><?php echo htmlspecialchars((string)($meetingPreview['error'] ?? 'Meeting directive found, but it could not be parsed.')); ?></span>
          <?php endif; ?>
        <?php else: ?>
          <span class="playerbots-empty">No <code>Meeting:</code> directive found in the current MOTD.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="playerbots-card">
      <h3 class="playerbots-section-title">Guild Share / Orders</h3>
      <?php if (!empty($selectedGuild)): ?>
      <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="playerbots_action" value="save_share">
        <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
        <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
        <div class="playerbots-field">
          <label><code>Share:</code> Block</label>
          <textarea name="share_block" placeholder="Warrior: Elixir of the Mongoose 10&#10;All: Major Healing Potion 20"><?php echo htmlspecialchars((string)$shareBlock); ?></textarea>
        </div>
        <div class="playerbots-actions">
          <button class="playerbots-button" type="submit">Save Share Block</button>
          <span class="playerbots-note">Each line must be <code>&lt;filter&gt;: &lt;item&gt; &lt;amount&gt;, &lt;item&gt; &lt;amount&gt;</code>.</span>
        </div>
      </form>
      <?php endif; ?>
      <div class="playerbots-preview" style="margin-top:12px;">
        <strong>Decoded Share Preview</strong>
        <?php if (!empty($sharePreview['entries'])): ?>
          <table class="playerbots-table is-compact">
            <thead><tr><th>Filter</th><th>Items</th></tr></thead>
            <tbody>
              <?php foreach ($sharePreview['entries'] as $entry): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$entry['filter']); ?></td>
                  <td class="playerbots-note">
                    <?php
                    $parts = array();
                    foreach (($entry['items'] ?? array()) as $itemRow) {
                        $parts[] = (string)$itemRow['item_name'] . ' x' . (int)$itemRow['amount'];
                    }
                    echo htmlspecialchars(implode(', ', $parts));
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php elseif (!empty($sharePreview['errors'])): ?>
          <div class="playerbots-note"><?php echo implode('<br>', array_map('htmlspecialchars', $sharePreview['errors'])); ?></div>
        <?php else: ?>
          <span class="playerbots-empty">No <code>Share:</code> block is currently configured.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="playerbots-card">
    <h3 class="playerbots-section-title">Officer Order Notes</h3>
    <?php if (!empty($guildMembers)): ?>
    <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
      <input type="hidden" name="playerbots_action" value="save_notes">
      <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
      <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
      <table class="playerbots-table is-compact">
        <thead><tr><th>Character</th><th>Officer Note</th><th>Decoded</th></tr></thead>
        <tbody>
          <?php foreach ($orderPreview as $orderRow): $parsed = $orderRow['parsed']; ?>
            <tr>
              <td><?php echo htmlspecialchars((string)$orderRow['name']); ?></td>
              <td><input type="text" maxlength="31" name="offnote[<?php echo (int)$orderRow['guid']; ?>]" value="<?php echo htmlspecialchars((string)$orderRow['offnote']); ?>"></td>
              <td class="playerbots-note">
                <?php if (!empty($parsed['valid'])): ?>
                  <?php echo htmlspecialchars((string)($parsed['normalized'] !== '' ? $parsed['normalized'] : 'No order')); ?>
                <?php else: ?>
                  <?php echo htmlspecialchars((string)($parsed['error'] ?? 'Invalid order')); ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="playerbots-actions">
        <button class="playerbots-button" type="submit">Save Officer Notes</button>
        <span class="playerbots-note">Allowed: empty, <code>skip order</code>, or <code>Craft:/Farm:/Kill:/Explore:</code> notes.</span>
      </div>
    </form>
    <?php else: ?>
      <p class="playerbots-empty">No guild members were found for this guild.</p>
    <?php endif; ?>
  </div>

  <div class="playerbots-card">
    <h3 class="playerbots-section-title">Guild Strategy Flavor</h3>
    <p class="playerbots-note">Use this area as a comparison between two things: the unguilded random-bot baseline that makes bots feel like sloppy, social noobs, and the guild flavor layer that turns a guild into a culture. The baseline below is read-only reference. Saving here writes the shared guild layer into <code>preset='default'</code> for the selected guild.</p>
    <div class="playerbots-actions" style="margin-top:0;margin-bottom:12px;">
      <span class="playerbots-status"><?php echo $guildStrategyConsistent ? 'Sampled bots match' : ('Mixed sampled state across ' . $guildStrategyMixedCount . ' members'); ?></span>
      <span class="playerbots-status">Sample profile: <?php echo htmlspecialchars((string)(($guildStrategyProfiles[$guildStrategyProfileKey]['label'] ?? 'Custom'))); ?></span>
    </div>
    <div class="playerbots-preview" style="margin-bottom:12px;">
      <strong>Unguilded Baseline Reference</strong>
      <div class="playerbots-note" style="margin:8px 0 12px;"><?php echo htmlspecialchars((string)($randomBotBaselineProfile['description'] ?? '')); ?></div>
      <div class="playerbots-strategy-grid">
        <div class="playerbots-field">
          <label>Combat (<code>co</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['co'] ?? '')); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Non-Combat (<code>nc</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['nc'] ?? '')); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Dead (<code>dead</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['dead'] ?? '')); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Reaction (<code>react</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['react'] ?? '')); ?></textarea>
        </div>
      </div>
    </div>
    <?php if (!empty($selectedGuild)): ?>
    <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
      <input type="hidden" name="playerbots_action" value="save_strategy">
      <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
      <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
      <div class="playerbots-field">
        <label>Guild Flavor Preset</label>
        <select id="playerbots-guild-strategy-profile" onchange="sppPlayerbotsApplyStrategyProfile('guild', this.value)">
          <?php foreach (($guildStrategyProfiles ?? array()) as $profileKey => $profile): ?>
            <option value="<?php echo htmlspecialchars((string)$profileKey); ?>"<?php echo $profileKey === $guildStrategyProfileKey ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$profile['label']); ?> - <?php echo htmlspecialchars((string)$profile['description']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="playerbots-profile-grid" style="margin-bottom:12px;">
        <?php foreach (($guildStrategyProfiles ?? array()) as $profileKey => $profile): ?>
          <?php if ($profileKey === 'custom'): continue; endif; ?>
          <div class="playerbots-profile-card">
            <strong><?php echo htmlspecialchars((string)$profile['label']); ?></strong>
            <div class="playerbots-note"><?php echo htmlspecialchars((string)$profile['description']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="playerbots-strategy-grid">
        <div class="playerbots-field">
          <label>Guild Combat Layer (<code>co</code>)</label>
          <textarea id="playerbots-guild-strategy-co" name="strategy_co" placeholder="+dps,+dps assist,-threat"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['co'] ?? ($guildStrategyValues['co'] ?? ''))); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Guild Non-Combat Layer (<code>nc</code>)</label>
          <textarea id="playerbots-guild-strategy-nc" name="strategy_nc" placeholder="+rpg,+quest,+grind"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['nc'] ?? ($guildStrategyValues['nc'] ?? ''))); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Guild Dead Layer (<code>dead</code>)</label>
          <textarea id="playerbots-guild-strategy-dead" name="strategy_dead" placeholder="+auto release"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['dead'] ?? ($guildStrategyValues['dead'] ?? ''))); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Guild Reaction Layer (<code>react</code>)</label>
          <textarea id="playerbots-guild-strategy-react" name="strategy_react" placeholder="+pvp,+preheal"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['react'] ?? ($guildStrategyValues['react'] ?? ''))); ?></textarea>
        </div>
      </div>
      <div class="playerbots-actions">
        <button class="playerbots-button" type="submit">Apply Guild Flavor</button>
        <span class="playerbots-note">The baseline above is the noisy random-bot world. These presets are the “guild culture” layer that makes a leveling guild, quest guild, PvP guild, or profession guild feel intentionally different from it.</span>
      </div>
    </form>
    <?php endif; ?>
    <div class="playerbots-preview" style="margin-top:12px;">
      <strong>Sample Effective Strategy Preview</strong>
      <div class="playerbots-list" style="margin-top:8px;">
        <div class="playerbots-row"><strong>co</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['co'] ?? '') !== '' ? $guildStrategyValues['co'] : 'No sampled bot value')); ?></div></div>
        <div class="playerbots-row"><strong>nc</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['nc'] ?? '') !== '' ? $guildStrategyValues['nc'] : 'No sampled bot value')); ?></div></div>
        <div class="playerbots-row"><strong>dead</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['dead'] ?? '') !== '' ? $guildStrategyValues['dead'] : 'No sampled bot value')); ?></div></div>
        <div class="playerbots-row"><strong>react</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['react'] ?? '') !== '' ? $guildStrategyValues['react'] : 'No sampled bot value')); ?></div></div>
      </div>
    </div>
  </div>

  <div class="playerbots-card">
    <h3 class="playerbots-section-title">Bot Personality</h3>
      <div class="playerbots-field">
        <label>Character</label>
        <select onchange="window.location.href='index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid='+this.value;">
          <?php foreach ($guildMembers as $member): ?>
            <option value="<?php echo (int)$member['guid']; ?>"<?php echo (int)$member['guid'] === (int)$selectedCharacterGuid ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$member['name']); ?> (Lvl <?php echo (int)$member['level']; ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!empty($selectedCharacter)): ?>
      <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="playerbots_action" value="save_personality">
        <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
        <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
        <div class="playerbots-field">
          <label>LLM Personality Text</label>
          <textarea name="personality_text" placeholder="I am the keeper of the source."><?php echo htmlspecialchars((string)$selectedPersonality); ?></textarea>
        </div>
        <div class="playerbots-actions">
          <button class="playerbots-button" type="submit">Save Personality</button>
          <span class="playerbots-note">The editor starts from the current stored prompt for this bot and saves the newest full prompt snapshot back to <code>ai_playerbot_db_store</code>.</span>
        </div>
      </form>
      <?php else: ?>
        <p class="playerbots-empty">Select a guild member to edit bot personality text.</p>
      <?php endif; ?>
      <div class="playerbots-preview" style="margin-top:12px;">
        <strong>Decoded Personality</strong><br>
        <?php if ($selectedPersonality !== ''): ?>
          <code><?php echo htmlspecialchars((string)$selectedPersonality); ?></code>
        <?php else: ?>
          <span class="playerbots-empty">No stored personality prompt for the selected character.</span>
        <?php endif; ?>
      </div>

      <?php if (!empty($selectedCharacter)): ?>
      <div class="playerbots-preview" style="margin-top:12px;">
        <strong>Bot React / Role Builder</strong>
        <p class="playerbots-note" style="margin:8px 0 12px;">These fields start from the current effective strategy values for <strong><?php echo htmlspecialchars((string)($selectedCharacter['name'] ?? 'this bot')); ?></strong>, including the guild flavor stored in <code>preset='default'</code>. Saving here stores only this bot's override layer, so future guild flavor updates can still flow through.</p>
        <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
          <input type="hidden" name="playerbots_action" value="save_bot_strategy">
          <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
          <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
          <div class="playerbots-field">
            <label>Bot Role Preset</label>
            <select id="playerbots-bot-strategy-profile" onchange="sppPlayerbotsApplyStrategyProfile('bot', this.value)">
              <?php foreach (($botStrategyProfiles ?? array()) as $profileKey => $profile): ?>
                <option value="<?php echo htmlspecialchars((string)$profileKey); ?>"<?php echo $profileKey === $characterStrategyProfileKey ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$profile['label']); ?> - <?php echo htmlspecialchars((string)$profile['description']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="playerbots-profile-grid" style="margin-bottom:12px;">
            <?php foreach (($botStrategyProfiles ?? array()) as $profileKey => $profile): ?>
              <?php if ($profileKey === 'custom'): continue; endif; ?>
              <div class="playerbots-profile-card">
                <strong><?php echo htmlspecialchars((string)$profile['label']); ?></strong>
                <div class="playerbots-note"><?php echo htmlspecialchars((string)$profile['description']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="playerbots-strategy-grid">
            <div class="playerbots-field">
              <label>Combat (<code>co</code>)</label>
              <textarea id="playerbots-bot-strategy-co" name="strategy_co" placeholder="+dps,+dps assist,-threat"><?php echo htmlspecialchars((string)($characterStrategyValues['co'] ?? '')); ?></textarea>
            </div>
            <div class="playerbots-field">
              <label>Non-Combat (<code>nc</code>)</label>
              <textarea id="playerbots-bot-strategy-nc" name="strategy_nc" placeholder="+follow,+loot,+food"><?php echo htmlspecialchars((string)($characterStrategyValues['nc'] ?? '')); ?></textarea>
            </div>
            <div class="playerbots-field">
              <label>Dead (<code>dead</code>)</label>
              <textarea id="playerbots-bot-strategy-dead" name="strategy_dead" placeholder="+auto release"><?php echo htmlspecialchars((string)($characterStrategyValues['dead'] ?? '')); ?></textarea>
            </div>
            <div class="playerbots-field">
              <label>Reaction (<code>react</code>)</label>
              <textarea id="playerbots-bot-strategy-react" name="strategy_react" placeholder="+pvp,+preheal"><?php echo htmlspecialchars((string)($characterStrategyValues['react'] ?? '')); ?></textarea>
            </div>
          </div>
          <div class="playerbots-actions">
            <button class="playerbots-button" type="submit">Save Bot Role / React</button>
            <span class="playerbots-note">The editor starts from this bot's current saved strategy snapshot. Choosing a preset layers that role onto the current values instead of flattening the existing guild flavor.</span>
          </div>
        </form>
      </div>
      <?php endif; ?>
  </div>
</div>

<script>
window.playerbotsGuildStrategyProfiles = <?php echo json_encode($guildStrategyProfiles ?? array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.playerbotsBotStrategyProfiles = <?php echo json_encode($botStrategyProfiles ?? array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function sppPlayerbotsNormalizeStrategyValue(value) {
  return String(value || '')
    .replace(/\r\n?/g, '\n')
    .trim()
    .replace(/\s*\n\s*/g, '');
}

function sppPlayerbotsParseStrategyTokens(value) {
  var normalized = sppPlayerbotsNormalizeStrategyValue(value);
  if (!normalized) {
    return [];
  }

  return normalized.split(',').map(function (token) {
    return token.trim();
  }).filter(function (token) {
    return token !== '';
  });
}

function sppPlayerbotsStrategyTokenKey(token) {
  var normalized = String(token || '').trim();
  if (!normalized) {
    return '';
  }

  var prefix = normalized.charAt(0);
  if (prefix === '+' || prefix === '-' || prefix === '~') {
    normalized = normalized.slice(1);
  }

  return normalized.trim().toLowerCase();
}

function sppPlayerbotsMergeStrategyValue(currentValue, deltaValue) {
  var merged = {};
  var order = [];

  sppPlayerbotsParseStrategyTokens(currentValue).forEach(function (token) {
    var key = sppPlayerbotsStrategyTokenKey(token);
    if (!key) {
      return;
    }
    if (!Object.prototype.hasOwnProperty.call(merged, key)) {
      order.push(key);
    }
    merged[key] = token;
  });

  sppPlayerbotsParseStrategyTokens(deltaValue).forEach(function (token) {
    var key = sppPlayerbotsStrategyTokenKey(token);
    if (!key) {
      return;
    }
    if (!Object.prototype.hasOwnProperty.call(merged, key)) {
      order.push(key);
    }
    merged[key] = token;
  });

  return order.map(function (key) {
    return merged[key] || '';
  }).filter(function (token) {
    return token.trim() !== '';
  }).join(',');
}

function sppPlayerbotsApplyStrategyProfile(scope, profileKey) {
  var profiles = scope === 'bot' ? (window.playerbotsBotStrategyProfiles || {}) : (window.playerbotsGuildStrategyProfiles || {});
  if (!profiles[profileKey]) {
    return;
  }

  var profile = profiles[profileKey];
  var prefix = scope === 'bot' ? 'playerbots-bot-strategy-' : 'playerbots-guild-strategy-';
  var fields = {
    co: document.getElementById(prefix + 'co'),
    nc: document.getElementById(prefix + 'nc'),
    dead: document.getElementById(prefix + 'dead'),
    react: document.getElementById(prefix + 'react')
  };

  if (profileKey === 'custom') {
    return;
  }

  Object.keys(fields).forEach(function (key) {
    if (fields[key]) {
      if (scope === 'bot') {
        fields[key].value = sppPlayerbotsMergeStrategyValue(fields[key].value || '', profile[key] || '');
        return;
      }

      fields[key].value = profile[key] || '';
    }
  });
}
</script>
<?php builddiv_end(); ?>
