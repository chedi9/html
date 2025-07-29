<?php
/**
 * Security Dashboard for Security Personnel
 * Restricted access dashboard for security monitoring
 */

require_once '../security_integration.php';

// Check if user is logged in and has security access
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has security access (security_personnel or superadmin)
$allowed_roles = ['security_personnel', 'superadmin'];
if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    header('Location: dashboard.php');
    exit();
}

// Get security statistics
$stats = [];

// WAF Statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM waf_patterns WHERE is_active = 1");
    $stats['waf_patterns'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM waf_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['waf_detections_24h'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM waf_logs WHERE blocked = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['waf_blocked_24h'] = $stmt->fetch()['total'];
} catch (Exception $e) {
    $stats['waf_patterns'] = 0;
    $stats['waf_detections_24h'] = 0;
    $stats['waf_blocked_24h'] = 0;
}

// Security Logs
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM security_logs");
    $stats['total_logs'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM security_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['logs_24h'] = $stmt->fetch()['total'];
} catch (Exception $e) {
    $stats['total_logs'] = 0;
    $stats['logs_24h'] = 0;
}

// Blocked IPs
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blocked_ips WHERE expires_at > NOW()");
    $stats['blocked_ips'] = $stmt->fetch()['total'];
} catch (Exception $e) {
    $stats['blocked_ips'] = 0;
}

// Recent Security Events
try {
    $stmt = $pdo->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 10");
    $recent_events = $stmt->fetchAll();
} catch (Exception $e) {
    $recent_events = [];
}

// Recent WAF Detections
try {
    $stmt = $pdo->query("SELECT * FROM waf_logs ORDER BY created_at DESC LIMIT 10");
    $recent_waf = $stmt->fetchAll();
} catch (Exception $e) {
    $recent_waf = [];
}

// Threat Statistics by Type
try {
    $stmt = $pdo->query("SELECT threat_type, COUNT(*) as count FROM waf_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY threat_type ORDER BY count DESC");
    $threat_stats = $stmt->fetchAll();
} catch (Exception $e) {
    $threat_stats = [];
}

