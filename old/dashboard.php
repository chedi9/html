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

// Get user role and info
$user_role = $_SESSION['admin_role'] ?? 'admin';
$user_name = $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Administrator';

// Define role-based access permissions
$role_permissions = [
    'superadmin' => ['all'], // God mode - can do everything
    'admin' => ['products', 'orders', 'reviews', 'categories', 'disabled_sellers', 'activity', 'newsletter', 'email_campaigns', 'seller_tips', 'seller_analytics', 'automated_reports', 'returns', 'payment_settings', 'payment_analytics', 'view_security'], // Can view security but not edit
    'moderator' => ['orders', 'reviews', 'activity', 'returns', 'view_security'], // Can view security but not edit
    'security_personnel' => ['security_dashboard', 'security_features', 'security_logs', 'security_monitoring', 'view_all'] // Can view everything but only edit security
];

// Get allowed features for current user
$allowed_features = $role_permissions[$user_role] ?? ['orders', 'reviews'];
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <?php if (!empty($_SESSION['is_mobile'])): ?>
    <link rel="stylesheet" href="../mobile.css">
    <?php endif; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            direction: rtl;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .dashboard-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dashboard-subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        
        .logout-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: #fff;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        
        .dashboard-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .nav-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #f39c12, #e74c3c);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        
        .nav-card:hover::before {
            transform: scaleX(1);
        }
        
        .nav-card h3 {
            font-size: 1.3em;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .nav-card p {
            color: #7f8c8d;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .nav-icon {
            font-size: 2em;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Special styling for disabled sellers card */
        .nav-card.disabled-sellers {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-color: #ffc107;
        }
        
        .nav-card.disabled-sellers .nav-icon {
            color: #ffc107;
        }
        
        /* Category-specific colors */
        .nav-card.products { border-left: 4px solid #3498db; }
        .nav-card.orders { border-left: 4px solid #2ecc71; }
        .nav-card.reviews { border-left: 4px solid #f39c12; }
        .nav-card.categories { border-left: 4px solid #9b59b6; }
        .nav-card.disabled-sellers { border-left: 4px solid #ffc107; }
        .nav-card.admins { border-left: 4px solid #e74c3c; }
        .nav-card.activity { border-left: 4px solid #1abc9c; }
        .nav-card.newsletter { border-left: 4px solid #34495e; }
        .nav-card.email-campaigns { border-left: 4px solid #e67e22; }
        .nav-card.seller-tips { border-left: 4px solid #16a085; }
        .nav-card.bulk-upload { border-left: 4px solid #9c27b0; }
        .nav-card.seller-analytics { border-left: 4px solid #2980b9; }
        .nav-card.automated-reports { border-left: 4px solid #8e44ad; }
        .nav-card.returns { border-left: 4px solid #e67e22; }
        .nav-card.security-dashboard { border-left: 4px solid #e74c3c; }
        
        .welcome-message {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .welcome-message h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .dashboard-container {
                margin: 10px;
                padding: 20px;
            }
            
            .dashboard-nav {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header h2 {
                font-size: 2em;
            }
            
            .logout-btn {
                position: static;
                display: inline-block;
                margin-bottom: 20px;
            }
        }
        
        /* Animation for cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .nav-card {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .nav-card:nth-child(1) { animation-delay: 0.1s; }
        .nav-card:nth-child(2) { animation-delay: 0.2s; }
        .nav-card:nth-child(3) { animation-delay: 0.3s; }
        .nav-card:nth-child(4) { animation-delay: 0.4s; }
        .nav-card:nth-child(5) { animation-delay: 0.5s; }
        .nav-card:nth-child(6) { animation-delay: 0.6s; }
        .nav-card:nth-child(7) { animation-delay: 0.7s; }
        .nav-card:nth-child(8) { animation-delay: 0.8s; }
        .nav-card:nth-child(9) { animation-delay: 0.9s; }
        .nav-card:nth-child(10) { animation-delay: 1s; }
        .nav-card:nth-child(11) { animation-delay: 1.1s; }
        .nav-card:nth-child(12) { animation-delay: 1.2s; }
        .nav-card:nth-child(13) { animation-delay: 1.3s; }
        .nav-card:nth-child(14) { animation-delay: 1.4s; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
            <h2>لوحة تحكم المشرف</h2>
            <p class="dashboard-subtitle">WeBuy - إدارة متجرك الإلكتروني</p>
            <div style="margin-top: 15px; padding: 10px; background: rgba(52, 152, 219, 0.1); border-radius: 8px; border: 1px solid rgba(52, 152, 219, 0.3);">
                <strong>مرحبًا:</strong> <?php echo htmlspecialchars($user_name); ?>
                                 <span style="margin-right: 10px; margin-left: 10px; padding: 4px 8px; background: #<?php echo $user_role === 'superadmin' ? '8e44ad' : ($user_role === 'security_personnel' ? 'e74c3c' : '3498db'); ?>; color: white; border-radius: 4px; font-size: 0.8em;">
                     <?php echo strtoupper(str_replace('_', ' ', $user_role)); ?>
                 </span>
            </div>
        </div>
        
        <nav class="dashboard-nav">
            <?php if (in_array('security_dashboard', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="security_dashboard.php" class="nav-card security-dashboard">
                <span class="nav-icon">🔒</span>
                <h3>لوحة الأمان</h3>
                <p>مراقبة الأمان والتهديدات والأنشطة المشبوهة</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('security_features', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="security_features.php" class="nav-card security-features">
                <span class="nav-icon">⚙️</span>
                <h3>إعدادات الأمان</h3>
                <p>تفعيل وتعطيل ميزات الأمان</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('products', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="products.php" class="nav-card products">
                <span class="nav-icon">📦</span>
                <h3>إدارة المنتجات</h3>
                <p>إضافة، تعديل، وحذف المنتجات في المتجر</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('bulk_upload', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="bulk_upload.php" class="nav-card bulk-upload">
                <span class="nav-icon">📊</span>
                <h3>رفع المنتجات بالجملة</h3>
                <p>استيراد منتجات متعددة من ملف CSV</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('orders', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="orders.php" class="nav-card orders">
                <span class="nav-icon">🛒</span>
                <h3>إدارة الطلبات</h3>
                <p>متابعة وإدارة طلبات العملاء</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('reviews', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="reviews_management.php" class="nav-card reviews">
                <span class="nav-icon">⭐</span>
                <h3>إدارة المراجعات</h3>
                <p>إدارة المراجعات والأسئلة والأجوبة والبلاغات</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('categories', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="categories.php" class="nav-card categories">
                <span class="nav-icon">📂</span>
                <h3>إدارة التصنيفات</h3>
                <p>تنظيم المنتجات في تصنيفات</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('disabled_sellers', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="disabled_sellers.php" class="nav-card disabled-sellers">
                <span class="nav-icon">🌟</span>
                <h3>البائعون ذوو الإعاقة</h3>
                <p>إدارة البائعين ذوي الإعاقة ومنتجاتهم</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('admins', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="admins.php" class="nav-card admins">
                <span class="nav-icon">👥</span>
                <h3>إدارة المشرفين</h3>
                <p>إدارة حسابات المشرفين والصلاحيات</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('activity', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="activity.php" class="nav-card activity">
                <span class="nav-icon">📊</span>
                <h3>سجل الأنشطة</h3>
                <p>متابعة نشاطات النظام والمستخدمين</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('newsletter', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="newsletter.php" class="nav-card newsletter">
                <span class="nav-icon">📧</span>
                <h3>النشرات الإخبارية</h3>
                <p>إدارة النشرات الإخبارية للمشتركين</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('email_campaigns', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="email_campaigns.php" class="nav-card email-campaigns">
                <span class="nav-icon">📢</span>
                <h3>حملات البريد الإلكتروني</h3>
                <p>إرسال حملات تسويقية للعملاء</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('seller_tips', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="seller_tips.php" class="nav-card seller-tips">
                <span class="nav-icon">💡</span>
                <h3>نصائح البائعين</h3>
                <p>نصائح وإرشادات للبائعين</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('seller_analytics', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="seller_analytics.php" class="nav-card seller-analytics">
                <span class="nav-icon">📈</span>
                <h3>تحليلات البائعين</h3>
                <p>عرض وإرسال التقارير التحليلية للبائعين</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('automated_reports', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="automated_reports.php" class="nav-card automated-reports">
                <span class="nav-icon">📊</span>
                <h3>التقارير الآلية</h3>
                <p>إدارة التقارير اليومية والأسبوعية والشهرية</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('returns', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="returns.php" class="nav-card returns">
                <span class="nav-icon">🔄</span>
                <h3>إدارة الإرجاعات</h3>
                <p>مراجعة وإدارة طلبات الإرجاع من العملاء</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('payment_settings', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="payment_settings.php" class="nav-card payment-settings">
                <span class="nav-icon">💳</span>
                <h3>إعدادات الدفع</h3>
                <p>تكوين بوابات الدفع المختلفة (PayPal, Stripe, D17, Flouci)</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array('payment_analytics', $allowed_features) || $allowed_features === ['all']): ?>
            <a href="payment_analytics.php" class="nav-card payment-analytics">
                <span class="nav-icon">📊</span>
                <h3>تحليلات الدفع</h3>
                <p>مراقبة أداء بوابات الدفع والمعاملات</p>
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="welcome-message">
            <h3>مرحبًا بك في لوحة التحكم!</h3>
            <p>اختر القسم المطلوب من البطاقات أعلاه لإدارة متجرك الإلكتروني</p>
        </div>
    </div>
</body>
</html> 