<?php
// Security and compatibility headers
require_once 'security_integration.php';

session_start();
require 'db.php';

$page_title = 'إعادة تعيين كلمة المرور';
$error = '';
$success = '';

// Step 1: Accept token from URL or manual input
$token = $_GET['token'] ?? '';
if (empty($token) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_token'])) {
    $token = trim($_POST['manual_token']);
    if (!empty($token)) {
        // Redirect to self with token in URL
        header('Location: reset_password.php?token=' . urlencode($token));
        exit();
    }
}

if (empty($token)) {
    // Show manual code entry form
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?> - WeBuy</title>
        
        <!-- CSS Files - Load in correct order -->
        <link rel="stylesheet" href="css/base/_variables.css">
        <link rel="stylesheet" href="css/base/_reset.css">
        <link rel="stylesheet" href="css/base/_typography.css">
        <link rel="stylesheet" href="css/base/_utilities.css">
        <link rel="stylesheet" href="css/components/_buttons.css">
        <link rel="stylesheet" href="css/components/_forms.css">
        <link rel="stylesheet" href="css/components/_cards.css">
        <link rel="stylesheet" href="css/components/_navigation.css">
        <link rel="stylesheet" href="css/layout/_grid.css">
        <link rel="stylesheet" href="css/layout/_sections.css">
        <link rel="stylesheet" href="css/layout/_footer.css">
        <link rel="stylesheet" href="css/themes/_light.css">
        <link rel="stylesheet" href="css/themes/_dark.css">
        <link rel="stylesheet" href="css/build.css">
        
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
        
        <!-- JavaScript -->
        <script src="main.js?v=1.2" defer></script>
    </head>
    <body>
        <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px;max-width:600px;margin-left:auto;margin-right:auto;gap:18px;">
            <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode" style="background:#00BFAE;color:#fff;border:none;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-left:16px;cursor:pointer;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:background 0.2s, color 0.2s;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                </svg>
            </button>
        </div>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-logo">🛒 WeBuy</div>
                <h2 class="auth-subtitle">إعادة تعيين كلمة المرور</h2>
                <div class="message error">يرجى إدخال رمز إعادة تعيين كلمة المرور الذي استلمته عبر البريد الإلكتروني.</div>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="manual_token">رمز إعادة التعيين</label>
                        <input type="text" id="manual_token" name="manual_token" class="form-control" placeholder="أدخل الرمز هنا" required>
                    </div>
                    <button type="submit" class="btn-primary">متابعة</button>
                </form>
                <div class="auth-links">
                    <a href="forgot_password.php">← العودة لطلب رابط جديد</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Verify token and get user info
$stmt = $pdo->prepare("
    SELECT pr.user_id, pr.expires_at, u.name, u.email 
    FROM password_resets pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.token = ? AND pr.used = 0
");
$stmt->execute([$token]);
$reset_data = $stmt->fetch();

if (!$reset_data) {
    $error = 'رابط إعادة تعيين كلمة المرور غير صحيح أو منتهي الصلاحية.';
} elseif (strtotime($reset_data['expires_at']) < time()) {
    $error = 'رابط إعادة تعيين كلمة المرور منتهي الصلاحية. يرجى طلب رابط جديد.';
} else {
    // Token is valid, handle password reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $error = 'يرجى إدخال كلمة المرور الجديدة';
        } elseif (strlen($password) < 8) {
            $error = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        } elseif ($password !== $confirm_password) {
            $error = 'كلمة المرور وتأكيد كلمة المرور غير متطابقين';
        } else {
            // Update password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            
            if ($stmt->execute([$password_hash, $reset_data['user_id']])) {
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->execute([$token]);
                
                $success = 'تم إعادة تعيين كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.';
            } else {
                $error = 'حدث خطأ أثناء إعادة تعيين كلمة المرور. يرجى المحاولة مرة أخرى.';
            }
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
    <link rel="stylesheet" href="css/base/_variables.css">
    <link rel="stylesheet" href="css/base/_reset.css">
    <link rel="stylesheet" href="css/base/_typography.css">
    <link rel="stylesheet" href="css/base/_utilities.css">
    <link rel="stylesheet" href="css/components/_buttons.css">
    <link rel="stylesheet" href="css/components/_forms.css">
    <link rel="stylesheet" href="css/components/_cards.css">
    <link rel="stylesheet" href="css/components/_navigation.css">
    <link rel="stylesheet" href="css/layout/_grid.css">
    <link rel="stylesheet" href="css/layout/_sections.css">
    <link rel="stylesheet" href="css/layout/_footer.css">
    <link rel="stylesheet" href="css/themes/_light.css">
    <link rel="stylesheet" href="css/themes/_dark.css">
    <link rel="stylesheet" href="css/build.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="main.js?v=1.2" defer></script>
</head>
<body>
    <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px;max-width:600px;margin-left:auto;margin-right:auto;gap:18px;">
        <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode" style="background:#00BFAE;color:#fff;border:none;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-left:16px;cursor:pointer;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:background 0.2s, color 0.2s;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
            </svg>
        </button>
    </div>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">🛒 WeBuy</div>
            <h2 class="auth-subtitle">إعادة تعيين كلمة المرور</h2>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php if (strpos($error, 'منتهي الصلاحية') !== false): ?>
                    <div class="auth-links">
                        <a href="forgot_password.php">طلب رابط جديد</a>
                    </div>
                <?php endif; ?>
            <?php elseif ($success): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                <div class="auth-links">
                    <a href="login.php">تسجيل الدخول</a>
                </div>
            <?php else: ?>
                <div class="user-info">
                    <p>مرحباً <strong><?php echo htmlspecialchars($reset_data['name']); ?></strong></p>
                    <p>البريد الإلكتروني: <strong><?php echo htmlspecialchars($reset_data['email']); ?></strong></p>
                </div>
                
                <p style="color: #666; margin-bottom: 30px;">أدخل كلمة المرور الجديدة لحسابك</p>
                
                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="password">كلمة المرور الجديدة</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="أدخل كلمة المرور الجديدة" required minlength="8">
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">تأكيد كلمة المرور</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="أعد إدخال كلمة المرور الجديدة" required>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="submitBtn">إعادة تعيين كلمة المرور</button>
                </form>
                
                <div class="auth-links">
                    <a href="login.php">← العودة لتسجيل الدخول</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="main.js?v=1.2"></script>
    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const submitBtn = document.getElementById('submitBtn');
            
            let strength = 0;
            let message = '';
            let className = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength < 3) {
                message = 'كلمة المرور ضعيفة';
                className = 'weak';
                submitBtn.disabled = true;
            } else if (strength < 4) {
                message = 'كلمة المرور متوسطة';
                className = 'medium';
                submitBtn.disabled = false;
            } else {
                message = 'كلمة المرور قوية';
                className = 'strong';
                submitBtn.disabled = false;
            }
            
            strengthDiv.textContent = message;
            strengthDiv.className = 'password-strength ' + className;
        });
        
        // Confirm password checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.style.borderColor = '#dc3545';
                submitBtn.disabled = true;
            } else {
                this.style.borderColor = '#e0e0e0';
                submitBtn.disabled = false;
            }
        });
    </script>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 