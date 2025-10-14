<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
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
        $stmt = $pdo->prepare('INSERT INTO users (name, email, gender, password_hash, is_verified, is_seller) VALUES (?, ?, ?, ?, 1, ?)');
        $stmt->execute([$pending['name'], $pending['email'], $pending['gender'], $pending['password_hash'], $pending['is_seller']]);
        $user_id = $pdo->lastInsertId();
        // If seller, create seller record
        if (!empty($pending['is_seller'])) {
            $stmt = $pdo->prepare('INSERT INTO sellers (user_id, store_name) VALUES (?, ?)');
            $stmt->execute([$user_id, $pending['name'] . "'s Store"]);
        }
        // Send welcome email based on user type
        if (!empty($pending['is_seller'])) {
            send_welcome_email_seller($pending['email'], $pending['name']);
        } else {
            send_welcome_email_client($pending['email'], $pending['name']);
        }
        unset($_SESSION['pending_registration']);
        unset($_SESSION['pending_email']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= __('verify_email') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    <script src="../main.js?v=1.5" defer></script>
</head>
<body>
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title"><?= __('verify_email') ?></h1>
                    <p class="auth-subtitle"><?php echo sprintf(__('verification_code_sent_to'), '<b>' . htmlspecialchars($email) . '</b>'); ?></p>
                </div>
                <?php if ($resend_msg): ?>
                    <div class="alert alert--info"><?php echo $resend_msg; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert--danger"><?php echo $error; ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert--success"><?= __('email_verified') ?></div>
                    <div class="auth-links">
                        <a href="../login.php" class="btn btn--primary btn--full"><?= __('login') ?></a>
                    </div>
                <?php else: ?>
                <form method="post" class="auth-form" autocomplete="off">
                    <div class="form-group">
                        <label for="code" class="form-label"><?= __('enter_verification_code') ?></label>
                        <div class="form-input-wrapper">
                            <input type="text" id="code" name="code" class="form-input" required pattern="[0-9]{6}" maxlength="6" autofocus autocomplete="one-time-code">
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary btn--full"><?= __('verify') ?></button>
                </form>
                <div class="auth-links resend-link">
                    <a href="?resend=1" id="resendLink" class="auth-link">
                        <?= __('resend_code') ?> (<span id="timer">60</span>)
                    </a>
                </div>
                <?php endif; ?>
                <div class="auth-consent-notice">
                    <p class="consent-text">
                        <?php echo __('by_logging_in_you_agree'); ?> 
                        <a href="../terms.php" class="consent-link"><?php echo __('terms_and_conditions'); ?></a>, 
                        <a href="../privacy.php" class="consent-link"><?php echo __('privacy_policy'); ?></a>, 
                        <?php echo __('and'); ?> 
                        <a href="../cookies.php" class="consent-link"><?php echo __('cookie_policy'); ?></a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
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
        resendLink.textContent = '<?= __('resend_code') ?>';
      }
    }
    updateTimer();
    </script>
    <?php include '../cookie_consent_banner.php'; ?>
</body>
</html> 