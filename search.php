<?php
// Security and compatibility headers
require_once 'security_integration.php';

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
session_start();
require 'db.php';
require 'lang.php';
require_once 'includes/thumbnail_helper.php';

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

// Add sorting
switch ($sort) {
    case 'price_low':
        $sql .= ' ORDER BY p.price ASC';
        break;
    case 'price_high':
        $sql .= ' ORDER BY p.price DESC';
        break;
    case 'name':
        $sql .= ' ORDER BY p.name ASC';
        break;
    case 'rating':
        $sql .= ' ORDER BY avg_rating DESC';
        break;
    default:
        $sql .= ' ORDER BY p.created_at DESC';
}

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Handle AJAX requests for live search
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $results = [];
    foreach ($products as $product) {
        $prod_name = $product['name'];
        if ($lang === 'ar' && !empty($product['name_ar'])) $prod_name = $product['name_ar'];
        elseif ($lang === 'en' && !empty($product['name_en'])) $prod_name = $product['name_en'];
        elseif ($lang === 'fr' && !empty($product['name_fr'])) $prod_name = $product['name_fr'];
        
        $results[] = [
            'id' => $product['id'],
            'name' => $prod_name,
            'price' => $product['price'],
            'image' => $product['image'],
            'avg_rating' => $product['avg_rating'],
            'review_count' => $product['review_count'],
            'stock' => $product['stock'],
            'disabled_seller_name' => $product['disabled_seller_name'],
            'is_priority_product' => !empty($product['disabled_seller_id'])
        ];
    }
    echo json_encode($results);
    exit;
}

