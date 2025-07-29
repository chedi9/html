<?php
/**
 * PCI Compliance Helper Functions
 * Contains functions that were removed from SQL due to database user privileges
 */

/**
 * Get PCI Compliance Status Report
 */
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

/**
 * Get PCI Compliance Status for all tables
 */
function getPCIComplianceStatus($pdo) {
    $status = [];
    
    // Payment audit logs status
    $stmt = $pdo->prepare("
        SELECT 
            'payment_audit_logs' as table_name,
            COUNT(*) as record_count,
            MAX(created_at) as last_record_date,
            'Active' as status
        FROM payment_audit_logs
    ");
    $stmt->execute();
    $status[] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Payment tokens status
    $stmt = $pdo->prepare("
        SELECT 
            'payment_tokens' as table_name,
            COUNT(*) as record_count,
            MAX(created_at) as last_record_date,
            CASE WHEN COUNT(*) > 0 THEN 'Active' ELSE 'Inactive' END as status
        FROM payment_tokens
    ");
    $stmt->execute();
    $status[] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Encrypted payment data status
    $stmt = $pdo->prepare("
        SELECT 
            'encrypted_payment_data' as table_name,
            COUNT(*) as record_count,
            MAX(created_at) as last_record_date,
            CASE WHEN COUNT(*) > 0 THEN 'Active' ELSE 'Inactive' END as status
        FROM encrypted_payment_data
    ");
    $stmt->execute();
    $status[] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // PCI compliance settings status
    $stmt = $pdo->prepare("
        SELECT 
            'pci_compliance_settings' as table_name,
            COUNT(*) as record_count,
            MAX(created_at) as last_record_date,
            'Active' as status
        FROM pci_compliance_settings
    ");
    $stmt->execute();
    $status[] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $status;
}

/**
 * Get Payment Security Summary
 */
function getPaymentSecuritySummary($pdo, $days = 30) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            payment_method,
            COUNT(*) as total_attempts,
            COUNT(CASE WHEN action = 'payment_success' THEN 1 END) as successful_payments,
            COUNT(CASE WHEN action = 'payment_error' THEN 1 END) as failed_payments,
            COUNT(CASE WHEN action = 'payment_attempt' THEN 1 END) as payment_attempts,
            SUM(amount) as total_amount
        FROM payment_audit_logs
        WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at), payment_method
        ORDER BY date DESC, payment_method
    ");
    $stmt->execute([$days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cleanup Expired Payment Data
 */
function cleanupExpiredPaymentData($pdo) {
    $stmt = $pdo->prepare("SELECT data_type, retention_days FROM payment_data_retention WHERE is_active = 1");
    $stmt->execute();
    $retention_policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cleaned_records = 0;
    
    foreach ($retention_policies as $policy) {
        $data_type = $policy['data_type'];
        $retention_days = $policy['retention_days'];
        
        switch ($data_type) {
            case 'payment_audit_logs':
                $stmt = $pdo->prepare("DELETE FROM payment_audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$retention_days]);
                $cleaned_records += $stmt->rowCount();
                break;
            case 'payment_tokens':
                $stmt = $pdo->prepare("DELETE FROM payment_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$retention_days]);
                $cleaned_records += $stmt->rowCount();
                break;
            case 'payment_security_events':
                $stmt = $pdo->prepare("DELETE FROM payment_security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$retention_days]);
                $cleaned_records += $stmt->rowCount();
                break;
            case 'payment_rate_limits':
                $stmt = $pdo->prepare("DELETE FROM payment_rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$retention_days]);
                $cleaned_records += $stmt->rowCount();
                break;
        }
    }
    
    return $cleaned_records;
}

/**
 * Get PCI Compliance Statistics
 */
function getPCIComplianceStats($pdo) {
    $stats = [];
    
    // Total orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders");
    $stmt->execute();
    $stats['total_orders'] = $stmt->fetchColumn();
    
    // PCI compliant orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as pci_compliant_orders FROM orders WHERE pci_compliant = 1");
    $stmt->execute();
    $stats['pci_compliant_orders'] = $stmt->fetchColumn();
    
    // Non-PCI compliant orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as non_pci_compliant_orders FROM orders WHERE pci_compliant = 0");
    $stmt->execute();
    $stats['non_pci_compliant_orders'] = $stmt->fetchColumn();
    
    // Payment audit logs (last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as audit_logs_30_days FROM payment_audit_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $stats['audit_logs_30_days'] = $stmt->fetchColumn();
    
    // Active payment tokens
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_tokens FROM payment_tokens WHERE is_active = 1");
    $stmt->execute();
    $stats['active_tokens'] = $stmt->fetchColumn();
    
    // Successful encrypted payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as successful_encrypted_payments FROM encrypted_payment_data WHERE status = 'success'");
    $stmt->execute();
    $stats['successful_encrypted_payments'] = $stmt->fetchColumn();
    
    // Unresolved security events
    $stmt = $pdo->prepare("SELECT COUNT(*) as unresolved_security_events FROM payment_security_events WHERE resolved = 0 AND severity IN ('high', 'critical')");
    $stmt->execute();
    $stats['unresolved_security_events'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Get Recent Payment Activity
 */
function getRecentPaymentActivity($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT 
            payment_method,
            amount,
            action,
            transaction_id,
            ip_address,
            created_at
        FROM payment_audit_logs
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Payment Method Distribution
 */
function getPaymentMethodDistribution($pdo, $days = 30) {
    $stmt = $pdo->prepare("
        SELECT 
            payment_method,
            COUNT(*) as total_attempts,
            COUNT(CASE WHEN action = 'payment_success' THEN 1 END) as successful_payments,
            COUNT(CASE WHEN action = 'payment_error' THEN 1 END) as failed_payments,
            SUM(amount) as total_amount
        FROM payment_audit_logs
        WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY payment_method
        ORDER BY total_attempts DESC
    ");
    $stmt->execute([$days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?> 