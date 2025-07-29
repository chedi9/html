<?php
/**
 * PayPal Webhook Handler
 * Processes payment notifications from PayPal
 */

require_once '../db.php';
require_once '../payment_gateway_processor.php';

// Set content type for webhook
header('Content-Type: application/json');

// Get the raw POST data
$raw_data = file_get_contents('php://input');
$headers = getallheaders();

// Log webhook for debugging
error_log("PayPal Webhook received: " . $raw_data);

try {
    // Verify webhook signature (in production, implement proper signature verification)
    $webhook_secret = getPayPalWebhookSecret();
    
    // Parse the webhook data
    $webhook_data = json_decode($raw_data, true);
    
    if (!$webhook_data) {
        throw new Exception('Invalid webhook data');
    }
    
    // Process different webhook events
    $event_type = $webhook_data['event_type'] ?? '';
    $resource = $webhook_data['resource'] ?? [];
    
    $processor = new PaymentGatewayProcessor($pdo);
    
    switch ($event_type) {
        case 'PAYMENT.CAPTURE.COMPLETED':
            handlePaymentCompleted($resource, $processor);
            break;
            
        case 'PAYMENT.CAPTURE.DENIED':
            handlePaymentDenied($resource, $processor);
            break;
            
        case 'PAYMENT.CAPTURE.REFUNDED':
            handlePaymentRefunded($resource, $processor);
            break;
            
        case 'PAYMENT.CAPTURE.PENDING':
            handlePaymentPending($resource, $processor);
            break;
            
        default:
            // Log unknown event type
            error_log("Unknown PayPal webhook event: " . $event_type);
            break;
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("PayPal Webhook Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle completed payment
 */
function handlePaymentCompleted($resource, $processor) {
    $transaction_id = $resource['id'] ?? '';
    $amount = $resource['amount']['value'] ?? 0;
    $status = $resource['status'] ?? '';
    
    // Find order by transaction ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $transaction_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'success', [
            'paypal_payment_id' => $transaction_id,
            'amount' => $amount,
            'status' => $status
        ]);
        
        // Update order status
        $stmt = $processor->pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        $stmt->execute([$payment_log['order_id']]);
        
        // Send confirmation email
        sendPaymentConfirmationEmail($payment_log['order_id']);
    }
}

/**
 * Handle denied payment
 */
function handlePaymentDenied($resource, $processor) {
    $transaction_id = $resource['id'] ?? '';
    
    // Find order by transaction ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $transaction_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'failed', [
            'paypal_payment_id' => $transaction_id,
            'reason' => 'Payment denied by PayPal'
        ]);
        
        // Update order status
        $stmt = $processor->pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$payment_log['order_id']]);
    }
}

/**
 * Handle refunded payment
 */
function handlePaymentRefunded($resource, $processor) {
    $transaction_id = $resource['id'] ?? '';
    $refund_amount = $resource['amount']['value'] ?? 0;
    
    // Find order by transaction ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $transaction_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'refunded', [
            'paypal_payment_id' => $transaction_id,
            'refund_amount' => $refund_amount
        ]);
        
        // Update order status
        $stmt = $processor->pdo->prepare("UPDATE orders SET status = 'refunded' WHERE id = ?");
        $stmt->execute([$payment_log['order_id']]);
    }
}

/**
 * Handle pending payment
 */
function handlePaymentPending($resource, $processor) {
    $transaction_id = $resource['id'] ?? '';
    
    // Find order by transaction ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $transaction_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'pending', [
            'paypal_payment_id' => $transaction_id
        ]);
    }
}

/**
 * Get PayPal webhook secret from database
 */
function getPayPalWebhookSecret() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_value FROM payment_settings WHERE gateway = 'paypal' AND setting_key = 'webhook_secret'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['setting_value'] ?? '';
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($order_id) {
    // This would integrate with your existing email system
    // For now, just log the event
    error_log("Payment confirmation email should be sent for order: " . $order_id);
} 