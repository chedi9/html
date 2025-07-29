-- Security and Fraud Detection System Database Tables
-- This file creates all necessary tables for security monitoring and fraud detection

-- 1. Add security columns to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `two_factor_secret` varchar(32) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `status` enum('active','blocked','suspended') NOT NULL DEFAULT 'active',
ADD COLUMN IF NOT EXISTS `blocked_reason` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `blocked_at` timestamp NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL;

-- 2. Create security logs table
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` enum('login','logout','password_change','2fa_enabled','2fa_disabled','profile_update','email_change','phone_change','address_change','payment_method_change','order_placed','order_cancelled','refund_requested','review_posted','account_locked','account_unlocked','suspicious_activity','failed_login','password_reset','email_verification','phone_verification') NOT NULL,
  `status` enum('success','failed','pending','cancelled') NOT NULL DEFAULT 'success',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `status` (`status`),
  KEY `ip_address` (`ip_address`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create user sessions table
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`),
  KEY `last_activity` (`last_activity`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create fraud alerts table
CREATE TABLE IF NOT EXISTS `fraud_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `alert_type` enum('suspicious_login','multiple_failed_logins','unusual_transaction','high_value_order','suspicious_ip','account_takeover','payment_fraud','identity_theft','bot_activity','phishing_attempt','malware_detected','data_breach','unusual_behavior','location_mismatch','device_mismatch','time_anomaly','velocity_check','pattern_analysis','risk_assessment','manual_review') NOT NULL,
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `risk_score` int(11) NOT NULL DEFAULT 50,
  `description` text NOT NULL,
  `evidence` text DEFAULT NULL,
  `status` enum('pending','investigating','resolved','confirmed_fraud','false_positive') NOT NULL DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `alert_type` (`alert_type`),
  KEY `risk_level` (`risk_level`),
  KEY `status` (`status`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create fraud rules table
CREATE TABLE IF NOT EXISTS `fraud_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(255) NOT NULL,
  `rule_type` enum('login','transaction','behavior','location','device','velocity','pattern','custom') NOT NULL,
  `description` text DEFAULT NULL,
  `conditions` text NOT NULL,
  `actions` text NOT NULL,
  `risk_score` int(11) NOT NULL DEFAULT 50,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rule_type` (`rule_type`),
  KEY `is_active` (`is_active`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create IP blacklist table
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `source` enum('manual','automatic','third_party') NOT NULL DEFAULT 'manual',
  `blocked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `is_active` (`is_active`),
  KEY `blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create device fingerprinting table
CREATE TABLE IF NOT EXISTS `device_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `screen_resolution` varchar(20) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `plugins` text DEFAULT NULL,
  `canvas_fingerprint` varchar(64) DEFAULT NULL,
  `webgl_fingerprint` varchar(64) DEFAULT NULL,
  `audio_fingerprint` varchar(64) DEFAULT NULL,
  `fonts` text DEFAULT NULL,
  `is_trusted` tinyint(1) NOT NULL DEFAULT 0,
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `fingerprint_hash` (`fingerprint_hash`),
  KEY `user_id` (`user_id`),
  KEY `is_trusted` (`is_trusted`),
  KEY `last_used` (`last_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create location tracking table
CREATE TABLE IF NOT EXISTS `user_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `is_suspicious` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ip_address` (`ip_address`),
  KEY `is_suspicious` (`is_suspicious`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create security settings table
CREATE TABLE IF NOT EXISTS `security_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Insert default fraud rules
INSERT IGNORE INTO `fraud_rules` (`rule_name`, `rule_type`, `description`, `conditions`, `actions`, `risk_score`, `priority`) VALUES
('Multiple Failed Logins', 'login', 'Detect multiple failed login attempts from same IP', '{"failed_logins": {"count": 5, "timeframe": "1 hour"}}', '{"alert": true, "block_ip": true, "notify_user": true}', 80, 1),
('High Value Transaction', 'transaction', 'Flag transactions above threshold', '{"amount": {"min": 1000}}', '{"alert": true, "require_verification": true}', 70, 2),
('Unusual Location', 'location', 'Detect login from unusual location', '{"location_change": {"timeframe": "1 hour", "distance": "100 km"}}', '{"alert": true, "require_2fa": true}', 60, 3),
('New Device Login', 'device', 'Detect login from new device', '{"new_device": true}', '{"alert": true, "require_verification": true}', 50, 4),
('Rapid Transactions', 'velocity', 'Detect rapid transaction pattern', '{"transaction_velocity": {"count": 3, "timeframe": "5 minutes"}}', '{"alert": true, "hold_transaction": true}', 90, 1);

-- 11. Insert default security settings
INSERT IGNORE INTO `security_settings` (`setting_key`, `setting_value`, `description`) VALUES
('max_failed_logins', '5', 'Maximum failed login attempts before account lockout'),
('lockout_duration', '30', 'Account lockout duration in minutes'),
('session_timeout', '1440', 'Session timeout in minutes (24 hours)'),
('require_2fa_threshold', '1000', 'Require 2FA for transactions above this amount'),
('suspicious_ip_threshold', '3', 'Number of failed attempts from IP before blocking'),
('high_value_threshold', '1000', 'Transaction amount threshold for fraud detection'),
('location_change_threshold', '100', 'Distance in km for location change detection'),
('device_verification_required', '1', 'Require device verification for new devices'),
('enable_fraud_detection', '1', 'Enable automatic fraud detection'),
('enable_ip_blacklisting', '1', 'Enable automatic IP blacklisting');

-- 12. Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_security_logs_user_action` ON `security_logs` (`user_id`, `action`);
CREATE INDEX IF NOT EXISTS `idx_security_logs_ip_time` ON `security_logs` (`ip_address`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_fraud_alerts_user_status` ON `fraud_alerts` (`user_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_fraud_alerts_risk_time` ON `fraud_alerts` (`risk_level`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_user_sessions_user_active` ON `user_sessions` (`user_id`, `is_active`);
CREATE INDEX IF NOT EXISTS `idx_device_fingerprints_user_trusted` ON `device_fingerprints` (`user_id`, `is_trusted`);

-- 13. Log existing user logins
INSERT IGNORE INTO `security_logs` (`user_id`, `action`, `status`, `ip_address`, `user_agent`, `created_at`)
SELECT 
    id as user_id,
    'login' as action,
    'success' as status,
    '127.0.0.1' as ip_address,
    'System Migration' as user_agent,
    created_at
FROM `users` 
WHERE last_login IS NOT NULL;

-- 14. Add foreign key constraints (commented out for safety)
/*
ALTER TABLE `security_logs` 
ADD CONSTRAINT `fk_security_logs_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_sessions` 
ADD CONSTRAINT `fk_user_sessions_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `fraud_alerts` 
ADD CONSTRAINT `fk_fraud_alerts_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `device_fingerprints` 
ADD CONSTRAINT `fk_device_fingerprints_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_locations` 
ADD CONSTRAINT `fk_user_locations_user` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
*/ 