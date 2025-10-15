<?php
/**
 * Security Headers Implementation
 * Comprehensive security headers for WeBuy marketplace
 */

class SecurityHeaders {
    
    /**
     * Set comprehensive security headers
     */
    public static function setSecurityHeaders() {
        // Only set security headers if enabled and headers haven't been sent
        if ((!function_exists('isSecurityHeadersEnabled') || isSecurityHeadersEnabled()) && !headers_sent()) {
            // Content Security Policy (CSP) - Prevent XSS attacks
            $csp = "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.stripe.com https://www.paypal.com https://js.stripe.com https://www.googletagmanager.com https://www.google-analytics.com; " .
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
                    "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
                    "font-src 'self' https://fonts.gstatic.com; " .
                    "img-src 'self' data: https: blob:; " .
                    "connect-src 'self' https://api.stripe.com https://api.paypal.com https://d17.tn https://flouci.com https://www.google-analytics.com https://analytics.google.com https://cdn.jsdelivr.net; " .
                    "frame-src 'self' https://checkout.stripe.com https://www.paypal.com; " .
                    "object-src 'none'; " .
                    "base-uri 'self'; " .
                    "form-action 'self'; " .
                    "frame-ancestors 'self'; " .
                    "upgrade-insecure-requests;";
            
            header("Content-Security-Policy: " . $csp);
            
            // X-Frame-Options - Prevent clickjacking
            header("X-Frame-Options: DENY");
            
            // X-Content-Type-Options - Prevent MIME type sniffing
            header("X-Content-Type-Options: nosniff");
            
            // X-XSS-Protection - Enable XSS protection
            header("X-XSS-Protection: 1; mode=block");
            
            // Strict-Transport-Security (HSTS) - Enforce HTTPS
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
            
            // Referrer-Policy - Control referrer information
            header("Referrer-Policy: strict-origin-when-cross-origin");
            
            // Permissions-Policy - Control browser features
            header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
            
            // Cache-Control for sensitive pages
            if (self::isSensitivePage()) {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Pragma: no-cache");
                header("Expires: 0");
            }
            
            // Additional security headers
            header("X-Permitted-Cross-Domain-Policies: none");
            header("X-Download-Options: noopen");
            header("X-DNS-Prefetch-Control: off");
        }
    }
    
    /**
     * Check if current page is sensitive (login, payment, admin)
     */
    private static function isSensitivePage() {
        $sensitive_pages = [
            'login.php', 'register.php', 'checkout.php', 'payment',
            'admin/', 'client/', 'wallet.php', 'security_center.php'
        ];
        
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($sensitive_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Set secure cookie parameters
     */
    public static function setSecureCookieParams() {
        // Only set session ini settings if session hasn't started yet
        if (session_status() === PHP_SESSION_NONE) {
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $httponly = true;
            $samesite = 'Strict';
            
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', $secure ? 1 : 0);
            ini_set('session.cookie_samesite', $samesite);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_lifetime', 3600); // 1 hour
            ini_set('session.gc_maxlifetime', 3600);
        }
        
        // Only try to set cookie parameters if session is active and we haven't set them yet
        if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['security_cookies_set'])) {
            try {
                $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                $httponly = true;
                $samesite = 'Strict';
                
                // Use session_set_cookie_params for active sessions
                session_set_cookie_params([
                    'lifetime' => 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => $httponly,
                    'samesite' => $samesite
                ]);
                
                // Mark that we've set the cookies to avoid duplicate calls
                $_SESSION['security_cookies_set'] = true;
            } catch (Exception $e) {
                // Log the error but don't break the application
                error_log("Security cookie parameters could not be set: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Validate and sanitize input data
     */
    public static function sanitizeInput($data, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($data), FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'string':
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($action, $limit = 5, $window = 300) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
        }
        
        // Reset if window has passed
        if (time() > $_SESSION[$key]['reset_time']) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
        }
        
        // Check if limit exceeded
        if ($_SESSION[$key]['count'] >= $limit) {
            return false;
        }
        
        // Increment count
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event_type, $details = [], $user_id = null) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, details, user_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
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
    
    /**
     * Check for suspicious activity
     */
    public static function checkSuspiciousActivity($user_id = null) {
        global $pdo;
        
        $suspicious = false;
        $reasons = [];
        
        // Check for multiple failed login attempts
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as failed_attempts FROM security_logs WHERE user_id = ? AND event_type = 'failed_login' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result['failed_attempts'] > 5) {
                $suspicious = true;
                $reasons[] = 'Multiple failed login attempts';
            }
        }
        
        // Check for unusual IP activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("SELECT COUNT(*) as ip_activity FROM security_logs WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
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
    
    /**
     * Implement IP-based blocking
     */
    public static function checkIPBlock($ip) {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->execute([$ip]);
        
        return $stmt->fetch() ? true : false;
    }
    
    /**
     * Block IP address
     */
    public static function blockIP($ip, $reason = '', $duration = 3600) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO blocked_ips (ip_address, reason, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW()) ON DUPLICATE KEY UPDATE expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)");
            $stmt->execute([$ip, $reason, $duration, $duration]);
            
            self::logSecurityEvent('ip_blocked', ['ip' => $ip, 'reason' => $reason, 'duration' => $duration]);
        } catch (Exception $e) {
            error_log("IP blocking failed: " . $e->getMessage());
        }
    }
} 