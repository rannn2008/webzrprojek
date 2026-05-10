<?php
// c:/xampp/htdocs/parking/users.php
include "config.php";
include "auth.php";
restrictToAdmin();

// Handle Add User
if (isset($_POST["add_user"])) {
    $uid = $_POST["uid"];
    $name = $_POST["name"];
    $plate = $_POST["plate"];
    $stmt = $conn->prepare("INSERT INTO users (rfid_uid, name, plate_number) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $uid, $name, $plate);
    $stmt->execute();
    $msg = "<div class='badge bg-success' style='width:100%'>User added successfully!</div>";
}

// Handle Delete
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: users.php");
}

// Handle Top-up Approval
if (isset($_GET["approve_topup"])) {
    $tid = intval($_GET["approve_topup"]);
    $req = $conn->query("SELECT * FROM topup_requests WHERE id=$tid AND status='PENDING'")->fetch_assoc();
    if ($req) {
        $amount = $req["amount"];
        $uid = $req["user_id"];
        $conn->query("UPDATE users SET balance = balance + $amount WHERE id=$uid");
        $conn->query("UPDATE topup_requests SET status='APPROVED' WHERE id=$tid");
        
        // Log to unified history
        $stmt_hist = $conn->prepare("INSERT INTO parking_history (user_id, action, fee) VALUES (?, 'TOPUP', ?)");
        $stmt_hist->bind_param("ii", $uid, $amount);
        $stmt_hist->execute();

        $msg = "<div class='badge bg-success' style='width:100%'>Top-up Approved! Balance updated & history logged.</div>";
    }
}

if (isset($_GET["reject_topup"])) {
    $tid = intval($_GET["reject_topup"]);
    $conn->query("UPDATE topup_requests SET status='REJECTED' WHERE id=$tid");
    $msg = "<div class='badge bg-danger' style='width:100%'>Top-up Rejected.</div>";
}

