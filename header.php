<?php
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
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'WeBuy - Online Shopping Platform'; ?></title>
    
    <!-- CSS Files - Load in correct order -->
    <link rel="stylesheet" href="../css/base/_variables.css">
    <link rel="stylesheet" href="../css/base/_reset.css">
    <link rel="stylesheet" href="../css/base/_typography.css">
    <link rel="stylesheet" href="../css/base/_utilities.css">
    <link rel="stylesheet" href="../css/components/_buttons.css">
    <link rel="stylesheet" href="../css/components/_forms.css">
    <link rel="stylesheet" href="../css/components/_cards.css">
    <link rel="stylesheet" href="../css/components/_navigation.css">
    <link rel="stylesheet" href="../css/layout/_grid.css">
    <link rel="stylesheet" href="../css/layout/_sections.css">
    <link rel="stylesheet" href="../css/layout/_footer.css">
    <link rel="stylesheet" href="../css/themes/_light.css">
    <link rel="stylesheet" href="../css/themes/_dark.css">
    <link rel="stylesheet" href="../css/build.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- Additional styles for specific pages -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="../main.js?v=1.3" defer></script>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Header -->
    <header class="header" role="banner">
        <div class="container">
            <nav class="nav" role="navigation">
                <!-- Logo -->
                <div class="nav__brand">
                    <a href="index.php" class="nav__logo">
                        <img src="../webuy-logo-transparent.jpg" alt="WeBuy Logo" style="height: 40px; width: auto;">
                        <span class="nav__logo-text">WeBuy</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="index.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav__link--active' : ''; ?>">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الرئيسية' : 'Home'; ?>
                        </a>
                    </li>
                    <li class="nav__item">
                        <a href="store.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'store.php' ? 'nav__link--active' : ''; ?>">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'المتجر' : 'Store'; ?>
                        </a>
                    </li>
                    <li class="nav__item">
                        <a href="faq.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'faq.php' ? 'nav__link--active' : ''; ?>">
                            <?php echo ($lang ?? 'en') === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?>
                        </a>
                    </li>
                </ul>
                
                <!-- User Actions -->
                <div class="nav__actions">
                    <!-- Dark Mode Toggle -->
                    <button class="theme-toggle" id="themeToggle" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'تبديل الوضع المظلم' : 'Toggle Dark Mode'; ?>">
                        <svg class="theme-toggle__icon theme-toggle__icon--light" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                        <svg class="theme-toggle__icon theme-toggle__icon--dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>
                    
                    <!-- Search -->
                    <div class="nav__search">
                        <form action="search_suggest.php" method="GET" class="search-form">
                            <div class="form__group">
                                <input type="text" name="q" placeholder="<?php echo ($lang ?? 'en') === 'ar' ? 'البحث عن المنتجات...' : 'Search products...'; ?>" 
                                       class="form__input form__input--search" 
                                       autocomplete="off">
                                <button type="submit" class="btn btn--primary btn--sm">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.35-4.35"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Cart -->
                    <a href="cart.php" class="nav__cart" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'عربة التسوق' : 'Shopping Cart'; ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                            <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="nav__cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                    </a>
                    
                    <!-- User Menu -->
                    <div class="nav__user">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="nav__user-menu">
                                <button class="nav__user-toggle" aria-label="User menu">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6"></path>
                                        <path d="M18.36 5.64l-4.24 4.24m0 0l4.24 4.24m-4.24-4.24l4.24-4.24"></path>
                                        <path d="M5.64 5.64l4.24 4.24m0 0l-4.24 4.24m4.24-4.24l-4.24-4.24"></path>
                                    </svg>
                                </button>
                                <div class="nav__user-dropdown">
                                    <a href="/client/account.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'الملف الشخصي' : 'Profile'; ?>
                                    </a>
                                    <a href="my_orders.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'طلباتي' : 'My Orders'; ?>
                                    </a>
                                    <a href="wishlist.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'المفضلة' : 'Wishlist'; ?>
                                    </a>
                                    <a href="logout.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الخروج' : 'Logout'; ?>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn--secondary btn--sm">
                                <?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الدخول' : 'Login'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main id="main-content" role="main"> 