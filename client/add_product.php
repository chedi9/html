<?php
session_start();
require '../db.php';
require '../lang.php';
require_once '../db.php';
require_once 'make_thumbnail.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
// Check if user is a seller
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE user_id = ?');
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) {
    echo 'You are not a seller.';
    exit();
}
// Fetch categories
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = intval($_POST['category_id']);
    $main_image = '';
    $image_paths = [];
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        foreach ($_FILES['images']['name'] as $i => $img_name) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_file = uniqid('prodimg_', true) . '.' . $ext;
                move_uploaded_file($_FILES['images']['tmp_name'][$i], '../uploads/' . $img_file);
                
                // Generate thumbnail
                $thumb_dir = '../uploads/thumbnails/';
                if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
                $thumb_path = $thumb_dir . pathinfo($img_file, PATHINFO_FILENAME) . '_thumb.jpg';
                make_thumbnail('../uploads/' . $img_file, $thumb_path, 300, 300);
                
                $image_paths[] = $img_file;
                if ($i == 0) $main_image = $img_file;
            }
        }
    }
    if ($name && $description && $price > 0 && $stock >= 0 && $category_id && $main_image) {
        $stmt = $pdo->prepare('INSERT INTO products (name, description, price, stock, category_id, image, seller_id, approved) VALUES (?, ?, ?, ?, ?, ?, ?, 0)');
        $stmt->execute([$name, $description, $price, $stock, $category_id, $main_image, $seller['id']]);
        $product_id = $pdo->lastInsertId();
        // Save all images to product_images
        foreach ($image_paths as $idx => $img) {
            $is_main = ($idx == 0) ? 1 : 0;
            $stmt_img = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)');
            $stmt_img->execute([$product_id, $img, $is_main, $idx]);
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
        header('Location: seller_dashboard.php');
        exit();
    } else {
        $error = 'Please fill in all fields and upload at least one image.';
    }
}
?><!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    
</head>
<body>
    <div class="add-product-container">
        <h2>Add Product</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" min="0" required>
            </div>
            <div class="form-group">
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="images">Product Images:</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                <small>First image will be the main image. You can upload up to 10 images.</small>
            </div>
            <div class="form-group">
                <div>
                    <h3>Product Variants (اختياري)</h3>
                    <p>Add options like Size, Color, Material, etc. to create different versions of your product.</p>
                </div>
                <div id="variantOptionsContainer"></div>
                <button type="button" id="addVariantOptionBtn">
                    <span>+</span> إضافة خيار (مثل: الحجم، اللون)
                </button>
                <div id="variantCombinationsContainer"></div>
            </div>
            <button type="submit">Add Product</button>
        </form>
        <a href="seller_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    </div>
<script>
// --- Improved Product Variants UI ---
const variantOptionsContainer = document.getElementById('variantOptionsContainer');
const addVariantOptionBtn = document.getElementById('addVariantOptionBtn');
const variantCombinationsContainer = document.getElementById('variantCombinationsContainer');
let variantOptions = [];

function renderVariantOptions() {
    variantOptionsContainer.innerHTML = '';
    
    if (variantOptions.length === 0) {
        variantOptionsContainer.innerHTML = '<p>لا توجد خيارات مضافة بعد. انقر على "إضافة خيار" لبدء إضافة متغيرات المنتج.</p>';
        return;
    }
    
    variantOptions.forEach((opt, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'margin-bottom: 15px; padding: 15px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);';
        
        div.innerHTML = `
            <div>
                <input type="text" name="variant_option_${idx}" value="${opt.name}" placeholder="اسم الخيار (مثل: الحجم)" 
                       
                       onblur="updateVariantOption(${idx}, 'name', this.value)">
                <button type="button" onclick="removeVariantOption(${idx})" 
                       >
                    حذف
                </button>
            </div>
            <div>
                <label>القيم:</label>
                <div id="values-container-${idx}">
                    ${opt.values.map(val => `
                        <span class="value-chip">
                            ${val}
                            <button type="button" onclick="removeValue(${idx}, '${val}')">×</button>
                        </span>
                    `).join('')}
                </div>
                <div>
                    <input type="text" id="new-value-${idx}" placeholder="أضف قيمة جديدة" 
                          
                           onkeypress="if(event.key==='Enter'){event.preventDefault();addValue(${idx});}">
                    <button type="button" onclick="addValue(${idx})" 
                           >
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
        <h3>متغيرات المنتج (${combos.length} خيار)</h3>
        <p>حدد المخزون والسعر لكل متغير من المنتج:</p>
    `;
    
    // Create table
    const table = document.createElement('table');
    table.style.cssText = 'width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
    
    // Table header
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            ${variantOptions.map(opt => `<th>${opt.name}</th>`).join('')}
            <th>المخزون</th>
            <th>السعر (د.ت)</th>
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
            tr.innerHTML += `<td>
                <input type="hidden" name="combo_${i}_opt_${j}" value="${val}">${val}
            </td>`;
        });
        
        // Add stock and price inputs
        tr.innerHTML += `
            <td>
                <input type="number" name="combo_${i}_stock" min="0" value="0" 
                      >
            </td>
            <td>
                <input type="number" name="combo_${i}_price" step="0.01" min="0" 
                      
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