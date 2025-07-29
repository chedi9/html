-- Add Flouci payment method to database enums
-- This script updates the payment_method enum in orders and payment_logs tables

-- Update orders table payment_method enum
ALTER TABLE `orders` 
MODIFY COLUMN `payment_method` enum('card','d17','flouci','bank_transfer','cod') NOT NULL;

-- Update payment_logs table payment_method enum
ALTER TABLE `payment_logs` 
MODIFY COLUMN `payment_method` enum('card','d17','flouci','bank_transfer','cod') NOT NULL;

-- Add comment to document the updated payment methods
ALTER TABLE `orders` 
MODIFY COLUMN `payment_method` enum('card','d17','flouci','bank_transfer','cod') NOT NULL 
COMMENT 'Payment methods: card (credit/debit cards), d17 (mobile payment), flouci (digital wallet), bank_transfer (bank transfer), cod (cash on delivery)';

ALTER TABLE `payment_logs` 
MODIFY COLUMN `payment_method` enum('card','d17','flouci','bank_transfer','cod') NOT NULL 
COMMENT 'Payment methods: card (credit/debit cards), d17 (mobile payment), flouci (digital wallet), bank_transfer (bank transfer), cod (cash on delivery)'; 