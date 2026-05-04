<?php
// c:/xampp/htdocs/parking/api_gate.php
// Admin: POST command=OPEN|CLOSE to send gate command
// Arduino: GET ?poll=1 to check for pending commands
include "config.php";
header("Content-Type: application/json");

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS gate_commands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    command ENUM('OPEN','CLOSE','REBOOT') NOT NULL,
    status ENUM('PENDING','DONE') DEFAULT 'PENDING',
    created_by VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("ALTER TABLE gate_commands MODIFY COLUMN command ENUM('OPEN','CLOSE','REBOOT') NOT NULL");

// Arduino polls for pending commands
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["poll"])) {
    $res = $conn->query("SELECT id, command FROM gate_commands WHERE status='PENDING' ORDER BY id ASC LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        // Mark as done
        $conn->query("UPDATE gate_commands SET status='DONE' WHERE id=" . $row["id"]);
        echo json_encode(["command" => $row["command"], "id" => (int) $row["id"]]);
    } else {
        echo json_encode(["command" => "NONE"]);
    }
    exit;
}

// Admin sends gate command
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["command"])) {
    $cmd = strtoupper(trim($_POST["command"]));
    $client_id = isset($_POST["client_id"]) ? (int)$_POST["client_id"] : 0;
    
    if ($cmd == "OPEN" || $cmd == "CLOSE" || $cmd == "REBOOT") {
        
        // If reboot command is sent, we also reset the local database slots so UI instantly clears
        if ($cmd == "REBOOT") {
            $conn->query("UPDATE parking_slots SET is_occupied = 0, plate_number = NULL, user_name = NULL");
        }

        // SMART OVERRIDE: If opening for a specific client, automatically register them as IN!
        if ($cmd == "OPEN" && $client_id > 0) {
            // Check if user is already IN to prevent double entry
            $last_log = $conn->query("SELECT action FROM parking_history WHERE user_id = $client_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
            if (($last_log['action'] ?? '') != "IN") {
                // Register IN
                $conn->query("INSERT INTO parking_history (user_id, action, fee) VALUES ($client_id, 'IN', 0)");
                
                // Get user info to write welcome message and assign slot
                $u_res = $conn->query("SELECT name FROM users WHERE id = $client_id");
                if ($u_res && $user = $u_res->fetch_assoc()) {
                    $name = $user['name'];
                    // Find a free slot or their booked slot
                    $booking_res = $conn->query("SELECT slot_id FROM sensor_status WHERE booked_user_id = $client_id AND status = 2");
                    if ($booking_res->num_rows > 0) {
                        $target_slot = $booking_res->fetch_assoc()['slot_id'];
                        $conn->query("INSERT INTO gate_events (user_id, message, status) VALUES ($client_id, 'Akses Manual Diberikan. Selamat datang $name, silakan menuju slot $target_slot yang telah Anda booking.', 'GRANTED')");
                        $conn->query("REPLACE INTO transit_users (user_id, assigned_slot_id) VALUES ($client_id, $target_slot)");
                    } else {
                        // User has no booking. Allow them to pick any free slot.
                        $conn->query("INSERT INTO gate_events (user_id, message, status) VALUES ($client_id, 'Akses Manual Diberikan. Selamat datang $name, silakan parkir di slot mana saja yang tersedia.', 'GRANTED')");
                        $conn->query("REPLACE INTO transit_users (user_id, assigned_slot_id) VALUES ($client_id, 0)");
                    }
                }
            }
        }

        // Clear old pending commands
        $conn->query("UPDATE gate_commands SET status='DONE' WHERE status='PENDING'");
        // Insert new
        $stmt = $conn->prepare("INSERT INTO gate_commands (command, created_by) VALUES (?, ?)");
        $by = "admin";
        $stmt->bind_param("ss", $cmd, $by);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Gate command: $cmd" . ($client_id > 0 ? " (Auto Check-In Applied)" : "")]);
        } else {
            echo json_encode(["success" => false, "message" => "DB error"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid command"]);
    }
    exit;
}

// GET gate status (latest command)
$latest = $conn->query("SELECT command, created_at FROM gate_commands ORDER BY id DESC LIMIT 1");
$row = $latest ? $latest->fetch_assoc() : null;
echo json_encode(["last_command" => $row ? $row["command"] : "NONE", "last_time" => $row ? $row["created_at"] : null]);
?>