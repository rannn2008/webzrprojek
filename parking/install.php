<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup | Smart Parking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background: radial-gradient(circle at top right, #1e293b, #0f172a); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .repair-card { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 24px; padding: 40px; max-width: 600px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); animation: cardFade 0.8s ease-out; }
        @keyframes cardFade { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .repair-header { text-align: center; margin-bottom: 30px; }
        .repair-icon { width: 80px; height: 80px; background: var(--gradient-main); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(0, 229, 255, 0.3); }
        .log-container { background: rgba(0,0,0,0.3); border-radius: 12px; padding: 20px; font-family: monospace; font-size: 0.85rem; max-height: 400px; overflow-y: auto; color: #94a3b8; border: 1px solid rgba(255,255,255,0.05); }
        .log-item { margin-bottom: 8px; border-left: 2px solid #334155; padding-left: 12px; }
        .status-ok { color: #4ade80; font-weight: 600; }
        .status-fixed { color: #00e5ff; font-weight: 600; }
        .status-err { color: #f87171; font-weight: 600; }
        .status-warn { color: #fbbf24; font-weight: 600; }
        .action-btn { display: block; width: 100%; text-align: center; padding: 15px; border-radius: 12px; font-weight: 700; margin-top: 10px; text-decoration: none; font-family: 'Poppins',sans-serif; font-size: 1rem; border: none; cursor: pointer; transition: transform 0.2s; }
        .action-btn:hover { transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="repair-card">
        <div class="repair-header">
            <div class="repair-icon"><i class="fas fa-microchip"></i></div>
            <h1 style="font-size: 1.5rem; margin-bottom: 5px;">SYSTEM SETUP</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Database Setup & Reset Tool</p>
        </div>
        <div class="log-container">
<?php
include "config.php";
$reset_mode = isset($_GET['reset']) && $_GET['reset'] == '1';

if ($reset_mode) {
    echo "<div class='log-item'><span class='status-warn'>⚠ RESET MODE</span></div>";
    $conn->query("DROP TABLE IF EXISTS topup_requests");
    $conn->query("DROP TABLE IF EXISTS parking_history");
    $conn->query("DROP TABLE IF EXISTS sensor_status");
    $conn->query("DROP TABLE IF EXISTS users");
    $conn->query("DROP TABLE IF EXISTS admin");
    echo "<div class='log-item'>All tables dropped <span class='status-fixed'>CLEARED</span></div>";
}

// Users
$q = $conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rfid_uid VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20) NOT NULL,
    balance INT DEFAULT 50000,
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "<div class='log-item'>users table... " . ($q ? "<span class='status-ok'>OK</span>" : "<span class='status-err'>FAIL</span>") . "</div>";

// History
$q = $conn->query("CREATE TABLE IF NOT EXISTS parking_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action ENUM('IN','OUT','BOOK','CANCEL') NOT NULL,
    fee INT DEFAULT 0,
    slot_id INT DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
echo "<div class='log-item'>parking_history... " . ($q ? "<span class='status-ok'>OK</span>" : "<span class='status-err'>FAIL</span>") . "</div>";

// Sensors — WITH status and booking info
$q = $conn->query("CREATE TABLE IF NOT EXISTS sensor_status (
    slot_id INT PRIMARY KEY,
    status TINYINT DEFAULT 0, -- 0:Empty, 1:Occupied, 2:Booked
    is_occupied TINYINT(1) DEFAULT 0, -- Legacy support
    user_name VARCHAR(100) DEFAULT '',
    plate_number VARCHAR(20),
    booked_user_id INT UNSIGNED,
    booking_expires_at DATETIME,
    booked_at DATETIME,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;");
echo "<div class='log-item'>sensor_status... " . ($q ? "<span class='status-ok'>OK</span>" : "<span class='status-err'>FAIL</span>") . "</div>";

// Ensure new columns exist (for non-reset upgrades)
$conn->query("ALTER TABLE sensor_status ADD COLUMN status TINYINT DEFAULT 0 AFTER slot_id");
$conn->query("ALTER TABLE sensor_status ADD COLUMN booked_user_id INT UNSIGNED DEFAULT NULL AFTER plate_number");
$conn->query("ALTER TABLE sensor_status ADD COLUMN booking_expires_at DATETIME DEFAULT NULL AFTER booked_user_id");
$conn->query("ALTER TABLE sensor_status ADD COLUMN user_name VARCHAR(100) DEFAULT '' AFTER is_occupied");
$conn->query("ALTER TABLE sensor_status ADD COLUMN plate_number VARCHAR(20) DEFAULT '' AFTER user_name");

// Add new profile columns to users
$conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN gender ENUM('Male','Female') DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN dob DATE DEFAULT NULL");
echo "<div class='log-item'>Profile columns <span class='status-ok'>READY</span></div>";

$conn->query("INSERT IGNORE INTO sensor_status (slot_id, is_occupied, user_name, plate_number) VALUES (1, 0, '', ''), (2, 0, '', '')");
echo "<div class='log-item'>Sensor slots <span class='status-ok'>READY</span></div>";

// Topup
$q = $conn->query("CREATE TABLE IF NOT EXISTS topup_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    amount INT NOT NULL,
    status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
echo "<div class='log-item'>topup_requests... " . ($q ? "<span class='status-ok'>OK</span>" : "<span class='status-err'>FAIL</span>") . "</div>";

// Admin
$q = $conn->query("CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
)");
echo "<div class='log-item'>admin table... " . ($q ? "<span class='status-ok'>OK</span>" : "<span class='status-err'>FAIL</span>") . "</div>";

$adm = $conn->query("SELECT id FROM admin LIMIT 1");
if ($adm->num_rows == 0) {
    $p = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admin (username, password) VALUES ('admin', '$p')");
    echo "<div class='log-item'>Admin: <strong>admin/admin123</strong> <span class='status-fixed'>CREATED</span></div>";
}
else {
    echo "<div class='log-item'>Admin <span class='status-ok'>EXISTS</span></div>";
}

file_put_contents(__DIR__ . "/last_scan.txt", "");
$uc = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
echo "<div class='log-item' style='margin-top:10px;border-left-color:#00e5ff'>Users: <strong style='color:#00e5ff'>$uc</strong></div>";
if ($reset_mode)
    echo "<div class='log-item' style='border-left-color:#4ade80'><span class='status-ok'>✅ RESET COMPLETE</span></div>";
?>
        </div>
        <a href="index.php" class="action-btn" style="background:var(--gradient-main);color:#0f172a;"><i class="fas fa-rocket"></i> DASHBOARD</a>
        <a href="users.php" class="action-btn" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;"><i class="fas fa-user-plus"></i> REGISTER RFID</a>
        <?php if (!$reset_mode): ?>
        <form method="GET" onsubmit="return confirm('⚠️ HAPUS SEMUA DATA?\nSemua user & history akan dihapus.\nRFID harus di-scan ulang.');">
            <input type="hidden" name="reset" value="1">
            <button type="submit" class="action-btn" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;"><i class="fas fa-trash-alt"></i> RESET SEMUA DATA</button>
        </form>
        <?php
endif; ?>
    </div>
</body>
</html>
