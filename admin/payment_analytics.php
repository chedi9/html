<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';
require '../lang.php';

// Get payment analytics data
$analytics = [];

// Total transactions
$stmt = $pdo->query("SELECT COUNT(*) as total_transactions FROM payment_logs");
$analytics['total_transactions'] = $stmt->fetch()['total_transactions'];

// Successful transactions
$stmt = $pdo->query("SELECT COUNT(*) as successful_transactions FROM payment_logs WHERE status = 'success'");
$analytics['successful_transactions'] = $stmt->fetch()['successful_transactions'];

// Failed transactions
$stmt = $pdo->query("SELECT COUNT(*) as failed_transactions FROM payment_logs WHERE status = 'failed'");
$analytics['failed_transactions'] = $stmt->fetch()['failed_transactions'];

// Total revenue
$stmt = $pdo->query("SELECT SUM(amount) as total_revenue FROM payment_logs WHERE status = 'success'");
$analytics['total_revenue'] = $stmt->fetch()['total_revenue'] ?? 0;

// Payment method breakdown
$stmt = $pdo->query("SELECT payment_method, COUNT(*) as count, SUM(amount) as total FROM payment_logs WHERE status = 'success' GROUP BY payment_method ORDER BY total DESC");
$analytics['payment_methods'] = $stmt->fetchAll();

// Recent transactions
$stmt = $pdo->query("SELECT pl.*, o.name as customer_name FROM payment_logs pl LEFT JOIN orders o ON pl.order_id = o.id ORDER BY pl.created_at DESC LIMIT 10");
$analytics['recent_transactions'] = $stmt->fetchAll();

// Monthly revenue
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as revenue FROM payment_logs WHERE status = 'success' GROUP BY month ORDER BY month DESC LIMIT 12");
$analytics['monthly_revenue'] = $stmt->fetchAll();

// Success rate
$analytics['success_rate'] = $analytics['total_transactions'] > 0 ? 
    round(($analytics['successful_transactions'] / $analytics['total_transactions']) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تحليلات الدفع - لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .chart-container {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .transactions-table th,
        .transactions-table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e9ecef;
        }
        
        .transactions-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .payment-method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .payment-method-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .payment-method-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .payment-method-count {
            font-size: 1.5em;
            font-weight: 700;
            color: #00BFAE;
            margin-bottom: 5px;
        }
        
        .payment-method-total {
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="analytics-container">
        <div class="dashboard-header">
            <h2>تحليلات الدفع</h2>
            <p class="dashboard-subtitle">مراقبة أداء بوابات الدفع والمعاملات</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($analytics['total_transactions']); ?></div>
                <div class="stat-label">إجمالي المعاملات</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($analytics['successful_transactions']); ?></div>
                <div class="stat-label">المعاملات الناجحة</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $analytics['success_rate']; ?>%</div>
                <div class="stat-label">معدل النجاح</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($analytics['total_revenue'], 2); ?> د.ت</div>
                <div class="stat-label">إجمالي الإيرادات</div>
            </div>
        </div>
        
        <!-- Payment Methods Breakdown -->
        <div class="chart-container">
            <div class="chart-title">توزيع طرق الدفع</div>
            <div class="payment-method-grid">
                <?php foreach ($analytics['payment_methods'] as $method): ?>
                    <div class="payment-method-card">
                        <div class="payment-method-name"><?php echo ucfirst($method['payment_method']); ?></div>
                        <div class="payment-method-count"><?php echo number_format($method['count']); ?></div>
                        <div class="payment-method-total"><?php echo number_format($method['total'], 2); ?> د.ت</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Monthly Revenue Chart -->
        <div class="chart-container">
            <div class="chart-title">الإيرادات الشهرية</div>
            <canvas id="monthlyRevenueChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Recent Transactions -->
        <div class="chart-container">
            <div class="chart-title">آخر المعاملات</div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>العميل</th>
                        <th>طريقة الدفع</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>معرف المعاملة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['recent_transactions'] as $transaction): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['customer_name'] ?? 'غير محدد'); ?></td>
                            <td><?php echo ucfirst($transaction['payment_method']); ?></td>
                            <td><?php echo number_format($transaction['amount'], 2); ?> د.ت</td>
                            <td>
                                <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                    <?php 
                                    switch($transaction['status']) {
                                        case 'success': echo 'نجح'; break;
                                        case 'failed': echo 'فشل'; break;
                                        case 'pending': echo 'قيد المعالجة'; break;
                                        default: echo $transaction['status'];
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Monthly Revenue Chart
        const monthlyData = <?php echo json_encode($analytics['monthly_revenue']); ?>;
        const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'الإيرادات الشهرية (د.ت)',
                    data: monthlyData.map(item => parseFloat(item.revenue)),
                    borderColor: '#00BFAE',
                    backgroundColor: 'rgba(0, 191, 174, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' د.ت';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 