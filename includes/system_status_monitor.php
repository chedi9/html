<?php
/**
 * System Status Monitor
 * Monitors the health of all critical services in the website
 */

class SystemStatusMonitor {
    private $pdo;
    private $status_results = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check all system services
     */
    public function checkAllServices() {
        $this->status_results = [
            'database' => $this->checkDatabase(),
            'payment_systems' => $this->checkPaymentSystems(),
            'delivery_systems' => $this->checkDeliverySystems(),
            'security_systems' => $this->checkSecuritySystems(),
            'email_system' => $this->checkEmailSystem(),
            'file_system' => $this->checkFileSystem(),
            'api_services' => $this->checkAPIServices(),
            'performance' => $this->checkPerformance()
        ];
        
        return $this->status_results;
    }
    
    /**
     * Check database connectivity and performance
     */
    private function checkDatabase() {
        try {
            $start_time = microtime(true);
            
            // Test basic connectivity
            $stmt = $this->pdo->query("SELECT 1");
            $stmt->fetch();
            
            // Test table access with error handling
            try {
                $stmt = $this->pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Check critical tables
                $critical_tables = ['users', 'orders', 'products', 'payment_settings', 'delivery_settings'];
                $missing_tables = array_diff($critical_tables, $tables);
                
                $response_time = (microtime(true) - $start_time) * 1000;
                
                if (!empty($missing_tables)) {
                    return [
                        'status' => 'warning',
                        'message' => 'بعض الجداول المهمة مفقودة',
                        'details' => 'الجداول المفقودة: ' . implode(', ', $missing_tables),
                        'response_time' => round($response_time, 2)
                    ];
                }
                
                if ($response_time > 1000) {
                    return [
                        'status' => 'warning',
                        'message' => 'بطء في الاستجابة',
                        'details' => 'وقت الاستجابة: ' . round($response_time, 2) . 'ms',
                        'response_time' => round($response_time, 2)
                    ];
                }
                
                return [
                    'status' => 'online',
                    'message' => 'متصل ومستقر',
                    'details' => 'وقت الاستجابة: ' . round($response_time, 2) . 'ms',
                    'response_time' => round($response_time, 2)
                ];
                
            } catch (Exception $e) {
                $response_time = (microtime(true) - $start_time) * 1000;
                return [
                    'status' => 'warning',
                    'message' => 'مشكلة في الوصول للجداول',
                    'details' => 'خطأ: ' . $e->getMessage() . ' | وقت الاستجابة: ' . round($response_time, 2) . 'ms',
                    'response_time' => round($response_time, 2)
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'offline',
                'message' => 'خطأ في الاتصال',
                'details' => $e->getMessage(),
                'response_time' => null
            ];
        }
    }
    
    /**
     * Check payment systems
     */
    private function checkPaymentSystems() {
        try {
            // Check payment settings table structure first
            $stmt = $this->pdo->prepare("DESCRIBE payment_settings");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $payment_checks = [];
            $active_gateways = 0;
            
            // Check for enabled payment gateways
            if (in_array('is_enabled', $columns)) {
                $stmt = $this->pdo->prepare("
                    SELECT gateway_name, is_enabled 
                    FROM payment_settings 
                    WHERE is_enabled = 1
                ");
                $stmt->execute();
                $enabled_gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $active_gateways = count($enabled_gateways);
                
                foreach ($enabled_gateways as $gateway) {
                    $payment_checks[] = $gateway['gateway_name'] . ': مفعل';
                }
            } else {
                // Fallback: check if any payment settings exist
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM payment_settings");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && $result['count'] > 0) {
                    $payment_checks[] = 'Payment Settings: موجود';
                    $active_gateways = 1; // Assume at least one is active
                } else {
                    $payment_checks[] = 'Payment Settings: غير موجود';
                }
            }
            
            // Check for recent payment transactions
            try {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM orders 
                    WHERE payment_status = 'completed' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $stmt->execute();
                $recent_payments = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($recent_payments && $recent_payments['count'] > 0) {
                    $payment_checks[] = 'Recent Payments: ' . $recent_payments['count'] . ' معاملة';
                } else {
                    $payment_checks[] = 'Recent Payments: لا توجد معاملات حديثة';
                }
                
            } catch (Exception $e) {
                $payment_checks[] = 'Recent Payments: غير متاح';
            }
            
            if ($active_gateways == 0) {
                return [
                    'status' => 'warning',
                    'message' => 'لا توجد بوابات دفع مفعلة',
                    'details' => implode(', ', $payment_checks),
                    'active_gateways' => $active_gateways
                ];
            }
            
            return [
                'status' => 'online',
                'message' => 'أنظمة الدفع تعمل بشكل طبيعي',
                'details' => implode(', ', $payment_checks),
                'active_gateways' => $active_gateways
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص أنظمة الدفع',
                'details' => $e->getMessage(),
                'active_gateways' => 0
            ];
        }
    }
    
    /**
     * Check delivery systems
     */
    private function checkDeliverySystems() {
        try {
            // Check delivery settings
            $stmt = $this->pdo->query("SELECT * FROM delivery_settings WHERE delivery_company = 'first_delivery'");
            $delivery_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($delivery_settings)) {
                return [
                    'status' => 'warning',
                    'message' => 'إعدادات التوصيل غير مكتملة',
                    'details' => 'يجب إعداد First Delivery',
                    'configured' => false
                ];
            }
            
            $settings = [];
            foreach ($delivery_settings as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            $required_settings = ['api_key', 'merchant_id', 'mode'];
            $missing_settings = array_diff($required_settings, array_keys($settings));
            
            if (!empty($missing_settings)) {
                return [
                    'status' => 'warning',
                    'message' => 'إعدادات First Delivery غير مكتملة',
                    'details' => 'الإعدادات المفقودة: ' . implode(', ', $missing_settings),
                    'configured' => false
                ];
            }
            
            return [
                'status' => 'online',
                'message' => 'First Delivery مُعد ومفعل',
                'details' => 'الوضع: ' . ($settings['mode'] ?? 'غير محدد'),
                'configured' => true
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص أنظمة التوصيل',
                'details' => $e->getMessage(),
                'configured' => false
            ];
        }
    }
    
    /**
     * Check security systems
     */
    private function checkSecuritySystems() {
        try {
            $security_checks = [];
            
            // Check if security headers are enabled
            if (file_exists('security_headers.php')) {
                $security_checks[] = 'Security Headers: متاح';
            } else {
                $security_checks[] = 'Security Headers: غير متاح';
            }
            
            // Check if security integration is enabled
            if (file_exists('security_integration.php')) {
                $security_checks[] = 'Security Integration: متاح';
            } else {
                $security_checks[] = 'Security Integration: غير متاح';
            }
            
            // Check for recent security logs (with table existence check)
            try {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM security_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $stmt->execute();
                $recent_logs = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($recent_logs && $recent_logs['count'] > 0) {
                    $security_checks[] = 'Security Logs: ' . $recent_logs['count'] . ' حدث في 24 ساعة';
                } else {
                    $security_checks[] = 'Security Logs: لا توجد أحداث حديثة';
                }
                
                // Check for failed login attempts
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM security_logs 
                    WHERE event_type = 'failed_login' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ");
                $stmt->execute();
                $failed_logins = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($failed_logins && $failed_logins['count'] > 10) {
                    return [
                        'status' => 'warning',
                        'message' => 'محاولات تسجيل دخول فاشلة كثيرة',
                        'details' => implode(', ', $security_checks),
                        'failed_logins' => $failed_logins['count']
                    ];
                }
                
                return [
                    'status' => 'online',
                    'message' => 'أنظمة الأمان تعمل بشكل طبيعي',
                    'details' => implode(', ', $security_checks),
                    'failed_logins' => $failed_logins['count'] ?? 0
                ];
                
            } catch (Exception $e) {
                // Security logs table doesn't exist or other error
                $security_checks[] = 'Security Logs: غير متاح';
                
                return [
                    'status' => 'warning',
                    'message' => 'بعض أنظمة الأمان غير متاحة',
                    'details' => implode(', ', $security_checks),
                    'failed_logins' => 0
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص أنظمة الأمان',
                'details' => $e->getMessage(),
                'failed_logins' => 0
            ];
        }
    }
    
    /**
     * Check email system
     */
    private function checkEmailSystem() {
        try {
            // Check if mail function is available
            if (!function_exists('mail')) {
                return [
                    'status' => 'offline',
                    'message' => 'دالة البريد الإلكتروني غير متاحة',
                    'details' => 'يجب إعداد خادم البريد الإلكتروني',
                    'configured' => false
                ];
            }
            
            // Check email settings in database (with table existence check)
            try {
                $stmt = $this->pdo->query("SELECT * FROM system_settings WHERE setting_key LIKE '%email%'");
                $email_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($email_settings)) {
                    return [
                        'status' => 'warning',
                        'message' => 'إعدادات البريد الإلكتروني غير مكتملة',
                        'details' => 'يجب إعداد إعدادات البريد الإلكتروني',
                        'configured' => false
                    ];
                }
                
                return [
                    'status' => 'online',
                    'message' => 'نظام البريد الإلكتروني متاح',
                    'details' => 'عدد الإعدادات: ' . count($email_settings),
                    'configured' => true
                ];
                
            } catch (Exception $e) {
                // system_settings table doesn't exist
                return [
                    'status' => 'warning',
                    'message' => 'إعدادات البريد الإلكتروني غير مُعدة',
                    'details' => 'جدول system_settings غير موجود',
                    'configured' => false
                ];
            }
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص نظام البريد الإلكتروني',
                'details' => $e->getMessage(),
                'configured' => false
            ];
        }
    }
    
