<?php
/**
 * Web Application Firewall (WAF)
 * Provides additional protection against common web attacks
 */

class WebApplicationFirewall {
    private $pdo;
    private $blocked_patterns = [];
    private $suspicious_patterns = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadPatterns();
    }
    
    /**
     * Load security patterns from database
     */
    private function loadPatterns() {
        try {
            // Load blocked patterns
            $stmt = $this->pdo->query("SELECT pattern, type FROM waf_patterns WHERE is_active = 1 AND action = 'block'");
            $this->blocked_patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Load suspicious patterns
            $stmt = $this->pdo->query("SELECT pattern, type FROM waf_patterns WHERE is_active = 1 AND action = 'alert'");
            $this->suspicious_patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WAF pattern loading failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check incoming request for threats
     */
    public function checkRequest() {
        $threats = [];
        
        // Check GET parameters
        if (!empty($_GET)) {
            $threats = array_merge($threats, $this->checkInput($_GET, 'GET'));
        }
        
        // Check POST parameters
        if (!empty($_POST)) {
            $threats = array_merge($threats, $this->checkInput($_POST, 'POST'));
        }
        
        // Check headers
        $threats = array_merge($threats, $this->checkHeaders());
        
        // Check user agent
        $threats = array_merge($threats, $this->checkUserAgent());
        
        // Check request method
        $threats = array_merge($threats, $this->checkRequestMethod());
        
        // Check file uploads
        if (!empty($_FILES)) {
            $threats = array_merge($threats, $this->checkFileUploads());
        }
        
        return $threats;
    }
    
    /**
     * Check input data for malicious patterns
     */
    private function checkInput($data, $type) {
        $threats = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $threats = array_merge($threats, $this->checkInput($value, $type));
            } else {
                $threats = array_merge($threats, $this->checkValue($value, $key, $type));
            }
        }
        
        return $threats;
    }
    
    /**
     * Check individual value for threats
     */
    private function checkValue($value, $key, $type) {
        $threats = [];
        
        // Check for SQL injection patterns
        $sql_patterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            '/insert\s+into/i',
            '/update\s+set/i',
            '/alter\s+table/i',
            '/exec\s*\(/i',
            '/xp_cmdshell/i'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $threats[] = [
                    'type' => 'sql_injection',
                    'pattern' => $pattern,
                    'value' => $value,
                    'key' => $key,
                    'input_type' => $type,
                    'severity' => 'high'
                ];
            }
        }
        
        // Check for XSS patterns
        $xss_patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i'
        ];
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $threats[] = [
                    'type' => 'xss',
                    'pattern' => $pattern,
                    'value' => $value,
                    'key' => $key,
                    'input_type' => $type,
                    'severity' => 'high'
                ];
            }
        }
        
        // Check for command injection
        $cmd_patterns = [
            '/\b(cat|ls|dir|rm|del|wget|curl|nc|telnet|ssh|ftp)\b/i',
            '/[;&|`$()]/',
            '/\b(eval|exec|system|shell_exec|passthru)\b/i'
        ];
        
        foreach ($cmd_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $threats[] = [
                    'type' => 'command_injection',
                    'pattern' => $pattern,
                    'value' => $value,
                    'key' => $key,
                    'input_type' => $type,
                    'severity' => 'critical'
                ];
            }
        }
        
        // Check for path traversal
        $path_patterns = [
            '/\.\.\//',
            '/\.\.\\\/',
            '/\/etc\/passwd/',
            '/\/proc\//',
            '/\/sys\//'
        ];
        
        foreach ($path_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $threats[] = [
                    'type' => 'path_traversal',
                    'pattern' => $pattern,
                    'value' => $value,
                    'key' => $key,
                    'input_type' => $type,
                    'severity' => 'high'
                ];
            }
        }
        
        return $threats;
    }
    
    /**
     * Check HTTP headers for threats
     */
    private function checkHeaders() {
        $threats = [];
        
        $suspicious_headers = [
            'HTTP_X_FORWARDED_FOR' => '/^(?!\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$)/',
            'HTTP_USER_AGENT' => '/bot|crawler|spider|scraper/i',
            'HTTP_REFERER' => '/javascript:/i'
        ];
        
        foreach ($suspicious_headers as $header => $pattern) {
            if (isset($_SERVER[$header])) {
                if (preg_match($pattern, $_SERVER[$header])) {
                    $threats[] = [
                        'type' => 'suspicious_header',
                        'header' => $header,
                        'value' => $_SERVER[$header],
                        'pattern' => $pattern,
                        'severity' => 'medium'
                    ];
                }
            }
        }
        
        return $threats;
    }
    
    /**
     * Check user agent for threats
     */
    private function checkUserAgent() {
        $threats = [];
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check for suspicious user agents
        $suspicious_agents = [
            '/sqlmap/i',
            '/nikto/i',
            '/nmap/i',
            '/w3af/i',
            '/burp/i',
            '/zap/i',
            '/acunetix/i',
            '/nessus/i'
        ];
        
        foreach ($suspicious_agents as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                $threats[] = [
                    'type' => 'suspicious_user_agent',
                    'pattern' => $pattern,
                    'user_agent' => $user_agent,
                    'severity' => 'high'
                ];
            }
        }
        
        return $threats;
    }
    
    /**
     * Check request method
     */
    private function checkRequestMethod() {
        $threats = [];
        
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Block dangerous methods
        $dangerous_methods = ['PUT', 'DELETE', 'PATCH', 'TRACE', 'OPTIONS'];
        
        if (in_array($method, $dangerous_methods)) {
            $threats[] = [
                'type' => 'dangerous_method',
                'method' => $method,
                'severity' => 'high'
            ];
        }
        
        return $threats;
    }
    
    /**
     * Check file uploads for threats
     */
    private function checkFileUploads() {
        $threats = [];
        
        foreach ($_FILES as $field => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Check file extension
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $dangerous_extensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd', 'sh'];
                
                if (in_array($extension, $dangerous_extensions)) {
                    $threats[] = [
                        'type' => 'dangerous_file_upload',
                        'filename' => $file['name'],
                        'extension' => $extension,
                        'field' => $field,
                        'severity' => 'critical'
                    ];
                }
                
                // Check file content (basic check)
                $content = file_get_contents($file['tmp_name']);
                if (strpos($content, '<?php') !== false || strpos($content, '<%') !== false) {
                    $threats[] = [
                        'type' => 'php_code_in_file',
                        'filename' => $file['name'],
                        'field' => $field,
                        'severity' => 'critical'
                    ];
                }
            }
        }
        
        return $threats;
    }
    
    /**
     * Handle threats based on severity
     */
    public function handleThreats($threats) {
        foreach ($threats as $threat) {
            $this->logThreat($threat);
            
            // Block critical and high severity threats
            if (in_array($threat['severity'], ['critical', 'high'])) {
                $this->blockRequest($threat);
            }
            
            // Alert for medium severity threats
            if ($threat['severity'] === 'medium') {
                $this->alertThreat($threat);
            }
        }
    }
    
    /**
     * Log threat for monitoring
     */
    private function logThreat($threat) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO waf_logs (threat_type, pattern, value, severity, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $threat['type'],
                $threat['pattern'] ?? '',
                json_encode($threat),
                $threat['severity'],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("WAF logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Block the request
     */
    private function blockRequest($threat) {
        // Log the block
        $this->logSecurityEvent('waf_block', $threat);
        
        // Send 403 Forbidden response
        http_response_code(403);
        echo json_encode([
            'error' => 'Access denied',
            'message' => 'Your request has been blocked for security reasons.',
            'reference' => uniqid()
        ]);
        exit();
    }
    
    /**
     * Alert about threat
     */
    private function alertThreat($threat) {
        $this->logSecurityEvent('waf_alert', $threat);
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent($event, $data) {
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent($event, $data);
        }
    }
}

// Initialize WAF
$waf = new WebApplicationFirewall($pdo);

// Check request for threats
$threats = $waf->checkRequest();

// Handle any threats found
if (!empty($threats)) {
    $waf->handleThreats($threats);
}
?> 