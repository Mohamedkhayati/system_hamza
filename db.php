<?php
// db.php - PHP 5.4 compatible using mysql_*
// Edit credentials as needed.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'gym_management';

$link = mysql_connect($DB_HOST, $DB_USER, $DB_PASS);
if (!$link) {
    die('DB connection failed: ' . mysql_error());
}
$db_selected = mysql_select_db($DB_NAME, $link);
if (!$db_selected) {
    die('Cannot select database: ' . mysql_error());
}
mysql_query("SET NAMES 'utf8'");
?>
