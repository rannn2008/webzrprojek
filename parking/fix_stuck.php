<?php
// c:/xampp/htdocs/parking/fix_stuck.php
// Jalankan sekali dari browser: http://localhost/parking/fix_stuck.php
// Script ini akan:
// 1. Menampilkan semua user yang statusnya "tersangkut" di dalam
// 2. Otomatis mengeluarkan mereka (insert record OUT)
include "config.php";
header("Content-Type: text/html; charset=utf-8");

echo "<h2>🔧 Fix Stuck Parking Records</h2>";

// Cari semua user yang terakhirnya IN tapi belum OUT
$query = "SELECT h.user_id, h.timestamp, u.name, u.rfid_uid
          FROM parking_history h
          JOIN users u ON h.user_id = u.id
          WHERE h.action = 'IN'
          AND h.id = (SELECT MAX(h2.id) FROM parking_history h2 WHERE h2.user_id = h.user_id)
          AND h.user_id NOT IN (
              SELECT ph.user_id FROM parking_history ph 
              WHERE ph.action = 'OUT' AND ph.id > h.id
          )";

$result = $conn->query($query);
$fixed = 0;

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>User ID</th><th>Name</th><th>RFID</th><th>Entry Time</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        // Force OUT
        $uid = $row["user_id"];
        $stmt = $conn->prepare("INSERT INTO parking_history (user_id, action, fee) VALUES (?, 'OUT', 0)");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $fixed++;
        echo "<tr><td>{$row['user_id']}</td><td>{$row['name']}</td><td>{$row['rfid_uid']}</td><td>{$row['timestamp']}</td><td>✅ Fixed → OUT</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ Tidak ada record yang tersangkut. Semua user sudah berstatus OUT.</p>";
}

echo "<br><p><b>Total diperbaiki: $fixed user(s)</b></p>";
echo "<p>✅ Sekarang silahkan scan RFID kembali, seharusnya akses diterima.</p>";

// Tampilkan 10 record terakhir
echo "<h3>📋 10 Record Terakhir:</h3>";
$recent = $conn->query("SELECT h.*, u.name, u.rfid_uid FROM parking_history h JOIN users u ON h.user_id = u.id ORDER BY h.id DESC LIMIT 10");
if ($recent && $recent->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>RFID</th><th>Action</th><th>Fee</th><th>Time</th></tr>";
    while ($r = $recent->fetch_assoc()) {
        $color = $r['action'] == 'IN' ? '#e8f5e9' : '#fff3e0';
        echo "<tr style='background:$color'><td>{$r['id']}</td><td>{$r['name']}</td><td>{$r['rfid_uid']}</td><td><b>{$r['action']}</b></td><td>{$r['fee']}</td><td>{$r['timestamp']}</td></tr>";
    }
    echo "</table>";
}

// Tampilkan semua user terdaftar
echo "<h3>👤 User Terdaftar:</h3>";
$users = $conn->query("SELECT * FROM users");
if ($users && $users->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>RFID UID</th><th>Plate</th></tr>";
    while ($u = $users->fetch_assoc()) {
        echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td><b>{$u['rfid_uid']}</b></td><td>{$u['plate_number']}</td></tr>";
    }
    echo "</table>";
}
?>
