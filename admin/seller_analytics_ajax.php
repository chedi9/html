<?php
// Security and compatibility headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
if (session_status() === PHP_SESSION_NONE) session_start();

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require '../db.php';

// Get period from request
$period = $_GET['period'] ?? 'weekly';

// Validate period
$valid_periods = ['daily', 'weekly', 'monthly', 'yearly'];
if (!in_array($period, $valid_periods)) {
    $period = 'weekly';
}

// Get chart data using the same function from seller_analytics.php
function getChartData($period = 'weekly') {
    global $pdo;
    
    $end_date = date('Y-m-d');
    switch ($period) {
        case 'daily':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $group_by = 'DATE(o.created_at)';
            $date_format = '%Y-%m-%d';
            break;
        case 'weekly':
            $start_date = date('Y-m-d', strtotime('-28 days'));
            $group_by = 'YEARWEEK(o.created_at)';
            $date_format = '%Y-%u';
            break;
        case 'monthly':
            $start_date = date('Y-m-d', strtotime('-6 months'));
            $group_by = 'DATE_FORMAT(o.created_at, "%Y-%m")';
            $date_format = '%Y-%m';
            break;
        case 'yearly':
            $start_date = date('Y-m-d', strtotime('-2 years'));
            $group_by = 'YEAR(o.created_at)';
            $date_format = '%Y';
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $group_by = 'DATE(o.created_at)';
            $date_format = '%Y-%m-%d';
    }
    
    // Get sales data
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(o.created_at, ?) as date_label,
            COUNT(*) as sales_count,
            SUM(o.total_amount) as revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY $group_by
        ORDER BY o.created_at
    ");
    $stmt->execute([$date_format, $start_date, $end_date]);
    $sales_data = $stmt->fetchAll();
    
    // Get ratings distribution
    $stmt = $pdo->prepare("
        SELECT 
            r.rating,
            COUNT(*) as count
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        WHERE DATE(r.created_at) BETWEEN ? AND ?
        GROUP BY r.rating
        ORDER BY r.rating
    ");
    $stmt->execute([$start_date, $end_date]);
    $ratings_data = $stmt->fetchAll();
    
    return [
        'sales' => $sales_data,
        'ratings' => $ratings_data
    ];
}

try {
    $chart_data = getChartData($period);
    
    // Prepare data for JavaScript
    $labels = [];
    $sales = [];
    $revenue = [];
    $ratings = [0, 0, 0, 0, 0]; // Initialize for 1-5 stars
    
    foreach ($chart_data['sales'] as $sale) {
        $labels[] = $sale['date_label'];
        $sales[] = (int)$sale['sales_count'];
        $revenue[] = (float)$sale['revenue'];
    }
    
    foreach ($chart_data['ratings'] as $rating) {
        if ($rating['rating'] >= 1 && $rating['rating'] <= 5) {
            $ratings[$rating['rating'] - 1] = (int)$rating['count'];
        }
    }
    
    // If no data, provide default values
    if (empty($labels)) {
        $labels = ['لا توجد بيانات'];
        $sales = [0];
        $revenue = [0];
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'period' => $period,
        'labels' => $labels,
        'sales' => $sales,
        'revenue' => $revenue,
        'ratings' => $ratings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?> 