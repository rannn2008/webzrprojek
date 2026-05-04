<?php
require_once '../config/config.php';
require_once 'chat_bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chat_json_error('Metode request tidak valid', 405);
}

$sender_type = $_POST['sender_type'] ?? '';
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$order_id = intval($_POST['order_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!ensure_chats_table($conn)) {
    chat_json_error('Gagal menyiapkan tabel chat: ' . mysqli_error($conn), 500);
}

if (empty($message) || empty($sender_type)) {
    chat_json_error('Data tidak lengkap');
}
if ($order_id <= 0) {
    chat_json_error('Order ID diperlukan');
}

if (!in_array($sender_type, ['customer', 'admin'], true)) {
    chat_json_error('sender_type tidak valid');
}

$order = fetch_order_chat_context($conn, $order_id);
if (!$order) {
    chat_json_error('Pesanan tidak ditemukan', 404);
}

$order_customer_id = intval($order['customer_id'] ?? 0);
if ($order_customer_id <= 0) {
    chat_json_error('Pesanan belum terhubung ke akun customer');
}

if ($sender_type === 'customer') {
    if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true || !isset($_SESSION['customer_id'])) {
        chat_json_error('Unauthorized', 401);
    }

    $sender_id = intval($_SESSION['customer_id']);
    if ($sender_id !== $order_customer_id) {
        chat_json_error('Anda tidak punya akses ke chat pesanan ini', 403);
    }
    if (!is_order_chat_send_allowed($order['status'])) {
        chat_json_error('Chat hanya bisa dikirim saat pesanan sedang diproses');
    }

    $receiver_id = 0; // Chat customer selalu ke admin
}
else {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        chat_json_error('Unauthorized', 401);
    }

    $sender_id = 0; // Admin tidak menggunakan ID spesifik di skema saat ini
    if (!is_order_chat_send_allowed($order['status'])) {
        chat_json_error('Pesanan sudah selesai, chat tidak bisa dikirim lagi');
    }

    $receiver_id = $order_customer_id;
}

if (secure_query($conn, "INSERT INTO chats (order_id, sender_type, sender_id, receiver_id, message) VALUES (?, ?, ?, ?, ?)", "isiis", [$order_id, $sender_type, $sender_id, $receiver_id, $message], false)) {
    echo json_encode([
        'success' => true,
        'chat_id' => intval(mysqli_insert_id($conn)),
        'order_id' => $order_id
    ]);
}
else {
    chat_json_error('Gagal menyimpan pesan.', 500);
}
?>
