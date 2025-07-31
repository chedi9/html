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
<!DOCTYPE html>
<html lang="<?php echo isset($_COOKIE['language']) ? $_COOKIE['language'] : ($lang ?? 'en'); ?>" dir="<?php echo (isset($_COOKIE['language']) ? $_COOKIE['language'] : ($lang ?? 'en')) === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'WeBuy - Online Shopping Platform'; ?></title>
    
    <!-- CSS Files - Load in correct order -->
    <link rel="stylesheet" href="../css/main.css">
    
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
    <script src="../js/theme-controller.js" defer></script>
    <script src="../main.js?v=1.5" defer></script>
    
    <!-- Google Analytics (only if consent is given) -->
    <?php
    // Check if user has given consent for analytics cookies
    $cookie_preferences = $_COOKIE['cookie_preferences'] ?? null;
    $analytics_enabled = false;
    
    if ($cookie_preferences) {
        $prefs = json_decode($cookie_preferences, true);
        $analytics_enabled = $prefs['analytics'] ?? false;
    }
    ?>
    
    <?php if ($analytics_enabled): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-PVP8CCFQPL');
    </script>
    <?php endif; ?>
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
                    
                    <!-- Language Select -->
                    <div class="language-select">
                        <button class="language-select__button" id="languageSelect" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'اختر اللغة' : 'Select Language'; ?>" aria-expanded="false">
                            <span class="language-select__flag">
                                <?php 
                                $current_lang = $lang ?? 'en';
                                if ($current_lang === 'ar') echo '🇸🇦';
                                elseif ($current_lang === 'en') echo '🇬🇧';
                                else echo '🇫🇷';
                                ?>
                            </span>
                            <span class="language-select__text">
                                <?php 
                                if ($current_lang === 'ar') echo 'العربية';
                                elseif ($current_lang === 'en') echo 'English';
                                else echo 'Français';
                                ?>
                            </span>
                            <svg class="language-select__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 8l6 6 6-6"/>
                            </svg>
                        </button>
                        <div class="language-select__dropdown" id="languageDropdown">
                            <a href="?lang=ar" class="language-select__option <?php echo $current_lang === 'ar' ? 'language-select__option--active' : ''; ?>">
                                <span class="language-select__flag">🇸🇦</span>
                                <span class="language-select__text">العربية</span>
                            </a>
                            <a href="?lang=en" class="language-select__option <?php echo $current_lang === 'en' ? 'language-select__option--active' : ''; ?>">
                                <span class="language-select__flag">🇬🇧</span>
                                <span class="language-select__text">English</span>
                            </a>
                            <a href="?lang=fr" class="language-select__option <?php echo $current_lang === 'fr' ? 'language-select__option--active' : ''; ?>">
                                <span class="language-select__flag">🇫🇷</span>
                                <span class="language-select__text">Français</span>
                            </a>
                        </div>
                    </div>
                    
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
                    <div class="nav__cart">
                        <a href="cart.php" class="nav__cart-link" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'عربة التسوق' : 'Shopping Cart'; ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="nav__cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                        </a>
                        
                        <!-- Cart Dropdown -->
                        <div class="nav__cart-dropdown">
                            <div class="nav__cart-header">
                                <h3 class="nav__cart-title"><?php echo ($lang ?? 'en') === 'ar' ? 'عربة التسوق' : 'Shopping Cart'; ?></h3>
                            </div>
                            
                            <div class="nav__cart-items">
                                <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                                    <?php 
                                    $cart_total = 0;
                                    $cart_items_display = [];
                                    $cart_keys = array_keys($_SESSION['cart']);
                                    $ids = array_map(function($k){ return explode('|', $k)[0]; }, $cart_keys);
                                    $ids_str = implode(',', array_map('intval', $ids));
                                    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids_str)");
                                    $products_map = [];
                                    while ($row = $stmt->fetch()) {
                                        $products_map[$row['id']] = $row;
                                    }
                                    
                                    foreach (array_slice($cart_keys, 0, 3) as $cart_key): 
                                        $parts = explode('|', $cart_key, 2);
                                        $pid = intval($parts[0]);
                                        $variant = isset($parts[1]) ? $parts[1] : '';
                                        if (!isset($products_map[$pid])) continue;
                                        $product = $products_map[$pid];
                                        $qty = $_SESSION['cart'][$cart_key];
                                        $subtotal = $qty * $product['price'];
                                        $cart_total += $subtotal;
                                        $prod_name = $product['name_' . $lang] ?? $product['name'];
                                    ?>
                                        <div class="nav__cart-item">
                                            <?php if (!empty($product['image'])): ?>
                                                <?php 
                                                $optimized_image = get_optimized_image('uploads/' . $product['image'], 'admin');
                                                ?>
                                                <img src="<?php echo $optimized_image['src']; ?>" 
                                                     alt="<?php echo htmlspecialchars($prod_name); ?>" 
                                                     class="nav__cart-item-image">
                                            <?php else: ?>
                                                <div class="nav__cart-item-image" style="background: var(--color-gray-200); display: flex; align-items: center; justify-content: center;">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                        <polyline points="21,15 16,10 5,21"></polyline>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="nav__cart-item-details">
                                                <h4 class="nav__cart-item-name"><?php echo htmlspecialchars($prod_name); ?></h4>
                                                <div class="nav__cart-item-price"><?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?></div>
                                                <div class="nav__cart-item-qty"><?php echo __('qty'); ?>: <?php echo $qty; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($_SESSION['cart']) > 3): ?>
                                        <div class="nav__cart-item">
                                            <div class="nav__cart-item-details">
                                                <div class="nav__cart-item-name"><?php echo __('and_more_items', ['count' => count($_SESSION['cart']) - 3]); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="nav__cart-empty">
                                        <div class="nav__cart-empty-icon">🛒</div>
                                        <p class="nav__cart-empty-text"><?php echo ($lang ?? 'en') === 'ar' ? 'عربة التسوق فارغة' : 'Your cart is empty'; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                                <div class="nav__cart-footer">
                                    <div class="nav__cart-total">
                                        <span class="nav__cart-total-label"><?php echo __('total'); ?>:</span>
                                        <span class="nav__cart-total-amount"><?php echo number_format($cart_total, 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                    <div class="nav__cart-actions">
                                        <a href="cart.php" class="nav__cart-btn nav__cart-btn--primary"><?php echo __('view_cart'); ?></a>
                                        <a href="checkout.php" class="nav__cart-btn nav__cart-btn--secondary"><?php echo __('checkout'); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
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
                            <a href="client/seller_dashboard.php" class="nav__seller-dashboard" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'لوحة تحكم البائع' : 'Seller Dashboard'; ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                    <path d="M2 17l10 5 10-5"></path>
                                    <path d="M2 12l10 5 10-5"></path>
                                </svg>
                                <span class="nav__seller-dashboard-text"><?php echo ($lang ?? 'en') === 'ar' ? 'لوحة البائع' : 'Seller'; ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
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
                                    <a href="client/account.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'الملف الشخصي' : 'Profile'; ?>
                                    </a>
                                    <a href="client/orders.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'طلباتي' : 'My Orders'; ?>
                                    </a>
                                    <a href="wishlist.php" class="nav__user-link">
                                        <?php echo ($lang ?? 'en') === 'ar' ? 'المفضلة' : 'Wishlist'; ?>
                                    </a>
                                    <a href="client/logout.php" class="nav__user-link">
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