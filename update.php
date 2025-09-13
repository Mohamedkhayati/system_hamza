<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$error = '';
$success = '';
$subscriber = [];
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT * FROM subscribers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscriber = $result->fetch_assoc();
    $stmt->close();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['csrf']) && $_POST['csrf'] === $_SESSION['csrf']) {
        $name = trim($_POST['name']);
        $number = intval($_POST['number']);
        $age = intval($_POST['age']);
        $start_date = $_POST['start_date'];
        $months = intval($_POST['months']);
        if (strlen($name) < 2) $error = "Name must be at least 2 characters.";
        elseif ($number <= 0) $error = "Invalid phone number.";
        elseif ($age < 16 || $age > 100) $error = "Age must be between 16 and 100.";
        elseif (new DateTime($start_date) > new DateTime()) $error = "Start date cannot be in the future.";
        else {
            $end_date = (new DateTime($start_date))->modify("+$months months")->format('Y-m-d');
            $active = ($end_date >= date('Y-m-d')) ? 1 : 0;
            $photo = $subscriber['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_buffer($finfo, $photo);
                finfo_close($finfo);
                $allowed_types = ['image/jpeg', 'image/png'];
                if (!in_array($mime_type, $allowed_types) || strlen($photo) > 2 * 1024 * 1024) {
                    $error = "Invalid photo format or size. Only JPEG/PNG up to 2MB allowed.";
                }
            } elseif (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
                $photo_data = preg_replace('#^data:image/\w+;base64,#i', '', $_POST['photo_data']);
                $photo = base64_decode($photo_data);
                $finfo = finfo_open();
                $mime_type = finfo_buffer($finfo, $photo, FILEINFO_MIME_TYPE);
                finfo_close($finfo);
                if (!in_array($mime_type, $allowed_types) || strlen($photo) > 2 * 1024 * 1024) {
                    $error = "Invalid captured photo format or size.";
                }
            }
            if (!$error) {
                $stmt = $conn->prepare("UPDATE subscribers SET name=?, number=?, age=?, start_date=?, end_date=?, active=?, photo=? WHERE id=?");
                $null = null;
                $stmt->bind_param("siissibi", $name, $number, $age, $start_date, $end_date, $active, $null, $id);
                if ($photo) {
                    $stmt->send_long_data(6, $photo);
                }
                if ($stmt->execute()) {
                    header("Location: afficher.php?success=updated");
                    exit();
                } else {
                    $error = "Error updating subscriber: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
} else {
    header("Location: afficher.php?error=no_id");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Subscription - Gym Management System</title>
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
        form { width: 50%; margin: 0 auto; padding: 20px; background-color: white; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; }
        button { width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 5px; transition: background 0.3s; }
        button:hover { background-color: #45a049; }
        #capture { background-color: #007BFF; }
        #capture:hover { background-color: #0056b3; }
        .current-photo { max-width: 100px; height: auto; border-radius: 5px; margin-bottom: 15px; }
        #video, #captured-image { width: 100%; max-height: 300px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; }
        #canvas { display: none; }
        .error { color: red; text-align: center; margin-bottom: 15px; }
        .success { color: green; text-align: center; margin-bottom: 15px; }
        @media (max-width: 768px) {
            form { width: 90%; }
            header nav ul { flex-direction: column; }
            header nav ul li { margin: 10px 0; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="afficher.php">Subscribers</a></li>
            <li><a href="ajouter.php">Add New</a></li>
            <li><a href="non_active.php">Non-Active</a></li>
        </ul>
    </nav>
</header>
<main>
    <h1>Renew Subscription</h1>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" id="updateForm">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32))) ?>">
        <input type="hidden" name="id" value="<?= $subscriber['id'] ?>">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($subscriber['name']) ?>" required minlength="2"></label>
        <label>Number: <input type="number" name="number" value="<?= $subscriber['number'] ?>" required min="1"></label>
        <label>Age: <input type="number" name="age" value="<?= $subscriber['age'] ?>" required min="16" max="100"></label>
        <label>Subscription Period:
            <select name="months" id="months" required onchange="updateEndDate()">
                <option value="1">1 Month</option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">12 Months</option>
            </select>
        </label>
        <label>Start Date: <input type="date" id="start_date" name="start_date" value="<?= $subscriber['start_date'] ?>" required oninput="updateEndDate()"></label>
        <label>End Date: <input type="date" id="end_date" value="<?= $subscriber['end_date'] ?>" readonly></label>
        <label>Current Photo:</label>
        <?php if ($subscriber['photo']): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($subscriber['photo']) ?>" class="current-photo" alt="Current photo of <?= htmlspecialchars($subscriber['name']) ?>">
        <?php else: ?>
            <p>No photo available</p>
        <?php endif; ?>
        <label>Upload New Photo (optional): <input type="file" name="photo" accept="image/jpeg,image/png"></label>
        <label>Or Capture New Photo:</label>
        <video id="video" autoplay></video>
        <canvas id="canvas" style="display: none;"></canvas>
        <img id="captured-image" alt="Captured Image" style="display: none;">
        <input type="hidden" name="photo_data" id="photo_data">
        <button type="button" id="capture">Capture Photo</button>
        <button type="submit">Update Subscription</button>
    </form>
</main>
<script>
function updateEndDate() {
    const startDate = document.getElementById('start_date').value;
    const months = document.getElementById('months').value;
    if (startDate && months) {
        const date = new Date(startDate);
        date.setMonth(date.getMonth() + parseInt(months));
        document.getElementById('end_date').value = date.toISOString().split('T')[0];
    }
}
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const captureButton = document.getElementById('capture');
const capturedImage = document.getElementById('captured-image');
const photoDataInput = document.getElementById('photo_data');
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
    })
    .catch(err => {
        console.error("Camera error:", err);
        alert("Cannot access camera. Check permissions.");
    });
captureButton.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    capturedImage.src = imageData;
    capturedImage.style.display = 'block';
    video.style.display = 'none';
    captureButton.style.display = 'none';
    photoDataInput.value = imageData;
});
document.getElementById('updateForm').addEventListener('submit', (e) => {
    const name = document.querySelector('input[name="name"]').value;
    const number = document.querySelector('input[name="number"]').value;
    const age = document.querySelector('input[name="age"]').value;
    if (name.length < 2 || number <= 0 || age < 16 || age > 100) {
        e.preventDefault();
        alert('Please check your inputs: Name must be 2+ characters, Number must be positive, Age must be 16-100.');
    }
});
</script>
</body>
</html>