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

$page_title = 'إدارة المدراء';
$page_subtitle = 'عرض وإدارة حسابات المدراء';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'dashboard.php'],
    ['title' => 'إدارة المدراء']
];

require '../db.php';
require 'admin_header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Prevent deleting self
    if ($id === $_SESSION['admin_id']) {
        $error = 'لا يمكنك حذف حسابك الخاص';
    } else {
        $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
        $stmt->execute([$id]);
        // Log activity
        $admin_id = $_SESSION['admin_id'];
        $action = 'delete_admin';
        $details = 'Deleted admin ID: ' . $id;
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
        header('Location: admins.php');
        exit();
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'], $_POST['role'])) {
    $admin_id = intval($_POST['admin_id']);
    $role = $_POST['role'];
    // Prevent changing own role
    if ($admin_id === $_SESSION['admin_id']) {
        $error = 'لا يمكنك تغيير دورك الخاص';
    } else {
        $stmt = $pdo->prepare('UPDATE admins SET role = ? WHERE id = ?');
        $stmt->execute([$role, $admin_id]);
        // Log activity
        $current_admin_id = $_SESSION['admin_id'];
        $action = 'update_admin_role';
        $details = 'Updated admin ID: ' . $admin_id . ' to role: ' . $role;
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$current_admin_id, $action, $details]);
        header('Location: admins.php');
        exit();
    }
}

$admins = $pdo->query('SELECT * FROM admins ORDER BY created_at DESC')->fetchAll();

// Get current admin details
$stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

$permissions = [
    'superadmin' => [ 'manage_admins' => true ],
    'admin' => [ 'manage_admins' => false ],
    'moderator' => [ 'manage_admins' => false ],
];
$role = $current_admin['role'];
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-actions">
            <a href="add_admin.php" class="btn btn-primary" <?php if (!$permissions[$role]['manage_admins']) echo ' style="opacity:0.5;pointer-events:none;" tabindex="-1"'; ?>>
                <span class="btn-icon">👤</span>
                إضافة مدير جديد
            </a>
        </div>
    </div>

    <div class="content-body">
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admins-grid">
            <?php foreach ($admins as $admin): ?>
            <div class="admin-card <?php echo $admin['id'] === $_SESSION['admin_id'] ? 'current-user' : ''; ?>">
                <div class="admin-header">
                    <div class="admin-avatar">
                        <span class="avatar-text"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></span>
                    </div>
                    <div class="admin-info">
                        <h3 class="admin-name"><?php echo htmlspecialchars($admin['username']); ?></h3>
                        <div class="admin-meta">
                            <span class="admin-id">ID: <?php echo $admin['id']; ?></span>
                            <span class="admin-role">
                                <span class="role-badge role-<?php echo $admin['role']; ?>">
                                    <?php
                                    switch ($admin['role']) {
                                        case 'superadmin': echo 'مدير عام'; break;
                                        case 'admin': echo 'مدير'; break;
                                        case 'moderator': echo 'مشرف'; break;
                                        default: echo htmlspecialchars($admin['role']);
                                    }
                                    ?>
                                </span>
                            </span>
                        </div>
                    </div>
                    <?php if ($admin['id'] === $_SESSION['admin_id']): ?>
                        <div class="current-user-badge">
                            <span class="badge-text">أنت</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="admin-details">
                    <div class="detail-item">
                        <span class="detail-label">البريد الإلكتروني:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">تاريخ الإنشاء:</span>
                        <span class="detail-value"><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="admin-actions">
                    <?php if ($admin['id'] !== $_SESSION['admin_id'] && $permissions[$role]['manage_admins']): ?>
                        <form method="post" class="role-update-form">
                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                            <select name="role" class="role-select">
                                <option value="moderator" <?php if ($admin['role'] === 'moderator') echo 'selected'; ?>>مشرف</option>
                                <option value="admin" <?php if ($admin['role'] === 'admin') echo 'selected'; ?>>مدير</option>
                                <option value="superadmin" <?php if ($admin['role'] === 'superadmin') echo 'selected'; ?>>مدير عام</option>
                            </select>
                            <button type="submit" class="btn btn-warning btn-sm">
                                <span class="btn-icon">🔄</span>
                                تحديث الدور
                            </button>
                        </form>
                        
                        <a href="?delete=<?php echo $admin['id']; ?>" 
                           class="btn btn-danger btn-sm" 
                           title="حذف"
                           onclick="return confirm('هل أنت متأكد من حذف هذا المدير؟')">
                            <span class="btn-icon">🗑️</span>
                            حذف
                        </a>
                    <?php elseif ($admin['id'] === $_SESSION['admin_id']): ?>
                        <span class="current-user-message">لا يمكن تعديل حسابك الخاص</span>
                    <?php else: ?>
                        <span class="no-permission-message">لا توجد صلاحيات كافية</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($admins)): ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>لا توجد حسابات مدراء</h3>
            <p>لم يتم إضافة أي حسابات مدراء بعد.</p>
            <a href="add_admin.php" class="btn btn-primary">إضافة مدير جديد</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.admin-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.admin-card.current-user {
    border: 2px solid #667eea;
    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
}

.admin-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.admin-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 24px;
}

.admin-info {
    flex: 1;
}

.admin-name {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.2em;
    font-weight: 600;
}

.admin-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85em;
    color: #666;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.role-superadmin {
    background: #ffebee;
    color: #c62828;
}

.role-admin {
    background: #e8f5e8;
    color: #2e7d32;
}

.role-moderator {
    background: #fff3e0;
    color: #ef6c00;
}

.current-user-badge {
    background: #667eea;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.admin-details {
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.9em;
}

.detail-label {
    font-weight: 600;
    color: #555;
}

.detail-value {
    color: #333;
}

.admin-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.role-update-form {
    display: flex;
    gap: 8px;
    align-items: center;
}

.role-select {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    font-size: 0.9em;
}

.current-user-message, .no-permission-message {
    font-size: 0.9em;
    color: #666;
    font-style: italic;
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
    .admins-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-header {
        flex-direction: column;
        text-align: center;
    }
    
    .admin-actions {
        justify-content: center;
    }
    
    .role-update-form {
        flex-direction: column;
        width: 100%;
    }
}
</style>

<?php require 'admin_footer.php'; ?> 