<?php
// c:/xampp/htdocs/parking/login.php
include "config.php";
session_start();

if (isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST["username"];
    $pass = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password, name FROM admins WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (password_verify($pass, $row["password"])) {
            $_SESSION["admin_id"] = $row["id"];
            $_SESSION["admin_name"] = $row["name"];
            header("Location: index.php");
            exit();
        }
    }
    $error = "Invalid username or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart Parking Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #1e293b, #0f172a);
            margin: 0;
            font-family: "Poppins", sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-card card">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 3rem; margin-bottom: 10px; background: var(--gradient-main); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">
                <i class="fas fa-parking-circle"></i>
            </div>
            <h1 style="font-size: 1.5rem; color: #fff;">ADMIN ACCESS</h1>
            <p class="tagline">Enter credentials to manage system</p>
        </div>

        <?php if ($error): ?>
            <div class="badge bg-danger" style="display: block; padding: 12px; margin-bottom: 20px; text-align: center; border-radius: 8px;">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="search-box" style="margin-bottom: 20px;">
                <i class="fas fa-user-shield"></i>
                <input type="text" name="username" class="form-control search-input" placeholder="Username" required autofocus>
            </div>
            
            <div class="search-box" style="margin-bottom: 25px;">
                <i class="fas fa-key"></i>
                <input type="password" name="password" class="form-control search-input" placeholder="Password" required>
            </div>

            <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center; padding: 15px; font-size: 1rem;">
                <i class="fas fa-right-to-bracket"></i> AUTHORIZE LOGIN
            </button>
        </form>

        <div style="margin-top: 25px; text-align: center;">
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-arrow-left"></i> Back to Public Dashboard
            </a>
        </div>
    </div>
</body>
</html>
