<?php
/**
 * Quick Status Widget
 * Shows a compact system status overview that can be embedded in other pages
 */

require_once 'system_status_monitor.php';

class QuickStatusWidget {
    private $status_monitor;
    
    public function __construct($pdo) {
        $this->status_monitor = new SystemStatusMonitor($pdo);
    }
    
    /**
     * Get quick status summary
     */
    public function getQuickStatus() {
        $results = $this->status_monitor->checkAllServices();
        $overall_status = $this->status_monitor->getOverallStatus();
        
        $status_counts = [
            'online' => 0,
            'warning' => 0,
            'offline' => 0,
            'error' => 0
        ];
        
        foreach ($results as $service => $status) {
            $status_counts[$status['status']]++;
        }
        
        return [
            'overall_status' => $overall_status,
            'status_counts' => $status_counts,
            'critical_services' => $this->getCriticalServices($results)
        ];
    }
    
    /**
     * Get critical services that need attention
     */
    private function getCriticalServices($results) {
        $critical = [];
        
        foreach ($results as $service => $status) {
            if ($status['status'] === 'offline' || $status['status'] === 'error') {
                $critical[] = [
                    'service' => $service,
                    'status' => $status['status'],
                    'message' => $status['message']
                ];
            }
        }
        
        return $critical;
    }
    
    /**
     * Render the widget HTML
     */
    public function render($show_details = false) {
        $status = $this->getQuickStatus();
        
        $status_icon = $this->getStatusIcon($status['overall_status']);
        $status_color = $this->getStatusColor($status['overall_status']);
        
        ob_start();
        ?>
        <div class="quick-status-widget" style="
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        ">
            <div>
                <span><?php echo $status_icon; ?></span>
                <div>
                    <div>
                        حالة النظام: 
                        <?php 
                        switch($status['overall_status']) {
                            case 'healthy': echo 'صحي'; break;
                            case 'warning': echo 'تحذير'; break;
                            case 'critical': echo 'حرج'; break;
                            default: echo 'غير معروف'; break;
                        }
                        ?>
                    </div>
                    <div>
                        <?php echo $status['status_counts']['online']; ?> متاح | 
                        <?php echo $status['status_counts']['warning']; ?> تحذير | 
                        <?php echo $status['status_counts']['offline'] + $status['status_counts']['error']; ?> مشاكل
                    </div>
                </div>
            </div>
            
            <?php if ($show_details && !empty($status['critical_services'])): ?>
                <div>
                    <div>خدمات تحتاج انتباه:</div>
                    <?php foreach ($status['critical_services'] as $service): ?>
                        <div>
                            • <?php echo $this->getServiceName($service['service']); ?>: <?php echo htmlspecialchars($service['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div>
                <a href="system_status.php" style="
                    color: #3498db;
                    text-decoration: none;
                    font-size: 0.9em;
                    font-weight: 600;
                ">عرض التفاصيل الكاملة</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get status icon
     */
    private function getStatusIcon($status) {
        switch($status) {
            case 'healthy': return '✅';
            case 'warning': return '⚠️';
            case 'critical': return '🚨';
            default: return '❓';
        }
    }
    
    /**
     * Get status color
     */
    private function getStatusColor($status) {
        switch($status) {
            case 'healthy': return '#28a745';
            case 'warning': return '#ffc107';
            case 'critical': return '#dc3545';
            default: return '#6c757d';
        }
    }
    
    /**
     * Get service name in Arabic
     */
    private function getServiceName($service) {
        switch($service) {
            case 'database': return 'قاعدة البيانات';
            case 'payment_systems': return 'أنظمة الدفع';
            case 'delivery_systems': return 'أنظمة التوصيل';
            case 'security_systems': return 'أنظمة الأمان';
            case 'email_system': return 'نظام البريد الإلكتروني';
            case 'file_system': return 'نظام الملفات';
            case 'api_services': return 'خدمات API';
            case 'performance': return 'الأداء';
            default: return ucfirst(str_replace('_', ' ', $service));
        }
    }
}
?> 