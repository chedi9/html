<?php
/**
 * Test API with Fallback Security
 * This script tests the featured products API with fallback security
 */

echo "=== Featured Products API Fallback Security Test ===\n\n";

// Test 1: Check if main security file exists
echo "1. Checking security files...\n";
$main_security = '../security_integration.php';
$fallback_security = 'security_fallback.php';

if (file_exists($main_security)) {
    echo "✓ Main security file exists\n";
} else {
    echo "⚠ Main security file not found\n";
}

if (file_exists($fallback_security)) {
    echo "✓ Fallback security file exists\n";
} else {
    echo "✗ Fallback security file missing\n";
}

// Test 2: Test API with fallback security
echo "\n2. Testing API with fallback security...\n";

// Simulate missing main security file by temporarily renaming it
$temp_name = null;
if (file_exists($main_security)) {
    $temp_name = $main_security . '.temp';
    rename($main_security, $temp_name);
    echo "  Temporarily moved main security file for testing\n";
}

try {
    // Set up test environment
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['page'] = 1;
    $_GET['lang'] = 'en';
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['lang'] = 'en';
    
    // Capture API output
    ob_start();
    $include_result = @include 'api/featured-products.php';
    $output = ob_get_clean();
    
    if ($include_result === false) {
        echo "✗ API failed to load with fallback security\n";
        echo "  Error output: " . $output . "\n";
    } else {
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            echo "✓ API works with fallback security\n";
            if ($response['success']) {
                echo "✓ API returned valid response\n";
                if (isset($response['data']['products'])) {
                    echo "  - Found " . count($response['data']['products']) . " products\n";
                }
            } else {
                echo "⚠ API returned error: " . ($response['error'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "✗ API response format invalid\n";
            echo "  Raw output: " . substr($output, 0, 200) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} finally {
    // Restore main security file if it was moved
    if ($temp_name && file_exists($temp_name)) {
        rename($temp_name, $main_security);
        echo "  Restored main security file\n";
    }
}

// Test 3: Verify security headers are set
echo "\n3. Checking security headers...\n";
$headers_list = headers_list();
$security_headers = [
    'X-Content-Type-Options',
    'X-Frame-Options', 
    'X-XSS-Protection',
    'Referrer-Policy'
];

foreach ($security_headers as $header) {
    $found = false;
    foreach ($headers_list as $sent_header) {
        if (stripos($sent_header, $header) === 0) {
            echo "✓ {$header} header set\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "✗ {$header} header not set\n";
    }
}

echo "\n=== Test Complete ===\n";
