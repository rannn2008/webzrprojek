<?php
require_once '../config/config.php';
require_once '../includes/db_helper.php';
require_once '../api/order_receipt_helper.php';

ensure_order_receipts_table($conn);

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID pesanan tidak ditemukan']);
    exit();
}

$order_id = intval($_GET['id']);

// Get order info
$order = fetch_one(secure_query($conn, "SELECT o.*, c.foto_profil, r.receipt_code, r.generated_by as receipt_generated_by, r.generated_at as receipt_generated_at, r.pickup_confirmed_at
              FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.id 
              LEFT JOIN order_receipts r ON r.order_id = o.id
              WHERE o.id = ?", "i", [$order_id]));

if (!$order) {
    echo json_encode(['error' => 'Pesanan tidak ditemukan']);
    exit();
}

$status = strtolower((string) ($order['status'] ?? ''));
if (in_array($status, ['done', 'selesai'], true) && empty($order['receipt_code'])) {
    $receipt = ensure_order_receipt($conn, $order_id, 'system', false);
    if ($receipt['success']) {
        // Re-fetch order info to get new receipt data
        $order = fetch_one(secure_query($conn, "SELECT o.*, c.foto_profil, r.receipt_code, r.generated_by as receipt_generated_by, r.generated_at as receipt_generated_at, r.pickup_confirmed_at
                      FROM orders o 
                      LEFT JOIN customers c ON o.customer_id = c.id 
                      LEFT JOIN order_receipts r ON r.order_id = o.id
                      WHERE o.id = ?", "i", [$order_id]));
    }
}

// Get order items
$items_result = secure_query($conn, "SELECT * FROM order_items WHERE order_id = ?", "i", [$order_id]);
$items = [];
if ($items_result) {
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
}

// Format WhatsApp number
if (!empty($order['whatsapp'])) {
    $wa = preg_replace('/[^0-9]/', '', $order['whatsapp']);
    if (isset($wa[0]) && $wa[0] === '0') {
        $wa = '62' . substr($wa, 1);
    }
    $order['whatsapp'] = $wa;
}

echo json_encode([
    'order' => $order,
    'items' => $items
]);

mysqli_close($conn);
?>
