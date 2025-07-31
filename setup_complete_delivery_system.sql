-- Complete Delivery System Setup SQL
-- Execute this in phpMyAdmin to set up all delivery tables and settings

-- 1. Create delivery_settings table
CREATE TABLE IF NOT EXISTS delivery_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_company VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (delivery_company, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create delivery_webhook_logs table
CREATE TABLE IF NOT EXISTS delivery_webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_company VARCHAR(50) NOT NULL,
    order_id VARCHAR(100) NOT NULL,
    tracking_id VARCHAR(100),
    status VARCHAR(50) NOT NULL,
    webhook_type VARCHAR(50),
    payload TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_delivery_company (delivery_company),
    INDEX idx_order_id (order_id),
    INDEX idx_tracking_id (tracking_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add delivery columns to orders table if they don't exist
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS delivery_company VARCHAR(50) NULL AFTER status,
ADD COLUMN IF NOT EXISTS delivery_tracking_id VARCHAR(100) NULL AFTER delivery_company,
ADD COLUMN IF NOT EXISTS delivery_status VARCHAR(50) NULL AFTER delivery_tracking_id,
ADD COLUMN IF NOT EXISTS delivery_cost DECIMAL(10,2) NULL AFTER delivery_status;

-- 4. Create delivery_territories table
CREATE TABLE IF NOT EXISTS delivery_territories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_company VARCHAR(50) NOT NULL,
    territory_id VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    shortcode VARCHAR(10) NOT NULL,
    zone_data JSON,
    center_data JSON,
    auto_dispatch BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_territory (delivery_company, territory_id),
    INDEX idx_delivery_company (delivery_company),
    INDEX idx_shortcode (shortcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create delivery_routes table
CREATE TABLE IF NOT EXISTS delivery_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_company VARCHAR(50) NOT NULL,
    pickup_address TEXT NOT NULL,
    dropoff_address TEXT NOT NULL,
    distance_km DECIMAL(10,2),
    estimated_time_minutes INT,
    actual_time_minutes INT,
    route_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_delivery_company (delivery_company)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create delivery_analytics table
CREATE TABLE IF NOT EXISTS delivery_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_company VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    total_orders INT DEFAULT 0,
    completed_orders INT DEFAULT 0,
    cancelled_orders INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    avg_delivery_time_minutes DECIMAL(10,2),
    customer_satisfaction_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_analytics (delivery_company, date),
    INDEX idx_delivery_company (delivery_company),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Insert First Delivery settings with new pricing structure
INSERT INTO delivery_settings (delivery_company, setting_key, setting_value) VALUES
('first_delivery', 'enabled', '1'),
('first_delivery', 'base_cost', '7.00'),
('first_delivery', 'express_cost', '12.00'),
('first_delivery', 'free_threshold', '105.00'),
('first_delivery', 'per_km_cost', '0.50'),
('first_delivery', 'mode', 'sandbox'),
('first_delivery', 'api_key', ''),
('first_delivery', 'merchant_id', ''),
('first_delivery', 'webhook_secret', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- 8. Show the setup results
SELECT '✅ Delivery System Setup Complete!' as status;
SELECT COUNT(*) as delivery_settings_count FROM delivery_settings WHERE delivery_company = 'first_delivery';
SELECT COUNT(*) as webhook_logs_table_exists FROM information_schema.tables WHERE table_name = 'delivery_webhook_logs';
SELECT COUNT(*) as territories_table_exists FROM information_schema.tables WHERE table_name = 'delivery_territories';
SELECT COUNT(*) as routes_table_exists FROM information_schema.tables WHERE table_name = 'delivery_routes';
SELECT COUNT(*) as analytics_table_exists FROM information_schema.tables WHERE table_name = 'delivery_analytics';

-- 9. Show current First Delivery settings
SELECT delivery_company, setting_key, setting_value 
FROM delivery_settings 
WHERE delivery_company = 'first_delivery' 
ORDER BY setting_key; 