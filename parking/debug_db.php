<?php
include 'config.php';
$res = $conn->query("DESCRIBE sensor_status");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
