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

require '../db.php';

// Check if current admin has permission to add admins
$stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

$permissions = [
    'superadmin' => [ 'manage_admins' => true ],
    'admin' => [ 'manage_admins' => false ],
    'moderator' => [ 'manage_admins' => false ],
];

if (!$permissions[$current_admin['role']]['manage_admins']) {
    header('Location: admins.php');
    exit();
}

// Handle form submission - BEFORE including admin_header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    } elseif (strlen($username) < 3) {
        $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
    }
    
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمة المرور غير متطابقة';
    }
    
    if (!in_array($role, ['moderator', 'admin', 'superadmin'])) {
        $errors[] = 'الدور غير صحيح';
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = 'اسم المستخدم موجود مسبقاً';
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'البريد الإلكتروني موجود مسبقاً';
    }
    
    if (empty($errors)) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new admin
        $stmt = $pdo->prepare('INSERT INTO admins (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$username, $email, $password_hash, $role]);
        
        $new_admin_id = $pdo->lastInsertId();
        
        // Log activity
        $admin_id = $_SESSION['admin_id'];
        $action = 'add_admin';
        $details = 'Added new admin: ' . $username . ' with role: ' . $role;
        $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
        
        header('Location: admins.php');
        exit();
    }
}

$page_title = 'إضافة مدير جديد';
$page_subtitle = 'إضافة حساب مدير جديد للنظام';
$breadcrumb = [
    ['title' => 'الرئيسية', 'url' => 'dashboard.php'],
    ['title' => 'إدارة المدراء', 'url' => 'admins.php'],
    ['title' => 'إضافة مدير جديد']
];

require 'admin_header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-actions">
            <a href="admins.php" class="btn btn-secondary">
                <span class="btn-icon">←</span>
                العودة إلى المدراء
            </a>
        </div>
    </div>

    <div class="content-body">
        <div class="form-container">
            <h2>إضافة مدير جديد</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" class="admin-form">
                <div class="form-group">
                    <label for="username">اسم المستخدم:</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">البريد الإلكتروني:</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">كلمة المرور:</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">تأكيد كلمة المرور:</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="role">الدور:</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">اختر الدور</option>
                        <option value="moderator" <?php echo (isset($_POST['role']) && $_POST['role'] === 'moderator') ? 'selected' : ''; ?>>مشرف</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>مدير</option>
                        <option value="superadmin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'superadmin') ? 'selected' : ''; ?>>مدير عام</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">➕</span>
                        إضافة المدير
                    </button>
                    <a href="admins.php" class="btn btn-secondary">
                        <span class="btn-icon">❌</span>
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto;
}

.form-container h2 {
    margin-bottom: 25px;
    color: #333;
    text-align: center;
    font-size: 1.5em;
}

.admin-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #555;
    font-size: 0.95em;
}

.form-control {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    margin-top: 20px;
}

.message.error {
    background: #ffebee;
    color: #c62828;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #c62828;
}

.message.error ul {
    margin: 0;
    padding-left: 20px;
}

.message.error li {
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .form-container {
        margin: 20px;
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php require 'admin_footer.php'; ?> 