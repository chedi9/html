<?php
/**
 * First Delivery Webhook Handler
 * Processes delivery status updates from First Delivery
 */

require_once '../db.php';
require_once '../includes/first_delivery_api.php';

// Get delivery settings
$delivery_settings = [];
$stmt = $pdo->query("SELECT * FROM delivery_settings WHERE delivery_company = 'first_delivery'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $delivery_settings[$row['setting_key']] = $row['setting_value'];
}

// Check if First Delivery is enabled
if (empty($delivery_settings['enabled']) || $delivery_settings['enabled'] != '1') {
    http_response_code(404);
    exit('First Delivery not enabled');
}

// Initialize First Delivery API
$api = new FirstDeliveryAPI(
    $delivery_settings['api_key'] ?? '',
    $delivery_settings['merchant_id'] ?? '',
    $delivery_settings['webhook_secret'] ?? '',
    $delivery_settings['mode'] ?? 'sandbox'
);

// Get webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_FIRST_SIGNATURE'] ?? '';

// Process webhook
$result = $api->processWebhook($payload, $signature);

if ($result['status'] === 'success') {
    $webhook_data = $result['data'];
    $order_id = $webhook_data['order_id'] ?? null;
    $order_status = $webhook_data['status'] ?? null;
    $webhook_type = $webhook_data['type'] ?? null;
    
    if ($order_id && $order_status) {
        // Update order status in database
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET delivery_status = ?, 
                delivery_tracking_id = ?, 
                updated_at = NOW() 
            WHERE delivery_tracking_id = ?
        ");
        
        $tracking_id = $webhook_data['tracking_id'] ?? $order_id;
        $stmt->execute([$order_status, $tracking_id, $tracking_id]);
        
        // Log webhook event
        $stmt = $pdo->prepare("
            INSERT INTO delivery_webhook_logs 
            (delivery_company, order_id, tracking_id, status, webhook_type, payload, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'first_delivery',
            $order_id,
            $tracking_id,
            $order_status,
            $webhook_type,
            $payload
        ]);
        
        // Send notification to customer if status is completed
        if ($order_status === 'completed') {
            // Get order details
            $stmt = $pdo->prepare("
                SELECT o.*, u.email, u.name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.delivery_tracking_id = ?
            ");
            $stmt->execute([$tracking_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order && $order['email']) {
                // Send delivery completion email
                $subject = "Your order has been delivered!";
                $message = "
                    Dear {$order['name']},
                    
                    Your order #{$order['id']} has been successfully delivered!
                    
                    Thank you for choosing our service.
                    
                    Best regards,
                    WeBuy Team
                ";
                
                // You can use your existing mailer here
                // mail($order['email'], $subject, $message);
            }
        }
        
        // Send notification to customer if runner is assigned
        if ($order_status === 'runner_assigned') {
            $runner_info = $webhook_data['runner_info'] ?? null;
            if ($runner_info) {
                // Get order details
                $stmt = $pdo->prepare("
                    SELECT o.*, u.email, u.name 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.delivery_tracking_id = ?
                ");
                $stmt->execute([$tracking_id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order && $order['email']) {
                    $runner_name = $runner_info['name'] ?? 'Your delivery partner';
                    $estimated_delivery = $webhook_data['estimated_delivery'] ?? '';
                    
                    $subject = "Your order is on the way!";
                    $message = "
                        Dear {$order['name']},
                        
                        Great news! Your order #{$order['id']} has been assigned to {$runner_name}.
                        
                        Estimated delivery time: {$estimated_delivery}
                        
                        You can track your order in real-time through your account.
                        
                        Best regards,
                        WeBuy Team
                    ";
                    
                    // You can use your existing mailer here
                    // mail($order['email'], $subject, $message);
                }
            }
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing order_id or status']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $result['message']]);
}
?> 