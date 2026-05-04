<?php
require_once '../config/config.php';

// Redirect jika sudah login
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header("Location: customer_dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = bersihkan_input($_POST['nama'], $conn);
    $email = bersihkan_input($_POST['email'], $conn);
    $whatsapp = bersihkan_input($_POST['whatsapp'], $conn);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi
    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    }
    else {
        // Cek duplikat
        $check = secure_query($conn, "SELECT id FROM customers WHERE email = ? OR whatsapp = ?", "ss", [$email, $whatsapp]);
        if ($check && $check->num_rows > 0) {
            $error = "Email atau Nomor WhatsApp sudah terdaftar!";
        }
        else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999); // Generate OTP dummy

            $sql = "INSERT INTO customers (nama, email, whatsapp, password, otp_code, is_verified) 
                    VALUES (?, ?, ?, ?, ?, 1)"; // Auto verified for demo

            if (secure_query($conn, $sql, "sssss", [$nama, $email, $whatsapp, $hashed_password, $otp], false)) {
                // Auto Login
                $new_id = mysqli_insert_id($conn);
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = $new_id;
                $_SESSION['customer_nama'] = $nama;
                $_SESSION['customer_whatsapp'] = $whatsapp;

                // Log
                secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES ('system', 'New Register', ?)", "s", ["Customer baru (Auto Login): $nama"], false);

                echo "<script>alert('Pendaftaran berhasil! Selamat datang.'); window.location.href='customer_dashboard.php';</script>";
                exit();
            }
            else {
                $error = "Terjadi kesalahan pendaftaran. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <style>
        :root {
            --primary: #8b5a2b;       
            --primary-dark: #5c3a18;  
            --primary-light: #c19a6b;
            --secondary: #d2a679;     
            --secondary-dark: #a67c52;
            --accent: #e6ccb8;        
            
            --dark: #3e2723;
            --light: #fdfbf7;
            --gray: #9E9E9E;
            --bg-color: #f4eee6;
            
            --glass: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow: 0 10px 40px -10px rgba(139, 90, 43, 0.1);
            --radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            background-size: 300% 300%;
            animation: gradientShift 20s ease infinite;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        #particles-js {
            position: fixed;
            width: 100%; height: 100%;
            top: 0; left: 0; z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 30px;
            padding: 40px;
            width: 500px;
            max-width: 95%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.6s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 70px; height: 70px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.3);
            font-size: 2rem;
        }
        
        .login-header h1 { font-size: 1.8rem; color: var(--dark); margin-bottom: 5px; }
        .login-header p { color: var(--gray); font-size: 0.9rem; }
        
        .form-group { margin-bottom: 18px; }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #eee;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: #fcfcfc;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.3);
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(139, 90, 43, 0.4);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-footer a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 700;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .alert-error { background: #FFEBEE; color: #C62828; border: 1px solid #ffcdd2; }
        .alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        
        .back-btn {
            position: fixed; top: 30px; left: 30px; z-index: 10;
            background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3); color: white;
            padding: 12px 24px; border-radius: 50px; text-decoration: none;
            font-weight: 600; transition: all 0.3s ease;
        }
        
        .back-btn:hover { background: rgba(255,255,255,0.3); transform: translateX(-5px); }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <a href="customer_login.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="login-container">
        <div class="login-header">
            <div class="logo"><i class="fas fa-user-plus"></i></div>
            <h1 style="font-family:'Playfair Display', serif; font-weight:800;">Buat Akun Baru</h1>
            <p>Bergabunglah dengan kami untuk promo menarik</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php
elseif ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php
endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-input" placeholder="Nama Anda" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="email@contoh.com" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp</label>
                <input type="tel" name="whatsapp" class="form-input" placeholder="08xxxxxxxxxx" required>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ulangi Password</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-paper-plane"></i> Daftar Sekarang
            </button>
        </form>
        
        <div class="login-footer">
            <p>Sudah punya akun? <a href="customer_login.php">Masuk disini</a></p>
        </div>
    </div>
    
    <script>
        particlesJS('particles-js', {
            particles: {
                number: { value: 50 },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.3, random: true },
                size: { value: 3, random: true },
                move: { enable: true, speed: 1.5 }
            }
        });
    </script>
</body>
</html>
