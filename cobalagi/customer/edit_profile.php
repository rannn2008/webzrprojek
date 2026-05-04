<?php
require_once '../config/config.php';
require_once '../includes/db_helper.php';

// Cek login customer
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get customer data
$customer = fetch_one(secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$customer_id]));

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama = bersihkan_input($_POST['nama'], $conn);
    $alamat = bersihkan_input($_POST['alamat'], $conn);
    $error_msg = null;
    $new_file_name = null;

    // Handle foto profil upload
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === 0) {
        $upload_dir = '../assets/images/profiles/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $_FILES['foto_profil']['name'];
        $file_size = $_FILES['foto_profil']['size'];
        $file_tmp = $_FILES['foto_profil']['tmp_name'];
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi tipe file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_type, $allowed_extensions)) {
            // Validasi ukuran (maksimal 2MB)
            if ($file_size <= 2 * 1024 * 1024) {
                // Generate nama file unik
                $new_file_name = 'profile_' . $customer_id . '_' . time() . '.' . $file_type;
                $target_file = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Hapus foto lama jika ada 
                    if (!empty($customer['foto_profil']) && file_exists($upload_dir . $customer['foto_profil'])) {
                        @unlink($upload_dir . $customer['foto_profil']);
                    }
                }
                else {
                    $error_msg = "Gagal mengunggah foto profil ke direktori server.";
                }
            }
            else {
                $error_msg = "Ukuran foto terlalu besar. Maksimal 2MB.";
            }
        }
        else {
            $error_msg = "Tipe file tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
        }
    }

    if (!$error_msg) {
        if ($new_file_name) {
            secure_query($conn, "UPDATE customers SET nama = ?, alamat = ?, foto_profil = ? WHERE id = ?", "sssi", [$nama, $alamat, $new_file_name, $customer_id], false);
        } else {
            secure_query($conn, "UPDATE customers SET nama = ?, alamat = ? WHERE id = ?", "ssi", [$nama, $alamat, $customer_id], false);
        }
        
        $_SESSION['customer_nama'] = $nama;
        header("Location: customer_dashboard.php?updated=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5a2b;
            --primary-dark: #5c3a18;
            --primary-light: #c19a6b;
            --secondary: #d2a679;
            --accent: #e6ccb8;
            --dark: #3e2723;
            --light: #fdfbf7;
            --gray: #9E9E9E;
            --bg-color: #f4eee6;
            --card-bg: #ffffff;
            --shadow: 0 10px 40px -10px rgba(139, 90, 43, 0.1);
            --radius: 20px;
            --transition: all 0.3s ease;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            background-color: var(--bg-color);
            color: var(--dark);
        }
        
        .navbar {
            background: var(--card-bg);
            padding: 15px 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark);
        }
        
        .nav-brand i { font-size: 1.8rem; color: var(--primary); }
        .nav-brand h2 { font-size: 1.3rem; font-family: 'Playfair Display'; font-weight: 800;}
        .nav-brand span { color: var(--primary); font-family: 'Outfit';}
        
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { color: var(--gray); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; transition: var(--transition); }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); background: rgba(139, 90, 43, 0.1); }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .section-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(139, 90, 43, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Playfair Display';
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); font-size: 0.95rem; }
        .form-input {
            width: 100%; padding: 12px 15px; border: 2px solid rgba(139, 90, 43, 0.1); border-radius: 12px; font-size: 1rem; transition: var(--transition);
            background: var(--bg-color); font-family: 'Outfit', sans-serif;
        }
        .form-input:focus { outline: none; border-color: var(--primary); background: var(--card-bg); box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1); }
        .form-input:disabled { background: #f0f0f0; cursor: not-allowed; opacity: 0.7;}
        
        .btn-save {
            background: var(--primary); color: white; border: none; padding: 14px 25px; border-radius: 12px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-top: 10px;
            font-family: 'Outfit', sans-serif; box-shadow: 0 5px 15px rgba(139, 90, 43, 0.3);
        }
        .btn-save:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(139, 90, 43, 0.4);}
        
        .btn-cancel {
            background: transparent; color: var(--gray); border: 2px solid var(--gray); padding: 12px 25px; border-radius: 12px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; margin-top: 15px; text-decoration: none;
        }
        .btn-cancel:hover { background: var(--gray); color: white; }
        
        .alert-error { background: #FFEBEE; color: #C62828; border: 1px solid #ffcdd2; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 0.95rem; display: flex; align-items: center; gap: 10px;}
        
        /* Loading Overlay */
        .loading-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.8); z-index: 2000; display: none; align-items: center; justify-content: center; flex-direction: column; backdrop-filter: blur(5px); }
        .loading-spinner { width: 50px; height: 50px; border: 5px solid rgba(139, 90, 43, 0.2); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../index.php" class="nav-brand">
            <i class="fas fa-mug-hot"></i>
            <div>
                <h2>Pondok Es Teller ZR</h2>
                <span>Taste the Freshness</span>
            </div>
        </a>
        <div class="nav-links">
            <a href="customer_dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="section-card">
            <h3 class="section-title">
                <i class="fas fa-user-edit"></i> Edit Profil
            </h3>
            
            <?php if (isset($error_msg) && $error_msg): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="profile-form" enctype="multipart/form-data" onsubmit="document.getElementById('loading').style.display='flex'">
                <input type="hidden" name="update_profile" value="1">
                
                <div style="display: flex; gap: 40px; align-items: flex-start; margin-bottom: 30px; flex-wrap: wrap;">
                    
                    <!-- Profile Picture Upload Area -->
                    <div style="text-align: center; flex-shrink: 0; margin: 0 auto;">
                        <div id="preview-container" style="width: 150px; height: 150px; border-radius: 50%; background: var(--bg-color); overflow: hidden; margin-bottom: 15px; border: 4px solid var(--primary); box-shadow: var(--shadow); display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($customer['foto_profil']) && file_exists('../assets/images/profiles/' . $customer['foto_profil'])): ?>
                                <img src="../assets/images/profiles/<?php echo $customer['foto_profil']; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user" style="font-size: 4rem; color: var(--primary-light);"></i>
                            <?php endif; ?>
                        </div>
                        <label for="foto_profil" style="cursor: pointer; color: var(--primary); font-size: 1rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(139, 90, 43, 0.1); border-radius: 30px; transition: var(--transition);">
                            <i class="fas fa-camera"></i> Ganti Foto Profil
                        </label>
                        <input type="file" id="foto_profil" name="foto_profil" accept="image/png, image/jpeg, image/jpg" style="display: none;">
                        <p style="font-size: 0.8rem; color: var(--gray); margin-top: 10px;">Format: JPG, PNG. Maks 2MB.</p>
                    </div>
                    
                    <!-- Form Fields Area -->
                    <div style="flex: 1; min-width: 300px;">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-input" value="<?php echo htmlspecialchars($customer['nama']); ?>" required placeholder="Masukkan nama lengkap">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-input" value="+<?php echo htmlspecialchars($customer['whatsapp']); ?>" disabled title="Nomor WhatsApp tidak dapat diubah karena digunakan untuk login.">
                            <small style="color: var(--gray); font-size: 0.8rem; margin-top: 5px; display: block;">* Nomor WhatsApp tidak dapat diubah (digunakan untuk login).</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($customer['email'] ?? '-'); ?>" disabled>
                            <small style="color: var(--gray); font-size: 0.8rem; margin-top: 5px; display: block;">* Email saat ini tidak dapat diubah.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Alamat Lengkap (Untuk Pengiriman)</label>
                            <textarea name="alamat" class="form-input" rows="4" placeholder="Masukkan detail alamat untuk pengiriman pesanan Anda..."><?php echo htmlspecialchars($customer['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Simpan Perubahan Profil
                    </button>
                </div>
                <a href="customer_dashboard.php" class="btn-cancel">
                    Batal
                </a>
            </form>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading">
        <div class="loading-spinner"></div>
        <p style="margin-top: 15px; font-weight: 600; color: var(--primary);">Menyimpan Perubahan...</p>
    </div>

    <script>
        // Preview foto profil
        document.getElementById('foto_profil').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const imgContainer = document.getElementById('preview-container');
                    imgContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>
