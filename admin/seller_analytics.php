<?php
// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_mobile'])) {
    $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['is_mobile'] = $is_mobile ? true : false;
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require '../db.php';
require_once 'email_helper.php';

if (!function_exists('is_all_zero_or_empty')) {
    function is_all_zero_or_empty($arr) {
        if (empty($arr)) return true;
        foreach ($arr as $v) { if ((int)$v !== 0) return false; }
        return true;
    }
}

$page_title = 'تحليلات البائعين';
$page_subtitle = 'إحصائيات متقدمة ونصائح ذكية للبائعين';
$breadcrumb = [
            ['title' => 'الرئيسية', 'url' => 'unified_dashboard.php'],
    ['title' => 'تحليلات البائعين']
];

require 'admin_header.php';

// Handle form submission for sending reports
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'] ?? 'daily';
    $selected_sellers = $_POST['selected_sellers'] ?? [];
    $custom_message = trim($_POST['custom_message'] ?? '');
    
    if (empty($selected_sellers)) {
        $error = 'يرجى اختيار بائع واحد على الأقل';
    } else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($selected_sellers as $seller_id) {
            $seller_data = generateSellerAnalytics($seller_id, $report_type);
            if ($seller_data && sendAnalyticsReport($seller_data, $custom_message)) {
                $success_count++;
                // Log activity
                $admin_id = $_SESSION['admin_id'];
                $action = 'send_analytics_report';
                $details = 'Sent ' . $report_type . ' analytics to seller ID: ' . $seller_id;
                $pdo->prepare('INSERT INTO activity_log (admin_id, action, details) VALUES (?, ?, ?)')->execute([$admin_id, $action, $details]);
            } else {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            $success = "تم إرسال التقرير بنجاح إلى $success_count بائع";
            if ($error_count > 0) {
                $success .= " (فشل في إرسال $error_count تقارير)";
            }
        } else {
            $error = "فشل في إرسال أي تقارير";
        }
    }
}

// Get all sellers
$sellers = $pdo->query("SELECT s.*, u.name, u.email FROM sellers s JOIN users u ON s.user_id = u.id ORDER BY s.store_name")->fetchAll();

// Get recent analytics reports sent
$recent_reports = $pdo->query("SELECT * FROM email_campaigns WHERE type = 'analytics_report' ORDER BY created_at DESC LIMIT 5")->fetchAll();

function generateSellerAnalytics($seller_id, $period = 'daily') {
    global $pdo;
    
    // Calculate date range based on period
    $end_date = date('Y-m-d');
    switch ($period) {
        case 'daily':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $period_text = 'اليوم الماضي';
            break;
        case 'weekly':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            $period_text = 'الأسبوع الماضي';
            break;
        case 'monthly':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            $period_text = 'الشهر الماضي';
            break;
        case 'yearly':
            $start_date = date('Y-m-d', strtotime('-365 days'));
            $period_text = 'السنة الماضية';
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $period_text = 'اليوم الماضي';
    }
    
    // Get seller info
    $stmt = $pdo->prepare("SELECT s.*, u.name, u.email FROM sellers s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch();
    
    if (!$seller) return null;
    
    // Get products statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_products,
            COUNT(CASE WHEN approved = 1 THEN 1 END) as approved_products,
            COUNT(CASE WHEN approved = 0 THEN 1 END) as pending_products,
            AVG(price) as avg_price,
            MIN(price) as min_price,
            MAX(price) as max_price,
            SUM(stock) as total_stock
        FROM products 
        WHERE seller_id = ?
    ");
    $stmt->execute([$seller_id]);
    $products_stats = $stmt->fetch();
    
    // Get orders statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            COUNT(DISTINCT user_id) as unique_customers
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ? 
        AND DATE(o.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$seller_id, $start_date, $end_date]);
    $orders_stats = $stmt->fetch();
    
    // Get reviews statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as avg_rating,
            COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews,
            COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_reviews
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        WHERE p.seller_id = ?
        AND DATE(r.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$seller_id, $start_date, $end_date]);
    $reviews_stats = $stmt->fetch();
    
    // Get top performing products
    $stmt = $pdo->prepare("
        SELECT 
            p.name, p.price, p.stock,
            COUNT(oi.id) as sales_count,
            SUM(oi.quantity) as total_quantity_sold,
            AVG(r.rating) as avg_rating
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.seller_id = ?
        GROUP BY p.id
        ORDER BY sales_count DESC
        LIMIT 5
    ");
    $stmt->execute([$seller_id]);
    $top_products = $stmt->fetchAll();
    
    // Generate insights and recommendations
    $insights = generateInsights($products_stats, $orders_stats, $reviews_stats, $top_products, $period);
    
    return [
        'seller' => $seller,
        'period' => $period,
        'period_text' => $period_text,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'products_stats' => $products_stats,
        'orders_stats' => $orders_stats,
        'reviews_stats' => $reviews_stats,
        'top_products' => $top_products,
        'insights' => $insights
    ];
}

