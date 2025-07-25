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
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$users = $pdo->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}
$id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: products.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);
    $seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
    $image = $product['image'];
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('prod_', true) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
    }
    $stmt = $pdo->prepare('UPDATE products SET name=?, description=?, price=?, image=?, stock=?, category_id=?, seller_id=? WHERE id=?');
    $stmt->execute([$name, $description, $price, $image, $stock, $category_id, $seller_id, $id]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'edit_product';
    $details = 'Edited product: ' . $name . ' (ID: ' . $id . ')';
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: products.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل المنتج</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-container h2 { text-align: center; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; }
        input, textarea, select { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; }
        .back-btn { display: block; margin: 20px auto 0; background: var(--secondary-color); text-align: center; text-decoration: none; color: #fff; padding: 10px 24px; border-radius: 5px; }
        .back-btn:hover { background: var(--primary-color); }
        .current-img { display: block; margin-bottom: 10px; max-width: 120px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>تعديل المنتج</h2>
        <form method="post" enctype="multipart/form-data">
            <label for="name">اسم المنتج:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            <label for="description">الوصف:</label>
            <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            <label for="price">السعر (د.ت):</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            <label for="stock">المخزون:</label>
            <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
            <label for="category_id">التصنيف:</label>
            <select id="category_id" name="category_id" required>
                <option value="">اختر تصنيفًا</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($product['category_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="seller_id">البائع:</label>
            <select id="seller_id" name="seller_id">
                <option value="">اختر بائعًا</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php if ($product['seller_id'] == $user['id']) echo 'selected'; ?>><?php echo htmlspecialchars($user['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="image">صورة المنتج (اختياري):</label>
            <?php if ($product['image']): ?>
                <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="صورة المنتج الحالية" class="current-img">
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <button type="submit">حفظ التعديلات</button>
        </form>
        <a href="products.php" class="back-btn">العودة لإدارة المنتجات</a>
    </div>
</body>
</html> 