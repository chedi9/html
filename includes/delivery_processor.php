<?php
/**
 * Delivery Processor
 * Handles delivery calculations and order creation for different delivery companies
 */

require_once 'first_delivery_api.php';

class DeliveryProcessor {
    private $pdo;
    private $delivery_settings;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadDeliverySettings();
    }
    
    /**
     * Load delivery settings from database
     */
    private function loadDeliverySettings() {
        $this->delivery_settings = [];
        $stmt = $this->pdo->query("SELECT * FROM delivery_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['delivery_company'] . '_' . $row['setting_key'];
            $this->delivery_settings[$key] = $row['setting_value'];
        }
    }
    
    /**
     * Get available delivery options
     */
    public function getDeliveryOptions($pickup_address, $dropoff_address, $order_total = 0) {
        $options = [];
        
        // First Delivery is always available (default option)
        $base_cost = floatval($this->delivery_settings['first_delivery_base_cost'] ?? 7.00);
        $express_cost = floatval($this->delivery_settings['first_delivery_express_cost'] ?? 12.00);
        $free_threshold = floatval($this->delivery_settings['first_delivery_free_threshold'] ?? 105.00);
        
        // Calculate costs based on order total
        $standard_cost = $order_total >= $free_threshold ? 0 : $base_cost;
        $express_cost_final = $order_total >= $free_threshold ? 0 : $express_cost;
        
        $options['first_delivery_standard'] = [
            'name' => 'First Delivery - Standard',
            'description' => 'Fast and reliable delivery service',
            'estimated_time' => '30-60 minutes',
            'cost' => $standard_cost,
            'available' => true
        ];
        
        $options['first_delivery_express'] = [
            'name' => 'First Delivery - Express',
            'description' => 'Premium express delivery service',
            'estimated_time' => '15-30 minutes',
            'cost' => $express_cost_final,
            'available' => true
        ];
        
        // Add standard delivery option as backup
        $options['standard'] = [
            'name' => 'Standard Delivery',
            'description' => 'Standard delivery service',
            'estimated_time' => '2-3 business days',
            'cost' => $order_total >= $free_threshold ? 0 : 7.00,
            'available' => true
        ];
        
        return $options;
    }
    
    /**
     * Calculate First Delivery cost
     */
    private function calculateFirstDeliveryCost($pickup_address, $dropoff_address) {
        $base_cost = floatval($this->delivery_settings['first_delivery_base_cost'] ?? 5.00);
        $per_km_cost = floatval($this->delivery_settings['first_delivery_per_km_cost'] ?? 0.50);
        
        // Calculate distance (simplified - you might want to use a proper geocoding service)
        $distance_km = $this->calculateDistance($pickup_address, $dropoff_address);
        
        $total_cost = $base_cost + ($distance_km * $per_km_cost);
        return round($total_cost, 2);
    }
    
    /**
     * Calculate distance between two addresses (simplified)
     */
    private function calculateDistance($pickup_address, $dropoff_address) {
        // This is a simplified calculation
        // In a real implementation, you would use a geocoding service like Google Maps API
        // For now, we'll return a default distance
        return 10.0; // Default 10km
    }
    
    /**
     * Create delivery order
     */
    public function createDeliveryOrder($order_id, $delivery_company, $delivery_data) {
        try {
            switch ($delivery_company) {
                case 'first_delivery':
                case 'first_delivery_standard':
                case 'first_delivery_express':
                    return $this->createFirstDeliveryOrder($order_id, $delivery_data);
                    
                case 'standard':
                    return $this->createStandardDeliveryOrder($order_id, $delivery_data);
                    
                default:
                    throw new Exception("Unknown delivery company: $delivery_company");
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create First Delivery order
     */
    private function createFirstDeliveryOrder($order_id, $delivery_data) {
        // First Delivery is always available (default option)
        
        // Get order details
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        // Get order items
        $stmt = $this->pdo->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Initialize First Delivery API
        $api = new FirstDeliveryAPI(
            $this->delivery_settings['first_delivery_api_key'] ?? '',
            $this->delivery_settings['first_delivery_merchant_id'] ?? '',
            $this->delivery_settings['first_delivery_webhook_secret'] ?? '',
            $this->delivery_settings['first_delivery_mode'] ?? 'sandbox'
        );
        
        // Prepare order data for First Delivery
        $order_data = [
            'pickup_address' => $delivery_data['pickup_address'] ?? $order['shipping_address'],
            'dropoff_address' => $delivery_data['dropoff_address'] ?? $order['shipping_address'],
            'order_items' => array_map(function($item) {
                return [
                    'name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }, $order_items),
            'customer_info' => [
                'name' => $order['customer_name'],
                'email' => $order['customer_email'],
                'phone' => $order['customer_phone']
            ],
            'special_instructions' => $delivery_data['special_instructions'] ?? '',
            'auto_dispatch' => true
        ];
        
        // Create delivery order
        $result = $api->createDeliveryOrder($order_data);
        
        if ($result['status'] === 'success') {
            // Update order with delivery tracking information
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET delivery_company = ?, 
                    delivery_tracking_id = ?, 
                    delivery_status = ?, 
                    delivery_cost = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $delivery_cost = $this->calculateFirstDeliveryCost(
                $order_data['pickup_address'], 
                $order_data['dropoff_address']
            );
            
            $stmt->execute([
                'first_delivery',
                $result['tracking_id'],
                $result['status'],
                $delivery_cost,
                $order_id
            ]);
            
            return [
                'status' => 'success',
                'tracking_id' => $result['tracking_id'],
                'delivery_status' => $result['status'],
                'delivery_cost' => $delivery_cost
            ];
        } else {
            throw new Exception($result['message']);
        }
    }
    
    /**
     * Create standard delivery order
     */
    private function createStandardDeliveryOrder($order_id, $delivery_data) {
        // Update order with standard delivery information
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET delivery_company = ?, 
                delivery_status = ?, 
                delivery_cost = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            'standard',
            'pending',
            5.00, // Standard delivery cost
            $order_id
        ]);
        
        return [
            'status' => 'success',
            'tracking_id' => null,
            'delivery_status' => 'pending',
            'delivery_cost' => 5.00
        ];
    }
    
    /**
     * Get delivery status
     */
    public function getDeliveryStatus($order_id) {
        $stmt = $this->pdo->prepare("
            SELECT delivery_company, delivery_tracking_id, delivery_status, delivery_cost
            FROM orders 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        if ($order['delivery_company'] === 'first_delivery' && $order['delivery_tracking_id']) {
            // Get status from First Delivery API
            $api = new FirstDeliveryAPI(
                $this->delivery_settings['first_delivery_api_key'] ?? '',
                $this->delivery_settings['first_delivery_merchant_id'] ?? '',
                $this->delivery_settings['first_delivery_webhook_secret'] ?? '',
                $this->delivery_settings['first_delivery_mode'] ?? 'sandbox'
            );
            
            $result = $api->getOrderStatus($order['delivery_tracking_id']);
            
            if ($result['status'] === 'success') {
                return [
                    'status' => 'success',
                    'delivery_status' => $result['order_status'],
                    'tracking_info' => $result['tracking_info'],
                    'runner_info' => $result['runner_info'],
                    'estimated_delivery' => $result['estimated_delivery']
                ];
            } else {
                return $result;
            }
        } else {
            return [
                'status' => 'success',
                'delivery_status' => $order['delivery_status'],
                'tracking_info' => null,
                'runner_info' => null,
                'estimated_delivery' => null
            ];
        }
    }
    
    /**
     * Cancel delivery order
     */
    public function cancelDeliveryOrder($order_id) {
        $stmt = $this->pdo->prepare("
            SELECT delivery_company, delivery_tracking_id
            FROM orders 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        if ($order['delivery_company'] === 'first_delivery' && $order['delivery_tracking_id']) {
            // Cancel First Delivery order
            $api = new FirstDeliveryAPI(
                $this->delivery_settings['first_delivery_api_key'] ?? '',
                $this->delivery_settings['first_delivery_merchant_id'] ?? '',
                $this->delivery_settings['first_delivery_webhook_secret'] ?? '',
                $this->delivery_settings['first_delivery_mode'] ?? 'sandbox'
            );
            
            $result = $api->cancelOrder($order['delivery_tracking_id']);
            
            if ($result['status'] === 'success') {
                // Update order status
                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET delivery_status = 'cancelled', 
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$order_id]);
                
                return ['status' => 'success', 'message' => 'Delivery order cancelled successfully'];
            } else {
                return $result;
            }
        } else {
            // Update standard delivery order
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET delivery_status = 'cancelled', 
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$order_id]);
            
            return ['status' => 'success', 'message' => 'Delivery order cancelled successfully'];
        }
    }
    
    /**
     * Assign runner to delivery order
     */
    public function assignRunnerToOrder($order_id, $runner_id) {
        $stmt = $this->pdo->prepare("
            SELECT delivery_company, delivery_tracking_id
            FROM orders 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        if ($order['delivery_company'] === 'first_delivery' && $order['delivery_tracking_id']) {
            // Assign runner to First Delivery order
            $api = new FirstDeliveryAPI(
                $this->delivery_settings['first_delivery_api_key'] ?? '',
                $this->delivery_settings['first_delivery_merchant_id'] ?? '',
                $this->delivery_settings['first_delivery_webhook_secret'] ?? '',
                $this->delivery_settings['first_delivery_mode'] ?? 'sandbox'
            );
            
            $result = $api->assignRunnerToOrder($order['delivery_tracking_id'], $runner_id);
            
            if ($result['status'] === 'success') {
                // Update order status
                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET delivery_status = 'runner_assigned', 
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$order_id]);
                
                return ['status' => 'success', 'message' => 'Runner assigned to order successfully'];
            } else {
                return $result;
            }
        } else {
            return ['status' => 'error', 'message' => 'Order is not using First Delivery'];
        }
    }
    
    /**
     * Get available runners for territory
     */
    public function getAvailableRunners($territory_id) {
        $api = new FirstDeliveryAPI(
            $this->delivery_settings['first_delivery_api_key'] ?? '',
            $this->delivery_settings['first_delivery_merchant_id'] ?? '',
            $this->delivery_settings['first_delivery_webhook_secret'] ?? '',
            $this->delivery_settings['first_delivery_mode'] ?? 'sandbox'
        );
        
        return $api->getAvailableRunners($territory_id);
    }
    
    /**
     * Get runner details
     */
    public function getRunnerDetails($runner_id) {
        $api = new FirstDeliveryAPI(
            $this->delivery_settings['first_delivery_api_key'] ?? '',
            $this->delivery_settings['first_delivery_merchant_id'] ?? '',
            $this->delivery_settings['first_delivery_webhook_secret'] ?? '',
            $this->delivery_settings['first_delivery_mode'] ?? 'sandbox'
        );
        
        return $api->getRunner($runner_id);
    }
}
?> 