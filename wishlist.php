<?php
// Security and compatibility headers
require_once 'security_integration.php';

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'lang.php';
require_once 'includes/thumbnail_helper.php';
if (!isset($_SESSION['user_id'])) {
  echo '<div style="max-width:600px;margin:40px auto;text-align:center;font-size:1.2em;">'.__('login_to_view_wishlist').'</div>';
  exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?');
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= __('my_wishlist') ?></title>
  
  <!-- Bootstrap CSS (local) -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap-custom.css">
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
  
  <!-- JavaScript -->
  <script src="main.js?v=1.4" defer></script>
  
  <?php if (!empty($_SESSION['is_mobile'])): ?>
  <link rel="stylesheet" href="mobile.css">
  <?php endif; ?>
</head>
<body>
  <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:10px;max-width:900px;margin-left:auto;margin-right:auto;gap:18px;">
    <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode" style="background:#00BFAE;color:#fff;border:none;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-left:16px;cursor:pointer;box-shadow:0 2px 8px rgba(0,191,174,0.10);transition:background 0.2s, color 0.2s;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
      </svg>
    </button>
  </div>
<div id="pageContent">
  <div class="container">
    <h2><?= __('my_wishlist') ?></h2>
    <div class="product-grid">
      <?php foreach ($wishlist as $prod): ?>
        <?php $prod_name = $prod['name_' . $lang] ?? $prod['name']; ?>
        <div class="product-card">
          <button class="wishlist-remove-btn" data-product-id="<?= $prod['id'] ?>" title="<?= __('remove_from_wishlist') ?>" style="position:absolute;top:10px;left:10px;background:none;border:none;cursor:pointer;font-size:1.5em;color:#F44336;z-index:2;">✖</button>
          <a href="product.php?id=<?= $prod['id'] ?>">
            <div class="product-img-wrap">
                <div class="skeleton skeleton--image"></div>
                <?php 
                $optimized_image = get_optimized_image('uploads/' . $prod['image'], 'card');
                ?>
                <img src="<?php echo $optimized_image['src']; ?>" 
                     srcset="<?php echo $optimized_image['srcset']; ?>" 
                     sizes="<?php echo $optimized_image['sizes']; ?>"
                     alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                     loading="lazy" 
                     width="280" 
                     height="280"
                     onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
            </div>
            <div class="skeleton skeleton--title"></div>
            <div class="product-name" style="font-weight:bold;font-size:1.08em;"> <?= htmlspecialchars($prod_name) ?> </div>
            <div class="skeleton skeleton--text"></div>
            <div class="product-price" style="color:#00BFAE;font-weight:bold;"> <?= $prod['price'] ?> د.ت </div>
          </a>
        </div>
      <?php endforeach; ?>
      <?php if (!$wishlist): ?>
        <div style="width:100%;text-align:center;color:#888;font-size:1.1em;">لا توجد منتجات في المفضلة بعد.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
  
  <!-- Cookie Consent Banner -->
  <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 