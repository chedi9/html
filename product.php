<?php
require_once 'db.php';
require_once 'lang.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$product_id = intval($_GET['id']);

// Get product details with seller information
$stmt = $pdo->prepare('
    SELECT p.*, c.name as category_name, c.name_ar as category_name_ar, 
           ds.name as disabled_seller_name, ds.story as disabled_seller_story, ds.seller_photo as disabled_seller_photo,
           ds.disability_type, ds.priority_level
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
    WHERE p.id = ? AND p.approved = 1
');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit();
}

// Get all product images
$stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC');
$stmt->execute([$product_id]);
$product_images = $stmt->fetchAll();

// If no additional images, use main product image
if (empty($product_images)) {
    $product_images = [['image_path' => $product['image'], 'is_main' => 1]];
}

// Get related products
$stmt = $pdo->prepare('
    SELECT p.*, ds.name as disabled_seller_name, ds.priority_level
    FROM products p 
    LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
    WHERE p.category_id = ? AND p.id != ? AND p.approved = 1
    ORDER BY ds.priority_level DESC, p.created_at DESC 
    LIMIT 8
');
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll();

// Get reviews
$stmt = $pdo->prepare('
    SELECT r.*, u.name as user_name, u.id as user_id
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.status = "approved"
    ORDER BY r.created_at DESC
');
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($total_rating / count($reviews), 1);
}

$page_title = $product['name'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - WeBuy</title>
    <link rel="stylesheet" href="beta333.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Product Gallery Styles */
        .product-gallery {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .main-image-container {
            position: relative;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .main-image {
            width: 100%;
            height: 600px;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        
        .main-image:hover {
            transform: scale(1.02);
        }
        
        .image-zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .zoomed-image {
            max-width: 95%;
            max-height: 95%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .close-zoom {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-zoom:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        
        .main-image:hover {
            transform: scale(1.02);
        }
        
        .image-zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .zoomed-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .close-zoom {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .thumbnail-gallery {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .thumbnail.active {
            border-color: #00BFAE;
            transform: scale(1.1);
            box-shadow: 0 4px 20px rgba(0,191,174,0.3);
        }
        
        .thumbnail:hover {
            transform: scale(1.15);
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
        }
        
        .image-nav {
            display: flex;
            justify-content: space-between;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            padding: 0 20px;
            z-index: 10;
        }
        
        .nav-btn {
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .nav-btn:hover {
            background: white;
            transform: scale(1.15);
            box-shadow: 0 6px 25px rgba(0,0,0,0.3);
        }
        
        .nav-btn:active {
            transform: scale(0.95);
        }
        
        .product-info {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .product-title {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-size: 2em;
            color: #00BFAE;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .product-description {
            font-size: 1.1em;
            line-height: 1.6;
            color: #666;
            margin-bottom: 25px;
        }
        
        .seller-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .seller-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        
        .seller-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .seller-story {
            font-size: 0.9em;
            color: #666;
            line-height: 1.5;
        }
        
        .priority-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00BFAE, #00897B);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,191,174,0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #F44336, #D32F2F);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(244,67,54,0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #00BFAE;
            border: 2px solid #00BFAE;
        }
        
        .btn-outline:hover {
            background: #00BFAE;
            color: white;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #00BFAE;
            background: white;
            color: #00BFAE;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .quantity-btn:hover {
            background: #00BFAE;
            color: white;
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .product-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .meta-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .meta-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }
        
        .reviews-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .rating-stars {
            color: #FFD700;
            font-size: 1.5em;
        }
        
        .rating-text {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .related-products {
            margin-top: 40px;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .product-gallery {
                grid-template-columns: 1fr;
            }
            
            .thumbnail-gallery {
                flex-direction: row;
                max-height: none;
                overflow-x: auto;
            }
            
            .thumbnail {
                width: 60px;
                height: 60px;
                flex-shrink: 0;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-title {
                font-size: 2em;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container" style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
        <!-- Image Zoom Overlay -->
        <div class="image-zoom-overlay" id="zoomOverlay">
            <div class="close-zoom" onclick="closeZoom()">×</div>
            <img class="zoomed-image" id="zoomedImage" src="" alt="">
        </div>
        
        <div class="product-gallery">
            <!-- Main Image Section -->
            <div class="main-image-container">
                <img class="main-image" id="mainImage" src="uploads/<?php echo htmlspecialchars($product_images[0]['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" onclick="openZoomWithNavigation(this.src, currentImageIndex)">
                
                <!-- Navigation Buttons -->
                <div class="image-nav">
                    <button class="nav-btn" onclick="previousImage()" id="prevBtn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="nav-btn" onclick="nextImage()" id="nextBtn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Thumbnail Gallery -->
            <div class="thumbnail-gallery">
                <?php foreach ($product_images as $index => $image): ?>
                    <img class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                         src="uploads/<?php echo htmlspecialchars($image['image_path']); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?> - Image <?php echo $index + 1; ?>"
                         onclick="changeMainImage(this.src, <?php echo $index; ?>)"
                         data-index="<?php echo $index; ?>">
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Product Information -->
        <div class="product-info">
            <?php if ($product['is_priority_product'] || $product['disabled_seller_id']): ?>
                <div class="priority-badge">
                    <i class="fas fa-star"></i> منتج ذو أولوية
                </div>
            <?php endif; ?>
            
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-price"><?php echo number_format($product['price'], 3); ?> د.ت</div>
            
            <div class="product-meta">
                <div class="meta-item">
                    <div class="meta-label">الفئة</div>
                    <div class="meta-value"><?php echo htmlspecialchars($product['category_name_ar'] ?? $product['category_name']); ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">المخزون</div>
                    <div class="meta-value"><?php echo $product['stock']; ?> قطعة</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">التقييم</div>
                    <div class="meta-value">
                        <span class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </span>
                        (<?php echo count($reviews); ?>)
                    </div>
                </div>
            </div>
            
            <div class="product-description">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
            
            <?php if ($product['disabled_seller_id'] && $product['disabled_seller_name']): ?>
                <div class="seller-info">
                    <?php if ($product['disabled_seller_photo']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($product['disabled_seller_photo']); ?>" 
                             alt="<?php echo htmlspecialchars($product['disabled_seller_name']); ?>" 
                             class="seller-photo">
                    <?php endif; ?>
                    <div class="seller-name"><?php echo htmlspecialchars($product['disabled_seller_name']); ?></div>
                    <div class="seller-story"><?php echo htmlspecialchars($product['disabled_seller_story']); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Quantity Selector -->
            <div class="quantity-selector">
                <label for="quantity">الكمية:</label>
                <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="addToCart(<?php echo $product_id; ?>)">
                    <i class="fas fa-shopping-cart"></i>
                    أضف إلى السلة
                </button>
                <button class="btn btn-secondary" onclick="addToWishlist(<?php echo $product_id; ?>)">
                    <i class="fas fa-heart"></i>
                    أضف إلى المفضلة
                </button>
                <a href="#reviews" class="btn btn-outline">
                    <i class="fas fa-star"></i>
                    التقييمات (<?php echo count($reviews); ?>)
                </a>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="reviews-section" id="reviews">
            <div class="reviews-header">
                <div class="rating-summary">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-text"><?php echo $avg_rating; ?>/5 (<?php echo count($reviews); ?> تقييم)</div>
                </div>
                <a href="submit_review.php?product_id=<?php echo $product_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i>
                    اكتب تقييم
                </a>
            </div>
            
            <?php if (!empty($reviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item" style="border-bottom: 1px solid #eee; padding: 20px 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div style="font-weight: bold; color: #333;">
                                    <?php echo htmlspecialchars($review['user_name'] ?? 'مستخدم'); ?>
                                </div>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div style="color: #666; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                            <div style="font-size: 0.9em; color: #999; margin-top: 10px;">
                                <?php echo date('Y-m-d', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: #666; padding: 40px;">
                    <i class="fas fa-star" style="font-size: 3em; color: #ddd; margin-bottom: 20px;"></i>
                    <p>لا توجد تقييمات بعد. كن أول من يقيم هذا المنتج!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h2 style="margin-bottom: 20px; color: #333;">منتجات مشابهة</h2>
                <div class="related-grid">
                    <?php foreach ($related_products as $rel): ?>
                        <div class="product-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                            <a href="product.php?id=<?php echo $rel['id']; ?>">
                                <div class="product-img-wrap">
                                    <?php 
                                    $image_path = "uploads/" . htmlspecialchars($rel['image']);
                                    $thumb_path = "uploads/thumbnails/" . pathinfo($rel['image'], PATHINFO_FILENAME) . "_thumb.jpg";
                                    $final_image = file_exists($thumb_path) ? $thumb_path : $image_path;
                                    ?>
                                    <img src="<?php echo $final_image; ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>" loading="lazy" width="300" height="300">
                                </div>
                                <h3><?php echo htmlspecialchars($rel['name']); ?></h3>
                                <div style="color: #00BFAE; font-weight: bold; font-size: 1.2em;">
                                    <?php echo number_format($rel['price'], 3); ?> د.ت
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        let currentImageIndex = 0;
        const images = <?php echo json_encode(array_column($product_images, 'image_path')); ?>;
        const totalImages = images.length;
        
        function changeMainImage(src, index) {
            document.getElementById('mainImage').src = 'uploads/' + src;
            currentImageIndex = index;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
            
            // Update navigation buttons
            updateNavButtons();
        }
        
        function nextImage() {
            currentImageIndex = (currentImageIndex + 1) % totalImages;
            changeMainImage(images[currentImageIndex], currentImageIndex);
        }
        
        function previousImage() {
            currentImageIndex = (currentImageIndex - 1 + totalImages) % totalImages;
            changeMainImage(images[currentImageIndex], currentImageIndex);
        }
        
        function updateNavButtons() {
            document.getElementById('prevBtn').style.display = totalImages > 1 ? 'flex' : 'none';
            document.getElementById('nextBtn').style.display = totalImages > 1 ? 'flex' : 'none';
        }
        
        function openZoom(src) {
            document.getElementById('zoomedImage').src = src;
            document.getElementById('zoomOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Add smooth fade-in effect
            const overlay = document.getElementById('zoomOverlay');
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.opacity = '1';
                overlay.style.transition = 'opacity 0.3s ease';
            }, 10);
        }
        
        function closeZoom() {
            const overlay = document.getElementById('zoomOverlay');
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        // Enhanced zoom with image navigation
        function openZoomWithNavigation(src, index) {
            currentImageIndex = index;
            openZoom(src);
            updateZoomedImage();
        }
        
        function updateZoomedImage() {
            const zoomedImage = document.getElementById('zoomedImage');
            zoomedImage.src = 'uploads/' + images[currentImageIndex];
        }
        
        function changeQuantity(delta) {
            const input = document.getElementById('quantity');
            const newValue = Math.max(1, Math.min(<?php echo $product['stock']; ?>, parseInt(input.value) + delta));
            input.value = newValue;
        }
        
        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            window.location.href = `add_to_cart.php?product_id=${productId}&quantity=${quantity}`;
        }
        
        function addToWishlist(productId) {
            fetch('wishlist_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إضافة المنتج إلى المفضلة بنجاح!');
                } else {
                    alert(data.message || 'حدث خطأ أثناء إضافة المنتج إلى المفضلة');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إضافة المنتج إلى المفضلة');
            });
        }
        
        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            const overlay = document.getElementById('zoomOverlay');
            const isZoomed = overlay.style.display === 'flex';
            
            if (isZoomed) {
                if (e.key === 'Escape') {
                    closeZoom();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    previousImage();
                    updateZoomedImage();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextImage();
                    updateZoomedImage();
                }
            } else {
                // Navigation when not zoomed
                if (e.key === 'ArrowLeft' && totalImages > 1) {
                    e.preventDefault();
                    previousImage();
                } else if (e.key === 'ArrowRight' && totalImages > 1) {
                    e.preventDefault();
                    nextImage();
                }
            }
        });
        
        // Touch/swipe support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next image
                    if (document.getElementById('zoomOverlay').style.display === 'flex') {
                        nextImage();
                        updateZoomedImage();
                    } else {
                        nextImage();
                    }
                } else {
                    // Swipe right - previous image
                    if (document.getElementById('zoomOverlay').style.display === 'flex') {
                        previousImage();
                        updateZoomedImage();
                    } else {
                        previousImage();
                    }
                }
            }
        }
        
        // Initialize navigation buttons
        updateNavButtons();
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html> 