<?php
require_once 'config/config.php';
require_once 'includes/db_helper.php';

$q = secure_query($conn, "SELECT id, order_code, nama_customer FROM orders LIMIT 10", "", []);
while ($row = fetch_one($q)) {
    print_r($row);
}
?>
