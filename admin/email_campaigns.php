<?php
session_start();
require '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle campaign creation
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'price_reduction') {
        $product_id = intval($_POST['product_id']);
        $discount_percent = intval($_POST['discount_percent']);
        
        if ($product_id && $discount_percent > 0) {
            try {
                // Get product details
                $stmt = $pdo->prepare("SELECT p.*, s.store_name FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product) {
                    // Get users who have this product in their wishlist
                    $stmt = $pdo->prepare("SELECT DISTINCT u.email, u.name FROM users u 
                                          JOIN wishlist w ON u.id = w.user_id 
                                          WHERE w.product_id = ? AND u.email IS NOT NULL AND u.email != ''");
                    $stmt->execute([$product_id]);
                    $recipients = $stmt->fetchAll();
                    
                    $sent_count = 0;
                    foreach ($recipients as $recipient) {
                        if (sendPriceReductionEmail($recipient['email'], $recipient['name'], $product, $discount_percent)) {
                            $sent_count++;
                        }
                    }
                    
                    // Log the campaign
                    $stmt = $pdo->prepare("INSERT INTO email_campaigns (type, product_id, discount_percent, sent_count, created_at) VALUES ('price_reduction', ?, ?, ?, NOW())");
                    $stmt->execute([$product_id, $discount_percent, $sent_count]);
                    
                    $message = "تم إرسال تنبيه تخفيض السعر إلى $sent_count مستخدم بنجاح";
                } else {
                    $error = 'المنتج غير موجود';
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        } else {
            $error = 'يرجى اختيار منتج ونسبة تخفيض صحيحة';
        }
    }
    
    if ($_POST['action'] === 'wishlist_promo') {
        $product_id = intval($_POST['product_id']);
        $promo_message = trim($_POST['promo_message']);
        
        if ($product_id && !empty($promo_message)) {
            try {
                // Get product details
                $stmt = $pdo->prepare("SELECT p.*, s.store_name FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product) {
                    // Get users who have this product in their wishlist
                    $stmt = $pdo->prepare("SELECT DISTINCT u.email, u.name FROM users u 
                                          JOIN wishlist w ON u.id = w.user_id 
                                          WHERE w.product_id = ? AND u.email IS NOT NULL AND u.email != ''");
                    $stmt->execute([$product_id]);
                    $recipients = $stmt->fetchAll();
                    
                    $sent_count = 0;
                    foreach ($recipients as $recipient) {
                        if (sendWishlistPromoEmail($recipient['email'], $recipient['name'], $product, $promo_message)) {
                            $sent_count++;
                        }
                    }
                    
                    // Log the campaign
                    $stmt = $pdo->prepare("INSERT INTO email_campaigns (type, product_id, promo_message, sent_count, created_at) VALUES ('wishlist_promo', ?, ?, ?, NOW())");
                    $stmt->execute([$product_id, $promo_message, $sent_count]);
                    
                    $message = "تم إرسال رسالة الترويج إلى $sent_count مستخدم بنجاح";
                } else {
                    $error = 'المنتج غير موجود';
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        } else {
            $error = 'يرجى اختيار منتج ورسالة ترويجية';
        }
    }
}

// Get products for selection
$products = $pdo->query("SELECT p.*, s.store_name FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.approved = 1 ORDER BY p.name")->fetchAll();

// Get campaign statistics
$total_campaigns = $pdo->query("SELECT COUNT(*) FROM email_campaigns")->fetchColumn();
$total_emails_sent = $pdo->query("SELECT SUM(sent_count) FROM email_campaigns")->fetchColumn();
$recent_campaigns = $pdo->query("SELECT * FROM email_campaigns ORDER BY created_at DESC LIMIT 5")->fetchAll();

function sendPriceReductionEmail($email, $name, $product, $discount_percent) {
    $subject = "تخفيض على المنتج: " . $product['name'];
    $new_price = $product['price'] * (1 - $discount_percent / 100);
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: WeBuy <noreply@webuy.com>" . "\r\n";
    
    $html_content = "
    <!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <title>$subject</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1A237E; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .product-card { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .price-old { text-decoration: line-through; color: #999; font-size: 1.2em; }
            .price-new { color: #e74c3c; font-size: 1.5em; font-weight: bold; }
            .discount-badge { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.9em; }
            .btn { display: inline-block; padding: 10px 20px; background: #00BFAE; color: white; text-decoration: none; border-radius: 5px; }
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>WeBuy</h1>
                <p>تخفيض خاص!</p>
            </div>
            <div class='content'>
                <h2>مرحباً $name</h2>
                <p>لدينا تخفيض خاص على منتج قد يهمك:</p>
                
                <div class='product-card'>
                    <h3>" . htmlspecialchars($product['name']) . "</h3>
                    <p>" . htmlspecialchars($product['description']) . "</p>
                    <div style='margin: 15px 0;'>
                        <span class='price-old'>" . number_format($product['price'], 2) . " د.ت</span>
                        <span class='price-new'>" . number_format($new_price, 2) . " د.ت</span>
                        <span class='discount-badge'>تخفيض $discount_percent%</span>
                    </div>
                    <a href='https://webuytn.infy.uk/product.php?id=" . $product['id'] . "' class='btn'>اشتري الآن</a>
                </div>
                
                <p>عرض محدود! لا تفوت هذه الفرصة.</p>
            </div>
            <div class='footer'>
                <p>هذه الرسالة من WeBuy. إذا كنت لا تريد تلقي هذه الرسائل، يمكنك إلغاء الاشتراك من إعدادات حسابك.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return mail($email, $subject, $html_content, $headers);
}

function sendWishlistPromoEmail($email, $name, $product, $promo_message) {
    $subject = "عرض خاص: " . $product['name'];
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: WeBuy <noreply@webuy.com>" . "\r\n";
    
    $html_content = "
    <!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <title>$subject</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1A237E; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .product-card { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .price { color: #00BFAE; font-size: 1.5em; font-weight: bold; }
            .promo-message { background: #FFD600; color: #1A237E; padding: 15px; border-radius: 10px; margin: 15px 0; }
            .btn { display: inline-block; padding: 10px 20px; background: #00BFAE; color: white; text-decoration: none; border-radius: 5px; }
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>WeBuy</h1>
                <p>عرض خاص!</p>
            </div>
            <div class='content'>
                <h2>مرحباً $name</h2>
                <p>لدينا عرض خاص على منتج في قائمة أمنياتك:</p>
                
                <div class='product-card'>
                    <h3>" . htmlspecialchars($product['name']) . "</h3>
                    <p>" . htmlspecialchars($product['description']) . "</p>
                    <div class='price'>" . number_format($product['price'], 2) . " د.ت</div>
                    <div class='promo-message'>
                        <strong>رسالة خاصة:</strong><br>
                        " . nl2br(htmlspecialchars($promo_message)) . "
                    </div>
                    <a href='https://webuytn.infy.uk/product.php?id=" . $product['id'] . "' class='btn'>اشتري الآن</a>
                </div>
                
                <p>عرض محدود! لا تفوت هذه الفرصة.</p>
            </div>
            <div class='footer'>
                <p>هذه الرسالة من WeBuy. إذا كنت لا تريد تلقي هذه الرسائل، يمكنك إلغاء الاشتراك من إعدادات حسابك.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return mail($email, $subject, $html_content, $headers);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حملات البريد الإلكتروني - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .campaigns-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: var(--primary-color); }
        .campaign-form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { height: 150px; resize: vertical; }
        .btn-send { background: var(--primary-color); color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
        .recent-campaigns { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .campaign-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .campaign-item:last-child { border-bottom: none; }
        .campaign-type { font-weight: bold; margin-bottom: 5px; }
        .campaign-meta { color: #666; font-size: 0.9em; }
        .tabs { display: flex; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #f5f5f5; border: none; cursor: pointer; }
        .tab.active { background: var(--primary-color); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="campaigns-container">
        <h1>حملات البريد الإلكتروني</h1>
        
        <?php if ($message): ?>
            <div class="alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_campaigns ?></div>
                <div>إجمالي الحملات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_emails_sent ?></div>
                <div>إجمالي الرسائل المرسلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($products) ?></div>
                <div>المنتجات المتاحة</div>
            </div>
        </div>
        
        <!-- Campaign Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('price-reduction')">تنبيهات تخفيض الأسعار</button>
            <button class="tab" onclick="showTab('wishlist-promo')">ترويج قائمة الأمنيات</button>
        </div>
        
        <!-- Price Reduction Campaign -->
        <div id="price-reduction" class="tab-content active">
            <div class="campaign-form">
                <h2>إرسال تنبيه تخفيض السعر</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="price_reduction">
                    
                    <div class="form-group">
                        <label>اختر المنتج:</label>
                        <select name="product_id" required>
                            <option value="">اختر منتج...</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> - <?= number_format($product['price'], 2) ?> د.ت
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>نسبة التخفيض (%):</label>
                        <input type="number" name="discount_percent" min="1" max="90" placeholder="مثال: 20" required>
                    </div>
                    
                    <button type="submit" class="btn-send">إرسال تنبيه التخفيض</button>
                </form>
            </div>
        </div>
        
        <!-- Wishlist Promotion Campaign -->
        <div id="wishlist-promo" class="tab-content">
            <div class="campaign-form">
                <h2>ترويج قائمة الأمنيات</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="wishlist_promo">
                    
                    <div class="form-group">
                        <label>اختر المنتج:</label>
                        <select name="product_id" required>
                            <option value="">اختر منتج...</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> - <?= number_format($product['price'], 2) ?> د.ت
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>رسالة الترويج:</label>
                        <textarea name="promo_message" placeholder="اكتب رسالة ترويجية خاصة للمنتج..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-send">إرسال رسالة الترويج</button>
                </form>
            </div>
        </div>
        
        <!-- Recent Campaigns -->
        <div class="recent-campaigns">
            <h2>آخر الحملات</h2>
            <?php if ($recent_campaigns): ?>
                <?php foreach ($recent_campaigns as $campaign): ?>
                    <div class="campaign-item">
                        <div class="campaign-type">
                            <?= $campaign['type'] === 'price_reduction' ? 'تنبيه تخفيض السعر' : 'ترويج قائمة الأمنيات' ?>
                        </div>
                        <div class="campaign-meta">
                            المنتج ID: <?= $campaign['product_id'] ?> | 
                            تم الإرسال: <?= $campaign['sent_count'] ?> | 
                            التاريخ: <?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>لا توجد حملات مرسلة بعد.</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="dashboard.php" class="btn">العودة للوحة التحكم</a>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html> 