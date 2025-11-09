<?php
require_once 'db.php';

$message = '';
$errors = array();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve form fields (old PHP syntax)
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $category = isset($_POST['category']) ? $_POST['category'] : 'general';
    $professional = isset($_POST['professional']) ? 1 : 0;
    $start = isset($_POST['subscription_start']) ? $_POST['subscription_start'] : null;
    $period = isset($_POST['period']) ? (int)$_POST['period'] : 0;

    if ($name === '') {
        $errors[] = 'Nom requis.';
    }

    // Calculate subscription end date
    $end = null;
    if (!empty($start) && $period > 0) {
        $end = date('Y-m-d', strtotime($start . " +$period months"));
    }

    // File upload (for photo)
    $savedPath = null;
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array('jpg','jpeg','png','gif','webp');
        if (in_array($ext, $allowed)) {
            $dir = dirname(__FILE__) . '/uploads/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $new = uniqid('mbr_') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $new)) {
                $savedPath = 'uploads/' . $new;
            }
        }
    }

    // Optional: base64 image capture
    if (!$savedPath && !empty($_POST['photo_data'])) {
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

    // Insert into DB
    if (empty($errors)) {
        $name = mysql_real_escape_string($name);
        $phone = mysql_real_escape_string($phone);
        $category = mysql_real_escape_string($category);
        $photo = $savedPath ? mysql_real_escape_string($savedPath) : '';
        $start_sql = $start ? "'" . mysql_real_escape_string($start) . "'" : "NULL";
        $end_sql = $end ? "'" . mysql_real_escape_string($end) . "'" : "NULL";

        $sql = "INSERT INTO members (name, age, phone, category, professional, photo, subscription_start, subscription_end, created_at)
                VALUES ('$name', $age, '$phone', '$category', $professional, '$photo', $start_sql, $end_sql, NOW())";

        $res = mysql_query($sql);
        if ($res) {
            $member_id = mysql_insert_id();

            // Insert into subscriptions
            if (!empty($start) && !empty($end)) {
                $active = ($today >= $start && $today <= $end) ? 1 : 0;
                $plan = mysql_real_escape_string($period . ' mois');
                $ins = "INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, active)
                        VALUES ($member_id, '$start', '$end', '$plan', $active)";
                mysql_query($ins);
            }

            $message = 'Ajouté.';
        } else {
            $errors[] = 'Erreur DB : ' . mysql_error();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ajouter</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <h2>Ajouter membre</h2>

    <?php if ($message != ''): ?>
        <p style="color:green;"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <p style="color:red;"><?php echo $e; ?></p>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Nom
            <input type="text" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
        </label>

        <label>Âge
            <input type="number" name="age" min="1" value="<?php echo isset($_POST['age']) ? $_POST['age'] : ''; ?>">
        </label>

        <label>Téléphone
            <input type="text" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
        </label>

        <label>Catégorie
            <select name="category">
                <option value="general" <?php echo (isset($_POST['category']) && $_POST['category'] == 'general') ? 'selected' : ''; ?>>General</option>
                <option value="karate" <?php echo (isset($_POST['category']) && $_POST['category'] == 'karate') ? 'selected' : ''; ?>>Karate</option>
                <option value="fitness" <?php echo (isset($_POST['category']) && $_POST['category'] == 'fitness') ? 'selected' : ''; ?>>Fitness</option>
            </select>
        </label>

        <label><input type="checkbox" name="professional" <?php echo !empty($_POST['professional']) ? 'checked' : ''; ?>> Pro</label>

        <label>Date début
            <input type="date" name="subscription_start" id="start_date" value="<?php echo isset($_POST['subscription_start']) ? $_POST['subscription_start'] : ''; ?>">
        </label>

        <label>Période
            <select name="period" id="period">
                <option value="0">Aucune</option>
                <option value="1" <?php echo (isset($_POST['period']) && $_POST['period'] == 1) ? 'selected' : ''; ?>>1 mois</option>
                <option value="3" <?php echo (isset($_POST['period']) && $_POST['period'] == 3) ? 'selected' : ''; ?>>3 mois</option>
                <option value="12" <?php echo (isset($_POST['period']) && $_POST['period'] == 12) ? 'selected' : ''; ?>>12 mois</option>
            </select>
        </label>

        <label>Date fin
            <input type="date" name="subscription_end" id="end_date" readonly>
        </label>

        <div class="controls">
            <label class="small">Photo:
                <input type="file" name="photo" accept="image/*">
            </label>
            <button type="button" id="openCamera" class="secondary">Caméra</button>
        </div>

        <input type="hidden" name="photo_data" id="photo_data">
        <div id="previewBox"><img id="previewImg" class="preview hidden" src="#"></div>

        <div style="margin-top:12px;">
            <button type="submit">Ajouter</button>
            <a href="Afficher.php">Annuler</a>
        </div>
    </form>
</div>

<!-- CAMERA MODAL + SCRIPT -->
<div id="cameraModal" class="modal hidden"> ... </div>

<script>
function updateEndDate() {
    var start = document.getElementById('start_date').value;
    var period = parseInt(document.getElementById('period').value);
    var endInput = document.getElementById('end_date');
    if (start && period > 0) {
        var d = new Date(start);
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
