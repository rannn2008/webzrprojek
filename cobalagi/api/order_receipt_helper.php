<?php
/**
 * Shared helpers for online order receipts.
 */

function ensure_order_receipts_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS order_receipts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL UNIQUE,
        receipt_code VARCHAR(40) NOT NULL UNIQUE,
        generated_by ENUM('admin', 'customer', 'system') DEFAULT 'system',
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        pickup_confirmed_at DATETIME NULL,
        note TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($conn, $sql)) {
        return false;
    }

    if (!ensure_order_receipts_index($conn, 'idx_order_receipts_order_id', 'order_id')) {
        return false;
    }
    if (!ensure_order_receipts_index($conn, 'idx_order_receipts_generated_at', 'generated_at')) {
        return false;
    }

    return true;
}

function ensure_order_receipts_index($conn, $indexName, $columnSql)
{
    $check = secure_query($conn, "SHOW INDEX FROM order_receipts WHERE Key_name = ?", "s", [$indexName]);
    if ($check && $check->num_rows > 0) {
        return true;
    }

    // Index creation SQL usually doesn't support parameterized names
    return mysqli_query($conn, "CREATE INDEX $indexName ON order_receipts ($columnSql)") !== false;
}

function generate_receipt_code($orderId)
{
    $orderId = intval($orderId);
    return 'RCT' . date('Ymd') . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
}

function ensure_order_receipt($conn, $orderId, $generatedBy = 'system', $pickupConfirmed = false)
{
    if (!ensure_order_receipts_table($conn)) {
        return ['success' => false, 'error' => mysqli_error($conn)];
    }

    $orderId = intval($orderId);
    if ($orderId <= 0) {
        return ['success' => false, 'error' => 'Order ID tidak valid'];
    }

    $allowed = ['admin', 'customer', 'system'];
    if (!in_array($generatedBy, $allowed, true)) {
        $generatedBy = 'system';
    }

    $existing = secure_query($conn, "SELECT id, receipt_code, pickup_confirmed_at FROM order_receipts WHERE order_id = ? LIMIT 1", "i", [$orderId]);
    $row = fetch_one($existing);

    if ($row) {
        $receiptCode = $row['receipt_code'];

        if ($pickupConfirmed && empty($row['pickup_confirmed_at'])) {
            secure_query($conn, "UPDATE order_receipts SET pickup_confirmed_at = NOW(), generated_by = ? WHERE order_id = ?", "si", [$generatedBy, $orderId], false);
        }

        return ['success' => true, 'receipt_code' => $receiptCode, 'created' => false];
    }

    $receiptCode = generate_receipt_code($orderId);
    $pickupValue = $pickupConfirmed ? 'NOW()' : 'NULL';
    
    // pickup_confirmed_at will be set to NOW() or NULL based on $pickupValue string in SQL (safe as it's not user input)
    $sql = "INSERT INTO order_receipts (order_id, receipt_code, generated_by, pickup_confirmed_at)
            VALUES (?, ?, ?, " . ($pickupConfirmed ? "NOW()" : "NULL") . ")";

    if (!secure_query($conn, $sql, "iss", [$orderId, $receiptCode, $generatedBy], false)) {
        return ['success' => false, 'error' => 'Gagal membuat struk.'];
    }

    return ['success' => true, 'receipt_code' => $receiptCode, 'created' => true];
}
