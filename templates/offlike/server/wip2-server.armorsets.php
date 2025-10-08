<?php
/* ---------- DEBUG MODE ---------- */
$DEBUG = isset($_GET['debug']) ? strtolower($_GET['debug']) : '';
if ($DEBUG) {
    echo "<div style='background:black;color:lime;padding:6px;font-weight:bold;'>DEBUG MODE: "
       . htmlspecialchars($DEBUG) . "</div>";
}

/* ---------- Banner ---------- */
?>
<img src="<?php echo $currtmp; ?>/images/armorsets.jpg" />
<br />
<?php builddiv_start(1, $lang['armorsets2']); ?>

<?php
/* =========================
   Armor page (tier/dungeon sets)
   ========================= */

/* ---------- Expansion ---------- */
$expansion = isset($GLOBALS['expansion']) ? (int)$GLOBALS['expansion'] : 1; // 0 Classic, 1 TBC, 2 WotLK

/* ---------- Schemas ---------- */
$SCHEMAS = [
    0 => ['armory' => 'classicarmory', 'world' => 'classicmangos'],
    1 => ['armory' => 'tbcarmory',     'world' => 'tbcmangos'],
    2 => ['armory' => 'wotlkarmory',   'world' => 'wotlkmangos'],
];
$ERA           = isset($SCHEMAS[$expansion]) ? $expansion : 1;
$ARMORY_SCHEMA = $SCHEMAS[$ERA]['armory'];
$WORLD_SCHEMA  = $SCHEMAS[$ERA]['world'];

/* ---------- Qualify table names ---------- */
function qualify_tables($sql, $schema, array $tables) {
    $pat = '/(?<=\bFROM|\bJOIN)\s+`?(' . implode('|', array_map('preg_quote', $tables)) . ')`?\b/i';
    return preg_replace($pat, ' `'.$schema.'`.`$1`', $sql);
}

/* ---------- DbSimple dispatcher ---------- */
function _dbsimple_run($conn, $sql, $mode = 0) {
    switch ($mode) {
        case 1: return $conn->selectRow($sql);
        case 2: return $conn->selectCell($sql);
        default:return $conn->select($sql);
    }
}

/* ---------- Armory query helper ---------- */
function armory_query($sql, $mode = 0) {
    global $DB, $ARMORY_SCHEMA;
    $sql = qualify_tables($sql, $ARMORY_SCHEMA, [
        'dbc_spell','dbc_spellicon','dbc_spellduration','dbc_spellradius',
        'dbc_itemset','dbc_talent','dbc_talenttab','dbc_itemdisplayinfo',
        'dbc_itemsubclass','dbc_itemrandomproperties','dbc_itemrandomsuffix',
        'dbc_randproppoints'
    ]);
    return _dbsimple_run($DB, $sql, $mode);
}

/* ---------- World query helper ---------- */
function world_query($sql, $mode = 0) {
    global $WSDB, $WORLD_SCHEMA;
    $sql = qualify_tables($sql, $WORLD_SCHEMA, ['item_template']);
    return _dbsimple_run($WSDB, $sql, $mode);
}

/* ---------- Tiny cache ---------- */
if (!function_exists('_cache')) {
    function _cache($k, $fn) {
        static $C=[];
        return $C[$k] ?? ($C[$k]=$fn());
    }
}
?>

<?php

/* ---------- Helpers ---------- */

/* Slot sort order */
if (!function_exists('slot_order')) {
    function slot_order($inv) {
        switch ((int)$inv) {
            case 1:  return 1;  // Head
            case 3:  return 2;  // Shoulder
            case 5:  return 3;  // Chest/Robe
            case 6:  return 4;  // Waist
            case 7:  return 5;  // Legs
            case 8:  return 6;  // Feet
            case 9:  return 7;  // Wrist
            case 10: return 8;  // Hands
            default: return 99;
        }
    }
}

/* Icon url helper */
if (!function_exists('icon_url')) {
    function icon_url($iconBase) {
        return '/armory/images/icons/64x64/'.$iconBase.'.png';
    }
}

/* Item icon from displayid */
function icon_from_displayid(int $displayId): string {
    if ($displayId <= 0) return 'inv_misc_questionmark';
    $row = armory_query("SELECT name FROM dbc_itemdisplayinfo WHERE id={$displayId} LIMIT 1", 1);
    if ($row && !empty($row['name'])) {
        return strtolower(pathinfo($row['name'], PATHINFO_FILENAME));
    }
    return 'inv_misc_key_02';
}

