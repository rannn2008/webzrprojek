<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true || !isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$customerId = (int) $_SESSION['customer_id'];
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
if ($limit <= 0) {
    $limit = 20;
}
$limit = min($limit, 100);

$sql = "SELECT id, order_code, status, created_at, updated_at
        FROM orders
        WHERE customer_id = ?
          AND (status IN ('new','baru','process','cancel') OR status IS NULL OR status = '')
        ORDER BY updated_at DESC
        LIMIT $limit";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyiapkan query'
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $customerId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = [
        'id' => (int) $row['id'],
        'order_code' => (string) ($row['order_code'] ?? ''),
        'status' => strtolower((string) ($row['status'] ?? 'new')),
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    'success' => true,
    'server_time' => date('Y-m-d H:i:s'),
    'orders' => $orders
]);
?>
