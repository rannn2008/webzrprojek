<?php
require_once '../config/config.php';
require_once 'chat_bootstrap.php';

header('Content-Type: application/json');

$customer_id = intval($_GET['customer_id'] ?? 0);
$order_id = intval($_GET['order_id'] ?? 0);
$is_admin = isset($_GET['is_admin']) && $_GET['is_admin'] == 1;

if (!ensure_chats_table($conn)) {
    chat_json_error('Gagal menyiapkan tabel chat: ' . mysqli_error($conn), 500);
}
if ($order_id <= 0) {
    chat_json_error('Order ID diperlukan');
}

$order = fetch_order_chat_context($conn, $order_id);
if (!$order) {
    chat_json_error('Pesanan tidak ditemukan', 404);
}
$order_customer_id = intval($order['customer_id'] ?? 0);
if ($order_customer_id <= 0) {
    chat_json_error('Pesanan belum terhubung ke akun customer');
}

if ($is_admin) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        chat_json_error('Unauthorized', 401);
    }

    $customer_id = $order_customer_id;
}
else {
    if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true || !isset($_SESSION['customer_id'])) {
        chat_json_error('Unauthorized', 401);
    }

    // Paksa customer hanya bisa membaca chat miliknya sendiri.
    $customer_id = intval($_SESSION['customer_id']);
    if ($customer_id !== $order_customer_id) {
        chat_json_error('Anda tidak punya akses ke chat pesanan ini', 403);
    }
    if (!is_order_chat_allowed_for_customer($order['status'])) {
        chat_json_error('Chat untuk pesanan ini belum tersedia', 403);
    }
}

// Fetch all chats involving this customer
$sql = "SELECT c.*, 
        CASE WHEN c.sender_type = 'customer' THEN cu.nama ELSE 'Admin' END as sender_name 
        FROM chats c
        LEFT JOIN customers cu ON c.sender_id = cu.id
        WHERE c.order_id = ?
          AND ((c.sender_type = 'customer' AND c.sender_id = ?)
           OR (c.sender_type = 'admin' AND c.receiver_id = ?))
        ORDER BY c.created_at ASC";

$result = secure_query($conn, $sql, "iii", [$order_id, $customer_id, $customer_id]);
if (!$result) {
    chat_json_error('Gagal mengambil data chat.', 500);
}

$chats = [];

$unread_to_update = [];

while ($row = mysqli_fetch_assoc($result)) {
    $chats[] = [
        'id' => $row['id'],
        'sender_type' => $row['sender_type'],
        'sender_name' => $row['sender_name'],
        'message' => htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'),
        'created_at' => date('H:i', strtotime($row['created_at'])),
        'is_read' => $row['is_read']
    ];

    // Mark as read if the current viewer is the receiver and it's unread
    if ($row['is_read'] == 0) {
        if ($is_admin && $row['sender_type'] == 'customer') {
            $unread_to_update[] = $row['id'];
        }
        elseif (!$is_admin && $row['sender_type'] == 'admin') {
            $unread_to_update[] = $row['id'];
        }
    }
}

// Update read status
if (!empty($unread_to_update)) {
    // For IN clause with integers, we can safely build the string after sanitizing each ID
    $sanitized_ids = array_map('intval', $unread_to_update);
    $ids = implode(',', $sanitized_ids);
    secure_query($conn, "UPDATE chats SET is_read = 1 WHERE id IN ($ids)", "", [], false);
}

$can_send = is_order_chat_send_allowed($order['status']);
echo json_encode([
    'success' => true,
    'chats' => $chats,
    'order' => [
        'id' => intval($order['id']),
        'order_code' => $order['order_code'],
        'status' => $order['status'],
        'can_send' => $can_send
    ]
]);
?>
