<?php
require_once 'config/config.php';
require_once 'includes/db_helper.php';

$q_orders = secure_query($conn, "SELECT o.*, c.foto_profil, COUNT(oi.id) as items_count 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                GROUP BY o.id 
                ORDER BY o.created_at DESC LIMIT 1", "", []);

$o = fetch_one($q_orders);
echo "<pre>";
print_r($o);
echo "</pre>";
?>
