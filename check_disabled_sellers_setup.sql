-- Check current database structure for disabled sellers setup
-- Run these queries to see what's already in place

-- 1. Check if disabled_sellers table exists
SHOW TABLES LIKE 'disabled_sellers';

-- 2. Check if disabled_seller_id column exists in products table
DESCRIBE products;

-- 3. Check if is_priority_product column exists in products table
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'products' 
AND COLUMN_NAME IN ('disabled_seller_id', 'is_priority_product');

-- 4. Check if priority_products view exists
SHOW TABLES LIKE 'priority_products'; 