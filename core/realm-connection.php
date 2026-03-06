//uncomment and copy to file to use in

//<?php
//require_once(__DIR__ . '/../includes/realm-connection.php');

// Example usage:
//render_realm_selector($realmId, $realmName);

// Use PDO like normal
//$sql = "SELECT COUNT(*) FROM {$db['name']}.auction";
//$total = $pdo->query($sql)->fetchColumn();

//echo "<p>Realm: {$realmName}</p>";
//echo "<p>Total auctions: {$total}</p>";
//?>


<?php
/* ---------------------------------------------------------
   realm-connection.php
   Unified DB connection for MangosWeb Enhanced
   Modernized version - William Lynn / 2025
----------------------------------------------------------*/

if (!defined('INCLUDED')) define('INCLUDED', true);

// Load config once
if (!isset($CONFIG_LOADED)) {
    require_once(__DIR__ . '/../config/config-protected.php');
    require_once(__DIR__ . '/dbsimple/Mysql.php');
    $CONFIG_LOADED = true;
}

global $CHDB, $WSDB, $REALMD, $realmId, $realmName;

/* ---------- Realm Selection ---------- */
$realmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 1;

switch ($realmId) {
    case 1:
        $db_name_chars = 'classiccharacters';
        $db_name_world = 'classicmangos';
        $realmName = 'Classic';
        break;
    case 2:
        $db_name_chars = 'tbccharacters';
        $db_name_world = 'tbcmangos';
        $realmName = 'The Burning Crusade';
        break;
    case 3:
        $db_name_chars = 'wotlkcharacters';
        $db_name_world = 'wotlkmangos';
        $realmName = 'Wrath of the Lich King';
        break;
    default:
        $db_name_chars = 'classiccharacters';
        $db_name_world = 'classicmangos';
        $realmName = 'Classic';
}

/* ---------- Shared Connection Settings ---------- */
$db_host = '127.0.0.1';
$db_port = 3310;
$db_user = 'root';
$db_pass = '123456';
$db_realmd = 'realmd';

/* ---------- Connection Helper ---------- */
function connect_db($host, $port, $user, $pass, $name) {
    try {
        $dsn = sprintf('%s:%d:%s', $host, $port, $name);
        $db = DbSimple_Generic::connect("mysql://$user:$pass@$host:$port/$name");
        $db->setErrorHandler('dbErrorHandler');
        return $db;
    } catch (Exception $e) {
        echo "<div style='color:red;font-weight:bold;'>Database Connection Error: {$e->getMessage()}</div>";
        return null;
    }
}

function dbErrorHandler($message, $info) {
    if (!error_reporting()) return;
    echo "<div style='color:red;font-weight:bold;'>SQL Error: $message</div>";
    if (!empty($info['query'])) {
        echo "<div style='color:orange;'>Query: {$info['query']}</div>";
    }
    exit;
}

/* ---------- Create Connections if not already defined ---------- */
if (empty($CHDB) || !is_object($CHDB))
    $CHDB = connect_db($db_host, $db_port, $db_user, $db_pass, $db_name_chars);

if (empty($WSDB) || !is_object($WSDB))
    $WSDB = connect_db($db_host, $db_port, $db_user, $db_pass, $db_name_world);

if (empty($REALMD) || !is_object($REALMD))
    $REALMD = connect_db($db_host, $db_port, $db_user, $db_pass, $db_realmd);
/* ---------- Verify ---------- */
if (!$CHDB || !$WSDB || !$REALMD) {
    echo "<div style='color:red;font-weight:bold;'>?? Failed to connect to one or more databases. Check realm-connection.php.</div>";
}
?>
