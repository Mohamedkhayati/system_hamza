<?php
require 'db.php';

$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : (isset($_POST['member_id']) ? intval($_POST['member_id']) : 0);
if ($member_id <= 0) { header('Location: Afficher.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = mysql_real_escape_string($_POST['start_date']);
    $end = mysql_real_escape_string($_POST['end_date']);
    $plan = mysql_real_escape_string($_POST['plan_name']);
    $price = floatval($_POST['price']);

    // Archive current active subscription(s)
    $cur_res = mysql_query("SELECT * FROM subscriptions WHERE member_id = $member_id AND active = 1");
    if ($cur_res && mysql_num_rows($cur_res) > 0) {
        while ($cur = mysql_fetch_assoc($cur_res)) {
            $note = 'replaced on ' . date('Y-m-d H:i:s');
            $archive_sql = "INSERT INTO subscription_archive (member_id, start_date, end_date, plan_name, price, note, archived_at) 
                            VALUES ($member_id, '{$cur['start_date']}', '{$cur['end_date']}', '".mysql_real_escape_string($cur['plan_name'])."', {$cur['price']}, '$note', NOW())";
            mysql_query($archive_sql);
            mysql_query("UPDATE subscriptions SET active = 0 WHERE id = {$cur['id']}");
        }
    }

    // Insert new subscription as active
    $ins_sql = "INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, price, active) 
                VALUES ($member_id, '$start', '$end', '$plan', $price, 1)";
    mysql_query($ins_sql);

    // archive snapshot
    $archive_note = 'new subscription';
    mysql_query("INSERT INTO subscription_archive (member_id, start_date, end_date, plan_name, price, note, archived_at) 
                 VALUES ($member_id, '$start', '$end', '$plan', $price, '$archive_note', NOW())");

    // Set member active
    mysql_query("UPDATE members SET active = 1, subscription_start = '$start', subscription_end = '$end' WHERE id = $member_id");

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