function getSellerChartData($seller_id, $period, $start_date, $end_date) {
    global $pdo;
    
    // Debug logging
    $debug_log = "=== getSellerChartData Debug ===\n";
    $debug_log .= "Seller ID: $seller_id\n";
    $debug_log .= "Period: $period\n";
    $debug_log .= "Start Date: $start_date\n";
    $debug_log .= "End Date: $end_date\n";
    
    // Determine grouping and date format based on period
    switch ($period) {
        case 'daily':
            $group_by = 'DATE(o.created_at)';
            $date_format = '%Y-%m-%d';
            break;
        case 'weekly':
            $group_by = 'YEARWEEK(o.created_at)';
            $date_format = '%Y-%u';
            break;
        case 'monthly':
            $group_by = 'DATE_FORMAT(o.created_at, "%Y-%m")';
            $date_format = '%Y-%m';
            break;
        case 'yearly':
            $group_by = 'YEAR(o.created_at)';
            $date_format = '%Y';
            break;
        default:
            $group_by = 'DATE(o.created_at)';
            $date_format = '%Y-%m-%d';
    }
    
    // Get sales and revenue data for this seller
    $sales_query = "
        SELECT 
            DATE_FORMAT(o.created_at, ?) as date_label,
            COUNT(*) as sales_count,
            SUM(o.total_amount) as revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ? 
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY $group_by
        ORDER BY o.created_at
    ";
    
    $debug_log .= "Sales Query: $sales_query\n";
    $debug_log .= "Sales Query Params: [$date_format, $seller_id, $start_date, $end_date]\n";
    
    $stmt = $pdo->prepare($sales_query);
    $stmt->execute([$date_format, $seller_id, $start_date, $end_date]);
    $sales_data = $stmt->fetchAll();
    
    $debug_log .= "Sales Data Raw: " . print_r($sales_data, true) . "\n";
    
    // Get ratings distribution for this seller
    $ratings_query = "
        SELECT 
            r.rating,
            COUNT(*) as count
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        WHERE p.seller_id = ?
        AND DATE(r.created_at) BETWEEN ? AND ?
        GROUP BY r.rating
        ORDER BY r.rating
    ";
    
    $debug_log .= "Ratings Query: $ratings_query\n";
    $debug_log .= "Ratings Query Params: [$seller_id, $start_date, $end_date]\n";
    
    $stmt = $pdo->prepare($ratings_query);
    $stmt->execute([$seller_id, $start_date, $end_date]);
    $ratings_data = $stmt->fetchAll();
    
    $debug_log .= "Ratings Data Raw: " . print_r($ratings_data, true) . "\n";
    
    // Let's also check if this seller has any products at all
    $products_query = "SELECT COUNT(*) as product_count FROM products WHERE seller_id = ?";
    $stmt = $pdo->prepare($products_query);
    $stmt->execute([$seller_id]);
    $product_count = $stmt->fetchColumn();
    
    $debug_log .= "Products count for seller $seller_id: $product_count\n";
    
    // Check if there are any orders at all in the date range
    $orders_query = "SELECT COUNT(*) as order_count FROM orders WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $pdo->prepare($orders_query);
    $stmt->execute([$start_date, $end_date]);
    $order_count = $stmt->fetchColumn();
    
    $debug_log .= "Total orders in date range: $order_count\n";
    
    // Prepare data arrays
    $labels = [];
    $sales = [];
    $revenue = [];
    $ratings = [0, 0, 0, 0, 0]; // Initialize for 1-5 stars
    
    foreach ($sales_data as $sale) {
        $labels[] = $sale['date_label'];
        $sales[] = (int)$sale['sales_count'];
        $revenue[] = (float)$sale['revenue'];
    }
    
    foreach ($ratings_data as $rating) {
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
    
    $result = [
        'labels' => $labels,
        'sales' => $sales,
        'revenue' => $revenue,
        'ratings' => $ratings
    ];
    
    $debug_log .= "Final Result: " . print_r($result, true) . "\n";
    $debug_log .= "=== End Debug ===\n\n";
    
    error_log($debug_log, 3, __DIR__ . '/seller_chart_debug.log');
    
    return $result;
}

function generateInsights($products_stats, $orders_stats, $reviews_stats, $top_products, $period) {
    $insights = [];
    
    // Product insights
    if ($products_stats['pending_products'] > 0) {
        $insights[] = [
            'type' => 'warning',
            'title' => 'منتجات في انتظار الموافقة',
            'message' => 'لديك ' . $products_stats['pending_products'] . ' منتج في انتظار الموافقة. تأكد من اكتمال المعلومات والصور.',
            'action' => 'راجع المنتجات المعلقة وأكمل المعلومات المطلوبة'
        ];
    }
    
    if ($products_stats['total_stock'] < 10) {
        $insights[] = [
            'type' => 'warning',
            'title' => 'مخزون منخفض',
            'message' => 'إجمالي المخزون منخفض (' . $products_stats['total_stock'] . ' قطعة). فكر في إعادة التخزين.',
            'action' => 'أضف المزيد من المخزون للمنتجات الأكثر مبيعاً'
        ];
    }
    
    // Sales insights
    if ($orders_stats['total_orders'] > 0) {
        $conversion_rate = ($orders_stats['total_orders'] / $products_stats['total_products']) * 100;
        if ($conversion_rate < 5) {
            $insights[] = [
                'type' => 'info',
                'title' => 'معدل تحويل منخفض',
                'message' => 'معدل التحويل من المنتجات إلى المبيعات منخفض (' . round($conversion_rate, 1) . '%).',
                'action' => 'حسن أوصاف المنتجات وأضف صور أفضل'
            ];
        }
        
        if ($orders_stats['avg_order_value'] < 50) {
            $insights[] = [
                'type' => 'info',
                'title' => 'متوسط قيمة الطلب منخفض',
                'message' => 'متوسط قيمة الطلب ' . round($orders_stats['avg_order_value'], 2) . ' د.ت.',
                'action' => 'فكر في العروض المجمعة والمنتجات التكميلية'
            ];
        }
    }
    
    // Review insights
    if ($reviews_stats['total_reviews'] > 0) {
        if ($reviews_stats['avg_rating'] < 4) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'تقييمات منخفضة',
                'message' => 'متوسط التقييم ' . round($reviews_stats['avg_rating'], 1) . '/5.',
                'action' => 'حسن جودة المنتجات وخدمة العملاء'
            ];
        }
        
        $positive_rate = ($reviews_stats['positive_reviews'] / $reviews_stats['total_reviews']) * 100;
        if ($positive_rate < 80) {
            $insights[] = [
                'type' => 'info',
                'title' => 'تحسين التقييمات',
                'message' => round($positive_rate, 1) . '% من التقييمات إيجابية.',
                'action' => 'اطلب تقييمات من العملاء الراضين'
            ];
        }
    }
    
    // Performance insights
    if (count($top_products) > 0) {
        $best_product = $top_products[0];
        if ($best_product['sales_count'] > 0) {
            $insights[] = [
                'type' => 'success',
                'title' => 'المنتج الأفضل أداءً',
                'message' => '"' . $best_product['name'] . '" هو منتجك الأكثر مبيعاً.',
                'action' => 'فكر في إضافة منتجات مشابهة أو تحسين هذا المنتج'
            ];
        }
    }
    
    // General recommendations based on period
    switch ($period) {
        case 'daily':
            $insights[] = [
                'type' => 'info',
                'title' => 'نصيحة يومية',
                'message' => 'راجع الطلبات الجديدة ورد على رسائل العملاء بسرعة.',
                'action' => 'تحقق من الإشعارات والرسائل الجديدة'
            ];
            break;
        case 'weekly':
            $insights[] = [
                'type' => 'info',
                'title' => 'نصيحة أسبوعية',
                'message' => 'حلل أداء المنتجات وخطط للمخزون للأسبوع القادم.',
                'action' => 'راجع الإحصائيات وحدد المنتجات التي تحتاج تحسين'
            ];
            break;
        case 'monthly':
            $insights[] = [
                'type' => 'info',
                'title' => 'نصيحة شهرية',
                'message' => 'حلل الاتجاهات الشهرية وخطط للعروض والترويجات.',
                'action' => 'خطط للعروض الموسمية والمنتجات الجديدة'
            ];
            break;
        case 'yearly':
            $insights[] = [
                'type' => 'info',
                'title' => 'نصيحة سنوية',
                'message' => 'راجع الأداء السنوي وخطط للاستراتيجية القادمة.',
                'action' => 'حدد الأهداف الجديدة وخطط للتوسع'
            ];
            break;
    }
    
    return $insights;
}

