<?php
require 'db.php';
require 'lang.php';
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';
// Build query
$where = [];
$params = [];
if ($name) {
    $where[] = 'name LIKE ?';
    $params[] = "%$name%";
}
if ($category) {
    $where[] = 'description LIKE ?';
    $params[] = "%$category%";
}
if ($min_price !== '') {
    $where[] = 'price >= ?';
    $params[] = $min_price;
}
if ($max_price !== '') {
    $where[] = 'price <= ?';
    $params[] = $max_price;
}
if ($category_id) {
    $where[] = 'category_id = ?';
    $params[] = $category_id;
}
$sql = 'SELECT * FROM products';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
if ($sort === 'price_asc') {
    $sql .= ' ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
    $sql .= ' ORDER BY price DESC';
} elseif ($sort === 'newest') {
    $sql .= ' ORDER BY created_at DESC';
} else {
    $sql .= ' ORDER BY created_at DESC';
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
// Fetch average ratings for all products
$ratings = [];
$rate_stmt = $pdo->query("SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews GROUP BY product_id");
while ($row = $rate_stmt->fetch()) {
    $ratings[$row['product_id']] = [
        'avg' => round($row['avg_rating'], 1),
        'count' => $row['review_count']
    ];
}
// AJAX: Only output product grid if requested
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ?>
    <div class="product-grid">
        <?php if ($products): foreach ($products as $product): ?>
        <?php $prod_name = $product['name_' . $lang] ?? $product['name']; ?>
        <div class="product-card">
            <a href="product.php?id=<?php echo $product['id']; ?>">
            <?php if ($product['image']): ?>
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?= __('product_image') ?>" loading="lazy">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($prod_name); ?></h3>
            </a>
            <?php $r = $ratings[$product['id']] ?? null; ?>
            <div class="product-rating" style="color:#FFD600;font-size:1.1em;margin-bottom:4px;">
                <?php
                $stars = $r ? round($r['avg']) : 0;
                for ($i = 1; $i <= 5; $i++) echo $i <= $stars ? '★' : '☆';
                if ($r) echo " <span style='color:#888;font-size:0.95em;'>($r[avg])</span>";
                ?>
            </div>
            <div class="price"><?php echo htmlspecialchars($product['price']); ?> د.ت</div>
            <?php if ($product['stock'] > 0): ?>
                <div class="stock"><?= __('stock') ?>: <?php echo htmlspecialchars($product['stock']); ?></div>
                <form action="add_to_cart.php" method="get" class="add-to-cart-form">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="add-cart-btn"><?= __('add_to_cart') ?></button>
                </form>
            <?php else: ?>
                <div class="out-stock"><?= __('out_of_stock') ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; else: ?>
            <p><?= __('no_products_found') ?></p>
        <?php endif; ?>
    </div>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('advanced_search') ?></title>
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
    <style>
        .search-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .search-container h2 { text-align: center; margin-bottom: 30px; }
        .search-form { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .search-form input, .search-form select { padding: 10px; border-radius: 5px; border: 1px solid #ccc; min-width: 120px; }
        .search-form button { padding: 10px 24px; border-radius: 5px; background: var(--primary-color); color: #fff; border: none; font-size: 1em; }
        .product-grid { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .product-card { width: 220px; background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 16px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .product-card img { width: 100%; height: 140px; object-fit: cover; border-radius: 8px; }
        .product-card h3 { margin: 10px 0 5px; font-size: 1.1em; }
        .product-card .price { color: var(--secondary-color); font-weight: bold; }
        .product-card .stock { color: #228B22; font-size: 0.95em; }
        .product-card .out-stock { color: #c00; font-size: 0.95em; }
        .add-cart-btn { margin-top: 10px; padding: 8px 18px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; cursor: pointer; }
        .add-cart-btn:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
?>
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert-success" id="cartAlert">
        <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
        <button class="close-btn" onclick="document.getElementById('cartAlert').style.display='none'">&times;</button>
    </div>
<?php endif; ?>
<?php
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
?>
<div class="header-search-row" style="margin: 32px auto 24px auto; max-width: 480px;">
  <form action="search.php" method="get" class="central-search-bar" style="display:flex;align-items:center;gap:0;background:#fff;border-radius:14px;box-shadow:0 2px 8px #00BFAE11;padding:0 0 0 0;">
    <input type="text" id="liveSearchInput" name="name" placeholder="<?= __('search_placeholder') ?>" autocomplete="off" style="flex:1;padding:14px 18px;border:none;border-radius:14px 0 0 14px;font-size:1.13em;outline:none;" value="<?php echo htmlspecialchars($name); ?>">
    <button type="submit" style="background:var(--primary-color);color:#fff;border:none;border-radius:0 14px 14px 0;padding:0 22px;font-size:1.2em;cursor:pointer;">🔍</button>
    <button type="button" id="toggleFilters" style="background:none;border:none;font-size:1.3em;padding:0 12px;cursor:pointer;" title="تصفية">🧰</button>
  </form>
</div>
<div id="filtersPanel" style="display:none;max-width:480px;margin:0 auto 24px auto;background:#fff;border-radius:14px;box-shadow:0 2px 8px #00BFAE11;padding:18px 18px 8px 18px;">
  <form action="search.php" method="get" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
    <select name="category_id" style="padding:10px;border-radius:8px;border:1.5px solid #eee;min-width:140px;">
      <option value="">كل التصنيفات</option>
      <?php foreach ($categories as $cat): ?>
        <?php $cat_name = $cat['name_' . $lang] ?? $cat['name']; ?>
        <option value="<?= $cat['id'] ?>" <?php if ($category_id == $cat['id']) echo 'selected'; ?>><?= htmlspecialchars($cat_name) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="min_price" placeholder="السعر الأدنى" min="0" value="<?php echo htmlspecialchars($min_price); ?>" style="padding:10px;border-radius:8px;border:1.5px solid #eee;max-width:110px;">
    <input type="number" name="max_price" placeholder="السعر الأعلى" min="0" value="<?php echo htmlspecialchars($max_price); ?>" style="padding:10px;border-radius:8px;border:1.5px solid #eee;max-width:110px;">
    <select name="sort" style="padding:10px;border-radius:8px;border:1.5px solid #eee;min-width:120px;">
      <option value="">ترتيب حسب...</option>
      <option value="price_asc" <?php if (isset($_GET['sort']) && $_GET['sort']==='price_asc') echo 'selected'; ?>>الأقل سعراً</option>
      <option value="price_desc" <?php if (isset($_GET['sort']) && $_GET['sort']==='price_desc') echo 'selected'; ?>>الأعلى سعراً</option>
      <option value="newest" <?php if (isset($_GET['sort']) && $_GET['sort']==='newest') echo 'selected'; ?>>الأحدث</option>
    </select>
    <button type="submit" style="background:var(--primary-color);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:1em;">تطبيق</button>
    <button type="button" id="closeFilters" style="background:none;border:none;font-size:1.2em;padding:0 10px;cursor:pointer;">✖</button>
  </form>
</div>
<script>
document.getElementById('toggleFilters').onclick = function() {
  var panel = document.getElementById('filtersPanel');
  panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
};
document.getElementById('closeFilters').onclick = function() {
  document.getElementById('filtersPanel').style.display = 'none';
};
</script>
    <div class="search-container">
        <h2><?= __('advanced_search') ?></h2>
        <div class="product-grid">
            <?php if ($products): foreach ($products as $product): ?>
            <?php $prod_name = $product['name_' . $lang] ?? $product['name']; ?>
            <div class="product-card">
                <a href="product.php?id=<?php echo $product['id']; ?>">
                <?php if ($product['image']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?= __('product_image') ?>" loading="lazy">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($prod_name); ?></h3>
                </a>
                <?php $r = $ratings[$product['id']] ?? null; ?>
                <div class="product-rating" style="color:#FFD600;font-size:1.1em;margin-bottom:4px;">
                    <?php
                    $stars = $r ? round($r['avg']) : 0;
                    for ($i = 1; $i <= 5; $i++) echo $i <= $stars ? '★' : '☆';
                    if ($r) echo " <span style='color:#888;font-size:0.95em;'>($r[avg])</span>";
                    ?>
                </div>
                <div class="price"><?php echo htmlspecialchars($product['price']); ?> د.ت</div>
                <?php if ($product['stock'] > 0): ?>
                    <div class="stock"><?= __('stock') ?>: <?php echo htmlspecialchars($product['stock']); ?></div>
                    <form action="add_to_cart.php" method="get" class="add-to-cart-form">
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="add-cart-btn"><?= __('add_to_cart') ?></button>
                    </form>
                <?php else: ?>
                    <div class="out-stock"><?= __('out_of_stock') ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; else: ?>
                <p><?= __('no_products_found') ?></p>
            <?php endif; ?>
        </div>
    </div>
<script src="main.js"></script>
<script>
if (!localStorage.getItem('cookiesAccepted')) {
  // do nothing, wait for accept
} else {
  var gaScript = document.createElement('script');
  gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
  gaScript.async = true;
  document.head.appendChild(gaScript);
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-PVP8CCFQPL');
}
</script>
<script>
var acceptBtn = document.getElementById('acceptCookiesBtn');
if (acceptBtn) {
  acceptBtn.addEventListener('click', function() {
    var gaScript = document.createElement('script');
    gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=G-PVP8CCFQPL';
    gaScript.async = true;
    document.head.appendChild(gaScript);
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-PVP8CCFQPL');
  });
}
</script>
<script>
// Debounce function
function debounce(fn, delay) {
  let timer = null;
  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}
const searchForm = document.querySelector('.central-search-bar');
const filtersPanel = document.getElementById('filtersPanel');
const productGrid = document.querySelector('.product-grid');
function getFilters() {
  const data = new FormData(searchForm);
  if (filtersPanel) {
    Array.from(filtersPanel.querySelectorAll('input, select')).forEach(el => {
      if (el.name) data.set(el.name, el.value);
    });
  }
  return new URLSearchParams(data).toString();
}
function fetchResults() {
  if (!productGrid) return;
  productGrid.innerHTML = '<div style="text-align:center;padding:40px;">جاري التحميل...</div>';
  fetch('search.php?' + getFilters() + '&ajax=1')
    .then(res => res.text())
    .then(html => { productGrid.innerHTML = html; });
}
const debouncedFetch = debounce(fetchResults, 300);
if (searchForm) searchForm.addEventListener('input', debouncedFetch);
if (filtersPanel) filtersPanel.addEventListener('change', fetchResults);
</script>
</div>
</body>
</html> 