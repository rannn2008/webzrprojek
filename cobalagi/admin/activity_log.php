<?php
require_once '../config/config.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Buat tabel activity_logs jika belum ada
$create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_user VARCHAR(50),
    action VARCHAR(100),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
secure_query($conn, $create_table, "", [], false);

// Ambil log activity
$logs_result = secure_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100", "", []);
$logs = [];
if ($logs_result) {
    while ($row = $logs_result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Pondok Es Teller ZR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00C897;
            --primary-dark: #019267;
            --secondary: #FF6B6B;
            --accent: #FFD166;
            --dark: #1A1A2E;
            --light: #F8F9FA;
            --gray: #6C757D;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light);
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 2.2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: var(--primary);
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 200, 151, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, var(--gray), #495057);
        }
        
        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, var(--secondary), #ff5252);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, var(--accent), #ffc107);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 30px;
        }
        
        .table-header {
            background: var(--dark);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255,255,255,0.15);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.7);
        }
        
        .table-wrapper {
            overflow-x: auto;
            max-height: 600px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            position: sticky;
            top: 0;
            background: var(--light);
            z-index: 10;
        }
        
        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #eaeaea;
            white-space: nowrap;
        }
        
        td {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background-color: rgba(0, 200, 151, 0.05);
        }
        
        .admin-badge {
            background: var(--primary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .action-login { background: #e3f2fd; color: #1976d2; }
        .action-add { background: #e8f5e9; color: #2e7d32; }
        .action-edit { background: #fff3e0; color: #f57c00; }
        .action-delete { background: #ffebee; color: #c62828; }
        .action-status { background: #f3e5f5; color: #7b1fa2; }
        
        .time-ago {
            color: var(--gray);
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
            
            .search-box {
                width: 100%;
                margin-top: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 20px;
            }
            
            th, td {
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 576px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-history"></i>
                Activity Logs
                <span style="font-size: 1rem; color: var(--gray); font-weight: 400;">
                    (Terakhir 100 aktivitas)
                </span>
            </h1>
            
            <div class="header-actions">
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <button onclick="exportLogs()" class="btn">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-cards">
            <?php
            // Hitung statistik
            $total_logs = count($logs);
            $today_logs = 0;
            $admin_counts = [];
            
            $today = date('Y-m-d');
            foreach ($logs as $log) {
                if (date('Y-m-d', strtotime($log['created_at'])) == $today) {
                    $today_logs++;
                }
                $admin_counts[$log['admin_user']] = isset($admin_counts[$log['admin_user']]) 
                    ? $admin_counts[$log['admin_user']] + 1 
                    : 1;
            }
            $unique_admins = count($admin_counts);
            ?>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-number"><?php echo $total_logs; ?></div>
                <div class="stat-label">Total Aktivitas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?php echo $today_logs; ?></div>
                <div class="stat-label">Aktivitas Hari Ini</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $unique_admins; ?></div>
                <div class="stat-label">Admin Aktif</div>
            </div>
        </div>
        
        <!-- Activity Logs Table -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Riwayat Aktivitas</h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari aktivitas...">
                </div>
            </div>
            
            <div class="table-wrapper">
                <?php if (count($logs) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Admin</th>
                                <th>Aksi</th>
                                <th>Detail</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="logsTable">
                            <?php foreach ($logs as $log): 
                                $time_ago = timeAgo($log['created_at']);
                                $action_class = 'action-' . strtolower(explode(' ', $log['action'])[0]);
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($log['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <span class="admin-badge">
                                        <i class="fas fa-user-shield"></i> 
                                        <?php echo htmlspecialchars($log['admin_user']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="action-badge <?php echo $action_class; ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.95rem; color: var(--dark);">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--dark);">
                                        <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                                    </div>
                                    <div class="time-ago">
                                        <?php echo $time_ago; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3 style="margin-bottom: 10px;">Belum ada aktivitas</h3>
                        <p>Log aktivitas akan muncul di sini setelah admin melakukan aksi.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk format waktu
        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'Baru saja';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' menit lalu';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' jam lalu';
            if (seconds < 604800) return Math.floor(seconds / 86400) + ' hari lalu';
            return date.toLocaleDateString('id-ID');
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#logsTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Export logs to CSV
        function exportLogs() {
            const table = document.querySelector('table');
            let csv = [];
            
            // Headers
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent);
            });
            csv.push(headers.join(','));
            
            // Data rows
            table.querySelectorAll('tbody tr').forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach(cell => {
                    // Remove badges and icons for clean CSV
                    let text = cell.textContent.replace(/[\n\r]+|[\s]{2,}/g, ' ').trim();
                    rowData.push(`"${text}"`);
                });
                csv.push(rowData.join(','));
            });
            
            // Create download link
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `activity_logs_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            
            // Show notification
            showNotification('Log berhasil diekspor!', 'success');
        }
        
        // Notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#00C897' : '#FF6B6B'};
                color: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 1000;
                animation: slideIn 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                display: flex;
                align-items: center;
                gap: 15px;
                min-width: 300px;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <div>
                    <div style="font-weight: 600; margin-bottom: 3px;">${type === 'success' ? 'Berhasil!' : 'Info!'}</div>
                    <div>${message}</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .fade-in {
                animation: fadeIn 0.5s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
        
        // Apply time ago to existing elements
        document.querySelectorAll('.time-ago').forEach(element => {
            const dateString = element.previousElementSibling.textContent;
            element.textContent = timeAgo(dateString);
        });
    </script>
</body>
</html>
<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' hari lalu';
    } else {
        return date('d/m/Y', $time);
    }
}
mysqli_close($conn);
?>
