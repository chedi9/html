<?php
// Security and compatibility headers
require_once 'security_integration.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language if not defined
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
}

// Include language file if not already included
if (!function_exists('__')) {
    require_once 'lang.php';
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = '';
if ($is_logged_in) {
    require_once 'db.php';
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $user_name = $user['name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'WeBuy - Online Shopping Platform'; ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #00BFAE 0%, #00A693 100%);
            box-shadow: 0 4px 20px rgba(0, 191, 174, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0;
        }
        
        .header__container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 70px;
        }
        
        /* Logo Styles */
        .header__logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-size: 28px;
            font-weight: bold;
            transition: transform 0.3s ease;
        }
        
        .header__logo:hover {
            transform: scale(1.05);
        }
        
        .header__logo-icon {
            width: 40px;
            height: 40px;
            margin-right: 12px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        /* Navigation Styles */
        .header__nav {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .nav__link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav__link:hover,
        .nav__link--active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        /* Actions Group */
        .header__actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Search Bar */
        .header__search {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search__input {
            width: 300px;
            padding: 12px 45px 12px 16px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            transition: all 0.3s ease;
        }
        
        .search__input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            width: 350px;
        }
        
        .search__button {
            position: absolute;
            right: 5px;
            background: #00A693;
            border: none;
            padding: 8px 12px;
            border-radius: 20px;
            color: white;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .search__button:hover {
            background: #008a7a;
        }
        
        /* Language Selector */
        .language-selector {
            position: relative;
        }
        
        .language-button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .language-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            padding: 8px 0;
            min-width: 150px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .language-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .language-option {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        
        .language-option:hover,
        .language-option.active {
            background: #f1f3f4;
        }
        
        /* Cart Button */
        .cart-button {
            position: relative;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cart-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
        }
        
        .user-button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
            text-decoration: none;
            font-size: 14px;
        }
        
        .user-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            padding: 8px 0;
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .user-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown-item {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        
        .user-dropdown-item:hover {
            background: #f1f3f4;
        }
        
        /* Mobile Menu Toggle */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            .header__container {
                padding: 0 15px;
                min-height: 60px;
            }
            
            .header__nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #00A693;
                flex-direction: column;
                gap: 0;
                padding: 20px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
            
            .header__nav.active {
                display: flex;
            }
            
            .nav__link {
                padding: 15px 0;
                width: 100%;
                text-align: center;
                border-radius: 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .nav__link:last-child {
                border-bottom: none;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .header__search {
                display: none;
            }
            
            .search__input {
                width: 200px;
            }
            
            .search__input:focus {
                width: 250px;
            }
            
            .header__actions {
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .header__logo {
                font-size: 24px;
            }
            
            .header__logo-icon {
                width: 35px;
                height: 35px;
                margin-right: 8px;
            }
            
            .user-button,
            .language-button {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
        
        /* RTL Support */
        [dir="rtl"] .header__logo-icon {
            margin-right: 0;
            margin-left: 12px;
        }
        
        [dir="rtl"] .language-dropdown,
        [dir="rtl"] .user-dropdown {
            right: auto;
            left: 0;
        }
        
        [dir="rtl"] .search__button {
            right: auto;
            left: 5px;
        }
        
        [dir="rtl"] .cart-count {
            right: auto;
            left: -5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header__container">
            <!-- Logo -->
            <a href="index.php" class="header__logo">
                <div class="header__logo-icon">🛒</div>
                WeBuy
            </a>
            
            <!-- Main Navigation -->
            <nav class="header__nav" id="mainNav">
                <a href="index.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav__link--active' : ''; ?>">
                    <?php echo __('home'); ?>
                </a>
                <a href="store.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'store.php' ? 'nav__link--active' : ''; ?>">
                    <?php echo __('categories'); ?>
                </a>
                <a href="#" class="nav__link">
                    <?php echo __('about'); ?>
                </a>
                <a href="#" class="nav__link">
                    <?php echo __('contact'); ?>
                </a>
                <a href="faq.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'faq.php' ? 'nav__link--active' : ''; ?>">
                    <?php echo __('faq'); ?>
                </a>
            </nav>
            
            <!-- Actions Group -->
            <div class="header__actions">
                <!-- Search Bar -->
                <div class="header__search">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" class="search__input" placeholder="<?php echo __('search_placeholder'); ?>" />
                        <button type="submit" class="search__button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </form>
                </div>
                
                <!-- Language Selector -->
                <div class="language-selector">
                    <button class="language-button" onclick="toggleLanguageDropdown()">
                        <?php 
                        $current_lang = $lang ?? 'en';
                        if ($current_lang === 'ar') echo '🇸🇦 العربية';
                        elseif ($current_lang === 'en') echo '🇬🇧 English';
                        else echo '🇫🇷 Français';
                        ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    <div class="language-dropdown" id="languageDropdown">
                        <a href="?lang=ar" class="language-option <?php echo $current_lang === 'ar' ? 'active' : ''; ?>">🇸🇦 العربية</a>
                        <a href="?lang=en" class="language-option <?php echo $current_lang === 'en' ? 'active' : ''; ?>">🇬🇧 English</a>
                        <a href="?lang=fr" class="language-option <?php echo $current_lang === 'fr' ? 'active' : ''; ?>">🇫🇷 Français</a>
                    </div>
                </div>
                
                <!-- Cart Button -->
                <a href="cart.php" class="cart-button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                        <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- User Menu -->
                <div class="user-menu">
                    <?php if ($is_logged_in): ?>
                        <button class="user-button" onclick="toggleUserDropdown()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                            </svg>
                            <?php echo htmlspecialchars(substr($user_name, 0, 15)) . (strlen($user_name) > 15 ? '...' : ''); ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="client/account.php" class="user-dropdown-item"><?php echo __('account'); ?></a>
                            <a href="my_orders.php" class="user-dropdown-item"><?php echo __('orders'); ?></a>
                            <a href="wishlist.php" class="user-dropdown-item"><?php echo __('wishlist'); ?></a>
                            <hr style="margin: 8px 0; border: none; border-top: 1px solid #eee;">
                            <button onclick="logout()" class="user-dropdown-item"><?php echo __('logout'); ?></button>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="user-button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php echo __('login'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-toggle" onclick="toggleMobileMenu()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <script>
        // Language dropdown toggle
        function toggleLanguageDropdown() {
            const dropdown = document.getElementById('languageDropdown');
            dropdown.classList.toggle('active');
            
            // Close user dropdown if open
            document.getElementById('userDropdown')?.classList.remove('active');
        }
        
        // User dropdown toggle
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
            
            // Close language dropdown if open
            document.getElementById('languageDropdown').classList.remove('active');
        }
        
        // Mobile menu toggle
        function toggleMobileMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const languageSelector = document.querySelector('.language-selector');
            const userMenu = document.querySelector('.user-menu');
            
            if (!languageSelector.contains(event.target)) {
                document.getElementById('languageDropdown').classList.remove('active');
            }
            
            if (!userMenu?.contains(event.target)) {
                document.getElementById('userDropdown')?.classList.remove('active');
            }
        });
        
        // Logout function
        function logout() {
            if (confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد من تسجيل الخروج؟' : 'Are you sure you want to logout?'; ?>')) {
                window.location.href = 'logout.php';
            }
        }
        
        // Enhanced search functionality
        document.querySelector('.search__input')?.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        document.querySelector('.search__input')?.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    </script>