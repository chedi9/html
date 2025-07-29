<?php
/**
 * Security Features Demonstration
 * Shows how security features work when enabled/disabled
 */

require_once 'db.php';
require_once 'security_feature_checker.php';

// Include security integration
if (file_exists('security_integration.php')) {
    require_once 'security_integration.php';
}

echo "<h1>🔒 Security Features Demonstration</h1>";
echo "<p>This page demonstrates how security features work when enabled or disabled.</p>";

// Get current feature status
$features = getAllSecurityFeatures();

echo "<h2>📊 Current Security Features Status</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Feature</th><th>Status</th><th>Description</th></tr>";

$feature_descriptions = [
    'waf_enabled' => 'Web Application Firewall - Protects against SQL injection, XSS, and other attacks',
    'rate_limiting_enabled' => 'Rate Limiting - Prevents brute force attacks and API abuse',
    'ip_blocking_enabled' => 'IP Blocking - Automatically blocks malicious IP addresses',
    'security_logging_enabled' => 'Security Logging - Logs all security events for monitoring',
    'fraud_detection_enabled' => 'Fraud Detection - Detects suspicious activities and transactions',
    'pci_compliance_enabled' => 'PCI Compliance - Ensures secure payment processing standards',
    'cookie_consent_enabled' => 'Cookie Consent - GDPR-compliant cookie consent banner',
    'security_headers_enabled' => 'Security Headers - Sets HTTP security headers (CSP, HSTS, etc.)',
    'csrf_protection_enabled' => 'CSRF Protection - Protects against Cross-Site Request Forgery',
    'session_security_enabled' => 'Session Security - Secure session management and validation'
];

foreach ($features as $feature_name => $is_enabled) {
    $status = $is_enabled ? '✅ ENABLED' : '❌ DISABLED';
    $status_color = $is_enabled ? '#d4edda' : '#f8d7da';
    $description = $feature_descriptions[$feature_name] ?? 'No description available';
    
    echo "<tr>";
    echo "<td style='padding: 10px;'><strong>$feature_name</strong></td>";
    echo "<td style='padding: 10px; background: $status_color;'>$status</td>";
    echo "<td style='padding: 10px;'>$description</td>";
    echo "</tr>";
}
echo "</table>";

// Demonstrate feature functionality
echo "<h2>🧪 Feature Functionality Tests</h2>";

// Test 1: WAF Status
echo "<h3>1. Web Application Firewall (WAF)</h3>";
if (isWAFEnabled()) {
    echo "<p>✅ WAF is <strong>ENABLED</strong> - Will protect against:</p>";
    echo "<ul>";
    echo "<li>SQL Injection attacks</li>";
    echo "<li>Cross-Site Scripting (XSS)</li>";
    echo "<li>Command injection</li>";
    echo "<li>Path traversal attacks</li>";
    echo "<li>File upload vulnerabilities</li>";
    echo "</ul>";
} else {
    echo "<p>❌ WAF is <strong>DISABLED</strong> - No protection against web attacks</p>";
}

// Test 2: Rate Limiting
echo "<h3>2. Rate Limiting</h3>";
if (isRateLimitingEnabled()) {
    echo "<p>✅ Rate Limiting is <strong>ENABLED</strong> - Will prevent:</p>";
    echo "<ul>";
    echo "<li>Brute force login attempts</li>";
    echo "<li>API abuse</li>";
    echo "<li>DDoS attacks</li>";
    echo "<li>Spam submissions</li>";
    echo "</ul>";
} else {
    echo "<p>❌ Rate Limiting is <strong>DISABLED</strong> - No protection against abuse</p>";
}

// Test 3: Security Logging
echo "<h3>3. Security Logging</h3>";
if (isSecurityLoggingEnabled()) {
    echo "<p>✅ Security Logging is <strong>ENABLED</strong> - Will log:</p>";
    echo "<ul>";
    echo "<li>Login attempts (successful and failed)</li>";
    echo "<li>Security violations</li>";
    echo "<li>IP blocking events</li>";
    echo "<li>Suspicious activities</li>";
    echo "<li>Feature changes by administrators</li>";
    echo "</ul>";
} else {
    echo "<p>❌ Security Logging is <strong>DISABLED</strong> - No security event logging</p>";
}

// Test 4: IP Blocking
echo "<h3>4. IP Blocking</h3>";
if (isIPBlockingEnabled()) {
    echo "<p>✅ IP Blocking is <strong>ENABLED</strong> - Will automatically block:</p>";
    echo "<ul>";
    echo "<li>IPs with suspicious activity</li>";
    echo "<li>IPs that trigger WAF rules</li>";
    echo "<li>IPs that exceed rate limits</li>";
    echo "<li>Manually blocked IPs by administrators</li>";
    echo "</ul>";
} else {
    echo "<p>❌ IP Blocking is <strong>DISABLED</strong> - No automatic IP blocking</p>";
}

