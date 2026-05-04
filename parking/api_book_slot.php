<?php
// c:/xampp/htdocs/parking/api_book_slot.php
include 'config.php';
include 'auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["success" => false, "message" => "POST only"]);
    exit;
}

// 1. Auth check
if (!isset($_SESSION['client_id'])) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$client_id = $_SESSION['client_id'];
$slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;

if ($slot_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid slot"]);
    exit;
}

// 2. Load settings
$booking_fee = (int)getSetting($conn, 'booking_fee', '5000');
$duration = (int)getSetting($conn, 'booking_duration_minutes', '15');

try {
    $conn->begin_transaction();

    // 3. User checks (Balance & Status)
    $u_res = $conn->query("SELECT balance, name FROM users WHERE id = $client_id FOR UPDATE");
    $user = $u_res->fetch_assoc();

    if ($user['balance'] < $booking_fee) {
        throw new Exception("Insufficient balance. Need Rp " . number_format($booking_fee, 0, ',', '.'));
    }

    // Check if already has active booking
    $active_b = $conn->query("SELECT slot_id FROM sensor_status WHERE booked_user_id = $client_id AND status = 2");
    if ($active_b->num_rows > 0) {
        throw new Exception("You already have an active reservation.");
    }

    // Check if already parked
    $last_log = $conn->query("SELECT action FROM parking_history WHERE user_id = $client_id ORDER BY id DESC LIMIT 1");
    if ($last_log && $row = $last_log->fetch_assoc()) {
        if ($row['action'] == 'IN') {
             throw new Exception("You are already inside the parking area.");
        }
    }

    // 4. Slot check
    $s_res = $conn->query("SELECT status FROM sensor_status WHERE slot_id = $slot_id FOR UPDATE");
    $slot = $s_res->fetch_assoc();

    if (!$slot) throw new Exception("Slot not found.");
    if ($slot['status'] != 0) {
        throw new Exception("This slot is no longer available.");
    }

    // 5. Commit Booking
    $expiry = date("Y-m-d H:i:s", strtotime("+$duration minutes"));
    $conn->query("UPDATE sensor_status SET 
                    status = 2, 
                    booked_user_id = $client_id, 
                    booking_expires_at = '$expiry',
                    booked_at = NOW(),
                    user_name = '" . $conn->real_escape_string($user['name']) . "' 
                  WHERE slot_id = $slot_id");
    
    $conn->query("UPDATE users SET balance = balance - $booking_fee, points = points + 20 WHERE id = $client_id");

    // RECORD IN HISTORY
    $conn->query("INSERT INTO parking_history (user_id, action, fee, slot_id) VALUES ($client_id, 'BOOK', $booking_fee, $slot_id)");

    $conn->commit();
    echo json_encode([
        "success" => true, 
        "message" => "Booking successful! Slot $slot_id reserved for $duration mins.",
        "expiry" => $expiry
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
