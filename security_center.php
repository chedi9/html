<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

if (session_status() === PHP_SESSION_NONE) session_start();

require 'db.php';
require 'lang.php';
require 'security_headers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: client/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_password':
            handlePasswordChange($user_id);
            break;
        case 'enable_2fa':
            handle2FAEnable($user_id);
            break;
        case 'disable_2fa':
            handle2FADisable($user_id);
            break;
        case 'trust_device':
            handleTrustDevice($user_id);
            break;
        case 'revoke_device':
            handleRevokeDevice($user_id);
            break;
    }
}

// Get user security data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get recent security events
$stmt = $pdo->prepare("SELECT * FROM security_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$security_events = $stmt->fetchAll();

// Get trusted devices
$stmt = $pdo->prepare("SELECT * FROM device_fingerprints WHERE user_id = ? AND is_trusted = 1 ORDER BY last_used DESC");
$stmt->execute([$user_id]);
$trusted_devices = $stmt->fetchAll();

// Get security alerts
$stmt = $pdo->prepare("SELECT * FROM security_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$security_alerts = $stmt->fetchAll();

// Check suspicious activity
$suspicious_check = SecurityHeaders::checkSuspiciousActivity($user_id);

function handlePasswordChange($user_id) {
    global $pdo;
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['security_error'] = 'كلمة المرور الحالية غير صحيحة';
        return;
    }
    
    // Validate new password
    if (strlen($new_password) < 8) {
        $_SESSION['security_error'] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل';
        return;
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['security_error'] = 'كلمة المرور الجديدة غير متطابقة';
        return;
    }
    
    // Hash and update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);
    
    // Log security event
    SecurityHeaders::logSecurityEvent('password_changed', [], $user_id);
    
    $_SESSION['security_success'] = 'تم تغيير كلمة المرور بنجاح';
}

function handle2FAEnable($user_id) {
    global $pdo;
    
    // Generate 2FA secret
    $secret = bin2hex(random_bytes(16));
    
    $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
    $stmt->execute([$secret, $user_id]);
    
    SecurityHeaders::logSecurityEvent('2fa_enabled', [], $user_id);
    $_SESSION['security_success'] = 'تم تفعيل المصادقة الثنائية بنجاح';
}

function handle2FADisable($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    SecurityHeaders::logSecurityEvent('2fa_disabled', [], $user_id);
    $_SESSION['security_success'] = 'تم إلغاء تفعيل المصادقة الثنائية';
}

function handleTrustDevice($user_id) {
    global $pdo;
    
    $device_id = $_POST['device_id'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE device_fingerprints SET is_trusted = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    
    SecurityHeaders::logSecurityEvent('device_trusted', ['device_id' => $device_id], $user_id);
    $_SESSION['security_success'] = 'تم الوثوق بالجهاز بنجاح';
}

function handleRevokeDevice($user_id) {
    global $pdo;
    
    $device_id = $_POST['device_id'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE device_fingerprints SET is_trusted = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    
    SecurityHeaders::logSecurityEvent('device_revoked', ['device_id' => $device_id], $user_id);
    $_SESSION['security_success'] = 'تم إلغاء الوثوق بالجهاز';
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>مركز الأمان - WeBuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="beta333.css">
    <style>
        .security-center {
            max-width: 1000px;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .security-section {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .security-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .security-title {
            font-size: 1.5em;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .security-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            font-weight: bold;
            color: white;
        }
        
        .password-icon { background: #e74c3c; }
        .twofa-icon { background: #3498db; }
        .device-icon { background: #2ecc71; }
        .activity-icon { background: #f39c12; }
        .alert-icon { background: #e67e22; }
        
        .security-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #00BFAE;
            box-shadow: 0 0 0 3px rgba(0, 191, 174, 0.1);
            outline: none;
        }
        
        .btn {
            background: linear-gradient(135deg, #00BFAE, #008ba3);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 191, 174, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .btn-danger:hover {
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .security-events {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .event-item {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .event-type {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .event-time {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .device-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .device-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .device-details {
            flex: 1;
        }
        
        .device-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .device-meta {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .suspicious-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .suspicious-warning h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .suspicious-reasons {
            list-style: none;
            padding: 0;
        }
        
        .suspicious-reasons li {
            color: #856404;
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }
        
        .suspicious-reasons li:before {
            content: "⚠️";
            position: absolute;
            left: 0;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="security-center">
        <div class="dashboard-header">
            <h2>مركز الأمان</h2>
            <p class="dashboard-subtitle">إدارة أمان حسابك وحمايته</p>
        </div>
        
        <?php if (isset($_SESSION['security_success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['security_success']; unset($_SESSION['security_success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['security_error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['security_error']; unset($_SESSION['security_error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($suspicious_check['suspicious']): ?>
            <div class="suspicious-warning">
                <h4>⚠️ نشاط مشبوه تم اكتشافه</h4>
                <ul class="suspicious-reasons">
                    <?php foreach ($suspicious_check['reasons'] as $reason): ?>
                        <li><?php echo htmlspecialchars($reason); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Password Security -->
        <div class="security-section">
            <div class="security-header">
                <div class="security-title">
                    <div class="security-icon password-icon">🔒</div>
                    كلمة المرور
                </div>
                <span class="security-status status-enabled">مفعلة</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                <div class="form-grid">
                    <div class="form-group">
                        <label>كلمة المرور الحالية</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>كلمة المرور الجديدة</label>
                        <input type="password" name="new_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label>تأكيد كلمة المرور الجديدة</label>
                        <input type="password" name="confirm_password" required minlength="8">
                    </div>
                </div>
                
                <button type="submit" class="btn">تغيير كلمة المرور</button>
            </form>
        </div>
        
        <!-- Two-Factor Authentication -->
        <div class="security-section">
            <div class="security-header">
                <div class="security-title">
                    <div class="security-icon twofa-icon">🔐</div>
                    المصادقة الثنائية
                </div>
                <span class="security-status <?php echo $user['two_factor_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                    <?php echo $user['two_factor_enabled'] ? 'مفعلة' : 'معطلة'; ?>
                </span>
            </div>
            
            <?php if (!$user['two_factor_enabled']): ?>
                <p>المصادقة الثنائية تضيف طبقة إضافية من الحماية لحسابك</p>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="enable_2fa">
                    <button type="submit" class="btn">تفعيل المصادقة الثنائية</button>
                </form>
            <?php else: ?>
                <p>المصادقة الثنائية مفعلة لحسابك</p>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="disable_2fa">
                    <button type="submit" class="btn btn-danger">إلغاء تفعيل المصادقة الثنائية</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Trusted Devices -->
        <div class="security-section">
            <div class="security-header">
                <div class="security-title">
                    <div class="security-icon device-icon">📱</div>
                    الأجهزة الموثوقة
                </div>
            </div>
            
            <?php if (empty($trusted_devices)): ?>
                <p>لا توجد أجهزة موثوقة حالياً</p>
            <?php else: ?>
                <?php foreach ($trusted_devices as $device): ?>
                    <div class="device-item">
                        <div class="device-info">
                            <div class="device-details">
                                <div class="device-name"><?php echo htmlspecialchars($device['browser'] ?? 'جهاز غير معروف'); ?></div>
                                <div class="device-meta">
                                    <?php echo htmlspecialchars($device['os'] ?? ''); ?> • 
                                    آخر استخدام: <?php echo date('Y-m-d H:i', strtotime($device['last_used'])); ?>
                                </div>
                            </div>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="revoke_device">
                                <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                <button type="submit" class="btn btn-danger">إلغاء الوثوق</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Security Events -->
        <div class="security-section">
            <div class="security-header">
                <div class="security-title">
                    <div class="security-icon activity-icon">📊</div>
                    نشاط الأمان
                </div>
            </div>
            
            <div class="security-events">
                <?php if (empty($security_events)): ?>
                    <p>لا توجد أحداث أمان مسجلة</p>
                <?php else: ?>
                    <?php foreach ($security_events as $event): ?>
                        <div class="event-item">
                            <div>
                                <div class="event-type"><?php echo htmlspecialchars($event['event_type']); ?></div>
                                <div class="event-time"><?php echo date('Y-m-d H:i', strtotime($event['created_at'])); ?></div>
                            </div>
                            <div class="event-ip"><?php echo htmlspecialchars($event['ip_address']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Security Alerts -->
        <?php if (!empty($security_alerts)): ?>
            <div class="security-section">
                <div class="security-header">
                    <div class="security-title">
                        <div class="security-icon alert-icon">⚠️</div>
                        تنبيهات الأمان
                    </div>
                </div>
                
                <?php foreach ($security_alerts as $alert): ?>
                    <div class="alert alert-warning">
                        <h4><?php echo htmlspecialchars($alert['title']); ?></h4>
                        <p><?php echo htmlspecialchars($alert['message']); ?></p>
                        <small><?php echo date('Y-m-d H:i', strtotime($alert['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 