// Test 5: Security Headers
echo "<h3>5. Security Headers</h3>";
if (isSecurityHeadersEnabled()) {
    echo "<p>✅ Security Headers are <strong>ENABLED</strong> - Will set:</p>";
    echo "<ul>";
    echo "<li>Content Security Policy (CSP)</li>";
    echo "<li>Strict Transport Security (HSTS)</li>";
    echo "<li>X-Frame-Options (clickjacking protection)</li>";
    echo "<li>X-Content-Type-Options (MIME sniffing protection)</li>";
    echo "<li>X-XSS-Protection (XSS protection)</li>";
    echo "<li>Referrer Policy</li>";
    echo "<li>Permissions Policy</li>";
    echo "</ul>";
} else {
    echo "<p>❌ Security Headers are <strong>DISABLED</strong> - No HTTP security headers</p>";
}

// Demonstrate conditional functions
echo "<h2>🔧 Conditional Function Tests</h2>";

// Test logging
echo "<h3>Security Event Logging Test</h3>";
try {
    logSecurityEventIfEnabled('demo_test', [
        'test_type' => 'demonstration',
        'timestamp' => date('Y-m-d H:i:s'),
        'feature' => 'security_features_demo'
    ]);
    echo "<p>✅ Security event logged successfully (if logging is enabled)</p>";
} catch (Exception $e) {
    echo "<p>⚠️ Logging error: " . $e->getMessage() . "</p>";
}

// Test IP blocking
echo "<h3>IP Blocking Test</h3>";
try {
    $demo_ip = '192.168.1.100';
    $is_blocked = isIPBlockedIfEnabled($demo_ip);
    echo "<p>✅ IP Blocking check completed: " . ($is_blocked ? 'IP is BLOCKED' : 'IP is NOT BLOCKED') . "</p>";
} catch (Exception $e) {
    echo "<p>⚠️ IP blocking error: " . $e->getMessage() . "</p>";
}

// Show how to manage features
echo "<h2>🎛️ How to Manage Security Features</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>For Security Officers and Superadmins:</h3>";
echo "<ol>";
echo "<li><strong>Access the Management Interface:</strong> Go to <code>admin/security_features.php</code></li>";
echo "<li><strong>Toggle Features:</strong> Use the toggle switches to enable/disable features</li>";
echo "<li><strong>Bulk Actions:</strong> Use 'Enable All', 'Disable All', or 'Critical Only' buttons</li>";
echo "<li><strong>Save Changes:</strong> Click 'Save Changes' to apply your settings</li>";
echo "<li><strong>Monitor Effects:</strong> Check this demo page to see the changes</li>";
echo "</ol>";

echo "<h3>Feature Management Tips:</h3>";
echo "<ul>";
echo "<li><strong>Critical Features:</strong> WAF, Rate Limiting, Security Headers should usually stay enabled</li>";
echo "<li><strong>Optional Features:</strong> Cookie Consent, PCI Compliance can be disabled if not needed</li>";
echo "<li><strong>Logging:</strong> Keep Security Logging enabled for audit purposes</li>";
echo "<li><strong>Testing:</strong> Disable features temporarily for testing, then re-enable</li>";
echo "</ul>";
echo "</div>";

echo "<h2>📈 Security Impact Analysis</h2>";
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;'>";
echo "<h3>⚠️ Security Recommendations:</h3>";
echo "<ul>";
echo "<li><strong>Production Environment:</strong> Keep all critical security features enabled</li>";
echo "<li><strong>Development Environment:</strong> You can disable some features for testing</li>";
echo "<li><strong>Monitoring:</strong> Always keep security logging enabled</li>";
echo "<li><strong>Regular Reviews:</strong> Review security feature settings monthly</li>";
echo "<li><strong>Incident Response:</strong> Disable features only temporarily during incidents</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎉 Demonstration Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. Visit <a href='admin/security_features.php'>Security Features Management</a> to change settings</li>";
echo "<li>2. Test the website functionality with different feature combinations</li>";
echo "<li>3. Monitor security logs to see the impact of your changes</li>";
echo "<li>4. Set up alerts for when critical features are disabled</li>";
echo "</ul>";

echo "<p><strong>Remember:</strong> Security features are designed to protect your website and users. Only disable features when absolutely necessary and always re-enable them promptly.</p>";
?> 