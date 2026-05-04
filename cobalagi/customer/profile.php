<?php
require_once '../config/config.php';

// Cek login customer
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$success = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = bersihkan_input($_POST['nama'], $conn);
    $email = bersihkan_input($_POST['email'], $conn);
    $whatsapp = bersihkan_input($_POST['whatsapp'], $conn);
    $alamat = bersihkan_input($_POST['alamat'], $conn);
    $password = $_POST['password'];

    // Update Info
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $ok = secure_query($conn, "UPDATE customers SET nama = ?, email = ?, whatsapp = ?, alamat = ?, password = ? WHERE id = ?", "sssssi", [$nama, $email, $whatsapp, $alamat, $hashed, $customer_id], false);
    } else {
        $ok = secure_query($conn, "UPDATE customers SET nama = ?, email = ?, whatsapp = ?, alamat = ? WHERE id = ?", "ssssi", [$nama, $email, $whatsapp, $alamat, $customer_id], false);
    }

    if ($ok) {
        $_SESSION['customer_nama'] = $nama; // Update session name
        $success = "Profil berhasil diperbarui!";
    }
    else {
        $error = "Gagal memperbarui profil. Mohon coba lagi.";
    }
}

// Get Customer Data
$customer = fetch_one(secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$customer_id]));

// Get Order History
$orders = secure_query($conn, "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC", "i", [$customer_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00E676;
            --primary-dark: #00C853;
            --secondary: #FF9100;
            --dark: #121212;
            --light: #F5F7FA;
            --gray: #9E9E9E;
            --radius: 20px;
            --shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
        }
        
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Outfit', sans-serif; }
        
        body {
            background-color: var(--light);
            min-height: 100vh;
            color: var(--dark);
            padding: 20px;
        }
        
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .btn-back {
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            display: flex; align-items: center; gap: 8px;
        }
        
        .btn-logout {
            text-decoration: none;
            color: #C62828;
            font-weight: 600;
            padding: 8px 15px;
            background: #FFEBEE;
            border-radius: 10px;
        }
        
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .grid-layout { grid-template-columns: 1fr; }
        }
        
        .card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }
        
        h2 { margin-bottom: 20px; font-size: 1.5rem; color: var(--dark); }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-input {
            width: 100%; padding: 12px 15px;
            border: 2px solid #eee; border-radius: 12px;
            font-size: 0.95rem; transition: 0.3s;
        }
        .form-input:focus { border-color: var(--primary); outline: none; }
        
        .btn-save {
            width: 100%; padding: 14px;
            background: var(--primary); color: white;
            border: none; border-radius: 12px;
            font-weight: 700; cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 230, 118, 0.3);
            transition: 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 230, 118, 0.4); }
        
        /* Order History */
        .order-card {
            border: 1px solid #eee;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: 0.3s;
        }
        .order-card:hover { border-color: var(--primary); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        
        .oc-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee;
        }
        
        .oc-status {
            font-size: 0.8rem; font-weight: 700; padding: 4px 10px;
            border-radius: 20px; text-transform: uppercase;
        }
        .st-new, .st-baru { background: #E3F2FD; color: #1565C0; }
        .st-process { background: #FFF3E0; color: #EF6C00; }
        .st-done { background: #E8F5E9; color: #2E7D32; }
        .st-cancel { background: #FFEBEE; color: #C62828; }
        
        .oc-total { font-size: 1.1rem; font-weight: 700; color: var(--primary-dark); }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background: #E8F5E9; color: #2E7D32; }
        .alert-error { background: #FFEBEE; color: #C62828; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="../index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Menu</a>
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="text-align:right;">
                <div style="font-weight:700;"><?php echo $customer['nama']; ?></div>
                <div style="font-size:0.8rem; color:var(--gray);"><?php echo $customer['email']; ?></div>
            </div>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
    
    <div class="grid-layout">
        <!-- Edit Profile -->
        <div class="card">
            <h2><i class="fas fa-user-edit" style="color:var(--secondary);"></i> Edit Profil</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php
elseif ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php
endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-input" value="<?php echo $customer['nama']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?php echo $customer['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="tel" name="whatsapp" class="form-input" value="<?php echo $customer['whatsapp']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat Lengkap (Default)</label>
                    <textarea name="alamat" class="form-input" rows="3" placeholder="Alamat untuk pengiriman..."><?php echo $customer['alamat']; ?></textarea>
                </div>
                
                <div style="border-top:1px solid #eee; margin:20px 0;"></div>
                
                <div class="form-group">
                    <label class="form-label">Password Baru (Opsional)</label>
                    <input type="password" name="password" class="form-input" placeholder="biarkan kosong jika tidak ganti">
                </div>
                
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </form>
        </div>
        
        <!-- Order History -->
        <div class="card">
            <h2><i class="fas fa-history" style="color:var(--primary);"></i> Riwayat Pesanan</h2>
            
            <?php if ($orders && $orders->num_rows > 0): ?>
                <?php while ($ord = $orders->fetch_assoc()):
        $st_class = 'st-' . strtolower($ord['status']);
?>
                    <div class="order-card">
                        <div class="oc-header">
                            <div>
                                <div style="font-weight:700;"><?php echo $ord['order_code']; ?></div>
                                <div style="font-size:0.8rem; color:var(--gray);"><?php echo date('d M Y, H:i', strtotime($ord['created_at'])); ?></div>
                            </div>
                            <span class="oc-status <?php echo $st_class; ?>"><?php echo $ord['status']; ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-size:0.9rem; color:var(--gray);">Total Pembayaran</div>
                            <div class="oc-total">Rp <?php echo number_format($ord['total_harga'], 0, ',', '.'); ?></div>
                        </div>
                        <?php if (!empty($ord['catatan'])): ?>
                            <div style="margin-top:10px; font-size:0.85rem; color:var(--gray); background:#f9f9f9; padding:8px; border-radius:8px;">
                                <i class="fas fa-sticky-note"></i> "<?php echo $ord['catatan']; ?>"
                            </div>
                        <?php
        endif; ?>
                    </div>
                <?php
    endwhile; ?>
            <?php
else: ?>
                <div style="text-align:center; padding:40px; color:var(--gray);">
                    <i class="fas fa-receipt" style="font-size:3rem; margin-bottom:15px; opacity:0.3;"></i>
                    <p>Belum ada riwayat pesanan.</p>
                </div>
            <?php
endif; ?>
        </div>
    </div>
</div>

</body>
</html>
