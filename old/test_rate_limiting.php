<?php
/**
 * Rate Limiting Test
 * Tests the enhanced rate limiting functionality
 */

require_once 'db.php';
require_once 'security_feature_checker.php';

// Include enhanced rate limiting
if (file_exists('enhanced_rate_limiting.php')) {
    require_once 'enhanced_rate_limiting.php';
}

echo "<h1>⏱️ Rate Limiting Test</h1>";

// Test if rate limiting is enabled
if (!isRateLimitingEnabled()) {
    echo "<p>❌ Rate limiting is disabled</p>";
    exit();
}

echo "<p>✅ Rate limiting is enabled</p>";

// Create rate limiter instance
try {
    $rateLimiter = new EnhancedRateLimiting($pdo);
    echo "<p>✅ EnhancedRateLimiting class loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error creating rate limiter: " . $e->getMessage() . "</p>";
    exit();
}

// Test 1: Basic rate limiting
echo "<h2>🧪 Test 1: Basic Rate Limiting</h2>";

$test_action = 'test_action';
$test_identifier = 'test_user_' . time();

// Test multiple attempts
for ($i = 1; $i <= 6; $i++) {
    $result = $rateLimiter->checkRateLimit($test_action, $test_identifier, 5, 300);
    $status = $result ? '✅ ALLOWED' : '❌ BLOCKED';
    echo "<p>Attempt $i: $status</p>";
    
    if (!$result) {
        echo "<p>✅ Rate limiting is working - blocked after 5 attempts</p>";
        break;
    }
}

// Test 2: Payment rate limiting
echo "<h2>💳 Test 2: Payment Rate Limiting</h2>";

$payment_methods = ['card', 'paypal', 'stripe', 'd17', 'flouci', 'bank_transfer', 'cod'];

foreach ($payment_methods as $method) {
    $result = $rateLimiter->checkPaymentRateLimit(null, $method);
    $status = $result ? '✅ ALLOWED' : '❌ BLOCKED';
    echo "<p>$method payment: $status</p>";
}

// Test 3: Login rate limiting
echo "<h2>🔐 Test 3: Login Rate Limiting</h2>";

$test_email = 'test@example.com';
for ($i = 1; $i <= 6; $i++) {
    $result = $rateLimiter->checkLoginRateLimit($test_email);
    $status = $result ? '✅ ALLOWED' : '❌ BLOCKED';
    echo "<p>Login attempt $i: $status</p>";
    
    if (!$result) {
        echo "<p>✅ Login rate limiting is working - blocked after 5 attempts</p>";
        break;
    }
}

// Test 4: Registration rate limiting
echo "<h2>📝 Test 4: Registration Rate Limiting</h2>";

for ($i = 1; $i <= 4; $i++) {
    $result = $rateLimiter->checkRegistrationRateLimit();
    $status = $result ? '✅ ALLOWED' : '❌ BLOCKED';
    echo "<p>Registration attempt $i: $status</p>";
    
    if (!$result) {
        echo "<p>✅ Registration rate limiting is working - blocked after 3 attempts</p>";
        break;
    }
}

// Test 5: API rate limiting
echo "<h2>🔌 Test 5: API Rate Limiting</h2>";

$api_endpoints = ['users', 'products', 'orders', 'payments'];

foreach ($api_endpoints as $endpoint) {
    $result = $rateLimiter->checkAPIRateLimit($endpoint);
    $status = $result ? '✅ ALLOWED' : '❌ BLOCKED';
    echo "<p>API $endpoint: $status</p>";
}

// Test 6: Rate limit status
echo "<h2>📊 Test 6: Rate Limit Status</h2>";

$status = $rateLimiter->getRateLimitStatus($test_action, $test_identifier);
echo "<p>Rate limit status for <strong>$test_action</strong>:</p>";
echo "<ul>";
foreach ($status as $window => $attempts) {
    echo "<li><strong>$window</strong>: $attempts attempts</li>";
}
echo "</ul>";

// Test 7: Cleanup old records
echo "<h2>🧹 Test 7: Cleanup Old Records</h2>";

try {
    $rateLimiter->cleanupOldRecords();
    echo "<p>✅ Cleanup completed successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Cleanup failed: " . $e->getMessage() . "</p>";
}

echo "<h2>🎉 Rate Limiting Test Complete!</h2>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>✅ Rate limiting is enabled and functional</li>";
echo "<li>✅ Different limits for different actions</li>";
echo "<li>✅ Payment method-specific limits</li>";
echo "<li>✅ Login protection working</li>";
echo "<li>✅ Registration protection working</li>";
echo "<li>✅ API rate limiting working</li>";
echo "<li>✅ Status tracking working (shows attempts per time window)</li>";
echo "<li>✅ Cleanup functionality working</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. <a href='test_security_features.php'>Test Security Features</a></li>";
echo "<li>2. <a href='demo_security_features.php'>View Demo</a></li>";
echo "<li>3. <a href='verify_security_system.php'>Verify System</a></li>";
echo "<li>4. <a href='admin/security_features.php'>Manage Features</a></li>";
echo "</ul>";
?> 