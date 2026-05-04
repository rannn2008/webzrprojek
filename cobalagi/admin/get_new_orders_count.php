<?php
require_once '../config/config.php';

// Return count of new orders as JSON
$result = secure_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('new','baru','') OR status IS NULL", "", []);
$row = fetch_one($result);

header('Content-Type: application/json');
echo json_encode(['count' => (int)$row['total']]);

mysqli_close($conn);
?>