    /**
     * Check file system
     */
    private function checkFileSystem() {
        try {
            $file_checks = [];
            
            // Check critical directories
            $critical_dirs = [
                'uploads' => 'uploads/',
                'images' => 'images/',
                'admin' => 'admin/',
                'includes' => 'includes/'
            ];
            
            foreach ($critical_dirs as $name => $path) {
                if (is_dir($path) && is_writable($path)) {
                    $file_checks[] = "$name: متاح وقابل للكتابة";
                } elseif (is_dir($path)) {
                    $file_checks[] = "$name: متاح (غير قابل للكتابة)";
                } else {
                    $file_checks[] = "$name: غير متاح";
                }
            }
            
            // Check disk space (with fallback for hosting environments)
            $disk_usage = 0;
            $disk_info = "غير متاح";
            
            if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
                try {
                    $disk_free = disk_free_space('.');
                    $disk_total = disk_total_space('.');
                    
                    if ($disk_free !== false && $disk_total !== false) {
                        $disk_usage = (($disk_total - $disk_free) / $disk_total) * 100;
                        $disk_info = round($disk_usage, 1) . '%';
                        
                        if ($disk_usage > 90) {
                            return [
                                'status' => 'warning',
                                'message' => 'مساحة القرص منخفضة',
                                'details' => 'المساحة المستخدمة: ' . $disk_info,
                                'disk_usage' => round($disk_usage, 1)
                            ];
                        }
                    }
                } catch (Exception $e) {
                    $disk_info = "خطأ في القياس";
                }
            } else {
                $disk_info = "دوال غير متاحة";
            }
            
            return [
                'status' => 'online',
                'message' => 'نظام الملفات يعمل بشكل طبيعي',
                'details' => implode(', ', $file_checks) . ' | المساحة المستخدمة: ' . $disk_info,
                'disk_usage' => round($disk_usage, 1)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص نظام الملفات',
                'details' => $e->getMessage(),
                'disk_usage' => 0
            ];
        }
    }
    
    /**
     * Check API services
     */
    private function checkAPIServices() {
        try {
            $api_checks = [];
            
            // Check if First Delivery API is configured
            $stmt = $this->pdo->query("SELECT * FROM delivery_settings WHERE delivery_company = 'first_delivery' AND setting_key = 'api_key'");
            $fd_api = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fd_api && !empty($fd_api['setting_value'])) {
                $api_checks[] = 'First Delivery API: مُعد';
            } else {
                $api_checks[] = 'First Delivery API: غير مُعد';
            }
            
            // Check payment APIs (with column existence check)
            try {
                // First check if gateway_name column exists
                $stmt = $this->pdo->prepare("DESCRIBE payment_settings");
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('gateway_name', $columns)) {
                    $stmt = $this->pdo->query("SELECT gateway_name FROM payment_settings WHERE is_enabled = 1");
                    $enabled_gateways = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($enabled_gateways)) {
                        $api_checks[] = 'Payment APIs: ' . implode(', ', $enabled_gateways);
                    } else {
                        $api_checks[] = 'Payment APIs: غير مُعدة';
                    }
                } else {
                    // Fallback: check if any payment settings exist
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM payment_settings");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && $result['count'] > 0) {
                        $api_checks[] = 'Payment APIs: موجودة';
                    } else {
                        $api_checks[] = 'Payment APIs: غير مُعدة';
                    }
                }
                
            } catch (Exception $e) {
                $api_checks[] = 'Payment APIs: غير متاحة';
            }
            
            // Check if any APIs are configured
            $configured_apis = 0;
            if ($fd_api && !empty($fd_api['setting_value'])) $configured_apis++;
            if (strpos(implode(', ', $api_checks), 'مُعدة') !== false || strpos(implode(', ', $api_checks), 'موجودة') !== false) $configured_apis++;
            
            if ($configured_apis == 0) {
                return [
                    'status' => 'warning',
                    'message' => 'لا توجد خدمات API مُعدة',
                    'details' => implode(', ', $api_checks),
                    'configured_count' => 0
                ];
            }
            
            return [
                'status' => 'online',
                'message' => $configured_apis . ' خدمة API مُعدة',
                'details' => implode(', ', $api_checks),
                'configured_count' => $configured_apis
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص خدمات API',
                'details' => $e->getMessage(),
                'configured_count' => 0
            ];
        }
    }
    
    /**
     * Check system performance
     */
    private function checkPerformance() {
        try {
            $performance_checks = [];
            
            // Check memory usage (with fallback)
            $memory_percent = 0;
            if (function_exists('memory_get_usage')) {
                $memory_usage = memory_get_usage(true);
                $memory_limit = ini_get('memory_limit');
                
                if ($memory_limit && $memory_limit !== '-1') {
                    $memory_percent = ($memory_usage / $this->parseSize($memory_limit)) * 100;
                    
                    if ($memory_percent > 80) {
                        $performance_checks[] = 'Memory: عالي (' . round($memory_percent, 1) . '%)';
                    } else {
                        $performance_checks[] = 'Memory: طبيعي (' . round($memory_percent, 1) . '%)';
                    }
                } else {
                    $performance_checks[] = 'Memory: غير محدود';
                }
            } else {
                $performance_checks[] = 'Memory: غير متاح';
            }
            
            // Check execution time
            $execution_time = 0;
            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $execution_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
                
                if ($execution_time > 5) {
                    $performance_checks[] = 'Execution Time: بطيء (' . round($execution_time, 2) . 's)';
                } else {
                    $performance_checks[] = 'Execution Time: طبيعي (' . round($execution_time, 2) . 's)';
                }
            } else {
                $performance_checks[] = 'Execution Time: غير متاح';
            }
            
            // Check PHP version
            $php_version = phpversion();
            $performance_checks[] = 'PHP Version: ' . $php_version;
            
            if ($memory_percent > 80 || $execution_time > 5) {
                return [
                    'status' => 'warning',
                    'message' => 'أداء النظام يحتاج انتباه',
                    'details' => implode(', ', $performance_checks),
                    'memory_usage' => round($memory_percent, 1),
                    'execution_time' => round($execution_time, 2)
                ];
            }
            
            return [
                'status' => 'online',
                'message' => 'أداء النظام طبيعي',
                'details' => implode(', ', $performance_checks),
                'memory_usage' => round($memory_percent, 1),
                'execution_time' => round($execution_time, 2)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'خطأ في فحص الأداء',
                'details' => $e->getMessage(),
                'memory_usage' => 0,
                'execution_time' => 0
            ];
        }
    }
    
    /**
     * Parse size string to bytes
     */
    private function parseSize($size) {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
    
    /**
     * Get overall system status
     */
    public function getOverallStatus() {
        $results = $this->checkAllServices();
        
        $status_counts = [
            'online' => 0,
            'warning' => 0,
            'offline' => 0,
            'error' => 0
        ];
        
        foreach ($results as $service => $status) {
            $status_counts[$status['status']]++;
        }
        
        if ($status_counts['offline'] > 0 || $status_counts['error'] > 0) {
            return 'critical';
        } elseif ($status_counts['warning'] > 0) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }
    
    /**
     * Get status results
     */
    public function getStatusResults() {
        return $this->status_results;
    }
}
?> 