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
if (!isset($_GET['id'])) {
    header('Location: categories.php');
    exit();
}
$id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch();
if (!$cat) {
    header('Location: categories.php');
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ar = trim($_POST['name_ar']);
    $name_fr = trim($_POST['name_fr']);
    $name_en = trim($_POST['name_en']);
    $image = $cat['image'];
    $icon = $cat['icon'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('catimg_', true) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
    }
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $icon = uniqid('caticon_', true) . '.' . $ext;
        move_uploaded_file($_FILES['icon']['tmp_name'], '../uploads/' . $icon);
    }
    if ($name_ar) {
        $stmt = $pdo->prepare('UPDATE categories SET name=?, name_ar=?, name_fr=?, name_en=?, image=?, icon=? WHERE id=?');
        $stmt->execute([$name_ar, $name_ar, $name_fr, $name_en, $image, $icon, $id]);
        // Log activity
        $admin_id = $_SESSION['admin_id'];
        $action = 'edit_category';
        $details = 'Edited category: ' . $name_ar . ' (ID: ' . $id . ')';
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
    <title>تعديل التصنيف</title>
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
        .cat-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1.5px solid #eee; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>تعديل التصنيف</h2>
        <?php if ($error): ?><div style="color:red;text-align:center;margin-bottom:10px;"> <?php echo $error; ?> </div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="name_ar">اسم التصنيف (بالعربية):</label>
            <input type="text" id="name_ar" name="name_ar" value="<?php echo htmlspecialchars($cat['name_ar'] ?? $cat['name']); ?>" required>
            <label for="name_fr">Nom de la catégorie (Français):</label>
            <input type="text" id="name_fr" name="name_fr" value="<?php echo htmlspecialchars($cat['name_fr'] ?? ''); ?>">
            <label for="name_en">Category Name (English):</label>
            <input type="text" id="name_en" name="name_en" value="<?php echo htmlspecialchars($cat['name_en'] ?? ''); ?>">
            <label for="image">صورة التصنيف (اختياري):</label>
            <?php if ($cat['image']): ?><img src="../uploads/<?php echo htmlspecialchars($cat['image']); ?>" class="cat-thumb" alt="صورة التصنيف الحالية"><?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <label for="icon">أيقونة التصنيف (PNG 50x50, اختياري):</label>
            <?php if ($cat['icon']): ?><img src="../uploads/<?php echo htmlspecialchars($cat['icon']); ?>" class="cat-thumb" alt="أيقونة التصنيف الحالية"><?php endif; ?>
            <input type="file" id="icon" name="icon" accept="image/png">
            <button type="submit">حفظ التعديلات</button>
        </form>
        <a href="categories.php" class="back-btn">العودة لإدارة التصنيفات</a>
    </div>
</body>
</html> 