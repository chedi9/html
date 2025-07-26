-- Create disabled_sellers table
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

-- Add disabled_seller_id column to products table
ALTER TABLE `products` ADD COLUMN `disabled_seller_id` int(11) DEFAULT NULL AFTER `seller_photo`;
ALTER TABLE `products` ADD COLUMN `is_priority_product` tinyint(1) DEFAULT 0 AFTER `disabled_seller_id`;

-- Add foreign key constraint
ALTER TABLE `products` ADD CONSTRAINT `fk_products_disabled_seller` 
FOREIGN KEY (`disabled_seller_id`) REFERENCES `disabled_sellers`(`id`) ON DELETE SET NULL;

-- Add indexes for better performance
ALTER TABLE `products` ADD INDEX `idx_disabled_seller_id` (`disabled_seller_id`);
ALTER TABLE `products` ADD INDEX `idx_is_priority_product` (`is_priority_product`);

-- Create a view for priority products (disabled sellers' products)
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