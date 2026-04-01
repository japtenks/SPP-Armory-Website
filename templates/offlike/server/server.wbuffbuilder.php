<?php
require_once(dirname(__FILE__, 4) . '/core/xfer/com_db.php');

$worldBuffLiveCatalog = array();

try {
    $worldPdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$world_db};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    if (isset($_GET['wbuff_lookup'])) {
        $term = trim((string)$_GET['wbuff_lookup']);
        header('Content-Type: application/json; charset=utf-8');
        if ($term === '') {
            echo json_encode(array());
            exit;
        }

        $stmt = $worldPdo->prepare("
            SELECT Id, SpellName, Rank1
            FROM spell_template
            WHERE SpellName <> ''
              AND (SpellName LIKE :term OR Id = :idExact)
            ORDER BY
              CASE WHEN SpellName LIKE :prefix THEN 0 ELSE 1 END,
              SpellName ASC
            LIMIT 25
        ");
        $stmt->execute(array(
            ':term' => '%' . $term . '%',
            ':prefix' => $term . '%',
            ':idExact' => ctype_digit($term) ? (int)$term : -1,
        ));
        $results = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $label = trim((string)$row['SpellName']);
            $rank = trim((string)($row['Rank1'] ?? ''));
            if ($rank !== '') {
                $label .= ' (' . $rank . ')';
            }
            $results[] = array(
                'id' => (string)$row['Id'],
                'label' => $label,
                'name' => trim((string)$row['SpellName']),
                'rank' => $rank,
            );
        }
        echo json_encode($results);
        exit;
    }

    $seedIds = array(
        17626,17627,17628,22888,24425,16609,22817,22818,22820,15366,24382,17538,11405,17539,
        11348,11371,25661,3593,16323,16327,16329,10668,10669,12174,12176,12177,24799,10693,
        18194,11474,10692,24361,24363,26276,22730,10305
    );
    $stmt = $worldPdo->query('SELECT Id, SpellName, Rank1 FROM spell_template WHERE Id IN (' . implode(',', $seedIds) . ')');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = trim((string)$row['SpellName']);
        $rank = trim((string)($row['Rank1'] ?? ''));
        if ($name !== '') {
            $worldBuffLiveCatalog[(string)$row['Id']] = $rank !== '' ? ($name . ' (' . $rank . ')') : $name;
        }
    }
} catch (Throwable $e) {
    $worldBuffLiveCatalog = array();
}

$worldBuffSpellCatalog = array(
    '17626' => 'Flask of the Titans',
    '17627' => 'Flask of Distilled Wisdom',
    '17628' => 'Flask of Supreme Power',
    '22888' => 'Rallying Cry of the Dragonslayer',
    '24425' => 'Spirit of Zandalar',
    '16609' => 'Warchief\'s Blessing',
    '22817' => 'Fengus\' Ferocity (Dire Maul)',
    '22818' => 'Mol\'dar\'s Moxie (Dire Maul)',
    '22820' => 'Slip\'kik\'s Savvy (Dire Maul)',
    '15366' => 'Songflower Serenade',
    '24382' => 'Sayge\'s Dark Fortune',
    '17538' => 'Elixir of the Mongoose',
    '11405' => 'Elixir of Giants',
    '17539' => 'Greater Arcane Elixir',
    '11348' => 'Elixir of Greater Agility',
    '11371' => 'Juju Power',
    '25661' => 'Rumsey Rum Black Label',
    '3593'  => 'Guide utility consumable',
    '16323' => 'Guide melee consumable',
    '16327' => 'Guide caster consumable',
    '16329' => 'Guide physical consumable',
    '10668' => 'Guide tank consumable',
    '10669' => 'Guide melee elixir',
    '12174' => 'Guide stamina / tank consumable',
    '12176' => 'Guide caster food or elixir',
    '12177' => 'Guide caster food or elixir',
    '24799' => 'Guide threat or melee consumable',
    '10693' => 'Guide healing consumable',
    '18194' => 'Guide mana or healer consumable',
    '11474' => 'Guide shadow or caster consumable',
    '10692' => 'Guide caster damage consumable',
    '24361' => 'Spirit of Zanza or shared raid buff',
    '24363' => 'Guide mana or caster world buff',
    '26276' => 'Guide caster weapon or oil buff',
    '22730' => 'Guide caster damage consumable',
);