function sendAnalyticsReport($analytics_data, $custom_message = '') {
    $seller = $analytics_data['seller'];
    $subject = "تقرير تحليلات " . $analytics_data['period_text'] . " - " . $seller['store_name'];
    
    $html_content = generateAnalyticsEmail($analytics_data, $custom_message);
    
    return sendEmail($seller['email'], $seller['name'], $subject, $html_content);
}

function generateAnalyticsEmail($data, $custom_message = '') {
    $seller = $data['seller'];
    $products_stats = $data['products_stats'];
    $orders_stats = $data['orders_stats'];
    $reviews_stats = $data['reviews_stats'];
    $top_products = $data['top_products'];
    $insights = $data['insights'];

    // Generate real chart data for this specific seller and period
    $seller_chart_data = getSellerChartData($seller['id'], $data['period'], $data['start_date'], $data['end_date']);

    $sales_labels = urlencode(json_encode($seller_chart_data['labels']));
    $sales_data = urlencode(json_encode($seller_chart_data['sales']));
    $revenue_data = urlencode(json_encode($seller_chart_data['revenue']));
    $ratings_data = urlencode(json_encode($seller_chart_data['ratings']));

    $sales_chart_url = "https://quickchart.io/chart?c={type:'bar',data:{labels:$sales_labels,datasets:[{label:'عدد المبيعات',data:$sales_data,backgroundColor:'rgba(102,126,234,0.7)'}]}}";
    $revenue_chart_url = "https://quickchart.io/chart?c={type:'line',data:{labels:$sales_labels,datasets:[{label:'الإيرادات (د.ت)',data:$revenue_data,fill:true,backgroundColor:'rgba(0,191,174,0.2)',borderColor:'rgba(0,191,174,1)'}]}}";
    $ratings_chart_url = "https://quickchart.io/chart?c={type:'doughnut',data:{labels:['⭐','⭐⭐','⭐⭐⭐','⭐⭐⭐⭐','⭐⭐⭐⭐⭐'],datasets:[{label:'عدد التقييمات',data:$ratings_data,backgroundColor:['#e57373','#ffb74d','#fff176','#81c784','#64b5f6']}]} }";

 
    $show_sales_chart = !is_all_zero_or_empty($seller_chart_data['sales']);
    $show_revenue_chart = !is_all_zero_or_empty($seller_chart_data['revenue']);
    $show_ratings_chart = !is_all_zero_or_empty($seller_chart_data['ratings']);

    $html = "
    <!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>تقرير تحليلات WeBuy</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #2c3e50; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px 0;
            }
            .container { 
                max-width: 800px; 
                margin: 0 auto; 
                background: white;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #1A237E 0%, #00BFAE 100%); 
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
                position: relative;
                overflow: hidden;
            }
            .header::before {
                content: '';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-20px) rotate(180deg); }
            }
            .header h1 { 
                font-size: 2.5em; 
                font-weight: 700; 
                margin-bottom: 10px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                position: relative;
                z-index: 1;
            }
            .header p { 
                font-size: 1.1em; 
                opacity: 0.9;
                position: relative;
                z-index: 1;
            }
            .content { 
                padding: 40px 30px; 
                background: #f8f9fa;
            }
            .welcome-section {
                background: white;
                padding: 30px;
                border-radius: 15px;
                margin-bottom: 30px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                border-left: 5px solid #00BFAE;
            }
            .welcome-section h2 {
                color: #1A237E;
                font-size: 1.8em;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .custom-message {
                background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
                padding: 20px;
                border-radius: 12px;
                margin: 25px 0;
                border: 1px solid #e1bee7;
                position: relative;
            }
            .custom-message::before {
                content: '💬';
                position: absolute;
                top: -10px;
                right: 20px;
                background: white;
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 1.2em;
            }
            .charts-row { 
                display: flex; 
                flex-wrap: wrap; 
                gap: 25px; 
                justify-content: center; 
                margin: 35px 0;
            }
            .chart-img-box { 
                background: white;
                border-radius: 15px; 
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                padding: 20px;
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                transition: transform 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            .chart-img-box::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #667eea, #764ba2);
            }
            .chart-img-box:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            }
            .chart-title {
                text-align: center;
                font-weight: 700;
                margin-bottom: 15px;
                color: #1A237E;
                font-size: 1.1em;
                position: relative;
            }
            .chart-title::after {
                content: '';
                position: absolute;
                bottom: -5px;
                left: 50%;
                transform: translateX(-50%);
                width: 50px;
                height: 2px;
                background: linear-gradient(90deg, #667eea, #764ba2);
                border-radius: 1px;
            }
            .no-data-msg { 
                text-align: center; 
                color: #7f8c8d; 
                font-size: 1.1em; 
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 12px; 
                padding: 30px 20px; 
                margin: 0 0 15px 0;
                border: 2px dashed #dee2e6;
                position: relative;
            }
            .no-data-msg::before {
                content: '📊';
                font-size: 2em;
                display: block;
                margin-bottom: 10px;
                opacity: 0.5;
            }
            .stats-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                gap: 20px; 
                margin: 35px 0; 
            }
            .stat-card { 
                background: white; 
                padding: 25px; 
                border-radius: 15px; 
                text-align: center; 
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                border: 1px solid #e0e0e0;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            .stat-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #00BFAE, #1A237E);
            }
            .stat-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            }
            .stat-number { 
                font-size: 2.5em; 
                font-weight: 800; 
                color: #1A237E; 
                margin-bottom: 10px;
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .stat-label { 
                color: #7f8c8d; 
                font-size: 0.95em;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .insights-section { 
                margin: 35px 0; 
            }
            .insight-item { 
                background: white; 
                padding: 25px; 
                margin: 20px 0; 
                border-radius: 15px; 
                border-left: 5px solid #00BFAE;
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                transition: transform 0.3s ease;
            }
            .insight-item:hover {
                transform: translateX(5px);
            }
            .insight-title { 
                font-weight: 700; 
                color: #1A237E; 
                margin-bottom: 12px;
                font-size: 1.1em;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .insight-message { 
                margin-bottom: 15px;
                color: #2c3e50;
                line-height: 1.7;
            }
            .insight-action { 
                background: linear-gradient(135deg, #f0f8ff 0%, #e3f2fd 100%); 
                padding: 15px; 
                border-radius: 10px; 
                font-style: italic;
                border: 1px solid #bbdefb;
                color: #1565c0;
            }
            .top-products { 
                margin: 35px 0; 
            }
            .product-item { 
                background: white; 
                padding: 20px; 
                margin: 15px 0; 
                border-radius: 12px; 
                border: 1px solid #e0e0e0;
                box-shadow: 0 3px 10px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }
            .product-item:hover {
                transform: translateX(5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            }
            .product-name { 
                font-weight: 700; 
                color: #1A237E;
                font-size: 1.1em;
                margin-bottom: 8px;
            }
            .product-stats { 
                color: #7f8c8d; 
                font-size: 0.9em; 
                margin-top: 8px;
                display: flex;
                gap: 15px;
            }
            .footer { 
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
                color: white;
                padding: 30px; 
                text-align: center; 
                border-radius: 0 0 20px 20px;
            }
            .footer p {
                margin: 8px 0;
                opacity: 0.9;
            }
            .btn { 
                display: inline-block; 
                padding: 15px 30px; 
                background: linear-gradient(135deg, #00BFAE 0%, #1A237E 100%);
                color: white; 
                text-decoration: none; 
                border-radius: 25px; 
                margin: 15px 10px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            }
            .period-badge {
                display: inline-block;
                background: rgba(255,255,255,0.2);
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 0.9em;
                margin: 10px 0;
                backdrop-filter: blur(10px);
            }
            .date-range {
                font-size: 0.95em;
                opacity: 0.8;
                margin-top: 5px;
            }
            @media (max-width: 600px) {
                .container { margin: 10px; border-radius: 15px; }
                .header { padding: 30px 20px; }
                .header h1 { font-size: 2em; }
                .content { padding: 25px 20px; }
                .charts-row { gap: 15px; }
                .stats-grid { grid-template-columns: 1fr; }
                .stat-card { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📊 تقرير تحليلات WeBuy</h1>
                <div class='period-badge'>" . $data['period_text'] . "</div>
                <p>" . $seller['store_name'] . "</p>
                <div class='date-range'>من " . $data['start_date'] . " إلى " . $data['end_date'] . "</div>
            </div>
            
            <div class='content'>
                <div class='welcome-section'>
                    <h2>👋 مرحباً " . $seller['name'] . "</h2>
                    <p>إليك تحليل شامل ومفصل لأداء متجرك خلال " . $data['period_text'] . ". نتمنى أن تساعدك هذه البيانات على تحسين أداء متجرك وزيادة مبيعاتك.</p>
                </div>
                
                " . ($custom_message ? "<div class='custom-message'><strong>رسالة خاصة من فريق WeBuy:</strong><br>" . htmlspecialchars($custom_message) . "</div>" : "") . "
                
                <div class='charts-row'>
                    <div class='chart-img-box'>
                        <div class='chart-title'>📈 المبيعات خلال الفترة</div>
                        " . ($show_sales_chart ? "<img src='$sales_chart_url' alt='Sales Chart' style='max-width:320px;width:100%;height:auto;border-radius:8px;'>" : "<div class='no-data-msg'>لا توجد بيانات كافية لعرض الرسم البياني للمبيعات في هذه الفترة.</div>") . "
                    </div>
                    <div class='chart-img-box'>
                        <div class='chart-title'>💰 الإيرادات خلال الفترة</div>
                        " . ($show_revenue_chart ? "<img src='$revenue_chart_url' alt='Revenue Chart' style='max-width:320px;width:100%;height:auto;border-radius:8px;'>" : "<div class='no-data-msg'>لا توجد بيانات كافية لعرض رسم الإيرادات في هذه الفترة.</div>") . "
                    </div>
                    <div class='chart-img-box'>
                        <div class='chart-title'>⭐ توزيع التقييمات</div>
                        " . ($show_ratings_chart ? "<img src='$ratings_chart_url' alt='Ratings Chart' style='max-width:220px;width:100%;height:auto;border-radius:8px;'>" : "<div class='no-data-msg'>لا توجد بيانات كافية لعرض توزيع التقييمات في هذه الفترة.</div>") . "
                    </div>
                </div>
                
                <div class='stats-grid'>
                    <div class='stat-card'>
                        <div class='stat-number'>" . $products_stats['total_products'] . "</div>
                        <div class='stat-label'>إجمالي المنتجات</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>" . $orders_stats['total_orders'] . "</div>
                        <div class='stat-label'>الطلبات</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>" . round($orders_stats['total_revenue'], 2) . " د.ت</div>
                        <div class='stat-label'>إجمالي الإيرادات</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>" . round($orders_stats['avg_order_value'], 2) . " د.ت</div>
                        <div class='stat-label'>متوسط قيمة الطلب</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>" . $reviews_stats['total_reviews'] . "</div>
                        <div class='stat-label'>التقييمات</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>" . round($reviews_stats['avg_rating'], 1) . "/5</div>
                        <div class='stat-label'>متوسط التقييم</div>
                    </div>
                </div>
                
                " . (!empty($insights) ? "
                <div class='insights-section'>
                    <h3 style='color: #1A237E; margin-bottom: 25px; font-size: 1.5em; text-align: center;'>💡 نصائح وتحليلات ذكية</h3>
                    " . implode('', array_map(function($insight) {
                        $icon = $insight['type'] === 'warning' ? '⚠️' : ($insight['type'] === 'success' ? '✅' : '💡');
                        return "
                        <div class='insight-item'>
                            <div class='insight-title'>$icon " . htmlspecialchars($insight['title']) . "</div>
                            <div class='insight-message'>" . htmlspecialchars($insight['message']) . "</div>
                            " . (isset($insight['action']) ? "<div class='insight-action'>" . htmlspecialchars($insight['action']) . "</div>" : "") . "
                        </div>";
                    }, $insights)) . "
                </div>" : "") . "
                
                " . (!empty($top_products) ? "
                <div class='top-products'>
                    <h3 style='color: #1A237E; margin-bottom: 25px; font-size: 1.5em; text-align: center;'>🏆 أفضل المنتجات أداءً</h3>
                    " . implode('', array_map(function($product) {
                        return "
                        <div class='product-item'>
                            <div class='product-name'>" . htmlspecialchars($product['name']) . "</div>
                            <div class='product-stats'>
                                <span>📦 " . $product['sales_count'] . " مبيعات</span>
                                <span>💰 " . round($product['revenue'], 2) . " د.ت</span>
                                <span>⭐ " . round($product['avg_rating'], 1) . "/5</span>
                            </div>
                        </div>";
                    }, $top_products)) . "
                </div>" : "") . "
            </div>
            
            <div class='footer'>
                <p><strong>هذا التقرير تم إنشاؤه تلقائياً من WeBuy</strong></p>
                <p>للمساعدة والدعم، تواصل مع فريق WeBuy</p>
                <p style='margin-top: 20px;'>
                    <a href='#' class='btn'>عرض التقرير الكامل</a>
                    <a href='#' class='btn'>تواصل مع الدعم</a>
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}
?>

<div class="admin-content">
    <div class="content-header">
        <div class="header-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($sellers); ?></div>
                <div class="stat-label">إجمالي البائعين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($recent_reports); ?></div>
                <div class="stat-label">التقارير المرسلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">4</div>
                <div class="stat-label">أنواع التقارير</div>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="analytics-section">
            <h3>إرسال تقارير تحليلات للبائعين</h3>
            <form method="post" class="analytics-form">
                <div class="form-group">
                    <label for="report_type">نوع التقرير:</label>
                    <select name="report_type" id="report_type" class="form-control">
                        <option value="daily">تقرير يومي</option>
                        <option value="weekly">تقرير أسبوعي</option>
                        <option value="monthly">تقرير شهري</option>
                        <option value="yearly">تقرير سنوي</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="custom_message">رسالة مخصصة (اختياري):</label>
                    <textarea name="custom_message" id="custom_message" rows="4" class="form-control" placeholder="أضف رسالة مخصصة للبائعين..."><?php echo isset($_POST['custom_message']) ? htmlspecialchars($_POST['custom_message']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>اختر البائعين:</label>
                    <div class="sellers-grid">
                        <?php if (!empty($sellers)): ?>
                            <?php foreach ($sellers as $seller): ?>
                            <div class="seller-checkbox">
                                <input type="checkbox" name="selected_sellers[]" value="<?php echo $seller['id']; ?>" id="seller_<?php echo $seller['id']; ?>">
                                <label for="seller_<?php echo $seller['id']; ?>">
                                    <strong><?php echo htmlspecialchars($seller['store_name']); ?></strong>
                                    <span class="seller-email"><?php echo htmlspecialchars($seller['email']); ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-sellers">
                                <p>لا يوجد بائعين في النظام حالياً</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">📊</span>
                        إرسال التقارير
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="previewReport()">
                        <span class="btn-icon">👁️</span>
                        معاينة التقرير
                    </button>
                </div>
            </form>
        </div>
        
        <div class="recent-reports-section">
            <h3>التقارير المرسلة مؤخراً</h3>
            <?php if ($recent_reports): ?>
                <div class="reports-list">
                    <?php foreach ($recent_reports as $report): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <span class="report-type"><?php echo htmlspecialchars($report['type']); ?></span>
                            <span class="report-date"><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></span>
                        </div>
                        <div class="report-message"><?php echo htmlspecialchars($report['promo_message']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>لا توجد تقارير مرسلة بعد</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.analytics-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.analytics-section h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.3em;
}

.analytics-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #555;
    font-size: 0.95em;
}

.form-control {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.sellers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.seller-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.seller-checkbox label {
    display: flex;
    flex-direction: column;
    gap: 2px;
    cursor: pointer;
}

.seller-email {
    font-size: 0.85em;
    color: #666;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    margin-top: 20px;
}

.recent-reports-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.recent-reports-section h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.3em;
}

.reports-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.report-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #e0e0e0;
}

