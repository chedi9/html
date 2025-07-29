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

// Handle tips sending
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_tips') {
        $tip_type = $_POST['tip_type'] ?? 'custom';
        $custom_message = trim($_POST['custom_message'] ?? '');
        
        if (!empty($custom_message)) {
            try {
                // Get all sellers
                $stmt = $pdo->query("SELECT u.email, u.name, s.store_name FROM users u JOIN sellers s ON u.id = s.user_id WHERE u.email IS NOT NULL AND u.email != ''");
                $sellers = $stmt->fetchAll();
                
                if (empty($sellers)) {
                    $error = 'لا يوجد بائعين مسجلين';
                } else {
                    $sent_count = 0;
                    foreach ($sellers as $seller) {
                        if (sendSellerTipEmail($seller['email'], $seller['name'], $seller['store_name'], $tip_type, $custom_message)) {
                            $sent_count++;
                        }
                    }
                    
                    // Log the tips campaign
                    $stmt = $pdo->prepare("INSERT INTO email_campaigns (type, promo_message, sent_count, created_at) VALUES ('seller_tips', ?, ?, NOW())");
                    $stmt->execute([$custom_message, $sent_count]);
                    
                    $message = "تم إرسال النصائح إلى $sent_count بائع بنجاح";
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        } else {
            $error = 'يرجى كتابة رسالة النصائح';
        }
    }
}

