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

$page_title = 'إدارة التصنيفات';
$page_subtitle = 'عرض، تعديل، وحذف تصنيفات المنتجات';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'unified_dashboard.php'],
    ['title' => 'إدارة التصنيفات']
];

require '../db.php';
require 'admin_header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = 'delete_category';
    $details = 'Deleted category ID: ' . $id;
    $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
    header('Location: categories.php');
    exit();
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-actions">
            <a href="add_category.php" class="btn btn-primary">
                <span class="btn-icon">➕</span>
                إضافة تصنيف جديد
            </a>
        </div>
    </div>

    <div class="content-body">
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-header">
                    <div class="category-icon">
                        <?php if ($category['icon']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($category['icon']); ?>" 
                                 alt="أيقونة التصنيف" 
                                 class="category-icon-img">
                        <?php else: ?>
                            <div class="category-icon-placeholder">📁</div>
                        <?php endif; ?>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <div class="category-meta">
                            <span class="category-id">ID: <?php echo $category['id']; ?></span>
                            <span class="category-order">ترتيب: <?php echo $category['sort_order']; ?></span>
                        </div>
                    </div>
                    <div class="category-status">
                        <span class="status-badge <?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $category['is_active'] ? 'نشط' : 'غير نشط'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="category-content">
                    <div class="category-description">
                        <?php echo htmlspecialchars($category['description'] ?: 'لا يوجد وصف'); ?>
                    </div>
                    
                    <div class="category-languages">
                        <div class="language-item">
                            <span class="lang-label">عربي:</span>
                            <span class="lang-value"><?php echo htmlspecialchars($category['name_ar'] ?: $category['name']); ?></span>
                        </div>
                        <div class="language-item">
                            <span class="lang-label">فرنسي:</span>
                            <span class="lang-value"><?php echo htmlspecialchars($category['name_fr'] ?: $category['name']); ?></span>
                        </div>
                        <div class="language-item">
                            <span class="lang-label">إنجليزي:</span>
                            <span class="lang-value"><?php echo htmlspecialchars($category['name_en'] ?: $category['name']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="category-actions">
                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" 
                       class="btn btn-warning btn-sm" 
                       title="تعديل">
                        <span class="btn-icon">✏️</span>
                        تعديل
                    </a>
                    
                    <a href="?delete=<?php echo $category['id']; ?>" 
                       class="btn btn-danger btn-sm" 
                       title="حذف"
                       onclick="return confirm('هل أنت متأكد من حذف هذا التصنيف؟ سيتم حذف جميع المنتجات المرتبطة به.')">
                        <span class="btn-icon">🗑️</span>
                        حذف
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($categories)): ?>
        <div class="empty-state">
            <div class="empty-icon">📁</div>
            <h3>لا توجد تصنيفات</h3>
            <p>لم يتم إضافة أي تصنيفات بعد. ابدأ بإضافة تصنيف جديد.</p>
            <a href="add_category.php" class="btn btn-primary">إضافة تصنيف جديد</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.category-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.category-icon {
    flex-shrink: 0;
}

.category-icon-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.category-icon-placeholder {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.category-info {
    flex: 1;
}

.category-name {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1em;
    font-weight: 600;
}

.category-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85em;
    color: #666;
}

.category-status {
    flex-shrink: 0;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.status-badge.active {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-badge.inactive {
    background: #ffebee;
    color: #c62828;
}

.category-content {
    margin-bottom: 15px;
}

.category-description {
    color: #666;
    font-size: 0.9em;
    line-height: 1.4;
    margin-bottom: 12px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.category-languages {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.language-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85em;
}

.lang-label {
    font-weight: 600;
    color: #555;
    min-width: 60px;
}

.lang-value {
    color: #333;
    text-align: left;
    flex: 1;
    margin-left: 10px;
}

.category-actions {
    display: flex;
    gap: 8px;
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
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-header {
        flex-direction: column;
        text-align: center;
    }
    
    .category-actions {
        justify-content: center;
    }
}
</style>

<?php require 'admin_footer.php'; ?> 