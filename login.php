<?php
// Comprehensive Security Integration
require_once 'security_integration.php';

// Additional compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
require 'lang.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare('SELECT id, password_hash, is_verified, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && $user['password_hash'] && password_verify($password, $user['password_hash'])) {
        if (!$user['is_verified']) {
            $error = __('email_not_verified');
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Redirect to checkout if that's where they came from
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect_url);
            } else {
                header('Location: index.php');
            }
            exit();
        }
    } else {
        $error = __('invalid_credentials');
    }
}

// Set language
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login'); ?> - WeBuy</title>
    
    <!-- CSS Files -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.5" defer></script>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
    <!-- Main Content -->
    <main id="main-content" role="main">
        <section class="auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1 class="auth-title"><?php echo __('login'); ?></h1>
                            <p class="auth-subtitle"><?php echo __('welcome_back'); ?></p>
                        </div>
                        
                        <?php if (isset($_GET['message']) && $_GET['message'] === 'login_required_cod'): ?>
                            <div class="alert alert--warning">
                                <div class="alert__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </div>
                                <div class="alert__content">
                                    <strong><?php echo __('login_required'); ?></strong>
                                    <p><?php echo __('cod_login_message'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert--danger">
                                <div class="alert__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                </div>
                                <div class="alert__content">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="auth-form" autocomplete="off">
                            <div class="form-group">
                                <label for="email" class="form-label"><?php echo __('email'); ?></label>
                                <div class="form-input-wrapper">
                                    <svg class="form-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="form-input" 
                                           required 
                                           autocomplete="email"
                                           placeholder="<?php echo __('enter_email'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label"><?php echo __('password'); ?></label>
                                <div class="form-input-wrapper">
                                    <svg class="form-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <circle cx="12" cy="16" r="1"></circle>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-input" 
                                           required 
                                           autocomplete="current-password"
                                           placeholder="<?php echo __('enter_password'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn--primary btn--full">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                        <polyline points="10,17 15,12 10,7"></polyline>
                                        <line x1="15" y1="12" x2="3" y2="12"></line>
                                    </svg>
                                    <?php echo __('login'); ?>
                                </button>
                            </div>
                            
                            <div class="auth-consent-notice">
                                <p class="consent-text">
                                    <?php echo __('by_logging_in_you_agree'); ?> 
                                    <a href="terms.php" class="consent-link"><?php echo __('terms_and_conditions'); ?></a>, 
                                    <a href="privacy.php" class="consent-link"><?php echo __('privacy_policy'); ?></a>, 
                                    <?php echo __('and'); ?> 
                                    <a href="cookies.php" class="consent-link"><?php echo __('cookie_policy'); ?></a>.
                                </p>
                            </div>
                        </form>
                        
                        <div class="auth-divider">
                            <span><?php echo __('or'); ?></span>
                        </div>
                        
                        <form action="client/google_login.php" method="get" class="auth-form">
                            <button type="submit" class="btn btn--outline btn--full google-btn">
                                <img src="google-icon.svg" alt="Google" class="google-icon">
                                <?php echo __('login_with_google'); ?>
                            </button>
                        </form>
                        
                        <div class="auth-links">
                            <a href="forgot_password.php" class="auth-link">
                                <?php echo __('forgot_password'); ?>?
                            </a>
                            <span class="auth-separator">•</span>
                            <a href="client/register.php" class="auth-link">
                                <?php echo __('create_new_account'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Cookie Consent Banner -->
    <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 