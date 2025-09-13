<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Update active status
$today = date('Y-m-d');
$stmt = $conn->prepare("UPDATE subscribers SET active = 0 WHERE end_date < ? AND active = 1");
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->close();
// Fetch stats
$total = $conn->query("SELECT COUNT(*) as count FROM subscribers")->fetch_assoc()['count'];
$active = $conn->query("SELECT COUNT(*) as count FROM subscribers WHERE active = 1")->fetch_assoc()['count'];
$non_active = $total - $active;
$expiring = $conn->query("SELECT COUNT(*) as count FROM subscribers WHERE active = 1 AND end_date <= DATE_ADD('$today', INTERVAL 7 DAY)")->fetch_assoc()['count'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gym Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; }
        header { background-color: #333; color: white; padding: 10px 0; position: sticky; top: 0; z-index: 100; }
        header nav ul { list-style: none; display: flex; justify-content: center; flex-wrap: wrap; }
        header nav ul li { margin: 0 15px; }
        header nav ul li a { color: white; text-decoration: none; font-size: 18px; padding: 10px 15px; display: block; transition: background 0.3s; }
        header nav ul li a:hover { background-color: #555; border-radius: 5px; }
        main { padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; padding: 20px; color: #333; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h2 { margin-bottom: 10px; font-size: 1.2em; }
        .stat-card p { font-size: 2em; font-weight: bold; }
        .total { color: #4CAF50; }
        .active { color: #2196F3; }
        .non-active { color: #f44336; }
        .expiring { color: #FF9800; }
        .note { text-align: center; font-style: italic; margin-top: 20px; }
        @media (max-width: 768px) {
            header nav ul { flex-direction: column; }
            header nav ul li { margin: 10px 0; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="dashboard.php" aria-current="page">Dashboard</a></li>
            <li><a href="afficher.php">Subscribers</a></li>
            <li><a href="ajouter.php">Add New</a></li>
            <li><a href="non_active.php">Non-Active</a></li>
        </ul>
    </nav>
</header>
<main>
    <h1>Dashboard - Gym Management Overview</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <h2>Total Subscribers</h2>
            <p class="total"><?= $total ?></p>
        </div>
        <div class="stat-card">
            <h2>Active</h2>
            <p class="active"><?= $active ?></p>
        </div>
        <div class="stat-card">
            <h2>Non-Active</h2>
            <p class="non-active"><?= $non_active ?></p>
        </div>
        <div class="stat-card">
            <h2>Expiring Soon (7 Days)</h2>
            <p class="expiring"><?= $expiring ?></p>
        </div>
    </div>
    <p class="note">System updated automatically. Last check: <?= date('Y-m-d H:i') ?>.</p>
</main>
<script>
function updateActiveStatus() {
    fetch('update_active.php', { method: 'POST' })
        .then(response => response.text())
        .then(data => console.log('Active status updated:', data))
        .catch(err => console.error('Update error:', err));
}
window.onload = updateActiveStatus;
</script>
</body>
</html>