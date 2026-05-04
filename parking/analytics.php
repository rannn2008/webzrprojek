<?php
// c:/xampp/htdocs/parking/analytics.php
include "config.php";
include "auth.php";
restrictToAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics | Smart Parking Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .chart-box {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }
        .chart-container {
            flex: 1;
            position: relative;
        }
        .stat-mini-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .stat-mini-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--glass-border);
        }
        .stat-mini-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-primary);
        }
        .stat-mini-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        @media (max-width: 1024px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'global_ai_assistant.php'; ?>
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-chart-line"></i> ADVANCED ANALYTICS</h1>
                    <p class="tagline">Data-driven insights for smarter parking management</p>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-chart-pie"></i> Public View</a>
                <a href="users.php" class="tab-btn"><i class="fas fa-user-shield"></i> Users & RFID</a>
                <a href="history.php" class="tab-btn"><i class="fas fa-clock-rotate-left"></i> Full History</a>
                <a href="analytics.php" class="tab-btn active"><i class="fas fa-chart-line"></i> Analytics</a>
                <a href="settings.php" class="tab-btn"><i class="fas fa-cog"></i> Settings</a>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="chart-box">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-money-bill-trend-up"></i> Revenue Trend (Last 7 Days)</div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-users"></i> Member Stats</div>
                </div>
                <div class="stat-mini-grid" id="member-stats-container">
                    <div class="stat-mini-card">
                        <div class="stat-mini-val" id="stat-total-members">0</div>
                        <div class="stat-mini-label">Total Registered</div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-val" id="stat-active-members" style="color:#4ade80;">0</div>
                        <div class="stat-mini-label">Active (30d)</div>
                    </div>
                    <div class="stat-mini-card" style="grid-column: span 2;">
                        <div class="stat-mini-val" id="stat-retention" style="color:#f59e0b;">0%</div>
                        <div class="stat-mini-label">User Retention Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="chart-box">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-clock"></i> Hourly Occupancy Distribution (Peak Hours)</div>
                </div>
                <div class="chart-container">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <div class="chart-header">
                    <div class="chart-title"><i class="fas fa-traffic-light"></i> Traffic Trend</div>
                </div>
                <div class="chart-container">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadAnalytics();
            setInterval(loadAnalytics, 5000);
        });

        let revenueChartInstance = null;
        let trafficChartInstance = null;
        let hourlyChartInstance = null;
        let hasSpokenAnalytics = false;

        function loadAnalytics() {
            $.get("api_get_analytics.php", function(data) {
                renderRevenueChart(data.revenue_chart);
                renderTrafficChart(data.traffic_chart);
                renderHourlyChart(data.hourly_chart);
                
                $("#stat-total-members").text(data.member_stats.total);
                $("#stat-active-members").text(data.member_stats.active);
                const retention = data.member_stats.total > 0 ? Math.round((data.member_stats.active / data.member_stats.total) * 100) : 0;
                $("#stat-retention").text(retention + "%");

                if (!hasSpokenAnalytics && retention > 50) {
                    hasSpokenAnalytics = true;
                    speakText("Analisis selesai. Retensi pengguna cukup tinggi, sistem berjalan sangat optimal.");
                }
            });
        }

        function renderRevenueChart(data) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            if (revenueChartInstance) revenueChartInstance.destroy();
            revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Parking Revenue (Rp)',
                        data: data.map(d => d.amount),
                        borderColor: '#00e5ff',
                        backgroundColor: 'rgba(0, 229, 255, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#00e5ff',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: items => data[items[0].dataIndex].date,
                                label: item => 'Rp ' + Number(item.raw).toLocaleString('id-ID')
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8', font: { family: 'Poppins' } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { family: 'Poppins' } }
                        }
                    }
                }
            });
        }

        function renderTrafficChart(data) {
            const ctx = document.getElementById('trafficChart').getContext('2d');
            if (trafficChartInstance) trafficChartInstance.destroy();
            trafficChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Vehicle Entries',
                        data: data.map(d => d.count),
                        backgroundColor: 'rgba(99, 102, 241, 0.6)',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: items => data[items[0].dataIndex].date,
                                label: item => item.raw + ' kendaraan masuk'
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8', stepSize: 1 }
                        },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        }

        function renderHourlyChart(data) {
            const ctx = document.getElementById('hourlyChart').getContext('2d');
            if (hourlyChartInstance) hourlyChartInstance.destroy();
            hourlyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Avg Activity',
                        data: data.map(d => d.count),
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74, 222, 128, 0.1)',
                        borderWidth: 2,
                        tension: 0.5,
                        fill: true,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { display: false }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 0 }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
