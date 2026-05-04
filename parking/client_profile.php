<?php
// c:/xampp/htdocs/parking/client_profile.php
include "config.php";
include "auth.php";
restrictToClient();

$client_id = $_SESSION["client_id"];
$msg = "";

// Ensure directory exists
$target_dir = "assets/avatars/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// 1. Fetch User Data
$user = $conn->query("SELECT * FROM users WHERE id = $client_id")->fetch_assoc();

// 2. Handle Profile Update & Avatar Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $name = trim($_POST["name"]);
    $plate = strtoupper(trim($_POST["plate_number"]));
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $gender = $_POST["gender"] ?? "";
    $dob = $_POST["dob"] ?? "";
    $pass = $_POST["password"];

    // Avatar upload is now handled via AJAX and api_upload_avatar.php for better UX

    if (!empty($pass)) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?, plate_number=?, email=?, phone=?, address=?, gender=?, dob=?, password=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $name, $plate, $email, $phone, $address, $gender, $dob, $hashed, $client_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, plate_number=?, email=?, phone=?, address=?, gender=?, dob=? WHERE id=?");
        $stmt->bind_param("sssssssi", $name, $plate, $email, $phone, $address, $gender, $dob, $client_id);
    }

    if ($stmt->execute()) {
        $msg .= "<div class='badge bg-success' style='width:100%'>Profile Updated Successfully!</div>";
        $_SESSION["client_name"] = $name;
        $user = $conn->query("SELECT * FROM users WHERE id = $client_id")->fetch_assoc();
    }
}

// Check for avatar
$avatar_path = !empty($user['avatar']) ? $user['avatar'] . "?t=" . time() : "assets/img/default-avatar.png";

