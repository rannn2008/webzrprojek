<?php
// c:/xampp/htdocs/parking/api_get_revenue.php
include 'config.php';
include 'auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(["chart" => []]);
    exit();
}

// Get Revenue for the last 7 days
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));

    $sql = "SELECT SUM(fee) as total FROM parking_history WHERE DATE(timestamp) = '$date' AND action = 'OUT'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    $chart[] = [
        "label" => $label,
        "amount" => $row['total'] ?? 0
    ];
}

// Calculate Total Revenue
$total_sql = "SELECT SUM(fee) as grand_total FROM parking_history WHERE action = 'OUT'";
$total_res = $conn->query($total_sql);
$total_row = $total_res->fetch_assoc();

echo json_encode([
    "chart" => $chart,
    "grand_total" => number_format($total_row['grand_total'] ?? 0, 0, ',', '.')
]);
?>
