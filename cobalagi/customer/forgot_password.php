<?php
require_once '../config/config.php';

$error = '';
$success = '';
$step = 1; // 1: masukkan WA, 2: verifikasi OTP

// Cek apakah ada pending reset
if (isset($_SESSION['reset_customer_id']) && isset($_SESSION['reset_otp'])) {
    $step = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1: Request reset
    if (isset($_POST['request_reset'])) {
        $whatsapp = bersihkan_input($_POST['whatsapp'], $conn);

        // Format WhatsApp
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
        if ($whatsapp[0] === '0') {
            $whatsapp = '62' . substr($whatsapp, 1);
        }

        // Cek customer exists
        $result = secure_query($conn, "SELECT id, nama FROM customers WHERE whatsapp = ? AND is_verified = 1", "s", [$whatsapp]);
        $customer = fetch_one($result);

        if ($customer) {

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            secure_query($conn, "UPDATE customers SET reset_token = ?, reset_expires = ? WHERE id = ?", "ssi", [$otp, $expires, $customer['id']]);

            // Save to session
            $_SESSION['reset_customer_id'] = $customer['id'];
            $_SESSION['reset_whatsapp'] = $whatsapp;
            $_SESSION['reset_otp'] = $otp; // Demo

            $step = 2;
            $success = "Kode OTP telah dikirim ke WhatsApp Anda!";
        }
        else {
            $error = "Nomor WhatsApp tidak ditemukan atau belum terverifikasi!";
        }
    }

    // Step 2: Verify OTP & Reset Password
    if (isset($_POST['reset_password'])) {
        $otp = bersihkan_input($_POST['otp'], $conn);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $customer_id = $_SESSION['reset_customer_id'] ?? 0;

        if (strlen($new_password) < 6) {
            $error = "Password minimal 6 karakter!";
            $step = 2;
        }
        elseif ($new_password !== $confirm_password) {
            $error = "Konfirmasi password tidak cocok!";
            $step = 2;
        }
        else {
            // Verify OTP
            $result = secure_query($conn, "SELECT id FROM customers WHERE id = ? AND reset_token = ? AND reset_expires > NOW()", "is", [$customer_id, $otp]);

            if ($result && $result->num_rows > 0) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                secure_query($conn, "UPDATE customers SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?", "si", [$hashed, $customer_id]);

                // Clear session
                unset($_SESSION['reset_customer_id']);
                unset($_SESSION['reset_whatsapp']);
                unset($_SESSION['reset_otp']);

                // Redirect to login
                header("Location: customer_login.php?reset=success");
                exit();
            }
            else {
                $error = "Kode OTP salah atau sudah kadaluarsa!";
                $step = 2;
            }
        }
    }
}

$demo_otp = $_SESSION['reset_otp'] ?? '';
$whatsapp = $_SESSION['reset_whatsapp'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00C897;
            --primary-dark: #019267;
            --secondary: #FF6B6B;
            --dark: #1A1A2E;
            --gray: #6C757D;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #00C897 0%, #019267 100%);
            padding: 20px;
        }
        
        .forgot-container {
            background: white;
            border-radius: 24px;
            padding: 45px 40px;
            width: 450px;
            max-width: 95%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .forgot-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 15px 40px rgba(0, 200, 151, 0.3);
        }
        
        .forgot-icon i { font-size: 2.2rem; color: white; }
        
        h1 { font-size: 1.7rem; color: var(--dark); margin-bottom: 8px; }
        .subtitle { color: var(--gray); font-size: 0.95rem; line-height: 1.5; }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        
        .alert-error { background: #ffebee; color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        
        .demo-otp {
            background: #fff3e0;
            color: #e65100;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px dashed #ffb74d;
        }
        
        .demo-otp strong { font-size: 1.3rem; letter-spacing: 6px; }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .input-group { position: relative; }
        
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .form-input {
            width: 100%;
            padding: 14px 14px 14px 48px;
            border: 2px solid #e0e6ef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 200, 151, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 200, 151, 0.4);
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="forgot-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1><?php echo $step == 1 ? 'Lupa Password?' : 'Reset Password'; ?></h1>
            <p class="subtitle">
                <?php echo $step == 1
    ? 'Masukkan nomor WhatsApp yang terdaftar untuk reset password'
    : 'Masukkan kode OTP dan password baru Anda'; ?>
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php
endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php
endif; ?>
        
        <?php if ($step == 1): ?>
            <!-- Step 1: Request Reset -->
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <div class="input-group">
                        <i class="fab fa-whatsapp"></i>
                        <input type="tel" name="whatsapp" class="form-input" placeholder="Contoh: 081234567890" required>
                    </div>
                </div>
                
                <button type="submit" name="request_reset" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Kirim Kode OTP
                </button>
            </form>
        <?php
else: ?>
            <!-- Step 2: Verify OTP & Reset -->
            <div class="demo-otp">
                <small>🔐 Demo Mode - Kode OTP:</small><br>
                <strong><?php echo $demo_otp; ?></strong>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Kode OTP</label>
                    <div class="input-group">
                        <i class="fas fa-shield-alt"></i>
                        <input type="text" name="otp" class="form-input" placeholder="Masukkan 6 digit OTP" maxlength="6" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="new_password" class="form-input" placeholder="Min. 6 karakter" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Ulangi password baru" required>
                    </div>
                </div>
                
                <button type="submit" name="reset_password" class="btn-submit">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
        <?php
endif; ?>
        
        <div class="back-link">
            <a href="customer_login.php"><i class="fas fa-arrow-left"></i> Kembali ke Login</a>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
