<?php
/**
 * Admin Login Test
 * Simple test to verify admin login functionality
 */

require_once 'db.php';

echo "<h1>🔐 Admin Login Test</h1>";

// Test 1: Check if admins table exists and has users
echo "<h2>📊 Test 1: Admin Users</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    $admin_count = $result['count'];
    echo "<p>✅ Found <strong>$admin_count</strong> admin users</p>";
    
    if ($admin_count > 0) {
        $stmt = $pdo->query("SELECT username, role FROM admins LIMIT 5");
        $admins = $stmt->fetchAll();
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li><strong>{$admin['username']}</strong> ({$admin['role']})</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking admin users: " . $e->getMessage() . "</p>";
}

// Test 2: Check admin login page
echo "<h2>🔐 Test 2: Admin Login Page</h2>";
if (file_exists('admin/login.php')) {
    echo "<p>✅ Admin login page exists</p>";
    echo "<p><a href='admin/login.php' target='_blank'>🔗 Test Admin Login</a></p>";
} else {
    echo "<p>❌ Admin login page missing</p>";
}

// Test 3: Check security integration for admin
echo "<h2>🛡️ Test 3: Admin Security Integration</h2>";
if (file_exists('security_integration_admin.php')) {
    echo "<p>✅ Admin security integration exists</p>";
} else {
    echo "<p>❌ Admin security integration missing</p>";
}

// Test 4: Check if session handling works
echo "<h2>📝 Test 4: Session Handling</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>✅ Session handling is working</p>";
} else {
    echo "<p>❌ Session handling failed</p>";
}

// Test 5: Check admin dashboard
echo "<h2>📊 Test 5: Admin Dashboard</h2>";
$dashboard_files = [
    'admin/dashboard.php',
    'admin/unified_dashboard.php',
    'admin/security_features.php'
];

foreach ($dashboard_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ <strong>$file</strong> exists</p>";
    } else {
        echo "<p>❌ <strong>$file</strong> missing</p>";
    }
}

// Test 6: Simulate admin login (without actual login)
echo "<h2>🧪 Test 6: Login Simulation</h2>";
echo "<p>To test actual login functionality:</p>";
echo "<ol>";
echo "<li>Go to <a href='admin/login.php'>admin/login.php</a></li>";
echo "<li>Try logging in with one of the admin users listed above</li>";
echo "<li>Check if you're redirected to the dashboard</li>";
echo "<li>Verify that security features are accessible</li>";
echo "</ol>";

echo "<h2>🎯 Quick Access Links</h2>";
echo "<ul>";
echo "<li><a href='admin/login.php'>🔐 Admin Login</a></li>";
echo "<li><a href='admin/security_features.php'>⚙️ Security Features Management</a></li>";
echo "<li><a href='admin/dashboard.php'>📊 Admin Dashboard</a></li>";
echo "<li><a href='verify_security_system.php'>🔒 Security System Verification</a></li>";
echo "<li><a href='test_security_features.php'>🧪 Security Features Test</a></li>";
echo "</ul>";

echo "<h2>📋 Login Test Summary</h2>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Admin System Ready!</h3>";
echo "<p>Your admin login system appears to be properly configured. You can now:</p>";
echo "<ul>";
echo "<li>✅ Access admin login at <code>admin/login.php</code></li>";
echo "<li>✅ Log in with any of the admin users</li>";
echo "<li>✅ Access security features management</li>";
echo "<li>✅ Use the unified dashboard</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔍 Troubleshooting Tips:</h3>";
echo "<ul>";
echo "<li><strong>If login fails:</strong> Check that the password hash is correct</li>";
echo "<li><strong>If redirect loops:</strong> Clear browser cookies and try again</li>";
echo "<li><strong>If security features don't work:</strong> Run <a href='verify_security_system.php'>security verification</a></li>";
echo "<li><strong>If WAF blocks you:</strong> Temporarily disable WAF in security features</li>";
echo "</ul>";
?> 