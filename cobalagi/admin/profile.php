<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $currentUsername = $_SESSION['admin_username'];

    // Update Username
    if (!empty($username) && $username !== $currentUsername) {
        // Cek availability
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM admin_users WHERE username = ? LIMIT 1");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $username);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $exists = mysqli_num_rows($checkResult) > 0;
            mysqli_stmt_close($checkStmt);

            if ($exists) {
                $error = "Username sudah digunakan!";
            } else {
                $updateUserStmt = mysqli_prepare($conn, "UPDATE admin_users SET username = ? WHERE username = ?");
                if ($updateUserStmt) {
                    mysqli_stmt_bind_param($updateUserStmt, "ss", $username, $currentUsername);
                    mysqli_stmt_execute($updateUserStmt);
                    mysqli_stmt_close($updateUserStmt);
                    $_SESSION['admin_username'] = $username;
                    $currentUsername = $username;
                    $success = "Username berhasil diperbarui. ";
                } else {
                    $error = "Gagal memperbarui username.";
                }
            }
        } else {
            $error = "Gagal mengecek username.";
        }
    }

    // Update Password
    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $error .= "Password konfirmasi tidak cocok!";
        }
        else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $updatePassStmt = mysqli_prepare($conn, "UPDATE admin_users SET password = ? WHERE username = ?");
            if ($updatePassStmt) {
                mysqli_stmt_bind_param($updatePassStmt, "ss", $hashed, $currentUsername);
                mysqli_stmt_execute($updatePassStmt);
                mysqli_stmt_close($updatePassStmt);
                $success .= "Password berhasil diperbarui.";
            } else {
                $error .= "Gagal memperbarui password.";
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
    <title>Profile Admin - Pondok Es Teller ZR</title>
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
            --radius: 16px;
            --shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Outfit', sans-serif; }
        body { background: var(--light); min-height: 100vh; color: var(--dark); padding: 50px 20px; }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        h1 { margin-bottom: 30px; text-align: center; color: var(--dark); }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control {
            width: 100%; padding: 12px 15px;
            border: 2px solid #eee; border-radius: 10px;
            font-size: 1rem; transition: 0.3s;
        }
        .form-control:focus { border-color: var(--primary); outline: none; }
        
        .btn {
            width: 100%; padding: 14px;
            background: var(--primary); color: white;
            border: none; border-radius: 10px;
            font-size: 1.1rem; font-weight: 700;
            cursor: pointer; transition: 0.3s;
            margin-top: 10px;
        }
        .btn:hover { background: var(--primary-dark); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background: #E8F5E9; color: #2E7D32; }
        .alert-error { background: #FFEBEE; color: #C62828; }
        
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--gray); text-decoration: none; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="container">
    <h1>Pengaturan Akun</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php
elseif ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php
endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?php echo $_SESSION['admin_username']; ?>" required>
        </div>
        
        <div style="border-top:1px solid #eee; margin:30px 0;"></div>
        <p style="margin-bottom:20px; color:var(--gray); font-size:0.9rem;">Biarkan kosong jika tidak ingin mengganti password</p>
        
        <div class="form-group">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password" class="form-control">
        </div>
        
        <div class="form-group">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="confirm_password" class="form-control">
        </div>
        
        <button type="submit" class="btn">Simpan Perubahan</button>
    </form>
    
    <a href="admin.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
</div>

</body>
</html>
