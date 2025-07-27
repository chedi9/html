<?php
require_once 'db.php';
require_once 'client/make_thumbnail.php';

echo "Starting thumbnail generation for existing images...\n";

// Create thumbnails directory if it doesn't exist
$thumb_dir = 'uploads/thumbnails/';
if (!is_dir($thumb_dir)) {
    mkdir($thumb_dir, 0777, true);
    echo "Created thumbnails directory: $thumb_dir\n";
}

// Get all product images
$stmt = $pdo->query('SELECT DISTINCT image FROM products WHERE image IS NOT NULL AND image != ""');
$product_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Found " . count($product_images) . " product images to process\n";

$processed = 0;
$errors = 0;

foreach ($product_images as $image) {
    $source_path = "uploads/$image";
    $thumb_path = $thumb_dir . pathinfo($image, PATHINFO_FILENAME) . '_thumb.jpg';
    
    if (file_exists($source_path)) {
        if (make_thumbnail($source_path, $thumb_path, 300, 300)) {
            $processed++;
            echo "✓ Generated thumbnail for: $image\n";
        } else {
            $errors++;
            echo "✗ Failed to generate thumbnail for: $image\n";
        }
    } else {
        $errors++;
        echo "✗ Source file not found: $source_path\n";
    }
}

// Get all product_images table entries
$stmt = $pdo->query('SELECT DISTINCT image_path FROM product_images WHERE image_path IS NOT NULL AND image_path != ""');
$additional_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nFound " . count($additional_images) . " additional product images to process\n";

foreach ($additional_images as $image) {
    $source_path = "uploads/$image";
    $thumb_path = $thumb_dir . pathinfo($image, PATHINFO_FILENAME) . '_thumb.jpg';
    
    if (file_exists($source_path)) {
        if (make_thumbnail($source_path, $thumb_path, 300, 300)) {
            $processed++;
            echo "✓ Generated thumbnail for: $image\n";
        } else {
            $errors++;
            echo "✗ Failed to generate thumbnail for: $image\n";
        }
    } else {
        $errors++;
        echo "✗ Source file not found: $source_path\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Successfully processed: $processed images\n";
echo "Errors: $errors\n";
echo "Thumbnails saved in: $thumb_dir\n";
echo "Done!\n";
?> 