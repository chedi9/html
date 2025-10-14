<?php
// Security and compatibility headers
require_once 'security_integration.php';

session_start();
require 'db.php';

$page_title = 'نسيت كلمة المرور';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'يرجى إدخال بريدك الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            if ($stmt->execute([$user['id'], $token, $expires])) {
                // Send reset email
                $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                
                $subject = "إعادة تعيين كلمة المرور - WeBuy";
                $message = "
                <html dir='rtl'>
                <head>
                    <meta charset='UTF-8'>
                    <title>إعادة تعيين كلمة المرور</title>
                    
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🔐 إعادة تعيين كلمة المرور</h1>
                            <p>مرحباً " . htmlspecialchars($user['name']) . "</p>
                        </div>
                        <div class='content'>
                            <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في WeBuy.</p>
                            <p>إذا لم تطلب هذا التغيير، يمكنك تجاهل هذا البريد الإلكتروني.</p>
                            <div style='text-align: center;'>
                                <a href='$reset_link' class='btn'>إعادة تعيين كلمة المرور</a>
                            </div>
                            <div class='warning'>
                                <strong>⚠️ تنبيه:</strong> هذا الرابط صالح لمدة ساعة واحدة فقط. بعد انتهاء المدة، ستحتاج إلى طلب رابط جديد.
                            </div>
                            <p>إذا لم يعمل الزر أعلاه، يمكنك نسخ ولصق الرابط التالي في متصفحك:</p>
                            <p style='word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px;'>$reset_link</p>
                        </div>
                        <div class='footer'>
                            <p>هذا البريد الإلكتروني تم إرساله من WeBuy</p>
                            <p>للمساعدة والدعم، تواصل مع فريق WeBuy</p>
                        </div>
                    </div>
                </body>
                </html>";
                
                // Make sure mailer is included
                require_once __DIR__ . '/client/mailer.php';
                // Replace sendEmail() with send_user_reset_email()
                // Example usage:
                // send_user_reset_email($user_email, $user_name, $reset_code);
                if (send_user_reset_email($email, $user['name'], $token)) {
                    $success = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني. يرجى التحقق من صندوق الوارد الخاص بك.';
                } else {
                    $error = 'حدث خطأ أثناء إرسال البريد الإلكتروني. يرجى المحاولة مرة أخرى.';
                }
            } else {
                $error = 'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.';
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = 'إذا كان هذا البريد الإلكتروني مسجل في نظامنا، ستتلقى رابط إعادة تعيين كلمة المرور قريباً.';
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - WeBuy</title>
    
    <!-- CSS Files - Load in correct order -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="main.js?v=1.2" defer></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">🛒 WeBuy</div>
            <h2 class="auth-subtitle">نسيت كلمة المرور؟</h2>
            <p>أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور</p>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="أدخل بريدك الإلكتروني" required>
                </div>
                
                <button type="submit" class="btn-primary">إرسال رابط إعادة التعيين</button>
            </form>
            
            <div class="auth-links">
                <a href="login.php">← العودة لتسجيل الدخول</a>
            </div>
        </div>
    </div>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 