/* Spell icon from ref_spellicon */
if (!function_exists('icon_base_from_icon_id')) {
    function icon_base_from_icon_id(int $iconId): string {
        if ($iconId <= 0) return 'inv_misc_key_02';
        $r = armory_query("SELECT name FROM dbc_spellicon WHERE id={$iconId} LIMIT 1", 1);
        if ($r && !empty($r['name'])) {
            return strtolower(preg_replace('/[^a-z0-9_]/i', '', $r['name']));
        }
        return 'inv_misc_key_01';
    }
}

/* Inventory slot names */
function inventory_type_name(int $id): string {
    $map = [
        1=>"Head", 3=>"Shoulder", 5=>"Chest", 6=>"Waist", 7=>"Legs",
        8=>"Feet", 9=>"Wrist", 10=>"Hands", 16=>"Back", 20=>"Chest (Robe)",
    ];
    return $map[$id] ?? "Slot ".$id;
}

/* Class/subclass names */
function item_class_name(int $class, int $sub): string {
    if ($class == 4) {
        $armor = [1=>"Cloth",2=>"Leather",3=>"Mail",4=>"Plate"];
        return $armor[$sub] ?? "Armor";
    }
    return "Item";
}

/* Stat names (primary only; ratings handled elsewhere) */
function stat_name(int $id): string {
    static $map = [
        3 => 'Agility',
        4 => 'Strength',
        5 => 'Intellect',
        6 => 'Spirit',
        7 => 'Stamina',
    ];
    return $map[$id] ?? '';
}

/* Rating lines renderer */
function render_rating_lines(array $row): string {
    $ratingMap = [
        12=>'Defense Rating', 13=>'Dodge Rating', 14=>'Parry Rating', 15=>'Block Rating',
        16=>'Hit Rating (Melee)', 17=>'Hit Rating (Ranged)', 18=>'Hit Rating (Spell)',
        19=>'Crit Rating (Melee)', 20=>'Crit Rating (Ranged)', 21=>'Crit Rating (Spell)',
        25=>'Resilience Rating', 28=>'Haste Rating (Melee)', 29=>'Haste Rating (Ranged)',
        30=>'Haste Rating (Spell)', 31=>'Hit Rating', 32=>'Crit Rating',
        35=>'Resilience Rating', 36=>'Haste Rating', 37=>'Expertise Rating',
        44=>'Armor Penetration Rating', 47=>'Spell Penetration', 48=>'Block Value'
    ];
    $out = '';
    for ($i=1; $i<=10; $i++) {
        $t = (int)($row["stat_type{$i}"] ?? 0);
        $v = (int)($row["stat_value{$i}"] ?? 0);
        if ($t && $v && isset($ratingMap[$t])) {
            $out .= '<div style="color:#00ff00">Equip: Improves '.$ratingMap[$t].' by '.$v.'.</div>';
        }
    }
    return $out;
}

/* Item link */
if (!function_exists('item_href')) {
    function item_href(int $entry): string {
        return 'armory/index.php?searchType=iteminfo&item='.$entry;
    }
}

/* Duration helper */
if (!function_exists('spell_duration')) {
    function spell_duration(int $durId): string {
        if (!$durId) return '';
        $r = armory_query("SELECT * FROM dbc_spellduration WHERE id={$durId} LIMIT 1", 1);
        if (!$r) return '';
        $min = (int)$r['duration1']; 
        $max = (int)$r['duration2'];
        if ($min === $max) return ($min/1000).' sec';
        return ($min/1000).'–'.($max/1000).' sec';
    }
}

/* Radius helper */
if (!function_exists('spell_radius')) {
    function spell_radius(int $radId): string {
        if (!$radId) return '';
        $r = armory_query("SELECT * FROM dbc_spellradius WHERE id={$radId} LIMIT 1", 1);
        if (!$r) return '';
        return (float)$r['radius1'].' yd';
    }
}

/* Format helper */
if (!function_exists('fmt_value')) {
    function fmt_value($v) {
        return number_format($v, 0, '', '');
    }
}

