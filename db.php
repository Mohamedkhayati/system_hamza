<?php
// Database connection (old MySQL extension for PHP 5.4)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'gym_management';

// Connect to MySQL server
$link = mysql_connect($host, $user, $pass);
if (!$link) {
    die('DB connection failed: ' . mysql_error());
}

// Select the database
$db_selected = mysql_select_db($dbname, $link);
if (!$db_selected) {
    die('Cannot select database: ' . mysql_error());
}

// Set character encoding
mysql_query("SET NAMES 'utf8'");

// Done — $link is your active connection
?>
