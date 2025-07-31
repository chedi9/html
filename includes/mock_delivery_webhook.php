<?php
/**
 * Mock First Delivery Webhook System
 * Simulates real delivery status updates for testing purposes
 */

class MockDeliveryWebhook {
    private $pdo;
    private $delivery_settings;
    private $mock_runners = [
        [
            'id' => 'mock_runner_001',
            'name' => 'أحمد محمد',
            'phone' => '+216 50 123 456',
            'transport_type' => 'car',
            'rating' => 4.8
        ],
        [
            'id' => 'mock_runner_002', 
            'name' => 'علي بن علي',
            'phone' => '+216 51 234 567',
            'transport_type' => 'bike',
            'rating' => 4.6
        ],
        [
            'id' => 'mock_runner_003',
            'name' => 'محمد الزيتوني',
            'phone' => '+216 52 345 678',
            'transport_type' => 'car',
            'rating' => 4.9
        ]
    ];
    
    private $delivery_statuses = [
        'pending_assign' => 'في انتظار تعيين السائق',
        'pending_merchant' => 'في انتظار تأكيد التاجر',
        'runner_assigned' => 'تم تعيين السائق',
        'en_route_pickup' => 'السائق في الطريق للاستلام',
        'arrived_pickup' => 'السائق وصل للاستلام',
        'picked_up' => 'تم الاستلام',
        'en_route_dropoff' => 'السائق في الطريق للتوصيل',
        'arrived_dropoff' => 'السائق وصل للتوصيل',
        'completed' => 'تم التوصيل بنجاح',
        'cancelled' => 'تم إلغاء الطلب'
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadDeliverySettings();
    }
    
