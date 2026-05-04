-- Buat database
CREATE DATABASE IF NOT EXISTS pondok_esteller_db;
USE pondok_esteller_db;

-- Tabel produk
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    harga INT NOT NULL,
    kategori VARCHAR(50),
    gambar VARCHAR(255),
    deskripsi TEXT,
    tersedia BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel pesanan
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_code VARCHAR(20) UNIQUE,
    nama_customer VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    catatan TEXT,
    total_harga INT NOT NULL,
    status ENUM('new', 'process', 'done', 'cancel') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel items pesanan
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    nama_product VARCHAR(100),
    harga INT,
    quantity INT,
    subtotal INT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Tabel admin (untuk login)
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel chat customer-admin
CREATE TABLE chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL,
    sender_type ENUM('customer', 'admin') NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel struk online pesanan
CREATE TABLE order_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    receipt_code VARCHAR(40) NOT NULL UNIQUE,
    generated_by ENUM('admin', 'customer', 'system') DEFAULT 'system',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_confirmed_at DATETIME NULL,
    note TEXT NULL
);

-- Insert admin default
INSERT INTO admin_users (username, password, nama) 
VALUES ('admin', MD5('admin123'), 'Administrator');

-- Hapus data lama
DELETE FROM products;

-- Reset auto increment
ALTER TABLE products AUTO_INCREMENT = 1;

-- Insert data baru dengan nama file gambar
INSERT INTO products (nama, harga, kategori, gambar, deskripsi) VALUES
('Es Teller Special', 25000, 'es teller', 'estelleroriginal.jpg', 'Es teller dengan topping lengkap'),
('Es Campur', 20000, 'es campur', 'test.jpg', 'Es campur buah segar'),
('Es Teller Jumbo', 30000, 'es teller', 'esrumputlautpremium.jpg', 'Porsi jumbo untuk 2 orang'),
('Es Cincau', 15000, 'es cincau', 'escampurpremium.jpg', 'Es cincau segar'),
('Es Dawet', 18000, 'es tradisional', 'estellerpremium.jpg', 'Es dawet khas'),
('Es Kelapa Muda', 22000, 'es kelapa', 'estelleroriginal.jpg', 'Es kelapa muda segar');

-- Insert sample orders
INSERT INTO orders (order_code, nama_customer, whatsapp, total_harga, status) VALUES
('ORD001', 'Budi Santoso', '081234567890', 70000, 'new'),
('ORD002', 'Siti Aminah', '081298765432', 45000, 'process');

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, nama_product, harga, quantity, subtotal) VALUES
(1, 1, 'Es Teller Special', 25000, 2, 50000),
(1, 2, 'Es Campur', 20000, 1, 20000),
(2, 3, 'Es Teller Jumbo', 30000, 1, 30000),
(2, 4, 'Es Cincau', 15000, 1, 15000);
