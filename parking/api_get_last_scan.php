<?php
header('Content-Type: application/json');
$file = "last_scan.txt";
if (file_exists($file)) {
    echo json_encode(["uid" => trim(file_get_contents($file))]);
}
else {
    echo json_encode(["uid" => ""]);
}
?>
