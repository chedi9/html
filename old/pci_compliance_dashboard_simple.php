<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Include helper functions directly to avoid path issues
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
    
    // Calculate compliance percentage
    $stats['compliance_percentage'] = $stats['total_orders'] > 0 ? round(($stats['pci_compliant_orders'] / $stats['total_orders']) * 100, 1) : 0;
    
    return $stats;
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

// Get PCI compliance data
$pci_stats = getPCIComplianceStats($pdo);
$pci_status = getPCIComplianceStatus($pdo);
$recent_activity = getRecentPaymentActivity($pdo, 10);
$payment_distribution = getPaymentMethodDistribution($pdo, 30);

// Handle cleanup action
if (isset($_POST['cleanup_data'])) {
    $cleaned_records = cleanupExpiredPaymentData($pdo);
    $cleanup_message = "Cleaned up $cleaned_records expired records.";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم توافق PCI DSS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        .pci-dashboard { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 10px 0; color: #333; }
        .stat-value { font-size: 2em; font-weight: bold; color: #00BFAE; }
        .compliance-good { color: #43A047; }
        .compliance-warning { color: #FF9800; }
        .compliance-danger { color: #F44336; }
        .action-buttons { margin: 20px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-right: 10px; }
        .btn-warning { background: #FF9800; color: #fff; }
        .success-message { background: #4CAF50; color: #fff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="pci-dashboard">
        <h1>🔒 لوحة تحكم توافق PCI DSS</h1>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <form method="post" style="display: inline;">
                <button type="submit" name="cleanup_data" class="btn btn-warning">
                    🗑️ تنظيف البيانات المنتهية الصلاحية
                </button>
            </form>
        </div>
        
        <?php if (isset($cleanup_message)): ?>
            <div class="success-message"><?php echo $cleanup_message; ?></div>
        <?php endif; ?>
        
        <!-- PCI Compliance Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>نسبة التوافق مع PCI DSS</h3>
                <div class="stat-value <?php echo $pci_stats['compliance_percentage'] >= 90 ? 'compliance-good' : ($pci_stats['compliance_percentage'] >= 70 ? 'compliance-warning' : 'compliance-danger'); ?>">
                    <?php echo $pci_stats['compliance_percentage']; ?>%
                </div>
                <p>الطلبات المتوافقة: <?php echo $pci_stats['pci_compliant_orders']; ?> / <?php echo $pci_stats['total_orders']; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>سجلات التدقيق</h3>
                <div class="stat-value"><?php echo number_format($pci_stats['audit_logs_30_days']); ?></div>
                <p>آخر 30 يوم</p>
            </div>
            
            <div class="stat-card">
                <h3>الرموز النشطة</h3>
                <div class="stat-value"><?php echo number_format($pci_stats['active_tokens']); ?></div>
                <p>رموز الدفع النشطة</p>
            </div>
            
            <div class="stat-card">
                <h3>المدفوعات المشفرة</h3>
                <div class="stat-value compliance-good"><?php echo number_format($pci_stats['successful_encrypted_payments']); ?></div>
                <p>المدفوعات المشفرة الناجحة</p>
            </div>
        </div>
        
        <!-- PCI Compliance Status -->
        <div class="compliance-status">
            <h2>حالة التوافق مع PCI DSS</h2>
            <table border="1" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th>الجدول</th>
                        <th>عدد السجلات</th>
                        <th>آخر تحديث</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pci_status as $status): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($status['table_name']); ?></td>
                        <td><?php echo $status['record_count']; ?></td>
                        <td><?php echo $status['last_record_date'] ? $status['last_record_date'] : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($status['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2>النشاط الأخير</h2>
            <?php if ($recent_activity): ?>
                <table border="1" style="border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th>طريقة الدفع</th>
                            <th>المبلغ</th>
                            <th>الإجراء</th>
                            <th>عنوان IP</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $activity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['payment_method']); ?></td>
                            <td><?php echo $activity['amount']; ?></td>
                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                            <td><?php echo $activity['created_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>لا توجد نشاطات حديثة</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'admin_footer.php'; ?>
</body>
</html> 