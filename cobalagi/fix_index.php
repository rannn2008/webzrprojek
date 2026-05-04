<?php
$file = 'index.php';
$lines = file($file);

$hero_header_index = -1;
foreach ($lines as $i => $line) {
    if (strpos($line, '<h1>We Offer A Delicious <br>Variety Of ICE</h1>') !== false) {
        $hero_header_index = $i;
        break;
    }
}

$recovery_start_index = -1;
foreach ($lines as $i => $line) {
    if (strpos($line, '<h3 class="card-title"><?php echo $row[\'nama\']; ?></h3>') !== false) {
        $recovery_start_index = $i;
        break;
    }
}

if ($hero_header_index !== -1 && $recovery_start_index !== -1) {
    // Keep everything up to the hero header index + 2 (title and p tag and link tag)
    // Actually, line 1449 is the h1. 1450 is p. 1451 is a. 
    // We want to stop at 1451 and resume at recovery_start_index.
    
    $prefix = array_slice($lines, 0, $hero_header_index + 3); // Keep h1, p, a
    $suffix = array_slice($lines, $recovery_start_index);
    
    $middle = [
        "\n",
        "                <div class=\"pagination-dots\">\n",
        "                    <div class=\"dot active\"></div>\n",
        "                    <div class=\"dot\"></div>\n",
        "                    <div class=\"dot\"></div>\n",
        "                </div>\n",
        "            </div>\n",
        "        </div>\n",
        "    </section>\n",
        "\n",
        "    <!-- Menu Section -->\n",
        "    <section id=\"menu\" class=\"section\">\n",
        "        <div class=\"container\">\n",
        "            <h2 class=\"section-title\" data-aos=\"fade-up\">Ice Popular Menu</h2>\n",
        "            <p class=\"section-subtitle\" data-aos=\"fade-up\" data-aos-delay=\"100\">Proin libero nunc consequat interdum\n",
        "                feugiat in ferme lorem.</p>\n",
        "\n",
        "            <!-- Category Filter Mapping: map 'original', 'premium', 'icecream' to new labels if needed -->\n",
        "            <div class=\"category-tabs\" data-aos=\"fade-up\" data-aos-delay=\"200\" id=\"categoryTabs\">\n",
        "                <button class=\"tab-btn active\" data-filter=\"all\">All</button>\n",
        "                <button class=\"tab-btn\" data-filter=\"original\">Chocolate</button>\n",
        "                <button class=\"tab-btn\" data-filter=\"premium\">Coffee</button>\n",
        "                <button class=\"tab-btn\" data-filter=\"icecream\">Sweets</button>\n",
        "                <button class=\"tab-btn\" data-filter=\"blacktea\">Black Tea</button>\n",
        "                <button class=\"tab-btn\" data-filter=\"greentea\">Green Tea</button>\n",
        "            </div>\n",
        "\n",
        "            <div class=\"menu-grid\" id=\"productGrid\">\n",
        "                <?php\n",
        "                \$query = secure_query(\$conn, \"SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count \n",
        "                           FROM products p \n",
        "                           LEFT JOIN reviews r ON p.id = r.product_id \n",
        "                           WHERE p.tersedia = 1 AND p.is_deleted = 0 \n",
        "                           GROUP BY p.id \n",
        "                           ORDER BY p.created_at DESC\");\n",
        "                if (\$query && \$query->num_rows > 0) {\n",
        "                    while (\$row = \$query->fetch_array()) {\n",
        "                        \$imgUrl = (!empty(\$row['gambar']) && file_exists('assets/images/products/' . \$row['gambar'])) ?\n",
        "                            \"assets/images/products/\" . \$row['gambar'] :\n",
        "                            \"https://images.unsplash.com/photo-1541167760496-1628856ab772?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80\"; // Default coffee image\n",
        "                \n",
        "                        // Generate a fake cross-out price (approx 50-60% higher to match the design aesthetics \$358 vs \$225)\n",
        "                        \$fakePrice = \$row['harga'] * 1.5;\n",
        "\n",
        "                        // Create JSON for JS Cart\n",
        "                        \$productData = json_encode([\n",
        "                            'id' => \$row['id'],\n",
        "                            'name' => \$row['nama'],\n",
        "                            'price' => \$row['harga'],\n",
        "                            'image' => \$imgUrl\n",
        "                        ]);\n",
        "                        \$productData = htmlspecialchars(\$productData, ENT_QUOTES, 'UTF-8');\n",
        "\n",
        "                        \$kategori = strtolower(trim(\$row['kategori']));\n",
        "                        ?>\n",
        "                        <div class=\"menu-card\" data-aos=\"fade-up\" data-category=\"<?php echo \$kategori; ?>\"\n",
        "                            data-name=\"<?php echo strtolower(\$row['nama']); ?>\">\n",
        "                            <div class=\"card-img-wrapper\">\n",
        "                                <img src=\"<?php echo \$imgUrl; ?>\" alt=\"<?php echo \$row['nama']; ?>\" class=\"card-img\"\n",
        "                                    onerror=\"this.src='https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=600&q=80'\">\n",
        "                                <div class=\"card-pills\">\n",
        "                                    <!-- Optional Badge -->\n",
        "                                </div>\n",
        "                            </div>\n",
        "                            <div class=\"card-body\">\n"
    ];
    
    $final_lines = array_merge($prefix, $middle, $suffix);
    if (file_put_contents($file, implode('', $final_lines))) {
        echo "Successfully recovered index.php via line mapping\n";
    } else {
        echo "Failed to write to index.php\n";
    }
} else {
    echo "Could not find markers. Header: " . ($hero_header_index === -1 ? "NO" : "YES ($hero_header_index)") . ", Recovery: " . ($recovery_start_index === -1 ? "NO" : "YES ($recovery_start_index)") . "\n";
}
