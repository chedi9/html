-- Create security-related database tables for WeBuy marketplace

-- Security logs table
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT 'Type of security event (login, logout, failed_login, payment, etc.)',
  `details` json DEFAULT NULL COMMENT 'Additional event details in JSON format',
  `user_id` int(11) DEFAULT NULL COMMENT 'User ID if applicable',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP address of the request',
  `user_agent` text DEFAULT NULL COMMENT 'User agent string',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `user_id` (`user_id`),
  KEY `ip_address` (`ip_address`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Security event logging for audit and monitoring';

-- Blocked IPs table
CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL COMMENT 'Blocked IP address',
  `reason` text DEFAULT NULL COMMENT 'Reason for blocking',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When the block expires (NULL = permanent)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'IP addresses blocked for security reasons';

-- Rate limiting table
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL COMMENT 'Rate limit identifier (IP, user_id, etc.)',
  `action` varchar(50) NOT NULL COMMENT 'Action being rate limited',
  `count` int(11) NOT NULL DEFAULT 0 COMMENT 'Current count',
  `window_start` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Start of rate limit window',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier_action` (`identifier`, `action`),
  KEY `window_start` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Rate limiting data for API and form submissions';

-- Security settings table
CREATE TABLE IF NOT EXISTS `security_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT 'Setting key',
  `setting_value` text NOT NULL COMMENT 'Setting value',
  `description` text DEFAULT NULL COMMENT 'Setting description',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Security configuration settings';

-- Insert default security settings
INSERT INTO `security_settings` (`setting_key`, `setting_value`, `description`) VALUES
('max_login_attempts', '5', 'Maximum failed login attempts before account lockout'),
('lockout_duration', '3600', 'Account lockout duration in seconds'),
('session_timeout', '3600', 'Session timeout in seconds'),
('password_min_length', '8', 'Minimum password length'),
('password_require_special', '1', 'Require special characters in passwords'),
('two_factor_enabled', '0', 'Enable two-factor authentication'),
('ip_whitelist', '', 'Comma-separated list of whitelisted IP addresses'),
('security_headers_enabled', '1', 'Enable security headers'),
('csrf_protection_enabled', '1', 'Enable CSRF protection'),
('rate_limiting_enabled', '1', 'Enable rate limiting'),
('fraud_detection_enabled', '1', 'Enable fraud detection'),
('suspicious_activity_threshold', '10', 'Threshold for suspicious activity detection');

-- Security alerts table
CREATE TABLE IF NOT EXISTS `security_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(50) NOT NULL COMMENT 'Type of security alert',
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `title` varchar(255) NOT NULL COMMENT 'Alert title',
  `message` text NOT NULL COMMENT 'Alert message',
  `details` json DEFAULT NULL COMMENT 'Additional alert details',
  `user_id` int(11) DEFAULT NULL COMMENT 'Affected user ID if applicable',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address involved',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether alert has been read',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alert_type` (`alert_type`),
  KEY `severity` (`severity`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Security alerts and notifications';

-- Device fingerprinting table
CREATE TABLE IF NOT EXISTS `device_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'User ID if logged in',
  `fingerprint_hash` varchar(64) NOT NULL COMMENT 'Device fingerprint hash',
  `user_agent` text DEFAULT NULL COMMENT 'User agent string',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address',
  `device_type` varchar(50) DEFAULT NULL COMMENT 'Device type (mobile, desktop, tablet)',
  `browser` varchar(100) DEFAULT NULL COMMENT 'Browser information',
  `os` varchar(100) DEFAULT NULL COMMENT 'Operating system',
  `screen_resolution` varchar(20) DEFAULT NULL COMMENT 'Screen resolution',
  `timezone` varchar(50) DEFAULT NULL COMMENT 'Timezone',
  `language` varchar(10) DEFAULT NULL COMMENT 'Language preference',
  `is_trusted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether device is trusted',
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fingerprint_hash` (`fingerprint_hash`),
  KEY `is_trusted` (`is_trusted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Device fingerprinting for security and fraud detection';

-- Security audit trail table
CREATE TABLE IF NOT EXISTS `security_audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'User ID if applicable',
  `action` varchar(100) NOT NULL COMMENT 'Action performed',
  `resource` varchar(100) DEFAULT NULL COMMENT 'Resource affected',
  `resource_id` int(11) DEFAULT NULL COMMENT 'Resource ID if applicable',
  `old_values` json DEFAULT NULL COMMENT 'Previous values (for updates)',
  `new_values` json DEFAULT NULL COMMENT 'New values (for updates)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address',
  `user_agent` text DEFAULT NULL COMMENT 'User agent',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Session ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `resource` (`resource`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT = 'Comprehensive audit trail for security monitoring'; 