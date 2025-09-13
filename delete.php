<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['csrf']) && $_POST['csrf'] === $_SESSION['csrf']) {
    $id = intval($_POST['id']);
    $delete_query = "DELETE FROM subscribers WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $id);
    if ($delete_stmt->execute()) {
        header("Location: afficher.php?success=deleted");
    } else {
        header("Location: afficher.php?error=delete_failed");
    }
    $delete_stmt->close();
} else {
    header("Location: afficher.php?error=invalid_request");
}
$conn->close();
exit();
?>