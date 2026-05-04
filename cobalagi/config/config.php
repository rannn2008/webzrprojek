<?php
// config.php - Koneksi database Pondok Es Teller ZR
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pondokestellerzr_db';

// Buat koneksi ke MySQL server
$conn = mysqli_connect($host, $username, $password);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Buat database jika belum ada
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $database 
                  CHARACTER SET utf8mb4 
                  COLLATE utf8mb4_general_ci";
mysqli_query($conn, $sql_create_db);

// Pilih database
mysqli_select_db($conn, $database);

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Fungsi untuk bersihkan input
function bersihkan_input($data, $conn)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);