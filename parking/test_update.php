<?php
// Test POST request to api_update_sensors.php
$url = 'http://localhost/parking/api_update_sensors.php';
$data = array('s1' => 1, 's1_name' => 'Tester', 's1_plate' => 'T123');

$options = array(
    'http' => array(
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data),
    ),
);
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response: " . $result . "\n";

// Check DB after update
include 'config.php';
$res = $conn->query("SELECT * FROM sensor_status WHERE slot_id = 1");
$row = $res->fetch_assoc();
echo "New DB State: " . json_encode($row, JSON_PRETTY_PRINT) . "\n";
?>