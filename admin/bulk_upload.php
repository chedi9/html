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

$page_title = 'رفع المنتجات بالجملة';
$page_subtitle = 'استيراد منتجات متعددة من ملف CSV';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'unified_dashboard.php'],
    ['title' => 'رفع المنتجات بالجملة']
];

require '../db.php';
require_once '../client/make_thumbnail.php';
require 'admin_header.php';

$message = '';
$error = '';
$uploaded_products = [];

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $filename = $_FILES['csv_file']['name'];
        
        // Check file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $error = 'يرجى رفع ملف CSV صحيح.';
        } else {
            // Read CSV file
            $handle = fopen($file, 'r');
            if ($handle) {
                $row = 1;
                $success_count = 0;
                $error_count = 0;
                $errors = [];
                
                // Skip header row
                $headers = fgetcsv($handle);
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row++;
                    
                    try {
                        // Validate required fields
                        if (count($data) < 6) {
                            $errors[] = "الصف $row: عدد الأعمدة غير كافي";
                            $error_count++;
                            continue;
                        }
                        
                        // Parse CSV data
                        $name_ar = trim($data[0] ?? '');
                        $name_fr = trim($data[1] ?? '');
                        $name_en = trim($data[2] ?? '');
                        $description = trim($data[3] ?? '');
                        $price = floatval($data[4] ?? 0);
                        $stock = intval($data[5] ?? 0);
                        $category_name = trim($data[6] ?? '');
                        $seller_name = trim($data[7] ?? '');
                        $seller_story = trim($data[8] ?? '');
                        $disabled_seller_id = !empty($data[9]) ? intval($data[9]) : null;
                        $is_priority = !empty($data[10]) && strtolower($data[10]) === 'yes' ? 1 : 0;
                        
                        // Validation
                        if (empty($name_ar)) {
                            $errors[] = "الصف $row: اسم المنتج مطلوب";
                            $error_count++;
                            continue;
                        }
                        
                        if ($price <= 0) {
                            $errors[] = "الصف $row: السعر يجب أن يكون أكبر من صفر";
                            $error_count++;
                            continue;
                        }
                        
                        if ($stock < 0) {
                            $errors[] = "الصف $row: المخزون يجب أن يكون صفر أو أكثر";
                            $error_count++;
                            continue;
                        }
                        
                        // Get or create category
                        $category_id = null;
                        if (!empty($category_name)) {
                            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
                            $stmt->execute([$category_name]);
                            $category = $stmt->fetch();
                            
                            if ($category) {
                                $category_id = $category['id'];
                            } else {
                                // Create new category
                                $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
                                $stmt->execute([$category_name]);
                                $category_id = $pdo->lastInsertId();
                            }
                        }
                        
                        // Validate disabled seller if provided
                        if ($disabled_seller_id) {
                            $stmt = $pdo->prepare('SELECT id FROM disabled_sellers WHERE id = ?');
                            $stmt->execute([$disabled_seller_id]);
                            if (!$stmt->fetch()) {
                                $errors[] = "الصف $row: معرف البائع ذو الإعاقة غير صحيح";
                                $error_count++;
                                continue;
                            }
                        }
                        
$product_id = $pdo->lastInsertId();
$uploaded_products[] = [
    'id' => $product_id,
    'name' => $name_ar,
    'price' => $price,
    'stock' => $stock
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Existing upload logic...
    
    // Handle image uploads
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $image_name = $_FILES['images']['name'][$key];
            $image_tmp = $_FILES['images']['tmp_name'][$key];
            
            // Move the uploaded image to the desired directory
            move_uploaded_file($image_tmp, "uploads/$image_name");
            
$stmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, ?, ?)');
$stmt->execute([$product_id, $image_name, 0, 0]); // Default values for is_main and sort_order
        }
    }
}
                        
                        $product_id = $pdo->lastInsertId();
                        $uploaded_products[] = [
                            'id' => $product_id,
                            'name' => $name_ar,
                            'price' => $price,
                            'stock' => $stock
                        ];
                        
                        $success_count++;
                        
                        // Log activity
                        $admin_id = $_SESSION['admin_id'];
                        $action = 'bulk_upload_product';
                        $details = 'Bulk uploaded product: ' . $name_ar;
                        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
                        
                    } catch (Exception $e) {
                        $errors[] = "الصف $row: " . $e->getMessage();
                        $error_count++;
                    }
                }
                
                fclose($handle);
                
                if ($success_count > 0) {
                    $message = "تم رفع $success_count منتج بنجاح.";
                    if ($error_count > 0) {
                        $message .= " فشل في رفع $error_count منتج.";
                    }
                } else {
                    $error = "لم يتم رفع أي منتج. يرجى التحقق من البيانات.";
                }
                
                if (!empty($errors)) {
                    $error .= "<br><br>تفاصيل الأخطاء:<br>" . implode("<br>", array_slice($errors, 0, 10));
                    if (count($errors) > 10) {
                        $error .= "<br>... والمزيد من الأخطاء";
                    }
                }
                
            } else {
                $error = 'لا يمكن قراءة الملف.';
            }
        }
    } else {
        $error = 'حدث خطأ في رفع الملف.';
    }
}

// Get categories for reference
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Get disabled sellers for reference
$disabled_sellers = $pdo->query('SELECT id, name, disability_type FROM disabled_sellers ORDER BY name')->fetchAll();
?>

<?php if ($message): ?>
    <div class="message success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="message error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Upload Form -->
<div class="form-section">
    <h3>رفع ملف CSV</h3>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv_file">اختر ملف CSV:</label>
