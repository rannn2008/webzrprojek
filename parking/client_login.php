<?php
// c:/xampp/htdocs/parking/client_login.php
include 'config.php';
session_start();

if (isset($_SESSION['client_id'])) {
    header("Location: client_dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $user = strtoupper(str_replace(' ', '', $_POST['username']));
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE plate_number = ? OR email = ?");
    $stmt->bind_param("ss", $user, $user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if ($row['password'] && password_verify($pass, $row['password'])) {
            $_SESSION['client_id'] = $row['id'];
            $_SESSION['client_name'] = $row['name'];
            header("Location: client_dashboard.php");
            exit();
        }
    }
    $error = "Invalid plate number or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Login | Smart Parking Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: radial-gradient(circle at top left, #1e293b, #0f172a); margin: 0; font-family: 'Poppins', sans-serif; }
        .login-card { width: 100%; max-width: 400px; animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="login-card card">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 3.5rem; margin-bottom: 10px; background: linear-gradient(135deg, #00e5ff, #0066ff); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;"><i class="fas fa-user-circle"></i></div>
            <h1 style="font-size: 1.5rem; color: #fff;">CLIENT PORTAL</h1>
            <p class="tagline">Enter plate number to view your status</p>
        </div>
        <?php if ($error): ?>
            <div class="badge bg-danger" style="display: block; padding: 12px; margin-bottom: 20px; text-align: center; border-radius: 8px;"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="search-box" style="margin-bottom: 20px;"><i class="fas fa-car-side"></i><input type="text" name="username" class="form-control search-input" placeholder="Plate Number" required autofocus></div>
            <div class="search-box" style="margin-bottom: 25px;"><i class="fas fa-key"></i><input type="password" name="password" class="form-control search-input" placeholder="Password" required></div>
            <button type="submit" name="login" class="btn btn-success" style="width: 100%; justify-content: center; padding: 15px; font-size: 1rem;"><i class="fas fa-right-to-bracket"></i> CLIENT LOGIN</button>
        </form>
        <div style="margin-top: 25px; text-align: center;">
            <p style="color: var(--text-muted); font-size: 0.9rem;">No account yet? <a href="client_register.php" style="color: var(--accent-primary); text-decoration: none;">Register Now</a></p>
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: block; margin-top: 10px;"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</body>
</html>
