-- Add sample shipping methods to the database
-- This script will insert sample shipping methods if they don't already exist

-- Check if shipping_methods table exists and has data
INSERT IGNORE INTO `shipping_methods` (`name`, `description`, `price`, `free_shipping_threshold`, `estimated_days`, `is_active`, `sort_order`, `created_at`) VALUES
('التوصيل القياسي', 'التوصيل العادي خلال 3-5 أيام عمل', 5.00, 50.00, '3-5', 1, 1, NOW()),
('التوصيل السريع', 'التوصيل السريع خلال 1-2 أيام عمل', 10.00, 100.00, '1-2', 1, 2, NOW()),
('التوصيل المجاني', 'التوصيل المجاني للطلبات فوق 50 دينار', 0.00, 50.00, '3-5', 1, 3, NOW()),
('التوصيل المميز', 'التوصيل المميز مع تتبع مباشر', 15.00, 150.00, '1', 1, 4, NOW());

-- Update existing shipping methods if they exist
UPDATE `shipping_methods` SET 
    `description` = 'التوصيل العادي خلال 3-5 أيام عمل',
    `price` = 5.00,
    `free_shipping_threshold` = 50.00,
    `estimated_days` = '3-5',
    `is_active` = 1,
    `sort_order` = 1
WHERE `name` = 'التوصيل القياسي';

UPDATE `shipping_methods` SET 
    `description` = 'التوصيل السريع خلال 1-2 أيام عمل',
    `price` = 10.00,
    `free_shipping_threshold` = 100.00,
    `estimated_days` = '1-2',
    `is_active` = 1,
    `sort_order` = 2
WHERE `name` = 'التوصيل السريع';

UPDATE `shipping_methods` SET 
    `description` = 'التوصيل المجاني للطلبات فوق 50 دينار',
    `price` = 0.00,
    `free_shipping_threshold` = 50.00,
    `estimated_days` = '3-5',
    `is_active` = 1,
    `sort_order` = 3
WHERE `name` = 'التوصيل المجاني';

-- Show current shipping methods
SELECT * FROM `shipping_methods` WHERE `is_active` = 1 ORDER BY `sort_order` ASC; 