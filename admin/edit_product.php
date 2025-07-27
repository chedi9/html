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
// Fetch all images for this product
$stmt_imgs = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC, id ASC');
$stmt_imgs->execute([$id]);
$product_images = $stmt_imgs->fetchAll();
// Handle delete image
if (isset($_GET['delete_img'])) {
    $img_id = intval($_GET['delete_img']);
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE id = ? AND product_id = ?');
    $stmt->execute([$img_id, $id]);
    $img = $stmt->fetch();
    if ($img) {
        // Remove file
        @unlink('../uploads/' . $img['image_path']);
        // Delete from DB
        $pdo->prepare('DELETE FROM product_images WHERE id = ?')->execute([$img_id]);
        // If deleted image was main, set another as main
        if ($img['is_main']) {
            $stmt2 = $pdo->prepare('SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1');
            $stmt2->execute([$id]);
            $new_main = $stmt2->fetch();
            if ($new_main) {
                $pdo->prepare('UPDATE product_images SET is_main = 1 WHERE id = ?')->execute([$new_main['id']]);
                $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$new_main['image_path'], $id]);
            } else {
                $pdo->prepare('UPDATE products SET image = NULL WHERE id = ?')->execute([$id]);
            }
        }
    }
    header('Location: edit_product.php?id=' . $id);
    exit();
}
// Handle set main image
if (isset($_GET['set_main'])) {
    $img_id = intval($_GET['set_main']);
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE id = ? AND product_id = ?');
    $stmt->execute([$img_id, $id]);
    $img = $stmt->fetch();
    if ($img) {
        $pdo->prepare('UPDATE product_images SET is_main = 0 WHERE product_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE product_images SET is_main = 1 WHERE id = ?')->execute([$img_id]);
        $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$img['image_path'], $id]);
    }
    header('Location: edit_product.php?id=' . $id);
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
    // Handle single image upload (legacy)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('prod_', true) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
        
        // Generate thumbnail
        $thumb_dir = '../uploads/thumbnails/';
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
        $thumb_path = $thumb_dir . pathinfo($image, PATHINFO_FILENAME) . '_thumb.jpg';
        make_thumbnail('../uploads/' . $image, $thumb_path, 300, 300);
        
        $stmt = $pdo->prepare('UPDATE products SET image = ? WHERE id = ?');
        $stmt->execute([$image, $id]);
    }
    // Handle multiple new images
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        $current_count = count($product_images);
        $files = $_FILES['images'];
        $max = min(10 - $current_count, count($files['name']));
        for ($i = 0; $i < $max; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $img_name = uniqid('prodimg_', true) . '.' . $ext;
                move_uploaded_file($files['tmp_name'][$i], '../uploads/' . $img_name);
                
                // Generate thumbnail
                $thumb_dir = '../uploads/thumbnails/';
                if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
                $thumb_path = $thumb_dir . pathinfo($img_name, PATHINFO_FILENAME) . '_thumb.jpg';
                make_thumbnail('../uploads/' . $img_name, $thumb_path, 300, 300);
                
                $is_main = ($i === 0) ? 1 : 0; // Assuming the first new image is the main one
                if ($is_main) $main_image_filename = $img_name;
                $stmt_img = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)');
                $stmt_img->execute([$id, $img_name, $is_main, $current_count + $i]);
            }
        }
    }
    // --- Save product variants ---
    // Delete old variants for this product
    $pdo->prepare('DELETE FROM product_variant_combinations WHERE product_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM product_variant_values WHERE option_id IN (SELECT id FROM product_variant_options WHERE product_id = ?)')->execute([$id]);
    $pdo->prepare('DELETE FROM product_variant_options WHERE product_id = ?')->execute([$id]);
    if (!empty($_POST['variant_combo_count']) && intval($_POST['variant_combo_count']) > 0) {
        $variantOptions = [];
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'variant_option_') === 0) {
                $idx = intval(substr($k, 14));
                $variantOptions[$idx] = ['name' => trim($v), 'values' => []];
            }
        }
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'variant_values_') === 0) {
                $idx = intval(substr($k, 14));
                if (isset($variantOptions[$idx])) {
                    $variantOptions[$idx]['values'] = array_map('trim', explode(',', $v));
                }
            }
        }
        // Insert options and values
        $optionIdMap = [];
        foreach ($variantOptions as $optIdx => $opt) {
            if (!$opt['name']) continue;
            $stmtOpt = $pdo->prepare('INSERT INTO product_variant_options (product_id, option_name, sort_order) VALUES (?, ?, ?)');
            $stmtOpt->execute([$id, $opt['name'], $optIdx]);
            $option_id = $pdo->lastInsertId();
            $optionIdMap[$optIdx] = $option_id;
            foreach ($opt['values'] as $valIdx => $val) {
                if (!$val) continue;
                $stmtVal = $pdo->prepare('INSERT INTO product_variant_values (option_id, value, sort_order) VALUES (?, ?, ?)');
                $stmtVal->execute([$option_id, $val, $valIdx]);
            }
        }
        // Insert combinations
        $comboCount = intval($_POST['variant_combo_count']);
        for ($i = 0; $i < $comboCount; $i++) {
            $comboParts = [];
            foreach ($variantOptions as $optIdx => $opt) {
                $val = $_POST["combo_{$i}_opt_{$optIdx}"] ?? '';
                $comboParts[] = $opt['name'] . ':' . $val;
            }
            $combKey = implode(';', $comboParts);
            $stock = isset($_POST["combo_{$i}_stock"]) ? intval($_POST["combo_{$i}_stock"]) : 0;
            $price = isset($_POST["combo_{$i}_price"]) ? floatval($_POST["combo_{$i}_price"]) : null;
            $stmtCombo = $pdo->prepare('INSERT INTO product_variant_combinations (product_id, combination_key, stock, price, sort_order) VALUES (?, ?, ?, ?, ?)');
            $stmtCombo->execute([$id, $combKey, $stock, $price, $i]);
        }
    }
    // --- End product variants ---
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
            <label>صور المنتج الحالية:</label>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php foreach ($product_images as $img): ?>
            <div style="position:relative;display:inline-block;">
                <img src="../uploads/<?php echo htmlspecialchars($img['image_path']); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:2px solid <?php echo $img['is_main']?'#FFD600':'#ccc'; ?>;">
                <?php if (!$img['is_main']): ?>
                    <a href="edit_product.php?id=<?php echo $id; ?>&set_main=<?php echo $img['id']; ?>" title="تعيين كصورة رئيسية" style="position:absolute;top:2px;right:2px;background:#FFD600;color:#333;padding:2px 6px;border-radius:4px;font-size:0.9em;text-decoration:none;">رئيسية</a>
                <?php else: ?>
                    <span style="position:absolute;top:2px;right:2px;background:#FFD600;color:#333;padding:2px 6px;border-radius:4px;font-size:0.9em;">رئيسية</span>
                <?php endif; ?>
                <a href="edit_product.php?id=<?php echo $id; ?>&delete_img=<?php echo $img['id']; ?>" onclick="return confirm('حذف هذه الصورة؟');" style="position:absolute;bottom:2px;right:2px;background:#c00;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.9em;text-decoration:none;">حذف</a>
            </div>
        <?php endforeach; ?>
    </div>
    <label for="images">إضافة صور جديدة (حتى 10 صور):</label>
    <input type="file" id="images" name="images[]" accept="image/*" multiple>
    <small>أول صورة رئيسية، يمكنك تعيين صورة رئيسية بعد الحفظ.</small>
    <label>Product Variants (optional):</label>
    <div id="variantOptionsContainer"></div>
    <button type="button" id="addVariantOptionBtn" style="margin-bottom:10px;background:#FFD600;color:#1A237E;">+ Add Option (e.g., Size, Color)</button>
    <div id="variantCombinationsContainer" style="margin-top:18px;"></div>
            <button type="submit">حفظ التعديلات</button>
        </form>
        <a href="products.php" class="back-btn">العودة لإدارة المنتجات</a>
    </div>
