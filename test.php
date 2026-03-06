<?php phpinfo(); ?>
<?php
$mysqli = new mysqli("192.168.1.13", "root", "eltnub", "tbcrealmd", 3310);
if ($mysqli->connect_error) {
    die("? Connection failed: " . $mysqli->connect_error);
}
echo "? PHP connected successfully!";
?>
