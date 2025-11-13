<?php
require_once 'db.php';

$message = '';
$errors = array();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $category = isset($_POST['category']) ? mysql_real_escape_string($_POST['category']) : 'general';
    $professional = isset($_POST['professional']) ? 1 : 0;
    $start = !empty($_POST['subscription_start']) ? $_POST['subscription_start'] : null;
    $period = isset($_POST['period']) ? (int)$_POST['period'] : 0;

    if ($name === '') {
        $errors[] = 'Nom requis.';
    }

    // Calculate subscription end date (simple strtotime add months)
    $end = null;
    if (!empty($start) && $period > 0) {
        // Add months safely handling months overflow
        $d = date_create($start);
        if ($d) {
            date_add($d, date_interval_create_from_date_string($period . ' months'));
            $end = date_format($d, 'Y-m-d');
        } else {
            $errors[] = 'Date de début invalide.';
        }
    }

    // File upload
    $savedPath = '';
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
    }

    // base64 camera data
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

    if (empty($errors)) {
        $name_sql = mysql_real_escape_string($name);
        $phone_sql = mysql_real_escape_string($phone);
        $photo_sql = $savedPath ? mysql_real_escape_string($savedPath) : '';

        $start_sql = $start ? "'" . mysql_real_escape_string($start) . "'" : "NULL";
        $end_sql = $end ? "'" . mysql_real_escape_string($end) . "'" : "NULL";

        $sql = "INSERT INTO members (name, age, phone, category, professional, photo, subscription_start, subscription_end, active, created_at)
                VALUES ('$name_sql', $age, '$phone_sql', '$category', $professional, '$photo_sql', $start_sql, $end_sql, 0, NOW())";

        $res = mysql_query($sql);
        if ($res) {
            $member_id = mysql_insert_id();

            // Insert into subscriptions if defined
            if (!empty($start) && !empty($end)) {
                $active = ($today >= $start && $today <= $end) ? 1 : 0;
                $plan = mysql_real_escape_string($period . ' mois');
                $ins = "INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, active)
                        VALUES ($member_id, '$start', '$end', '$plan', $active)";
                mysql_query($ins);

                // Update members.active based on subscription just inserted
                if ($active) {
                    mysql_query("UPDATE members SET active = 1 WHERE id = $member_id");
                }
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
                <input type="file" name="photo" accept="image/*" id="fileInput">
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

<!-- CAMERA MODAL (simple inline modal) -->
<div id="cameraModal" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.8);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:10px;max-width:640px;margin:auto;">
        <video id="camVideo" autoplay playsinline style="width:100%;max-height:480px"></video>
        <div style="margin-top:8px">
            <button id="captureBtn">Capturer</button>
            <button id="closeCam">Fermer</button>
        </div>
        <canvas id="camCanvas" style="display:none"></canvas>
    </div>
</div>

<script>
// end date calculation
function updateEndDate() {
    var start = document.getElementById('start_date').value;
    var period = parseInt(document.getElementById('period').value);
    var endInput = document.getElementById('end_date');
    if (start && period > 0) {
        var d = new Date(start);
        d.setMonth(d.getMonth() + period);
        // correct for month overflow
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

// Camera logic (getUserMedia)
var openBtn = document.getElementById('openCamera');
var modal = document.getElementById('cameraModal');
var video = document.getElementById('camVideo');
var canvas = document.getElementById('camCanvas');
var captureBtn = document.getElementById('captureBtn');
var closeCam = document.getElementById('closeCam');
var photoDataInput = document.getElementById('photo_data');
var previewImg = document.getElementById('previewImg');

openBtn.onclick = function(){
    // show modal
    modal.style.display = 'flex';
    // ask for camera
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
            video.srcObject = stream;
            video.play();
        }).catch(function(err){
            alert('Impossible d\'accéder à la caméra: ' + err.message);
            modal.style.display = 'none';
        });
    } else {
        alert('Caméra non supportée par ce navigateur.');
        modal.style.display = 'none';
    }
};

captureBtn.onclick = function(){
    var w = video.videoWidth;
    var h = video.videoHeight;
    canvas.width = w;
    canvas.height = h;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);
    var dataURL = canvas.toDataURL('image/jpeg');
    photoDataInput.value = dataURL;
    previewImg.src = dataURL;
    previewImg.className = 'preview';
    // stop the stream
    try {
        var stream = video.srcObject;
        if (stream) {
            var tracks = stream.getTracks();
            for (var i=0;i<tracks.length;i++) tracks[i].stop();
        }
    } catch (e) {}
    modal.style.display = 'none';
};

closeCam.onclick = function(){
    // stop stream and hide
    try {
        var stream = video.srcObject;
        if (stream) {
            var tracks = stream.getTracks();
            for (var i=0;i<tracks.length;i++) tracks[i].stop();
        }
    } catch (e) {}
    modal.style.display = 'none';
};
</script>
</body>
</html>
