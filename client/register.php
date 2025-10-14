<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
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
    $gender = $_POST['gender'] ?? 'prefer_not_to_say';
    $password = $_POST['password'];
    $is_seller = isset($_POST['is_seller']) ? 1 : 0;
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
                'gender' => $gender,
                'password_hash' => $hash,
                'verification_code' => $verification_code,
                'code_expires_at' => $code_expires_at,
                'is_seller' => $is_seller
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
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= __('register') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- WeBuy Custom Bootstrap Configuration -->
    <link rel="stylesheet" href="../css/bootstrap-custom.css">
    
    <!-- Legacy CSS for gradual migration -->
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/pages/_auth.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    <script src="../js/theme-controller.js" defer></script>
    <script src="../main.js?v=1.5" defer></script>
</head>
<body>
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title"><?= __('register') ?></h1>
                    <p class="auth-subtitle"><?= __('create_account_subtitle') ?></p>
                </div>
                <form action="google_login.php" method="get" class="auth-form" style="margin-bottom:15px;">
                    <button type="submit" class="google-btn">
                        <span class="google-icon"><img src="../google-icon.svg" alt="Google"></span>
                        <?= __('login_with_google') ?>
                    </button>
                </form>
                <?php if ($error): ?>
                    <div class="alert alert--danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post" class="auth-form" autocomplete="off">
                    <div class="form-group">
                        <label for="name" class="form-label"><?= __('name') ?></label>
                        <div class="form-input-wrapper">
                            <input type="text" name="name" id="name" class="form-input" required autocomplete="name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label"><?= __('email') ?></label>
                        <div class="form-input-wrapper">
                            <input type="email" name="email" id="email" class="form-input" required autocomplete="email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="gender" class="form-label"><?= __('gender') ?></label>
                        <div class="form-input-wrapper">
                            <select name="gender" id="gender" class="form-input" required>
                                <option value="prefer_not_to_say"><?= __('prefer_not_to_say') ?></option>
                                <option value="male"><?= __('male') ?></option>
                                <option value="female"><?= __('female') ?></option>
                                <option value="other"><?= __('other') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label"><?= __('password') ?></label>
                        <div class="form-input-wrapper">
                            <input type="password" name="password" id="password" class="form-input" required autocomplete="new-password" aria-describedby="passwordHelp">
                        </div>
                        <div id="passwordHelp" class="password-requirements">
                            <ul class="password-req-list">
                                <li id="pw-length" class="pw-req-item">• <?= __('min_8_chars') ?></li>
                                <li id="pw-uppercase" class="pw-req-item">• <?= __('uppercase_required') ?></li>
                                <li id="pw-lowercase" class="pw-req-item">• <?= __('lowercase_required') ?></li>
                                <li id="pw-number" class="pw-req-item">• <?= __('number_required') ?></li>
                                <li id="pw-special" class="pw-req-item">• <?= __('specialchar_required') ?></li>
                            </ul>
                            <div class="password-strength">
                                <span id="pw-strength-label">...</span>
                                <div class="pw-strength-bar" id="pw-strength-bar"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label"><?= __('confirm_password') ?></label>
                        <div class="form-input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-input" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label"><?= __('phone') ?></label>
                        <div class="form-input-wrapper">
                            <input type="tel" name="phone" id="phone" class="form-input" required autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><input type="checkbox" name="is_seller" value="1"> <?= __('register_as_seller') ?></label>
                    </div>
                    <button type="submit" class="btn btn--primary btn--full"><?= __('register') ?></button>
                </form>
                <div class="auth-links">
                    <span class="auth-link-text"><?= __('already_have_account') ?></span>
                    <a href="../login.php" class="auth-link"><?= __('login') ?></a>
                </div>
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
    <?php include '../cookie_consent_banner.php'; ?>
    <script>
// Password strength checker
const pwInput = document.getElementById('password');
const pwLength = document.getElementById('pw-length');
const pwUpper = document.getElementById('pw-uppercase');
const pwLower = document.getElementById('pw-lowercase');
const pwNumber = document.getElementById('pw-number');
const pwSpecial = document.getElementById('pw-special');
const pwStrengthLabel = document.getElementById('pw-strength-label');
const pwStrengthBar = document.getElementById('pw-strength-bar');

function checkPasswordStrength(pw) {
    let score = 0;
    // Requirements
    const hasLength = pw.length >= 8;
    const hasUpper = /[A-Z]/.test(pw);
    const hasLower = /[a-z]/.test(pw);
    const hasNumber = /[0-9]/.test(pw);
    const hasSpecial = /[^A-Za-z0-9]/.test(pw);
    // Visual feedback
    pwLength.classList.toggle('pw-req-met', hasLength);
    pwUpper.classList.toggle('pw-req-met', hasUpper);
    pwLower.classList.toggle('pw-req-met', hasLower);
    pwNumber.classList.toggle('pw-req-met', hasNumber);
    pwSpecial.classList.toggle('pw-req-met', hasSpecial);
    // Score
    score += hasLength ? 1 : 0;
    score += hasUpper ? 1 : 0;
    score += hasLower ? 1 : 0;
    score += hasNumber ? 1 : 0;
    score += hasSpecial ? 1 : 0;
    // Strength label and bar
    let label = '<?= __('weak') ?>';
    let color = '#e74c3c';
    let width = '20%';
    if (score >= 4) {
        label = '<?= __('strong') ?>';
        color = '#27ae60';
        width = '100%';
    } else if (score === 3) {
        label = '<?= __('medium') ?>';
        color = '#f39c12';
        width = '60%';
    }
    pwStrengthLabel.textContent = label;
    pwStrengthBar.style.background = color;
    pwStrengthBar.style.width = width;
}
if (pwInput) {
    pwInput.addEventListener('input', function() {
        checkPasswordStrength(pwInput.value);
    });
    // Initial state
    checkPasswordStrength(pwInput.value);
}
</script>
</body>
</html> 