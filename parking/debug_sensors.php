<?php
include 'config.php';
$res = $conn->query("SELECT * FROM sensor_status");
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data, JSON_PRETTY_PRINT);
?>