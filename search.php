<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require 'db.php';
require 'lang.php';

// Get filter parameters
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : '';
$in_stock = isset($_GET['in_stock']) ? $_GET['in_stock'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ar';

// Build query with proper joins for ratings and seller info
$where = [];
$params = [];

// Base query with proper joins including disabled sellers
$sql = 'SELECT p.*, s.is_disabled, s.store_name, 
        ds.name as disabled_seller_name, ds.disability_type, ds.priority_level,
        COALESCE(AVG(r.rating), 0) as avg_rating, 
        COUNT(r.id) as review_count
        FROM products p 
        LEFT JOIN sellers s ON p.seller_id = s.id 
        LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
        LEFT JOIN reviews r ON p.id = r.product_id 
        WHERE p.approved = 1';

// Add filters
if ($name) {
    $where[] = '(p.name LIKE ? OR p.name_ar LIKE ? OR p.name_en LIKE ? OR p.name_fr LIKE ?)';
    $params[] = "%$name%";
    $params[] = "%$name%";
    $params[] = "%$name%";
    $params[] = "%$name%";
}

if ($category) {
    $where[] = '(p.description LIKE ? OR p.description_ar LIKE ? OR p.description_en LIKE ? OR p.description_fr LIKE ?)';
    $params[] = "%$category%";
    $params[] = "%$category%";
    $params[] = "%$category%";
    $params[] = "%$category%";
}

if ($min_price !== '') {
    $where[] = 'p.price >= ?';
    $params[] = $min_price;
}

if ($max_price !== '') {
    $where[] = 'p.price <= ?';
    $params[] = $max_price;
}

if ($category_id) {
    $where[] = 'p.category_id = ?';
    $params[] = $category_id;
}

if ($brand) {
    $where[] = '(p.name LIKE ? OR s.store_name LIKE ?)';
    $params[] = "%$brand%";
    $params[] = "%$brand%";
}

if ($in_stock === '1') {
    $where[] = 'p.stock > 0';
}

if ($priority === 'disabled_sellers') {
    $where[] = 'p.disabled_seller_id IS NOT NULL';
}

// Add WHERE conditions
if ($where) {
    $sql .= ' AND ' . implode(' AND ', $where);
}

// Group by product to handle multiple reviews
$sql .= ' GROUP BY p.id';

// Add rating filter after grouping
if ($rating) {
    $sql .= ' HAVING avg_rating >= ?';
    $params[] = $rating;
}

// Always prioritize disabled sellers and priority products
if ($sort === 'price_asc') {
    $sql .= ' ORDER BY p.is_priority_product DESC, ds.priority_level DESC, p.price ASC';
} elseif ($sort === 'price_desc') {
    $sql .= ' ORDER BY p.is_priority_product DESC, ds.priority_level DESC, p.price DESC';
} elseif ($sort === 'newest') {
    $sql .= ' ORDER BY p.is_priority_product DESC, ds.priority_level DESC, p.created_at DESC';
} elseif ($sort === 'rating') {
    $sql .= ' ORDER BY p.is_priority_product DESC, ds.priority_level DESC, avg_rating DESC, review_count DESC';
} elseif ($sort === 'popularity') {
    $sql .= ' ORDER BY s.is_disabled DESC, review_count DESC, avg_rating DESC';
} else {
    $sql .= ' ORDER BY s.is_disabled DESC, p.created_at DESC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// AJAX: Only output product grid if requested
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ?>
    <div class="product-grid">
        <?php if ($products): foreach ($products as $product): ?>
        <?php $prod_name = $product['name_' . $lang] ?? $product['name']; ?>
        <div class="product-card">
            <?php if (!empty($product['disabled_seller_name'])): ?>
                <span class="product-badge" style="background:#FFD600;color:#1A237E;left:auto;right:12px;top:12px;position:absolute;z-index:4;font-weight:bold;">
                    🌟 بائع ذو إعاقة
                </span>
            <?php elseif (!empty($product['is_priority_product'])): ?>
                <span class="product-badge" style="background:#00BFAE;color:#fff;left:auto;right:12px;top:12px;position:absolute;z-index:4;">
                    ⭐ منتج ذو أولوية
                </span>
            <?php endif; ?>
            <a href="product.php?id=<?php echo $product['id']; ?>">
            <?php if ($product['image']): ?>
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?= __('product_image') ?>" loading="lazy">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($prod_name); ?></h3>
            </a>
            <div class="product-rating" style="color:#FFD600;font-size:1.1em;margin-bottom:4px;">
                <?php
                $stars = round($product['avg_rating']);
                for ($i = 1; $i <= 5; $i++) echo $i <= $stars ? '★' : '☆';
                if ($product['review_count'] > 0) echo " <span style='color:#888;font-size:0.95em;'>(" . round($product['avg_rating'], 1) . ")</span>";
                ?>
            </div>
            <div class="price"><?php echo htmlspecialchars($product['price']); ?> <?= __('currency') ?></div>
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

// Get unique brands for filter
$brands = $pdo->query("SELECT DISTINCT s.store_name FROM sellers s JOIN products p ON s.id = p.seller_id WHERE p.approved = 1 AND s.store_name IS NOT NULL AND s.store_name != '' ORDER BY s.store_name")->fetchAll();
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
        .search-container { max-width: 1200px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .search-container h2 { text-align: center; margin-bottom: 30px; }
        .search-form { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .search-form input, .search-form select { padding: 10px; border-radius: 5px; border: 1px solid #ccc; min-width: 120px; }
        .search-form button { padding: 10px 24px; border-radius: 5px; background: var(--primary-color); color: #fff; border: none; font-size: 1em; }
        .product-grid { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .product-card { width: 220px; background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 16px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); position: relative; }
        .product-card img { width: 100%; height: 140px; object-fit: cover; border-radius: 8px; }
        .product-card h3 { margin: 10px 0 5px; font-size: 1.1em; }
        .product-card .price { color: var(--secondary-color); font-weight: bold; }
        .product-card .stock { color: #228B22; font-size: 0.95em; }
        .product-card .out-stock { color: #c00; font-size: 0.95em; }
        .add-cart-btn { margin-top: 10px; padding: 8px 18px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; cursor: pointer; }
        .add-cart-btn:disabled { background: #ccc; cursor: not-allowed; }
        .autocomplete-suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; max-height: 200px; overflow-y: auto; z-index: 1000; }
        .autocomplete-suggestion { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .autocomplete-suggestion:hover { background: #f5f5f5; }
        .autocomplete-suggestion:last-child { border-bottom: none; }
        .search-input-container { position: relative; flex: 1; }
        .filter-tags { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
        .filter-tag { background: var(--primary-color); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em; display: flex; align-items: center; gap: 8px; }
        .filter-tag .remove { cursor: pointer; font-weight: bold; }
        .results-info { text-align: center; margin: 20px 0; color: #666; }
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
    <div class="search-input-container">
        <input type="text" id="liveSearchInput" name="name" placeholder="<?= __('search_placeholder') ?>" autocomplete="off" style="width:100%;padding:14px 18px;border:none;border-radius:14px 0 0 14px;font-size:1.13em;outline:none;" value="<?php echo htmlspecialchars($name); ?>">
        <div id="autocompleteSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
    </div>
    <button type="submit" style="background:var(--primary-color);color:#fff;border:none;border-radius:0 14px 14px 0;padding:0 22px;font-size:1.2em;cursor:pointer;">🔍</button>
    <button type="button" id="toggleFilters" style="background:none;border:none;font-size:1.3em;padding:0 12px;cursor:pointer;" title="<?= __('filter_toggle') ?>">🧰</button>
  </form>
</div>

<!-- Filter Tags -->
<div id="filterTags" class="filter-tags" style="max-width:480px;margin:0 auto 16px auto;justify-content:center;">
    <?php if ($name): ?>
        <span class="filter-tag">البحث: <?= htmlspecialchars($name) ?> <span class="remove" onclick="removeFilter('name')">×</span></span>
    <?php endif; ?>
    <?php if ($category_id): 
        $cat_name = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $cat_name = $cat['name_' . $lang] ?? $cat['name'];
                break;
            }
        }
    ?>
        <span class="filter-tag">التصنيف: <?= htmlspecialchars($cat_name) ?> <span class="remove" onclick="removeFilter('category_id')">×</span></span>
    <?php endif; ?>
    <?php if ($min_price || $max_price): ?>
        <span class="filter-tag">السعر: <?= $min_price ? $min_price : '0' ?> - <?= $max_price ? $max_price : '∞' ?> <span class="remove" onclick="removeFilter('price')">×</span></span>
    <?php endif; ?>
    <?php if ($brand): ?>
        <span class="filter-tag">العلامة التجارية: <?= htmlspecialchars($brand) ?> <span class="remove" onclick="removeFilter('brand')">×</span></span>
    <?php endif; ?>
    <?php if ($rating): ?>
        <span class="filter-tag">التقييم: <?= $rating ?>+ نجوم <span class="remove" onclick="removeFilter('rating')">×</span></span>
    <?php endif; ?>
    <?php if ($in_stock === '1'): ?>
        <span class="filter-tag">متوفر فقط <span class="remove" onclick="removeFilter('in_stock')">×</span></span>
    <?php endif; ?>
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
    
    <select name="brand" style="padding:10px;border-radius:8px;border:1.5px solid #eee;min-width:140px;">
      <option value="">كل العلامات التجارية</option>
      <?php foreach ($brands as $brand_item): ?>
        <option value="<?= htmlspecialchars($brand_item['store_name']) ?>" <?php if ($brand == $brand_item['store_name']) echo 'selected'; ?>><?= htmlspecialchars($brand_item['store_name']) ?></option>
      <?php endforeach; ?>
    </select>
    
    <input type="number" name="min_price" placeholder="<?= __('min_price_placeholder') ?>" min="0" value="<?php echo htmlspecialchars($min_price); ?>" style="padding:10px;border-radius:8px;border:1.5px solid #eee;max-width:110px;">
    <input type="number" name="max_price" placeholder="<?= __('max_price_placeholder') ?>" min="0" value="<?php echo htmlspecialchars($max_price); ?>" style="padding:10px;border-radius:8px;border:1.5px solid #eee;max-width:110px;">
    
    <select name="rating" style="padding:10px;border-radius:8px;border:1.5px solid #eee;min-width:120px;">
      <option value="">أي تقييم</option>
      <option value="5" <?php if ($rating == 5) echo 'selected'; ?>>5 نجوم</option>
      <option value="4" <?php if ($rating == 4) echo 'selected'; ?>>4+ نجوم</option>
      <option value="3" <?php if ($rating == 3) echo 'selected'; ?>>3+ نجوم</option>
      <option value="2" <?php if ($rating == 2) echo 'selected'; ?>>2+ نجوم</option>
      <option value="1" <?php if ($rating == 1) echo 'selected'; ?>>1+ نجوم</option>
    </select>
    
    <select name="in_stock" style="padding:10px;border-radius:8px;border:1.5px solid #eee;min-width:120px;">
      <option value="">جميع المنتجات</option>
      <option value="1" <?php if ($in_stock === '1') echo 'selected'; ?>>متوفر فقط</option>
    </select>
    
    <select name="sort" style="padding:10px;border-radius:8px;border:1.5px solid #eee;min-width:120px;">
      <option value=""><?= __('sort_by') ?>...</option>
      <option value="newest" <?php if ($sort === 'newest') echo 'selected'; ?>><?= __('newest_first') ?></option>
      <option value="price_asc" <?php if ($sort === 'price_asc') echo 'selected'; ?>><?= __('lowest_price') ?></option>
      <option value="price_desc" <?php if ($sort === 'price_desc') echo 'selected'; ?>><?= __('highest_price') ?></option>
      <option value="rating" <?php if ($sort === 'rating') echo 'selected'; ?>>الأعلى تقييماً</option>
      <option value="popularity" <?php if ($sort === 'popularity') echo 'selected'; ?>>الأكثر شعبية</option>
    </select>
    
    <button type="submit" style="background:var(--primary-color);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:1em;"><?= __('apply_filters') ?></button>
    <button type="button" id="closeFilters" style="background:none;border:none;font-size:1.2em;padding:0 10px;cursor:pointer;">✖</button>
  </form>
</div>

<div class="search-container">
    <h2><?= __('advanced_search') ?></h2>
    
    <!-- Results Info -->
    <div class="results-info">
        تم العثور على <?= count($products) ?> منتج<?= count($products) != 1 ? 'ات' : '' ?>
        <?php if ($name || $category_id || $brand || $rating || $in_stock): ?>
            مع الفلاتر المطبقة
        <?php endif; ?>
    </div>
    
    <div class="product-grid">
        <?php if ($products): foreach ($products as $product): ?>
        <?php $prod_name = $product['name_' . $lang] ?? $product['name']; ?>
        <div class="product-card">
            <?php if (!empty($product['disabled_seller_name'])): ?>
                <span class="product-badge" style="background:#FFD600;color:#1A237E;left:auto;right:12px;top:12px;position:absolute;z-index:4;font-weight:bold;">
                    🌟 بائع ذو إعاقة
                </span>
            <?php elseif (!empty($product['is_priority_product'])): ?>
                <span class="product-badge" style="background:#00BFAE;color:#fff;left:auto;right:12px;top:12px;position:absolute;z-index:4;">
                    ⭐ منتج ذو أولوية
                </span>
            <?php endif; ?>
            <a href="product.php?id=<?php echo $product['id']; ?>">
            <?php if ($product['image']): ?>
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?= __('product_image') ?>" loading="lazy">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($prod_name); ?></h3>
            </a>
            <div class="product-rating" style="color:#FFD600;font-size:1.1em;margin-bottom:4px;">
                <?php
                $stars = round($product['avg_rating']);
                for ($i = 1; $i <= 5; $i++) echo $i <= $stars ? '★' : '☆';
                if ($product['review_count'] > 0) echo " <span style='color:#888;font-size:0.95em;'>(" . round($product['avg_rating'], 1) . ")</span>";
                ?>
            </div>
            <div class="price"><?php echo htmlspecialchars($product['price']); ?> <?= __('currency') ?></div>
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
// Filter toggle functionality
document.getElementById('toggleFilters').onclick = function() {
  var panel = document.getElementById('filtersPanel');
  panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
};

document.getElementById('closeFilters').onclick = function() {
  document.getElementById('filtersPanel').style.display = 'none';
};

// Remove filter function
function removeFilter(filterName) {
    const url = new URL(window.location);
    if (filterName === 'price') {
        url.searchParams.delete('min_price');
        url.searchParams.delete('max_price');
    } else {
        url.searchParams.delete(filterName);
    }
    window.location.href = url.toString();
}

// Autocomplete functionality
const searchInput = document.getElementById('liveSearchInput');
const suggestionsDiv = document.getElementById('autocompleteSuggestions');

searchInput.addEventListener('input', debounce(function() {
    const query = this.value.trim();
    if (query.length < 2) {
        suggestionsDiv.style.display = 'none';
        return;
    }
    
    fetch('search_suggest.php?q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            suggestionsDiv.innerHTML = '';
            if (data.length > 0) {
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    div.innerHTML = `<span style="margin-right:8px;">${item.icon}</span>${item.name}`;
                    div.onclick = () => {
                        searchInput.value = item.name;
                        suggestionsDiv.style.display = 'none';
                        fetchResults();
                    };
                    suggestionsDiv.appendChild(div);
                });
                suggestionsDiv.style.display = 'block';
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
            suggestionsDiv.style.display = 'none';
        });
}, 300));

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
        suggestionsDiv.style.display = 'none';
    }
});

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
      if (el.name && el.value) data.set(el.name, el.value);
    });
  }
  return new URLSearchParams(data).toString();
}

function fetchResults() {
  if (!productGrid) return;
  productGrid.innerHTML = '<div style="text-align:center;padding:40px;">جاري التحميل...</div>';
  fetch('search.php?' + getFilters() + '&ajax=1')
    .then(res => res.text())
    .then(html => { 
        productGrid.innerHTML = html;
        // Update results count
        const resultsCount = productGrid.querySelectorAll('.product-card').length;
        const resultsInfo = document.querySelector('.results-info');
        if (resultsInfo) {
            resultsInfo.textContent = `تم العثور على ${resultsCount} منتج${resultsCount != 1 ? 'ات' : ''}`;
        }
    })
    .catch(error => {
        console.error('Error fetching results:', error);
        productGrid.innerHTML = '<div style="text-align:center;padding:40px;color:red;">حدث خطأ في تحميل النتائج</div>';
    });
}

const debouncedFetch = debounce(fetchResults, 300);

if (searchForm) {
    searchForm.addEventListener('input', debouncedFetch);
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchResults();
    });
}

if (filtersPanel) {
    filtersPanel.addEventListener('change', fetchResults);
}

// Google Analytics
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
</body>
</html> 