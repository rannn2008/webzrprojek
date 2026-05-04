<?php
// c:/xampp/htdocs/parking/api_check_access.php
// ENTRY ONLY — RFID is for entering the parking. Exit is via exit sensor.
include "config.php";
header("Content-Type: application/json; charset=utf-8");

$response = array("access" => false, "message" => "Scan Failed");

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["uid"])) {
        // 0. Cleanup Expired Bookings (status 2) and Stale Assignments (status 3)
        $conn->query("UPDATE sensor_status SET status = 0, booked_user_id = NULL, booking_expires_at = NULL WHERE status = 2 AND booking_expires_at < NOW()");
        // Cleanup assignments (status 3) older than 5 minutes
        $conn->query("UPDATE sensor_status SET status = 0, booked_user_id = NULL WHERE status = 3 AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

        $uid = strtoupper(trim(str_replace(' ', '', $_POST["uid"])));
        if (empty($uid)) throw new Exception("UID cannot be empty");
        
        file_put_contents("last_scan.txt", $uid);

        // 1. Check if User Exists
        $stmt = $conn->prepare("SELECT id, name, plate_number, balance FROM users WHERE rfid_uid = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $user_id = $user["id"];
            $user_name = $user["name"];

            // 1.5. Check Booking Status for THIS user
            $booking_res = $conn->query("SELECT slot_id FROM sensor_status WHERE booked_user_id = $user_id AND status = 2");
            $has_booking = $booking_res->num_rows > 0;
            $booked_slot = $has_booking ? $booking_res->fetch_assoc()['slot_id'] : null;

            // 1.6. Optimistic Capacity Check
            // As per user request: Gate should open as long as there is PHYSICAL space (is_occupied = 0)
            // Even if some slots are Reserved, we allow entry and let the AI handle slot enforcement.
            $physical_free_res = $conn->query("SELECT slot_id, status FROM sensor_status WHERE is_occupied = 0");
            $physical_free_slots = [];
            $truly_free_slots = []; // Status 0
            while($fs = $physical_free_res->fetch_assoc()) {
                $physical_free_slots[] = $fs['slot_id'];
                if ($fs['status'] == 0) $truly_free_slots[] = $fs['slot_id'];
            }
            
            if (!$has_booking && empty($physical_free_slots)) {
                $msg = "Akses ditolak. Parkir benar-benar penuh.";
                $conn->query("INSERT INTO gate_events (user_id, message, status) VALUES ($user_id, '$msg', 'DENIED')");
                echo json_encode(["access" => false, "message" => "PARKIR PENUH", "name" => $user_name]);
                exit;
            }

            // 2. Check if user is already INSIDE
            $last_log = $conn->query("SELECT action FROM parking_history WHERE user_id = $user_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
            if (($last_log['action'] ?? '') == "IN") {
                $stuck_check = $conn->query("SELECT TIMESTAMPDIFF(MINUTE, timestamp, NOW()) FROM parking_history WHERE user_id = $user_id AND action = 'IN' ORDER BY id DESC LIMIT 1")->fetch_row();
                $stuck_min = (int)($stuck_check[0] ?? 0);
                
                if ($stuck_min > 5) {
                    $conn->query("INSERT INTO parking_history (user_id, action, fee) VALUES ($user_id, 'OUT', 0)");
                } else {
                    $msg = "Akses ditolak. Kendaraan Anda terdeteksi masih di dalam.";
                    $conn->query("INSERT INTO gate_events (user_id, message, status) VALUES ($user_id, '$msg', 'DENIED')");
                    echo json_encode(["access" => false, "message" => "SUDAH DI DALAM", "name" => $user_name]);
                    exit;
                }
            }

            // 3. Grant Access
            $conn->query("INSERT INTO parking_history (user_id, action, fee) VALUES ($user_id, 'IN', 0)");
            $conn->query("UPDATE users SET points = points + 50 WHERE id = $user_id");

            if ($has_booking) {
                $target_slot = $booked_slot;
                $msg = "Selamat datang " . $user_name . ". Silakan gunakan slot nomor " . $target_slot . " yang telah Anda booking.";
                // REGISTER TRANSIT FOR SPECIFIC SLOT
                $conn->query("REPLACE INTO transit_users (user_id, assigned_slot_id) VALUES ($user_id, $target_slot)");
            } else {
                $target_slot = 0; // 0 means "Any free slot"
                $msg = "Selamat datang " . $user_name . ". Silakan parkir di slot mana saja yang tersedia.";
                if (empty($truly_free_slots)) {
                    $msg .= " Mohon diperhatikan, slot mungkin sangat terbatas.";
                }
                // REGISTER TRANSIT FOR ANY SLOT
                $conn->query("REPLACE INTO transit_users (user_id, assigned_slot_id) VALUES ($user_id, 0)");
            }

            $conn->query("INSERT INTO gate_events (user_id, message, status) VALUES ($user_id, '$msg', 'GRANTED')");
            echo json_encode([
                "access" => true, 
                "name" => $user_name, 
                "plate" => $user["plate_number"],
                "message" => "WELCOME",
                "target_slot" => $target_slot
            ]);
        } else {
            $msg = "Akses ditolak. Kartu RFID tidak terdaftar.";
            $conn->query("INSERT INTO gate_events (message, status) VALUES ('$msg', 'DENIED')");
            echo json_encode(["access" => false, "message" => "BELUM TERDAFTAR"]);
        }
    } else {
        echo json_encode(["access" => false, "message" => "Invalid Request"]);
    }
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
    $response["error"] = true;
    echo json_encode($response);
}
?>
