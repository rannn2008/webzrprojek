@extends('layouts.admin')

@section('title', 'Overview')
@section('header_title', 'Overview')
@section('header_subtitle', 'Ringkasan aktivitas hari ini')

@section('styles')
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
        }

        .stat-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-val {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-lbl {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .admin-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .chart-container {
            height: 300px;
            position: relative;
        }
    </style>
@endsection

@section('content')
    <!-- STATS CARDS - 1:1 Legacy Layout -->
    <div class="stats-grid">
        <!-- 1. Pesanan Baru -->
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon" style="background: rgba(139, 90, 43, 0.1); color: var(--primary);">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <span class="stat-lbl" style="color: var(--secondary); font-weight: 700;">Perlu Proses</span>
            </div>
            <div class="stat-val">{{ $stats['new_orders'] }}</div>
            <div class="stat-lbl">Pesanan Baru</div>
        </div>

        <!-- 2. Semua Pesanan -->
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon" style="background: rgba(255, 145, 0, 0.1); color: var(--secondary);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-lbl">Total</span>
            </div>
            <div class="stat-val">{{ $stats['total_orders'] }}</div>
            <div class="stat-lbl">Semua Pesanan</div>
        </div>

        <!-- 3. Total Customer -->
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-users"></i>
                </div>
                <span class="stat-lbl">Pelanggan</span>
            </div>
            <div class="stat-val">{{ $stats['total_customers'] }}</div>
            <div class="stat-lbl">Total Customer</div>
        </div>

        <!-- 4. Pesanan Selesai -->
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon" style="background: rgba(46, 125, 50, 0.1); color: #2E7D32;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-lbl">Selesai</span>
            </div>
            <div class="stat-val">{{ $stats['total_done'] }}</div>
            <div class="stat-lbl">Pesanan Selesai</div>
        </div>

        <!-- 5. Rating Toko -->
        <div class="stat-card" style="border: 1px solid rgba(255, 179, 0, 0.2); cursor: pointer;"
            onclick="window.location.href='{{ route('admin.reviews.index') }}'">
            <div class="stat-head">
                <div class="stat-icon" style="background: rgba(255, 179, 0, 0.1); color: #FFB300;">
                    <i class="fas fa-star"></i>
                </div>
                <span class="stat-lbl">Rating Toko</span>
            </div>
            <div class="stat-val">{{ number_format($avg_rating, 1) }}<span
                    style="font-size: 0.9rem; color: var(--gray); font-weight: 400;">/5.0</span></div>
            <div class="stat-lbl">{{ $total_reviews }} Ulasan Pelanggan</div>
        </div>
    </div>

    <!-- CHARTS SECTION -->
    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 25px; margin-bottom: 25px;">
        <!-- Revenue Chart -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.1rem; color: var(--dark);">
                    <i class="fas fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i>
                    Tren Penjualan (7 Hari Terakhir)
                </h3>
                <span style="font-size: 0.8rem; color: var(--gray);">Status: Selesai</span>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Top Products Donut -->
        <div class="admin-card">
            <h3 style="font-size: 1.1rem; color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-crown" style="color: var(--secondary); margin-right: 8px;"></i>
                Produk Terlaris
            </h3>
            <div class="chart-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 25px;">
        <!-- Distribution Bar Chart -->
        <div class="admin-card">
            <h3 style="font-size: 1.1rem; color: var(--dark); margin-bottom: 20px;">
                <i class="fas fa-tasks" style="color: var(--primary-dark); margin-right: 8px;"></i>
                Distribusi Status
            </h3>
            <div class="chart-container" style="height: 250px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Excel Export Card -->
        <div class="admin-card"
            style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
            <div
                style="width: 70px; height: 70px; background: rgba(139, 90, 43, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 2rem; margin-bottom: 20px;">
                <i class="fas fa-file-excel"></i>
            </div>
            <h3 style="font-size: 1.3rem; margin-bottom: 10px;">Laporan Penjualan</h3>
            <p style="color: var(--gray); margin-bottom: 20px; max-width: 300px;">Unduh rekap data pesanan lengkap dalam
                format Excel untuk pembukuan.</p>
            <button class="btn btn-primary" onclick="exportToExcel()" style="padding: 12px 30px;">
                <i class="fas fa-download"></i> Unduh Data Excel
            </button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Colors from Coffee Theme
            const coffeeColors = {
                primary: '#8b5a2b',
                secondary: '#d2a679',
                labels: ['#8b5a2b', '#c19a6b', '#d2a679', '#e6ccb8', '#a67c52']
            };

            // 1. Revenue Chart
            const revenueData = {!! json_encode($revenue_7days) !!};
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: revenueData.map(d => d.label),
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: revenueData.map(d => d.total),
                        borderColor: coffeeColors.primary,
                        backgroundColor: 'rgba(139, 90, 43, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: coffeeColors.primary,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. Top Products Chart (Doughnut)
            const topProdData = {!! json_encode($top_products) !!};
            new Chart(document.getElementById('topProductsChart'), {
                type: 'doughnut',
                data: {
                    labels: topProdData.map(d => d.nama_product),
                    datasets: [{
                        data: topProdData.map(d => d.total_qty),
                        backgroundColor: coffeeColors.labels,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, padding: 20, font: { family: 'Outfit', size: 11 } }
                        }
                    }
                }
            });

            // 3. Status Chart (Bar)
            const statusDist = {!! json_encode($status_dist) !!};
            new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: ['Baru', 'Proses', 'Selesai', 'Batal'],
                    datasets: [{
                        data: [
                            statusDist.new,
                            statusDist.process,
                            statusDist.done,
                            statusDist.cancel
                        ],
                        backgroundColor: ['#1565C0', '#EF6C00', '#2E7D32', '#C62828'],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });

        // Excel Export
        function exportToExcel() {
            // Replicate legacy logic using SheetJS
            const tableData = [
                ['ID Pesanan', 'Customer', 'Total', 'Status', 'Tanggal']
            ];

            // This normally would fetch from an API or hidden data, 
            // but for parity we can show the mechanism 
            alert('Fitur ekspor Excel sedang menyiapkan data...');
            window.location.href = "{{ route('admin.orders.index') }}?export=excel";
        }
    </script>
@endsection