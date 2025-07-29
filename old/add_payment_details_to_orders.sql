-- Update payment_details column setup (Safe version for existing column)
-- This script adds indexes and updates for the existing payment_details column

-- Add index for payment method if it doesn't exist
-- Note: If this fails with "Duplicate key name", the index already exists and can be ignored
ALTER TABLE `orders` 
ADD INDEX `idx_payment_method` (`payment_method`);

-- Add index for payment details if it doesn't exist
-- Note: If this fails with "Duplicate key name", the index already exists and can be ignored
ALTER TABLE `orders` 
ADD INDEX `idx_payment_details` (`payment_details`);

-- Update existing orders to have empty payment details
UPDATE `orders` 
SET `payment_details` = '{}' 
WHERE `payment_details` IS NULL;

-- Add comment to document the payment_details column structure
ALTER TABLE `orders` 
MODIFY COLUMN `payment_details` JSON NULL 
COMMENT 'JSON object containing payment-specific details:
- For card payments: {"card_number": "1234", "card_holder": "John Doe", "card_type": "visa", "expiry_month": "12", "expiry_year": "2025", "cvv_provided": true}
- For D17: {"d17_phone": "+21612345678", "d17_email": "user@example.com"}
- For bank transfer: {"bank_name": "BIAT", "account_holder": "John Doe", "reference_number": "REF123"}
- For COD: {}'; 