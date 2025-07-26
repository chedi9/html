<?php
require 'db.php';
require 'lang.php';
if (!isset($_GET['seller_id'])) {
    echo 'No seller specified.';
    exit();
}
$seller_id = intval($_GET['seller_id']);
$stmt = $pdo->prepare('SELECT * FROM sellers WHERE id = ?');
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();
if (!$seller) {
    echo 'Seller not found.';
    exit();
}
// Fetch categories for filter
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
// Handle filters
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
// Build product query
$where = ['seller_id = ?', 'approved = 1'];
$params = [$seller_id];
if ($category_id) { $where[] = 'category_id = ?'; $params[] = $category_id; }
if ($min_price !== '') { $where[] = 'price >= ?'; $params[] = $min_price; }
if ($max_price !== '') { $where[] = 'price <= ?'; $params[] = $max_price; }
$sql = 'SELECT * FROM products WHERE ' . implode(' AND ', $where);
if ($sort === 'price_asc') {
    $sql .= ' ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
    $sql .= ' ORDER BY price DESC';
} elseif ($sort === 'newest') {
    $sql .= ' ORDER BY created_at DESC';
} else {
    $sql .= ' ORDER BY created_at DESC';
}
$products = $pdo->prepare($sql);
$products->execute($params);
$products = $products->fetchAll();
// Fetch seller reviews
$reviews = $pdo->prepare('SELECT * FROM seller_reviews WHERE seller_id = ? ORDER BY created_at DESC');
$reviews->execute([$seller_id]);
$reviews = $reviews->fetchAll();
$avg_rating = 0;
$review_count = count($reviews);
if ($review_count) {
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / $review_count, 1);
}
?><!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($seller['store_name']); ?> - Store</title>
    <link rel="stylesheet" href="beta333.css">
    <style>
        .store-hero {
            background: linear-gradient(120deg, var(--primary-color) 60%, var(--accent-color) 100%);
            color: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            padding: 36px 18px 28px 18px;
            text-align: center;
            margin-bottom: 32px;
            position: relative;
        }
        .store-hero-logo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 2px 12px rgba(0,191,174,0.10);
            margin-bottom: 18px;
            background: #fff;
        }
        .store-hero-title {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #FFD600;
            text-shadow: 0 2px 16px rgba(0,191,174,0.18), 0 2px 8px rgba(0,0,0,0.10);
        }
        .store-hero-desc {
            font-size: 1.15em;
            color: #fff;
            margin-bottom: 10px;
        }
        .store-rating {
            font-size: 1.2em;
            color: #FFD600;
            margin-bottom: 8px;
        }
        .store-products-title {
            color: var(--primary-color);
            font-size: 1.3em;
            margin: 32px 0 18px 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 6px;
            text-align: center;
        }
        .store-products-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: center;
            margin-bottom: 32px;
        }
        .store-product-card {
            background: #fff;
            border: 1.5px solid #E3E7ED;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(26,35,126,0.06);
            padding: 18px 10px 12px 10px;
            min-width: 180px;
            max-width: 220px;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.18s, transform 0.18s;
        }
        .store-product-card img {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .store-product-card .product-name {
            margin: 8px 0 4px;
            font-weight: bold;
            color: var(--primary-color);
        }
        .store-product-card .product-price {
            color: #00BFAE;
            font-weight: bold;
        }
        .store-product-card:hover {
            box-shadow: 0 8px 24px rgba(0,191,174,0.18), 0 4px 16px rgba(26,35,126,0.08);
            transform: translateY(-4px) scale(1.04);
            z-index: 2;
        }
        .store-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f4f6fb;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(26,35,126,0.04);
            padding: 48px 18px;
            margin: 32px 0;
        }
        @media (max-width: 900px) {
            .store-products-grid { flex-direction: column; align-items: center; }
        }
        @media (max-width: 700px) {
            .store-filter-bar { flex-direction: column; gap: 8px; }
            .store-products-grid { flex-direction: column; align-items: center; }
            .store-empty-state { padding: 32px 4px; }
        }
    </style>
