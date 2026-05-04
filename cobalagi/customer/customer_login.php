<?php
require_once '../config/config.php';
require_once '../includes/db_helper.php';

// Redirect jika sudah login
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header("Location: customer_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? ''); // bisa email atau whatsapp
    $password = $_POST['password'] ?? '';
    
    // Format WhatsApp jika angka
    $whatsapp_formatted = preg_replace('/[^0-9]/', '', $login_id);
    if (!empty($whatsapp_formatted) && isset($whatsapp_formatted[0]) && $whatsapp_formatted[0] === '0') {
        $whatsapp_formatted = '62' . substr($whatsapp_formatted, 1);
    }
    if (empty($login_id) || empty($password)) {
        $error = "Email/WhatsApp dan password wajib diisi.";
    } else {
        $result = secure_query($conn, "SELECT id, nama, whatsapp, password FROM customers WHERE (email = ? OR whatsapp = ? OR whatsapp = ?) AND is_verified = 1 LIMIT 1", "sss", [$login_id, $login_id, $whatsapp_formatted]);
        $customer = fetch_one($result);

        if ($customer) {
            if (password_verify($password, $customer['password'])) {
                // Login berhasil
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = (int) $customer['id'];
                $_SESSION['customer_nama'] = $customer['nama'];
                $_SESSION['customer_whatsapp'] = $customer['whatsapp'];

                // Log activity
                $details = "Login: {$customer['nama']} ({$customer['whatsapp']})";
                secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES ('system', 'Customer Login', ?)", "s", [$details], false);

                header("Location: customer_dashboard.php");
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Akun tidak ditemukan atau belum diverifikasi!";
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
    <title>Login Pelanggan - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }

        /* Floating shapes */
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
            filter: blur(40px);
            z-index: 1;
        }

        .shape1 {
            background: white;
            width: 300px;
            height: 300px;
            top: -10%;
            left: -10%;
        }

        .shape2 {
            background: var(--accent);
            width: 200px;
            height: 200px;
            bottom: 10%;
            right: -5%;
            animation-delay: 5s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            50% {
                transform: translate(30px, -30px) rotate(10deg);
            }
        }

        .login-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 30px;
            padding: 45px 40px;
            width: 450px;
            max-width: 95%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.6s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(139, 90, 43, 0.3);
            font-size: 2.5rem;
        }

        .login-header h1 {
            font-size: 1.9rem;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            animation: shake 0.5s ease;
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #ffcdd2;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-8px);
            }

            75% {
                transform: translateX(8px);
            }
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .form-input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #eee;
            border-radius: 15px;
            font-size: 1rem;
            transition: var(--transition);
            background: #f9f9f9;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
        }

        .form-input:focus+i {
            color: var(--primary);
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
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--dark);
        }

        .forgot-link {
            text-align: right;
            margin-bottom: 25px;
        }

        .forgot-link a {
            color: var(--secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .forgot-link a:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(139, 90, 43, 0.4);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: var(--gray);
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }

        .divider span {
            padding: 0 15px;
        }

        .btn-guest {
            width: 100%;
            padding: 14px;
            background: white;
            color: var(--dark);
            border: 2px solid #eee;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-guest:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(139, 90, 43, 0.05);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .login-footer p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 700;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .back-btn {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 10;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
    </style>
</head>

<body>
    <div id="particles-js"></div>
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>

    <a href="../index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-mug-hot"></i>
            </div>
            <h1 style="font-family:'Playfair Display', serif; font-weight:800;">Masuk Akun</h1>
            <p>Login untuk menikmati kemudahan order</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
                <?php
        endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email atau Nomor WhatsApp</label>
                <div class="input-group">
                    <input type="text" name="login_id" class="form-input" placeholder="email@example.com atau 081xxx"
                        required>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-input"
                        placeholder="Masukkan password" required>
                    <i class="fas fa-lock"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="forgot-link">
                <a href="forgot_password.php">Lupa password?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
            </button>
        </form>

        <div class="divider"><span>atau</span></div>

        <a href="../index.php" class="btn-guest">
            <i class="fas fa-shopping-cart"></i> Order Tanpa Akun
        </a>

        <div class="login-footer">
            <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
        </div>
    </div>

    <script>
        particlesJS('particles-js', {
            particles: {
                number: { value: 60, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.3, random: true },
                size: { value: 3, random: true },
                move: { enable: true, speed: 2, direction: 'none', random: true, out_mode: 'out' }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'grab' } }
            }
        });

        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
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
