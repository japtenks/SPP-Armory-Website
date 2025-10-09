<?php
if (INCLUDED !== true) exit;

$pathway_info[] = array('title' => $lang['bot_commands'], 'link' => '');
$userlevel = ($user['gmlevel'] != '' ? $user['gmlevel'] : 0);

/* ---------- Verify DB ---------- */
if (!isset($DB)) {
  echo "<div style='color:red;padding:8px;'>[ERROR] No DB connection.</div>";
  return;
}

/* ---------- Load Commands ---------- */
$botCommands = $DB->select("
  SELECT name, security, help, category, subcategory
  FROM tbcarmory.bot_command
  WHERE security <= $userlevel
  ORDER BY category, subcategory, name ASC
");
?>