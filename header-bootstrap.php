<?php
// Security and compatibility headers
require_once 'security_integration.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language if not defined
if (!isset($lang)) {
    $lang = 'en'; // Default to English
}

// Include language file if not already included
if (!function_exists('__')) {
    require_once 'lang.php';
}
?>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Bootstrap Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" role="banner">
        <div class="container">
            <!-- Brand/Logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="webuy-logo-transparent.jpg" alt="WeBuy Logo" style="height: 40px; width: auto;">
                <span class="ms-2 fw-bold">WeBuy</span>
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الرئيسية' : 'Home'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'store.php' ? 'active' : ''; ?>" href="store.php">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'المتجر' : 'Store'; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'faq.php' ? 'active' : ''; ?>" href="faq.php">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?>
                        </a>
                    </li>
                </ul>
                
                <!-- Right Side Actions -->
                <ul class="navbar-nav">
                    <!-- Cart -->
                    <li class="nav-item">
                        <a href="cart.php" class="nav-link position-relative" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'عربة التسوق' : 'Shopping Cart'; ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <?php $cart_count = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <button class="btn btn-outline-secondary btn-sm" id="themeToggle" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'تبديل الوضع المظلم' : 'Toggle Dark Mode'; ?>">
                            <svg class="theme-toggle__icon theme-toggle__icon--light" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="5"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                            </svg>
                            <svg class="theme-toggle__icon theme-toggle__icon--dark d-none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </button>
                    </li>
                    
                    <!-- Language Selector -->
                    <li class="nav-item dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                            $current_lang = $lang ?? 'en';
                            if ($current_lang === 'ar') echo '🇸🇦 العربية';
                            elseif ($current_lang === 'en') echo '🇬🇧 English';
                            else echo '🇫🇷 Français';
                            ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item <?php echo $current_lang === 'ar' ? 'active' : ''; ?>" href="?lang=ar">🇸🇦 العربية</a></li>
                            <li><a class="dropdown-item <?php echo $current_lang === 'en' ? 'active' : ''; ?>" href="?lang=en">🇬🇧 English</a></li>
                            <li><a class="dropdown-item <?php echo $current_lang === 'fr' ? 'active' : ''; ?>" href="?lang=fr">🇫🇷 Français</a></li>
                        </ul>
                    </li>
                    
                    <!-- Search -->
                    <li class="nav-item">
                        <form action="search_suggest.php" method="GET" class="d-flex">
                            <input type="text" name="q" placeholder="<?php echo ($lang ?? 'en') === 'ar' ? 'البحث عن المنتجات...' : 'Search products...'; ?>" 
                                   class="form-control form-control-sm me-2" 
                                   autocomplete="off">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                            </button>
                        </form>
                    </li>
                    
                    <!-- Seller Dashboard Button (only for sellers) -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        // Check if user is a seller
                        require_once 'db.php';
                        $user_id = $_SESSION['user_id'];
                        $stmt = $pdo->prepare('SELECT is_seller FROM users WHERE id = ?');
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                        ?>
                        <?php if (!empty($user['is_seller'])): ?>
                            <li class="nav-item">
                                <a href="client/seller_dashboard.php" class="btn btn-outline-primary btn-sm" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'لوحة تحكم البائع' : 'Seller Dashboard'; ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                        <path d="M2 17l10 5 10-5"></path>
                                        <path d="M2 12l10 5 10-5"></path>
                                    </svg>
                                    <span class="ms-1"><?php echo ($lang ?? 'en') === 'ar' ? 'لوحة البائع' : 'Seller'; ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- User Menu -->
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6"></path>
                                        <path d="M18.36 5.64l-4.24 4.24m0 0l4.24 4.24m-4.24-4.24l4.24-4.24"></path>
                                        <path d="M5.64 5.64l4.24 4.24m0 0l-4.24 4.24m4.24-4.24l-4.24-4.24"></path>
                                    </svg>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="client/account.php">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'الملف الشخصي' : 'Profile'; ?>
                                    </a></li>
                                    <li><a class="dropdown-item" href="client/orders.php">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'طلباتي' : 'My Orders'; ?>
                                    </a></li>
                                    <li><a class="dropdown-item" href="wishlist.php">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'المفضلة' : 'Wishlist'; ?>
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="client/logout.php">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الخروج' : 'Logout'; ?>
                                    </a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-secondary btn-sm">
                                <?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الدخول' : 'Login'; ?>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main id="main-content" role="main">