/* Full token parser (copied from talent-calc) */
if (!function_exists('replace_spell_tokens')) {
    function replace_spell_tokens(string $desc, array $sp): string {
        // $s1..$s3
        for ($i=1; $i<=3; $i++) {
            $bp = (int)($sp["effect_basepoints_{$i}"] ?? 0) + 1;
            $desc = str_replace('$s'.$i, fmt_value($bp), $desc);
        }
        // $d
        if (strpos($desc, '$d') !== false) {
            $desc = str_replace('$d', spell_duration((int)($sp['ref_spellduration'] ?? 0)), $desc);
        }
        // $t1..$t3
        for ($i=1; $i<=3; $i++) {
            if (strpos($desc, '$t'.$i) !== false) {
                $desc = str_replace('$t'.$i, spell_radius((int)($sp["effect_radius_index_{$i}"] ?? 0)), $desc);
            }
        }
        // $o1..$o3
        for ($i=1; $i<=3; $i++) {
            if (strpos($desc, '$o'.$i) !== false) {
                $amp = (int)($sp["effect_amplitude_{$i}"] ?? 0);
                $dur = (int)($sp['ref_spellduration'] ?? 0);
                $ticks = ($amp > 0 && $dur > 0) ? floor($dur / $amp) : 0;
                $desc = str_replace('$o'.$i, $ticks, $desc);
            }
        }
        // $a1..$a3
        for ($i=1; $i<=3; $i++) {
            if (strpos($desc, '$a'.$i) !== false) {
                $amp = (int)($sp["effect_amplitude_{$i}"] ?? 0);
                $desc = str_replace('$a'.$i, ($amp/1000).' sec', $desc);
            }
        }
        // $<spellid>d / $<spellid>t
        if (preg_match_all('/\$(\d+)([a-z])/', $desc, $m, PREG_SET_ORDER)) {
            foreach ($m as $mm) {
                $otherId = (int)$mm[1];
                $tok     = $mm[2];
                $other   = armory_query("SELECT * FROM dbc_spell WHERE id={$otherId} LIMIT 1", 1);
                if (!$other) continue;
                $rep = '';
                if ($tok === 'd') {
                    $rep = spell_duration((int)($other['ref_spellduration'] ?? 0));
                }
                elseif ($tok === 't') {
                    $rep = spell_radius((int)($other['effect_radius_index_1'] ?? 0));
                }
                $desc = str_replace($mm[0], $rep, $desc);
            }
        }
        return $desc;
    }
}
/**
 * Fetch all items + bonuses for a given itemset.
 */
function get_itemset_data(int $setId): array {
    // --- Pull the set row ---
    $row = armory_query("SELECT * FROM dbc_itemset WHERE id={$setId} LIMIT 1", 1);
    if (!$row) {
        return [
            'id'      => $setId,
            'name'    => 'Unknown Set',
            'items'   => [],
            'bonuses' => [],
        ];
    }

    // --- Items loop ---
    $items = [];
    for ($i = 1; $i <= 10; $i++) {
        $itemId = (int)($row["item_$i"] ?? 0);
        if (!$itemId) continue;

        $sql = "
            SELECT it.entry, it.name, it.InventoryType, it.displayid
            FROM item_template it
            WHERE it.entry = {$itemId}
            LIMIT 1
        ";
        $item = world_query($sql, 1);

        if ($item) {
            $iconBase = icon_from_displayid((int)$item['displayid']);
            $items[] = [
                'entry' => (int)$item['entry'],
                'slot'  => (int)$item['InventoryType'],
                'name'  => (string)$item['name'],
                'icon'  => $iconBase,
            ];
        }
    }

    // --- Bonuses loop ---
    $bonuses = [];
    for ($b = 1; $b <= 8; $b++) {
        $bonusId = (int)($row["bonus_$b"] ?? 0);
        $pieces  = (int)($row["pieces_$b"] ?? 0);
        if (!$bonusId || !$pieces) continue;

        $sp = armory_query("SELECT * FROM dbc_spell WHERE id={$bonusId} LIMIT 1", 1);
        if ($sp) {
            $bonuses[] = [
                'pieces' => $pieces,
                'name'   => (string)($sp['name'] ?? ''),
                'desc'   => (string)($sp['description'] ?? ''),
                'icon'   => icon_base_from_icon_id((int)($sp['ref_spellicon'] ?? 0)),
                'spell'  => $sp, // keep raw spell row for token replacement
            ];
        }
    }

    return [
        'id'      => $setId,
        'name'    => (string)($row['name'] ?? 'Unknown Set'),
        'items'   => $items,
        'bonuses' => $bonuses,
    ];
}

