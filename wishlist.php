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
  echo '<div>'.__('login_to_view_wishlist').'</div>';
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
  
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
  
  <!-- JavaScript -->
  <script src="main.js?v=1.4" defer></script>
  
</head>
<body>
  <div>
    <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle dark mode">
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
          <button class="wishlist-remove-btn" data-product-id="<?= $prod['id'] ?>" title="<?= __('remove_from_wishlist') ?>">✖</button>
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
            <div class="product-name"> <?= htmlspecialchars($prod_name) ?> </div>
            <div class="skeleton skeleton--text"></div>
            <div class="product-price"> <?= $prod['price'] ?> د.ت </div>
          </a>
        </div>
      <?php endforeach; ?>
      <?php if (!$wishlist): ?>
        <div>لا توجد منتجات في المفضلة بعد.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
  
  <!-- Cookie Consent Banner -->
  <?php include 'cookie_consent_banner.php'; ?>
</body>
</html> 