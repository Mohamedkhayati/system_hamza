<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get today's date
$today = date('Y-m-d');

// Update subscribers whose end_date is earlier than today
$query = "UPDATE subscribers SET active = 0 WHERE end_date < '$today' AND active = 1";
if ($conn->query($query)) {
    echo "Subscriptions updated successfully!";
} else {
    echo "Error updating subscriptions: " . $conn->error;
}

$conn->close();
?>
