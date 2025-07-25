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
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'delete_category';
    $details = 'Deleted category ID: ' . $id;
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: categories.php');
    exit();
}
$categories = $pdo->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة التصنيفات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .categories-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .categories-container h2 { text-align: center; margin-bottom: 30px; }
        .add-btn { background: var(--primary-color); color: #fff; padding: 10px 24px; border-radius: 5px; text-decoration: none; font-size: 1em; margin-bottom: 20px; display: inline-block; }
        .add-btn:hover { background: var(--secondary-color); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .action-btn { background: #c00; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none; font-size: 0.95em; margin: 0 4px; }
        .action-btn:hover { background: #a00; }
        .edit-btn { background: var(--secondary-color); color: #fff; }
        .edit-btn:hover { background: var(--primary-color); }
        .cat-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1.5px solid #eee; }
    </style>
</head>
<body>
    <div class="categories-container">
        <h2>إدارة التصنيفات</h2>
        <a href="add_category.php" class="add-btn">إضافة تصنيف جديد</a>
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الصورة</th>
                    <th>الأيقونة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td><?php if ($cat['image']): ?><img src="../uploads/<?php echo htmlspecialchars($cat['image']); ?>" class="cat-thumb" alt="صورة التصنيف"><?php endif; ?></td>
                    <td><?php if ($cat['icon']): ?><img src="../uploads/<?php echo htmlspecialchars($cat['icon']); ?>" class="cat-thumb" alt="أيقونة التصنيف"><?php endif; ?></td>
                    <td>
                        <a href="edit_category.php?id=<?php echo $cat['id']; ?>" class="action-btn edit-btn">تعديل</a>
                        <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="action-btn" onclick="return confirm('هل أنت متأكد من حذف هذا التصنيف؟');">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="add-btn" style="background:var(--secondary-color);margin-top:30px;">العودة للوحة التحكم</a>
    </div>
</body>
</html> 