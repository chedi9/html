<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ['p.approved = 1'];
$params = [];

if ($category_id) {
    $where_conditions[] = 'p.category_id = ?';
    $params[] = $category_id;
}

if ($search) {
    $where_conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM products p WHERE $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Build sort clause
$sort_clause = 'ORDER BY p.created_at DESC';
switch ($sort) {
    case 'price_low':
        $sort_clause = 'ORDER BY p.price ASC';
        break;
    case 'price_high':
        $sort_clause = 'ORDER BY p.price DESC';
        break;
    case 'name':
        $sort_clause = 'ORDER BY p.name ASC';
        break;
    case 'popular':
        $sort_clause = 'ORDER BY p.views DESC';
        break;
}

// Get products
$sql = "
    SELECT p.*, c.name as category_name, s.store_name as seller_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE $where_clause
    $sort_clause
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
$categories = $stmt->fetchAll();

$page_title = __('store') . ' - WeBuy';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
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
    <script src="main.js?v=1.3" defer></script>
    
    <!-- Store page specific styles -->
    <style>
        .store-hero {
            background: linear-gradient(135deg, var(--color-primary-50), var(--color-accent-50));
            padding: var(--space-8) 0;
            text-align: center;
        }
        
        .store-hero__title {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-gray-900);
            margin-bottom: var(--space-4);
        }
        
        .store-hero__subtitle {
            font-size: var(--font-size-lg);
            color: var(--color-gray-600);
            margin-bottom: var(--space-6);
        }
        
        .store-filters {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--space-8);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }
        
        .filter-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            color: var(--color-gray-700);
        }
        
        .filter-input {
            padding: var(--space-3);
            border: 2px solid var(--color-gray-300);
            border-radius: var(--border-radius-md);
            font-size: var(--font-size-base);
            transition: all var(--transition-fast);
        }
        
        .filter-input:focus {
            border-color: var(--color-primary-500);
            box-shadow: 0 0 0 3px var(--color-primary-100);
        }
        
        .filter-btn {
            padding: var(--space-3) var(--space-6);
            background: var(--color-primary-600);
            color: var(--color-white);
            border: none;
            border-radius: var(--border-radius-md);
            font-weight: var(--font-weight-medium);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .filter-btn:hover {
            background: var(--color-primary-700);
            transform: translateY(-1px);
        }
        
        .store-results {
            margin-bottom: var(--space-8);
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-6);
            flex-wrap: wrap;
            gap: var(--space-4);
        }
        
        .results-count {
            font-size: var(--font-size-lg);
            color: var(--color-gray-600);
        }
        
        .results-sort {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        
        .sort-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            color: var(--color-gray-700);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }
        
        .product-card {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-fast);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-card__image {
            aspect-ratio: 1;
            overflow: hidden;
            position: relative;
        }
        
        .product-card__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-medium);
        }
        
        .product-card:hover .product-card__image img {
            transform: scale(1.05);
        }
        
        .product-card__badge {
            position: absolute;
            top: var(--space-3);
            right: var(--space-3);
            background: var(--color-primary-600);
            color: var(--color-white);
            padding: var(--space-1) var(--space-2);
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-medium);
        }
        
        .product-card__body {
            padding: var(--space-4);
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-card__title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--color-gray-900);
            margin-bottom: var(--space-2);
            line-height: var(--line-height-tight);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-card__title a {
            color: inherit;
            text-decoration: none;
        }
        
        .product-card__title a:hover {
            color: var(--color-primary-600);
        }
        
        .product-card__meta {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-3);
            font-size: var(--font-size-sm);
            color: var(--color-gray-600);
        }
        
        .product-card__price {
            font-size: var(--font-size-xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-primary-600);
            margin-bottom: var(--space-4);
        }
        
        .product-card__actions {
            margin-top: auto;
            display: flex;
            gap: var(--space-2);
        }
        
        .product-card__btn {
            flex: 1;
            padding: var(--space-2) var(--space-3);
            border: none;
            border-radius: var(--border-radius-md);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-1);
        }
        
        .product-card__btn--primary {
            background: var(--color-primary-600);
            color: var(--color-white);
        }
        
        .product-card__btn--primary:hover {
            background: var(--color-primary-700);
        }
        
        .product-card__btn--secondary {
            background: var(--color-gray-100);
            color: var(--color-gray-700);
        }
        
        .product-card__btn--secondary:hover {
            background: var(--color-gray-200);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-2);
            margin-top: var(--space-8);
        }
        
        .pagination__link {
            padding: var(--space-3) var(--space-4);
            border: 2px solid var(--color-gray-300);
            border-radius: var(--border-radius-md);
            color: var(--color-gray-700);
            text-decoration: none;
            font-weight: var(--font-weight-medium);
            transition: all var(--transition-fast);
        }
        
        .pagination__link:hover {
            border-color: var(--color-primary-500);
            color: var(--color-primary-600);
        }
        
        .pagination__link--active {
            background: var(--color-primary-600);
            border-color: var(--color-primary-600);
            color: var(--color-white);
        }
        
        .pagination__link--disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: var(--space-12) var(--space-4);
            color: var(--color-gray-600);
        }
        
        .empty-state__icon {
            font-size: var(--font-size-4xl);
            margin-bottom: var(--space-4);
            opacity: 0.5;
        }
        
        .empty-state__title {
            font-size: var(--font-size-xl);
            font-weight: var(--font-weight-semibold);
            margin-bottom: var(--space-2);
            color: var(--color-gray-700);
        }
        
        .empty-state__text {
            font-size: var(--font-size-lg);
            margin-bottom: var(--space-6);
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: var(--space-4);
            }
            
            .product-card__actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
    <main id="main-content" role="main">
        <!-- Store Hero Section -->
        <section class="store-hero">
            <div class="container">
                <h1 class="store-hero__title"><?php echo __('store'); ?></h1>
                <p class="store-hero__subtitle"><?php echo __('discover_amazing_products'); ?></p>
            </div>
        </section>
        
        <!-- Store Filters -->
        <section class="store-filters">
            <div class="container">
                <form method="GET" class="filters-grid">
                    <div class="filter-group">
                        <label for="search" class="filter-label"><?php echo __('search'); ?></label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="filter-input" placeholder="<?php echo __('search_products'); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="category" class="filter-label"><?php echo __('category'); ?></label>
                        <select id="category" name="category" class="filter-input">
                            <option value=""><?php echo __('all_categories'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort" class="filter-label"><?php echo __('sort_by'); ?></label>
                        <select id="sort" name="sort" class="filter-input">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                                <?php echo __('newest'); ?>
                            </option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>
                                <?php echo __('price_low_to_high'); ?>
                            </option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>
                                <?php echo __('price_high_to_low'); ?>
                            </option>
                            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>
                                <?php echo __('name'); ?>
                            </option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>
                                <?php echo __('popular'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <?php echo __('filter'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Store Results -->
        <section class="store-results">
            <div class="container">
                <div class="results-header">
                    <div class="results-count">
                        <?php echo sprintf(__('showing_products'), $total_products); ?>
                    </div>
                    
                    <div class="results-sort">
                        <span class="sort-label"><?php echo __('sort_by'); ?>:</span>
                        <select onchange="window.location.href=this.value" class="form__select">
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" 
                                    <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                                <?php echo __('newest'); ?>
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>" 
                                    <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>
                                <?php echo __('price_low_to_high'); ?>
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>" 
                                    <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>
                                <?php echo __('price_high_to_low'); ?>
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" 
                                    <?php echo $sort === 'name' ? 'selected' : ''; ?>>
                                <?php echo __('name'); ?>
                            </option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'popular'])); ?>" 
                                    <?php echo $sort === 'popular' ? 'selected' : ''; ?>>
                                <?php echo __('popular'); ?>
                            </option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-state__icon">🛍️</div>
                        <h2 class="empty-state__title"><?php echo __('no_products_found'); ?></h2>
                        <p class="empty-state__text"><?php echo __('try_different_filters'); ?></p>
                        <a href="store.php" class="btn btn--primary"><?php echo __('view_all_products'); ?></a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-card__image">
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         loading="lazy">
                                    
                                    <?php if ($product['stock'] <= 0): ?>
                                        <div class="product-card__badge"><?php echo __('out_of_stock'); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-card__body">
                                    <h3 class="product-card__title">
                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="product-card__meta">
                                        <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <?php if ($product['seller_name']): ?>
                                            <span>•</span>
                                            <span><?php echo htmlspecialchars($product['seller_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-card__price">
                                        <?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?>
                                    </div>
                                    
                                    <div class="product-card__actions">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" 
                                           class="product-card__btn product-card__btn--primary">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <?php echo __('view'); ?>
                                        </a>
                                        
                                        <button onclick="addToWishlist(<?php echo $product['id']; ?>)" 
                                                class="product-card__btn product-card__btn--secondary">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                            </svg>
                                            <?php echo __('wishlist'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="pagination__link">
                                    <?php echo __('previous'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="pagination__link <?php echo $i === $page ? 'pagination__link--active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="pagination__link">
                                    <?php echo __('next'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Store Page JavaScript -->
    <script>
        function addToWishlist(productId) {
            fetch('wishlist_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Product added to wishlist!', 'success');
                } else {
                    showToast(data.message || 'Error adding to wishlist', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error adding to wishlist', 'danger');
            });
        }
        
        function showToast(message, type = 'info') {
            if (window.showToast) {
                window.showToast(type);
            } else {
                alert(message);
            }
        }
    </script>
</body>
</html> 