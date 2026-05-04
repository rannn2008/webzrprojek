<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
        }

        .shape2 {
            background: linear-gradient(135deg, #d2a679, #8b5a2b);
            width: 250px;
            height: 250px;
            top: 50%;
            right: -5%;
        }

        .shape3 {
            background: linear-gradient(135deg, #c19a6b, #5c3a18);
            width: 350px;
            height: 350px;
            bottom: -10%;
            left: 30%;
        }

        @keyframes float {

            0%,
            100% {
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
            border-radius: 32px;
            padding: 50px 45px;
            width: 480px;
            max-width: 92%;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37), 0 0 0 1px rgba(255, 255, 255, 0.18) inset;
            border: 1px solid rgba(255, 255, 255, 0.18);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
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
            box-shadow: 0 20px 40px rgba(139, 90, 43, 0.6);
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .logo i {
            font-size: 2.8rem;
            color: white;
        }

        .login-header h1 {
            font-size: 2.2rem;
            color: white;
            margin-bottom: 10px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            color: #fee2e2;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: white;
            font-weight: 600;
            font-size: 0.92rem;
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
        }

        .form-input {
            width: 100%;
            padding: 16px 52px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: white;
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .back-btn {
            position: absolute;
            top: 35px;
            left: 35px;
            z-index: 3;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div id="particles-js"></div>
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>
    <div class="floating-shape shape3"></div>

    <a href="{{ url('/') }}" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="login-container">
        <div class="login-header">
            <div class="logo"><i class="fas fa-user-shield"></i></div>
            <h1>Admin Login</h1>
            <p>Silakan masuk ke dashboard admin</p>
        </div>

        @if($errors->any())
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="form-group">
                <label class="form-label"><i class="fas fa-user"></i> Username</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-input" placeholder="Username"
                        value="{{ old('username') }}" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk ke Dashboard
            </button>
        </form>
    </div>

    <script>
        particlesJS('particles-js', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: '#ffffff', opacity: 0.4, width: 1 },
                move: { enable: true, speed: 2, direction: 'none', random: true, straight: false, out_mode: 'out' }
            },
            interactivity: {
                events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' } },
                modes: { grab: { distance: 140, line_linked: { opacity: 1 } } }
            }
        });
    </script>
</body>

</html>