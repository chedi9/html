<?php
require_once __DIR__ . '/includes/featured_products_cache.php';

// Simple CLI/HTTP script to clean featured products cache
// Usage (CLI): php cache_cleanup.php [--clear-all]
// Usage (HTTP): /cache_cleanup.php?clear_all=1

$cache = new FeaturedProductsCache();

$isCli = (php_sapi_name() === 'cli');
$clearAll = false;

if ($isCli) {
	$clearAll = in_array('--clear-all', $argv ?? []);
} else {
	$clearAll = isset($_GET['clear_all']) && $_GET['clear_all'] == '1';
	header('Content-Type: text/plain; charset=utf-8');
}

if ($clearAll) {
	$cache->clear();
	$output = "All featured products cache files have been cleared.\n";
} else {
	$cleaned = $cache->cleanExpired();
	$stats = $cache->getStats();
	$output = "Expired cache cleanup complete.\n";
	$output .= "Files cleaned: {$cleaned}\n";
	$output .= "Total cache files remaining: {$stats['total_files']}\n";
	$output .= "Total cache size (bytes): {$stats['total_size']}\n";
}

if ($isCli) {
	echo $output;
	exit(0);
} else {
	echo $output;
}
?>