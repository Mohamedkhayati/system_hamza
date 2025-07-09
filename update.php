<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $query = "SELECT * FROM subscribers WHERE id = ?";
    $stmt = $conn->prepare($query);
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

        // Calculate end date based on start date and months
        $end_date = (new DateTime($start_date))->modify("+$months months")->format('Y-m-d');
        $active = ($end_date >= date('Y-m-d')) ? 1 : 0;

        // Handle photo upload
        $photo = $subscriber['photo'];
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
            $update_query = "UPDATE subscribers SET name = ?, number = ?, age = ?, start_date = ?, end_date = ?, active = ?, photo = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $null = null;
            $update_stmt->bind_param("siissibi", $name, $number, $age, $start_date, $end_date, $active, $null, $id);
            if ($photo) {
                $update_stmt->send_long_data(6, $photo);
            }
            if ($update_stmt->execute()) {
                $success = "Subscriber updated successfully! Subscription set for $months month(s).";
            } else {
                $error = "Error updating subscriber: " . $conn->error;
            }
            $update_stmt->close();
        }
    }
} else {
    echo "<p>No subscriber ID provided.</p>";
    $conn->close();
    exit();
}
$conn->close();
?>

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
    </script>
</head>
<body>
<nav>
    <ul>
        <li><a href="afficher.php">Afficher</a></li>
        <li><a href="ajouter.php">Ajouter</a></li>
        <li><a href="non_active.php">Non Active</a></li>
    </ul>
</nav>

<h1>Renew Subscription</h1>
<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
<form action="update.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $subscriber['id'] ?>">
    <label for="name">Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($subscriber['name']) ?>" required><br><br>
    <label for="number">Number:</label>
    <input type="number" name="number" value="<?= $subscriber['number'] ?>" required><br><br>
    <label for="age">Age:</label>
    <input type="number" name="age" value="<?= $subscriber['age'] ?>" required><br><br>
    <label for="months">Subscription Period:</label>
    <select name="months" id="months" onchange="updateEndDate()" required>
        <option value="1">1 Month</option>
        <option value="3">3 Months</option>
        <option value="6">6 Months</option>
        <option value="12">12 Months</option>
    </select><br><br>
    <label for="start_date">Start Date:</label>
    <input type="date" id="start_date" name="start_date" value="<?= $subscriber['start_date'] ?>" oninput="updateEndDate()" required><br><br>
    <label for="end_date">End Date:</label>
    <input type="date" id="end_date" name="end_date" value="<?= $subscriber['end_date'] ?>" readonly><br><br>
    <label for="photo">Current Photo:</label>
    <?php if ($subscriber['photo']): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($subscriber['photo']) ?>" class="current-photo" alt="Current Photo"><br>
    <?php else: ?>
        <p>No photo available</p>
    <?php endif; ?>
    <label for="photo">Upload New Photo (JPEG/PNG, max 2MB, optional):</label>
    <input type="file" name="photo" accept="image/jpeg,image/png"><br><br>
    <button type="submit">Update Subscription</button>
</form>

<p><a href="afficher.php">Back to Subscribers List</a></p>
</body>
</html>