foreach ($worldBuffLiveCatalog as $spellId => $spellLabel) {
    $worldBuffSpellCatalog[$spellId] = $spellLabel;
}

$worldBuffClasses = array(
    '1' => array(
        'label' => 'Warrior',
        'presets' => array(
            array('key' => 'warrior_0', 'spec' => 0, 'name' => 'pve arms', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 11348, 24361, 11371, 25661, 3593, 16323, 16329, 10668)),
            array('key' => 'warrior_1', 'spec' => 1, 'name' => 'pve fury', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 17538, 22818, 15366, 11405, 10669, 11348, 24361, 11371, 24799, 3593, 16323, 16329, 12174)),
            array('key' => 'warrior_2', 'spec' => 2, 'name' => 'pve prot', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 17538, 22818, 15366, 11405, 10669, 11348, 24361, 11371, 24799, 3593, 16323, 16329, 12174)),
            array('key' => 'warrior_3', 'spec' => 3, 'name' => 'pvp arms', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 22888, 24382, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 11348, 24361, 11371, 25661, 3593, 16323, 16329, 10668, 12174, 10305)),
        ),
    ),
    '2' => array(
        'label' => 'Paladin',
        'presets' => array(
            array('key' => 'paladin_1', 'spec' => 1, 'name' => 'pve dps ret (geared ret)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22817, 22818, 22820, 15366, 24363, 24361, 3593, 12176, 12177)),
            array('key' => 'paladin_2', 'spec' => 2, 'name' => 'pve heal holy (sanctuary)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22817, 22818, 22820, 15366, 17538, 11405, 17539, 11348, 24361, 11371, 25661, 3593, 16323, 16329, 10668, 12174, 10305)),
            array('key' => 'paladin_3', 'spec' => 3, 'name' => 'pve heal holy (prot holy shock taunt)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22817, 22818, 22820, 15366, 17538, 11405, 17539, 24363, 11348, 24361, 24799, 3593, 16323, 16329)),
        ),
    ),
    '3' => array(
        'label' => 'Hunter',
        'presets' => array(
            array('key' => 'hunter_0', 'spec' => 0, 'name' => 'pve dps mm (mm/sv)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 10669, 24361, 3593, 16329, 16327, 12174, 12176, 12177)),
        ),
    ),
    '4' => array(
        'label' => 'Rogue',
        'presets' => array(
            array('key' => 'rogue_0', 'spec' => 0, 'name' => 'pve dps assassination', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17626, 24382, 22888, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 10669, 11348, 24361, 24799, 3593, 16323, 16329, 24799, 12174)),
        ),
    ),
    '5' => array(
        'label' => 'Priest',
        'presets' => array(
            array('key' => 'priest_1', 'spec' => 1, 'name' => 'pve heal holy', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 24363, 24361, 3593, 10693, 18194, 16327, 12176, 12177)),
            array('key' => 'priest_2', 'spec' => 2, 'name' => 'pve dps shadow', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17627, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 24363, 24361, 3593, 10693, 18194, 16327, 12176, 12177)),
            array('key' => 'priest_3', 'spec' => 3, 'name' => 'pvp dps disc', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 11474, 24363, 24361, 3593, 10693, 18194, 16327, 12176, 12177)),
        ),
    ),
    '7' => array(
        'label' => 'Shaman',
        'presets' => array(
            array('key' => 'shaman_1', 'spec' => 1, 'name' => 'pve dps elem (nature\'s swiftness)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 17539, 10692, 24363, 24361, 3593, 18194, 16327, 12176, 12177)),
            array('key' => 'shaman_2', 'spec' => 2, 'name' => 'pve dps elem (hand of edward the odd)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22817, 22818, 15366, 17538, 11405, 24363, 11348, 24361, 3593, 18194, 10668, 16323)),
        ),
    ),
    '8' => array(
        'label' => 'Mage',
        'presets' => array(
            array('key' => 'mage_0', 'spec' => 0, 'name' => 'pve dps arcane', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 17539, 10692, 24363, 24361, 26276, 3593, 16327, 12176, 12177, 22730)),
        ),
    ),
    '9' => array(
        'label' => 'Warlock',
        'presets' => array(
            array('key' => 'warlock_0', 'spec' => 0, 'name' => 'pve dps demo (ds/ruin)', 'faction' => 0, 'min' => 60, 'max' => 60, 'spells' => array(17628, 24382, 22888, 24425, 16609, 22818, 22820, 15366, 11474, 10692, 24363, 24361, 3593, 16327, 12176, 12177, 22730)),
        ),
    ),
    '11' => array(
        'label' => 'Druid',
        'presets' => array(),
    ),
);
?>

