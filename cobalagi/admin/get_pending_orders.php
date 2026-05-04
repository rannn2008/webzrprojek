<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if ($limit <= 0) {
    $limit = 20;
}
$limit = min($limit, 100);

$orders_result = secure_query($conn, "SELECT id, order_code, nama_customer, total_harga, status, created_at,
        TIMESTAMPDIFF(SECOND, created_at, NOW()) AS waiting_seconds
        FROM orders
        WHERE status IN ('new','baru','') OR status IS NULL
        ORDER BY created_at ASC
        LIMIT ?", "i", [$limit]);

if (!$orders_result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data pesanan.'
    ]);
    exit();
}

$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = [
        'id' => (int) $row['id'],
        'order_code' => $row['order_code'],
        'nama_customer' => $row['nama_customer'],
        'total_harga' => (int) $row['total_harga'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'waiting_seconds' => max(0, (int) $row['waiting_seconds'])
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($orders),
    'server_time' => date('Y-m-d H:i:s'),
    'orders' => $orders
]);

mysqli_close($conn);
?>
