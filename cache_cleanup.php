<?php
/**
 * Cache Cleanup Script
 * Cleans expired cache files for featured products
 * Can be run via cron job or manually
 */

require_once 'includes/featured_products_cache.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $cache = new FeaturedProductsCache();
    
    // Clean expired cache files
    $cleaned = $cache->cleanExpired();
    
    // Get cache statistics
    $stats = $cache->getStats();
    
    // Output results
    echo "Cache cleanup completed successfully!\n";
    echo "Files cleaned: {$cleaned}\n";
    echo "Total cache files: {$stats['total_files']}\n";
    echo "Total cache size: " . number_format($stats['total_size'] / 1024, 2) . " KB\n";
    
    if ($stats['oldest_file']) {
        echo "Oldest cache file: " . date('Y-m-d H:i:s', $stats['oldest_file']) . "\n";
    }
    if ($stats['newest_file']) {
        echo "Newest cache file: " . date('Y-m-d H:i:s', $stats['newest_file']) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error during cache cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
?>