-- Create Payment Settings Table
-- This script creates the payment_settings table if it doesn't exist

CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gateway` varchar(50) NOT NULL COMMENT 'Payment gateway name (paypal, stripe, d17, flouci)',
  `setting_key` varchar(100) NOT NULL COMMENT 'Setting key (client_id, secret_key, etc.)',
  `setting_value` text NOT NULL COMMENT 'Setting value (encrypted if sensitive)',
  `encrypted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether the value is encrypted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `gateway_setting` (`gateway`, `setting_key`),
  KEY `gateway` (`gateway`),
  KEY `encrypted` (`encrypted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Stores payment gateway configuration settings with encryption support';

-- Insert default settings (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO `payment_settings` (`gateway`, `setting_key`, `setting_value`, `encrypted`) VALUES
-- PayPal Settings
('paypal', 'client_id', '', 1),
('paypal', 'client_secret', '', 1),
('paypal', 'mode', 'sandbox', 0),
('paypal', 'webhook_id', '', 0),
('paypal', 'enabled', '0', 0),

-- Stripe Settings
('stripe', 'publishable_key', '', 1),
('stripe', 'secret_key', '', 1),
('stripe', 'webhook_secret', '', 1),
('stripe', 'mode', 'test', 0),
('stripe', 'enabled', '0', 0),

-- D17 Settings
('d17', 'api_key', '', 1),
('d17', 'merchant_id', '', 1),
('d17', 'enabled', '0', 0),

-- Flouci Settings
('flouci', 'api_key', '', 1),
('flouci', 'merchant_id', '', 1),
('flouci', 'enabled', '0', 0); 