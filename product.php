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
    <script src="js/theme-controller.js" defer></script>
    <script src="main.js?v=1.4" defer></script>
    
    <!-- Product page specific styles -->
    <style>
        /* Product Gallery Styles */
        .product-gallery {
            position: relative;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            max-width: 500px; /* Limit maximum width */
            margin: 0 auto; /* Center the gallery */
        }
        
        .product-gallery__main {
            aspect-ratio: 4/3; /* Change from 1:1 to 4:3 for better proportions */
            position: relative;
            overflow: hidden;
            max-height: 400px; /* Limit maximum height */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-gallery__main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-medium);
        }
        
        .product-gallery__main:hover img {
            transform: scale(1.05);
        }
        
        .product-gallery__thumbnails {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--space-2);
            margin-top: var(--space-4);
            justify-items: center; /* Center thumbnails */
        }
        
        .product-gallery__thumbnail {
            aspect-ratio: 1;
            border-radius: var(--border-radius-md);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all var(--transition-fast);
            max-width: 80px; /* Limit thumbnail size */
            max-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-gallery__thumbnail:hover,
        .product-gallery__thumbnail--active {
            border-color: var(--color-primary-500);
            transform: scale(1.05);
        }
        
        .product-gallery__thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            background: var(--color-white);
            border-radius: var(--border-radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-lg);
        }
        
        .product-title {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-gray-900);
            margin-bottom: var(--space-2);
        }
        
        .product-price {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-primary-600);
            margin-bottom: var(--space-4);
        }
        
        .product-description {
            color: var(--color-gray-700);
            line-height: var(--line-height-relaxed);
            margin-bottom: var(--space-6);
        }
        
        .product-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }
        
        .product-meta__item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--color-gray-600);
        }
        
        .product-meta__icon {
            width: 20px;
            height: 20px;
            color: var(--color-primary-500);
        }
        
        .product-card {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-fast);
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-card__image {
            aspect-ratio: 1;
            overflow: hidden;
            max-height: 200px; /* Limit height for related products */
            display: flex;
            align-items: center;
            justify-content: center;
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
        
        .product-card__body {
            padding: var(--space-4);
        }
        
        .product-card__title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--color-gray-900);
            margin-bottom: var(--space-2);
            line-height: var(--line-height-tight);
        }
        
        .product-card__price {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            color: var(--color-primary-600);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .product-gallery {
                max-width: 100%;
            }
            
            .product-gallery__main {
                aspect-ratio: 3/2; /* Slightly taller on mobile */
                max-height: 300px;
            }
            
            .product-gallery__thumbnails {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .product-gallery__thumbnail {
                max-width: 60px;
                max-height: 60px;
            }
            
            .product-card__image {
                max-height: 150px; /* Smaller on mobile */
            }
        }
        
        @media (max-width: 480px) {
            .product-gallery__main {
                aspect-ratio: 1; /* Square on very small screens */
                max-height: 250px;
            }
            
            .product-gallery__thumbnails {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .product-card__image {
                max-height: 120px; /* Even smaller on very small screens */
            }
        }
    </style>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link"><?php echo __('skip_to_main_content'); ?></a>
    
    <?php include 'header.php'; ?>
    
    <main id="main-content" role="main">
        <!-- Product Hero Section -->
        <section class="product-hero">
            <div class="container">
                <div class="grid grid--2">
                    <!-- Product Gallery -->
                    <div class="product-gallery">
                        <div class="product-gallery__main">
                            <img id="main-image" src="<?php echo htmlspecialchars($main_image_full); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-gallery__thumbnails">
                            <?php foreach ($images as $img): ?>
                                <?php 
                                $img_full = 'uploads/' . $img['image_path'];
                                $is_active = !empty($img['is_main']);
                                ?>
                                <div class="product-gallery__thumbnail <?php echo $is_active ? 'product-gallery__thumbnail--active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($img_full); ?>')">
                                    <img src="<?php echo htmlspecialchars($img_full); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="product-info">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="product-price"><?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?></div>
                        
                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                        
                        <div class="product-meta">
                            <div class="product-meta__item">
                                <svg class="product-meta__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                                </svg>
                                <span><?php echo __('category'); ?>: <?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            <div class="product-meta__item">
                                <svg class="product-meta__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span><?php echo __('seller'); ?>: <?php echo htmlspecialchars($product['seller_name']); ?></span>
                            </div>
                            <?php if (!empty($product['stock'])): ?>
                                <div class="product-meta__item">
                                    <svg class="product-meta__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                    </svg>
                                    <span><?php echo __('stock'); ?>: <?php echo $product['stock']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($product['stock'] > 0): ?>
                            <form action="add_to_cart.php" method="get" class="product-form">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                    <input type="number" id="quantity-input" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                                </div>
                                
                                <div class="product-actions">
                                    <button type="submit" class="btn btn--primary btn--lg">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 12l2 2 4-4"></path>
                                            <circle cx="9" cy="12" r="7"></circle>
                                            <path d="M21 12c0 6.627-5.373 12-12 12S-3 18.627-3 12 2.373 0 9 0s12 5.373 12 12z"></path>
                                        </svg>
                                        <?php echo __('add_to_cart'); ?>
                                    </button>
                                    
                                    <button type="button" class="btn btn--secondary btn--lg" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                        <?php echo __('add_to_wishlist'); ?>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="product-actions">
                                <button class="btn btn--disabled btn--lg" disabled>
                                    <?php echo __('out_of_stock'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Seller Information Section -->
        <?php if ($product['seller_name']): ?>
            <section class="seller-section">
                <div class="container">
                    <h2 class="section-title"><?php echo __('seller_information'); ?></h2>
                    <div class="seller-card">
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <div class="seller-avatar__placeholder">
                                    <?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?>
                                </div>
                            </div>
                            <div class="seller-details">
                                <h3 class="seller-name"><?php echo htmlspecialchars($product['seller_name']); ?></h3>
                                <div class="seller-meta">
                                    <span class="seller-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php if ($product['seller_id']): ?>
                                        <span class="seller-id"><?php echo __('seller_id'); ?>: <?php echo $product['seller_id']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="seller-actions">
                                    <a href="store.php?seller=<?php echo $product['seller_id']; ?>" class="btn btn--outline btn--sm">
                                        <?php echo __('view_all_products'); ?>
                                    </a>
                                    <button class="btn btn--outline btn--sm" onclick="contactSeller(<?php echo $product['seller_id']; ?>)">
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
        <section class="reviews-section">
            <div class="container">
                <h2 class="section-title"><?php echo __('reviews_and_ratings'); ?></h2>
                
                <!-- Review Summary -->
                <div class="review-summary">
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
                    
                    <div class="review-summary__overview">
                        <div class="review-summary__rating">
                            <div class="rating-display">
                                <span class="rating-number"><?php echo $avg_rating; ?></span>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="total-reviews"><?php echo $total_reviews; ?> <?php echo __('reviews'); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($total_reviews > 0): ?>
                            <div class="review-summary__breakdown">
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <?php 
                                    $star_count = $review_stats[$star . '_star'] ?? 0;
                                    $percentage = $total_reviews > 0 ? ($star_count / $total_reviews) * 100 : 0;
                                    ?>
                                    <div class="rating-bar">
                                        <span class="star-label"><?php echo $star; ?> <?php echo __('stars'); ?></span>
                                        <div class="rating-progress">
                                            <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <span class="star-count"><?php echo $star_count; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Review Form -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="review-form-container">
                        <h3><?php echo __('write_review'); ?></h3>
                        <form action="enhanced_submit_review.php" method="POST" enctype="multipart/form-data" class="review-form">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div class="form-group">
                                <label for="review_title"><?php echo __('review_title'); ?></label>
                                <input type="text" name="review_title" id="review_title" required maxlength="100">
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo __('rating'); ?></label>
                                <div class="rating-input">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>" class="star-label">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment"><?php echo __('review_comment'); ?></label>
                                <textarea name="comment" id="comment" rows="4" required maxlength="1000"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="review_images"><?php echo __('review_images'); ?></label>
                                <input type="file" name="review_images[]" id="review_images" multiple accept="image/*">
                                <small><?php echo __('max_5_images_5mb_each'); ?></small>
                            </div>
                            
                            <button type="submit" class="btn btn--primary"><?php echo __('submit_review'); ?></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="review-login-prompt">
                        <p><?php echo __('login_to_review'); ?></p>
                        <a href="login.php" class="btn btn--primary"><?php echo __('login'); ?></a>
                    </div>
                <?php endif; ?>
                
                <!-- Reviews List -->
                <div class="reviews-list">
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
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-author">
                                        <span class="author-name"><?php echo htmlspecialchars($review['author_name'] ?? $review['name'] ?? __('anonymous_reviewer')); ?></span>
                                        <?php if (isset($review['verified_purchase']) && $review['verified_purchase']): ?>
                                            <span class="verified-badge">✓ <?php echo __('verified_purchase'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-date">
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($review['review_title']): ?>
                                    <h4 class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></h4>
                                <?php endif; ?>
                                
                                <div class="review-content">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </div>
                                
                                <?php if ($review['images']): ?>
                                    <div class="review-images">
                                        <?php 
                                        $images = explode(',', $review['images']);
                                        $image_names = explode(',', $review['image_names']);
                                        ?>
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="review-image">
                                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                                     alt="<?php echo htmlspecialchars($image_names[$index] ?? 'Review image'); ?>"
                                                     onclick="openImageModal('<?php echo htmlspecialchars($image); ?>')">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="review-actions">
                                    <button class="btn btn--sm btn--outline" onclick="voteReview(<?php echo $review['id']; ?>, 'helpful')">
                                        👍 <?php echo __('helpful'); ?> (<span id="helpful-<?php echo $review['id']; ?>"><?php echo $review['helpful_votes'] ?? 0; ?></span>)
                                    </button>
                                    <button class="btn btn--sm btn--outline" onclick="voteReview(<?php echo $review['id']; ?>, 'unhelpful')">
                                        👎 <?php echo __('not_helpful'); ?> (<span id="unhelpful-<?php echo $review['id']; ?>"><?php echo $review['unhelpful_votes'] ?? 0; ?></span>)
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <p><?php echo __('no_reviews_yet'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section class="related-products">
                <div class="container">
                    <h2 class="section-title"><?php echo __('related_products'); ?></h2>
                    
                    <div class="grid grid--4">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card">
                                <a href="product.php?id=<?php echo $related['id']; ?>" class="product-card__image">
                                    <?php 
                                    $optimized_image = get_optimized_image('uploads/' . $related['image'], 'card');
                                    ?>
                                    <img src="<?php echo $optimized_image['src']; ?>" 
                                         srcset="<?php echo $optimized_image['srcset']; ?>" 
                                         sizes="<?php echo $optimized_image['sizes']; ?>"
                                         alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         loading="lazy"
                                         width="280" 
                                         height="280"
                                         onload="this.classList.add('loaded');">
                                </a>
                                <div class="product-card__body">
                                    <h3 class="product-card__title">
                                        <a href="product.php?id=<?php echo $related['id']; ?>">
                                            <?php echo htmlspecialchars($related['name']); ?>
                                        </a>
                                    </h3>
                                    <div class="product-card__price">
                                        <?php echo number_format($related['price'], 2); ?> <?php echo __('currency'); ?>
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