.report-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.report-type {
    background: #667eea;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
}

.report-date {
    color: #666;
    font-size: 0.9em;
}

.report-message {
    color: #333;
    font-style: italic;
}

@media (max-width: 768px) {
    .sellers-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .charts-section > div:first-child {
        flex-direction: column;
        gap: 15px;
    }
    
    .charts-section > div:first-child > div:last-child {
        align-self: flex-start;
    }
}

/* Chart loading states */
.chart-loading {
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.chart-container {
    position: relative;
    min-height: 300px;
}

.chart-loading::after {
    content: 'جاري التحميل...';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255, 255, 255, 0.9);
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: bold;
    color: #667eea;
    z-index: 10;
}
</style>

<script>
function previewReport() {
    const reportType = document.getElementById('report_type').value;
    const customMessage = document.getElementById('custom_message').value;
    
    // Create a preview window
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write(`
        <html dir='rtl'>
        <head>
            <title>معاينة التقرير</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 800px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1A237E 0%, #00BFAE 100%); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
                .content { padding: 30px; background: #f9f9f9; }
                .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
                .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .stat-number { font-size: 2em; font-weight: bold; color: #1A237E; margin-bottom: 10px; }
                .stat-label { color: #666; font-size: 0.9em; }
                .footer { background: #eee; padding: 20px; text-align: center; border-radius: 0 0 12px 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📊 تقرير تحليلات WeBuy</h1>
                    <p>معاينة تقرير ${reportType}</p>
                </div>
                <div class='content'>
                    <h2>مرحباً [اسم البائع] 👋</h2>
                    <p>إليك تحليل شامل لأداء متجرك...</p>
                    ${customMessage ? '<div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;"><strong>رسالة خاصة:</strong> ' + customMessage + '</div>' : ''}
                    <div class='stats-grid'>
                        <div class='stat-card'>
                            <div class='stat-number'>25</div>
                            <div class='stat-label'>إجمالي المنتجات</div>
                        </div>
                        <div class='stat-card'>
                            <div class='stat-number'>12</div>
                            <div class='stat-label'>الطلبات</div>
                        </div>
                        <div class='stat-card'>
                            <div class='stat-number'>450.00 د.ت</div>
                            <div class='stat-label'>إجمالي الإيرادات</div>
                        </div>
                        <div class='stat-card'>
                            <div class='stat-number'>4.2/5</div>
                            <div class='stat-label'>متوسط التقييم</div>
                        </div>
                    </div>
                </div>
                <div class='footer'>
                    <p>هذا التقرير تم إنشاؤه تلقائياً من WeBuy</p>
                </div>
            </div>
        </body>
        </html>
    `);
}
</script>

