<?php
$file = 'customer/customer_dashboard.php';
$content = file_get_contents($file);

// 1. Refactor Top Logic
$top_pattern = '/\/\/ --- Handle Review Submission[\s\S]+?\/\/ Get existing reviews for this customer \(keyed by order_id\)[\s\S]+?\}\n\}/';
$top_replacement = '// --- Handle Review Submission (1 review per order) ---
if (isset($_POST["submit_review"])) {
    $order_id = intval($_POST["order_id"]);
    $rating = intval($_POST["rating"]);
    $comment = trim($_POST["comment"] ?? "");

    // Verify the order belongs to this customer and is done
    $verify = secure_query($conn, "SELECT id FROM orders WHERE id = ? AND customer_id = ? AND status IN (\'done\',\'selesai\')", "ii", [$order_id, $customer_id]);
    
    if ($verify && $verify->num_rows > 0) {
        // Check if review already exists for this order
        $check = secure_query($conn, "SELECT id FROM reviews WHERE customer_id = ? AND order_id = ?", "ii", [$customer_id, $order_id]);
        
        if ($check && $check->num_rows > 0) {
            // Update existing review
            secure_query($conn, "UPDATE reviews SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ? AND order_id = ?", "isii", [$rating, $comment, $customer_id, $order_id]);
        } else {
            // Insert new review
            secure_query($conn, "INSERT INTO reviews (customer_id, order_id, rating, comment) VALUES (?, ?, ?, ?)", "iiis", [$customer_id, $order_id, $rating, $comment]);
        }

        header("Location: customer_dashboard.php?review_success=1");
        exit();
    }
}

// Get Customer Data
$customer = fetch_one(secure_query($conn, "SELECT * FROM customers WHERE id = ?", "i", [$customer_id]));

// Get customer orders + online receipt data
$orders_query = secure_query($conn, "SELECT 
                                    o.*,
                                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as total_items,
                                    r.receipt_code,
                                    r.generated_at as receipt_generated_at,
                                    r.pickup_confirmed_at
                                FROM orders o 
                                LEFT JOIN order_receipts r ON r.order_id = o.id
                                WHERE o.customer_id = ? 
                                ORDER BY o.created_at DESC", "i", [$customer_id]);

// Get existing reviews for this customer (keyed by order_id)
$customer_reviews = [];
$q_reviews = secure_query($conn, "SELECT * FROM reviews WHERE customer_id = ? AND order_id IS NOT NULL", "i", [$customer_id]);
if ($q_reviews) {
    while ($rev = $q_reviews->fetch_assoc()) {
        $customer_reviews[$rev["order_id"]] = $rev;
    }
}';

$content = preg_replace($top_pattern, $top_replacement, $content);

// 2. Refactor loop condition
$content = str_replace(
    "<?php if (mysqli_num_rows(\$orders) > 0): ?>",
    "<?php if (\$orders_query && \$orders_query->num_rows > 0): ?>",
    $content
);
$content = str_replace(
    "<?php while (\$order = mysqli_fetch_assoc(\$orders)):",
    "<?php while (\$order = \$orders_query->fetch_assoc()):",
    $content
);

file_put_contents($file, $content);
echo "Hardened customer_dashboard.php\n";
