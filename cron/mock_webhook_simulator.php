<?php
/**
 * Mock Webhook Simulator - Cron Job
 * Automatically simulates delivery status updates for testing
 * Run this script every 5-10 minutes for realistic delivery simulation
 */

// Set unlimited execution time for cron jobs
set_time_limit(0);
ini_set('memory_limit', '256M');

// Include required files
require_once '../db.php';
require_once '../includes/mock_delivery_webhook.php';

// Initialize mock webhook
$mock_webhook = new MockDeliveryWebhook($pdo);

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
    
    // Also write to log file
    $log_file = __DIR__ . '/mock_webhook_simulator.log';
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

logMessage("Starting Mock Webhook Simulator...");

try {
    // Get pending orders that need status updates
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.delivery_company = 'first_delivery' 
        AND o.delivery_status NOT IN ('completed', 'cancelled')
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY o.created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($pending_orders) . " pending orders to process");
    
    if (empty($pending_orders)) {
        logMessage("No pending orders found. Exiting.");
        exit(0);
    }
    
    // Process each order
    foreach ($pending_orders as $order) {
        $order_id = $order['id'];
        $current_status = $order['delivery_status'] ?? 'pending_assign';
        
        logMessage("Processing order #$order_id (current status: $current_status)");
        
        // Determine next status based on current status and time elapsed
        $next_status = determineNextStatus($current_status, $order['created_at']);
        
        if ($next_status && $next_status !== $current_status) {
            // Generate and process mock webhook
            $payload = $mock_webhook->generateMockWebhook($order_id, $next_status);
            $result = $mock_webhook->processMockWebhook($payload);
            
            if ($result['status'] === 'success') {
                logMessage("Order #$order_id status updated: $current_status -> $next_status");
            } else {
                logMessage("Failed to update order #$order_id: " . $result['message']);
            }
        } else {
            logMessage("Order #$order_id status unchanged (current: $current_status)");
        }
        
        // Small delay between orders
        usleep(500000); // 0.5 seconds
    }
    
    logMessage("Mock Webhook Simulator completed successfully");
    
} catch (Exception $e) {
    logMessage("Error in Mock Webhook Simulator: " . $e->getMessage());
    exit(1);
}

/**
 * Determine the next status based on current status and time elapsed
 */
function determineNextStatus($current_status, $created_at) {
    $time_elapsed = time() - strtotime($created_at);
    $minutes_elapsed = $time_elapsed / 60;
    
    // Status progression with realistic timing
    $status_progression = [
        'pending_assign' => [
            'next' => 'runner_assigned',
            'min_minutes' => 2,
            'max_minutes' => 5
        ],
        'runner_assigned' => [
            'next' => 'en_route_pickup',
            'min_minutes' => 3,
            'max_minutes' => 8
        ],
        'en_route_pickup' => [
            'next' => 'arrived_pickup',
            'min_minutes' => 5,
            'max_minutes' => 12
        ],
        'arrived_pickup' => [
            'next' => 'picked_up',
            'min_minutes' => 2,
            'max_minutes' => 5
        ],
        'picked_up' => [
            'next' => 'en_route_dropoff',
            'min_minutes' => 3,
            'max_minutes' => 8
        ],
        'en_route_dropoff' => [
            'next' => 'arrived_dropoff',
            'min_minutes' => 5,
            'max_minutes' => 15
        ],
        'arrived_dropoff' => [
            'next' => 'completed',
            'min_minutes' => 1,
            'max_minutes' => 3
        ]
    ];
    
    if (!isset($status_progression[$current_status])) {
        return null;
    }
    
    $progression = $status_progression[$current_status];
    $min_minutes = $progression['min_minutes'];
    $max_minutes = $progression['max_minutes'];
    
    // Add some randomness to make it more realistic
    $random_factor = rand(0, 100) / 100;
    $required_minutes = $min_minutes + ($max_minutes - $min_minutes) * $random_factor;
    
    if ($minutes_elapsed >= $required_minutes) {
        return $progression['next'];
    }
    
    return null;
}

/**
 * Optional: Add some random events for more realistic simulation
 */
function addRandomEvents($mock_webhook, $pdo) {
    // 5% chance to cancel an order
    if (rand(1, 100) <= 5) {
        $stmt = $pdo->prepare("
            SELECT id FROM orders 
            WHERE delivery_company = 'first_delivery' 
            AND delivery_status IN ('pending_assign', 'runner_assigned')
            ORDER BY RAND() 
            LIMIT 1
        ");
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $payload = $mock_webhook->generateMockWebhook($order['id'], 'cancelled');
            $mock_webhook->processMockWebhook($payload);
            logMessage("Random event: Order #{$order['id']} cancelled");
        }
    }
}

// Run random events occasionally
if (rand(1, 100) <= 10) { // 10% chance
    addRandomEvents($mock_webhook, $pdo);
}

logMessage("Mock Webhook Simulator finished");
?> 