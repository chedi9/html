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

$page_title = 'إدارة البائعين ذوي الإعاقة';
$page_subtitle = 'إضافة وتعديل وحذف البائعين ذوي الإعاقة ومنتجاتهم';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'dashboard.php'],
    ['title' => 'إدارة البائعين ذوي الإعاقة']
];

require '../db.php';
require 'admin_header.php';
require_once '../client/make_thumbnail.php';

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_seller':
                $name = trim($_POST['seller_name']);
                $story = trim($_POST['seller_story']);
                $disability_type = trim($_POST['disability_type']);
                $location = trim($_POST['location']);
                $contact_info = trim($_POST['contact_info']);
                $priority_level = intval($_POST['priority_level']);
                
                // Handle seller photo upload
                $seller_photo = '';
                if (isset($_FILES['seller_photo']) && $_FILES['seller_photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['seller_photo']['name'], PATHINFO_EXTENSION);
                    $seller_photo = uniqid('seller_', true) . '.' . $ext;
                    move_uploaded_file($_FILES['seller_photo']['tmp_name'], '../uploads/' . $seller_photo);
                    
                    // Generate thumbnail
                    $thumb_dir = '../uploads/thumbnails/';
                    if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
                    $thumb_path = $thumb_dir . pathinfo($seller_photo, PATHINFO_FILENAME) . '_thumb.jpg';
                    make_thumbnail('../uploads/' . $seller_photo, $thumb_path, 150, 150);
                }
                
                $stmt = $pdo->prepare('INSERT INTO disabled_sellers (name, story, disability_type, location, contact_info, seller_photo, priority_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$name, $story, $disability_type, $location, $contact_info, $seller_photo, $priority_level]);
                $message = 'تم إضافة البائع بنجاح';
                break;
                
            case 'update_seller':
                $seller_id = intval($_POST['seller_id']);
                $name = trim($_POST['seller_name']);
                $story = trim($_POST['seller_story']);
                $disability_type = trim($_POST['disability_type']);
                $location = trim($_POST['location']);
                $contact_info = trim($_POST['contact_info']);
                $priority_level = intval($_POST['priority_level']);
                
                // Handle seller photo upload
                $seller_photo = $_POST['current_photo'];
                if (isset($_FILES['seller_photo']) && $_FILES['seller_photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['seller_photo']['name'], PATHINFO_EXTENSION);
                    $seller_photo = uniqid('disabled_seller_', true) . '.' . $ext;
                    move_uploaded_file($_FILES['seller_photo']['tmp_name'], '../uploads/' . $seller_photo);
                    
                    // Generate thumbnail
                    $thumb_dir = '../uploads/thumbnails/';
                    if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
                    $thumb_path = $thumb_dir . pathinfo($seller_photo, PATHINFO_FILENAME) . '_thumb.jpg';
                    make_thumbnail('../uploads/' . $seller_photo, $thumb_path, 150, 150);
                }
                
                $stmt = $pdo->prepare('UPDATE disabled_sellers SET name = ?, story = ?, disability_type = ?, location = ?, contact_info = ?, seller_photo = ?, priority_level = ? WHERE id = ?');
                $stmt->execute([$name, $story, $disability_type, $location, $contact_info, $seller_photo, $priority_level, $seller_id]);
                $message = 'تم تحديث البائع بنجاح';
                break;
                
            case 'delete_seller':
                $seller_id = intval($_POST['seller_id']);
                $stmt = $pdo->prepare('DELETE FROM disabled_sellers WHERE id = ?');
                $stmt->execute([$seller_id]);
                $message = 'تم حذف البائع بنجاح';
                break;
        }
    }
}

// Get all disabled sellers
$sellers = $pdo->query('SELECT * FROM disabled_sellers ORDER BY priority_level DESC, created_at DESC')->fetchAll();

