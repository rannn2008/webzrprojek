<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Unauthorized access');
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die('Invalid order ID');
}

// Ambil data order
$order = fetch_one(secure_query($conn, "SELECT * FROM orders WHERE id = ?", "i", [$order_id]));

if (!$order) {
    die('Order not found');
}

// Ambil items
$items_result = secure_query($conn, "SELECT * FROM order_items WHERE order_id = ?", "i", [$order_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - <?php echo $order['order_code']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            padding: 30px;
            color: #1A1A2E;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #00C897 0%, #019267 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .order-code {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #1A1A2E;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-label {
            color: #6C757D;
            font-weight: 500;
        }
        
        .info-value {
            color: #1A1A2E;
            font-weight: 600;
            text-align: right;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-new, .status-baru {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-process, .status-proses {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-done, .status-selesai {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancel, .status-batal {
            background: #f8d7da;
            color: #721c24;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .total-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 1.1rem;
        }
        
        .grand-total {
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #019267;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-print {
            background: linear-gradient(to right, #4dabf7, #339af0);
            color: white;
        }
        
        .btn-whatsapp {
            background: linear-gradient(to right, #25D366, #128C7E);
            color: white;
        }
        
        .btn-close {
            background: #6C757D;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        @media print {
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-receipt"></i> Detail Pesanan</h1>
            <div class="order-code"><?php echo $order['order_code']; ?></div>
        </div>
        
        <div class="content">
            <!-- Customer Info -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-user"></i> Informasi Customer
                </h2>
                <div class="info-row">
                    <span class="info-label">Nama Customer:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['nama_customer']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">WhatsApp:</span>
                    <span class="info-value">
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp']); ?>" 
                           target="_blank" 
                           style="color: #25D366; text-decoration: none;">
                            <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($order['whatsapp']); ?>
                        </a>
                    </span>
                </div>
                <?php if (!empty($order['catatan'])): ?>
                <div class="info-row">
                    <span class="info-label">Catatan:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['catatan']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Info -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i> Informasi Pesanan
                </h2>
                <div class="info-row">
                    <span class="info-label">Tanggal Pesanan:</span>
                    <span class="info-value"><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php 
                                $status_text = [
                                    'new' => 'Baru',
                                    'baru' => 'Baru',
                                    'process' => 'Diproses',
                                    'proses' => 'Diproses',
                                    'done' => 'Selesai',
                                    'selesai' => 'Selesai',
                                    'cancel' => 'Dibatalkan',
                                    'batal' => 'Dibatalkan'
                                ];
                                echo $status_text[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-shopping-bag"></i> Item Pesanan
                </h2>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items_result): ?>
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nama_product']); ?></td>
                            <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?>x</td>
                            <td><strong>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="total-section">
                    <div class="total-row grand-total">
                        <span>Total Pembayaran</span>
                        <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="action-buttons">
                <button class="btn btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp']); ?>?text=Halo%20<?php echo urlencode($order['nama_customer']); ?>,%20pesanan%20Anda%20dengan%20kode%20<?php echo $order['order_code']; ?>%20telah%20diterima!" 
                   target="_blank" 
                   style="flex: 1; text-decoration: none;">
                    <button class="btn btn-whatsapp" style="width: 100%;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Customer
                    </button>
                </a>
                <button class="btn btn-close" onclick="window.close()">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
