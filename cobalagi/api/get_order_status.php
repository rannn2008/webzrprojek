<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$order_code = $_GET['order_code'] ?? '';

if (empty($order_code)) {
    echo json_encode(['success' => false, 'message' => 'Order code is required']);
    exit;
}

$sql = "SELECT status, alasan FROM orders WHERE order_code = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $order_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
} else {
    echo json_encode(['success' => true, 'status' => $order['status'], 'reason' => $order['alasan']]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
