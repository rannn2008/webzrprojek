<?php
require_once '../config/config.php';
require_once '../api/order_receipt_helper.php';

ensure_order_receipts_table($conn);

if (!isset($_GET['id'])) {
    die('ID pesanan tidak ditemukan');
}

$order_id = intval($_GET['id']);
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_customer = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true && isset($_SESSION['customer_id']);

if (!$is_admin && !$is_customer) {
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Get order info
$order = fetch_one(secure_query($conn, "SELECT o.*, r.receipt_code, r.generated_at as receipt_generated_at, r.generated_by, r.pickup_confirmed_at
              FROM orders o
              LEFT JOIN order_receipts r ON r.order_id = o.id
              WHERE o.id = ?
              LIMIT 1", "i", [$order_id]));

if (!$order) {
    die('Pesanan tidak ditemukan');
}

if ($is_customer && intval($order['customer_id']) !== intval($_SESSION['customer_id'])) {
    die('Akses ditolak untuk struk pesanan ini.');
}

$status = strtolower((string)($order['status'] ?? ''));
if (!in_array($status, ['done', 'selesai'], true)) {
    die('Struk online tersedia setelah pesanan selesai.');
}

if (empty($order['receipt_code'])) {
    $generated_by = $is_admin ? 'admin' : 'customer';
    $make_receipt = ensure_order_receipt($conn, $order_id, $generated_by, false);
    if (!$make_receipt['success']) {
        die('Gagal membuat struk online: ' . $make_receipt['error']);
    }

    $order = fetch_one(secure_query($conn, "SELECT o.*, r.receipt_code, r.generated_at as receipt_generated_at, r.generated_by, r.pickup_confirmed_at
                  FROM orders o
                  LEFT JOIN order_receipts r ON r.order_id = o.id
                  WHERE o.id = ?
                  LIMIT 1", "i", [$order_id]));
}

// Get order items
$items_result = secure_query($conn, "SELECT * FROM order_items WHERE order_id = ?", "i", [$order_id]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Struk Pesanan - <?php echo htmlspecialchars($order['order_code']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 10px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { font-size: 18px; font-weight: bold; }
        .header p { font-size: 11px; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 5px 0; border-bottom: 1px solid #ddd; }
        td { padding: 5px 0; }
        .right { text-align: right; }
        .total { font-weight: bold; font-size: 14px; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
        .meta-box { background: #f5f5f5; border: 1px dashed #777; padding: 8px; margin: 8px 0; }
        @media print {
            body { width: 80mm; }
            button { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PONDOK ES TELLER ZR</h1>
        <p>Jl. Kalumbuk NO.21, Padang</p>
        <p>Telp: 0813-7411-0444</p>
    </div>
    
    <div class="divider"></div>

    <div class="meta-box">
        <p><strong>Struk Online:</strong> <?php echo htmlspecialchars($order['receipt_code']); ?></p>
        <p><strong>Dibuat:</strong> <?php echo !empty($order['receipt_generated_at']) ? date('d/m/Y H:i', strtotime($order['receipt_generated_at'])) : '-'; ?></p>
        <p><strong>Oleh:</strong> <?php echo strtoupper(htmlspecialchars($order['generated_by'] ?? 'system')); ?></p>
        <?php if (!empty($order['pickup_confirmed_at'])): ?>
            <p><strong>Pickup Confirmed:</strong> <?php echo date('d/m/Y H:i', strtotime($order['pickup_confirmed_at'])); ?></p>
        <?php
endif; ?>
    </div>
    
    <div style="margin-bottom: 10px;">
        <p><strong>Kode Pesanan:</strong> <?php echo htmlspecialchars($order['order_code']); ?></p>
        <p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($order['nama_customer']); ?></p>
        <p><strong>WhatsApp:</strong> <?php echo htmlspecialchars($order['whatsapp']); ?></p>
        <p><strong>Tanggal Pesan:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
        <p><strong>Metode Bayar:</strong> <?php echo strtoupper(htmlspecialchars($order['metode_bayar'] ?? 'COD')); ?></p>
    </div>
    
    <div class="divider"></div>
    
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($items_result): ?>
            <?php while ($item = $items_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nama_product']); ?></td>
                <td class="right"><?php echo intval($item['quantity']); ?></td>
                <td class="right"><?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                <td class="right"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <div style="text-align: right; margin-top: 10px;">
        <p class="total">TOTAL: Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></p>
    </div>
    
    <div class="divider"></div>
    
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda</p>
        <p>*** Struk online ini valid sebagai bukti transaksi ***</p>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #00C897; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Cetak Struk
        </button>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
