<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require '../lang.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../db.php';
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare('SELECT id, password_hash, is_verified FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && $user['password_hash'] && password_verify($password, $user['password_hash'])) {
        if (!$user['is_verified']) {
            $error = __('email_not_verified');
        } else {
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect to checkout if that's where they came from
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ../' . $redirect_url);
            } else {
                header('Location: ../index.php');
            }
            exit();
        }
    } else {
        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل دخول العميل</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .login-container { max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .login-container h2 { text-align: center; margin-bottom: 20px; }
        .login-container label { display: block; margin-bottom: 8px; }
        .login-container input { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; }
        .login-container button { width: 100%; padding: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; }
        .login-container .error { color: red; text-align: center; margin-bottom: 10px; }
        .google-btn { width: 100%; background: #fff; color: #444; border: 1px solid #ccc; padding: 10px; border-radius: 5px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: bold; cursor: pointer; }
        .google-btn img { width: 22px; height: 22px; }
        .register-link { text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="login-container">
        <?php if (isset($_GET['message']) && $_GET['message'] === 'login_required_cod'): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <strong>⚠️ Login Required for Cash on Delivery</strong><br>
                For security reasons, cash on delivery orders require account registration to prevent fraud.
                <br>Please login or create an account to continue.
            </div>
        <?php endif; ?>
        
        <h2><?= __('login') ?></h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="email"><?= __('email') ?>:</label>
            <input type="email" name="email" id="email" required autocomplete="email">
            <label for="password"><?= __('password') ?>:</label>
            <input type="password" name="password" id="password" required autocomplete="current-password">
            <button type="submit"><?= __('login') ?></button>
        </form>
        <form action="google_login.php" method="get" style="margin-top:10px;">
            <button type="submit" class="google-btn">
                <img src="../google-icon.svg" alt="Google"> <?= __('login_with_google') ?>
            </button>
        </form>
        <div class="register-link">
            <?= __('no_account') ?> <a href="register.php"><?= __('create_new_account') ?></a>
        </div>
    </div>
    <script src="../main.js"></script>
    <script>
if (!localStorage.getItem('cookiesAccepted')) {
  // do nothing, wait for accept
} else {
  var gaScript = document.createElement('script');
  gaScript.src = '../https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
  gaScript.async = true;
  document.head.appendChild(gaScript);
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-PVP8CCFQPL');
}
</script>
<script>
var acceptBtn = document.getElementById('acceptCookiesBtn');
if (acceptBtn) {
  acceptBtn.addEventListener('click', function() {
    var gaScript = document.createElement('script');
    gaScript.src = '../https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
    gaScript.async = true;
    document.head.appendChild(gaScript);
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-PVP8CCFQPL');
  });
}
</script>
</body>
</html> 