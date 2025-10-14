<?php
// Security and compatibility headers
require_once 'security_integration.php';

session_start();
require_once 'db.php';
require_once 'lang.php';
require_once 'includes/thumbnail_helper.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: store.php');
    exit;
}

// Get product details
$stmt = $pdo->prepare('
    SELECT p.*, c.name as category_name, s.store_name as seller_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE p.id = ? AND p.approved = 1
');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: store.php');
    exit;
}

// Get product images
try {
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order');
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll();
} catch (PDOException $e) {
    // If product_images table doesn't exist or has different structure, use main image
    $images = [];
}

// If no additional images, use images from products table (image, image2, image3, image4)
if (empty($images)) {
    $images = [];
    if (!empty($product['image'])) {
        $images[] = ['image_path' => $product['image'], 'is_main' => 1];
    }
    foreach (['image2', 'image3', 'image4'] as $field) {
        if (!empty($product[$field])) {
            $images[] = ['image_path' => $product[$field], 'is_main' => 0];
        }
    }
}

// Determine main image from images list
$main_image_entry = null;
foreach ($images as $img) {
    if (!empty($img['is_main'])) {
        $main_image_entry = $img;
        break;
    }
}
if (!$main_image_entry) {
    $main_image_entry = $images[0];
}
$main_image_full = 'uploads/' . $main_image_entry['image_path'];

