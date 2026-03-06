<?php
require_once("func.php");
require_once("config/playermap_config.php");
require_once("libs/data_lib.php");

// --- Realm detection with override ---
$realm_id = (int)($_GET['realm'] ?? 0);

if ($realm_id === 0) {
    if (file_exists("../../vanilla.spp")) {
        $realm_id = 1;
    } elseif (file_exists("../../tbc.spp")) {
        $realm_id = 2;
    } elseif (file_exists("../../wotlk.spp")) {
        $realm_id = 3;
    } elseif (file_exists("../../vmangos.spp")) {
        $realm_id = 4;
    }
}

if ($realm_id === 0) {
    $realm_id = 1; // fallback
}

// --- Language setup ---
if (isset($_COOKIE["lang"])) {
    $lang = "en";
    if (!file_exists("map_{$lang}.php") && !file_exists("zone_names_{$lang}.php")) {
        $lang = $language;
    }
} else {
    $lang = $language;
}

// --- Database / server setup ---
$database_encoding = $site_encoding;
$server_arr = $server;

$server   = $server_arr[$realm_id]["addr"];
$port     = $server_arr[$realm_id]["game_port"];

$host     = $characters_db[$realm_id]["addr"];
$user     = $characters_db[$realm_id]["user"];
$password = $characters_db[$realm_id]["pass"];
$db       = $characters_db[$realm_id]["name"];

$hostr    = $realm_db["addr"];
$userr    = $realm_db["user"];
$passwordr= $realm_db["pass"];
$dbr      = $realm_db["name"];

// --- Realm name lookup ---
$sql = new DBLayer($hostr, $userr, $passwordr, $dbr);
$query = $sql->query("SELECT name FROM realmlist WHERE id = $realm_id");
$realm_name = $sql->fetch_assoc($query);
$realm_name = htmlentities($realm_name["name"]);

// --- Display settings ---
$gm_show_online                = $gm_online;
$gm_show_online_only_gmoff     = $map_gm_show_online_only_gmoff;
$gm_show_online_only_gmvisible = $map_gm_show_online_only_gmvisible;
$gm_add_suffix                 = $map_gm_add_suffix;
$gm_include_online             = $gm_online_count;
$show_status                   = $map_show_status;
$time_to_show_uptime           = $map_time_to_show_uptime;
$time_to_show_maxonline        = $map_time_to_show_maxonline;
$time_to_show_gmonline         = $map_time_to_show_gmonline;
$status_gm_include_all         = $map_status_gm_include_all;
$time                          = $map_time;
$show_time                     = $map_show_time;

// --- Maps for player positions ---
$maps_for_points = "0,1,530,571,609";

// --- Map backgrounds ---
switch ($realm_id) {
    case 1: $img_base = "img/map_vanilla/"; break;
    case 2: $img_base = "img/map_tbc/";     break;
    case 3: $img_base = "img/map_wotlk/";   break;
    default: $img_base = "img/map_vanilla/"; break;
}
$img_base2 = "img/c_icons/";

// --- Player flags ---
$PLAYER_FLAGS = CHAR_DATA_OFFSET_FLAGS;

// --- Dynamic DB connection based on realm ---
$char_conf  = $characters_db[$realm_id];
$world_conf = $world_db[$realm_id];

$DB_chars = new mysqli(
    explode(':', $char_conf['addr'])[0],
    $char_conf['user'],
    $char_conf['pass'],
    $char_conf['name'],
    explode(':', $char_conf['addr'])[1]
);

$DB_world = new mysqli(
    explode(':', $world_conf['addr'])[0],
    $world_conf['user'],
    $world_conf['pass'],
    $world_conf['name'],
    explode(':', $world_conf['addr'])[1]
);

// --- Connection check ---
if ($DB_chars->connect_error) {
    die("Character DB connection failed: " . $DB_chars->connect_error);
}

if ($DB_world->connect_error) {
    die("World DB connection failed: " . $DB_world->connect_error);
}

// --- Show current realm name ---
//echo "<div style='text-align:center;
//                color:#FFD700;
//                font-family:Georgia;
//                font-size:16px;
//                margin:10px 0;
//                text-shadow:1px 1px 3px #000;'>
//        Realm: <b>{$realm_name}</b>
//      </div>";
?>
