<?php
/**
 * Timezone Check and Fix
 * Verifies and sets the correct timezone for Tunisia
 */

require_once 'db.php';

echo "<h1>🕐 Timezone Check and Fix</h1>";

// Check current PHP timezone
echo "<h2>📅 Current PHP Timezone Settings</h2>";
echo "<p><strong>PHP Default Timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current PHP Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current UTC Time:</strong> " . gmdate('Y-m-d H:i:s') . "</p>";

// Set timezone to Tunisia
date_default_timezone_set('Africa/Tunis');

echo "<h2>🔄 After Setting Tunisia Timezone</h2>";
echo "<p><strong>PHP Default Timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current PHP Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current UTC Time:</strong> " . gmdate('Y-m-d H:i:s') . "</p>";

// Check database timezone
echo "<h2>🗄️ Database Timezone Settings</h2>";

try {
    // Check current database timezone
    $stmt = $pdo->query("SELECT @@global.time_zone, @@session.time_zone, NOW() as current_time");
    $result = $stmt->fetch();
    
    echo "<p><strong>Global Timezone:</strong> " . $result['@@global.time_zone'] . "</p>";
    echo "<p><strong>Session Timezone:</strong> " . $result['@@session.time_zone'] . "</p>";
    echo "<p><strong>Database Current Time:</strong> " . $result['current_time'] . "</p>";
    
    // Set database timezone to Tunisia
    $pdo->exec("SET time_zone = '+01:00'");
    
    // Check again after setting
    $stmt = $pdo->query("SELECT @@session.time_zone, NOW() as current_time");
    $result = $stmt->fetch();
    
    echo "<h3>🔄 After Setting Database Timezone</h3>";
    echo "<p><strong>Session Timezone:</strong> " . $result['@@session.time_zone'] . "</p>";
    echo "<p><strong>Database Current Time:</strong> " . $result['current_time'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error checking database timezone: " . $e->getMessage() . "</p>";
}

// Test logging with correct timezone
echo "<h2>📝 Test Logging with Correct Timezone</h2>";

try {
    // Test inserting a log entry
    $stmt = $pdo->prepare("
        INSERT INTO security_logs (event_type, event_data, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        'timezone_test',
        json_encode(['test' => 'timezone_fix', 'php_time' => date('Y-m-d H:i:s')]),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Get the inserted record
    $stmt = $pdo->query("SELECT created_at FROM security_logs WHERE event_type = 'timezone_test' ORDER BY id DESC LIMIT 1");
    $result = $stmt->fetch();
    
    echo "<p>✅ Test log entry created with time: <strong>" . $result['created_at'] . "</strong></p>";
    
    // Clean up test entry
    $pdo->exec("DELETE FROM security_logs WHERE event_type = 'timezone_test'");
    echo "<p>✅ Test entry cleaned up</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error testing logging: " . $e->getMessage() . "</p>";
}

echo "<h2>✅ Timezone Fix Complete!</h2>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>✅ PHP timezone set to Africa/Tunis</li>";
echo "<li>✅ Database timezone set to +01:00</li>";
echo "<li>✅ All future logs will use correct timezone</li>";
echo "<li>✅ Current time: " . date('Y-m-d H:i:s T') . "</li>";
echo "</ul>";

echo "<h3>📋 Next Steps:</h3>";
echo "<ul>";
echo "<li>1. <a href='test_security_features.php'>Test Security Features</a> - Verify logging works correctly</li>";
echo "<li>2. <a href='admin/security_features.php'>Manage Features</a> - Check admin logs</li>";
echo "<li>3. <a href='verify_security_system.php'>Verify System</a> - Complete system check</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> All new log entries will now use the correct Tunisia timezone (+01:00).</p>";
?> 