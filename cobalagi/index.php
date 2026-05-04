<?php
require_once 'config/config.php';
require_once 'includes/db_helper.php';

// Get customer data if logged in
$customer_data = null;
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    $cust_id = $_SESSION['customer_id'];
    $query_cust = secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$cust_id]);
    if ($query_cust && $query_cust->num_rows > 0) {
        $customer_data = $query_cust->fetch_assoc();
        // Update session name if it changed in DB
        $_SESSION['customer_nama'] = $customer_data['nama'];

        $saldo_gopay = $customer_data['saldo_gopay'];
        $saldo_ovo = $customer_data['saldo_ovo'];
        $saldo_dana = $customer_data['saldo_dana'];
    }
}
?>
<script>
    const cid = <?php echo isset($_SESSION['customer_id']) ? 'true' : 'false'; ?>;
    const gopayBalance = <?php echo isset($saldo_gopay) && $saldo_gopay !== null ? $saldo_gopay : 'null'; ?>;
    const ovoBalance = <?php echo isset($saldo_ovo) && $saldo_ovo !== null ? $saldo_ovo : 'null'; ?>;
    const danaBalance = <?php echo isset($saldo_dana) && $saldo_dana !== null ? $saldo_dana : 'null'; ?>;
</script>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondok Es Teller ZR - Taste the Freshness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap"
        rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* CSS reset and variables */
        :root {
            --primary: #8b5a2b;
            /* Brown */
            --primary-dark: #5c3a18;
            /* Dark Brown */
            --primary-light: #c19a6b;
            --secondary: #d2a679;
            /* Light Brown/Caramel */
            --bg-color: #fdfbf7;
            /* Warm Off-white */
            --card-bg: #f4eee6;
            /* Slightly darker than bg for contrast */
            --dark: #3e2723;
            --text-dark: #4e342e;
            --text-muted: #795548;
            --white: #ffffff;
            --transition: var(--transition);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 90px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Playfair Display', serif;
            color: var(--dark);
            font-weight: 700;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Top Information Bar */
        .top-bar {
            background-color: var(--dark);
            color: #d7ccc8;
            font-size: 0.85rem;
            padding: 10px 0;
        }

        .top-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar-left {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .top-bar-left span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .top-bar-socials {
            display: flex;
            gap: 15px;
        }

        .top-bar-socials a {
            color: #d7ccc8;
            text-decoration: none;
            transition: var(--transition);
        }

        .top-bar-socials a:hover {
            color: var(--secondary);
        }

        /* Main Header */
        header {
            background-color: var(--white);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: var(--primary);
            color: var(--white);
            padding: 15px 30px;
            height: 100px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.2);
            text-decoration: none;
            position: relative;
            top: 0;
        }

        .logo i {
            font-size: 2rem;
        }

        .logo h2 {
            color: var(--white);
            font-size: 1.5rem;
            letter-spacing: 1px;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            line-height: 1;
        }

        .logo span {
            font-size: 0.75rem;
            display: block;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 400;
            opacity: 0.8;
            margin-top: 4px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .action-icon {
            color: var(--dark);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            background: none;
            border: none;
        }

        .action-icon:hover {
            color: var(--primary);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--primary);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .btn-signup {
            background-color: var(--primary);
            color: var(--white);
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: 2px solid var(--primary);
            font-family: 'Outfit', sans-serif;
        }

        .btn-signup:hover {
            background-color: transparent;
            color: var(--primary);
        }

        /* Hero Section */
        .hero {
            position: relative;
            background-image: linear-gradient(rgba(30, 20, 15, 0.75), rgba(30, 20, 15, 0.85)), url('https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1447&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 550px;
            display: flex;
            align-items: center;
            color: var(--white);
            padding: 80px 0;
        }

        .hero-content {
            max-width: 650px;
        }

        .hero h1 {
            font-size: 4.5rem;
            color: var(--white);
            line-height: 1.1;
            margin-bottom: 24px;
        }

        .hero p {
            font-size: 1.15rem;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
            letter-spacing: 0.5px;
        }

        .btn-hero {
            background-color: var(--primary-light);
            color: var(--white);
            padding: 16px 36px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.1rem;
            display: inline-block;
            transition: var(--transition);
        }

        .btn-hero:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.4);
        }

        .pagination-dots {
            display: flex;
            gap: 8px;
            margin-top: 60px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid var(--primary-light);
            cursor: pointer;
            transition: var(--transition);
            background: transparent;
        }

        .dot.active {
            background-color: var(--primary-light);
        }

        /* General Section */
        .section {
            padding: 90px 0;
            background-color: var(--bg-color);
            text-align: center;
        }

        .section-title {
            font-size: 2.8rem;
            margin-bottom: 15px;
            color: var(--dark);
            font-weight: 800;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
            margin-bottom: 45px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 60px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: transparent;
            border: none;
            font-size: 1.05rem;
            color: var(--text-muted);
            font-weight: 500;
            cursor: pointer;
            padding: 10px 24px;
            border-radius: 30px;
            transition: var(--transition);
            border: 1px solid transparent;
            font-family: 'Outfit';
        }

        .tab-btn.active {
            background-color: var(--primary);
            color: var(--white);
            box-shadow: 0 8px 15px rgba(139, 90, 43, 0.2);
        }

        .tab-btn:hover:not(.active) {
            color: var(--primary);
            border: 1px solid var(--primary-light);
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 40px;
            text-align: left;
        }

        /* Menu Card */
        .menu-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(139, 90, 43, 0.1);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(139, 90, 43, 0.15);
        }

        .card-img-wrapper {
            height: 220px;
            overflow: hidden;
            margin: 15px 15px 0 15px;
            border-radius: 12px;
            position: relative;
        }

        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .menu-card:hover .card-img-wrapper img {
            transform: scale(1.08);
        }

        .card-pills {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            gap: 5px;
        }

        .card-badge {
            background-color: var(--primary);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .card-body {
            padding: 25px 20px;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.3rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--dark);
            font-family: 'Outfit', sans-serif;
        }

        .card-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price-wrapper {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .price-fake {
            text-decoration: line-through;
            color: var(--text-muted);
            font-size: 1.05rem;
            opacity: 0.7;
        }

        .price-real {
            font-size: 1.5rem;
            color: var(--primary-dark);
            font-weight: 800;
        }

        .card-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .btn-action {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background-color: var(--white);
            color: var(--primary-light);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            cursor: pointer;
            border: 1px solid rgba(139, 90, 43, 0.2);
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-action:hover {
            background-color: var(--primary);
            color: var(--white);
            box-shadow: 0 5px 15px rgba(139, 90, 43, 0.3);
            border-color: var(--primary);
        }

        /* Search Modal */
        .search-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(30, 20, 15, 0.95);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
        }

        .search-modal.active {
            display: flex;
            opacity: 1;
        }

        .search-container {
            background: var(--white);
            width: 90%;
            max-width: 600px;
            border-radius: 50px;
            padding: 10px 25px;
            display: flex;
            align-items: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .search-input {
            border: none;
            outline: none;
            flex: 1;
            padding: 15px;
            font-size: 1.2rem;
            color: var(--dark);
            background: transparent;
            font-family: 'Outfit';
        }

        .btn-close-search {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.3s;
        }

        .btn-close-search:hover {
            color: var(--dark);
        }

        /* Cart Sidebar Modal */
        .cart-overlay {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            opacity: 0;
            transition: var(--transition);
        }

        .cart-overlay.active {
            display: block;
            opacity: 1;
        }

        .cart-sidebar {
            position: absolute;
            right: -420px;
            top: 0;
            bottom: 0;
            width: 420px;
            max-width: 100%;
            background: var(--bg-color);
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            transition: right 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }

        .cart-overlay.active .cart-sidebar {
            right: 0;
        }

        .cart-header {
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(139, 90, 43, 0.1);
        }

        .cart-header h3 {
            font-size: 1.6rem;
            margin: 0;
            font-family: 'Playfair Display';
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-close-cart {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.3s;
        }

        .btn-close-cart:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .cart-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px dashed rgba(139, 90, 43, 0.2);
        }

        .cart-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
        }

        .cart-details {
            flex: 1;
        }

        .cart-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 1.05rem;
        }

        .cart-price {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--white);
            border: 1px solid rgba(139, 90, 43, 0.2);
            border-radius: 20px;
            padding: 4px 10px;
            width: max-content;
        }

        .qty-btn {
            background: none;
            border: none;
            width: 25px;
            height: 25px;
            font-size: 1rem;
            cursor: pointer;
            color: var(--dark);
            transition: color 0.2s;
        }

        .qty-btn:hover {
            color: var(--primary);
        }

        .cart-footer {
            padding: 25px 30px;
            border-top: 1px solid rgba(139, 90, 43, 0.1);
            background: var(--white);
            overflow-y: auto;
            max-height: 55vh;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 1.4rem;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .btn-checkout {
            background: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Outfit';
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.2);
        }

        .btn-checkout:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Cart Method Selectors */
        .cart-section-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-section-label i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .method-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .method-pill {
            position: relative;
        }

        .method-pill input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .method-pill label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 25px;
            border: 2px solid rgba(139, 90, 43, 0.15);
            background: var(--card-bg);
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--dark);
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .method-pill label i {
            font-size: 0.9rem;
            color: var(--primary-light);
        }

        .method-pill input:checked+label {
            border-color: var(--primary);
            background: rgba(139, 90, 43, 0.08);
            color: var(--primary-dark);
            box-shadow: 0 2px 10px rgba(139, 90, 43, 0.12);
        }

        .method-pill label:hover {
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--dark);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 3000;
            transition: transform 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            font-weight: 500;
        }

        .toast.active {
            transform: translateX(-50%) translateY(0);
        }

        .toast i {
            color: var(--primary-light);
            font-size: 1.3rem;
        }

        /* About Section */
        .about-content {
            display: flex;
            align-items: center;
            gap: 50px;
            text-align: left;
        }

        .about-img {
            flex: 1;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .about-img img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 20px;
            transition: var(--transition);
        }

        .about-img:hover img {
            transform: scale(1.05);
        }

        .about-text {
            flex: 1;
        }

        .about-text h2 {
            font-size: 2.8rem;
            margin-bottom: 20px;
            color: var(--dark);
            font-weight: 800;
            line-height: 1.2;
        }

        .about-text p {
            font-size: 1.1rem;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .about-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--card-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .feature-item:hover .feature-icon {
            background: var(--primary);
            color: white;
            transform: rotate(10deg);
        }

        .feature-text h4 {
            font-size: 1.1rem;
            color: var(--dark);
            margin: 0 0 5px 0;
            font-family: 'Outfit', sans-serif;
        }

        .feature-text p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Blog Section */
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            text-align: left;
        }

        .blog-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(139, 90, 43, 0.08);
            transition: var(--transition);
            border: 1px solid rgba(139, 90, 43, 0.05);
        }

        .blog-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(139, 90, 43, 0.15);
        }

        .blog-img {
            height: 240px;
            overflow: hidden;
            position: relative;
        }

        .blog-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .blog-card:hover .blog-img img {
            transform: scale(1.08);
        }

        .blog-date {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: center;
            line-height: 1.2;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .blog-date span {
            display: block;
            font-size: 1.2rem;
        }

        .blog-body {
            padding: 30px;
        }

        .blog-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .blog-meta span i {
            color: var(--primary-light);
            margin-right: 5px;
        }

        .blog-title {
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 15px;
            font-family: 'Playfair Display';
            line-height: 1.4;
            transition: var(--transition);
            cursor: pointer;
        }

        .blog-title:hover {
            color: var(--primary);
        }

        .read-more {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 1rem;
            transition: var(--transition);
        }

        .read-more i {
            transition: transform 0.3s;
        }

        .read-more:hover {
            color: var(--primary-dark);
        }

        .read-more:hover i {
            transform: translateX(5px);
        }

        /* Pages Info Boxes */
        .info-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
            padding: 0 20px;
        }

        .info-box {
            background: var(--white);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(139, 90, 43, 0.08);
            transition: var(--transition);
            border-bottom: 4px solid transparent;
        }

        .info-box:hover {
            transform: translateY(-10px);
            border-bottom-color: var(--primary);
        }

        .info-box i {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .info-box:hover i {
            color: var(--primary);
            transform: scale(1.1);
        }

        .info-box h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--dark);
            font-family: 'Outfit';
            font-weight: 800;
        }

        .info-box p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: #d7ccc8;
            padding: 80px 0 40px;
            text-align: center;
        }

        .footer-logo {
            font-size: 2rem;
            color: var(--white);
            font-family: 'Playfair Display';
            margin-bottom: 20px;
        }

        .footer-socials {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .footer-socials a {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-socials a:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-5px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .nav-links {
                display: none;
            }

            .top-bar {
                display: none;
            }

            .logo {
                padding: 10px 20px;
                height: 75px;
            }

            .hero h1 {
                font-size: 3.5rem;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .about-text h2 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .about-content {
                flex-direction: column;
                text-align: center;
                gap: 30px;
            }

            .about-text {
                text-align: center;
            }

            .about-features {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .info-boxes {
                margin-top: 20px;
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero {
                padding: 60px 0;
                min-height: 400px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .category-tabs {
                gap: 10px;
            }

            .tab-btn {
                padding: 8px 16px;
                font-size: 0.95rem;
            }
        }
    
        /* Premium Order Notification Bar */
        .order-notif-bar {
            position: fixed;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: min(500px, 90vw);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 0 0 20px 20px;
            padding: 20px 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            z-index: 10001;
            transition: top 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 20px;
            border: 2px solid #00E676;
            border-top: none;
        }
        .order-notif-bar.active { top: 0; }
        .order-notif-bar.rejected { border-color: #ff4444; }
        
        .notif-bar-icon {
            width: 55px; height: 55px;
            border-radius: 15px;
            background: #00E676;
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(0, 230, 118, 0.3);
        }
        .order-notif-bar.rejected .notif-bar-icon {
            background: #ff4444;
            box-shadow: 0 8px 20px rgba(255, 68, 68, 0.3);
        }
        
        .notif-bar-body { flex: 1; }
        .notif-bar-body h4 { margin: 0 0 5px 0; font-size: 1.1rem; color: #333; }
        .notif-bar-body p { margin: 0; font-size: 0.9rem; color: #666; line-height: 1.4; }
        
        .notif-bar-close {
            background: none; border: none; font-size: 1.2rem; color: #ccc; cursor: pointer; transition: 0.3s;
        }
        .notif-bar-close:hover { color: #333; }

        /* Premium Order Notification Bar */
        .order-notif-bar {
            position: fixed;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: min(550px, 95vw);
            background: #ffffff;
            border-radius: 0 0 25px 25px;
            padding: 22px 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            z-index: 10005;
            transition: all 0.7s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 22px;
            border: 3px solid #00E676;
            border-top: none;
        }
        .order-notif-bar.active { top: 0; }
        .order-notif-bar.rejected { border-color: #ff4444; }
        
        .notif-bar-icon {
            width: 60px; height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, #00E676, #00C853);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
            box-shadow: 0 10px 25px rgba(0, 230, 118, 0.4);
        }
        .order-notif-bar.rejected .notif-bar-icon {
            background: linear-gradient(135deg, #ff4444, #d32f2f);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.4);
        }
        
        .notif-bar-content { flex: 1; }
        .notif-bar-content h4 { margin: 0 0 6px 0; font-size: 1.2rem; font-weight: 800; color: #1a1a1a; font-family: 'Outfit'; }
        .notif-bar-content p { margin: 0; font-size: 0.95rem; color: #4a4a4a; line-height: 1.5; font-weight: 500; }
        
        .notif-bar-dismiss {
            background: #f0f0f0; border: none; width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #666; cursor: pointer; transition: 0.3s;
        }
        .notif-bar-dismiss:hover { background: #e0e0e0; color: #111; }

        .notif-bar-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .notif-bar-btn {
            border: none;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .notif-bar-btn.detail {
            background: var(--primary);
            color: #fff;
        }

        .notif-bar-btn.close {
            background: #efefef;
            color: #444;
        }
        </style>
</head>

<body>

    <!-- Top Information Bar -->
    <div class="top-bar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="top-bar-left">
                <span><i class="fas fa-phone-alt"></i> +62 813 7411 0444</span>
                <span><i class="fas fa-envelope"></i> pondokestellerzr@gmail.com</span>
                <span><i class="fas fa-map-marker-alt"></i> Jl. Kalumbuk NO.21</span>
            </div>
            <div class="top-bar-right">
                <div class="top-bar-socials">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-mug-hot"></i>
                <div>
                    <h2>Pondok</h2>
                    <span>Es Teller ZR</span>
                </div>
            </a>

            <ul class="nav-links">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#menu">Menu</a></li>
                <li><a
                        href="<?php echo $customer_data ? 'customer/customer_dashboard.php' : 'customer/customer_login.php'; ?>">Pesanan</a>
                </li>
                <li><a href="#footer">Contact</a></li>
            </ul>

            <div class="nav-actions">
                <button class="action-icon" onclick="openSearch()"><i class="fas fa-search"></i></button>
                <button class="action-icon" onclick="openCart()">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-badge" id="cartCount">0</span>
                </button>

                <?php if ($customer_data): ?>
                    <a href="customer/customer_dashboard.php" class="btn-signup"
                        style="display: flex; align-items: center; gap: 10px; padding: 6px 15px; border-radius: 30px;">
                        <div
                            style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.2); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($customer_data['foto_profil']) && file_exists('assets/images/profiles/' . $customer_data['foto_profil'])): ?>
                                <img src="assets/images/profiles/<?php echo $customer_data['foto_profil']; ?>" alt="Profile"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                                <?php
                            else: ?>
                                <i class="fas fa-user-circle" style="font-size: 1.2rem;"></i>
                                <?php
                            endif; ?>
                        </div>
                        <span style="max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo explode(' ', $customer_data['nama'])[0]; ?>
                        </span>
                    </a>
                    <?php
                else: ?>
                    <a href="customer/customer_login.php" class="btn-signup">Sign Up</a>
                    <?php
                endif; ?>
            </div>
        </div>
    </header>

    <!-- Search Modal -->
    <div class="search-modal" id="searchModal">
        <div class="search-container">
            <input type="text" class="search-input" id="searchInput" placeholder="Search our delicious menu...">
            <button class="btn-close-search" onclick="closeSearch()"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                <h1>We Offer A Delicious <br>Variety Of ICE</h1>
                <p>We have everything from classic coffee to our house made specialty beverages.</p>
                <a href="#menu" class="btn-hero">Book A Table</a>

                <div class="pagination-dots">
                    <div class="dot active"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Ice Popular Menu</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Proin libero nunc consequat interdum
                feugiat in ferme lorem.</p>

            <!-- Category Filter Mapping: map 'original', 'premium', 'icecream' to new labels if needed -->
            <div class="category-tabs" data-aos="fade-up" data-aos-delay="200" id="categoryTabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="original">Chocolate</button>
                <button class="tab-btn" data-filter="premium">Coffee</button>
                <button class="tab-btn" data-filter="icecream">Sweets</button>
                <button class="tab-btn" data-filter="blacktea">Black Tea</button>
                <button class="tab-btn" data-filter="greentea">Green Tea</button>
            </div>

            <div class="menu-grid" id="productGrid">
                <?php
                $query = secure_query($conn, "SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count 
                           FROM products p 
                           LEFT JOIN reviews r ON p.id = r.product_id 
                           WHERE p.tersedia = 1 AND p.is_deleted = 0 
                           GROUP BY p.id 
                           ORDER BY p.created_at DESC");
                if ($query && $query->num_rows > 0) {
                    while ($row = $query->fetch_array()) {
                        $imgUrl = (!empty($row['gambar']) && file_exists('assets/images/products/' . $row['gambar'])) ?
                            "assets/images/products/" . $row['gambar'] :
                            "https://images.unsplash.com/photo-1541167760496-1628856ab772?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80"; // Default coffee image
                
                        // Generate a fake cross-out price (approx 50-60% higher to match the design aesthetics $358 vs $225)
                        $fakePrice = $row['harga'] * 1.5;

                        // Create JSON for JS Cart
                        $productData = json_encode([
                            'id' => $row['id'],
                            'name' => $row['nama'],
                            'price' => $row['harga'],
                            'image' => $imgUrl
                        ]);
                        $productData = htmlspecialchars($productData, ENT_QUOTES, 'UTF-8');

                        $kategori = strtolower(trim($row['kategori']));
                        ?>
                        <div class="menu-card" data-aos="fade-up" data-category="<?php echo $kategori; ?>"
                            data-name="<?php echo strtolower($row['nama']); ?>">
                            <div class="card-img-wrapper">
                                <img src="<?php echo $imgUrl; ?>" alt="<?php echo $row['nama']; ?>" class="card-img"
                                    onerror="this.src='https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=600&q=80'">
                                <div class="card-pills">
                                    <!-- Optional Badge -->
                                </div>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo $row['nama']; ?></h3>
                                <div
                                    style="margin-bottom: 8px; display: flex; align-items: center; gap: 5px; justify-content: center;">
                                    <?php
                                    $stars = round($row['avg_rating'] ?: 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo '<i class="fas fa-star" style="color: ' . ($i <= $stars ? '#FFD700' : '#ddd') . '; font-size: 0.8rem;"></i>';
                                    }
                                    ?>
                                    <span
                                        style="font-size: 0.75rem; color: var(--gray);">(<?php echo $row['review_count']; ?>)</span>
                                </div>
                                <p class="card-desc"><?php echo substr($row['deskripsi'], 0, 70) . '...'; ?></p>
                                <div class="price-wrapper">
                                    <span class="price-fake">Rp <?php echo number_format($fakePrice, 0, ',', '.'); ?></span>
                                    <span class="price-real">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="card-actions">
                                    <button class="btn-action" onclick='addToCart(<?php echo $productData; ?>)'
                                        title="Add to Cart">
                                        <i class="fas fa-shopping-bag"></i>
                                    </button>
                                    <a href="#" class="btn-action" onclick="event.preventDefault();" title="Quick View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div style="grid-column:1/-1; text-align:center; padding:50px; color:var(--text-muted); font-size:1.2rem;">Belum ada produk tersedia.</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section" style="background-color: var(--white);">
        <div class="container about-content">
            <div class="about-img" data-aos="fade-right">
                <img src="assets/images/products/coba1.jpg" alt="About Us">
            </div>
            <div class="about-text" data-aos="fade-left">
                <span
                    style="color: var(--primary); font-weight: 700; letter-spacing: 2px; text-transform: uppercase; font-size: 0.9rem; display: block; margin-bottom: 10px;">Our
                    Story</span>
                <h2>We Make The Best Coffee In Your Home Town</h2>
                <p>Pondok Es Teller ZR brings you the authentic taste of freshness. We combine the richness of
                    traditional Indonesian ice desserts with the relaxing ambiance of a modern coffee shop. Every sip
                    tells a story of quality ingredients and passionate brewing.</p>

                <div class="about-features">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-coffee"></i></div>
                        <div class="feature-text">
                            <h4>Special Coffee</h4>
                            <p>Freshly brewed beans</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-ice-cream"></i></div>
                        <div class="feature-text">
                            <h4>Signature Ice</h4>
                            <p>Handcrafted desserts</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                        <div class="feature-text">
                            <h4>Natural Recipe</h4>
                            <p>100% organic</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-mug-hot"></i></div>
                        <div class="feature-text">
                            <h4>Cozy Place</h4>
                            <p>Perfect ambiance</p>
                        </div>
                    </div>
                </div>

                <a href="#menu" class="btn-hero" style="margin-top: 10px;">Explore Our Menu</a>
            </div>
        </div>
    </section>

    <!-- Pages Section: Info Boxes -->
    <section id="pages" style="background-color: var(--bg-color); padding-bottom: 90px; padding-top: 40px;">
        <div class="container">
            <div class="info-boxes">
                <div class="info-box" data-aos="fade-up" data-aos-delay="0">
                    <i class="fas fa-clock"></i>
                    <h3>Opening Hours</h3>
                    <p>Mon - Sun: 08.00 AM - 10.00 PM<br>(Ramdhan): 15.00 - Berbuka</p>
                </div>
                <div class="info-box" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Our Location</h3>
                    <p>Jl. Kalumbuk NO.21<br>Padang, West Sumatra</p>
                </div>
                <div class="info-box" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-phone-alt"></i>
                    <h3>Contact Us</h3>
                    <p>+62 813 7411 0444<br>pondokestellerzr@gmail.com</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section id="blog" class="section" style="background-color: var(--card-bg);">
        <div class="container">
            <span
                style="color: var(--primary); font-weight: 700; letter-spacing: 2px; text-transform: uppercase; font-size: 0.9rem; display: block; margin-bottom: 10px;">News
                & Articles</span>
            <h2 class="section-title" data-aos="fade-up">Our Latest Blog</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Stay updated with our latest news,
                recipes, and coffee culture articles.</p>

            <div class="blog-grid">
                <div class="blog-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="blog-img">
                        <img src="assets/images/products/coba6.jpg" alt="Blog 1">
                        <div class="blog-date"><span>15</span>Oct</div>
                    </div>
                    <div class="blog-body">
                        <div class="blog-meta">
                            <span><i class="fas fa-user"></i> Admin</span>
                            <span><i class="fas fa-comments"></i> 3 Comments</span>
                        </div>
                        <h3 class="blog-title">The Secret Behind Our Signature Es Teller</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.6;">Discover the fresh
                            ingredients and traditional methods we use to craft the perfect bowl of our signature
                            dessert.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="blog-img">
                        <img src="assets/images/products/coba3.jpg" alt="Blog 2">
                        <div class="blog-date"><span>22</span>Oct</div>
                    </div>
                    <div class="blog-body">
                        <div class="blog-meta">
                            <span><i class="fas fa-user"></i> Admin</span>
                            <span><i class="fas fa-comments"></i> 5 Comments</span>
                        </div>
                        <h3 class="blog-title">How to Brew the Perfect Cup of Coffee</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.6;">Learn the essential
                            techniques and tips from our barista to achieve a cafÃ©-quality brew right in your own
                            kitchen.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="blog-img">
                        <img src="assets/images/products/coba5.jpg" alt="Blog 3">
                        <div class="blog-date"><span>28</span>Oct</div>
                    </div>
                    <div class="blog-body">
                        <div class="blog-meta">
                            <span><i class="fas fa-user"></i> Admin</span>
                            <span><i class="fas fa-comments"></i> 2 Comments</span>
                        </div>
                        <h3 class="blog-title">New Menu Alert: Caramel Macchiato</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.6;">We are excited to
                            introduce our new seasonal drink! A perfect blend of espresso, velvety milk, and sweet
                            caramel.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer">
        <div class="container">
            <div class="footer-logo"><i class="fas fa-mug-hot"></i> Coffee Es Teller ZR</div>
            <div class="footer-socials">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Pondok Es Teller ZR. All Rights Reserved. Designed with precision.
                </p>
            </div>
        </div>
    </footer>

    <!-- Cart Sidebar Modal -->
    <div class="cart-overlay" id="cartModal">
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-bag" style="color:var(--primary);"></i> Your Cart</h3>
                <button class="btn-close-cart" onclick="closeCart()"><i class="fas fa-times"></i></button>
            </div>

            <div class="cart-items" id="cartItemsContainer">
                <!-- Items injected here via JS -->
                <div style="text-align:center; padding:50px 0; color:var(--text-muted);">
                    <i class="fas fa-shopping-basket" style="font-size:3rem; margin-bottom:20px; opacity:0.3;"></i>
                    <p>Your cart is currently empty.</p>
                </div>
            </div>

            <div class="cart-footer">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="cartTotal">Rp 0</span>
                </div>

                <!-- Metode Pembayaran -->
                <div class="cart-section-label"><i class="fas fa-wallet"></i> Metode Pembayaran</div>
                <div class="method-grid">
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_bayar" id="pay_cod" value="cod" checked>
                        <label for="pay_cod"><i class="fas fa-hand-holding-usd"></i> COD</label>
                    </div>
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_bayar" id="pay_bank" value="transfer">
                        <label for="pay_bank"><i class="fas fa-university"></i> Transfer Bank</label>
                    </div>
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_bayar" id="pay_gopay" value="gopay">
                        <label for="pay_gopay"><i class="fas fa-mobile-alt"></i> GoPay</label>
                    </div>
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_bayar" id="pay_ovo" value="ovo">
                        <label for="pay_ovo"><i class="fas fa-mobile-alt"></i> OVO</label>
                    </div>
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_bayar" id="pay_dana" value="dana">
                        <label for="pay_dana"><i class="fas fa-mobile-alt"></i> DANA</label>
                    </div>
                </div>

                <div id="ewalletInfo"
                    style="display:none; margin-bottom: 20px; padding: 15px; background: rgba(139,90,43,0.05); border-radius: 12px; font-size: 0.9rem;">
                    <!-- E-Wallet balance info will be injected here -->
                </div>

                <!-- Metode Pengiriman -->
                <div class="cart-section-label"><i class="fas fa-truck"></i> Metode Pengambilan</div>
                <div class="method-grid" style="margin-bottom:15px;">
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_pengiriman" id="ship_pickup" value="pickup" checked>
                        <label for="ship_pickup"><i class="fas fa-store"></i> Jemput Sendiri</label>
                    </div>
                    <div class="method-pill">
                        <input type="radio" name="cart_metode_pengiriman" id="ship_delivery" value="delivery">
                        <label for="ship_delivery"><i class="fas fa-motorcycle"></i> Delivery</label>
                    </div>
                </div>

                <!-- Catatan Pesanan -->
                <div class="cart-section-label"><i class="fas fa-comment-dots"></i> Catatan Pesanan (Opsional)</div>
                <textarea id="cartNotesInput" rows="2"
                    style="width: 100%; border-radius: 12px; padding: 12px; font-family: Outfit; font-size: 0.9rem; margin-bottom: 20px; border: 2px solid #ddd; resize: none; transition: 0.3s;"
                    placeholder="Contoh: Es dipisah, kurang manis..."></textarea>

                <form action="customer/order.php" method="POST" id="checkoutForm">
                    <input type="hidden" name="cart_data" id="cartDataInput">
                    <input type="hidden" name="metode_bayar" id="metodeBayarInput" value="cod">
                    <input type="hidden" name="metode_pengiriman" id="metodePengirimanInput" value="pickup">
                    <input type="hidden" name="cart_catatan" id="catatanHiddenInput" value="">
                    <button type="button" class="btn-checkout" onclick="proceedCheckout()">
                        <i class="fas fa-shopping-bag" style="margin-right:8px;"></i> Checkout Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span>Item added to your cart!</span>
    </div>

    <div id="indexOrderStatusNotif" class="order-notif-bar" style="display:none;" role="status" aria-live="polite">
        <div class="notif-bar-icon">
            <i id="indexOrderStatusIcon" class="fas fa-bell"></i>
        </div>
        <div class="notif-bar-content">
            <h4 id="indexOrderStatusTitle">Update Pesanan</h4>
            <p id="indexOrderStatusText"></p>
            <div class="notif-bar-actions">
                <button type="button" class="notif-bar-btn detail" onclick="goToOrderDashboardDetail()">Detail</button>
                <button type="button" class="notif-bar-btn close" onclick="closeIndexOrderNotification()">Tutup</button>
            </div>
        </div>
    </div>

    <audio id="audioOrderAcceptedIndex" preload="auto" src="assets/sounds/pesanan-berhasil-diterima.mp3"></audio>
    <audio id="audioOrderRejectedIndex" preload="auto" src="assets/sounds/pesanan-telah-ditolak.mp3"></audio>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // E-Wallet logic
        let currentTotal = 0;

        document.querySelectorAll('input[name="cart_metode_bayar"]').forEach(radio => {
            radio.addEventListener('change', checkWalletStatus);
        });

        function formatRp(num) {
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        async function linkWallet(type) {
            const btn = document.getElementById('btnLinkWallet');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghubungkan...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('wallet_type', type);

            try {
                const res = await fetch('api/link_wallet.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    alert(json.message);
                    location.reload(); // Reload to get new balances from PHP
                } else {
                    alert(json.message);
                    btn.innerHTML = 'Coba Lagi';
                    btn.disabled = false;
                }
            } catch (err) {
                alert("Error menghubungkan e-wallet.");
                btn.innerHTML = 'Coba Lagi';
                btn.disabled = false;
            }
        }

        function checkWalletStatus() {
            const selected = document.querySelector('input[name="cart_metode_bayar"]:checked').value;
            const infoBox = document.getElementById('ewalletInfo');
            const btnCheckout = document.querySelector('.btn-checkout');

            if (selected === 'cod' || selected === 'transfer') {
                infoBox.style.display = 'none';
                btnCheckout.disabled = false;
                btnCheckout.innerHTML = '<i class="fas fa-shopping-bag" style="margin-right:8px;"></i> Checkout Sekarang';
                btnCheckout.style.opacity = '1';
                return;
            }

            infoBox.style.display = 'block';

            if (!cid) {
                infoBox.innerHTML = `<span style="color:var(--secondary-dark);"><i class="fas fa-exclamation-circle"></i> Silakan <b>login</b> untuk menggunakan ${selected.toUpperCase()}.</span>`;
                btnCheckout.disabled = true;
                btnCheckout.style.opacity = '0.5';
                return;
            }

            let balance = null;
            if (selected === 'gopay') balance = gopayBalance;
            if (selected === 'ovo') balance = ovoBalance;
            if (selected === 'dana') balance = danaBalance;

            if (balance === null) {
                infoBox.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted);"><i class="fas fa-link"></i> Akun belum terhubung.</span>
                        <button id="btnLinkWallet" onclick="linkWallet('${selected}')" style="background:var(--primary); color:white; border:none; padding:6px 12px; border-radius:8px; cursor:pointer; font-size:0.8rem; font-weight:600;">Hubungkan</button>
                    </div>
                `;
                btnCheckout.disabled = true;
                btnCheckout.style.opacity = '0.5';
            } else {
                const isEnough = balance >= currentTotal;
                const color = isEnough ? 'var(--primary-dark)' : 'red';
                const icon = isEnough ? 'fa-check-circle' : 'fa-times-circle';

                infoBox.innerHTML = `
                    <div style="display:flex; justify-content:space-between; font-weight:600;">
                        <span>Saldo ${selected.toUpperCase()}</span>
                        <span style="color:${color};"><i class="fas ${icon}"></i> ${formatRp(balance)}</span>
                    </div>
                    ${!isEnough ? '<div style="color:red; font-size:0.75rem; margin-top:5px;">Saldo tidak mencukupi untuk pembayaran ini.</div>' : ''}
                `;

                if (isEnough) {
                    btnCheckout.disabled = false;
                    btnCheckout.style.opacity = '1';
                    btnCheckout.innerHTML = '<i class="fas fa-wallet" style="margin-right:8px;"></i> Bayar dengan ' + selected.toUpperCase();
                } else {
                    btnCheckout.disabled = true;
                    btnCheckout.style.opacity = '0.5';
                    btnCheckout.innerHTML = 'Saldo Tidak Cukup';
                }
            }
        }

        // Initialize AOS Animation
        AOS.init({ once: true, offset: 50, duration: 800 });

        // Smooth Scroll handling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });

                    // Update active state
                    document.querySelectorAll('.nav-links a').forEach(link => link.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });

        // Search Modal Logic
        function openSearch() {
            document.getElementById('searchModal').classList.add('active');
            setTimeout(() => document.getElementById('searchInput').focus(), 100);
        }
        function closeSearch() {
            document.getElementById('searchModal').classList.remove('active');
            // reset filter
            document.getElementById('searchInput').value = '';
            filterMenuCards('');
        }

        // Search Filter
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keyup', (e) => {
            filterMenuCards(e.target.value.toLowerCase());
        });

        function filterMenuCards(term) {
            const cards = document.querySelectorAll('.menu-card');
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(term)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Category Filter
        const tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const filter = tab.getAttribute('data-filter');
                const cards = document.querySelectorAll('.menu-card');

                cards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-category').includes(filter)) {
                        card.style.display = 'flex';
                    } else {
                        // For demonstration purposes since categories might not match exactly, 
                        // if filter is 'premium' or 'coffee' etc, we could hide them.
                        // But since we map Chocolate -> original, Coffee -> premium etc:
                        const cat = card.getAttribute('data-category');
                        if (filter === 'chocolate' && cat.includes('original')) { card.style.display = 'flex'; }
                        else if (filter === 'coffee' && cat.includes('premium')) { card.style.display = 'flex'; }
                        else if (filter === 'sweets' && cat.includes('icecream')) { card.style.display = 'flex'; }
                        else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });

        // Close search when clicking outside
        document.getElementById('searchModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('searchModal')) closeSearch();
        });

        // Shopping Cart Logic
        let cart = JSON.parse(localStorage.getItem('cart_esteller')) || [];
        updateCartCount();

        function addToCart(product) {
            const existingItem = cart.find(item => item.id === product.id);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ ...product, quantity: 1 });
            }
            saveCart();
            showToast();
        }

        function updateQuantity(id, change) {
            const item = cart.find(item => String(item.id) === String(id));
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => String(i.id) !== String(id));
                }
                saveCart();
                renderCart();
            }
        }

        function saveCart() {
            localStorage.setItem('cart_esteller', JSON.stringify(cart));
            updateCartCount();
        }

        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').innerText = count;
        }

        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('active');
            setTimeout(() => toast.classList.remove('active'), 2500);
        }

        function openCart() {
            document.getElementById('cartModal').classList.add('active');
            renderCart();
        }

        function closeCart() {
            document.getElementById('cartModal').classList.remove('active');
        }

        function renderCart() {
            const container = document.getElementById('cartItemsContainer');
            const totalEl = document.getElementById('cartTotal');

            if (cart.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:50px 0; color:var(--text-muted);">
                        <i class="fas fa-shopping-basket" style="font-size:3rem; margin-bottom:20px; opacity:0.3;"></i>
                        <p>Your cart is currently empty.</p>
                    </div>`;
                totalEl.innerText = 'Rp 0';
                return;
            }

            let html = '';
            let total = 0;

            cart.forEach(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                html += `
                    <div class="cart-item">
                        <img src="${item.image}" alt="${item.name}" class="cart-img" onerror="this.src='https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=200&q=80'">
                        <div class="cart-details">
                            <div class="cart-title">${item.name}</div>
                            <div class="cart-price">Rp ${item.price.toLocaleString('id-ID')}</div>
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span style="font-size: 0.95rem; font-weight: 600;">${item.quantity}</span>
                                <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            currentTotal = total;
            totalEl.innerText = formatRp(total);
            checkWalletStatus(); // Re-verify balance logic with new total
        }

        function proceedCheckout() {
            if (cart.length === 0) {
                alert("Keranjang masih kosong!");
                return;
            }

            // Re-check wallet logic just in case
            checkWalletStatus();
            if (document.querySelector('.btn-checkout').disabled) {
                return;
            }

            // Pass cart data to PHP via hidden input
            document.getElementById('cartDataInput').value = JSON.stringify(cart);

            // Pass selected payment and delivery methods
            const metodeBayar = document.querySelector('input[name="cart_metode_bayar"]:checked')?.value || 'cod';
            const metodePengiriman = document.querySelector('input[name="cart_metode_pengiriman"]:checked')?.value || 'pickup';
            document.getElementById('metodeBayarInput').value = metodeBayar;
            document.getElementById('metodePengirimanInput').value = metodePengiriman;
            document.getElementById('catatanHiddenInput').value = document.getElementById('cartNotesInput').value;

            document.getElementById('checkoutForm').submit();
        }

        // Close sidebar when clicking outside
        document.getElementById('cartModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('cartModal')) closeCart();
        });

        const ORDER_STATUS_STATE_KEY = 'client_order_status_state_v1';
        const ORDER_STATUS_NOTIFIED_KEY = 'client_order_status_notified_v1';
        const ORDER_STATUS_PENDING_CODE_KEY = 'pending_order_code';
        const ORDER_STATUS_INDEX_DISMISSED_KEY = 'client_order_status_index_dismissed_v1';
        let currentIndexNotifOrderCode = '';
        let currentIndexNotifStatus = '';
        let indexAudioUnlocked = false;

        function normalizeOrderStatus(rawStatus) {
            const status = String(rawStatus || '').toLowerCase().trim();
            if (status === 'baru' || status === '' || status === 'null') return 'new';
            if (status === 'diterima' || status === 'proses') return 'process';
            if (status === 'batal' || status === 'dibatalkan') return 'cancel';
            return status;
        }

        function loadJsonStorage(key, fallback = {}) {
            try {
                const raw = localStorage.getItem(key);
                if (!raw) return fallback;
                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : fallback;
            } catch (e) {
                return fallback;
            }
        }

        function saveJsonStorage(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                // ignore storage errors
            }
        }

        function unlockIndexNotificationAudio() {
            if (indexAudioUnlocked) return;
            const acceptedAudio = document.getElementById('audioOrderAcceptedIndex');
            const rejectedAudio = document.getElementById('audioOrderRejectedIndex');
            [acceptedAudio, rejectedAudio].forEach(audio => {
                if (!audio) return;
                const previousMuted = audio.muted;
                audio.muted = true;
                const p = audio.play();
                if (p && typeof p.then === 'function') {
                    p.then(() => {
                        audio.pause();
                        audio.currentTime = 0;
                        audio.muted = previousMuted;
                    }).catch(() => {
                        audio.muted = previousMuted;
                    });
                } else {
                    audio.muted = previousMuted;
                }
            });
            indexAudioUnlocked = true;
        }

        function playIndexOrderStatusSound(type) {
            const audio = type === 'accepted'
                ? document.getElementById('audioOrderAcceptedIndex')
                : document.getElementById('audioOrderRejectedIndex');
            if (!audio) return;
            audio.currentTime = 0;
            const p = audio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(() => { });
            }
        }

        function closeIndexOrderNotification() {
            const notif = document.getElementById('indexOrderStatusNotif');
            if (!notif) return;
            notif.classList.remove('active');
            setTimeout(() => {
                notif.style.display = 'none';
            }, 250);

            if (currentIndexNotifOrderCode && currentIndexNotifStatus) {
                const dismissed = loadJsonStorage(ORDER_STATUS_INDEX_DISMISSED_KEY, {});
                const token = `${currentIndexNotifOrderCode}:${currentIndexNotifStatus}`;
                dismissed[token] = new Date().toISOString();
                saveJsonStorage(ORDER_STATUS_INDEX_DISMISSED_KEY, dismissed);
            }
        }

        function goToOrderDashboardDetail() {
            if (currentIndexNotifOrderCode && currentIndexNotifStatus) {
                const dismissed = loadJsonStorage(ORDER_STATUS_INDEX_DISMISSED_KEY, {});
                const token = `${currentIndexNotifOrderCode}:${currentIndexNotifStatus}`;
                dismissed[token] = new Date().toISOString();
                saveJsonStorage(ORDER_STATUS_INDEX_DISMISSED_KEY, dismissed);
            }
            const orderCode = encodeURIComponent(currentIndexNotifOrderCode || '');
            window.location.href = `customer/customer_dashboard.php${orderCode ? `?order_code=${orderCode}` : ''}`;
        }

        function showIndexOrderStatusNotification(type, orderCode, message) {
            const notif = document.getElementById('indexOrderStatusNotif');
            const icon = document.getElementById('indexOrderStatusIcon');
            const title = document.getElementById('indexOrderStatusTitle');
            const text = document.getElementById('indexOrderStatusText');
            if (!notif || !icon || !title || !text) return;

            const accepted = type === 'accepted';
            currentIndexNotifOrderCode = orderCode || '';
            currentIndexNotifStatus = accepted ? 'process' : 'cancel';

            notif.classList.toggle('rejected', !accepted);
            icon.className = `fas ${accepted ? 'fa-check-circle' : 'fa-times-circle'}`;
            title.textContent = accepted ? 'Pesanan Diterima Admin' : 'Pesanan Ditolak Admin';
            text.innerHTML = `<strong>${orderCode || '-'}</strong><br>${message}`;

            notif.style.display = 'flex';
            requestAnimationFrame(() => {
                notif.classList.add('active');
            });
        }

        function seedIndexOrderStatus() {
            const pendingCode = String(localStorage.getItem(ORDER_STATUS_PENDING_CODE_KEY) || '').trim();
            if (!pendingCode) return;
            const stateMap = loadJsonStorage(ORDER_STATUS_STATE_KEY, {});
            if (!stateMap[pendingCode]) {
                stateMap[pendingCode] = 'new';
                saveJsonStorage(ORDER_STATUS_STATE_KEY, stateMap);
            }
        }

        function processIndexOrderStatus(order) {
            const code = String(order.order_code || '').trim();
            if (!code) return;

            const latestStatus = normalizeOrderStatus(order.status);
            const stateMap = loadJsonStorage(ORDER_STATUS_STATE_KEY, {});
            const notifiedMap = loadJsonStorage(ORDER_STATUS_NOTIFIED_KEY, {});
            const dismissedMap = loadJsonStorage(ORDER_STATUS_INDEX_DISMISSED_KEY, {});
            const previousStatus = normalizeOrderStatus(stateMap[code] || '');
            const pendingCode = String(localStorage.getItem(ORDER_STATUS_PENDING_CODE_KEY) || '').trim();

            const token = `${code}:${latestStatus}`;
            const statusIsFinal = latestStatus === 'process' || latestStatus === 'cancel';
            const pendingMatched = pendingCode && pendingCode === code;
            const transitionedFromNew = previousStatus === 'new';

            if (statusIsFinal && !notifiedMap[token] && !dismissedMap[token] && (pendingMatched || transitionedFromNew)) {
                if (latestStatus === 'process') {
                    showIndexOrderStatusNotification('accepted', code, 'Pesanan kamu sudah diterima. Tekan Detail untuk lihat status lengkap.');
                    playIndexOrderStatusSound('accepted');
                    localStorage.removeItem('cart_esteller');
                    if (typeof cart !== 'undefined') {
                        cart = [];
                        updateCartCount();
                        renderCart();
                    }
                } else if (latestStatus === 'cancel') {
                    showIndexOrderStatusNotification('rejected', code, 'Maaf, pesanan kamu ditolak admin. Tekan Detail untuk cek informasi lanjut.');
                    playIndexOrderStatusSound('rejected');
                }

                notifiedMap[token] = order.updated_at || new Date().toISOString();
                saveJsonStorage(ORDER_STATUS_NOTIFIED_KEY, notifiedMap);

                if (pendingMatched) {
                    localStorage.removeItem(ORDER_STATUS_PENDING_CODE_KEY);
                }
            }

            stateMap[code] = latestStatus;
            saveJsonStorage(ORDER_STATUS_STATE_KEY, stateMap);
        }

        function pollIndexOrderStatus() {
            if (!cid) return;
            fetch(`api/get_customer_order_status.php?limit=30&_=${Date.now()}`, { cache: 'no-store' })
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success || !Array.isArray(data.orders)) return;
                    data.orders.forEach(processIndexOrderStatus);
                })
                .catch(() => { });
        }

        seedIndexOrderStatus();
        if (cid) {
            pollIndexOrderStatus();
            setInterval(pollIndexOrderStatus, 3500);
            window.addEventListener('pointerdown', unlockIndexNotificationAudio, { passive: true });
            window.addEventListener('keydown', unlockIndexNotificationAudio);
        }
    </script>
</body>

</html>