<!-- Add Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="charts-section" style="margin: 40px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>📊 الرسوم البيانية للأداء</h3>
        <div style="display: flex; gap: 10px; align-items: center;">
            <label for="chart_period" style="font-weight: 600; color: #555;">الفترة:</label>
            <select id="chart_period" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; background: white;">
                <option value="daily">يومي (7 أيام)</option>
                <option value="weekly" selected>أسبوعي (4 أسابيع)</option>
                <option value="monthly">شهري (6 أشهر)</option>
                <option value="yearly">سنوي (سنتان)</option>
            </select>
        </div>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 32px;">
        <div class="chart-container" style="flex:1; min-width:320px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 10px rgba(0,0,0,0.07);">
            <h4 style="text-align:center;">المبيعات خلال الفترة</h4>
            <canvas id="salesChart"></canvas>
        </div>
        <div class="chart-container" style="flex:1; min-width:320px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 10px rgba(0,0,0,0.07);">
            <h4 style="text-align:center;">الإيرادات خلال الفترة</h4>
            <canvas id="revenueChart"></canvas>
        </div>
        <div class="chart-container" style="flex:1; min-width:320px; background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 10px rgba(0,0,0,0.07);">
            <h4 style="text-align:center;">توزيع التقييمات</h4>
            <canvas id="ratingsChart"></canvas>
        </div>
    </div>
