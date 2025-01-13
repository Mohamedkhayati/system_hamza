<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $delete_query = "DELETE FROM subscribers WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $id);
    $delete_stmt->execute();

    echo "<p>Subscriber deleted successfully!</p>";
} else {
    echo "<p>No subscriber ID provided.</p>";
    exit();
}
?>

<p><a href="Afficher.php">Back to Subscribers List</a></p>
