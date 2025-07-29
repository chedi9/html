<?php
/**
 * Security Integration for Admin Pages - Less Aggressive
 * Modified version to prevent redirect loops in admin area
 */

// Include security headers first (before session start)
require_once 'security_headers.php';

// Set secure cookie parameters before starting session
SecurityHeaders::setSecureCookieParams();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Include security feature checker
require_once 'security_feature_checker.php';

// Include enhanced rate limiting only if enabled
if (isRateLimitingEnabled() && file_exists('enhanced_rate_limiting.php')) {
    require_once 'enhanced_rate_limiting.php';
    $rateLimiter = new EnhancedRateLimiting($pdo);
} else {
    $rateLimiter = null;
}

// Security monitoring and logging
function logSecurityEvent($event_type, $details = [], $user_id = null) {
    global $pdo;
    
    try {
        // Check if security_logs table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'security_logs'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO security_logs (event_type, event_data, user_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $event_type,
                json_encode($details),
                $user_id,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } else {
            // Table doesn't exist, just log to error log
            error_log("Security Event [$event_type]: " . json_encode($details));
        }
    } catch (Exception $e) {
        error_log("Security logging failed: " . $e->getMessage());
    }
}

// Check for suspicious activity (less aggressive for admin)
function checkSuspiciousActivity($user_id = null) {
    global $pdo;
    
    $suspicious = false;
    $reasons = [];
    
    // Check for multiple failed login attempts (more lenient for admin)
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_attempts 
            FROM security_logs 
            WHERE user_id = ? AND event_type = 'failed_login' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result['failed_attempts'] > 10) { // Increased from 5 to 10
            $suspicious = true;
            $reasons[] = 'Multiple failed login attempts';
        }
    }
    
    // Check for unusual IP activity (more lenient for admin)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as ip_activity 
        FROM security_logs 
        WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    if ($result['ip_activity'] > 200) { // Increased from 100 to 200
        $suspicious = true;
        $reasons[] = 'Unusual IP activity';
    }
    
    return [
        'suspicious' => $suspicious,
        'reasons' => $reasons
    ];
}

// Check if IP is blocked
function isIPBlocked($ip = null) {
    global $pdo;
    
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    try {
        // Check if blocked_ips table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'blocked_ips'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT * FROM blocked_ips 
                WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$ip]);
            return $stmt->fetch() !== false;
        } else {
            return false; // Table doesn't exist, no IPs blocked
        }
    } catch (Exception $e) {
        error_log("IP block check failed: " . $e->getMessage());
        return false; // Don't block if we can't check
    }
}

// Block IP (less aggressive)
function blockIP($ip, $reason = 'Security violation', $duration = 3600) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO blocked_ips (ip_address, reason, created_at, expires_at) 
            VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
            ON DUPLICATE KEY UPDATE 
            reason = VALUES(reason), 
            expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$ip, $reason, $duration]);
        
        logSecurityEvent('ip_blocked', [
            'ip' => $ip,
            'reason' => $reason,
            'duration' => $duration
        ]);
    } catch (Exception $e) {
        error_log("IP blocking failed: " . $e->getMessage());
    }
}

// Validate session (simplified for admin)
function validateSession() {
    // Only check if user is logged in, don't do aggressive checks
    if (isset($_SESSION['admin_id'])) {
        return true;
    }
    return false;
}

// Sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// CSRF token validation
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Basic input sanitization for admin pages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST = array_map('sanitizeInput', $_POST);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_GET = array_map('sanitizeInput', $_GET);
}

// Log admin access (but don't block)
if (isset($_SESSION['admin_id'])) {
    logSecurityEvent('admin_page_access', [
        'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'admin_id' => $_SESSION['admin_id'],
        'role' => $_SESSION['admin_role'] ?? 'unknown'
    ]);
}

// Only do basic rate limiting for admin pages (no aggressive blocking)
if (isset($_SESSION['admin_id'])) {
    // Admin is logged in, allow access
    return;
} else {
    // Check basic rate limiting for login attempts only
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $current_attempts = 0;
    
    try {
        // Check if rate_limits table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'rate_limits'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM rate_limits 
                WHERE rate_key LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute(["login_$ip%"]);
            $result = $stmt->fetch();
            $current_attempts = $result['attempts'];
        } else {
            $current_attempts = 0; // Table doesn't exist, no attempts recorded
        }
    } catch (Exception $e) {
        // If rate limiting fails, don't block access
        error_log("Rate limit check failed: " . $e->getMessage());
        $current_attempts = 0;
    }
    
    // Only block if there are too many login attempts
    if ($current_attempts > 20) { // Very high threshold for admin
        logSecurityEvent('admin_rate_limit_exceeded', [
            'ip' => $ip,
            'attempts' => $current_attempts
        ]);
        
        // Don't redirect, just show error
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'login.php') !== false) {
            // This is a login page, let it handle the error
            return;
        }
    }
}
?> 