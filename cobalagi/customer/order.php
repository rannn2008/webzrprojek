<?php
ob_start();
require_once '../config/config.php';
require_once '../includes/db_helper.php';

function generate_order_code_secure()
{
    try {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $bytes = random_bytes(3);
        $suffix = '';
        for ($i = 0; $i < 3; $i++) {
            $suffix .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }
    } catch (Exception $e) {
        $suffix = strtoupper(substr(base_convert((string) mt_rand(1000, 46655), 10, 36), 0, 3));
    }

    // 20 chars max: ORD (3) + YmdHis (14) + random suffix (3)
    return 'ORD' . date('YmdHis') . $suffix;
}

$saldo_gopay = null;
$saldo_ovo = null;
$saldo_dana = null;

// Cek apakah form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Input Data
    $nama = trim($_POST['nama'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $metode_bayar = $_POST['metode_bayar'] ?? 'cod';
    $metode_pengiriman = $_POST['metode_pengiriman'] ?? 'pickup';
    $catatan = trim($_POST['catatan'] ?? '');

    // Cart Data validation
    $cart_json = $_POST['cart_data'] ?? '[]';
    $cart = json_decode($cart_json, true);
    if (!is_array($cart)) {
        $cart = [];
    }

    // Get Customer ID if logged in
    $customer_id = 0;

    if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
        $customer_id = (int) $_SESSION['customer_id'];

        $check = fetch_one(secure_query($conn, "SELECT alamat, saldo_gopay, saldo_ovo, saldo_dana FROM customers WHERE id = ? LIMIT 1", "i", [$customer_id]));

        if ($check) {
            $saldo_gopay = $check['saldo_gopay'];
            $saldo_ovo = $check['saldo_ovo'];
            $saldo_dana = $check['saldo_dana'];

            // Update customer address once when still empty
            if (!empty($alamat) && empty($check['alamat'])) {
                secure_query($conn, "UPDATE customers SET alamat = ? WHERE id = ?", "si", [$alamat, $customer_id]);
            }
        }
    }

    if (empty($cart)) {
        $error = "Keranjang belanja kosong! Silakan pilih menu terlebih dahulu.";
    } else {
        // Calculate Total
        $total_harga = 0;
        foreach ($cart as $item) {
            $total_harga += (int)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0);
        }

        // Validate E-Wallet Balance
        $is_ewallet = in_array($metode_bayar, ['gopay', 'ovo', 'dana'], true);
        $saldo_column = "saldo_" . $metode_bayar;
        $valid_ewallet = false;

        if ($is_ewallet) {
            if ($customer_id <= 0) {
                $error = "Anda harus login untuk menggunakan $metode_bayar.";
            } else {
                $check_saldo = fetch_one(secure_query($conn, "SELECT $saldo_column FROM customers WHERE id = ? LIMIT 1", "i", [$customer_id]));

                if (!$check_saldo || $check_saldo[$saldo_column] === null) {
                    $error = "Akun $metode_bayar Anda belum terhubung.";
                } elseif ((int) $check_saldo[$saldo_column] < $total_harga) {
                    $error = "Saldo $metode_bayar Anda tidak mencukupi. (Sisa: Rp " . number_format((int) $check_saldo[$saldo_column], 0, ',', '.') . ")";
                } else {
                    $valid_ewallet = true;
                }
            }
        }

        if (!isset($error)) {
            mysqli_begin_transaction($conn);

            try {
                $order_id = 0;
                $order_code = '';
                $maxAttempts = 8;
                
                for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                    $order_code = generate_order_code_secure();
                    $orderRes = secure_query($conn, "INSERT INTO orders 
                        (order_code, customer_id, nama_customer, whatsapp, alamat, metode_bayar, metode_pengiriman, catatan, total_harga, status)
                        VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, 'new')", 
                        "sissssssi", 
                        [$order_code, $customer_id, $nama, $whatsapp, $alamat, $metode_bayar, $metode_pengiriman, $catatan, $total_harga], 
                        false);

                    if ($orderRes) {
                        $order_id = (int) mysqli_insert_id($conn);
                        break;
                    }

                    // Duplicate order_code (errno 1062)
                    if (mysqli_errno($conn) !== 1062) {
                        throw new Exception('Gagal menyimpan pesanan.');
                    }
                }

                if ($order_id <= 0) {
                    throw new Exception('Gagal membuat kode pesanan unik. Coba lagi.');
                }

                foreach ($cart as $item) {
                    $product_id = (int) ($item['id'] ?? 0);
                    $product_name = trim((string) ($item['name'] ?? 'Produk'));
                    $price = (int) ($item['price'] ?? 0);
                    $quantity = (int) ($item['quantity'] ?? 0);

                    if ($product_id <= 0 || $price <= 0 || $quantity <= 0) {
                        throw new Exception('Data item pesanan tidak valid.');
                    }

                    $subtotal = $price * $quantity;
                    secure_query($conn, "INSERT INTO order_items (order_id, product_id, nama_product, harga, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)", 
                        "iisiii", [$order_id, $product_id, $product_name, $price, $quantity, $subtotal], false);
                }

                // Deduct Wallet Balance atomically
                if ($is_ewallet && $valid_ewallet) {
                    $walletRes = secure_query($conn, "UPDATE customers SET $saldo_column = $saldo_column - ? WHERE id = ? AND $saldo_column >= ?", 
                        "iii", [$total_harga, $customer_id, $total_harga], false);
                    if (!$walletRes || mysqli_affected_rows($conn) <= 0) {
                        throw new Exception('Saldo e-wallet tidak cukup atau akun tidak valid.');
                    }
                }

                // Log activity
                $logDetails = "Pesanan baru: $order_code oleh $nama";
                secure_query($conn, "INSERT INTO activity_logs (admin_user, action, details) VALUES ('system', 'New Order', ?)", "s", [$logDetails], false);

                mysqli_commit($conn);

                // Redirect Success
                header("Location: order_succes.php?kode=" . urlencode($order_code) . "&clear=1");
                exit();
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = "Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.";
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
    <title>Checkout - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5a2b;
            --primary-dark: #5c3a18;
            --primary-light: #c19a6b;
            --secondary: #d2a679;
            --dark: #121212;
            --light: #FFFFFF;
            --gray: #9E9E9E;
            --bg-color: #F5F7FA;
            --shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
            --radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(139, 90, 43, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(210, 166, 121, 0.05) 0px, transparent 50%);
            min-height: 100vh;
            color: var(--dark);
            padding: 40px 20px;
        }
        
        .container { max-width: 1100px; margin: 0 auto; }
        
        .step-header {
            display: flex;
            justify-content: center;
            margin-bottom: 50px;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
            font-weight: 600;
        }
        
        .step.active { color: var(--primary); }
        .step-icon {
            width: 35px; height: 35px;
            border-radius: 50%;
            background: #eee;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }
        
        .step.active .step-icon {
            background: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(139, 90, 43, 0.3);
        }
        
        .step-line {
            width: 80px; height: 2px;
            background: #eee;
            margin: 0 15px;
            align-self: center;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        .card {
            background: white;
            border-radius: var(--radius);
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex; align-items: center; gap: 10px;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.95rem; }
        
        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .pay-opt input { display: none; }
        
        .pay-label {
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .pay-opt input:checked + .pay-label {
            border-color: var(--primary);
            background: rgba(139, 90, 43, 0.05);
            color: var(--primary);
            font-weight: 700;
        }
        
        .summary-card { background: #FAFAFA; }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e0e0e0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 800;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .btn-pay {
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 25px;
            transition: var(--transition);
            box-shadow: 0 10px 30px rgba(139, 90, 43, 0.3);
        }
        
        .btn-pay:hover { transform: translateY(-3px); }
        
        .alert {
            background: #ffebee; color: #c62828;
            padding: 15px; border-radius: 12px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            text-decoration: none;
            margin-bottom: 20px;
        }
        
        @media (max-width: 900px) {
            .checkout-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Menu</a>

    <div class="step-header">
        <div class="step active"><div class="step-icon">1</div><span>Menu</span></div>
        <div class="step-line"></div>
        <div class="step active"><div class="step-icon">2</div><span>Checkout</span></div>
        <div class="step-line"></div>
        <div class="step"><div class="step-icon">3</div><span>Selesai</span></div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="orderForm">
        <div class="checkout-grid">
            <div class="card">
                <h2><i class="fas fa-user-circle"></i> Informasi Pemesan</h2>
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-input" value="<?php echo htmlspecialchars($_SESSION['customer_nama'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="tel" name="whatsapp" class="form-input" value="<?php echo htmlspecialchars($_SESSION['customer_whatsapp'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat Pengiriman</label>
                    <textarea name="alamat" class="form-input" rows="3" id="alamatField"><?php echo htmlspecialchars($check['alamat'] ?? ''); ?></textarea>
                </div>
                
                <h2 style="margin-top:30px;"><i class="fas fa-truck"></i> Pengiriman & Pembayaran</h2>
                
                <div class="payment-options" style="margin-bottom:25px;">
                    <label class="pay-opt">
                        <input type="radio" name="metode_pengiriman" value="pickup" checked onchange="toggleAlamat()">
                        <div class="pay-label"><i class="fas fa-store"></i><br>Ambil Sendiri</div>
                    </label>
                    <label class="pay-opt">
                        <input type="radio" name="metode_pengiriman" value="delivery" onchange="toggleAlamat()">
                        <div class="pay-label"><i class="fas fa-motorcycle"></i><br>Delivery</div>
                    </label>
                </div>
                
                <div class="payment-options">
                    <label class="pay-opt">
                        <input type="radio" name="metode_bayar" value="cod" checked onchange="checkWalletStatus()">
                        <div class="pay-label">Cash On Delivery</div>
                    </label>
                    <label class="pay-opt">
                        <input type="radio" name="metode_bayar" value="gopay" onchange="checkWalletStatus()">
                        <div class="pay-label">GoPay</div>
                    </label>
                    <label class="pay-opt">
                        <input type="radio" name="metode_bayar" value="ovo" onchange="checkWalletStatus()">
                        <div class="pay-label">OVO</div>
                    </label>
                    <label class="pay-opt">
                        <input type="radio" name="metode_bayar" value="dana" onchange="checkWalletStatus()">
                        <div class="pay-label">DANA</div>
                    </label>
                </div>
                
                <div id="ewalletInfo" style="display:none; margin-top:15px; padding:15px; background:rgba(139,90,43,0.05); border-radius:12px;"></div>

                <div class="form-group" style="margin-top:20px;">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea name="catatan" class="form-input" rows="2"></textarea>
                </div>
            </div>
            
            <div class="card summary-card">
                <h2>Ringkasan Pesanan</h2>
                <div id="orderItems"></div>
                <div class="total-row"><span>Total</span><span id="displayTotal">Rp 0</span></div>
                <button type="submit" class="btn-pay" id="btnSubmit">Konfirmasi Pesanan</button>
            </div>
        </div>
        <input type="hidden" name="cart_data" id="cartData">
    </form>
</div>

<script>
    const cid = <?php echo $customer_id ? 'true' : 'false'; ?>;
    const balances = {
        gopay: <?php echo json_encode($saldo_gopay); ?>,
        ovo: <?php echo json_encode($saldo_ovo); ?>,
        dana: <?php echo json_encode($saldo_dana); ?>
    };
    let currentTotal = 0;

    document.addEventListener('DOMContentLoaded', () => {
        const cart = JSON.parse(localStorage.getItem('cart_esteller') || '[]');
        document.getElementById('cartData').value = JSON.stringify(cart);
        
        let html = '';
        cart.forEach(item => {
            const sub = item.price * item.quantity;
            currentTotal += sub;
            html += `<div class="cart-item"><span>${item.name} x${item.quantity}</span><span>Rp ${sub.toLocaleString()}</span></div>`;
        });
        document.getElementById('orderItems').innerHTML = html;
        document.getElementById('displayTotal').innerText = 'Rp ' + currentTotal.toLocaleString();
        
        toggleAlamat();
    });

    function toggleAlamat() {
        const method = document.querySelector('input[name="metode_pengiriman"]:checked').value;
        const field = document.getElementById('alamatField');
        field.required = (method === 'delivery');
        field.parentElement.style.opacity = (method === 'delivery' ? '1' : '0.5');
    }

    function checkWalletStatus() {
        const method = document.querySelector('input[name="metode_bayar"]:checked').value;
        const info = document.getElementById('ewalletInfo');
        const btn = document.getElementById('btnSubmit');
        
        if (method === 'cod') {
            info.style.display = 'none';
            btn.disabled = false;
            return;
        }
        
        info.style.display = 'block';
        if (!cid) {
            info.innerHTML = 'Silakan login untuk e-wallet.';
            btn.disabled = true;
            return;
        }
        
        const balance = balances[method];
        if (balance === null) {
            info.innerHTML = 'E-wallet belum terhubung.';
            btn.disabled = true;
        } else if (balance < currentTotal) {
            info.innerHTML = `Saldo tidak cukup (Rp ${balance.toLocaleString()})`;
            btn.disabled = true;
        } else {
            info.innerHTML = `Saldo tersedia: Rp ${balance.toLocaleString()}`;
            btn.disabled = false;
        }
    }
</script>
</body>
</html>
