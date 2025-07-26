-- Complete Disabled Sellers Setup - Safe Version
-- This script will only add what's missing, avoiding duplicate column errors

-- 1. Create disabled_sellers table if it doesn't exist
CREATE TABLE IF NOT EXISTS `disabled_sellers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `story` text NOT NULL,
  `disability_type` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `seller_photo` varchar(255) DEFAULT NULL,
  `priority_level` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `priority_level` (`priority_level`),
  KEY `disability_type` (`disability_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add disabled_seller_id column to products table (only if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND COLUMN_NAME = 'disabled_seller_id') = 0,
    'ALTER TABLE `products` ADD COLUMN `disabled_seller_id` int(11) DEFAULT NULL AFTER `seller_photo`',
    'SELECT "disabled_seller_id column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add is_priority_product column to products table (only if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND COLUMN_NAME = 'is_priority_product') = 0,
    'ALTER TABLE `products` ADD COLUMN `is_priority_product` tinyint(1) DEFAULT 0 AFTER `disabled_seller_id`',
    'SELECT "is_priority_product column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Add foreign key constraint (only if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND COLUMN_NAME = 'disabled_seller_id' 
     AND REFERENCED_TABLE_NAME = 'disabled_sellers') = 0,
    'ALTER TABLE `products` ADD CONSTRAINT `fk_products_disabled_seller` FOREIGN KEY (`disabled_seller_id`) REFERENCES `disabled_sellers`(`id`) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Add indexes for better performance (only if they don't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND INDEX_NAME = 'idx_disabled_seller_id') = 0,
    'ALTER TABLE `products` ADD INDEX `idx_disabled_seller_id` (`disabled_seller_id`)',
    'SELECT "idx_disabled_seller_id index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND INDEX_NAME = 'idx_is_priority_product') = 0,
    'ALTER TABLE `products` ADD INDEX `idx_is_priority_product` (`is_priority_product`)',
    'SELECT "idx_is_priority_product index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Create or replace priority_products view
CREATE OR REPLACE VIEW `priority_products` AS
SELECT 
    p.*,
    ds.name as disabled_seller_name,
    ds.story as disabled_seller_story,
    ds.disability_type,
    ds.seller_photo as disabled_seller_photo,
    ds.priority_level
FROM products p
LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
WHERE p.disabled_seller_id IS NOT NULL
ORDER BY ds.priority_level DESC, p.created_at DESC;

-- 7. Show final status
SELECT 'Disabled sellers setup completed successfully!' as status; 