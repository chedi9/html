<?php
/**
 * Stripe Webhook Handler
 * Processes payment notifications from Stripe
 */

require_once '../db.php';
require_once '../payment_gateway_processor.php';

// Set content type for webhook
header('Content-Type: application/json');

// Get the raw POST data
$raw_data = file_get_contents('php://input');
$headers = getallheaders();

// Log webhook for debugging
error_log("Stripe Webhook received: " . $raw_data);

try {
    // Verify webhook signature (in production, implement proper signature verification)
    $webhook_secret = getStripeWebhookSecret();
    
    // Parse the webhook data
    $webhook_data = json_decode($raw_data, true);
    
    if (!$webhook_data) {
        throw new Exception('Invalid webhook data');
    }
    
    // Process different webhook events
    $event_type = $webhook_data['type'] ?? '';
    $data = $webhook_data['data']['object'] ?? [];
    
    $processor = new PaymentGatewayProcessor($pdo);
    
    switch ($event_type) {
        case 'payment_intent.succeeded':
            handlePaymentSucceeded($data, $processor);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentFailed($data, $processor);
            break;
            
        case 'charge.refunded':
            handlePaymentRefunded($data, $processor);
            break;
            
        case 'payment_intent.processing':
            handlePaymentProcessing($data, $processor);
            break;
            
        default:
            // Log unknown event type
            error_log("Unknown Stripe webhook event: " . $event_type);
            break;
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Stripe Webhook Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle successful payment
 */
function handlePaymentSucceeded($data, $processor) {
    $payment_intent_id = $data['id'] ?? '';
    $amount = $data['amount'] / 100; // Convert from cents
    $status = $data['status'] ?? '';
    
    // Find order by payment intent ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $payment_intent_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'success', [
            'stripe_payment_intent_id' => $payment_intent_id,
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
 * Handle failed payment
 */
function handlePaymentFailed($data, $processor) {
    $payment_intent_id = $data['id'] ?? '';
    $last_payment_error = $data['last_payment_error'] ?? [];
    $error_message = $last_payment_error['message'] ?? 'Payment failed';
    
    // Find order by payment intent ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $payment_intent_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'failed', [
            'stripe_payment_intent_id' => $payment_intent_id,
            'error' => $error_message
        ]);
        
        // Update order status
        $stmt = $processor->pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$payment_log['order_id']]);
    }
}

/**
 * Handle refunded payment
 */
function handlePaymentRefunded($data, $processor) {
    $charge_id = $data['id'] ?? '';
    $refund_amount = $data['amount_refunded'] / 100; // Convert from cents
    
    // Find order by charge ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $charge_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'refunded', [
            'stripe_charge_id' => $charge_id,
            'refund_amount' => $refund_amount
        ]);
        
        // Update order status
        $stmt = $processor->pdo->prepare("UPDATE orders SET status = 'refunded' WHERE id = ?");
        $stmt->execute([$payment_log['order_id']]);
    }
}

/**
 * Handle processing payment
 */
function handlePaymentProcessing($data, $processor) {
    $payment_intent_id = $data['id'] ?? '';
    
    // Find order by payment intent ID
    $stmt = $processor->pdo->prepare("SELECT order_id FROM payment_logs WHERE transaction_id LIKE ?");
    $stmt->execute(['%' . $payment_intent_id]);
    $payment_log = $stmt->fetch();
    
    if ($payment_log) {
        // Update payment status
        $processor->updatePaymentStatus($payment_log['transaction_id'], 'processing', [
            'stripe_payment_intent_id' => $payment_intent_id
        ]);
    }
}

/**
 * Get Stripe webhook secret from database
 */
function getStripeWebhookSecret() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_value FROM payment_settings WHERE gateway = 'stripe' AND setting_key = 'webhook_secret'");
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