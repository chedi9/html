<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'إدارة الطلبات';
$page_subtitle = 'عرض وإدارة طلبات العملاء';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'dashboard.php'],
    ['title' => 'إدارة الطلبات']
];

require '../db.php';
require 'admin_header.php';

// Get current admin details
$stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

$permissions = [
    'superadmin' => [ 'manage_orders' => true ],
    'admin' => [ 'manage_orders' => true ],
    'moderator' => [ 'manage_orders' => false ],
];
$role = $current_admin['role'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'update_order_status';
    $details = 'Updated order ID: ' . intval($_POST['order_id']) . ' to status: ' . htmlspecialchars($_POST['status']);
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
}

$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($orders); ?></div>
                <div class="stat-label">إجمالي الطلبات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'pending'; })); ?></div>
                <div class="stat-label">في الانتظار</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($orders, function($o) { return $o['status'] === 'delivered'; })); ?></div>
                <div class="stat-label">تم التسليم</div>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if ($orders): ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3 class="order-number">طلب #<?php echo $order['id']; ?></h3>
                            <div class="order-date"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                <?php
                                switch ($order['status']) {
                                    case 'pending': echo 'قيد المعالجة'; break;
                                    case 'shipped': echo 'تم الشحن'; break;
                                    case 'delivered': echo 'تم التسليم'; break;
                                    case 'cancelled': echo 'ملغي'; break;
                                    default: echo htmlspecialchars($order['status']);
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="customer-info">
                            <div class="info-item">
                                <span class="info-label">العميل:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">الهاتف:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">البريد:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                        </div>
                        
                        <div class="order-summary">
                            <div class="info-item">
                                <span class="info-label">المبلغ الإجمالي:</span>
                                <span class="info-value total-amount"><?php echo htmlspecialchars($order['total_amount']); ?> د.ت</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">طريقة الدفع:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <form class="status-update-form" method="post" <?php if (!$permissions[$role]['manage_orders']) echo ' style="opacity:0.5;pointer-events:none;"'; ?>>
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="status-select" <?php if (!$permissions[$role]['manage_orders']) echo ' disabled'; ?>>
                                <option value="pending" <?php if ($order['status']=='pending') echo 'selected'; ?>>قيد المعالجة</option>
                                <option value="shipped" <?php if ($order['status']=='shipped') echo 'selected'; ?>>تم الشحن</option>
                                <option value="delivered" <?php if ($order['status']=='delivered') echo 'selected'; ?>>تم التسليم</option>
                                <option value="cancelled" <?php if ($order['status']=='cancelled') echo 'selected'; ?>>ملغي</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm" <?php if (!$permissions[$role]['manage_orders']) echo ' disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>
                                <span class="btn-icon">💾</span>
                                تحديث
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>لا توجد طلبات</h3>
                <p>لم يتم تقديم أي طلبات بعد.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.header-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

.orders-grid {
    display: grid;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.order-number {
    margin: 0;
    color: #333;
    font-size: 1.2em;
}

.order-date {
    color: #666;
    font-size: 0.9em;
    margin-top: 4px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
}

.status-pending {
    background: #fff3e0;
    color: #ef6c00;
}

.status-shipped {
    background: #e3f2fd;
    color: #1976d2;
}

.status-delivered {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-cancelled {
    background: #ffebee;
    color: #c62828;
}

.order-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.customer-info, .order-summary {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-label {
    font-weight: 600;
    color: #555;
}

.info-value {
    color: #333;
}

.total-amount {
    font-weight: bold;
    color: #2e7d32;
    font-size: 1.1em;
}

.order-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.status-update-form {
    display: flex;
    gap: 8px;
    align-items: center;
}

.status-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    font-size: 0.9em;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    margin-bottom: 8px;
    color: #333;
}

.empty-state p {
    margin-bottom: 24px;
    color: #666;
}

@media (max-width: 768px) {
    .order-details {
        grid-template-columns: 1fr;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .order-actions {
        justify-content: center;
    }
    
    .status-update-form {
        flex-direction: column;
        width: 100%;
    }
}
</style>

<?php require 'admin_footer.php'; ?> 