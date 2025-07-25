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
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ar = trim($_POST['name_ar']);
    $name_fr = trim($_POST['name_fr']);
    $name_en = trim($_POST['name_en']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);
    $seller_name = trim($_POST['seller_name'] ?? '');
    $seller_story = trim($_POST['seller_story'] ?? '');
    $seller_photo = '';
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('prod_', true) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
    }
    // Add seller_story and seller_photo logic
    if (isset($_FILES['seller_photo']) && $_FILES['seller_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['seller_photo']['name'], PATHINFO_EXTENSION);
        $seller_photo = uniqid('seller_', true) . '.' . $ext;
        move_uploaded_file($_FILES['seller_photo']['tmp_name'], '../uploads/' . $seller_photo);
    }
    $stmt = $pdo->prepare('INSERT INTO products (name, name_ar, name_fr, name_en, description, price, image, stock, category_id, seller_name, seller_story, seller_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name_ar, $name_ar, $name_fr, $name_en, $description, $price, $image, $stock, $category_id, $seller_name, $seller_story, $seller_photo]);
    $product_id = $pdo->lastInsertId();
    // Handle multiple product images
    if (isset($_FILES['images']) && isset($_POST['main_image_idx'])) {
        $main_idx = intval($_POST['main_image_idx']);
        $files = $_FILES['images'];
        $count = min(count($files['name']), 10);
        $main_image_filename = '';
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $img_name = uniqid('prodimg_', true) . '.' . $ext;
                move_uploaded_file($files['tmp_name'][$i], '../uploads/' . $img_name);
                $is_main = ($i === $main_idx) ? 1 : 0;
                if ($is_main) $main_image_filename = $img_name;
                $stmt_img = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)');
                $stmt_img->execute([$product_id, $img_name, $is_main, $i]);
            }
        }
        // Update main image in products table
        if ($main_image_filename) {
            $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$main_image_filename, $product_id]);
        }
    }
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'add_product';
    $details = 'Added product: ' . $name_ar;
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: products.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إضافة منتج جديد</title>
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>إضافة منتج جديد</h2>
        <form method="post" enctype="multipart/form-data">
            <label for="name_ar">اسم المنتج (بالعربية):</label>
            <input type="text" id="name_ar" name="name_ar" required>
            <label for="name_fr">Nom du produit (Français):</label>
            <input type="text" id="name_fr" name="name_fr">
            <label for="name_en">Product Name (English):</label>
            <input type="text" id="name_en" name="name_en">
            <label for="description">الوصف:</label>
            <textarea id="description" name="description" rows="3" required></textarea>
            <label for="price">السعر (د.ت):</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
            <label for="stock">المخزون:</label>
            <input type="number" id="stock" name="stock" min="0" value="0" required>
            <label for="category_id">التصنيف:</label>
            <select id="category_id" name="category_id" required>
                <option value="">اختر تصنيفًا</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="seller_name">اسم البائع (اختياري):</label>
            <input type="text" id="seller_name" name="seller_name">
            <label for="seller_story">قصة البائع (اختياري):</label>
            <textarea id="seller_story" name="seller_story" rows="3"></textarea>
            <label for="seller_photo">صورة البائع (اختياري):</label>
            <input type="file" id="seller_photo" name="seller_photo" accept="image/*">
            <label>صور المنتج (حتى 10 صور):</label>
            <input type="file" name="images[]" accept="image/*" multiple required>
            <div id="mainImageSelector" style="margin-top:10px;"></div>
            <div id="imagePreview" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;"></div>
            <button type="submit">إضافة المنتج</button>
        </form>
        <a href="products.php" class="back-btn">العودة لإدارة المنتجات</a>
    </div>
    <script>
// JS to preview images and select main image
const imagesInput = document.querySelector('input[name="images[]"]');
const mainImageSelector = document.getElementById('mainImageSelector');
const imagePreview = document.getElementById('imagePreview');
if (imagesInput && mainImageSelector) {
  imagesInput.addEventListener('change', function() {
    mainImageSelector.innerHTML = '';
    imagePreview.innerHTML = '';
    const files = Array.from(this.files).slice(0, 10);
    files.forEach((file, idx) => {
      // Main image radio
      const label = document.createElement('label');
      label.style.marginRight = '12px';
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'main_image_idx';
      radio.value = idx;
      if (idx === 0) radio.checked = true;
      label.appendChild(radio);
      label.appendChild(document.createTextNode(' صورة #' + (idx+1)));
      mainImageSelector.appendChild(label);
      // Image preview
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.width = '60px';
        img.style.height = '60px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '8px';
        img.style.border = '1.5px solid #eee';
        imagePreview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  });
}
</script>
</body>
</html> 