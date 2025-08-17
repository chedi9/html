ke<?php
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
<?php include_once 'include_load_analytics.php'; ?>
<script src="js/carousel.js"></script>

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
  <style>
    .hero-carousel {
      position: relative;
      overflow: hidden;
      font-family: 'Cairo', sans-serif;
      color: #fff;
    }

    .carousel-wrapper {
      position: relative;
    }

    .carousel-slide {
      display: none;
      padding: 6rem 2rem;
      background: var(--bg);
      text-align: center;
      position: relative;
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.8s ease, transform 0.8s ease;
    }

    .carousel-slide::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.35);
      z-index: 0;
    }

    .carousel-slide.active {
      display: block;
      opacity: 1;
      transform: translateY(0);
    }

    .hero-content {
      position: relative;
      z-index: 1;
    }

    .hero-content h1 {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 1rem;
      line-height: 1.2;
      text-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    .hero-content p {
      font-size: 1.25rem;
      margin-bottom: 2rem;
      max-width: 750px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.7;
      opacity: 0.95;
    }

    .btn-primary {
      background: #fff;
      color: #1A237E;
      padding: 0.9rem 1.8rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #f0f0f0;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.35);
    }

    .arrow {
      font-size: 1.2rem;
      transition: transform 0.3s ease;
    }

    .btn-primary:hover .arrow {
      transform: translateX(4px);
    }

    .carousel-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0,0,0,0.4);
      border: none;
      font-size: 2.5rem;
      color: #fff;
      cursor: pointer;
      padding: 0.5rem 1rem;
      border-radius: 50%;
      backdrop-filter: blur(6px);
      transition: background 0.3s ease;
      z-index: 2;
    }

    .carousel-nav:hover {
      background: rgba(0,0,0,0.65);
    }

    .carousel-nav.prev {
      left: 1rem;
    }

    .carousel-nav.next {
      right: 1rem;
    }

    .carousel-indicators {
      display: flex;
      justify-content: center;
      gap: 0.6rem;
      margin-top: 1.5rem;
      z-index: 2;
    }

    .carousel-indicators button {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      border: none;
      background-color: rgba(255, 255, 255, 0.6);
      cursor: pointer;
      transition: transform 0.3s ease, background-color 0.3s ease;
    }

    .carousel-indicators button:hover {
      transform: scale(1.2);
      background-color: #fff;
    }

    .carousel-indicators .active {
      background-color: #fff;
      transform: scale(1.3);
      box-shadow: 0 0 8px rgba(255,255,255,0.8);
    }
  </style>

  <section class="hero-carousel" aria-label="Hero Banner">
    <div class="carousel-wrapper">

      <div class="carousel-slide active" style="--bg: linear-gradient(135deg, #1A237E, #3949AB);">
        <div class="hero-content">
          <h1><?php echo __('discover_tunisian_talents'); ?></h1>
          <p><?php echo __('webuy_platform_description'); ?></p>
          <a href="#categories" class="btn-primary">
            <?php echo __('browse_categories'); ?>
            <span class="arrow">→</span>
          </a>
        </div>
      </div>

      <div class="carousel-slide" style="--bg: linear-gradient(135deg, #FFD600, #FFAB00);">
        <div class="hero-content">
          <h1>🌟 <?php echo __('products_from_disabled_sellers'); ?></h1>
          <p><?php echo __('support_disabled_sellers'); ?></p>
          <a href="store.php?priority=disabled_sellers" class="btn-primary">
            <?php echo __('view_all_disabled_seller_products'); ?>
            <span class="arrow">→</span>
          </a>
        </div>
      </div>

      <div class="carousel-slide" style="--bg: linear-gradient(135deg, #00BFAE, #00897B);">
        <div class="hero-content">
          <h1><?php echo __('fast_delivery'); ?></h1>
          <p><?php echo __('secure_payment'); ?></p>
          <a href="store.php" class="btn-primary">
            <?php echo __('shop_now'); ?>
            <span class="arrow">→</span>
          </a>
        </div>
      </div>

      <button class="carousel-nav prev" aria-label="Previous slide">‹</button>
      <button class="carousel-nav next" aria-label="Next slide">›</button>

      <div class="carousel-indicators">
        <button class="active" aria-label="Slide 1"></button>
        <button aria-label="Slide 2"></button>
        <button aria-label="Slide 3"></button>
      </div>
    </div>
  </section>
</main>

<?php include_once 'include_load_analytics.php'; ?>
    
    <?php include 'footer.php'; ?>
    
    <!-- Enhanced JavaScript -->
    <script src="js/carousel-controller.js" defer></script>
    <script src="js/quick-view-modal.js" defer></script>
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