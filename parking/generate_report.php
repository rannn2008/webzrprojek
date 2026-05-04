<?php
// c:/xampp/htdocs/parking/generate_report.php
include 'config.php';
include 'auth.php';
// admin check if needed, but for now we follow general auth
// requireAdmin(); // if exists

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$monthName = date("F", mktime(0, 0, 0, $month, 10));

// 1. Stats
$revenue_query = "SELECT SUM(fee) as total FROM parking_history WHERE action IN ('OUT', 'BOOK') AND MONTH(timestamp) = $month AND YEAR(timestamp) = $year";
$rev_res = $conn->query($revenue_query);
$total_rev = (int)($rev_res->fetch_assoc()['total'] ?? 0);

$entry_query = "SELECT COUNT(*) as total FROM parking_history WHERE action IN ('IN', 'BOOK') AND MONTH(timestamp) = $month AND YEAR(timestamp) = $year";
$entries = (int)($conn->query($entry_query)->fetch_assoc()['total'] ?? 0);

$daily_query = "SELECT DATE(timestamp) as date, COUNT(id) as count, SUM(fee) as day_rev 
                FROM parking_history 
                WHERE MONTH(timestamp) = $month AND YEAR(timestamp) = $year
                GROUP BY DATE(timestamp) ORDER BY date ASC";
$daily_res = $conn->query($daily_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report - <?php echo "$monthName $year"; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #000; }
        .report-title { font-size: 18px; margin-top: 10px; text-transform: uppercase; }
        
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .summary-item { border: 1px solid #ddd; padding: 20px; text-align: center; border-radius: 8px; }
        .summary-val { font-size: 24px; font-weight: bold; color: #0284c7; }
        .summary-label { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8fafc; font-size: 12px; text-transform: uppercase; }
        
        .footer { margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature { border-top: 1px solid #333; width: 200px; text-align: center; padding-top: 5px; margin-top: 80px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0284c7; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Print to PDF</button>
    </div>

    <div class="header">
        <div class="logo">SPOTFINDER PARKING SYSTEM</div>
        <div class="report-title">Monthly Revenue & Traffic Report</div>
        <div style="font-size: 14px; color: #666;"><?php echo "$monthName $year"; ?></div>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-val">Rp <?php echo number_format($total_rev, 0, ',', '.'); ?></div>
            <div class="summary-label">Total Revenue</div>
        </div>
        <div class="summary-item">
            <div class="summary-val"><?php echo $entries; ?></div>
            <div class="summary-label">Total Vehicle Entries</div>
        </div>
        <div class="summary-item">
            <div class="summary-val"><?php echo $daily_res->num_rows; ?></div>
            <div class="summary-label">Active Operational Days</div>
        </div>
    </div>

    <h3>Detailed Daily Statistics</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Activities (IN/OUT)</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $daily_res->fetch_assoc()): ?>
            <tr>
                <td><?php echo date("d M Y", strtotime($row['date'])); ?></td>
                <td><?php echo $row['count']; ?> Transaksi</td>
                <td>Rp <?php echo number_format($row['day_rev'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        <div>
            <p>Generated on: <?php echo date("d M Y, H:i"); ?></p>
            <p style="font-size: 10px; color: #999;">System ID: SPF-<?php echo strtoupper(substr(md5($year.$month), 0, 8)); ?></p>
        </div>
        <div>
            <div class="signature">Admin / Supervisor</div>
        </div>
    </div>
</body>
</html>
