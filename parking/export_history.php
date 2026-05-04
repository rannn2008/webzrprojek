<?php
// c:/xampp/htdocs/parking/export_history.php
include 'config.php';
include 'auth.php';
requireLogin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=parking_history_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Header
fputcsv($output, array('ID', 'Time', 'Name', 'Plate', 'Action', 'Fee (Rp)'));

// Data
$sql = "SELECT h.id, h.timestamp, u.name, u.plate_number, h.action, h.fee 
        FROM parking_history h 
        JOIN users u ON h.user_id = u.id 
        ORDER BY h.id DESC";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
