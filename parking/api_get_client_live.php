<?php
// c:/xampp/htdocs/parking/api_get_client_live.php
include "config.php";
include "auth.php";
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

restrictToClient();

$client_id = (int)$_SESSION["client_id"];

$user = $conn->query("SELECT id, name, plate_number, email, balance, points, avatar, created_at FROM users WHERE id = $client_id")->fetch_assoc();
if (!$user) {
    echo json_encode(["error" => true, "message" => "User not found"]);
    exit;
}

$total_fees = (int)($conn->query("SELECT SUM(fee) as total FROM parking_history WHERE user_id = $client_id AND action='OUT'")->fetch_assoc()["total"] ?? 0);

$loyalty_points_available = (int)($user["points"] ?? 0);
$loyalty_points_total = $loyalty_points_available; // We use current points for display

$check_park = $conn->query("
    SELECT timestamp FROM parking_history AS ph1
    WHERE user_id = $client_id AND action='IN'
    AND NOT EXISTS (
        SELECT 1 FROM parking_history AS ph2
        WHERE ph2.user_id = $client_id AND ph2.action='OUT' AND ph2.id > ph1.id
    )
    ORDER BY id DESC LIMIT 1
");
$active_session = false;
$park_time = "";
if ($check_park && $check_park->num_rows > 0) {
    $active_session = true;
    $park_time = $check_park->fetch_assoc()["timestamp"];
}

$history = [];
$res = $conn->query("SELECT id, action, fee, timestamp FROM parking_history WHERE user_id = $client_id ORDER BY id DESC LIMIT 20");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $history[] = [
            "id" => (int)$row["id"],
            "action" => $row["action"],
            "fee" => (int)$row["fee"],
            "timestamp" => $row["timestamp"]
        ];
    }
}

echo json_encode([
    "user" => [
        "id" => (int)$user["id"],
        "name" => $user["name"],
        "plate_number" => $user["plate_number"],
        "email" => $user["email"],
        "balance" => (int)$user["balance"],
        "created_at" => $user["created_at"]
    ],
    "total_fees" => $total_fees,
    "active_session" => $active_session,
    "park_time" => $park_time,
    "history" => $history,
    "loyalty" => [
        "total_points" => $loyalty_points_total,
        "available_points" => $loyalty_points_available,
        "claimed_points" => $claimed_points,
        "reward_threshold" => 1000
    ],
    "settings" => [
        "parking_rate" => (int)getSetting($conn, "parking_rate", "3000"),
        "min_fee" => (int)getSetting($conn, "min_fee", "3000"),
        "grace_period" => (int)getSetting($conn, "grace_period", "15"),
        "billing_interval_minutes" => (int)getSetting($conn, "billing_interval_minutes", "10")
    ],
    "server_time" => date("Y-m-d H:i:s")
]);
?>
