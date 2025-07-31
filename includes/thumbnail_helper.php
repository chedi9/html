<?php
/**
 * Thumbnail Helper Functions
 * Centralized thumbnail management for the WeBuy platform
 */

/**
 * Get thumbnail path for an image
 * @param string $image_path Original image path
 * @param string $size Thumbnail size (small, medium, large)
 * @return string Thumbnail path
 */
function get_thumbnail_path($image_path, $size = 'medium') {
    if (empty($image_path)) {
        return '';
    }
    
    $sizes = [
        'small' => 150,
        'medium' => 300,
        'large' => 500
    ];
    
    $size_value = $sizes[$size] ?? 300;
    $filename = pathinfo($image_path, PATHINFO_FILENAME);
    $extension = pathinfo($image_path, PATHINFO_EXTENSION);
    
    // Check if thumbnail exists
    $thumb_path = "uploads/thumbnails/{$filename}_{$size_value}.jpg";
    
    if (file_exists($thumb_path)) {
        return $thumb_path;
    }
    
    // If thumbnail doesn't exist, return original
    return $image_path;
}

/**
 * Get optimized image source for different contexts
 * @param string $image_path Original image path
 * @param string $context Usage context (card, gallery, admin, etc.)
 * @return array Image data with src, srcset, and sizes
 */
function get_optimized_image($image_path, $context = 'card') {
    if (empty($image_path)) {
        return [
            'src' => '',
            'srcset' => '',
            'sizes' => '',
            'alt' => ''
        ];
    }
    
    $contexts = [
        'card' => ['small', 'medium'],
        'gallery' => ['medium', 'large'],
        'admin' => ['small'],
        'hero' => ['large'],
        'category' => ['medium']
    ];
    
    $sizes = $contexts[$context] ?? ['medium'];
    $srcset = [];
    $sizes_attr = '';
    
    foreach ($sizes as $size) {
        $thumb_path = get_thumbnail_path($image_path, $size);
        $width = $size === 'small' ? 150 : ($size === 'medium' ? 300 : 500);
        $srcset[] = "{$thumb_path} {$width}w";
    }
    
    // Set appropriate sizes attribute
    switch ($context) {
        case 'card':
            $sizes_attr = '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 25vw';
            break;
        case 'gallery':
            $sizes_attr = '(max-width: 768px) 100vw, 50vw';
            break;
        case 'admin':
            $sizes_attr = '150px';
            break;
        case 'hero':
            $sizes_attr = '100vw';
            break;
        case 'category':
            $sizes_attr = '(max-width: 768px) 100vw, 33vw';
            break;
    }
    
    return [
        'src' => get_thumbnail_path($image_path, $sizes[0]),
        'srcset' => implode(', ', $srcset),
        'sizes' => $sizes_attr,
        'alt' => pathinfo($image_path, PATHINFO_FILENAME)
    ];
}

/**
 * Check if thumbnail exists and create if missing
 * @param string $image_path Original image path
 * @param string $size Thumbnail size
 * @return bool Success status
 */
function ensure_thumbnail_exists($image_path, $size = 'medium') {
    if (empty($image_path) || !file_exists($image_path)) {
        return false;
    }
    
    $sizes = [
        'small' => 150,
        'medium' => 300,
        'large' => 500
    ];
    
    $size_value = $sizes[$size] ?? 300;
    $filename = pathinfo($image_path, PATHINFO_FILENAME);
    $thumb_path = "uploads/thumbnails/{$filename}_{$size_value}.jpg";
    
    // If thumbnail already exists, return true
    if (file_exists($thumb_path)) {
        return true;
    }
    
    // Create thumbnail directory if it doesn't exist
    $thumb_dir = 'uploads/thumbnails/';
    if (!is_dir($thumb_dir)) {
        mkdir($thumb_dir, 0777, true);
    }
    
    // Include thumbnail creation function
    require_once 'client/make_thumbnail.php';
    
    // Create thumbnail
    return make_thumbnail($image_path, $thumb_path, $size_value, $size_value);
}

/**
 * Generate all thumbnails for an image
 * @param string $image_path Original image path
 * @return array Generated thumbnail paths
 */
function generate_all_thumbnails($image_path) {
    $sizes = ['small' => 150, 'medium' => 300, 'large' => 500];
    $generated = [];
    
    foreach ($sizes as $size_name => $size_value) {
        if (ensure_thumbnail_exists($image_path, $size_name)) {
            $filename = pathinfo($image_path, PATHINFO_FILENAME);
            $generated[] = "uploads/thumbnails/{$filename}_{$size_value}.jpg";
        }
    }
    
    return $generated;
}

/**
 * Clean up orphaned thumbnails
 * @param array $valid_images Array of valid image paths
 * @return int Number of files cleaned up
 */
function cleanup_orphaned_thumbnails($valid_images) {
    $thumb_dir = 'uploads/thumbnails/';
    if (!is_dir($thumb_dir)) {
        return 0;
    }
    
    $valid_filenames = [];
    foreach ($valid_images as $image) {
        $valid_filenames[] = pathinfo($image, PATHINFO_FILENAME);
    }
    
    $cleaned = 0;
    $files = glob($thumb_dir . '*.jpg');
    
    foreach ($files as $file) {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $base_filename = preg_replace('/_\d+$/', '', $filename);
        
        if (!in_array($base_filename, $valid_filenames)) {
            unlink($file);
            $cleaned++;
        }
    }
    
    return $cleaned;
}
?> 