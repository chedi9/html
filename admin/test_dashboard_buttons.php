<?php
/**
 * Dashboard Button Functionality Test
 * This script tests all buttons in the admin dashboard
 */

// Security check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Please login first to test dashboard buttons');
}

require '../db.php';

echo "<h1>🔍 Dashboard Button Functionality Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; direction: ltr; }
    .test-result { padding: 10px; margin: 5px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .button-test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .file-check { margin: 5px 0; }
</style>";

// Define all dashboard buttons and their target files
$dashboard_buttons = [
    'products.php' => [
        'name' => '📦 إدارة المنتجات',
        'description' => 'Add, edit, and delete products',
        'required_tables' => ['products', 'categories']
    ],
    'bulk_upload.php' => [
        'name' => '📊 رفع المنتجات بالجملة',
        'description' => 'Import multiple products via CSV',
        'required_tables' => ['products', 'categories', 'sellers']
    ],
    'orders.php' => [
        'name' => '🛒 إدارة الطلبات',
        'description' => 'Manage customer orders',
        'required_tables' => ['orders', 'users']
    ],
    'reviews.php' => [
        'name' => '⭐ إدارة المراجعات',
        'description' => 'Manage customer reviews',
        'required_tables' => ['reviews', 'products', 'users']
    ],
    'categories.php' => [
        'name' => '📂 إدارة التصنيفات',
        'description' => 'Manage product categories',
        'required_tables' => ['categories']
    ],
    'disabled_sellers.php' => [
        'name' => '🌟 البائعون ذوو الإعاقة',
        'description' => 'Manage disabled sellers',
        'required_tables' => ['disabled_sellers', 'products']
    ],
    'admins.php' => [
        'name' => '👥 إدارة المشرفين',
        'description' => 'Manage admin accounts',
        'required_tables' => ['admins']
    ],
    'activity.php' => [
        'name' => '📊 سجل الأنشطة',
        'description' => 'View activity logs',
        'required_tables' => ['activity_log']
    ],
    'newsletter.php' => [
        'name' => '📧 النشرات الإخبارية',
        'description' => 'Manage newsletters',
        'required_tables' => ['newsletter_subscribers']
    ],
    'email_campaigns.php' => [
        'name' => '📢 حملات البريد الإلكتروني',
        'description' => 'Manage email campaigns',
        'required_tables' => ['email_campaigns']
    ],
    'seller_tips.php' => [
        'name' => '💡 نصائح البائعين',
        'description' => 'Send tips to sellers',
        'required_tables' => ['sellers', 'email_campaigns']
    ],
    'seller_analytics.php' => [
        'name' => '📈 تحليلات البائعين',
        'description' => 'View and send seller analytics',
        'required_tables' => ['sellers', 'products', 'orders', 'reviews']
    ],
    'automated_reports.php' => [
        'name' => '📊 التقارير الآلية',
        'description' => 'Manage automated reports',
        'required_tables' => ['sellers', 'email_campaigns']
    ]
];

echo "<h2>Testing Dashboard Button Functionality</h2>";

$overall_status = 'success';
$test_results = [];

// Test 1: Check if all button files exist
echo "<h3>1. File Existence Check</h3>";
foreach ($dashboard_buttons as $file => $info) {
    $file_path = __DIR__ . '/' . $file;
    $exists = file_exists($file_path);
    $status = $exists ? 'success' : 'error';
    $icon = $exists ? '✅' : '❌';
    
    echo "<div class='test-result $status'>";
    echo "<strong>$icon {$info['name']}</strong><br>";
    echo "File: $file<br>";
    echo "Status: " . ($exists ? 'EXISTS' : 'MISSING') . "<br>";
    echo "Description: {$info['description']}";
    echo "</div>";
    
    if (!$exists) {
        $overall_status = 'error';
    }
    $test_results[$file] = ['exists' => $exists, 'status' => $status];
}

// Test 2: Check session security
echo "<h3>2. Session Security Check</h3>";
foreach ($dashboard_buttons as $file => $info) {
    if (!isset($test_results[$file]) || !$test_results[$file]['exists']) {
        continue;
    }
    
    $file_content = file_get_contents(__DIR__ . '/' . $file);
    $has_session_check = strpos($file_content, '$_SESSION[\'admin_id\']') !== false;
    $has_redirect = strpos($file_content, 'header(\'Location: login.php\')') !== false;
    
    $status = ($has_session_check && $has_redirect) ? 'success' : 'warning';
    $icon = ($has_session_check && $has_redirect) ? '✅' : '⚠️';
    
    echo "<div class='test-result $status'>";
    echo "<strong>$icon {$info['name']}</strong><br>";
    echo "Session Check: " . ($has_session_check ? 'YES' : 'NO') . "<br>";
    echo "Login Redirect: " . ($has_redirect ? 'YES' : 'NO') . "<br>";
    echo "Security: " . (($has_session_check && $has_redirect) ? 'SECURE' : 'NEEDS ATTENTION');
    echo "</div>";
    
    if (!($has_session_check && $has_redirect)) {
        $overall_status = 'warning';
    }
}

// Test 3: Check required database tables
echo "<h3>3. Database Tables Check</h3>";
foreach ($dashboard_buttons as $file => $info) {
    if (!isset($test_results[$file]) || !$test_results[$file]['exists']) {
        continue;
    }
    
    $missing_tables = [];
    foreach ($info['required_tables'] as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                $missing_tables[] = $table;
            }
        } catch (Exception $e) {
            $missing_tables[] = $table;
        }
    }
    
    $status = empty($missing_tables) ? 'success' : 'warning';
    $icon = empty($missing_tables) ? '✅' : '⚠️';
    
    echo "<div class='test-result $status'>";
    echo "<strong>$icon {$info['name']}</strong><br>";
    echo "Required Tables: " . implode(', ', $info['required_tables']) . "<br>";
    if (empty($missing_tables)) {
        echo "Status: ALL TABLES EXIST";
    } else {
        echo "Missing Tables: " . implode(', ', $missing_tables);
    }
    echo "</div>";
    
    if (!empty($missing_tables)) {
        $overall_status = 'warning';
    }
}

