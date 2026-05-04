<?php
require_once '../config/config.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Access denied. Please login first.']));
}

// Set header JSON
header('Content-Type: application/json');

// Proses update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = clean_input($_POST['order_id']);
    $status = clean_input($_POST['status']);
    $admin = $_SESSION['admin_username'] ?? 'system';
    
    // Validasi status
    $allowed_statuses = ['baru', 'diproses', 'selesai', 'dibatalkan'];
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid!']);
        exit();
    }
    
    // Get order info before update
    $order = fetch_one(secure_query($conn, "SELECT kode_pesanan, status as old_status FROM orders WHERE id = ?", "i", [$order_id]));
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan!']);
        exit();
    }
    
    $old_status = $order['old_status'];
    
    // Update status di database
    if (secure_query($conn, "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", "si", [$status, $order_id], false)) {
        // Log activity
        $action = "Update Order Status";
        $details = "Order #{$order['kode_pesanan']} status changed from '{$old_status}' to '{$status}'";
        secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES (?, ?, ?)", "sss", [$admin, $action, $details], false);
        
        // Send WhatsApp notification if status changed to diproses or selesai
        if (in_array($status, ['diproses', 'selesai'])) {
            sendWhatsAppNotification($order_id, $status);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Status berhasil diupdate menjadi '$status'!",
            'new_status' => $status,
            'order_id' => $order_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method!']);
}

// Function to send WhatsApp notification
function sendWhatsAppNotification($order_id, $status) {
    global $conn;
    
    // Get order details
    $order = fetch_one(secure_query($conn, "SELECT nama_customer, whatsapp, kode_pesanan FROM orders WHERE id = ?", "i", [$order_id]));
    
    if ($order) {
        $customer_name = $order['nama_customer'];
        $whatsapp = $order['whatsapp'];
        $order_code = $order['kode_pesanan'];
        
        $status_text = [
            'diproses' => 'sedang diproses',
            'selesai' => 'telah selesai'
        ][$status];
        
        $message = "Halo $customer_name! 👋\n\n";
        $message .= "Pesanan Anda dengan kode *#$order_code* $status_text.\n\n";
        
        if ($status === 'diproses') {
            $message .= "Pesanan Anda sedang kami siapkan dengan penuh cinta ❤️\n";
            $message .= "Estimasi waktu penyajian: 15-30 menit.\n\n";
        } else {
            $message .= "Pesanan Anda siap diambil/diantar! 🎉\n";
            $message .= "Terima kasih telah memilih Pondok Es Teller ZR! 🙏\n\n";
        }
        
        $message .= "_Pesan ini dikirim otomatis dari sistem Pondok Es Teller ZR_";
        
        // Encode message for URL
        $encoded_message = urlencode($message);
        
        // Return WhatsApp link (in real implementation, you would send via API)
        $whatsapp_link = "https://wa.me/$whatsapp?text=$encoded_message";
        
        // Log notification attempt
        $log_details = "Notification sent to $whatsapp for order #$order_code";
        secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES (?, ?, ?)", "sss", ['system', 'WhatsApp Notification', $log_details], false);
        
        return $whatsapp_link;
    }
    
    return false;
}

mysqli_close($conn);
?>
