-- Create Returns Management Tables
-- This script creates the missing tables for the returns system

-- 1. Create returns table for return requests
CREATE TABLE IF NOT EXISTS `returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL,
  `reason` enum('defective','wrong_item','not_as_described','changed_mind','other') NOT NULL,
  `description` text DEFAULT NULL,
  `return_date` date NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_method` varchar(50) DEFAULT NULL,
  `refund_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `return_date` (`return_date`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create return_items table for individual items being returned
CREATE TABLE IF NOT EXISTS `return_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `return_reason` text NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `product_id` (`product_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add return_status column to orders table if it doesn't exist
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `return_status` enum('none','return_requested','return_approved','return_rejected','return_completed') NOT NULL DEFAULT 'none' AFTER `status`;

-- 4. Add foreign key constraints (optional - uncomment if needed)
/*
ALTER TABLE `returns` 
ADD CONSTRAINT `fk_returns_order` 
FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

ALTER TABLE `returns` 
ADD CONSTRAINT `fk_returns_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `return_items` 
ADD CONSTRAINT `fk_return_items_return` 
FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE;

ALTER TABLE `return_items` 
ADD CONSTRAINT `fk_return_items_order_item` 
FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

ALTER TABLE `return_items` 
ADD CONSTRAINT `fk_return_items_product` 
FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
*/

-- 5. Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_returns_order_user` ON `returns` (`order_id`, `user_id`);
CREATE INDEX IF NOT EXISTS `idx_returns_status_date` ON `returns` (`status`, `return_date`);
CREATE INDEX IF NOT EXISTS `idx_return_items_return_product` ON `return_items` (`return_id`, `product_id`);
CREATE INDEX IF NOT EXISTS `idx_return_items_status` ON `return_items` (`status`);

-- 6. Insert sample data for testing (optional)
-- INSERT INTO `returns` (`order_id`, `user_id`, `return_number`, `reason`, `description`, `return_date`, `status`) VALUES 
-- (1, 1, 'RET-20241201-000001', 'defective', 'المنتج لا يعمل بشكل صحيح', '2024-12-01', 'pending');

-- INSERT INTO `return_items` (`return_id`, `order_item_id`, `product_id`, `quantity`, `return_reason`) VALUES 
-- (1, 1, 1, 1, 'المنتج معيب'); 