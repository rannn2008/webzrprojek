-- Database Master Schema for Pondok Es Teller ZR
-- Consolidates all migrations into a single file

CREATE DATABASE IF NOT EXISTS pondokestellerzr_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE pondokestellerzr_db;

-- 1. Customers Table (Upgraded)
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    whatsapp VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    alamat TEXT,
    foto_profil VARCHAR(255) DEFAULT 'default_user.png',
    saldo_gopay INT DEFAULT 0,
    saldo_ovo INT DEFAULT 0,
    saldo_dana INT DEFAULT 0,
    points INT DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    otp_code VARCHAR(6) DEFAULT NULL,
    otp_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    harga INT NOT NULL,
    kategori VARCHAR(50),
    gambar VARCHAR(255),
    deskripsi TEXT,
    tersedia BOOLEAN DEFAULT TRUE,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NULL,
    order_code VARCHAR(20) UNIQUE,
    nama_customer VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    alamat TEXT,
    catatan TEXT,
    alasan_batal TEXT,
    total_harga INT NOT NULL,
    metode_bayar VARCHAR(20) DEFAULT 'cod',
    metode_pengiriman VARCHAR(20) DEFAULT 'pickup',
    status ENUM('new', 'process', 'preparing', 'ready', 'done', 'cancel') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    nama_product VARCHAR(100),
    harga INT,
    quantity INT,
    subtotal INT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Admin Users Table (Upgraded)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Chats Table
CREATE TABLE IF NOT EXISTS chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL,
    sender_type ENUM('customer', 'admin') NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_id INT NULL,
    rating INT NOT NULL DEFAULT 5,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Order Receipts (Online Struk)
CREATE TABLE IF NOT EXISTS order_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    receipt_code VARCHAR(40) NOT NULL UNIQUE,
    generated_by ENUM('admin', 'customer', 'system') DEFAULT 'system',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_confirmed_at DATETIME NULL,
    note TEXT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user VARCHAR(50),
    action VARCHAR(100),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Admin Default
-- Gunakan MD5('admin123') agar valid di SQL murni.
-- Sistem login akan otomatis upgrade ke password_hash setelah login pertama berhasil.
INSERT IGNORE INTO admin_users (username, password, nama) 
VALUES ('admin', MD5('admin123'), 'Administrator');
