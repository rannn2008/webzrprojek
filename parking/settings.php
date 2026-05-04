<?php
// c:/xampp/htdocs/parking/settings.php
include "config.php";
include "auth.php";
restrictToAdmin();

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_settings"])) {
    $rate = intval($_POST["parking_rate"]);
    $min = intval($_POST["min_fee"]);
    $grace = intval($_POST["grace_period"]);
    $interval = max(1, intval($_POST["billing_interval_minutes"] ?? 10));

    if ($rate > 0) {
        $conn->query("UPDATE settings SET setting_value = '$rate' WHERE setting_key = 'parking_rate'");
        $conn->query("UPDATE settings SET setting_value = '$min' WHERE setting_key = 'min_fee'");
        $conn->query("UPDATE settings SET setting_value = '$grace' WHERE setting_key = 'grace_period'");
        $conn->query("UPDATE settings SET setting_value = '$interval' WHERE setting_key = 'billing_interval_minutes'");
        $msg = '<div class="badge bg-success" style="width:100%;padding:10px;margin-bottom:15px;">✅ Pengaturan berhasil disimpan!</div>';
    } else {
        $msg = '<div class="badge bg-danger" style="width:100%;padding:10px;margin-bottom:15px;">❌ Tarif harus lebih dari 0</div>';
    }
}

