<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require 'db.php';
require 'lang.php';
session_start();
if (!isset($_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'] = [];
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id) {
    // Remove if already exists
    $_SESSION['viewed_products'] = array_diff($_SESSION['viewed_products'], [$id]);
    // Add to end
    $_SESSION['viewed_products'][] = $id;
    // Limit to last 6
    $_SESSION['viewed_products'] = array_slice($_SESSION['viewed_products'], -6);
}
$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    die('المنتج غير موجود.');
}

// Related products (same category, exclude current)
$related = [];
if ($product['category_id']) {
    $rel_stmt = $pdo->prepare('SELECT id, name, image, price FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 3');
    $rel_stmt->execute([$product['category_id'], $product['id']]);
    $related = $rel_stmt->fetchAll();
}
// Fetch reviews for this product
$reviews = [];
$avg_rating = 0;
$review_count = 0;
$stmt = $pdo->prepare('SELECT * FROM reviews WHERE product_id = ? AND (hidden IS NULL OR hidden = 0) ORDER BY created_at DESC');
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();
if ($reviews) {
    $review_count = count($reviews);
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / $review_count, 1);
}
$rating_counts = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($reviews as $rev) {
    $r = (int)$rev['rating'];
    if (isset($rating_counts[$r])) $rating_counts[$r]++;
}
// Fetch product images
$stmt_imgs = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC, id ASC');
$stmt_imgs->execute([$product['id']]);
$product_images = $stmt_imgs->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name_' . $lang] ?? $product['name']); ?> - تفاصيل المنتج</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="mobile.css">
    <?php endif; ?>
    <style>
        .product-detail-container { max-width: 700px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .breadcrumb { font-size: 0.98em; margin-bottom: 18px; color: #888; }
        .breadcrumb a { color: var(--secondary-color); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .product-header { display: flex; gap: 30px; align-items: flex-start; }
        .product-gallery { display: flex; gap: 10px; align-items: center; }
        .product-img { width: 260px; height: 260px; object-fit: cover; border-radius: 10px; background: #fafafa; }
        .gallery-thumbs { display: flex; gap: 8px; margin-top: 10px; }
        .gallery-thumbs img { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 2px solid #eee; cursor: pointer; }
        .product-info { flex: 1; }
        .product-info h2 { margin: 0 0 10px; }
        .product-info .price { color: var(--secondary-color); font-weight: bold; font-size: 1.2em; margin-bottom: 10px; }
        .product-info .stock { color: #228B22; }
        .product-info .out-stock { color: #c00; }
        .product-info .category { color: #555; font-size: 0.98em; margin-bottom: 10px; }
        .add-cart-btn { margin-top: 10px; padding: 10px 24px; background: var(--primary-color); color: #fff; border: none; border-radius: 5px; font-size: 1em; cursor: pointer; }
        .add-cart-btn:disabled { background: #ccc; cursor: not-allowed; }
        .tabs { display: flex; gap: 10px; margin-top: 30px; }
        .tab-btn { background: #eee; color: #333; border: none; border-radius: 6px 6px 0 0; padding: 10px 24px; cursor: pointer; font-size: 1em; }
        .tab-btn.active { background: var(--primary-color); color: #fff; }
        .tab-content { background: #fafafa; border-radius: 0 0 10px 10px; padding: 20px; margin-top: -2px; }
        .reviews-section { margin-top: 0; }
        .review-item { background: #f9f9f9; border-radius: 8px; padding: 16px; margin-bottom: 18px; }
        .reviewer-name { font-weight: bold; color: var(--secondary-color); }
        .review-text { margin-top: 5px; }
        .related-products { margin-top: 40px; }
        .related-products h3 { margin-bottom: 18px; }
        .related-grid { display: flex; gap: 18px; flex-wrap: wrap; }
        .related-card { width: 180px; background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .related-card img { width: 100%; height: 90px; object-fit: cover; border-radius: 8px; }
        .related-card h4 { margin: 8px 0 4px; font-size: 1em; }
        .related-card .price { color: var(--secondary-color); font-weight: bold; }
        /* Star rating widget styles */
        .star-rating { display: flex; gap: 5px; }
        .star-rating span { cursor: pointer; }
        .star-rating span:hover { color: #FFD600; }
        .star-rating span.active { color: #FFD600; }
    </style>
    <script>
    // function showTab(tab) {
    //     document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    //     document.querySelectorAll('.tab-content').forEach(tc => tc.style.display = 'none');
    //     document.getElementById(tab+'-tab').classList.add('active');
    //     document.getElementById(tab+'-content').style.display = 'block';
    // }
    // window.onload = function() { showTab('desc'); };
    </script>
</head>
<body>
<div id="pageContent">
    <?php include 'header.php'; ?>
    <!-- Skeleton loader for product detail -->
    <div class="skeleton-detail" id="skeletonDetail" style="display:none;">
      <div class="skeleton-detail-img"></div>
      <div class="skeleton-detail-info">
        <div class="skeleton-detail-line long"></div>
        <div class="skeleton-detail-line medium"></div>
        <div class="skeleton-detail-line short"></div>
        <div class="skeleton-detail-line medium"></div>
      </div>
    </div>
    <div class="product-detail-container">
        <a href="index.php" class="back-home-btn"><span class="arrow">&#8592;</span> <?= __('back_to_home') ?></a>
        <div class="breadcrumb">
            <a href="index.php"><?= __('home') ?></a> &gt; 
            <?php if ($product['category_name']): ?>
                <a href="search.php?category=<?php echo urlencode($product['category_name_' . $lang] ?? $product['category_name']); ?>"><?php echo htmlspecialchars($product['category_name_' . $lang] ?? $product['category_name']); ?></a> &gt; 
            <?php endif; ?>
            <span><?php echo htmlspecialchars($product['name_' . $lang] ?? $product['name']); ?></span>
        </div>
        <div class="product-header">
            <div class="product-gallery">
                <?php if ($product_images && count($product_images) > 0): ?>
                    <div class="gallery-main-frame" style="width:260px;height:260px;overflow:hidden;position:relative;">
                        <img id="mainProductImg" src="uploads/<?php echo htmlspecialchars($product_images[0]['image_path']); ?>" alt="صورة المنتج" style="width:260px;height:260px;object-fit:cover;border-radius:10px;transition:opacity 0.35s;">
                        <button class="gallery-arrow left" id="galleryArrowLeft" style="position:absolute;top:50%;left:0;transform:translateY(-50%);z-index:2;background:rgba(255,255,255,0.7);border:none;border-radius:50%;width:36px;height:36px;font-size:1.5em;cursor:pointer;">&#8592;</button>
                        <button class="gallery-arrow right" id="galleryArrowRight" style="position:absolute;top:50%;right:0;transform:translateY(-50%);z-index:2;background:rgba(255,255,255,0.7);border:none;border-radius:50%;width:36px;height:36px;font-size:1.5em;cursor:pointer;">&#8594;</button>
                    </div>
                    <div class="gallery-thumbs" id="galleryThumbs" style="display:flex;gap:8px;margin-top:10px;justify-content:center;">
                        <?php foreach ($product_images as $i => $img): ?>
                            <img src="uploads/<?php echo htmlspecialchars($img['image_path']); ?>" alt="صورة المنتج" class="gallery-thumb" data-idx="<?php echo $i; ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:2px solid <?php echo $i===0?'#FFD600':'#eee'; ?>;cursor:pointer;transition:border 0.2s;">
                        <?php endforeach; ?>
                    </div>
                    <script>
                    (function(){
                        const mainImg = document.getElementById('mainProductImg');
                        const thumbs = document.querySelectorAll('#galleryThumbs .gallery-thumb');
                        const leftBtn = document.getElementById('galleryArrowLeft');
                        const rightBtn = document.getElementById('galleryArrowRight');
                        let activeIdx = 0;
                        let autoScroll = true;
                        let autoTimer = null;
                        function setActive(idx, user) {
                            if (idx === activeIdx) return;
                            mainImg.style.opacity = 0.5;
                            setTimeout(()=>{
                                mainImg.src = thumbs[idx].src;
                                mainImg.style.opacity = 1;
                            }, 150);
                            thumbs.forEach((t,i)=>t.style.border = i===idx?'2px solid #FFD600':'2px solid #eee');
                            activeIdx = idx;
                            thumbs[idx].scrollIntoView({behavior:'smooth',inline:'center',block:'nearest'});
                            if (user) resetAuto();
                        }
                        function next() { setActive((activeIdx+1)%thumbs.length); }
                        function prev() { setActive((activeIdx-1+thumbs.length)%thumbs.length); }
                        function resetAuto() {
                            autoScroll = false;
                            clearInterval(autoTimer);
                            setTimeout(()=>{ autoScroll = true; autoTimer = setInterval(()=>{ if(autoScroll) next(); }, 3000); }, 7000);
                        }
                        thumbs.forEach((thumb,i)=>{
                            thumb.addEventListener('click',()=>setActive(i,true));
                        });
                        leftBtn.addEventListener('click',()=>{ prev(); resetAuto(); });
                        rightBtn.addEventListener('click',()=>{ next(); resetAuto(); });
                        // Pause auto-scroll on hover
                        [mainImg,leftBtn,rightBtn,...thumbs].forEach(el=>{
                            el.addEventListener('mouseenter',()=>{autoScroll=false;clearInterval(autoTimer);});
                            el.addEventListener('mouseleave',()=>{autoScroll=true;autoTimer=setInterval(()=>{if(autoScroll)next();},3000);});
                        });
                        // Touch swipe for mobile
                        let startX=0,dx=0;
                        mainImg.parentElement.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;});
                        mainImg.parentElement.addEventListener('touchmove',e=>{dx=e.touches[0].clientX-startX;});
                        mainImg.parentElement.addEventListener('touchend',()=>{
                            if(Math.abs(dx)>50){
                                if(dx>0) prev(); else next();
                                resetAuto();
                            }
                            dx=0;
                        });
                        // Start auto-scroll
                        autoTimer = setInterval(()=>{ if(autoScroll) next(); }, 3000);
                    })();
                    </script>
                <?php else: ?>
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="صورة المنتج" class="product-img main-img" id="mainProductImg" loading="lazy">
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h2><?php echo htmlspecialchars($product['name_' . $lang] ?? $product['name']); ?></h2>
                <div class="price">السعر: <?php echo htmlspecialchars($product['price']); ?> د.ت</div>
                <?php if ($product['stock'] > 0): ?>
                    <div class="stock"><?= __('in_stock') ?>: <?php echo htmlspecialchars($product['stock']); ?></div>
                <?php else: ?>
                    <div class="out-stock"><?= __('out_of_stock') ?></div>
                <?php endif; ?>
                <?php if ($product['category_name']): ?>
                    <div class="category"><?= __('category') ?>: <?php echo htmlspecialchars($product['category_name_' . $lang] ?? $product['category_name']); ?></div>
                <?php endif; ?>
                <form action="add_to_cart.php" method="get" class="add-to-cart-form sticky-add-cart">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="add-cart-btn" <?php if ($product['stock'] <= 0) echo 'disabled'; ?>><?= __('add_to_cart') ?></button>
                </form>
            </div>
        </div>
        <?php if (!empty($product['seller_name']) || !empty($product['seller_story']) || !empty($product['seller_photo'])): ?>
        <div class="seller-story" style="margin:32px 0 0 0; padding:24px; background:#f4f6fb; border-radius:14px; box-shadow:0 2px 8px #0001;">
            <h3 style="color:var(--primary-color);margin-bottom:10px;"><?= __('about_seller') ?></h3>
            <?php if (!empty($product['seller_photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($product['seller_photo']); ?>" alt="صورة البائع" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:10px;">
            <?php endif; ?>
            <?php if (!empty($product['seller_name'])): ?>
                <div class="seller-name" style="font-weight:bold;font-size:1.1em;margin-bottom:6px;"> <?php echo htmlspecialchars($product['seller_name']); ?> </div>
            <?php endif; ?>
            <?php if (!empty($product['seller_story'])): ?>
                <div class="seller-story-text" style="color:#333;line-height:1.7;"> <?php echo nl2br(htmlspecialchars($product['seller_story'])); ?> </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="product-details-section" style="background:#fafafa;border-radius:14px;padding:36px 28px 32px 28px;margin-top:36px;box-shadow:0 2px 16px rgba(0,191,174,0.06);max-width:800px;margin-left:auto;margin-right:auto;">
    <h2 style="display:flex;align-items:center;gap:10px;font-size:1.5em;color:var(--primary-color);margin-bottom:18px;">
        <span style="font-size:1.2em;">📝</span> <?= __('description') ?>
    </h2>
    <p style="font-size:1.13em;line-height:1.7;color:#222;margin-bottom:32px;"> <?php echo nl2br(htmlspecialchars($product['description'])); ?> </p>
    <hr style="border:none;border-top:1.5px solid #E3E7ED;margin:32px 0 28px 0;">
    <div style="display:flex;align-items:center;gap:18px;margin-bottom:18px;">
        <h2 style="margin:0;font-size:1.25em;color:var(--primary-color);display:flex;align-items:center;gap:8px;">
            <span style="font-size:1.1em;">⭐</span> <?= __('customer_reviews') ?>
        </h2>
        <?php if($review_count): ?>
            <div style="display:flex;align-items:center;gap:6px;font-size:1.18em;">
                <span style="color:#FFD600;font-size:1.3em;letter-spacing:2px;">
                    <?php for($i=1;$i<=5;$i++) echo $i <= round($avg_rating) ? '★' : '☆'; ?>
                </span>
                <span style="color:#888;font-size:1em;">(<?= $avg_rating ?>/5, <?= $review_count ?>)</span>
            </div>
        <?php endif; ?>
    </div>
    <?php if($review_count): ?>
    <div class="review-breakdown" style="margin-bottom:18px;max-width:340px;">
        <?php for($star=5;$star>=1;$star--): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                <span style="width:38px;display:inline-block;"> <?= $star ?> <span style="color:#FFD600;">★</span> </span>
                <div style="background:#E3E7ED;border-radius:6px;height:12px;width:120px;overflow:hidden;">
                    <div style="background:#FFD600;height:100%;width:<?= $review_count ? round($rating_counts[$star]/$review_count*100) : 0 ?>%;"></div>
                </div>
                <span style="color:#888;font-size:0.98em;min-width:22px;text-align:right;"> <?= $rating_counts[$star] ?> </span>
        </div>
        <?php endfor; ?>
        </div>
    <?php endif; ?>
    <div id="reviews-content">
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $rev): ?>
                <div style="background:#fff;border:1.5px solid #E3E7ED;border-radius:10px;padding:16px 18px 12px 18px;margin-bottom:18px;box-shadow:0 2px 8px rgba(26,35,126,0.04);">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <span style="font-weight:bold;color:#00BFAE;font-size:1.08em;"> <?php echo htmlspecialchars($rev['name']); ?> </span>
                        <span style="color:#FFD600;font-size:1.15em;letter-spacing:1px;">
                            <?php echo str_repeat('★', (int)$rev['rating']); ?><?php echo str_repeat('☆', 5-(int)$rev['rating']); ?>
                        </span>
                        </div>
                    <?php if (!empty($rev['comment'])): ?>
                        <div style="margin-bottom:6px;font-size:1.08em;color:#222;"> <?php echo nl2br(htmlspecialchars($rev['comment'])); ?> </div>
                    <?php endif; ?>
                    <div style="color:#888;font-size:0.97em;text-align:left;"> <?php echo $rev['created_at']; ?> </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div style="color:#888;font-size:1.08em;margin-bottom:18px;"> <?= __('no_reviews_yet') ?> </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
        <div style="background:#f4f6fb;border:1.5px solid #E3E7ED;border-radius:12px;padding:22px 18px 18px 18px;margin:32px 0 0 0;max-width:420px;">
            <h4 style="margin:0 0 12px 0;font-size:1.13em;color:#1A237E;display:flex;align-items:center;gap:6px;">
                <span style="font-size:1.1em;">✍️</span> <?= __('add_your_review') ?>
            </h4>
            <form method="post" action="submit_review.php" class="modern-form" id="reviewForm">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="font-size:1.08em;"> <?= __('your_rating') ?>: </label>
                    <div id="starRating" class="star-rating" style="font-size:2em; color:#FFD600; cursor:pointer;">
                        <span data-value="1">☆</span><span data-value="2">☆</span><span data-value="3">☆</span><span data-value="4">☆</span><span data-value="5">☆</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" required>
                </div>
                <div class="form-group" id="commentGroup" style="display:none;margin-bottom:14px;">
                    <label for="comment" style="font-size:1.08em;"> <?= __('your_comment') ?>: </label>
                    <textarea id="comment" name="comment" rows="3" placeholder="<?= __('your_comment') ?> (اختياري)" style="width:100%;border-radius:8px;border:1.5px solid #E3E7ED;padding:10px 12px;font-size:1em;"></textarea>
                </div>
                <button type="submit" class="add-cart-btn" id="submitReviewBtn" style="display:none;width:100%;font-size:1.08em;"> <?= __('submit_review') ?> </button>
            </form>
        </div>
        <script>
        // Interactive star rating widget
        const stars = document.querySelectorAll('#starRating span');
        const ratingInput = document.getElementById('ratingInput');
        const commentGroup = document.getElementById('commentGroup');
        const submitBtn = document.getElementById('submitReviewBtn');
        let selected = 0;
        stars.forEach(star => {
            star.addEventListener('mouseenter', function() {
                const val = parseInt(this.dataset.value);
                stars.forEach((s, i) => s.textContent = i < val ? '★' : '☆');
            });
            star.addEventListener('mouseleave', function() {
                stars.forEach((s, i) => s.textContent = i < selected ? '★' : '☆');
            });
            star.addEventListener('click', function() {
                selected = parseInt(this.dataset.value);
                ratingInput.value = selected;
                stars.forEach((s, i) => s.textContent = i < selected ? '★' : '☆');
                commentGroup.style.display = 'block';
                submitBtn.style.display = 'block';
            });
        });
        // Prevent submit if no rating
        const reviewForm = document.getElementById('reviewForm');
        reviewForm.addEventListener('submit', function(e) {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('يرجى اختيار عدد النجوم');
            }
        });
        </script>
            <?php else: ?>
            <div style="color:#888;font-size:1.08em;margin-top:18px;"> <?= __('please_login_to_add_review') ?> <a href="client/login.php"> <?= __('login') ?> </a></div>
            <?php endif; ?>
        </div>
    <hr style="border:none;border-top:1.5px solid #E3E7ED;margin:36px 0 28px 0;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <h3 style="margin:0;font-size:1.15em;color:var(--primary-color);display:flex;align-items:center;gap:8px;">
            <span style="font-size:1.1em;">🚚</span> <?= __('shipping') ?>
        </h3>
    </div>
    <div style="background:#fffbe7;border:1.5px solid #FFD600;border-radius:10px;padding:16px 18px 12px 18px;font-size:1.08em;color:#1A237E;max-width:420px;">
        <?= __('shipping_available') ?> <?= __('within_2_5_working_days') ?>
    </div>
        </div>
        <div class="related-products">
            <h3><?= __('related_products') ?></h3>
            <div class="related-grid">
                <?php foreach ($related as $rel): ?>
                    <?php $rel_name = $rel['name_' . $lang] ?? $rel['name']; ?>
                <div class="related-card">
                    <a href="product.php?id=<?php echo $rel['id']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($rel['image']); ?>" alt="<?php echo htmlspecialchars($rel_name); ?>">
                        <h4><?php echo htmlspecialchars($rel_name); ?></h4>
                        <div class="price"><?php echo htmlspecialchars($rel['price']); ?> د.ت</div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
    // function showTab(tab) {
    //     document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    //     document.querySelectorAll('.tab-content').forEach(tc => tc.style.display = 'none');
    //     document.getElementById(tab+'-tab').classList.add('active');
    //     document.getElementById(tab+'-content').style.display = 'block';
    // }
    // window.onload = function() { showTab('desc'); };
    </script>
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
</div>
</body>
</html> 