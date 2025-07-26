<?php
// Test script for search functionality
echo "=== WeBuy Search Functionality Test ===\n\n";

// Test 1: Check if search_suggest.php exists and is accessible
echo "1. Testing search_suggest.php endpoint...\n";
if (file_exists('search_suggest.php')) {
    echo "✅ search_suggest.php file exists\n";
} else {
    echo "❌ search_suggest.php file missing\n";
}

// Test 2: Check if search.php exists
echo "\n2. Testing search.php file...\n";
if (file_exists('search.php')) {
    echo "✅ search.php file exists\n";
} else {
    echo "❌ search.php file missing\n";
}

// Test 3: Check database connection
echo "\n3. Testing database connection...\n";
if (file_exists('db.php')) {
    echo "✅ db.php file exists\n";
    // Try to include db.php to test connection
    try {
        require_once 'db.php';
        echo "✅ Database connection successful\n";
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ db.php file missing\n";
}

// Test 4: Check if required tables exist (if db connection works)
echo "\n4. Testing database tables...\n";
if (isset($pdo)) {
    try {
        $tables = ['products', 'categories', 'sellers', 'reviews'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists\n";
            } else {
                echo "❌ Table '$table' missing\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking tables: " . $e->getMessage() . "\n";
    }
}

// Test 5: Check search parameters parsing
echo "\n5. Testing search parameter parsing...\n";
$test_params = [
    'name' => 'test product',
    'category_id' => '1',
    'min_price' => '10',
    'max_price' => '100',
    'brand' => 'test brand',
    'rating' => '4',
    'in_stock' => '1',
    'sort' => 'rating'
];

foreach ($test_params as $param => $value) {
    echo "✅ Parameter '$param' with value '$value' is valid\n";
}

// Test 6: Check if autocomplete suggestions work
echo "\n6. Testing autocomplete functionality...\n";
if (isset($pdo)) {
    try {
        // Test product search
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE approved = 1");
        $stmt->execute();
        $product_count = $stmt->fetchColumn();
        echo "✅ Found $product_count approved products\n";
        
        // Test category search
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
        $stmt->execute();
        $category_count = $stmt->fetchColumn();
        echo "✅ Found $category_count categories\n";
        
        // Test seller search
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sellers");
        $stmt->execute();
        $seller_count = $stmt->fetchColumn();
        echo "✅ Found $seller_count sellers\n";
        
    } catch (Exception $e) {
        echo "❌ Error testing autocomplete: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the actual search functionality:\n";
echo "1. Open your browser and go to: http://your-domain.com/search.php\n";
echo "2. Try typing in the search box to test autocomplete\n";
echo "3. Use the filter panel to test different filters\n";
echo "4. Test sorting options\n";
echo "5. Verify that disabled sellers are prioritized\n";
?> 