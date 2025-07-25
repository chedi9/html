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
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المنتجات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .products-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .products-container h2 { text-align: center; margin-bottom: 30px; }
        .add-btn { background: var(--primary-color); color: #fff; padding: 10px 24px; border-radius: 5px; text-decoration: none; font-size: 1em; margin-bottom: 20px; display: inline-block; transition: background 0.2s, color 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1.5px solid var(--primary-color); }
        .add-btn:hover { background: var(--secondary-color); color: #fff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .action-btn { background: #c00; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none; font-size: 0.95em; margin: 0 4px; border: 1.5px solid #a00; box-shadow: 0 1px 4px rgba(0,0,0,0.07); transition: background 0.2s, color 0.2s; }
        .action-btn:hover { background: #a00; color: #fff; }
        .edit-btn { background: #1A237E; color: #fff; border: 1.5px solid #FFD600; box-shadow: 0 2px 8px rgba(26,35,126,0.10); }
        .edit-btn:hover { background: #FFD600; color: #1A237E; border: 1.5px solid #1A237E; }
    </style>
</head>
<body>
    <div class="products-container">
        <h2>إدارة المنتجات</h2>
        <a href="add_product.php" class="add-btn"<?php if (!$permissions[$role]['manage_products']) echo ' style="opacity:0.5;pointer-events:none;" tabindex="-1"'; ?>>إضافة منتج جديد</a>
        <table>
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>الاسم</th>
                    <th>الوصف</th>
                    <th>السعر</th>
                    <th>المخزون</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php if ($product['image']): ?><img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="صورة المنتج" style="width:60px; height:60px; object-fit:cover; border-radius:6px; "><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                    <td><?php echo htmlspecialchars($product['price']); ?> د.ت</td>
                    <td><?php echo htmlspecialchars($product['stock']); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn"<?php if (!$permissions[$role]['manage_products']) echo ' style="opacity:0.5;pointer-events:none;" tabindex="-1"'; ?>>تعديل</a>
                        <a href="products.php?delete=<?php echo $product['id']; ?>" class="action-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟');"<?php if (!$permissions[$role]['manage_products']) echo ' style="opacity:0.5;pointer-events:none;" tabindex="-1"'; ?>>حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="add-btn" style="background:var(--secondary-color);margin-top:30px; color:#fff; border:1.5px solid #FFD600; box-shadow:0 2px 8px rgba(0,191,174,0.10);">العودة للوحة التحكم</a>
    </div>
</body>
</html> 