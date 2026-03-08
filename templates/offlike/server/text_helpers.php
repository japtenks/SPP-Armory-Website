
<?php
function highlight_class_names($text) {
  $classesToCSS = [
    'Warrior'=>'is-warrior','Paladin'=>'is-paladin','Hunter'=>'is-hunter',
    'Rogue'=>'is-rogue','Priest'=>'is-priest','Shaman'=>'is-shaman',
    'Mage'=>'is-mage','Warlock'=>'is-warlock','Druid'=>'is-druid',
    'Death Knight'=>'is-dk'
  ];

  $text = str_replace(['<b>','</b>'],'',$text);

  return preg_replace_callback(
    '/\b(' . implode('|', array_map('preg_quote', array_keys($classesToCSS))) . ')(s)?\b/i',
    function($m) use ($classesToCSS){
      $name = ucfirst(strtolower($m[1]));
      $css  = $classesToCSS[$name] ?? '';
      $suffix = $m[2] ?? '';
      return "<span class='{$css}'><b>{$name}{$suffix}</b></span>";
    },
    $text
  );
}