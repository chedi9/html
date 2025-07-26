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
    $disabled_seller_id = !empty($_POST['disabled_seller_id']) ? intval($_POST['disabled_seller_id']) : null;
    $is_priority_product = isset($_POST['is_priority_product']) ? 1 : 0;
    
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
    $stmt = $pdo->prepare('INSERT INTO products (name, name_ar, name_fr, name_en, description, price, image, stock, category_id, seller_name, seller_story, seller_photo, disabled_seller_id, is_priority_product) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name_ar, $name_ar, $name_fr, $name_en, $description, $price, $image, $stock, $category_id, $seller_name, $seller_story, $seller_photo, $disabled_seller_id, $is_priority_product]);
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
    // --- Save product variants ---
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
            $stmtOpt->execute([$product_id, $opt['name'], $optIdx]);
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
            $stmtCombo->execute([$product_id, $combKey, $stock, $price, $i]);
        }
    }
    // --- End product variants ---
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
            
            <!-- Disabled Seller Selection -->
            <div style="margin: 20px 0; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h3 style="margin: 0 0 10px 0; color: #856404;">البائع ذو الإعاقة</h3>
                <p style="margin: 0; color: #856404; font-size: 0.9em;">اختر بائع ذو إعاقة إذا كان هذا المنتج له. سيتم إعطاء الأولوية لهذا المنتج في الموقع.</p>
            </div>
            
            <?php
            // Get disabled sellers for dropdown
            $disabled_sellers = $pdo->query('SELECT id, name, disability_type, priority_level FROM disabled_sellers ORDER BY priority_level DESC, name')->fetchAll();
            ?>
            <label for="disabled_seller_id">البائع ذو الإعاقة:</label>
            <select id="disabled_seller_id" name="disabled_seller_id">
                <option value="">اختر بائع ذو إعاقة (اختياري)</option>
                <?php foreach ($disabled_sellers as $seller): ?>
                    <option value="<?php echo $seller['id']; ?>">
                        <?php echo htmlspecialchars($seller['name']); ?> 
                        (<?php echo htmlspecialchars($seller['disability_type']); ?> - أولوية: <?php echo $seller['priority_level']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label>
                <input type="checkbox" name="is_priority_product" value="1"> 
                منتج ذو أولوية (سيظهر في أعلى نتائج البحث)
            </label>
            <label>صور المنتج (حتى 10 صور):</label>
            <input type="file" name="images[]" accept="image/*" multiple required>
            <div id="mainImageSelector" style="margin-top:10px;"></div>
            <div id="imagePreview" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;"></div>
            <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #00BFAE;">
                <h3 style="margin: 0 0 10px 0; color: #1A237E;">Product Variants (اختياري)</h3>
                <p style="margin: 0; color: #666; font-size: 0.9em;">Add options like Size, Color, Material, etc. to create different versions of your product.</p>
            </div>
            <div id="variantOptionsContainer"></div>
            <button type="button" id="addVariantOptionBtn" style="margin-bottom:15px; background:#FFD600; color:#1A237E; border:none; padding:10px 20px; border-radius:6px; font-weight:bold; cursor:pointer;">
                <span style="font-size:1.2em;">+</span> إضافة خيار (مثل: الحجم، اللون)
            </button>
            <div id="variantCombinationsContainer" style="margin-top:20px;"></div>
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
// --- Product Variants UI ---
const variantOptionsContainer = document.getElementById('variantOptionsContainer');
const addVariantOptionBtn = document.getElementById('addVariantOptionBtn');
const variantCombinationsContainer = document.getElementById('variantCombinationsContainer');
let variantOptions = [];

function renderVariantOptions() {
    variantOptionsContainer.innerHTML = '';
    
    if (variantOptions.length === 0) {
        variantOptionsContainer.innerHTML = '<p style="color:#666; font-style:italic; text-align:center; padding:20px;">لا توجد خيارات مضافة بعد. انقر على "إضافة خيار" لبدء إضافة متغيرات المنتج.</p>';
        return;
    }
    
    variantOptions.forEach((opt, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'margin-bottom: 15px; padding: 15px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);';
        
        div.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input type="text" name="variant_option_${idx}" value="${opt.name}" placeholder="اسم الخيار (مثل: الحجم)" 
                       style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" 
                       onblur="updateVariantOption(${idx}, 'name', this.value)">
                <button type="button" onclick="removeVariantOption(${idx})" 
                        style="background: #dc3545; color: #fff; border: none; border-radius: 4px; padding: 8px 12px; cursor: pointer; font-size: 12px;">
                    حذف
                </button>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #1A237E;">القيم:</label>
                <div id="values-container-${idx}" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    ${opt.values.map(val => `
                        <span class="value-chip" style="background: #e3f2fd; color: #1A237E; padding: 4px 12px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; gap: 5px;">
                            ${val}
                            <button type="button" onclick="removeValue(${idx}, '${val}')" style="background: none; border: none; color: #1A237E; cursor: pointer; font-size: 14px;">×</button>
                        </span>
                    `).join('')}
                </div>
                <div style="display: flex; gap: 8px; margin-top: 8px;">
                    <input type="text" id="new-value-${idx}" placeholder="أضف قيمة جديدة" 
                           style="flex: 1; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;"
                           onkeypress="if(event.key==='Enter'){event.preventDefault();addValue(${idx});}">
                    <button type="button" onclick="addValue(${idx})" 
                            style="background: #00BFAE; color: #fff; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 12px;">
                        إضافة
                    </button>
                </div>
            </div>
        `;
        
        variantOptionsContainer.appendChild(div);
    });
    
    renderVariantCombinations();
}

window.removeVariantOption = function(idx) {
    variantOptions.splice(idx, 1);
    renderVariantOptions();
}

window.updateVariantOption = function(idx, field, value) {
    if (field === 'name') {
        variantOptions[idx].name = value.trim();
    }
    renderVariantCombinations();
}

window.addValue = function(idx) {
    const input = document.getElementById(`new-value-${idx}`);
    const value = input.value.trim();
    if (value && !variantOptions[idx].values.includes(value)) {
        variantOptions[idx].values.push(value);
        input.value = '';
        renderVariantOptions();
    }
}

window.removeValue = function(idx, value) {
    const valueIndex = variantOptions[idx].values.indexOf(value);
    if (valueIndex > -1) {
        variantOptions[idx].values.splice(valueIndex, 1);
        renderVariantOptions();
    }
}

addVariantOptionBtn.onclick = function() {
    variantOptions.push({name: '', values: []});
    renderVariantOptions();
}
function renderVariantCombinations() {
    variantCombinationsContainer.innerHTML = '';
    
    if (variantOptions.length === 0 || variantOptions.some(opt => !opt.name || !opt.values.length)) {
        return;
    }
    
    // Build all combinations
    const valueArrays = variantOptions.map(opt => opt.values);
    function cartesian(arr) {
        return arr.reduce((a, b) => a.flatMap(d => b.map(e => [...d, e])), [[]]);
    }
    const combos = cartesian(valueArrays);
    
    // Create combinations section
    const section = document.createElement('div');
    section.style.cssText = 'margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;';
    
    section.innerHTML = `
        <h3 style="margin: 0 0 15px 0; color: #1A237E; font-size: 1.1em;">متغيرات المنتج (${combos.length} خيار)</h3>
        <p style="margin: 0 0 15px 0; color: #666; font-size: 0.9em;">حدد المخزون والسعر لكل متغير من المنتج:</p>
    `;
    
    // Create table
    const table = document.createElement('table');
    table.style.cssText = 'width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
    
    // Table header
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr style="background: #1A237E; color: #fff;">
            ${variantOptions.map(opt => `<th style="padding: 12px; text-align: center; font-weight: bold; border-right: 1px solid #fff;">${opt.name}</th>`).join('')}
            <th style="padding: 12px; text-align: center; font-weight: bold;">المخزون</th>
            <th style="padding: 12px; text-align: center; font-weight: bold;">السعر (د.ت)</th>
        </tr>
    `;
    table.appendChild(thead);
    
    // Table body
    const tbody = document.createElement('tbody');
    combos.forEach((combo, i) => {
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom: 1px solid #e0e0e0;';
        
        // Add variant values
        combo.forEach((val, j) => {
            tr.innerHTML += `<td style="padding: 10px; text-align: center; background: #f8f9fa; font-weight: bold; color: #1A237E;">
                <input type="hidden" name="combo_${i}_opt_${j}" value="${val}">${val}
            </td>`;
        });
        
        // Add stock and price inputs
        tr.innerHTML += `
            <td style="padding: 10px; text-align: center;">
                <input type="number" name="combo_${i}_stock" min="0" value="0" 
                       style="width: 80px; padding: 6px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
            </td>
            <td style="padding: 10px; text-align: center;">
                <input type="number" name="combo_${i}_price" step="0.01" min="0" 
                       style="width: 100px; padding: 6px; border: 1px solid #ddd; border-radius: 4px; text-align: center;"
                       placeholder="السعر الأساسي">
            </td>
        `;
        
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    
    section.appendChild(table);
    
    // Add combo count hidden input
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'variant_combo_count';
    input.value = combos.length;
    section.appendChild(input);
    
    variantCombinationsContainer.appendChild(section);
}
// Update form submission to handle variant data
document.querySelector('form').addEventListener('submit', function(e) {
    // Update hidden inputs for variant data
    variantOptions.forEach((opt, idx) => {
        // Create hidden input for option name
        let input = document.querySelector(`input[name="variant_option_${idx}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = `variant_option_${idx}`;
            this.appendChild(input);
        }
        input.value = opt.name;
        
        // Create hidden input for option values
        input = document.querySelector(`input[name="variant_values_${idx}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = `variant_values_${idx}`;
            this.appendChild(input);
        }
        input.value = opt.values.join(',');
    });
});
</script>
</body>
</html> 