<?php
// c:/xampp/htdocs/parking/get_status.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
include 'config.php';

// 0. Auto-expire bookings (If current time > expiry time)
$conn->query("UPDATE sensor_status SET status = 0, booked_user_id = NULL, booking_expires_at = NULL, user_name='', plate_number='' WHERE status = 2 AND booking_expires_at < NOW()");

// 1. Get Sensors (with status info)
$sensor_res = $conn->query("SELECT * FROM sensor_status ORDER BY slot_id ASC");
$sensors = [];
while ($row = $sensor_res->fetch_assoc()) {
    $sensors[] = $row;
}

// 2. Latest IDs for change detection
$latest_res = $conn->query("SELECT MAX(id) as latest_id FROM parking_history");
$latest_row = $latest_res->fetch_assoc();
$latest_id = (int)($latest_row['latest_id'] ?? 0);

$max_event_res = $conn->query("SELECT MAX(id) as max_id FROM gate_events");
$max_event_id = (int)($max_event_res->fetch_assoc()['max_id'] ?? 0);

// 3. Active parked count (users currently IN)
$active_res = $conn->query("SELECT COUNT(DISTINCT h.user_id) as active
    FROM parking_history h
    WHERE h.action = 'IN'
    AND NOT EXISTS (
        SELECT 1 FROM parking_history ho
        WHERE ho.user_id = h.user_id
        AND ho.action = 'OUT'
        AND ho.id > h.id
    )");
$active_count = (int)($active_res->fetch_assoc()['active'] ?? 0);

// 4. Recent History
$hist_sql = "SELECT h.id, h.action, h.fee, h.slot_id, DATE_FORMAT(h.timestamp, '%H:%i:%s') as time, 
                    h.timestamp as full_time, u.name, u.plate_number as plate, h.user_id 
             FROM parking_history h 
             JOIN users u ON h.user_id = u.id 
             ORDER BY h.id DESC LIMIT 10";
$hist_res = $conn->query($hist_sql);
$history = [];
while ($row = $hist_res->fetch_assoc()) {
    $row['duration'] = '';

    $row['ai_message'] = '';
    if ($row['action'] == 'IN') {
        // Use an entry event only. Without this filter, a later exit message can be reused
        // for an older IN row in the live dashboard history.
        $ev_stmt = $conn->prepare("SELECT message FROM gate_events WHERE user_id = ? AND status = 'GRANTED' AND message LIKE 'Selamat datang%' ORDER BY id DESC LIMIT 1");
        if ($ev_stmt) {
            $ev_stmt->bind_param("i", $row['user_id']);
            if ($ev_stmt->execute()) {
                $ev_row = $ev_stmt->get_result()->fetch_assoc();
                $row['ai_message'] = $ev_row['message'] ?? '';
            }
        }

        $entry = new DateTime($row['full_time']);
        $now = new DateTime();
        $diff = $now->diff($entry);
        $mins = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        $row['duration'] = $mins . ' menit';
    }
    else if ($row['action'] == 'OUT' && $row['fee'] > 0) {
        $row['duration'] = 'Rp ' . number_format($row['fee'], 0, ',', '.');
    }
    if ($row['action'] == 'OUT') {
        $ev_stmt = $conn->prepare("SELECT message FROM gate_events WHERE user_id = ? AND (status = 'EXIT' OR message LIKE 'Selamat jalan%' OR message LIKE 'Terima kasih%') ORDER BY id DESC LIMIT 1");
        if ($ev_stmt) {
            $ev_stmt->bind_param("i", $row['user_id']);
            if ($ev_stmt->execute()) {
                $ev_row = $ev_stmt->get_result()->fetch_assoc();
                $row['ai_message'] = $ev_row['message'] ?? '';
            }
        }
        if ($row['ai_message'] === '') {
            $row['ai_message'] = 'Selamat jalan ' . $row['name'] . '. Terima kasih sudah parkir di SpotFinder. Sampai jumpa kembali.';
        }
    }
    $history[] = $row;
}

echo json_encode([
    'sensors' => $sensors,
    'history' => $history,
    'latest_id' => $latest_id,
    'max_gate_event_id' => $max_event_id,
    'active_parked' => $active_count,
    'total_slots' => count($sensors),
    'booked_count' => (int)($conn->query("SELECT COUNT(*) as c FROM sensor_status WHERE status = 2")->fetch_assoc()['c'] ?? 0),
    'pending_topup_count' => (int)($conn->query("SELECT COUNT(*) as c FROM topup_requests WHERE status = 'PENDING'")->fetch_assoc()['c'] ?? 0),
    'is_hardware_online' => (bool)($conn->query("SELECT 1 FROM sensor_status WHERE updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1")->fetch_row()),
    'last_sync' => ($last_sync = $conn->query("SELECT MAX(updated_at) as t FROM sensor_status")->fetch_assoc()['t'] ?? null),
    'stability_score' => (function($ls) {
        if (!$ls) return 0;
        $diff = time() - strtotime($ls);
        if ($diff < 10) return 100;
        if ($diff < 30) return 95;
        if ($diff < 60) return 85;
        if ($diff < 120) return 60;
        if ($diff < 300) return 30;
        return 0;
    })($last_sync)
]);
?>
