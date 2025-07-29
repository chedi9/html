<?php
/**
 * PCI Compliance Data Cleanup Script
 * This script cleans up expired payment data according to retention policies
 * Can be run manually or via cron job
 */

require_once 'db.php';
require_once 'pci_compliance_helper.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function
function logCleanup($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

try {
    logCleanup("Starting PCI compliance data cleanup...");
    
    // Get cleanup statistics before cleanup
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_audit_logs");
    $stmt->execute();
    $audit_logs_before = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_tokens");
    $stmt->execute();
    $tokens_before = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_security_events");
    $stmt->execute();
    $security_events_before = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_rate_limits");
    $stmt->execute();
    $rate_limits_before = $stmt->fetchColumn();
    
    logCleanup("Records before cleanup:");
    logCleanup("- Payment audit logs: $audit_logs_before");
    logCleanup("- Payment tokens: $tokens_before");
    logCleanup("- Security events: $security_events_before");
    logCleanup("- Rate limits: $rate_limits_before");
    
    // Perform cleanup
    $cleaned_records = cleanupExpiredPaymentData($pdo);
    
    // Get cleanup statistics after cleanup
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_audit_logs");
    $stmt->execute();
    $audit_logs_after = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_tokens");
    $stmt->execute();
    $tokens_after = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_security_events");
    $stmt->execute();
    $security_events_after = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payment_rate_limits");
    $stmt->execute();
    $rate_limits_after = $stmt->fetchColumn();
    
    logCleanup("Records after cleanup:");
    logCleanup("- Payment audit logs: $audit_logs_after");
    logCleanup("- Payment tokens: $tokens_after");
    logCleanup("- Security events: $security_events_after");
    logCleanup("- Rate limits: $rate_limits_after");
    
    // Calculate records removed
    $audit_logs_removed = $audit_logs_before - $audit_logs_after;
    $tokens_removed = $tokens_before - $tokens_after;
    $security_events_removed = $security_events_before - $security_events_after;
    $rate_limits_removed = $rate_limits_before - $rate_limits_after;
    
    logCleanup("Records removed:");
    logCleanup("- Payment audit logs: $audit_logs_removed");
    logCleanup("- Payment tokens: $tokens_removed");
    logCleanup("- Security events: $security_events_removed");
    logCleanup("- Rate limits: $rate_limits_removed");
    
    logCleanup("PCI compliance data cleanup completed successfully!");
    logCleanup("Total records cleaned: $cleaned_records");
    
} catch (Exception $e) {
    logCleanup("ERROR: " . $e->getMessage());
    exit(1);
}
?> 