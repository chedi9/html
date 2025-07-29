<?php
/**
 * Test Security Features System
 * Verifies that security features can be enabled/disabled correctly
 */

require_once 'db.php';
require_once 'security_feature_checker.php';

// Include security integration to get the logSecurityEvent function
if (file_exists('security_integration.php')) {
    require_once 'security_integration.php';
}

echo "<h1>🔒 Security Features Test</h1>";

// Test 1: Check if security_features table exists
echo "<h2>Test 1: Database Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'security_features'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Security features table exists</p>";
    } else {
        echo "<p>❌ Security features table does not exist</p>";
        echo "<p>Creating table...</p>";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_features (
                id INT PRIMARY KEY AUTO_INCREMENT,
                feature_name VARCHAR(100) UNIQUE NOT NULL,
                is_enabled TINYINT(1) DEFAULT 1,
                updated_by INT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (updated_by) REFERENCES admins(id)
            )
        ");
        
        // Insert default features
        $default_features = [
            'waf_enabled' => 1,
            'rate_limiting_enabled' => 1,
            'ip_blocking_enabled' => 1,
            'security_logging_enabled' => 1,
            'fraud_detection_enabled' => 1,
            'pci_compliance_enabled' => 1,
            'cookie_consent_enabled' => 1,
            'security_headers_enabled' => 1,
            'csrf_protection_enabled' => 1,
            'session_security_enabled' => 1
        ];
        
        foreach ($default_features as $feature_name => $is_enabled) {
            $stmt = $pdo->prepare("
                INSERT INTO security_features (feature_name, is_enabled) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)
            ");
            $stmt->execute([$feature_name, $is_enabled]);
        }
        
        echo "<p>✅ Security features table created and populated</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

// Test 2: Check feature functions
echo "<h2>Test 2: Feature Functions</h2>";
$features_to_test = [
    'waf_enabled' => 'isWAFEnabled',
    'rate_limiting_enabled' => 'isRateLimitingEnabled',
    'ip_blocking_enabled' => 'isIPBlockingEnabled',
    'security_logging_enabled' => 'isSecurityLoggingEnabled',
    'fraud_detection_enabled' => 'isFraudDetectionEnabled',
    'pci_compliance_enabled' => 'isPCIComplianceEnabled',
    'cookie_consent_enabled' => 'isCookieConsentEnabled',
    'security_headers_enabled' => 'isSecurityHeadersEnabled',
    'csrf_protection_enabled' => 'isCSRFProtectionEnabled',
    'session_security_enabled' => 'isSessionSecurityEnabled'
];

foreach ($features_to_test as $feature_name => $function_name) {
    if (function_exists($function_name)) {
        $result = $function_name();
        echo "<p>✅ $function_name(): " . ($result ? 'ENABLED' : 'DISABLED') . "</p>";
    } else {
        echo "<p>❌ Function $function_name() does not exist</p>";
    }
}

// Test 3: Test feature toggling
echo "<h2>Test 3: Feature Toggling</h2>";
try {
    // Disable WAF temporarily
    $stmt = $pdo->prepare("UPDATE security_features SET is_enabled = 0 WHERE feature_name = 'waf_enabled'");
    $stmt->execute();
    
    echo "<p>🔧 Temporarily disabled WAF...</p>";
    echo "<p>isWAFEnabled(): " . (isWAFEnabled() ? 'ENABLED' : 'DISABLED') . "</p>";
    
    // Re-enable WAF
    $stmt = $pdo->prepare("UPDATE security_features SET is_enabled = 1 WHERE feature_name = 'waf_enabled'");
    $stmt->execute();
    
    echo "<p>🔧 Re-enabled WAF...</p>";
    echo "<p>isWAFEnabled(): " . (isWAFEnabled() ? 'ENABLED' : 'DISABLED') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error toggling features: " . $e->getMessage() . "</p>";
}

// Test 4: Get all features
echo "<h2>Test 4: All Features Status</h2>";
$all_features = getAllSecurityFeatures();
if (!empty($all_features)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Feature</th><th>Status</th></tr>";
    foreach ($all_features as $feature_name => $is_enabled) {
        $status = $is_enabled ? '✅ ENABLED' : '❌ DISABLED';
        echo "<tr><td>$feature_name</td><td>$status</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No features found in database</p>";
}

// Test 5: Test conditional functions
echo "<h2>Test 5: Conditional Functions</h2>";

// Test logging function
try {
    logSecurityEventIfEnabled('test_event', ['test' => 'data']);
    echo "<p>✅ logSecurityEventIfEnabled() called (will only log if enabled)</p>";
} catch (Exception $e) {
    echo "<p>⚠️ logSecurityEventIfEnabled() error: " . $e->getMessage() . "</p>";
}

// Test IP blocking function
try {
    $test_ip = '192.168.1.100';
    $is_blocked = isIPBlockedIfEnabled($test_ip);
    echo "<p>✅ isIPBlockedIfEnabled(): " . ($is_blocked ? 'BLOCKED' : 'NOT BLOCKED') . "</p>";
} catch (Exception $e) {
    echo "<p>⚠️ isIPBlockedIfEnabled() error: " . $e->getMessage() . "</p>";
}

echo "<h2>🎉 Security Features Test Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. Go to admin/security_features.php to manage features</li>";
echo "<li>2. Test enabling/disabling features</li>";
echo "<li>3. Verify that disabled features are not active</li>";
echo "<li>4. Check that security logging respects the settings</li>";
echo "</ul>";
?> 