<input type="file" id="csv_file" name="csv_file" accept=".xlsx" required multiple>
            <small>يجب أن يكون الملف بصيغة CSV مع الأعمدة المطلوبة</small>
        </div>
        <button type="submit" class="btn btn-primary">رفع المنتجات</button>
    </form>
</div>

<!-- CSV Template -->
<div class="form-section">
    <h3>قالب ملف CSV</h3>
    <p>استخدم هذا القالب لإنشاء ملف CSV الخاص بك:</p>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; overflow-x: auto;">
        <table class="admin-table" style="font-size: 0.9em;">
            <thead>
                <tr>
                    <th>اسم المنتج (عربي)</th>
                    <th>اسم المنتج (فرنسي)</th>
                    <th>اسم المنتج (إنجليزي)</th>
                    <th>الوصف</th>
                    <th>السعر</th>
                    <th>المخزون</th>
                    <th>التصنيف</th>
                    <th>اسم البائع</th>
                    <th>قصة البائع</th>
                    <th>معرف البائع ذو الإعاقة</th>
                    <th>منتج ذو أولوية</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>منتج تجريبي</td>
                    <td>Produit Test</td>
                    <td>Test Product</td>
                    <td>وصف المنتج هنا</td>
                    <td>25.50</td>
                    <td>10</td>
                    <td>الإلكترونيات</td>
                    <td>أحمد محمد</td>
                    <td>قصة البائع هنا</td>
                    <td>1</td>
                    <td>yes</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="download_csv_template.php" class="btn btn-success">تحميل قالب CSV</a>
    </div>
</div>

<!-- Instructions -->
<div class="form-section">
    <h3>تعليمات الاستخدام</h3>
    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border-left: 4px solid #ffc107;">
        <h4 style="margin-top: 0; color: #856404;">📋 الأعمدة المطلوبة:</h4>
        <ul style="color: #856404;">
            <li><strong>اسم المنتج (عربي):</strong> مطلوب - اسم المنتج باللغة العربية</li>
            <li><strong>اسم المنتج (فرنسي):</strong> اختياري - اسم المنتج باللغة الفرنسية</li>
            <li><strong>اسم المنتج (إنجليزي):</strong> اختياري - اسم المنتج باللغة الإنجليزية</li>
            <li><strong>الوصف:</strong> مطلوب - وصف المنتج</li>
            <li><strong>السعر:</strong> مطلوب - سعر المنتج (أكبر من صفر)</li>
            <li><strong>المخزون:</strong> مطلوب - كمية المخزون المتاحة</li>
            <li><strong>التصنيف:</strong> اختياري - اسم التصنيف (سيتم إنشاؤه إذا لم يكن موجوداً)</li>
            <li><strong>اسم البائع:</strong> اختياري - اسم البائع</li>
            <li><strong>قصة البائع:</strong> اختياري - قصة البائع</li>
            <li><strong>معرف البائع ذو الإعاقة:</strong> اختياري - معرف البائع ذو الإعاقة من قاعدة البيانات</li>
            <li><strong>منتج ذو أولوية:</strong> اختياري - "yes" لجعل المنتج ذو أولوية</li>
        </ul>
        
        <h4 style="color: #856404;">⚠️ ملاحظات مهمة:</h4>
        <ul style="color: #856404;">
            <li>يجب أن يكون الملف بصيغة CSV</li>
            <li>استخدم الفاصلة (,) كفاصل بين الأعمدة</li>
            <li>إذا كان النص يحتوي على فواصل، ضعه بين علامتي اقتباس ("")</li>
            <li>الصف الأول يجب أن يحتوي على أسماء الأعمدة</li>
            <li>جميع المنتجات المرفوعة ستكون معتمدة تلقائياً</li>
        </ul>
    </div>
</div>

<!-- Reference Data -->
<div class="form-section">
    <h3>البيانات المرجعية</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Categories -->
        <div>
            <h4>التصنيفات المتاحة:</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto;">
                <?php if (empty($categories)): ?>
                    <p style="color: #666;">لا توجد تصنيفات متاحة</p>
                <?php else: ?>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($categories as $cat): ?>
                            <li><?php echo htmlspecialchars($cat['name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Disabled Sellers -->
        <div>
            <h4>البائعون ذوو الإعاقة:</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto;">
                <?php if (empty($disabled_sellers)): ?>
                    <p style="color: #666;">لا يوجد بائعون ذوو إعاقة مسجلون</p>
                <?php else: ?>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($disabled_sellers as $seller): ?>
                            <li>
                                <strong>ID: <?php echo $seller['id']; ?></strong> - 
                                <?php echo htmlspecialchars($seller['name']); ?> 
                                (<?php echo htmlspecialchars($seller['disability_type']); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Uploaded Products -->
<?php if (!empty($uploaded_products)): ?>
<div class="form-section">
    <h3>المنتجات المرفوعة بنجاح</h3>
    <div style="background: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>معرف المنتج</th>
                    <th>اسم المنتج</th>
                    <th>السعر</th>
                    <th>المخزون</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uploaded_products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo $product['price']; ?> د.ت</td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8em;">تعديل</a>
                        <a href="products.php" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8em;">عرض الكل</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
    .form-section {
        margin-bottom: 30px;
        padding: 25px;
        border: 1px solid #e9ecef;
        border-radius: 15px;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    }
    
    .form-section h3 {
        margin-top: 0;
        color: #2c3e50;
        font-size: 1.5em;
        margin-bottom: 20px;
    }
    
    .form-section h4 {
        color: #2c3e50;
        margin-bottom: 15px;
    }
    
    .form-section ul {
        margin-bottom: 15px;
    }
    
    .form-section li {
        margin-bottom: 5px;
    }
    
    .admin-table {
        font-size: 0.9em;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 8px 12px;
    }
</style>

<?php require 'admin_footer.php'; ?> 