</div>

<script>
<?php
// Get real analytics data for charts
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

$chart_data = getChartData('weekly');

// Prepare data for JavaScript
$sales_labels = [];
$sales_counts = [];
$revenue_amounts = [];
$ratings_distribution = [0, 0, 0, 0, 0]; // Initialize for 1-5 stars

foreach ($chart_data['sales'] as $sale) {
    $sales_labels[] = $sale['date_label'];
    $sales_counts[] = (int)$sale['sales_count'];
    $revenue_amounts[] = (float)$sale['revenue'];
}

foreach ($chart_data['ratings'] as $rating) {
    if ($rating['rating'] >= 1 && $rating['rating'] <= 5) {
        $ratings_distribution[$rating['rating'] - 1] = (int)$rating['count'];
    }
}
?>

// Global chart variables
let salesChart, revenueChart, ratingsChart;
let currentPeriod = 'weekly';

// Initial data from PHP
const initialSalesLabels = <?php echo json_encode($sales_labels); ?>;
const initialSalesData = <?php echo json_encode($sales_counts); ?>;
const initialRevenueData = <?php echo json_encode($revenue_amounts); ?>;
const initialRatingsData = <?php echo json_encode($ratings_distribution); ?>;

// Initialize charts
function initializeCharts() {
    // Sales Chart
    salesChart = new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels: initialSalesLabels,
        datasets: [{
            label: 'عدد المبيعات',
            data: initialSalesData,
            backgroundColor: 'rgba(102, 126, 234, 0.7)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
    
    // Revenue Chart
    revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: initialSalesLabels,
            datasets: [{
                label: 'الإيرادات (د.ت)',
                data: initialRevenueData,
                fill: true,
                backgroundColor: 'rgba(0, 191, 174, 0.2)',
                borderColor: 'rgba(0, 191, 174, 1)',
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(0, 191, 174, 1)'
            }]
        },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
    
    // Ratings Distribution Chart
    ratingsChart = new Chart(document.getElementById('ratingsChart'), {
        type: 'doughnut',
        data: {
            labels: ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'],
            datasets: [{
                label: 'عدد التقييمات',
                data: initialRatingsData,
                backgroundColor: [
                    '#e57373', '#ffb74d', '#fff176', '#81c784', '#64b5f6'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

// Function to update charts with new data
function updateCharts(period) {
    // Show loading state
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        container.classList.add('chart-loading');
    });
    
    // Fetch new data via AJAX
    fetch('seller_analytics_ajax.php?period=' + period)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update sales chart
                salesChart.data.labels = data.labels;
                salesChart.data.datasets[0].data = data.sales;
                salesChart.update();
                
                // Update revenue chart
                revenueChart.data.labels = data.labels;
                revenueChart.data.datasets[0].data = data.revenue;
                revenueChart.update();
                
                // Update ratings chart
                ratingsChart.data.datasets[0].data = data.ratings;
                ratingsChart.update();
            } else {
                console.error('Error in response:', data.error);
            }
        })
        .catch(error => {
            console.error('Error updating charts:', error);
        })
        .finally(() => {
            // Remove loading state
            chartContainers.forEach(container => {
                container.classList.remove('chart-loading');
            });
        });
}

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Add event listener for period change
    document.getElementById('chart_period').addEventListener('change', function() {
        const newPeriod = this.value;
        if (newPeriod !== currentPeriod) {
            currentPeriod = newPeriod;
            updateCharts(newPeriod);
        }
    });
});
</script>

<?php require 'admin_footer.php'; ?> 