<?php
/**
 * Advanced Security Monitoring Dashboard
 * Comprehensive security monitoring with WAF integration
 */

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../db.php';
require_once '../security_headers.php';

// Get security statistics
function getAdvancedSecurityStats($pdo) {
    $stats = [];
    
    // WAF Statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM waf_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['waf_events_24h'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as blocked FROM waf_logs WHERE blocked = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['waf_blocked_24h'] = $stmt->fetch()['blocked'];
    
    // Threat type breakdown
    $stmt = $pdo->query("
        SELECT threat_type, COUNT(*) as count 
        FROM waf_logs 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY threat_type 
        ORDER BY count DESC
    ");
    $stats['threat_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Severity breakdown
    $stmt = $pdo->query("
        SELECT severity, COUNT(*) as count 
        FROM waf_logs 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY severity 
        ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low')
    ");
    $stats['severity_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top attacking IPs
    $stmt = $pdo->query("
        SELECT ip_address, COUNT(*) as attacks 
        FROM waf_logs 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ip_address 
        ORDER BY attacks DESC 
        LIMIT 10
    ");
    $stats['top_attackers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Security events timeline
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as events
        FROM waf_logs 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stats['timeline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Get recent WAF events
function getRecentWAFEvents($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM waf_logs 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get WAF patterns
function getWAFPatterns($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM waf_patterns 
        WHERE is_active = 1 
        ORDER BY severity DESC, type ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stats = getAdvancedSecurityStats($pdo);
$recent_events = getRecentWAFEvents($pdo);
$waf_patterns = getWAFPatterns($pdo);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>مراقبة الأمان المتقدمة | WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .security-dashboard {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .threat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .threat-type {
            font-weight: bold;
        }
        
        .threat-count {
            background: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        
        .severity-critical { color: #dc3545; }
        .severity-high { color: #fd7e14; }
        .severity-medium { color: #ffc107; }
        .severity-low { color: #28a745; }
        
        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .events-table th,
        .events-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .events-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .blocked { background: #ffe6e6; }
        .alert { background: #fff3cd; }
        
        .pattern-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .pattern-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
        }
        
        .pattern-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .pattern-type {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .pattern-action {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .action-block { background: #dc3545; color: white; }
        .action-alert { background: #ffc107; color: #212529; }
        .action-log { background: #6c757d; color: white; }
        
        .pattern-description {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        .timeline-chart {
            height: 300px;
            margin-top: 15px;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: var(--accent-color); color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="security-dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: var(--primary-color); margin: 0;">🛡️ مراقبة الأمان المتقدمة</h1>
            <a href="dashboard.php" class="action-btn btn-primary">العودة للوحة التحكم</a>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="action-btn btn-primary" onclick="refreshStats()">🔄 تحديث الإحصائيات</button>
            <button class="action-btn btn-warning" onclick="exportWAFLogs()">📊 تصدير سجلات WAF</button>
            <button class="action-btn btn-danger" onclick="clearOldLogs()">🗑️ مسح السجلات القديمة</button>
            <button class="action-btn btn-primary" onclick="testWAF()">🧪 اختبار WAF</button>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['waf_events_24h']; ?></div>
                <div class="stat-label">أحداث WAF (24 ساعة)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['waf_blocked_24h']; ?></div>
                <div class="stat-label">الطلبات المحظورة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($stats['top_attackers']); ?></div>
                <div class="stat-label">عنوان IP مهاجم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($waf_patterns); ?></div>
                <div class="stat-label">أنماط الحماية النشطة</div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- Threat Breakdown -->
            <div class="chart-container">
                <div class="chart-title">📊 توزيع التهديدات</div>
                <?php foreach ($stats['threat_breakdown'] as $threat): ?>
                <div class="threat-item">
                    <span class="threat-type"><?php echo htmlspecialchars($threat['threat_type']); ?></span>
                    <span class="threat-count"><?php echo $threat['count']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Severity Breakdown -->
            <div class="chart-container">
                <div class="chart-title">⚠️ توزيع خطورة التهديدات</div>
                <?php foreach ($stats['severity_breakdown'] as $severity): ?>
                <div class="threat-item">
                    <span class="threat-type severity-<?php echo $severity['severity']; ?>">
                        <?php echo ucfirst($severity['severity']); ?>
                    </span>
                    <span class="threat-count"><?php echo $severity['count']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Top Attackers -->
        <div class="chart-container">
            <div class="chart-title">🎯 أعلى 10 عناوين IP مهاجمة</div>
            <table class="events-table">
                <thead>
                    <tr>
                        <th>عنوان IP</th>
                        <th>عدد الهجمات</th>
                        <th>آخر هجوم</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['top_attackers'] as $attacker): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($attacker['ip_address']); ?></td>
                        <td><?php echo $attacker['attacks']; ?></td>
                        <td>اليوم</td>
                        <td>
                            <button class="action-btn btn-danger" onclick="blockIP('<?php echo $attacker['ip_address']; ?>')">
                                حظر
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent WAF Events -->
        <div class="chart-container">
            <div class="chart-title">📝 أحداث WAF الأخيرة</div>
            <table class="events-table">
                <thead>
                    <tr>
                        <th>النوع</th>
                        <th>الخطورة</th>
                        <th>عنوان IP</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_events as $event): ?>
                    <tr class="<?php echo $event['blocked'] ? 'blocked' : 'alert'; ?>">
                        <td><?php echo htmlspecialchars($event['threat_type']); ?></td>
                        <td>
                            <span class="severity-<?php echo $event['severity']; ?>">
                                <?php echo ucfirst($event['severity']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($event['ip_address']); ?></td>
                        <td>
                            <?php echo $event['blocked'] ? '🛡️ محظور' : '⚠️ تنبيه'; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($event['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- WAF Patterns -->
        <div class="chart-container">
            <div class="chart-title">🔍 أنماط الحماية النشطة</div>
            <div class="pattern-grid">
                <?php foreach ($waf_patterns as $pattern): ?>
                <div class="pattern-card">
                    <div class="pattern-header">
                        <span class="pattern-type"><?php echo htmlspecialchars($pattern['type']); ?></span>
                        <span class="pattern-action action-<?php echo $pattern['action']; ?>">
                            <?php echo ucfirst($pattern['action']); ?>
                        </span>
                    </div>
                    <div class="pattern-description">
                        <?php echo htmlspecialchars($pattern['description']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
    function refreshStats() {
        location.reload();
    }
    
    function exportWAFLogs() {
        window.open('export_waf_logs.php', '_blank');
    }
    
    function clearOldLogs() {
        if (confirm('هل أنت متأكد من مسح السجلات القديمة؟')) {
            fetch('clear_old_logs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم مسح السجلات القديمة بنجاح');
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء مسح السجلات');
                }
            });
        }
    }
    
    function testWAF() {
        // Test WAF with various attack patterns
        const testPatterns = [
            'union select',
            '<script>alert("xss")</script>',
            'cat /etc/passwd',
            '../../../etc/passwd'
        ];
        
        testPatterns.forEach(pattern => {
            fetch('test_waf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ test_pattern: pattern })
            });
        });
        
        alert('تم إرسال أنماط اختبار WAF');
    }
    
    function blockIP(ip) {
        if (confirm(`هل تريد حظر عنوان IP: ${ip}؟`)) {
            fetch('block_ip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ip: ip, reason: 'WAF monitoring - high attack count' })
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`تم حظر عنوان IP: ${ip}`);
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حظر عنوان IP');
                }
            });
        }
    }
    
    // Auto-refresh every 30 seconds
    setInterval(refreshStats, 30000);
    </script>
</body>
</html> 