<?php
/**
 * Database Status Checker for Disabled Sellers System
 * This script checks what's already set up in the database
 */

require 'db.php';

echo "<h2>Database Status Check - Disabled Sellers System</h2>";

// Check 1: disabled_sellers table
echo "<h3>1. Disabled Sellers Table</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'disabled_sellers'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ <strong>disabled_sellers table exists</strong><br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE disabled_sellers");
        $columns = $stmt->fetchAll();
        echo "Table structure:<br>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        // Check if there's data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM disabled_sellers");
        $result = $stmt->fetch();
        echo "Records in table: " . $result['count'] . "<br>";
        
        if ($result['count'] > 0) {
            $sellers = $pdo->query("SELECT * FROM disabled_sellers ORDER BY priority_level DESC LIMIT 3")->fetchAll();
            echo "Sample data:<br>";
            foreach ($sellers as $seller) {
                echo "- " . htmlspecialchars($seller['name']) . " (" . htmlspecialchars($seller['disability_type']) . ") - Priority: " . $seller['priority_level'] . "<br>";
            }
        }
    } else {
        echo "❌ <strong>disabled_sellers table does not exist</strong><br>";
        echo "You need to run the database setup script.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Check 2: products table columns
echo "<h3>2. Products Table Columns</h3>";
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
    
    echo $has_disabled_seller_id ? "✅ <strong>disabled_seller_id column exists</strong><br>" : "❌ <strong>disabled_seller_id column missing</strong><br>";
    echo $has_is_priority_product ? "✅ <strong>is_priority_product column exists</strong><br>" : "❌ <strong>is_priority_product column missing</strong><br>";
    
    // Check if there are any products linked to disabled sellers
    if ($has_disabled_seller_id) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE disabled_seller_id IS NOT NULL");
        $result = $stmt->fetch();
        echo "Products linked to disabled sellers: " . $result['count'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Check 3: Test the helper functions
echo "<h3>3. Helper Functions Test</h3>";
try {
    require_once 'priority_products_helper.php';
    
    $priority_products = getPriorityProducts($pdo, 3);
    echo "✅ <strong>Helper functions work correctly</strong><br>";
    echo "Priority products found: " . count($priority_products) . "<br>";
    
    $count = getPriorityProductsCount($pdo);
    echo "Total priority products count: " . $count . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Check 4: Admin pages
echo "<h3>4. Admin Pages Check</h3>";
$admin_files = [
    'admin/disabled_sellers.php' => 'Disabled Sellers Management',
    'admin/add_product.php' => 'Add Product (with disabled seller selection)',
    'admin/dashboard.php' => 'Admin Dashboard (with disabled sellers link)',
    'priority_products_helper.php' => 'Priority Products Helper',
    'test_disabled_sellers.php' => 'Test Script'
];

foreach ($admin_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ " . $description . " exists<br>";
    } else {
        echo "❌ " . $description . " missing<br>";
    }
}

// Summary
echo "<h3>5. Summary</h3>";
if ($table_exists && $has_disabled_seller_id && $has_is_priority_product) {
    echo "🎉 <strong>Database setup is complete!</strong><br>";
    echo "The disabled sellers system is ready to use.<br>";
    echo "<br>";
    echo "<strong>Next steps:</strong><br>";
    echo "1. <a href='admin/disabled_sellers.php'>Add more disabled sellers</a><br>";
    echo "2. <a href='admin/add_product.php'>Add products and link them to disabled sellers</a><br>";
    echo "3. <a href='index.php'>Check the homepage showcase</a><br>";
    echo "4. <a href='search.php?priority=disabled_sellers'>Test search functionality</a><br>";
} else {
    echo "⚠️ <strong>Database setup is incomplete</strong><br>";
    echo "Please run the database setup script to complete the installation.<br>";
    echo "<br>";
    echo "<strong>Missing components:</strong><br>";
    if (!$table_exists) echo "- disabled_sellers table<br>";
    if (!$has_disabled_seller_id) echo "- disabled_seller_id column in products table<br>";
    if (!$has_is_priority_product) echo "- is_priority_product column in products table<br>";
}
?> 