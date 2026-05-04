<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak ditemukan']);
    exit();
}

$id = (int)$_GET['id'];
$sql = "SELECT * FROM products WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyiapkan query']);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    echo json_encode(mysqli_fetch_assoc($result), JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Produk tidak ditemukan']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
