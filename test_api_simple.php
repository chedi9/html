<?php
/**
 * Simple API Test - Bypasses complex security system
 * This script tests the featured products API with minimal dependencies
 */

echo "=== Simple Featured Products API Test ===\n\n";

// Test 1: Check required files
echo "1. Checking required files...\n";
$required_files = [
    'includes/featured_products_cache.php',
    'includes/thumbnail_helper.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✓ {$file} exists\n";
    } else {
        echo "✗ {$file} missing\n";
    }
}

// Test 2: Test API with minimal setup
echo "\n2. Testing API with minimal setup...\n";

// Set up minimal environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['page'] = 1;
$_GET['lang'] = 'en';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['lang'] = 'en';

// Set basic security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    // Include the API file
    ob_start();
    $include_result = @include 'api/featured-products.php';
    $output = ob_get_clean();
    
    if ($include_result === false) {
        echo "✗ Failed to include API file\n";
        echo "  Error output: " . $output . "\n";
    } else {
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            echo "✓ API responded successfully\n";
            if ($response['success']) {
                echo "✓ API returned success response\n";
                if (isset($response['data']['products'])) {
                    echo "  - Found " . count($response['data']['products']) . " featured products\n";
                    foreach ($response['data']['products'] as $product) {
                        echo "    * " . $product['name'] . " - $" . $product['price'] . "\n";
                    }
                }
                if (isset($response['data']['pagination'])) {
                    echo "  - Pagination: Page " . $response['data']['pagination']['current_page'] . " of " . $response['data']['pagination']['total_pages'] . "\n";
                }
            } else {
                echo "✗ API returned error: " . ($response['error'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "✗ API response format invalid\n";
            echo "  Raw output: " . substr($output, 0, 300) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
