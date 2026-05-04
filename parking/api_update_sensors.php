<?php
// c:/xampp/htdocs/parking/api_update_sensors.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
include 'config.php';

$response = array("success" => false);

// Ensure slot rows exist (safe to call repeatedly).
$conn->query("INSERT IGNORE INTO sensor_status (slot_id, is_occupied, user_name, plate_number) VALUES (1, 0, '', ''), (2, 0, '', '')");

// GET: heartbeat only — updates updated_at to keep ONLINE status
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $conn->query("UPDATE sensor_status SET updated_at = NOW()");
    $response["success"] = true;
    $response["type"] = "heartbeat";
    $response["updated_at"] = date("Y-m-d H:i:s");
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $s1 = isset($_POST['s1']) ? (int)$_POST['s1'] : null;
    $s2 = isset($_POST['s2']) ? (int)$_POST['s2'] : null;
    $s1_name = trim((string)($_POST['s1_name'] ?? ''));
    $s1_plate = trim((string)($_POST['s1_plate'] ?? ''));
    $s2_name = trim((string)($_POST['s2_name'] ?? ''));
    $s2_plate = trim((string)($_POST['s2_plate'] ?? ''));

    if ($s1 !== null)
        $s1 = $s1 === 1 ? 1 : 0;
    if ($s2 !== null)
        $s2 = $s2 === 1 ? 1 : 0;

    // Empty slot must not keep stale identity.
    if ($s1 === 0) {
        $s1_name = '';
        $s1_plate = '';
    }
    if ($s2 === 0) {
        $s2_name = '';
        $s2_plate = '';
    }

    // If physically occupied but no name, use UNKNOWN placeholder
    if ($s1 === 1 && $s1_name === '') $s1_name = 'UNKNOWN';
    if ($s2 === 1 && $s2_name === '') $s2_name = 'UNKNOWN';

    // Function to sync status column with is_occupied
    function getNewStatus($slot_id, $isOccupied, &$sensor_name, &$sensor_plate, $conn) {
        $curr_res = $conn->query("SELECT status, booked_user_id, user_name, plate_number FROM sensor_status WHERE slot_id = $slot_id");
        $curr = $curr_res->fetch_assoc();
        $status = (int)($curr['status'] ?? 0);
        $booked_uid = (int)($curr['booked_user_id'] ?? 0);
        $db_name = $curr['user_name'] ?? '';
        $db_plate = $curr['plate_number'] ?? '';

        if ($isOccupied == 0) {
            // Physically empty: Reset status if it was occupied (1) or violated (4)
            if ($status == 1) {
                // If it was occupied by the booker, the booking is fulfilled. So clear it.
                $conn->query("UPDATE sensor_status SET user_name = '', plate_number = '', booked_user_id = NULL, booking_expires_at = NULL WHERE slot_id = $slot_id");
                return 0;
            } else if ($status == 4) {
                // If it was a violation on a booked/assigned slot, restore it to reserved (2) instead of 0
                // We don't wipe the booked_user_id because the real owner still needs it
                $conn->query("UPDATE sensor_status SET user_name = '', plate_number = '' WHERE slot_id = $slot_id");
                return 2; // Assume it reverts to booked
            }
            return $status; // Keep 2 or 3
        } else {
            // Physically Occupied (1)
            if ($status == 1) return 1; // Already correctly occupied
            
            // Check who is in transit and assigned to THIS slot, OR assigned to ANY slot (0)
            $transit_res = $conn->query("SELECT user_id, assigned_slot_id FROM transit_users WHERE assigned_slot_id = $slot_id OR assigned_slot_id = 0 ORDER BY timestamp DESC LIMIT 1");
            $transit = $transit_res->fetch_assoc();
            
            if ($transit) {
                // Correct user arrived!
                $user_id = $transit['user_id'];
                $u_res = $conn->query("SELECT name, plate_number FROM users WHERE id = $user_id")->fetch_assoc();
                $sensor_name = $u_res['name'] ?? 'Unknown';
                $sensor_plate = $u_res['plate_number'] ?? '-';
                $conn->query("UPDATE sensor_status SET booked_user_id = $user_id WHERE slot_id = $slot_id");
                $conn->query("DELETE FROM transit_users WHERE user_id = $user_id");
                return 1;
            } else {
                // No one in transit is assigned to this slot.
                // If it was reserved (2) or assigned (3) to someone else, it's THEFT.
                if ($status == 2 || $status == 3) {
                    // Find the likely thief (Most recent entry who isn't yet parked)
                    $any_transit_res = $conn->query("SELECT t.user_id, t.assigned_slot_id, u.name 
                                                     FROM transit_users t 
                                                     JOIN users u ON t.user_id = u.id 
                                                     ORDER BY t.timestamp DESC LIMIT 1");
                    $any_transit = $any_transit_res->fetch_assoc();
                    
                    if ($any_transit) {
                        $user_id = $any_transit['user_id'];
                        $name = $any_transit['name'];
                        $assigned = $any_transit['assigned_slot_id'];
                        
                        $msg = "Akses terdeteksi tidak sah! Pengemudi " . $name . ", Anda baru saja mengambil slot nomor " . $slot_id . " yang telah dibooking orang lain. Mohon segera pindahkan kendaraan Anda ke slot nomor " . $assigned . "!";
                        $conn->query("INSERT INTO gate_events (user_id, message, status) VALUES ($user_id, '$msg', 'THEFT')");
                        
                        // Remove thief from transit so they don't block their originally assigned slot permanently
                        $conn->query("DELETE FROM transit_users WHERE user_id = $user_id");
                    } else {
                        // Someone already inside or unknown stole it
                        $msg = "Perhatian! Slot nomor " . $slot_id . " baru saja ditempati oleh kendaraan yang tidak memiliki hak booking. Mohon petugas segera memeriksa!";
                        $conn->query("INSERT INTO gate_events (message, status) VALUES ('$msg', 'THEFT')");
                    }
                    return 4; // Status 4 = VIOLATION / THEFT
                }
                
                // If status was 1, keep db_name
                if ($status == 1 && $sensor_name === 'UNKNOWN') {
                    $sensor_name = $db_name;
                    $sensor_plate = $db_plate;
                }
                return 1; // Walk-in or unknown, mark as occupied
            }
        }
    }

    if ($s1 !== null) {
        $newStatus = getNewStatus(1, $s1, $s1_name, $s1_plate, $conn);
        $stmt = $conn->prepare("UPDATE sensor_status SET is_occupied = ?, status = ?, user_name = ?, plate_number = ?, updated_at = NOW() WHERE slot_id = 1");
        if ($stmt) {
            $stmt->bind_param("iiss", $s1, $newStatus, $s1_name, $s1_plate);
            $stmt->execute();
            $stmt->close();
        }
    }
    if ($s2 !== null) {
        $newStatus = getNewStatus(2, $s2, $s2_name, $s2_plate, $conn);
        $stmt = $conn->prepare("UPDATE sensor_status SET is_occupied = ?, status = ?, user_name = ?, plate_number = ?, updated_at = NOW() WHERE slot_id = 2");
        if ($stmt) {
            $stmt->bind_param("iiss", $s2, $newStatus, $s2_name, $s2_plate);
            $stmt->execute();
            $stmt->close();
        }
    }

    $response["success"] = true;
    $response["updated_at"] = date("Y-m-d H:i:s");
    
    // FETCH LATEST STATUS TO SYNC BACK TO ARDUINO
    $all_slots = $conn->query("SELECT slot_id, status, user_name, plate_number FROM sensor_status ORDER BY slot_id ASC");
    $slots_data = [];
    while($as = $all_slots->fetch_assoc()) {
        $slots_data[] = [
            "id" => (int)$as['slot_id'],
            "status" => (int)$as['status'],
            "name" => $as['user_name'],
            "plate" => $as['plate_number']
        ];
    }
    $response["slots"] = $slots_data;
}
else {
    $response["message"] = "POST only";
}

echo json_encode($response);
?>
