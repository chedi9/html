<?php
// Comprehensive Security Integration
require_once 'security_integration.php';

// Additional compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
require 'db.php';
require 'lang.php';

// Modern Cookie Consent Banner Integration
// Check if user has already made a choice
$cookie_consent = $_COOKIE['cookie_consent'] ?? null;
$cookie_preferences = $_COOKIE['cookie_preferences'] ?? null;

// Handle consent form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cookie_consent'])) {
    $consent_data = [
        'essential' => true, // Always required
        'preferences' => isset($_POST['preferences']) ? true : false,
        'analytics' => isset($_POST['analytics']) ? true : false,
        'marketing' => isset($_POST['marketing']) ? true : false,
        'security' => true, // Always required for security
        'timestamp' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    // Set cookies with appropriate expiration
    setcookie('cookie_consent', 'accepted', time() + (365 * 24 * 60 * 60), '/', '', true, true);
    setcookie('cookie_preferences', json_encode($consent_data), time() + (365 * 24 * 60 * 60), '/', '', true, true);
    
    // Log consent for security monitoring
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('cookie_consent_given', $consent_data);
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Handle consent withdrawal
if (isset($_GET['withdraw_consent'])) {
    setcookie('cookie_consent', '', time() - 3600, '/');
    setcookie('cookie_preferences', '', time() - 3600, '/');
    
    // Log withdrawal for security monitoring
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('cookie_consent_withdrawn', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Update product query:
$products = $pdo->query("SELECT p.*, s.is_disabled FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.approved = 1 ORDER BY s.is_disabled DESC, p.created_at DESC LIMIT 8")->fetchAll();
// Fetch average ratings for all products
$ratings = [];
$stmt = $pdo->query("SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews GROUP BY product_id");
while ($row = $stmt->fetch()) {
    $ratings[$row['product_id']] = [
        'avg' => round($row['avg_rating'], 1),
        'count' => $row['review_count']
    ];
}
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
$recently_viewed = [];
if (!empty($_SESSION['viewed_products'])) {
    $ids = array_reverse($_SESSION['viewed_products']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT p.*, s.is_disabled FROM products p LEFT JOIN sellers s ON p.seller_id = s.id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    // Keep order as in $ids
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $recently_viewed = [];
    foreach ($ids as $id) {
        foreach ($all as $prod) {
            if ($prod['id'] == $id) {
                $recently_viewed[] = $prod;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBuy - نجوم تونس</title>
    <link rel="stylesheet" href="beta333.css?v=1.2">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css?v=1.2">
    <?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    <!-- ... meta tags ... -->
</head>
<body>
<div id="pageContent">
    <?php include 'header.php'; ?>

<!-- Header Banner Zone: flex column for vertical centering -->
<div class="header-banner-zone">
  <div id="promoBanner" class="promo-banner">
    <span class="promo-message" style="font-family:'Amiri',serif;font-weight:700;letter-spacing:0.5px;direction:rtl;">
      <?php echo __('promo_banner', ['countdown' => '<span id="promoCountdown"></span>']); ?>
    </span>
    <button class="promo-close" aria-label="Close">&times;</button>
  </div>
</div>
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert-success" id="cartAlert">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            <button class="close-btn" onclick="document.getElementById('cartAlert').style.display='none'">&times;</button>
        </div>
    <?php endif; ?>
    <header>
        <div class="header-top-row">
                <div class="header-logo">
                    <img src="webuy.jpg" alt="WeBuy Logo" class="logo" loading="lazy">
                <span class="logo-text" style="font-size:1.5em;font-weight:bold;color:#FFD600;margin-right:12px;letter-spacing:1.5px;text-shadow:0 2px 8px rgba(0,191,174,0.10);">WeBuy نجوم تونس</span>
            </div>
            <div class="header-actions-group">
                <!-- Header actions are now handled by header.php include -->
            </div>
        </div>
        <div class="header-mega-menu-row">
            <nav class="mega-menu" aria-label="Mega Menu">
              <ul class="mega-menu-list">
                <?php foreach ($categories as $category): ?>
                  <?php $cat_name = $category['name_' . $lang] ?? $category['name']; ?>
                  <li class="mega-menu-item">
                    <a href="search.php?category_id=<?php echo $category['id']; ?>">
                      <span class="mega-menu-icon">
                        <?php if (!empty($category['icon'])): ?>
                          <img src="uploads/<?php echo htmlspecialchars($category['icon']); ?>" alt="<?php echo htmlspecialchars($cat_name); ?>" style="width:28px;height:28px;object-fit:cover;border-radius:50%;background:#fff;">
                        <?php elseif (!empty($category['image'])): ?>
                          <img src="uploads/<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($cat_name); ?>" style="width:28px;height:28px;object-fit:cover;border-radius:50%;background:#fff;">
                    <?php else: ?>
                          <svg width="28" height="28" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="6" y="14" width="36" height="24" rx="8" fill="#00BFAE"/>
                            <rect x="14" y="6" width="20" height="20" rx="6" fill="#FFD600"/>
                            <circle cx="24" cy="24" r="6" fill="#1A237E"/>
                          </svg>
                    <?php endif; ?>
                </span>
                      <span class="mega-menu-label"><?php echo htmlspecialchars($cat_name); ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </nav>
                </div>
        <div class="header-search-row">
            <div class="central-search-bar">
              <input type="text" id="liveSearchInput" placeholder="<?= __('search_placeholder') ?>" autocomplete="search">
            </div>
        </div>
        <div class="header-nav-row">
            <div class="lang-switcher" style="margin-right:18px;">
                <form method="get" id="langForm" style="display:inline;">
                    <label for="langSelect" class="sr-only" style="position:absolute;left:-9999px;">Language</label>
                    <select name="lang" id="langSelect" title="Language" style="padding:4px 12px;border-radius:8px;border:1.5px solid #00BFAE;font-size:1em;">
                        <option value="ar" <?php if(($_GET['lang'] ?? $_SESSION['lang'] ?? 'ar')=='ar') echo 'selected'; ?>><?= __('arabic_language') ?></option>
                        <option value="fr" <?php if(($_GET['lang'] ?? $_SESSION['lang'] ?? 'ar')=='fr') echo 'selected'; ?>>Français</option>
                        <option value="en" <?php if(($_GET['lang'] ?? $_SESSION['lang'] ?? 'ar')=='en') echo 'selected'; ?>>English</option>
                    </select>
                </form>
            </div>
            <nav aria-label="Main Navigation">
                <ul>
                    <li><a href="#welcome" aria-label="<?= __('welcome') ?>" class="active"><?= __('welcome') ?></a></li>
                    <li><a href="#services" aria-label="<?= __('services') ?>"><?= __('services') ?></a></li>
                    <?php if (!empty($categories)): ?>
                        <li><a href="search.php?category_id=<?php echo $categories[0]['id']; ?>" aria-label="<?= __('categories') ?>"><?= __('categories') ?></a></li>
                    <?php else: ?>
                        <li><a href="search.php" aria-label="<?= __('categories') ?>"><?= __('categories') ?></a></li>
                    <?php endif; ?>
                    <li><a href="#about" aria-label="<?= __('about') ?>"><?= __('about') ?></a></li>
                    <li><a href="#contact" aria-label="<?= __('contact') ?>"><?= __('contact') ?></a></li>
                    <li><a href="search.php" aria-label="<?= __('search') ?>"><?= __('search') ?></a></li>
                    <li><a href="faq.php" aria-label="<?= __('faq') ?>"><?= __('faq') ?></a></li>
                </ul>
            </nav>
        </div>
    </header>
    <!-- Hero Carousel -->
    <section class="hero-banner">
      <div class="hero-carousel" id="heroCarousel">
        <div class="hero-slide active" style="background:#1A237E url('webuy.jpg') center/cover no-repeat;">
          <div class="hero-overlay"></div>
          <div class="hero-content">
            <h1 class="hero-title">اكتشف مواهب تونس وادعم الإبداع المحلي</h1>
            <p class="hero-subtitle">منصة WeBuy تجمع أفضل المنتجات المصنوعة بحب وإتقان من قبل أفراد ذوي إعاقة في تونس. تسوق، شارك، وكن جزءًا من التغيير!</p>
            <a href="#categories" class="hero-cta">تصفح التصنيفات <span class="arrow">→</span></a>
          </div>
        </div>
        <div class="hero-slide" style="background:#FFD600 url('webuy-logo-transparent.jpg') center/contain no-repeat;">
          <div class="hero-overlay"></div>
          <div class="hero-content">
            <h1 class="hero-title">عروض الصيف: خصومات تصل إلى 50%</h1>
            <p class="hero-subtitle">استفد من التخفيضات على مختاراتنا لفترة محدودة. تسوق الآن!</p>
            <a href="search.php?sort=price_asc" class="hero-cta">تسوق العروض <span class="arrow">→</span></a>
          </div>
        </div>
        <div class="hero-slide" style="background:#00BFAE;">
          <div class="hero-overlay"></div>
          <div class="hero-content">
            <h1 class="hero-title">ادعم الحرفيين المحليين</h1>
            <p class="hero-subtitle">كل عملية شراء تساهم في تمكين المواهب التونسية وتحقيق الاستقلالية المالية.</p>
            <a href="#about" class="hero-cta">تعرف على رسالتنا <span class="arrow">→</span></a>
          </div>
        </div>
        <button class="hero-arrow left" id="heroArrowLeft">&#8592;</button>
        <button class="hero-arrow right" id="heroArrowRight">&#8594;</button>
        <div class="hero-dots" id="heroDots"></div>
      </div>
    </section>
    <!-- Product Carousel -->
    <section class="product-carousel-section container">
      <h2 style="margin-bottom:18px;color:var(--primary-color);font-size:1.18em;">منتجات مختارة</h2>
      <div class="product-carousel" id="productCarousel">
        <?php foreach (array_slice($products, 0, 8) as $prod): ?>
          <?php $prod_name = $prod['name_' . $lang] ?? $prod['name']; ?>
          <div class="product-carousel-card">
            <a href="product.php?id=<?= $prod['id'] ?>">
              <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod_name) ?>" style="width:100%;height:110px;object-fit:cover;border-radius:8px;">
              <div class="product-name" style="font-weight:bold;font-size:1.08em;"> <?= htmlspecialchars($prod_name) ?> </div>
              <div class="product-price" style="color:#00BFAE;font-weight:bold;"> <?= $prod['price'] ?> د.ت </div>
            </a>
          </div>
        <?php endforeach; ?>
        <button class="carousel-arrow left" id="productArrowLeft">&#8592;</button>
        <button class="carousel-arrow right" id="productArrowRight">&#8594;</button>
        <div class="carousel-dots" id="productDots"></div>
      </div>
    </section>
    <!-- Category Carousel -->
    <section class="category-carousel-section container">
      <h2 style="margin-bottom:18px;color:var(--primary-color);font-size:1.18em;">تصفح التصنيفات</h2>
      <div class="category-carousel" id="categoryCarousel">
        <?php foreach ($categories as $cat): ?>
          <?php $cat_name = $cat['name_' . $lang] ?? $cat['name']; ?>
          <div class="category-carousel-card">
            <a href="search.php?category_id=<?= $cat['id'] ?>">
              <?php if (!empty($cat['image'])): ?>
                <img src="uploads/<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat_name) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
              <?php elseif (!empty($cat['icon'])): ?>
                <img src="uploads/<?= htmlspecialchars($cat['icon']) ?>" alt="<?= htmlspecialchars($cat_name) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
              <?php else: ?>
                <svg width="80" height="80" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-bottom:10px;"><rect x="6" y="14" width="36" height="24" rx="8" fill="#00BFAE"/><rect x="14" y="6" width="20" height="20" rx="6" fill="#FFD600"/><circle cx="24" cy="24" r="6" fill="#1A237E"/></svg>
              <?php endif; ?>
              <div class="category-name" style="font-weight:bold;font-size:1.08em;"> <?= htmlspecialchars($cat_name) ?> </div>
            </a>
          </div>
        <?php endforeach; ?>
        <button class="carousel-arrow left" id="categoryArrowLeft">&#8592;</button>
        <button class="carousel-arrow right" id="categoryArrowRight">&#8594;</button>
        <div class="carousel-dots" id="categoryDots"></div>
      </div>
    </section>
    <section class="featured-categories container">
        <h2>الأقسام المميزة</h2>
        <div class="category-grid">
            <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                <?php $cat_name = $cat['name_' . $lang] ?? $cat['name']; ?>
                <a href="search.php?category_id=<?= $cat['id'] ?>" class="category-item">
                    <?php if (!empty($cat['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat_name) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
                    <?php elseif (!empty($cat['icon'])): ?>
                        <img src="uploads/<?= htmlspecialchars($cat['icon']) ?>" alt="<?= htmlspecialchars($cat_name) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
                    <?php else: ?>
                        <svg width="100" height="100" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-bottom:10px;"><rect x="6" y="14" width="36" height="24" rx="8" fill="#00BFAE"/><rect x="14" y="6" width="20" height="20" rx="6" fill="#FFD600"/><circle cx="24" cy="24" r="6" fill="#1A237E"/></svg>
                    <?php endif; ?>
                    <div class="category-name" style="font-weight:bold;font-size:1.1em;"> <?= htmlspecialchars($cat_name) ?> </div>
                </a>
            <?php endforeach; ?>
            </div>
        </section>
    <main>
        <section id="welcome" class="container">
            <h2><?= __('welcome') ?></h2>
            <p><?= __('welcome_paragraph') ?></p>
        </section>
        
        <!-- Disabled Sellers Showcase Section -->
        <?php
        require_once 'priority_products_helper.php';
        $priority_products = getPriorityProducts(6);
        
        if (!empty($priority_products)):
        ?>
        <section id="disabled-sellers-showcase" class="container" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); padding: 30px; border-radius: 15px; margin: 30px auto; border: 2px solid #ffc107;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #856404; margin-bottom: 10px;">🌟 منتجات البائعين ذوي الإعاقة</h2>
                <p style="color: #856404; font-size: 1.1em; margin: 0;">نساند وندعم البائعين ذوي الإعاقة في رحلتهم نحو النجاح</p>
            </div>
            
            <div class="product-grid">
                <?php foreach ($priority_products as $product): ?>
                <div class="product-card" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>" data-image="uploads/<?php echo htmlspecialchars($product['image']); ?>" data-description="<?php echo htmlspecialchars($product['description']); ?>">
                    <span class="product-badge" style="background: #FFD600; color: #1A237E; left: auto; right: 12px; top: 12px; position: absolute; z-index: 4; font-weight: bold;">
                        🌟 بائع ذو إعاقة
                    </span>
                    <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" title="<?= __('add_to_favorites') ?>" style="position: absolute; top: 12px; left: 12px; z-index: 3; background: none; border: none; cursor: pointer; outline: none;">
                        <?php if (!empty($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist'])): ?>
                            <span style="font-size: 1.5em; color: #e74c3c;">&#10084;</span>
                        <?php else: ?>
                            <span style="font-size: 1.5em; color: #bbb;">&#9825;</span>
                        <?php endif; ?>
                    </button>
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <div class="product-img-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" width="300" height="300">
                        </div>
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="price"><?php echo htmlspecialchars($product['price']); ?> <?= __('currency') ?></p>
                        
                        <!-- Disabled Seller Info -->
                        <?php if (!empty($product['disabled_seller_name'])): ?>
                        <div style="background: rgba(255, 193, 7, 0.1); padding: 10px; border-radius: 8px; margin-top: 10px; border-left: 3px solid #ffc107;">
                            <p style="margin: 0; font-size: 0.9em; color: #856404;">
                                <strong>البائع:</strong> <?php echo htmlspecialchars($product['disabled_seller_name']); ?><br>
                                <strong>نوع الإعاقة:</strong> <?php echo htmlspecialchars($product['disability_type']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </a>
                    <form action="add_to_cart.php" method="get" class="add-to-cart-form" style="margin-top: 10px;">
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="add-cart-btn"><?= __('add_to_cart') ?></button>
                    </form>
                    <button class="quick-view-btn" type="button">👁️ <?= __('quick_view') ?></button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="search.php?priority=disabled_sellers" style="background: #ffc107; color: #1A237E; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block;">
                    عرض جميع منتجات البائعين ذوي الإعاقة
                </a>
            </div>
        </section>
        <?php endif; ?>
        <section id="showcase" class="container">
            <h2><?= __('showcase') ?></h2>
            <div class="product-grid">
                <?php foreach ($products as $i => $product): ?>
                <div class="product-card" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>" data-image="uploads/<?php echo htmlspecialchars($product['image']); ?>" data-description="<?php echo htmlspecialchars($product['description']); ?>">
                    <?php if ($i < 3): ?>
                        <span class="product-badge new"><?= __('new') ?></span>
                    <?php endif; ?>
                    <?php if (!empty($product['is_disabled'])): ?>
                        <span class="product-badge" style="background:#FFD600;color:#1A237E;left:auto;right:12px;top:12px;position:absolute;z-index:4;">Disabled Seller</span>
                    <?php endif; ?>
                    <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" title="<?= __('add_to_favorites') ?>" style="position:absolute;top:12px;left:12px;z-index:3;background:none;border:none;cursor:pointer;outline:none;">
                            <?php if (!empty($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist'])): ?>
                                <span style="font-size:1.5em;color:#e74c3c;">&#10084;</span>
                            <?php else: ?>
                                <span style="font-size:1.5em;color:#bbb;">&#9825;</span>
                            <?php endif; ?>
                        </button>
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <div class="product-img-wrap">
                            <?php 
                            $image_path = "uploads/" . htmlspecialchars($product['image']);
                            $thumb_path = "uploads/thumbnails/" . pathinfo($product['image'], PATHINFO_FILENAME) . "_thumb.jpg";
                            $final_image = file_exists($thumb_path) ? $thumb_path : $image_path;
                            ?>
                            <img src="<?php echo $final_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" width="300" height="300">
                        </div>
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="price"><?php echo htmlspecialchars($product['price']); ?> <?= __('currency') ?></p>
                    </a>
                    <form action="add_to_cart.php" method="get" class="add-to-cart-form" style="margin-top:10px;">
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="add-cart-btn"><?= __('add_to_cart') ?></button>
                    </form>
                    <button class="quick-view-btn" type="button">👁️ <?= __('quick_view') ?></button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php if ($recently_viewed): ?>
<section class="recently-viewed container">
    <h2 style="margin-top:36px;">المنتجات التي شاهدتها مؤخرًا</h2>
    <div class="product-grid">
        <?php foreach ($recently_viewed as $prod): ?>
            <?php $prod_name = $prod['name_' . $lang] ?? $prod['name']; ?>
            <div class="product-card">
                <?php if (!empty($prod['is_disabled'])): ?>
                    <span class="product-badge" style="background:#FFD600;color:#1A237E;left:auto;right:12px;top:12px;position:absolute;z-index:4;">Disabled Seller</span>
                <?php endif; ?>
                <a href="product.php?id=<?= $prod['id'] ?>">
                    <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod_name) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:10px;">
                    <div class="product-name" style="font-weight:bold;font-size:1.08em;"> <?= htmlspecialchars($prod_name) ?> </div>
                    <div class="product-price" style="color:#00BFAE;font-weight:bold;"> <?= $prod['price'] ?> د.ت </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
        <!-- Skeleton loader for product grid -->
        <div class="skeleton-grid" id="skeletonGrid" style="display:none;">
          <?php for ($i=0; $i<6; $i++): ?>
            <div class="skeleton-card">
              <div class="skeleton-img"></div>
              <div class="skeleton-line long"></div>
              <div class="skeleton-line medium"></div>
              <div class="skeleton-line short"></div>
            </div>
          <?php endfor; ?>
        </div>
        <!-- ... rest of main content ... -->
    </main>
    <section id="services" class="container">
        <h2><?= __('services') ?></h2>
        <ul style="font-size:1.1em;line-height:2;list-style:disc inside;margin:18px 0 0 0;color:var(--primary-color);">
            <li><?= __('service_marketing') ?></li>
            <li><?= __('service_support') ?></li>
            <li><?= __('service_payment') ?></li>
            <li><?= __('service_customer') ?></li>
            <li><?= __('service_delivery') ?></li>
        </ul>
    </section>
    <section id="about" class="container">
        <h2><?= __('about') ?></h2>
        <p style="font-size:1.1em;line-height:2;color:var(--text-color);"><?= __('about_paragraph') ?></p>
    </section>
    <section id="contact" class="container">
        <h2><?= __('contact') ?></h2>
        <form method="post" action="#" style="max-width:420px;margin:0 auto;">
            <div class="form-group">
                <input type="text" id="contact-name" name="name" required placeholder=" " autocomplete="name">
                <label for="contact-name"><?= __('full_name') ?></label>
            </div>
            <div class="form-group">
                <input type="email" id="contact-email" name="email" required placeholder=" " autocomplete="email">
                <label for="contact-email"><?= __('email') ?></label>
            </div>
            <div class="form-group">
                <textarea id="contact-message" name="message" rows="4" required placeholder=" " autocomplete="off"></textarea>
                <label for="contact-message"><?= __('your_message') ?></label>
            </div>
            <button type="submit" class="checkout-btn"><?= __('send') ?></button>
        </form>
        <div style="text-align:center;margin-top:18px;color:var(--primary-color);font-size:1.08em;">
            <?= __('or_email') ?> <a href="mailto:webuytn0@gmail.com" style="color:var(--accent-color);">webuytn0@gmail.com</a>
        </div>
    </section>
    <footer>
        <p><?= __('contact') ?>: <a href="mailto:webuytn0@gmail.com" style="color:#FFD600;">webuytn0@gmail.com</a></p>
        <p>&copy; <?php echo date('Y'); ?> WeBuy. <?= __('all_rights_reserved') ?></p>
        <p style="margin-top:10px;font-size:0.98em;">
            <a href="privacy.php" style="color:var(--accent-color);margin-left:18px;"><?= __('privacy_policy') ?></a>
            |
            <a href="cookies.php" style="color:var(--accent-color);margin-right:18px;"><?= __('cookies_policy') ?></a>
        </p>
    </footer>
    <!-- Quick View Modal -->
    <div id="quickViewModal" class="quick-view-modal" style="display:none;">
        <div class="quick-view-content">
            <button class="quick-view-close" onclick="closeQuickView()">&times; <?= __('close') ?></button>
            <img id="quickViewImg" src="" alt="<?= __('product_image') ?>">
            <h3 id="quickViewName"></h3>
            <div class="price" id="quickViewPrice"></div>
            <p id="quickViewDesc"></p>
            <form action="add_to_cart.php" method="get" class="add-to-cart-form">
                <input type="hidden" name="id" id="quickViewProductId">
                <button type="submit" class="add-cart-btn"><?= __('add_to_cart') ?></button>
            </form>
        </div>
    </div>

<!-- Modern Cookie Consent Banner -->
<?php if (!$cookie_consent): ?>
<div id="cookie-banner" class="cookie-banner">
    <div class="cookie-content">
        <div class="cookie-header">
            <h3>🍪 ملفات تعريف الارتباط</h3>
            <p>نستخدم ملفات تعريف الارتباط لتحسين تجربتك وحماية حسابك. يمكنك اختيار أنواع الملفات التي تريد السماح بها.</p>
        </div>
        
        <form method="POST" class="cookie-form">
            <div class="cookie-options">
                <div class="cookie-option essential">
                    <label>
                        <input type="checkbox" name="essential" checked disabled>
                        <span class="checkmark"></span>
                        <strong>🛡️ ملفات الأمان الأساسية</strong>
                        <small>ضرورية لتشغيل الموقع وحماية حسابك</small>
                    </label>
                </div>
                
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" name="preferences" checked>
                        <span class="checkmark"></span>
                        <strong>⚙️ ملفات التفضيلات</strong>
                        <small>تذكر إعداداتك وتفضيلاتك</small>
                    </label>
                </div>
                
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" name="analytics">
                        <span class="checkmark"></span>
                        <strong>📊 ملفات التحليل</strong>
                        <small>فهم كيفية استخدام الموقع لتحسين الخدمات</small>
                    </label>
                </div>
                
                <div class="cookie-option">
                    <label>
                        <input type="checkbox" name="marketing">
                        <span class="checkmark"></span>
                        <strong>🎯 ملفات التسويق</strong>
                        <small>تقديم إعلانات ذات صلة وتحسين تجربتك</small>
                    </label>
                </div>
            </div>
            
            <div class="cookie-actions">
                <button type="submit" name="cookie_consent" value="accept_all" class="btn-accept-all">
                    قبول الكل
                </button>
                <button type="submit" name="cookie_consent" value="accept_selected" class="btn-accept-selected">
                    قبول المحدد
                </button>
                <button type="button" class="btn-reject-all" onclick="rejectAllCookies()">
                    رفض الكل
                </button>
                <a href="cookies.php" class="btn-learn-more">تعرف على المزيد</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Cookie Settings Button (for users who have already consented) -->
<?php if ($cookie_consent && !isset($_GET['cookie_settings'])): ?>
<div class="cookie-settings-button">
    <button onclick="showCookieSettings()" class="btn-cookie-settings">
        ⚙️ إعدادات ملفات تعريف الارتباط
    </button>
</div>
<?php endif; ?>

<!-- Cookie Settings Modal -->
<div id="cookie-settings-modal" class="cookie-modal" style="display: none;">
    <div class="cookie-modal-content">
        <div class="cookie-modal-header">
            <h3>⚙️ إعدادات ملفات تعريف الارتباط</h3>
            <button onclick="closeCookieSettings()" class="btn-close">&times;</button>
        </div>
        
        <div class="cookie-modal-body">
            <?php if ($cookie_preferences): ?>
                <?php $prefs = json_decode($cookie_preferences, true); ?>
                <p><strong>الإعدادات الحالية:</strong></p>
                <ul>
                    <li>🛡️ ملفات الأمان الأساسية: <span class="status-enabled">مفعلة</span></li>
                    <li>⚙️ ملفات التفضيلات: <span class="status-<?php echo $prefs['preferences'] ? 'enabled' : 'disabled'; ?>"><?php echo $prefs['preferences'] ? 'مفعلة' : 'معطلة'; ?></span></li>
                    <li>📊 ملفات التحليل: <span class="status-<?php echo $prefs['analytics'] ? 'enabled' : 'disabled'; ?>"><?php echo $prefs['analytics'] ? 'مفعلة' : 'معطلة'; ?></span></li>
                    <li>🎯 ملفات التسويق: <span class="status-<?php echo $prefs['marketing'] ? 'enabled' : 'disabled'; ?>"><?php echo $prefs['marketing'] ? 'مفعلة' : 'معطلة'; ?></span></li>
                </ul>
            <?php endif; ?>
            
            <div class="cookie-actions">
                <a href="cookies.php" class="btn-learn-more">تعرف على المزيد</a>
                <a href="?withdraw_consent=1" class="btn-withdraw" onclick="return confirm('هل أنت متأكد من سحب الموافقة؟')">
                    سحب الموافقة
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    z-index: 10000;
    box-shadow: 0 -5px 20px rgba(0,0,0,0.2);
    animation: slideUp 0.5s ease-out;
}

.cookie-content {
    max-width: 1200px;
    margin: 0 auto;
}

.cookie-header h3 {
    margin: 0 0 10px 0;
    font-size: 1.2em;
}

.cookie-header p {
    margin: 0 0 20px 0;
    opacity: 0.9;
}

.cookie-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cookie-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.cookie-option {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
}

.cookie-option.essential {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.4);
}

.cookie-option label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.cookie-option input[type="checkbox"] {
    margin: 0;
    transform: scale(1.2);
}

.cookie-option strong {
    display: block;
    margin-bottom: 5px;
}

.cookie-option small {
    display: block;
    opacity: 0.8;
    font-size: 0.9em;
}

.cookie-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.cookie-actions button,
.cookie-actions a {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-accept-all {
    background: #28a745;
    color: white;
}

.btn-accept-all:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-accept-selected {
    background: #007bff;
    color: white;
}

.btn-accept-selected:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.btn-reject-all {
    background: #dc3545;
    color: white;
}

.btn-reject-all:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.btn-learn-more {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.btn-learn-more:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.cookie-settings-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.btn-cookie-settings {
    background: rgba(0,191,174,0.9);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 0.9em;
    box-shadow: 0 4px 15px rgba(0,191,174,0.3);
    transition: all 0.3s ease;
}

.btn-cookie-settings:hover {
    background: rgba(0,191,174,1);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,191,174,0.4);
}

.cookie-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cookie-modal-content {
    background: white;
    border-radius: 15px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.cookie-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.cookie-modal-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    color: #666;
}

.cookie-modal-body {
    padding: 20px;
}

.status-enabled {
    color: #28a745;
    font-weight: bold;
}

.status-disabled {
    color: #dc3545;
    font-weight: bold;
}

.btn-withdraw {
    background: #dc3545;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
}

.btn-withdraw:hover {
    background: #c82333;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .cookie-banner {
        padding: 15px;
    }
    
    .cookie-options {
        grid-template-columns: 1fr;
    }
    
    .cookie-actions {
        flex-direction: column;
    }
    
    .cookie-actions button,
    .cookie-actions a {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
function toggleMenu() {
    var nav = document.querySelector('nav ul');
    nav.classList.toggle('active');
}

// Cookie consent functions
function rejectAllCookies() {
    // Set minimal consent (only essential cookies)
    document.querySelector('input[name="preferences"]').checked = false;
    document.querySelector('input[name="analytics"]').checked = false;
    document.querySelector('input[name="marketing"]').checked = false;
    
    // Submit the form
    document.querySelector('.cookie-form').submit();
}

function showCookieSettings() {
    document.getElementById('cookie-settings-modal').style.display = 'flex';
}

function closeCookieSettings() {
    document.getElementById('cookie-settings-modal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cookie-settings-modal');
    if (event.target === modal) {
        closeCookieSettings();
    }
});

// Auto-hide banner after 10 seconds if user doesn't interact
setTimeout(function() {
    const banner = document.getElementById('cookie-banner');
    if (banner && !banner.classList.contains('interacted')) {
        banner.style.opacity = '0.8';
    }
}, 10000);

// Mark banner as interacted when user interacts with it
document.addEventListener('click', function(event) {
    const banner = document.getElementById('cookie-banner');
    if (banner && banner.contains(event.target)) {
        banner.classList.add('interacted');
    }
});

// Google Analytics loader (only after consent)
function loadAnalytics() {
    <?php if ($cookie_preferences): ?>
        <?php $prefs = json_decode($cookie_preferences, true); ?>
        <?php if ($prefs['analytics']): ?>
        if (window.analyticsLoaded) return;
        window.analyticsLoaded = true;
        var s = document.createElement('script');
        s.src = 'https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
        s.async = true;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', 'G-PVP8CCFQPL');
        <?php endif; ?>
    <?php endif; ?>
}

// Load analytics if consent is given
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($cookie_preferences): ?>
        <?php $prefs = json_decode($cookie_preferences, true); ?>
        <?php if ($prefs['analytics']): ?>
        loadAnalytics();
        <?php endif; ?>
    <?php endif; ?>
});

// Log cookie consent for analytics (if analytics cookies are accepted)
<?php if ($cookie_preferences): ?>
    <?php $prefs = json_decode($cookie_preferences, true); ?>
    <?php if ($prefs['analytics']): ?>
    console.log('Cookie consent analytics enabled');
    // Add analytics tracking here
    <?php endif; ?>
<?php endif; ?>
</script>
<script>
document.getElementById('langSelect').addEventListener('change', function() {
  document.getElementById('langForm').submit();
});
</script>
<script>
window.addEventListener('scroll', function() {
  var header = document.querySelector('header');
  if (window.scrollY > 24) {
    header.classList.add('header-scrolled');
  } else {
    header.classList.remove('header-scrolled');
  }
});
</script>
<script>
    // Auto-hide alert-success after 2.5s
    document.addEventListener('DOMContentLoaded', function() {
      var alert = document.querySelector('.alert-success');
      if (alert) {
        setTimeout(function() {
          alert.style.transition = 'opacity 0.5s';
          alert.style.opacity = '0';
          setTimeout(function() { alert.style.display = 'none'; }, 500);
        }, 2500);
  }
});
</script>
</div>
<script src="main.js?v=1.2"></script>
</body>
</html> 