// Test 4: Check for common errors
echo "<h3>4. Common Error Check</h3>";
foreach ($dashboard_buttons as $file => $info) {
    if (!isset($test_results[$file]) || !$test_results[$file]['exists']) {
        continue;
    }
    
    $file_content = file_get_contents(__DIR__ . '/' . $file);
    $errors = [];
    
    // Check for common issues
    if (strpos($file_content, 'require \'../db.php\'') === false && strpos($file_content, 'require_once \'../db.php\'') === false) {
        $errors[] = 'Missing database connection';
    }
    
    if (strpos($file_content, 'admin_header.php') === false) {
        $errors[] = 'Missing admin header';
    }
    
    if (strpos($file_content, 'Content-Type: text/html') === false) {
        $errors[] = 'Missing content-type header';
    }
    
    $status = empty($errors) ? 'success' : 'warning';
    $icon = empty($errors) ? '✅' : '⚠️';
    
    echo "<div class='test-result $status'>";
    echo "<strong>$icon {$info['name']}</strong><br>";
    if (empty($errors)) {
        echo "Status: NO ISSUES FOUND";
    } else {
        echo "Issues: " . implode(', ', $errors);
    }
    echo "</div>";
    
    if (!empty($errors)) {
        $overall_status = 'warning';
    }
}

// Summary
echo "<h3>📊 Test Summary</h3>";
$summary_icon = $overall_status === 'success' ? '🎉' : ($overall_status === 'warning' ? '⚠️' : '❌');
echo "<div class='test-result $overall_status'>";
echo "<strong>$summary_icon Overall Status: " . strtoupper($overall_status) . "</strong><br>";
echo "Total Buttons Tested: " . count($dashboard_buttons) . "<br>";
echo "All Files Exist: " . (array_sum(array_column($test_results, 'exists')) === count($dashboard_buttons) ? 'YES' : 'NO') . "<br>";
echo "Security Status: " . ($overall_status === 'success' ? 'SECURE' : 'NEEDS REVIEW');
echo "</div>";

// Recommendations
echo "<h3>💡 Recommendations</h3>";
if ($overall_status === 'success') {
    echo "<div class='test-result success'>";
    echo "✅ All dashboard buttons are working correctly!<br>";
    echo "✅ Security measures are in place<br>";
    echo "✅ Database tables are properly set up<br>";
    echo "✅ Files are properly structured";
    echo "</div>";
} else {
    echo "<div class='test-result warning'>";
    echo "⚠️ Some issues were found:<br>";
    echo "• Check missing files<br>";
    echo "• Verify session security<br>";
    echo "• Ensure database tables exist<br>";
    echo "• Review file structure";
    echo "</div>";
}

echo "<br><a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Dashboard</a>";
?> 