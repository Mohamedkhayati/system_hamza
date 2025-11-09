<?php
require_once 'db.php';
$today = date('Y-m-d');

// Auto update subscription status
mysql_query("UPDATE subscriptions SET active = 0 WHERE end_date < '$today' AND active = 1");
mysql_query("UPDATE subscriptions s 
            JOIN members m ON s.member_id = m.id 
            SET s.active = 1 
            WHERE m.subscription_start <= '$today' AND m.subscription_end >= '$today' AND s.active = 0");

// Fetch counts
$total_res = mysql_query("SELECT COUNT(*) AS cnt FROM members");
$total_row = mysql_fetch_assoc($total_res);
$total = $total_row['cnt'];

$active_res = mysql_query("SELECT COUNT(*) AS cnt FROM subscriptions WHERE active = 1");
$active_row = mysql_fetch_assoc($active_res);
$active = $active_row['cnt'];

$non_active_res = mysql_query("SELECT COUNT(*) AS cnt FROM members WHERE subscription_end < '$today' OR subscription_end IS NULL");
$non_active_row = mysql_fetch_assoc($non_active_res);
$non_active = $non_active_row['cnt'];

$expiring_res = mysql_query("SELECT COUNT(*) AS cnt FROM subscriptions WHERE active = 1 AND end_date <= DATE_ADD('$today', INTERVAL 7 DAY)");
$expiring_row = mysql_fetch_assoc($expiring_res);
$expiring = $expiring_row['cnt'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <h1>Tableau de bord</h1>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-top:20px;">
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);text-align:center;">
            <h3>Total</h3>
            <p style="font-size:2em;color:#4CAF50;"><?php echo $total; ?></p>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);text-align:center;">
            <h3>Actifs</h3>
            <p style="font-size:2em;color:#2196F3;"><?php echo $active; ?></p>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);text-align:center;">
            <h3>Inactifs</h3>
            <p style="font-size:2em;color:#f44336;"><?php echo $non_active; ?></p>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);text-align:center;">
            <h3>Expire (7j)</h3>
            <p style="font-size:2em;color:#FF9800;"><?php echo $expiring; ?></p>
        </div>
    </div>
</div>
</body>
</html>
