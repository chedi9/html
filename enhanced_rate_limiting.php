<?php
/**
 * Enhanced Rate Limiting System
 * Comprehensive rate limiting with payment attempt limits and time-based restrictions
 */

class EnhancedRateLimiting {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check rate limit for a specific action
     */
    public function checkRateLimit($action, $identifier = null, $max_attempts = 5, $time_window = 300) {
        if (!$identifier) {
            $identifier = $this->getIdentifier();
        }
        
        $key = $this->getRateLimitKey($action, $identifier);
        
        // Get current attempts
        $current_attempts = $this->getCurrentAttempts($key, $time_window);
        
        if ($current_attempts >= $max_attempts) {
            $this->logRateLimitExceeded($action, $identifier, $current_attempts);
            return false;
        }
        
        // Record this attempt
        $this->recordAttempt($key);
        
        return true;
    }
    
    /**
     * Check payment rate limit
     */
    public function checkPaymentRateLimit($user_id = null, $payment_method = null) {
        $identifier = $user_id ? "user_{$user_id}" : $this->getIdentifier();
        
        // Different limits for different payment methods
        $limits = [
            'card' => ['attempts' => 3, 'window' => 300], // 3 attempts per 5 minutes
            'paypal' => ['attempts' => 5, 'window' => 600], // 5 attempts per 10 minutes
            'stripe' => ['attempts' => 5, 'window' => 600], // 5 attempts per 10 minutes
            'd17' => ['attempts' => 3, 'window' => 300], // 3 attempts per 5 minutes
            'flouci' => ['attempts' => 3, 'window' => 300], // 3 attempts per 5 minutes
            'bank_transfer' => ['attempts' => 2, 'window' => 1800], // 2 attempts per 30 minutes
            'cod' => ['attempts' => 10, 'window' => 3600], // 10 attempts per hour
        ];
        
        $method = $payment_method ?: 'card';
        $limit = $limits[$method] ?? $limits['card'];
        
        return $this->checkRateLimit("payment_{$method}", $identifier, $limit['attempts'], $limit['window']);
    }
    
    /**
     * Check login rate limit
     */
    public function checkLoginRateLimit($email = null) {
        $identifier = $email ?: $this->getIdentifier();
        
        // Stricter limits for login attempts
        return $this->checkRateLimit('login', $identifier, 5, 900); // 5 attempts per 15 minutes
    }
    
    /**
     * Check registration rate limit
     */
    public function checkRegistrationRateLimit() {
        $identifier = $this->getIdentifier();
        
        // Limit registration attempts
        return $this->checkRateLimit('registration', $identifier, 3, 3600); // 3 attempts per hour
    }
    
    /**
     * Check password reset rate limit
     */
    public function checkPasswordResetRateLimit($email = null) {
        $identifier = $email ?: $this->getIdentifier();
        
        // Limit password reset attempts
        return $this->checkRateLimit('password_reset', $identifier, 3, 3600); // 3 attempts per hour
    }
    
    /**
     * Check API rate limit
     */
    public function checkAPIRateLimit($endpoint = null) {
        $identifier = $this->getIdentifier();
        $action = $endpoint ? "api_{$endpoint}" : 'api';
        
        return $this->checkRateLimit($action, $identifier, 100, 3600); // 100 requests per hour
    }
    
    /**
     * Get current attempts for a rate limit key
     */
    private function getCurrentAttempts($key, $time_window) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM rate_limits 
                WHERE rate_key = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$key, $time_window]);
            $result = $stmt->fetch();
            
            return (int) $result['attempts'];
        } catch (Exception $e) {
            error_log("Rate limit check failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Record an attempt
     */
    public function recordAttempt($key) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (rate_key, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $key,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Rate limit recording failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get rate limit key
     */
    private function getRateLimitKey($action, $identifier) {
        return "{$action}_{$identifier}";
    }
    
    /**
     * Get identifier (IP address or user ID)
     */
    private function getIdentifier() {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log rate limit exceeded
     */
    private function logRateLimitExceeded($action, $identifier, $attempts) {
        $this->logSecurityEvent('rate_limit_exceeded', [
            'action' => $action,
            'identifier' => $identifier,
            'attempts' => $attempts,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Block IP if too many violations
        $this->checkAndBlockIP($action, $identifier);
    }
    
    /**
     * Check and block IP if necessary
     */
    private function checkAndBlockIP($action, $identifier) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as violations 
                FROM security_logs 
                WHERE event_type = 'rate_limit_exceeded' 
                AND event_data LIKE ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute(["%\"action\":\"{$action}\"%"]);
            $result = $stmt->fetch();
            
            $violations = (int) $result['violations'];
            
            // Block IP after 5 violations in 1 hour
            if ($violations >= 5) {
                $this->blockIP($_SERVER['REMOTE_ADDR'] ?? '', "Rate limit violations: {$action}");
            }
        } catch (Exception $e) {
            error_log("IP blocking check failed: " . $e->getMessage());
        }
    }
    
    /**
     * Block IP address
     */
    private function blockIP($ip, $reason) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ip_blacklist (ip_address, reason, expires_at, created_at) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
                ON DUPLICATE KEY UPDATE 
                expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                reason = CONCAT(reason, '; ', ?)
            ");
            $stmt->execute([$ip, $reason, $reason]);
        } catch (Exception $e) {
            error_log("IP blocking failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent($event, $data = []) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (event_type, event_data, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $event,
                json_encode($data),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get rate limit status
     */
    public function getRateLimitStatus($action, $identifier = null) {
        if (!$identifier) {
            $identifier = $this->getIdentifier();
        }
        
        $key = $this->getRateLimitKey($action, $identifier);
        
        // Get attempts in different time windows
        $windows = [
            '1min' => 60,
            '5min' => 300,
            '15min' => 900,
            '1hour' => 3600
        ];
        
        $status = [];
        foreach ($windows as $window_name => $seconds) {
            $status[$window_name] = $this->getCurrentAttempts($key, $seconds);
        }
        
        return $status;
    }
    
    /**
     * Clean up old rate limit records
     */
    public function cleanupOldRecords() {
        try {
            // Clean up records older than 24 hours
            $stmt = $this->pdo->prepare("
                DELETE FROM rate_limits 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            
            // Clean up expired IP blocks
            $stmt = $this->pdo->prepare("
                DELETE FROM ip_blacklist 
                WHERE expires_at < NOW()
            ");
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Rate limit cleanup failed: " . $e->getMessage());
        }
    }
}

// Initialize rate limiting
if (!defined('SKIP_RATE_LIMITING')) {
    global $pdo;
    $rateLimiter = new EnhancedRateLimiting($pdo);
}
?> 