<?php
require_once '../config/config.php';

// Cek apakah ada pending registration
if (!isset($_SESSION['pending_customer_id']) || !isset($_SESSION['pending_whatsapp'])) {
    header("Location: register.php");
    exit();
}

$customer_id = $_SESSION['pending_customer_id'];
$whatsapp = $_SESSION['pending_whatsapp'];
$demo_otp = $_SESSION['otp_code'] ?? ''; // Untuk demo

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = bersihkan_input($_POST['otp'], $conn);

    // Cek OTP di database
    $result = secure_query($conn, "SELECT id FROM customers WHERE id = ? AND otp_code = ? AND otp_expires > NOW()", "is", [$customer_id, $entered_otp]);

    if ($result && $result->num_rows > 0) {
        // OTP valid, verifikasi akun
        secure_query($conn, "UPDATE customers SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?", "i", [$customer_id]);

        // Get customer data
        $customer = fetch_one(secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$customer_id]));

        // Set session login
        $_SESSION['customer_logged_in'] = true;
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['customer_nama'] = $customer['nama'];
        $_SESSION['customer_whatsapp'] = $customer['whatsapp'];

        // Clear pending session
        unset($_SESSION['pending_customer_id']);
        unset($_SESSION['pending_whatsapp']);
        unset($_SESSION['otp_code']);

        // Log activity
        secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES ('system', 'Customer Verified', ?)", "s", ["Akun terverifikasi: {$customer['nama']} ({$customer['whatsapp']})"], false);

        header("Location: customer_dashboard.php");
        exit();
    }
    else {
        $error = "Kode OTP salah atau sudah kadaluarsa!";
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    $new_otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $new_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    secure_query($conn, "UPDATE customers SET otp_code = ?, otp_expires = ? WHERE id = ?", "ssi", [$new_otp, $new_expires, $customer_id]);
    $_SESSION['otp_code'] = $new_otp; // Update demo OTP

    $success = "Kode OTP baru telah dikirim!";
    $demo_otp = $new_otp;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - Pondok Es Teller ZR</title>
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
        
        .otp-container {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            width: 450px;
            max-width: 95%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .otp-icon {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 15px 40px rgba(0, 200, 151, 0.3);
        }
        
        .otp-icon i { font-size: 2.5rem; color: white; }
        
        h1 { font-size: 1.8rem; color: var(--dark); margin-bottom: 10px; }
        
        .subtitle { color: var(--gray); margin-bottom: 30px; line-height: 1.6; }
        
        .wa-number {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .demo-otp {
            background: #fff3e0;
            color: #e65100;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px dashed #ffb74d;
        }
        
        .demo-otp strong { font-size: 1.5rem; letter-spacing: 8px; }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .alert-error { background: #ffebee; color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }
        
        .otp-input {
            width: 55px; height: 65px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            border: 2px solid #e0e6ef;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 200, 151, 0.1);
        }
        
        .btn-verify {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-verify:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 200, 151, 0.4);
        }
        
        .resend-link {
            margin-top: 25px;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .resend-link a:hover { text-decoration: underline; }
        
        /* Hidden input for form submission */
        #otpHidden { display: none; }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-icon">
            <i class="fab fa-whatsapp"></i>
        </div>
        
        <h1>Verifikasi WhatsApp</h1>
        <p class="subtitle">Masukkan kode OTP 6 digit yang kami kirimkan ke nomor WhatsApp Anda</p>
        
        <div class="wa-number">
            <i class="fab fa-whatsapp"></i>
            +<?php echo $whatsapp; ?>
        </div>
        
        <!-- Demo OTP Display (hapus di production) -->
        <div class="demo-otp">
            <small>🔐 Demo Mode - Kode OTP:</small><br>
            <strong><?php echo $demo_otp; ?></strong>
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
        
        <form method="POST" action="" id="otpForm">
            <div class="otp-inputs">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            </div>
            <input type="hidden" name="otp" id="otpHidden">
            
            <button type="submit" class="btn-verify">
                <i class="fas fa-check-circle"></i> Verifikasi
            </button>
        </form>
        
        <div class="resend-link">
            <p>Tidak menerima kode? <a href="?resend=1">Kirim Ulang</a></p>
        </div>
    </div>
    
    <script>
        const inputs = document.querySelectorAll('.otp-input');
        const form = document.getElementById('otpForm');
        const hiddenInput = document.getElementById('otpHidden');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value;
                if (val && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            
            // Paste support
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/\D/g, '').split('');
                digits.forEach((digit, i) => {
                    if (inputs[i]) inputs[i].value = digit;
                });
                inputs[Math.min(digits.length, inputs.length) - 1]?.focus();
            });
        });
        
        form.addEventListener('submit', (e) => {
            let otp = '';
            inputs.forEach(input => otp += input.value);
            hiddenInput.value = otp;
        });
        
        // Auto focus first input
        inputs[0].focus();
    </script>
</body>
</html>
