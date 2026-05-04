<?php
// c:/xampp/htdocs/parking/api_auto_exit.php
include "config.php";
header("Content-Type: application/json");

$response = array("success" => false, "message" => "No one inside");

function getActiveParkingCandidate($conn, $expectedName = '', $expectedPlate = '')
{
    $sql = "SELECT h.user_id, h.timestamp, u.name, u.plate_number
            FROM parking_history h
            JOIN users u ON h.user_id = u.id
            WHERE h.action = 'IN'
            AND h.id = (
                SELECT MAX(h2.id) FROM parking_history h2
                WHERE h2.user_id = h.user_id
                AND h2.action = 'IN'
            )
            AND NOT EXISTS (
                SELECT 1 FROM parking_history hx
                WHERE hx.user_id = h.user_id
                AND hx.action = 'OUT'
                AND hx.id > h.id
            )";

    if ($expectedName !== '') {
        $sql .= " AND LOWER(u.name) = LOWER(?)";
    }
    if ($expectedPlate !== '') {
        $sql .= " AND REPLACE(UPPER(u.plate_number), ' ', '') = ?";
    }
    $sql .= " ORDER BY h.timestamp DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($expectedName !== '' && $expectedPlate !== '') {
        $stmt->bind_param("ss", $expectedName, $expectedPlate);
    } else if ($expectedName !== '') {
        $stmt->bind_param("s", $expectedName);
    } else if ($expectedPlate !== '') {
        $stmt->bind_param("s", $expectedPlate);
    }

    if (!$stmt->execute()) {
        return null;
    }

    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

$expected_name = trim($_POST["expected_name"] ?? "");
$expected_plate = strtoupper(trim(str_replace(' ', '', $_POST["expected_plate"] ?? "")));

$row = null;
if ($expected_name !== '' || $expected_plate !== '') {
    $row = getActiveParkingCandidate($conn, $expected_name, $expected_plate);
}
if (!$row) {
    $row = getActiveParkingCandidate($conn);
}

if ($row) {
    $user_id = $row["user_id"];

    // Get Dynamic Rates
    $tariff = getParkingTariffConfig($conn);
    $rate = $tariff['parking_rate'];
    $min_fee = $tariff['min_fee'];
    $grace = $tariff['grace_period'];
    $interval = $tariff['billing_interval_minutes'];

    // Calculate Duration & Fee
    $total_minutes = 0.0;
    $durStmt = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) AS total_seconds");
    $durStmt->bind_param("s", $row["timestamp"]);
    if ($durStmt->execute()) {
        $durRes = $durStmt->get_result()->fetch_assoc();
        $total_minutes = max(0.0, ((float)($durRes["total_seconds"] ?? 0)) / 60.0);
    }
    $fee = calculateParkingFee($total_minutes, $rate, $min_fee, $grace, $interval);

    // 2. Deduct Balance & Award Points
    $points_earned = 30 + floor($fee / 100);
    $conn->query("UPDATE users SET balance = balance - $fee, points = points + $points_earned WHERE id = $user_id");

    // 2.5 Clear slot status (Cleanup)
    // If the slot actually had a booking (even if stolen), revert to status=2 ONLY IF the exiting user is NOT the booker.
    // If the booker is exiting, the booking is fulfilled, so reset to 0.
    $name = $row["name"];
    $plate = $row["plate_number"];
    $conn->query("UPDATE sensor_status 
                  SET status = CASE WHEN booked_user_id IS NOT NULL AND booked_user_id != $user_id THEN 2 ELSE 0 END, 
                      booked_user_id = CASE WHEN booked_user_id = $user_id THEN NULL ELSE booked_user_id END,
                      booking_expires_at = CASE WHEN booked_user_id = $user_id THEN NULL ELSE booking_expires_at END,
                      is_occupied = 0, user_name = '', plate_number = '' 
                  WHERE user_name = '$name' OR plate_number = '$plate'");

    // 3. Log them as "OUT"
    $stmt = $conn->prepare("INSERT INTO parking_history (user_id, action, fee) VALUES (?, 'OUT', ?)");
    $stmt->bind_param("ii", $user_id, $fee);

    if ($stmt->execute()) {
        $exitMessage = "Selamat jalan " . $row["name"] . ". Terima kasih sudah parkir di SpotFinder. Sampai jumpa kembali.";
        if ($fee > 0) {
            $exitMessage .= " Biaya parkir Anda Rp " . number_format($fee, 0, ',', '.') . ".";
        }

        $eventStmt = $conn->prepare("INSERT INTO gate_events (user_id, message, status) VALUES (?, ?, 'EXIT')");
        if ($eventStmt) {
            $eventStmt->bind_param("is", $user_id, $exitMessage);
            if (!$eventStmt->execute()) {
                // Compatibility fallback for older databases whose status column is an ENUM
                // that does not yet include EXIT. The message text still marks it as an exit.
                $fallbackEventStmt = $conn->prepare("INSERT INTO gate_events (user_id, message, status) VALUES (?, ?, 'GRANTED')");
                if ($fallbackEventStmt) {
                    $fallbackEventStmt->bind_param("is", $user_id, $exitMessage);
                    $fallbackEventStmt->execute();
                }
            }
        }

        $response = array(
            "success" => true,
            "name" => $row["name"],
            "user_id" => (int)$user_id,
            "message" => $exitMessage,
            "fee" => $fee,
            "duration" => $total_minutes . " mins"
        );
    } else {
        $response["message"] = "Database error";
    }
}

echo json_encode($response);
?>
