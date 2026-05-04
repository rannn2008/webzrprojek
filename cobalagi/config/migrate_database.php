<?php
/**
 * Database Migration Script
 * Jalankan file ini sekali untuk memperbaiki struktur database
 * Buka di browser: http://localhost/cobalagi/migrate_database.php
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration - Pondok Es Teller ZR</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1A1A2E;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6C757D;
            margin-bottom: 30px;
        }
        .migration {
            background: #f8f9fa;
            border-left: 4px solid #00C897;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .migration.error {
            border-left-color: #FF6B6B;
            background: #fff5f5;
        }
        .migration.success {
            border-left-color: #00C897;
            background: #f0fdf4;
        }
        .migration.warning {
            border-left-color: #FFD166;
            background: #fffbeb;
        }
        .migration-title {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .migration-desc {
            font-size: 0.9rem;
            color: #6C757D;
        }
        .icon {
            font-size: 1.2rem;
        }
        .success-icon { color: #00C897; }
        .error-icon { color: #FF6B6B; }
        .warning-icon { color: #FFD166; }
        .back-btn {
            display: inline-block;
            background: #00C897;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 600;
        }
        .back-btn:hover {
            background: #019267;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Database Migration</h1>
        <p class='subtitle'>Memperbaiki struktur database untuk fitur terbaru</p>
";

// Array untuk menyimpan hasil migration
$migrations = [];

// Migration 1: Add 'alamat' column to orders table
$check_alamat = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'alamat'");
if (mysqli_num_rows($check_alamat) == 0) {
    $sql = "ALTER TABLE `orders` ADD COLUMN `alamat` TEXT NULL AFTER `whatsapp`";
    if (mysqli_query($conn, $sql)) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Kolom Alamat ditambahkan',
            'desc' => 'Kolom `alamat` berhasil ditambahkan ke tabel orders'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal menambahkan kolom Alamat',
            'desc' => mysqli_error($conn)
        ];
    }
}
else {
    $migrations[] = [
        'status' => 'warning',
        'title' => 'Kolom Alamat sudah ada',
        'desc' => 'Kolom `alamat` sudah tersedia di tabel orders, tidak perlu migrasi'
    ];
}

// Migration 2: Check if metode_bayar column exists
$check_metode = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'metode_bayar'");
if (mysqli_num_rows($check_metode) == 0) {
    $sql = "ALTER TABLE `orders` ADD COLUMN `metode_bayar` VARCHAR(20) DEFAULT 'cod' AFTER `alamat`";
    if (mysqli_query($conn, $sql)) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Kolom Metode Bayar ditambahkan',
            'desc' => 'Kolom `metode_bayar` berhasil ditambahkan ke tabel orders'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal menambahkan kolom Metode Bayar',
            'desc' => mysqli_error($conn)
        ];
    }
}
else {
    $migrations[] = [
        'status' => 'warning',
        'title' => 'Kolom Metode Bayar sudah ada',
        'desc' => 'Kolom `metode_bayar` sudah tersedia di tabel orders'
    ];
}

// Migration 3: Add metode_pengiriman column to orders
$check_metode_p = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'metode_pengiriman'");
if (mysqli_num_rows($check_metode_p) == 0) {
    $sql = "ALTER TABLE `orders` ADD COLUMN `metode_pengiriman` VARCHAR(20) DEFAULT 'pickup' AFTER `metode_bayar`";
    if (mysqli_query($conn, $sql)) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Kolom Metode Pengiriman ditambahkan',
            'desc' => 'Kolom `metode_pengiriman` berhasil ditambahkan ke tabel orders'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal menambahkan kolom Metode Pengiriman',
            'desc' => mysqli_error($conn)
        ];
    }
}
else {
    $migrations[] = [
        'status' => 'warning',
        'title' => 'Kolom Metode Pengiriman sudah ada',
        'desc' => 'Kolom `metode_pengiriman` sudah tersedia di tabel orders'
    ];
}

// Migration 4: Add order_id column to reviews table
$check_reviews = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
if (mysqli_num_rows($check_reviews) > 0) {
    $check_order_id = mysqli_query($conn, "SHOW COLUMNS FROM reviews LIKE 'order_id'");
    if (mysqli_num_rows($check_order_id) == 0) {
        $sql = "ALTER TABLE `reviews` ADD COLUMN `order_id` INT NULL AFTER `customer_id`";
        if (mysqli_query($conn, $sql)) {
            $migrations[] = [
                'status' => 'success',
                'title' => 'Kolom order_id ditambahkan ke reviews',
                'desc' => 'Kolom `order_id` berhasil ditambahkan ke tabel reviews'
            ];
        }
        else {
            $migrations[] = [
                'status' => 'error',
                'title' => 'Gagal menambahkan kolom order_id',
                'desc' => mysqli_error($conn)
            ];
        }
    }
    else {
        $migrations[] = [
            'status' => 'warning',
            'title' => 'Kolom order_id sudah ada di reviews',
            'desc' => 'Kolom `order_id` sudah tersedia di tabel reviews'
        ];
    }
}
else {
    // Create reviews table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        order_id INT NULL,
        product_id INT NULL,
        rating INT NOT NULL DEFAULT 5,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Tabel reviews dibuat',
            'desc' => 'Tabel `reviews` berhasil dibuat dengan kolom order_id'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal membuat tabel reviews',
            'desc' => mysqli_error($conn)
        ];
    }
}

// Migration 5: Add E-Wallet balances
$check_gopay = mysqli_query($conn, "SHOW COLUMNS FROM customers LIKE 'saldo_gopay'");
if (mysqli_num_rows($check_gopay) == 0) {
    $sql = "ALTER TABLE `customers` 
            ADD COLUMN `saldo_gopay` INT NULL AFTER `alamat`,
            ADD COLUMN `saldo_ovo` INT NULL AFTER `saldo_gopay`,
            ADD COLUMN `saldo_dana` INT NULL AFTER `saldo_ovo`";
    if (mysqli_query($conn, $sql)) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Kolom Saldo E-Wallet ditambahkan',
            'desc' => 'Kolom `saldo_gopay`, `saldo_ovo`, `saldo_dana` berhasil ditambahkan ke tabel customers'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal menambahkan kolom Saldo E-Wallet',
            'desc' => mysqli_error($conn)
        ];
    }
}
else {
    $migrations[] = [
        'status' => 'warning',
        'title' => 'Kolom Saldo E-Wallet sudah ada',
        'desc' => 'Kolom saldo e-wallet sudah tersedia di tabel customers'
    ];
}

// Migration 6: Add alasan_batal to orders
$check_alasan = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'alasan_batal'");
if (mysqli_num_rows($check_alasan) == 0) {
    if (mysqli_query($conn, "ALTER TABLE `orders` ADD COLUMN `alasan_batal` TEXT NULL AFTER `catatan`")) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Kolom Alasan Batal ditambahkan',
            'desc' => 'Kolom `alasan_batal` berhasil ditambahkan ke tabel orders'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal menambahkan kolom Alasan Batal',
            'desc' => mysqli_error($conn)
        ];
    }
}
else {
    $migrations[] = [
        'status' => 'warning',
        'title' => 'Kolom Alasan Batal sudah ada',
        'desc' => 'Kolom `alasan_batal` sudah tersedia di tabel orders'
    ];
}

// Migration 7: Create Chats table
$sql_chats = "CREATE TABLE IF NOT EXISTS chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL,
    sender_type ENUM('customer', 'admin') NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql_chats)) {
    $migrations[] = [
        'status' => 'success',
        'title' => 'Tabel chats dibuat',
        'desc' => 'Tabel `chats` berhasil dibuat untuk fitur Live Chat'
    ];
}
else {
    $migrations[] = [
        'status' => 'error',
        'title' => 'Gagal membuat tabel chats',
        'desc' => mysqli_error($conn)
    ];
}

// Migration 8: Add order_id column to chats (if old table exists)
$check_chat_order = mysqli_query($conn, "SHOW COLUMNS FROM chats LIKE 'order_id'");
if ($check_chat_order && mysqli_num_rows($check_chat_order) == 0) {
    if (mysqli_query($conn, "ALTER TABLE chats ADD COLUMN order_id INT NULL AFTER id")) {
        $migrations[] = [
            'status' => 'success',
            'title' => 'Kolom order_id pada chats ditambahkan',
            'desc' => 'Kolom `order_id` ditambahkan agar chat bisa per pesanan'
        ];
    }
    else {
        $migrations[] = [
            'status' => 'error',
            'title' => 'Gagal menambahkan order_id pada chats',
            'desc' => mysqli_error($conn)
        ];
    }
}
else {
    $migrations[] = [
        'status' => 'warning',
        'title' => 'Kolom order_id chats sudah ada',
        'desc' => 'Tidak perlu perubahan untuk kolom order_id pada tabel chats'
    ];
}

// Migration 9: Create order_receipts table
$sql_order_receipts = "CREATE TABLE IF NOT EXISTS order_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    receipt_code VARCHAR(40) NOT NULL UNIQUE,
    generated_by ENUM('admin', 'customer', 'system') DEFAULT 'system',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_confirmed_at DATETIME NULL,
    note TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql_order_receipts)) {
    $migrations[] = [
        'status' => 'success',
        'title' => 'Tabel order_receipts dibuat',
        'desc' => 'Tabel `order_receipts` berhasil dibuat untuk struk online'
    ];
}
else {
    $migrations[] = [
        'status' => 'error',
        'title' => 'Gagal membuat tabel order_receipts',
        'desc' => mysqli_error($conn)
    ];
}

// Display migration results
foreach ($migrations as $migration) {
    $icon = '';
    switch ($migration['status']) {
        case 'success':
            $icon = '<span class="icon success-icon">✓</span>';
            break;
        case 'error':
            $icon = '<span class="icon error-icon">✗</span>';
            break;
        case 'warning':
            $icon = '<span class="icon warning-icon">⚠</span>';
            break;
    }

    echo "<div class='migration {$migration['status']}'>
            <div class='migration-title'>{$icon} {$migration['title']}</div>
            <div class='migration-desc'>{$migration['desc']}</div>
          </div>";
}

mysqli_close($conn);

echo "
        <div style='margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;'>
            <p><strong>✅ Migration selesai!</strong> Database sudah siap digunakan.</p>
            <a href='index.php' class='back-btn'>← Kembali ke Beranda</a>
            <a href='order.php' class='back-btn' style='background: #FF6B6B; margin-left: 10px;'>🛒 Coba Pesan Sekarang</a>
        </div>
    </div>
</body>
</html>";
?>
