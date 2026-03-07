<?php

function loadCommands($pdo,$world_db,$type){

  /* $armory_db = str_replace('mangos','armory',$world_db); */
  $armory_db=tbcarmory;

  if($type == 'bot'){
    $sql = "SELECT name,category,subcategory,security,help
            FROM {$armory_db}.bot_command
            ORDER BY category,subcategory,name";
  } else {
    $sql = "SELECT name,security,help
            FROM {$world_db}.command
            WHERE security >= 0
            ORDER BY security,name";
  }

  return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

?>