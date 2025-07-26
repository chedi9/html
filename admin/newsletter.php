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

// Handle newsletter creation
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_newsletter') {
        $subject = trim($_POST['subject']);
        $content = trim($_POST['content']);
        $recipients = $_POST['recipients'];
        
        if (empty($subject) || empty($content)) {
            $error = 'يرجى ملء جميع الحقول المطلوبة';
        } else {
            try {
                // Get recipients based on selection
                $recipient_emails = [];
                
                if ($recipients === 'all') {
                    $stmt = $pdo->query("SELECT email FROM users WHERE email IS NOT NULL AND email != ''");
                    $recipient_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($recipients === 'sellers') {
                    $stmt = $pdo->query("SELECT u.email FROM users u JOIN sellers s ON u.id = s.user_id WHERE u.email IS NOT NULL AND u.email != ''");
                    $recipient_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($recipients === 'customers') {
                    $stmt = $pdo->query("SELECT email FROM users WHERE email IS NOT NULL AND email != '' AND id NOT IN (SELECT user_id FROM sellers)");
                    $recipient_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                
                if (empty($recipient_emails)) {
                    $error = 'لا يوجد مستلمين للرسالة';
                } else {
                    // Send emails
                    $sent_count = 0;
                    foreach ($recipient_emails as $email) {
                        if (sendNewsletterEmail($email, $subject, $content)) {
                            $sent_count++;
                        }
                    }
                    
                    // Log the newsletter
                    $stmt = $pdo->prepare("INSERT INTO newsletter_logs (subject, content, recipients, sent_count, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$subject, $content, $recipients, $sent_count]);
                    
                    $message = "تم إرسال النشرة الإخبارية إلى $sent_count مستلم بنجاح";
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ أثناء إرسال النشرة الإخبارية: ' . $e->getMessage();
            }
        }
    }
}

// Get newsletter statistics
$total_newsletters = $pdo->query("SELECT COUNT(*) FROM newsletter_logs")->fetchColumn();
$total_recipients = $pdo->query("SELECT SUM(sent_count) FROM newsletter_logs")->fetchColumn();
$recent_newsletters = $pdo->query("SELECT * FROM newsletter_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get user counts
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE email IS NOT NULL AND email != ''")->fetchColumn();
$total_sellers = $pdo->query("SELECT COUNT(*) FROM sellers")->fetchColumn();
$total_customers = $total_users - $total_sellers;

function sendNewsletterEmail($email, $subject, $content) {
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
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; }
            .btn { display: inline-block; padding: 10px 20px; background: #00BFAE; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>WeBuy</h1>
                <p>منصة التسوق الشاملة</p>
            </div>
            <div class='content'>
                <h2>$subject</h2>
                " . nl2br(htmlspecialchars($content)) . "
                <br><br>
                <a href='https://webuytn.infy.uk' class='btn'>تسوق الآن</a>
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
    <title>إدارة النشرات الإخبارية - WeBuy</title>
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .newsletter-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: var(--primary-color); }
        .newsletter-form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { height: 200px; resize: vertical; }
        .btn-send { background: var(--primary-color); color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
        .recent-newsletters { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .newsletter-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .newsletter-item:last-child { border-bottom: none; }
        .newsletter-subject { font-weight: bold; margin-bottom: 5px; }
        .newsletter-meta { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="newsletter-container">
        <h1>إدارة النشرات الإخبارية</h1>
        
        <?php if ($message): ?>
            <div class="alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_newsletters ?></div>
                <div>إجمالي النشرات المرسلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_recipients ?></div>
                <div>إجمالي المستلمين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_users ?></div>
                <div>إجمالي المستخدمين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_sellers ?></div>
                <div>إجمالي البائعين</div>
            </div>
        </div>
        
        <!-- Newsletter Form -->
        <div class="newsletter-form">
            <h2>إرسال نشرة إخبارية جديدة</h2>
            <form method="POST">
                <input type="hidden" name="action" value="send_newsletter">
                
                <div class="form-group">
                    <label>المستلمين:</label>
                    <select name="recipients" required>
                        <option value="all">جميع المستخدمين (<?= $total_users ?>)</option>
                        <option value="sellers">البائعين فقط (<?= $total_sellers ?>)</option>
                        <option value="customers">العملاء فقط (<?= $total_customers ?>)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>الموضوع:</label>
                    <input type="text" name="subject" placeholder="أدخل موضوع النشرة الإخبارية" required>
                </div>
                
                <div class="form-group">
                    <label>المحتوى:</label>
                    <textarea name="content" placeholder="أدخل محتوى النشرة الإخبارية..." required></textarea>
                </div>
                
                <button type="submit" class="btn-send">إرسال النشرة الإخبارية</button>
            </form>
        </div>
        
        <!-- Recent Newsletters -->
        <div class="recent-newsletters">
            <h2>آخر النشرات الإخبارية</h2>
            <?php if ($recent_newsletters): ?>
                <?php foreach ($recent_newsletters as $newsletter): ?>
                    <div class="newsletter-item">
                        <div class="newsletter-subject"><?= htmlspecialchars($newsletter['subject']) ?></div>
                        <div class="newsletter-meta">
                            المستلمين: <?= $newsletter['recipients'] ?> | 
                            تم الإرسال: <?= $newsletter['sent_count'] ?> | 
                            التاريخ: <?= date('Y-m-d H:i', strtotime($newsletter['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>لا توجد نشرات إخبارية مرسلة بعد.</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="dashboard.php" class="btn">العودة للوحة التحكم</a>
        </div>
    </div>
</body>
</html> 