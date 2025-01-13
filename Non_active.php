<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$query = "SELECT * FROM subscribers WHERE active = 0";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Non-Active Subscribers</title>
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
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #FF6347;
            color: white;
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


<h1>Non-Active Subscribers</h1>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Start Date</th>
        <th>End Date</th>
    </tr>
    <?php if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['start_date']}</td><td>{$row['end_date']}</td></tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No non-active subscribers found</td></tr>";
    }
    ?>
</table>

</body>
</html>
