<?php
// c:/xampp/htdocs/parking/api_get_gate_events.php
include "config.php";
header("Content-Type: application/json");

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$result = $conn->query("SELECT * FROM gate_events WHERE id > $last_id ORDER BY id ASC");
$events = [];
while($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode(["events" => $events]);
?>
