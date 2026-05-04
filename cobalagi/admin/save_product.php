<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

function json_error($message, $status = 400)
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function generate_file_name($extension)
{
    try {
        return time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    } catch (Exception $e) {
        return time() . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 12) . '.' . $extension;
    }
}

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : 0;
$nama = trim($_POST['nama'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$harga = (int) ($_POST['harga'] ?? 0);
$kategori = trim($_POST['kategori'] ?? '');
$tersedia = isset($_POST['tersedia']) ? 1 : 0;
$gambarExisting = trim($_POST['gambar_existing'] ?? '');

if ($nama === '') {
    json_error('Nama produk wajib diisi.');
}
if ($harga <= 0) {
    json_error('Harga produk harus lebih dari 0.');
}
if ($kategori === '') {
    json_error('Kategori produk wajib diisi.');
}

$uploadDir = '../assets/images/products/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
    json_error('Gagal membuat folder upload.', 500);
}

$gambar = $gambarExisting;
$newUploadedFile = '';

if (isset($_FILES['gambar']) && ($_FILES['gambar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        json_error('Upload gambar gagal.');
    }

    if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
        json_error('Ukuran gambar maksimal 2MB.');
    }

    $tmpFile = $_FILES['gambar']['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpFile) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($mimeMap[$mime])) {
        json_error('Format gambar harus JPG, PNG, GIF, atau WEBP.');
    }

    $newUploadedFile = generate_file_name($mimeMap[$mime]);
    $targetPath = $uploadDir . $newUploadedFile;

    if (!move_uploaded_file($tmpFile, $targetPath)) {
        json_error('Gagal menyimpan file gambar.', 500);
    }

    $gambar = $newUploadedFile;
}

if ($id > 0) {
    $sql = "UPDATE products SET nama = ?, deskripsi = ?, harga = ?, kategori = ?, tersedia = ?, gambar = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        if ($newUploadedFile !== '' && file_exists($uploadDir . $newUploadedFile)) {
            unlink($uploadDir . $newUploadedFile);
        }
        json_error('Gagal menyiapkan query update.', 500);
    }

    mysqli_stmt_bind_param($stmt, "ssisssi", $nama, $deskripsi, $harga, $kategori, $tersedia, $gambar, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        if ($newUploadedFile !== '' && file_exists($uploadDir . $newUploadedFile)) {
            unlink($uploadDir . $newUploadedFile);
        }
        json_error('Gagal mengupdate produk.', 500);
    }

    // Remove old image after successful update if replaced
    if ($newUploadedFile !== '' && $gambarExisting !== '' && $gambarExisting !== $newUploadedFile && file_exists($uploadDir . $gambarExisting)) {
        @unlink($uploadDir . $gambarExisting);
    }

    echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate']);
} else {
    $sql = "INSERT INTO products (nama, deskripsi, harga, kategori, gambar, tersedia) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        if ($newUploadedFile !== '' && file_exists($uploadDir . $newUploadedFile)) {
            unlink($uploadDir . $newUploadedFile);
        }
        json_error('Gagal menyiapkan query insert.', 500);
    }

    mysqli_stmt_bind_param($stmt, "ssissi", $nama, $deskripsi, $harga, $kategori, $gambar, $tersedia);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        if ($newUploadedFile !== '' && file_exists($uploadDir . $newUploadedFile)) {
            unlink($uploadDir . $newUploadedFile);
        }
        json_error('Gagal menambah produk.', 500);
    }

    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan']);
}

mysqli_close($conn);
?>
