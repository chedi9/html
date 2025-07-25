<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require '../db.php';
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
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة الطلبات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .orders-container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .orders-container h2 { text-align: center; margin-bottom: 30px; }
        .order-block { margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .order-block:last-child { border-bottom: none; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .order-status { font-weight: bold; }
        .order-status.pending { color: #c90; }
        .order-status.shipped { color: #09c; }
        .order-status.delivered { color: #228B22; }
        .order-status.cancelled { color: #c00; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .status-form { display: inline-block; }
        .status-form select { padding: 4px 10px; border-radius: 4px; }
        .status-form button { padding: 4px 14px; border-radius: 4px; background: var(--primary-color); color: #fff; border: none; margin-right: 6px; }
        .status-form button:hover { background: var(--secondary-color); }
    </style>
</head>
<body>
    <div class="orders-container">
        <h2>إدارة الطلبات</h2>
        <?php if ($orders): foreach ($orders as $order): ?>
        <div class="order-block">
            <div class="order-header">
                <div>رقم الطلب: <?php echo $order['id']; ?></div>
                <div class="order-status <?php echo htmlspecialchars($order['status']); ?>">
                    <?php
                    switch ($order['status']) {
                        case 'pending': echo 'قيد المعالجة'; break;
                        case 'shipped': echo 'تم الشحن'; break;
                        case 'delivered': echo 'تم التسليم'; break;
                        case 'cancelled': echo 'ملغي'; break;
                        default: echo htmlspecialchars($order['status']);
                    }
                    ?>
                </div>
                <div><?php echo $order['created_at']; ?></div>
                <form class="status-form" method="post"<?php if (!$permissions[$role]['manage_orders']) echo ' style="opacity:0.5;pointer-events:none;"'; ?>>
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <select name="status"<?php if (!$permissions[$role]['manage_orders']) echo ' disabled'; ?>>
                        <option value="pending" <?php if ($order['status']=='pending') echo 'selected'; ?>>قيد المعالجة</option>
                        <option value="shipped" <?php if ($order['status']=='shipped') echo 'selected'; ?>>تم الشحن</option>
                        <option value="delivered" <?php if ($order['status']=='delivered') echo 'selected'; ?>>تم التسليم</option>
                        <option value="cancelled" <?php if ($order['status']=='cancelled') echo 'selected'; ?>>ملغي</option>
                    </select>
                    <button type="submit"<?php if (!$permissions[$role]['manage_orders']) echo ' disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>تحديث</button>
                </form>
            </div>
            <div>العميل: <?php echo htmlspecialchars($order['name']); ?> | الهاتف: <?php echo htmlspecialchars($order['phone']); ?> | البريد: <?php echo htmlspecialchars($order['email']); ?></div>
            <div>العنوان: <?php echo htmlspecialchars($order['address']); ?> | طريقة الدفع: <?php echo htmlspecialchars($order['payment_method']); ?> | الشحن: <?php echo htmlspecialchars($order['shipping_method']); ?></div>
            <div>الإجمالي: <?php echo $order['total']; ?> د.ت</div>
            <table>
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>المجموع الفرعي</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $items->execute([$order['id']]);
                foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo $item['price']; ?> د.ت</td>
                        <td><?php echo $item['subtotal']; ?> د.ت</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; else: ?>
            <p style="text-align:center;">لا توجد طلبات حتى الآن.</p>
        <?php endif; ?>
        <a href="dashboard.php" class="add-btn" style="background:var(--secondary-color);margin-top:30px;">العودة للوحة التحكم</a>
    </div>
</body>
</html> 