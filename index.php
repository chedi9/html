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

require 'db.php';
require_once 'includes/thumbnail_helper.php';

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
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBuy - Online Shopping Platform</title>
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- WeBuy Custom Bootstrap Configuration -->
    <link rel="stylesheet" href="css/bootstrap-custom.css">
    
    <!-- Legacy CSS for gradual migration -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/components/featured-products.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.5" defer></script>
    <script src="js/featured-products.js" defer></script>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link"><?php echo __('skip_to_main_content'); ?></a>
    
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
        <section class="bg-primary text-white py-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold mb-4">
                            <?php echo __('discover_tunisian_talents'); ?>
                        </h1>
                        <p class="lead mb-4">
                            <?php echo __('webuy_platform_description'); ?>
                        </p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <span class="fs-3 me-3">🌟</span>
                                    <span><?php echo __('support_disabled_sellers'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <span class="fs-3 me-3">🚚</span>
                                    <span><?php echo __('fast_delivery'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <span class="fs-3 me-3">💳</span>
                                    <span><?php echo __('secure_payment'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="#categories" class="btn btn-light btn-lg">
                                <?php echo __('browse_categories'); ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ms-2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12,5 19,12 12,19"></polyline>
                                </svg>
                            </a>
                            <a href="store.php" class="btn btn-outline-light btn-lg">
                                <?php echo __('shop_now'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <!-- Hero visual content can be added here -->
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Featured Categories -->
        <section class="py-5" id="categories">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold mb-3">
                        <?php echo __('featured_categories'); ?>
                    </h2>
                    <p class="lead text-muted">
                        <?php echo __('discover_diverse_collection'); ?>
                    </p>
                </div>
                
                <div class="row g-3">
                    <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                        <?php $cat_name = $category['name_' . $lang] ?? $category['name']; ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-3">
                            <div class="card h-100 shadow-sm">
                                <a href="store.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                                    <div class="card-img-top" style="aspect-ratio: 1/1; overflow: hidden;">
                                        <div class="skeleton w-100 h-100"></div>
                                        <?php if (!empty($category['image'])): ?>
                                            <?php 
                                            $optimized_image = get_optimized_image('uploads/' . $category['image'], 'category');
                                            ?>
                                            <img src="<?php echo $optimized_image['src']; ?>" 
                                                 srcset="<?php echo $optimized_image['srcset']; ?>"
                                                 sizes="<?php echo $optimized_image['sizes']; ?>"
                                                 alt="<?php echo htmlspecialchars($cat_name); ?>" 
                                                 loading="lazy"
                                                 class="w-100 h-100 object-fit-cover"
                                                 onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
                                        <?php elseif (!empty($category['icon'])): ?>
                                            <?php 
                                            $optimized_image = get_optimized_image('uploads/' . $category['icon'], 'category');
                                            ?>
                                            <img src="<?php echo $optimized_image['src']; ?>" 
                                                 srcset="<?php echo $optimized_image['srcset']; ?>"
                                                 sizes="<?php echo $optimized_image['sizes']; ?>"
                                                 alt="<?php echo htmlspecialchars($cat_name); ?>" 
                                                 loading="lazy"
                                                 class="w-100 h-100 object-fit-cover"
                                                 onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                    <polyline points="21,15 16,10 5,21"></polyline>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body text-center p-2">
                                        <h6 class="card-title text-dark mb-0"><?php echo htmlspecialchars($cat_name); ?></h6>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Priority Products Section -->
        <?php if (!empty($priority_products)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold mb-3">
                        🌟 <?php echo __('products_from_disabled_sellers'); ?>
                    </h2>
                    <p class="lead text-muted">
                        <?php echo __('support_disabled_sellers'); ?>
                    </p>
                </div>
                
                <div class="row g-3">
                    <?php foreach ($priority_products as $product): ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="card h-100 shadow-sm position-relative" data-product-id="<?php echo $product['id']; ?>">
                                <!-- Priority Badge -->
                                <span class="badge bg-warning position-absolute top-0 start-0 m-1 small">
                                    🌟
                                </span>
                                
                                <!-- Wishlist Button -->
                                <button class="btn btn-outline-secondary btn-sm position-absolute top-0 end-0 m-1 p-1" 
                                        data-product-id="<?php echo $product['id']; ?>" 
                                        title="<?php echo __('add_to_wishlist'); ?>">
                                    <?php if (!empty($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist'])): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    <?php endif; ?>
                                </button>
                                
                                <!-- Product Image -->
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <div class="card-img-top" style="aspect-ratio: 1/1; overflow: hidden;">
                                        <div class="skeleton w-100 h-100"></div>
                                        <?php 
                                        $optimized_image = get_optimized_image('uploads/' . $product['image'], 'card');
                                        ?>
                                        <img src="<?php echo $optimized_image['src']; ?>" 
                                             srcset="<?php echo $optimized_image['srcset']; ?>"
                                             sizes="<?php echo $optimized_image['sizes']; ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             loading="lazy"
                                             class="w-100 h-100 object-fit-cover"
                                             onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
                                    </div>
                                    
                                    <!-- Product Content -->
                                    <div class="card-body d-flex flex-column p-2">
                                        <h6 class="card-title text-dark mb-1 text-truncate" title="<?php echo htmlspecialchars($product['name']); ?>"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        
                                        <!-- Rating Section -->
                                        <?php
                                        // Get product rating
                                        $stmt = $pdo->prepare('
                                            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                                            FROM reviews 
                                            WHERE product_id = ? AND status = "approved"
                                        ');
                                        $stmt->execute([$product['id']]);
                                        $rating_data = $stmt->fetch();
                                        $avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
                                        $review_count = $rating_data['review_count'] ?? 0;
                                        ?>
                                        
                                        <?php if ($avg_rating > 0): ?>
                                            <div class="mb-1">
                                                <div class="d-flex align-items-center small">
                                                    <div class="text-warning me-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="<?php echo $i <= $avg_rating ? 'text-warning' : 'text-muted'; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <small class="text-muted">(<?php echo $review_count; ?>)</small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-auto">
                                            <div class="mb-2">
                                                <span class="h6 mb-0 text-primary"><?php echo htmlspecialchars($product['price']); ?> <?php echo __('currency'); ?></span>
                                            </div>
                                            
                                            <form action="add_to_cart.php" method="get">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                                    <?php echo __('add_to_cart'); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="store.php?priority=disabled_sellers" class="btn btn-outline-primary">
                        <?php echo __('view_all_disabled_seller_products'); ?>
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
                        <?php echo __('featured_products'); ?>
                    </h2>
                    <p class="section__subtitle">
                        <?php echo __('discover_latest_products'); ?>
                    </p>
                </div>
                
                <div class="grid grid--auto-fill-sm">
                    <?php foreach ($products as $i => $product): ?>
                        <div class="card card--product" data-product-id="<?php echo $product['id']; ?>">
                            <?php if ($i < 3): ?>
                                <div class="card__badge card__badge--new">
                                    <?php echo __('new'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['is_disabled'])): ?>
                                <div class="card__badge card__badge--disabled">
                                    <?php echo __('disabled_seller'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <button class="card__wishlist" data-product-id="<?php echo $product['id']; ?>" 
                                    title="<?php echo __('add_to_wishlist'); ?>">
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
                                    <div class="skeleton skeleton--image"></div>
                                    <?php 
                                    $optimized_image = get_optimized_image('uploads/' . $product['image'], 'card');
                                    ?>
                                    <img src="<?php echo $optimized_image['src']; ?>" 
                                         srcset="<?php echo $optimized_image['srcset']; ?>"
                                         sizes="<?php echo $optimized_image['sizes']; ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         loading="lazy"
                                         onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
                                </div>
                                <div class="card__content">
                                    <div class="skeleton skeleton--title"></div>
                                    <h3 class="card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="skeleton skeleton--text"></div>
                                    <p class="card__description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    
                                    <!-- Rating Section -->
                                    <?php
                                    // Get product rating
                                    $stmt = $pdo->prepare('
                                        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                                        FROM reviews 
                                        WHERE product_id = ? AND status = "approved"
                                    ');
                                    $stmt->execute([$product['id']]);
                                    $rating_data = $stmt->fetch();
                                    $avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
                                    $review_count = $rating_data['review_count'] ?? 0;
                                    ?>
                                    
                                    <?php if ($avg_rating > 0): ?>
                                        <div class="card__rating">
                                            <div class="card__rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="card__rating-count">(<?php echo $review_count; ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="skeleton skeleton--price"></div>
                                    <div class="card__price"><?php echo htmlspecialchars($product['price']); ?> <?php echo __('currency'); ?></div>
                                </div>
                            </a>
                            
                            <form action="add_to_cart.php" method="get" class="card__form">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn--primary btn--sm">
                                    <?php echo __('add_to_cart'); ?>
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
                        <?php echo __('recently_viewed'); ?>
                    </h2>
                </div>
                
                <div class="grid grid--auto-fill-sm">
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
                <div class="section__content">
                    <h2 class="section__title">
                        <?php echo $lang === 'ar' ? 'من نحن' : 'About Us'; ?>
                    </h2>
                    <p class="section__text">
                        <?php echo $lang === 'ar' ? 'منصة WeBuy هي منصة تسوق إلكتروني مخصصة لدعم وتمكين الأفراد ذوي الإعاقة في تونس. نساعدهم على بيع منتجاتهم وبناء مستقبل مستقل.' : 'WeBuy is an e-commerce platform dedicated to supporting and empowering individuals with disabilities in Tunisia. We help them sell their products and build an independent future.'; ?>
                    </p>
                    <div class="section__actions">
                        <a href="faq.php" class="btn btn--primary">
                            <?php echo $lang === 'ar' ? 'تعرف على المزيد' : 'Learn More'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
<?php include_once 'include_load_analytics.php'; ?>
    
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