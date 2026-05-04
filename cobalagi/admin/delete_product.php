<?php
require_once '../config/config.php';

// Accept ID from either GET or JSON payload
$id = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
}

if (!$id) {
    if (isset($_GET['id'])) {
        die("ID tidak valid");
    } else {
        die(json_encode(['success' => false, 'message' => 'ID tidak valid']));
    }
}

// Set is_deleted = 1 instead of hard deleting
if (secure_query($conn, "UPDATE products SET is_deleted = 1, tersedia = 0 WHERE id = ?", "i", [$id], false)) {
    if (isset($_GET['id'])) {
        header("Location: admin.php?msg=deleted");
        exit;
    } else {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus (soft delete)']);
    }
} else {
    if (isset($_GET['id'])) {
        die("Error: Gagal menghapus produk.");
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: Gagal menghapus produk.']);
    }
}

mysqli_close($conn);
?>