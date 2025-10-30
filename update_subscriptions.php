<?php
require 'db.php';

$member_id = intval($_GET['member_id'] ?? ($_POST['member_id'] ?? 0));
if ($member_id <= 0) { header('Location: Afficher.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // create new subscription, archive previous active one if exists
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $plan = $_POST['plan_name'] ?? 'Plan';
    $price = floatval($_POST['price'] ?? 0);

    // archive current active subscription (if any)
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE member_id = ? AND active = 1");
    $stmt->execute([$member_id]);
    $cur = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cur) {
        $archive = $pdo->prepare("INSERT INTO subscription_archive (member_id, start_date, end_date, plan_name, price, note) VALUES (?,?,?,?,?,?)");
        $archive->execute([$member_id, $cur['start_date'], $cur['end_date'], $cur['plan_name'], $cur['price'], 'replaced on ' . date('Y-m-d H:i:s')]);

        // mark old subscription inactive
        $pdo->prepare("UPDATE subscriptions SET active = 0 WHERE id = ?")->execute([$cur['id']]);
    }

    // insert new subscription
    $ins = $pdo->prepare("INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, price, active) VALUES (?, ?, ?, ?, ?, 1)");
    $ins->execute([$member_id, $start, $end, $plan, $price]);

    // also archive the new subscription snapshot (keeps history)
    $archive->execute([$member_id, $start, $end, $plan, $price, 'new subscription']);

    header("Location: Afficher.php?sub_updated=1");
    exit;
}

// show form
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) { header('Location: Afficher.php'); exit; }
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Gérer Abonnement</title><link rel="stylesheet" href="styles.css"></head>
<body>
<?php include 'dashboard_nav.php'; ?>
<main class="container">
  <h2>Gérer abonnement: <?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?></h2>
  <form method="post" class="form-card">
    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
    <label>Plan <input name="plan_name" required></label>
    <label>Prix <input name="price" type="number" step="0.01" required></label>
    <label>Date début <input name="start_date" type="date" required></label>
    <label>Date fin <input name="end_date" type="date" required></label>
    <button class="btn" type="submit">Enregistrer</button>
  </form>
</main>
</body>
</html>