// Get seller for editing
$edit_seller = null;
if (isset($_GET['edit']) && intval($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM disabled_sellers WHERE id = ?');
    $stmt->execute([intval($_GET['edit'])]);
    $edit_seller = $stmt->fetch();
}
?>

        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Add/Edit Seller Form -->
        <div class="form-section">
            <h3><?php echo $edit_seller ? 'تعديل البائع' : 'إضافة بائع جديد'; ?></h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_seller ? 'update_seller' : 'add_seller'; ?>">
                <?php if ($edit_seller): ?>
                    <input type="hidden" name="seller_id" value="<?php echo $edit_seller['id']; ?>">
                    <input type="hidden" name="current_photo" value="<?php echo $edit_seller['seller_photo']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="seller_name">اسم البائع:</label>
                    <input type="text" name="seller_name" id="seller_name" required value="<?php echo $edit_seller ? htmlspecialchars($edit_seller['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="disability_type">نوع الإعاقة:</label>
                    <select name="disability_type" id="disability_type" required>
                        <option value="">اختر نوع الإعاقة</option>
                        <option value="حركية" <?php echo ($edit_seller && $edit_seller['disability_type'] == 'حركية') ? 'selected' : ''; ?>>حركية</option>
                        <option value="بصرية" <?php echo ($edit_seller && $edit_seller['disability_type'] == 'بصرية') ? 'selected' : ''; ?>>بصرية</option>
                        <option value="سمعية" <?php echo ($edit_seller && $edit_seller['disability_type'] == 'سمعية') ? 'selected' : ''; ?>>سمعية</option>
                        <option value="ذهنية" <?php echo ($edit_seller && $edit_seller['disability_type'] == 'ذهنية') ? 'selected' : ''; ?>>ذهنية</option>
                        <option value="أخرى" <?php echo ($edit_seller && $edit_seller['disability_type'] == 'أخرى') ? 'selected' : ''; ?>>أخرى</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="location">الموقع:</label>
                    <input type="text" name="location" id="location" required value="<?php echo $edit_seller ? htmlspecialchars($edit_seller['location']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_info">معلومات الاتصال:</label>
                    <input type="text" name="contact_info" id="contact_info" value="<?php echo $edit_seller ? htmlspecialchars($edit_seller['contact_info']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="priority_level">مستوى الأولوية (1-10):</label>
                    <select name="priority_level" id="priority_level" required>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($edit_seller && $edit_seller['priority_level'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="seller_story">قصة البائع:</label>
                    <textarea name="seller_story" id="seller_story" required><?php echo $edit_seller ? htmlspecialchars($edit_seller['story']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="seller_photo">صورة البائع:</label>
                    <input type="file" name="seller_photo" id="seller_photo" accept="image/*">
                    <?php if ($edit_seller && $edit_seller['seller_photo']): ?>
                        <p>الصورة الحالية: <?php echo $edit_seller['seller_photo']; ?></p>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo $edit_seller ? 'تحديث البائع' : 'إضافة البائع'; ?></button>
                <?php if ($edit_seller): ?>
                    <a href="disabled_sellers.php" class="btn btn-secondary">إلغاء</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Sellers List -->
        <div class="form-section">
            <h3>البائعون ذوو الإعاقة</h3>
            <?php if (empty($sellers)): ?>
                <p>لا يوجد بائعون ذوو إعاقة مسجلون حالياً.</p>
            <?php else: ?>
                <div class="sellers-grid">
                    <?php foreach ($sellers as $seller): ?>
                        <div class="seller-card">
                            <?php if ($seller['seller_photo']): ?>
                                <img src="../uploads/<?php echo $seller['seller_photo']; ?>" alt="<?php echo htmlspecialchars($seller['name']); ?>" class="seller-photo">
                            <?php endif; ?>
                            <h4 style="color: #2c3e50; margin-bottom: 15px; font-size: 1.3em;"><?php echo htmlspecialchars($seller['name']); ?></h4>
                            <div class="seller-info">
                                <p><strong>نوع الإعاقة:</strong> <?php echo htmlspecialchars($seller['disability_type']); ?></p>
                                <p><strong>الموقع:</strong> <?php echo htmlspecialchars($seller['location']); ?></p>
                                <p><strong>الأولوية:</strong> <span class="priority-badge"><?php echo $seller['priority_level']; ?></span></p>
                                <p><strong>القصة:</strong> <?php echo htmlspecialchars(substr($seller['story'], 0, 100)) . (strlen($seller['story']) > 100 ? '...' : ''); ?></p>
                            </div>
                            <div class="actions">
                                <a href="?edit=<?php echo $seller['id']; ?>" class="btn btn-warning">تعديل</a>
                                <form method="post" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا البائع؟')">
                                    <input type="hidden" name="action" value="delete_seller">
                                    <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                                    <button type="submit" class="btn btn-danger">حذف</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
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
        .sellers-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 25px; 
            margin-top: 20px; 
        }
        .seller-card { 
            border: 1px solid #e9ecef; 
            border-radius: 15px; 
            padding: 20px; 
            background: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .seller-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .seller-photo { 
            width: 120px; 
            height: 120px; 
            object-fit: cover; 
            border-radius: 10px; 
            margin-bottom: 15px;
            border: 3px solid #e9ecef;
        }
        .priority-badge { 
            background: linear-gradient(135deg, #ff6b6b, #ee5a52); 
            color: #fff; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.9em;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        .actions { 
            margin-top: 15px; 
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .actions a, .actions button { 
            padding: 8px 16px; 
            text-decoration: none; 
            border-radius: 20px; 
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .seller-info {
            margin-bottom: 15px;
        }
        .seller-info p {
            margin: 5px 0;
            color: #2c3e50;
        }
        .seller-info strong {
            color: #3498db;
        }
    </style>
    
    <?php require 'admin_footer.php'; ?> 