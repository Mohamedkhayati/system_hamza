<?php
$conn = new mysqli('localhost', 'root', '', 'gym_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$type = isset($_GET['type']) && $_GET['type'] === 'inactive' ? 'inactive' : 'active';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";
$where = $type === 'inactive' ? 'WHERE active = 0' : 'WHERE active = 1';
if ($search) {
    $where .= " AND (name LIKE '$search_param' OR number LIKE '$search_param' OR age LIKE '$search_param')";
}
$query = "SELECT id, name, number, age, start_date, end_date, active FROM subscribers $where ORDER BY id DESC";
$result = $conn->query($query);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="subscribers_' . $type . '.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Number', 'Age', 'Start Date', 'End Date', 'Active']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['number'],
        $row['age'],
        $row['start_date'],
        $row['end_date'],
        $row['active'] ? 'Yes' : 'No'
    ]);
}
fclose($output);
$conn->close();
exit();
?>