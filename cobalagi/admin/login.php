<?php
require_once '../config/config.php';
require_once '../includes/db_helper.php';
// session_start(); // Handled in config.php

// Redirect jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        $result = secure_query($conn, "SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1", "s", [$username]);
        $admin = fetch_one($result);

        $authenticated = false;
        $needsUpgrade = false;
        if ($admin) {
            $storedHash = (string) ($admin['password'] ?? '');

            // New scheme
            if (password_verify($password, $storedHash)) {
                $authenticated = true;
                $needsUpgrade = password_needs_rehash($storedHash, PASSWORD_DEFAULT);
            }
            // Legacy MD5 fallback (auto-upgrade after successful login)
            elseif (strlen($storedHash) === 32 && ctype_xdigit($storedHash) && hash_equals(strtolower($storedHash), md5($password))) {
                $authenticated = true;
                $needsUpgrade = true;
            }
        }

        if ($authenticated) {
            if ($needsUpgrade) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                secure_query($conn, "UPDATE admin_users SET password = ? WHERE id = ?", "si", [$newHash, (int)$admin['id']], false);
            }

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];

            secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES (?, 'Login', 'Admin berhasil login')", "s", [$admin['username']], false);

            header("Location: admin.php");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Particles.js for animated background -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <style>
        :root {
            --primary: #8b5a2b;
            --primary-light: #c19a6b;
            --primary-dark: #5c3a18;
            --secondary: #d2a679;
            --accent: #e6ccb8;
            --success: #8b5a2b;
            --dark: #3e2723;
            --dark-light: #4e342e;
            --gray: #9E9E9E;
            --gray-light: #E0E0E0;
            --light: #fdfbf7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #8b5a2b 0%, #5c3a18 50%, #c19a6b 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Animated Background */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }
        
        /* Floating shapes with enhanced animation */
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
            filter: blur(40px);
        }
        
        .shape1 {
            background: linear-gradient(135deg, #fff, #c19a6b);
            width: 300px;
            height: 300px;
            top: -10%;
            left: -5%;
            animation-delay: 0s;
        }
        
        .shape2 {
            background: linear-gradient(135deg, #d2a679, #8b5a2b);
            width: 250px;
            height: 250px;
            top: 50%;
            right: -5%;
            animation-delay: 5s;
        }
        
        .shape3 {
            background: linear-gradient(135deg, #c19a6b, #5c3a18);
            width: 350px;
            height: 350px;
            bottom: -10%;
            left: 30%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            33% { 
                transform: translate(50px, -50px) rotate(120deg) scale(1.1);
            }
            66% { 
                transform: translate(-30px, 30px) rotate(240deg) scale(0.9);
            }
        }
        
        /* Login Container with Premium Glassmorphism */
        .login-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border-radius: 32px;
            padding: 50px 45px;
            width: 480px;
            max-width: 92%;
            box-shadow: 
                0 8px 32px 0 rgba(31, 38, 135, 0.37),
                0 0 0 1px rgba(255, 255, 255, 0.18) inset;
            border: 1px solid rgba(255, 255, 255, 0.18);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            transform-style: preserve-3d;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Header Section */
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 
                0 20px 40px rgba(139, 90, 43, 0.6),
                0 0 0 8px rgba(255, 255, 255, 0.1);
            animation: logoFloat 3s ease-in-out infinite;
            position: relative;
        }
        
        .logo::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 26px;
            opacity: 0.5;
            filter: blur(10px);
            z-index: -1;
        }
        
        @keyframes logoFloat {
            0%, 100% { 
                transform: translateY(0) rotate(0deg);
                box-shadow: 0 20px 40px rgba(139, 90, 43, 0.6);
            }
            50% { 
                transform: translateY(-10px) rotate(5deg);
                box-shadow: 0 30px 50px rgba(139, 90, 43, 0.7);
            }
        }
        
        .logo i {
            font-size: 2.8rem;
            color: white;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }
        
        .login-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            font-weight: 400;
        }
        
        /* Error Message with Modern Design */
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            backdrop-filter: blur(10px);
            color: #fee2e2;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        
        .error-message i {
            font-size: 1.3rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }
        
        /* Credentials Info with Premium Style */
        .credentials-info {
            background: rgba(59, 130, 246, 0.15);
            backdrop-filter: blur(10px);
            color: #dbeafe;
            padding: 16px 18px;
            border-radius: 16px;
            margin-bottom: 28px;
            font-size: 0.87rem;
            border: 1px solid rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .credentials-info:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.2);
        }
        
        .credentials-info strong {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .credentials-info code {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        
        /* Form Group */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.92rem;
            letter-spacing: 0.3px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.2rem;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 55px 16px 52px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: 500;
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 0 0 4px rgba(99, 102, 241, 0.2),
                0 8px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .form-input:focus + .input-icon {
            color: white;
            transform: translateY(-50%) scale(1.1);
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 8px;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .password-toggle:hover {
            color: white;
            transform: translateY(-50%) scale(1.15);
        }
        
        /* Caps Lock Warning */
        .caps-warning {
            position: absolute;
            right: 55px;
            top: 50%;
            transform: translateY(-50%);
            color: #fbbf24;
            display: none;
            font-size: 0.8rem;
            background: rgba(251, 191, 36, 0.2);
            padding: 6px 10px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(251, 191, 36, 0.3);
            white-space: nowrap;
        }
        
        .caps-warning.show {
            display: flex;
            align-items: center;
            gap: 6px;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-50%) scale(0.8); }
            to { opacity: 1; transform: translateY(-50%) scale(1); }
        }
        
        /* Remember Me */
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            cursor: pointer;
            user-select: none;
        }
        
        .remember-me input[type="checkbox"] {
            width: 22px;
            height: 22px;
            accent-color: var(--primary);
            cursor: pointer;
            border-radius: 6px;
        }
        
        .remember-me label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            font-weight: 500;
        }
        
        /* Submit Button with 3D Effect */
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 
                0 10px 30px rgba(99, 102, 241, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 20px 40px rgba(99, 102, 241, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.2) inset;
        }
        
        .btn-login:active {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Login Footer */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .login-footer p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* Back Button with Enhanced Style */
        .back-btn {
            position: absolute;
            top: 35px;
            left: 35px;
            z-index: 3;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 14px 28px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        
        .back-btn i {
            transition: transform 0.3s ease;
        }
        
        .back-btn:hover i {
            transform: translateX(-3px);
        }
        
        /* Responsive Design */
        @media (max-width: 576px) {
            .login-container {
                padding: 40px 30px;
                width: 95%;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
            
            .logo {
                width: 75px;
                height: 75px;
            }
            
            .logo i {
                font-size: 2.2rem;
            }
            
            .back-btn {
                top: 20px;
                left: 20px;
                padding: 12px 22px;
                font-size: 0.88rem;
            }
            
            .form-input {
                padding: 15px 50px 15px 50px;
            }
        }
        
        /* Loading Spinner Animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div id="particles-js"></div>
    
    <!-- Floating Shapes -->
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>
    <div class="floating-shape shape3"></div>
    
    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
    
    <!-- Login Container -->
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Admin Login</h1>
            <p>Silakan masuk untuk mengakses dashboard admin</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php
endif; ?>
        
        <div class="credentials-info">
            <strong><i class="fas fa-info-circle"></i> Default Credentials:</strong>
            <div>Username: <code>admin</code></div>
            <div>Password: <code>admin123</code></div>
        </div>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user"></i> Username
                </label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" id="username" class="form-input" 
                           placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-input" 
                           placeholder="Masukkan password" required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                    <span class="caps-warning" id="capsWarning">
                        <i class="fas fa-exclamation-triangle"></i> Caps Lock
                    </span>
                </div>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya selama 30 hari</label>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk ke Dashboard
            </button>
        </form>
        
        <div class="login-footer">
            <p>
                <i class="fas fa-shield-alt"></i> Data Anda dilindungi dengan enkripsi
            </p>
        </div>
    </div>

    <script>
        // Initialize Particles.js with Enhanced Config
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 100,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#ffffff'
                },
                shape: {
                    type: 'circle'
                },
                opacity: {
                    value: 0.6,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 2,
                        size_min: 0.5,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#ffffff',
                    opacity: 0.5,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2.5,
                    direction: 'none',
                    random: true,
                    straight: false,
                    out_mode: 'out',
                    bounce: false,
                    attract: {
                        enable: true,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: true,
                        mode: 'grab'
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 140,
                        line_linked: {
                            opacity: 1
                        }
                    },
                    push: {
                        particles_nb: 4
                    }
                }
            },
            retina_detect: true
        });
        
        // Password Toggle Functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        // Caps Lock Warning
        const capsWarning = document.getElementById('capsWarning');
        
        passwordInput.addEventListener('keyup', function(e) {
            if (e.getModifierState('CapsLock')) {
                capsWarning.classList.add('show');
            } else {
                capsWarning.classList.remove('show');
            }
        });
        
        // Enhanced Focus Animation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Form Submission with Loading State
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.querySelector('.btn-login');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btn.disabled = true;
        });
        
        // Add subtle parallax effect to login container
        document.addEventListener('mousemove', (e) => {
            const container = document.querySelector('.login-container');
            const xAxis = (window.innerWidth / 2 - e.pageX) / 50;
            const yAxis = (window.innerHeight / 2 - e.pageY) / 50;
            container.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });
        
        // Reset on mouse leave
        document.addEventListener('mouseleave', () => {
            const container = document.querySelector('.login-container');
            container.style.transform = 'rotateY(0deg) rotateX(0deg)';
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
