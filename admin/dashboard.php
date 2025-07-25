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
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .dashboard-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .dashboard-container h2 { text-align: center; margin-bottom: 30px; }
        .dashboard-nav { display: flex; justify-content: center; gap: 30px; margin-bottom: 30px; }
        .dashboard-nav a { background: var(--primary-color); color: #fff; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-size: 1.1em; transition: background 0.2s; }
        .dashboard-nav a:hover { background: var(--secondary-color); }
        .logout-btn { float: left; background: #c00; color: #fff; padding: 8px 18px; border-radius: 5px; text-decoration: none; margin-bottom: 20px; }
        .logout-btn:hover { background: #a00; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
        <h2>لوحة تحكم المشرف</h2>
        <nav class="dashboard-nav">
            <a href="products.php">إدارة المنتجات</a>
            <a href="orders.php">إدارة الطلبات</a>
            <a href="reviews.php">إدارة المراجعات</a>
            <a href="categories.php">إدارة التصنيفات</a>
            <a href="admins.php">إدارة المشرفين</a>
            <a href="activity.php">سجل الأنشطة</a>
        </nav>
        <p style="text-align:center;">مرحبًا بك في لوحة التحكم. اختر إجراء من الأعلى.</p>
    </div>
</body>
</html> 