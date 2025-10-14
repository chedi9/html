<?php
session_start();
require_once 'db.php';
require_once 'lang.php';
require_once 'pci_compliant_payment_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: client/orders.php');
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Parse payment details if available (PCI compliant)
$payment_details = [];
$pci_payment_handler = new PCICompliantPaymentHandler($pdo);

if (!empty($order['payment_details'])) {
    // Check if this is PCI compliant data (encrypted)
    if ($order['pci_compliant'] == 1) {
        try {
            $payment_details = $pci_payment_handler->getPaymentDisplayData($order['payment_details']);
        } catch (Exception $e) {
            // Fallback to old format
            $payment_details = json_decode($order['payment_details'], true) ?: [];
        }
    } else {
        // Old format (non-PCI compliant)
        $payment_details = json_decode($order['payment_details'], true) ?: [];
    }
}

if (!$order) {
    header('Location: client/orders.php');
    exit();
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image, 
           COALESCE(s.store_name, 'Unknown Seller') as seller_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate missing values
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping_cost = $order['shipping_cost'] ?? 0;
$tax_amount = $order['tax_amount'] ?? 0;
$total_amount = $subtotal + $shipping_cost + $tax_amount;

// Set default values for missing columns
$order['subtotal'] = $subtotal;
$order['shipping_cost'] = $shipping_cost;
$order['tax_amount'] = $tax_amount;
$order['total_amount'] = $total_amount;
$order['billing_address'] = $order['billing_address'] ?? $order['shipping_address'] ?? 'Not specified';

include 'header.php';
?>



<div class="confirmation-container">
    <div class="confirmation-header">
        <h1>✅ <?php echo __('order_confirmed'); ?></h1>
        <p><?php echo __('thank_you_for_your_order'); ?></p>
    </div>

    <!-- Order Details -->
    <div class="order-details">
        <h3>📋 <?php echo __('order_details'); ?></h3>
        <div class="details-grid">
            <div class="detail-item">
                <div class="label"><?php echo __('order_number'); ?></div>
                <div class="value">#<?php echo $order['id']; ?></div>
            </div>
            <div class="detail-item">
                <div class="label"><?php echo __('customer_name'); ?></div>
                <div class="value"><?php echo htmlspecialchars($order['name']); ?></div>
            </div>
            <div class="detail-item">
                <div class="label"><?php echo __('order_date'); ?></div>
                <div class="value"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
            </div>
            <div class="detail-item">
                <div class="label"><?php echo __('order_status'); ?></div>
                <div class="value"><?php echo ucfirst($order['status']); ?></div>
            </div>
            <div class="detail-item">
                <div class="label"><?php echo __('payment_method'); ?></div>
                <div class="value">
                    <?php 
                    switch ($order['payment_method']) {
                        case 'card': echo '💳 ' . __('payment_method_card'); break;
                        case 'd17': echo '📱 D17'; break;
                        case 'bank_transfer': echo '🏦 ' . __('payment_method_bank_transfer'); break;
                        case 'cod': echo '💰 ' . __('payment_method_cod'); break;
                        default: echo ucfirst($order['payment_method']);
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Details Section -->
        <?php if (!empty($payment_details)): ?>
        <div class="payment-details">
            <h4>💳 <?php echo __('payment_details'); ?></h4>
            
            <?php if ($order['payment_method'] === 'card'): ?>
                <div>
                    <?php if (!empty($payment_details['card_holder'])): ?>
                    <div>
                        <strong><?php echo __('card_holder_name'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['card_holder']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['card_number'])): ?>
                    <div>
                        <strong><?php echo __('card_number'); ?>:</strong><br>
                        **** **** **** <?php echo htmlspecialchars($payment_details['card_number']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['card_type'])): ?>
                    <div>
                        <strong><?php echo __('card_type'); ?>:</strong><br>
                        <?php echo htmlspecialchars(ucfirst($payment_details['card_type'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['expiry_month']) && !empty($payment_details['expiry_year'])): ?>
                    <div>
                        <strong><?php echo __('expiry_date'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['expiry_month']); ?>/<?php echo htmlspecialchars($payment_details['expiry_year']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($order['payment_method'] === 'd17'): ?>
                <div>
                    <?php if (!empty($payment_details['d17_phone'])): ?>
                    <div>
                        <strong><?php echo __('phone_number'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['d17_phone']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['d17_email'])): ?>
                    <div>
                        <strong><?php echo __('email'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['d17_email']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <strong>ℹ️ <?php echo __('note'); ?>:</strong> <?php echo __('payment_link_will_be_sent'); ?>
                </div>
                
            <?php elseif ($order['payment_method'] === 'flouci'): ?>
                <div>
                    <?php if (!empty($payment_details['flouci_phone'])): ?>
                    <div>
                        <strong>رقم الهاتف:</strong><br>
                        <?php echo htmlspecialchars($payment_details['flouci_phone']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['flouci_email'])): ?>
                    <div>
                        <strong>البريد الإلكتروني:</strong><br>
                        <?php echo htmlspecialchars($payment_details['flouci_email']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['flouci_account_type'])): ?>
                    <div>
                        <strong>نوع الحساب:</strong><br>
                        <?php 
                        $account_type = $payment_details['flouci_account_type'];
                        echo htmlspecialchars($account_type === 'personal' ? 'حساب شخصي' : 'حساب تجاري'); 
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <strong>🟢 Flouci:</strong> تطبيق الدفع الرقمي الأسرع نمواً في تونس. سيتم إرسال رابط الدفع إلى رقم الهاتف والبريد الإلكتروني المحددين.
                </div>
                
            <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                <div>
                    <?php if (!empty($payment_details['bank_name'])): ?>
                    <div>
                        <strong><?php echo __('bank_name'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['bank_name']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['account_holder'])): ?>
                    <div>
                        <strong><?php echo __('account_holder'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['account_holder']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($payment_details['reference_number'])): ?>
                    <div>
                        <strong><?php echo __('reference_number'); ?>:</strong><br>
                        <?php echo htmlspecialchars($payment_details['reference_number']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <strong>💡 <?php echo __('tip'); ?>:</strong> <?php echo __('account_details_will_be_sent'); ?>
                </div>
                
            <?php elseif ($order['payment_method'] === 'cod'): ?>
                <div>
                    <strong>💰 <?php echo __('payment_method_cod'); ?>:</strong> <?php echo __('payment_will_be_on_delivery'); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="details-grid">
            <div class="detail-item">
                <div class="label"><?php echo __('shipping_address'); ?></div>
                <div class="value"><?php echo htmlspecialchars($order['address']); ?></div>
            </div>
            <div class="detail-item">
                <div class="label"><?php echo __('billing_address'); ?></div>
                <div class="value"><?php echo htmlspecialchars($order['address']); ?></div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="order-items">
        <h3>🛍️ <?php echo __('order_items'); ?></h3>
        <?php foreach ($order_items as $item): ?>
            <div class="item">
                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                <div class="item-details">
                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="item-seller"><?php echo __('sold_by'); ?>: <?php echo htmlspecialchars($item['seller_name']); ?></div>
                    <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                    <div class="item-quantity"><?php echo __('quantity'); ?>: <?php echo $item['quantity']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Order Summary -->
    <div class="order-summary">
        <h3>💰 <?php echo __('order_summary'); ?></h3>
        <div class="summary-row">
            <span><?php echo __('subtotal'); ?></span>
            <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
        </div>
        <div class="summary-row">
            <span><?php echo __('shipping'); ?></span>
            <span>$<?php echo number_format($order['shipping_cost'], 2); ?></span>
        </div>
        <div class="summary-row">
            <span><?php echo __('tax'); ?></span>
            <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
        </div>
        <div class="summary-row">
            <span><?php echo __('total'); ?></span>
            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
    </div>

    <!-- Actions -->
    <div class="actions">
        <a href="client/orders.php" class="action-btn secondary"><?php echo __('view_all_orders'); ?></a>
        <a href="client/orders.php?order_id=<?php echo $order_id; ?>" class="action-btn"><?php echo __('track_order'); ?></a>
        <a href="index.php" class="action-btn success"><?php echo __('continue_shopping'); ?></a>
    </div>
</div>

<?php include 'footer.php'; ?> 