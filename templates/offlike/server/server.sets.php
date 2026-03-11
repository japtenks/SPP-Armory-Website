<?php
$section = strtolower(trim($_GET['section'] ?? 'misc'));
$providerMap = [
    'misc'  => __DIR__ . '/_sets_class.php',
    'world' => __DIR__ . '/_sets_world.php',
    'pvp'   => __DIR__ . '/_sets_pvp.php',
];

if (!isset($providerMap[$section])) {
    $section = 'misc';
}

ob_start();
include $providerMap[$section];
ob_end_clean();

$sectionTitles = [
    'misc'  => 'Misc & Tier Sets',
    'world' => 'World Sets',
    'pvp'   => 'PvP Sets',
];

if (!function_exists('sets_colorize_desc')) {
    function sets_colorize_desc($text) {
        $text = str_replace(['<b>', '</b>'], '', $text);
        $classesToCSS = [
            'Warrior'      => 'is-warrior',
            'Paladin'      => 'is-paladin',
            'Hunter'       => 'is-hunter',
            'Rogue'        => 'is-rogue',
            'Priest'       => 'is-priest',
            'Shaman'       => 'is-shaman',
            'Mage'         => 'is-mage',
            'Warlock'      => 'is-warlock',
            'Druid'        => 'is-druid',
            'Death Knight' => 'is-dk'
        ];

        return preg_replace_callback(
            '/\b(' . implode('|', array_map('preg_quote', array_keys($classesToCSS))) . ')(s)?\b/i',
            function($m) use ($classesToCSS) {
                $name = ucwords(strtolower($m[1]));
                if (strtolower($m[1]) === 'death knight') {
                    $name = 'Death Knight';
                }
                $css = $classesToCSS[$name] ?? '';
                $suffix = $m[2] ?? '';
                return "<span class='{$css}'><b>{$name}{$suffix}</b></span>";
            },
            $text
        );
    }
}

foreach ($classes as $c) {
    if (strcasecmp($selectedClass, $c['name']) === 0) {
        $selectedClass = $c['name'];
        break;
    }
}

