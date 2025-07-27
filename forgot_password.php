<?php
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
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #1A237E 0%, #00BFAE 100%); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
                        .content { padding: 30px; background: #f9f9f9; }
                        .btn { display: inline-block; padding: 15px 30px; background: #00BFAE; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                        .footer { background: #eee; padding: 20px; text-align: center; border-radius: 0 0 12px 12px; }
                        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0; }
                    </style>
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
    <link rel="stylesheet" href="beta333.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .auth-logo {
            font-size: 2.5em;
            font-weight: bold;
            color: #1A237E;
            margin-bottom: 10px;
        }
        .auth-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: right;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #00BFAE;
            box-shadow: 0 0 0 3px rgba(0, 191, 174, 0.1);
        }
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00BFAE 0%, #1A237E 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .auth-links {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .auth-links a {
            color: #00BFAE;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">🛒 WeBuy</div>
            <h2 class="auth-subtitle">نسيت كلمة المرور؟</h2>
            <p style="color: #666; margin-bottom: 30px;">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور</p>
            
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
</body>
</html> 