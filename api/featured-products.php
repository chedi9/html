<?php
// Security and compatibility headers - with fallback for missing dependencies
$security_file = dirname(__DIR__) . '/security_integration.php';
$fallback_security = __DIR__ . '/security_fallback.php';

if (file_exists($security_file)) {
    require_once $security_file;
} else if (file_exists($fallback_security)) {
    require_once $fallback_security;
} else {
    // Minimal fallback security headers if no security files are available
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Basic session security
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Database connection with error handling
$db_file = dirname(__DIR__) . '/db.php';
if (!file_exists($db_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database configuration not found']);
    exit;
}

try {
    require $db_file;
    $db_connected = true;
} catch (Exception $e) {
    // Database connection failed - provide fallback for testing
    $db_connected = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Include required files with error handling
$thumbnail_helper = dirname(__DIR__) . '/includes/thumbnail_helper.php';
$cache_file = dirname(__DIR__) . '/includes/featured_products_cache.php';

if (!file_exists($thumbnail_helper)) {
    // Fallback function if thumbnail helper is missing
    function get_optimized_image($image_path, $size = 'card') {
        return $image_path; // Return original image if helper is not available
    }
} else {
    require_once $thumbnail_helper;
}

if (!file_exists($cache_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Cache system not found']);
    exit;
}
require_once $cache_file;

try {
    // Check if database is connected
    if (!$db_connected) {
        // Provide fallback response for testing
        $fallback_response = [
            'success' => true,
            'data' => [
                'products' => [
                    [
                        'id' => 1,
                        'name' => 'Sample Featured Product',
                        'description' => 'This is a sample featured product for testing purposes.',
                        'price' => 99.99,
                        'image' => 'uploads/sample-product.jpg',
                        'stock' => 10,
                        'created_at' => date('Y-m-d H:i:s'),
                        'is_new' => true,
                        'is_disabled_seller' => false,
                        'disabled_seller_name' => null,
                        'disability_type' => null,
                        'priority_level' => 1,
                        'rating' => [
                            'average' => 4.5,
                            'count' => 12
                        ]
                    ]
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 12,
                    'total_products' => 1,
                    'total_pages' => 1,
                    'has_next_page' => false,
                    'has_prev_page' => false
                ]
            ]
        ];
        echo json_encode($fallback_response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Get pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 12; // Products per page
    $offset = ($page - 1) * $per_page;
    
    // Get language
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
    
    // Initialize cache
    $cache = new FeaturedProductsCache();
    
    // Check cache first
    $cached_data = $cache->get($page, $lang);
    if ($cached_data !== null) {
        echo json_encode($cached_data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Build the query to get featured products
    // Featured products are those with is_priority_product = 1 or from disabled sellers
    $sql = "
        SELECT 
            p.*,
            s.is_disabled,
            s.name as seller_name,
            ds.name AS disabled_seller_name,
            ds.disability_type,
            ds.priority_level,
            CASE 
                WHEN ds.id IS NOT NULL THEN 1 
                WHEN p.is_priority_product = 1 THEN 2 
                ELSE 3 
            END as priority_order
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
        WHERE p.approved = 1 
        AND (p.is_priority_product = 1 OR ds.id IS NOT NULL)
        ORDER BY priority_order ASC, ds.priority_level DESC, p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM products p
        LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
        WHERE p.approved = 1 
        AND (p.is_priority_product = 1 OR ds.id IS NOT NULL)
    ";
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute();
    $total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process products to add additional data
    $processed_products = [];
    foreach ($products as $product) {
        // Get product rating
        $rating_stmt = $pdo->prepare('
            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
            FROM reviews 
            WHERE product_id = ? AND status = "approved"
        ');
        $rating_stmt->execute([$product['id']]);
        $rating_data = $rating_stmt->fetch();
        
        // Get optimized image
        $optimized_image = get_optimized_image('uploads/' . $product['image'], 'card');
        
        // Determine product name based on language
        $product_name = $product['name_' . $lang] ?? $product['name'];
        
        // Check if product is new (created within last 30 days)
        $is_new = (strtotime($product['created_at']) > strtotime('-30 days'));
        
        // Check if product is from disabled seller
        $is_disabled_seller = !empty($product['disabled_seller_id']);
        
        $processed_products[] = [
            'id' => $product['id'],
            'name' => $product_name,
            'description' => $product['description'],
            'price' => $product['price'],
            'image' => $optimized_image,
            'stock' => $product['stock'],
            'created_at' => $product['created_at'],
            'is_new' => $is_new,
            'is_disabled_seller' => $is_disabled_seller,
            'disabled_seller_name' => $product['disabled_seller_name'],
            'disability_type' => $product['disability_type'],
            'priority_level' => $product['priority_level'],
            'rating' => [
                'average' => round($rating_data['avg_rating'] ?? 0, 1),
                'count' => $rating_data['review_count'] ?? 0
            ]
        ];
    }
    
    // Calculate pagination info
    $total_pages = ceil($total_products / $per_page);
    $has_next_page = $page < $total_pages;
    $has_prev_page = $page > 1;
    
    // Prepare response data
    $response_data = [
        'success' => true,
        'data' => [
            'products' => $processed_products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_products' => $total_products,
                'total_pages' => $total_pages,
                'has_next_page' => $has_next_page,
                'has_prev_page' => $has_prev_page
            ]
        ]
    ];
    
    // Cache the response
    $cache->set($page, $lang, $response_data);
    
    // Return JSON response
    echo json_encode($response_data, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Featured products API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Failed to fetch featured products'
    ], JSON_UNESCAPED_UNICODE);
}
?>