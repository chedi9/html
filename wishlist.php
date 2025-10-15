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
  header('Location: login.php');
  exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?');
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= __('my_wishlist') ?> - WeBuy</title>
  
  <!-- Bootstrap 5.3+ CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
  
  <!-- JavaScript -->
  <script src="js/theme-controller.js" defer></script>
  <script src="main.js?v=1.4" defer></script>
</head>
<body class="page-transition">
  <!-- Skip to main content for accessibility -->
  <a href="#main-content" class="skip-link">Skip to main content</a>
  
  <?php include 'header.php'; ?>
  
  <main id="main-content" role="main">
    <!-- Wishlist Section -->
    <section class="py-5">
      <div class="container">
        <div class="mb-4">
          <h1 class="display-5 fw-bold"><?= __('my_wishlist') ?></h1>
          <p class="text-muted"><?= __('your_favorite_products') ?></p>
        </div>
        
        <?php if (!empty($wishlist)): ?>
          <div class="row g-4">
            <?php foreach ($wishlist as $prod): ?>
              <?php $prod_name = $prod['name_' . $lang] ?? $prod['name']; ?>
              <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm position-relative">
                  <!-- Remove Button -->
                  <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle wishlist-remove-btn" 
                          data-product-id="<?= $prod['id'] ?>" 
                          title="<?= __('remove_from_wishlist') ?>"
                          style="width: 40px; height: 40px; z-index: 10;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="18" y1="6" x2="6" y2="18"></line>
                      <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                  </button>
                  
                  <a href="product.php?id=<?= $prod['id'] ?>" class="text-decoration-none">
                    <div class="card-img-top" style="height: 200px; overflow: hidden;">
                      <?php 
                      $optimized_image = get_optimized_image('uploads/' . $prod['image'], 'card');
                      ?>
                      <img src="<?php echo $optimized_image['src']; ?>" 
                           srcset="<?php echo $optimized_image['srcset']; ?>" 
                           sizes="<?php echo $optimized_image['sizes']; ?>"
                           alt="<?php echo htmlspecialchars($prod['name']); ?>" 
                           loading="lazy" 
                           class="w-100 h-100 object-fit-cover">
                    </div>
                    
                    <div class="card-body">
                      <h5 class="card-title text-dark"><?= htmlspecialchars($prod_name) ?></h5>
                      <p class="card-text text-primary fw-bold"><?= number_format($prod['price'], 2) ?> <?= __('currency') ?></p>
                      <div class="d-grid">
                        <a href="add_to_cart.php?id=<?= $prod['id'] ?>" class="btn btn-primary">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                            <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                            <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                          </svg>
                          <?= __('add_to_cart') ?>
                        </a>
                      </div>
                    </div>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <!-- Empty Wishlist -->
          <div class="text-center py-5">
            <div class="mb-4">
              <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="text-muted">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </div>
            <h2 class="h3 mb-3"><?= __('wishlist_empty') ?></h2>
            <p class="text-muted mb-4"><?= __('add_products_to_wishlist') ?></p>
            <a href="store.php" class="btn btn-primary btn-lg">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <line x1="9" y1="21" x2="9" y2="9"></line>
              </svg>
              <?= __('browse_products') ?>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  
  <?php include 'footer.php'; ?>
  
  <!-- Wishlist JavaScript -->
  <script>
    document.querySelectorAll('.wishlist-remove-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const productId = this.dataset.productId;
        
        if (confirm('<?= __('confirm_remove_from_wishlist') ?>')) {
          fetch('wishlist_action.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=remove&product_id=' + productId
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              location.reload();
            } else {
              alert(data.message || '<?= __('error_removing_from_wishlist') ?>');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('<?= __('error_removing_from_wishlist') ?>');
          });
        }
      });
    });
  </script>
  
  <!-- Cookie Consent Banner -->
  <?php include 'cookie_consent_banner.php'; ?>
</body>
</html>
