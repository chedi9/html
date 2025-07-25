<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
require 'lang.php';
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
  <link rel="stylesheet" href="beta333.css">
  <?php if (!empty($_SESSION['is_mobile'])): ?>
  <link rel="stylesheet" href="mobile.css">
  <?php endif; ?>
</head>
<body>
<div id="pageContent">
  <div class="container">
    <h2><?= __('my_wishlist') ?></h2>
    <div class="product-grid">
      <?php foreach ($wishlist as $prod): ?>
        <?php $prod_name = $prod['name_' . $lang] ?? $prod['name']; ?>
        <div class="product-card">
          <button class="wishlist-remove-btn" data-product-id="<?= $prod['id'] ?>" title="<?= __('remove_from_wishlist') ?>" style="position:absolute;top:10px;left:10px;background:none;border:none;cursor:pointer;font-size:1.5em;color:#F44336;z-index:2;">✖</button>
          <a href="product.php?id=<?= $prod['id'] ?>">
            <img src="uploads/<?= htmlspecialchars($prod['image'] ?? 'default-product.png') ?>" alt="<?= htmlspecialchars($prod_name) ?>" style="width:120px;height:120px;object-fit:cover;border-radius:12px;margin-bottom:10px;">
            <div class="product-name" style="font-weight:bold;font-size:1.08em;"> <?= htmlspecialchars($prod_name) ?> </div>
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
  <script src="main.js"></script>
</body>
</html> 