// Stats for profile
$visit_count = (int) ($conn->query("SELECT COUNT(*) as c FROM parking_history WHERE user_id = $client_id AND action='IN'")->fetch_assoc()["c"] ?? 0);
$total_fees = $conn->query("SELECT SUM(fee) as total FROM parking_history WHERE user_id = $client_id AND action='OUT'")->fetch_assoc()["total"] ?? 0;
$member_since = $user["created_at"] ?? date("Y-m-d");
$user_points = $user["points"] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SpotFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .profile-hero {
            display: flex;
            align-items: center;
            gap: 30px;
            padding: 30px;
            background: linear-gradient(135deg, rgba(0, 229, 255, 0.08), rgba(99, 102, 241, 0.08));
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            margin-bottom: 25px;
        }

        .avatar-wrapper {
            position: relative;
        }

        .avatar-preview {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent-primary);
            box-shadow: 0 0 25px rgba(0, 229, 255, 0.25);
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--gradient-main);
            border: 3px solid var(--bg-primary);
            color: #fff;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .avatar-edit-btn:hover {
            transform: scale(1.1);
        }

        .hero-info {
            flex: 1;
        }

        .hero-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .hero-plate {
            font-size: 0.9rem;
            color: var(--accent-primary);
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .hero-stats {
            display: flex;
            gap: 25px;
        }

        .hero-stat-item {
            text-align: center;
        }

        .hero-stat-val {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }

        .hero-stat-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            color: var(--accent-primary);
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group .form-control {
            margin-bottom: 0;
        }

        .form-group select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2300e5ff' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .section-divider {
            border: none;
            border-top: 1px solid var(--glass-border);
            margin: 25px 0;
        }

        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .section-title i {
            color: var(--accent-primary);
            margin-right: 8px;
        }

        .btn-save {
            width: 100%;
            padding: 16px;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 16px;
            background: var(--gradient-main);
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 1px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 229, 255, 0.3);
        }

        input[type="file"] {
            display: none;
        }

        @media (max-width: 768px) {
            .profile-hero {
                flex-direction: column;
                text-align: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <div class="container">
        <?php include 'global_ai_assistant.php'; ?>
        <header>
            <div class="header-top">
                <div>
                    <h1><i class="fas fa-user-circle"></i> MY PROFILE</h1>
                    <p class="tagline">Manage your account and personalize your profile</p>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <img src="<?= $avatar_path ?>" class="header-avatar"
                        style="width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-primary);">
                    <div style="text-align: right;">
                        <a href="logout.php" class="btn btn-danger" style="padding: 8px 15px;"><i
                                class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="tabs-container">
            <div class="tabs">
                <a href="index.php" class="tab-btn"><i class="fas fa-house"></i> Public View</a>
                <a href="client_dashboard.php" class="tab-btn"><i class="fas fa-gauge-high"></i> My Account</a>
                <a href="client_chat.php" class="tab-btn"><i class="fas fa-comments"></i> Chat</a>
                <a href="client_profile.php" class="tab-btn active"><i class="fas fa-user-gear"></i> Profile</a>
            </div>
        </div>

        <?= $msg ?>

        <form method="POST" enctype="multipart/form-data">

            <!-- HERO SECTION -->
            <div class="profile-hero">
                <div class="avatar-wrapper">
                    <img src="<?= $avatar_path ?>" class="avatar-preview" id="avatar-img">
                    <label class="avatar-edit-btn" for="avatar-input" title="Change Photo">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" name="avatar" accept="image/*" id="avatar-input" style="display:none;"
                        onchange="handleAvatarSelection(this)">

                    <!-- NEW: CONFIRMATION OVERLAY -->
                    <div id="avatar-confirm-overlay"
                        style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); border-radius:50%; flex-direction:column; align-items:center; justify-content:center; gap:8px; z-index:10; border:2px solid var(--accent-primary);">
                        <button type="button" onclick="uploadAvatar()" class="btn btn-success"
                            style="width:34px; height:34px; border-radius:50%; padding:0; display:flex; align-items:center; justify-content:center;"
                            title="Confirm Change"><i class="fas fa-check"></i></button>
                        <button type="button" onclick="cancelAvatar()" class="btn btn-danger"
                            style="width:34px; height:34px; border-radius:50%; padding:0; display:flex; align-items:center; justify-content:center;"
                            title="Cancel"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="hero-info">
                    <div class="hero-name"><?= $user["name"] ?></div>
                    <div class="hero-plate"><i class="fas fa-id-badge"></i> <?= $user["plate_number"] ?></div>
                    <div class="hero-stats">
                        <div class="hero-stat-item">
                            <div class="hero-stat-val" style="color:#4ade80;"><?= $visit_count ?></div>
                            <div class="hero-stat-label">Visits</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-val" style="color:#f87171;">Rp
                                <?= number_format($total_fees, 0, ',', '.') ?></div>
                            <div class="hero-stat-label">Total Spent</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-val" style="color:#f59e0b;"><i class="fas fa-crown"></i>
                                <?= number_format($user_points, 0, ',', '.') ?></div>
                            <div class="hero-stat-label">Loyalty Points</div>
                        </div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-val" style="color:#60a5fa;">
                                <?= date("d M Y", strtotime($member_since)) ?></div>
                            <div class="hero-stat-label">Member Since</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERSONAL INFO -->
            <div class="card" style="margin-bottom:25px;">
                <div class="section-title"><i class="fas fa-user"></i> Personal Information</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user["name"]) ?>"
                            class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user["email"] ?? '') ?>"
                            class="form-control" placeholder="you@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user["phone"] ?? '') ?>"
                            class="form-control" placeholder="+62 812 xxxx xxxx">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="" <?= empty($user["gender"]) ? 'selected' : '' ?>>-- Select --</option>
                            <option value="Male" <?= ($user["gender"] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($user["gender"] ?? '') === 'Female' ? 'selected' : '' ?>>Female
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?= $user["dob"] ?? '' ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number" value="<?= htmlspecialchars($user["plate_number"]) ?>"
                            class="form-control" required
                            style="text-transform:uppercase; letter-spacing:2px; font-weight:700;">
                    </div>
                    <div class="form-group form-full">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Your home address"
                            style="resize:vertical; min-height:60px;"><?= htmlspecialchars($user["address"] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECURITY -->
            <div class="card" style="margin-bottom:25px;">
                <div class="section-title"><i class="fas fa-shield-halved"></i> Security</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>RFID UID (Read-only)</label>
                        <input type="text" value="<?= $user["rfid_uid"] ?>" class="form-control" disabled
                            style="opacity:0.5; font-family:monospace; letter-spacing:2px;">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>

            <button type="submit" name="update_profile" class="btn-save">
                <i class="fas fa-save"></i> SAVE ALL CHANGES
            </button>

        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let originalAvatar = "<?= $avatar_path ?>";

        function handleAvatarSelection(input) {
            if (input.files && input.files[0]) {
                if (input.files[0].size > 2 * 1024 * 1024) {
                    alert("File too large! Max 2MB.");
                    input.value = "";
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('avatar-img').src = e.target.result;
                    document.getElementById('avatar-confirm-overlay').style.display = 'flex';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function cancelAvatar() {
            document.getElementById('avatar-img').src = originalAvatar;
            document.getElementById('avatar-confirm-overlay').style.display = 'none';
            document.getElementById('avatar-input').value = "";
        }

        function uploadAvatar() {
            const fileInput = document.getElementById('avatar-input');
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);

            // Show loading
            const btn = document.querySelector('#avatar-confirm-overlay .btn-success');
            const oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            $.ajax({
                url: 'api_upload_avatar.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        const newUrl = response.avatar_url + "?t=" + new Date().getTime();
                        originalAvatar = newUrl;

                        // Update all avatars on page
                        $(".avatar-preview, .header-avatar").attr("src", newUrl);
                        document.getElementById('avatar-confirm-overlay').style.display = 'none';

                        // Show success notification (Toast style if possible, or alert)
                        alert("Profile picture updated successfully!");
                    } else {
                        alert(response.message || "Failed to upload.");
                        cancelAvatar();
                    }
                },
                error: function () {
                    alert("Error connecting to server.");
                    cancelAvatar();
                },
                complete: function () {
                    btn.innerHTML = oldHtml;
                    btn.disabled = false;
                }
            });
        }
    </script>
</body>

</html>