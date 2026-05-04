<?php
require_once 'config/config.php';
require_once 'includes/db_helper.php';

$q = secure_query($conn, "SELECT nama FROM customers WHERE id=?", "i", [4]);
print_r(fetch_one($q));
?>
