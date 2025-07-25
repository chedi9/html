<?php
require 'db.php'; // Adjust path if needed

// Get all products
$products = $pdo->query("SELECT id FROM products")->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $prod) {
    $id = $prod['id'];
    // Get the main image for this product
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC, id ASC LIMIT 1");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img) {
        // Update products.image
        $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$img, $id]);
    }
}
echo "All products updated with their main image.";
?>