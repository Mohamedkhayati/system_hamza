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
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= $max_size) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        } else {
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
    <label>Photo (JPEG/PNG, max 2MB): <input type="file" name="photo" accept="image/jpeg,image/png"></label>
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

</body>
</html>