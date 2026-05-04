<?php
require_once '../config/config.php';

echo "<h2>Complete Database Setup</h2>";

// Drop semua tabel jika ada
$drop_tables = [
    "DROP TABLE IF EXISTS order_receipts",
    "DROP TABLE IF EXISTS chats",
    "DROP TABLE IF EXISTS order_items",
    "DROP TABLE IF EXISTS orders",
    "DROP TABLE IF EXISTS products",
    "DROP TABLE IF EXISTS admin_users",
    "DROP TABLE IF EXISTS activity_logs"
];

foreach ($drop_tables as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<p>✓ Table dropped successfully</p>";
    }
}

// Buat tabel baru
$create_tables = [
    "CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama VARCHAR(100) NOT NULL,
        harga INT NOT NULL,
        kategori VARCHAR(50),
        gambar VARCHAR(10),
        deskripsi TEXT,
        tersedia BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_code VARCHAR(20) UNIQUE,
        nama_customer VARCHAR(100) NOT NULL,
        whatsapp VARCHAR(20) NOT NULL,
        catatan TEXT,
        total_harga INT NOT NULL,
        status ENUM('baru', 'diproses', 'selesai', 'dibatalkan') DEFAULT 'baru',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT,
        product_id INT,
        nama_product VARCHAR(100),
        harga INT,
        quantity INT,
        subtotal INT,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_user VARCHAR(50),
        action VARCHAR(100),
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS chats (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NULL,
        sender_type ENUM('customer', 'admin') NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS order_receipts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL UNIQUE,
        receipt_code VARCHAR(40) NOT NULL UNIQUE,
        generated_by ENUM('admin', 'customer', 'system') DEFAULT 'system',
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        pickup_confirmed_at DATETIME NULL,
        note TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($create_tables as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green;'>✓ Table created successfully</p>";
    } else {
        echo "<p style='color:red;'>✗ Error: " . mysqli_error($conn) . "</p>";
    }
}

// Insert data awal
$insert_data = [
    "INSERT IGNORE INTO admin_users (username, password, nama) 
     VALUES ('admin', MD5('admin123'), 'Administrator')",
    
    "INSERT IGNORE INTO products (nama, harga, kategori, gambar, deskripsi) VALUES
    ('Es Teller Special', 25000, 'es teller', '🍧', 'Es teller dengan topping lengkap'),
    ('Es Campur', 20000, 'es campur', '🥥', 'Es campur buah segar'),
    ('Es Teller Jumbo', 30000, 'es teller', '🍨', 'Porsi jumbo untuk 2 orang'),
    ('Es Cincau', 15000, 'es cincau', '🧋', 'Es cincau segar'),
    ('Es Dawet', 18000, 'es tradisional', '🥤', 'Es dawet khas'),
    ('Es Kelapa Muda', 22000, 'es kelapa', '🥥', 'Es kelapa muda segar')",
    
    "INSERT IGNORE INTO activity_logs (admin_user, action, details) VALUES
    ('system', 'Database Setup', 'Database berhasil di-setup pada " . date('Y-m-d H:i:s') . "')"
];

foreach ($insert_data as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color:green;'>✓ Data inserted successfully</p>";
    } else {
        echo "<p style='color:orange;'>Note: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr><h3 style='color:green;'>✅ Setup completed!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='check_db.php'>Check Database</a></p>";

mysqli_close($conn);
?>