    private function loadDeliverySettings() {
        $this->delivery_settings = [];
        $stmt = $this->pdo->query("SELECT * FROM delivery_settings WHERE delivery_company = 'first_delivery'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->delivery_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    /**
     * Generate mock webhook payload
     */
    public function generateMockWebhook($order_id, $status, $runner_id = null) {
        $runner = $runner_id ? $this->getMockRunner($runner_id) : $this->getRandomMockRunner();
        
        $payload = [
            'type' => 'order_status_update',
            'order_id' => $order_id,
            'status' => $status,
            'tracking_id' => 'mock_track_' . $order_id,
            'timestamp' => date('c'),
            'runner_info' => $runner,
            'estimated_delivery' => $this->getEstimatedDeliveryTime($status),
            'location' => $this->getMockLocation($status),
            'notes' => $this->getMockNotes($status)
        ];
        
        return $payload;
    }
    
    /**
     * Process mock webhook (same as real webhook)
     */
    public function processMockWebhook($payload) {
        try {
            $order_id = $payload['order_id'] ?? null;
            $order_status = $payload['status'] ?? null;
            $tracking_id = $payload['tracking_id'] ?? null;
            $runner_info = $payload['runner_info'] ?? null;
            
            if ($order_id && $order_status) {
                // Update order status in database
                $stmt = $this->pdo->prepare("
                    UPDATE orders 
                    SET delivery_status = ?, 
                        delivery_tracking_id = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $stmt->execute([$order_status, $tracking_id, $order_id]);
                
                // Log webhook event
                $stmt = $this->pdo->prepare("
                    INSERT INTO delivery_webhook_logs 
                    (delivery_company, order_id, tracking_id, status, webhook_type, payload, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    'first_delivery',
                    $order_id,
                    $tracking_id,
                    $order_status,
                    'mock_webhook',
                    json_encode($payload)
                ]);
                
                // Send notifications based on status
                $this->sendMockNotifications($order_id, $order_status, $runner_info);
                
                return [
                    'status' => 'success',
                    'message' => 'Mock webhook processed successfully',
                    'order_status' => $order_status,
                    'runner_info' => $runner_info
                ];
            } else {
                return ['status' => 'error', 'message' => 'Missing order_id or status'];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Simulate delivery progress for an order
     */
    public function simulateDeliveryProgress($order_id, $interval_seconds = 30) {
        $statuses = [
            'pending_assign',
            'runner_assigned', 
            'en_route_pickup',
            'arrived_pickup',
            'picked_up',
            'en_route_dropoff',
            'arrived_dropoff',
            'completed'
        ];
        
        $current_status = $this->getCurrentOrderStatus($order_id);
        $current_index = array_search($current_status, $statuses);
        
        if ($current_index === false || $current_index >= count($statuses) - 1) {
            return ['status' => 'error', 'message' => 'Order already completed or not found'];
        }
        
        $next_status = $statuses[$current_index + 1];
        $payload = $this->generateMockWebhook($order_id, $next_status);
        
        return $this->processMockWebhook($payload);
    }
    
    /**
     * Get current order status
     */
    private function getCurrentOrderStatus($order_id) {
        $stmt = $this->pdo->prepare("SELECT delivery_status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['delivery_status'] : null;
    }
    
    /**
     * Get random mock runner
     */
    private function getRandomMockRunner() {
        return $this->mock_runners[array_rand($this->mock_runners)];
    }
    
    /**
     * Get specific mock runner
     */
    private function getMockRunner($runner_id) {
        foreach ($this->mock_runners as $runner) {
            if ($runner['id'] === $runner_id) {
                return $runner;
            }
        }
        return $this->getRandomMockRunner();
    }
    
    /**
     * Get estimated delivery time based on status
     */
    private function getEstimatedDeliveryTime($status) {
        $times = [
            'pending_assign' => '+15 minutes',
            'runner_assigned' => '+12 minutes', 
            'en_route_pickup' => '+10 minutes',
            'arrived_pickup' => '+8 minutes',
            'picked_up' => '+5 minutes',
            'en_route_dropoff' => '+3 minutes',
            'arrived_dropoff' => '+1 minute',
            'completed' => 'Delivered',
            'cancelled' => 'Cancelled'
        ];
        
        return $times[$status] ?? '+10 minutes';
    }
    
    /**
     * Get mock location based on status
     */
    private function getMockLocation($status) {
        $locations = [
            'pending_assign' => 'مركز التوزيع',
            'runner_assigned' => 'في الطريق للاستلام',
            'en_route_pickup' => 'في الطريق للاستلام',
            'arrived_pickup' => 'وصل للاستلام',
            'picked_up' => 'تم الاستلام',
            'en_route_dropoff' => 'في الطريق للتوصيل',
            'arrived_dropoff' => 'وصل للتوصيل',
            'completed' => 'تم التوصيل',
            'cancelled' => 'تم الإلغاء'
        ];
        
        return $locations[$status] ?? 'غير محدد';
    }
    
    /**
     * Get mock notes based on status
     */
    private function getMockNotes($status) {
        $notes = [
            'pending_assign' => 'جاري البحث عن سائق متاح',
            'runner_assigned' => 'تم تعيين السائق للطلب',
            'en_route_pickup' => 'السائق في الطريق لاستلام الطلب',
            'arrived_pickup' => 'السائق وصل لموقع الاستلام',
            'picked_up' => 'تم استلام الطلب بنجاح',
            'en_route_dropoff' => 'السائق في الطريق لتوصيل الطلب',
            'arrived_dropoff' => 'السائق وصل لموقع التوصيل',
            'completed' => 'تم توصيل الطلب بنجاح',
            'cancelled' => 'تم إلغاء الطلب'
        ];
        
        return $notes[$status] ?? 'تحديث حالة الطلب';
    }
    
    /**
     * Send mock notifications
     */
    private function sendMockNotifications($order_id, $status, $runner_info) {
        // Get order details
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.email, u.name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) return;
        
        // Log notification (in real system, this would send email/SMS)
        $notification_data = [
            'order_id' => $order_id,
            'customer_email' => $order['email'],
            'customer_name' => $order['name'],
            'status' => $status,
            'status_arabic' => $this->delivery_statuses[$status] ?? $status,
            'runner_name' => $runner_info['name'] ?? 'السائق',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Store notification in database for admin viewing
        $stmt = $this->pdo->prepare("
            INSERT INTO delivery_notifications 
            (order_id, customer_email, customer_name, status, status_arabic, runner_name, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        try {
            $stmt->execute([
                $notification_data['order_id'],
                $notification_data['customer_email'],
                $notification_data['customer_name'],
                $notification_data['status'],
                $notification_data['status_arabic'],
                $notification_data['runner_name']
            ]);
        } catch (Exception $e) {
            // Table might not exist, that's okay for testing
        }
    }
    
    /**
     * Get delivery status in Arabic
     */
    public function getStatusInArabic($status) {
        return $this->delivery_statuses[$status] ?? $status;
    }
    
    /**
     * Get all mock runners
     */
    public function getMockRunners() {
        return $this->mock_runners;
    }
    
    /**
     * Get all delivery statuses
     */
    public function getDeliveryStatuses() {
        return $this->delivery_statuses;
    }
}
?> 