// Get user info
$current_user = $_SESSION['admin_username'] ?? 'Unknown';
$user_role = $_SESSION['admin_role'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 Security Dashboard - WeBuy Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .user-info {
            background: rgba(52, 152, 219, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.success {
            border-left: 5px solid #27ae60;
        }
        
        .stat-card.warning {
            border-left: 5px solid #f39c12;
        }
        
        .stat-card.danger {
            border-left: 5px solid #e74c3c;
        }
        
        .stat-card.info {
            border-left: 5px solid #3498db;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-number.success { color: #27ae60; }
        .stat-number.warning { color: #f39c12; }
        .stat-number.danger { color: #e74c3c; }
        .stat-number.info { color: #3498db; }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1.1em;
            font-weight: 500;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .panel {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .panel h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3em;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        .event-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
        }
        
        .event-item.waf {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        
        .event-item.security {
            border-left-color: #f39c12;
            background: #fffbf0;
        }
        
        .event-time {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .event-type {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .event-details {
            color: #34495e;
            margin-top: 5px;
        }
        
        .threat-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }
        
        .threat-type.sql_injection { background: #e74c3c; }
        .threat-type.xss { background: #f39c12; }
        .threat-type.command_injection { background: #8e44ad; }
        .threat-type.path_traversal { background: #e67e22; }
        .threat-type.file_upload { background: #c0392b; }
        .threat-type.user_agent { background: #16a085; }
        .threat-type.header { background: #2980b9; }
        
        .actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background: #27ae60; }
        .status-warning { background: #f39c12; }
        .status-offline { background: #e74c3c; }
        
        .access-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            margin-left: 10px;
        }
        
        .access-security { background: #e74c3c; }
        .access-super { background: #8e44ad; }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔒 Security Dashboard</h1>
            <p>Enterprise-grade security monitoring and threat management</p>
            <div class="user-info">
                <strong>Logged in as:</strong> <?php echo htmlspecialchars($current_user); ?>
                <span class="access-badge access-<?php echo $user_role === 'superadmin' ? 'super' : 'security'; ?>">
                    <?php echo strtoupper(str_replace('_', ' ', $user_role)); ?>
                </span>
                <span class="status-indicator status-online"></span>
                <strong>Security System: ONLINE</strong>
                <span style="margin-left: 20px; color: #7f8c8d;">
                    Last Updated: <?php echo date('Y-m-d H:i:s'); ?>
                </span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-number success"><?php echo $stats['waf_patterns']; ?></div>
                <div class="stat-label">Active WAF Patterns</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-number warning"><?php echo $stats['waf_detections_24h']; ?></div>
                <div class="stat-label">WAF Detections (24h)</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-number danger"><?php echo $stats['waf_blocked_24h']; ?></div>
                <div class="stat-label">Threats Blocked (24h)</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-number info"><?php echo $stats['blocked_ips']; ?></div>
                <div class="stat-label">Currently Blocked IPs</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-number success"><?php echo $stats['total_logs']; ?></div>
                <div class="stat-label">Total Security Events</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-number info"><?php echo $stats['logs_24h']; ?></div>
                <div class="stat-label">Events (24h)</div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="dashboard-grid">
            <!-- Recent Events -->
            <div class="panel">
                <h3>📊 Recent Security Events</h3>
                <?php if (!empty($recent_events)): ?>
                    <?php foreach ($recent_events as $event): ?>
                        <div class="event-item security">
                            <div class="event-time"><?php echo date('M j, Y H:i:s', strtotime($event['created_at'])); ?></div>
                            <div class="event-type"><?php echo htmlspecialchars($event['event_type']); ?></div>
                            <div class="event-details">
                                IP: <?php echo htmlspecialchars($event['ip_address']); ?>
                                <?php if ($event['user_id']): ?>
                                    | User ID: <?php echo $event['user_id']; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8c8d; text-align: center; padding: 20px;">No recent security events</p>
                <?php endif; ?>
            </div>

            <!-- Threat Statistics -->
            <div class="panel">
                <h3>🛡️ Threat Statistics (7 Days)</h3>
                <?php if (!empty($threat_stats)): ?>
                    <?php foreach ($threat_stats as $threat): ?>
                        <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                            <span class="threat-type <?php echo $threat['threat_type']; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $threat['threat_type'])); ?>
                            </span>
                            <span style="float: right; font-weight: bold; color: #2c3e50;">
                                <?php echo $threat['count']; ?> attacks
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8c8d; text-align: center; padding: 20px;">No threat data available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent WAF Detections -->
        <div class="panel">
            <h3>🚨 Recent WAF Detections</h3>
            <?php if (!empty($recent_waf)): ?>
                <?php foreach ($recent_waf as $waf): ?>
                    <div class="event-item waf">
                        <div class="event-time"><?php echo date('M j, Y H:i:s', strtotime($waf['created_at'])); ?></div>
                        <div class="event-type">
                            <span class="threat-type <?php echo $waf['threat_type']; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $waf['threat_type'])); ?>
                            </span>
                            <?php if ($waf['blocked']): ?>
                                <span style="color: #e74c3c; font-weight: bold;">[BLOCKED]</span>
                            <?php endif; ?>
                        </div>
                        <div class="event-details">
                            IP: <?php echo htmlspecialchars($waf['ip_address']); ?> | 
                            Severity: <?php echo strtoupper($waf['severity']); ?> |
                            Pattern: <?php echo htmlspecialchars(substr($waf['pattern'], 0, 50)); ?>...
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #7f8c8d; text-align: center; padding: 20px;">No recent WAF detections</p>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="actions">
            <?php if ($user_role === 'superadmin'): ?>
                <a href="security_personnel.php" class="btn btn-warning">👥 Manage Security Personnel</a>
            <?php endif; ?>
            <a href="enhanced_security_dashboard.php" class="btn btn-primary">🔧 Advanced Security Dashboard</a>
            <a href="../test_security.php" class="btn btn-success">🧪 Run Security Tests</a>
            <a href="../verify_security_system.php" class="btn btn-info">✅ Verify System Status</a>
            <a href="dashboard.php" class="btn btn-primary">🏠 Back to Admin Dashboard</a>
        </div>

        <!-- Security Status Footer -->
        <div style="text-align: center; margin-top: 30px; color: #7f8c8d;">
            <p><strong>Security Level:</strong> Enterprise Grade | <strong>Access Level:</strong> <?php echo strtoupper(str_replace('_', ' ', $user_role)); ?></p>
            <p>WeBuy Security System - Protecting your e-commerce platform 24/7</p>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html> 