<script>
// --- Product Variants UI ---
const variantOptionsContainer = document.getElementById('variantOptionsContainer');
const addVariantOptionBtn = document.getElementById('addVariantOptionBtn');
const variantCombinationsContainer = document.getElementById('variantCombinationsContainer');
let variantOptions = [];
function renderVariantOptions() {
    variantOptionsContainer.innerHTML = '';
    variantOptions.forEach((opt, idx) => {
        const div = document.createElement('div');
        div.style.marginBottom = '8px';
        div.innerHTML = `<input type="text" name="variant_option_${idx}" value="${opt.name}" placeholder="Option name (e.g., Size)" style="width:120px;"> ` +
            `<input type="text" name="variant_values_${idx}" value="${opt.values.join(',')}" placeholder="Values (comma separated, e.g., S,M,L)" style="width:220px;"> ` +
            `<button type="button" onclick="removeVariantOption(${idx})" style="background:#c00;color:#fff;border:none;border-radius:4px;padding:2px 8px;">Remove</button>`;
        variantOptionsContainer.appendChild(div);
    });
    renderVariantCombinations();
}
window.removeVariantOption = function(idx) {
    variantOptions.splice(idx, 1);
    renderVariantOptions();
}
addVariantOptionBtn.onclick = function() {
    variantOptions.push({name:'', values:[]});
    renderVariantOptions();
}
function renderVariantCombinations() {
    variantCombinationsContainer.innerHTML = '';
    if (variantOptions.length === 0 || variantOptions.some(opt=>!opt.name||!opt.values.length)) return;
    // Build all combinations
    const valueArrays = variantOptions.map(opt => opt.values);
    function cartesian(arr) {
        return arr.reduce((a,b)=>a.flatMap(d=>b.map(e=>[...d,e])), [[]]);
    }
    const combos = cartesian(valueArrays);
    // Table
    const table = document.createElement('table');
    table.style.width = '100%';
    table.style.marginTop = '10px';
    table.innerHTML = '<tr>' + variantOptions.map(opt=>`<th>${opt.name}</th>`).join('') + '<th>Stock</th><th>Price</th></tr>';
    combos.forEach((combo, i) => {
        const tr = document.createElement('tr');
        combo.forEach((val, j) => {
            tr.innerHTML += `<td><input type="hidden" name="combo_${i}_opt_${j}" value="${val}">${val}</td>`;
        });
        tr.innerHTML += `<td><input type="number" name="combo_${i}_stock" min="0" value="0" style="width:60px;"></td>`;
        tr.innerHTML += `<td><input type="number" name="combo_${i}_price" step="0.01" min="0" style="width:80px;"></td>`;
        table.appendChild(tr);
    });
    variantCombinationsContainer.appendChild(table);
    // Save combo count
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'variant_combo_count';
    input.value = combos.length;
    variantCombinationsContainer.appendChild(input);
}
// Parse values on blur
variantOptionsContainer.addEventListener('blur', function(e) {
    if (e.target.name && e.target.name.startsWith('variant_values_')) {
        const idx = parseInt(e.target.name.split('_')[2]);
        variantOptions[idx].values = e.target.value.split(',').map(v=>v.trim()).filter(Boolean);
        renderVariantCombinations();
    }
    if (e.target.name && e.target.name.startsWith('variant_option_')) {
        const idx = parseInt(e.target.name.split('_')[2]);
        variantOptions[idx].name = e.target.value.trim();
        renderVariantCombinations();
    }
}, true);
</script>
</body>
</html> 