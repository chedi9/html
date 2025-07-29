<?php
// Less Aggressive Security Integration for Admin
require_once '../security_integration_admin.php';

// Additional compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
// Session is already started by security_integration.php

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: unified_dashboard.php');
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../db.php'; // Database connection
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Check in admins table
    $stmt = $pdo->prepare('SELECT id, password_hash, role FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = $admin['role'] ?? 'admin'; // Use actual role from database
        $_SESSION['admin_full_name'] = 'Administrator';
        
        logSecurityEvent('admin_login_success_legacy', [
            'username' => $username,
            'role' => $admin['role'] ?? 'admin',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        // All users go to the unified dashboard - permissions are handled within the dashboard
        header('Location: unified_dashboard.php');
        exit();
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        
        logSecurityEvent('admin_login_failed', [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل دخول المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .login-container { max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .login-container h2 { text-align: center; margin-bottom: 20px; }
        .login-container label { display: block; margin-bottom: 8px; }
        .login-container input { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; }
        .login-container button { width: 100%; padding: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; }
        .login-container .error { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>تسجيل دخول المشرف</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="username">اسم المستخدم:</label>
            <input type="text" id="username" name="username" required autofocus>
            <label for="password">كلمة المرور:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="submit">دخول</button>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <a href="forgot_admin_password.php">نسيت كلمة المرور؟</a>
        </div>
    </div>
</body>
</html> 