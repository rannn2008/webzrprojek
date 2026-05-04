<?php
include 'config.php';

// Simulate what api_check_access.php does
$uid = '2A01FB05';
$uid = strtoupper(trim(str_replace(' ', '', $uid)));

echo "Testing UID: [$uid]\n";

$stmt = $conn->prepare("SELECT id, name, plate_number, balance FROM users WHERE rfid_uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

echo "Rows found: " . $result->num_rows . "\n";

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "User found: " . $user['name'] . "\n";
    echo "Plate: " . $user['plate_number'] . "\n";
    echo "ACCESS WOULD BE: GRANTED\n";
} else {
    echo "ACCESS WOULD BE: DENIED (BELUM TERDAFTAR)\n";
}
?>