$rate = getSetting($conn, 'parking_rate', '3000');
$min_fee = getSetting($conn, 'min_fee', '3000');
$grace = getSetting($conn, 'grace_period', '15');
$billing_interval = getSetting($conn, 'billing_interval_minutes', '10');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | SpotFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-cog"></i> SETTINGS</h1>
                    <p class="tagline">Pengaturan Tarif Parkir</p>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-chart-pie"></i> Dashboard</a>
                <a href="users.php" class="tab-btn"><i class="fas fa-user-shield"></i> Users</a>
                <a href="settings.php" class="tab-btn active"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
            </div>
        </div>

        <?= $msg ?>

        <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
            <!-- Rate Settings -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-money-bill-wave"></i> Tarif Parkir</h2>
                    <div id="settings-live-status" style="font-size:0.75rem; color:var(--success); font-weight:600;">LIVE</div>
                </div>
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-coins"></i> Tarif per Interval (Rp)
                        </label>
                        <input type="number" id="parking_rate_input" name="parking_rate" value="<?= $rate ?>" class="form-control" min="1000"
                            required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-hourglass-half"></i> Interval Tagihan (menit)
                        </label>
                        <input type="number" id="billing_interval_input" name="billing_interval_minutes"
                            value="<?= $billing_interval ?>" class="form-control" min="1" required>
                        <small style="color:var(--text-muted);font-size:0.7rem;">Contoh: 10 = biaya bertambah setiap 10 menit</small>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-tag"></i> Biaya Minimum (Rp)
                        </label>
                        <input type="number" id="min_fee_input" name="min_fee" value="<?= $min_fee ?>" class="form-control" min="0"
                            required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-size:0.85rem; color:var(--text-muted);">
                            <i class="fas fa-clock"></i> Grace Period (menit gratis)
                        </label>
                        <input type="number" id="grace_period_input" name="grace_period" value="<?= $grace ?>" class="form-control" min="0"
                            required>
                        <small style="color:var(--text-muted);font-size:0.7rem;">Menit awal gratis sebelum mulai
                            dihitung</small>
                    </div>
                    <button type="submit" name="save_settings" class="btn btn-success"
                        style="width:100%; padding:12px; font-size:1rem;">
                        <i class="fas fa-save"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>

            <!-- Preview -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-calculator"></i> Simulasi Biaya</h2>
                </div>
                <div style="font-size:0.85rem; color:var(--text-muted);">
                    <p>Berdasarkan pengaturan saat ini:</p>
                    <table class="table" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th>Durasi</th>
                                <th style="text-align:right">Biaya</th>
                            </tr>
                        </thead>
                        <tbody id="fee-preview-body">
                            <?php
                            $g = intval($grace);
                            $r = intval($rate);
                            $m = intval($min_fee);
                            $i = max(1, intval($billing_interval));
                            $durations = [10, 20, 30, 60, 120, 180, 300, 480];
                            foreach ($durations as $mins) {
                                $cost = calculateParkingFee($mins, $r, $m, $g, $i);
                                $label = $mins < 60 ? $mins . " menit" : ($mins / 60) . " jam";
                                echo "<tr><td>$label</td><td style='text-align:right;font-weight:700;color:var(--accent-primary)'>Rp " . number_format($cost, 0, ',', '.') . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Troubleshooting Section -->
        <div class="card" style="margin-top: 20px; border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);">
            <div class="card-header">
                <h2 class="card-title" style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Troubleshooting & Perbaikan Bug</h2>
            </div>
            <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom: 15px; line-height: 1.5;">
                <p>Fitur ini digunakan jika terjadi <b>bug atau nyangkut</b> pada sistem parkir Anda. Contoh kasus:</p>
                <ul style="margin-top:5px; margin-left: 20px;">
                    <li>Mobil sudah keluar, tetapi LED merah di model parkiran (ESP32) masih menyala.</li>
                    <li>Status di web/dashboard masih menunjukkan "Terisi / Occupied" padahal sudah kosong.</li>
                    <li>Palang macet atau sistem error.</li>
                </ul>
                <p style="margin-top:5px; font-weight:bold; color: #f87171;">Dengan menekan tombol di bawah ini, sistem akan mereset paksa seluruh database slot ke status "KOSONG" dan merestart/reboot ulang alat fisik ESP32 secara jarak jauh.</p>
            </div>
            <button type="button" class="btn btn-danger" style="padding:12px; font-size:1rem; width:100%; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3); font-weight:600; letter-spacing:0.5px;" onclick="rebootSystem()">
                <i class="fas fa-power-off" style="margin-right:5px;"></i> RESET DATABASE & RESTART ESP32
            </button>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function formatRupiah(v) {
            return "Rp " + Number(v || 0).toLocaleString("id-ID");
        }

        function calcCost(mins, rate, minFee, grace, interval) {
            // Completely free only if parking duration is precisely 0, or inside a non-zero grace period
            if (mins <= 0 || (grace > 0 && mins <= grace)) return 0;
            const unit = Math.max(1.0, Number(interval || 1));
            // Simulate the float math used in backend
            let billableUnits = Math.ceil((mins - grace) / unit);
            if (billableUnits < 1) billableUnits = 1;
            return Math.max(billableUnits * rate, minFee);
        }

        function renderPreview(rate, minFee, grace, interval) {
            const durations = [10, 20, 30, 60, 120, 180, 300, 480];
            const rows = durations.map(mins => {
                const label = mins < 60 ? `${mins} menit` : `${mins / 60} jam`;
                const cost = calcCost(mins, rate, minFee, grace, interval);
                return `<tr><td>${label}</td><td style="text-align:right;font-weight:700;color:var(--accent-primary)">${formatRupiah(cost)}</td></tr>`;
            }).join("");
            $("#fee-preview-body").html(rows);
        }

        function getInputValues() {
            return {
                rate: Number($("#parking_rate_input").val() || 0),
                minFee: Number($("#min_fee_input").val() || 0),
                grace: Number($("#grace_period_input").val() || 0),
                interval: Number($("#billing_interval_input").val() || 1)
            };
        }

        function anySettingInputFocused() {
            const active = document.activeElement;
            return active && ["parking_rate_input", "min_fee_input", "grace_period_input", "billing_interval_input"].includes(active.id);
        }

        function applyServerSettings(data) {
            if (!anySettingInputFocused()) {
                $("#parking_rate_input").val(data.parking_rate);
                $("#min_fee_input").val(data.min_fee);
                $("#grace_period_input").val(data.grace_period);
                $("#billing_interval_input").val(data.billing_interval_minutes || 10);
            }
            const vals = getInputValues();
            renderPreview(vals.rate, vals.minFee, vals.grace, vals.interval);
            $("#settings-live-status").text("LIVE • " + (data.server_time || ""));
        }

        function pollSettings() {
            $.ajax({
                url: "api_get_settings.php",
                method: "GET",
                dataType: "json",
                cache: false
            }).done(applyServerSettings);
        }

        $(document).ready(function () {
            const vals = getInputValues();
            renderPreview(vals.rate, vals.minFee, vals.grace, vals.interval);
            $("#parking_rate_input,#min_fee_input,#grace_period_input,#billing_interval_input").on("input", function () {
                const nowVals = getInputValues();
                renderPreview(nowVals.rate, nowVals.minFee, nowVals.grace, nowVals.interval);
            });
            pollSettings();
            setInterval(pollSettings, 2000);
        });

        function rebootSystem() {
            if(!confirm('PERINGATAN!\n\nAnda yakin ingin mereset state server dan me-restart ESP32?\nPastikan slot parkir fisik benar-benar telah kosong dari kendaraan.')) return;
            $.post("api_gate.php", { command: "REBOOT" }, function(res) {
                if(res.success) {
                    alert('Berhasil! Database telah dikosongkan dan Modul ESP32 akan otomatis restart dalam 1-2 detik.');
                } else {
                    alert('Gagal: ' + res.message);
                }
            }, "json").fail(function() {
                alert('Gagal menghubungi server.');
            });
        }
    </script>
</body>

</html>
