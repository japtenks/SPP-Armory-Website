<?php
// -------------------------
// Player Map Configuration
// -------------------------

$language      = "en";
$site_encoding = "utf-8";
$db_type       = "MySQL";

// ---------- Realm Database ----------
$realm_db = [
  "addr"     => "192.168.1.13:3310",
  "user"     => "root",
  "pass"     => "eltnub",
  "name"     => "tbcrealmd",
  "encoding" => "utf8"
];

// ---------- World Databases ----------
$world_db = [
  1 => ["addr" => "192.168.1.13:3310", "user" => "root", "pass" => "eltnub", "name" => "classicmangos", "encoding" => "utf8"],
  2 => ["addr" => "192.168.1.13:3310", "user" => "root", "pass" => "eltnub", "name" => "tbcmangos", "encoding" => "utf8"],
  3 => ["addr" => "192.168.1.13:3310", "user" => "root", "pass" => "eltnub", "name" => "wotlkmangos", "encoding" => "utf8"]
];

// ---------- Character Databases ----------
$characters_db = [
  1 => ["addr" => "192.168.1.13:3310", "user" => "root", "pass" => "eltnub", "name" => "classiccharacters", "encoding" => "utf8"],
  2 => ["addr" => "192.168.1.13:3310", "user" => "root", "pass" => "eltnub", "name" => "tbccharacters", "encoding" => "utf8"],
  3 => ["addr" => "192.168.1.13:3310", "user" => "root", "pass" => "eltnub", "name" => "wotlkcharacters", "encoding" => "utf8"]
];

// ---------- Server Info ----------
$server = [
  1 => ["addr" => "192.168.1.22", "addr_wan" => "192.168.1.22", "game_port" => 8085],
  2 => ["addr" => "192.168.1.21", "addr_wan" => "192.168.1.21", "game_port" => 8085],
  3 => ["addr" => "192.168.1.23", "addr_wan" => "192.168.1.23", "game_port" => 8085]
];

// ---------- GM & Display Options ----------
$gm_online                         = false;
$gm_online_count                   = false;
$map_gm_show_online_only_gmoff     = false;
$map_gm_show_online_only_gmvisible = false;
$map_gm_add_suffix                 = false;
$map_status_gm_include_all         = false;

// ---------- Status Display ----------
$map_show_status        = true;
$map_show_time          = true;
$map_time               = 10;
$map_time_to_show_uptime    = 5000;
$map_time_to_show_maxonline = 5000;
$map_time_to_show_gmonline  = 5000;

// ---------- Misc ----------
$developer_test_mode = false;
$multi_realm_mode    = true;
?>
