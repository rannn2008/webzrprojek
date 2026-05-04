<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'];
    $wallet_type = $_POST['wallet_type'] ?? '';

    if (!in_array($wallet_type, ['gopay', 'ovo', 'dana'])) {
        echo json_encode(['success' => false, 'message' => 'Tipe e-wallet tidak valid.']);
        exit;
    }

    $col_name = "saldo_" . $wallet_type;
    $mock_saldo = 500000; // Mock initial balance Rp 500.000

    // Column name cannot be parameterized, but it's whitelisted above
    if (secure_query($conn, "UPDATE customers SET $col_name = ? WHERE id = ?", "ii", [$mock_saldo, $customer_id], false)) {
        echo json_encode([
            'success' => true,
            'message' => 'Akun berhasil dihubungkan!',
            'saldo' => $mock_saldo
        ]);
    }
    else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghubungkan akun.'
        ]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
