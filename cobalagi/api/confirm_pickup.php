<?php
require_once '../config/config.php';
require_once 'order_receipt_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metode request tidak valid']);
    exit;
}

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true || !isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$customer_id = intval($_SESSION['customer_id']);
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Order ID tidak valid']);
    exit;
}

$q_order = secure_query($conn, "SELECT id, order_code, status, total_harga, metode_pengiriman FROM orders WHERE id = ? AND customer_id = ? LIMIT 1", "ii", [$order_id, $customer_id]);
$order = fetch_one($q_order);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
    exit;
}
$status = strtolower((string) ($order['status'] ?? ''));
$metode_pengiriman = strtolower((string) ($order['metode_pengiriman'] ?? 'pickup'));

if ($metode_pengiriman !== 'pickup') {
    echo json_encode(['success' => false, 'error' => 'Konfirmasi ambil hanya untuk pesanan jemput sendiri']);
    exit;
}

if ($status === 'done' || $status === 'selesai') {
    $receipt = ensure_order_receipt($conn, $order_id, 'customer', true);
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan sudah selesai sebelumnya',
        'receipt_code' => $receipt['receipt_code'] ?? null
    ]);
    exit;
}

if ($status !== 'ready' && $status !== 'siap') {
    echo json_encode(['success' => false, 'error' => 'Pesanan belum siap diambil']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    if (!secure_query($conn, "UPDATE orders SET status = 'done', updated_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$order_id], false)) {
        throw new Exception("Gagal update status pesanan.");
    }

    $points = floor((intval($order['total_harga']) ?: 0) / 10000);
    if ($points > 0) {
        if (!secure_query($conn, "UPDATE customers SET points = points + ? WHERE id = ?", "ii", [$points, $customer_id], false)) {
            throw new Exception("Gagal update poin customer.");
        }
    }

    $receipt = ensure_order_receipt($conn, $order_id, 'customer', true);
    if (!$receipt['success']) {
        throw new Exception($receipt['error']);
    }

    secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES (?, 'Pickup Confirmed', ?)", "ss", ["customer_$customer_id", "Pesanan {$order['order_code']} dikonfirmasi sudah diambil customer"], false);

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dikonfirmasi sudah diambil',
        'receipt_code' => $receipt['receipt_code']
    ]);
}
catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Gagal konfirmasi pickup: ' . $e->getMessage()]);
}

