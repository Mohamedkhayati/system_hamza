<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Subscription</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            padding: 20px;
        }
        form {
            width: 50%;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background-color: #45a049;
        }
        nav ul {
            list-style-type: none;
            padding: 0;
            background-color: #333;
            margin: 0;
        }
        nav ul li {
            display: inline;
            margin-right: 20px;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: inline-block;
        }
        nav ul li a:hover {
            background-color: #575757;
        }
        .current-photo {
            max-width: 100px;
            height: auto;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
            text-align: center;
        }
    </style>
</head>
<body>
<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
$subscriber = [];

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("SELECT * FROM subscribers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscriber = $result->fetch_assoc();
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
        $name = $_POST['name'];
        $number = $_POST['number'];
        $age = $_POST['age'];
        $start_date = $_POST['start_date'];
        $months = intval($_POST['months']);

        $end_date = (new DateTime($start_date))->modify("+$months months")->format('Y-m-d');
        $active = ($end_date >= date('Y-m-d')) ? 1 : 0;

        $photo = $subscriber['photo'];
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = preg_replace('#^data:image/\w+;base64,#i', '', $_POST['photo_data']);
            $photo = base64_decode($photo_data);
            $finfo = finfo_open();
            $mime_type = finfo_buffer($finfo, $photo, FILEINFO_MIME_TYPE);
            finfo_close($finfo);
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024;
            if (!in_array($mime_type, $allowed_types) || strlen($photo) > $max_size) {
                $error = "Invalid photo format or size. Only JPEG/PNG up to 2MB allowed.";
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
                $success = "Subscriber updated successfully!";
            } else {
                $error = "Error updating subscriber: " . $conn->error;
            }
            $stmt->close();
        }
    }
} else {
    echo "<p>No subscriber ID provided.</p>";
    $conn->close();
    exit();
}
$conn->close();
?>

<nav>
    <ul>
        <li><a href="afficher.php">Afficher</a></li>
        <li><a href="ajouter.php">Ajouter</a></li>
        <li><a href="non_active.php">Non Active</a></li>
    </ul>
</nav>

<h1>Renew Subscription</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="POST">
    <input type="hidden" name="id" value="<?= $subscriber['id'] ?>">

    <label>Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($subscriber['name']) ?>" required>

    <label>Number:</label>
    <input type="number" name="number" value="<?= $subscriber['number'] ?>" required>

    <label>Age:</label>
    <input type="number" name="age" value="<?= $subscriber['age'] ?>" required>

    <label>Subscription Period:</label>
    <select name="months" id="months" onchange="updateEndDate()" required>
        <option value="1">1 Month</option>
        <option value="3">3 Months</option>
        <option value="6">6 Months</option>
        <option value="12">12 Months</option>
    </select>

    <label>Start Date:</label>
    <input type="date" id="start_date" name="start_date" value="<?= $subscriber['start_date'] ?>" oninput="updateEndDate()" required>

    <label>End Date:</label>
    <input type="date" id="end_date" name="end_date" value="<?= $subscriber['end_date'] ?>" readonly>

    <label>Current Photo:</label>
    <?php if ($subscriber['photo']): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($subscriber['photo']) ?>" class="current-photo" alt="Current Photo">
    <?php else: ?>
        <p>No photo available</p>
    <?php endif; ?>

    <label>Capture New Photo (optional):</label>
    <video id="video" autoplay></video>
    <canvas id="canvas" style="display: none;"></canvas>
    <img id="captured-image" alt="Captured Image" style="display:none; max-width: 100%; border-radius: 5px; margin-bottom: 15px;">
    <input type="hidden" name="photo_data" id="photo_data">
    <button type="button" id="capture">Capture Photo</button>

    <button type="submit">Update Subscription</button>
</form>

<script>
function updateEndDate() {
    const startDateInput = document.getElementById('start_date').value;
    const monthsSelect = document.getElementById('months').value;
    if (startDateInput && monthsSelect) {
        const startDate = new Date(startDateInput);
        startDate.setMonth(startDate.getMonth() + parseInt(monthsSelect));
        const endDateInput = document.getElementById('end_date');
        endDateInput.value = startDate.toISOString().split('T')[0];
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
</script>

</body>
</html>
