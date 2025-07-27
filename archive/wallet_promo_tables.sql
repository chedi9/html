-- Wallet and Promo Code System Database Tables
-- This file creates all necessary tables for wallet, loyalty points, and promo code functionality

-- 1. Add wallet and loyalty columns to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `phone`,
ADD COLUMN IF NOT EXISTS `loyalty_points` int(11) NOT NULL DEFAULT 0 AFTER `wallet_balance`,
ADD COLUMN IF NOT EXISTS `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `loyalty_points`;

-- 2. Create wallet transactions table
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal','purchase','refund','points_redeem','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'completed',
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `reference` (`reference_id`, `reference_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create promo codes table
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `uses` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `start_date` timestamp NULL DEFAULT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`),
  KEY `expiry_date` (`expiry_date`),
  KEY `start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create promo code usage tracking table
CREATE TABLE IF NOT EXISTS `promo_code_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promo_code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `promo_code_id` (`promo_code_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `used_at` (`used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create voucher types table
CREATE TABLE IF NOT EXISTS `voucher_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `validity_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create user vouchers table
CREATE TABLE IF NOT EXISTS `user_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `voucher_type_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_order` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `used_order_id` int(11) DEFAULT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `user_id` (`user_id`),
  KEY `voucher_type_id` (`voucher_type_id`),
  KEY `is_used` (`is_used`),
  KEY `expiry_date` (`expiry_date`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create loyalty tiers table
CREATE TABLE IF NOT EXISTS `loyalty_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `minimum_spend` decimal(10,2) NOT NULL,
  `points_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `benefits` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `minimum_spend` (`minimum_spend`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create loyalty points history table
CREATE TABLE IF NOT EXISTS `loyalty_points_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('earned','redeemed','expired','bonus','adjustment') NOT NULL,
  `points` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `order_id` (`order_id`),
  KEY `expiry_date` (`expiry_date`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Insert default loyalty tiers
INSERT IGNORE INTO `loyalty_tiers` (`name`, `display_name`, `minimum_spend`, `points_multiplier`, `benefits`, `color`) VALUES
('bronze', 'Bronze', 0.00, 1.00, 'Basic rewards and standard shipping', '#cd7f32'),
('silver', 'Silver', 1000.00, 1.25, '25% more points, free shipping on orders over $50', '#c0c0c0'),
('gold', 'Gold', 2500.00, 1.50, '50% more points, priority support, exclusive offers', '#ffd700'),
('platinum', 'Platinum', 5000.00, 2.00, 'Double points, VIP support, early access to sales', '#e5e4e2'),
('diamond', 'Diamond', 10000.00, 2.50, 'Maximum benefits, personal account manager, custom offers', '#b9f2ff');

-- 10. Insert sample promo codes
INSERT IGNORE INTO `promo_codes` (`code`, `name`, `description`, `discount_type`, `discount_value`, `minimum_order`, `max_uses`, `status`) VALUES
('WELCOME10', 'Welcome Discount', '10% off your first order', 'percentage', 10.00, 50.00, 1000, 'active'),
('SAVE20', 'Save $20', '$20 off orders over $100', 'fixed', 20.00, 100.00, 500, 'active'),
('FREESHIP', 'Free Shipping', 'Free shipping on any order', 'fixed', 10.00, 25.00, 2000, 'active');

-- 11. Insert sample voucher types
INSERT IGNORE INTO `voucher_types` (`name`, `description`, `discount_type`, `discount_value`, `minimum_order`, `validity_days`) VALUES
('Birthday Voucher', 'Special birthday discount for our valued customers', 'percentage', 15.00, 30.00, 30),
('Loyalty Reward', 'Reward for loyal customers', 'fixed', 25.00, 50.00, 60),
('Referral Bonus', 'Bonus for referring friends', 'percentage', 20.00, 40.00, 90),
('Seasonal Offer', 'Limited time seasonal discount', 'fixed', 15.00, 25.00, 45);

-- 12. Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_wallet_transactions_user_type` ON `wallet_transactions` (`user_id`, `type`);
CREATE INDEX IF NOT EXISTS `idx_promo_codes_status_expiry` ON `promo_codes` (`status`, `expiry_date`);
CREATE INDEX IF NOT EXISTS `idx_user_vouchers_user_used` ON `user_vouchers` (`user_id`, `is_used`);
CREATE INDEX IF NOT EXISTS `idx_loyalty_points_user_type` ON `loyalty_points_history` (`user_id`, `type`);

-- 13. Update existing orders to calculate total spent for users
UPDATE `users` u 
SET `total_spent` = (
    SELECT COALESCE(SUM(total_amount), 0) 
    FROM `orders` 
    WHERE user_id = u.id 
    AND status IN ('delivered', 'completed')
);

-- 14. Award loyalty points for existing orders
INSERT IGNORE INTO `loyalty_points_history` (`user_id`, `type`, `points`, `description`, `order_id`, `created_at`)
SELECT 
    o.user_id,
    'earned' as type,
    FLOOR(o.total_amount) as points,
    CONCAT('Points earned from order #', o.id) as description,
    o.id as order_id,
    o.created_at
FROM `orders` o
WHERE o.status IN ('delivered', 'completed')
AND o.total_amount > 0;

-- 15. Update user loyalty points based on history
UPDATE `users` u 
SET `loyalty_points` = (
    SELECT COALESCE(SUM(
        CASE 
            WHEN type IN ('earned', 'bonus') THEN points
            WHEN type IN ('redeemed', 'expired') THEN -points
            ELSE 0
        END
    ), 0)
    FROM `loyalty_points_history` 
    WHERE user_id = u.id
    AND (expiry_date IS NULL OR expiry_date > NOW())
);

-- 16. Add foreign key constraints (commented out for safety)
/*
ALTER TABLE `wallet_transactions` 
ADD CONSTRAINT `fk_wallet_transactions_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `promo_code_usage` 
ADD CONSTRAINT `fk_promo_usage_code` 
FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE;

ALTER TABLE `promo_code_usage` 
ADD CONSTRAINT `fk_promo_usage_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_vouchers` 
ADD CONSTRAINT `fk_user_vouchers_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_vouchers` 
ADD CONSTRAINT `fk_user_vouchers_type` 
FOREIGN KEY (`voucher_type_id`) REFERENCES `voucher_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `loyalty_points_history` 
ADD CONSTRAINT `fk_loyalty_points_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
*/ 