/**
 * Render tooltip HTML for an item set’s bonuses.
 */
if (!function_exists('render_set_bonus_tip_html')) {
    function render_set_bonus_tip_html(array $setData): string {
        $h  = '<div class="tt-bonuses">';
        $h .= '<h5>'.htmlspecialchars($setData['name'] ?? 'Unknown Set').'</h5>';

        if (!empty($setData['bonuses'])) {
            // Sort by pieces needed (2,4,6,8…)
            usort($setData['bonuses'], function($a,$b){
                return ($a['pieces'] <=> $b['pieces']);
            });

            foreach ($setData['bonuses'] as $b) {
                $pieces = (int)$b['pieces'];
                $descRaw = (string)($b['desc'] ?? '');

                // Run through token parser if spell row is available
                if (!empty($b['spell'])) {
                    $descRaw = replace_spell_tokens($descRaw, $b['spell']);
                }

                // Escape for output
                $desc = htmlspecialchars($descRaw);

                $h .= '<div class="tt-bonus-row" '
                    . 'style="display:flex;gap:8px;align-items:flex-start;margin:6px 0;">'
                    .   '<div><div><b>('.$pieces.')</b> '.$desc.'</div></div>'
                    . '</div>';
            }
        } else {
            $h .= '<div class="tt-subtle">No set bonuses found.</div>';
        }

        $h .= '</div>';
        return $h;
    }
}
/**
 * Render one spell effect line
 */
function render_spell_effect(int $spellId, int $trigger): string {
    if ($spellId <= 0) return '';

    $sp = armory_query("SELECT * FROM dbc_spell WHERE id={$spellId} LIMIT 1", 1);
    if (!$sp) return '';

    $desc = replace_spell_tokens((string)$sp['description'], $sp);
    if ($desc === '') return '';

    $prefix = '';
    if ($trigger == 1) $prefix = 'Equip: ';
    elseif ($trigger == 2) $prefix = 'Use: ';
    elseif ($trigger == 4) $prefix = 'Chance on hit: ';

    return '<div style="color:#00ff00">'.$prefix.htmlspecialchars($desc).'</div>';
}

/**
 * Render full tooltip for an item
 */
function render_item_tip_html(array $item): string {
    $sql = "
        SELECT Quality, ItemLevel, InventoryType, class, subclass,
               RequiredLevel, Armor, MaxDurability, AllowableClass,
               holy_res, fire_res, nature_res, frost_res, shadow_res, arcane_res,
               stat_type1, stat_value1,
               stat_type2, stat_value2,
               stat_type3, stat_value3,
               stat_type4, stat_value4,
               stat_type5, stat_value5,
               stat_type6, stat_value6,
               stat_type7, stat_value7,
               stat_type8, stat_value8,
               stat_type9, stat_value9,
               stat_type10, stat_value10,
               spellid_1, spelltrigger_1,
               spellid_2, spelltrigger_2,
               spellid_3, spelltrigger_3,
               spellid_4, spelltrigger_4,
               spellid_5, spelltrigger_5
        FROM item_template
        WHERE entry = {$item['entry']}
        LIMIT 1
    ";
    $row = world_query($sql, 1);
    if (!$row) return '<div class="tt-error">No item data.</div>';

    $h  = '<div class="tt-item">';
    $h .= '<h4>'.htmlspecialchars($item['name']).'</h4>';

    // Slot + class
    $slotName  = inventory_type_name((int)$row['InventoryType']);
    $className = item_class_name((int)$row['class'], (int)$row['subclass']);
    $h .= '<div style="display:flex;justify-content:space-between;">'
        . '<div>'.$slotName.'</div>'
        . '<div>'.$className.'</div>'
        . '</div>';

    // Stats
    for ($i=1; $i<=10; $i++) {
        $t = (int)($row["stat_type{$i}"] ?? 0);
        $v = (int)($row["stat_value{$i}"] ?? 0);
        if ($t && $v) {
            $label = stat_name($t);
            if ($label !== '') {
                $h .= '<div>+'.$v.' '.$label.'</div>';
            }
        }
    }

    // Resistances
    $resMap = [
        'holy_res'   => 'Holy Resistance',
        'fire_res'   => 'Fire Resistance',
        'nature_res' => 'Nature Resistance',
        'frost_res'  => 'Frost Resistance',
        'shadow_res' => 'Shadow Resistance',
        'arcane_res' => 'Arcane Resistance'
    ];
    foreach ($resMap as $col => $name) {
        $val = (int)($row[$col] ?? 0);
        if ($val > 0) {
            $h .= '<div style="color:#00ccff">+'.$val.' '.$name.'</div>';
        }
    }

    // Ratings
    $h .= render_rating_lines($row);

    // Spells
    for ($i=1; $i<=5; $i++) {
        $sid = (int)($row["spellid_{$i}"] ?? 0);
        $trg = (int)($row["spelltrigger_{$i}"] ?? 0);
        $h  .= render_spell_effect($sid, $trg);
    }

    $h .= '</div>';
    return $h;
}
?>
<?php
/* ---------- Main Render Loop ---------- */

