<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- SheetJS for Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        :root {
            /* Coffee Shop Theme - Exact Legacy Variables */
            --primary: #8b5a2b;
            /* Coffee Brown */
            --primary-dark: #5c3a18;
            /* Dark Brown */
            --primary-light: #c19a6b;
            --secondary: #d2a679;
            /* Light Brown/Caramel */
            --secondary-dark: #a67c52;
            --accent: #e6ccb8;
            --dark: #3e2723;
            --text-dark: #4e342e;
            --text-muted: #795548;
            --light: #fdfbf7;
            /* Warm Off-white */
            --gray: #9E9E9E;
            --white: #FFFFFF;
            --shadow: 0 4px 20px rgba(139, 90, 43, 0.08);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: #F5F7FA;
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: #FFFFFF;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.02);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            /* Increased margin match legacy */
        }

        .logo img {
            width: 100px;
            height: auto;
        }

        .logo span {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
            display: none;
            /* In legacy logo text is usually hidden if img is large */
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .menu-item {
            padding: 14px 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--gray);
            font-weight: 500;
            text-decoration: none;
        }

        .menu-item:hover {
            background: rgba(139, 90, 43, 0.1);
            color: var(--primary-dark);
        }

        .menu-item.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 10px 20px rgba(139, 90, 43, 0.3);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .badge {
            background: var(--secondary);
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: auto;
            font-weight: 700;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 1.8rem;
            color: var(--dark);
            font-family: 'Playfair Display', serif;
            font-weight: 800;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 8px 15px;
            border-radius: 50px;
            box-shadow: var(--shadow);
            cursor: pointer;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-family: 'Outfit';
        }

        /* CARDS & STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .stat-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(139, 90, 43, 0.1);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-val {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }

        .stat-lbl {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .admin-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .st-new {
            background: #E3F2FD;
            color: #1565C0;
        }

        .st-process {
            background: #FFF3E0;
            color: #EF6C00;
        }

        .st-preparing {
            background: #F3E5F5;
            color: #7B1FA2;
        }

        .st-ready {
            background: #E0F2F1;
            color: #00796B;
        }

        .st-done {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .st-cancel {
            background: #FFEBEE;
            color: #C62828;
        }

        .btn {
            border: none;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-danger {
            background: #FFEBEE;
            color: #C62828;
        }

        .btn-warning {
            background: #FFF3E0;
            color: #EF6C00;
        }

        .btn-info {
            background: #E3F2FD;
            color: #1565C0;
        }

        .btn-full {
            grid-column: 1 / -1;
            width: 100%;
        }

        /* NOTIFICATION WINDOW */
        .order-notif-window {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: min(390px, calc(100vw - 20px));
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(139, 90, 43, 0.2);
            box-shadow: 0 20px 45px rgba(62, 39, 35, 0.2);
            z-index: 2600;
            overflow: hidden;
            display: none;
            animation: orderNotifIn .35s ease;
        }

        @keyframes orderNotifIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .order-notif-head {
            padding: 14px 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .order-notif-title {
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* MODALS */
        .modal-ov {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            animation: zoomIn 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 30px 10px;
            }

            .logo span,
            .menu-item span,
            .badge {
                display: none;
            }

            .menu-item {
                justify-content: center;
                padding: 15px;
            }

            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }

        @yield('styles')
    </style>
</head>

<body>
    <div class="container">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="logo">
                <img src="{{ asset('assets/images/products/logozr.png') }}" alt="Logo"
                    onerror="this.src='https://placehold.co/100x40?text=ZR+LOGO'">
                <span>ZR Admin</span>
            </div>
            <div class="menu">
                <a href="{{ route('admin.dashboard') }}"
                    class="menu-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.orders.index') }}"
                    class="menu-item {{ Request::is('admin/orders*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-bag"></i> <span>Pesanan</span>
                    @php $newOrdersCount = \App\Models\Order::whereIn('status', ['new', 'baru', ''])->orWhereNull('status')->count(); @endphp
                    @if($newOrdersCount > 0)
                        <span class="badge" id="newOrdersBadge">{{ $newOrdersCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.logs.index') }}"
                    class="menu-item {{ Request::is('admin/logs*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> <span>Log Aktivitas</span>
                </a>
                <a href="{{ route('admin.products.index') }}"
                    class="menu-item {{ Request::is('admin/products*') ? 'active' : '' }}">
                    <i class="fas fa-coffee"></i> <span>Produk</span>
                </a>
                <a href="{{ route('admin.chats.index') }}"
                    class="menu-item {{ Request::is('admin/chats*') ? 'active' : '' }}">
                    <i class="fas fa-comment-dots"></i> <span>Pesan</span>
                </a>
                <a href="{{ route('admin.reviews.index') }}"
                    class="menu-item {{ Request::is('admin/reviews*') ? 'active' : '' }}">
                    <i class="fas fa-star"></i> <span>Ulasan</span>
                </a>
                <form action="{{ route('admin.logout') }}" method="POST" style="margin-top: auto">
                    @csrf
                    <button type="submit" class="menu-item"
                        style="border:none; background:none; width:100%; text-align:left; cursor:pointer">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1 id="pageTitle">@yield('header_title', 'Overview')</h1>
                    <p id="pageSub">@yield('header_subtitle', 'Ringkasan aktivitas hari ini')</p>
                </div>
                <a href="{{ route('admin.profile') }}" class="user-profile" style="text-decoration: none;">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::guard('admin')->user()->username, 0, 1)) }}
                    </div>
                    <span
                        style="font-weight:600; font-size:0.9rem; color: var(--dark);">{{ Auth::guard('admin')->user()->username }}</span>
                </a>
            </div>

            @yield('content')
        </div>
    </div>

    <!-- NOTIFICATION WINDOW (Floating) -->
    <div id="orderNotifWindow" class="order-notif-window">
        <div class="order-notif-head">
            <div class="order-notif-title"><i class="fas fa-bell"></i> Pesanan Baru</div>
            <div id="orderNotifCount" class="badge" style="margin-left:0">0</div>
        </div>
        <div id="orderNotifList" style="padding:15px; max-height:300px; overflow-y:auto">
            <!-- Dynamic content -->
        </div>
    </div>

    <audio id="orderAlertAudio" src="{{ asset('assets/audio/pesanan-masuk-harap-diterima.mp3') }}"
        preload="auto"></audio>

    @yield('scripts')

    <script>
        // Global logic for Notifications and Sound
        let lastOrderCount = {{ \App\Models\Order::whereIn('status', ['new', 'baru', ''])->orWhereNull('status')->count() }};
        let autoAcceptMinutes = localStorage.getItem('autoAcceptMinutes') || 3;

        function checkNewOrders() {
            fetch("{{ route('admin.orders.index') }}?ajax_count=1")
                .then(res => res.json())
                .then(data => {
                    const currentCount = data.count;
                    const badge = document.getElementById('newOrdersBadge');
                    const audio = document.getElementById('orderAlertAudio');

                    if (currentCount > 0) {
                        if (badge) { badge.innerText = currentCount; badge.style.display = 'block'; }
                    } else if (badge) { badge.style.display = 'none'; }

                    if (currentCount > lastOrderCount) {
                        audio.play().catch(e => console.log("Audio play blocked"));
                        showOrderNotif(currentCount);
                    }

                    lastOrderCount = currentCount;

                    // Logic for Auto-Accept could be added here to poll detailed orders if count > 0
                });
        }

        function saveAutoAcceptMinutes() {
            const input = document.getElementById('autoAcceptMinutesInput');
            autoAcceptMinutes = input.value;
            localStorage.setItem('autoAcceptMinutes', autoAcceptMinutes);
            alert('Konfigurasi auto-accept disimpan: ' + autoAcceptMinutes + ' menit');
        }

        function showOrderNotif(count) {
            const win = document.getElementById('orderNotifWindow');
            const cnt = document.getElementById('orderNotifCount');
            if (cnt) cnt.innerText = count;
            if (win) win.style.display = 'block';
            setTimeout(() => { if (win) win.style.display = 'none'; }, 15000);
        }

        setInterval(checkNewOrders, 10000);
    </script>
</body>

</html>