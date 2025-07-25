<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
require '../admin/check_admin.php';
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
// Fetch current admin's role
$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$current_admin = $stmt->fetch();
if (!$current_admin) {
    header('Location: logout.php');
    exit();
}
$is_superadmin = $current_admin['role'] === 'superadmin';
// After fetching $current_admin
$permissions = [
    'superadmin' => [
        'manage_admins' => true,
    ],
    'admin' => [
        'manage_admins' => false,
    ],
    'moderator' => [
        'manage_admins' => false,
    ],
];
$role = $current_admin['role'];
// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin']) && $is_superadmin) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        if ($username && $email && $password && in_array($role, ['superadmin','admin','moderator'])) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO admins (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, $hash, $role]);
            $admin_id_new = $pdo->lastInsertId();
            // Log activity
            $admin_id = $_SESSION['admin_id'];
            $action = 'add_admin';
            $details = 'Added admin: ' . $username . ' (ID: ' . $admin_id_new . ')';
            $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
            header('Location: admins.php'); exit();
        }
    } elseif (isset($_POST['edit_admin']) && $is_superadmin) {
        $id = intval($_POST['id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        if ($username && $email && in_array($role, ['superadmin','admin','moderator'])) {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE admins SET username=?, email=?, password_hash=?, role=? WHERE id=?');
                $stmt->execute([$username, $email, $hash, $role, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE admins SET username=?, email=?, role=? WHERE id=?');
                $stmt->execute([$username, $email, $role, $id]);
            }
            // Log activity
            $admin_id = $_SESSION['admin_id'];
            $action = 'edit_admin';
            $details = 'Edited admin: ' . $username . ' (ID: ' . $id . ')';
            $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
            header('Location: admins.php'); exit();
        }
    } elseif (isset($_POST['delete_admin']) && $is_superadmin) {
        $id = intval($_POST['id']);
        if ($id != $_SESSION['admin_id']) { // Prevent deleting self
            $stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
            $stmt->execute([$id]);
            $username = $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
            $stmt->execute([$id]);
            // Log activity
            $admin_id = $_SESSION['admin_id'];
            $action = 'delete_admin';
            $details = 'Deleted admin: ' . $username . ' (ID: ' . $id . ')';
            $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
            header('Location: admins.php'); exit();
        }
    }
}
// Fetch all admins
$admins = $pdo->query('SELECT * FROM admins ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المشرفين</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        .admins-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .admins-container h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #f4f4f4; }
        .action-btn { background: #c00; color: #fff; padding: 6px 16px; border-radius: 5px; text-decoration: none; font-size: 0.95em; margin: 0 4px; }
        .action-btn:hover { background: #a00; }
        .edit-btn { background: #43A047; }
        .edit-btn:hover { background: #228B22; }
        .add-form, .edit-form { margin: 24px 0; background: #f9f9f9; padding: 18px; border-radius: 8px; }
        .add-form input, .edit-form input, .add-form select, .edit-form select { margin: 0 8px 0 0; padding: 6px 10px; border-radius: 5px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="admins-container">
        <h2>إدارة المشرفين</h2>
        <?php if ($permissions[$role]['manage_admins']): ?>
        <form class="add-form" method="post">
            <strong>إضافة مشرف جديد:</strong><br>
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="email" name="email" placeholder="البريد الإلكتروني" required autocomplete="email">
            <input type="password" name="password" placeholder="كلمة المرور" required autocomplete="new-password">
            <select name="role">
                <option value="admin">مشرف</option>
                <option value="moderator">مراقب</option>
                <option value="superadmin">مدير عام</option>
            </select>
            <button type="submit" name="add_admin" class="action-btn edit-btn">إضافة</button>
        </form>
        <?php else: ?>
        <form class="add-form" method="post" style="opacity:0.5;pointer-events:none;">
            <strong>إضافة مشرف جديد:</strong><br>
            <input type="text" name="username" placeholder="اسم المستخدم" required disabled>
            <input type="email" name="email" placeholder="البريد الإلكتروني" required disabled autocomplete="email">
            <input type="password" name="password" placeholder="كلمة المرور" required disabled autocomplete="new-password">
            <select name="role" disabled>
                <option value="admin">مشرف</option>
                <option value="moderator">مراقب</option>
                <option value="superadmin">مدير عام</option>
            </select>
            <button type="button" class="action-btn edit-btn" disabled>إضافة</button>
        </form>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>اسم المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الدور</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <form class="edit-form" method="post" id="form-<?php echo $admin['id']; ?>">
                        <td>
                            <select name="username" required <?php if (!$permissions[$role]['manage_admins']) echo 'disabled style="opacity:0.5;"'; ?> >
                                <?php foreach ($admins as $a): ?>
                                    <option value="<?php echo htmlspecialchars($a['username']); ?>" <?php if ($a['username'] === $admin['username']) echo 'selected'; ?>><?php echo htmlspecialchars($a['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required <?php if (!$permissions[$role]['manage_admins']) echo 'disabled style="opacity:0.5;"'; ?> autocomplete="email"></td>
                        <td>
                            <select name="role" <?php if (!$is_superadmin) echo 'disabled'; ?> <?php if (!$permissions[$role]['manage_admins']) echo 'style="opacity:0.5;"'; ?>>
                                <option value="admin" <?php if ($admin['role']==='admin') echo 'selected'; ?>>مشرف</option>
                                <option value="moderator" <?php if ($admin['role']==='moderator') echo 'selected'; ?>>مراقب</option>
                                <option value="superadmin" <?php if ($admin['role']==='superadmin') echo 'selected'; ?>>مدير عام</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                            <input type="password" name="password" placeholder="تغيير كلمة المرور (اختياري)" <?php if (!$permissions[$role]['manage_admins']) echo 'disabled style="opacity:0.5;"'; ?> autocomplete="new-password">
                            <button type="submit" name="edit_admin" class="action-btn edit-btn" <?php if (!$permissions[$role]['manage_admins']) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>تحديث</button>
                            <?php if ($is_superadmin && $admin['id'] != $_SESSION['admin_id']): ?>
                                <button type="submit" name="delete_admin" class="action-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المشرف؟');" <?php if (!$permissions[$role]['manage_admins']) echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>حذف</button>
                            <?php endif; ?>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="add-btn" style="background:var(--secondary-color);margin-top:30px;">العودة للوحة التحكم</a>
    </div>
</body>
</html> 