// Which sets to show (order defined here)
$order = array_keys($BLURB);

// Loop through each set
foreach ($order as $key) {
    if (!isset($BLURB[$key])) {
        echo '<div class="set-desc set-note">No data block found for '.$key.'.</div>';
        continue;
    }

    $title   = $BLURB[$key]['title'];
    $pieces  = (int)$BLURB[$key]['pieces'];
    $text    = $BLURB[$key]['text'];
    $pairs   = (!empty($N[$key][$selectedClass]))
             ? armor_set_variants($N[$key][$selectedClass])
             : [];

    // Output set heading
    echo '<div class="set-block">';
    echo '<h3>'.htmlspecialchars($title).'</h3>';
    echo '<div class="set-desc">'.$text.'</div>';

    // Show each variant of this set
    foreach ($pairs as $setId) {
        $setData = get_itemset_data($setId);

        echo '<div class="set-row">';
        echo render_set_bonus_tip_html($setData);

        echo '<div class="set-items">';
        foreach ($setData['items'] as $itm) {
            $tip = render_item_tip_html($itm);
            echo '<div class="set-item" style="display:inline-block;margin:4px;">';
            echo '<a href="'.item_href($itm['entry']).'">';
            echo '<img src="'.icon_url($itm['icon']).'" alt="" />';
            echo '</a>';
            echo '<div class="tt">'.$tip.'</div>';
            echo '</div>';
        }
        echo '</div>'; // .set-items

        echo '</div>'; // .set-row
    }

    echo '</div>'; // .set-block
}

builddiv_end();
?>


<style>

/* ---------- Class Bar (Classic Mode) ---------- */
.class-bar {
  position: sticky; top: 0; z-index: 20;
  display: flex; flex-wrap: wrap; gap: 10px;
  margin: 6px 0 16px; padding: 8px 6px;
  background: rgba(244,230,198,.92);
  border: 1px solid #c3a779; border-radius: 8px;
  backdrop-filter: saturate(160%) blur(2px);
}
/* Class icons (circle buttons) */
.class-token {
  --ring:#888; --glow:rgba(136,136,136,.55);
  width: 40px; height: 40px; border-radius: 999px;
  background: transparent; cursor: pointer;
  box-shadow: 0 0 0 2px rgba(0,0,0,.45) inset,
              0 0 0 2px rgba(255,255,255,.2);
  transition: transform .12s, box-shadow .12s, filter .12s;
}
.class-token img { width: 100%; height: 100%; border-radius: 999px; }
/* Hover + active effects */
.class-token:hover,
.class-token:focus {
  transform: translateY(-1px);
  filter: brightness(1.05);
  box-shadow: 0 0 0 2px rgba(0,0,0,.6) inset,
              0 0 0 2px var(--ring),
              0 0 16px var(--glow);
}
.class-token.is-active {
  box-shadow: 0 0 0 2px rgba(0,0,0,.7) inset,
              0 0 0 2px var(--ring),
              0 0 18px var(--glow);
}

