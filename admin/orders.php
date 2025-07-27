<?php
session_start();
require_once '../db.php';
require_once '../client/mailer.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Update order status
    $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
    $success = $stmt->execute([$new_status, $order_id]);
    
    if ($success) {
        // Get order details for email notification
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['customer_email']) {
            // Get order items
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name as product_name, p.image as product_image,
                       COALESCE(s.store_name, 'WeBuy') as seller_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN sellers s ON p.seller_id = s.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Prepare order data for email
            $order_data = [
                'order' => $order,
                'order_items' => array_map(function($item) {
                    return [
                        'product_name' => $item['product_name'],
                        'product_image' => $item['product_image'] ?? '',
                        'quantity' => $item['qty'],
                        'price' => $item['price'],
                        'subtotal' => $item['subtotal'],
                        'seller_name' => $item['seller_name']
                    ];
                }, $order_items)
            ];
            
            // Send order status update email
            send_order_status_update_email($order['customer_email'], $order['customer_name'], $order_data, $new_status);
        }
        
        $_SESSION['success_message'] = "تم تحديث حالة الطلب بنجاح وإرسال إشعار بالبريد الإلكتروني.";
    } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء تحديث حالة الطلب.";
    }
    
    header('Location: orders.php');
    exit();
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total orders count
$stmt = $pdo->query('SELECT COUNT(*) FROM orders');
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get orders with user information
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        SUM(total) as total_revenue
    FROM orders
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

include 'admin_header.php';
?>

<div class="orders-container">
    <h1>📦 إدارة الطلبات</h1>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>إجمالي الطلبات</h3>
            <p><?php echo number_format($stats['total_orders']); ?></p>
        </div>
        <div class="stat-card">
            <h3>الطلبات المعلقة</h3>
            <p><?php echo number_format($stats['pending_orders']); ?></p>
        </div>
        <div class="stat-card">
            <h3>قيد المعالجة</h3>
            <p><?php echo number_format($stats['processing_orders']); ?></p>
        </div>
        <div class="stat-card">
            <h3>تم الشحن</h3>
            <p><?php echo number_format($stats['shipped_orders']); ?></p>
        </div>
        <div class="stat-card">
            <h3>تم التوصيل</h3>
            <p><?php echo number_format($stats['delivered_orders']); ?></p>
        </div>
        <div class="stat-card">
            <h3>إجمالي الإيرادات</h3>
            <p><?php echo number_format($stats['total_revenue'], 2); ?> د.ت</p>
        </div>
    </div>
    
    <!-- Orders List -->
    <div class="orders-grid">
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>لا توجد طلبات حالياً</h3>
                <p>لم يتم إنشاء أي طلبات بعد.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">#<?php echo $order['id']; ?></div>
                        <div class="order-status status-<?php echo $order['status']; ?>">
                            <?php 
                            switch ($order['status']) {
                                case 'pending': echo '⏳ معلق'; break;
                                case 'processing': echo '⚙️ قيد المعالجة'; break;
                                case 'shipped': echo '📦 تم الشحن'; break;
                                case 'delivered': echo '✅ تم التوصيل'; break;
                                case 'cancelled': echo '❌ ملغي'; break;
                                default: echo ucfirst($order['status']);
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">اسم العميل</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">البريد الإلكتروني</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">رقم الهاتف</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">المجموع</div>
                            <div class="detail-value"><?php echo number_format($order['total'], 2); ?> د.ت</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">عدد المنتجات</div>
                            <div class="detail-value"><?php echo $order['item_count']; ?> منتج</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">تاريخ الطلب</div>
                            <div class="detail-value"><?php echo date('Y/m/d H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <button class="btn btn-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                            👁️ عرض التفاصيل
                        </button>
                        <button class="btn btn-secondary" onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                            🔄 تحديث الحالة
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">السابق</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">التالي</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.orders-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 1.5em;
}

.stat-card p {
    margin: 0;
    font-size: 2em;
    font-weight: bold;
}

.orders-grid {
    display: grid;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #00BFAE;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-id {
    font-size: 1.2em;
    font-weight: bold;
    color: #1A237E;
}

.order-status {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #d1ecf1; color: #0c5460; }
.status-shipped { background: #d4edda; color: #155724; }
.status-delivered { background: #d1e7dd; color: #0f5132; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.detail-value {
    font-weight: bold;
    color: #333;
}

.order-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.btn-primary { background: #00BFAE; color: white; }
.btn-primary:hover { background: #1A237E; }

.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #545b62; }
</style>

<script>
function viewOrderDetails(orderId) {
    window.open('../order_confirmation.php?order_id=' + orderId, '_blank');
}

function openStatusModal(orderId, currentStatus) {
    const newStatus = prompt('اختر الحالة الجديدة:\n1. pending - معلق\n2. processing - قيد المعالجة\n3. shipped - تم الشحن\n4. delivered - تم التوصيل\n5. cancelled - ملغي', currentStatus);
    
    if (newStatus && newStatus !== currentStatus) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="new_status" value="${newStatus}">
            <input type="hidden" name="update_status" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'admin_footer.php'; ?> 