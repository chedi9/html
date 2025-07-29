<?php
/**
 * HTTPS Enforcement System
 * Comprehensive HTTPS enforcement with SSL certificate validation
 */

class HTTPSEnforcement {
    
    /**
     * Enforce HTTPS for all requests
     */
    public static function enforceHTTPS() {
        // Check if HTTPS is not being used
        if (!self::isHTTPS()) {
            // Get the current URL
            $current_url = self::getCurrentURL();
            
            // Convert to HTTPS
            $https_url = str_replace('http://', 'https://', $current_url);
            
            // Redirect to HTTPS
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $https_url);
            exit();
        }
        
        // Set HSTS header for HTTPS requests
        if (self::isHTTPS()) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }
    
    /**
     * Check if current request is using HTTPS
     */
    public static function isHTTPS() {
        // Check for HTTPS server variable
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }
        
        // Check for forwarded HTTPS header (for load balancers)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        // Check for forwarded SSL header
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        // Check port 443
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current URL
     */
    public static function getCurrentURL() {
        $protocol = self::isHTTPS() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Validate SSL certificate
     */
    public static function validateSSLCertificate($domain = null) {
        if (!$domain) {
            $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }
        
        // Skip validation for localhost
        if ($domain === 'localhost' || $domain === '127.0.0.1') {
            return true;
        }
        
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);
        
        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$client) {
            return false;
        }
        
        $params = stream_context_get_params($client);
        $cert = $params['options']['ssl']['peer_certificate'];
        
        if (!$cert) {
            fclose($client);
            return false;
        }
        
        // Check certificate expiration
        $cert_info = openssl_x509_parse($cert);
        $expiry = $cert_info['validTo_time_t'];
        
        if ($expiry < time()) {
            fclose($client);
            return false;
        }
        
        // Check if certificate is valid for the domain
        $subject_alt_names = $cert_info['extensions']['subjectAltName'] ?? '';
        $common_name = $cert_info['subject']['CN'] ?? '';
        
        $valid_domains = array_merge(
            [$common_name],
            array_map('trim', explode(',', str_replace('DNS:', '', $subject_alt_names)))
        );
        
        $domain_valid = false;
        foreach ($valid_domains as $valid_domain) {
            if (self::domainMatches($domain, $valid_domain)) {
                $domain_valid = true;
                break;
            }
        }
        
        fclose($client);
        return $domain_valid;
    }
    
    /**
     * Check if domain matches certificate domain
     */
    private static function domainMatches($domain, $cert_domain) {
        // Remove wildcard prefix
        $cert_domain = preg_replace('/^\*\./', '', $cert_domain);
        
        // Check exact match
        if ($domain === $cert_domain) {
            return true;
        }
        
        // Check wildcard match
        if (strpos($cert_domain, '*') === 0) {
            $wildcard_base = substr($cert_domain, 2); // Remove '*.'
            if (strpos($domain, $wildcard_base) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get SSL certificate information
     */
    public static function getCertificateInfo($domain = null) {
        if (!$domain) {
            $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }
        
        // Skip for localhost
        if ($domain === 'localhost' || $domain === '127.0.0.1') {
            return [
                'valid' => true,
                'domain' => $domain,
                'expires' => null,
                'issuer' => 'Local Development',
                'subject' => 'Local Development'
            ];
        }
        
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);
        
        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$client) {
            return [
                'valid' => false,
                'error' => $errstr,
                'domain' => $domain
            ];
        }
        
        $params = stream_context_get_params($client);
        $cert = $params['options']['ssl']['peer_certificate'];
        
        if (!$cert) {
            fclose($client);
            return [
                'valid' => false,
                'error' => 'No certificate found',
                'domain' => $domain
            ];
        }
        
        $cert_info = openssl_x509_parse($cert);
        
        fclose($client);
        
        return [
            'valid' => true,
            'domain' => $domain,
            'expires' => date('Y-m-d H:i:s', $cert_info['validTo_time_t']),
            'issuer' => $cert_info['issuer']['O'] ?? 'Unknown',
            'subject' => $cert_info['subject']['CN'] ?? 'Unknown',
            'serial' => $cert_info['serialNumber'] ?? 'Unknown'
        ];
    }
    
    /**
     * Check TLS version
     */
    public static function checkTLSVersion() {
        $tls_version = $_SERVER['SSL_PROTOCOL'] ?? '';
        
        // Map TLS versions to numbers for comparison
        $tls_versions = [
            'TLSv1.0' => 1.0,
            'TLSv1.1' => 1.1,
            'TLSv1.2' => 1.2,
            'TLSv1.3' => 1.3
        ];
        
        $current_version = $tls_versions[$tls_version] ?? 0;
        
        return [
            'version' => $tls_version,
            'version_number' => $current_version,
            'secure' => $current_version >= 1.2, // Require TLS 1.2 or higher
            'recommended' => $current_version >= 1.3 // TLS 1.3 is recommended
        ];
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $data = []) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, event_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
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
}

// Initialize HTTPS enforcement
if (!defined('SKIP_HTTPS_ENFORCEMENT')) {
    HTTPSEnforcement::enforceHTTPS();
}
?> 