<?php
session_start();
date_default_timezone_set('Africa/Tunis');

// Connect to DB
$conn = mysql_connect('localhost', 'root', '');
if (!$conn) {
    die('Connection failed: ' . mysql_error());
}
$db_selected = mysql_select_db('gym_management', $conn);
if (!$db_selected) {
    die('Database selection failed: ' . mysql_error());
}

// Check POST request and CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['csrf']) && $_POST['csrf'] === $_SESSION['csrf']) {
    $id = intval($_POST['id']);
    $id_safe = mysql_real_escape_string($id);
    $delete_query = "DELETE FROM subscribers WHERE id = $id_safe";
    $res = mysql_query($delete_query);

    if ($res) {
        header("Location: afficher.php?success=deleted");
    } else {
        header("Location: afficher.php?error=delete_failed");
    }
} else {
    header("Location: afficher.php?error=invalid_request");
}

// Close connection
mysql_close($conn);
exit();
?>
