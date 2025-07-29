<?php
/**
 * Security Logs Page
 * Displays security logs and events
 */

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role
$user_role = $_SESSION['admin_role'] ?? 'admin';

// Check if user has permission to view security logs
if ($user_role !== 'superadmin' && $user_role !== 'security_personnel') {
    header('Location: unified_dashboard.php');
    exit();
}

require_once '../db.php';
require_once '../security_feature_checker.php';

// Get security logs
$logs = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM security_logs 
        ORDER BY created_at DESC 
        LIMIT 100
    ");
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error loading security logs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سجلات الأمان</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            direction: rtl;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .logs-table th,
        .logs-table td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        .logs-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }
        
        .logs-table tr:hover {
            background: #f8f9fa;
        }
        
        .event-type {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .event-type.login { background: #d4edda; color: #155724; }
        .event-type.failed { background: #f8d7da; color: #721c24; }
        .event-type.security { background: #fff3cd; color: #856404; }
        .event-type.blocked { background: #f8d7da; color: #721c24; }
        
        .no-logs {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 سجلات الأمان</h1>
            <p>مراقبة الأحداث والأنشطة الأمنية</p>
        </div>
        
        <a href="unified_dashboard.php" class="back-btn">← العودة للوحة التحكم</a>
        
        <?php if (isset($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin: 20px 0;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($logs)): ?>
            <div class="no-logs">
                <h3>📝 لا توجد سجلات أمان</h3>
                <p>لم يتم العثور على أي سجلات أمان في قاعدة البيانات</p>
            </div>
        <?php else: ?>
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>التاريخ والوقت</th>
                        <th>نوع الحدث</th>
                        <th>عنوان IP</th>
                        <th>تفاصيل الحدث</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td>
                                <span class="event-type <?php echo strtolower($log['event_type']); ?>">
                                    <?php echo htmlspecialchars($log['event_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            <td>
                                <?php 
                                $event_data = json_decode($log['event_data'], true);
                                if ($event_data) {
                                    foreach ($event_data as $key => $value) {
                                        echo "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "<br>";
                                    }
                                } else {
                                    echo htmlspecialchars($log['event_data']);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 