if ($selectedClass === '') {
    $rand = $classes[array_rand($classes)];
    $selectedClass = $rand['name'];
}
?>
<style>
.sets-tabs {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin: 0 auto 18px;
  flex-wrap: wrap;
}
.sets-tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 140px;
  padding: 10px 16px;
  border: 1px solid rgba(255, 193, 7, 0.55);
  border-radius: 12px;
  color: #ffcc66;
  text-decoration: none;
  font-weight: 700;
  background: rgba(0, 0, 0, 0.35);
}
.sets-tab.is-active {
  background: linear-gradient(180deg, rgba(255, 193, 7, 0.28), rgba(255, 140, 0, 0.12));
  box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.18) inset;
}
</style>
<?php builddiv_start(1, $sectionTitles[$section], 1); ?>
<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/armorsets.jpg" alt="<?php echo htmlspecialchars($sectionTitles[$section]); ?>" class="banner"/>

  <div class="sets-tabs">
    <?php
    foreach ($sectionTitles as $key => $label) {
        $active = ($section === $key) ? ' is-active' : '';
        echo '<a class="sets-tab' . $active . '" href="index.php?n=server&sub=sets&section=' . $key . '&class=' . urlencode($selectedClass) . '&realm=' . (int)$realmId . '">' . htmlspecialchars($label) . '</a>';
    }
    ?>
  </div>

  <?php
  echo '<div class="class-bar">';
  foreach ($classes as $c) {
      $className = $c['name'];
      $slug = $c['slug'];
      $href = 'index.php?n=server&sub=sets&section=' . $section . '&class=' . urlencode($className) . '&realm=' . (int)$realmId;
      $src = $iconBase . $iconPref . $slug . $iconExt;
      $active = (strcasecmp($selectedClass, $className) === 0) ? ' is-active' : '';
      echo '<a class="class-token ' . $c['css'] . $active . '" href="' . $href . '" aria-label="' . htmlspecialchars($className) . '" data-name="' . htmlspecialchars($className) . '"><img src="' . $src . '" alt="' . htmlspecialchars($className) . '"></a>';
  }
  echo '</div>';

  if ($section === 'misc') {
      echo '<div class="set-group"><div class="set-title">Dungeon & Tier Sets</div>';
      foreach ($tierOrder as $key) {
          if (preg_match('/^T(\d+)/', $key, $m) && (int)$m[1] > $maxTier) {
              continue;
          }
          if (empty($TIER_BLURB[$key])) {
              continue;
          }

          $title = $TIER_BLURB[$key]['title'];
          $pieces = (int)$TIER_BLURB[$key]['pieces'];
          $text = sets_colorize_desc($TIER_BLURB[$key]['text']);
          $pairs = !empty($tier_N[$key][$selectedClass]) ? armor_set_variants($tier_N[$key][$selectedClass]) : [];
          if (!$pairs && !empty($tier_N[$key][$selectedClass])) {
              $pairs = [['name' => $tier_N[$key][$selectedClass], 'role' => '']];
          }
          if (empty($pairs)) {
              continue;
          }

          echo "<div class='set-block'>";
          echo "<div class='set-title'>" . htmlspecialchars($title) . "</div>";
          echo "<div class='set-desc'>{$text}</div>";

          foreach ($pairs as $p) {
              $nm = trim($p['name']);
              $setId = find_itemset_id_by_name($nm);
              $tipHtml = '';
              $itemsHtml = '';

              if ($setId) {
                  $data = get_itemset_data($setId);
                  $tipHtml = render_set_bonus_tip_html($data);
                  if (!empty($data['items'])) {
                      $icons = [];
                      foreach ($data['items'] as $it) {
                          $tipItemHtml = render_item_tip_html($it);
                          $icons[] =
                              '<a href="' . htmlspecialchars(item_href((int)$it['entry'])) . '" class="js-item-tip" data-item-id="' . (int)$it['entry'] . '" data-tip-html="' . htmlspecialchars($tipItemHtml, ENT_QUOTES) . '">'
                            . '<img src="/armory/images/icons/64x64/' . htmlspecialchars($it['icon']) . '.png" alt="' . htmlspecialchars($it['name']) . '" width="32" height="32"></a>';
                      }
                      $itemsHtml = '<span class="set-icons">' . implode('', $icons) . '</span>';
                  }
              }

              if ($itemsHtml === '' && $pieces > 0) {
                  $itemsHtml = build_placeholder_chips($pieces);
              }

              echo '<div class="set-row">'
                 . '<span class="set-name"><b class="js-set-tip" data-tip-html="' . htmlspecialchars($tipHtml, ENT_QUOTES) . '">' . htmlspecialchars($nm) . '</b></span>'
                 . '<span class="set-icons">' . $itemsHtml . '</span>'
                 . '</div>';
          }
          echo "</div>";
      }
  } elseif ($section === 'world') {
      echo '<div class="set-group"><div class="set-title">World Drop Sets</div>';
      foreach ($order as $key) {
          if (empty($BLURB[$key])) {
              continue;
          }
          $title = $BLURB[$key]['title'];
          $pieces = (int)$BLURB[$key]['pieces'];
          $text = sets_colorize_desc($BLURB[$key]['text']);
          $pairs = !empty($N[$key][$selectedClass]) ? armor_set_variants($N[$key][$selectedClass]) : [];
          if (empty($pairs)) {
              continue;
          }

          echo "<div class='set-block'>";
          echo "<div class='set-title'>" . htmlspecialchars($title) . "</div>";
          echo "<div class='set-desc'>{$text}</div>";
          foreach ($pairs as $nm) {
              $setName = $nm['name'];
              $setId = find_itemset_id_by_name($setName);
              $items = ($setId) ? get_itemset_data($setId) : [];
              render_armor_set($setName, $pieces, $items, $setId);
          }
          echo "</div>";
      }
  } else {
      echo '<div class="set-group"><div class="set-title">PvP Rank Sets</div>';
      foreach ($pvporder as $key) {
          if (empty($PVP_BLURB[$key])) {
              continue;
          }
          $title = $PVP_BLURB[$key]['title'];
          $pieces = (int)$PVP_BLURB[$key]['pieces'];
          $text = sets_colorize_desc($PVP_BLURB[$key]['text']);
          $pairs = !empty($N_PVP[$key][$selectedClass]) ? armor_set_variants($N_PVP[$key][$selectedClass]) : [];
          if (empty($pairs)) {
              continue;
          }

          echo "<div class='set-block'>";
          echo "<div class='set-title'>" . htmlspecialchars($title) . "</div>";
          echo "<div class='set-desc'>{$text}</div>";
          foreach ($pairs as $nm) {
              $setName = $nm['name'];
              $setId = find_itemset_id_by_name($setName);
              $items = ($setId) ? get_itemset_data($setId) : [];
              render_armor_set($setName, $pieces, $items, $setId);
          }
          echo "</div>";
      }
  }
  ?>
</div>
<?php builddiv_end(); ?>
<script>
(function(){
  const tip = document.createElement('div');
  tip.className = 'talent-tt';
  tip.style.display = 'none';
  document.body.appendChild(tip);
  let anchor = null;
  function place(el){
    const pad = 8;
    const r = el.getBoundingClientRect();
    tip.style.visibility = 'hidden';
    tip.style.display = 'block';
    const t = tip.getBoundingClientRect();
    let left = Math.max(6, Math.min(r.left + (r.width - t.width) / 2, innerWidth - t.width - 6));
    let top = Math.max(6, r.top - t.height - pad);
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
    tip.style.visibility = 'visible';
  }
  function show(el){
    anchor = el;
    const raw = el.getAttribute('data-tip-html') || '';
    const ta = document.createElement('textarea');
    ta.innerHTML = raw;
    tip.innerHTML = ta.value;
    place(el);
  }
  function hide(){
    tip.style.display = 'none';
    anchor = null;
  }
  function nudge(){
    if (anchor && tip.style.display !== 'none') place(anchor);
  }
  document.addEventListener('mouseover', e => {
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if (el) show(el);
  });
  document.addEventListener('mouseout', e => {
    const el = e.target.closest('.js-set-tip, .js-item-tip');
    if (el && !(e.relatedTarget && el.contains(e.relatedTarget))) hide();
  });
  addEventListener('scroll', nudge, {passive:true});
  addEventListener('resize', nudge);
})();
</script>
