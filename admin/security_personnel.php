<?php
/**
 * Security Personnel Management
 * Admin interface for managing security personnel roles
 */

require_once '../security_integration.php';

// Check if user is super admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_security_personnel':
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email'], 'email');
                $password = $_POST['password'];
                $full_name = sanitizeInput($_POST['full_name']);
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, is_active, created_at) VALUES (?, ?, ?, ?, 'security_personnel', 1, NOW())");
                    $stmt->execute([$username, $email, $hashed_password, $full_name]);
                    
                    logSecurityEvent('security_personnel_added', [
                        'username' => $username,
                        'email' => $email,
                        'added_by' => $_SESSION['admin_id']
                    ]);
                    
                    $success_message = "Security personnel added successfully!";
                } catch (Exception $e) {
                    $error_message = "Error adding security personnel: " . $e->getMessage();
                }
                break;
                
            case 'update_security_personnel':
                $user_id = (int)$_POST['user_id'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare("UPDATE admins SET is_active = ? WHERE id = ? AND role = 'security_personnel'");
                    $stmt->execute([$is_active, $user_id]);
                    
                    logSecurityEvent('security_personnel_updated', [
                        'user_id' => $user_id,
                        'is_active' => $is_active,
                        'updated_by' => $_SESSION['admin_id']
                    ]);
                    
                    $success_message = "Security personnel updated successfully!";
                } catch (Exception $e) {
                    $error_message = "Error updating security personnel: " . $e->getMessage();
                }
                break;
                
            case 'delete_security_personnel':
                $user_id = (int)$_POST['user_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND role = 'security_personnel'");
                    $stmt->execute([$user_id]);
                    
                    logSecurityEvent('security_personnel_deleted', [
                        'user_id' => $user_id,
                        'deleted_by' => $_SESSION['admin_id']
                    ]);
                    
                    $success_message = "Security personnel deleted successfully!";
                } catch (Exception $e) {
                    $error_message = "Error deleting security personnel: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all security personnel
try {
            $stmt = $pdo->query("SELECT * FROM admins WHERE role = 'security_personnel' ORDER BY created_at DESC");
    $security_personnel = $stmt->fetchAll();
} catch (Exception $e) {
    $security_personnel = [];
}

// Get security statistics
try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM admins WHERE role = 'security_personnel'");
    $total_security = $stmt->fetch()['total'];
    
            $stmt = $pdo->query("SELECT COUNT(*) as active FROM admins WHERE role = 'security_personnel' AND is_active = 1");
    $active_security = $stmt->fetch()['active'];
} catch (Exception $e) {
    $total_security = 0;
    $active_security = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Personnel Management - WeBuy Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .panel {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .panel h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .actions {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🛡️ Security Personnel Management</h1>
            <p>Manage security personnel access and permissions</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_security; ?></div>
                <div class="stat-label">Total Security Personnel</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_security; ?></div>
                <div class="stat-label">Active Security Personnel</div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Add Security Personnel -->
        <div class="panel">
            <h3>➕ Add Security Personnel</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_security_personnel">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <button type="submit" class="btn btn-success">Add Security Personnel</button>
            </form>
        </div>

        <!-- Security Personnel List -->
        <div class="panel">
            <h3>👥 Security Personnel List</h3>
            <?php if (!empty($security_personnel)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($security_personnel as $person): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($person['username']); ?></td>
                                <td><?php echo htmlspecialchars($person['email']); ?></td>
                                <td><?php echo htmlspecialchars($person['full_name']); ?></td>
                                <td>
                                    <?php if ($person['is_active']): ?>
                                        <span class="status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($person['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_security_personnel">
                                        <input type="hidden" name="user_id" value="<?php echo $person['id']; ?>">
                                        <input type="checkbox" name="is_active" <?php echo $person['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        <label>Active</label>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this security personnel?')">
                                        <input type="hidden" name="action" value="delete_security_personnel">
                                        <input type="hidden" name="user_id" value="<?php echo $person['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="margin-left: 10px;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">No security personnel found</p>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="actions">
            <a href="unified_dashboard.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="security_dashboard.php" class="btn btn-success">🔒 Security Dashboard</a>
            <a href="admins.php" class="btn btn-warning">👥 Manage All Users</a>
        </div>
    </div>
</body>
</html> 