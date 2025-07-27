<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: client/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle security actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    // Log password change
                    $stmt = $pdo->prepare("
                        INSERT INTO security_logs (user_id, action, ip_address, user_agent, status) 
                        VALUES (?, 'password_change', ?, ?, 'success')
                    ");
                    $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                    
                    $success_message = __('password_changed_successfully');
                } else {
                    $error_message = __('password_too_short');
                }
            } else {
                $error_message = __('passwords_dont_match');
            }
        } else {
            $error_message = __('current_password_incorrect');
        }
    }
    
    if (isset($_POST['enable_2fa'])) {
        // Generate 2FA secret
        $secret = generate2FASecret();
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
        $stmt->execute([$secret, $user_id]);
        
        // Log 2FA enablement
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (user_id, action, ip_address, user_agent, status) 
            VALUES (?, '2fa_enabled', ?, ?, 'success')
        ");
        $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        
        $success_message = __('2fa_enabled_successfully');
    }
    
    if (isset($_POST['disable_2fa'])) {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log 2FA disablement
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (user_id, action, ip_address, user_agent, status) 
            VALUES (?, '2fa_disabled', ?, ?, 'success')
        ");
        $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        
        $success_message = __('2fa_disabled_successfully');
    }
    
    if (isset($_POST['revoke_session'])) {
        $session_id = $_POST['session_id'];
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE id = ? AND user_id = ?");
        $stmt->execute([$session_id, $user_id]);
        
        $success_message = __('session_revoked_successfully');
    }
}

