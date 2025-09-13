<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";
$query = "SELECT id, name, number, age, start_date, end_date, active, photo FROM subscribers WHERE (name LIKE ? OR number LIKE ? OR age LIKE ?) ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribers List - Gym Management System</title>
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
        #search-bar { margin-bottom: 20px; text-align: center; }
        #search-bar form { display: inline-block; background-color: #fff; padding: 10px; border-radius: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        #search-bar input[type="text"] { padding: 8px 15px; border: 1px solid #ccc; border-radius: 15px; font-size: 16px; width: 250px; }
        #search-bar button { padding: 8px 15px; border: none; background-color: #4CAF50; color: white; font-size: 16px; cursor: pointer; border-radius: 15px; margin-left: 10px; transition: background 0.3s; }
        #search-bar button:hover { background-color: #45a049; }
        .export-btn { background-color: #2196F3; }
        .export-btn:hover { background-color: #1976D2; }
        table { width: 100%; margin: 20px 0; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #4CAF50; color: white; }
        .thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer; }
        button { padding: 6px 12px; background-color: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 5px; margin: 2px; transition: background 0.3s; }
        button:hover { background-color: #45a049; }
        .delete-btn { background-color: #f44336; }
        .delete-btn:hover { background-color: #da190b; }
        .view-btn { background-color: #2196F3; }
        .view-btn:hover { background-color: #1976D2; }
        .popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .popup-content { background-color: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; text-align: center; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .popup-content img { max-width: 300px; height: auto; border-radius: 10px; margin-bottom: 15px; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #aaa; }
        .close-btn:hover { color: #000; }
        .success { color: green; text-align: center; margin: 10px 0; }
        @media (max-width: 768px) {
            header nav ul { flex-direction: column; }
            header nav ul li { margin: 10px 0; }
            #search-bar input[type="text"] { width: 80%; }
            table { font-size: 14px; }
            .popup-content { width: 90%; }
        }
    </style>
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="afficher.php" aria-current="page">Subscribers</a></li>
            <li><a href="ajouter.php">Add New</a></li>
            <li><a href="non_active.php">Non-Active</a></li>
        </ul>
    </nav>
</header>
<main>
    <h1>List of Subscribers</h1>
    <?php if ($success === 'added'): ?><p class="success">Subscriber added successfully!</p><?php elseif ($success === 'updated'): ?><p class="success">Subscriber updated successfully!</p><?php elseif ($success === 'deleted'): ?><p class="success">Subscriber deleted successfully!</p><?php endif; ?>
    <div id="search-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search by name, number, or age" value="<?= htmlspecialchars($search) ?>" aria-label="Search subscribers">
            <button type="submit">Search</button>
            <a href="export.php?type=active&search=<?= urlencode($search) ?>"><button type="button" class="export-btn">Export CSV</button></a>
        </form>
    </div>
    <table>
        <tr><th>ID</th><th>Photo</th><th>Name</th><th>Number</th><th>Age</th><th>Start Date</th><th>End Date</th><th>Active</th><th>Actions</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td>
                    <?php if ($row['photo']): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($row['photo']) ?>" class="thumbnail" alt="Photo of <?= htmlspecialchars($row['name']) ?>" data-photo="<?= base64_encode($row['photo']) ?>" onclick="showDetails(<?php echo json_encode([
                            'id' => $row['id'],
                            'name' => htmlspecialchars($row['name']),
                            'number' => $row['number'],
                            'age' => $row['age'],
                            'start_date' => $row['start_date'],
                            'end_date' => $row['end_date'],
                            'active' => $row['active']
                        ], JSON_HEX_QUOT | JSON_HEX_APOS); ?>, this.getAttribute('data-photo'))">
                    <?php else: ?>
                        No Photo
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['number'] ?></td>
                <td><?= $row['age'] ?></td>
                <td><?= $row['start_date'] ?></td>
                <td><?= $row['end_date'] ?></td>
                <td><?= $row['active'] ? 'Yes' : 'No' ?></td>
                <td>
    <button class="view-btn" onclick='showDetails(
        <?php echo json_encode([
            "id" => $row["id"],
            "name" => htmlspecialchars($row["name"], ENT_QUOTES),
            "number" => $row["number"],
            "age" => $row["age"],
            "start_date" => $row["start_date"],
            "end_date" => $row["end_date"],
            "active" => $row["active"]
        ], JSON_HEX_QUOT | JSON_HEX_APOS); ?>, 
        <?php echo $row["photo"] ? json_encode(base64_encode($row["photo"])) : "null"; ?>
    )'>View Details</button>

    <form action="update.php" method="post" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32))) ?>">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <button type="submit">Update</button>
    </form>
    <form action="delete.php" method="post" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <button class="delete-btn" type="submit" onclick="return confirm('Are you sure you want to delete this subscriber?')">Delete</button>
    </form>
</td>

            </tr>
        <?php endwhile; ?>
        <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="9">No subscribers found.</td></tr>
        <?php endif; ?>
    </table>
</main>
<div id="subscriber-popup" class="popup" role="dialog" aria-modal="true">
    <div class="popup-content">
        <span class="close-btn" onclick="closePopup()" aria-label="Close popup">&times;</span>
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
function showDetails(subscriber, photoData) {
    try {
        const popup = document.getElementById('subscriber-popup');
        document.getElementById('popup-id').textContent = subscriber.id;
        document.getElementById('popup-name').textContent = subscriber.name;
        document.getElementById('popup-number').textContent = subscriber.number;
        document.getElementById('popup-age').textContent = subscriber.age;
        document.getElementById('popup-start-date').textContent = subscriber.start_date;
        document.getElementById('popup-end-date').textContent = subscriber.end_date;
        document.getElementById('popup-active').textContent = subscriber.active ? 'Yes' : 'No';
        const photo = document.getElementById('popup-photo');
        if (photoData) {
            photo.src = 'data:image/jpeg;base64,' + photoData;
            photo.style.display = 'block';
            photo.alt = 'Photo of ' + subscriber.name;
        } else {
            photo.style.display = 'none';
            photo.alt = '';
        }
        popup.style.display = 'flex';
    } catch (error) {
        console.error('Error in showDetails:', error);
        alert('Failed to display subscriber details. Please check the console for errors.');
    }
}
function closePopup() {
    document.getElementById('subscriber-popup').style.display = 'none';
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePopup();
});
</script>
<?php
$stmt->close();
$conn->close();
?>
</body>
</html>