// Get related products
$stmt = $pdo->prepare('
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.approved = 1
    ORDER BY RAND()
    LIMIT 4
');
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll();

$page_title = $product['name'] . ' - WeBuy';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
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
    
    <!-- Product page specific styles -->
    <style>
        .product-gallery__main {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
            max-height: 500px;
        }
        
        .product-gallery__main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-gallery__main:hover img {
            transform: scale(1.05);
        }
        
        .product-gallery__thumbnail {
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 0.375rem;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        
        .product-gallery__thumbnail:hover,
        .product-gallery__thumbnail--active {
            border-color: var(--bs-primary);
            transform: scale(1.05);
        }
        
        .product-gallery__thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid var(--bs-border-color);
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            padding: 0.5rem;
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
    
    <main id="main-content" role="main">
        <!-- Product Detail Section -->
        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <!-- Product Gallery -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="product-gallery__main">
                                <img id="main-image" src="<?php echo htmlspecialchars($main_image_full); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="card-img-top">
                            </div>
                        </div>
                        <div class="row g-2 mt-3">
                            <?php foreach ($images as $img): ?>
                                <?php 
                                $img_full = 'uploads/' . $img['image_path'];
                                $is_active = !empty($img['is_main']);
                                ?>
                                <div class="col-3">
                                    <div class="product-gallery__thumbnail <?php echo $is_active ? 'product-gallery__thumbnail--active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($img_full); ?>')">
                                        <img src="<?php echo htmlspecialchars($img_full); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="height: 80px; object-fit: cover;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm p-4">
                            <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                            <div class="h3 text-primary fw-bold mb-4"><?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?></div>
                            
                            <div class="mb-4">
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                                        </svg>
                                        <span><strong><?php echo __('category'); ?>:</strong> <?php echo htmlspecialchars($product['category_name']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center gap-2 text-muted">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <span><strong><?php echo __('seller'); ?>:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($product['stock'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center gap-2 text-muted">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                            </svg>
                                            <span><strong><?php echo __('stock'); ?>:</strong> <?php echo $product['stock']; ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['stock'] > 0): ?>
                                <form action="add_to_cart.php" method="get">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    
                                    <div class="quantity-selector mb-3">
                                        <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                        <input type="number" id="quantity-input" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input form-control">
                                        <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                                <path d="M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"></path>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                            <?php echo __('add_to_cart'); ?>
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                            </svg>
                                            <?php echo __('add_to_wishlist'); ?>
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="d-grid">
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <?php echo __('out_of_stock'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Seller Information Section -->
        <?php if ($product['seller_name']): ?>
            <section class="py-4">
                <div class="container">
                    <h2 class="h4 mb-4"><?php echo __('seller_information'); ?></h2>
                    <div class="card shadow-sm p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">
                                <?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <h3 class="h5 mb-1"><?php echo htmlspecialchars($product['seller_name']); ?></h3>
                                <div class="text-muted small">
                                    <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php if ($product['seller_id']): ?>
                                        <span class="ms-2"><?php echo __('seller_id'); ?>: <?php echo $product['seller_id']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-3">
                                    <a href="store.php?seller=<?php echo $product['seller_id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                        <?php echo __('view_all_products'); ?>
                                    </a>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="contactSeller(<?php echo $product['seller_id']; ?>)">
                                        <?php echo __('contact_seller'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Reviews & Ratings Section -->
        <section class="py-5">
            <div class="container">
                <h2 class="h4 mb-4"><?php echo __('reviews_and_ratings'); ?></h2>
                
                <!-- Review Summary -->
                <div class="card shadow-sm p-4 mb-4">
                    <?php
                    // Get review statistics
                    $stmt = $pdo->prepare('
                        SELECT 
                            COUNT(*) as total_reviews,
                            AVG(rating) as avg_rating,
                            COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                            COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                            COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                            COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                            COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
                        FROM reviews 
                        WHERE product_id = ? AND status = "approved"
                    ');
                    $stmt->execute([$product_id]);
                    $review_stats = $stmt->fetch();
                    
                    $total_reviews = $review_stats['total_reviews'] ?? 0;
                    $avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
                    ?>
                    
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="display-4 fw-bold text-primary"><?php echo $avg_rating; ?></div>
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="text-muted"><?php echo $total_reviews; ?> <?php echo __('reviews'); ?></div>
                        </div>
                        
                        <?php if ($total_reviews > 0): ?>
                            <div class="col-md-8">
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <?php 
                                    $star_count = $review_stats[$star . '_star'] ?? 0;
                                    $percentage = $total_reviews > 0 ? ($star_count / $total_reviews) * 100 : 0;
                                    ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2" style="min-width: 60px;"><?php echo $star; ?> ★</span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="ms-2 text-muted" style="min-width: 40px;"><?php echo $star_count; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Review Form -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="card shadow-sm p-4 mb-4">
                        <h3 class="h5 mb-3"><?php echo __('write_review'); ?></h3>
                        <form action="enhanced_submit_review.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div class="mb-3">
                                <label for="review_title" class="form-label"><?php echo __('review_title'); ?></label>
                                <input type="text" name="review_title" id="review_title" class="form-control" required maxlength="100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('rating'); ?></label>
                                <div class="rating-input">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" class="btn-check" required>
                                        <label for="star<?php echo $i; ?>" class="btn btn-outline-warning">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comment" class="form-label"><?php echo __('review_comment'); ?></label>
                                <textarea name="comment" id="comment" rows="4" class="form-control" required maxlength="1000"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="review_images" class="form-label"><?php echo __('review_images'); ?></label>
                                <input type="file" name="review_images[]" id="review_images" class="form-control" multiple accept="image/*">
                                <small class="form-text text-muted"><?php echo __('max_5_images_5mb_each'); ?></small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><?php echo __('submit_review'); ?></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm p-4 mb-4 text-center">
                        <p class="mb-3"><?php echo __('login_to_review'); ?></p>
                        <a href="login.php" class="btn btn-primary"><?php echo __('login'); ?></a>
                    </div>
                <?php endif; ?>
                
                <!-- Reviews List -->
                <div class="mt-4">
                    <?php
                    // Get approved reviews with images
                    $stmt = $pdo->prepare('
                        SELECT r.*, 
                               GROUP_CONCAT(ri.image_path ORDER BY ri.sort_order, ri.id) as images,
                               GROUP_CONCAT(ri.image_name ORDER BY ri.sort_order, ri.id) as image_names
                        FROM reviews r 
                        LEFT JOIN review_images ri ON r.id = ri.review_id 
                        WHERE r.product_id = ? AND r.status = "approved"
                        GROUP BY r.id 
                        ORDER BY r.created_at DESC
                    ');
                    $stmt->execute([$product_id]);
                    $reviews = $stmt->fetchAll();
                    ?>
                    
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="card shadow-sm p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($review['author_name'] ?? $review['name'] ?? __('anonymous_reviewer')); ?></div>
                                        <?php if (isset($review['verified_purchase']) && $review['verified_purchase']): ?>
                                            <span class="badge bg-success">✓ <?php echo __('verified_purchase'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                </div>
                                
                                <?php if ($review['review_title']): ?>
                                    <h4 class="h6 fw-bold"><?php echo htmlspecialchars($review['review_title']); ?></h4>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </div>
                                
                                <?php if ($review['images']): ?>
                                    <div class="d-flex gap-2 mb-3">
                                        <?php 
                                        $images = explode(',', $review['images']);
                                        $image_names = explode(',', $review['image_names']);
                                        ?>
                                        <?php foreach ($images as $index => $image): ?>
                                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                                 alt="<?php echo htmlspecialchars($image_names[$index] ?? 'Review image'); ?>"
                                                 class="img-thumbnail"
                                                 style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
                                                 onclick="openImageModal('<?php echo htmlspecialchars($image); ?>')">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="voteReview(<?php echo $review['id']; ?>, 'helpful')">
                                        👍 <?php echo __('helpful'); ?> (<span id="helpful-<?php echo $review['id']; ?>"><?php echo $review['helpful_votes'] ?? 0; ?></span>)
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="voteReview(<?php echo $review['id']; ?>, 'unhelpful')">
                                        👎 <?php echo __('not_helpful'); ?> (<span id="unhelpful-<?php echo $review['id']; ?>"><?php echo $review['unhelpful_votes'] ?? 0; ?></span>)
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card shadow-sm p-4 text-center">
                            <p class="text-muted mb-0"><?php echo __('no_reviews_yet'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section class="py-5 bg-light">
                <div class="container">
                    <h2 class="h4 mb-4"><?php echo __('related_products'); ?></h2>
                    
                    <div class="row g-4">
                        <?php foreach ($related_products as $related): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 shadow-sm">
                                    <a href="product.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                        <?php 
                                        $optimized_image = get_optimized_image('uploads/' . $related['image'], 'card');
                                        ?>
                                        <img src="<?php echo $optimized_image['src']; ?>" 
                                             srcset="<?php echo $optimized_image['srcset']; ?>" 
                                             sizes="<?php echo $optimized_image['sizes']; ?>"
                                             alt="<?php echo htmlspecialchars($related['name']); ?>"
                                             class="card-img-top"
                                             loading="lazy"
                                             style="height: 200px; object-fit: cover;"
                                             onload="this.classList.add('loaded');">
                                    </a>
                                    <div class="card-body">
                                        <h3 class="h6 card-title">
                                            <a href="product.php?id=<?php echo $related['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($related['name']); ?>
                                            </a>
                                        </h3>
                                        <div class="h5 text-primary mb-0">
                                            <?php echo number_format($related['price'], 2); ?> <?php echo __('currency'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Product Page JavaScript -->
    <script>
        function changeImage(src) {
            document.getElementById('main-image').src = src;
            
            // Update active thumbnail
            document.querySelectorAll('.product-gallery__thumbnail').forEach(thumb => {
                thumb.classList.remove('product-gallery__thumbnail--active');
            });
            event.target.closest('.product-gallery__thumbnail').classList.add('product-gallery__thumbnail--active');
        }
        
        function changeQuantity(delta) {
            const input = document.getElementById('quantity-input');
            const newValue = parseInt(input.value) + delta;
            const max = parseInt(input.max);
            const min = parseInt(input.min);
            
            if (newValue >= min && newValue <= max) {
                input.value = newValue;
            }
        }
        
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
                    // Show success message
                    showToast('<?php echo __('product_added_to_wishlist'); ?>', 'success');
                } else {
                    showToast(data.message || '<?php echo __('error_adding_to_wishlist'); ?>', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('<?php echo __('error_adding_to_wishlist'); ?>', 'danger');
            });
        }
        
        function contactSeller(sellerId) {
            // For now, show a simple message. In the future, this could open a contact form or chat
                            showToast('<?php echo __('contact_feature_coming_soon'); ?>', 'info');
            
            // Optionally redirect to seller's products page
            // window.location.href = 'store.php?seller=' + sellerId;
        }
        
        function showToast(message, type = 'info') {
            // Use the toast manager from the optimized JS
            if (window.showToast) {
                window.showToast(type);
            } else {
                alert(message);
            }
        }
    </script>
</body>
</html> 