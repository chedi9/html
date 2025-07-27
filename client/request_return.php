<?php
session_start();
require '../db.php';
require '../lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Verify order belongs to user
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: account.php');
    exit();
}

// Check if return already exists
$stmt = $pdo->prepare('SELECT * FROM returns WHERE order_id = ?');
$stmt->execute([$order_id]);
$existing_return = $stmt->fetch();

if ($existing_return) {
    header('Location: account.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description'] ?? '');
    $return_date = date('Y-m-d');
    
    if (empty($reason)) {
        $error = 'يرجى تحديد سبب الإرجاع';
    } else {
        // Generate return number
        $return_number = 'RET-' . date('Ymd') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
        
        // Create return record
        $stmt = $pdo->prepare('
            INSERT INTO returns (order_id, user_id, return_number, reason, description, return_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, "pending")
        ');
        $stmt->execute([$order_id, $user_id, $return_number, $reason, $description, $return_date]);
        $return_id = $pdo->lastInsertId();
        
        // Add return items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item_id => $item_data) {
                if (isset($item_data['selected']) && $item_data['selected'] == '1') {
                    $quantity = intval($item_data['quantity']);
                    $return_reason = trim($item_data['return_reason']);
                    
                    if ($quantity > 0 && !empty($return_reason)) {
                        $stmt = $pdo->prepare('
                            INSERT INTO return_items (return_id, order_item_id, product_id, quantity, return_reason) 
                            VALUES (?, ?, ?, ?, ?)
                        ');
                        $stmt->execute([$return_id, $item_id, $item_data['product_id'], $quantity, $return_reason]);
                    }
                }
            }
        }
        
        // Update order return status
        $stmt = $pdo->prepare('UPDATE orders SET return_status = "return_requested" WHERE id = ?');
        $stmt->execute([$order_id]);
        
        // Create notification
        $stmt = $pdo->prepare('
            INSERT INTO notifications (user_id, type, title, message) 
            VALUES (?, "order", "طلب إرجاع جديد", ?)
        ');
        $stmt->execute([$user_id, "تم إرسال طلب إرجاع للطلب رقم #{$order_id}. سنراجع طلبك ونوافيك بالنتيجة قريباً."]);
        
        header('Location: account.php');
        exit();
    }
}

