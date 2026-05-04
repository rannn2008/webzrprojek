<?php
// c:/xampp/htdocs/parking/client_dashboard.php
include "config.php";
include "auth.php";
restrictToClient();

$client_id = $_SESSION["client_id"];

// 1. Fetch User Data
$user = $conn->query("SELECT * FROM users WHERE id = $client_id")->fetch_assoc();
$avatar_path = !empty($user['avatar']) ? $user['avatar'] . "?t=" . time() : "assets/img/default-avatar.png";

// 2. Summary stats
$total_fees = $conn->query("SELECT SUM(fee) as total FROM parking_history WHERE user_id = $client_id AND action='OUT'")->fetch_assoc()["total"] ?? 0;
$last_entry = $conn->query("SELECT timestamp FROM parking_history WHERE user_id = $client_id ORDER BY id DESC LIMIT 1")->fetch_assoc()["timestamp"] ?? "Never";

// Extra stats for dashboard widgets
$visit_count = (int)($conn->query("SELECT COUNT(*) as c FROM parking_history WHERE user_id = $client_id AND action='IN'")->fetch_assoc()["c"] ?? 0);
$avg_duration_res = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, hi.timestamp, ho.timestamp)) as avg_min
    FROM parking_history hi
    JOIN parking_history ho ON ho.user_id = hi.user_id AND ho.action='OUT' AND ho.id > hi.id
    WHERE hi.user_id = $client_id AND hi.action='IN'
    AND NOT EXISTS (SELECT 1 FROM parking_history hx WHERE hx.user_id = hi.user_id AND hx.action='IN' AND hx.id > hi.id AND hx.id < ho.id)
");
$avg_duration = (int)($avg_duration_res->fetch_assoc()["avg_min"] ?? 0);
$last_visit_res = $conn->query("SELECT timestamp FROM parking_history WHERE user_id = $client_id AND action='IN' ORDER BY id DESC LIMIT 1");
$last_visit = $last_visit_res->fetch_assoc()["timestamp"] ?? null;
$recent_5 = $conn->query("SELECT action, fee, timestamp FROM parking_history WHERE user_id = $client_id ORDER BY id DESC LIMIT 5");

// Loyalty engine - now persists in users.points
$loyalty_points_available = (int)($user["points"] ?? 0);
$loyalty_points_total = $loyalty_points_available; // For level calculation simplicity
$loyalty_level = max(1, min(5, (int)floor($loyalty_points_total / 500) + 1));
$loyalty_level_names = [1 => "Starter", 2 => "Silver", 3 => "Gold", 4 => "Elite", 5 => "Platinum"];
$loyalty_next_points = 1000;
$loyalty_progress = min(100, (int)floor(($loyalty_points_available / $loyalty_next_points) * 100));
$loyalty_claim_ready = $loyalty_points_available >= $loyalty_next_points;

// Check if currently parked (IN without OUT)
$check_park = $conn->query("
    SELECT timestamp FROM parking_history AS ph1 
    WHERE user_id = $client_id AND action='IN' 
    AND NOT EXISTS (
        SELECT 1 FROM parking_history AS ph2 
        WHERE ph2.user_id = $client_id AND ph2.action='OUT' AND ph2.id > ph1.id
    ) 
    ORDER BY id DESC LIMIT 1
");
$active_session = false;
$park_time = "";
if ($check_park && $check_park->num_rows > 0) {
    $active_session = true;
    $park_time = $check_park->fetch_assoc()["timestamp"];
}

// 3. Parking History
$history_res = $conn->query("SELECT * FROM parking_history WHERE user_id = $client_id ORDER BY id DESC LIMIT 20");

// 4. Top-up History
$topup_history = $conn->query("SELECT * FROM topup_requests WHERE user_id = $client_id ORDER BY id DESC LIMIT 10");

// 4. Handle Top-up Request
$topup_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["request_topup"])) {
    $amount = intval($_POST["amount"]);
    if ($amount >= 1000) {
        $stmt = $conn->prepare("INSERT INTO topup_requests (user_id, amount) VALUES (?, ?)");
        $stmt->bind_param("ii", $client_id, $amount);
        if ($stmt->execute()) {
            $topup_msg = "<div class='badge bg-warning' style='width:100%'>Request Sent! Waiting for Admin Approval.</div>";
        }
    } else {
        $topup_msg = "<div class='badge bg-danger' style='width:100%'>Minimum top-up is Rp 1.000</div>";
    }
}

// 5. Handle Profile Update
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $email = $_POST["email"];
    $pass = $_POST["password"];
    if (!empty($pass)) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET email=?, password=? WHERE id=?");
        $stmt->bind_param("ssi", $email, $hashed, $client_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");
        $stmt->bind_param("si", $email, $client_id);
    }
    if ($stmt->execute()) {
        $msg = "<div class='badge bg-success' style='width:100%'>Profile Updated!</div>";
        header("Refresh: 2");
    }
}

$rate = getSetting($conn, 'parking_rate', '3000');
$min_fee = getSetting($conn, 'min_fee', '3000');
$grace = getSetting($conn, 'grace_period', '15');
$billing_interval = getSetting($conn, 'billing_interval_minutes', '10');