// Get user security information
$stmt = $pdo->prepare("
    SELECT email, phone, two_factor_enabled, last_login, created_at 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user_security = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent login history
$stmt = $pdo->prepare("
    SELECT * FROM security_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$login_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active sessions
$stmt = $pdo->prepare("
    SELECT * FROM user_sessions 
    WHERE user_id = ? AND is_active = 1 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get security statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_logins,
        COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_logins,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_logins,
        COUNT(CASE WHEN action = 'password_change' THEN 1 END) as password_changes
    FROM security_logs 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$security_stats = $stmt->fetch(PDO::FETCH_ASSOC);

function generate2FASecret() {
    return bin2hex(random_bytes(16));
}

include 'header.php';
?>

<style>
.security-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.security-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.security-header h1 {
    margin: 0;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.security-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.security-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.security-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

.security-card h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.3em;
}

.security-card .number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 10px;
}

.security-card .success {
    color: #28a745;
}

.security-card .warning {
    color: #ffc107;
}

.security-card .danger {
    color: #dc3545;
}

.security-card .info {
    color: #17a2b8;
}

.security-card .label {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 15px;
}

.security-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.security-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.security-form {
    max-width: 500px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #007bff;
}

.form-group button {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
    width: 100%;
}

.form-group button:hover {
    background: #0056b3;
}

.form-group .btn-danger {
    background: #dc3545;
}

.form-group .btn-danger:hover {
    background: #c82333;
}

.form-group .btn-success {
    background: #28a745;
}

.form-group .btn-success:hover {
    background: #218838;
}

.security-status {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.security-status .status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.security-status .status-enabled {
    background: #28a745;
}

.security-status .status-disabled {
    background: #dc3545;
}

.security-status .status-text {
    font-weight: bold;
}

.login-history {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.login-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f8f9fa;
}

.login-item:last-child {
    border-bottom: none;
}

.login-info {
    flex: 1;
}

.login-action {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.login-details {
    font-size: 0.9em;
    color: #666;
}

.login-status {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: bold;
}

.login-status.success {
    background: #d4edda;
    color: #155724;
}

.login-status.failed {
    background: #f8d7da;
    color: #721c24;
}

.session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f8f9fa;
}

.session-item:last-child {
    border-bottom: none;
}

.session-info {
    flex: 1;
}

.session-device {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.session-details {
    font-size: 0.9em;
    color: #666;
}

.session-actions form {
    margin: 0;
}

.session-actions button {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
}

.session-actions button:hover {
    background: #c82333;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-danger {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.security-tips {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.security-tips h4 {
    margin: 0 0 15px 0;
    color: #0056b3;
}

.security-tips ul {
    margin: 0;
    padding-left: 20px;
}

.security-tips li {
    margin: 5px 0;
    color: #333;
}

@media (max-width: 768px) {
    .security-overview {
        grid-template-columns: 1fr;
    }
    
    .login-item,
    .session-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<div class="security-container">
    <div class="security-header">
        <h1>🔒 <?php echo __('security_center'); ?></h1>
        <p><?php echo __('manage_your_account_security'); ?></p>
    </div>

    <!-- Security Overview -->
    <div class="security-overview">
        <div class="security-card">
            <h3><?php echo __('total_logins'); ?></h3>
            <div class="number info"><?php echo $security_stats['total_logins']; ?></div>
            <div class="label"><?php echo __('all_time_logins'); ?></div>
        </div>
        
        <div class="security-card">
            <h3><?php echo __('successful_logins'); ?></h3>
            <div class="number success"><?php echo $security_stats['successful_logins']; ?></div>
            <div class="label"><?php echo __('successful_attempts'); ?></div>
        </div>
        
        <div class="security-card">
            <h3><?php echo __('failed_logins'); ?></h3>
            <div class="number danger"><?php echo $security_stats['failed_logins']; ?></div>
            <div class="label"><?php echo __('failed_attempts'); ?></div>
        </div>
        
        <div class="security-card">
            <h3><?php echo __('password_changes'); ?></h3>
            <div class="number warning"><?php echo $security_stats['password_changes']; ?></div>
            <div class="label"><?php echo __('times_changed'); ?></div>
        </div>
    </div>

    <!-- Security Tips -->
    <div class="security-tips">
        <h4>💡 <?php echo __('security_tips'); ?></h4>
        <ul>
            <li><?php echo __('use_strong_password'); ?></li>
            <li><?php echo __('enable_2fa'); ?></li>
            <li><?php echo __('never_share_credentials'); ?></li>
            <li><?php echo __('log_out_public_devices'); ?></li>
            <li><?php echo __('monitor_login_activity'); ?></li>
        </ul>
    </div>

    <!-- Change Password -->
    <div class="security-section">
        <h3>🔑 <?php echo __('change_password'); ?></h3>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="security-form">
            <div class="form-group">
                <label><?php echo __('current_password'); ?></label>
                <input type="password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label><?php echo __('new_password'); ?></label>
                <input type="password" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label><?php echo __('confirm_new_password'); ?></label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <button type="submit" name="change_password"><?php echo __('change_password'); ?></button>
            </div>
        </form>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="security-section">
        <h3>🔐 <?php echo __('two_factor_authentication'); ?></h3>
        
        <div class="security-status">
            <div class="status-indicator <?php echo $user_security['two_factor_enabled'] ? 'status-enabled' : 'status-disabled'; ?>"></div>
            <div class="status-text">
                <?php echo $user_security['two_factor_enabled'] ? __('2fa_enabled') : __('2fa_disabled'); ?>
            </div>
        </div>
        
        <p><?php echo __('2fa_description'); ?></p>
        
        <form method="POST" style="display: inline;">
            <?php if ($user_security['two_factor_enabled']): ?>
                <button type="submit" name="disable_2fa" class="btn-danger"><?php echo __('disable_2fa'); ?></button>
            <?php else: ?>
                <button type="submit" name="enable_2fa" class="btn-success"><?php echo __('enable_2fa'); ?></button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Active Sessions -->
    <div class="security-section">
        <h3>💻 <?php echo __('active_sessions'); ?></h3>
        
        <?php if (empty($active_sessions)): ?>
            <p><?php echo __('no_active_sessions'); ?></p>
        <?php else: ?>
            <?php foreach ($active_sessions as $session): ?>
                <div class="session-item">
                    <div class="session-info">
                        <div class="session-device"><?php echo htmlspecialchars($session['device_info']); ?></div>
                        <div class="session-details">
                            <?php echo __('last_activity'); ?>: <?php echo date('M j, Y g:i A', strtotime($session['last_activity'])); ?>
                        </div>
                    </div>
                    <div class="session-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                            <button type="submit" name="revoke_session"><?php echo __('revoke'); ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Login History -->
    <div class="login-history">
        <h3>📋 <?php echo __('login_history'); ?></h3>
        
        <?php if (empty($login_history)): ?>
            <p><?php echo __('no_login_history'); ?></p>
        <?php else: ?>
            <?php foreach ($login_history as $login): ?>
                <div class="login-item">
                    <div class="login-info">
                        <div class="login-action"><?php echo ucfirst($login['action']); ?></div>
                        <div class="login-details">
                            <?php echo date('M j, Y g:i A', strtotime($login['created_at'])); ?> - 
                            <?php echo htmlspecialchars($login['ip_address']); ?>
                        </div>
                    </div>
                    <div class="login-status <?php echo $login['status']; ?>">
                        <?php echo ucfirst($login['status']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh security logs every 60 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // Check for new security events
        fetch('check_security_events.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_events > 0) {
                    // Show notification
                    if (confirm('<?php echo __("new_security_events"); ?>')) {
                        location.reload();
                    }
                }
            });
    }
}, 60000);

// Password strength checker
document.querySelector('input[name="new_password"]').addEventListener('input', function() {
    const password = this.value;
    const strength = checkPasswordStrength(password);
    
    // Update password strength indicator
    const strengthIndicator = document.querySelector('.password-strength') || 
        document.createElement('div');
    strengthIndicator.className = 'password-strength ' + strength.class;
    strengthIndicator.textContent = strength.text;
    
    if (!this.parentNode.querySelector('.password-strength')) {
        this.parentNode.appendChild(strengthIndicator);
    }
});

function checkPasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    if (score < 2) return { class: 'weak', text: '<?php echo __("password_weak"); ?>' };
    if (score < 4) return { class: 'medium', text: '<?php echo __("password_medium"); ?>' };
    return { class: 'strong', text: '<?php echo __("password_strong"); ?>' };
}
</script>

<style>
.password-strength {
    margin-top: 5px;
    font-size: 0.9em;
    font-weight: bold;
}

.password-strength.weak {
    color: #dc3545;
}

.password-strength.medium {
    color: #ffc107;
}

.password-strength.strong {
    color: #28a745;
}
</style>

<?php include 'footer.php'; ?> 