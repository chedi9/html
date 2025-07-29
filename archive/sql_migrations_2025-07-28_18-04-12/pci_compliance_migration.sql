-- PCI DSS Compliance Database Migration
-- This script adds necessary tables and modifications for PCI compliance

-- 1. Create payment audit logs table for PCI compliance
CREATE TABLE IF NOT EXISTS `payment_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create payment tokens table for tokenization
CREATE TABLE IF NOT EXISTS `payment_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `gateway_token` varchar(255) DEFAULT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `card_type` varchar(20) DEFAULT NULL,
  `expiry_month` varchar(2) DEFAULT NULL,
  `expiry_year` varchar(4) DEFAULT NULL,
  `masked_holder_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_gateway` (`gateway`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create encrypted payment data table
CREATE TABLE IF NOT EXISTS `encrypted_payment_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `encrypted_data` longtext NOT NULL,
  `payment_token` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_payment` (`order_id`, `payment_method`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_payment_token` (`payment_token`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create PCI compliance settings table
CREATE TABLE IF NOT EXISTS `pci_compliance_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Insert default PCI compliance settings (ignore duplicates)
INSERT IGNORE INTO `pci_compliance_settings` (`setting_key`, `setting_value`, `description`, `is_encrypted`) VALUES
('encryption_key', '', 'Payment data encryption key', 1),
('tokenization_enabled', '1', 'Enable payment tokenization', 0),
('audit_logging_enabled', '1', 'Enable payment audit logging', 0),
('data_retention_days', '365', 'Days to retain payment audit logs', 0),
('mask_sensitive_data', '1', 'Mask sensitive data in logs', 0),
('require_https', '1', 'Require HTTPS for all payment operations', 0),
('max_payment_attempts', '5', 'Maximum payment attempts per hour', 0),
('payment_timeout_seconds', '300', 'Payment session timeout in seconds', 0);

-- 6. Add PCI compliance columns to orders table if they don't exist
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `payment_token` varchar(255) DEFAULT NULL AFTER `payment_details`,
ADD COLUMN IF NOT EXISTS `gateway_transaction_id` varchar(255) DEFAULT NULL AFTER `payment_token`,
ADD COLUMN IF NOT EXISTS `payment_gateway` varchar(50) DEFAULT NULL AFTER `gateway_transaction_id`,
ADD COLUMN IF NOT EXISTS `pci_compliant` tinyint(1) NOT NULL DEFAULT 0 AFTER `payment_gateway`,
ADD COLUMN IF NOT EXISTS `encrypted_payment_data_id` int(11) DEFAULT NULL AFTER `pci_compliant`;

-- 7. Add indexes for PCI compliance
ALTER TABLE `orders` 
ADD INDEX IF NOT EXISTS `idx_payment_token` (`payment_token`),
ADD INDEX IF NOT EXISTS `idx_gateway_transaction_id` (`gateway_transaction_id`),
ADD INDEX IF NOT EXISTS `idx_payment_gateway` (`payment_gateway`),
ADD INDEX IF NOT EXISTS `idx_pci_compliant` (`pci_compliant`);

-- 8. Create payment security events table
CREATE TABLE IF NOT EXISTS `payment_security_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `severity` enum('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `event_details` json DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_resolved` (`resolved`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create payment rate limiting table
CREATE TABLE IF NOT EXISTS `payment_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `identifier_type` enum('ip', 'user_id', 'email') NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `first_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_identifier_method` (`identifier`, `identifier_type`, `payment_method`),
  KEY `idx_identifier` (`identifier`),
  KEY `idx_identifier_type` (`identifier_type`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Create payment data retention policy table
CREATE TABLE IF NOT EXISTS `payment_data_retention` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_type` varchar(50) NOT NULL,
  `retention_days` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_data_type` (`data_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Insert default retention policies (ignore duplicates)
INSERT IGNORE INTO `payment_data_retention` (`data_type`, `retention_days`, `description`) VALUES
('payment_audit_logs', 365, 'Payment audit logs retention period'),
('payment_tokens', 2555, 'Payment tokens retention period (7 years)'),
('encrypted_payment_data', 2555, 'Encrypted payment data retention period'),
('payment_security_events', 730, 'Payment security events retention period'),
('payment_rate_limits', 30, 'Payment rate limiting data retention period');

-- 12. Note: Foreign key constraints are not added due to MyISAM engine compatibility
-- The orders table uses MyISAM engine which doesn't support foreign keys
-- Data integrity will be maintained through application-level checks

-- 13. Note: Views are not created due to database user privileges
-- The following queries will be used directly in PHP code instead of views
-- PCI Compliance Status Query (for use in PHP):
/*
SELECT 
    'payment_audit_logs' as table_name,
    COUNT(*) as record_count,
    MAX(created_at) as last_record_date,
    'Active' as status
FROM payment_audit_logs
UNION ALL
SELECT 
    'payment_tokens' as table_name,
    COUNT(*) as record_count,
    MAX(created_at) as last_record_date,
    CASE WHEN COUNT(*) > 0 THEN 'Active' ELSE 'Inactive' END as status
FROM payment_tokens
UNION ALL
SELECT 
    'encrypted_payment_data' as table_name,
    COUNT(*) as record_count,
    MAX(created_at) as last_record_date,
    CASE WHEN COUNT(*) > 0 THEN 'Active' ELSE 'Inactive' END as status
FROM encrypted_payment_data
UNION ALL
SELECT 
    'pci_compliance_settings' as table_name,
    COUNT(*) as record_count,
    MAX(created_at) as last_record_date,
    'Active' as status
FROM pci_compliance_settings;
*/

-- 14. Note: Payment Security Summary Query (for use in PHP):
/*
SELECT 
    DATE(created_at) as date,
    payment_method,
    COUNT(*) as total_attempts,
    COUNT(CASE WHEN action = 'payment_success' THEN 1 END) as successful_payments,
    COUNT(CASE WHEN action = 'payment_error' THEN 1 END) as failed_payments,
    COUNT(CASE WHEN action = 'payment_attempt' THEN 1 END) as payment_attempts,
    SUM(amount) as total_amount
FROM payment_audit_logs
GROUP BY DATE(created_at), payment_method
ORDER BY date DESC, payment_method;
*/

-- 15. Note: Triggers are not created due to database user privileges
-- The cleanup functionality will be handled in PHP code instead
-- Cleanup functions are available in pci_compliance_helper.php
/*
-- Automatic cleanup functionality (implemented in PHP):
-- - cleanupExpiredPaymentData($pdo) - Cleans up expired payment data
-- - This function can be called manually or via cron job
-- - Triggers are not needed since cleanup is handled at application level
*/

-- 16. Note: Stored procedures are not created due to database user privileges
-- The following functions will be implemented in PHP code instead
-- PCI Compliance Report Function (for use in PHP):
/*
function getPCIComplianceReport($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            'PCI Compliance Status Report' as report_title,
            NOW() as generated_at,
            (SELECT COUNT(*) FROM payment_audit_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)) as audit_logs_last_30_days,
            (SELECT COUNT(*) FROM payment_tokens WHERE is_active = 1) as active_tokens,
            (SELECT COUNT(*) FROM encrypted_payment_data WHERE status = 'success') as successful_encrypted_payments,
            (SELECT COUNT(*) FROM payment_security_events WHERE resolved = 0 AND severity IN ('high', 'critical')) as unresolved_security_events,
            (SELECT COUNT(*) FROM orders WHERE pci_compliant = 1) as pci_compliant_orders,
            (SELECT COUNT(*) FROM orders WHERE pci_compliant = 0) as non_pci_compliant_orders
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
*/

-- Cleanup Expired Payment Data Function (for use in PHP):
/*
function cleanupExpiredPaymentData($pdo) {
    $stmt = $pdo->prepare("SELECT data_type, retention_days FROM payment_data_retention WHERE is_active = 1");
    $stmt->execute();
    $retention_policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($retention_policies as $policy) {
        $data_type = $policy['data_type'];
        $retention_days = $policy['retention_days'];
        
        switch ($data_type) {
            case 'payment_audit_logs':
                $pdo->exec("DELETE FROM payment_audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
                break;
            case 'payment_tokens':
                $pdo->exec("DELETE FROM payment_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
                break;
            case 'payment_security_events':
                $pdo->exec("DELETE FROM payment_security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
                break;
            case 'payment_rate_limits':
                $pdo->exec("DELETE FROM payment_rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
                break;
        }
    }
}
*/

-- 17. Insert sample PCI compliance data for testing (ignore duplicates)
INSERT IGNORE INTO `payment_audit_logs` (`payment_method`, `amount`, `action`, `ip_address`, `user_agent`) VALUES
('card', 100.00, 'payment_attempt', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
('paypal', 150.00, 'payment_success', '192.168.1.2', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
('d17', 75.50, 'payment_success', '192.168.1.3', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)');

-- 18. Update existing orders to mark as non-PCI compliant (will be updated when migrated)
UPDATE `orders` SET `pci_compliant` = 0 WHERE `pci_compliant` IS NULL;

-- Migration completed successfully
SELECT 'PCI DSS Compliance Database Migration Completed Successfully' as status; 