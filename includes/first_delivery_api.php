<?php
/**
 * First Delivery API Integration
 * Handles all interactions with First Delivery API for delivery management
 */

class FirstDeliveryAPI {
    private $api_key;
    private $base_url;
    private $merchant_id;
    private $webhook_secret;
    private $mode;
    
    public function __construct($api_key, $merchant_id, $webhook_secret, $mode = 'sandbox') {
        $this->api_key = $api_key;
        $this->merchant_id = $merchant_id;
        $this->webhook_secret = $webhook_secret;
        $this->mode = $mode;
        
        // Set base URL based on mode
        $this->base_url = $mode === 'production' 
            ? 'https://api.firstdelivery.com/api/v3/'
            : 'https://sandbox.firstdelivery.com/api/v3/';
    }
    
    /**
     * Make API request with retry mechanism
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $retries = 3) {
        $url = $this->base_url . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $attempt = 0;
        while ($attempt < $retries) {
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            if ($error) {
                throw new Exception("cURL Error: " . $error);
            }
            
            // Check if we need to retry
            if (in_array($http_code, [408, 500, 502, 503]) && $attempt < $retries - 1) {
                $attempt++;
                $wait_time = pow(2, $attempt) * 1000; // Exponential backoff
                usleep($wait_time * 1000);
                continue;
            }
            
            break;
        }
        
        curl_close($ch);
        
        $response_data = json_decode($response, true);
        
        if ($http_code >= 400) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
            throw new Exception("API Error ($http_code): $error_message");
        }
        
        return $response_data;
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            $response = $this->makeRequest('territories');
            return ['status' => 'success', 'message' => 'Connection successful'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get delivery quote for an order
     */
    public function getDeliveryQuote($pickup_address, $dropoff_address, $order_details = []) {
        try {
            $data = [
                'pickup_address' => $pickup_address,
                'dropoff_address' => $dropoff_address,
                'order_details' => $order_details
            ];
            
            $response = $this->makeRequest('quotes', 'POST', $data);
            
            return [
                'status' => 'success',
                'quote' => $response['quote'] ?? null,
                'estimated_cost' => $response['estimated_cost'] ?? null,
                'estimated_time' => $response['estimated_time'] ?? null
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create delivery order
     */
    public function createDeliveryOrder($order_data) {
        try {
            $required_fields = ['pickup_address', 'dropoff_address', 'order_items', 'customer_info'];
            
            foreach ($required_fields as $field) {
                if (!isset($order_data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            $data = [
                'merchant_id' => $this->merchant_id,
                'pickup_address' => $order_data['pickup_address'],
                'dropoff_address' => $order_data['dropoff_address'],
                'order_items' => $order_data['order_items'],
                'customer_info' => $order_data['customer_info'],
                'special_instructions' => $order_data['special_instructions'] ?? '',
                'scheduled_time' => $order_data['scheduled_time'] ?? null,
                'auto_dispatch' => $order_data['auto_dispatch'] ?? true
            ];
            
            $response = $this->makeRequest('orders', 'POST', $data);
            
            return [
                'status' => 'success',
                'order_id' => $response['order']['id'] ?? null,
                'tracking_id' => $response['order']['tracking_id'] ?? null,
                'status' => $response['order']['status'] ?? 'pending_assign'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get order status
     */
    public function getOrderStatus($order_id) {
        try {
            $response = $this->makeRequest("orders/$order_id");
            
            return [
                'status' => 'success',
                'order_status' => $response['order']['status'] ?? null,
                'tracking_info' => $response['order']['tracking_info'] ?? null,
                'runner_info' => $response['order']['runner_info'] ?? null,
                'estimated_delivery' => $response['order']['estimated_delivery'] ?? null
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus($order_id, $status, $additional_data = []) {
        try {
            $data = array_merge(['status' => $status], $additional_data);
            $response = $this->makeRequest("orders/$order_id", 'PUT', $data);
            
            return [
                'status' => 'success',
                'message' => 'Order status updated successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder($order_id, $reason = '') {
        try {
            $data = ['reason' => $reason];
            $response = $this->makeRequest("orders/$order_id/cancel", 'POST', $data);
            
            return [
                'status' => 'success',
                'message' => 'Order cancelled successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get available territories
     */
    public function getTerritories() {
        try {
            $response = $this->makeRequest('territories');
            
            return [
                'status' => 'success',
                'territories' => $response['territories'] ?? []
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create territory
     */
    public function createTerritory($territory_data) {
        try {
            $required_fields = ['name', 'shortcode', 'zone', 'center'];
            
            foreach ($required_fields as $field) {
                if (!isset($territory_data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            $response = $this->makeRequest('territories', 'POST', $territory_data);
            
            return [
                'status' => 'success',
                'territory_id' => $response['territory']['id'] ?? null,
                'message' => 'Territory created successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature) {
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Process webhook
     */
    public function processWebhook($payload, $signature) {
        try {
            if (!$this->verifyWebhookSignature($payload, $signature)) {
                throw new Exception('Invalid webhook signature');
            }
            
            $data = json_decode($payload, true);
            
            if (!$data) {
                throw new Exception('Invalid JSON payload');
            }
            
            return [
                'status' => 'success',
                'webhook_type' => $data['type'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'order_status' => $data['status'] ?? null,
                'data' => $data
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Calculate delivery cost based on distance
     */
    public function calculateDeliveryCost($distance_km, $base_cost = 5.00, $per_km_cost = 0.50) {
        $total_cost = $base_cost + ($distance_km * $per_km_cost);
        return round($total_cost, 2);
    }
    
    /**
     * Get order tracking URL
     */
    public function getTrackingUrl($order_id) {
        $dashboard_url = $this->mode === 'production' 
            ? 'https://dispatch.firstdelivery.com'
            : 'https://dispatch-v3-sandbox.herokuapp.com';
        
        return $dashboard_url . "/orders/$order_id";
    }
    
    /**
     * Create runner (delivery driver)
     */
    public function createRunner($runner_data) {
        try {
            $required_fields = ['name', 'email', 'phone', 'address', 'territory_id'];
            
            foreach ($required_fields as $field) {
                if (!isset($runner_data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            $data = [
                'name' => $runner_data['name'],
                'email' => $runner_data['email'],
                'phone' => $runner_data['phone'],
                'address' => $runner_data['address'],
                'territory_id' => $runner_data['territory_id'],
                'transport_type' => $runner_data['transport_type'] ?? 'car',
                'profile_pic' => $runner_data['profile_pic'] ?? null
            ];
            
            $response = $this->makeRequest('runners', 'POST', $data);
            
            return [
                'status' => 'success',
                'runner_id' => $response['user']['id'] ?? null,
                'runner_name' => $response['user']['name'] ?? null,
                'message' => 'Runner created successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get all runners
     */
    public function getRunners() {
        try {
            $response = $this->makeRequest('runners');
            
            return [
                'status' => 'success',
                'runners' => $response['users'] ?? []
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get runner by ID
     */
    public function getRunner($runner_id) {
        try {
            $response = $this->makeRequest("runners/$runner_id");
            
            return [
                'status' => 'success',
                'runner' => $response['user'] ?? null
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update runner
     */
    public function updateRunner($runner_id, $runner_data) {
        try {
            $response = $this->makeRequest("runners/$runner_id", 'PUT', $runner_data);
            
            return [
                'status' => 'success',
                'message' => 'Runner updated successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Archive runner (deactivate)
     */
    public function archiveRunner($runner_id) {
        try {
            $data = ['archived' => true];
            $response = $this->makeRequest("runners/$runner_id", 'PUT', $data);
            
            return [
                'status' => 'success',
                'message' => 'Runner archived successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Assign runner to order
     */
    public function assignRunnerToOrder($order_id, $runner_id) {
        try {
            $data = [
                'status' => 'runner_assigned',
                'runner_id' => $runner_id
            ];
            
            $response = $this->makeRequest("orders/$order_id", 'PUT', $data);
            
            return [
                'status' => 'success',
                'message' => 'Runner assigned to order successfully'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get available runners for territory
     */
    public function getAvailableRunners($territory_id) {
        try {
            $response = $this->makeRequest("territories/$territory_id/runners");
            
            return [
                'status' => 'success',
                'runners' => $response['runners'] ?? []
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?> 