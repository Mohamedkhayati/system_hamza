<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$search = $_GET['search-name'] ?? '';
$query = "SELECT * FROM subscribers WHERE name LIKE '%$search%'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribers List</title>
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
            background-color: #4CAF50;
            color: white;
        }
        button {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
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

<h1>List of Subscribers</h1>
<form method="GET" style="text-align:center; margin-bottom: 20px;">
    <input type="text" name="search-name" placeholder="Search by name" value="<?= $search ?>">
    <button type="submit">Search</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>number</th>
        <th>Age</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Active</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['number'] ?></td>
            <td><?= $row['age'] ?></td>
            <td><?= $row['start_date'] ?></td>
            <td><?= $row['end_date'] ?></td>
            <td><?= $row['active'] ? 'Yes' : 'No' ?></td>
            <td>
                <form action="update.php" method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit">Update</button>
                </form>
                <form action="delete.php" method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" onclick="return confirm('Are you sure you want to delete this subscriber?')">Delete</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
