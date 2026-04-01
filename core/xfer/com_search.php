<?php

function loadCommands($pdo,$world_db,$type){

  if($type == 'bot'){
    $rows = $pdo->query("
      SELECT `name`, `template_text`, `text`
      FROM {$world_db}.ai_playerbot_help_texts
      WHERE `name` LIKE 'action:%'
         OR `name` LIKE 'strategy:%'
         OR `name` LIKE 'trigger:%'
         OR `name` LIKE 'value:%'
         OR `name` LIKE 'list:%'
         OR `name` LIKE 'chatfilter:%'
         OR `name` LIKE 'object:%'
         OR `name` LIKE 'template:%'
         OR `name` LIKE 'help:%'
      ORDER BY `name` ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $commands = array();
    foreach ($rows as $row) {
      $rawName = trim((string)($row['name'] ?? ''));
      if ($rawName === '') {
        continue;
      }

      $parts = explode(':', $rawName, 2);
      $prefix = strtolower(trim((string)($parts[0] ?? 'bot')));
      $name = trim((string)($parts[1] ?? $rawName));
      if ($name === '') {
        $name = $rawName;
      }

      $help = trim((string)($row['template_text'] ?? ''));
      if ($help === '') {
        $help = trim((string)($row['text'] ?? ''));
      }

      $commands[] = array(
        'name' => $name,
        'category' => ucfirst($prefix),
        'subcategory' => '-',
        'security' => '-',
        'help' => $help,
      );
    }

    return $commands;
  } else {
    $sql = "SELECT name,security,help
            FROM {$world_db}.command
            WHERE security >= 0
            ORDER BY security,name";
  }

  return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

?>
