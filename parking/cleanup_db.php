<?php
include 'config.php';
$conn->query("UPDATE users SET rfid_uid = UPPER(TRIM(rfid_uid))");
echo "Cleaned up UIDs in Database.\n";
// Also clean up plate numbers
$conn->query("UPDATE users SET plate_number = UPPER(REPLACE(plate_number, ' ', ''))");
echo "Cleaned up Plate Numbers in Database.\n";
?>
