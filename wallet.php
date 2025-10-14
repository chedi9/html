<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle wallet actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_funds'])) {
        $amount = (float)$_POST['amount'];
        if ($amount > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO wallet_transactions (user_id, type, amount, description, status) 
                VALUES (?, 'deposit', ?, 'Wallet deposit', 'completed')
            ");
            $stmt->execute([$user_id, $amount]);
            
            // Update wallet balance
            $stmt = $pdo->prepare("
                UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
            ");
            $stmt->execute([$amount, $user_id]);
        }
    }
    
    if (isset($_POST['redeem_points'])) {
        $points = (int)$_POST['points'];
        if ($points > 0) {
            // Check if user has enough points
            $stmt = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_points = $stmt->fetchColumn();
            
            if ($current_points >= $points) {
                $cash_value = $points * 0.01; // 1 point = $0.01
                
                // Deduct points and add to wallet
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET loyalty_points = loyalty_points - ?, 
                        wallet_balance = wallet_balance + ? 
                    WHERE id = ?
                ");
                $stmt->execute([$points, $cash_value, $user_id]);
                
                // Record transaction
                $stmt = $pdo->prepare("
                    INSERT INTO wallet_transactions (user_id, type, amount, description, status) 
                    VALUES (?, 'points_redeem', ?, 'Points redemption', 'completed')
                ");
                $stmt->execute([$user_id, $cash_value]);
            }
        }
    }
}

