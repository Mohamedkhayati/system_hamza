<?php
// Connect to DB
date_default_timezone_set('Africa/Tunis');

$conn = mysql_connect('localhost', 'root', '');
if (!$conn) {
    die('Connection failed: ' . mysql_error());
}
$db_selected = mysql_select_db('gym_management', $conn);
if (!$db_selected) {
    die('Database selection failed: ' . mysql_error());
}

// Determine type and search
$type = (isset($_GET['type']) && $_GET['type'] === 'inactive') ? 'inactive' : 'active';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = mysql_real_escape_string("%$search%");
$where = ($type === 'inactive') ? 'WHERE active = 0' : 'WHERE active = 1';
if ($search) {
    $where .= " AND (name LIKE '$search_param' OR number LIKE '$search_param' OR age LIKE '$search_param')";
}

// Fetch subscribers
$query = "SELECT id, name, number, age, start_date, end_date, active FROM subscribers $where ORDER BY id DESC";
$result = mysql_query($query);
if (!$result) {
    die('Error fetching data: ' . mysql_error());
}

// Output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="subscribers_' . $type . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Name', 'Number', 'Age', 'Start Date', 'End Date', 'Active'));

while ($row = mysql_fetch_assoc($result)) {
    fputcsv($output, array(
        $row['id'],
        $row['name'],
        $row['number'],
        $row['age'],
        $row['start_date'],
        $row['end_date'],
        ($row['active'] ? 'Yes' : 'No')
    ));
}

fclose($output);
mysql_close($conn);
exit();
?>
