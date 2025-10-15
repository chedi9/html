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
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login'); ?> - WeBuy</title>
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.5" defer></script>
</head>
<body class="page-transition bg-light">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
    <!-- Main Content -->
    <main id="main-content" role="main">
        <section class="py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="card shadow-sm">
                            <div class="card-body p-4 p-md-5">
                                <div class="text-center mb-4">
                                    <h1 class="h3 fw-bold"><?php echo __('login'); ?></h1>
                                    <p class="text-muted"><?php echo __('welcome_back'); ?></p>
                                </div>
                                
                                <?php if (isset($_GET['message']) && $_GET['message'] === 'login_required_cod'): ?>
                                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                            <line x1="12" y1="9" x2="12" y2="13"></line>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                        <div><?php echo __('login_required_for_cod'); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?php echo $success; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="email" class="form-label"><?php echo __('email'); ?></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label"><?php echo __('password'); ?></label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                        <label class="form-check-label" for="remember_me">
                                            <?php echo __('remember_me'); ?>
                                        </label>
                                    </div>
                                    
                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <?php echo __('login'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="forgot_password.php" class="text-decoration-none">
                                            <?php echo __('forgot_password'); ?>
                                        </a>
                                    </div>
                                </form>
                                
                                <hr class="my-4">
                                
                                <div class="text-center mb-3">
                                    <p class="text-muted"><?php echo __('or_login_with'); ?></p>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="client/google_login.php" class="btn btn-outline-danger">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
                                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                        </svg>
                                        <?php echo __('login_with_google'); ?>
                                    </a>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="text-center">
                                    <p class="text-muted mb-0">
                                        <?php echo __('dont_have_account'); ?>
                                        <a href="client/register.php" class="text-decoration-none fw-semibold">
                                            <?php echo __('register'); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>
