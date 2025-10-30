<?php
// db.php - central DB connection
$DB_HOST = '127.0.0.1';
$DB_NAME = 'gym_management';
$DB_USER = 'root';
$DB_PASS = ''; // change as needed

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>
