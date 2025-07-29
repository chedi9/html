<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'إدارة التقييمات';
$page_subtitle = 'عرض وإدارة تقييمات المنتجات';
$breadcrumb = [
            ['title' => 'الرئيسية', 'url' => 'unified_dashboard.php'],
    ['title' => 'إدارة التقييمات']
];

require '../db.php';
require 'admin_header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
    $stmt->execute([$id]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'delete_review';
    $details = 'Deleted review ID: ' . $id;
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: reviews.php');
    exit();
}

// Get reviews with product and user information
$reviews = $pdo->query('
    SELECT r.*, p.name as product_name, p.image as product_image, u.name as user_name, u.email as user_email
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
')->fetchAll();
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($reviews); ?></div>
                <div class="stat-label">إجمالي التقييمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $avgRating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
                    echo number_format($avgRating, 1);
                    ?>
                </div>
                <div class="stat-label">متوسط التقييم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($reviews, function($r) { return $r['rating'] >= 4; })); ?>
                </div>
                <div class="stat-label">تقييمات إيجابية</div>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if ($reviews): ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="product-info">
                            <?php if ($review['product_image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($review['product_image']); ?>" 
                                     alt="صورة المنتج" 
                                     class="product-thumbnail">
                            <?php else: ?>
                                <div class="product-thumbnail-placeholder">📦</div>
                            <?php endif; ?>
                            <div class="product-details">
                                <h4 class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></h4>
                                <div class="review-meta">
                                    <span class="review-id">ID: <?php echo $review['id']; ?></span>
                                    <span class="review-date"><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="rating-display">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : 'empty'; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-number"><?php echo $review['rating']; ?>/5</div>
                        </div>
                    </div>
                    
                    <div class="review-content">
                        <div class="user-info">
                            <div class="user-avatar">
                                <span class="avatar-text"><?php echo strtoupper(substr($review['user_name'], 0, 1)); ?></span>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($review['user_email']); ?></div>
                            </div>
                        </div>
                        
                        <div class="review-text">
                            <?php echo htmlspecialchars($review['comment']); ?>
                        </div>
                    </div>
                    
                    <div class="review-actions">
                        <a href="?delete=<?php echo $review['id']; ?>" 
                           class="btn btn-danger btn-sm" 
                           title="حذف"
                           onclick="return confirm('هل أنت متأكد من حذف هذا التقييم؟')">
                            <span class="btn-icon">🗑️</span>
                            حذف
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <h3>لا توجد تقييمات</h3>
                <p>لم يتم إضافة أي تقييمات بعد.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.header-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

.reviews-grid {
    display: grid;
    gap: 20px;
}

.review-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.product-thumbnail-placeholder {
    width: 50px;
    height: 50px;
    background: #f5f5f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #999;
    border: 2px dashed #ddd;
}

.product-name {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1em;
    font-weight: 600;
}

.review-meta {
    display: flex;
    gap: 15px;
    font-size: 0.8em;
    color: #666;
}

.rating-display {
    text-align: center;
}

.stars {
    display: flex;
    gap: 2px;
    margin-bottom: 5px;
}

.star {
    font-size: 18px;
    color: #ddd;
}

.star.filled {
    color: #ffc107;
}

.rating-number {
    font-size: 0.9em;
    font-weight: 600;
    color: #333;
}

.review-content {
    margin-bottom: 15px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

.user-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.user-email {
    font-size: 0.85em;
    color: #666;
}

.review-text {
    color: #555;
    line-height: 1.5;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.review-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    margin-bottom: 8px;
    color: #333;
}

.empty-state p {
    margin-bottom: 24px;
    color: #666;
}

@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .review-actions {
        justify-content: center;
    }
}
</style>

<?php require 'admin_footer.php'; ?> 