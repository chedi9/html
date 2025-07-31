<?php
require_once 'db.php';
require_once 'includes/thumbnail_helper.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'generate_all':
            $result = generateAllMissingThumbnails();
            echo json_encode($result);
            break;
            
        case 'cleanup':
            $result = cleanupOrphanedThumbnails();
            echo json_encode($result);
            break;
            
        case 'test_generation':
            $result = testThumbnailGeneration();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function generateAllMissingThumbnails() {
    global $pdo;
    
    $generated = 0;
    $start_time = microtime(true);
    
    // Get all product images
    $stmt = $pdo->prepare("SELECT image FROM products WHERE image IS NOT NULL AND image != ''");
    $stmt->execute();
    $product_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all category images
    $stmt = $pdo->prepare("SELECT image, icon FROM categories WHERE (image IS NOT NULL AND image != '') OR (icon IS NOT NULL AND icon != '')");
    $stmt->execute();
    $category_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $all_images = [];
    
    // Add product images
    foreach ($product_images as $image) {
        $all_images[] = 'uploads/' . $image;
    }
    
    // Add category images
    foreach ($category_images as $category) {
        if (!empty($category['image'])) {
            $all_images[] = 'uploads/' . $category['image'];
        }
        if (!empty($category['icon'])) {
            $all_images[] = 'uploads/' . $category['icon'];
        }
    }
    
    // Generate thumbnails for each image
    foreach ($all_images as $image_path) {
        if (file_exists($image_path)) {
            $sizes = ['small', 'medium', 'large'];
            foreach ($sizes as $size) {
                if (ensure_thumbnail_exists($image_path, $size)) {
                    $generated++;
                }
            }
        }
    }
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    return [
        'success' => true,
        'generated' => $generated,
        'total_images' => count($all_images),
        'execution_time' => round($execution_time, 2),
        'average_time' => count($all_images) > 0 ? round($execution_time / count($all_images), 2) : 0
    ];
}

function cleanupOrphanedThumbnails() {
    global $pdo;
    
    // Get all valid image paths
    $valid_images = [];
    
    // Get product images
    $stmt = $pdo->prepare("SELECT image FROM products WHERE image IS NOT NULL AND image != ''");
    $stmt->execute();
    $product_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($product_images as $image) {
        $valid_images[] = 'uploads/' . $image;
    }
    
    // Get category images
    $stmt = $pdo->prepare("SELECT image, icon FROM categories WHERE (image IS NOT NULL AND image != '') OR (icon IS NOT NULL AND icon != '')");
    $stmt->execute();
    $category_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($category_images as $category) {
        if (!empty($category['image'])) {
            $valid_images[] = 'uploads/' . $category['image'];
        }
        if (!empty($category['icon'])) {
            $valid_images[] = 'uploads/' . $category['icon'];
        }
    }
    
    // Clean up orphaned thumbnails
    $cleaned = cleanup_orphaned_thumbnails($valid_images);
    
    return [
        'success' => true,
        'cleaned' => $cleaned,
        'valid_images' => count($valid_images)
    ];
}

function testThumbnailGeneration() {
    global $pdo;
    
    $start_time = microtime(true);
    $generated = 0;
    
    // Test with a few sample images
    $stmt = $pdo->prepare("SELECT image FROM products WHERE image IS NOT NULL AND image != '' LIMIT 3");
    $stmt->execute();
    $test_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($test_images as $image) {
        $image_path = 'uploads/' . $image;
        if (file_exists($image_path)) {
            $sizes = ['small', 'medium', 'large'];
            foreach ($sizes as $size) {
                if (ensure_thumbnail_exists($image_path, $size)) {
                    $generated++;
                }
            }
        }
    }
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    return [
        'success' => true,
        'generated' => $generated,
        'time' => round($execution_time, 2),
        'average' => $generated > 0 ? round($execution_time / $generated, 2) : 0
    ];
}
?> 