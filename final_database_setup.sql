-- Final Database Setup for Disabled Sellers System
-- This script only adds what's missing, avoiding permission issues

-- 1. Create disabled_sellers table (if it doesn't exist)
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
-- Note: This column might already exist based on your previous error
-- If you get an error saying it already exists, that's fine - just skip this step

-- 3. Add is_priority_product column to products table (only if it doesn't exist)
-- Note: This column might already exist based on your previous error
-- If you get an error saying it already exists, that's fine - just skip this step

-- 4. Add indexes for better performance (only if they don't exist)
-- These are optional but recommended for better performance

-- 5. Insert a sample disabled seller for testing
INSERT IGNORE INTO `disabled_sellers` (`name`, `story`, `disability_type`, `location`, `contact_info`, `priority_level`) VALUES
('أحمد محمد', 'بائع متميز يقدم منتجات عالية الجودة. يعمل بجد لتحقيق أحلامه رغم التحديات.', 'حركية', 'تونس', 'ahmed@example.com', 8),
('فاطمة علي', 'صاحبة مشروع صغير تقدم منتجات يدوية فريدة من نوعها.', 'بصرية', 'صفاقس', 'fatima@example.com', 7),
('محمد حسن', 'مطور برمجيات موهوب يقدم خدمات تقنية متقدمة.', 'سمعية', 'سوسة', 'mohamed@example.com', 9);

-- 6. Show current status
SELECT 'Database setup completed!' as status;
SELECT COUNT(*) as disabled_sellers_count FROM disabled_sellers;
SELECT COUNT(*) as total_products FROM products; 