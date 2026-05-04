<?php
require_once '../config/config.php';

$order_code = $_GET['kode'] ?? '';

if (empty($order_code)) {
    header("Location: ../index.php");
    exit();
}

// Ambil detail pesanan
$sql = "SELECT o.*, 
        (SELECT GROUP_CONCAT(CONCAT(nama_product, ' (', quantity, 'x)') SEPARATOR ', ') 
         FROM order_items WHERE order_id = o.id) as items
        FROM orders o 
        WHERE o.order_code = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header("Location: ../index.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $order_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    mysqli_stmt_close($stmt);
    header("Location: ../index.php");
    exit();
}

$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil! - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Canvas Confetti -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --primary: #00C897;
            --primary-dark: #019267;
            --secondary: #FF6B6B;
            --accent: #FFD166;
            --dark: #1A1A2E;
            --light: #F8F9FA;
            --gray: #6C757D;
            --success: #4CAF50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-circles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 0;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }
        
        .circle:nth-child(1) {
            width: 150px;
            height: 150px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .circle:nth-child(2) {
            width: 100px;
            height: 100px;
            top: 60%;
            right: 15%;
            animation-delay: 3s;
        }
        
        .circle:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 20%;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-50px) scale(1.1); }
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* Success Card */
        .success-card {
            background: white;
            border-radius: 30px;
            padding: 60px 50px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Success Icon */
        .success-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success), #45a049);
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: bounceIn 1s ease-out;
            box-shadow: 0 15px 40px rgba(76, 175, 80, 0.3);
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .success-icon i {
            font-size: 4rem;
            color: white;
        }
        
        .success-title {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .success-subtitle {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 40px;
        }
        
        /* Order Code Badge */
        .order-code {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px 35px;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px rgba(0, 200, 151, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Order Timeline */
        .order-timeline {
            background: var(--light);
            border-radius: 20px;
            padding: 30px;
            margin: 40px 0;
            text-align: left;
        }
        
        .timeline-title {
            font-size: 1.3rem;
            margin-bottom: 25px;
            text-align: center;
            color: var(--dark);
            font-weight: 600;
        }
        
        .timeline-step {
            display: flex;
            align-items: center;
            padding: 20px;
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 29px;
            top: 70px;
            width: 2px;
            height: 100%;
            background: #e0e0e0;
        }
        
        .timeline-step.active::after {
            background: var(--primary);
        }
        
        .timeline-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e0e0e0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 20px;
            position: relative;
            z-index: 2;
        }
        
        .timeline-step.active .timeline-icon {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            animation: ping 1.5s infinite;
        }
        
        .timeline-step.completed .timeline-icon {
            background: var(--success);
        }
        
        @keyframes ping {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 200, 151, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(0, 200, 151, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 200, 151, 0);
            }
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-content h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .timeline-content p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Order Details */
        .order-details {
            background: var(--light);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-dark);
        }
        
        .detail-label {
            color: var(--gray);
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 40px;
        }
        
        .btn {
            padding: 18px 30px;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 200, 151, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-wa {
            background: #25D366;
            color: white;
            border: none;
        }
        
        .btn-wa:hover {
            background: #20BA5A;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
        }
        
        /* Social Share */
        .share-buttons {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }
        
        .share-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 15px;
        }
        
        .social-btns {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .social-btn:hover {
            transform: translateY(-5px);
        }
        
        .social-btn.facebook {
            background: #1877f2;
        }
        
        .social-btn.twitter {
            background: #1da1f2;
        }
        
        .social-btn.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        /* Client Success Notification */
        .client-notif-window {
            position: fixed;
            top: 18px;
            right: 18px;
            width: min(360px, calc(100vw - 26px));
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
            z-index: 4000;
            border: 1px solid rgba(0, 200, 151, 0.25);
            animation: notifSlideIn 0.45s ease;
        }

        @keyframes notifSlideIn {
            from {
                transform: translateY(-16px) scale(0.96);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .client-notif-head {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 12px 15px;
            font-size: 0.92rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .client-notif-body {
            padding: 13px 14px 14px;
        }

        .client-notif-body p {
            color: #374151;
            font-size: 0.86rem;
            line-height: 1.45;
            margin-bottom: 10px;
        }

        .client-notif-play {
            border: none;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.83rem;
            font-weight: 700;
            cursor: pointer;
            color: white;
            background: #10b981;
            width: 100%;
            transition: all 0.25s ease;
        }

        .client-notif-play:hover {
            background: #0f9f70;
        }

        .client-notif-play.secondary {
            background: #2563eb;
        }

        .client-notif-play.secondary:hover {
            background: #1e4fc2;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .success-card {
                padding: 40px 30px;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .order-code {
                font-size: 1.2rem;
                padding: 12px 25px;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }

            .client-notif-window {
                right: 10px;
                left: 10px;
                top: 10px;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Background Circles -->
    <div class="bg-circles">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div id="clientOrderNotif" class="client-notif-window">
        <div class="client-notif-head">
            <i class="fas fa-bell"></i> Notifikasi Pesanan
        </div>
        <div class="client-notif-body">
            <p id="clientNotifText">Pesanan berhasil dibuat. Notifikasi suara sedang diputar...</p>
            <button id="clientNotifPlayBtn" class="client-notif-play" onclick="playClientSuccessAudio(true)">
                Putar Suara Notifikasi
            </button>
        </div>
    </div>
    
    <div class="container">
        <div class="success-card">
            <!-- Success Icon -->
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <!-- Success Message -->
            <h1 class="success-title">🎉 Pesanan Berhasil!</h1>
            <p class="success-subtitle">Terima kasih atas pesanan Anda. Kami akan segera memprosesnya.</p>
            
            <!-- Order Code -->
            <div class="order-code">
                <i class="fas fa-receipt"></i> <?php echo $order_code; ?>
            </div>
            
            <!-- Order Timeline -->
            <div class="order-timeline">
                <h3 class="timeline-title">🕐 Status Pesanan Anda</h3>
                
                <div class="timeline-step completed">
                    <div class="timeline-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Pesanan Diterima</h4>
                        <p><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="timeline-step active">
                    <div class="timeline-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Menunggu Konfirmasi</h4>
                        <p>Admin akan mengkonfirmasi pesanan Anda</p>
                    </div>
                </div>
                
                <div class="timeline-step">
                    <div class="timeline-icon">
                        <i class="fas fa-blender"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Sedang Diproses</h4>
                        <p>Pesanan Anda sedang disiapkan</p>
                    </div>
                </div>
                
                <div class="timeline-step">
                    <div class="timeline-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Dalam Pengiriman</h4>
                        <p>Estimasi: 30-45 menit</p>
                    </div>
                </div>
                
                <div class="timeline-step">
                    <div class="timeline-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Selesai</h4>
                        <p>Selamat menikmati!</p>
                    </div>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Nama:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['nama_customer']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">WhatsApp:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['whatsapp']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['alamat'] ?? 'Tidak ada'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Item:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['items']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode Bayar:</span>
                    <span class="detail-value"><?php echo strtoupper($order['metode_bayar'] ?? 'COD'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pengiriman:</span>
                    <span class="detail-value"><?php echo($order['metode_pengiriman'] ?? 'pickup') === 'delivery' ? 'Delivery' : 'Jemput Sendiri'; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Pembayaran:</span>
                    <span class="detail-value">Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="https://wa.me/6281234567890?text=Halo,%20saya%20ingin%20konfirmasi%20pesanan%20<?php echo $order_code; ?>" 
                   class="btn btn-wa" target="_blank">
                    <i class="fab fa-whatsapp"></i> Hubungi Admin
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Pesan Lagi
                </a>
            </div>
            
            <!-- Social Share -->
            <div class="share-buttons">
                <p class="share-title">Bagikan pengalaman Anda:</p>
                <div class="social-btns">
                    <div class="social-btn facebook" onclick="shareToFacebook()">
                        <i class="fab fa-facebook-f"></i>
                    </div>
                    <div class="social-btn twitter" onclick="shareToTwitter()">
                        <i class="fab fa-twitter"></i>
                    </div>
                    <div class="social-btn instagram">
                        <i class="fab fa-instagram"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!isset($_SESSION['customer_logged_in'])): ?>
            <!-- Guest Signup Prompt -->
            <div class="signup-prompt" style="margin-top: 30px; background: white; border-radius: 20px; padding: 30px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); animation: slideUp 1s ease-out;">
                <div style="width: 60px; height: 60px; background: #e8f5e9; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 1.5rem;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 style="color: var(--dark); margin-bottom: 10px;">Simpan Pesanan Ini?</h3>
                <p style="color: var(--gray); margin-bottom: 20px;">Buat akun sekarang untuk melacak pesanan ini dan memesan lebih cepat di masa depan.</p>
                <a href="register.php" class="btn btn-primary" style="display: inline-flex; width: auto;">
                    <i class="fas fa-arrow-right"></i> Buat Akun & Simpan History
                </a>
            </div>
        <?php
endif; ?>
    </div>

    <audio id="clientSuccessAudio" preload="auto" src="../assets/sounds/pesanan-berhasil-diproses.mp3"></audio>

    <script>
        // Confetti Animation on Load
        function launchConfetti() {
            const duration = 3 * 1000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };
            
            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }
            
            const interval = setInterval(function() {
                const timeLeft = animationEnd - Date.now();
                
                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }
                
                const particleCount = 50 * (timeLeft / duration);
                
                confetti(Object.assign({}, defaults, {
                    particleCount,
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
                }));
                confetti(Object.assign({}, defaults, {
                    particleCount,
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
                }));
            }, 250);
        }

        function setClientNotifMessage(message, canReplay = false) {
            const msgEl = document.getElementById('clientNotifText');
            const btnEl = document.getElementById('clientNotifPlayBtn');
            if (msgEl) {
                msgEl.innerText = message;
            }
            if (btnEl) {
                btnEl.innerText = canReplay ? 'Putar Ulang Suara' : 'Putar Suara Notifikasi';
                btnEl.classList.toggle('secondary', canReplay);
            }
        }

        function playClientSuccessAudio(fromUserAction = false) {
            const audioEl = document.getElementById('clientSuccessAudio');
            if (!audioEl) return;

            if (fromUserAction) {
                audioEl.currentTime = 0;
            }

            const playPromise = audioEl.play();
            if (!playPromise || typeof playPromise.then !== 'function') {
                setClientNotifMessage('Notifikasi suara aktif.', true);
                return;
            }

            playPromise
                .then(() => {
                    setClientNotifMessage('Notifikasi suara aktif.', true);
                })
                .catch(() => {
                    setClientNotifMessage('Browser memblokir autoplay. Klik tombol untuk memutar suara notifikasi.', false);
                });
        }
        
        // Launch confetti on page load
        window.addEventListener('load', function() {
            setTimeout(launchConfetti, 500);
            setTimeout(() => playClientSuccessAudio(false), 300);
        });
        
        // Social Share Functions
        function shareToFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + url, '_blank');
        }
        
        function shareToTwitter() {
            const text = encodeURIComponent('Saya baru saja memesan Es Teller di Pondok Es Teller ZR! 🍨');
            const url = encodeURIComponent(window.location.href);
            window.open('https://twitter.com/intent/tweet?text=' + text + '&url=' + url, '_blank');
        }
        
        // Clear cart from localStorage + simpan kode pesanan terakhir untuk notifikasi status di dashboard
        localStorage.removeItem('cart_esteller');
        localStorage.setItem('pending_order_code', '<?php echo $order_code; ?>');
        
        // Auto-reload timeline status
        let currentStep = 1;
        setInterval(function() {
            // This is just a demo - in production, fetch real status from server
            console.log('Checking order status...');
        }, 10000);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
