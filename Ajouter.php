<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'gym_management');
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $name = $_POST['name'];
    $number = $_POST['number'];
    $age = $_POST['age'];
    $start_date = $_POST['start_date'];
    $months = intval($_POST['months']);

    // Calculate end_date by adding the selected number of months to start_date
    $end_date = date('Y-m-d', strtotime("+$months months", strtotime($start_date)));

    $today = date('Y-m-d');
    $active = ($end_date >= $today) ? 1 : 0;

    $query = "INSERT INTO subscribers (name, number, age, start_date, end_date, active) 
              VALUES ('$name', '$number', $age, '$start_date', '$end_date', $active)";

    if ($conn->query($query)) {
        echo "Subscriber added successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
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
<form method="POST">
    <label>Name: <input type="text" name="name" required></label>
    <label>Number: <input type="number" name="number" required></label>
    <label>Age: <input type="number" name="age" required></label>
    <label>Start Date: <input type="date" name="start_date" required></label>
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
