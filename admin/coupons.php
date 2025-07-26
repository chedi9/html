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

// Handle coupon operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_coupon'])) {
        $code = strtoupper(trim($_POST['code']));
        $type = $_POST['type'];
        $value = floatval($_POST['value']);
        $min_order = floatval($_POST['min_order_amount']);
        $max_uses = $_POST['max_uses'] ? intval($_POST['max_uses']) : null;
        $valid_from = $_POST['valid_from'];
        $valid_until = $_POST['valid_until'];
        $description = trim($_POST['description']);
        
        $stmt = $pdo->prepare('INSERT INTO coupons (code, type, value, min_order_amount, max_uses, valid_from, valid_until, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, $type, $value, $min_order, $max_uses, $valid_from, $valid_until, $description]);
        
        header('Location: coupons.php?success=added');
        exit();
    }
    
    if (isset($_POST['toggle_status'])) {
        $id = intval($_POST['coupon_id']);
        $stmt = $pdo->prepare('UPDATE coupons SET is_active = !is_active WHERE id = ?');
        $stmt->execute([$id]);
        
        header('Location: coupons.php?success=updated');
        exit();
    }
    
    if (isset($_POST['delete_coupon'])) {
        $id = intval($_POST['coupon_id']);
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = ?');
        $stmt->execute([$id]);
        
        header('Location: coupons.php?success=deleted');
        exit();
    }
}

// Fetch all coupons
$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الكوبونات - WeBuy Admin</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .admin-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .admin-header h1 {
            color: #fff;
            margin: 0;
            font-size: 2.5em;
        }
        .coupon-card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .add-coupon-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }
        .btn-success {
            background: linear-gradient(45deg, #00d2d3, #54a0ff);
        }
        .coupon-list {
            display: grid;
            gap: 15px;
        }
        .coupon-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
        }
        .coupon-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .coupon-details {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 0.9em;
            color: #666;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🎫 إدارة الكوبونات والعروض</h1>
            <p style="color: rgba(255,255,255,0.8); margin: 10px 0 0 0;">إنشاء وإدارة كوبونات الخصم</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php
                switch($_GET['success']) {
                    case 'added': echo 'تم إضافة الكوبون بنجاح!'; break;
                    case 'updated': echo 'تم تحديث الكوبون بنجاح!'; break;
                    case 'deleted': echo 'تم حذف الكوبون بنجاح!'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Add New Coupon -->
        <div class="coupon-card">
            <h2>إضافة كوبون جديد</h2>
            <form method="post" class="add-coupon-form">
                <div class="form-group">
                    <label for="code">كود الكوبون</label>
                    <input type="text" name="code" id="code" required maxlength="50" style="text-transform: uppercase;">
                </div>
                <div class="form-group">
                    <label for="type">نوع الخصم</label>
                    <select name="type" id="type" required>
                        <option value="percentage">نسبة مئوية (%)</option>
                        <option value="fixed">قيمة ثابتة</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="value">قيمة الخصم</label>
                    <input type="number" name="value" id="value" required min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="min_order_amount">الحد الأدنى للطلب</label>
                    <input type="number" name="min_order_amount" id="min_order_amount" min="0" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label for="max_uses">عدد الاستخدامات (اختياري)</label>
                    <input type="number" name="max_uses" id="max_uses" min="1">
                </div>
                <div class="form-group">
                    <label for="valid_from">تاريخ البداية</label>
                    <input type="datetime-local" name="valid_from" id="valid_from" required>
                </div>
                <div class="form-group">
                    <label for="valid_until">تاريخ الانتهاء</label>
                    <input type="datetime-local" name="valid_until" id="valid_until" required>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="description">الوصف</label>
                    <textarea name="description" id="description" rows="3" placeholder="وصف اختياري للكوبون"></textarea>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_coupon" class="btn">إضافة الكوبون</button>
                </div>
            </form>
        </div>

        <!-- Coupons List -->
        <div class="coupon-card">
            <h2>قائمة الكوبونات (<?= count($coupons) ?>)</h2>
            <div class="coupon-list">
                <?php if (empty($coupons)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">لا توجد كوبونات بعد</p>
                <?php else: ?>
                    <?php foreach ($coupons as $coupon): ?>
                        <div class="coupon-item">
                            <div class="coupon-info">
                                <h3><?= htmlspecialchars($coupon['code']) ?></h3>
                                <div class="coupon-details">
                                    <span><?= $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : $coupon['value'] . ' د.ت' ?></span>
                                    <span>الحد الأدنى: <?= $coupon['min_order_amount'] ?> د.ت</span>
                                    <?php if ($coupon['max_uses']): ?>
                                        <span>الاستخدامات: <?= $coupon['used_count'] ?>/<?= $coupon['max_uses'] ?></span>
                                    <?php else: ?>
                                        <span>الاستخدامات: <?= $coupon['used_count'] ?></span>
                                    <?php endif; ?>
                                    <span>من <?= date('Y-m-d', strtotime($coupon['valid_from'])) ?> إلى <?= date('Y-m-d', strtotime($coupon['valid_until'])) ?></span>
                                    <span class="status-badge <?= $coupon['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $coupon['is_active'] ? 'فعال' : 'غير فعال' ?>
                                    </span>
                                </div>
                                <?php if ($coupon['description']): ?>
                                    <p style="margin: 10px 0 0 0; font-size: 0.9em; color: #666;"><?= htmlspecialchars($coupon['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="actions">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn <?= $coupon['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                                        <?= $coupon['is_active'] ? 'إيقاف' : 'تفعيل' ?>
                                    </button>
                                </form>
                                <form method="post" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكوبون؟')">
                                    <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                    <button type="submit" name="delete_coupon" class="btn btn-danger">حذف</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn">العودة للوحة التحكم</a>
        </div>
    </div>
</body>
</html>