/* Class-specific colors */
.is-warrior{--ring:#C79C6E;--glow:rgba(199,156,110,.6)}
.is-paladin{--ring:#F58CBA;--glow:rgba(245,140,186,.6)}
.is-hunter{--ring:#ABD473;--glow:rgba(171,212,115,.6)}
.is-rogue {--ring:#FFF569;--glow:rgba(255,245,105,.55)}
.is-priest{--ring:#FFF;    --glow:rgba(255,255,255,.55)}
.is-shaman{--ring:#0070DE; --glow:rgba(0,112,222,.55)}
.is-mage  {--ring:#40C7EB; --glow:rgba(64,199,235,.55)}
.is-warlock{--ring:#8787ED;--glow:rgba(135,135,237,.55)}
.is-druid {--ring:#FF7D0A; --glow:rgba(255,125,10,.55)}
.is-dk    {--ring:#C41F3B; --glow:rgba(196,31,59,.55)}

/* ---------- Tier Group Rows ---------- */
.set-group { margin: 20px 0 10px; }
.set-title { font-size: 20px; font-weight: 700; color: #6b2d1f; margin: 14px 0 6px; }
.set-subtitle { font-weight: 700; color: #7a3f28; }
.set-desc { margin: 2px 0 10px; }
.set-note { color: #7b6a52; display: block; margin-top: 2px; }
.note-inline { display: inline; color: #7b6a52; margin-left: 6px; }

/* ---------- Chips (placeholder slots) ---------- */
.set-items { display: inline; margin-left: 8px; color: #2b2114; }
.set-item {
  display: inline-block; padding: 1px 6px; margin: 0 3px;
  border-radius: 7px; background: rgba(0,0,0,.06);
  box-shadow: 0 1px 0 rgba(255,255,255,.2) inset,
              0 1px 2px rgba(0,0,0,.08);
  font-weight: 700; font-size: 12px; white-space: nowrap;
}
.set-item img {
  width: 14px; height: 14px; margin-right: 4px;
  border-radius: 3px; vertical-align: -2px;
}
.set-item.ghost { background: rgba(0,0,0,.05); color: #7b6a52; opacity: .95; }

/* ---------- Tooltip Frame ---------- */
.talent-tt {
  position: fixed; z-index: 9999;
  min-width: 220px; max-width: 360px; padding: 14px;
  background: rgba(16,24,48,.78);
  border: 1px solid rgba(200,220,255,.18); border-radius: 10px;
  color: #e9eefb; font: 14px/1.45 "Trebuchet MS", Arial, sans-serif;
  pointer-events: none; backdrop-filter: blur(2px);
}
.talent-tt h5 { margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #f1f6ff; }
.talent-tt .tt-subtle { font-size: 13px; opacity: .9; }

/* ---------- Item Icons inside Sets ---------- */
.set-row { display: flex; align-items: center; margin: 6px 0; }
.set-name { flex: 0 0 240px; font-weight: bold; }
.set-icons { display: flex; gap: 6px; margin-left: 10px; }
.set-icons img {
  border-radius: 4px;
  box-shadow: 0 0 0 1px rgba(255,255,255,.2),
              0 1px 2px rgba(0,0,0,.4);
  transition: transform .12s, box-shadow .12s;
}
.set-icons img:hover {
  transform: translateY(-2px) scale(1.08);
  box-shadow: 0 0 0 1px rgba(255,255,255,.35),
              0 2px 6px rgba(0,0,0,.6);
}
</style>

<script>
(function(){
  // Create tooltip div once
  const tip = document.createElement('div');
  tip.className = 'talent-tt';
  tip.style.display = 'none';
  document.body.appendChild(tip);

  let anchor = null;

  // Position tooltip above element
  function place(el){
    const pad = 8;
    const r = el.getBoundingClientRect();
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    const t = tip.getBoundingClientRect();
    let left = Math.max(6, Math.min(r.left + (r.width - t.width)/2, innerWidth - t.width - 6));
    let top  = Math.max(6, r.top - t.height - pad);
    tip.style.left = left+'px';
    tip.style.top  = top+'px';
    tip.style.visibility = 'visible';
  }

  // Show tooltip (decode HTML safely)
  function show(el){
    anchor = el;
    const raw = el.getAttribute('data-tip-html') || '';
    const ta  = document.createElement('textarea');
    ta.innerHTML = raw;
    tip.innerHTML = ta.value;
    place(el);
  }

  // Hide tooltip
  function hide(){ tip.style.display = 'none'; anchor=null; }

  // Re-position on scroll/resize
  function nudge(){ if(anchor && tip.style.display!=='none') place(anchor); }

  // Mouse events for both set tips + item tips
  document.addEventListener('mouseover', e=>{
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if(el) show(el);
  });
  document.addEventListener('mouseout', e=>{
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if(el && !(e.relatedTarget && el.contains(e.relatedTarget))) hide();
  });

  // Keep tooltip stuck to screen when user scrolls or resizes
  addEventListener('scroll', nudge, {passive:true});
  addEventListener('resize', nudge);
})();
</script>
