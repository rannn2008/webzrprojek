<?php
// c:/xampp/htdocs/parking/api_get_analytics.php
include 'config.php';
header('Content-Type: application/json');

// Get actual vehicle traffic & parking revenue for the last 7 days.
// TOPUP is intentionally excluded from revenue because it is wallet balance,
// not earned parking income. BOOK is counted as revenue, but not vehicle traffic.
$analytics_data = [];
$revenue_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));

    $query = "SELECT COUNT(*) as count FROM parking_history WHERE action = 'IN' AND DATE(timestamp) = '$date'";
    $row = $conn->query($query)->fetch_assoc();

    $rev_query = "SELECT SUM(fee) as total FROM parking_history WHERE action IN ('OUT', 'BOOK') AND DATE(timestamp) = '$date'";
    $rev_row = $conn->query($rev_query)->fetch_assoc();

    $analytics_data[] = ['label' => $label, 'date' => $date, 'count' => (int)$row['count']];
    $revenue_data[] = ['label' => $label, 'date' => $date, 'amount' => (int)($rev_row['total'] ?? 0)];
}

// Get Hourly Distribution (Peak Hours) from actual vehicle entries.
$hourly_data = [];
for ($h = 0; $h < 24; $h++) {
    $h_label = sprintf("%02d:00", $h);
    $h_query = "SELECT COUNT(*) as count FROM parking_history WHERE action = 'IN' AND HOUR(timestamp) = $h";
    $h_row = $conn->query($h_query)->fetch_assoc();
    $hourly_data[] = ['label' => $h_label, 'count' => (int)$h_row['count']];
}

// Get Member Stats
$total_members = (int)($conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0);
$active_members = (int)($conn->query("SELECT COUNT(DISTINCT user_id) FROM parking_history WHERE action IN ('IN', 'BOOK') AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_row()[0] ?? 0);

// Get Top User
$top_user_query = "SELECT u.name, COUNT(h.id) as total 
                   FROM users u 
                   JOIN parking_history h ON u.id = h.user_id 
                   WHERE h.action IN ('IN', 'BOOK')
                   GROUP BY u.id 
                   ORDER BY total DESC LIMIT 1";
$top_res = $conn->query($top_user_query);
$top_user = $top_res->fetch_assoc() ?? ['name' => 'None', 'total' => 0];

// Get Peak Day
$peak_day_query = "SELECT DATE_FORMAT(timestamp, '%W') as day, COUNT(*) as total 
                   FROM parking_history 
                   WHERE action IN ('IN', 'BOOK')
                   GROUP BY day 
                   ORDER BY total DESC LIMIT 1";
$peak_res = $conn->query($peak_day_query);
$peak_day = $peak_res->fetch_assoc() ?? ['day' => 'None'];

// Get Occupancy Forecast (Dynamic)
$forecast = [
    'morning' => ['percent' => 20, 'status' => 'Low'],
    'lunch' => ['percent' => 40, 'status' => 'Medium'],
    'afternoon' => ['percent' => 30, 'status' => 'Low']
];

// Simple logic based on last 30 days
$morning_res = $conn->query("SELECT COUNT(*) FROM parking_history WHERE HOUR(timestamp) BETWEEN 8 AND 10 AND action='IN' AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$morning_count = (int)$morning_res->fetch_row()[0];
$lunch_res = $conn->query("SELECT COUNT(*) FROM parking_history WHERE HOUR(timestamp) BETWEEN 12 AND 14 AND action='IN' AND timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$lunch_count = (int)$lunch_res->fetch_row()[0];

if ($morning_count > 50) $forecast['morning'] = ['percent' => 85, 'status' => 'High'];
else if ($morning_count > 20) $forecast['morning'] = ['percent' => 50, 'status' => 'Medium'];

if ($lunch_count > 50) $forecast['lunch'] = ['percent' => 90, 'status' => 'High'];
else if ($lunch_count > 20) $forecast['lunch'] = ['percent' => 60, 'status' => 'Medium'];

// Get Grand Total Revenue from earned parking/booking fees only.
$grand_revenue_res = $conn->query("SELECT SUM(fee) FROM parking_history WHERE action IN ('OUT', 'BOOK')");
$grand_total_revenue = number_format((int)($grand_revenue_res->fetch_row()[0] ?? 0), 0, ',', '.');

echo json_encode([
    'traffic_chart' => $analytics_data,
    'revenue_chart' => $revenue_data,
    'hourly_chart' => $hourly_data,
    'member_stats' => [
        'total' => $total_members,
        'active' => $active_members
    ],
    'top_user' => $top_user,
    'peak_day' => $peak_day['day'],
    'grand_total_revenue' => $grand_total_revenue,
    'forecast' => $forecast,
    'generated_at' => date('Y-m-d H:i:s')
]);
?>
