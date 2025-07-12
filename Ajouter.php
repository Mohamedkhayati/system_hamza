<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $number = $_POST['number'];
    $age = $_POST['age'];
    $start_date = $_POST['start_date'];
    $months = intval($_POST['months']);

    // Calculate end_date
    $end_date = date('Y-m-d', strtotime("+$months months", strtotime($start_date)));
    $today = date('Y-m-d');
    $active = ($end_date >= $today) ? 1 : 0;

    // Handle photo upload
    $photo = null;
    if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
        $photo_data = $_POST['photo_data'];
        // Remove the data URL prefix (e.g., "data:image/jpeg;base64,")
        $photo_data = preg_replace('#^data:image/\w+;base64,#i', '', $photo_data);
        $photo = base64_decode($photo_data);
        $allowed_types = ['image/jpeg', 'image/png'];
        $finfo = finfo_open();
        $mime_type = finfo_buffer($finfo, $photo, FILEINFO_MIME_TYPE);
        finfo_close($finfo);
        $max_size = 2 * 1024 * 1024; // 2MB
        if (!in_array($mime_type, $allowed_types) || strlen($photo) > $max_size) {
            $error = "Invalid photo format or size. Only JPEG/PNG up to 2MB allowed.";
        }
    }

    if (!$error) {
        $query = "INSERT INTO subscribers (name, number, age, start_date, end_date, active, photo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $null = null;
        $stmt->bind_param("siissib", $name, $number, $age, $start_date, $end_date, $active, $null);
        if ($photo) {
            $stmt->send_long_data(6, $photo);
        }
        if ($stmt->execute()) {
            $success = "Subscriber added successfully!";
        } else {
            $error = "Error adding subscriber: " . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subscriber</title>
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
        .error {
            color: red;
            text-align: center;
        }
        .success {
            color: green;
            text-align: center;
        }
        #video {
            width: 100%;
            max-height: 300px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        #canvas {
            display: none;
        }
        #capture {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        #capture:hover {
            background-color: #0056b3;
        }
        #captured-image {
            width: 100%;
            max-height: 300px;
            margin-bottom: 15px;
            display: none;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<nav>
    <ul>
        <li><a href="afficher.php">Afficher</a></li>
        <li><a href="ajouter.php">Ajouter</a></li>
        <li><a href="non_active.php">Non Active</a></li>
    </ul>
</nav>

<h1>Add Subscriber</h1>
<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <label>Name: <input type="text" name="name" required></label>
    <label>Number: <input type="number" name="number" required></label>
    <label>Age: <input type="number" name="age" required></label>
    <label>Start Date: <input type="date" name="start_date" required></label>
    <label>Photo (JPEG/PNG, max 2MB):</label>
    <video id="video" autoplay></video>
    <canvas id="canvas"></canvas>
    <img id="captured-image" alt="Captured Image">
    <input type="hidden" name="photo_data" id="photo_data">
    <button type="button" id="capture">Capture Photo</button>
    <label>Duration (in months): 
        <select name="months" required>
            <option value="1">1 Month</option>
            <option value="3">3 Months</option>
            <option value="6">6 Months</option>
            <option value="12">12 Months</option>
        </select>
    </label>
    <button type="submit">Add Subscriber</button>
</form>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureButton = document.getElementById('capture');
    const capturedImage = document.getElementById('captured-image');
    const photoDataInput = document.getElementById('photo_data');

    // Access the camera
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            video.srcObject = stream;
        })
        .catch(err => {
            console.error("Error accessing camera: ", err);
            alert("Could not access the camera. Please ensure camera permissions are granted.");
        });

    // Capture photo
    captureButton.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const imageData = canvas.toDataURL('image/jpeg', 0.8); // JPEG, 80% quality
        capturedImage.src = imageData;
        capturedImage.style.display = 'block';
        video.style.display = 'none';
        captureButton.style.display = 'none';
        photoDataInput.value = imageData;
    });
</script>

</body>
</html>