<?php builddiv_start(1, 'World Buff Builder'); ?>
<style>
.wb-page { max-width: 1180px; }
.wb-grid { display:grid; grid-template-columns:minmax(300px, 420px) minmax(320px, 1fr); gap:16px; }
.wb-card { background:#1e1e1e; border:1px solid #383838; border-radius:8px; padding:14px; margin-bottom:16px; }
.wb-card h3 { color:#f0c070; margin:0 0 10px; font-size:14px; border-bottom:1px solid #444; padding-bottom:4px; }
.wb-card p { color:#bbb; font-size:13px; margin:4px 0 10px; }
.wb-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:10px; }
.wb-label { width:90px; color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
.wb-field { flex:1; min-width:180px; }
.wb-field-small { width:110px; }
.wb-input, .wb-select, .wb-textarea { width:100%; background:#1a1a1a; border:1px solid #444; color:#ddd; padding:6px 8px; border-radius:4px; font-size:13px; }
.wb-input:focus, .wb-select:focus, .wb-textarea:focus { outline:none; border-color:#f0c070; }
.wb-textarea { min-height:120px; resize:vertical; font-family:monospace; }
.wb-btn { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600; }
.wb-btn-add { background:#2a4a2a; color:#7ec87e; }
.wb-btn-add:hover { background:#3a5a3a; }
.wb-btn-del { background:#4a2a2a; color:#e07e7e; }
.wb-btn-del:hover { background:#5a3a3a; }
.wb-btn-copy { background:#2a3a4a; color:#7ec8e3; }
.wb-btn-copy:hover { background:#3a4a5a; }
.wb-preset-list { display:flex; flex-direction:column; gap:8px; max-height:420px; overflow:auto; }
.wb-preset-btn { width:100%; text-align:left; background:#161616; border:1px solid #3b3b3b; border-radius:6px; padding:10px 12px; color:#ddd; cursor:pointer; }
.wb-preset-btn:hover, .wb-preset-btn.active { border-color:#f0c070; background:#202020; }
.wb-preset-btn strong { display:block; color:#f0c070; margin-bottom:4px; }
.wb-preset-btn span { display:block; color:#999; font-size:12px; }
.wb-tag-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
.wb-tag { background:#151515; border:1px solid #494949; border-radius:999px; padding:4px 10px; color:#d7c29b; font-size:12px; }
.wb-spell-row { display:grid; grid-template-columns:minmax(260px, 1.4fr) minmax(110px, .6fr) auto; gap:8px; align-items:center; margin-bottom:8px; }
.wb-spell-name { width:100%; background:#1a1a1a; border:1px solid #444; color:#ddd; padding:6px 8px; border-radius:4px; font-size:13px; }
.wb-spell-name:focus { outline:none; border-color:#f0c070; }
.wb-copy-row { display:flex; gap:8px; align-items:flex-start; }
.wb-output { flex:1; background:#111; border:1px solid #333; border-radius:6px; padding:12px 14px; font-family:monospace; font-size:12px; color:#aed6a0; white-space:pre-wrap; word-break:break-all; min-height:42px; }
.wb-note { color:#999; font-size:12px; }
.wb-back { display:inline-flex; align-items:center; margin-bottom:12px; color:#7ec8e3; font-weight:600; text-decoration:none; }
.wb-back:hover { color:#a9def0; text-decoration:underline; }
@media (max-width: 960px) {
  .wb-grid { grid-template-columns:1fr; }
  .wb-spell-row { grid-template-columns:1fr; }
}
</style>

<div class="modern-content wb-page">
  <a class="wb-back" href="/index.php?n=server&sub=botcommands">&#8592; Back to Bot Guide</a>
  <div class="wb-card">
    <h3>World Buff Builder</h3>
    <p>Build <code>AiPlayerbot.WorldBuff...</code> config lines for class-specific Vanilla raid prep. Starter presets on this page are adapted from Ile's raid guide, and you can tweak every field before copying the final line.</p>
    <div class="wb-tag-row">
      <span class="wb-tag">Config key: <code>AiPlayerbot.WorldBuff</code></span>
      <span class="wb-tag">Travel strategy: <code>wbuff travel</code></span>
      <span class="wb-tag">Fast apply: <code>/ra nc +wbuff</code></span>
    </div>
  </div>

  <div class="wb-grid">
    <div>
      <div class="wb-card">
        <h3>Starter Presets</h3>
        <p>Pick a class, then load one of the available starter presets. If a class has no starter entry yet, you can still build the line manually.</p>
        <div class="wb-row">
          <span class="wb-label">Class</span>
          <div class="wb-field">
            <select id="wb-class-filter" class="wb-select" onchange="wbRenderPresetList()"></select>
          </div>
        </div>
        <div id="wb-preset-list" class="wb-preset-list"></div>
      </div>

      <div class="wb-card">
        <h3>Builder</h3>
        <div class="wb-row">
          <span class="wb-label">Faction</span>
          <div class="wb-field">
            <select id="wb-faction" class="wb-select" onchange="wbUpdateOutput()">
              <option value="0">0 = all bots</option>
              <option value="1">1 = alliance</option>
              <option value="2">2 = horde</option>
            </select>
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Class ID</span>
          <div class="wb-field">
            <select id="wb-class" class="wb-select" onchange="wbHandleClassChange()"></select>
          </div>
          <div class="wb-field">
            <input id="wb-class-label" class="wb-input" type="text" readonly>
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Spec ID</span>
          <div class="wb-field">
            <select id="wb-spec" class="wb-select" onchange="wbUpdateOutput()"></select>
          </div>
          <div class="wb-field">
            <input id="wb-spec-label" class="wb-input" type="text" readonly>
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Levels</span>
          <div class="wb-field wb-field-small">
            <input id="wb-min" class="wb-input" type="number" min="1" max="80" value="60" oninput="wbUpdateOutput()">
          </div>
          <div class="wb-field wb-field-small">
            <input id="wb-max" class="wb-input" type="number" min="1" max="80" value="60" oninput="wbUpdateOutput()">
          </div>
          <div class="wb-field">
            <input id="wb-event" class="wb-input" type="number" min="0" placeholder="Optional event id" oninput="wbUpdateOutput()">
          </div>
        </div>
        <div class="wb-row">
          <span class="wb-label">Preset Name</span>
          <div class="wb-field">
            <input id="wb-name" class="wb-input" type="text" placeholder="Optional note for yourself" oninput="wbUpdateOutput()">
          </div>
        </div>
      </div>
    </div>

    <div>

      <div class="wb-card">
        <h3>Buff List</h3>
        <p>Choose buffs from the available list. Each buff can only be selected once, and the spell ID updates automatically from that choice.</p>
        <div id="wb-spells"></div>
        <button type="button" class="wb-btn wb-btn-add" onclick="wbAddSpell()">+ Add Buff</button>
      </div>

      <div class="wb-card">
        <h3>Output</h3>
        <div class="wb-note">Config line</div>
        <div class="wb-copy-row">
          <div id="wb-out-line" class="wb-output"></div>
          <button type="button" class="wb-btn wb-btn-copy" onclick="wbCopy('wb-out-line')">Copy</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const WB_CLASSES = <?php echo json_encode($worldBuffClasses); ?>;
const WB_SPELL_CATALOG = <?php echo json_encode($worldBuffSpellCatalog); ?>;
let wbSelectedPreset = '';
let wbSpellRows = [{ id: '', label: '' }];

function wbSpellLabel(spellId) {
  return WB_SPELL_CATALOG[String(spellId)] || '';
}

function wbRegisterLookupEntry(entry) {
  if (!entry || !entry.id || !entry.label) {
    return;
  }
  WB_SPELL_CATALOG[String(entry.id)] = String(entry.label);
}

function wbClassLabel(classId) {
  return (WB_CLASSES[classId] && WB_CLASSES[classId].label) ? WB_CLASSES[classId].label : '';
}

function wbPresetLabel(classId, specId) {
  const classData = WB_CLASSES[classId];
  if (!classData) return 'custom';
  const preset = (classData.presets || []).find(function(item) { return String(item.spec) === String(specId); });
  return preset ? preset.name : 'custom';
}

function wbPopulateClassSelects() {
  const filterSelect = document.getElementById('wb-class-filter');
  const classSelect = document.getElementById('wb-class');
  const options = ['<option value=\"all\">All classes</option>'];
  const builderOptions = [];
  Object.keys(WB_CLASSES).forEach(function(classId) {
    const label = WB_CLASSES[classId].label + ' (' + classId + ')';
    options.push('<option value=\"' + classId + '\">' + label + '</option>');
    builderOptions.push('<option value=\"' + classId + '\">' + label + '</option>');
  });
  filterSelect.innerHTML = options.join('');
  classSelect.innerHTML = builderOptions.join('');
  classSelect.value = '1';
}

function wbRenderPresetList() {
  const classFilter = document.getElementById('wb-class-filter').value;
  const container = document.getElementById('wb-preset-list');
  let html = '';
  Object.keys(WB_CLASSES).forEach(function(classId) {
    if (classFilter !== 'all' && classFilter !== classId) {
      return;
    }
    const classData = WB_CLASSES[classId];
    if (!classData.presets || !classData.presets.length) {
      html += '<div class="wb-preset-btn"><strong>' + classData.label + '</strong><span>No starter presets yet. Use the builder manually.</span></div>';
      return;
    }
    classData.presets.forEach(function(preset) {
      const activeClass = wbSelectedPreset === preset.key ? ' active' : '';
      html += '<button type="button" class="wb-preset-btn' + activeClass + '" onclick="wbLoadPreset(\'' + preset.key + '\')">';
      html += '<strong>' + classData.label + ' - ' + preset.name + '</strong>';
      html += '<span>Spec ' + preset.spec + ' | ' + preset.spells.length + ' spell ids | level ' + preset.min + '-' + preset.max + '</span>';
      html += '</button>';
    });
  });
  container.innerHTML = html || '<div class="wb-preset-btn"><strong>No presets found</strong><span>Pick a different class filter or build a line manually.</span></div>';
}

function wbHandleClassChange() {
  const classId = document.getElementById('wb-class').value;
  const classData = WB_CLASSES[classId] || { presets: [] };
  const specSelect = document.getElementById('wb-spec');
  const options = ['<option value=\"0\">0 = custom / default</option>'];
  (classData.presets || []).forEach(function(preset) {
    options.push('<option value=\"' + preset.spec + '\">' + preset.spec + ' = ' + preset.name + '</option>');
  });
  specSelect.innerHTML = options.join('');
  document.getElementById('wb-class-label').value = wbClassLabel(classId);
  document.getElementById('wb-spec-label').value = wbPresetLabel(classId, specSelect.value);
  wbUpdateOutput();
}

function wbLoadPreset(presetKey) {
  wbSelectedPreset = presetKey;
  let found = null;
  Object.keys(WB_CLASSES).some(function(classId) {
    return (WB_CLASSES[classId].presets || []).some(function(preset) {
      if (preset.key === presetKey) {
        found = { classId: classId, preset: preset };
        return true;
      }
      return false;
    });
  });
  if (!found) {
    return;
  }
  document.getElementById('wb-class').value = found.classId;
  wbHandleClassChange();
  document.getElementById('wb-spec').value = String(found.preset.spec);
  document.getElementById('wb-faction').value = String(found.preset.faction);
  document.getElementById('wb-min').value = String(found.preset.min);
  document.getElementById('wb-max').value = String(found.preset.max);
  document.getElementById('wb-event').value = found.preset.event ? String(found.preset.event) : '';
  document.getElementById('wb-name').value = found.preset.name;
  wbSpellRows = found.preset.spells.map(function(spellId) {
    return { id: String(spellId), label: wbSpellLabel(spellId) || 'Guide buff' };
  });
  wbRenderSpells();
  wbRenderPresetList();
}

function wbSelectedSpellIds(excludeIndex) {
  const selected = {};
  wbSpellRows.forEach(function(row, index) {
    if (index === excludeIndex) {
      return;
    }
    const spellId = String((row && row.id) || '').trim();
    if (spellId) {
      selected[spellId] = true;
    }
  });
  return selected;
}

function wbSpellSelectOptions(currentId, index) {
  const taken = wbSelectedSpellIds(index);
  const options = [];
  Object.keys(WB_SPELL_CATALOG).sort(function(a, b) {
    return WB_SPELL_CATALOG[a].localeCompare(WB_SPELL_CATALOG[b]);
  }).forEach(function(id) {
    if (taken[id] && String(id) !== String(currentId || '')) {
      return;
    }
    const selected = String(id) === String(currentId || '') ? ' selected' : '';
    options.push('<option value="' + id + '"' + selected + '>' + WB_SPELL_CATALOG[id].replace(/"/g, '&quot;') + '</option>');
  });

  if (!options.length) {
    options.push('<option value="">No more buffs available</option>');
  }

  return options.join('');
}

function wbFirstAvailableSpellId() {
  const taken = wbSelectedSpellIds(-1);
  const ids = Object.keys(WB_SPELL_CATALOG).sort(function(a, b) {
    return WB_SPELL_CATALOG[a].localeCompare(WB_SPELL_CATALOG[b]);
  });
  for (let i = 0; i < ids.length; i += 1) {
    if (!taken[ids[i]]) {
      return ids[i];
    }
  }
  return '';
}

function wbRenderSpells() {
  const container = document.getElementById('wb-spells');
  container.innerHTML = wbSpellRows.map(function(row, index) {
    const safeId = String(row.id || '').replace(/"/g, '&quot;');
    return '<div class="wb-spell-row">' +
      '<select class="wb-spell-name" onchange="wbSetSpellSelect(' + index + ', this.value)">' + wbSpellSelectOptions(row.id, index) + '</select>' +
      '<input class="wb-input" type="text" value="' + safeId + '" placeholder="Spell id" readonly>' +
      '<button type="button" class="wb-btn wb-btn-del" onclick="wbRemoveSpell(' + index + ')">Remove</button>' +
      '</div>';
  }).join('');
  document.getElementById('wb-spec-label').value = wbPresetLabel(document.getElementById('wb-class').value, document.getElementById('wb-spec').value);
  wbUpdateOutput();
}

function wbAddSpell() {
  const spellId = wbFirstAvailableSpellId();
  if (!spellId) {
    return;
  }
  wbSpellRows.push({ id: spellId, label: wbSpellLabel(spellId) });
  wbRenderSpells();
}

function wbSetSpellSelect(index, value) {
  const id = String(value || '').trim();
  wbSpellRows[index] = { id: id, label: wbSpellLabel(id) };
  wbRenderSpells();
}

function wbRemoveSpell(index) {
  wbSpellRows.splice(index, 1);
  if (!wbSpellRows.length) {
    wbSpellRows = [{ id: '', label: '' }];
  }
  wbRenderSpells();
}

function wbNormalizedSpells() {
  const normalized = [];
  wbSpellRows.forEach(function(row) {
    const trimmed = String((row && row.id) || '').trim();
    if (trimmed) {
      normalized.push(trimmed);
    }
  });
  return normalized;
}

function wbUpdateOutput() {
  const faction = document.getElementById('wb-faction').value || '0';
  const classId = document.getElementById('wb-class').value || '0';
  const specId = document.getElementById('wb-spec').value || '0';
  const minLevel = document.getElementById('wb-min').value || '1';
  const maxLevel = document.getElementById('wb-max').value || minLevel;
  const eventId = document.getElementById('wb-event').value.trim();
  const spellIds = wbNormalizedSpells();
  document.getElementById('wb-class-label').value = wbClassLabel(classId);
  document.getElementById('wb-spec-label').value = wbPresetLabel(classId, specId);

  let key = 'AiPlayerbot.WorldBuff.' + faction + '.' + classId + '.' + specId + '.' + minLevel + '.' + maxLevel;
  if (eventId) {
    key += '.' + eventId;
  }
  document.getElementById('wb-out-line').textContent = spellIds.length ? (key + ' = ' + spellIds.join(',')) : '(add one or more spell ids to build the line)';
}

function wbCopy(id) {
  const text = document.getElementById(id).textContent || '';
  if (!text || text.charAt(0) === '(') {
    return;
  }
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text);
    return;
  }
  const area = document.createElement('textarea');
  area.value = text;
  document.body.appendChild(area);
  area.select();
  document.execCommand('copy');
  document.body.removeChild(area);
}

document.addEventListener('DOMContentLoaded', function() {
  wbPopulateClassSelects();
  wbRenderPresetList();
  wbHandleClassChange();
  wbRenderSpells();
  wbLoadPreset('warrior_2');
});
</script>
<?php builddiv_end(); ?>
