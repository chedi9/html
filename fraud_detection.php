<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin/login.php');
    exit();
}

// Handle fraud detection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_safe'])) {
        $alert_id = (int)$_POST['alert_id'];
        $stmt = $pdo->prepare("UPDATE fraud_alerts SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $alert_id]);
        $success_message = $lang['alert_marked_safe'];
    }
    
    if (isset($_POST['mark_fraud'])) {
        $alert_id = (int)$_POST['alert_id'];
        $stmt = $pdo->prepare("UPDATE fraud_alerts SET status = 'confirmed_fraud', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $alert_id]);
        $success_message = $lang['alert_marked_fraud'];
    }
    
    if (isset($_POST['block_user'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET status = 'blocked', blocked_reason = ?, blocked_at = NOW() WHERE id = ?");
        $stmt->execute([$_POST['block_reason'], $user_id]);
        $success_message = $lang['user_blocked_successfully'];
    }
}

// Get fraud detection statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_alerts,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_alerts,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_alerts,
        COUNT(CASE WHEN status = 'confirmed_fraud' THEN 1 END) as confirmed_fraud,
        COUNT(CASE WHEN risk_level = 'high' THEN 1 END) as high_risk,
        COUNT(CASE WHEN risk_level = 'medium' THEN 1 END) as medium_risk,
        COUNT(CASE WHEN risk_level = 'low' THEN 1 END) as low_risk
    FROM fraud_alerts 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$fraud_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent fraud alerts
$stmt = $pdo->prepare("
    SELECT fa.*, u.email, u.phone, u.first_name, u.last_name
    FROM fraud_alerts fa
    JOIN users u ON fa.user_id = u.id
    WHERE fa.status = 'pending'
    ORDER BY fa.risk_score DESC, fa.created_at DESC
    LIMIT 20
");
$stmt->execute();
$fraud_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get suspicious activities
$stmt = $pdo->prepare("
    SELECT 
        user_id,
        COUNT(*) as failed_attempts,
        MAX(created_at) as last_attempt,
        GROUP_CONCAT(DISTINCT ip_address) as ip_addresses
    FROM security_logs 
    WHERE action = 'login' 
    AND status = 'failed' 
    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    GROUP BY user_id 
    HAVING COUNT(*) > 5
    ORDER BY failed_attempts DESC
");
$stmt->execute();
$suspicious_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get high-value transactions
$stmt = $pdo->prepare("
    SELECT o.*, u.email, u.phone, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.total_amount > 1000 
    AND o.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY o.total_amount DESC
    LIMIT 10
");
$stmt->execute();
$high_value_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get blocked users
$stmt = $pdo->prepare("
    SELECT id, email, phone, first_name, last_name, blocked_reason, blocked_at
    FROM users 
    WHERE status = 'blocked'
    ORDER BY blocked_at DESC
    LIMIT 10
");
$stmt->execute();
$blocked_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
.fraud-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.fraud-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.fraud-header h1 {
    margin: 0;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.fraud-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.fraud-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.3em;
}

.stat-card .number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 10px;
}

.stat-card .danger {
    color: #dc3545;
}

.stat-card .warning {
    color: #ffc107;
}

.stat-card .success {
    color: #28a745;
}

.stat-card .info {
    color: #17a2b8;
}

.stat-card .label {
    color: #666;
    font-size: 0.9em;
}

.fraud-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.fraud-section h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #dc3545;
    padding-bottom: 10px;
}

.alert-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    position: relative;
}

.alert-item.high-risk {
    border-left: 4px solid #dc3545;
    background: #fff5f5;
}

.alert-item.medium-risk {
    border-left: 4px solid #ffc107;
    background: #fffbf0;
}

.alert-item.low-risk {
    border-left: 4px solid #28a745;
    background: #f0fff4;
}

.alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.alert-title {
    font-weight: bold;
    font-size: 1.1em;
    color: #333;
}

.alert-risk {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.alert-risk.high {
    background: #dc3545;
    color: white;
}

.alert-risk.medium {
    background: #ffc107;
    color: #333;
}

.alert-risk.low {
    background: #28a745;
    color: white;
}

.alert-details {
    margin-bottom: 15px;
}

.alert-detail {
    display: flex;
    justify-content: space-between;
    margin: 5px 0;
    font-size: 0.9em;
}

.alert-detail .label {
    font-weight: bold;
    color: #666;
}

.alert-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.alert-actions button {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
}

.alert-actions .btn-success {
    background: #28a745;
    color: white;
}

.alert-actions .btn-success:hover {
    background: #218838;
}

.alert-actions .btn-danger {
    background: #dc3545;
    color: white;
}

.alert-actions .btn-danger:hover {
    background: #c82333;
}

.alert-actions .btn-warning {
    background: #ffc107;
    color: #333;
}

.alert-actions .btn-warning:hover {
    background: #e0a800;
}

.activity-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.activity-user {
    font-weight: bold;
    color: #333;
}

.activity-count {
    background: #dc3545;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    font-weight: bold;
}

.activity-details {
    font-size: 0.9em;
    color: #666;
}

.transaction-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.transaction-amount {
    font-weight: bold;
    color: #dc3545;
    font-size: 1.1em;
}

.transaction-date {
    font-size: 0.9em;
    color: #666;
}

.transaction-details {
    font-size: 0.9em;
    color: #666;
}

.blocked-user-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}

.blocked-user-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.blocked-user-name {
    font-weight: bold;
    color: #333;
}

.blocked-date {
    font-size: 0.9em;
    color: #666;
}

.blocked-reason {
    font-size: 0.9em;
    color: #dc3545;
    font-style: italic;
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

@media (max-width: 768px) {
    .fraud-stats {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .alert-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .alert-actions {
        flex-direction: column;
    }
    
    .alert-actions button {
        width: 100%;
    }
}
</style>

<div class="fraud-container">
    <div class="fraud-header">
        <h1>🛡️ <?php echo $lang['fraud_detection']; ?></h1>
        <p><?php echo $lang['monitor_and_prevent_fraud']; ?></p>
    </div>

    <!-- Fraud Statistics -->
    <div class="fraud-stats">
        <div class="stat-card">
            <h3><?php echo $lang['total_alerts']; ?></h3>
            <div class="number info"><?php echo $fraud_stats['total_alerts']; ?></div>
            <div class="label"><?php echo $lang['last_30_days']; ?></div>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $lang['pending_alerts']; ?></h3>
            <div class="number danger"><?php echo $fraud_stats['pending_alerts']; ?></div>
            <div class="label"><?php echo $lang['require_attention']; ?></div>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $lang['confirmed_fraud']; ?></h3>
            <div class="number danger"><?php echo $fraud_stats['confirmed_fraud']; ?></div>
            <div class="label"><?php echo $lang['fraud_cases']; ?></div>
        </div>
        
        <div class="stat-card">
            <h3><?php echo $lang['high_risk']; ?></h3>
            <div class="number warning"><?php echo $fraud_stats['high_risk']; ?></div>
            <div class="label"><?php echo $lang['high_priority']; ?></div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <!-- Fraud Alerts -->
    <div class="fraud-section">
        <h3>🚨 <?php echo $lang['fraud_alerts']; ?></h3>
        
        <?php if (empty($fraud_alerts)): ?>
            <p><?php echo $lang['no_fraud_alerts']; ?></p>
        <?php else: ?>
            <?php foreach ($fraud_alerts as $alert): ?>
                <div class="alert-item <?php echo $alert['risk_level']; ?>-risk">
                    <div class="alert-header">
                        <div class="alert-title"><?php echo htmlspecialchars($alert['alert_type']); ?></div>
                        <div class="alert-risk <?php echo $alert['risk_level']; ?>">
                            <?php echo ucfirst($alert['risk_level']); ?> Risk
                        </div>
                    </div>
                    
                    <div class="alert-details">
                        <div class="alert-detail">
                            <span class="label"><?php echo $lang['user']; ?>:</span>
                            <span><?php echo htmlspecialchars($alert['first_name'] . ' ' . $alert['last_name']); ?> (<?php echo htmlspecialchars($alert['email']); ?>)</span>
                        </div>
                        <div class="alert-detail">
                            <span class="label"><?php echo $lang['risk_score']; ?>:</span>
                            <span><?php echo $alert['risk_score']; ?>/100</span>
                        </div>
                        <div class="alert-detail">
                            <span class="label"><?php echo $lang['detected_at']; ?>:</span>
                            <span><?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?></span>
                        </div>
                        <div class="alert-detail">
                            <span class="label"><?php echo $lang['description']; ?>:</span>
                            <span><?php echo htmlspecialchars($alert['description']); ?></span>
                        </div>
                    </div>
                    
                    <div class="alert-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                            <button type="submit" name="mark_safe" class="btn-success"><?php echo $lang['mark_safe']; ?></button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                            <button type="submit" name="mark_fraud" class="btn-danger"><?php echo $lang['mark_fraud']; ?></button>
                        </form>
                        <button onclick="blockUser(<?php echo $alert['user_id']; ?>)" class="btn-warning"><?php echo $lang['block_user']; ?></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Suspicious Activities -->
    <div class="fraud-section">
        <h3>⚠️ <?php echo $lang['suspicious_activities']; ?></h3>
        
        <?php if (empty($suspicious_activities)): ?>
            <p><?php echo $lang['no_suspicious_activities']; ?></p>
        <?php else: ?>
            <?php foreach ($suspicious_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-header">
                        <div class="activity-user">User ID: <?php echo $activity['user_id']; ?></div>
                        <div class="activity-count"><?php echo $activity['failed_attempts']; ?> attempts</div>
                    </div>
                    <div class="activity-details">
                        <div><strong><?php echo $lang['last_attempt']; ?>:</strong> <?php echo date('M j, Y g:i A', strtotime($activity['last_attempt'])); ?></div>
                        <div><strong><?php echo $lang['ip_addresses']; ?>:</strong> <?php echo htmlspecialchars($activity['ip_addresses']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- High Value Transactions -->
    <div class="fraud-section">
        <h3>💰 <?php echo $lang['high_value_transactions']; ?></h3>
        
        <?php if (empty($high_value_transactions)): ?>
            <p><?php echo $lang['no_high_value_transactions']; ?></p>
        <?php else: ?>
            <?php foreach ($high_value_transactions as $transaction): ?>
                <div class="transaction-item">
                    <div class="transaction-header">
                        <div class="transaction-amount">$<?php echo number_format($transaction['total_amount'], 2); ?></div>
                        <div class="transaction-date"><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></div>
                    </div>
                    <div class="transaction-details">
                        <div><strong><?php echo $lang['customer']; ?>:</strong> <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?> (<?php echo htmlspecialchars($transaction['email']); ?>)</div>
                        <div><strong><?php echo $lang['order_id']; ?>:</strong> #<?php echo $transaction['id']; ?></div>
                        <div><strong><?php echo $lang['status']; ?>:</strong> <?php echo ucfirst($transaction['status']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Blocked Users -->
    <div class="fraud-section">
        <h3>🚫 <?php echo $lang['blocked_users']; ?></h3>
        
        <?php if (empty($blocked_users)): ?>
            <p><?php echo $lang['no_blocked_users']; ?></p>
        <?php else: ?>
            <?php foreach ($blocked_users as $user): ?>
                <div class="blocked-user-item">
                    <div class="blocked-user-header">
                        <div class="blocked-user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</div>
                        <div class="blocked-date"><?php echo date('M j, Y g:i A', strtotime($user['blocked_at'])); ?></div>
                    </div>
                    <div class="blocked-reason"><?php echo htmlspecialchars($user['blocked_reason']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function blockUser(userId) {
    const reason = prompt('<?php echo $lang["enter_block_reason"]; ?>:');
    if (reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="block_reason" value="${reason}">
            <input type="hidden" name="block_user" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-refresh fraud alerts every 30 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // Check for new fraud alerts
        fetch('check_fraud_alerts.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_alerts > 0) {
                    // Show notification
                    if (confirm('<?php echo $lang["new_fraud_alerts"]; ?>')) {
                        location.reload();
                    }
                }
            });
    }
}, 30000);
</script>

<?php include 'footer.php'; ?> 