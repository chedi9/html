<?php
session_start();
require_once 'db.php';
require_once 'lang.php';
require_once 'pci_compliant_payment_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: client/login.php');
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

<style>
.confirmation-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.confirmation-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.confirmation-header h1 {
    margin: 0;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.confirmation-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.order-details {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.order-details h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.detail-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
}

.detail-item .label {
    font-weight: bold;
    color: #666;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.detail-item .value {
    color: #333;
    font-size: 1.1em;
}

.order-items {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.order-items h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}

.item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f8f9fa;
}

.item:last-child {
    border-bottom: none;
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.item-seller {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.item-price {
    font-weight: bold;
    color: #28a745;
}

.item-quantity {
    color: #666;
    font-size: 0.9em;
}

.order-summary {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.order-summary h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.5em;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
}

.summary-row:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 1.2em;
    color: #28a745;
}

.actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.action-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
    text-decoration: none;
    display: inline-block;
}

.action-btn:hover {
    background: #0056b3;
}

.action-btn.success {
    background: #28a745;
}

.action-btn.success:hover {
    background: #218838;
}

.action-btn.secondary {
    background: #6c757d;
}

.action-btn.secondary:hover {
    background: #545b62;
}

@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .item {
        flex-direction: column;
        text-align: center;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        text-align: center;
    }
}
</style>

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
        <div class="payment-details" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
            <h4 style="margin: 0 0 15px 0; color: #495057;">💳 <?php echo __('payment_details'); ?></h4>
            
            <?php if ($order['payment_method'] === 'card'): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
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
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
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
                
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404;">
                    <strong>ℹ️ <?php echo __('note'); ?>:</strong> <?php echo __('payment_link_will_be_sent'); ?>
                </div>
                
            <?php elseif ($order['payment_method'] === 'flouci'): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
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
                
                <div style="margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 5px; color: #155724;">
                    <strong>🟢 Flouci:</strong> تطبيق الدفع الرقمي الأسرع نمواً في تونس. سيتم إرسال رابط الدفع إلى رقم الهاتف والبريد الإلكتروني المحددين.
                </div>
                
            <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
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
                
                <div style="margin-top: 15px; padding: 10px; background: #d1ecf1; border-radius: 5px; color: #0c5460;">
                    <strong>💡 <?php echo __('tip'); ?>:</strong> <?php echo __('account_details_will_be_sent'); ?>
                </div>
                
            <?php elseif ($order['payment_method'] === 'cod'): ?>
                <div style="padding: 10px; background: #d4edda; border-radius: 5px; color: #155724;">
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