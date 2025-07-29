-- Rate Limits Migration
-- Creates the rate_limits table for enhanced rate limiting system

-- Drop table if exists to ensure correct structure
DROP TABLE IF EXISTS `rate_limits`;

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rate_key` (`rate_key`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security Reports Migration
-- Creates the security_reports table for storing security test results

-- Drop table if exists to ensure correct structure
DROP TABLE IF EXISTS `security_reports`;

CREATE TABLE `security_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_data` longtext NOT NULL,
  `total_tests` int(11) NOT NULL DEFAULT 0,
  `passed_tests` int(11) NOT NULL DEFAULT 0,
  `failed_tests` int(11) NOT NULL DEFAULT 0,
  `high_risk_issues` int(11) NOT NULL DEFAULT 0,
  `medium_risk_issues` int(11) NOT NULL DEFAULT 0,
  `low_risk_issues` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_total_tests` (`total_tests`),
  KEY `idx_failed_tests` (`failed_tests`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample rate limit records for testing
INSERT IGNORE INTO `rate_limits` (`rate_key`, `ip_address`, `user_agent`, `created_at`) VALUES
('login_192.168.1.1', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW()),
('payment_card_192.168.1.1', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW()),
('registration_192.168.1.2', '192.168.1.2', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW());

-- Insert sample security report
INSERT IGNORE INTO `security_reports` (`report_data`, `total_tests`, `passed_tests`, `failed_tests`, `high_risk_issues`, `medium_risk_issues`, `low_risk_issues`, `created_at`) VALUES
('{"timestamp":"2024-01-01 12:00:00","total_tests":50,"passed_tests":48,"failed_tests":2,"high_risk_issues":0,"medium_risk_issues":1,"low_risk_issues":1,"test_results":{"sql_injection":{"basic_sql_injection":{"payload":"\' OR \'1\'=\'1","status":"PASSED","risk_level":"LOW","recommendation":"Use prepared statements for all database queries"}},"xss":{"basic_xss":{"payload":"<script>alert(\"XSS\")</script>","status":"PASSED","risk_level":"LOW","recommendation":"Use htmlspecialchars() for all output"}}},"recommendations":["Use prepared statements for all database queries","Use htmlspecialchars() for all output"]}', 50, 48, 2, 0, 1, 1, NOW()); 