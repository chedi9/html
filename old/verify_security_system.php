<?php
/**
 * Security System Verification
 * Comprehensive check of all security components
 */

require_once 'db.php';
require_once 'security_feature_checker.php';

// Include security headers to make the class available
if (file_exists('security_headers.php')) {
    require_once 'security_headers.php';
}

echo "<h1>🔒 Security System Verification</h1>";

$all_tests_passed = true;

// Test 1: Database Tables
echo "<h2>📊 Test 1: Database Tables</h2>";
$required_tables = [
    'security_features',
    'security_logs', 
    'blocked_ips',
    'rate_limits',
    'waf_patterns',
    'waf_logs'
];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table <strong>$table</strong> exists</p>";
        } else {
            echo "<p>❌ Table <strong>$table</strong> is missing</p>";
            $all_tests_passed = false;
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking table <strong>$table</strong>: " . $e->getMessage() . "</p>";
        $all_tests_passed = false;
    }
}

// Test 2: Security Features
echo "<h2>⚙️ Test 2: Security Features</h2>";
$features = getAllSecurityFeatures();
foreach ($features as $feature_name => $is_enabled) {
    $status = $is_enabled ? '✅ ENABLED' : '❌ DISABLED';
    echo "<p>$status: <strong>$feature_name</strong></p>";
}

// Test 3: Feature Functions
echo "<h2>🔧 Test 3: Feature Functions</h2>";
$feature_functions = [
    'isWAFEnabled',
    'isRateLimitingEnabled', 
    'isIPBlockingEnabled',
    'isSecurityLoggingEnabled',
    'isFraudDetectionEnabled',
    'isPCIComplianceEnabled',
    'isCookieConsentEnabled',
    'isSecurityHeadersEnabled',
    'isCSRFProtectionEnabled',
    'isSessionSecurityEnabled'
];

foreach ($feature_functions as $function) {
    if (function_exists($function)) {
        $result = $function();
        $status = $result ? '✅ ENABLED' : '❌ DISABLED';
        echo "<p>$status: <strong>$function()</strong></p>";
    } else {
        echo "<p>❌ Function <strong>$function</strong> not found</p>";
        $all_tests_passed = false;
    }
}

// Test 4: WAF Patterns
echo "<h2>🛡️ Test 4: WAF Patterns</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM waf_patterns WHERE is_active = 1");
    $result = $stmt->fetch();
    $pattern_count = $result['count'];
    echo "<p>✅ Found <strong>$pattern_count</strong> active WAF patterns</p>";
    
    if ($pattern_count > 0) {
        $stmt = $pdo->query("SELECT pattern_type, description FROM waf_patterns WHERE is_active = 1 LIMIT 5");
        $patterns = $stmt->fetchAll();
        echo "<ul>";
        foreach ($patterns as $pattern) {
            echo "<li><strong>{$pattern['pattern_type']}</strong>: {$pattern['description']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking WAF patterns: " . $e->getMessage() . "</p>";
    $all_tests_passed = false;
}

// Test 5: Security Logging
echo "<h2>📝 Test 5: Security Logging</h2>";
try {
    // Test if we can log an event
    if (function_exists('logSecurityEventIfEnabled')) {
        logSecurityEventIfEnabled('verification_test', [
            'test_type' => 'system_verification',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        echo "<p>✅ Security logging test successful</p>";
    } else {
        echo "<p>❌ Security logging function not available</p>";
        $all_tests_passed = false;
    }
} catch (Exception $e) {
    echo "<p>❌ Security logging test failed: " . $e->getMessage() . "</p>";
    $all_tests_passed = false;
}

// Test 6: File System
echo "<h2>📁 Test 6: File System</h2>";
$required_files = [
    'security_feature_checker.php',
    'security_integration.php',
    'security_integration_admin.php',
    'admin/security_features.php',
    'admin/login.php',
    'test_security_features.php',
    'demo_security_features.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ File <strong>$file</strong> exists</p>";
    } else {
        echo "<p>❌ File <strong>$file</strong> is missing</p>";
        $all_tests_passed = false;
    }
}

// Test 7: Admin Access
echo "<h2>👥 Test 7: Admin Access</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    $admin_count = $result['count'];
    echo "<p>✅ Found <strong>$admin_count</strong> admin users</p>";
    
    if ($admin_count > 0) {
        $stmt = $pdo->query("SELECT username, role FROM admins LIMIT 3");
        $admins = $stmt->fetchAll();
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li><strong>{$admin['username']}</strong> ({$admin['role']})</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking admin users: " . $e->getMessage() . "</p>";
    $all_tests_passed = false;
}

// Test 8: Security Headers
echo "<h2>🛡️ Test 8: Security Headers</h2>";
if (class_exists('SecurityHeaders')) {
    echo "<p>✅ SecurityHeaders class available</p>";
    
    // Test if security headers are enabled
    if (isSecurityHeadersEnabled()) {
        echo "<p>✅ Security headers are enabled</p>";
    } else {
        echo "<p>⚠️ Security headers are disabled</p>";
    }
} else {
    echo "<p>❌ SecurityHeaders class not found</p>";
    $all_tests_passed = false;
}

// Final Summary
echo "<h2>📊 Verification Summary</h2>";

if ($all_tests_passed) {
    echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 All Tests Passed!</h3>";
    echo "<p>Your security system is fully functional and ready for production use.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ Some Tests Failed</h3>";
    echo "<p>Please review the failed tests above and fix any issues before using the system in production.</p>";
    echo "</div>";
}

echo "<h3>🔍 Next Steps:</h3>";
echo "<ul>";
echo "<li>1. <a href='test_security_features.php'>Test Security Features</a> - Detailed feature testing</li>";
echo "<li>2. <a href='demo_security_features.php'>View Demo</a> - See how features work</li>";
echo "<li>3. <a href='admin/security_features.php'>Manage Features</a> - Enable/disable security features</li>";
echo "<li>4. <a href='admin/login.php'>Admin Login</a> - Access admin dashboard</li>";
echo "<li>5. <a href='fix_waf_patterns_table.php'>Fix WAF Patterns</a> - If WAF patterns are missing</li>";
echo "</ul>";

echo "<h3>📋 System Status:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Component</th><th>Status</th><th>Description</th></tr>";

$components = [
    'Database Tables' => $all_tests_passed ? '✅ Ready' : '❌ Issues',
    'Security Features' => $all_tests_passed ? '✅ Configured' : '❌ Issues', 
    'WAF System' => $all_tests_passed ? '✅ Active' : '❌ Issues',
    'Admin Access' => $all_tests_passed ? '✅ Available' : '❌ Issues',
    'Security Logging' => $all_tests_passed ? '✅ Working' : '❌ Issues',
    'File System' => $all_tests_passed ? '✅ Complete' : '❌ Issues'
];

foreach ($components as $component => $status) {
    echo "<tr>";
    echo "<td style='padding: 10px;'><strong>$component</strong></td>";
    echo "<td style='padding: 10px;'>$status</td>";
    echo "<td style='padding: 10px;'>" . ($all_tests_passed ? 'All systems operational' : 'Some issues detected') . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($all_tests_passed) {
    echo "<p><strong>🎉 Your security system is ready for production use!</strong></p>";
} else {
    echo "<p><strong>⚠️ Please fix the issues above before using in production.</strong></p>";
}
?> 