// Get user wallet information
$stmt = $pdo->prepare("
    SELECT wallet_balance, loyalty_points, total_spent 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user_wallet = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent transactions
$stmt = $pdo->prepare("
    SELECT * FROM wallet_transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get loyalty tier information
$total_spent = $user_wallet['total_spent'] ?? 0;
$loyalty_tier = 'Bronze';
$next_tier = 'Silver';
$points_needed = 1000;

if ($total_spent >= 1000) {
    $loyalty_tier = 'Silver';
    $next_tier = 'Gold';
    $points_needed = 2500;
}
if ($total_spent >= 2500) {
    $loyalty_tier = 'Gold';
    $next_tier = 'Platinum';
    $points_needed = 5000;
}
if ($total_spent >= 5000) {
    $loyalty_tier = 'Platinum';
    $next_tier = 'Diamond';
    $points_needed = 10000;
}
if ($total_spent >= 10000) {
    $loyalty_tier = 'Diamond';
    $next_tier = 'Diamond';
    $points_needed = 0;
}

$progress_to_next = $total_spent / $points_needed * 100;
if ($progress_to_next > 100) $progress_to_next = 100;

include 'header.php';
?>



<div class="wallet-container">
    <div class="wallet-header">
        <h1>💰 <?php echo __('wallet_loyalty'); ?></h1>
        <p><?php echo __('manage_your_wallet_and_earn_rewards'); ?></p>
    </div>

    <!-- Wallet Overview -->
    <div class="wallet-overview">
        <div class="wallet-card wallet-balance">
            <h3><?php echo __('wallet_balance'); ?></h3>
            <div class="amount">$<?php echo number_format($user_wallet['wallet_balance'] ?? 0, 2); ?></div>
            <div class="label"><?php echo __('available_for_purchases'); ?></div>
        </div>
        
        <div class="wallet-card loyalty-points">
            <h3><?php echo __('loyalty_points'); ?></h3>
            <div class="amount"><?php echo number_format($user_wallet['loyalty_points'] ?? 0); ?></div>
            <div class="label"><?php echo __('earned_from_purchases'); ?></div>
        </div>
        
        <div class="wallet-card loyalty-tier">
            <h3><?php echo __('loyalty_tier'); ?></h3>
            <div class="amount"><?php echo $loyalty_tier; ?></div>
            <div class="label"><?php echo __('current_membership_level'); ?></div>
            
            <?php if ($loyalty_tier !== 'Diamond'): ?>
                <div class="loyalty-progress">
                    <div class="progress-text">
                        <?php echo __('progress_to'); ?> <?php echo $next_tier; ?>: 
                        $<?php echo number_format($total_spent); ?> / $<?php echo number_format($points_needed); ?>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">
                        <?php echo round($progress_to_next, 1); ?>% <?php echo __('complete'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wallet Actions -->
    <div class="wallet-actions">
        <h3><?php echo __('wallet_actions'); ?></h3>
        <div class="action-grid">
            <div class="action-card">
                <h4><?php echo __('add_funds'); ?></h4>
                <form method="POST" onsubmit="return validateAmount()">
                    <input type="number" name="amount" step="0.01" min="1" placeholder="<?php echo __('enter_amount'); ?>" required>
                    <button type="submit" name="add_funds" class="btn-success">
                        <?php echo __('add_to_wallet'); ?>
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <h4><?php echo __('redeem_points'); ?></h4>
                <form method="POST" onsubmit="return validatePoints()">
                    <input type="number" name="points" min="100" placeholder="<?php echo __('enter_points'); ?>" required>
                    <button type="submit" name="redeem_points" class="btn-success">
                        <?php echo __('redeem_for_cash'); ?>
                    </button>
                </form>
                <small>
                    <?php echo __('points_exchange_rate'); ?>: 100 points = $1.00
                </small>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="transactions-section">
        <h3><?php echo __('recent_transactions'); ?></h3>
        <?php if (empty($transactions)): ?>
            <p>
                <?php echo __('no_transactions_yet'); ?>
            </p>
        <?php else: ?>
            <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-title"><?php echo htmlspecialchars($transaction['description']); ?></div>
                        <div class="transaction-date">
                            <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                        </div>
                    </div>
                    <div class="transaction-amount <?php echo $transaction['type'] === 'deposit' || $transaction['type'] === 'points_redeem' ? 'positive' : 'negative'; ?>">
                        <?php echo $transaction['type'] === 'deposit' || $transaction['type'] === 'points_redeem' ? '+' : '-'; ?>
                        $<?php echo number_format($transaction['amount'], 2); ?>
                    </div>
                    <div class="transaction-status <?php echo $transaction['status']; ?>">
                        <?php echo ucfirst($transaction['status']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Loyalty Benefits -->
    <div class="loyalty-benefits">
        <h3><?php echo __('loyalty_benefits'); ?></h3>
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="icon">🎁</div>
                <h4><?php echo __('points_earned'); ?></h4>
                <p><?php echo __('earn_points_on_every_purchase'); ?></p>
            </div>
            <div class="benefit-item">
                <div class="icon">💰</div>
                <h4><?php echo __('cash_back'); ?></h4>
                <p><?php echo __('redeem_points_for_cash'); ?></p>
            </div>
            <div class="benefit-item">
                <div class="icon">🚚</div>
                <h4><?php echo __('free_shipping'); ?></h4>
                <p><?php echo __('free_shipping_on_orders'); ?></p>
            </div>
            <div class="benefit-item">
                <div class="icon">🎉</div>
                <h4><?php echo __('exclusive_offers'); ?></h4>
                <p><?php echo __('access_to_exclusive_deals'); ?></p>
            </div>
            <div class="benefit-item">
                <div class="icon">⭐</div>
                <h4><?php echo __('priority_support'); ?></h4>
                <p><?php echo __('faster_customer_support'); ?></p>
            </div>
            <div class="benefit-item">
                <div class="icon">🎯</div>
                <h4><?php echo __('early_access'); ?></h4>
                <p><?php echo __('early_access_to_new_products'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh wallet balance every 30 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        fetch('get_wallet_balance.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update wallet balance display
                    const balanceElement = document.querySelector('.wallet-balance .amount');
                    if (balanceElement) {
                        balanceElement.textContent = '$' + parseFloat(data.balance).toFixed(2);
                    }
                    
                    // Update loyalty points display
                    const pointsElement = document.querySelector('.loyalty-points .amount');
                    if (pointsElement) {
                        pointsElement.textContent = parseInt(data.points).toLocaleString();
                    }
                }
            });
    }
}, 30000);

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const amountInput = this.querySelector('input[name="amount"]');
        const pointsInput = this.querySelector('input[name="points"]');
        
        if (amountInput && parseFloat(amountInput.value) <= 0) {
            e.preventDefault();
            alert('<?php echo __("please_enter_valid_amount"); ?>');
            return;
        }
        
        if (pointsInput && parseInt(pointsInput.value) < 100) {
            e.preventDefault();
            alert('<?php echo __("minimum_points_required"); ?>');
            return;
        }
    });
});
</script>

<?php include 'footer.php'; ?> 