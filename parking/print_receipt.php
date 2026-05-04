<?php
// c:/xampp/htdocs/parking/print_receipt.php
include "config.php";
include "auth.php";

if (!isset($_GET["id"])) {
    die("Missing receipt ID");
}

$id = intval($_GET["id"]);

// Get the OUT record
$stmt = $conn->prepare("SELECT h.*, u.name, u.plate_number, u.rfid_uid 
    FROM parking_history h 
    JOIN users u ON h.user_id = u.id 
    WHERE h.id = ? AND h.action = 'OUT'");
$stmt->bind_param("i", $id);
$stmt->execute();
$out = $stmt->get_result()->fetch_assoc();

if (!$out) {
    die("Receipt not found");
}

// Find matching IN record
$in_stmt = $conn->prepare("SELECT timestamp FROM parking_history 
    WHERE user_id = ? AND action = 'IN' AND timestamp < ? 
    ORDER BY id DESC LIMIT 1");
$in_stmt->bind_param("is", $out["user_id"], $out["timestamp"]);
$in_stmt->execute();
$in = $in_stmt->get_result()->fetch_assoc();

$entry_time = $in ? $in["timestamp"] : "N/A";
$exit_time = $out["timestamp"];
$fee = $out["fee"];

// Duration
if ($in) {
    $d1 = new DateTime($entry_time);
    $d2 = new DateTime($exit_time);
    $diff = $d2->diff($d1);
    $duration = "";
    if ($diff->h > 0)
        $duration .= $diff->h . " jam ";
    $duration .= $diff->i . " menit";
} else {
    $duration = "-";
}

$rate = getSetting($conn, 'parking_rate', '3000');
$min_fee = getSetting($conn, 'min_fee', '3000');
$grace = getSetting($conn, 'grace_period', '15');
$billing_interval = max(1, intval(getSetting($conn, 'billing_interval_minutes', '10')));
$ref = "SF-" . str_pad($id, 6, "0", STR_PAD_LEFT);
$qr_data = urlencode("SpotFinder|Ref:$ref|" . $out["name"] . "|Rp$fee|$exit_time");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= $ref ?> | SpotFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=JetBrains+Mono&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .receipt {
            background: #fff;
            color: #1e293b;
            max-width: 380px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .receipt-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            padding: 25px;
            text-align: center;
        }

        .receipt-header h1 {
            font-size: 1.3rem;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }

        .receipt-header p {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .receipt-body {
            padding: 25px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.85rem;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            color: #64748b;
            font-size: 0.8rem;
        }

        .receipt-value {
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }

        .receipt-total {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .receipt-total .label {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .receipt-total .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4ade80;
        }

        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
        }

        .qr-section canvas {
            margin: 0 auto;
        }

        .qr-ref {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 8px;
        }

        .receipt-footer {
            text-align: center;
            padding: 15px;
            font-size: 0.7rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .actions button {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            transition: transform 0.2s;
        }

        .actions button:hover {
            transform: scale(1.05);
        }

        .btn-print {
            background: #0f172a;
            color: #fff;
        }

        .btn-back {
            background: #e2e8f0;
            color: #1e293b;
        }

        @media print {
            body {
                background: #fff;
            }

            .actions {
                display: none !important;
            }

            .receipt {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
        }
    </style>
</head>

<body>
    <div>
        <div class="receipt">
            <div class="receipt-header">
                <h1>🅿️ SPOTFINDER</h1>
                <p>Smart Parking Receipt</p>
            </div>

            <div class="receipt-body">
                <div class="receipt-row">
                    <span class="receipt-label">Nama</span>
                    <span class="receipt-value"><?= htmlspecialchars($out["name"]) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Plat Nomor</span>
                    <span class="receipt-value"><?= htmlspecialchars($out["plate_number"]) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Waktu Masuk</span>
                    <span class="receipt-value"><?= date("d/m/Y H:i", strtotime($entry_time)) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Waktu Keluar</span>
                    <span class="receipt-value"><?= date("d/m/Y H:i", strtotime($exit_time)) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Durasi</span>
                    <span class="receipt-value"><?= $duration ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Tarif</span>
                    <span class="receipt-value">Rp <?= number_format($rate, 0, ',', '.') ?>/<?= $billing_interval ?> menit</span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Aturan</span>
                    <span class="receipt-value">Grace <?= (int)$grace ?> menit, minimum Rp <?= number_format((int)$min_fee, 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="receipt-total">
                <span class="label">TOTAL BIAYA</span>
                <span class="amount">Rp <?= number_format($fee, 0, ',', '.') ?></span>
            </div>

            <div class="qr-section">
                <div id="qr-code"></div>
                <div class="qr-ref"><?= $ref ?></div>
            </div>

            <div class="receipt-footer">
                Terima kasih telah menggunakan SpotFinder 🅿️<br>
                <?= date("d/m/Y H:i:s") ?>
            </div>
        </div>

        <div class="actions">
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
            <button class="btn-back" onclick="history.back()"><i class="fas fa-arrow-left"></i> Kembali</button>
        </div>
    </div>

    <script>
        // Generate QR Code
        var qr = qrcode(0, 'M');
        qr.addData("<?= $qr_data ?>");
        qr.make();
        document.getElementById('qr-code').innerHTML = qr.createSvgTag(4, 0);
    </script>
</body>

</html>
