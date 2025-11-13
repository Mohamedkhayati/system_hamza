<?php
require_once 'db.php';
if (!isset($_GET['id'])) die('ID manquant');

$id = (int)$_GET['id'];

$id_safe = mysql_real_escape_string($id);
$q = "SELECT * FROM members WHERE id = $id_safe LIMIT 1";
$r = mysql_query($q);
if (!$r || mysql_num_rows($r) == 0) die('Membre introuvable');
$member = mysql_fetch_assoc($r);

$errors = array();
$message = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $category = isset($_POST['category']) ? mysql_real_escape_string($_POST['category']) : 'general';
    $professional = isset($_POST['professional']) ? 1 : 0;
    $start = !empty($_POST['subscription_start']) ? $_POST['subscription_start'] : null;
    $period = isset($_POST['period']) ? (int)$_POST['period'] : 0;

    if ($name === '') $errors[] = 'Le nom est requis.';

    // compute end
    $end = null;
    if ($start && $period > 0) {
        $d = date_create($start);
        if ($d) {
            date_add($d, date_interval_create_from_date_string($period . ' months'));
            $end = date_format($d, 'Y-m-d');
        } else {
            $errors[] = 'Date de début invalide.';
        }
    }

    // photo handling (file or base64)
    $savedPath = $member['photo'];
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array('jpg','jpeg','png','gif','webp');
        if (in_array($ext, $allowed)) {
            $dir = dirname(__FILE__) . '/uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $new = uniqid('mbr_') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $new)) {
                $savedPath = 'uploads/' . $new;
            }
        }
    } elseif (!empty($_POST['photo_data'])) {
        $data = $_POST['photo_data'];
        if (preg_match('#^data:image/(\w+);base64,#', $data, $m)) {
            $ext = ($m[1] == 'jpeg') ? 'jpg' : $m[1];
            $body = substr($data, strpos($data, ',') + 1);
            $decoded = base64_decode($body);
            if ($decoded !== false) {
                $dir = dirname(__FILE__) . '/uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $new = uniqid('mbr_') . '.' . $ext;
                if (file_put_contents($dir . $new, $decoded) !== false) {
                    $savedPath = 'uploads/' . $new;
                }
            }
        }
    }

    if (empty($errors)) {
        $name_sql = mysql_real_escape_string($name);
        $phone_sql = mysql_real_escape_string($phone);
        $photo_sql = $savedPath ? mysql_real_escape_string($savedPath) : '';

        $upd = "
            UPDATE members
            SET name = '$name_sql',
                age = $age,
                phone = '$phone_sql',
                category = '$category',
                professional = $professional,
                photo = '$photo_sql',
                subscription_start = " . ($start ? "'" . mysql_real_escape_string($start) . "'" : "NULL") . ",
                subscription_end = " . ($end ? "'" . mysql_real_escape_string($end) . "'" : "NULL") . "
            WHERE id = $id_safe
        ";
        $res = mysql_query($upd);
        if (!$res) {
            $errors[] = 'Erreur DB: ' . mysql_error();
        } else {
            // update or insert subscription record
            if ($start && $end) {
                $active = ($today >= $start && $today <= $end) ? 1 : 0;
                $plan = mysql_real_escape_string($period . ' mois');
                // check existing subscription for this member (any)
                $check = mysql_query("SELECT id FROM subscriptions WHERE member_id = $id_safe LIMIT 1");
                if ($check && mysql_num_rows($check) > 0) {
                    // update all subscriptions? better update the latest active/most recent subscription
                    // For simplicity update all subscriptions for member to new dates if you want to replace
                    mysql_query("UPDATE subscriptions SET start_date = '$start', end_date = '$end', plan_name = '$plan', active = $active WHERE member_id = $id_safe");
                } else {
                    mysql_query("INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, active) VALUES ($id_safe, '$start', '$end', '$plan', $active)");
                }

                // set member active flag
                mysql_query("UPDATE members SET active = $active WHERE id = $id_safe");
            }

            header("Location: member_profile.php?id=$id_safe");
            exit;
        }
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
<?php foreach ($errors as $e): ?><p style="color:red;"><?php echo htmlspecialchars($e); ?></p><?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
    <label>Nom <input type="text" name="name" required value="<?php echo htmlspecialchars($member['name']); ?>"></label>
    <label>Âge <input type="number" name="age" min="1" value="<?php echo $member['age']; ?>"></label>
    <label>Téléphone <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>"></label>
    <label>Catégorie
        <select name="category">
            <option value="general" <?php echo $member['category']==='general'?'selected':'' ?>>General</option>
            <option value="karate" <?php echo $member['category']==='karate'?'selected':'' ?>>Karate</option>
            <option value="fitness" <?php echo $member['category']==='fitness'?'selected':'' ?>>Fitness</option>
        </select>
    </label>
    <label><input type="checkbox" name="professional" <?php echo $member['professional'] ? 'checked' : ''; ?>> Pro</label>

    <label>Date début <input type="date" name="subscription_start" id="start_date" value="<?php echo $member['subscription_start']; ?>"></label>
    <label>Période
        <select name="period" id="period">
            <option value="0">Aucune</option>
            <option value="1">1 mois</option>
            <option value="3">3 mois</option>
            <option value="12">12 mois</option>
        </select>
    </label>
    <label>Date fin <input type="date" name="subscription_end" id="end_date" readonly value="<?php echo $member['subscription_end']; ?>"></label>

    <div class="controls">
        <label class="small">Photo: <input type="file" name="photo" accept="image/*"></label>
        <button type="button" id="openCamera" class="secondary">Caméra</button>
    </div>
    <input type="hidden" name="photo_data" id="photo_data">
    <?php if ($member['photo']): ?><img src="<?php echo htmlspecialchars($member['photo']); ?>" class="preview"><?php endif; ?>

    <div style="margin-top:12px;">
        <button type="submit">Enregistrer</button>
        <a href="member_profile.php?id=<?php echo $member['id']; ?>">Annuler</a>
    </div>
</form>
</div>

<script>
// update end date logic (same as add)
function updateEndDate() {
    var start = document.getElementById('start_date').value;
    var period = parseInt(document.getElementById('period').value);
    var endInput = document.getElementById('end_date');
    if (start && period > 0) {
        var d = new Date(start);
        d.setMonth(d.getMonth() + period);
        var yyyy = d.getFullYear();
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        endInput.value = yyyy + '-' + mm + '-' + dd;
    } else {
        endInput.value = '';
    }
}
document.getElementById('start_date').onchange = updateEndDate;
document.getElementById('period').onchange = updateEndDate;

// Reuse camera logic from Ajouter.php if desired (copy/paste)
</script>
</body>
</html>
