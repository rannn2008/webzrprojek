<?php
require_once '../config/config.php';

// Set header untuk styling
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00C897;
            --primary-dark: #019267;
            --secondary: #FF6B6B;
            --accent: #FFD166;
            --dark: #1A1A2E;
            --light: #F8F9FA;
            --gray: #6C757D;
            --radius: 16px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark);
        }
        
        .setup-container {
            background: white;
            border-radius: var(--radius);
            padding: 50px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .setup-header h1 {
            color: var(--dark);
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .setup-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .progress-bar {
            height: 8px;
            background: var(--light);
            border-radius: 4px;
            margin: 30px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 4px;
        }
        
        .setup-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }
        
        .step {
            text-align: center;
            position: relative;
            flex: 1;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
            color: var(--gray);
            border: 3px solid var(--light);
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .step.completed .step-circle {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .step.completed .step-circle::after {
            content: "✓";
        }
        
        .step-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .setup-content {
            min-height: 300px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeIn 0.5s;
        }
        
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 5px solid #2e7d32;
        }
        
        .message.error {
            background: #ffebee;
            color: #c62828;
            border-left: 5px solid #c62828;
        }
        
        .message.info {
            background: #e3f2fd;
            color: #1976d2;
            border-left: 5px solid #1976d2;
        }
        
        .message.warning {
            background: #fff3e0;
            color: #f57c00;
            border-left: 5px solid #f57c00;
        }
        
        .setup-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--light);
        }
        
        .btn {
            flex: 1;
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 200, 151, 0.3);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }
        
        .database-info {
            background: var(--light);
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .setup-container {
                padding: 30px 20px;
            }
            
            .setup-header h1 {
                font-size: 2rem;
            }
            
            .setup-steps {
                flex-direction: column;
                gap: 20px;
            }
            
            .setup-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="fas fa-database"></i> Database Setup</h1>
            <p>Setup database untuk Pondok Es Teller ZR</p>
        </div>
        
        <div class="setup-steps" id="setupSteps">
            <div class="step active" id="step1">
                <div class="step-circle">1</div>
                <div class="step-label">Koneksi</div>
            </div>
            <div class="step" id="step2">
                <div class="step-circle">2</div>
                <div class="step-label">Tabel</div>
            </div>
            <div class="step" id="step3">
                <div class="step-circle">3</div>
                <div class="step-label">Data</div>
            </div>
            <div class="step" id="step4">
                <div class="step-circle">4</div>
                <div class="step-label">Selesai</div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 25%"></div>
        </div>
        
        <div class="setup-content" id="setupContent">';

// Step 1: Koneksi database
echo '<div class="message success">
    <i class="fas fa-check-circle"></i>
    <div>
        <strong>Koneksi database berhasil!</strong><br>
        Database: ' . $database . '
    </div>
</div>';

$successCount = 0;
$totalSteps = 4;

// Step 2: Buat tabel
echo '<div class="message info">
    <i class="fas fa-spinner fa-spin"></i>
    <div>
        <strong>Membuat tabel...</strong><br>
        Menyiapkan struktur database
    </div>
</div>';

$sql_tables = [
    // Tabel produk
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
    
    // Tabel pesanan
    "CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        kode_pesanan VARCHAR(20) UNIQUE,
        nama_customer VARCHAR(100) NOT NULL,
        whatsapp VARCHAR(20) NOT NULL,
        catatan TEXT,
        total_harga INT NOT NULL,
        status ENUM('baru', 'diproses', 'selesai', 'dibatalkan') DEFAULT 'baru',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel items pesanan
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
    
    // Tabel admin
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel activity logs
    "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_user VARCHAR(50),
        action VARCHAR(100),
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Tabel chat customer-admin
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

    // Tabel struk online
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

$tablesCreated = 0;
foreach ($sql_tables as $sql) {
    if (mysqli_query($conn, $sql)) {
        $tablesCreated++;
        echo '<div class="message success">
            <i class="fas fa-check"></i>
            <div>Tabel berhasil dibuat</div>
        </div>';
    } else {
        echo '<div class="message warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>Note: ' . mysqli_error($conn) . '</div>
        </div>';
    }
}

$successCount += ($tablesCreated == count($sql_tables)) ? 1 : 0;

// Step 3: Insert data awal
echo '<div class="message info">
    <i class="fas fa-spinner fa-spin"></i>
    <div>
        <strong>Memasukkan data awal...</strong><br>
        Menyiapkan data default
    </div>
</div>';

$sql_insert_data = [
    // Insert admin default
    "INSERT IGNORE INTO admin_users (username, password, nama) 
     VALUES ('admin', MD5('admin123'), 'Administrator')",
    
    // Insert sample products
    "INSERT IGNORE INTO products (nama, harga, kategori, gambar, deskripsi) VALUES
    ('Es Teller Special', 25000, 'es teller', '🍧', 'Es teller dengan topping lengkap'),
    ('Es Campur', 20000, 'es campur', '🥥', 'Es campur buah segar'),
    ('Es Teller Jumbo', 30000, 'es teller', '🍨', 'Porsi jumbo untuk 2 orang'),
    ('Es Cincau', 15000, 'es cincau', '🧋', 'Es cincau segar'),
    ('Es Dawet', 18000, 'es tradisional', '🥤', 'Es dawet khas'),
    ('Es Kelapa Muda', 22000, 'es kelapa', '🥥', 'Es kelapa muda segar')",
    
    // Insert activity log for setup
    "INSERT IGNORE INTO activity_logs (admin_user, action, details) VALUES
    ('system', 'Database Setup', 'Database berhasil di-setup pada " . date('Y-m-d H:i:s') . "')"
];

$dataInserted = 0;
foreach ($sql_insert_data as $sql) {
    if (mysqli_query($conn, $sql)) {
        $dataInserted++;
        echo '<div class="message success">
            <i class="fas fa-check"></i>
            <div>Data berhasil dimasukkan</div>
        </div>';
    } else {
        echo '<div class="message warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>Note: ' . mysqli_error($conn) . '</div>
        </div>';
    }
}

$successCount += ($dataInserted > 0) ? 1 : 0;

// Step 4: Selesai
$successCount++; // Untuk step koneksi

echo '<div class="message success">
    <i class="fas fa-check-circle"></i>
    <div>
        <strong>Setup database selesai!</strong><br>
        ' . $successCount . ' dari ' . $totalSteps . ' langkah berhasil
    </div>
</div>';

// Tampilkan informasi database
echo '<div class="database-info">
    <h3 style="margin-bottom: 20px; color: var(--dark);"><i class="fas fa-info-circle"></i> Informasi Database</h3>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Nama Database</div>
            <div class="info-value">' . $database . '</div>
        </div>
        <div class="info-item">
            <div class="info-label">Host</div>
            <div class="info-value">' . $host . '</div>
        </div>
        <div class="info-item">
            <div class="info-label">Tabel Dibuat</div>
            <div class="info-value">' . $tablesCreated . ' tabel</div>
        </div>
        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value" style="color: var(--primary); font-weight: 700;">✓ Siap digunakan</div>
        </div>
    </div>
</div>';

echo '</div>
        
        <div class="setup-actions">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-user-shield"></i> Login Admin
            </a>
        </div>
    </div>
    
    <script>
        // Update progress bar dan steps
        document.addEventListener("DOMContentLoaded", function() {
            const steps = document.querySelectorAll(".step");
            const progressFill = document.getElementById("progressFill");
            const successCount = ' . $successCount . ';
            const totalSteps = ' . $totalSteps . ';
            
            // Update progress
            const progressPercentage = (successCount / totalSteps) * 100;
            progressFill.style.width = progressPercentage + "%";
            
            // Update step status
            steps.forEach((step, index) => {
                if (index < successCount) {
                    step.classList.add("completed");
                } else if (index === successCount) {
                    step.classList.add("active");
                }
            });
            
            // Auto scroll to bottom
            const setupContent = document.getElementById("setupContent");
            setupContent.scrollTop = setupContent.scrollHeight;
        });
    </script>
</body>
</html>';

mysqli_close($conn);
?>
