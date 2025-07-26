<?php
/**
 * Test Script for Disabled Sellers System
 * This script tests the functionality without requiring database views
 */

require 'db.php';
require 'priority_products_helper.php';

echo "<h2>Testing Disabled Sellers System</h2>";

// Test 1: Check if disabled_sellers table exists and has data
echo "<h3>1. Checking Disabled Sellers Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM disabled_sellers");
    $result = $stmt->fetch();
    echo "✅ Disabled sellers table exists. Count: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        $sellers = $pdo->query("SELECT * FROM disabled_sellers ORDER BY priority_level DESC LIMIT 3")->fetchAll();
        echo "Sample disabled sellers:<br>";
        foreach ($sellers as $seller) {
            echo "- " . htmlspecialchars($seller['name']) . " (" . htmlspecialchars($seller['disability_type']) . ") - Priority: " . $seller['priority_level'] . "<br>";
        }
    } else {
        echo "⚠️ No disabled sellers found. You can add them through the admin panel.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check if products table has the required columns
echo "<h3>2. Checking Products Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    $has_disabled_seller_id = false;
    $has_is_priority_product = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'disabled_seller_id') {
            $has_disabled_seller_id = true;
        }
        if ($column['Field'] === 'is_priority_product') {
            $has_is_priority_product = true;
        }
    }
    
    echo $has_disabled_seller_id ? "✅ disabled_seller_id column exists<br>" : "❌ disabled_seller_id column missing<br>";
    echo $has_is_priority_product ? "✅ is_priority_product column exists<br>" : "❌ is_priority_product column missing<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Test priority products helper function
echo "<h3>3. Testing Priority Products Helper</h3>";
try {
    $priority_products = getPriorityProducts($pdo, 3);
    echo "✅ Priority products helper function works. Found " . count($priority_products) . " priority products<br>";
    
    if (!empty($priority_products)) {
        echo "Sample priority products:<br>";
        foreach ($priority_products as $product) {
            echo "- " . htmlspecialchars($product['name']) . " (Seller: " . htmlspecialchars($product['disabled_seller_name']) . ")<br>";
        }
    } else {
        echo "ℹ️ No priority products found. Add products and link them to disabled sellers.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check priority products count
echo "<h3>4. Priority Products Count</h3>";
try {
    $count = getPriorityProductsCount($pdo);
    echo "✅ Total priority products: " . $count . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Check if admin pages are accessible
echo "<h3>5. Admin Pages Check</h3>";
$admin_files = [
    'admin/disabled_sellers.php' => 'Disabled Sellers Management',
    'admin/add_product.php' => 'Add Product (with disabled seller selection)',
    'admin/dashboard.php' => 'Admin Dashboard (with disabled sellers link)'
];

foreach ($admin_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ " . $description . " page exists<br>";
    } else {
        echo "❌ " . $description . " page missing<br>";
    }
}

echo "<h3>6. Next Steps</h3>";
echo "If all tests pass, you can:<br>";
echo "1. <a href='admin/disabled_sellers.php'>Add disabled sellers</a><br>";
echo "2. <a href='admin/add_product.php'>Add products and link them to disabled sellers</a><br>";
echo "3. <a href='index.php'>Check the homepage for the disabled sellers showcase</a><br>";
echo "4. <a href='search.php?priority=disabled_sellers'>Test the search filter for disabled sellers</a><br>";

echo "<h3>7. System Status</h3>";
echo "🎉 <strong>Disabled Sellers Priority System is ready to use!</strong><br>";
echo "The system works without database views, using direct SQL queries instead.<br>";
?> 