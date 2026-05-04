<?php
require_once '../config/config.php';
require_once '../includes/db_helper.php';
require_once '../api/order_receipt_helper.php';

ensure_order_receipts_table($conn);

// Cek login customer
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

// --- Handle Review Submission (1 review per order) ---
if (isset($_POST['submit_review'])) {
    $order_id = intval($_POST['order_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment'] ?? '');

    // Verify the order belongs to this customer and is done
    $verify = secure_query($conn, "SELECT id FROM orders WHERE id = ? AND customer_id = ? AND status IN ('done','selesai')", "ii", [$order_id, $customer_id]);
    
    if ($verify && $verify->num_rows > 0) {
        // Check if review already exists for this order
        $check = secure_query($conn, "SELECT id FROM reviews WHERE customer_id = ? AND order_id = ?", "ii", [$customer_id, $order_id]);
        
        if ($check && $check->num_rows > 0) {
            // Update existing review
            secure_query($conn, "UPDATE reviews SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ? AND order_id = ?", "isii", [$rating, $comment, $customer_id, $order_id]);
        } else {
            // Insert new review
            secure_query($conn, "INSERT INTO reviews (customer_id, order_id, rating, comment) VALUES (?, ?, ?, ?)", "iiis", [$customer_id, $order_id, $rating, $comment]);
        }

        header("Location: customer_dashboard.php?review_success=1");
        exit();
    }
}

// Get Customer Data
$customer = fetch_one(secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$customer_id]));

// Get customer orders + online receipt data
$orders_query = secure_query($conn, "SELECT 
                                    o.*,
                                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as total_items,
                                    r.receipt_code,
                                    r.generated_at as receipt_generated_at,
                                    r.pickup_confirmed_at
                                FROM orders o 
                                LEFT JOIN order_receipts r ON r.order_id = o.id
                                WHERE o.customer_id = ? 
                                ORDER BY o.created_at DESC", "i", [$customer_id]);

// Get existing reviews for this customer (keyed by order_id)
$customer_reviews = [];
$q_reviews = secure_query($conn, "SELECT * FROM reviews WHERE customer_id = ? AND order_id IS NOT NULL", "i", [$customer_id]);
if ($q_reviews) {
    while ($rev = $q_reviews->fetch_assoc()) {
        $customer_reviews[$rev['order_id']] = $rev;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5a2b;
            --primary-dark: #5c3a18;
            --primary-light: #c19a6b;
            --secondary: #d2a679;
            --accent: #e6ccb8;
            --dark: #3e2723;
            --light: #fdfbf7;
            --gray: #9E9E9E;
            --shadow: 0 10px 30px rgba(139, 90, 43, 0.08);
            --radius: 16px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        
        .navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark);
        }
        
        .nav-brand h2 { font-size: 1.3rem; }
        .nav-brand span { color: var(--primary); }
        
        .nav-links { display: flex; gap: 25px; align-items: center; }
        
        .nav-links a {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
            background: rgba(139, 90, 43, 0.05);
        }
        
        .nav-links a.btn-logout {
            background: var(--secondary);
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius);
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h1 { font-size: 1.8rem; margin-bottom: 8px; }
        
        .order-progress {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            position: relative;
            padding: 0 10px;
        }
        .order-progress::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 20px;
            right: 20px;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
        }
        .progress-line {
            position: absolute;
            top: 15px;
            left: 20px;
            height: 4px;
            background: var(--primary);
            z-index: 2;
            transition: width 0.8s ease;
        }
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 3;
            width: 60px;
        }
        .step-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 8px;
        }
        .progress-step.active .step-icon {
            border-color: var(--primary);
            color: var(--primary);
        }
        .progress-step.completed .step-icon {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .step-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gray);
            text-align: center;
        }
        
        .btn-order {
            background: white;
            color: var(--primary-dark);
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }
        
        .section-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }
        
        .order-item {
            padding: 20px;
            background: #fdfbf7;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid rgba(139, 90, 43, 0.05);
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-new { background: #E3F2FD; color: #1565C0; }
        .status-process { background: #FFF3E0; color: #EF6C00; }
        .status-ready { background: #E0F2F1; color: #00796B; }
        .status-done { background: #E8F5E9; color: #2E7D32; }
        .status-cancel { background: #FFEBEE; color: #C62828; }
        
        .order-btn {
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }
        
        .client-status-notif {
            position: fixed;
            top: 18px;
            right: 18px;
            width: 360px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.2);
            z-index: 3000;
            display: none;
        }
        .client-status-notif.show { display: block; }
        
        @media (max-width: 968px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../index.php" class="nav-brand">
            <h2>Pondok <span>Es Teller</span></h2>
        </a>
        <div class="nav-links">
            <a href="../index.php"><i class="fas fa-home"></i> Beranda</a>
            <a href="customer_dashboard.php" class="active"><i class="fas fa-user"></i> Dashboard</a>
            <a href="customer_logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div id="clientStatusNotif" class="client-status-notif">
        <div id="clientStatusHead" style="padding:15px; color:#fff; border-radius:14px 14px 0 0;">
            <span id="clientStatusTitle">Update Pesanan</span>
        </div>
        <div id="clientStatusBody" style="padding:15px;"></div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Halo, <?php echo htmlspecialchars(explode(' ', $customer['nama'])[0]); ?>!</h1>
                <p>Loyalty Points: <?php echo number_format($customer['points'] ?? 0, 0, ',', '.'); ?> PTS</p>
            </div>
            <a href="../index.php" class="btn-order">Pesan Sekarang</a>
        </div>
        
        <div class="dashboard-grid">
            <div class="section-card">
                <h3>Pesanan Saya</h3>
                <?php if ($orders_query && $orders_query->num_rows > 0): ?>
                    <?php while ($order = $orders_query->fetch_assoc()): 
                        $st = strtolower($order['status'] ?? 'new');
                        $step = 1;
                        if ($st === 'process' || $st === 'diterima') $step = 2;
                        elseif ($st === 'preparing' || $st === 'diracik') $step = 3;
                        elseif ($st === 'ready' || $st === 'siap') $step = 4;
                        elseif ($st === 'done' || $st === 'selesai') $step = 5;
                    ?>
                        <div class="order-item">
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                <strong><?php echo htmlspecialchars($order['order_code']); ?></strong>
                                <span class="order-status"><?php echo $st; ?></span>
                            </div>
                            <p>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></p>
                            <div class="order-actions" style="margin-top:10px;">
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="order-btn">Detail</a>
                                <?php if ($st === 'ready'): ?>
                                    <button class="order-btn" onclick="confirmPickup(<?php echo $order['id']; ?>)">Sudah Diambil</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Belum ada pesanan.</p>
                <?php endif; ?>
            </div>
            
            <div class="section-card">
                <h3>Profil Saya</h3>
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($customer['nama']); ?></p>
                <p><strong>WhatsApp:</strong> <?php echo htmlspecialchars($customer['whatsapp']); ?></p>
                <a href="edit_profile.php" class="order-btn" style="margin-top:15px;">Edit Profil</a>
            </div>
        </div>
    </div>

    <script>
        function confirmPickup(id) {
            if(confirm('Sudah ambil pesanan?')) {
                fetch('../api/confirm_pickup.php', {
                    method: 'POST',
                    body: new URLSearchParams({'order_id': id})
                }).then(r => r.json()).then(d => {
                    if(d.success) location.reload();
                    else alert(d.error);
                });
            }
        }
    </script>
</body>
</html>
