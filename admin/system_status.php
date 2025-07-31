<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

session_start();
require_once '../db.php';
require_once '../includes/system_status_monitor.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize status monitor
$status_monitor = new SystemStatusMonitor($pdo);
$overall_status = $status_monitor->getOverallStatus();
$status_results = $status_monitor->getStatusResults();

// Get status counts for summary
$status_counts = [
    'online' => 0,
    'warning' => 0,
    'offline' => 0,
    'error' => 0
];

foreach ($status_results as $service => $status) {
    $status_counts[$status['status']]++;
}

// Ensure no output is sent before headers
ob_start();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>حالة النظام - لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .status-dashboard-container {
            max-width: 1400px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .dashboard-header h2 {
            font-size: 2.2em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-subtitle {
            font-size: 1.1em;
            color: #7f8c8d;
            margin: 0;
        }
        
        .overall-status {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 30px 0;
            padding: 20px;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .overall-status.healthy {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
        }
        
        .overall-status.warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
        }
        
        .overall-status.critical {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 2px solid #dc3545;
        }
        
        .status-icon {
            font-size: 3em;
        }
        
        .status-text {
            font-size: 1.5em;
            font-weight: 700;
        }
        
        .status-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .summary-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 2em;
            font-weight: 700;
        }
        
        .summary-card p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .summary-online { color: #28a745; }
        .summary-warning { color: #ffc107; }
        .summary-offline { color: #dc3545; }
        .summary-error { color: #6f42c1; }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .service-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .service-card.online {
            border-left: 5px solid #28a745;
        }
        
        .service-card.warning {
            border-left: 5px solid #ffc107;
        }
        
        .service-card.offline {
            border-left: 5px solid #dc3545;
        }
        
        .service-card.error {
            border-left: 5px solid #6f42c1;
        }
        
        .service-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .service-icon {
            font-size: 2em;
        }
        
        .service-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .service-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-online {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-offline {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-error {
            background: #e2d9f3;
            color: #6f42c1;
        }
        
        .service-message {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .service-details {
            color: #6c757d;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 20px 0;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .last-updated {
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="status-dashboard-container">
        <div class="dashboard-header">
            <h2>📊 حالة النظام</h2>
            <p class="dashboard-subtitle">مراقبة صحة جميع الخدمات الحيوية في الموقع</p>
        </div>
        
        <!-- Overall Status -->
        <div class="overall-status <?php echo $overall_status; ?>">
            <div class="status-icon">
                <?php 
                switch($overall_status) {
                    case 'healthy': echo '✅'; break;
                    case 'warning': echo '⚠️'; break;
                    case 'critical': echo '🚨'; break;
                    default: echo '❓'; break;
                }
                ?>
            </div>
            <div class="status-text">
                <?php 
                switch($overall_status) {
                    case 'healthy': echo 'النظام يعمل بشكل طبيعي'; break;
                    case 'warning': echo 'النظام يحتاج انتباه'; break;
                    case 'critical': echo 'النظام يحتاج تدخل فوري'; break;
                    default: echo 'حالة غير معروفة'; break;
                }
                ?>
            </div>
        </div>
        
        <!-- Status Summary -->
        <div class="status-summary">
            <div class="summary-card">
                <h3 class="summary-online"><?php echo $status_counts['online']; ?></h3>
                <p>خدمات تعمل</p>
            </div>
            <div class="summary-card">
                <h3 class="summary-warning"><?php echo $status_counts['warning']; ?></h3>
                <p>تحتاج انتباه</p>
            </div>
            <div class="summary-card">
                <h3 class="summary-offline"><?php echo $status_counts['offline']; ?></h3>
                <p>غير متاحة</p>
            </div>
            <div class="summary-card">
                <h3 class="summary-error"><?php echo $status_counts['error']; ?></h3>
                <p>أخطاء</p>
            </div>
        </div>
        
        <!-- Refresh Button -->
        <div style="text-align: center;">
            <button onclick="location.reload()" class="refresh-btn">
                🔄 تحديث الحالة
            </button>
        </div>
        
        <!-- Services Grid -->
        <div class="services-grid">
            <?php foreach ($status_results as $service => $status): ?>
                <div class="service-card <?php echo $status['status']; ?>">
                    <div class="service-header">
                        <div class="service-icon">
                            <?php 
                            switch($service) {
                                case 'database': echo '🗄️'; break;
                                case 'payment_systems': echo '💳'; break;
                                case 'delivery_systems': echo '🚚'; break;
                                case 'security_systems': echo '🔒'; break;
                                case 'email_system': echo '📧'; break;
                                case 'file_system': echo '📁'; break;
                                case 'api_services': echo '🔌'; break;
                                case 'performance': echo '⚡'; break;
                                default: echo '❓'; break;
                            }
                            ?>
                        </div>
                        <div>
                            <div class="service-title">
                                <?php 
                                switch($service) {
                                    case 'database': echo 'قاعدة البيانات'; break;
                                    case 'payment_systems': echo 'أنظمة الدفع'; break;
                                    case 'delivery_systems': echo 'أنظمة التوصيل'; break;
                                    case 'security_systems': echo 'أنظمة الأمان'; break;
                                    case 'email_system': echo 'نظام البريد الإلكتروني'; break;
                                    case 'file_system': echo 'نظام الملفات'; break;
                                    case 'api_services': echo 'خدمات API'; break;
                                    case 'performance': echo 'الأداء'; break;
                                    default: echo ucfirst(str_replace('_', ' ', $service)); break;
                                }
                                ?>
                            </div>
                            <span class="service-status status-<?php echo $status['status']; ?>">
                                <?php 
                                switch($status['status']) {
                                    case 'online': echo 'متاح'; break;
                                    case 'warning': echo 'تحذير'; break;
                                    case 'offline': echo 'غير متاح'; break;
                                    case 'error': echo 'خطأ'; break;
                                    default: echo $status['status']; break;
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="service-message">
                        <?php echo htmlspecialchars($status['message']); ?>
                    </div>
                    
                    <div class="service-details">
                        <?php echo htmlspecialchars($status['details']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="last-updated">
            آخر تحديث: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html> 