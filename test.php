<?php phpinfo(); ?>
<?php
$mysqli = new mysqli("127.0.0.1", "root", "123456", "tbcrealmd", 3310);
if ($mysqli->connect_error) {
    die("? Connection failed: " . $mysqli->connect_error);
}
echo "? PHP connected successfully!";
?>
