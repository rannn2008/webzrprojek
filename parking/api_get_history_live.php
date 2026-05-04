<?php
// c:/xampp/htdocs/parking/api_get_history_live.php
include "config.php";
include "auth.php";
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

restrictToAdmin();

$rows = [];
$sql = "SELECT h.id, h.action, h.timestamp, h.fee, u.name, u.plate_number
        FROM parking_history h
        JOIN users u ON h.user_id = u.id
        ORDER BY h.id DESC
        LIMIT 100";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            "id" => (int)$row["id"],
            "action" => $row["action"],
            "timestamp" => $row["timestamp"],
            "fee" => (int)$row["fee"],
            "name" => $row["name"],
            "plate_number" => $row["plate_number"]
        ];
    }
}

echo json_encode([
    "history" => $rows,
    "server_time" => date("Y-m-d H:i:s")
]);
?>
