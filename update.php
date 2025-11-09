<?php
require_once 'db.php';
if (!isset($_GET['id'])) die('ID manquant');
$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$member) die('Membre introuvable');

$errors = []; $message = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $professional = isset($_POST['professional']) ? 1 : 0;
    $start = $_POST['subscription_start'] ?: null;
    $period = (int)($_POST['period'] ?? 0);

    if ($name === '') $errors[] = 'Le nom est requis.';

    // Calculate end date using DateTime
    $end = null;
    if ($start && $period > 0) {
        $dt = new DateTime($start);
        $dt->add(new DateInterval("P{$period}M"));
        $end = $dt->format('Y-m-d');
    }

    // Handle photo upload
    $savedPath = $member['photo'];
    if (!empty($_FILES['photo']['tmp_name'])) {
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $new = uniqid('mbr_') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $new)) $savedPath = 'uploads/' . $new;
        }
    } elseif (!empty($_POST['photo_data'])) {
        $data = $_POST['photo_data'];
        if (preg_match('#^data:image/(\w+);base64,#', $data, $m)) {
            $ext = $m[1]==='jpeg'?'jpg':$m[1];
            $body = substr($data, strpos($data, ',')+1);
            $decoded = base64_decode($body);
            if ($decoded !== false) {
                $dir = __DIR__ . '/uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $new = uniqid('mbr_') . '.' . $ext;
                if (file_put_contents($dir . $new, $decoded) !== false) $savedPath = 'uploads/' . $new;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE members SET name=?, age=?, phone=?, category=?, professional=?, photo=?, subscription_start=?, subscription_end=? WHERE id=?");
        $stmt->bind_param('sisssissi', $name, $age, $phone, $category, $professional, $savedPath, $start, $end, $id);
        if ($stmt->execute()) {
            // Update subscription status
            if ($start && $end) {
                $active = ($today >= $start && $today <= $end) ? 1 : 0;
                $plan = "$period mois";
                $check = $conn->query("SELECT id FROM subscriptions WHERE member_id = $id LIMIT 1");
                if ($check->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE subscriptions SET start_date=?, end_date=?, plan_name=?, active=? WHERE member_id=?");
                    $upd->bind_param('sssii', $start, $end, $plan, $active, $id);
                    $upd->execute(); $upd->close();
                } else {
                    $ins = $conn->prepare("INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, active) VALUES (?, ?, ?, ?, ?)");
                    $ins->bind_param('isssi', $id, $start, $end, $plan, $active);
                    $ins->execute(); $ins->close();
                }
            }
            header("Location: member_profile.php?id=$id"); exit;
        } else {
            $errors[] = 'Erreur DB: '.$stmt->error;
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Modifier membre</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
<h2>Modifier membre</h2>
<?php foreach ($errors as $e): ?><p style="color:red;"><?= $e ?></p><?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
    <label>Nom <input type="text" name="name" required value="<?= htmlspecialchars($member['name']) ?>"></label>
    <label>Âge <input type="number" name="age" min="1" value="<?= $member['age'] ?>"></label>
    <label>Téléphone <input type="text" name="phone" value="<?= htmlspecialchars($member['phone']) ?>"></label>
    <label>Catégorie
        <select name="category">
            <option value="general" <?= $member['category']==='general'?'selected':'' ?>>General</option>
            <option value="karate" <?= $member['category']==='karate'?'selected':'' ?>>Karate</option>
            <option value="fitness" <?= $member['category']==='fitness'?'selected':'' ?>>Fitness</option>
        </select>
    </label>
    <label><input type="checkbox" name="professional" <?= $member['professional']?'checked':'' ?>> Pro</label>

    <label>Date début <input type="date" name="subscription_start" id="start_date" value="<?= $member['subscription_start'] ?>"></label>
    <label>Période
        <select name="period" id="period">
            <option value="0">Aucune</option>
            <option value="1">1 mois</option>
            <option value="3">3 mois</option>
            <option value="12">12 mois</option>
        </select>
    </label>
    <label>Date fin <input type="date" name="subscription_end" id="end_date" readonly value="<?= $member['subscription_end'] ?>"></label>

    <div class="controls">
        <label class="small">Photo: <input type="file" name="photo" accept="image/*"></label>
        <button type="button" id="openCamera" class="secondary">Caméra</button>
    </div>
    <input type="hidden" name="photo_data" id="photo_data">
    <?php if ($member['photo']): ?><img src="<?= htmlspecialchars($member['photo']) ?>" class="preview"><?php endif; ?>

    <div style="margin-top:12px;">
        <button type="submit">Enregistrer</button>
        <a href="member_profile.php?id=<?= $member['id'] ?>">Annuler</a>
    </div>
</form>
</div>

<script>
// Update end date based on start and period
function updateEndDate() {
    const start = document.getElementById('start_date').value;
    const period = parseInt(document.getElementById('period').value);
    const endInput = document.getElementById('end_date');
    if (start && period > 0) {
        const d = new Date(start);
        d.setMonth(d.getMonth() + period);
        endInput.value = d.toISOString().split('T')[0];
    } else {
        endInput.value = '';
    }
}
document.getElementById('start_date').onchange = updateEndDate;
document.getElementById('period').onchange = updateEndDate;
</script>
</body>
</html>
