<?php
/**
 * Security Integration File
 * Include this at the top of all pages for comprehensive security protection
 */

// Debug mode - set to true to see what's happening
define('SECURITY_DEBUG', false);

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

// Include WAF (Web Application Firewall) only if enabled
if (isWAFEnabled()) {
    require_once 'web_application_firewall.php';
}

// Include enhanced rate limiting only if enabled
if (isRateLimitingEnabled()) {
    require_once 'enhanced_rate_limiting.php';
}

// Initialize security systems
$rateLimiter = new EnhancedRateLimiting($pdo);

// Security monitoring and logging
function logSecurityEvent($event_type, $details = [], $user_id = null) {
    global $pdo;
    
    // Only log if security logging is enabled
    if (!isSecurityLoggingEnabled()) {
        return;
    }
    
    try {
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
    } catch (Exception $e) {
        error_log("Security logging failed: " . $e->getMessage());
    }
}

// Check for suspicious activity
function checkSuspiciousActivity($user_id = null) {
    global $pdo;
    
    // Only check if fraud detection is enabled
    if (!isFraudDetectionEnabled()) {
        return ['suspicious' => false, 'reasons' => []];
    }
    
    $suspicious = false;
    $reasons = [];
    
    // Check for multiple failed login attempts
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_attempts 
            FROM security_logs 
            WHERE user_id = ? AND event_type = 'failed_login' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result['failed_attempts'] > 5) {
            $suspicious = true;
            $reasons[] = 'Multiple failed login attempts';
        }
    }
    
    // Check for unusual IP activity
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as ip_activity 
        FROM security_logs 
        WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    if ($result['ip_activity'] > 100) {
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
    
    // Only check if IP blocking is enabled
    if (!isIPBlockingEnabled()) {
        return false;
    }
    
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM blocked_ips 
            WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$ip]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("IP block check failed: " . $e->getMessage());
        return false; // Don't block if we can't check
    }
}

// Block IP
function blockIP($ip, $reason = 'Security violation', $duration = 3600) {
    global $pdo;
    
    // Only block if IP blocking is enabled
    if (!isIPBlockingEnabled()) {
        return;
    }
    
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

// Validate session
function validateSession() {
    // Only validate if session security is enabled
    if (!isSessionSecurityEnabled()) {
        if (SECURITY_DEBUG) {
            error_log("Security: Session security disabled, allowing access");
        }
        return true;
    }
    
    // Allow public access to main pages without requiring authentication
    $current_uri = $_SERVER['REQUEST_URI'] ?? '';
    $public_pages = [
        '/',
        '/index.php',
        '/store.php',
        '/product.php',
        '/search.php',
        '/faq.php',
        '/privacy.php',
        '/cookies.php',
        '/sitemap.xml',
        '/robots.txt',
        '/client/login.php',
        '/client/register.php',
        '/admin/login.php'
    ];
    
    // Check if current page is public
    foreach ($public_pages as $public_page) {
        if (strpos($current_uri, $public_page) !== false) {
            if (SECURITY_DEBUG) {
                error_log("Security: Public page access allowed: $current_uri");
            }
            return true; // Allow access to public pages
        }
    }
    
    // For protected pages, check authentication
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        if (SECURITY_DEBUG) {
            error_log("Security: No session found, redirecting to login");
        }
        return false;
    }
    
    // Check for session hijacking
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $current_ip) {
        logSecurityEvent('session_hijacking_attempt', [
            'original_ip' => $_SESSION['user_ip'],
            'current_ip' => $current_ip
        ]);
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
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
    // Only validate if CSRF protection is enabled
    if (!isCSRFProtectionEnabled()) {
        return true;
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token
function generateCSRFToken() {
    // Only generate if CSRF protection is enabled
    if (!isCSRFProtectionEnabled()) {
        return '';
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Basic input sanitization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST = array_map('sanitizeInput', $_POST);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_GET = array_map('sanitizeInput', $_GET);
}

// Check if IP is blocked
if (isIPBlocked()) {
    logSecurityEvent('blocked_ip_access', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
    http_response_code(403);
    die('Access denied. Your IP address has been blocked.');
}

// Validate session
if (!validateSession()) {
    if (SECURITY_DEBUG) {
        error_log("Security: Session validation failed, redirecting to login");
    }
    
    logSecurityEvent('invalid_session', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
    
    // Check if this is an admin page and redirect accordingly
    $current_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($current_uri, '/admin/') !== false) {
        if (SECURITY_DEBUG) {
            error_log("Security: Redirecting to admin login");
        }
        header('Location: admin/login.php');
    } elseif (strpos($current_uri, '/client/') !== false) {
        if (SECURITY_DEBUG) {
            error_log("Security: Redirecting to client login");
        }
        header('Location: client/login.php');
    } else {
        // For other pages, redirect to client login as default
        if (SECURITY_DEBUG) {
            error_log("Security: Redirecting to client login (default)");
        }
        header('Location: client/login.php');
    }
    exit();
}

// Check for suspicious activity
$suspicious_check = checkSuspiciousActivity($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null);
if ($suspicious_check['suspicious']) {
    logSecurityEvent('suspicious_activity_detected', [
        'reasons' => $suspicious_check['reasons'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null
    ]);
    
    // Block IP if suspicious activity detected
    blockIP($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'Suspicious activity: ' . implode(', ', $suspicious_check['reasons']));
}

// Rate limiting (only if enabled)
if (isRateLimitingEnabled() && isset($rateLimiter)) {
    $action = 'page_access';
    $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!$rateLimiter->checkRateLimit($action, $identifier, 100, 3600)) {
        logSecurityEvent('rate_limit_exceeded', [
            'action' => $action,
            'identifier' => $identifier,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        blockIP($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'Rate limit exceeded');
        http_response_code(429);
        die('Too many requests. Please try again later.');
    }
    
    $rateLimiter->recordAttempt($action . '_' . $identifier);
}

// WAF protection (only if enabled)
if (isWAFEnabled() && class_exists('WebApplicationFirewall')) {
    $waf = new WebApplicationFirewall($pdo);
    $threat_detected = $waf->checkRequest();
    
    if ($threat_detected) {
        logSecurityEvent('waf_threat_detected', [
            'threat_type' => $threat_detected['type'],
            'details' => $threat_detected['details'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        blockIP($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'WAF threat detected: ' . $threat_detected['type']);
        http_response_code(403);
        die('Access denied. Security threat detected.');
    }
}

// Log page access
logSecurityEvent('page_access', [
    'page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'user_id' => $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
    'user_type' => isset($_SESSION['admin_id']) ? 'admin' : 'user'
]);
?> 