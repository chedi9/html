-- Create payment_logs table for tracking payment attempts and transactions
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('card','d17','bank_transfer','cod') NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `additional_data` JSON NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `order_id` (`order_id`),
  KEY `payment_method` (`payment_method`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint separately (if needed)
-- ALTER TABLE `payment_logs` 
-- ADD CONSTRAINT `fk_payment_logs_order` 
-- FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

-- Add comment to document the table purpose
ALTER TABLE `payment_logs` 
COMMENT = 'Logs all payment attempts and transactions for audit and tracking purposes'; 