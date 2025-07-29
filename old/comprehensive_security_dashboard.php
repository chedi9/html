<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

session_start();
require_once '../db.php';
require_once '../https_enforcement.php';
require_once '../enhanced_rate_limiting.php';
require_once '../security_testing_framework.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle security test execution
if (isset($_POST['run_security_tests'])) {
    $securityTester = new SecurityTestingFramework($pdo);
    $test_results = $securityTester->runSecurityTests();
    $report = $securityTester->generateSecurityReport();
    $securityTester->saveTestResults($report);
}

// Get security statistics
$security_stats = getSecurityStatistics($pdo);
$recent_alerts = getRecentSecurityAlerts($pdo);
$rate_limit_status = getRateLimitStatus($pdo);
$https_status = getHTTPSStatus();
$certificate_info = HTTPSEnforcement::getCertificateInfo();
$tls_info = HTTPSEnforcement::checkTLSVersion();

function getSecurityStatistics($pdo) {
    $stats = [];
    
    // Get total security events
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM security_logs");
    $stats['total_events'] = $stmt->fetch()['total'];
    
    // Get events in last 24 hours
    $stmt = $pdo->query("SELECT COUNT(*) as recent FROM security_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['recent_events'] = $stmt->fetch()['recent'];
    
    // Get fraud alerts
    $stmt = $pdo->query("SELECT COUNT(*) as fraud FROM fraud_alerts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['fraud_alerts'] = $stmt->fetch()['fraud'];
    
    // Get blocked IPs
    $stmt = $pdo->query("SELECT COUNT(*) as blocked FROM ip_blacklist WHERE expires_at > NOW()");
    $stats['blocked_ips'] = $stmt->fetch()['blocked'];
    
    // Get rate limit violations
    $stmt = $pdo->query("SELECT COUNT(*) as violations FROM security_logs WHERE event_type = 'rate_limit_exceeded' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['rate_violations'] = $stmt->fetch()['violations'];
    
    return $stats;
}

function getRecentSecurityAlerts($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM security_logs 
        WHERE event_type IN ('login_failed', 'suspicious_activity', 'fraud_detected', 'rate_limit_exceeded')
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

function getRateLimitStatus($pdo) {
    $stmt = $pdo->query("
        SELECT rate_key, COUNT(*) as attempts, MAX(created_at) as last_attempt
        FROM rate_limits 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY rate_key
        ORDER BY attempts DESC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

function getHTTPSStatus() {
    return [
        'is_https' => HTTPSEnforcement::isHTTPS(),
        'current_url' => HTTPSEnforcement::getCurrentURL(),
        'certificate_valid' => HTTPSEnforcement::validateSSLCertificate()
    ];
}

$page_title = 'لوحة تحكم الأمان الشاملة';
include 'admin_header.php';
?>

<div class="comprehensive-security-dashboard">
    <div class="dashboard-header">
        <h1>🔒 لوحة تحكم الأمان الشاملة</h1>
        <p class="dashboard-subtitle">مراقبة وإدارة جميع جوانب الأمان في النظام</p>
    </div>

    <!-- Security Statistics -->
    <div class="security-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo $security_stats['total_events']; ?></h3>
                <p>إجمالي الأحداث الأمنية</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <h3><?php echo $security_stats['recent_events']; ?></h3>
                <p>أحداث اليوم</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🚨</div>
            <div class="stat-content">
                <h3><?php echo $security_stats['fraud_alerts']; ?></h3>
                <p>تنبيهات الاحتيال</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🚫</div>
            <div class="stat-content">
                <h3><?php echo $security_stats['blocked_ips']; ?></h3>
                <p>عنوان IP محظور</p>
            </div>
        </div>
    </div>

    <!-- HTTPS Status -->
    <div class="security-section">
        <h2>🔐 حالة HTTPS</h2>
        <div class="https-status-grid">
            <div class="status-card <?php echo $https_status['is_https'] ? 'success' : 'error'; ?>">
                <h4>HTTPS مفعل</h4>
                <p><?php echo $https_status['is_https'] ? '✅ مفعل' : '❌ غير مفعل'; ?></p>
            </div>
            
            <div class="status-card <?php echo $certificate_info['valid'] ? 'success' : 'error'; ?>">
                <h4>شهادة SSL</h4>
                <p><?php echo $certificate_info['valid'] ? '✅ صالحة' : '❌ غير صالحة'; ?></p>
                <?php if ($certificate_info['valid']): ?>
                    <small>تنتهي في: <?php echo $certificate_info['expires']; ?></small>
                <?php endif; ?>
            </div>
            
            <div class="status-card <?php echo $tls_info['secure'] ? 'success' : 'warning'; ?>">
                <h4>إصدار TLS</h4>
                <p><?php echo $tls_info['version'] ?: 'غير محدد'; ?></p>
                <small><?php echo $tls_info['secure'] ? 'آمن' : 'يحتاج تحديث'; ?></small>
            </div>
        </div>
    </div>

    <!-- Rate Limiting Status -->
    <div class="security-section">
        <h2>⏱️ حالة تحديد المعدل</h2>
        <div class="rate-limit-table">
            <table>
                <thead>
                    <tr>
                        <th>النشاط</th>
                        <th>المحاولات</th>
                        <th>آخر محاولة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rate_limit_status as $rate): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rate['rate_key']); ?></td>
                            <td><?php echo $rate['attempts']; ?></td>
                            <td><?php echo $rate['last_attempt']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $rate['attempts'] > 5 ? 'warning' : 'success'; ?>">
                                    <?php echo $rate['attempts'] > 5 ? 'تحذير' : 'طبيعي'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Security Alerts -->
    <div class="security-section">
        <h2>🚨 التنبيهات الأمنية الأخيرة</h2>
        <div class="alerts-list">
            <?php foreach ($recent_alerts as $alert): ?>
                <div class="alert-item">
                    <div class="alert-icon">
                        <?php
                        $icon = '🔍';
                        switch ($alert['event_type']) {
                            case 'login_failed': $icon = '🔑'; break;
                            case 'suspicious_activity': $icon = '⚠️'; break;
                            case 'fraud_detected': $icon = '🚨'; break;
                            case 'rate_limit_exceeded': $icon = '⏱️'; break;
                        }
                        echo $icon;
                        ?>
                    </div>
                    <div class="alert-content">
                        <h4><?php echo htmlspecialchars($alert['event_type']); ?></h4>
                        <p><?php echo htmlspecialchars($alert['ip_address']); ?></p>
                        <small><?php echo $alert['created_at']; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Security Testing -->
    <div class="security-section">
        <h2>🧪 اختبارات الأمان</h2>
        <form method="POST" class="security-test-form">
            <button type="submit" name="run_security_tests" class="test-button">
                تشغيل اختبارات الأمان الشاملة
            </button>
        </form>
        
        <?php if (isset($report)): ?>
            <div class="test-results">
                <h3>نتائج الاختبارات</h3>
                <div class="results-summary">
                    <div class="result-stat">
                        <span class="number"><?php echo $report['total_tests']; ?></span>
                        <span class="label">إجمالي الاختبارات</span>
                    </div>
                    <div class="result-stat success">
                        <span class="number"><?php echo $report['passed_tests']; ?></span>
                        <span class="label">نجح</span>
                    </div>
                    <div class="result-stat error">
                        <span class="number"><?php echo $report['failed_tests']; ?></span>
                        <span class="label">فشل</span>
                    </div>
                </div>
                
                <?php if (!empty($report['recommendations'])): ?>
                    <div class="recommendations">
                        <h4>التوصيات</h4>
                        <ul>
                            <?php foreach ($report['recommendations'] as $recommendation): ?>
                                <li><?php echo htmlspecialchars($recommendation); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="security-section">
        <h2>⚡ إجراءات سريعة</h2>
        <div class="quick-actions">
            <a href="security_dashboard.php" class="action-button">
                <span class="icon">🔍</span>
                <span>كشف الاحتيال</span>
            </a>
            <a href="security_features.php" class="action-button">
                <span class="icon">🛡️</span>
                <span>مركز الأمان</span>
            </a>
            <a href="pci_compliance_dashboard.php" class="action-button">
                <span class="icon">💳</span>
                <span>امتثال PCI</span>
            </a>
            <a href="payment_settings.php" class="action-button">
                <span class="icon">⚙️</span>
                <span>إعدادات الدفع</span>
            </a>
        </div>
    </div>
</div>

<style>
.comprehensive-security-dashboard {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 30px;
}

.dashboard-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.dashboard-subtitle {
    color: #7f8c8d;
    font-size: 1.1em;
}

.security-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5em;
    margin-right: 20px;
}

.stat-content h3 {
    font-size: 2em;
    margin: 0 0 5px 0;
}

.stat-content p {
    margin: 0;
    opacity: 0.9;
}

.security-section {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.security-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.https-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.status-card {
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    border: 2px solid;
}

.status-card.success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.status-card.error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.status-card.warning {
    background: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.status-card h4 {
    margin: 0 0 10px 0;
}

.status-card p {
    margin: 0 0 5px 0;
    font-weight: bold;
}

.status-card small {
    opacity: 0.7;
}

.rate-limit-table table {
    width: 100%;
    border-collapse: collapse;
}

.rate-limit-table th,
.rate-limit-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.rate-limit-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 500;
}

.status-badge.success {
    background: #d4edda;
    color: #155724;
}

.status-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.alert-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #007bff;
}

.alert-icon {
    font-size: 1.5em;
    margin-right: 15px;
}

.alert-content h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.alert-content p {
    margin: 0 0 5px 0;
    color: #6c757d;
}

.alert-content small {
    color: #adb5bd;
}

.security-test-form {
    margin-bottom: 20px;
}

.test-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 10px;
    font-size: 1.1em;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.test-button:hover {
    transform: translateY(-2px);
}

.test-results {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.results-summary {
    display: flex;
    gap: 30px;
    margin-bottom: 20px;
}

.result-stat {
    text-align: center;
}

.result-stat .number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
}

.result-stat .label {
    color: #6c757d;
    font-size: 0.9em;
}

.result-stat.success .number {
    color: #28a745;
}

.result-stat.error .number {
    color: #dc3545;
}

.recommendations ul {
    margin: 0;
    padding-left: 20px;
}

.recommendations li {
    margin-bottom: 8px;
    color: #6c757d;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.action-button:hover {
    transform: translateY(-3px);
    color: white;
    text-decoration: none;
}

.action-button .icon {
    font-size: 2em;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .security-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .https-status-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .results-summary {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<?php include 'admin_footer.php'; ?> 