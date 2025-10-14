<?php
// Security and compatibility headers
require_once 'security_integration.php';

session_start();
require_once 'db.php';
require_once 'lang.php';
require_once 'includes/thumbnail_helper.php';

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
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Stylesheets removed to reset site styling -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Google Fonts removed -->
    
    <!-- JavaScript -->
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.4" defer></script>
    
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
    <main id="main-content" role="main">
        <!-- Store Hero Section -->
        <section class="bg-primary text-white py-5">
            <div class="container">
                <div class="text-center">
                    <h1 class="display-4 fw-bold mb-3"><?php echo __('store'); ?></h1>
                    <p class="lead"><?php echo __('discover_amazing_products'); ?></p>
                </div>
            </div>
        </section>
        
        <!-- Store Filters -->
        <section class="py-4 bg-light border-bottom">
            <div class="container">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search" class="form-label"><?php echo __('search'); ?></label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="form-control" placeholder="<?php echo __('search_products'); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="category" class="form-label"><?php echo __('category'); ?></label>
                        <select id="category" name="category" class="form-select">
                            <option value=""><?php echo __('all_categories'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="sort" class="form-label"><?php echo __('sort_by'); ?></label>
                        <select id="sort" name="sort" class="form-select">
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
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
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
        <section class="py-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-muted">
                        <?php echo sprintf(__('showing_products'), $total_products); ?>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted"><?php echo __('sort_by'); ?>:</span>
                        <select onchange="window.location.href=this.value" class="form-select">
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
                    <div class="text-center py-5">
                        <div class="display-1 mb-4">🛍️</div>
                        <h2 class="h3 mb-3"><?php echo __('no_products_found'); ?></h2>
                        <p class="text-muted mb-4"><?php echo __('try_different_filters'); ?></p>
                        <a href="store.php" class="btn btn-primary"><?php echo __('view_all_products'); ?></a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="card h-100 shadow-sm">
                                    <div class="position-relative">
                                        <div class="card-img-top">
                                            <div class="skeleton w-100 h-100"></div>
                                            <?php 
                                            $optimized_image = get_optimized_image('uploads/' . $product['image'], 'card');
                                            ?>
                                            <img src="<?php echo $optimized_image['src']; ?>" 
                                                 srcset="<?php echo $optimized_image['srcset']; ?>" 
                                                 sizes="<?php echo $optimized_image['sizes']; ?>"
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 loading="lazy" 
                                                 class="w-100 h-100 object-fit-cover"
                                                 onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';">
                                        </div>
                                        
                                        <?php if ($product['stock'] <= 0): ?>
                                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                                <?php echo __('out_of_stock'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h5>
                                        
                                        <div class="text-muted small mb-2">
                                            <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                                            <?php if ($product['seller_name']): ?>
                                                <span>•</span>
                                                <span><?php echo htmlspecialchars($product['seller_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="h5 text-primary mb-3">
                                            <?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?>
                                        </div>
                                        
                                        <div class="mt-auto">
                                            <div class="d-grid gap-2">
                                                <a href="product.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                    <?php echo __('view'); ?>
                                                </a>
                                                
                                                <button onclick="addToWishlist(<?php echo $product['id']; ?>)" 
                                                        class="btn btn-outline-secondary btn-sm">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                                    </svg>
                                                    <?php echo __('wishlist'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Products pagination" class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                           class="page-link">
                                            <?php echo __('previous'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                           class="page-link">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                           class="page-link">
                                            <?php echo __('next'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
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
    </script>
</body>
</html>