<?php
require_once '../config/config.php';
require_once 'chat_bootstrap.php';

header('Content-Type: application/json');

$is_admin = isset($_GET['is_admin']) && $_GET['is_admin'] == 1;
$order_id = intval($_GET['order_id'] ?? 0);

if (!ensure_chats_table($conn)) {
    chat_json_error('Gagal menyiapkan tabel chat: ' . mysqli_error($conn), 500);
}

if ($is_admin) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        chat_json_error('Unauthorized', 401);
    }

    // Admin checks for total unread messages from any customer
    $result = secure_query($conn, "SELECT COUNT(*) as unread_count FROM chats WHERE sender_type = 'customer' AND is_read = 0 AND order_id IS NOT NULL", "", []);
}
else {
    if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true || !isset($_SESSION['customer_id'])) {
        chat_json_error('Unauthorized', 401);
    }

    // Customer checks for unread messages from admin
    $customer_id = intval($_SESSION['customer_id']);
    if ($order_id > 0) {
        $result = secure_query($conn, "SELECT COUNT(*) as unread_count FROM chats WHERE sender_type = 'admin' AND receiver_id = ? AND order_id = ? AND is_read = 0", "ii", [$customer_id, $order_id]);
    }
    else {
        $result = secure_query($conn, "SELECT COUNT(*) as unread_count FROM chats WHERE sender_type = 'admin' AND receiver_id = ? AND order_id IS NOT NULL AND is_read = 0", "i", [$customer_id]);
    }
}

$data = fetch_one($result);

echo json_encode(['success' => true, 'unread_count' => intval($data['unread_count'] ?? 0)]);
?>
