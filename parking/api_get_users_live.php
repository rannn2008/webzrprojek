<?php
// c:/xampp/htdocs/parking/api_get_users_live.php
include "config.php";
include "auth.php";
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

restrictToAdmin();

$pending = [];
$qPending = $conn->query("SELECT t.id, t.user_id, t.amount, t.created_at, u.name, u.plate_number 
                          FROM topup_requests t 
                          JOIN users u ON t.user_id = u.id 
                          WHERE t.status='PENDING'
                          ORDER BY t.id DESC");
if ($qPending) {
    while ($row = $qPending->fetch_assoc()) {
        $pending[] = [
            "id" => (int)$row["id"],
            "user_id" => (int)$row["user_id"],
            "name" => $row["name"],
            "plate_number" => $row["plate_number"],
            "amount" => (int)$row["amount"],
            "created_at" => $row["created_at"]
        ];
    }
}

$users = [];
$qUsers = $conn->query("SELECT id, name, plate_number, balance FROM users ORDER BY id DESC");
if ($qUsers) {
    while ($row = $qUsers->fetch_assoc()) {
        $users[] = [
            "id" => (int)$row["id"],
            "name" => $row["name"],
            "plate_number" => $row["plate_number"],
            "balance" => (int)$row["balance"]
        ];
    }
}

echo json_encode([
    "pending" => $pending,
    "users" => $users,
    "server_time" => date("Y-m-d H:i:s")
]);
?>
