<?php
// /cb/api/get_chat_list.php
// API untuk mendapatkan daftar pelanggan yang pernah ngobrol dengan admin
require_once '../config/config.php';
require_once 'chat_bootstrap.php';
header('Content-Type: application/json');

// Pastikan yang mengakses adalah admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    chat_json_error('Unauthorized', 401);
}

if (!ensure_chats_table($conn)) {
    chat_json_error('Gagal menyiapkan tabel chat: ' . mysqli_error($conn), 500);
}

// Daftar room chat per pesanan
$query = "
    SELECT 
        o.id as order_id,
        o.order_code,
        o.status as order_status,
        c.id as customer_id,
        c.nama as nama_customer,
        (SELECT message FROM chats ch WHERE ch.order_id = o.id ORDER BY ch.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM chats ch WHERE ch.order_id = o.id ORDER BY ch.created_at DESC LIMIT 1) as last_time_raw,
        (SELECT COUNT(*) FROM chats ch WHERE ch.order_id = o.id AND ch.sender_type = 'customer' AND ch.is_read = 0) as unread_count
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    WHERE o.id IN (SELECT DISTINCT order_id FROM chats WHERE order_id IS NOT NULL)
    ORDER BY last_time_raw DESC, o.updated_at DESC
";

$result = secure_query($conn, $query, "", []);

if (!$result) {
    chat_json_error('Gagal mengambil daftar chat.', 500);
}

$customers = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format Waktu
    if ($row['last_time_raw']) {
        $time = strtotime($row['last_time_raw']);
        if (date('Y-m-d') == date('Y-m-d', $time)) {
            $row['last_time'] = date('H:i', $time); // Hari ini, tampilkan jam saja
        }
        else {
            $row['last_time'] = date('d/m/y', $time); // Kemarin atau sebelumnya, tampilkan tanggal
        }
    }
    else {
        $row['last_time'] = '';
    }

    $row['nama_customer'] = $row['nama_customer'] ?? 'Customer';
    $row['last_message'] = $row['last_message'] ?? '';
    $row['order_code'] = $row['order_code'] ?? '-';

    $status = strtolower((string) ($row['order_status'] ?? ''));
    $status_map = [
        'new' => 'Baru',
        'baru' => 'Baru',
        'process' => 'Diterima',
        'preparing' => 'Diracik',
        'ready' => 'Siap',
        'done' => 'Selesai',
        'selesai' => 'Selesai',
        'cancel' => 'Batal',
        'batal' => 'Batal'
    ];
    $row['order_status_label'] = $status_map[$status] ?? ucfirst($status);

    // Potong last_message jika terlalu panjang
    if ($row['last_message'] && strlen($row['last_message']) > 40) {
        $row['last_message'] = substr($row['last_message'], 0, 40) . '...';
    }

    $customers[] = $row;
}

echo json_encode(['success' => true, 'customers' => $customers]);
?>
