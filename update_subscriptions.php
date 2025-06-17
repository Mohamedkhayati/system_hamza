<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$today = date('Y-m-d');
$query = "UPDATE subscribers SET active = 0 WHERE end_date < ? AND active = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
if ($stmt->execute()) {
    echo "Subscriptions updated successfully!";
} else {
    echo "Error updating subscriptions: " . $conn->error;
}
$stmt->close();
$conn->close();
?>