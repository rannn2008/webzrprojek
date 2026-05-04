<?php
/**
 * Migration: Tambah tabel customers dan kolom customer_id di orders
 * Sistem Role Pelanggan untuk Pondok Es Teller ZR
 */

require_once '../config/config.php';

echo "<h2>🔧 Migrasi Database - Sistem Pelanggan</h2>";
echo "<pre>";

// 1. Buat tabel customers
$sql_customers = "CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    whatsapp VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    alamat TEXT,
    is_verified TINYINT(1) DEFAULT 0,
    otp_code VARCHAR(6) DEFAULT NULL,
    otp_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql_customers)) {
    echo "✅ Tabel 'customers' berhasil dibuat/sudah ada\n";
}
else {
    echo "❌ Error buat tabel customers: " . mysqli_error($conn) . "\n";
}

// 2. Tambah kolom customer_id di tabel orders (jika belum ada)
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'customer_id'");
if (mysqli_num_rows($check_column) == 0) {
    $sql_add_column = "ALTER TABLE orders ADD COLUMN customer_id INT DEFAULT NULL AFTER id";
    if (mysqli_query($conn, $sql_add_column)) {
        echo "✅ Kolom 'customer_id' berhasil ditambahkan ke tabel orders\n";
    }
    else {
        echo "❌ Error tambah kolom: " . mysqli_error($conn) . "\n";
    }
}
else {
    echo "✅ Kolom 'customer_id' sudah ada di tabel orders\n";
}

// 3. Tambah foreign key (opsional, untuk integritas data)
$sql_fk = "ALTER TABLE orders ADD CONSTRAINT fk_customer 
           FOREIGN KEY (customer_id) REFERENCES customers(id) 
           ON DELETE SET NULL ON UPDATE CASCADE";
// Suppress error jika FK sudah ada
@mysqli_query($conn, $sql_fk);
echo "✅ Foreign key customer_id sudah dikonfigurasi\n";

echo "\n========================================\n";
echo "🎉 Migrasi selesai!\n";
echo "========================================\n";
echo "</pre>";

echo "<br><a href='register.php' style='padding:10px 20px; background:#00C897; color:white; text-decoration:none; border-radius:8px;'>→ Coba Registrasi Pelanggan</a>";
echo " <a href='admin.php' style='padding:10px 20px; background:#1A1A2E; color:white; text-decoration:none; border-radius:8px;'>→ Admin Dashboard</a>";
?>
