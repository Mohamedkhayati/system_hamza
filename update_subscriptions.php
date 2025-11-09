<?php
require 'db.php';

$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : (isset($_POST['member_id']) ? intval($_POST['member_id']) : 0);
if ($member_id <= 0) { header('Location: Afficher.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $plan = $_POST['plan_name'];
    $price = floatval($_POST['price']);

    // Archive current active subscription (if any)
    $cur_res = mysql_query("SELECT * FROM subscriptions WHERE member_id = $member_id AND active = 1");
    if ($cur_res && mysql_num_rows($cur_res) > 0) {
        $cur = mysql_fetch_assoc($cur_res);
        $note = 'replaced on ' . date('Y-m-d H:i:s');
        $archive_sql = "INSERT INTO subscription_archive (member_id, start_date, end_date, plan_name, price, note) 
                        VALUES ($member_id, '{$cur['start_date']}', '{$cur['end_date']}', '{$cur['plan_name']}', {$cur['price']}, '$note')";
        mysql_query($archive_sql);

        // mark old subscription inactive
        mysql_query("UPDATE subscriptions SET active = 0 WHERE id = {$cur['id']}");
    }

    // Insert new subscription
    $ins_sql = "INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, price, active) 
                VALUES ($member_id, '$start', '$end', '$plan', $price, 1)";
    mysql_query($ins_sql);

    // Archive the new subscription snapshot
    $archive_note = 'new subscription';
    mysql_query("INSERT INTO subscription_archive (member_id, start_date, end_date, plan_name, price, note) 
                 VALUES ($member_id, '$start', '$end', '$plan', $price, '$archive_note')");

    header("Location: Afficher.php?sub_updated=1");
    exit;
}

// Show form
$m_res = mysql_query("SELECT * FROM members WHERE id = $member_id");
if (!$m_res || mysql_num_rows($m_res) == 0) { header('Location: Afficher.php'); exit; }
$m = mysql_fetch_assoc($m_res);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gérer Abonnement</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<main class="container">
  <h2>Gérer abonnement: <?php echo htmlspecialchars($m['name']); ?></h2>
  <form method="post" class="form-card">
    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
    <label>Plan <input name="plan_name" required></label>
    <label>Prix <input name="price" type="number" step="0.01" required></label>
    <label>Date début <input name="start_date" type="date" required></label>
    <label>Date fin <input name="end_date" type="date" required></label>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
</main>
</body>
</html>
