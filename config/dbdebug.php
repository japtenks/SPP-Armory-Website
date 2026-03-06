<?php
require('config-protected.php');
echo "<pre>";
print_r($realmd);
echo "</pre>";

$mysqli = new mysqli(
    $realmd['db_host'],
    $realmd['db_username'],
    $realmd['db_password'],
    $realmd['db_name'],
    $realmd['db_port']
);

if ($mysqli->connect_error) {
    die("? Connection failed: " . $mysqli->connect_error);
}
echo "? PHP connected to: {$realmd['db_name']} @ {$realmd['db_host']}:{$realmd['db_port']}";
?>
