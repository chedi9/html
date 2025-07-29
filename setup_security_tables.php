<?php
/**
 * Security Tables Setup Script
 * Creates all necessary security-related database tables
 */

require_once 'db.php';

echo "<h1>🔧 Security Tables Setup</h1>";

$tables_to_create = [
    'security_features' => "
        CREATE TABLE IF NOT EXISTS security_features (
            id INT PRIMARY KEY AUTO_INCREMENT,
            feature_name VARCHAR(100) UNIQUE NOT NULL,
            is_enabled TINYINT(1) DEFAULT 1,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES admins(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'security_logs' => "
        CREATE TABLE IF NOT EXISTS security_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_type VARCHAR(100) NOT NULL,
            event_data JSON,
            user_id INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at),
            INDEX idx_ip_address (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'blocked_ips' => "
        CREATE TABLE IF NOT EXISTS blocked_ips (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_ip_address (ip_address),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'rate_limits' => "
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            rate_key VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rate_key (rate_key),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'waf_patterns' => "
        CREATE TABLE IF NOT EXISTS waf_patterns (
            id INT PRIMARY KEY AUTO_INCREMENT,
            pattern_type VARCHAR(50) NOT NULL,
            pattern TEXT NOT NULL,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pattern_type (pattern_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'waf_logs' => "
        CREATE TABLE IF NOT EXISTS waf_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            threat_type VARCHAR(50) NOT NULL,
            pattern_matched TEXT,
            request_data JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_threat_type (threat_type),
            INDEX idx_created_at (created_at),
            INDEX idx_ip_address (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

$success_count = 0;
$error_count = 0;

foreach ($tables_to_create as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p>✅ Created table: <strong>$table_name</strong></p>";
        $success_count++;
    } catch (Exception $e) {
        echo "<p>❌ Error creating table <strong>$table_name</strong>: " . $e->getMessage() . "</p>";
        $error_count++;
    }
}

// Insert default security features
echo "<h2>📋 Setting up default security features...</h2>";

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
    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_features (feature_name, is_enabled) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)
        ");
        $stmt->execute([$feature_name, $is_enabled]);
        echo "<p>✅ Set default for: <strong>$feature_name</strong> = " . ($is_enabled ? 'ENABLED' : 'DISABLED') . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error setting default for <strong>$feature_name</strong>: " . $e->getMessage() . "</p>";
    }
}

// Insert some basic WAF patterns
echo "<h2>🛡️ Setting up basic WAF patterns...</h2>";

$basic_patterns = [
    ['sql_injection', "('|'')|(\\b(union|select|insert|update|delete|drop|create|alter)\\b)", 'Basic SQL injection patterns'],
    ['xss', "(<script|javascript:|onload=|onerror=)", 'Basic XSS patterns'],
    ['path_traversal', "(\\.\\./|\\.\\.\\\\)", 'Path traversal patterns'],
    ['command_injection', "(;|\\||`|\\$\\()", 'Command injection patterns']
];

foreach ($basic_patterns as $pattern) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO waf_patterns (pattern_type, pattern, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE pattern = VALUES(pattern)
        ");
        $stmt->execute($pattern);
        echo "<p>✅ Added WAF pattern: <strong>{$pattern[0]}</strong></p>";
    } catch (Exception $e) {
        echo "<p>❌ Error adding WAF pattern <strong>{$pattern[0]}</strong>: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>📊 Setup Summary</h2>";
echo "<ul>";
echo "<li>✅ Tables created successfully: $success_count</li>";
echo "<li>❌ Tables with errors: $error_count</li>";
echo "<li>✅ Default security features configured</li>";
echo "<li>✅ Basic WAF patterns added</li>";
echo "</ul>";

if ($error_count === 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Security Tables Setup Complete!</h3>";
    echo "<p>All security tables have been created successfully. Your security system is now ready to use.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Setup Completed with Errors</h3>";
    echo "<p>Some tables could not be created. Please check the error messages above and try again.</p>";
    echo "</div>";
}

echo "<h3>🔍 Next Steps:</h3>";
echo "<ul>";
echo "<li>1. Test the security features: <a href='test_security_features.php'>test_security_features.php</a></li>";
echo "<li>2. View the demo: <a href='demo_security_features.php'>demo_security_features.php</a></li>";
echo "<li>3. Access admin security features: <a href='admin/security_features.php'>admin/security_features.php</a></li>";
echo "<li>4. Test admin login: <a href='admin/login.php'>admin/login.php</a></li>";
echo "</ul>";
?> 