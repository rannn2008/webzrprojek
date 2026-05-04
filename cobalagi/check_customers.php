<?php
require_once 'config/config.php';
require_once 'includes/db_helper.php';

$q = secure_query($conn, "SHOW COLUMNS FROM customers", "", []);
while ($row = fetch_one($q)) {
    print_r($row);
}
?>
