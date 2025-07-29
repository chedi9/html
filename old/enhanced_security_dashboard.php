<?php
/**
 * Enhanced Security Dashboard
 * Comprehensive security monitoring with WAF testing
 */

require_once '../security_integration.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get security statistics
function getSecurityStats($pdo) {
    $stats = [];
    
    // WAF Statistics
    $stmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM waf_patterns WHERE is_active = 1) as active_patterns,
        (SELECT COUNT(*) FROM waf_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as threats_24h,
        (SELECT COUNT(*) FROM waf_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as threats_1h,
        (SELECT COUNT(*) FROM rate_limits WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as rate_limits_24h,
        (SELECT COUNT(*) FROM blocked_ips WHERE expires_at > NOW()) as blocked_ips");
    $stats = $stmt->fetch();
    
    return $stats;
}

// Get recent threats
function getRecentThreats($pdo, $limit = 10) {
    $stmt = $pdo->prepare("SELECT * FROM waf_logs ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Get top attacking IPs
function getTopAttackingIPs($pdo, $limit = 10) {
    $stmt = $pdo->prepare("SELECT ip_address, COUNT(*) as attack_count, MAX(created_at) as last_attack 
                           FROM waf_logs 
                           WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           GROUP BY ip_address 
                           ORDER BY attack_count DESC 
                           LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

$stats = getSecurityStats($pdo);
$recentThreats = getRecentThreats($pdo);
$topIPs = getTopAttackingIPs($pdo);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Security Dashboard - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 10px 0; color: var(--primary-color); }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 10px; }
        .critical { color: #dc3545; }
        .warning { color: #ffc107; }
        .success { color: #28a745; }
        .info { color: #17a2b8; }
        
        .section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { margin: 0 0 20px 0; color: var(--primary-color); border-bottom: 2px solid var(--accent-color); padding-bottom: 10px; }
        
        .threat-table { width: 100%; border-collapse: collapse; }
        .threat-table th, .threat-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .threat-table th { background: #f8f9fa; font-weight: bold; }
        .severity-critical { background: #ffe6e6; color: #dc3545; }
        .severity-high { background: #fff3cd; color: #856404; }
        .severity-medium { background: #d1ecf1; color: #0c5460; }
        .severity-low { background: #d4edda; color: #155724; }
        
        .test-panel { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .test-panel h3 { margin: 0 0 15px 0; color: var(--primary-color); }
        .test-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .test-btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9em; }
        .test-btn.sql { background: #dc3545; color: white; }
        .test-btn.xss { background: #ffc107; color: #212529; }
        .test-btn.command { background: #6f42c1; color: white; }
        .test-btn.path { background: #fd7e14; color: white; }
        
        .block-btn { padding: 4px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.8em; }
        .block-btn:hover { background: #c82333; }
        
        .refresh-btn { padding: 10px 20px; background: var(--accent-color); color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px; }
        .refresh-btn:hover { background: #00a085; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>🛡️ Enhanced Security Dashboard</h1>
        <p>Real-time security monitoring and threat management</p>
        
        <button class="refresh-btn" onclick="refreshDashboard()">🔄 Refresh Dashboard</button>
        
        <div id="alerts"></div>
        
        <!-- Security Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🛡️ Active WAF Patterns</h3>
                <div class="stat-number success"><?= $stats['active_patterns'] ?></div>
                <p>Patterns protecting against threats</p>
            </div>
            
            <div class="stat-card">
                <h3>🚨 Threats (24h)</h3>
                <div class="stat-number <?= $stats['threats_24h'] > 10 ? 'critical' : ($stats['threats_24h'] > 5 ? 'warning' : 'success') ?>">
                    <?= $stats['threats_24h'] ?>
                </div>
                <p>Threats detected in last 24 hours</p>
            </div>
            
            <div class="stat-card">
                <h3>⚡ Threats (1h)</h3>
                <div class="stat-number <?= $stats['threats_1h'] > 5 ? 'critical' : ($stats['threats_1h'] > 2 ? 'warning' : 'success') ?>">
                    <?= $stats['threats_1h'] ?>
                </div>
                <p>Threats detected in last hour</p>
            </div>
            
            <div class="stat-card">
                <h3>⏱️ Rate Limits (24h)</h3>
                <div class="stat-number info"><?= $stats['rate_limits_24h'] ?></div>
                <p>Rate limiting events</p>
            </div>
            
            <div class="stat-card">
                <h3>🚫 Blocked IPs</h3>
                <div class="stat-number warning"><?= $stats['blocked_ips'] ?></div>
                <p>Currently blocked IP addresses</p>
            </div>
        </div>
        
        <!-- WAF Testing Panel -->
        <div class="section">
            <h2>🧪 WAF Testing Panel</h2>
            <div class="test-panel">
                <h3>Test WAF Patterns</h3>
                <p>Click buttons below to test different threat patterns and see WAF in action:</p>
                
                <div class="test-buttons">
                    <button class="test-btn sql" onclick="testWAF('1\' OR \'1\'=\'1')">SQL Injection</button>
                    <button class="test-btn xss" onclick="testWAF('<script>alert(\'XSS\')</script>')">XSS Attack</button>
                    <button class="test-btn command" onclick="testWAF('cat /etc/passwd')">Command Injection</button>
                    <button class="test-btn path" onclick="testWAF('../../../etc/passwd')">Path Traversal</button>
                    <button class="test-btn sql" onclick="testWAF('1 UNION SELECT * FROM users')">Union SQL</button>
                    <button class="test-btn xss" onclick="testWAF('javascript:alert(\'XSS\')')">JS Protocol</button>
                </div>
                
                <div id="test-results" style="margin-top: 15px;"></div>
            </div>
        </div>
        
        <!-- Recent Threats -->
        <div class="section">
            <h2>🚨 Recent Threats</h2>
            <table class="threat-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Threat Type</th>
                        <th>Severity</th>
                        <th>IP Address</th>
                        <th>Pattern</th>
                        <th>Value</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentThreats as $threat): ?>
                    <tr class="severity-<?= $threat['severity'] ?>">
                        <td><?= date('H:i:s', strtotime($threat['created_at'])) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $threat['threat_type'])) ?></td>
                        <td><span class="severity-<?= $threat['severity'] ?>"><?= ucfirst($threat['severity']) ?></span></td>
                        <td><?= htmlspecialchars($threat['ip_address']) ?></td>
                        <td><?= htmlspecialchars($threat['pattern']) ?></td>
                        <td><?= htmlspecialchars(substr($threat['value'], 0, 50)) ?></td>
                        <td>
                            <button class="block-btn" onclick="blockIP('<?= $threat['ip_address'] ?>')">
                                🚫 Block IP
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Attacking IPs -->
        <div class="section">
            <h2>🎯 Top Attacking IPs (24h)</h2>
            <table class="threat-table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Attack Count</th>
                        <th>Last Attack</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topIPs as $ip): ?>
                    <tr>
                        <td><?= htmlspecialchars($ip['ip_address']) ?></td>
                        <td><strong><?= $ip['attack_count'] ?></strong></td>
                        <td><?= date('H:i:s', strtotime($ip['last_attack'])) ?></td>
                        <td>
                            <button class="block-btn" onclick="blockIP('<?= $ip['ip_address'] ?>')">
                                🚫 Block IP
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Test WAF functionality
        function testWAF(pattern) {
            fetch('test_waf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ test_pattern: pattern })
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('test-results');
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            ✅ <strong>Threat Detected!</strong><br>
                            Type: ${data.threat_type}<br>
                            Severity: ${data.severity}<br>
                            Pattern: ${data.pattern}<br>
                            Blocked: ${data.blocked ? 'Yes' : 'No'}<br>
                            Time: ${data.timestamp}
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-error">
                            ❌ <strong>Test Failed:</strong> ${data.error}
                        </div>
                    `;
                }
                
                // Refresh dashboard after test
                setTimeout(refreshDashboard, 2000);
            })
            .catch(error => {
                document.getElementById('test-results').innerHTML = `
                    <div class="alert alert-error">
                        ❌ <strong>Error:</strong> ${error.message}
                    </div>
                `;
            });
        }
        
        // Block IP address
        function blockIP(ip) {
            if (confirm(`هل تريد حظر عنوان IP: ${ip}؟`)) {
                fetch('block_ip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        ip: ip, 
                        reason: 'WAF monitoring - high attack count',
                        duration: 3600 // 1 hour
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`تم حظر عنوان IP: ${ip}`, 'success');
                        setTimeout(refreshDashboard, 1000);
                    } else {
                        showAlert(`خطأ: ${data.error}`, 'error');
                    }
                })
                .catch(error => {
                    showAlert(`خطأ: ${error.message}`, 'error');
                });
            }
        }
        
        // Show alert
        function showAlert(message, type) {
            const alertsDiv = document.getElementById('alerts');
            alertsDiv.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                alertsDiv.innerHTML = '';
            }, 5000);
        }
        
        // Refresh dashboard
        function refreshDashboard() {
            location.reload();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshDashboard, 30000);
    </script>
</body>
</html> 