<?php
require_once '../config/config.php';
require_once '../includes/db_helper.php';
require_once '../api/order_receipt_helper.php';

ensure_order_receipts_table($conn);

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// =====================================================
// HANDLE AJAX ACTIONS
// =====================================================
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'accept') {
        $order_id = intval($_POST['order_id']);
        secure_query($conn, "UPDATE orders SET status = 'process', updated_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$order_id], false);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'preparing') {
        $order_id = intval($_POST['order_id']);
        secure_query($conn, "UPDATE orders SET status = 'preparing', updated_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$order_id], false);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'ready') {
        $order_id = intval($_POST['order_id']);
        secure_query($conn, "UPDATE orders SET status = 'ready', updated_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$order_id], false);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'done') {
        $order_id = intval($_POST['order_id']);

        // --- LOYALTY SYSTEM: Award Points ---
        $q_order = secure_query($conn, "SELECT customer_id, total_harga, status FROM orders WHERE id = ?", "i", [$order_id]);
        $order_data = fetch_one($q_order);

        $current_status = strtolower((string) ($order_data['status'] ?? ''));
        if ($order_data && !in_array($current_status, ['done', 'selesai'], true)) {
            $customer_id = $order_data['customer_id'];
            $new_points = floor($order_data['total_harga'] / 10000); // 1 point per 10k
            if ($customer_id) {
                secure_query($conn, "UPDATE customers SET points = points + ? WHERE id = ?", "ii", [$new_points, $customer_id], false);
            }
        }
        // ------------------------------------

        secure_query($conn, "UPDATE orders SET status = 'done', updated_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$order_id], false);
        $receipt = ensure_order_receipt($conn, $order_id, 'admin', false);
        if (!$receipt['success']) {
            echo json_encode(['success' => false, 'message' => 'Pesanan selesai tetapi gagal membuat struk: ' . $receipt['error']]);
            exit();
        }

        echo json_encode(['success' => true, 'receipt_code' => $receipt['receipt_code']]);
        exit();
    }

    if ($action === 'reject') {
        $order_id = intval($_POST['order_id']);
        $alasan = $_POST['alasan'];
        secure_query($conn, "UPDATE orders SET status = 'cancel', alasan_batal = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", "si", [$alasan, $order_id], false);
        echo json_encode(['success' => true]);
        exit();
    }
}

// =====================================================
// STATISTIK DASHBOARD
// =====================================================
$total_orders = fetch_one(secure_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'];
$new_orders = fetch_one(secure_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('new','baru','') OR status IS NULL"))['total'];
$total_products = fetch_one(secure_query($conn, "SELECT COUNT(*) as total FROM products WHERE is_deleted = 0"))['total'];
$total_customers = fetch_one(secure_query($conn, "SELECT COUNT(DISTINCT nama_customer) as total FROM orders"))['total'];
$total_done = fetch_one(secure_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('done','selesai')"))['total'];

// Query untuk produk
$products_query = secure_query($conn, "SELECT * FROM products WHERE is_deleted = 0 ORDER BY created_at DESC");

// =====================================================
// ANALYTICS DATA FETCHING
// =====================================================

// 1. Revenue last 7 days
$revenue_7days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($date));
    $q = secure_query($conn, "SELECT SUM(total_harga) as total FROM orders WHERE DATE(created_at) = ? AND status = 'done'", "s", [$date]);
    $res = fetch_one($q);
    $revenue_7days[] = [
        'label' => $label,
        'total' => $res['total'] ?? 0
    ];
}

// 2. Top 5 Products
$top_products = fetch_all(secure_query($conn, "
    SELECT pi.nama_product, SUM(pi.quantity) as total_qty 
    FROM order_items pi 
    JOIN orders o ON pi.order_id = o.id 
    WHERE o.status = 'done' 
    GROUP BY pi.nama_product 
    ORDER BY total_qty DESC 
    LIMIT 5
"));

// 2.1 Global Rating Stats
$global_rating = fetch_one(secure_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews"));
$avg_shop_rating = number_format($global_rating['avg_rating'] ?: 0, 1);
$total_shop_reviews = $global_rating['total_reviews'];

// 2.2 List of Reviews for Review Page
$q_all_reviews = secure_query($conn, "
    SELECT r.*, c.nama as customer_name, c.foto_profil, o.order_code
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.id 
    LEFT JOIN orders o ON r.order_id = o.id 
    ORDER BY r.created_at DESC
");
$all_reviews = fetch_all($q_all_reviews);

// 3. Order Status Distribution
$status_dist = [];
$q_status = secure_query($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = mysqli_fetch_assoc($q_status)) {
    $status_dist[$row['status'] ?: 'new'] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap"
        rel="stylesheet">
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- SheetJS for Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        .chat-history {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #fdfdfd;
        }

        .cmsg {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 15px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
        }

        .cmsg.customer {
            align-self: flex-start;
            background: #f1f3f5;
            color: var(--dark);
            border-bottom-left-radius: 4px;
        }

        .cmsg.admin {
            align-self: flex-end;
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .cmsg-time {
            font-size: 0.7rem;
            opacity: 0.7;
            display: block;
            margin-top: 6px;
            text-align: right;
        }

        .chat-input-area {
            padding: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            background: white;
            display: flex;
            gap: 15px;
        }

        .chat-input-area input {
            flex: 1;
            padding: 15px 20px;
            border-radius: 30px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: #f8f9fa;
            font-size: 1rem;
            outline: none;
            transition: var(--transition);
        }

        .chat-input-area input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
        }

        .chat-input-area button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-input-area button:hover {
            transform: scale(1.05);
            background: var(--primary-dark);
            box-shadow: 0 5px 15px rgba(139, 90, 43, 0.3);
        }

        .product-modal-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
            align-items: start;
        }

        .product-image-preview-wrap {
            border: 2px dashed rgba(139, 90, 43, 0.25);
            border-radius: 14px;
            padding: 10px;
            background: #faf7f2;
        }

        .product-image-preview {
            width: 100%;
            height: 180px;
            border-radius: 10px;
            object-fit: cover;
            background: #f0f0f0;
            display: block;
        }

        .product-switch {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 6px;
            font-size: 0.9rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .product-switch input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        @media (max-width: 768px) {
            .product-modal-grid {
                grid-template-columns: 1fr;
            }

            .product-image-preview {
                height: 150px;
            }
        }

        .order-countdown-chip {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff8e1;
            color: #8d6e63;
            border: 1px solid #ffe0b2;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .order-notif-window {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: min(390px, calc(100vw - 20px));
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(139, 90, 43, 0.2);
            box-shadow: 0 20px 45px rgba(62, 39, 35, 0.2);
            z-index: 2600;
            overflow: hidden;
            display: none;
            animation: orderNotifIn .35s ease;
        }

        @keyframes orderNotifIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .order-notif-head {
            padding: 14px 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .order-notif-title {
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-notif-count {
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .order-notif-controls {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .order-notif-controls input {
            width: 64px;
            border: 1px solid rgba(139, 90, 43, 0.35);
            border-radius: 8px;
            padding: 7px 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .order-notif-controls button {
            border: none;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-notif-save {
            background: rgba(139, 90, 43, 0.14);
            color: var(--primary-dark);
        }

        .btn-notif-sound {
            margin-left: auto;
            background: #e8f5e9;
            color: #1b5e20;
        }

        .btn-notif-sound.off {
            background: #ffebee;
            color: #c62828;
        }

        .order-notif-list {
            max-height: 330px;
            overflow-y: auto;
            padding: 10px 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .order-notif-item {
            border: 1px solid rgba(139, 90, 43, 0.18);
            border-radius: 12px;
            padding: 10px;
            background: #fff;
        }

        .order-notif-item.urgent {
            border-color: #ef5350;
            background: #fff5f5;
        }

        .order-notif-item h4 {
            font-size: 0.92rem;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .order-notif-item p {
            color: var(--text-muted);
            font-size: 0.78rem;
            margin-bottom: 6px;
        }

        .order-notif-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            font-size: 0.76rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .order-notif-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .order-notif-actions .btn {
            font-size: 0.78rem;
            padding: 8px;
        }

        @media (max-width: 768px) {
            .order-notif-window {
                right: 10px;
                left: 10px;
                bottom: 12px;
                width: auto;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo">
                <img src="../assets/images/products/logozr.png" alt="Logo">
                <span>Pondok Es Teller ZR</span>
            </div>
        </div>
        <div class="menu">
            <div class="menu-item active" onclick="showPage('dashboard', this)">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </div>
            <div class="menu-item" onclick="showPage('orders', this)">
                <i class="fas fa-shopping-bag"></i> <span>Pesanan</span>
                <span class="badge" id="newOrdersBadge" <?php echo $new_orders > 0 ? '' : 'style="display:none;"'; ?>>
                    <?php echo $new_orders; ?>
                </span>
            </div>
            <div class="menu-item" onclick="showPage('reviews', this)">
                <i class="fas fa-star"></i> <span>Ulasan</span>
                <?php if ($total_shop_reviews > 0): ?><span class="badge"
                        style="background:#FF9800;"><?php echo $total_shop_reviews; ?></span><?php
                endif; ?>
            </div>
            <div class="menu-item" onclick="showPage('chats', this)">
                <i class="fas fa-comment-dots"></i> <span>Pesan</span>
                <span class="badge" id="adminGlobalChatBadge" style="background:#ef4444; display:none;">0</span>
            </div>
            <div class="menu-item" onclick="showPage('products', this)">
                <i class="fas fa-coffee"></i> <span>Produk</span>
            </div>
            <div class="menu-item" onclick="if(confirm('Logout?')) location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1 id="pageTitle">Overview</h1>
                <p id="pageSub">Ringkasan aktivitas hari ini</p>
            </div>
            <div class="user-profile" onclick="location.href='profile.php'">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?></div>
                <span style="font-weight:600; font-size:0.9rem;"><?php echo $_SESSION['admin_username']; ?></span>
            </div>
        </div>

        <!-- DASHBOARD PAGE -->
        <div id="dashboard" class="page-content active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-head">
                        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                        <span class="stat-lbl" style="color:var(--secondary);">Perlu Proses</span>
                    </div>
                    <div class="stat-val" id="newOrdersStat"><?php echo $new_orders; ?></div>
                    <div class="stat-lbl">Pesanan Baru</div>
                </div>
                <div class="stat-card">
                    <div class="stat-head">
                        <div class="stat-icon" style="background:rgba(255,145,0,0.1); color:var(--secondary);"><i
                                class="fas fa-check-circle"></i></div>
                        <span class="stat-lbl">Total</span>
                    </div>
                    <div class="stat-val"><?php echo $total_orders; ?></div>
                    <div class="stat-lbl">Semua Pesanan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-head">
                        <div class="stat-icon" style="background:rgba(33,150,243,0.1); color:#2196F3;"><i
                                class="fas fa-users"></i></div>
                        <span class="stat-lbl">Pelanggan</span>
                    </div>
                    <div class="stat-val"><?php echo $total_customers; ?></div>
                    <div class="stat-lbl">Total Customer</div>
                </div>
                <div class="stat-card">
                    <div class="stat-head">
                        <div class="stat-icon" style="background:rgba(46,125,50,0.1); color:#2E7D32;"><i
                                class="fas fa-check-circle"></i></div>
                        <span class="stat-lbl">Selesai</span>
                    </div>
                    <div class="stat-val"><?php echo $total_done; ?></div>
                    <div class="stat-lbl">Pesanan Selesai</div>
                </div>

                <div class="stat-card" onclick="showPage('reviews', document.querySelector('[onclick*=\'reviews\']'))"
                    style="cursor:pointer; border: 1px solid rgba(255, 179, 0, 0.2);">
                    <div class="stat-head">
                        <div class="stat-icon" style="background: rgba(255, 179, 0, 0.1); color: #FFB300;"><i
                                class="fas fa-star"></i></div>
                        <span class="stat-lbl">Rating Toko</span>
                    </div>
                    <div class="stat-val"><?php echo $avg_shop_rating; ?><span
                            style="font-size:0.9rem; color:var(--gray); font-weight:400;">/5.0</span></div>
                    <div class="stat-lbl"><?php echo $total_shop_reviews; ?> Ulasan Pelanggan</div>
                </div>
            </div>

            <!-- Activity & Charts Section -->
            <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 25px;">
                <!-- Revenue Chart -->
                <div style="background:white; border-radius:var(--radius); padding:25px; box-shadow: var(--shadow);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 style="font-size:1.1rem; color:var(--dark);"><i class="fas fa-chart-line"
                                style="color:var(--primary); margin-right:8px;"></i> Tren Penjualan (7 Hari Terakhir)
                        </h3>
                        <span style="font-size:0.8rem; color:var(--gray);">Status: Selesai</span>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Top Products Chart -->
                <div style="background:white; border-radius:var(--radius); padding:25px; box-shadow: var(--shadow);">
                    <h3 style="font-size:1.1rem; color:var(--dark); margin-bottom:20px;"><i class="fas fa-crown"
                            style="color:var(--secondary); margin-right:8px;"></i> Produk Terlaris</h3>
                    <div style="height: 300px; position: relative;">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                    <div id="topProductsList" style="margin-top: 15px;">
                        <!-- Legend will be here -->
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 25px; margin-top: 25px;">
                <!-- Status Distribution Card -->
                <div style="background:white; border-radius:var(--radius); padding:25px; box-shadow: var(--shadow);">
                    <h3 style="font-size:1.1rem; color:var(--dark); margin-bottom:20px;"><i class="fas fa-tasks"
                            style="color:var(--primary-dark); margin-right:8px;"></i> Distribusi Status</h3>
                    <div style="height: 250px; position: relative;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Quick Export & Reports Card -->
                <div
                    style="background:white; border-radius:var(--radius); padding:25px; box-shadow: var(--shadow); display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                    <div
                        style="width:70px; height:70px; background:rgba(139,90,43,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:2rem; margin-bottom:20px;">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <h3 style="font-size:1.3rem; margin-bottom:10px;">Laporan Penjualan</h3>
                    <p style="color:var(--gray); margin-bottom:20px; max-width: 300px;">Unduh rekap data pesanan lengkap
                        dalam format Excel untuk pembukuan.</p>
                    <button class="btn btn-primary" onclick="exportToExcel()" style="padding: 12px 30px;">
                        <i class="fas fa-download"></i> Unduh Data Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- ORDERS PAGE (CARD LAYOUT) -->
        <div id="orders" class="page-content">
            <div class="cards-grid">
                <?php
                $q_orders = secure_query($conn, "SELECT o.*, c.foto_profil, c.nama as cust_fallback, r.receipt_code, COUNT(oi.id) as items_count 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id 
                LEFT JOIN order_receipts r ON r.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                GROUP BY o.id 
                ORDER BY o.created_at DESC");

                if (mysqli_num_rows($q_orders) > 0) {
                    while ($o = mysqli_fetch_assoc($q_orders)) {
                        $st = !empty($o['status']) ? strtolower($o['status']) : 'new';
                        $status_class = 'st-new';
                        $status_text = 'Baru';

                        if ($st == 'process') {
                            $status_class = 'st-process';
                            $status_text = 'Diterima';
                        } elseif ($st == 'preparing') {
                            $status_class = 'st-preparing';
                            $status_text = 'Diracik';
                        } elseif ($st == 'ready') {
                            $status_class = 'st-ready';
                            $status_text = 'Siap';
                        } elseif ($st == 'done') {
                            $status_class = 'st-done';
                            $status_text = 'Selesai';
                        } elseif ($st == 'cancel') {
                            $status_class = 'st-cancel';
                            $status_text = 'Batal';
                        }

                        $wa_clean = preg_replace('/[^0-9]/', '', $o['whatsapp'] ?? '');
                        if (isset($wa_clean[0]) && $wa_clean[0] == '0')
                            $wa_clean = '62' . substr($wa_clean, 1);
                        ?>
                        <div class="admin-card" id="card-order-<?php echo $o['id']; ?>" data-order-id="<?php echo $o['id']; ?>"
                            data-order-status="<?php echo $st; ?>"
                            data-order-created-at="<?php echo htmlspecialchars($o['created_at']); ?>">
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>

                            <div class="ac-header">
                                <div
                                    style="width:50px; height:50px; background:#f0f0f0; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; border: 2px solid var(--primary-light);">
                                    <?php if (!empty($o['foto_profil'])): ?>
                                        <img src="../assets/images/profiles/<?php echo $o['foto_profil']; ?>" alt="Profile"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php
                                    else: ?>
                                        <div style="font-weight:700; color:var(--gray);">
                                            <?php
                                            $initial_name = $o['nama_customer'] ?: ($o['cust_fallback'] ?: 'P');
                                            echo strtoupper(substr($initial_name, 0, 1));
                                            ?>
                                        </div>
                                        <?php
                                    endif; ?>
                                </div>
                                <div class="ac-title">
                                    <h3><?php echo htmlspecialchars($o['nama_customer'] ?: ($o['cust_fallback'] ?: 'Pelanggan')); ?>
                                    </h3>
                                    <span><?php echo $o['order_code']; ?></span>
                                    <?php if (!empty($o['receipt_code'])): ?>
                                        <div style="font-size:0.72rem; color:#1565C0; font-weight:700; margin-top:3px;">
                                            <i class="fas fa-file-invoice"></i> <?php echo htmlspecialchars($o['receipt_code']); ?>
                                        </div>
                                        <?php
                                    endif; ?>
                                </div>
                            </div>

                            <div class="ac-actions">
                                <?php if ($st == 'new' || $st == 'baru'): ?>
                                    <div class="order-countdown-chip"
                                        data-countdown-created-at="<?php echo htmlspecialchars($o['created_at']); ?>">
                                        <i class="fas fa-hourglass-half"></i>
                                        <span>Auto-terima dalam <strong data-countdown-value>--:--</strong></span>
                                    </div>
                                    <div
                                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; grid-column: 1 / -1;">
                                        <button class="btn btn-primary"
                                            onclick="updateOrder(<?php echo $o['id']; ?>, 'accept')">Terima</button>
                                        <button class="btn btn-danger"
                                            onclick="openRejectModal(<?php echo $o['id']; ?>)">Tolak</button>
                                    </div>
                                    <?php
                                elseif ($st == 'process'): ?>
                                    <button class="btn btn-primary btn-full"
                                        onclick="updateOrder(<?php echo $o['id']; ?>, 'preparing')">Mulai Diracik</button>
                                    <?php
                                elseif ($st == 'preparing'): ?>
                                    <button class="btn btn-primary btn-full"
                                        onclick="updateOrder(<?php echo $o['id']; ?>, 'ready')">Siap Diambil</button>
                                    <?php
                                elseif ($st == 'ready'): ?>
                                    <?php if (($o['metode_pengiriman'] ?? 'pickup') === 'delivery'): ?>
                                        <button class="btn btn-primary btn-full"
                                            onclick="updateOrder(<?php echo $o['id']; ?>, 'done')">Selesaikan (Delivery)</button>
                                        <?php
                                    else: ?>
                                        <div class="btn btn-warning btn-full" style="cursor:default;">Menunggu konfirmasi pickup
                                            customer</div>
                                        <?php
                                    endif; ?>
                                    <?php
                                endif; ?>
                                <button class="btn btn-info btn-full" onclick="showDetail(<?php echo $o['id']; ?>)"
                                    style="margin-top:5px;">Detail</button>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div style="grid-column:1/-1; text-align:center; padding:50px; color:var(--gray);">Belum ada pesanan</div>';
                }
                ?>
            </div>
        </div>

        <!-- REVIEWS PAGE -->
        <div id="reviews" class="page-content">
            <div class="cards-grid">
                <?php if (empty($all_reviews)): ?>
                    <div
                        style="background:white; padding:40px; border-radius:var(--radius); text-align:center; grid-column:1/-1;">
                        <i class="fas fa-comment-slash" style="font-size:3rem; color:#ddd; margin-bottom:15px;"></i>
                        <p style="color:var(--gray);">Belum ada ulasan dari pelanggan.</p>
                    </div>
                    <?php
                else: ?>
                    <?php foreach ($all_reviews as $r): ?>
                        <div class="admin-card">
                            <div class="ac-header">
                                <div
                                    style="width:45px; height:45px; background:#f0f0f0; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; border: 2px solid var(--primary-light);">
                                    <?php if (!empty($r['foto_profil'])): ?>
                                        <img src="../assets/images/profiles/<?php echo $r['foto_profil']; ?>"
                                            style="width:100%; height:100%; object-fit:cover;">
                                        <?php
                                    else: ?>
                                        <i class="fas fa-user" style="color:var(--gray);"></i>
                                        <?php
                                    endif; ?>
                                </div>
                                <div class="ac-title">
                                    <h3 style="font-size:1rem;"><?php echo htmlspecialchars($r['customer_name']); ?></h3>
                                    <div style="display:flex; gap:3px; color:#FFB300; font-size:0.85rem;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $r['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                            <?php
                                        endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin: 15px 0; padding:12px; background:rgba(139,90,43,0.03); border-radius:10px;">
                                <div style="font-size:0.75rem; color:var(--gray); margin-bottom:5px;">Pesanan:</div>
                                <div style="font-weight:600; color:var(--dark); font-size:0.9rem;">
                                    <?php echo htmlspecialchars($r['order_code'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div style="font-size:0.9rem; color:var(--dark); line-height:1.5; font-style:italic;">
                                "<?php echo htmlspecialchars($r['comment']); ?>"
                            </div>
                            <div style="margin-top:15px; font-size:0.75rem; color:var(--gray); text-align:right;">
                                <?php echo date('d M Y, H:i', strtotime($r['created_at'])); ?>
                            </div>
                        </div>
                        <?php
                    endforeach; ?>
                    <?php
                endif; ?>
            </div>
        </div>

        <!-- CHATS PAGE -->
        <div id="chats" class="page-content">
            <div class="chat-container">
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h3>Pesan Pelanggan</h3>
                    </div>
                    <div class="chat-list" id="adminChatList">
                        <div style="text-align:center; padding:30px; color:var(--gray);">Memuat daftar chat...</div>
                    </div>
                </div>
                <div class="chat-main">
                    <div class="chat-header" id="activeChatHeader" style="display:none;">
                        <div class="chat-avatar" id="activeChatAvatar"></div>
                        <div class="chat-info">
                            <h4 id="activeChatName"></h4>
                            <small id="activeChatOrderInfo">Order</small>
                        </div>
                    </div>
                    <div class="chat-history" id="adminChatHistory">
                        <div style="text-align:center; padding:50px; color:var(--gray);">Pilih pelanggan untuk memulai
                            percakapan.</div>
                    </div>
                    <div class="chat-input-area" id="adminChatInputArea" style="display:none;">
                        <input type="hidden" id="activeCustomerId">
                        <input type="hidden" id="activeOrderId">
                        <input type="text" id="adminChatInput" placeholder="Ketik pesan..."
                            onkeypress="if(event.key === 'Enter') sendAdminChatMessage()">
                        <button onclick="sendAdminChatMessage()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRODUCTS PAGE (CARD LAYOUT) -->
        <div id="products" class="page-content">
            <div class="cards-grid">
                <!-- Add New Card -->
                <div class="admin-card"
                    style="border:2px dashed var(--primary); display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; min-height:300px;"
                    onclick="openProductModal()">
                    <div
                        style="width:60px; height:60px; background:rgba(139,90,43,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.5rem; margin-bottom:15px;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3 style="color:var(--primary);">Tambah Produk</h3>
                </div>

                <?php
                if (mysqli_num_rows($products_query) > 0) {
                    // Reset pointer
                    mysqli_data_seek($products_query, 0);
                    while ($p = mysqli_fetch_assoc($products_query)) {
                        $imgAttr = (!empty($p['gambar']) && file_exists('../assets/images/products/' . $p['gambar']))
                            ? 'src="../assets/images/products/' . $p['gambar'] . '"'
                            : 'src="https://via.placeholder.com/150?text=No+Image"';
                        ?>
                        <div class="admin-card">
                            <div style="position:relative;">
                                <img <?php echo $imgAttr; ?>
                                    style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:15px;">
                                <span class="status-badge"
                                    style="top:10px; right:10px; background:<?php echo $p['tersedia'] ? '#E8F5E9' : '#FFEBEE'; ?>; color:<?php echo $p['tersedia'] ? '#2E7D32' : '#C62828'; ?>">
                                    <?php echo $p['tersedia'] ? 'Tersedia' : 'Habis'; ?>
                                </span>
                            </div>

                            <h3 style="font-size:1.1rem; margin-bottom:5px;"><?php echo $p['nama']; ?></h3>
                            <p style="color:var(--gray); font-size:0.85rem; margin-bottom:15px;"><?php echo $p['kategori']; ?>
                            </p>

                            <h2 style="color:var(--primary-dark); margin-bottom:20px;">Rp
                                <?php echo number_format($p['harga'], 0, ',', '.'); ?>
                            </h2>

                            <div class="ac-actions">
                                <button class="btn btn-warning"
                                    onclick='editProduct(<?php echo json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'><i
                                        class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-danger" onclick="deleteProduct(<?php echo $p['id']; ?>)"><i
                                        class="fas fa-trash"></i> Hapus</button>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- FLOATING ACTION BUTTON (Mobile Only) -->
    <button class="fab" onclick="openProductModal()" style="display:none;" id="fabMain">
        <i class="fas fa-plus"></i>
    </button>

    <!-- JAVASCRIPT LOGIC -->

    <!-- ORDER DETAIL MODAL -->
    <div id="orderDetailModal" class="modal-ov">
        <div class="modal-content" style="max-width: 600px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:1.5rem;">Detail Pesanan</h2>
                <button onclick="document.getElementById('orderDetailModal').style.display='none'"
                    style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div id="orderDetailContent">
                <!-- Content loaded via AJAX -->
                <div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div style="margin-top:20px; text-align:right;">
                <button class="btn btn-danger"
                    onclick="document.getElementById('orderDetailModal').style.display='none'">Tutup</button>
            </div>
        </div>
    </div>

    <!-- REJECT REASON MODAL -->
    <div id="rejectModal" class="modal-ov">
        <div class="modal-content">
            <h3>Tolak Pesanan</h3>
            <p style="margin-bottom:15px; color:var(--gray);">Silakan masukkan alasan penolakan pesanan ini.</p>
            <input type="hidden" id="rejectOrderId">
            <div class="form-group">
                <label style="font-size: 0.9rem; margin-bottom: 5px; display: block; color: var(--text-muted);">Pilih
                    Alasan Utama:</label>
                <select id="rejectReasonSelect" class="form-control" style="margin-bottom: 15px;"
                    onchange="toggleCustomReason()">
                    <option value="">-- Pilih Alasan --</option>
                    <option value="Mohon maaf, stok bahan sedang kosong.">Stok bahan sedang kosong</option>
                    <option value="Mohon maaf, toko sedang overload/sibuk.">Toko sedang overload/sibuk</option>
                    <option value="Mohon maaf, jam operasional sudah hampir tutup.">Jam operasional sudah hampir tutup
                    </option>
                    <option value="Lokasi pengiriman terlalu jauh/tidak terjangkau.">Lokasi pengiriman terlalu jauh
                    </option>
                    <option value="custom">Alasan Lainnya (Ketik sendiri)...</option>
                </select>
                <textarea id="rejectReason" class="form-control" rows="3"
                    placeholder="Ketik alasan spesifik penolakan..." style="display: none;"></textarea>
            </div>

            <script>
                function toggleCustomReason() {
                    const sel = document.getElementById('rejectReasonSelect');
                    const txt = document.getElementById('rejectReason');
                    if (sel.value === 'custom') {
                        txt.style.display = 'block';
                        txt.focus();
                    } else {
                        txt.style.display = 'none';
                        txt.value = sel.value;
                    }
                }
            </script>
            <div class="ac-actions">
                <button class="btn btn-danger" onclick="submitReject()">Tolak Pesanan</button>
                <button class="btn" style="background:#eee; color:#333;"
                    onclick="document.getElementById('rejectModal').style.display='none'">Batal</button>
            </div>
        </div>
    </div>

    <!-- ADD / EDIT PRODUCT MODAL -->
    <div id="productModal" class="modal-ov">
        <div class="modal-content" style="max-width:780px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <h2 id="productModalTitle" style="font-size:1.35rem; color:var(--dark);">Tambah Produk</h2>
                <button onclick="closeProductModal()"
                    style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>

            <form id="productForm" onsubmit="submitProductForm(event)">
                <input type="hidden" name="id" id="productId">
                <input type="hidden" name="gambar_existing" id="productGambarExisting">

                <div class="product-modal-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" name="nama" id="productNama" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Harga (Rp)</label>
                            <input type="number" min="1" step="1" class="form-control" name="harga" id="productHarga"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="kategori" id="productKategori"
                                placeholder="Contoh: es teller, es buah" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="4" name="deskripsi" id="productDeskripsi"
                                placeholder="Deskripsi singkat menu"></textarea>
                        </div>
                    </div>

                    <div>
                        <div class="product-image-preview-wrap">
                            <img id="productImagePreview" class="product-image-preview"
                                src="https://via.placeholder.com/600x400?text=Preview+Produk" alt="Preview Produk">
                        </div>
                        <div class="form-group" style="margin-top:12px;">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control" name="gambar" id="productGambar" accept="image/*">
                            <small style="color:var(--gray); font-size:0.78rem;">Maksimal 2MB (JPG, PNG, GIF,
                                WEBP)</small>
                        </div>
                        <label class="product-switch">
                            <input type="checkbox" name="tersedia" id="productTersedia" checked>
                            Produk tersedia
                        </label>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px;">
                    <button type="button" class="btn" style="background:#eee; color:#333;"
                        onclick="closeProductModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="productSubmitBtn">
                        <i class="fas fa-save"></i> Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ORDER NOTIFICATION WINDOW -->
    <div id="orderNotifWindow" class="order-notif-window">
        <div class="order-notif-head">
            <div class="order-notif-title">
                <i class="fas fa-bell"></i> Pesanan Baru Masuk
            </div>
            <div class="order-notif-count" id="orderNotifCount">0</div>
        </div>
        <div class="order-notif-controls">
            <span style="font-size:0.76rem; color:var(--gray);">Auto-terima (menit)</span>
            <input type="number" id="autoAcceptMinutesInput" min="1" max="30" step="1" value="3">
            <button class="btn-notif-save" onclick="saveAutoAcceptMinutes()">Simpan</button>
            <button class="btn-notif-sound" id="toggleOrderSoundBtn" onclick="toggleOrderAlertSound()">Suara ON</button>
        </div>
        <div id="orderNotifList" class="order-notif-list"></div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // Data injected from PHP for Charts
        window.initCharts = function (colors) {
            // 1. Revenue Chart
            const revenueData = <?php echo json_encode($revenue_7days); ?>;
            const revCanvas = document.getElementById('revenueChart');
            if (revCanvas) {
                new Chart(revCanvas, {
                    type: 'line',
                    data: {
                        labels: revenueData.map(d => d.label),
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: revenueData.map(d => d.total),
                            borderColor: colors.primary,
                            backgroundColor: 'rgba(139, 90, 43, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: colors.primary,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // 2. Top Products Chart
            const topProdData = <?php echo json_encode($top_products); ?>;
            const topProdCanvas = document.getElementById('topProductsChart');
            if (topProdCanvas) {
                new Chart(topProdCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: topProdData.map(d => d.nama_product),
                        datasets: [{
                            data: topProdData.map(d => d.total_qty),
                            backgroundColor: [
                                '#8b5a2b', '#c19a6b', '#d2a679', '#e6ccb8', '#a67c52'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, padding: 20, font: { family: 'Outfit', size: 11 } }
                            }
                        }
                    }
                });
            }

            // 3. Status Chart
            const statusDist = <?php echo json_encode($status_dist); ?>;
            const statusCanvas = document.getElementById('statusChart');
            if (statusCanvas) {
                new Chart(statusCanvas, {
                    type: 'bar',
                    data: {
                        labels: ['Baru', 'Proses', 'Selesai', 'Batal'],
                        datasets: [{
                            data: [
                                (statusDist['new'] || 0) + (statusDist['baru'] || 0) + (statusDist[''] || 0),
                                statusDist['process'] || 0,
                                statusDist['done'] || 0,
                                statusDist['cancel'] || 0
                            ],
                            backgroundColor: ['#1565C0', '#EF6C00', '#2E7D32', '#C62828'],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        };

        // Excel Export Function (requires PHP data)
        function exportToExcel() {
            const wb = XLSX.utils.book_new();
            const ordersRaw = <?php
                $q_all = secure_query($conn, "SELECT id, order_code, nama_customer, total_harga, status, created_at FROM orders ORDER BY created_at DESC");
                $all_orders = fetch_all($q_all);
                echo json_encode($all_orders);
            ?>;
            const ws = XLSX.utils.json_to_sheet(ordersRaw);
            XLSX.utils.book_append_sheet(wb, ws, "Semua Pesanan");
            XLSX.writeFile(wb, "Laporan_Penjualan_CoffeeZR.xlsx");
        }
    </script>
</body>

</html>