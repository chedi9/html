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

$page_title = 'إدارة المنتجات';
$page_subtitle = 'عرض، تعديل، وحذف المنتجات في المتجر';
$breadcrumb = [
            ['title' => 'الرئيسية', 'url' => 'unified_dashboard.php'],
    ['title' => 'إدارة المنتجات']
];

require '../db.php';
require 'admin_header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'delete_product';
    $details = 'Deleted product ID: ' . $id;
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: products.php');
    exit();
}

if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $stmt = $pdo->prepare('UPDATE products SET approved = 1 WHERE id = ?');
    $stmt->execute([$id]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'approve_product';
    $details = 'Approved product ID: ' . $id;
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: products.php');
    exit();
}

$products = $pdo->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll();

// Get current admin details
$stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

$permissions = [
    'superadmin' => [ 'manage_products' => true ],
    'admin' => [ 'manage_products' => true ],
    'moderator' => [ 'manage_products' => false ],
];
$role = $current_admin['role'];
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-actions">
            <a href="add_product.php" class="btn btn-primary" <?php if (!$permissions[$role]['manage_products']) echo ' style="opacity:0.5;pointer-events:none;" tabindex="-1"'; ?>>
                <span class="btn-icon">➕</span>
                إضافة منتج جديد
            </a>
        </div>
    </div>

    <div class="content-body">
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th>السعر</th>
                        <th>المخزون</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                    <tr>
                        <td>
                            <?php if ($prod['image']): ?>
                                <?php 
                                $image_path = "../uploads/" . htmlspecialchars($prod['image']);
                                $thumb_path = "../uploads/thumbnails/" . pathinfo($prod['image'], PATHINFO_FILENAME) . "_thumb.jpg";
                                $final_image = file_exists($thumb_path) ? $thumb_path : $image_path;
                                ?>
                                <img src="<?php echo $final_image; ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td class="description-cell">
                            <?php echo htmlspecialchars(substr($prod['description'], 0, 100)) . (strlen($prod['description']) > 100 ? '...' : ''); ?>
                        </td>
                        <td><?php echo htmlspecialchars($prod['price']); ?> د.ت</td>
                        <td>
                            <span class="stock-badge <?php echo $prod['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo $prod['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $prod['approved'] ? 'approved' : 'pending'; ?>">
                                <?php echo $prod['approved'] ? 'موافق عليه' : 'في الانتظار'; ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <div class="action-buttons">
                                <a href="edit_product.php?id=<?php echo $prod['id']; ?>" 
                                   class="btn btn-warning btn-sm" 
                                   title="تعديل">
                                    <span class="btn-icon">✏️</span>
                                </a>
                                
                                <?php if (!$prod['approved']): ?>
                                <a href="?approve=<?php echo $prod['id']; ?>" 
                                   class="btn btn-success btn-sm" 
                                   title="موافقة"
                                   onclick="return confirm('هل أنت متأكد من الموافقة على هذا المنتج؟')">
                                    <span class="btn-icon">✅</span>
                                </a>
                                <?php endif; ?>
                                
                                <a href="?delete=<?php echo $prod['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="حذف"
                                   onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                    <span class="btn-icon">🗑️</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>لا توجد منتجات</h3>
                <p>لم يتم إضافة أي منتجات بعد. ابدأ بإضافة منتج جديد.</p>
                <a href="add_product.php" class="btn btn-primary">إضافة منتج جديد</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.product-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.no-image {
    width: 60px;
    height: 60px;
    background: #f5f5f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #999;
    border: 2px dashed #ddd;
}

.description-cell {
    max-width: 200px;
    word-wrap: break-word;
}

.stock-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.stock-badge.in-stock {
    background: #e8f5e8;
    color: #2e7d32;
}

.stock-badge.out-of-stock {
    background: #ffebee;
    color: #c62828;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.approved {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-badge.pending {
    background: #fff3e0;
    color: #ef6c00;
}

.actions-cell {
    min-width: 120px;
}

.action-buttons {
    display: flex;
    gap: 4px;
    justify-content: center;
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
</style>

<?php require 'admin_footer.php'; ?> 