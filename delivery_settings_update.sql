-- Update Delivery Settings with New Pricing Structure
-- First Delivery is now the default option with updated pricing

-- Create delivery_settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS delivery_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_company VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (delivery_company, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert or update First Delivery settings with new pricing
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

-- Update existing settings if they exist
UPDATE delivery_settings SET setting_value = '1' WHERE delivery_company = 'first_delivery' AND setting_key = 'enabled';
UPDATE delivery_settings SET setting_value = '7.00' WHERE delivery_company = 'first_delivery' AND setting_key = 'base_cost';
UPDATE delivery_settings SET setting_value = '12.00' WHERE delivery_company = 'first_delivery' AND setting_key = 'express_cost';
UPDATE delivery_settings SET setting_value = '105.00' WHERE delivery_company = 'first_delivery' AND setting_key = 'free_threshold';

-- Show the updated settings
SELECT delivery_company, setting_key, setting_value 
FROM delivery_settings 
WHERE delivery_company = 'first_delivery' 
ORDER BY setting_key; 