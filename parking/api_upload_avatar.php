<?php
// c:/xampp/htdocs/parking/api_upload_avatar.php
include 'config.php';
include 'auth.php';
restrictToClient();

header('Content-Type: application/json');

$response = ["success" => false, "message" => "Unknown error"];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $userId = $_SESSION['client_id'];
    $file = $_FILES['avatar'];
    
    // Validate file
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        $response['message'] = "Invalid file type. Only JPG, PNG, WEBP allowed.";
        echo json_encode($response);
        exit;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        $response['message'] = "File too large. Max 2MB.";
        echo json_encode($response);
        exit;
    }
    
    // Generate unique name
    $newName = "avatar_" . $userId . "_" . time() . "." . $ext;
    $targetDir = "uploads/avatars/";
    $targetPath = $targetDir . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Delete old avatar if exists
        $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        if ($old && !empty($old['avatar']) && file_exists($old['avatar'])) {
            unlink($old['avatar']);
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $targetPath, $userId);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['avatar_url'] = $targetPath;
            $response['message'] = "Avatar updated successfully!";
        } else {
            $response['message'] = "Database update failed.";
        }
    } else {
        $response['message'] = "Failed to save file.";
    }
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
?>