// Get seller statistics
$total_sellers = $pdo->query("SELECT COUNT(*) FROM sellers")->fetchColumn();
$active_sellers = $pdo->query("SELECT COUNT(*) FROM sellers s JOIN products p ON s.id = p.seller_id WHERE p.approved = 1")->fetchColumn();
$recent_tips = $pdo->query("SELECT * FROM email_campaigns WHERE type = 'seller_tips' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Predefined tips
$predefined_tips = [
    'product_photos' => [
        'title' => 'نصائح لتصوير المنتجات',
        'content' => '• استخدم إضاءة جيدة وطبيعية\n• التقط صور من زوايا مختلفة\n• تأكد من وضوح الصور\n• استخدم خلفية بسيطة ونظيفة\n• التقط صور تفصيلية للمنتج'
    ],
    'pricing' => [
        'title' => 'نصائح لتسعير المنتجات',
        'content' => '• ابحث عن أسعار المنافسين\n• احسب تكاليف الإنتاج والشحن\n• اترك هامش ربح معقول\n• فكر في العروض والخصومات\n• راقب الأسعار بانتظام'
    ],
    'customer_service' => [
        'title' => 'نصائح لخدمة العملاء',
        'content' => '• رد على الرسائل بسرعة\n• كن مهذباً ومهنياً\n• قدم معلومات واضحة ودقيقة\n• تعامل مع الشكاوى بجدية\n• اطلب تقييمات إيجابية'
    ],
    'marketing' => [
        'title' => 'نصائح للتسويق',
        'content' => '• اكتب أوصاف جذابة للمنتجات\n• استخدم كلمات مفتاحية مناسبة\n• شارك قصتك مع العملاء\n• استخدم وسائل التواصل الاجتماعي\n• اعرض منتجاتك بانتظام'
    ],
    'inventory' => [
        'title' => 'نصائح لإدارة المخزون',
        'content' => '• راقب المخزون بانتظام\n• حدد المنتجات الأكثر مبيعاً\n• خطط للموسميات\n• احتفظ بسجلات دقيقة\n• تجنب نفاد المخزون'
    ]
];

function sendSellerTipEmail($email, $name, $store_name, $tip_type, $custom_message) {
    $subject = "نصائح مفيدة لتحسين متجرك - WeBuy";
    
    // Use the email helper for consistent email sending
    require_once 'email_helper.php';
    
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
            .tip-card { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .tip-title { color: #1A237E; font-size: 1.3em; margin-bottom: 15px; }
            .tip-content { line-height: 1.8; }
            .btn { display: inline-block; padding: 10px 20px; background: #00BFAE; color: white; text-decoration: none; border-radius: 5px; }
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>WeBuy</h1>
                <p>نصائح لتحسين متجرك</p>
            </div>
            <div class='content'>
                <h2>مرحباً $name</h2>
                <p>نحن هنا لمساعدتك في تحسين متجرك <strong>$store_name</strong> وزيادة مبيعاتك!</p>
                
                <div class='tip-card'>
                    <div class='tip-title'>💡 نصائح مفيدة</div>
                    <div class='tip-content'>
                        " . nl2br(htmlspecialchars($custom_message)) . "
                    </div>
                </div>
                
                <p>تذكر أن نجاحك هو نجاحنا! نحن هنا لدعمك في كل خطوة.</p>
                
                <a href='https://webuytn.infy.uk/client/seller_dashboard.php' class='btn'>إدارة متجري</a>
            </div>
            <div class='footer'>
                <p>هذه الرسالة من WeBuy. إذا كنت لا تريد تلقي هذه الرسائل، يمكنك إلغاء الاشتراك من إعدادات حسابك.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmail($email, $name, $subject, $html_content);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصائح البائعين - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .tips-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: var(--primary-color); }
        .tips-form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { height: 200px; resize: vertical; }
        .btn-send { background: var(--primary-color); color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
        .predefined-tips { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .tip-item { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s; }
        .tip-item:hover { border-color: var(--primary-color); background: #f9f9f9; }
        .tip-title { font-weight: bold; color: var(--primary-color); margin-bottom: 8px; }
        .tip-content { color: #666; font-size: 0.9em; }
        .recent-tips { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .tip-log-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .tip-log-item:last-child { border-bottom: none; }
        .tip-log-meta { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="tips-container">
        <h1>نصائح البائعين</h1>
        
        <?php if ($message): ?>
            <div class="alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_sellers ?></div>
                <div>إجمالي البائعين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $active_sellers ?></div>
                <div>البائعين النشطين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($recent_tips) ?></div>
                <div>آخر النصائح المرسلة</div>
            </div>
        </div>
        
        <!-- Tips Form -->
        <div class="tips-form">
            <h2>إرسال نصائح للبائعين</h2>
            <form method="POST">
                <input type="hidden" name="action" value="send_tips">
                
                <div class="form-group">
                    <label>نوع النصيحة:</label>
                    <select id="tipType" onchange="loadPredefinedTip()">
                        <option value="">اختر نوع النصيحة...</option>
                        <option value="product_photos">تصوير المنتجات</option>
                        <option value="pricing">تسعير المنتجات</option>
                        <option value="customer_service">خدمة العملاء</option>
                        <option value="marketing">التسويق</option>
                        <option value="inventory">إدارة المخزون</option>
                        <option value="custom">رسالة مخصصة</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>رسالة النصائح:</label>
                    <textarea name="custom_message" id="customMessage" placeholder="اكتب نصائح مفيدة للبائعين..." required></textarea>
                </div>
                
                <button type="submit" class="btn-send">إرسال النصائح للبائعين</button>
            </form>
        </div>
        
        <!-- Predefined Tips -->
        <div class="predefined-tips">
            <h2>نصائح جاهزة</h2>
            <p>انقر على أي نصيحة لتحميلها في النموذج:</p>
            
            <?php foreach ($predefined_tips as $key => $tip): ?>
                <div class="tip-item" onclick="loadTip('<?= $key ?>')">
                    <div class="tip-title"><?= htmlspecialchars($tip['title']) ?></div>
                    <div class="tip-content"><?= nl2br(htmlspecialchars($tip['content'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Recent Tips -->
        <div class="recent-tips">
            <h2>آخر النصائح المرسلة</h2>
            <?php if ($recent_tips): ?>
                <?php foreach ($recent_tips as $tip): ?>
                    <div class="tip-log-item">
                        <div class="tip-log-meta">
                            تم الإرسال: <?= $tip['sent_count'] ?> بائع | 
                            التاريخ: <?= date('Y-m-d H:i', strtotime($tip['created_at'])) ?>
                        </div>
                        <div style="margin-top: 8px;">
                            <?= htmlspecialchars(substr($tip['promo_message'], 0, 200)) ?>...
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>لا توجد نصائح مرسلة بعد.</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="unified_dashboard.php" class="btn">العودة للوحة التحكم</a>
        </div>
    </div>
    
    <script>
        const predefinedTips = <?= json_encode($predefined_tips) ?>;
        
        function loadTip(tipKey) {
            if (predefinedTips[tipKey]) {
                document.getElementById('tipType').value = tipKey;
                document.getElementById('customMessage').value = predefinedTips[tipKey].content;
            }
        }
        
        function loadPredefinedTip() {
            const tipType = document.getElementById('tipType').value;
            if (tipType && tipType !== 'custom' && predefinedTips[tipType]) {
                document.getElementById('customMessage').value = predefinedTips[tipType].content;
            } else if (tipType === 'custom') {
                document.getElementById('customMessage').value = '';
            }
        }
    </script>
</body>
</html> 