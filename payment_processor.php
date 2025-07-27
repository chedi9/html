<?php
/**
 * Payment Processor Helper
 * Handles different payment methods and their specific requirements
 */

class PaymentProcessor {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process payment based on payment method
     */
    public function processPayment($payment_method, $payment_details, $amount, $order_id) {
        switch ($payment_method) {
            case 'card':
                return $this->processCardPayment($payment_details, $amount, $order_id);
            case 'd17':
                return $this->processD17Payment($payment_details, $amount, $order_id);
            case 'flouci':
                return $this->processFlouciPayment($payment_details, $amount, $order_id);
            case 'bank_transfer':
                return $this->processBankTransfer($payment_details, $amount, $order_id);
            case 'cod':
                return $this->processCODPayment($amount, $order_id);
            default:
                throw new Exception('Invalid payment method');
        }
    }
    
    /**
     * Process credit card payment
     */
    private function processCardPayment($payment_details, $amount, $order_id) {
        // Validate card details
        $this->validateCardDetails($payment_details);
        
        // In a real implementation, you would integrate with a payment gateway here
        // For now, we'll simulate a successful payment
        
        $transaction_id = 'CARD_' . time() . '_' . $order_id;
        
        // Log the payment attempt
        $this->logPaymentAttempt($order_id, 'card', $transaction_id, $amount, 'success');
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'message' => 'Payment processed successfully',
            'payment_method' => 'Credit Card',
            'amount' => $amount
        ];
    }
    
    /**
     * Process D17 payment
     */
    private function processD17Payment($payment_details, $amount, $order_id) {
        // Validate D17 details
        $this->validateD17Details($payment_details);
        
        // Generate D17 payment link (in real implementation, integrate with D17 API)
        $payment_link = $this->generateD17PaymentLink($payment_details, $amount, $order_id);
        
        $transaction_id = 'D17_' . time() . '_' . $order_id;
        
        // Log the payment attempt
        $this->logPaymentAttempt($order_id, 'd17', $transaction_id, $amount, 'pending');
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'payment_link' => $payment_link,
            'message' => 'D17 payment link generated',
            'payment_method' => 'D17',
            'amount' => $amount,
            'status' => 'pending'
        ];
    }
    
    /**
     * Process Flouci payment
     */
    private function processFlouciPayment($payment_details, $amount, $order_id) {
        // Validate Flouci details
        $this->validateFlouciDetails($payment_details);
        
        // Generate Flouci payment link (in real implementation, integrate with Flouci API)
        $payment_link = $this->generateFlouciPaymentLink($payment_details, $amount, $order_id);
        
        $transaction_id = 'FLOUCI_' . time() . '_' . $order_id;
        
        // Log the payment attempt
        $this->logPaymentAttempt($order_id, 'flouci', $transaction_id, $amount, 'pending');
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'payment_link' => $payment_link,
            'message' => 'Flouci payment link generated',
            'payment_method' => 'Flouci',
            'amount' => $amount,
            'status' => 'pending'
        ];
    }
    
    /**
     * Process bank transfer
     */
    private function processBankTransfer($payment_details, $amount, $order_id) {
        // Validate bank transfer details
        $this->validateBankTransferDetails($payment_details);
        
        // Generate bank account details for transfer
        $bank_details = $this->generateBankTransferDetails($payment_details, $amount, $order_id);
        
        $transaction_id = 'BANK_' . time() . '_' . $order_id;
        
        // Log the payment attempt
        $this->logPaymentAttempt($order_id, 'bank_transfer', $transaction_id, $amount, 'pending');
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'bank_details' => $bank_details,
            'message' => 'Bank transfer details generated',
            'payment_method' => 'Bank Transfer',
            'amount' => $amount,
            'status' => 'pending'
        ];
    }
    
    /**
     * Process Cash on Delivery
     */
    private function processCODPayment($amount, $order_id) {
        $transaction_id = 'COD_' . time() . '_' . $order_id;
        
        // Log the payment attempt
        $this->logPaymentAttempt($order_id, 'cod', $transaction_id, $amount, 'pending');
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'message' => 'Cash on delivery payment confirmed',
            'payment_method' => 'Cash on Delivery',
            'amount' => $amount,
            'status' => 'pending'
        ];
    }
    
    /**
     * Validate card details
     */
    private function validateCardDetails($details) {
        if (empty($details['card_number']) || strlen($details['card_number']) < 4) {
            throw new Exception('Invalid card number');
        }
        
        if (empty($details['card_holder'])) {
            throw new Exception('Card holder name is required');
        }
        
        if (empty($details['card_type'])) {
            throw new Exception('Card type is required');
        }
        
        if (empty($details['expiry_month']) || empty($details['expiry_year'])) {
            throw new Exception('Card expiry date is required');
        }
        
        // Check if card is expired
        $expiry_date = $details['expiry_year'] . '-' . $details['expiry_month'] . '-01';
        if (strtotime($expiry_date) < strtotime('today')) {
            throw new Exception('Card has expired');
        }
        
        if (empty($details['cvv_provided'])) {
            throw new Exception('CVV is required');
        }
    }
    
    /**
     * Validate D17 details
     */
    private function validateD17Details($details) {
        if (empty($details['d17_phone'])) {
            throw new Exception('D17 phone number is required');
        }
        
        if (empty($details['d17_email'])) {
            throw new Exception('D17 email is required');
        }
        
        // Validate phone number format
        if (!preg_match('/^(\+216|00216)?\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/', $details['d17_phone'])) {
            throw new Exception('Invalid D17 phone number format');
        }
        
        // Validate email format
        if (!filter_var($details['d17_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid D17 email format');
        }
    }
    
    /**
     * Validate Flouci details
     */
    private function validateFlouciDetails($details) {
        if (empty($details['flouci_phone'])) {
            throw new Exception('Flouci phone number is required');
        }
        
        if (empty($details['flouci_email'])) {
            throw new Exception('Flouci email is required');
        }
        
        // Validate phone number format
        if (!preg_match('/^(\+216|00216)?\s?[0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/', $details['flouci_phone'])) {
            throw new Exception('Invalid Flouci phone number format');
        }
        
        // Validate email format
        if (!filter_var($details['flouci_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid Flouci email format');
        }
    }
    
    /**
     * Validate bank transfer details
     */
    private function validateBankTransferDetails($details) {
        if (empty($details['bank_name'])) {
            throw new Exception('Bank name is required');
        }
        
        if (empty($details['account_holder'])) {
            throw new Exception('Account holder name is required');
        }
    }
    
    /**
     * Generate D17 payment link
     */
    private function generateD17PaymentLink($details, $amount, $order_id) {
        // In a real implementation, this would integrate with D17 API
        // For now, return a placeholder link
        $params = http_build_query([
            'amount' => $amount,
            'order_id' => $order_id,
            'phone' => $details['d17_phone'],
            'email' => $details['d17_email']
        ]);
        
        return 'https://d17.tn/payment?' . $params;
    }
    
    /**
     * Generate Flouci payment link
     */
    private function generateFlouciPaymentLink($details, $amount, $order_id) {
        // In a real implementation, this would integrate with Flouci API
        // For now, return a placeholder link
        $params = http_build_query([
            'amount' => $amount,
            'order_id' => $order_id,
            'phone' => $details['flouci_phone'],
            'email' => $details['flouci_email']
        ]);
        
        return 'https://flouci.tn/payment?' . $params;
    }
    
    /**
     * Generate bank transfer details
     */
    private function generateBankTransferDetails($details, $amount, $order_id) {
        // In a real implementation, this would return actual bank account details
        return [
            'bank_name' => $details['bank_name'],
            'account_number' => 'TN59 1234 5678 9012 3456 7890',
            'account_holder' => 'Webuy Store',
            'swift_code' => 'BIASTNTN',
            'iban' => 'TN59 1234 5678 9012 3456 7890',
            'reference' => 'ORDER_' . $order_id,
            'amount' => $amount
        ];
    }
    
    /**
     * Log payment attempt
     */
    private function logPaymentAttempt($order_id, $payment_method, $transaction_id, $amount, $status) {
        $stmt = $this->pdo->prepare('
            INSERT INTO payment_logs (order_id, payment_method, transaction_id, amount, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$order_id, $payment_method, $transaction_id, $amount, $status]);
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatus($transaction_id) {
        $stmt = $this->pdo->prepare('
            SELECT status, payment_method, amount, created_at 
            FROM payment_logs 
            WHERE transaction_id = ?
        ');
        $stmt->execute([$transaction_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($transaction_id, $status, $additional_data = null) {
        $stmt = $this->pdo->prepare('
            UPDATE payment_logs 
            SET status = ?, additional_data = ?, updated_at = NOW() 
            WHERE transaction_id = ?
        ');
        $stmt->execute([$status, $additional_data ? json_encode($additional_data) : null, $transaction_id]);
    }
}

/**
 * Payment validation helper functions
 */
class PaymentValidator {
    /**
     * Validate card number using Luhn algorithm
     */
    public static function validateCardNumber($card_number) {
        $card_number = preg_replace('/\s+/', '', $card_number);
        
        if (!is_numeric($card_number)) {
            return false;
        }
        
        $length = strlen($card_number);
        if ($length < 13 || $length > 19) {
            return false;
        }
        
        // Luhn algorithm
        $sum = 0;
        $length = strlen($card_number);
        for ($i = $length - 1; $i >= 0; $i--) {
            $d = $card_number[$length - $i - 1];
            if ($i % 2 == 0) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
        }
        
        return ($sum % 10) == 0;
    }
    
    /**
     * Detect card type from card number
     */
    public static function detectCardType($card_number) {
        $card_number = preg_replace('/\s+/', '', $card_number);
        
        // Visa
        if (preg_match('/^4/', $card_number)) {
            return 'visa';
        }
        
        // Mastercard
        if (preg_match('/^5[1-5]|^2[2-7]|^222[1-9]|^22[3-9]|^2[3-6]|^27[0-1]|^2720/', $card_number)) {
            return 'mastercard';
        }
        
        // American Express
        if (preg_match('/^3[47]/', $card_number)) {
            return 'amex';
        }
        
        // Discover
        if (preg_match('/^6(?:011|5)/', $card_number)) {
            return 'discover';
        }
        
        return 'unknown';
    }
    
    /**
     * Validate CVV
     */
    public static function validateCVV($cvv, $card_type) {
        $cvv = preg_replace('/\s+/', '', $cvv);
        
        if (!is_numeric($cvv)) {
            return false;
        }
        
        switch ($card_type) {
            case 'amex':
                return strlen($cvv) == 4;
            default:
                return strlen($cvv) == 3;
        }
    }
    
    /**
     * Validate expiry date
     */
    public static function validateExpiryDate($month, $year) {
        if (!is_numeric($month) || !is_numeric($year)) {
            return false;
        }
        
        if ($month < 1 || $month > 12) {
            return false;
        }
        
        $current_year = date('Y');
        $current_month = date('n');
        
        if ($year < $current_year) {
            return false;
        }
        
        if ($year == $current_year && $month < $current_month) {
            return false;
        }
        
        return true;
    }
}
?> 