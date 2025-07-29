<?php
session_start();
require_once '../db.php';
require_once '../pci_compliance_helper.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
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
        .security-events { margin-top: 30px; }
        .event-item { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #00BFAE; }
        .severity-critical { border-left-color: #F44336; }
        .severity-high { border-left-color: #FF9800; }
        .severity-medium { border-left-color: #FFC107; }
        .severity-low { border-left-color: #4CAF50; }
        .audit-logs { margin-top: 30px; }
        .log-item { background: #fff; padding: 10px; margin-bottom: 8px; border-radius: 6px; font-size: 0.9em; }
        .action-buttons { margin: 20px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin-right: 10px; }
        .btn-primary { background: #00BFAE; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-warning { background: #FF9800; color: #fff; }
        .compliance-report { background: #fff; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .chart-container { height: 300px; margin: 20px 0; }
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
            <form method="post" style="display: inline;">
                <button type="submit" name="generate_compliance_report" class="btn btn-primary">
                    📊 إنشاء تقرير التوافق
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
                <div class="stat-value"><?php echo number_format($pci_stats['total_orders']); ?></div>
                <p>آخر 30 يوم</p>
            </div>
            
            <div class="stat-card">
                <h3>المدفوعات الناجحة</h3>
                <div class="stat-value compliance-good"><?php echo number_format($pci_stats['successful_payments']); ?></div>
                <p>معدل النجاح: <?php echo $pci_stats['total_orders'] > 0 ? round(($pci_stats['successful_payments'] / $pci_stats['total_orders']) * 100, 1) : 0; ?>%</p>
            </div>
            
            <div class="stat-card">
                <h3>أحداث الأمان</h3>
                <div class="stat-value"><?php echo count($pci_stats['security_events']); ?></div>
                <p>أنواع مختلفة من الأحداث</p>
            </div>
        </div>
        
        <!-- Payment Method Distribution -->
        <div class="stat-card">
            <h3>توزيع طرق الدفع</h3>
            <div class="chart-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>طريقة الدفع</th>
                            <th>عدد المعاملات</th>
                            <th>إجمالي المبلغ</th>
                            <th>متوسط المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_distribution as $method): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($method['payment_method']); ?></td>
                            <td><?php echo number_format($method['count']); ?></td>
                            <td><?php echo number_format($method['total_amount'], 2); ?> د.ت</td>
                            <td><?php echo number_format($method['avg_amount'], 2); ?> د.ت</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- PCI Compliance Status -->
        <div class="stat-card">
            <h3>حالة توافق PCI DSS</h3>
            <table style="width: 100%; border-collapse: collapse;">
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
                        <td><?php echo number_format($status['record_count']); ?></td>
                        <td><?php echo $status['last_record_date'] ? date('Y-m-d H:i', strtotime($status['last_record_date'])) : 'لا توجد بيانات'; ?></td>
                        <td>
                            <span class="<?php echo $status['status'] === 'Active' ? 'compliance-good' : 'compliance-warning'; ?>">
                                <?php echo $status['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Security Events -->
        <div class="security-events">
            <h3>أحداث الأمان</h3>
            <?php if ($pci_stats['security_events']): ?>
                <?php foreach ($pci_stats['security_events'] as $event): ?>
                <div class="event-item severity-<?php echo $event['severity']; ?>">
                    <strong><?php echo htmlspecialchars($event['event_type']); ?></strong>
                    <span class="severity-<?php echo $event['severity']; ?>">(<?php echo $event['severity']; ?>)</span>
                    <br>
                    <small>
                        العدد: <?php echo $event['event_count']; ?> | 
                        آخر حدوث: <?php echo date('Y-m-d H:i', strtotime($event['last_occurrence'])); ?>
                    </small>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>لا توجد أحداث أمان في آخر 30 يوم</p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Audit Logs -->
        <div class="audit-logs">
            <h3>آخر سجلات التدقيق</h3>
            <?php if ($recent_activity): ?>
                <?php foreach ($recent_activity as $log): ?>
                <div class="log-item">
                    <strong><?php echo htmlspecialchars($log['payment_method']); ?></strong> - 
                    <?php echo htmlspecialchars($log['action']); ?> - 
                    <?php echo number_format($log['amount'], 2); ?> د.ت
                    <br>
                    <small>
                        <?php echo $log['user_email'] ? htmlspecialchars($log['user_email']) : 'مستخدم مجهول'; ?> | 
                        <?php echo htmlspecialchars($log['ip_address']); ?> | 
                        <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                    </small>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>لا توجد سجلات تدقيق حديثة</p>
            <?php endif; ?>
        </div>
        
        <!-- Compliance Report -->
        <?php if (isset($compliance_report)): ?>
        <div class="compliance-report">
            <h3>تقرير توافق PCI DSS</h3>
            <p><strong>تاريخ التقرير:</strong> <?php echo $compliance_report['generated_at']; ?></p>
            <p><strong>سجلات التدقيق (آخر 30 يوم):</strong> <?php echo number_format($compliance_report['audit_logs_last_30_days']); ?></p>
            <p><strong>الرموز النشطة:</strong> <?php echo number_format($compliance_report['active_tokens']); ?></p>
            <p><strong>المدفوعات المشفرة الناجحة:</strong> <?php echo number_format($compliance_report['successful_encrypted_payments']); ?></p>
            <p><strong>أحداث الأمان غير المحلولة:</strong> <?php echo number_format($compliance_report['unresolved_security_events']); ?></p>
            <p><strong>الطلبات المتوافقة:</strong> <?php echo number_format($compliance_report['pci_compliant_orders']); ?></p>
            <p><strong>الطلبات غير المتوافقة:</strong> <?php echo number_format($compliance_report['non_pci_compliant_orders']); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Security Summary Chart -->
        <div class="stat-card">
            <h3>ملخص الأمان الأسبوعي</h3>
            <div class="chart-container">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>طريقة الدفع</th>
                            <th>إجمالي المحاولات</th>
                            <th>المدفوعات الناجحة</th>
                            <th>المدفوعات الفاشلة</th>
                            <th>إجمالي المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($security_summary as $summary): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($summary['date'])); ?></td>
                            <td><?php echo htmlspecialchars($summary['payment_method']); ?></td>
                            <td><?php echo number_format($summary['total_attempts']); ?></td>
                            <td><?php echo number_format($summary['successful_payments']); ?></td>
                            <td><?php echo number_format($summary['failed_payments']); ?></td>
                            <td><?php echo number_format($summary['total_amount'], 2); ?> د.ت</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'admin_footer.php'; ?>
</body>
</html> 