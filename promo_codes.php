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

// Handle promo code application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_promo'])) {
        $promo_code = trim($_POST['promo_code']);
        
        if (!empty($promo_code)) {
            // Check if promo code exists and is valid
            $stmt = $pdo->prepare("
                SELECT * FROM promo_codes 
                WHERE code = ? 
                AND status = 'active' 
                AND (expiry_date IS NULL OR expiry_date > NOW())
                AND (max_uses IS NULL OR uses < max_uses)
            ");
            $stmt->execute([$promo_code]);
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($promo) {
                // Check if user has already used this code
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM promo_code_usage 
                    WHERE promo_code_id = ? AND user_id = ?
                ");
                $stmt->execute([$promo['id'], $user_id]);
                $already_used = $stmt->fetchColumn() > 0;
                
                if (!$already_used) {
                    // Store promo code in session for checkout
                    $_SESSION['applied_promo'] = $promo;
                    $success_message = __('promo_code_applied');
                } else {
                    $error_message = __('promo_code_already_used');
                }
            } else {
                $error_message = __('invalid_promo_code');
            }
        }
    }
    
    if (isset($_POST['remove_promo'])) {
        unset($_SESSION['applied_promo']);
        $success_message = __('promo_code_removed');
    }
}

// Get user's available vouchers
$stmt = $pdo->prepare("
    SELECT v.*, vt.name as voucher_type_name, vt.description as voucher_description
    FROM user_vouchers v
    JOIN voucher_types vt ON v.voucher_type_id = vt.id
    WHERE v.user_id = ? 
    AND v.is_used = 0 
    AND (v.expiry_date IS NULL OR v.expiry_date > NOW())
    ORDER BY v.created_at DESC
");
$stmt->execute([$user_id]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's promo code usage history
$stmt = $pdo->prepare("
    SELECT pc.code, pc.discount_type, pc.discount_value, pcu.used_at, o.total_amount
    FROM promo_code_usage pcu
    JOIN promo_codes pc ON pcu.promo_code_id = pc.id
    LEFT JOIN orders o ON pcu.order_id = o.id
    WHERE pcu.user_id = ?
    ORDER BY pcu.used_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$promo_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>



<div class="promo-container">
    <div class="promo-header">
        <h1>🎫 <?php echo __('promo_codes_vouchers'); ?></h1>
        <p><?php echo __('apply_discounts_and_save_money'); ?></p>
    </div>

    <!-- Apply Promo Code -->
    <div class="promo-apply">
        <h3><?php echo __('apply_promo_code'); ?></h3>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['applied_promo'])): ?>
            <div class="applied-promo">
                <div class="promo-info">
                    <div class="promo-code"><?php echo $_SESSION['applied_promo']['code']; ?></div>
                    <div class="promo-discount">
                        <?php 
                        if ($_SESSION['applied_promo']['discount_type'] === 'percentage') {
                            echo $_SESSION['applied_promo']['discount_value'] . '% ' . __('off');
                        } else {
                            echo '$' . $_SESSION['applied_promo']['discount_value'] . ' ' . __('off');
                        }
                        ?>
                    </div>
                </div>
                <form method="POST">
                    <button type="submit" name="remove_promo"><?php echo __('remove'); ?></button>
                </form>
            </div>
        <?php else: ?>
            <form method="POST" class="promo-form">
                <input type="text" name="promo_code" placeholder="<?php echo __('enter_promo_code'); ?>" required>
                <button type="submit" name="apply_promo"><?php echo __('apply_code'); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Available Vouchers -->
    <div class="vouchers-section">
        <h3><?php echo __('your_vouchers'); ?></h3>
        
        <?php if (empty($vouchers)): ?>
            <div class="empty-state">
                <div class="icon">🎫</div>
                <h4><?php echo __('no_vouchers_available'); ?></h4>
                <p><?php echo __('earn_vouchers_by_shopping'); ?></p>
            </div>
        <?php else: ?>
            <div class="vouchers-grid">
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="voucher-card">
                        <div class="voucher-header">
                            <div class="voucher-type"><?php echo htmlspecialchars($voucher['voucher_type_name']); ?></div>
                            <div class="voucher-expiry">
                                <?php if ($voucher['expiry_date']): ?>
                                    <?php echo __('expires'); ?>: <?php echo date('M j, Y', strtotime($voucher['expiry_date'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="voucher-value">
                            <?php 
                            if ($voucher['discount_type'] === 'percentage') {
                                echo $voucher['discount_value'] . '% ' . __('off');
                            } else {
                                echo '$' . $voucher['discount_value'] . ' ' . __('off');
                            }
                            ?>
                        </div>
                        <div class="voucher-description">
                            <?php echo htmlspecialchars($voucher['voucher_description']); ?>
                        </div>
                        <div class="voucher-code"><?php echo $voucher['code']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Promo Code History -->
    <div class="history-section">
        <h3><?php echo __('promo_code_history'); ?></h3>
        
        <?php if (empty($promo_history)): ?>
            <div class="empty-state">
                <div class="icon">📋</div>
                <h4><?php echo __('no_promo_history'); ?></h4>
                <p><?php echo __('start_using_promo_codes'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($promo_history as $history): ?>
                <div class="history-item">
                    <div class="history-info">
                        <div class="history-code"><?php echo htmlspecialchars($history['code']); ?></div>
                        <div class="history-date">
                            <?php echo date('M j, Y g:i A', strtotime($history['used_at'])); ?>
                        </div>
                    </div>
                    <div class="history-savings">
                        <?php 
                        if ($history['discount_type'] === 'percentage') {
                            $savings = ($history['total_amount'] * $history['discount_value']) / 100;
                            echo '-$' . number_format($savings, 2);
                        } else {
                            echo '-$' . number_format($history['discount_value'], 2);
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh vouchers every 60 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        // Check for new vouchers
        fetch('check_new_vouchers.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_vouchers > 0) {
                    // Show notification or refresh page
                    if (confirm('<?php echo __("new_vouchers_available"); ?>')) {
                        location.reload();
                    }
                }
            });
    }
}, 60000);

// Copy voucher code to clipboard
function copyVoucherCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('<?php echo __("voucher_code_copied"); ?>');
    });
}

// Add click handlers to voucher codes
document.querySelectorAll('.voucher-code').forEach(function(element) {
    element.style.cursor = 'pointer';
    element.addEventListener('click', function() {
        copyVoucherCode(this.textContent);
    });
});
</script>

<?php include 'footer.php'; ?> 