<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "SELECT id, name, number, age, start_date, end_date, active, photo FROM subscribers WHERE active = 0";
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
        .thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .popup-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            text-align: center;
            position: relative;
        }
        .popup-content img {
            max-width: 400px;
            height: auto;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
        }
        @media (max-width: 600px) {
            .popup-content {
                width: 90%;
            }
            .popup-content img {
                max-width: 100%;
            }
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
        <th>Photo</th>
        <th>Name</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Actions</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td>
                    <?php if ($row['photo']): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($row['photo']) ?>" class="thumbnail" alt="Subscriber Photo">
                    <?php else: ?>
                        No Photo
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['start_date'] ?></td>
                <td><?= $row['end_date'] ?></td>
                <td>
                    <button onclick="showDetails({
                        id: <?= $row['id'] ?>,
                        name: '<?= addslashes(htmlspecialchars($row['name'])) ?>',
                        number: <?= $row['number'] ?>,
                        age: <?= $row['age'] ?>,
                        start_date: '<?= $row['start_date'] ?>',
                        end_date: '<?= $row['end_date'] ?>',
                        active: <?= $row['active'] ?>,
                        photo: '<?php echo $row['photo'] ? base64_encode($row['photo']) : ''; ?>'
                    })">View Details</button>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No non-active subscribers found</td></tr>
    <?php endif; ?>
</table>

<div id="subscriber-popup" class="popup">
    <div class="popup-content">
        <span class="close-btn" onclick="closePopup()">×</span>
        <img id="popup-photo" src="" alt="Subscriber Photo" style="display: none;">
        <h2 id="popup-name"></h2>
        <p><strong>ID:</strong> <span id="popup-id"></span></p>
        <p><strong>Number:</strong> <span id="popup-number"></span></p>
        <p><strong>Age:</strong> <span id="popup-age"></span></p>
        <p><strong>Start Date:</strong> <span id="popup-start-date"></span></p>
        <p><strong>End Date:</strong> <span id="popup-end-date"></span></p>
        <p><strong>Active:</strong> <span id="popup-active"></span></p>
    </div>
</div>

<script>
function showDetails(subscriber) {
    const popup = document.getElementById('subscriber-popup');
    document.getElementById('popup-id').textContent = subscriber.id;
    document.getElementById('popup-name').textContent = subscriber.name;
    document.getElementById('popup-number').textContent = subscriber.number;
    document.getElementById('popup-age').textContent = subscriber.age;
    document.getElementById('popup-start-date').textContent = subscriber.start_date;
    document.getElementById('popup-end-date').textContent = subscriber.end_date;
    document.getElementById('popup-active').textContent = subscriber.active ? 'Yes' : 'No';
    const photo = document.getElementById('popup-photo');
    if (subscriber.photo) {
        photo.src = 'data:image/jpeg;base64,' + subscriber.photo;
        photo.style.display = 'block';
    } else {
        photo.style.display = 'none';
    }
    popup.style.display = 'flex';
}

function closePopup() {
    document.getElementById('subscriber-popup').style.display = 'none';
}
</script>

</body>
</html>
<?php
$result->free();
$conn->close();
?>