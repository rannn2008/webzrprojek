<?php
// c:/xampp/htdocs/parking/api_get_settings.php
include "config.php";
include "auth.php";
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

restrictToAdmin();

echo json_encode([
    "parking_rate" => (int)getSetting($conn, "parking_rate", "3000"),
    "min_fee" => (int)getSetting($conn, "min_fee", "3000"),
    "grace_period" => (int)getSetting($conn, "grace_period", "15"),
    "billing_interval_minutes" => (int)getSetting($conn, "billing_interval_minutes", "10"),
    "server_time" => date("Y-m-d H:i:s")
]);
?>
