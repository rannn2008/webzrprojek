<?php
include 'config.php';
$res = $conn->query("SELECT * FROM users");
echo "Total Users: " . $res->num_rows . "\n\n";
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | UID: '" . $row['rfid_uid'] . "' | Plate: " . $row['plate_number'] . " | Balance: " . ($row['balance'] ?? 'N/A') . "\n";
}
?>
