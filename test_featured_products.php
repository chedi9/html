<?php
/**
 * Featured Products Test Script
 * Tests the featured products API and functionality
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Featured Products Test Script ===\n\n";

// Test 1: Check if required files exist
echo "1. Checking required files...\n";
$required_files = [
    'api/featured-products.php',
    'includes/featured_products_cache.php',
    'js/featured-products.js',
    'css/components/featured-products.css',
    'cache_cleanup.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✓ {$file} exists\n";
    } else {
        echo "✗ {$file} missing\n";
    }
}

// Test 2: Test cache functionality
echo "\n2. Testing cache functionality...\n";
try {
    require_once 'includes/featured_products_cache.php';
    $cache = new FeaturedProductsCache();
    echo "✓ Cache class loaded successfully\n";
    
    // Test cache operations
    $test_data = ['test' => 'data'];
    $cache->set(1, 'en', $test_data);
    echo "✓ Cache set operation successful\n";
    
    $retrieved = $cache->get(1, 'en');
    if ($retrieved && $retrieved['test'] === 'data') {
        echo "✓ Cache get operation successful\n";
    } else {
        echo "✗ Cache get operation failed\n";
    }
    
    // Clean up test cache
    $cache->clearPage(1, 'en');
    echo "✓ Cache cleanup successful\n";
    
} catch (Exception $e) {
    echo "✗ Cache test failed: " . $e->getMessage() . "\n";
}

// Test 3: Test database connection
echo "\n3. Testing database connection...\n";
try {
    require 'db.php';
    echo "✓ Database connection successful\n";
    
    // Test if products table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Products table exists\n";
    } else {
        echo "✗ Products table missing\n";
    }
    
    // Test if disabled_sellers table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'disabled_sellers'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Disabled sellers table exists\n";
    } else {
        echo "✗ Disabled sellers table missing\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database test failed: " . $e->getMessage() . "\n";
}

// Test 4: Testing API endpoint...
echo "\n4. Testing API endpoint...\n";
try {
    // Set up test environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['page'] = 1;
    $_GET['lang'] = 'en';
    $_SESSION['lang'] = 'en';
    
    // Check if security integration file exists
    $security_file = 'security_integration.php';
    if (!file_exists($security_file)) {
        echo "⚠ security_integration.php not found - API will use fallback security\n";
    } else {
        echo "✓ security_integration.php found\n";
    }
    
    // Capture output with error handling
    ob_start();
    $include_result = @include 'api/featured-products.php';
    $output = ob_get_clean();
    
    if ($include_result === false) {
        echo "✗ Failed to include API file\n";
        echo "  Error output: " . $output . "\n";
    } else {
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            echo "✓ API endpoint responds correctly\n";
            if ($response['success']) {
                echo "✓ API returned success response\n";
                if (isset($response['data']['products'])) {
                    echo "✓ Products data structure correct\n";
                    echo "  - Found " . count($response['data']['products']) . " products\n";
                }
                if (isset($response['data']['pagination'])) {
                    echo "✓ Pagination data structure correct\n";
                }
            } else {
                echo "✗ API returned error: " . ($response['error'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "✗ API response format invalid\n";
            echo "  Raw output: " . substr($output, 0, 200) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Test 5: Test language files
echo "\n5. Testing language files...\n";
$lang_files = ['lang/ar.php', 'lang/en.php', 'lang/fr.php'];
foreach ($lang_files as $lang_file) {
    if (file_exists($lang_file)) {
        include $lang_file;
        if (function_exists('__')) {
            $test_key = 'featured_products';
            $translation = __($test_key);
            if ($translation && $translation !== $test_key) {
                echo "✓ {$lang_file} translations working\n";
            } else {
                echo "✗ {$lang_file} translation missing for '{$test_key}'\n";
            }
        } else {
            echo "✗ {$lang_file} translation function not available\n";
        }
    } else {
        echo "✗ {$lang_file} missing\n";
    }
}

// Test 6: Test cache directory
echo "\n6. Testing cache directory...\n";
$cache_dir = 'cache/featured_products/';
if (is_dir($cache_dir)) {
    echo "✓ Cache directory exists\n";
    if (is_writable($cache_dir)) {
        echo "✓ Cache directory is writable\n";
    } else {
        echo "✗ Cache directory is not writable\n";
    }
} else {
    echo "✗ Cache directory missing\n";
    // Try to create it
    if (mkdir($cache_dir, 0755, true)) {
        echo "✓ Cache directory created successfully\n";
    } else {
        echo "✗ Failed to create cache directory\n";
    }
}

// Test 7: Performance test
echo "\n7. Performance test...\n";
try {
    $start_time = microtime(true);
    
    // Simulate API request
    $_GET['page'] = 1;
    $_GET['lang'] = 'en';
    
    ob_start();
    include 'api/featured-products.php';
    ob_end_clean();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    if ($execution_time < 1000) { // Less than 1 second
        echo "✓ API response time: " . number_format($execution_time, 2) . "ms (Good)\n";
    } elseif ($execution_time < 3000) { // Less than 3 seconds
        echo "⚠ API response time: " . number_format($execution_time, 2) . "ms (Acceptable)\n";
    } else {
        echo "✗ API response time: " . number_format($execution_time, 2) . "ms (Slow)\n";
    }
    
} catch (Exception $e) {
    echo "✗ Performance test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "All tests completed. Check the output above for any issues.\n";
echo "If all tests pass, the featured products implementation should work correctly.\n\n";

echo "Next steps:\n";
echo "1. Test the frontend by visiting the homepage\n";
echo "2. Check browser console for any JavaScript errors\n";
echo "3. Test responsive design on different screen sizes\n";
echo "4. Verify wishlist and cart functionality\n";
echo "5. Test language switching\n";
?>