// Get order items
$stmt = $pdo->prepare('
    SELECT oi.*, p.name as product_name, p.image as product_image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
');
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب إرجاع - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .return-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .return-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 24px;
            text-align: center;
        }

        .return-header h1 {
            margin: 0;
            font-size: 1.5em;
            font-weight: 600;
        }

        .return-content {
            padding: 24px;
        }

        .order-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .order-info h3 {
            margin: 0 0 12px 0;
            color: #333;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .order-detail {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .order-detail:last-child {
            border-bottom: none;
        }

        .order-detail strong {
            color: #333;
        }

        .items-section {
            margin-bottom: 24px;
        }

        .items-section h3 {
            margin: 0 0 16px 0;
            color: #333;
        }

        .item-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            background: #fff;
        }

        .item-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 500;
        }

        .item-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .item-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .item-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 4px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .general-reason {
            margin-bottom: 24px;
        }

        .general-reason h3 {
            margin: 0 0 12px 0;
            color: #333;
        }

        .submit-section {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #eee;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background: var(--secondary-color);
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            margin-left: 12px;
            transition: background 0.2s;
        }

        .cancel-btn:hover {
            background: #5a6268;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            border: 1px solid #ffcdd2;
        }

        @media (max-width: 768px) {
            .return-container {
                margin: 20px;
                border-radius: 8px;
            }

            .return-content {
                padding: 16px;
            }

            .item-form {
                grid-template-columns: 1fr;
            }

            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="return-container">
        <div class="return-header">
            <h1>🔄 طلب إرجاع</h1>
        </div>
        
        <div class="return-content">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="order-info">
                <h3>معلومات الطلب</h3>
                <div class="order-details">
                    <div class="order-detail">
                        <span>رقم الطلب:</span>
                        <strong>#<?php echo $order['id']; ?></strong>
                    </div>
                    <div class="order-detail">
                        <span>تاريخ الطلب:</span>
                        <strong><?php echo date('j M Y', strtotime($order['created_at'])); ?></strong>
                    </div>
                    <div class="order-detail">
                        <span>المبلغ الإجمالي:</span>
                        <strong><?php echo $order['total'] ?? $order['total_amount']; ?> د.ت</strong>
                    </div>
                    <div class="order-detail">
                        <span>حالة الطلب:</span>
                        <strong><?php echo $order['status']; ?></strong>
                    </div>
                </div>
            </div>

            <form method="post">
                <div class="items-section">
                    <h3>اختر المنتجات المراد إرجاعها</h3>
                    
                    <?php foreach ($order_items as $item): ?>
                        <div class="item-card">
                            <div class="item-header">
                                <img src="../uploads/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="item-image">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-price"><?php echo $item['price']; ?> د.ت × <?php echo $item['quantity']; ?></div>
                                </div>
                            </div>
                            
                            <div class="item-checkbox">
                                <input type="checkbox" id="item_<?php echo $item['id']; ?>" 
                                       name="items[<?php echo $item['id']; ?>][selected]" value="1" 
                                       onchange="toggleItemForm(<?php echo $item['id']; ?>)">
                                <label for="item_<?php echo $item['id']; ?>">إرجاع هذا المنتج</label>
                            </div>
                            
                            <div class="item-form" id="form_<?php echo $item['id']; ?>" style="display: none;">
                                <input type="hidden" name="items[<?php echo $item['id']; ?>][product_id]" value="<?php echo $item['product_id']; ?>">
                                
                                <div class="form-group">
                                    <label>الكمية المراد إرجاعها:</label>
                                    <input type="number" name="items[<?php echo $item['id']; ?>][quantity]" 
                                           min="1" max="<?php echo $item['quantity']; ?>" 
                                           value="<?php echo $item['quantity']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>سبب الإرجاع:</label>
                                    <select name="items[<?php echo $item['id']; ?>][return_reason]" required>
                                        <option value="">اختر السبب</option>
                                        <option value="defective">منتج معيب</option>
                                        <option value="wrong_item">منتج خاطئ</option>
                                        <option value="not_as_described">لا يتطابق مع الوصف</option>
                                        <option value="size_issue">مشكلة في المقاس</option>
                                        <option value="quality_issue">مشكلة في الجودة</option>
                                        <option value="changed_mind">غيرت رأيي</option>
                                        <option value="other">سبب آخر</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="general-reason">
                    <h3>سبب عام للإرجاع</h3>
                    <div class="form-group">
                        <label>السبب الرئيسي للإرجاع:</label>
                        <select name="reason" required>
                            <option value="">اختر السبب الرئيسي</option>
                            <option value="defective_products">منتجات معيبة</option>
                            <option value="wrong_items">منتجات خاطئة</option>
                            <option value="quality_issues">مشاكل في الجودة</option>
                            <option value="delivery_issues">مشاكل في التوصيل</option>
                            <option value="changed_mind">غيرت رأيي</option>
                            <option value="other">سبب آخر</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>تفاصيل إضافية (اختياري):</label>
                        <textarea name="description" placeholder="اكتب تفاصيل إضافية عن سبب الإرجاع..."></textarea>
                    </div>
                </div>

                <div class="submit-section">
                    <button type="submit" class="submit-btn">إرسال طلب الإرجاع</button>
                    <a href="account.php" class="cancel-btn">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleItemForm(itemId) {
            const checkbox = document.getElementById('item_' + itemId);
            const form = document.getElementById('form_' + itemId);
            
            if (checkbox.checked) {
                form.style.display = 'grid';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html> 