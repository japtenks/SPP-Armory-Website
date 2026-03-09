<?php
//mysql.php
set_time_limit(0);
ini_set("default_charset", "UTF-8");

// DB common vars
$db_host = '127.0.0.1';
$db_port = '3310';
$db_user = 'root';
$db_pass = '123456';

// Realm DB map - keyed by realm ID
$realmd_DB = array(
  1 => array("$db_host:$db_port", $db_user, $db_pass, "classicrealmd"),
  2 => array("$db_host:$db_port", $db_user, $db_pass, "tbcrealmd"),
  3 => array("$db_host:$db_port", $db_user, $db_pass, "wotlkrealmd"),
);
$characters_DB = array(
  1 => array("$db_host:$db_port", $db_user, $db_pass, "classiccharacters"),
  2 => array("$db_host:$db_port", $db_user, $db_pass, "tbccharacters"),
  3 => array("$db_host:$db_port", $db_user, $db_pass, "wotlkcharacters"),
);
$mangosd_DB = array(
  1 => array("$db_host:$db_port", $db_user, $db_pass, "classicmangos"),
  2 => array("$db_host:$db_port", $db_user, $db_pass, "tbcmangos"),
  3 => array("$db_host:$db_port", $db_user, $db_pass, "wotlkmangos"),
);
$armory_DB = array(
  1 => array("$db_host:$db_port", $db_user, $db_pass, "classicarmory"),
  2 => array("$db_host:$db_port", $db_user, $db_pass, "tbcarmory"),
  3 => array("$db_host:$db_port", $db_user, $db_pass, "wotlkarmory"),
);
$playerbot_DB = array(
  1 => array("$db_host:$db_port", $db_user, $db_pass, "classicplayerbots"),
  2 => array("$db_host:$db_port", $db_user, $db_pass, "tbcplayerbots"),
  3 => array("$db_host:$db_port", $db_user, $db_pass, "wotlkplayerbots"),
);

// Auto-build $realms from realmlist table
// Maps realm name => array(realmd_id, chars_id, world_id, armory_id, bots_id)
$realms = array();
$defaultRealm = null;

$realmDbMap = array(
    1 => "classicrealmd",
    2 => "tbcrealmd",
    3 => "wotlkrealmd",
);

foreach ($realmDbMap as $id => $dbname) {
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;port=$db_port;dbname=$dbname;charset=utf8",
            $db_user, $db_pass,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        $stmt = $pdo->query("SELECT `name` FROM `realmlist` LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $realmName = $row['name'];
            $realms[$realmName] = array($id, $id, $id, $id, $id);
            if (!$defaultRealm) $defaultRealm = $realmName;
        }
    } catch (PDOException $e) {
        // DB not available, skip this realm
    }
}

if ($defaultRealm) {
    define("DefaultRealmName", $defaultRealm);
} else {
    die("No realms could be loaded. Check DB connections.");
}

function execute_query($db_name, $query, $method = 0, $error = "")
{
    global $DB, $WSDB, $CHDB, $ARDB, $PBDB;
    $query_result = false;
    if ($method == 0) {
        if ($db_name == "realm")   $query_result = $DB->query($query);
        elseif ($db_name == "armory") $query_result = $ARDB->query($query);
        elseif ($db_name == "char")   $query_result = $CHDB->query($query);
        elseif ($db_name == "world")  $query_result = $WSDB->query($query);
        elseif ($db_name == "bots")   $query_result = $PBDB->query($query);
    } elseif ($method == 1) {
        if ($db_name == "realm")   $query_result = $DB->selectRow($query);
        elseif ($db_name == "armory") $query_result = $ARDB->selectRow($query);
        elseif ($db_name == "char")   $query_result = $CHDB->selectRow($query);
        elseif ($db_name == "world")  $query_result = $WSDB->selectRow($query);
        elseif ($db_name == "bots")   $query_result = $PBDB->selectRow($query);
    } elseif ($method == 2) {
        if ($db_name == "realm")   $query_result = $DB->selectCell($query);
        elseif ($db_name == "armory") $query_result = $ARDB->selectCell($query);
        elseif ($db_name == "char")   $query_result = $CHDB->selectCell($query);
        elseif ($db_name == "world")  $query_result = $WSDB->selectCell($query);
        elseif ($db_name == "bots")   $query_result = $PBDB->selectCell($query);
    }
    if (!$db_name) die($error . "Database not chosen");
    if ($query_result) return $query_result;
    elseif (!$query_result && $error) { die($error); return false; }
    return false;
}
?>