<?php
require_once '../config/config.php';

// Set header for JSON response
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = $_POST['status'] ?? '';
    
    // Validasi input
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit();
    }
    
    $valid_statuses = ['new', 'baru', 'process', 'proses', 'done', 'selesai', 'cancel', 'batal'];
    if (!in_array(strtolower($status), $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    // Update status
    if (secure_query($conn, "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", "si", [$status, $order_id], false)) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

mysqli_close($conn);
?>