// Removed redundant old avatar logic to prevent overwriting correct DB path
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | SpotFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .parking-map {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .parking-lot {
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            transition: all 0.3s ease;
        }

        .parking-map-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 34px 18px;
            min-height: 220px;
            align-items: stretch;
        }

        .parking-lot.recommended {
            outline: 2px solid #fbbf24;
            outline-offset: 4px;
        }

        .slot-recommendation {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.58rem;
            font-weight: 800;
            color: #111827;
            background: #fbbf24;
            padding: 4px 8px;
            border-radius: 999px;
        }

        .lot-occupied {
            background: rgba(34, 197, 94, 0.1);
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.2);
        }

        .lot-empty {
            background: rgba(34, 197, 94, 0.1);
            border: 2px dashed var(--success);
        }

        .lot-booked {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid #f59e0b;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
        }

        .lot-assigned {
            background: rgba(255, 225, 0, 0.05);
            border: 2px dashed #ffd700;
            animation: slot-pulse 2s infinite;
        }

        .lot-violation {
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid #ef4444;
            animation: violation-blink 0.8s infinite;
        }

        @keyframes slot-pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 225, 0, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(255, 225, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 225, 0, 0); }
        }

        @keyframes violation-blink {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.02); background: rgba(239, 68, 68, 0.3); }
            100% { opacity: 1; transform: scale(1); }
        }

        .car-visual {
            font-size: 3.5rem;
            color: var(--danger);
        }

        .bay-label {
            position: absolute;
            bottom: -30px;
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .gold-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9998;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(2, 6, 23, 0.78);
            backdrop-filter: blur(10px);
        }

        .gold-modal {
            width: min(560px, 100%);
            background: #121826;
            border: 1px solid rgba(251, 191, 36, 0.35);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.45);
        }

        .summary-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 20px;
            border-radius: 15px;
        }

        .balance-box {
            background: var(--gradient-main);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0, 229, 255, 0.2);
        }

        /* Timer Box Styles */
        .timer-box {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-left: 4px solid var(--success);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            animation: pulse-border 2s infinite;
        }

        @keyframes pulse-border {
            0% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }

        /* ===== NOTIFICATION TOAST OVERLAY ===== */
        .notif-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            opacity: 0;
            animation: notifFadeIn 0.5s ease forwards;
        }

        @keyframes notifFadeIn {
            to {
                opacity: 1;
            }
        }

        @keyframes notifFadeOut {
            to {
                opacity: 0;
            }
        }

        .notif-card {
            text-align: center;
            padding: 40px 50px;
            border-radius: 24px;
            max-width: 420px;
            width: 90%;
            animation: notifSlideUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            transform: translateY(40px);
            opacity: 0;
        }

        @keyframes notifSlideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .notif-card.welcome {
            background: linear-gradient(135deg, rgba(0, 229, 255, 0.15), rgba(34, 197, 94, 0.15));
            border: 2px solid rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 60px rgba(34, 197, 94, 0.3), 0 0 120px rgba(0, 229, 255, 0.1);
        }

        .notif-card.goodbye {
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.15), rgba(168, 85, 247, 0.15));
            border: 2px solid rgba(96, 165, 250, 0.5);
            box-shadow: 0 0 60px rgba(96, 165, 250, 0.3), 0 0 120px rgba(168, 85, 247, 0.1);
        }

        .notif-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            animation: notifPulse 1.5s ease-in-out infinite;
        }

        @keyframes notifPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.15);
            }
        }

        .notif-card.welcome .notif-icon {
            color: #4ade80;
        }

        .notif-card.goodbye .notif-icon {
            color: #60a5fa;
        }

        .notif-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .notif-subtitle {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .notif-bar {
            margin-top: 25px;
            height: 4px;
            border-radius: 2px;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .notif-bar-fill {
            height: 100%;
            border-radius: 2px;
            animation: notifBarShrink linear forwards;
        }

        .notif-card.welcome .notif-bar-fill {
            background: linear-gradient(90deg, #4ade80, #00e5ff);
        }

        .notif-card.goodbye .notif-bar-fill {
            background: linear-gradient(90deg, #60a5fa, #a855f7);
        }

        /* AI Orb Animation */
        .ai-orb-container {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-orb {
            width: 40px;
            height: 40px;
            background: var(--gradient-main);
            border-radius: 50%;
            position: relative;
            z-index: 2;
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.5);
            transition: all 0.3s;
        }

        .ai-orb-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid var(--accent-primary);
            border-radius: 50%;
            animation: orb-pulse 2s infinite;
            opacity: 0;
        }

        .ai-orb-ring:nth-child(2) { animation-delay: 0.5s; }
        .ai-orb-ring:nth-child(3) { animation-delay: 1s; }

        @keyframes orb-pulse {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        .ai-orb.speaking {
            transform: scale(1.2);
            box-shadow: 0 0 40px rgba(0, 229, 255, 0.8);
        }

        .ai-orb.speaking + .ai-orb-ring {
            animation-duration: 0.8s;
            border-color: #4ade80;
        }

        /* Loyalty Progress */
        .loyalty-progress {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0 10px 0;
        }

        /* Emoji Animation */
        .emoji-flyer {
            position: fixed;
            pointer-events: none;
            z-index: 10000;
            font-size: 2rem;
            animation: emoji-float 2.5s ease-out forwards;
            opacity: 0;
        }

        @keyframes emoji-float {
            0% { transform: translateY(0) scale(0.5) rotate(0deg); opacity: 0; }
            20% { opacity: 1; transform: translateY(-20px) scale(1.2) rotate(10deg); }
            100% { transform: translateY(-150px) scale(1.5) rotate(20deg); opacity: 0; }
        }

        @keyframes emoji-burst {
            0% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
            20% { opacity: 1; transform: translate(-50%, -50%) scale(1.5); }
            100% { transform: translate(calc(-50% + var(--tx)), calc(-50% + var(--ty))) scale(1); opacity: 0; }
        }

        /* Enhanced Card Polish */
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 229, 255, 0.15);
            border-color: rgba(0, 229, 255, 0.3);
        }

        .balance-box {
            background: linear-gradient(135deg, #00e5ff, #1266f1);
            border-radius: 24px;
            padding: 30px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 229, 255, 0.3);
            transition: all 0.3s;
        }

        .balance-box:hover {
            box-shadow: 0 20px 45px rgba(0, 229, 255, 0.5);
        }

        .balance-box::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <div class="container">
<?php include 'global_ai_assistant.php'; ?>
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-gauge-high"></i> MY DASHBOARD</h1>
                    <p class="tagline">Welcome back, <?= $user["name"] ?></p>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <img src="<?= $avatar_path ?>" class="header-avatar" style="width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-primary);">
                    <div style="text-align: right;">
                        <a href="logout.php" class="btn btn-danger" style="padding: 8px 15px;"><i
                                class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-house"></i> Public View</a>
                <a href="client_dashboard.php" class="tab-btn active"><i class="fas fa-gauge-high"></i> My Account</a>
                <a href="client_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
                <a href="client_profile.php" class="tab-btn"><i class="fas fa-user-gear"></i> Profile</a>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- BALANCE & STATS -->
            <div>
                <?php if ($active_session): ?>
                    <div class="timer-box">
                        <div
                            style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                            <div>
                                <div style="color:var(--success); font-weight:700; font-size:0.8rem; letter-spacing:1px;"><i
                                        class="fas fa-circle"
                                        style="font-size:0.6rem; animation:pulse-live 1s infinite;"></i> SEDANG PARKIR</div>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Masuk:
                                    <?= date("H:i", strtotime($park_time)) ?>
                                </div>
                            </div>
                            <i class="fas fa-car-side" style="font-size:2rem; color:var(--success); opacity:0.8;"></i>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div>
                                <div style="font-size:0.75rem; color:var(--text-muted);">DURASI</div>
                                <div id="live-timer"
                                    style="font-family:'JetBrains Mono', monospace; font-size:1.5rem; font-weight:700; color:#fff;">
                                    00:00:00</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:0.75rem; color:var(--text-muted);">ESTIMASI BIAYA</div>
                                <div id="live-fee" style="font-size:1.5rem; font-weight:700; color:var(--accent-primary);">
                                    Rp 0</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="balance-box">
                    <div style="font-size: 0.9rem; opacity: 0.8;">CURRENT BALANCE</div>
                    <div id="low-balance-warning"
                        style="background:rgba(239,68,68,0.2); padding:5px; border-radius:5px; margin:5px 0; font-size:0.8rem; font-weight:700; display:<?= ($user["balance"] < 10000) ? 'block' : 'none' ?>;">
                        <i class="fas fa-exclamation-triangle"></i> Low Balance! Please top-up.
                    </div>
                    <div id="client-balance" style="font-size: 2.5rem; font-weight: 700; margin: 5px 0;">Rp
                        <?= number_format($user["balance"], 0, ",", ".") ?>
                    </div>
                    <button onclick="$('#topup-modal').fadeIn()" class="btn btn-light"
                        style="background:#fff; color:var(--accent-primary); border:none; padding: 5px 15px; font-size: 0.8rem; font-weight:700;"><i
                            class="fas fa-plus-circle"></i> TOP UP NOW</button>
                    <?= $topup_msg ?>
                </div>

                <!-- NEW: PERSONALIZED GREETING CARD -->
                <div class="card" style="margin-bottom:20px; background: var(--gradient-main); border:none; padding:25px; position:relative; overflow:hidden;">
                    <div style="display:flex; align-items:center; gap:20px; position:relative; z-index:2;">
                        <img src="<?= $avatar_path ?>" class="greeting-avatar" style="width:70px; height:70px; border-radius:50%; object-fit:cover; border:3px solid rgba(255,255,255,0.3); box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                        <div>
                            <?php 
                                $hour = (int)date('H');
                                $greeting = ($hour < 11) ? "Selamat Pagi" : (($hour < 15) ? "Selamat Siang" : (($hour < 19) ? "Selamat Sore" : "Selamat Malam"));
                            ?>
                            <div style="font-size:0.8rem; opacity:0.8; font-weight:600;"><?= $greeting ?>,</div>
                            <div style="font-size:1.5rem; font-weight:800; margin:2px 0; color:#fff;"><?= explode(' ', $user["name"])[0] ?>! 👋</div>
                            <div style="font-size:0.7rem; opacity:0.9; line-height:1.4; color:rgba(255,255,255,0.85);">Sistem AI kami siap membantu Anda parkir hari ini.</div>
                        </div>
                    </div>
                    <i class="fas fa-sparkles" style="position:absolute; right:-10px; bottom:-10px; font-size:5rem; opacity:0.15; transform: rotate(-15deg);"></i>
                </div>

                <!-- Summary card removed from here -->

                <!-- AI VOICE ASSISTANT WIDGET -->
                <div class="card" style="margin-top:20px; text-align:center; padding:30px; border: 1px solid rgba(0, 229, 255, 0.3); background: rgba(0, 229, 255, 0.05); position:relative; overflow:hidden;">
                    <div style="position:absolute; top:0; left:0; width:100%; height:4px; background:var(--gradient-main);"></div>
                    <div style="font-size:0.75rem; font-weight:800; color:var(--accent-primary); margin-bottom:20px; text-transform:uppercase; letter-spacing:2px;">
                        <i class="fas fa-robot"></i> AI Smart System
                    </div>
                    
                    <div class="ai-orb-container">
                        <div class="ai-orb-ring"></div>
                        <div class="ai-orb-ring"></div>
                        <div id="ai-visualizer" class="ai-orb"></div>
                    </div>

                    <div id="ai-status-text" style="margin-top:20px; font-size:0.8rem; color:#fff; font-weight:600; min-height:1.2rem;">
                        Sistem Siap...
                    </div>
                    
                    <div style="margin-top:15px; font-size:0.65rem; color:var(--text-muted); font-style:italic; line-height:1.4;">
                        "Klik di mana saja untuk mengaktifkan suara otomatis."
                    </div>
                </div>

                <!-- LOYALTY CARD (ENHANCED) -->
                <div class="card" id="spotfinder-gold-card" style="margin-top:20px; padding:20px; background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(168,85,247,0.25)); border: 1px solid rgba(168,85,247,0.5); position:relative; overflow:hidden;">
                    <div style="display:flex; justify-content:space-between; align-items:center; position:relative; z-index:2;">
                        <div style="font-weight:800; font-size:0.9rem; color:#fff; text-shadow:0 0 10px rgba(168,85,247,0.5);">SPOTFINDER GOLD</div>
                        <div id="gold-tier-badge" style="font-size:0.7rem; background:#f59e0b; color:#000; padding:3px 10px; border-radius:20px; font-weight:800; box-shadow:0 0 15px rgba(245,158,11,0.4);"><?= strtoupper($loyalty_level_names[$loyalty_level]) ?></div>
                    </div>
                    <div class="loyalty-progress" style="height:10px; background:rgba(255,255,255,0.1); border-radius:10px; margin:15px 0; overflow:hidden;">
                        <div id="gold-progress-fill" class="loyalty-fill" style="width: <?= $loyalty_progress ?>%; height:100%; background:linear-gradient(90deg, #f59e0b, #fbbf24); box-shadow:0 0 10px #f59e0b;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.65rem; color:#fff; font-weight:600; margin-bottom:15px;">
                        <span id="gold-level-text">Level <?= $loyalty_level ?> (<?= $loyalty_level_names[$loyalty_level] ?>)</span>
                        <span id="gold-points-text"><?= $loyalty_points_available ?> / <?= $loyalty_next_points ?> Pts</span>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <button id="claim-gold-reward" class="btn btn-warning" <?= $loyalty_claim_ready ? "" : "disabled" ?> style="padding:6px; font-size:0.65rem; font-weight:800; border-radius:8px; border:none; background:<?= $loyalty_claim_ready ? "#f59e0b" : "rgba(245,158,11,0.35)" ?>; color:#000; cursor:<?= $loyalty_claim_ready ? "pointer" : "not-allowed" ?>;"><i class="fas fa-gift"></i> CLAIM REWARD</button>
                        <button id="show-gold-benefits" class="btn btn-light" style="padding:6px; font-size:0.65rem; font-weight:800; border-radius:8px; border:none; background:rgba(255,255,255,0.2); color:#fff;"><i class="fas fa-star"></i> BENEFITS</button>
                    </div>
                    <div id="gold-next-hint" style="margin-top:10px; font-size:0.62rem; color:#d1d5db; line-height:1.4;">
                        <?= $loyalty_claim_ready ? "Reward siap diklaim: bonus saldo Rp 10.000." : "Kumpulkan poin dari masuk parkir, keluar parkir, dan booking aktif." ?>
                    </div>
                    <i class="fas fa-crown" style="position:absolute; right:-15px; bottom:-15px; font-size:6rem; opacity:0.1; transform:rotate(-20deg); color:#f59e0b;"></i>
                </div>

                <!-- 5. AKTIVITAS TERAKHIR (MOVED BACK TO LEFT TO FILL GAP) -->
                <div class="card" style="margin-top:20px; padding:20px; border-bottom: 4px solid var(--accent-primary);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                        <div style="font-weight:700; font-size:0.85rem; color:#fff;"><i class="fas fa-history" style="color:var(--accent-primary);"></i> Aktivitas Terakhir</div>
                        <span style="font-size:0.6rem; color:var(--text-muted);">Real-time Feed</span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php 
                        if ($recent_5 && $recent_5->num_rows > 0):
                            $recent_5->data_seek(0);
                            while($act = $recent_5->fetch_assoc()): 
                                $isIn = $act['action'] === 'IN';
                                $isOut = $act['action'] === 'OUT';
                                $isTopup = $act['action'] === 'TOPUP';
                                $icon = $isIn ? 'fa-arrow-right-to-bracket' : ($isOut ? 'fa-arrow-right-from-bracket' : 'fa-wallet');
                                $color = $isIn ? '#4ade80' : ($isOut ? '#f87171' : '#f59e0b');
                                $label = $isIn ? 'Masuk' : ($isOut ? 'Keluar' : 'Top Up');
                        ?>
                        <div style="display:flex; align-items:center; gap:10px; padding:10px; background:rgba(255,255,255,0.03); border-radius:12px; border-left:3px solid <?= $color ?>;">
                            <i class="fas <?= $icon ?>" style="font-size:0.75rem; color:<?= $color ?>;"></i>
                            <div style="flex:1;">
                                <div style="font-size:0.75rem; font-weight:700; color:#fff;"><?= $label ?></div>
                                <div style="font-size:0.6rem; color:var(--text-muted);"><?= date('H:i', strtotime($act['timestamp'])) ?></div>
                            </div>
                        </div>
                        <?php endwhile; endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: WIDGETS -->
            <div>
                <!-- Vehicle Info Card -->
                <div class="card" style="margin-bottom:20px; border-left:4px solid var(--accent-primary); padding:20px;">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <div style="width:60px; height:60px; border-radius:16px; background:linear-gradient(135deg, #0ea5e9, #6366f1); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                            <i class="fas fa-car"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">My Vehicle</div>
                            <div style="font-size:1.3rem; font-weight:700; color:#fff;"><?= $user["name"] ?></div>
                            <div style="font-size:0.85rem; color:var(--accent-primary); font-weight:600; letter-spacing:2px;">
                                <i class="fas fa-id-badge"></i> <?= $user["plate_number"] ?>
                            </div>
                        </div>
                        <a href="client_profile.php" style="color:var(--text-muted); font-size:1.2rem;" title="Edit Profile"><i class="fas fa-pen-to-square"></i></a>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:20px;">
                    <div class="card" style="padding:18px; text-align:center;">
                        <div style="font-size:1.8rem; font-weight:700; color:#4ade80;"><?= $visit_count ?></div>
                        <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px;"><i class="fas fa-road"></i> Total Visits</div>
                    </div>
                    <div class="card" style="padding:18px; text-align:center;">
                        <div style="font-size:1.8rem; font-weight:700; color:#f59e0b;"><?= $avg_duration ?><span style="font-size:0.8rem;">m</span></div>
                        <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px;"><i class="fas fa-clock"></i> Avg Duration</div>
                    </div>
                    <div class="card" style="padding:18px; text-align:center;">
                        <div style="font-size:1.1rem; font-weight:700; color:#60a5fa;"><?= $last_visit ? date("d M", strtotime($last_visit)) : 'N/A' ?></div>
                        <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px;"><i class="fas fa-calendar"></i> Last Visit</div>
                    </div>
                </div>

                <!-- Activity feed moved to left for balance -->

                <!-- 1. SPACE FORECAST (DYNAMIC) -->
                <div class="card" style="padding:20px; border-top: 4px solid var(--accent-primary);">
                    <div style="font-weight:700; font-size:0.85rem; color:#fff; margin-bottom:15px;"><i class="fas fa-chart-line"></i> Occupancy Forecast</div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-muted);">
                            <span>Morning (08:00 - 10:00)</span>
                            <span id="morning-status" style="color:#4ade80; font-weight:700;">-</span>
                        </div>
                        <div style="height:4px; background:rgba(255,255,255,0.05); border-radius:2px;"><div id="morning-percent" style="width:0%; height:100%; background:#4ade80; transition:width 1s;"></div></div>
                        
                        <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-muted); margin-top:5px;">
                            <span>Lunch (12:00 - 13:30)</span>
                            <span id="lunch-status" style="color:#f87171; font-weight:700;">-</span>
                        </div>
                        <div style="height:4px; background:rgba(255,255,255,0.05); border-radius:2px;"><div id="lunch-percent" style="width:0%; height:100%; background:#f87171; transition:width 1s;"></div></div>
                    </div>
                    
                    <hr style="border:none; border-top:1px solid rgba(255,255,255,0.05); margin:15px 0;">
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <div style="font-size:0.6rem; color:var(--text-muted); text-transform:uppercase;">Top Member</div>
                            <div id="top-collector-name" style="font-size:0.75rem; color:#f59e0b; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">-</div>
                        </div>
                        <div>
                            <div style="font-size:0.6rem; color:var(--text-muted); text-transform:uppercase;">Peak Day</div>
                            <div id="peak-parking-day" style="font-size:0.75rem; color:#f87171; font-weight:700;">-</div>
                        </div>
                    </div>
                </div>

                <!-- 2. LIVE SUPPORT -->
                <div class="card" style="margin-top:20px; padding:20px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0, 229, 255, 0.05); text-align:center;">
                    <div style="font-weight:700; font-size:0.85rem; color:#fff; margin-bottom:10px;"><i class="fas fa-headset"></i> Live Support</div>
                    <a href="client_chat.php" class="btn btn-primary" style="padding:8px 15px; font-size:0.7rem; width:100%; border-radius:10px;">
                        <i class="fas fa-comment-dots"></i> Buka Chat
                    </a>
                </div>

                <!-- 3. PARKING RULES -->
                <div class="card" style="margin-top:20px; padding:20px; border-left: 4px solid #f59e0b;">
                    <div style="font-weight:700; font-size:0.85rem; color:#fff; margin-bottom:12px;">
                        <i class="fas fa-circle-info" style="color:#f59e0b;"></i> Parking Rules
                    </div>
                    <ul style="margin:0; padding:0; list-style:none; font-size:0.75rem; color:var(--text-muted); display:flex; flex-direction:column; gap:8px;">
                        <li><i class="fas fa-check" style="color:#4ade80; margin-right:6px;"></i> Max 5 km/jam & Parkir di tengah.</li>
                        <li><i class="fas fa-check" style="color:#4ade80; margin-right:6px;"></i> Saldo min Rp 10.000.</li>
                    </ul>
                </div>

                <!-- 4. ECO-IMPACT & PARTNERS -->
                <div class="card" style="margin-top:20px; padding:20px; background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.1)); border: 1px solid rgba(14,165,233,0.2);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <div style="font-weight:700; font-size:0.8rem; color:#4ade80;"><i class="fas fa-leaf"></i> Eco-Impact</div>
                        <div style="font-size:0.75rem; font-weight:800; color:#4ade80;">1.2kg CO2</div>
                    </div>
                    <hr style="border:none; border-top:1px solid rgba(255,255,255,0.1); margin:10px 0;">
                    <div style="font-weight:700; font-size:0.8rem; color:#f59e0b; margin-bottom:10px;"><i class="fas fa-tags"></i> Nearby Partner</div>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px; background:rgba(255,255,255,0.03); border-radius:10px;">
                        <div style="width:30px; height:30px; background:#f59e0b; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.7rem;"><i class="fas fa-mug-hot"></i></div>
                        <div style="font-size:0.65rem; font-weight:600;">Coffee Shop 10% OFF</div>
                    </div>
                </div>

                <!-- 5. SYSTEM STATUS -->
                <div class="card" style="margin-top:20px; padding:15px; display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1);">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:8px; height:8px; background:#4ade80; border-radius:50%; box-shadow: 0 0 10px #4ade80; animation: pulse 2s infinite;"></div>
                        <span style="font-size:0.7rem; font-weight:600; color:#4ade80;">System Active</span>
                    </div>
                    <span style="font-size:0.6rem; color:var(--text-muted);">v2.4.0</span>
                </div>

                <!-- 6. MEMBER SUMMARY (MOVED HERE TO FILL SPACE) -->
                <div class="card" style="margin-top:20px; padding:20px; background:rgba(0, 229, 255, 0.03); border-left: 4px solid var(--accent-primary);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div style="font-size:0.65rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Member Since</div>
                            <div style="font-size:1.1rem; font-weight:700; color:#fff;"><?= date("d M Y", strtotime($user["created_at"])) ?></div>
                        </div>
                        <i class="fas fa-calendar-check" style="font-size:1.5rem; color:var(--accent-primary); opacity:0.5;"></i>
                    </div>
                    <hr style="border:none; border-top:1px solid rgba(255,255,255,0.05); margin:15px 0;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                        <div>
                            <div style="font-size:0.65rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Total Parking Fees</div>
                            <div style="font-size:1.4rem; font-weight:800; color:#f87171;">Rp <?= number_format($total_fees, 0, ",", ".") ?></div>
                        </div>
                        <div style="font-size:0.65rem; color:#4ade80; font-weight:600;"><i class="fas fa-chart-line"></i> +12% this month</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- TOP-UP MODAL (ENHANCED) -->
        <div id="topup-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center;">
            <div class="card" style="max-width:400px; width:90%; position:relative; overflow:hidden;">
                <!-- Step 1: Input -->
                <div id="topup-step-1">
                    <div class="card-header"><h2 class="card-title"><i class="fas fa-wallet"></i> Top-up Balance</h2></div>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">Enter the amount you want to top up to your wallet.</p>
                    <div style="margin-bottom: 20px;">
                        <label style="font-size:0.75rem; color:var(--accent-primary); font-weight:600;">AMOUNT (RP)</label>
                        <input type="number" id="topup-amount-input" class="form-control" placeholder="e.g. 50000" min="1000" style="font-size:1.2rem; font-weight:700; text-align:center;">
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" onclick="$('#topup-modal').fadeOut()" class="btn btn-danger" style="flex:1">Cancel</button>
                        <button type="button" onclick="showQRIS()" class="btn btn-success" style="flex:2">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 2: QRIS Simulation -->
                <div id="topup-step-2" style="display:none; text-align:center;">
                    <div class="card-header"><h2 class="card-title" style="color:#ef4444;"><i class="fas fa-qrcode"></i> Scan QRIS</h2></div>
                    <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:15px;">Simulasi Pembayaran QRIS Berhasil!</p>
                    <div style="background:#fff; padding:15px; border-radius:12px; margin-bottom:15px; display:inline-block;">
                        <img src="assets/img/qris_mock.png" style="width:200px; height:auto; display:block; margin:0 auto;" alt="QRIS Mock">
                        <div style="color:#000; font-weight:700; margin-top:10px; font-size:1rem;" id="qris-amount-display">Rp 0</div>
                    </div>
                    <p style="font-size:0.7rem; color:var(--text-muted);">Silakan scan QR di atas (simulasi). Klik tombol di bawah setelah Anda merasa sudah membayar.</p>
                    <form method="POST">
                        <input type="hidden" name="amount" id="final-amount-hidden">
                        <button type="submit" name="request_topup" class="btn btn-success" style="width:100%; margin-top:10px;">
                            SAYA SUDAH BAYAR <i class="fas fa-check-circle"></i>
                        </button>
                        <button type="button" onclick="$('#topup-step-2').hide(); $('#topup-step-1').show();" class="btn btn-secondary" style="width:100%; margin-top:8px; font-size:0.7rem; border:none; background:transparent;">
                            Kembali ke Input
                        </button>
                    </form>
                </div>
            </div>
        </div>

                <!-- Tables moved to grid for balance -->
            </div>

        </div>

        <div id="gold-benefits-modal" class="gold-modal-backdrop">
            <div class="gold-modal">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:14px;">
                    <div>
                        <div style="font-size:0.75rem; color:#fbbf24; font-weight:800; letter-spacing:1px;">SPOTFINDER GOLD</div>
                        <div style="font-size:1.35rem; color:#fff; font-weight:800;">Benefit & Konsep Poin</div>
                    </div>
                    <button id="close-gold-benefits" class="btn" style="width:38px; height:38px; border-radius:10px; border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.08); color:#fff;"><i class="fas fa-xmark"></i></button>
                </div>
                <div style="display:grid; gap:10px; color:#cbd5e1; font-size:0.82rem; line-height:1.5;">
                    <div style="padding:12px; border-radius:12px; background:rgba(255,255,255,0.05);"><b style="color:#fff;">Poin otomatis:</b> Masuk +120, keluar +80 plus bonus biaya, booking +60, batal +10.</div>
                    <div style="padding:12px; border-radius:12px; background:rgba(255,255,255,0.05);"><b style="color:#fff;">Reward:</b> Setiap 1000 poin bisa ditukar menjadi saldo Rp 10.000.</div>
                    <div style="padding:12px; border-radius:12px; background:rgba(255,255,255,0.05);"><b style="color:#fff;">Gold Pro:</b> dashboard menandai slot rekomendasi dari slot kosong yang paling cepat tersedia.</div>
                    <div style="padding:12px; border-radius:12px; background:rgba(255,255,255,0.05);"><b style="color:#fff;">Roadmap fitur:</b> prioritas booking, diskon jam sepi, dan voucher partner bisa ditambahkan dari tabel reward yang sama.</div>
                </div>
            </div>
        </div>

        <!-- NEW FULL-WIDTH SECTION FOR MAP & TABLES -->
        <div style="margin-top:25px;">
            <div class="card" style="border-top: 5px solid var(--accent-primary);">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 class="card-title"><i class="fas fa-map-location-dot"></i> Real-time Map</h2>
                    <span id="hw-status-badge-client" style="font-size:0.7rem; font-weight:700; padding:4px 12px; border-radius:20px; background:rgba(148,163,184,0.1); color:#94a3b8; border:1px solid rgba(148,163,184,0.2); letter-spacing:1px;">
                        <i class="fas fa-circle"></i> Memuat...
                    </span>
                </div>
                <div class="parking-map-container" id="map-refresher" style="padding: 20px;">
                    <p style="text-align:center; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Memuat peta parkir...</p>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px; margin-top:25px;">
                <div class="card" style="border-left: 5px solid var(--warning);">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-clock-rotate-left"></i> Top-up Status</h2>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="table" style="font-size:0.8rem;">
                            <thead>
                                <tr><th>Date</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($topup_history->num_rows > 0): 
                                    while($th = $topup_history->fetch_assoc()): 
                                        $stClass = $th['status'] == 'APPROVED' ? 'bg-success' : ($th['status'] == 'REJECTED' ? 'bg-danger' : 'bg-warning');
                                ?>
                                    <tr>
                                        <td><?= date("d/m H:i", strtotime($th['created_at'])) ?></td>
                                        <td style="font-weight:700;">Rp <?= number_format($th['amount'], 0, ',', '.') ?></td>
                                        <td><span class="badge <?= $stClass ?>"><?= $th['status'] ?></span></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" style="text-align:center;">No records</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card" style="border-left: 5px solid var(--accent-primary);">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-list"></i> My History</h2>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="table" style="font-size:0.8rem;">
                            <thead>
                                <tr><th>Time</th><th>Action</th><th>Fee</th><th>Struk</th></tr>
                            </thead>
                            <tbody id="client-history-body">
                                <?php if ($history_res->num_rows > 0):
                                    while ($h = $history_res->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date("H:i:s", strtotime($h["timestamp"])) ?></td>
                                            <td><span class="badge <?= $h["action"] == "IN" ? "bg-success" : "bg-danger" ?>"><?= $h["action"] ?></span></td>
                                            <td style="text-align:right; font-weight:700"><?= $h["fee"] > 0 ? "Rp ".number_format($h["fee"], 0, ",", ".") : "-" ?></td>
                                            <td style="text-align:center;">
                                                <?php if ($h["action"] == "OUT"): ?>
                                                    <a href="print_receipt.php?id=<?= $h['id'] ?>" target="_blank" class="btn btn-warning" style="padding:5px 10px; font-size:0.7rem;"><i class="fas fa-file-invoice-dollar"></i> Struk</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" style="text-align:center;">No history</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 25px; border-left: 4px solid #4ade80;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-comments"></i> Chat dengan Admin</h2>
            </div>
            <p style="color:var(--text-muted); margin-bottom:12px;">Halaman chat sekarang dipisah agar komunikasi lebih nyaman dan fiturnya lengkap (voice note, hapus pesan).</p>
            <a href="client_chat.php" class="btn btn-success" style="display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-arrow-up-right-from-square"></i> Buka Tab Chat Client
            </a>
        </div>
    </div>

    <script>
        let isMapLoading = false;
        let isClientLiveLoading = false;
        let lastGateEventId = 0; 
        const initialActiveSession = <?= json_encode((bool) $active_session) ?>;
        let currentActiveSession = initialActiveSession;
        const clientName = <?= json_encode($user['name']) ?>;
        const currentClientId = <?= (int)$_SESSION['client_id'] ?>;
        let goldAvailablePoints = <?= (int)$loyalty_points_available ?>;
        const goldRewardThreshold = <?= (int)$loyalty_next_points ?>;

        // Local AI logic removed in favor of global_ai_assistant.php
        
        function unlockAudio() {
            // First time interaction to unlock audio
            const dummy = new SpeechSynthesisUtterance("");
            window.speechSynthesis.speak(dummy);
            console.log("Audio Unlocked via interaction.");
            // Remove listener after first interaction
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('touchstart', unlockAudio);
        }

        // Add listeners for interaction
        document.addEventListener('click', unlockAudio);
        document.addEventListener('touchstart', unlockAudio);

        function showNotification(type) {
            // type: 'welcome' or 'goodbye'
            const isWelcome = type === 'welcome';
            const icon = isWelcome ? 'fa-car-side' : 'fa-hand-peace';
            const title = isWelcome ? 'Selamat Datang!' : 'Selamat Jalan!';
            const subtitle = isWelcome
                ? 'Halo <strong>' + clientName + '</strong>,<br>Selamat datang di SpotFinder! Silahkan parkir kendaraan Anda.'
                : 'Terima kasih <strong>' + clientName + '</strong>,<br>Semoga perjalanan Anda menyenangkan. Sampai jumpa kembali!';

            // Clear entrance flag on goodbye so next entry triggers new welcome
            if (!isWelcome) {
                sessionStorage.removeItem('sf_notif_shown');
            } else {
                sessionStorage.setItem('sf_notif_shown', 'yes');
            }

            // Determine duration based on audio length, fallback to 6s
            let duration = 6000;

            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'notif-overlay';
            overlay.innerHTML = `
                <div class="notif-card ${type}">
                    <div class="notif-icon"><i class="fas ${icon}"></i></div>
                    <div class="notif-title">${title}</div>
                    <div class="notif-subtitle">${subtitle}</div>
                    <div class="notif-bar">
                        <div class="notif-bar-fill" style="animation-duration: ${duration}ms;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);

            // Play AI Voice
            const voiceText = isWelcome 
                ? `Selamat datang ${clientName}. Silahkan parkir kendaraan Anda.` 
                : `Terima kasih ${clientName}. Sampai jumpa kembali!`;
            speakText(voiceText);
            triggerEmojiAnimation(isWelcome ? 'welcome' : 'thanks', true);
            
            // Safety: reload even if audio fails or gets stuck
            let cleanupCalled = false;
            const cleanup = () => {
                if (cleanupCalled) return;
                cleanupCalled = true;
                overlay.style.animation = 'notifFadeOut 0.5s ease forwards';
                setTimeout(() => {
                    overlay.remove();
                    location.reload();
                }, 600);
            };

            setTimeout(cleanup, duration + 1500);
        }

        let lastBalance = -1;

        function escHtml(v) {
            return String(v ?? "").replace(/[&<>"']/g, function (c) {
                return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c];
            });
        }

        function renderClientHistory(rows) {
            if (!rows || rows.length === 0) {
                $("#client-history-body").html("<tr><td colspan='4' style='text-align:center;'>No movements logged yet.</td></tr>");
                return;
            }
            const html = rows.map(h => {
                const act = String(h.action).toUpperCase().trim();
                let bClass = "bg-danger";
                let icon = "fa-circle-arrow-left";
                let extraStyle = "padding: 5px 12px;";
                let label = "OUT";
                
                let feePrefix = "Rp ";
                let feeColor = "inherit";
                
                if (act === "IN") {
                    bClass = "bg-success";
                    icon = "fa-circle-arrow-right";
                    label = "IN";
                } else if (act === "BOOK") {
                    bClass = "";
                    extraStyle = "background: #8b5cf6; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 10px rgba(139, 92, 246, 0.3);";
                    icon = "fa-ticket-alt";
                    label = "BOOKING";
                } else if (act === "CANCEL") {
                    bClass = "";
                    extraStyle = "background: #64748b; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;";
                    icon = "fa-undo";
                    label = "BATAL";
                    feePrefix = "+ Rp ";
                    feeColor = "#4ade80";
                } else if (act === "TOPUP") {
                    bClass = "";
                    extraStyle = "background: #0ea5e9; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;";
                    icon = "fa-plus-circle";
                    label = "TOPUP";
                    feePrefix = "+ Rp ";
                    feeColor = "#4ade80";
                }

                return `<tr>
                    <td style="font-family:monospace; color:var(--accent-primary)">${escHtml(h.timestamp)}</td>
                    <td><span class="badge ${bClass}" style="${extraStyle}"><i class="fas ${icon}"></i> ${label}</span></td>
                    <td style="text-align:right; font-weight:700; color: ${feeColor}">${h.fee > 0 ? feePrefix + Number(h.fee).toLocaleString("id-ID") : "-"}</td>
                    <td style="text-align:center;">
                        ${act === "OUT" ? `<a href="print_receipt.php?id=${h.id}" target="_blank" class="btn btn-warning" style="padding:5px 10px; font-size:0.7rem;"><i class="fas fa-file-invoice-dollar"></i> Struk</a>` : ""}
                    </td>
                </tr>`;
            }).join("");
            $("#client-history-body").html(html);
        }

        function refreshClientLive() {
            if (isClientLiveLoading) return;
            isClientLiveLoading = true;
            $.ajax({
                url: "api_get_client_live.php",
                method: "GET",
                dataType: "json",
                cache: false
            }).done(function (data) {
                const active = !!data.active_session;
                if (active !== currentActiveSession) {
                    currentActiveSession = active; // Update state before notification
                }
                const balance = Number(data.user?.balance || 0);
                $("#client-balance").text("Rp " + balance.toLocaleString("id-ID"));
                
                // --- Top-up Detection ---
                if (lastBalance !== -1 && balance > lastBalance) {
                    speakText(`Top up berhasil! Saldo Anda sekarang adalah Rp ${balance.toLocaleString("id-ID")}`);
                }
                lastBalance = balance;

                const isLow = balance < 10000;
                $("#low-balance-warning").toggle(isLow);
                $(".balance-box").toggleClass("card-balance-critical", isLow);
                
                $("#client-total-used").text("Rp " + Number(data.total_fees || 0).toLocaleString("id-ID"));
                
                // Update Avatar if changed
                if (data.user && data.user.avatar) {
                    const newPath = data.user.avatar + "?t=" + new Date().getTime();
                    $(".header-avatar, .avatar-preview, .greeting-avatar").attr("src", newPath);
                }

                renderClientHistory(data.history || []);
                if (data.loyalty) {
                    updateGoldCard(data.loyalty.available_points, false);
                }

                if (typeof rate !== "undefined" && data.settings) {
                    rate = Number(data.settings.parking_rate || rate);
                    minFee = Number(data.settings.min_fee || minFee);
                    grace = Number(data.settings.grace_period || grace);
                    billingInterval = Number(data.settings.billing_interval_minutes || billingInterval || 10);
                }
            }).always(function () {
                isClientLiveLoading = false;
            });
        }

        // Map update
        function showQRIS() {
            const amt = $("#topup-amount-input").val();
            if (amt < 1000) {
                alert("Minimum top-up is Rp 1.000");
                return;
            }
            $("#qris-amount-display").text("Rp " + Number(amt).toLocaleString("id-ID"));
            $("#final-amount-hidden").val(amt);
            $("#topup-step-1").hide();
            $("#topup-step-2").fadeIn();
        }

        function updateMap() {
            if (isMapLoading) return;
            isMapLoading = true;

            $.ajax({
                url: "get_status.php",
                method: "GET",
                dataType: "json",
                cache: false,
                timeout: 5000
            }).done(function (data) {
                let html = "";
                let currentUserId = currentClientId;

                if (!data.sensors || data.sensors.length === 0) {
                    $("#map-refresher").html("<p style='text-align:center; color:var(--text-muted); padding:20px;'><i class='fas fa-satellite-dish'></i> Menunggu data sensor...</p>");
                    isMapLoading = false;
                    return;
                }

                const freeSlots = data.sensors
                    .filter(s => parseInt(s.status) === 0)
                    .map(s => parseInt(s.slot_id));
                const recommendedSlot = freeSlots.length ? Math.min(...freeSlots) : null;

                if (lastGateEventId === 0 && data.max_gate_event_id > 0) {
                    lastGateEventId = data.max_gate_event_id;
                    console.log("Client baseline event set to: " + lastGateEventId);
                }

                data.sensors.forEach(s => {
                    let isOcc = parseInt(s.status) === 1;
                    let isBooked = parseInt(s.status) === 2;
                    let isMine = isBooked && parseInt(s.booked_user_id) === currentUserId;
                    let isRecommended = !isOcc && !isBooked && parseInt(s.slot_id) === recommendedSlot;

                    let statusClass = "lot-empty";
                    let content = '<div style="font-size:2.5rem;color:#4ade80;"><i class="fas fa-circle-check"></i></div>';
                    let statusLabel = '<div style="font-size:0.65rem;font-weight:700;color:#4ade80;margin-top:5px;">TERSEDIA</div>';
                    let btn = `<button onclick="bookSlot(${s.slot_id})" style="margin-top:10px; background:var(--gradient-main); border:none; color:#0f172a; padding:5px 15px; border-radius:30px; font-size:0.7rem; font-weight:700; cursor:pointer;"><i class="fas fa-bookmark"></i> BOOK NOW</button>`;

                    if (isOcc) {
                        statusClass = "lot-occupied";
                        content = '<div class="car-visual"><i class="fas fa-car-side"></i></div>';
                        content += `<div style="font-size:0.65rem;color:#fff;margin-top:5px;">${escHtml(s.user_name || 'Occupied')}</div>`;
                        statusLabel = '<div style="font-size:0.65rem;font-weight:700;color:#f87171;margin-top:5px;">TERISI</div>';
                        btn = "";
                    } else if (isBooked) {
                        statusClass = "lot-booked";
                        content = `<div style="font-size:2.5rem;color:#f59e0b;"><i class="fas fa-clock"></i></div>`;
                        statusLabel = `<div style="font-size:0.65rem;font-weight:700;color:#f59e0b;margin-top:5px;">DIPESAN</div>`;

                        if (isMine) {
                            let timeRemainingStr = "";
                            let canCancel = true;
                            
                            if (s.booking_expires_at) {
                                const safeDateStr = s.booking_expires_at.replace(' ', 'T');
                                const expiryTime = new Date(safeDateStr).getTime();
                                const nowTime = new Date().getTime();
                                const diffSecs = Math.max(0, Math.floor((expiryTime - nowTime) / 1000));
                                const mins = Math.floor(diffSecs / 60);
                                const secs = diffSecs % 60;
                                timeRemainingStr = `<div style="font-size:0.8rem; font-family:monospace; font-weight:bold; color:#facc15; margin-top:4px;">${mins}:${secs.toString().padStart(2, '0')}</div>`;
                            }
                            
                            if (s.booked_at) {
                                const safeBookedStr = s.booked_at.replace(' ', 'T');
                                const bookedTime = new Date(safeBookedStr).getTime();
                                const nowTime = new Date().getTime();
                                const elapsedMins = Math.floor((nowTime - bookedTime) / 60000);
                                if (elapsedMins >= 5) {
                                    canCancel = false;
                                }
                            }

                            content += `<div style="font-size:0.55rem;color:#fff;margin-top:2px;">(Booking Anda)</div>`;
                            content += timeRemainingStr;
                            
                            if (canCancel) {
                                btn = `<button onclick="requestCancel(${s.slot_id})" style="margin-top:8px; background:#ef4444; border:none; color:#fff; padding:4px 10px; border-radius:30px; font-size:0.65rem; font-weight:700; cursor:pointer;"><i class="fas fa-xmark"></i> BATALKAN</button>`;
                            } else {
                                btn = `<div style="margin-top:8px; font-size:0.55rem; color:#94a3b8; font-style:italic;">Batas waktu batal (5m) habis</div>`;
                            }
                        } else {
                            btn = "";
                        }
                    } else if (parseInt(s.status) === 3) {
                        let isMyAssigned = parseInt(s.booked_user_id) === currentUserId;
                        
                        if (isMyAssigned) {
                            statusClass = "lot-assigned";
                            content = `<div style="font-size:2.5rem;color:#ffd700;"><i class="fas fa-person-walking-arrow-right"></i></div>`;
                            statusLabel = `<div style="font-size:0.65rem;font-weight:700;color:#ffd700;margin-top:5px;">MENUJU SLOT</div>`;
                            btn = "";
                        } else {
                            // To others, it just looks reserved
                            statusClass = "lot-booked";
                            content = `<div style="font-size:2.5rem;color:#f59e0b;"><i class="fas fa-clock"></i></div>`;
                            statusLabel = `<div style="font-size:0.65rem;font-weight:700;color:#f59e0b;margin-top:5px;">DIPESAN</div>`;
                            btn = "";
                        }
                    } else if (parseInt(s.status) === 4) {
                        statusClass = "lot-violation";
                        content = `<div style="font-size:2.5rem;color:#ef4444;"><i class="fas fa-triangle-exclamation"></i></div>`;
                        statusLabel = `<div style="font-size:0.65rem;font-weight:700;color:#ef4444;margin-top:5px;">PELANGGARAN</div>`;
                        btn = "";
                    }

                    html += `<div class="parking-lot ${statusClass} ${isRecommended ? 'recommended' : ''}">
                        ${isRecommended ? '<div class="slot-recommendation">BEST</div>' : ''}
                        ${content}
                        ${statusLabel}
                        ${btn}
                        <div class="bay-label">BAY 0${s.slot_id}</div>
                    </div>`;
                });

                $("#map-refresher").html(html);

                // --- Hardware Status Badge ---
                const hwBadge = $("#hw-status-badge-client");
                if (hwBadge.length) {
                    if (data.is_hardware_online) {
                        const score = data.stability_score || 0;
                        let scoreColor = score > 80 ? '#4ade80' : (score > 50 ? '#f59e0b' : '#f87171');
                        hwBadge.html(`<i class="fas fa-circle" style="animation: pulse-green 2s infinite;"></i> ONLINE <span style="font-size:0.55rem; margin-left:5px; padding-left:5px; border-left:1px solid rgba(255,255,255,0.1);">Stability: <span style="color:${scoreColor}">${score}%</span></span>`);
                        hwBadge.css({ "background": "rgba(74, 222, 128, 0.15)", "color": "#4ade80", "border": "1px solid rgba(74, 222, 128, 0.3)" });
                    } else {
                        const lastSync = data.last_sync ? new Date(data.last_sync).toLocaleTimeString('id-ID') : '-';
                        hwBadge.html(`<i class="fas fa-circle"></i> OFFLINE <span style="font-size:0.55rem;">(Last: ${lastSync})</span>`);
                        hwBadge.css({ "background": "rgba(148, 163, 184, 0.1)", "color": "#94a3b8", "border": "1px solid rgba(148, 163, 184, 0.2)" });
                    }
                }

            }).fail(function (xhr, status, error) {
                console.error("Map Update Failed:", error, xhr.status);
                $("#map-refresher").html("<p style='color:var(--danger); text-align:center; padding:20px;'><i class='fas fa-exclamation-triangle'></i> Gagal menghubungkan ke server (" + xhr.status + ")</p>");
            }).always(function () {
                isMapLoading = false;
            });
        }

        function requestCancel(id) {
            if (!confirm("Batalkan booking Bay " + id + "? Permintaan akan dikirim melalui chat ke admin.")) return;
            const msg = "[REQ_CANCEL:" + id + "] Saya ingin membatalkan booking di Bay 0" + id;
            $.post("api_chat.php", {
                action: "send_text",
                message: msg
            }, function (res) {
                if (res.success) {
                    location.href = "client_chat.php";
                } else {
                    alert(res.message || "Gagal mengirim permintaan");
                }
            }, "json");
        }

        function bookSlot(slotId) {
            if (!confirm("Booking ini dikenakan biaya Rp 5.000 (Non-Refundable).\nLanjutkan pemesanan Bay 0" + slotId + "?")) return;
            
            $.post("api_book_slot.php", { slot_id: slotId }, function(res) {
                if (res.success) {
                    alert(res.message);
                    updateMap();
                    refreshClientLive(); // Update balance/history
                } else {
                    alert(res.message);
                }
            }, "json");
        }

        function updateGoldCard(points, claimed) {
            goldAvailablePoints = Number(points || 0);
            const pct = Math.min(100, Math.floor((goldAvailablePoints / goldRewardThreshold) * 100));
            $("#gold-progress-fill").css("width", pct + "%");
            $("#gold-points-text").text(goldAvailablePoints + " / " + goldRewardThreshold + " Pts");
            const ready = goldAvailablePoints >= goldRewardThreshold;
            $("#claim-gold-reward")
                .prop("disabled", !ready)
                .css({
                    "background": ready ? "#f59e0b" : "rgba(245,158,11,0.35)",
                    "cursor": ready ? "pointer" : "not-allowed"
                });
            $("#gold-next-hint").text(ready ? "Reward siap diklaim: bonus saldo Rp 10.000." : "Kumpulkan poin dari masuk parkir, keluar parkir, dan booking aktif.");
            if (claimed) {
                speakText("Reward SpotFinder Gold berhasil diklaim. Saldo bonus sudah masuk.");
            }
        }

        $("#show-gold-benefits").on("click", function () {
            $("#gold-benefits-modal").css("display", "flex");
        });

        $("#close-gold-benefits, #gold-benefits-modal").on("click", function (e) {
            if (e.target === this) {
                $("#gold-benefits-modal").hide();
            }
        });

        $("#claim-gold-reward").on("click", function () {
            if ($(this).prop("disabled")) {
                alert("Poin belum cukup untuk claim reward. Butuh 1000 poin aktif.");
                return;
            }
            $.post("api_claim_reward.php", {}, function (res) {
                if (res.success) {
                    alert(res.message);
                    updateGoldCard(res.available_points, true);
                    refreshClientLive();
                } else {
                    alert(res.message || "Gagal claim reward");
                    if (typeof res.available_points !== "undefined") {
                        updateGoldCard(res.available_points, false);
                    }
                }
            }, "json");
        });

        <?php if ($active_session): ?>
            // Live Timer Logic
            const parkTimeStr = "<?= $park_time ?>";
            const parkTime = new Date(parkTimeStr.replace(/-/g, '/')).getTime();

            let rate = <?= $rate ?>;
            let minFee = <?= $min_fee ?>;
            let grace = <?= $grace ?>;
            let billingInterval = <?= intval($billing_interval) ?>;

            function updateTimer() {
                const now = new Date().getTime();
                const diff = now - parkTime;

                if (diff < 0) return;

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);

                const formatted =
                    String(hours).padStart(2, '0') + ':' +
                    String(mins).padStart(2, '0') + ':' +
                    String(secs).padStart(2, '0');

                $("#live-timer").text(formatted);

                const totalMins = Math.floor(diff / (1000 * 60));
                let fee = 0;
                if (totalMins > grace) {
                    const unit = Math.max(1, Number(billingInterval || 1));
                    let billableUnits = Math.ceil((totalMins - grace) / unit);
                    fee = Math.max(billableUnits * rate, minFee);
                }

                $("#live-fee").text("Rp " + fee.toLocaleString('id-ID'));
            }

            setInterval(updateTimer, 1000);
            updateTimer();
        <?php endif; ?>

        function updateClock() {
            const now = new Date();
            $("#digital-clock").text(now.toLocaleTimeString("id-ID", { hour12: false }));
            $("#digital-date").text(now.toLocaleDateString("id-ID", { weekday: "long", year: "numeric", month: "long", day: "numeric" }));
        }

        function speakText(txt) {
            if (!('speechSynthesis' in window)) return;
            const msg = new SpeechSynthesisUtterance(txt);
            msg.lang = 'id-ID';
            msg.rate = 1.0;
            msg.pitch = 1.1;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(msg);
        }

        function pollGateEvents() {
            $.get("api_get_gate_events.php?last_id=" + lastGateEventId, function(data) {
                if (data.events && data.events.length > 0) {
                    data.events.forEach(event => {
                        lastGateEventId = Math.max(lastGateEventId, event.id);
                        speakText(event.message);
                    });
                }
            }, "json");
        }

        $(document).ready(function () {
            $(document).one("click keydown touchstart", unlockAudio);

            // Trigger notification on first load if parked and not yet shown
            const justEntered = sessionStorage.getItem('sf_notif_shown');
            if (initialActiveSession && !justEntered) {
                showNotification('welcome');
            }
            
            updateMap();
            refreshClientLive();
            
            function updateAnalytics() {
                $.get("api_get_analytics.php", function (data) {
                    if (data.forecast) {
                        $("#morning-percent").css("width", data.forecast.morning.percent + "%").css("background", data.forecast.morning.status === 'High' ? '#f87171' : '#4ade80');
                        $("#morning-status").text(data.forecast.morning.status).css("color", data.forecast.morning.status === 'High' ? '#f87171' : '#4ade80');
                        
                        $("#lunch-percent").css("width", data.forecast.lunch.percent + "%").css("background", data.forecast.lunch.status === 'High' ? '#f87171' : '#4ade80');
                        $("#lunch-status").text(data.forecast.lunch.status).css("color", data.forecast.lunch.status === 'High' ? '#f87171' : '#4ade80');
                    }
                    if (data.top_user) {
                        $("#top-collector-name").text(data.top_user.name);
                    }
                    if (data.peak_day) {
                        $("#peak-parking-day").text(data.peak_day);
                    }
                }, "json");
            }
            updateAnalytics();
            setInterval(updateAnalytics, 15000);
            setInterval(refreshClientLive, 3000);
            setInterval(updateMap, 1000);
            setInterval(updateClock, 1000);
            setInterval(pollGateEvents, 500);
        });
    </script>
</body>

</html>
