<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require 'db.php';
require 'lang.php';
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
    <div id="cookieBanner" style="display:none;position:fixed;bottom:18px;left:50%;transform:translateX(-50%);background:#fff;border:1.5px solid var(--accent-color);box-shadow:0 2px 8px rgba(0,191,174,0.10);border-radius:14px;padding:18px 32px;z-index:5000;min-width:260px;max-width:95vw;text-align:center;font-size:1.08em;">
        <span>يستخدم هذا الموقع الكوكيز لتحسين تجربتك. <a href="cookies.php" style="color:var(--accent-color);text-decoration:underline;">اعرف المزيد</a></span>
        <button id="acceptCookiesBtn" style="margin-right:18px;background:var(--accent-color);color:#fff;border:none;border-radius:8px;padding:8px 22px;font-weight:bold;cursor:pointer;">موافق</button>
        <button id="rejectCookiesBtn" style="background:#eee;color:#1A237E;border:none;border-radius:8px;padding:8px 22px;font-weight:bold;cursor:pointer;">رفض</button>
    </div>
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
    <script>
    function toggleMenu() {
        var nav = document.querySelector('nav ul');
        nav.classList.toggle('active');
    }
    </script>
    <script>
    // Show cookie banner if not accepted
    if (!localStorage.getItem('cookiesAccepted')) {
      document.getElementById('cookieBanner').style.display = 'block';
    }
    document.getElementById('acceptCookiesBtn').onclick = function() {
      localStorage.setItem('cookiesAccepted', '1');
      document.getElementById('cookieBanner').style.display = 'none';
    };
    </script>
    <script>
    // Google Analytics loader (only after consent)
    function loadAnalytics() {
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
}
    // Cookie consent logic
    document.addEventListener('DOMContentLoaded', function() {
      var consent = localStorage.getItem('cookieConsent');
      var banner = document.getElementById('cookieBanner');
      if (consent === 'accepted') {
        loadAnalytics();
        if (banner) banner.style.display = 'none';
      } else if (consent === 'rejected') {
        if (banner) banner.style.display = 'none';
      } else {
        if (banner) banner.style.display = 'block';
      }
var acceptBtn = document.getElementById('acceptCookiesBtn');
      var rejectBtn = document.getElementById('rejectCookiesBtn');
if (acceptBtn) {
  acceptBtn.addEventListener('click', function() {
          localStorage.setItem('cookieConsent', 'accepted');
          loadAnalytics();
          if (banner) banner.style.display = 'none';
        });
      }
      if (rejectBtn) {
        rejectBtn.addEventListener('click', function() {
          localStorage.setItem('cookieConsent', 'rejected');
          if (banner) banner.style.display = 'none';
        });
      }
    });
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