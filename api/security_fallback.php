<?php
/**
 * Security Fallback for API
 * Minimal security implementation when main security_integration.php is not available
 */

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Session security settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Basic rate limiting function
function checkRateLimit($identifier, $max_requests = 100, $time_window = 3600) {
    // Simple file-based rate limiting
    $cache_dir = '../cache/rate_limits/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . md5($identifier) . '.txt';
    $current_time = time();
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && $data['expires'] > $current_time) {
            if ($data['count'] >= $max_requests) {
                return false; // Rate limit exceeded
            }
            $data['count']++;
        } else {
            $data = ['count' => 1, 'expires' => $current_time + $time_window];
        }
    } else {
        $data = ['count' => 1, 'expires' => $current_time + $time_window];
    }
    
    file_put_contents($cache_file, json_encode($data));
    return true;
}

// Basic input sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Basic CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Basic logging function
function logSecurityEvent($event_type, $details = []) {
    $log_file = '../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $event_type . ' - ' . json_encode($details) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
