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
require '../db.php';
require_once '../client/make_thumbnail.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ar = trim($_POST['name_ar']);
    $name_fr = trim($_POST['name_fr']);
    $name_en = trim($_POST['name_en']);
    $image = '';
    $icon = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('cat_', true) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
        
        // Generate thumbnail
        $thumb_dir = '../uploads/thumbnails/';
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
        $thumb_path = $thumb_dir . pathinfo($image, PATHINFO_FILENAME) . '_thumb.jpg';
        make_thumbnail('../uploads/' . $image, $thumb_path, 300, 300);
    }
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $icon = uniqid('icon_', true) . '.' . $ext;
        move_uploaded_file($_FILES['icon']['tmp_name'], '../uploads/' . $icon);
        
        // Generate thumbnail for icon
        $thumb_dir = '../uploads/thumbnails/';
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
        $thumb_path = $thumb_dir . pathinfo($icon, PATHINFO_FILENAME) . '_thumb.jpg';
        make_thumbnail('../uploads/' . $icon, $thumb_path, 150, 150);
    }
    if ($name_ar) {
        $stmt = $pdo->prepare('INSERT INTO categories (name, name_ar, name_fr, name_en, image, icon) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name_ar, $name_ar, $name_fr, $name_en, $image, $icon]);
        $cat_id = $pdo->lastInsertId();
        // Log activity
        $admin_id = $_SESSION['admin_id'];
        $action = 'add_category';
        $details = 'Added category: ' . $name_ar . ' (ID: ' . $cat_id . ')';
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
        header('Location: categories.php');
        exit();
    } else {
        $error = 'يرجى إدخال اسم التصنيف بالعربية';
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إضافة تصنيف جديد</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-container h2 { text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; }
        .back-btn { display: block; margin: 20px auto 0; background: var(--secondary-color); text-align: center; text-decoration: none; color: #fff; padding: 10px 24px; border-radius: 5px; }
        .back-btn:hover { background: var(--primary-color); }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>إضافة تصنيف جديد</h2>
        <?php if ($error): ?><div style="color:red;text-align:center;margin-bottom:10px;"> <?php echo $error; ?> </div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="name_ar">اسم التصنيف (بالعربية):</label>
            <input type="text" id="name_ar" name="name_ar" required>
            <label for="name_fr">Nom de la catégorie (Français):</label>
            <input type="text" id="name_fr" name="name_fr">
            <label for="name_en">Category Name (English):</label>
            <input type="text" id="name_en" name="name_en">
            <label for="image">صورة التصنيف (اختياري):</label>
            <input type="file" id="image" name="image" accept="image/*">
            <label for="icon">أيقونة التصنيف (PNG 50x50, اختياري):</label>
            <input type="file" id="icon" name="icon" accept="image/png">
            <button type="submit">إضافة التصنيف</button>
        </form>
        <a href="categories.php" class="back-btn">العودة لإدارة التصنيفات</a>
    </div>
</body>
</html> 