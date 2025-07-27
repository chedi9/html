<?php
session_start();
require '../db.php';

$page_title = 'إعادة تعيين كلمة المرور - المشرف';
$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot_admin_password.php');
    exit();
}

// Verify token and get admin info
$stmt = $pdo->prepare("
    SELECT apr.admin_id, apr.expires_at, a.username, a.email 
    FROM admin_password_resets apr 
    JOIN admins a ON apr.admin_id = a.id 
    WHERE apr.token = ? AND apr.used = 0
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
            $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            
            if ($stmt->execute([$password_hash, $reset_data['admin_id']])) {
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE admin_password_resets SET used = 1 WHERE token = ?");
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
    <link rel="stylesheet" href="../beta333.css">
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
        .password-strength {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }
        .password-strength.weak { color: #dc3545; }
        .password-strength.medium { color: #ffc107; }
        .password-strength.strong { color: #28a745; }
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
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        .user-info strong {
            color: #1A237E;
        }
        .admin-badge {
            background: linear-gradient(135deg, #1A237E 0%, #00BFAE 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">🛒 WeBuy</div>
            <div class="admin-badge">لوحة المشرف</div>
            <h2 class="auth-subtitle">إعادة تعيين كلمة المرور</h2>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php if (strpos($error, 'منتهي الصلاحية') !== false): ?>
                    <div class="auth-links">
                        <a href="forgot_admin_password.php">طلب رابط جديد</a>
                    </div>
                <?php endif; ?>
            <?php elseif ($success): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                <div class="auth-links">
                    <a href="login.php">تسجيل الدخول</a>
                </div>
            <?php else: ?>
                <div class="user-info">
                    <p>مرحباً <strong><?php echo htmlspecialchars($reset_data['username']); ?></strong></p>
                    <p>البريد الإلكتروني: <strong><?php echo htmlspecialchars($reset_data['email']); ?></strong></p>
                </div>
                
                <p style="color: #666; margin-bottom: 30px;">أدخل كلمة المرور الجديدة لحساب المشرف</p>
                
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
</body>
</html> 