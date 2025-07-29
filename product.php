<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

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

// If no additional images, use main product image
if (empty($images)) {
    $images = [['image_path' => $product['image'], 'is_main' => 1]];
}

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
    
    <!-- Product page specific styles -->
    <style>
        .product-hero {
            background: linear-gradient(135deg, var(--color-primary-50), var(--color-accent-50));
            padding: var(--space-8) 0;
        }
        
        .product-gallery {
            position: relative;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        
        .product-gallery__main {
            aspect-ratio: 1;
            position: relative;
            overflow: hidden;
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
        }
        
        .product-gallery__thumbnail {
            aspect-ratio: 1;
            border-radius: var(--border-radius-md);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all var(--transition-fast);
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
        
        .product-actions {
            display: flex;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            border: 2px solid var(--color-gray-300);
            border-radius: var(--border-radius-md);
            padding: var(--space-2);
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--color-gray-100);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .quantity-btn:hover {
            background: var(--color-primary-100);
            color: var(--color-primary-600);
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: none;
            font-weight: var(--font-weight-semibold);
        }
        
        .related-products {
            padding: var(--space-8) 0;
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
        
        @media (max-width: 768px) {
            .product-actions {
                flex-direction: column;
            }
            
            .product-gallery__thumbnails {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body class="page-transition">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php include 'header.php'; ?>
    
    <main id="main-content" role="main">
        <!-- Product Hero Section -->
        <section class="product-hero">
            <div class="container">
                <div class="grid grid--cols-2 grid--gap-lg">
                    <!-- Product Gallery -->
                    <div class="product-gallery">
                        <div class="product-gallery__main">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 id="main-image">
                        </div>
                        
                        <?php if (!empty($images) && count($images) > 1): ?>
                            <div class="product-gallery__thumbnails">
                                <div class="product-gallery__thumbnail product-gallery__thumbnail--active" 
                                     onclick="changeImage('uploads/<?php echo htmlspecialchars($product['image']); ?>')">
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <?php foreach ($images as $image): ?>
                                    <?php 
                                    // Handle different possible column names
                                    $image_path = isset($image['image_path']) ? $image['image_path'] : 
                                                (isset($image['image']) ? $image['image'] : $product['image']);
                                    ?>
                                    <div class="product-gallery__thumbnail" 
                                         onclick="changeImage('uploads/<?php echo htmlspecialchars($image_path); ?>')">
                                        <img src="uploads/<?php echo htmlspecialchars($image_path); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="product-info">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <div class="product-price">
                            <?php echo number_format($product['price'], 2); ?> <?php echo __('currency'); ?>
                        </div>
                        
                        <div class="product-meta">
                            <div class="product-meta__item">
                                <svg class="product-meta__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            
                            <?php if ($product['seller_name']): ?>
                                <div class="product-meta__item">
                                    <svg class="product-meta__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <span><?php echo htmlspecialchars($product['seller_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-meta__item">
                                <svg class="product-meta__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12l2 2 4-4"/>
                                    <path d="M21 12c-1 0-2.4-.4-3.5-1.5S16 9 16 8s.4-2.5 1.5-3.5S20 3 21 3s2.5.4 3.5 1.5S26 7 26 8s-.4 2.5-1.5 3.5S22 12 21 12z"/>
                                </svg>
                                <span><?php echo $product['stock'] > 0 ? __('in_stock') : __('out_of_stock'); ?></span>
                            </div>
                        </div>
                        
                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                        
                        <?php if ($product['stock'] > 0): ?>
                            <form action="add_to_cart.php" method="POST" class="product-actions">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                           class="quantity-input" id="quantity-input">
                                    <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                                </div>
                                
                                <button type="submit" class="btn btn--primary btn--lg">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="9" cy="21" r="1"/>
                                        <circle cx="20" cy="21" r="1"/>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                    </svg>
                                    <?php echo __('add_to_cart'); ?>
                                </button>
                                
                                <button type="button" class="btn btn--secondary btn--lg" onclick="addToWishlist(<?php echo $product_id; ?>)">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                    </svg>
                                    <?php echo __('add_to_wishlist'); ?>
                                </button>
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
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section class="related-products">
                <div class="container">
                    <h2 class="section-title"><?php echo __('related_products'); ?></h2>
                    
                    <div class="grid grid--cols-4 grid--gap-lg">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card">
                                <a href="product.php?id=<?php echo $related['id']; ?>" class="product-card__image">
                                    <img src="uploads/<?php echo htmlspecialchars($related['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>">
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