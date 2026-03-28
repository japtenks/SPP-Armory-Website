<?php

foreach ($classes as $c) {
  if (strcasecmp($selectedClass, $c['name']) === 0) { 
    $selectedClass = $c['name']; 
    break; 
  }
}

$currentSub = isset($_GET['sub']) ? $_GET['sub'] : 'armorsets';

echo '<div class="class-bar">';

foreach ($classes as $c) {
    $className = $c['name'];
    $slug      = $c['slug'];
    $href = "index.php?n=server&sub={$currentSub}&class={$className}&realm={$realmId}";
    $src  = $iconBase . $iconPref . $slug . $iconExt;
    $active = (strcasecmp($selectedClass, $className) === 0) ? ' is-active' : '';
    echo '<a class="class-token ' . $c['css'] . $active . '" href="' . $href . '"'
       . ' aria-label="' . htmlspecialchars($className) . '"'
       . ' data-name="' . htmlspecialchars($className) . '">'
       . '<img src="' . $src . '" alt="' . htmlspecialchars($className) . '">'
       . '</a>';
}

echo '</div>';

if ($selectedClass === '') {
  $rand = $classes[array_rand($classes)];
  $selectedClass = $rand['name'];
}