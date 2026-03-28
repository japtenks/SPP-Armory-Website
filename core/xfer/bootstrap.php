<?php

if (!isset($_GET['debug'])) {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors',0);
}

define('INCLUDED',true);

require_once(dirname(__FILE__).'/realm_db.php');
require_once(dirname(__FILE__).'/db_connect.php');
require_once(dirname(__FILE__).'/query_helpers.php');

/* connect databases */
$CHDB = dbConnect($db['host'],$db['port'],$db['user'],$db['pass'],$db['chars']);
$WSDB = dbConnect($db['host'],$db['port'],$db['user'],$db['pass'],$db['world']);
$ARDB = dbConnect($db['host'],$db['port'],$db['user'],$db['pass'],$db['armory']);

$ARMORY_SCHEMA = $db['armory'];
$WORLD_SCHEMA  = $db['world'];

if(!$ARDB || !$WSDB){
    die("Database connection failed for {$realmName}");
}
