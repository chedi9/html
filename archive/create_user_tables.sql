-- Create User-Specific Tables for Checkout System
-- This script creates the missing tables for user addresses and payment methods

-- 1. Create user_addresses table for user-specific saved addresses
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('shipping','billing','both') NOT NULL DEFAULT 'shipping',
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Tunisia',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_default` (`is_default`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create user_payment_methods table for user-specific saved payment methods
CREATE TABLE IF NOT EXISTS `user_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('credit_card','d17','bank_transfer','cash') NOT NULL,
  `name` varchar(255) NOT NULL,
  `card_number` varchar(255) DEFAULT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `expiry_month` varchar(2) DEFAULT NULL,
  `expiry_year` varchar(4) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_default` (`is_default`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add foreign key constraints (optional - uncomment if needed)
/*
ALTER TABLE `user_addresses` 
ADD CONSTRAINT `fk_user_addresses_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_payment_methods` 
ADD CONSTRAINT `fk_user_payment_methods_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
*/

-- 4. Insert sample data for testing (optional)
-- INSERT INTO `user_addresses` (`user_id`, `type`, `full_name`, `phone`, `address_line1`, `city`, `is_default`) VALUES 
-- (1, 'shipping', 'أحمد محمد', '+21612345678', 'شارع الحبيب بورقيبة 123', 'تونس', 1);

-- INSERT INTO `user_payment_methods` (`user_id`, `type`, `name`, `is_default`) VALUES 
-- (1, 'd17', 'D17 المحفظة', 1);

-- 5. Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_user_addresses_user_type` ON `user_addresses` (`user_id`, `type`);
CREATE INDEX IF NOT EXISTS `idx_user_addresses_default` ON `user_addresses` (`user_id`, `is_default`);
CREATE INDEX IF NOT EXISTS `idx_user_payment_methods_user_type` ON `user_payment_methods` (`user_id`, `type`);
CREATE INDEX IF NOT EXISTS `idx_user_payment_methods_default` ON `user_payment_methods` (`user_id`, `is_default`); 