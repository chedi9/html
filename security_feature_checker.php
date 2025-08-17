<?php
/**
 * Security Feature Checker
 * Helper functions to check if security features are enabled
 */

require_once 'db.php';

/**
 * Check if a security feature is enabled
 * @param string $feature_name The name of the feature to check
 * @param bool $default Default value if feature not found in database
 * @return bool True if enabled, false if disabled
 */
function isSecurityFeatureEnabled($feature_name, $default = true) {
    global $pdo;
    
    // If PDO is not available, return default
    if (!isset($pdo) || $pdo === null) {
        return $default;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT is_enabled FROM security_features WHERE feature_name = ?");
        $stmt->execute([$feature_name]);
        $result = $stmt->fetch();
        
        if ($result !== false) {
            return (bool)$result['is_enabled'];
        }
        
        // If feature not found in database, return default
        return $default;
    } catch (Exception $e) {
        // If table doesn't exist or error, return default
        error_log("Security feature check failed for '$feature_name': " . $e->getMessage());
        return $default;
    }
}

/**
 * Get all security feature statuses
 * @return array Array of feature names and their enabled status
 */
function getAllSecurityFeatures() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT feature_name, is_enabled FROM security_features");
        $features = [];
        while ($row = $stmt->fetch()) {
            $features[$row['feature_name']] = (bool)$row['is_enabled'];
        }
        return $features;
    } catch (Exception $e) {
        error_log("Failed to get security features: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if WAF is enabled
 * @return bool
 */
function isWAFEnabled() {
    return isSecurityFeatureEnabled('waf_enabled', true);
}

/**
 * Check if rate limiting is enabled
 * @return bool
 */
function isRateLimitingEnabled() {
    return isSecurityFeatureEnabled('rate_limiting_enabled', true);
}

/**
 * Check if IP blocking is enabled
 * @return bool
 */
function isIPBlockingEnabled() {
    return isSecurityFeatureEnabled('ip_blocking_enabled', true);
}

/**
 * Check if security logging is enabled
 * @return bool
 */
function isSecurityLoggingEnabled() {
    return isSecurityFeatureEnabled('security_logging_enabled', true);
}

/**
 * Check if fraud detection is enabled
 * @return bool
 */
function isFraudDetectionEnabled() {
    return isSecurityFeatureEnabled('fraud_detection_enabled', true);
}

/**
 * Check if PCI compliance is enabled
 * @return bool
 */
function isPCIComplianceEnabled() {
    return isSecurityFeatureEnabled('pci_compliance_enabled', true);
}

/**
 * Check if cookie consent is enabled
 * @return bool
 */
function isCookieConsentEnabled() {
    return isSecurityFeatureEnabled('cookie_consent_enabled', true);
}

/**
 * Check if security headers are enabled
 * @return bool
 */
function isSecurityHeadersEnabled() {
    return isSecurityFeatureEnabled('security_headers_enabled', true);
}

/**
 * Check if CSRF protection is enabled
 * @return bool
 */
function isCSRFProtectionEnabled() {
    return isSecurityFeatureEnabled('csrf_protection_enabled', true);
}

/**
 * Check if session security is enabled
 * @return bool
 */
function isSessionSecurityEnabled() {
    return isSecurityFeatureEnabled('session_security_enabled', true);
}

/**
 * Log security event only if logging is enabled
 * @param string $event_type
 * @param array $details
 * @param int|null $user_id
 */
function logSecurityEventIfEnabled($event_type, $details = [], $user_id = null) {
    if (isSecurityLoggingEnabled()) {
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent($event_type, $details, $user_id);
        } else {
            // Fallback logging if main function doesn't exist
            error_log("Security Event [$event_type]: " . json_encode($details));
        }
    }
}

/**
 * Check if IP is blocked only if IP blocking is enabled
 * @param string|null $ip
 * @return bool
 */
function isIPBlockedIfEnabled($ip = null) {
    if (!isIPBlockingEnabled()) {
        return false;
    }
    return isIPBlocked($ip);
}

/**
 * Block IP only if IP blocking is enabled
 * @param string $ip
 * @param string $reason
 * @param int $duration
 */
function blockIPIfEnabled($ip, $reason = 'Security violation', $duration = 3600) {
    if (isIPBlockingEnabled()) {
        blockIP($ip, $reason, $duration);
    }
}
?> 