// Handle Manual Balance Update
if (isset($_POST["update_balance"])) {
    $uid = intval($_POST["user_id"]);
    $new_bal = intval($_POST["new_balance"]);
    $conn->query("UPDATE users SET balance = $new_bal WHERE id=$uid");
    $msg = "<div class='badge bg-primary' style='width:100%'>Balance updated manually.</div>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage RFID & Wallet | Smart Parking</title>
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
                    <h1><i class="fas fa-id-card-clip"></i> USER & WALLET MGMT</h1>
                    <p class="tagline">Authorize Access and Manage Balances</p>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-chart-pie"></i> Public View</a>
                <a href="users.php" class="tab-btn active"><i class="fas fa-user-shield"></i> Users & Wallet</a>
                <a href="history.php" class="tab-btn"><i class="fas fa-clock-rotate-left"></i> History Log</a>
                <a href="settings.php" class="tab-btn"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
            </div>
        </div>

        <?php if (isset($msg))
            echo $msg; ?>

        <!-- TOPUP REQUESTS -->
        <div class="card" style="margin-bottom: 25px; border-left: 5px solid var(--warning);">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-money-bill-transfer"></i> Pending Top-up Requests</h2>
            </div>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="pending-topup-body">
                        <?php
                        $treqs = $conn->query("SELECT t.*, u.name, u.plate_number FROM topup_requests t JOIN users u ON t.user_id = u.id WHERE t.status='PENDING'");
                        if ($treqs->num_rows > 0):
                            while ($tr = $treqs->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $tr["name"] ?></strong><br><small><?= $tr["plate_number"] ?></small></td>
                                    <td style="color:var(--success); font-weight:700;">Rp
                                        <?= number_format($tr["amount"], 0, ",", ".") ?></td>
                                    <td><?= $tr["created_at"] ?></td>
                                    <td>
                                        <a href="users.php?approve_topup=<?= $tr["id"] ?>" class="btn btn-success"
                                            style="padding:5px 10px; font-size:0.75rem;"><i class="fas fa-check"></i>
                                            Approve</a>
                                        <a href="users.php?reject_topup=<?= $tr["id"] ?>" class="btn btn-danger"
                                            style="padding:5px 10px; font-size:0.75rem;"><i class="fas fa-times"></i> Reject</a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No pending requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- ADD USER -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-plus"></i> New Enrollment</h2>
                </div>
                <form method="POST">
                    <label>RFID UID</label>
                    <div style="display:flex; gap:5px; margin-bottom:10px;">
                        <input type="text" name="uid" id="uid_input" class="form-control" placeholder="Scan/Paste UID"
                            required>
                        <button type="button" onclick="getLastScan()" class="btn btn-warning"><i
                                class="fas fa-rss"></i></button>
                    </div>
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                    <label>Plate Number</label>
                    <input type="text" name="plate" class="form-control" required>
                    <button type="submit" name="add_user" class="btn btn-success"
                        style="width:100%; margin-top:10px;">Authorize User</button>
                </form>
            </div>

            <!-- USER LIST -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-users-gear"></i> Member Database</h2>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Identity</th>
                                <th>Balance</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="users-live-body">
                            <?php $users = $conn->query("SELECT * FROM users ORDER BY id DESC");
                            while ($u = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $u["name"] ?></strong><br><small><?= $u["plate_number"] ?></small></td>
                                    <td>
                                        <form method="POST" style="display:flex; gap:5px; align-items:center;">
                                            <input type="hidden" name="user_id" value="<?= $u["id"] ?>">
                                            <input type="number" name="new_balance" value="<?= $u["balance"] ?>"
                                                class="form-control"
                                                style="width:100px; margin-bottom:0; padding:5px; font-size:0.8rem;">
                                            <button type="submit" name="update_balance" class="btn btn-primary"
                                                style="padding:5px;"><i class="fas fa-sync"></i></button>
                                        </form>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="users.php?delete=<?= $u["id"] ?>" class="btn btn-danger"
                                            style="padding:5px 10px;" onclick="return confirm('Revoke access?')"><i
                                                class="fas fa-trash-can"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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

        function getLastScan() {
            fetch("api_get_last_scan.php").then(r => r.json()).then(d => { if (d.uid) document.getElementById("uid_input").value = d.uid; });
        }

        function formatRupiah(v) {
            return Number(v || 0).toLocaleString("id-ID");
        }

        function renderPendingRows(rows) {
            if (!rows || rows.length === 0) {
                $("#pending-topup-body").html("<tr><td colspan='4' style='text-align:center;'>No pending requests.</td></tr>");
                return;
            }
            const html = rows.map(tr => `
                <tr>
                    <td><strong>${escHtml(tr.name)}</strong><br><small>${escHtml(tr.plate_number)}</small></td>
                    <td style="color:var(--success); font-weight:700;">Rp ${formatRupiah(tr.amount)}</td>
                    <td>${escHtml(tr.created_at)}</td>
                    <td>
                        <a href="users.php?approve_topup=${tr.id}" class="btn btn-success" style="padding:5px 10px; font-size:0.75rem;"><i class="fas fa-check"></i> Approve</a>
                        <a href="users.php?reject_topup=${tr.id}" class="btn btn-danger" style="padding:5px 10px; font-size:0.75rem;"><i class="fas fa-times"></i> Reject</a>
                    </td>
                </tr>
            `).join("");
            $("#pending-topup-body").html(html);
        }

        function renderUsersRows(rows) {
            if (!rows || rows.length === 0) {
                $("#users-live-body").html("<tr><td colspan='3' style='text-align:center;'>No users.</td></tr>");
                return;
            }
            const html = rows.map(u => `
                <tr>
                    <td><strong>${escHtml(u.name)}</strong><br><small>${escHtml(u.plate_number)}</small></td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px; align-items:center;">
                            <input type="hidden" name="user_id" value="${u.id}">
                            <input type="number" name="new_balance" value="${u.balance}" class="form-control" style="width:100px; margin-bottom:0; padding:5px; font-size:0.8rem;">
                            <button type="submit" name="update_balance" class="btn btn-primary" style="padding:5px;"><i class="fas fa-sync"></i></button>
                        </form>
                    </td>
                    <td style="text-align:right;">
                        <a href="users.php?delete=${u.id}" class="btn btn-danger" style="padding:5px 10px;" onclick="return confirm('Revoke access?')"><i class="fas fa-trash-can"></i></a>
                    </td>
                </tr>
            `).join("");
            $("#users-live-body").html(html);
        }

        function pollUsersLive() {
            $.ajax({
                url: "api_get_users_live.php",
                method: "GET",
                dataType: "json",
                cache: false
            }).done(function (data) {
                renderPendingRows(data.pending || []);
                renderUsersRows(data.users || []);
            });
        }

        $(document).ready(function () {
            pollUsersLive();
            setInterval(pollUsersLive, 2000);
        });
    </script>
</body>

</html>
