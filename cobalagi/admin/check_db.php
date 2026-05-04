<?php
require_once '../config/config.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Akses ditolak. Silakan login sebagai admin.");
}

echo "<h2>Database Structure Check</h2>";

// Cek semua tabel
$tables = ['orders', 'products', 'order_items', 'admin_users', 'activity_logs', 'customers', 'chats'];

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $result = secure_query($conn, "SHOW COLUMNS FROM $table", "", []);
    
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Table doesn't exist or error: " . mysqli_error($conn);
    }
    echo "<hr>";
}

// Cek isi orders
echo "<h3>Data in orders table:</h3>";
$result = secure_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10", "", []);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No orders found";
}

mysqli_close($conn);
?>
