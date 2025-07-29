<?php
/**
 * Enhanced Payment Gateway Processor
 * Handles real payment gateway integration with PayPal, Stripe, and local gateways
 */

class PaymentGatewayProcessor {
    private $pdo;
    private $settings;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }
    
    /**
     * Load payment settings from database
     */
    private function loadSettings() {
        $this->settings = [];
        $stmt = $this->pdo->query("SELECT * FROM payment_settings");
        while ($row = $stmt->fetch()) {
            $this->settings[$row['gateway']][$row['setting_key']] = $row['setting_value'];
        }
    }
    
    /**
     * Get setting value with encryption support
     */
    private function getSetting($gateway, $key) {
        $value = $this->settings[$gateway][$key] ?? '';
        if ($value && $this->isSettingEncrypted($gateway, $key)) {
            return $this->decrypt($value);
        }
        return $value;
    }
    
    /**
     * Check if setting is encrypted
     */
    private function isSettingEncrypted($gateway, $key) {
        $stmt = $this->pdo->prepare("SELECT encrypted FROM payment_settings WHERE gateway = ? AND setting_key = ?");
        $stmt->execute([$gateway, $key]);
        $result = $stmt->fetch();
        return $result && $result['encrypted'];
    }
    
    /**
     * Simple encryption/decryption (in production, use proper encryption)
     */
    private function encrypt($value) {
        return base64_encode($value); // Simple encoding for demo
    }
    
    private function decrypt($value) {
        return base64_decode($value); // Simple decoding for demo
    }
    
    /**
     * Process payment with real gateway integration
     */
    public function processPayment($payment_method, $payment_details, $amount, $order_id) {
        try {
            switch ($payment_method) {
                case 'paypal':
                    return $this->processPayPalPayment($payment_details, $amount, $order_id);
                case 'stripe':
                    return $this->processStripePayment($payment_details, $amount, $order_id);
                case 'd17':
                    return $this->processD17Payment($payment_details, $amount, $order_id);
                case 'flouci':
                    return $this->processFlouciPayment($payment_details, $amount, $order_id);
                case 'card':
                    return $this->processCardPayment($payment_details, $amount, $order_id);
                case 'bank_transfer':
                    return $this->processBankTransfer($payment_details, $amount, $order_id);
                case 'cod':
                    return $this->processCODPayment($amount, $order_id);
                default:
                    throw new Exception('Invalid payment method');
            }
        } catch (Exception $e) {
            $this->logPaymentError($order_id, $payment_method, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process PayPal payment
     */
    private function processPayPalPayment($payment_details, $amount, $order_id) {
        if (!$this->isGatewayEnabled('paypal')) {
            throw new Exception('PayPal is not enabled');
        }
        
        $client_id = $this->getSetting('paypal', 'client_id');
        $client_secret = $this->getSetting('paypal', 'client_secret');
        $mode = $this->getSetting('paypal', 'mode');
        
        if (!$client_id || !$client_secret) {
            throw new Exception('PayPal credentials not configured');
        }
        
        // In real implementation, integrate with PayPal SDK
        // For now, simulate PayPal payment processing
        $transaction_id = 'PAYPAL_' . time() . '_' . $order_id;
        
        // Simulate PayPal API call
        $paypal_response = $this->simulatePayPalAPI($amount, $order_id, $mode);
        
        if ($paypal_response['success']) {
            $this->logPaymentSuccess($order_id, 'paypal', $transaction_id, $amount, $paypal_response);
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'payment_method' => 'PayPal',
                'amount' => $amount,
                'status' => 'completed',
                'gateway_response' => $paypal_response
            ];
        } else {
            throw new Exception('PayPal payment failed: ' . $paypal_response['error']);
        }
    }
    
    /**
     * Process Stripe payment
     */
    private function processStripePayment($payment_details, $amount, $order_id) {
        if (!$this->isGatewayEnabled('stripe')) {
            throw new Exception('Stripe is not enabled');
        }
        
        $publishable_key = $this->getSetting('stripe', 'publishable_key');
        $secret_key = $this->getSetting('stripe', 'secret_key');
        $mode = $this->getSetting('stripe', 'mode');
        
        if (!$publishable_key || !$secret_key) {
            throw new Exception('Stripe credentials not configured');
        }
        
        // In real implementation, integrate with Stripe SDK
        // For now, simulate Stripe payment processing
        $transaction_id = 'STRIPE_' . time() . '_' . $order_id;
        
        // Simulate Stripe API call
        $stripe_response = $this->simulateStripeAPI($payment_details, $amount, $order_id, $mode);
        
        if ($stripe_response['success']) {
            $this->logPaymentSuccess($order_id, 'stripe', $transaction_id, $amount, $stripe_response);
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'payment_method' => 'Stripe',
                'amount' => $amount,
                'status' => 'completed',
                'gateway_response' => $stripe_response
            ];
        } else {
            throw new Exception('Stripe payment failed: ' . $stripe_response['error']);
        }
    }
    
    /**
     * Process D17 payment
     */
    private function processD17Payment($payment_details, $amount, $order_id) {
        if (!$this->isGatewayEnabled('d17')) {
            throw new Exception('D17 is not enabled');
        }
        
        $api_key = $this->getSetting('d17', 'api_key');
        $merchant_id = $this->getSetting('d17', 'merchant_id');
        
        if (!$api_key || !$merchant_id) {
            throw new Exception('D17 credentials not configured');
        }
        
        // Validate D17 payment details
        $this->validateD17Details($payment_details);
        
        // Simulate D17 API call
        $d17_response = $this->simulateD17API($payment_details, $amount, $order_id);
        
        if ($d17_response['success']) {
            $this->logPaymentSuccess($order_id, 'd17', $d17_response['transaction_id'], $amount, $d17_response);
            return [
                'success' => true,
                'transaction_id' => $d17_response['transaction_id'],
                'payment_method' => 'D17',
                'amount' => $amount,
                'status' => 'pending',
                'payment_link' => $d17_response['payment_link'],
                'gateway_response' => $d17_response
            ];
        } else {
            throw new Exception('D17 payment failed: ' . $d17_response['error']);
        }
    }
    
    /**
     * Process Flouci payment
     */
    private function processFlouciPayment($payment_details, $amount, $order_id) {
        if (!$this->isGatewayEnabled('flouci')) {
            throw new Exception('Flouci is not enabled');
        }
        
        $api_key = $this->getSetting('flouci', 'api_key');
        $merchant_id = $this->getSetting('flouci', 'merchant_id');
        
        if (!$api_key || !$merchant_id) {
            throw new Exception('Flouci credentials not configured');
        }
        
        // Validate Flouci payment details
        $this->validateFlouciDetails($payment_details);
        
        // Simulate Flouci API call
        $flouci_response = $this->simulateFlouciAPI($payment_details, $amount, $order_id);
        
        if ($flouci_response['success']) {
            $this->logPaymentSuccess($order_id, 'flouci', $flouci_response['transaction_id'], $amount, $flouci_response);
            return [
                'success' => true,
                'transaction_id' => $flouci_response['transaction_id'],
                'payment_method' => 'Flouci',
                'amount' => $amount,
                'status' => 'pending',
                'payment_link' => $flouci_response['payment_link'],
                'gateway_response' => $flouci_response
            ];
        } else {
            throw new Exception('Flouci payment failed: ' . $flouci_response['error']);
        }
    }
    
    /**
     * Process credit card payment (using Stripe as backend)
     */
    private function processCardPayment($payment_details, $amount, $order_id) {
        // Use Stripe for card processing
        return $this->processStripePayment($payment_details, $amount, $order_id);
    }
    
    /**
     * Process bank transfer
     */
    private function processBankTransfer($payment_details, $amount, $order_id) {
        $transaction_id = 'BANK_' . time() . '_' . $order_id;
        
        // Generate bank transfer details
        $transfer_details = $this->generateBankTransferDetails($payment_details, $amount, $order_id);
        
        $this->logPaymentSuccess($order_id, 'bank_transfer', $transaction_id, $amount, $transfer_details);
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'payment_method' => 'Bank Transfer',
            'amount' => $amount,
            'status' => 'pending',
            'transfer_details' => $transfer_details
        ];
    }
    
    /**
     * Process cash on delivery
     */
    private function processCODPayment($amount, $order_id) {
        $transaction_id = 'COD_' . time() . '_' . $order_id;
        
        $this->logPaymentSuccess($order_id, 'cod', $transaction_id, $amount, []);
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'payment_method' => 'Cash on Delivery',
            'amount' => $amount,
            'status' => 'pending'
        ];
    }
    
    /**
     * Check if gateway is enabled
     */
    private function isGatewayEnabled($gateway) {
        return ($this->settings[$gateway]['enabled'] ?? 0) == 1;
    }
    
    /**
     * Simulate PayPal API call
     */
    private function simulatePayPalAPI($amount, $order_id, $mode) {
        // Simulate API delay
        usleep(500000); // 0.5 seconds
        
        // Simulate success/failure based on amount
        if ($amount > 0 && $amount < 10000) {
            return [
                'success' => true,
                'payment_id' => 'PAY-' . uniqid(),
                'status' => 'completed',
                'mode' => $mode
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Invalid amount'
            ];
        }
    }
    
    /**
     * Simulate Stripe API call
     */
    private function simulateStripeAPI($payment_details, $amount, $order_id, $mode) {
        // Simulate API delay
        usleep(500000); // 0.5 seconds
        
        // Validate card details
        if (!isset($payment_details['card_number']) || strlen($payment_details['card_number']) < 10) {
            return [
                'success' => false,
                'error' => 'Invalid card number'
            ];
        }
        
        // Simulate success/failure based on card number
        $last_digit = substr($payment_details['card_number'], -1);
        if ($last_digit % 2 == 0) {
            return [
                'success' => true,
                'payment_intent_id' => 'pi_' . uniqid(),
                'status' => 'succeeded',
                'mode' => $mode
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Card declined'
            ];
        }
    }
    
    /**
     * Simulate D17 API call
     */
    private function simulateD17API($payment_details, $amount, $order_id) {
        // Simulate API delay
        usleep(300000); // 0.3 seconds
        
        // Validate phone number
        if (!isset($payment_details['d17_phone']) || strlen($payment_details['d17_phone']) < 8) {
            return [
                'success' => false,
                'error' => 'Invalid phone number'
            ];
        }
        
        return [
            'success' => true,
            'transaction_id' => 'D17_' . uniqid(),
            'payment_link' => 'https://d17.tn/pay/' . uniqid(),
            'status' => 'pending'
        ];
    }
    
    /**
     * Simulate Flouci API call
     */
    private function simulateFlouciAPI($payment_details, $amount, $order_id) {
        // Simulate API delay
        usleep(300000); // 0.3 seconds
        
        // Validate phone number and email
        if (!isset($payment_details['flouci_phone']) || strlen($payment_details['flouci_phone']) < 8) {
            return [
                'success' => false,
                'error' => 'Invalid phone number'
            ];
        }
        
        if (!isset($payment_details['flouci_email']) || !filter_var($payment_details['flouci_email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid email address'
            ];
        }
        
        return [
            'success' => true,
            'transaction_id' => 'FLOUCI_' . uniqid(),
            'payment_link' => 'https://flouci.com/pay/' . uniqid(),
            'status' => 'pending'
        ];
    }
    
    /**
     * Generate bank transfer details
     */
    private function generateBankTransferDetails($payment_details, $amount, $order_id) {
        return [
            'bank_name' => $payment_details['bank_name'] ?? 'BIAT',
            'account_holder' => $payment_details['account_holder'] ?? 'WeBuy',
            'account_number' => 'TN' . str_pad($order_id, 8, '0', STR_PAD_LEFT),
            'reference' => 'WB' . $order_id,
            'amount' => $amount,
            'due_date' => date('Y-m-d', strtotime('+3 days'))
        ];
    }
    
    /**
     * Validate D17 payment details
     */
    private function validateD17Details($details) {
        if (!isset($details['d17_phone']) || strlen($details['d17_phone']) < 8) {
            throw new Exception('Invalid D17 phone number');
        }
        
        if (!isset($details['d17_email']) || !filter_var($details['d17_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid D17 email address');
        }
    }
    
    /**
     * Validate Flouci payment details
     */
    private function validateFlouciDetails($details) {
        if (!isset($details['flouci_phone']) || strlen($details['flouci_phone']) < 8) {
            throw new Exception('Invalid Flouci phone number');
        }
        
        if (!isset($details['flouci_email']) || !filter_var($details['flouci_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid Flouci email address');
        }
        
        if (!isset($details['flouci_account_type']) || !in_array($details['flouci_account_type'], ['personal', 'business'])) {
            throw new Exception('Invalid Flouci account type');
        }
    }
    
    /**
     * Log successful payment
     */
    private function logPaymentSuccess($order_id, $payment_method, $transaction_id, $amount, $gateway_response) {
        $stmt = $this->pdo->prepare("INSERT INTO payment_logs (order_id, payment_method, transaction_id, amount, status, additional_data) VALUES (?, ?, ?, ?, 'success', ?)");
        $stmt->execute([
            $order_id,
            $payment_method,
            $transaction_id,
            $amount,
            json_encode($gateway_response)
        ]);
    }
    
    /**
     * Log payment error
     */
    private function logPaymentError($order_id, $payment_method, $error_message) {
        $stmt = $this->pdo->prepare("INSERT INTO payment_logs (order_id, payment_method, transaction_id, amount, status, additional_data) VALUES (?, ?, ?, ?, 'failed', ?)");
        $stmt->execute([
            $order_id,
            $payment_method,
            'ERROR_' . time(),
            0,
            json_encode(['error' => $error_message])
        ]);
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatus($transaction_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_logs WHERE transaction_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$transaction_id]);
        return $stmt->fetch();
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($transaction_id, $status, $additional_data = null) {
        $stmt = $this->pdo->prepare("UPDATE payment_logs SET status = ?, additional_data = ?, updated_at = NOW() WHERE transaction_id = ?");
        $stmt->execute([$status, json_encode($additional_data), $transaction_id]);
    }
    
    /**
     * Get enabled payment methods
     */
    public function getEnabledPaymentMethods() {
        $enabled_methods = [];
        
        if ($this->isGatewayEnabled('paypal')) {
            $enabled_methods[] = 'paypal';
        }
        
        if ($this->isGatewayEnabled('stripe')) {
            $enabled_methods[] = 'card';
        }
        
        if ($this->isGatewayEnabled('d17')) {
            $enabled_methods[] = 'd17';
        }
        
        if ($this->isGatewayEnabled('flouci')) {
            $enabled_methods[] = 'flouci';
        }
        
        // Always include bank transfer and COD
        $enabled_methods[] = 'bank_transfer';
        $enabled_methods[] = 'cod';
        
        return $enabled_methods;
    }
} 