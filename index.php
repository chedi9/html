<?php
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

require 'db.php';

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

// Get priority products
require_once 'priority_products_helper.php';
$priority_products = getPriorityProducts(6);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBuy - Online Shopping Platform</title>
    
    <!-- CSS Files - Load in correct order -->
    <link rel="stylesheet" href="css/base/_variables.css">
    <link rel="stylesheet" href="css/base/_reset.css">
    <link rel="stylesheet" href="css/base/_typography.css">
    <link rel="stylesheet" href="css/base/_utilities.css">
    <link rel="stylesheet" href="css/components/_buttons.css">
    <link rel="stylesheet" href="css/components/_forms.css">
    <link rel="stylesheet" href="css/components/_cards.css">
    <link rel="stylesheet" href="css/components/_navigation.css">
    <link rel="stylesheet" href="css/layout/_grid.css">
    <link rel="stylesheet" href="css/layout/_sections.css">
    <link rel="stylesheet" href="css/layout/_footer.css">
    <link rel="stylesheet" href="css/themes/_light.css">
    <link rel="stylesheet" href="css/themes/_dark.css">
    <link rel="stylesheet" href="css/build.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
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
                    <h1 class="hero__title">
                        <?php echo $lang === 'ar' ? 'اكتشف مواهب تونس وادعم الإبداع المحلي' : 'Discover Tunisian Talents and Support Local Creativity'; ?>
                    </h1>
                    <p class="hero__subtitle">
                        <?php echo $lang === 'ar' ? 'منصة WeBuy تجمع أفضل المنتجات المصنوعة بحب وإتقان من قبل أفراد ذوي إعاقة في تونس. تسوق، شارك، وكن جزءًا من التغيير!' : 'WeBuy platform brings together the best products made with love and craftsmanship by individuals with disabilities in Tunisia. Shop, share, and be part of the change!'; ?>
                    </p>
                    <div class="hero__actions">
                        <a href="#categories" class="btn btn--primary btn--lg">
                            <?php echo $lang === 'ar' ? 'تصفح التصنيفات' : 'Browse Categories'; ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12,5 19,12 12,19"></polyline>
                            </svg>
                        </a>
                        <a href="store.php" class="btn btn--secondary btn--lg">
                            <?php echo $lang === 'ar' ? 'تسوق الآن' : 'Shop Now'; ?>
                        </a>
                    </div>
                </div>
                <div class="hero__image">
                    <img src="webuy-logo-transparent.jpg" alt="WeBuy Logo" loading="lazy">
                </div>
            </div>
        </section>
        
        <!-- Featured Categories -->
        <section class="section" id="categories">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__title">
                        <?php echo $lang === 'ar' ? 'التصنيفات المميزة' : 'Featured Categories'; ?>
                    </h2>
                    <p class="section__subtitle">
                        <?php echo $lang === 'ar' ? 'اكتشف مجموعة متنوعة من المنتجات المصنوعة بحب وإتقان' : 'Discover a diverse collection of products made with love and craftsmanship'; ?>
                    </p>
                </div>
                
                <div class="grid grid--3-cols">
                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                        <?php $cat_name = $category['name_' . $lang] ?? $category['name']; ?>
                        <div class="card card--category">
                            <a href="store.php?category_id=<?php echo $category['id']; ?>" class="card__link">
                                <div class="card__image">
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($category['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($cat_name); ?>" loading="lazy">
                                    <?php elseif (!empty($category['icon'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($category['icon']); ?>" 
                                             alt="<?php echo htmlspecialchars($cat_name); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="card__placeholder">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21,15 16,10 5,21"></polyline>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card__content">
                                    <h3 class="card__title"><?php echo htmlspecialchars($cat_name); ?></h3>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Priority Products Section -->
        <?php if (!empty($priority_products)): ?>
        <section class="section section--highlight">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__title">
                        <?php echo $lang === 'ar' ? '🌟 منتجات البائعين ذوي الإعاقة' : '🌟 Products from Sellers with Disabilities'; ?>
                    </h2>
                    <p class="section__subtitle">
                        <?php echo $lang === 'ar' ? 'نساند وندعم البائعين ذوي الإعاقة في رحلتهم نحو النجاح' : 'We support and empower sellers with disabilities in their journey to success'; ?>
                    </p>
                </div>
                
                <div class="grid grid--3-cols">
                    <?php foreach ($priority_products as $product): ?>
                        <div class="card card--product" data-product-id="<?php echo $product['id']; ?>">
                            <div class="card__badge card__badge--priority">
                                <?php echo $lang === 'ar' ? '🌟 بائع ذو إعاقة' : '🌟 Disabled Seller'; ?>
                            </div>
                            
                            <button class="card__wishlist" data-product-id="<?php echo $product['id']; ?>" 
                                    title="<?php echo $lang === 'ar' ? 'إضافة إلى المفضلة' : 'Add to Wishlist'; ?>">
                                <?php if (!empty($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist'])): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                <?php endif; ?>
                            </button>
                            
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="card__link">
                                <div class="card__image">
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                                </div>
                                <div class="card__content">
                                    <h3 class="card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="card__description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="card__price"><?php echo htmlspecialchars($product['price']); ?> <?php echo $lang === 'ar' ? 'د.ت' : 'TND'; ?></div>
                                </div>
                            </a>
                            
                            <form action="add_to_cart.php" method="get" class="card__form">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn--primary btn--sm">
                                    <?php echo $lang === 'ar' ? 'إضافة إلى السلة' : 'Add to Cart'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="section__footer">
                    <a href="store.php?priority=disabled_sellers" class="btn btn--secondary">
                        <?php echo $lang === 'ar' ? 'عرض جميع منتجات البائعين ذوي الإعاقة' : 'View All Products from Disabled Sellers'; ?>
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Featured Products -->
        <section class="section">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__title">
                        <?php echo $lang === 'ar' ? 'المنتجات المميزة' : 'Featured Products'; ?>
                    </h2>
                    <p class="section__subtitle">
                        <?php echo $lang === 'ar' ? 'اكتشف أحدث وأفضل المنتجات من بائعين موثوقين' : 'Discover the latest and best products from trusted sellers'; ?>
                    </p>
                </div>
                
                <div class="grid grid--4-cols">
                    <?php foreach ($products as $i => $product): ?>
                        <div class="card card--product" data-product-id="<?php echo $product['id']; ?>">
                            <?php if ($i < 3): ?>
                                <div class="card__badge card__badge--new">
                                    <?php echo $lang === 'ar' ? 'جديد' : 'New'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['is_disabled'])): ?>
                                <div class="card__badge card__badge--disabled">
                                    <?php echo $lang === 'ar' ? 'بائع ذو إعاقة' : 'Disabled Seller'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <button class="card__wishlist" data-product-id="<?php echo $product['id']; ?>" 
                                    title="<?php echo $lang === 'ar' ? 'إضافة إلى المفضلة' : 'Add to Wishlist'; ?>">
                                <?php if (!empty($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist'])): ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                <?php endif; ?>
                            </button>
                            
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="card__link">
                                <div class="card__image">
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                                </div>
                                <div class="card__content">
                                    <h3 class="card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="card__description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="card__price"><?php echo htmlspecialchars($product['price']); ?> <?php echo $lang === 'ar' ? 'د.ت' : 'TND'; ?></div>
                                </div>
                            </a>
                            
                            <form action="add_to_cart.php" method="get" class="card__form">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn--primary btn--sm">
                                    <?php echo $lang === 'ar' ? 'إضافة إلى السلة' : 'Add to Cart'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Recently Viewed Products -->
        <?php if (!empty($recently_viewed)): ?>
        <section class="section">
            <div class="container">
                <div class="section__header">
                    <h2 class="section__title">
                        <?php echo $lang === 'ar' ? 'المنتجات التي شاهدتها مؤخرًا' : 'Recently Viewed Products'; ?>
                    </h2>
                </div>
                
                <div class="grid grid--4-cols">
                    <?php foreach ($recently_viewed as $product): ?>
                        <?php $prod_name = $product['name_' . $lang] ?? $product['name']; ?>
                        <div class="card card--product">
                            <?php if (!empty($product['is_disabled'])): ?>
                                <div class="card__badge card__badge--disabled">
                                    <?php echo $lang === 'ar' ? 'بائع ذو إعاقة' : 'Disabled Seller'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="card__link">
                                <div class="card__image">
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($prod_name); ?>" loading="lazy">
                                </div>
                                <div class="card__content">
                                    <h3 class="card__title"><?php echo htmlspecialchars($prod_name); ?></h3>
                                    <div class="card__price"><?php echo htmlspecialchars($product['price']); ?> <?php echo $lang === 'ar' ? 'د.ت' : 'TND'; ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- About Section -->
        <section class="section section--highlight">
            <div class="container">
                <div class="grid grid--2-cols">
                    <div class="section__content">
                        <h2 class="section__title">
                            <?php echo $lang === 'ar' ? 'من نحن' : 'About Us'; ?>
                        </h2>
                        <p class="section__text">
                            <?php echo $lang === 'ar' ? 'منصة WeBuy هي منصة تسوق إلكتروني مخصصة لدعم وتمكين الأفراد ذوي الإعاقة في تونس. نساعدهم على بيع منتجاتهم وبناء مستقبل مستقل.' : 'WeBuy is an e-commerce platform dedicated to supporting and empowering individuals with disabilities in Tunisia. We help them sell their products and build an independent future.'; ?>
                        </p>
                        <div class="section__actions">
                            <a href="about.php" class="btn btn--primary">
                                <?php echo $lang === 'ar' ? 'تعرف على المزيد' : 'Learn More'; ?>
                            </a>
                        </div>
                    </div>
                    <div class="section__image">
                        <img src="webuy.jpg" alt="WeBuy Platform" loading="lazy">
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Optimized JavaScript -->
    <script src="js/optimized/main.min.js" defer></script>
    
    <!-- Performance monitoring -->
    <script>
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('Page load time:', loadTime.toFixed(2), 'ms');
        });
    </script>
</body>
</html> 