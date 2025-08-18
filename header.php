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

// Compute asset prefix based on script depth (supports nested routes)
$requestDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$asset_prefix = $requestDir === '' ? '' : str_repeat('../', substr_count($requestDir, '/') + 1);
?>
<!-- Header -->
<header class="header" role="banner">
	<div class="container header__container">
		<!-- Left: Logo -->
		<div class="nav__brand">
			<a href="<?php echo $asset_prefix; ?>index.php" class="nav__logo">
				<img src="<?php echo $asset_prefix; ?>webuy-logo-transparent.jpg" alt="WeBuy Logo" style="height: 40px; width: auto;">
				<span class="nav__logo-text">WeBuy</span>
			</a>
		</div>
		<!-- Center: Desktop Navigation (hidden on mobile) -->
		<nav class="nav nav--desktop" role="navigation">
			<ul class="nav__list">
				<li class="nav__item">
					<a href="<?php echo $asset_prefix; ?>index.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav__link--active' : ''; ?>">
						<?php echo ($lang ?? 'en') === 'ar' ? 'الرئيسية' : 'Home'; ?>
					</a>
				</li>
				<li class="nav__item">
					<a href="<?php echo $asset_prefix; ?>store.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'store.php' ? 'nav__link--active' : ''; ?>">
						<?php echo ($lang ?? 'en') === 'ar' ? 'المتجر' : 'Store'; ?>
					</a>
				</li>
				<li class="nav__item">
					<a href="<?php echo $asset_prefix; ?>faq.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'faq.php' ? 'nav__link--active' : ''; ?>">
						<?php echo ($lang ?? 'en') === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?>
					</a>
				</li>
			</ul>
		</nav>
		<!-- Right: Cart, Hamburger, Actions -->
		<div class="header__actions-group">
			<div class="nav__cart nav__cart--topbar">
				<a href="<?php echo $asset_prefix; ?>cart.php" class="nav__cart-link" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'عربة التسوق' : 'Shopping Cart'; ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
						<path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
						<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
					</svg>
					<span class="nav__cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
				</a>
			</div>
			<button class="nav__mobile-toggle" id="mobileMenuToggle" aria-label="Open menu">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="3" y1="12" x2="21" y2="12"></line>
					<line x1="3" y1="6" x2="21" y2="6"></line>
					<line x1="3" y1="18" x2="21" y2="18"></line>
				</svg>
			</button>
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
					<form action="<?php echo $asset_prefix; ?>search_suggest.php" method="GET" class="search-form">
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
						<a href="<?php echo $asset_prefix; ?>client/seller_dashboard.php" class="nav__seller-dashboard" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'لوحة تحكم البائع' : 'Seller Dashboard'; ?>">
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
								<a href="<?php echo $asset_prefix; ?>client/account.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'الملف الشخصي' : 'Profile'; ?>
								</a>
								<a href="<?php echo $asset_prefix; ?>client/orders.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'طلباتي' : 'My Orders'; ?>
								</a>
								<a href="<?php echo $asset_prefix; ?>wishlist.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'المفضلة' : 'Wishlist'; ?>
								</a>
								<a href="<?php echo $asset_prefix; ?>client/logout.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الخروج' : 'Logout'; ?>
								</a>
							</div>
						</div>
					<?php else: ?>
						<a href="<?php echo $asset_prefix; ?>login.php" class="btn btn--secondary btn--sm">
							<?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الدخول' : 'Login'; ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
		<!-- Mobile Navigation Drawer (hidden by default) -->
		<nav class="nav nav--mobile" id="mobileNav" style="display:none;">
			<ul class="nav__list">
				<li class="nav__item">
					<a href="<?php echo $asset_prefix; ?>index.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav__link--active' : ''; ?>">
						<?php echo ($lang ?? 'en') === 'ar' ? 'الرئيسية' : 'Home'; ?>
					</a>
				</li>
				<li class="nav__item">
					<a href="<?php echo $asset_prefix; ?>store.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'store.php' ? 'nav__link--active' : ''; ?>">
						<?php echo ($lang ?? 'en') === 'ar' ? 'المتجر' : 'Store'; ?>
					</a>
				</li>
				<li class="nav__item">
					<a href="<?php echo $asset_prefix; ?>faq.php" class="nav__link <?php echo basename($_SERVER['PHP_SELF']) === 'faq.php' ? 'nav__link--active' : ''; ?>">
						<?php echo ($lang ?? 'en') === 'ar' ? 'الأسئلة الشائعة' : 'FAQ'; ?>
					</a>
				</li>
			</ul>
			<div class="nav__actions nav__actions--mobile">
				<!-- Dark Mode Toggle -->
				<button class="theme-toggle" id="themeToggleMobile" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'تبديل الوضع المظلم' : 'Toggle Dark Mode'; ?>">
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
					<button class="language-select__button" id="languageSelectMobile" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'اختر اللغة' : 'Select Language'; ?>" aria-expanded="false">
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
					<div class="language-select__dropdown" id="languageDropdownMobile">
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
					<form action="<?php echo $asset_prefix; ?>search_suggest.php" method="GET" class="search-form">
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
						<a href="<?php echo $asset_prefix; ?>client/seller_dashboard.php" class="nav__seller-dashboard" aria-label="<?php echo ($lang ?? 'en') === 'ar' ? 'لوحة تحكم البائع' : 'Seller Dashboard'; ?>">
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
								<a href="<?php echo $asset_prefix; ?>client/account.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'الملف الشخصي' : 'Profile'; ?>
								</a>
								<a href="<?php echo $asset_prefix; ?>client/orders.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'طلباتي' : 'My Orders'; ?>
								</a>
								<a href="<?php echo $asset_prefix; ?>wishlist.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'المفضلة' : 'Wishlist'; ?>
								</a>
								<a href="<?php echo $asset_prefix; ?>client/logout.php" class="nav__user-link">
									<?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الخروج' : 'Logout'; ?>
								</a>
							</div>
						</div>
					<?php else: ?>
						<a href="<?php echo $asset_prefix; ?>login.php" class="btn btn--secondary btn--sm">
							<?php echo ($lang ?? 'en') === 'ar' ? 'تسجيل الدخول' : 'Login'; ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</header>