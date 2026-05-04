<?php
require_once '../config/config.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Akses ditolak. Silakan login sebagai admin.");
}

echo "<h1>Cek Folder images/products</h1>";

$folder_path = '../assets/images/products/';

// Cek folder
echo "Folder: " . realpath($folder_path) . "<br>";
echo "Folder exists: " . (file_exists($folder_path) ? '✅ ADA' : '❌ TIDAK ADA') . "<br>";

// List semua file
if (file_exists($folder_path)) {
    $files = scandir($folder_path);
    echo "<h3>File di folder:</h3>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $file_path = $folder_path . $file;
            $exists = file_exists($file_path) ? '✅' : '❌';
            $size = filesize($file_path);
            echo "<li>$exists $file ($size bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Folder tidak ditemukan! Buat folder '../assets/images/products/'</p>";
}

// Cek data di database
$result = secure_query($conn, "SELECT id, nama, gambar FROM products", "", []);

echo "<h3>Data di database:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nama Produk</th><th>File Gambar</th><th>File Ada?</th></tr>";

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $file_exists = '❌';
        if (!empty($row['gambar'])) {
            $file_path = $folder_path . $row['gambar'];
            $file_exists = file_exists($file_path) ? '✅' : '❌';
        }
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nama']}</td>";
    echo "<td>{$row['gambar']}</td>";
    echo "<td>$file_exists</td>";
    echo "</tr>";
}
echo "</table>";
?>
