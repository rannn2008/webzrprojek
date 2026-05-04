<?php
// c:/xampp/htdocs/parking/history.php
include "config.php";
include "auth.php";
restrictToAdmin(); // Strict separation
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access History | Smart Parking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-clock-rotate-left"></i> ACTIVITY LOGS</h1>
                    <p class="tagline">Comprehensive Record of Every Entry and Exit</p>
                </div>
                <div style="text-align: right;">
                    <a href="logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.8rem;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-chart-pie"></i> Public View</a>
                <a href="users.php" class="tab-btn"><i class="fas fa-user-shield"></i> Users & RFID</a>
                <a href="history.php" class="tab-btn active"><i class="fas fa-clock-rotate-left"></i> History Log</a>
                <a href="settings.php" class="tab-btn"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="justify-content: space-between; align-items: center; display: flex;">
                <h2 class="card-title"><i class="fas fa-database"></i> Detailed Registry</h2>
                <div style="display:flex; gap:10px;">
                    <a href="generate_report.php" target="_blank" class="btn" style="background:linear-gradient(135deg, #6366f1, #8b5cf6); color:#fff; padding: 8px 15px; font-size: 0.8rem; text-decoration:none; border-radius:8px;">
                        <i class="fas fa-file-pdf"></i> Laporan Bulanan
                    </a>
                    <a href="export_history.php" class="btn btn-success" style="padding: 8px 15px; font-size: 0.8rem;">
                        <i class="fas fa-file-export"></i> Export CSV
                    </a>
                </div>
            </div>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="history-search" class="form-control search-input"
                    placeholder="Search by name or plate...">
            </div>

            <div style="overflow-x:auto;">
                <table class="table" id="history-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Time Log</th>
                            <th>Identity</th>
                            <th style="text-align:center;">Action / Fee</th>
                        </tr>
                    </thead>
                    <tbody id="history-live-body">
                        <?php
                        $sql = "SELECT h.id, h.action, h.timestamp, u.name, u.plate_number, h.fee
                                FROM parking_history h 
                                JOIN users u ON h.user_id = u.id 
                                ORDER BY h.id DESC LIMIT 100";
                        $res = $conn->query($sql);

                        if (!$res) {
                            echo "<tr><td colspan='4' style='text-align:center;'>Error: " . $conn->error . "</td></tr>";
                        } else {
                            while ($row = $res->fetch_assoc()) {
                                $act = strtoupper(trim($row["action"]));
                                // Default (OUT)
                                $badgeStyle = "class='badge bg-danger'";
                                $icon = "fa-circle-arrow-left";
                                $actionLabel = "OUT";
                                $feePrefix = "Rp ";

                                if ($act === "IN") {
                                    $badgeStyle = "class='badge bg-success'";
                                    $icon = "fa-circle-arrow-right";
                                    $actionLabel = "IN";
                                } else if ($act === "BOOK") {
                                    $badgeStyle = "style='background: #8b5cf6; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 10px rgba(139, 92, 246, 0.3);'";
                                    $icon = "fa-ticket-alt";
                                    $actionLabel = "BOOKING";
                                } else if ($act === "CANCEL") {
                                    $badgeStyle = "style='background: #64748b; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;'";
                                    $icon = "fa-undo";
                                    $actionLabel = "BATAL";
                                    $feePrefix = "+ Rp ";
                                } else if ($act === "TOPUP") {
                                    $badgeStyle = "style='background: #0ea5e9; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;'";
                                    $icon = "fa-plus-circle";
                                    $actionLabel = "TOPUP";
                                    $feePrefix = "+ Rp ";
                                }
                                
                                $feeText = ($row["fee"] > 0) ? $feePrefix . number_format($row["fee"], 0, ",", ".") : "-";

                                echo "<tr class='history-row'>
                                    <td style='font-family: monospace; font-size: 0.85rem;'>#{$row["id"]}</td>
                                    <td style='color: var(--accent-primary); font-weight: 500;'>" . date("d/m H:i", strtotime($row["timestamp"])) . "</td>
                                    <td class='identity-cell'>
                                        <div style='font-weight:600;'>{$row["name"]}</div>
                                        <div style='font-size:0.75rem; color:var(--text-muted);'>{$row["plate_number"]}</div>
                                    </td>
                                    <td style='text-align:center;'>
                                        <span $badgeStyle>
                                            <i class='fas {$icon}'></i> {$actionLabel}
                                        </span>
                                        <div style='font-size:0.75rem; font-weight:700; margin-top:3px; color: " . ($act === "CANCEL" ? "#4ade80" : "inherit") . ";'>{$feeText}</div>
                                    </td>
                                </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function escHtml(v) {
            return String(v ?? "").replace(/[&<>"']/g, function (c) {
                return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c];
            });
        }

        function formatFee(fee) {
            const val = Number(fee || 0);
            return val > 0 ? "Rp " + val.toLocaleString("id-ID") : "-";
        }

        function formatLogTime(ts) {
            const d = new Date((ts || "").replace(" ", "T"));
            if (isNaN(d.getTime())) return ts || "-";
            const dd = String(d.getDate()).padStart(2, "0");
            const mm = String(d.getMonth() + 1).padStart(2, "0");
            const hh = String(d.getHours()).padStart(2, "0");
            const mi = String(d.getMinutes()).padStart(2, "0");
            return `${dd}/${mm} ${hh}:${mi}`;
        }

        function renderHistoryRows(rows) {
            if (!rows || rows.length === 0) {
                $("#history-live-body").html("<tr><td colspan='4' style='text-align:center;'>No history data.</td></tr>");
                return;
            }
            const html = rows.map(row => {
                const act = String(row.action).toUpperCase();
                let bStyle = "padding: 5px 12px;";
                let bClass = "badge bg-danger";
                let icon = "fa-circle-arrow-left";
                let label = "OUT";
                let feePrefix = "Rp ";
                let feeColor = "inherit";
                
                if (act === "IN") {
                    bClass = "badge bg-success";
                    icon = "fa-circle-arrow-right";
                    label = "IN";
                } else if (act === "BOOK") {
                    bClass = "badge";
                    bStyle = "background: #8b5cf6; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 10px rgba(139, 92, 246, 0.3);";
                    icon = "fa-ticket-alt";
                    label = "BOOKING";
                } else if (act === "CANCEL") {
                    bClass = "badge";
                    bStyle = "background: #64748b; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;";
                    icon = "fa-undo";
                    label = "BATAL";
                    feePrefix = "+ Rp ";
                    feeColor = "#4ade80";
                } else if (act === "TOPUP") {
                    bClass = "badge";
                    bStyle = "background: #0ea5e9; color: white; border-radius: 30px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;";
                    icon = "fa-plus-circle";
                    label = "TOPUP";
                    feePrefix = "+ Rp ";
                    feeColor = "#4ade80";
                }

                return `
                    <tr class="history-row">
                        <td style="font-family: monospace; font-size: 0.85rem;">#${row.id}</td>
                        <td style="color: var(--accent-primary); font-weight: 500;">${formatLogTime(row.timestamp)}</td>
                        <td class="identity-cell">
                            <div style="font-weight:600;">${escHtml(row.name)}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">${escHtml(row.plate_number)}</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="${bClass}" style="${bStyle}">
                                <i class="fas ${icon}"></i> ${label}
                            </span>
                            <div style="font-size:0.75rem; font-weight:700; margin-top:3px; color: ${feeColor};">${row.fee > 0 ? feePrefix + Number(row.fee).toLocaleString("id-ID") : "-"}</div>
                        </td>
                    </tr>
                `;
            }).join("");
            $("#history-live-body").html(html);
        }

        function applySearchFilter() {
            let val = $("#history-search").val().toLowerCase();
            $(".history-row").each(function () {
                $(this).toggle($(this).find(".identity-cell").text().toLowerCase().indexOf(val) > -1);
            });
        }

        function pollHistoryLive() {
            $.ajax({
                url: "api_get_history_live.php",
                method: "GET",
                dataType: "json",
                cache: false
            }).done(function (data) {
                renderHistoryRows(data.history || []);
                applySearchFilter();
            });
        }

        $(document).ready(function () {
            $("#history-search").on("keyup", function () {
                applySearchFilter();
            });
            pollHistoryLive();
            setInterval(pollHistoryLive, 2000);
        });
    </script>
</body>

</html>
