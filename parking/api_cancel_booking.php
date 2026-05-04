<?php
// c:/xampp/htdocs/parking/api_cancel_booking.php
include 'config.php';
include 'auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["success" => false, "message" => "POST only"]);
    exit;
}

// 1. Admin Auth Check
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;

if ($client_id <= 0 || $slot_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
    exit;
}

try {
    $conn->begin_transaction();

    // 2. Check if slot is actually booked by this client
    $s_res = $conn->query("SELECT status, booked_user_id, booked_at FROM sensor_status WHERE slot_id = $slot_id FOR UPDATE");
    $slot = $s_res->fetch_assoc();

    if (!$slot || $slot['status'] != 2 || $slot['booked_user_id'] != $client_id) {
        throw new Exception("Reservation not found or already changed.");
    }

    // 3. Time limit check (5 minutes)
    $booked_at = strtotime($slot['booked_at']);
    if (time() - $booked_at > 300) { // 300 seconds = 5 minutes
        throw new Exception("Cancellation period has ended (max 5 minutes).");
    }

    // 4. Refund logic
    $booking_fee = (int)getSetting($conn, 'booking_fee', '5000');
    $conn->query("UPDATE users SET balance = balance + $booking_fee WHERE id = $client_id");

    // 5. Reset slot
    $conn->query("UPDATE sensor_status SET 
                    status = 0, 
                    booked_user_id = NULL, 
                    booking_expires_at = NULL, 
                    booked_at = NULL,
                    user_name = NULL 
                  WHERE slot_id = $slot_id");

    // 6. Record in History
    $conn->query("INSERT INTO parking_history (user_id, action, fee, slot_id) VALUES ($client_id, 'CANCEL', $booking_fee, $slot_id)");

    // 7. Send confirmation chat message automatically
    $msg = "[CANCEL_CONFIRMED:$slot_id] Booking Bay $slot_id telah dibatalkan. Saldo Rp " . number_format($booking_fee, 0, ',', '.') . " telah dikembalikan.";
    $admin_id = $_SESSION['admin_id'];
    $admin_name = $_SESSION['admin_name'];
    
    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, sender_type, sender_name, client_id, message) VALUES (?, 'admin', ?, ?, ?)");
    $stmt->bind_param("isis", $admin_id, $admin_name, $client_id, $msg);
    $stmt->execute();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Booking successfully cancelled and space is now free."]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
