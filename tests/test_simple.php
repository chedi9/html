<?php
/**
 * Simple Test File
 * Basic functionality test to verify the testing system works
 */

require_once '../db.php';

echo "<h1>🧪 Simple Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result) {
        echo "<p style='color: #28a745;'>✅ Database connection successful</p>";
    } else {
        echo "<p style='color: #dc3545;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Check if users table exists
echo "<h2>Test 2: Users Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($columns) > 0) {
        echo "<p style='color: #28a745;'>✅ Users table exists with " . count($columns) . " columns</p>";
        
        // Check for gender field
        $genderFound = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'gender') {
                $genderFound = true;
                break;
            }
        }
        
        if ($genderFound) {
            echo "<p style='color: #28a745;'>✅ Gender field exists in users table</p>";
        } else {
            echo "<p style='color: #ffc107;'>⚠️ Gender field not found in users table</p>";
        }
    } else {
        echo "<p style='color: #dc3545;'>❌ Users table exists but has no columns</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Users table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check if products table exists
echo "<h2>Test 3: Products Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($columns) > 0) {
        echo "<p style='color: #28a745;'>✅ Products table exists with " . count($columns) . " columns</p>";
    } else {
        echo "<p style='color: #dc3545;'>❌ Products table exists but has no columns</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Products table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Check if key files exist
echo "<h2>Test 4: Key Files</h2>";
$files = [
    '../client/register.php' => 'Registration System',
    '../client/login.php' => 'Login System',
    '../client/mailer.php' => 'Email System',
    '../client/verify.php' => 'Verification System',
    '../db.php' => 'Database Configuration'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: #28a745;'>✅ $description exists</p>";
    } else {
        echo "<p style='color: #dc3545;'>❌ $description not found</p>";
    }
}

echo "<h2>🎯 Test Summary</h2>";
echo "<p>This simple test verifies basic system functionality.</p>";
echo "<p>If all tests pass, your system is ready for more comprehensive testing.</p>";

echo "</div>";
?> 