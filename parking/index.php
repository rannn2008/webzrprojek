<?php
// c:/xampp/htdocs/parking/index.php
include "config.php";
include "auth.php"; // Using session_start from here

$isAdmin = isLoggedIn();
$isClient = isClientLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Parking | Pro Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Emoji Animation */
        .emoji-flyer {
            position: fixed;
            pointer-events: none;
            z-index: 10000;
            font-size: 2.5rem;
            animation: emoji-float 3s ease-out forwards;
            opacity: 0;
        }

        @keyframes emoji-float {
            0% { transform: translateY(0) scale(0.5) rotate(0deg); opacity: 0; }
            20% { opacity: 1; transform: translateY(-30px) scale(1.3) rotate(15deg); }
            100% { transform: translateY(-200px) scale(1.6) rotate(30deg); opacity: 0; }
        }

        /* UI Polish */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 229, 255, 0.2);
        }

        /* AI Orb Animation */
        .ai-orb-container-mini {
            width: 40px;
            height: 40px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-orb-mini {
            width: 20px;
            height: 20px;
            background: var(--gradient-main);
            border-radius: 50%;
            position: relative;
            z-index: 2;
            box-shadow: 0 0 10px rgba(0, 229, 255, 0.5);
            transition: all 0.3s;
        }

        .ai-orb-ring-mini {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 1px solid var(--accent-primary);
            border-radius: 50%;
            animation: orb-pulse-mini 2s infinite;
            opacity: 0;
        }

        @keyframes orb-pulse-mini {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(2); opacity: 0; }
        }

        .ai-orb-mini.speaking {
            transform: scale(1.3);
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.8);
            background: #4ade80;
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
                    <h1><i class="fas fa-parking-circle"></i> SMART PARKING PRO</h1>
                    <p class="tagline">Next-Gen Parking Intelligence System</p>
                </div>
                <div style="display: flex; gap: 20px; align-items: center;">
                    <?php if ($isAdmin): ?>
                        <div style="text-align: right; border-right: 1px solid var(--glass-border); padding-right: 20px;">
                            <div style="font-size: 0.8rem; color: var(--accent-primary); font-weight: 600;">ADMIN:
                                <?= $_SESSION["admin_name"] ?>
                            </div>
                            <div style="display:flex; gap:10px; justify-content:flex-end;">
                                <a href="logout.php"
                                    style="font-size: 0.7rem; color: var(--danger); text-decoration: none;"><i
                                        class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    <?php elseif ($isClient): 
                        $cid = $_SESSION['client_id'];
                        $cuser = $conn->query("SELECT avatar FROM users WHERE id = $cid")->fetch_assoc();
                        $cavatar = !empty($cuser['avatar']) ? $cuser['avatar'] . "?t=" . time() : "assets/img/default-avatar.png";
                    ?>
                        <div style="display:flex; align-items:center; gap:12px; border-right: 1px solid var(--glass-border); padding-right: 20px;">
                            <img src="<?= $cavatar ?>" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:1px solid var(--accent-primary);">
                            <div style="text-align: right;">
                                <div style="font-size: 0.8rem; color: var(--accent-primary); font-weight: 600;">
                                    <?= $_SESSION["client_name"] ?>
                                </div>
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <a href="client_dashboard.php" style="font-size: 0.65rem; color: var(--success); text-decoration: none;">Dashboard</a>
                                    <span style="font-size:0.65rem; opacity:0.5;">|</span>
                                    <a href="client_profile.php" style="font-size: 0.65rem; color: var(--accent-primary); text-decoration: none;">Profile</a>
                                    <span style="font-size:0.65rem; opacity:0.5;">|</span>
                                    <a href="logout.php" style="font-size: 0.65rem; color: var(--danger); text-decoration: none;">Logout</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; gap: 10px;">
                            <a href="client_login.php" class="btn btn-success"
                                style="padding: 8px 15px; font-size: 0.8rem;"><i class="fas fa-car"></i> Client Portal</a>
                            <a href="login.php" class="btn btn-warning" style="padding: 8px 15px; font-size: 0.8rem;"><i
                                    class="fas fa-user-lock"></i> Admin</a>
                        </div>
                    <?php endif; ?>
                    <div class="clock-container">
                        <div id="digital-clock">00:00:00</div>
                        <div id="digital-date">Loading date...</div>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="ai-orb-container-mini" title="AI Voice Assistant Status">
                        <div class="ai-orb-ring-mini"></div>
                        <div id="ai-visualizer" class="ai-orb-mini"></div>
                    </div>
                    <button onclick="speakStatus()" class="btn-voice" title="Speak System Status">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn active"><i class="fas fa-chart-pie"></i> Public View</a>
                <?php if ($isAdmin): ?>
                    <a href="users.php" class="tab-btn"><i class="fas fa-user-shield"></i> Users & RFID</a>
                    <a href="history.php" class="tab-btn"><i class="fas fa-clock-rotate-left"></i> Full History</a>
                    <a href="analytics.php" class="tab-btn"><i class="fas fa-chart-line"></i> Analytics</a>
                    <a href="settings.php" class="tab-btn"><i class="fas fa-cog"></i> Settings</a>
                    <a href="admin_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
                <?php elseif ($isClient): ?>
                    <a href="client_dashboard.php" class="tab-btn"><i class="fas fa-gauge-high"></i> My Dashboard</a>
                    <a href="client_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 class="card-title"><i class="fas fa-microchip"></i> Real-time Slots</h2>
                    <div id="hw-status-badge" style="font-size:0.65rem; padding:4px 10px; border-radius:20px; font-weight:700; transition:all 0.3s ease;">
                        <i class="fas fa-circle" style="font-size:0.5rem; margin-right:4px;"></i> LOADING
                    </div>
                </div>
                <div class="slot-container" id="slot-refresher">
                    <p>Scanning signals...</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i
                            class="fas <?= $isAdmin ? "fa-hand-holding-dollar" : "fa-wave-square" ?>"></i>
                        <?= $isAdmin ? "Weekly Revenue (Rp)" : "Traffic Curve (7 Days)" ?></h2>
                </div>
                <div class="chart-container">
                    <canvas id="<?= $isAdmin ? "revenueChart" : "trafficCurve" ?>"></canvas>
                </div>
            </div>
        </div>

        <div class="stat-row" style="margin-bottom: 25px;">
            <?php if ($isAdmin): 
                $pending_count = $conn->query("SELECT COUNT(*) as c FROM topup_requests WHERE status='PENDING'")->fetch_assoc()['c'];
            ?>
            <div class="card" style="border-bottom: 4px solid var(--warning); cursor: pointer;" onclick="location.href='users.php'">
                <div class="stat-label"><i class="fas fa-money-bill-transfer"></i> Top-up Requests</div>
                <div class="stat-val" style="color:var(--warning)"><?= $pending_count ?> Requests</div>
                <div class="stat-label">Click to manage</div>
            </div>
            <?php endif; ?>

            <div class="card occupancy-card">
                <div class="gauge-wrapper">
                    <svg class="gauge-svg" viewBox="0 0 100 100">
                        <circle class="gauge-bg" cx="50" cy="50" r="45"></circle>
                        <circle class="gauge-fill" id="occupancy-fill" cx="50" cy="50" r="45" stroke-dasharray="283"
                            stroke-dashoffset="283"></circle>
                        <text id="occupancy-text" x="50" y="50" dominant-baseline="central" text-anchor="middle" class="gauge-text-svg">0%</text>
                    </svg>
                </div>
                <div>
                    <div class="stat-label">Capacity</div>
                    <div class="stat-val" id="occupancy-label">0/0 Slots</div>
                </div>
            </div>
            <div class="card">
                <?php if ($isAdmin): ?>
                    <div class="stat-label"><i class="fas fa-wallet"></i> Total Revenue</div>
                    <div class="stat-val" id="grand-revenue">Rp 0</div>
                    <div class="stat-label" style="font-size:0.6rem; color:#4ade80;">Total income since launch</div>
                <?php else: ?>
                    <div class="stat-label"><i class="fas fa-crown" style="color:#f59e0b;"></i> Member of the Month</div>
                    <div class="stat-val" style="font-size: 1.4rem;" id="top-user-name">-</div>
                    <div class="stat-label" style="font-size:0.6rem; color:#f59e0b;">Most active parking member</div>
                <?php endif; ?>
            </div>
            <div class="card">
                <div class="stat-label"><i class="fas fa-fire" style="color:#f87171;"></i> Peak Parking Day</div>
                <div class="stat-val" id="peak-day">-</div>
                <div class="stat-label" style="font-size:0.6rem; color:#f87171;">Busiest day for this location</div>
                <?php if ($isAdmin): ?>
                    <a href="analytics.php" style="display:block; margin-top:10px; font-size:0.65rem; color:var(--accent-primary); text-decoration:none; text-align:right;">Full Analytics <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list-ul"></i> Live Activity Feed</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>TimeStamp</th>
                            <th>Identity</th>
                            <th>Status / Duration</th>
                        </tr>
                    </thead>
                    <tbody id="activity-log">
                        <tr>
                            <td colspan="3" style="text-align:center;">Synchronizing logs...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <!-- GATE CONTROL (Admin Only) -->
            <div class="card" style="margin-top: 25px; border-left: 4px solid var(--accent-primary);">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-boom-gate"></i> Gate Control (Remote)</h2>
                </div>
                <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
                    <button onclick="sendGateCmd('OPEN')" class="btn btn-success"
                        style="flex:1; padding:15px; font-size:1rem;">
                        <i class="fas fa-lock-open"></i> BUKA PALANG
                    </button>
                    <button onclick="sendGateCmd('CLOSE')" class="btn btn-danger"
                        style="flex:1; padding:15px; font-size:1rem;">
                        <i class="fas fa-lock"></i> TUTUP PALANG
                    </button>
                    <div id="gate-status"
                        style="width:100%; text-align:center; font-size:0.8rem; color:var(--text-muted); padding:5px 0;">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div class="card" style="margin-top: 25px; border-left:4px solid #00e5ff;">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-comments"></i> Private Chat</h2>
                </div>
                <p style="color:var(--text-muted); margin-bottom:12px;">Buka halaman chat terpisah untuk percakapan admin-client lengkap.</p>
                <a href="admin_chat.php" class="btn btn-success" style="display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-arrow-up-right-from-square"></i> Buka Tab Chat Admin
                </a>
            </div>
        <?php elseif ($isClient): ?>
            <div class="card" style="margin-top: 25px; border-left:4px solid #4ade80;">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-comments"></i> Chat Support</h2>
                </div>
                <p style="color:var(--text-muted); margin-bottom:12px;">Chat support sekarang berada di halaman terpisah agar lebih fokus.</p>
                <a href="client_chat.php" class="btn btn-success" style="display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-arrow-up-right-from-square"></i> Buka Tab Chat Client
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let trafficChart, revenueChart;
        const isAdmin = <?= json_encode($isAdmin) ?>;
        let isDashboardLoading = false;
        let lastKnownId = 0;
        let lastBookedCount = -1;
        let lastPendingTopupCount = -1;
        let lastGateEventId = 0;

        function updateClock() {
            const now = new Date();
            $("#digital-clock").text(now.toLocaleTimeString("id-ID", { hour12: false }));
            $("#digital-date").text(now.toLocaleDateString("id-ID", { weekday: "long", year: "numeric", month: "long", day: "numeric" }));
        }



        function initCharts() {
            const tCtx = document.getElementById("trafficCurve")?.getContext("2d");
            if (tCtx) {
                trafficChart = new Chart(tCtx, {
                    type: "line",
                    data: { labels: [], datasets: [{ label: "Entries", data: [], borderColor: "#00e5ff", backgroundColor: "rgba(0, 229, 255, 0.1)", fill: true, tension: 0.4 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }
            const rCtx = document.getElementById("revenueChart")?.getContext("2d");
            if (rCtx) {
                revenueChart = new Chart(rCtx, {
                    type: "bar",
                    data: { labels: [], datasets: [{ label: "Revenue", data: [], backgroundColor: "rgba(74, 222, 128, 0.2)", borderColor: "#4ade80", borderWidth: 2 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }
        }

        function maskString(str, start = 2, end = 1) {
            if (!str || isAdmin) return str;
            if (str.length <= (start + end)) return str;
            return str.substring(0, start) + "***" + str.substring(str.length - end);
        }

        function updateAnalytics() {
            $.get("api_get_analytics.php", function (data) {
                // Update Traffic Curve
                if (trafficChart && data.traffic_chart) {
                    trafficChart.data.labels = data.traffic_chart.map(d => d.label);
                    trafficChart.data.datasets[0].data = data.traffic_chart.map(d => d.count);
                    trafficChart.update();
                }

                // Update Revenue Chart (Admin Only)
                if (revenueChart && data.revenue_chart) {
                    revenueChart.data.labels = data.revenue_chart.map(d => d.label);
                    revenueChart.data.datasets[0].data = data.revenue_chart.map(d => d.amount);
                    revenueChart.update();
                }

                // Update Stats
                $("#top-user-name").text(isAdmin ? (data.top_user?.name || "-") : maskString(data.top_user?.name));
                $("#peak-day").text(data.peak_day);
                
                if (isAdmin) {
                    $("#grand-revenue").text("Rp " + data.grand_total_revenue);
                }
            }, "json");
        }

        function updateDashboard() {
            if (isDashboardLoading) return;
            isDashboardLoading = true;

            $.ajax({
                url: "get_status.php",
                method: "GET",
                dataType: "json",
                cache: false
            }).done(function (data) {
                let slotsHtml = "";
                let occupiedCount = 0;
                data.sensors.forEach(slot => {
                    const status = parseInt(slot.status);
                    let slotClass = "slot-empty";
                    let iconClass = "fa-circle-check";
                    let statusLabel = "AVAILABLE";
                    
                    if (status == 1) {
                        slotClass = "slot-occupied";
                        iconClass = "fa-car-side";
                        statusLabel = "OCCUPIED";
                        occupiedCount++;
                    } else if (status == 2) {
                        slotClass = "slot-booked";
                        iconClass = "fa-clock";
                        statusLabel = "RESERVED";
                        occupiedCount++;
                    } else if (status == 3) {
                        slotClass = "slot-booked";
                        iconClass = "fa-clock";
                        statusLabel = "RESERVED";
                        occupiedCount++;
                    } else if (status == 4) {
                        slotClass = "slot-violation";
                        iconClass = "fa-triangle-exclamation";
                        statusLabel = "VIOLATION";
                        occupiedCount++;
                    }

                    slotsHtml += `<div class="slot ${slotClass}">
                        <div class="slot-icon"><i class="fas ${iconClass}"></i></div>
                        <h3 style="font-size: 0.8rem; color: #94a3b8;">BAY 0${slot.slot_id}</h3>
                        <div class="status" style="margin-top:5px; font-weight:700; font-size: 0.7rem;">${statusLabel}</div>
                    </div>`;
                });
                $("#slot-refresher").html(slotsHtml);

                // Setup baseline for gate events (prevent old voices on refresh)
                if (lastGateEventId === 0 && data.max_gate_event_id > 0) {
                    lastGateEventId = data.max_gate_event_id;
                    console.log("Baseline gate event set to: " + lastGateEventId);
                }

                // --- Hardware Status Badge ---
                const hwBadge = $("#hw-status-badge");
                if (data.is_hardware_online) {
                    const score = data.stability_score || 0;
                    let scoreColor = score > 80 ? '#4ade80' : (score > 50 ? '#f59e0b' : '#f87171');
                    hwBadge.html(`<i class="fas fa-circle" style="animation: pulse-green 2s infinite;"></i> ONLINE <span style="font-size:0.6rem; margin-left:5px; padding-left:5px; border-left:1px solid rgba(255,255,255,0.2);">Stability: <span style="color:${scoreColor}">${score}%</span></span>`);
                    hwBadge.css({ "background": "rgba(74, 222, 128, 0.15)", "color": "#4ade80", "border": "1px solid rgba(74, 222, 128, 0.3)" });
                } else {
                    hwBadge.html('<i class="fas fa-circle"></i> OFFLINE');
                    hwBadge.css({ "background": "rgba(148, 163, 184, 0.1)", "color": "#94a3b8", "border": "1px solid rgba(148, 163, 184, 0.2)" });
                }

                const totalSlots = data.total_slots || data.sensors.length || 0;
                const totalUsed = occupiedCount; // Already includes booked in my loop above
                let percent = totalSlots ? (totalUsed / totalSlots) * 100 : 0;
                $("#occupancy-fill").css("stroke-dashoffset", 283 - (283 * percent / 100));
                $("#occupancy-text").text(Math.round(percent) + "%");
                $("#occupancy-label").text(totalUsed + "/" + totalSlots + " Slots");

                let histHtml = "";
                data.history.forEach(row => {
                    let badge = row.action == "IN" ? "bg-success" : "bg-danger";
                    let duration = row.duration ? `<br><small style="color:var(--accent-primary)"><i class="fas fa-hourglass-half"></i> ${row.duration}</small>` : ""; let actionHtml = `<span class="badge ${badge}">${row.action}</span>${duration}`;
                    if (row.action === 'OUT') {
                        actionHtml += `<div style="margin-top:5px;"><a href="print_receipt.php?id=${row.id}" target="_blank" style="font-size:0.65rem; color:var(--accent-primary); text-decoration:none;"><i class="fas fa-print"></i> Receipt</a></div>`;
                    }

                    histHtml += `<tr>
                        <td style="font-family: monospace; color: var(--accent-primary);">${row.time}</td>
                        <td>
                            <div style="font-weight:600;">${isAdmin ? row.name : maskString(row.name)}</div>
                            <div style="font-size:0.7rem; color:var(--text-muted);">${isAdmin ? row.plate : maskString(row.plate, 2, 2)}</div>
                        </td>
                        <td>${actionHtml}</td>
                    </tr>`;
                });
                $("#activity-log").html(histHtml || "<tr><td colspan='3' style='text-align:center;'>No recent activity</td></tr>");
                
                if (lastKnownId === 0) lastKnownId = data.latest_id;
                else lastKnownId = data.latest_id;
                lastBookedCount = data.booked_count;

            }).always(function () {
                isDashboardLoading = false;
            });
        }

        // ========== GATE CONTROL ==========
        function sendGateCmd(cmd) {
            $.post("api_gate.php", { command: cmd }, function (data) {
                let color = cmd === 'OPEN' ? '#4ade80' : '#f87171';
                let icon = cmd === 'OPEN' ? '🔓' : '🔒';
                $("#gate-status").html(`<span style="color:${color}">${icon} Perintah ${cmd} terkirim!</span>`);
                setTimeout(() => $("#gate-status").html(""), 4000);
            }, "json").fail(function () {
                $("#gate-status").html('<span style="color:#f87171">⚠ Gagal mengirim perintah</span>');
            });
        }

        // ========== AI VOICE ASSISTANT ==========
        function speakStatus() {
            if (typeof playAiVoiceTts !== 'function') {
                alert("Maaf, fitur suara AI tidak tersedia.");
                return;
            }

            $.get("get_status.php", function (data) {
                const totalSlots = data.total_slots;
                const occupied = data.sensors.filter(s => s.status == 1).length;
                const booked = data.sensors.filter(s => s.status == 2).length;
                const online = data.is_hardware_online ? "Sistem saat ini online dan terhubung." : "Peringatan, sistem saat ini offline.";
                const revenue = $("#grand-revenue").text() || "nol rupiah";

                const text = `Halo Admin Zahran. ${online}. ` +
                    `Terdapat ${totalSlots} total slot parkir. ` +
                    `${occupied} slot sedang terisi, dan ${booked} slot telah dipesan. ` +
                    `Total pendapatan sistem saat ini adalah ${revenue}. ` +
                    `Semua sistem berjalan normal.`;

                $(".btn-voice i").addClass("fa-beat");
                playAiVoiceTts(text).then(() => {
                    $(".btn-voice i").removeClass("fa-beat");
                });
            }, "json");
        }
        
        // lastGateEventId already declared at the top of script
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

        function speakText(txt) {
            if (typeof playAiVoiceTts !== 'function') {
                return;
            }
            
            $(".ai-orb-ring").addClass("ai-speaking");
            playAiVoiceTts(txt).then(() => {
                $(".ai-orb-ring").removeClass("ai-speaking");
            });
        }

        function unlockAudio() {
            console.log("Admin Audio Unlocked.");
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('touchstart', unlockAudio);
        }
        document.addEventListener('click', unlockAudio);
        document.addEventListener('touchstart', unlockAudio);

        $(document).ready(function () {

            initCharts();
            updateDashboard();
            updateAnalytics();
            updateClock();
            setInterval(updateClock, 1000);
            setInterval(updateDashboard, 1000);
            setInterval(updateAnalytics, 5000);
            setInterval(pollGateEvents, 500);

        });
    </script>
</body>

</html>
