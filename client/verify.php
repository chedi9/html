<?php
session_start();
require '../lang.php';
require '../db.php';
require_once __DIR__ . '/mailer.php';

if (!isset($_SESSION['pending_email']) || !isset($_SESSION['pending_registration'])) {
    header('Location: register.php');
    exit();
}
$email = $_SESSION['pending_email'];
$pending = $_SESSION['pending_registration'];
$error = '';
$success = false;
$resend_msg = '';

// Handle resend
if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    // Regenerate code and expiry
    $verification_code = random_int(100000, 999999);
    $code_expires_at = date('Y-m-d H:i:s', time() + 15*60);
    $_SESSION['pending_registration']['verification_code'] = $verification_code;
    $_SESSION['pending_registration']['code_expires_at'] = $code_expires_at;
    $mail_ok = send_verification_email($email, $verification_code);
    if (!$mail_ok) {
        $resend_msg = __('email_send_failed');
    } else {
        $resend_msg = __('new_code_sent');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $pending = $_SESSION['pending_registration'];
    if (!$pending) {
        $error = __('no_user_found');
    } elseif ($pending['verification_code'] != $code) {
        $error = __('invalid_code');
    } elseif (strtotime($pending['code_expires_at']) < time()) {
        $error = __('code_expired');
    } else {
        // Insert user into DB
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, is_verified) VALUES (?, ?, ?, 1)');
        $stmt->execute([$pending['name'], $pending['email'], $pending['password_hash']]);
        unset($_SESSION['pending_registration']);
        unset($_SESSION['pending_email']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?= __('verify_email') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .verify-container { max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .verify-container h2 { text-align: center; margin-bottom: 20px; }
        .verify-container label { display: block; margin-bottom: 8px; }
        .verify-container input { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc; }
        .verify-container button { width: 100%; padding: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; }
        .verify-container .error { color: red; text-align: center; margin-bottom: 10px; }
        .verify-container .success { color: green; text-align: center; margin-bottom: 10px; }
        .verify-container .info { color: #1A237E; text-align: center; margin-bottom: 10px; }
        .resend-link { text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="verify-container">
        <h2><?= __('verify_email') ?></h2>
        <?php if ($resend_msg): ?>
            <div class="info"><?php echo $resend_msg; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="success"><?= __('email_verified') ?></div>
            <div style="text-align:center;margin-top:16px;"><a href="login.php" class="btn"><?= __('login') ?></a></div>
        <?php else: ?>
        <form method="post" autocomplete="off">
            <label for="code"><?= __('enter_verification_code') ?>:</label>
            <input type="text" id="code" name="code" required pattern="[0-9]{6}" maxlength="6" autofocus>
            <button type="submit"><?= __('verify') ?></button>
        </form>
        <div class="resend-link">
            <a href="?resend=1" id="resendLink" style="pointer-events:none;opacity:0.5;">إعادة إرسال الرمز؟ (<span id="timer">60</span>)</a>
        </div>
        <?php endif; ?>
    </div>
<script>
let timer = 60;
const resendLink = document.getElementById('resendLink');
const timerSpan = document.getElementById('timer');
const url = new URL(window.location.href);
if (url.searchParams.get('resend') === '1') {
  timer = 60;
}
function updateTimer() {
  if (timer > 0) {
    resendLink.style.pointerEvents = 'none';
    resendLink.style.opacity = '0.5';
    timerSpan.textContent = timer;
    timer--;
    setTimeout(updateTimer, 1000);
  } else {
    resendLink.style.pointerEvents = 'auto';
    resendLink.style.opacity = '1';
    timerSpan.textContent = '';
    resendLink.textContent = 'إعادة إرسال الرمز؟';
  }
}
updateTimer();
</script>
</body>
</html> 