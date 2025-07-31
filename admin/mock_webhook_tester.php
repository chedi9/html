<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

session_start();
require_once '../db.php';
require_once '../includes/mock_delivery_webhook.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$mock_webhook = new MockDeliveryWebhook($pdo);
$success_messages = [];
$error_messages = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['simulate_progress'])) {
        $order_id = $_POST['order_id'] ?? '';
        $result = $mock_webhook->simulateDeliveryProgress($order_id);
        
        if ($result['status'] === 'success') {
            $success_messages[] = "Delivery progress simulated: " . $result['order_status'];
        } else {
            $error_messages[] = "Failed to simulate progress: " . $result['message'];
        }
    }
    
    if (isset($_POST['set_status'])) {
        $order_id = $_POST['order_id'] ?? '';
        $status = $_POST['delivery_status'] ?? '';
        $runner_id = $_POST['runner_id'] ?? null;
        
        $payload = $mock_webhook->generateMockWebhook($order_id, $status, $runner_id);
        $result = $mock_webhook->processMockWebhook($payload);
        
        if ($result['status'] === 'success') {
            $success_messages[] = "Status updated: " . $status;
        } else {
            $error_messages[] = "Failed to update status: " . $result['message'];
        }
    }
    
    if (isset($_POST['simulate_complete_delivery'])) {
        $order_id = $_POST['order_id'] ?? '';
        $statuses = ['pending_assign', 'runner_assigned', 'en_route_pickup', 'arrived_pickup', 'picked_up', 'en_route_dropoff', 'arrived_dropoff', 'completed'];
        
        foreach ($statuses as $status) {
            $payload = $mock_webhook->generateMockWebhook($order_id, $status);
            $mock_webhook->processMockWebhook($payload);
            sleep(1); // Small delay between updates
        }
        
        $success_messages[] = "Complete delivery simulation finished";
    }
}

// Get orders with First Delivery
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.delivery_company = 'first_delivery' 
    ORDER BY o.created_at DESC 
    LIMIT 20
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get delivery statuses and runners
$delivery_statuses = $mock_webhook->getDeliveryStatuses();
$mock_runners = $mock_webhook->getMockRunners();

// Get webhook logs
$stmt = $pdo->prepare("
    SELECT * FROM delivery_webhook_logs 
    WHERE delivery_company = 'first_delivery' 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute();
$webhook_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure no output is sent before headers
ob_start();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>اختبار Webhook الوهمي - لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .mock-tester-container {
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
        
        .section {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        .section h3 {
            margin: 0 0 25px 0;
            color: #2c3e50;
            font-size: 1.3em;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 15px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .order-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .order-card h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .order-info {
            margin-bottom: 15px;
        }
        
        .order-info p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            margin: 5px 0;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-assigned {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-pickup {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivery {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .logs-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .log-entry {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        
        .log-entry .timestamp {
            color: #6c757d;
            font-size: 0.8em;
        }
        
        .log-entry .status {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="mock-tester-container">
        <div class="dashboard-header">
            <h2>🧪 اختبار Webhook الوهمي</h2>
            <p class="dashboard-subtitle">محاكاة تحديثات حالة التوصيل للاختبار</p>
        </div>
        
        <?php if (!empty($success_messages)): ?>
            <?php foreach ($success_messages as $message): ?>
                <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (!empty($error_messages)): ?>
            <?php foreach ($error_messages as $message): ?>
                <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Quick Actions Section -->
        <div class="section">
            <h3>⚡ إجراءات سريعة</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="quick_order_id">رقم الطلب</label>
                    <input type="number" name="order_id" id="quick_order_id" placeholder="أدخل رقم الطلب">
                </div>
                
                <div class="form-group">
                    <label for="quick_status">حالة التوصيل</label>
                    <select name="delivery_status" id="quick_status">
                        <option value="">اختر الحالة</option>
                        <?php foreach ($delivery_statuses as $status => $arabic_name): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>">
                                <?php echo htmlspecialchars($arabic_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quick_runner">السائق</label>
                    <select name="runner_id" id="quick_runner">
                        <option value="">اختر السائق</option>
                        <?php foreach ($mock_runners as $runner): ?>
                            <option value="<?php echo htmlspecialchars($runner['id']); ?>">
                                <?php echo htmlspecialchars($runner['name']); ?> 
                                (<?php echo $runner['transport_type'] === 'car' ? 'سيارة' : 'دراجة نارية'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="set_status" class="btn btn-success">تحديث الحالة</button>
            <button type="submit" name="simulate_progress" class="btn btn-info">محاكاة التقدم</button>
            <button type="submit" name="simulate_complete_delivery" class="btn btn-warning">محاكاة التوصيل الكامل</button>
        </div>
        
        <!-- Orders Section -->
        <div class="section">
            <h3>📦 الطلبات مع First Delivery</h3>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <h4>طلب #<?php echo htmlspecialchars($order['id']); ?></h4>
                        <div class="order-info">
                            <p><strong>العميل:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>التاريخ:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                            <p><strong>التكلفة:</strong> <?php echo htmlspecialchars($order['delivery_cost'] ?? 'غير محدد'); ?> د.ت</p>
                        </div>
                        
                        <?php 
                        $status_class = 'status-pending';
                        if (strpos($order['delivery_status'], 'completed') !== false) $status_class = 'status-completed';
                        elseif (strpos($order['delivery_status'], 'pickup') !== false) $status_class = 'status-pickup';
                        elseif (strpos($order['delivery_status'], 'dropoff') !== false) $status_class = 'status-delivery';
                        elseif (strpos($order['delivery_status'], 'assigned') !== false) $status_class = 'status-assigned';
                        ?>
                        
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($mock_webhook->getStatusInArabic($order['delivery_status'] ?? 'pending_assign')); ?>
                        </span>
                        
                        <form method="post" style="margin-top: 15px;">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                            <button type="submit" name="simulate_progress" class="btn btn-info">محاكاة التقدم</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Webhook Logs Section -->
        <div class="section">
            <h3>📋 سجلات Webhook</h3>
            <div class="logs-container">
                <?php foreach ($webhook_logs as $log): ?>
                    <div class="log-entry">
                        <div class="timestamp">
                            <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                        </div>
                        <div class="status">
                            طلب #<?php echo htmlspecialchars($log['order_id']); ?> - 
                            <?php echo htmlspecialchars($mock_webhook->getStatusInArabic($log['status'])); ?>
                        </div>
                        <div style="font-size: 0.8em; color: #6c757d;">
                            نوع: <?php echo htmlspecialchars($log['webhook_type']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html> 