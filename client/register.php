<?php
require '../lang.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../db.php';
    require_once __DIR__ . '/mailer.php';
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    // Password strength check
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = __('weak_password');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = __('email_in_use');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = random_int(100000, 999999);
            $code_expires_at = date('Y-m-d H:i:s', time() + 15*60); // 15 min expiry
            $_SESSION['pending_registration'] = [
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'verification_code' => $verification_code,
                'code_expires_at' => $code_expires_at
            ];
            $mail_ok = send_verification_email($email, $verification_code);
            if (!$mail_ok) {
                $error = __('email_send_failed');
            } else {
                $_SESSION['pending_email'] = $email;
                header('Location: verify.php');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= __('register') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .register-container { max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .register-container h2 { text-align: center; margin-bottom: 20px; }
        .register-container label { display: block; margin-bottom: 8px; }
        .register-container input { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; }
        .register-container button { width: 100%; padding: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; }
        .register-container .error { color: red; text-align: center; margin-bottom: 10px; }
        .login-link { text-align: center; margin-top: 10px; }
        .google-btn { width: 100%; background: #fff; color: #444; border: 1px solid #ccc; padding: 10px; border-radius: 5px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: bold; cursor: pointer; }
        .google-btn img { width: 22px; height: 22px; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><?= __('register') ?></h2>
        <form action="google_login.php" method="get" style="margin-bottom:15px;">
            <button type="submit" class="google-btn">
                <img src="../google-icon.svg" alt="Google" style="width:22px;height:22px;vertical-align:middle;"> <?= __('login_with_google') ?>
            </button>
        </form>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="name"><?= __('name') ?>:</label>
            <input type="text" name="name" id="name" required autocomplete="name">
            <label for="email"><?= __('email') ?>:</label>
            <input type="email" name="email" id="email" required autocomplete="email">
            <label for="password"><?= __('password') ?>:</label>
            <input type="password" name="password" id="password" required autocomplete="new-password">
            <label for="confirm_password"><?= __('confirm_password') ?>:</label>
            <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">
            <label for="phone"><?= __('phone') ?>:</label>
            <input type="tel" name="phone" id="phone" required autocomplete="tel">
            <button type="submit"><?= __('register') ?></button>
        </form>
        <div class="login-link">
            <?= __('already_have_account') ?> <a href="login.php"><?= __('login') ?></a>
        </div>
    </div>
</body>
</html> 