</head>
<body>
    <div class="account-container">
        <div class="store-hero">
            <?php if (!empty($seller['store_logo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($seller['store_logo']); ?>" alt="Logo" class="store-hero-logo">
            <?php endif; ?>
            <div class="store-hero-title">
                <?php echo htmlspecialchars($seller['store_name']); ?>
                <?php if (!empty($seller['is_disabled'])): ?>
                    <span style="background:#FFD600;color:#1A237E;padding:4px 14px;border-radius:8px;font-size:0.85em;margin-left:10px;vertical-align:middle;">Disabled Seller</span>
                <?php endif; ?>
            </div>
            <div class="store-hero-desc"><?php echo nl2br(htmlspecialchars($seller['store_description'])); ?></div>
            <div class="store-rating">
                <?php
                $stars = str_repeat('★', (int)round($avg_rating)) . str_repeat('☆', 5-(int)round($avg_rating));
                echo $stars . " (" . $avg_rating . "/5, " . $review_count . " reviews)";
                ?>
            </div>
            <?php if (!empty($seller['is_disabled']) && !empty($seller['store_logo'])): ?>
                <div style="margin:18px 0 0 0;">
                    <img src="uploads/<?php echo htmlspecialchars($seller['store_logo']); ?>" alt="Seller Photo" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #FFD600;box-shadow:0 2px 8px #FFD60033;">
                </div>
            <?php endif; ?>
            <?php if (!empty($seller['is_disabled']) && !empty($seller['store_description'])): ?>
                <div style="margin:16px 0 0 0;font-size:1.13em;color:#1A237E;background:#FFF8E1;padding:14px 18px;border-radius:10px;max-width:600px;margin-left:auto;margin-right:auto;box-shadow:0 2px 8px #FFD60022;">
                    <b>Seller Story:</b> <?php echo nl2br(htmlspecialchars($seller['store_description'])); ?>
                </div>
            <?php endif; ?>
        </div>
        <form method="get" class="store-filter-bar" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:center;margin-bottom:24px;background:#fff;padding:18px 12px;border-radius:12px;box-shadow:0 2px 8px #E3E7ED;">
            <input type="hidden" name="seller_id" value="<?php echo $seller_id; ?>">
            <select name="category_id" style="padding:8px 12px;border-radius:8px;border:1.5px solid #E3E7ED;min-width:140px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($category_id == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="min_price" placeholder="Min Price" value="<?php echo htmlspecialchars($min_price); ?>" style="padding:8px 12px;border-radius:8px;border:1.5px solid #E3E7ED;width:110px;">
            <input type="number" name="max_price" placeholder="Max Price" value="<?php echo htmlspecialchars($max_price); ?>" style="padding:8px 12px;border-radius:8px;border:1.5px solid #E3E7ED;width:110px;">
            <select name="sort" style="padding:8px 12px;border-radius:8px;border:1.5px solid #E3E7ED;min-width:120px;">
                <option value="">Sort By</option>
                <option value="price_asc" <?php if ($sort==='price_asc') echo 'selected'; ?>>Price: Low to High</option>
                <option value="price_desc" <?php if ($sort==='price_desc') echo 'selected'; ?>>Price: High to Low</option>
                <option value="newest" <?php if ($sort==='newest') echo 'selected'; ?>>Newest</option>
            </select>
            <button type="submit" style="background:var(--primary-color);color:#fff;border:none;border-radius:8px;padding:8px 24px;font-weight:bold;">Filter</button>
            <?php if ($category_id || $min_price !== '' || $max_price !== '' || $sort): ?>
                <a href="store.php?seller_id=<?php echo $seller_id; ?>" style="margin-left:10px;color:#c00;font-weight:bold;">Clear</a>
            <?php endif; ?>
        </form>
        <div class="store-products-title">Products</div>
        <?php if ($products): ?>
            <div class="store-products-grid">
            <?php foreach ($products as $prod): ?>
                <div class="store-product-card">
                    <a href="product.php?id=<?php echo $prod['id']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                    </a>
                    <div class="product-name"><?php echo htmlspecialchars($prod['name']); ?></div>
                    <div class="product-price"><?php echo $prod['price']; ?> <?= __('currency') ?></div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="store-empty-state">
                <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/1f6d2.svg" alt="No products" style="width:80px;height:80px;opacity:0.7;margin-bottom:18px;">
                <p style="font-size:1.15em;color:#888;">No products found for this seller.<br>Try adjusting your filters or check back later!</p>
            </div>
        <?php endif; ?>
        <a href="index.php" class="back-home-btn">Back to Home</a>
        <hr>
        <h3 style="color:var(--primary-color);text-align:center;">Seller Ratings & Reviews</h3>
        <div style="max-width:600px;margin:0 auto 32px auto;">
        <?php if ($reviews): ?>
            <?php foreach ($reviews as $rev): ?>
                <div style="background:#fff;border:1.5px solid #E3E7ED;border-radius:10px;padding:12px 16px;margin-bottom:12px;">
                    <div style="font-weight:bold;color:#00BFAE;display:inline-block;min-width:90px;"> <?php echo htmlspecialchars($rev['name']); ?> </div>
                    <span style="color:#FFD600;font-size:1.15em;letter-spacing:1px;"> <?php echo str_repeat('★', (int)$rev['rating']) . str_repeat('☆', 5-(int)$rev['rating']); ?> </span>
                    <div style="margin:6px 0 2px 0;font-size:1.08em;color:#222;"> <?php echo nl2br(htmlspecialchars($rev['comment'])); ?> </div>
                    <div style="color:#888;font-size:0.97em;text-align:left;"> <?php echo $rev['created_at']; ?> </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">No reviews yet for this seller.</p>
        <?php endif; ?>
        </div>
    </div>
</body>
</html> 