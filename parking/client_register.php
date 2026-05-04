<?php
// c:/xampp/htdocs/parking/client_register.php
include 'config.php';
session_start();

if (isset($_SESSION['client_id'])) {
    header("Location: client_dashboard.php");
    exit();
}

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $plate = strtoupper(str_replace(' ', '', $_POST['plate']));
    $uid = strtoupper(trim(str_replace(' ', '', $_POST['uid'])));
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($pass !== $confirm_pass) {
        $msg = "Passwords do not match!";
        $msg_type = "bg-danger";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE rfid_uid = ? OR plate_number = ?");
        $check->bind_param("ss", $uid, $plate);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $msg = "RFID Card or Plate Number already registered!";
            $msg_type = "bg-danger";
        } else {
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (rfid_uid, name, plate_number, password, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $uid, $name, $plate, $hashed_pass, $email);

            if ($stmt->execute()) {
                $msg = "Registration successful! You can now login.";
                $msg_type = "bg-success";
            } else {
                $msg = "Error: " . $conn->error;
                $msg_type = "bg-danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Smart Parking Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #1e293b, #0f172a);
            margin: 0;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .register-card {
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        @media (max-width: 480px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>

<body>
    <div class="register-card card">
        <div style="text-align: center; margin-bottom: 30px;">
            <div
                style="font-size: 3rem; margin-bottom: 10px; background: var(--gradient-main); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">
                <i class="fas fa-id-card"></i>
            </div>
            <h1 style="font-size: 1.5rem; color: #fff;">CLIENT REGISTRATION</h1>
            <p class="tagline">Join our smart parking network</p>
        </div>
        <?php if ($msg): ?>
            <div class="badge <?= $msg_type ?>"
                style="display: block; padding: 12px; margin-bottom: 20px; text-align: center; border-radius: 8px;">
                <i class="fas <?= $msg_type == 'bg-success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="search-box" style="margin-bottom: 15px;">
                <i class="fas fa-user"></i>
                <input type="text" name="name" class="form-control search-input" placeholder="Full Name" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <div class="search-box" style="margin-bottom: 15px;"><i class="fas fa-car"></i><input type="text"
                            name="plate" class="form-control search-input" placeholder="Plate Number" required></div>
                </div>
                <div class="form-group">
                    <div class="search-box" style="margin-bottom: 15px;"><i class="fas fa-envelope"></i><input
                            type="email" name="email" class="form-control search-input" placeholder="Email" required>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <div class="search-box" style="flex:1; margin-bottom: 0;"><i class="fas fa-rss"></i><input type="text"
                        name="uid" id="uid_input" class="form-control search-input" placeholder="RFID UID" required>
                </div>
                <button type="button" onclick="getLastScan()" class="btn btn-warning"
                    style="white-space:nowrap; padding: 0 15px;"><i class="fas fa-bolt"></i> Tap Card</button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <div class="search-box" style="margin-bottom: 15px;"><i class="fas fa-lock"></i><input
                            type="password" name="password" class="form-control search-input" placeholder="Password"
                            required></div>
                </div>
                <div class="form-group">
                    <div class="search-box" style="margin-bottom: 15px;"><i class="fas fa-shield-halved"></i><input
                            type="password" name="confirm_password" class="form-control search-input"
                            placeholder="Confirm" required></div>
                </div>
            </div>
            <button type="submit" name="register" class="btn btn-success"
                style="width: 100%; justify-content: center; padding: 15px; font-size: 1rem;"><i
                    class="fas fa-user-check"></i> CREATE ACCOUNT</button>
        </form>
        <div style="margin-top: 25px; text-align: center;">
            <p style="color: var(--text-muted); font-size: 0.9rem;">Already have an account? <a href="client_login.php"
                    style="color: var(--accent-primary); text-decoration: none;">Login here</a></p>
            <a href="index.php"
                style="color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: block; margin-top: 10px;"><i
                    class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
    <script>
        function getLastScan() {
            const btn = event.currentTarget; const icon = btn.querySelector('i'); icon.className = 'fas fa-spinner fa-spin';
            fetch('api_get_last_scan.php').then(r => r.json()).then(d => { if (d.uid) { document.getElementById('uid_input').value = d.uid; } else { alert("Please tap your card first."); } }).finally(() => { icon.className = 'fas fa-bolt'; });
        }
    </script>
</body>

</html>