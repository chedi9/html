<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

session_start();
require_once '../db.php';
require_once '../includes/first_delivery_api.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get delivery settings
$delivery_settings = [];
$stmt = $pdo->query("SELECT * FROM delivery_settings WHERE delivery_company = 'first_delivery'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $delivery_settings[$row['setting_key']] = $row['setting_value'];
}

// Initialize First Delivery API
$api = new FirstDeliveryAPI(
    $delivery_settings['api_key'] ?? '',
    $delivery_settings['merchant_id'] ?? '',
    $delivery_settings['webhook_secret'] ?? '',
    $delivery_settings['mode'] ?? 'sandbox'
);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success_messages = [];
    $error_messages = [];
    
    if (isset($_POST['create_runner'])) {
        $runner_data = [
            'name' => $_POST['runner_name'] ?? '',
            'email' => $_POST['runner_email'] ?? '',
            'phone' => $_POST['runner_phone'] ?? '',
            'address' => $_POST['runner_address'] ?? '',
            'territory_id' => $_POST['territory_id'] ?? '',
            'transport_type' => $_POST['transport_type'] ?? 'car',
            'profile_pic' => $_POST['profile_pic'] ?? null
        ];
        
        $result = $api->createRunner($runner_data);
        
        if ($result['status'] === 'success') {
            $success_messages[] = "Runner created successfully: " . $result['runner_name'];
        } else {
            $error_messages[] = "Failed to create runner: " . $result['message'];
        }
    }
    
    if (isset($_POST['archive_runner'])) {
        $runner_id = $_POST['runner_id'] ?? '';
        $result = $api->archiveRunner($runner_id);
        
        if ($result['status'] === 'success') {
            $success_messages[] = "Runner archived successfully";
        } else {
            $error_messages[] = "Failed to archive runner: " . $result['message'];
        }
    }
    
    if (isset($_POST['assign_runner'])) {
        $order_id = $_POST['order_id'] ?? '';
        $runner_id = $_POST['assign_runner_id'] ?? '';
        $result = $api->assignRunnerToOrder($order_id, $runner_id);
        
        if ($result['status'] === 'success') {
            $success_messages[] = "Runner assigned to order successfully";
        } else {
            $error_messages[] = "Failed to assign runner: " . $result['message'];
        }
    }
}

// Get territories for dropdown
$territories_result = $api->getTerritories();
$territories = $territories_result['status'] === 'success' ? $territories_result['territories'] : [];

// Get runners
$runners_result = $api->getRunners();
$runners = $runners_result['status'] === 'success' ? $runners_result['runners'] : [];

// Get orders that need runners
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.delivery_company = 'first_delivery' 
    AND o.delivery_status IN ('pending_assign', 'pending_merchant')
    ORDER BY o.created_at DESC
");
$stmt->execute();
$pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure no output is sent before headers
ob_start();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة السائقين - لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .runner-management-container {
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
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .runners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .runner-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .runner-card h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .runner-info {
            margin-bottom: 15px;
        }
        
        .runner-info p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .runner-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-archived {
            background: #f8d7da;
            color: #721c24;
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
    <div class="runner-management-container">
        <div class="dashboard-header">
            <h2>🚚 إدارة السائقين</h2>
            <p class="dashboard-subtitle">إدارة سائقي التوصيل وتوزيع الطلبات</p>
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
        
        <!-- Create Runner Section -->
        <div class="section">
            <h3>➕ إضافة سائق جديد</h3>
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="runner_name">اسم السائق</label>
                        <input type="text" name="runner_name" id="runner_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="runner_email">البريد الإلكتروني</label>
                        <input type="email" name="runner_email" id="runner_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="runner_phone">رقم الهاتف</label>
                        <input type="tel" name="runner_phone" id="runner_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="runner_address">العنوان</label>
                        <input type="text" name="runner_address" id="runner_address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="territory_id">المنطقة</label>
                        <select name="territory_id" id="territory_id" required>
                            <option value="">اختر المنطقة</option>
                            <?php foreach ($territories as $territory): ?>
                                <option value="<?php echo htmlspecialchars($territory['id']); ?>">
                                    <?php echo htmlspecialchars($territory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="transport_type">نوع النقل</label>
                        <select name="transport_type" id="transport_type">
                            <option value="car">سيارة</option>
                            <option value="bike">دراجة نارية</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="create_runner" class="btn btn-success">إضافة السائق</button>
            </form>
        </div>
        
        <!-- Runners List Section -->
        <div class="section">
            <h3>👥 قائمة السائقين</h3>
            <div class="runners-grid">
                <?php foreach ($runners as $runner): ?>
                    <div class="runner-card">
                        <h4><?php echo htmlspecialchars($runner['name']); ?></h4>
                        <div class="runner-info">
                            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($runner['email']); ?></p>
                            <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($runner['phone']); ?></p>
                            <p><strong>العنوان:</strong> <?php echo htmlspecialchars($runner['address']); ?></p>
                            <p><strong>نوع النقل:</strong> <?php echo $runner['transport_type'] === 'car' ? 'سيارة' : 'دراجة نارية'; ?></p>
                        </div>
                        
                        <span class="runner-status <?php echo $runner['archived'] ? 'status-archived' : 'status-active'; ?>">
                            <?php echo $runner['archived'] ? 'مؤرشف' : 'نشط'; ?>
                        </span>
                        
                        <?php if (!$runner['archived']): ?>
                            <form method="post" style="margin-top: 15px;">
                                <input type="hidden" name="runner_id" value="<?php echo htmlspecialchars($runner['id']); ?>">
                                <button type="submit" name="archive_runner" class="btn btn-warning">أرشفة السائق</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Assign Runners to Orders Section -->
        <div class="section">
            <h3>📦 تعيين السائقين للطلبات</h3>
            <div class="orders-grid">
                <?php foreach ($pending_orders as $order): ?>
                    <div class="order-card">
                        <h4>طلب #<?php echo htmlspecialchars($order['id']); ?></h4>
                        <div class="order-info">
                            <p><strong>العميل:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>الحالة:</strong> <?php echo htmlspecialchars($order['delivery_status']); ?></p>
                            <p><strong>التاريخ:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        
                        <form method="post">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                            <div class="form-group">
                                <label for="assign_runner_id_<?php echo $order['id']; ?>">اختر السائق</label>
                                <select name="assign_runner_id" id="assign_runner_id_<?php echo $order['id']; ?>" required>
                                    <option value="">اختر السائق</option>
                                    <?php foreach ($runners as $runner): ?>
                                        <?php if (!$runner['archived']): ?>
                                            <option value="<?php echo htmlspecialchars($runner['id']); ?>">
                                                <?php echo htmlspecialchars($runner['name']); ?> 
                                                (<?php echo $runner['transport_type'] === 'car' ? 'سيارة' : 'دراجة نارية'; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="assign_runner" class="btn btn-success">تعيين السائق</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html> 