<?php
// Simplified index.php without complex security features
require 'db.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language if not defined
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

// Simple translation function
$lang_file = __DIR__ . '/lang/' . $lang . '.php';
$trans = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/lang/ar.php';
function __($key) {
    global $trans;
    return $trans[$key] ?? $key;
}

// Fetch products
$products = $pdo->query("SELECT p.*, s.is_disabled FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.approved = 1 ORDER BY s.is_disabled DESC, p.created_at DESC LIMIT 8")->fetchAll();

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Fetch recently viewed products
$recently_viewed = [];
if (!empty($_SESSION['viewed_products'])) {
    $ids = array_reverse($_SESSION['viewed_products']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT p.*, s.is_disabled FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ids as $id) {
        foreach ($all as $prod) {
            if ($prod['id'] == $id) {
                $recently_viewed[] = $prod;
                break;
            }
        }
    }
}

// Get priority products (simple version)
$priority_products = $pdo->query("SELECT p.*, s.is_disabled FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.approved = 1 ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBuy - Online Shopping Platform</title>
    
    <!-- CSS Files - Load in correct order -->
    <link rel="stylesheet" href="css/main.css">
    
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
    <a href="#main-content" class="skip-link"><?php echo __('skip_to_main_content'); ?></a>
    
    <?php 
    // Simple header inclusion - if header.php fails, show basic header
    try {
        include 'header.php'; 
    } catch (Exception $e) {
        echo '<header><h1>WeBuy</h1><nav><a href="store.php">Store</a> | <a href="login.php">Login</a></nav></header>';
    }
    ?>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert--success" id="flashMessage">
            <div class="container">
                <p><?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?></p>
                <button class="alert__close" onclick="this.parentElement.parentElement.style.display='none'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero__content">
                    <div class="hero__text">
                        <h1 class="hero__title">
                            <?php echo __('discover_tunisian_talents'); ?>
                        </h1>
                        <p class="hero__subtitle">
                            <?php echo __('webuy_platform_description'); ?>
                        </p>
                        <div class="hero__features">
                            <div class="hero__feature">
                                <span class="hero__feature-icon">🌟</span>
                                <span class="hero__feature-text"><?php echo __('support_disabled_sellers'); ?></span>
                            </div>
                            <div class="hero__feature">
                                <span class="hero__feature-icon">🚚</span>
                                <span class="hero__feature-text"><?php echo __('fast_delivery'); ?></span>
                            </div>
                            <div class="hero__feature">
                                <span class="hero__feature-icon">💳</span>
                                <span class="hero__feature-text"><?php echo __('secure_payment'); ?></span>
                            </div>
                        </div>
                        <div class="hero__actions">
                            <a href="#categories" class="btn btn--primary btn--lg hero__btn">
                                <?php echo __('browse_categories'); ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12,5 19,12 12,19"></polyline>
                                </svg>
                            </a>
                            <a href="store.php" class="btn btn--secondary btn--lg hero__btn">
                                <?php echo __('shop_now'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="hero__visual">
                        <!-- Hero images removed for mobile/cleaner look -->
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Featured Categories -->
        <section class="section" id="categories">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__title">
                        <?php echo __('featured_categories'); ?>
                    </h2>
                    <p class="section__subtitle">
                        <?php echo __('discover_diverse_collection'); ?>
                    </p>
                </div>
                
                <?php if (!empty($categories)): ?>
                    <div class="categories-grid">
                        <?php foreach ($categories as $category): ?>
                            <div class="category-card">
                                <div class="category-card__content">
                                    <h3 class="category-card__title"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <p class="category-card__description"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                                    <a href="store.php?category=<?php echo $category['id']; ?>" class="category-card__link">
                                        <?php echo __('view_products'); ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12,5 19,12 12,19"></polyline>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php echo __('no_categories_found'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Featured Products -->
        <section class="section">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__title">
                        <?php echo __('featured_products'); ?>
                    </h2>
                    <p class="section__subtitle">
                        <?php echo __('hand_picked_items'); ?>
                    </p>
                </div>
                
                <?php if (!empty($products)): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-card__content">
                                    <h3 class="product-card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-card__description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (strlen($product['description'] ?? '') > 100 ? '...' : ''); ?></p>
                                    <div class="product-card__price">
                                        <span class="price"><?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                    <?php if ($product['is_disabled']): ?>
                                        <div class="product-card__badge">
                                            <span class="badge badge--special"><?php echo __('supporting_disabled_seller'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-card__actions">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn--primary">
                                            <?php echo __('view_details'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="section__footer">
                        <a href="store.php" class="btn btn--secondary btn--lg">
                            <?php echo __('view_all_products'); ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12,5 19,12 12,19"></polyline>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php echo __('no_products_found'); ?></p>
                        <a href="store.php" class="btn btn--primary"><?php echo __('browse_store'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php 
    // Simple footer inclusion - if footer.php fails, show basic footer
    try {
        include 'footer.php'; 
    } catch (Exception $e) {
        echo '<footer><p>&copy; 2025 WeBuy - Online Shopping Platform</p></footer>';
    }
    ?>
</body>
</html>