// Check if user is logged in for wishlist functionality
$user_id = $_SESSION['user_id'] ?? null;
$wishlist_items = [];
if ($user_id) {
    $wishlist_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wishlist_stmt->execute([$user_id]);
    $wishlist_items = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Handle AJAX results display
if (isset($_GET['display_results']) && $_GET['display_results'] === '1') {
    ?>
    <div class="product-grid">
        <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
        <?php 
        $prod_name = $product['name'];
        if ($lang === 'ar' && !empty($product['name_ar'])) $prod_name = $product['name_ar'];
        elseif ($lang === 'en' && !empty($product['name_en'])) $prod_name = $product['name_en'];
        elseif ($lang === 'fr' && !empty($product['name_fr'])) $prod_name = $product['name_fr'];
        ?>
        <div class="product-card">
            <?php if ($user_id): ?>
                <?php $in_wishlist = in_array($product['id'], $wishlist_items); ?>
                <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" title="<?= __('add_to_favorites') ?>" style="position: absolute; top: 12px; left: 12px; z-index: 3; background: none; border: none; cursor: pointer; font-size: 1.5em; color: <?= $in_wishlist ? '#F44336' : '#FFD600' ?>;"><?= $in_wishlist ? '★' : '☆' ?></button>
            <?php endif; ?>
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
                <?php 
                $optimized_image = get_optimized_image('uploads/' . $product['image'], 'card');
                ?>
                <img src="<?php echo $optimized_image['src']; ?>" 
                     srcset="<?php echo $optimized_image['srcset']; ?>" 
                     sizes="<?php echo $optimized_image['sizes']; ?>"
                     alt="<?= __('product_image') ?>" 
                     loading="lazy"
                     width="220" 
                     height="140"
                     onload="this.classList.add('loaded');">
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
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('search_products'); ?> - WeBuy</title>
    
    <!-- Bootstrap 5.3+ CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- WeBuy Custom Bootstrap Configuration -->
    <link rel="stylesheet" href="css/bootstrap-custom.css">
    
    <!-- Legacy CSS for gradual migration -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.4" defer></script>
    <style>
        .autocomplete-suggestions { 
            position: absolute; 
            top: 100%; 
            left: 0; 
            right: 0; 
            background: white; 
            border: 1px solid var(--bs-border-color); 
            border-top: none; 
            border-radius: 0 0 0.375rem 0.375rem; 
            max-height: 200px; 
            overflow-y: auto; 
            z-index: 1000; 
        }
        .autocomplete-suggestion { 
            padding: 10px; 
            cursor: pointer; 
            border-bottom: 1px solid var(--bs-border-color); 
        }
        .autocomplete-suggestion:hover { 
            background: var(--bs-light); 
        }
        .autocomplete-suggestion:last-child { 
            border-bottom: none; 
        }
        .search-input-container { 
            position: relative; 
        }
        .rating-stars .star {
            color: #fbbf24;
            font-size: 1.25rem;
        }
        .rating-stars .star:not(.filled) {
            color: #d1d5db;
        }
    </style>
</head>
<body class="page-transition">
<?php include 'header.php'; ?>

<div class="search-container">
    <h2><?= __('search_products') ?></h2>
    
    <!-- Search Form -->
    <form class="search-form" method="GET" action="">
        <div class="search-input-container">
            <input type="text" id="liveSearchInput" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="<?= __('search_placeholder') ?>" autocomplete="off">
            <div id="autocompleteSuggestions" class="autocomplete-suggestions" style="display: none;"></div>
        </div>
        
        <select name="category_id">
            <option value=""><?= __('all_categories') ?></option>
            <?php foreach ($brands as $brand_item): ?>
                <option value="<?= $brand_item['store_name'] ?>" <?= $brand === $brand_item['store_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($brand_item['store_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="number" name="min_price" value="<?= $min_price ?>" placeholder="<?= __('min_price') ?>" step="0.01">
        <input type="number" name="max_price" value="<?= $max_price ?>" placeholder="<?= __('max_price') ?>" step="0.01">
        
        <select name="in_stock">
            <option value=""><?= __('all_products') ?></option>
            <option value="1" <?= $in_stock === '1' ? 'selected' : '' ?>><?= __('in_stock_only') ?></option>
        </select>
        
        <select name="priority">
            <option value=""><?= __('all_products') ?></option>
            <option value="disabled_sellers" <?= $priority === 'disabled_sellers' ? 'selected' : '' ?>><?= __('disabled_sellers_only') ?></option>
        </select>
        
        <select name="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= __('newest') ?></option>
            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>><?= __('price_low_to_high') ?></option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>><?= __('price_high_to_low') ?></option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>><?= __('name') ?></option>
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>><?= __('rating') ?></option>
        </select>
        
        <button type="submit"><?= __('search') ?></button>
    </form>
    
    <!-- Filter Tags -->
    <div class="filter-tags">
        <?php if ($name): ?>
            <span class="filter-tag">
                <?= __('name') ?>: <?= htmlspecialchars($name) ?>
                <span class="remove" onclick="removeFilter('name')">×</span>
            </span>
        <?php endif; ?>
        <?php if ($min_price || $max_price): ?>
            <span class="filter-tag">
                <?= __('price') ?>: <?= $min_price ? $min_price : '0' ?> - <?= $max_price ? $max_price : '∞' ?>
                <span class="remove" onclick="removeFilter('price')">×</span>
            </span>
        <?php endif; ?>
        <?php if ($priority === 'disabled_sellers'): ?>
            <span class="filter-tag">
                <?= __('disabled_sellers_only') ?>
                <span class="remove" onclick="removeFilter('priority')">×</span>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Results Info -->
    <div class="results-info">
        <?= sprintf(__('found_products'), count($products)) ?>
    </div>
    
    <!-- Product Grid -->
    <div class="product-grid">
        <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
        <?php 
        $prod_name = $product['name'];
        if ($lang === 'ar' && !empty($product['name_ar'])) $prod_name = $product['name_ar'];
        elseif ($lang === 'en' && !empty($product['name_en'])) $prod_name = $product['name_en'];
        elseif ($lang === 'fr' && !empty($product['name_fr'])) $prod_name = $product['name_fr'];
        ?>
        <div class="product-card">
            <?php if ($user_id): ?>
                <?php $in_wishlist = in_array($product['id'], $wishlist_items); ?>
                <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" title="<?= __('add_to_favorites') ?>" style="position: absolute; top: 12px; left: 12px; z-index: 3; background: none; border: none; cursor: pointer; font-size: 1.5em; color: <?= $in_wishlist ? '#F44336' : '#FFD600' ?>;"><?= $in_wishlist ? '★' : '☆' ?></button>
            <?php endif; ?>
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
                <?php 
                $optimized_image = get_optimized_image('uploads/' . $product['image'], 'card');
                ?>
                <img src="<?php echo $optimized_image['src']; ?>" 
                     srcset="<?php echo $optimized_image['srcset']; ?>" 
                     sizes="<?php echo $optimized_image['sizes']; ?>"
                     alt="<?= __('product_image') ?>" 
                     loading="lazy"
                     width="220" 
                     height="140"
                     onload="this.classList.add('loaded');">
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

<script src="main.js?v=1.4"></script>
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
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Fetch results function
function fetchResults() {
    const form = document.querySelector('.search-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    fetch('search.php?display_results=1&' + params.toString())
        .then(response => response.text())
        .then(html => {
            document.querySelector('.product-grid').innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching results:', error);
        });
}
</script>

<?php include 'footer.php'; ?>
</body>
</html> 