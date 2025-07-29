<?php
/**
 * Unified Dashboard with Role-Based Permissions
 * All users see the same dashboard but with different access levels
 */

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
    'admin' => ['products', 'orders', 'reviews', 'categories', 'disabled_sellers', 'bulk_upload', 'activity', 'newsletter', 'email_campaigns', 'seller_tips', 'seller_analytics', 'automated_reports', 'returns', 'payment_settings', 'payment_analytics', 'view_security'], // Can view security but not edit
    'moderator' => ['orders', 'reviews', 'activity', 'returns', 'view_security'], // Can view security but not edit
    'security_personnel' => ['security_dashboard', 'security_features', 'security_logs', 'security_monitoring', 'view_all'] // Can view everything but only edit security
];

// Get allowed features for current user
$allowed_features = $role_permissions[$user_role] ?? ['orders', 'reviews'];

// Helper function to check if user can access a feature
function canAccess($feature) {
    global $allowed_features, $user_role;
    return in_array('all', $allowed_features) || in_array($feature, $allowed_features);
}

// Helper function to check if user can edit a feature
function canEdit($feature) {
    global $user_role;
    if ($user_role === 'superadmin') return true;
    if ($user_role === 'security_personnel' && strpos($feature, 'security') !== false) return true;
    if ($user_role === 'admin' && strpos($feature, 'security') === false) return true;
    if ($user_role === 'moderator' && in_array($feature, ['orders', 'reviews', 'activity', 'returns'])) return true;
    return false;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المشرف - Unified Dashboard</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            direction: rtl;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: 1;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .dashboard-header h2 {
            font-size: 2.8em;
            margin-bottom: 15px;
            font-weight: 800;
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2em;
            margin-bottom: 25px;
            font-weight: 300;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: #fff;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .logout-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
            background: linear-gradient(135deg, #ff5252, #d32f2f);
        }
        
        .user-info {
            margin-top: 20px;
            padding: 15px 25px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            display: inline-block;
        }
        
        .role-badge {
            margin-right: 15px;
            margin-left: 15px;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85em;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .role-superadmin { 
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            box-shadow: 0 3px 10px rgba(142, 68, 173, 0.4);
        }
        .role-admin { 
            background: linear-gradient(135deg, #3498db, #2980b9);
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.4);
        }
        .role-moderator { 
            background: linear-gradient(135deg, #f39c12, #e67e22);
            box-shadow: 0 3px 10px rgba(243, 156, 18, 0.4);
        }
        .role-security_personnel { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.4);
        }
        
        .permission-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 2px solid rgba(33, 150, 243, 0.2);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.1);
        }
        
        .permission-info h4 {
            color: #1565c0;
            margin-bottom: 15px;
            font-size: 1.3em;
            font-weight: 700;
        }
        
        .permission-info ul {
            margin: 0;
            padding-right: 20px;
        }
        
        .permission-info li {
            margin-bottom: 8px;
            color: #1976d2;
            font-weight: 500;
            line-height: 1.6;
        }
        
        .section-title {
            font-size: 2em;
            color: #2c3e50;
            margin: 40px 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid #ecf0f1;
            position: relative;
            font-weight: 700;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            right: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .dashboard-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .nav-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 20px;
            padding: 30px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .nav-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .nav-card:hover::before {
            opacity: 1;
        }
        
        .nav-card.read-only {
            opacity: 0.8;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-color: #dee2e6;
        }
        
        .nav-card.read-only::after {
            content: "عرض فقط";
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
        }
        
        .nav-card h3 {
            font-size: 1.4em;
            margin-bottom: 12px;
            color: #2c3e50;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        
        .nav-card p {
            color: #6c757d;
            font-size: 0.95em;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }
        
        .nav-card .icon {
            font-size: 2.5em;
            margin-bottom: 20px;
            display: block;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
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
        .nav-card:nth-child(15) { animation-delay: 1.5s; }
        .nav-card:nth-child(16) { animation-delay: 1.6s; }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .dashboard-container {
                margin: 10px;
                padding: 20px;
            }
            
            .dashboard-nav {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .dashboard-header h2 {
                font-size: 2.2em;
            }
            
            .logout-btn {
                position: static;
                display: inline-block;
                margin-bottom: 20px;
            }
            
            .section-title {
                font-size: 1.6em;
            }
        }
        
        /* Floating particles effect */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 1s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 3s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 5s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 2s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 4s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 1s; }
    </style>
</head>
<body>
    <!-- Floating particles -->
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h2>🔐 لوحة تحكم المشرف الموحدة</h2>
            <p class="dashboard-subtitle">مرحبًا بك في لوحة التحكم الموحدة مع صلاحيات محددة حسب الدور</p>
            <a href="logout.php" class="logout-btn">تسجيل خروج</a>
            
            <div class="user-info">
                <strong>مرحبًا:</strong> <?php echo htmlspecialchars($user_name); ?>
                <span class="role-badge role-<?php echo $user_role; ?>">
                    <?php echo strtoupper(str_replace('_', ' ', $user_role)); ?>
                </span>
            </div>
        </div>

        <!-- Permission Info -->
        <div class="permission-info">
            <h4>📋 معلومات الصلاحيات</h4>
            <ul>
                <?php if ($user_role === 'superadmin'): ?>
                    <li>🔴 <strong>مدير عام:</strong> يمكنك الوصول إلى جميع الأقسام وتعديلها</li>
                <?php elseif ($user_role === 'admin'): ?>
                    <li>🔵 <strong>مدير:</strong> يمكنك إدارة المنتجات والطلبات والمراجعات، ويمكنك مشاهدة الأمان فقط</li>
                <?php elseif ($user_role === 'moderator'): ?>
                    <li>🟡 <strong>مشرف:</strong> يمكنك إدارة الطلبات والمراجعات، ويمكنك مشاهدة الأمان فقط</li>
                <?php elseif ($user_role === 'security_personnel'): ?>
                    <li>🔴 <strong>موظف الأمان:</strong> يمكنك مشاهدة جميع الأقسام، ولكن يمكنك تعديل الأمان فقط</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Security Section -->
        <h3 class="section-title">🛡️ الأمان والمراقبة</h3>
        <div class="dashboard-nav">
            <?php if (canAccess('security_dashboard') || canAccess('view_security')): ?>
                <a href="security_dashboard.php" class="nav-card <?php echo !canEdit('security_dashboard') ? 'read-only' : ''; ?>">
                    <span class="icon">🔒</span>
                    <h3>لوحة الأمان</h3>
                    <p>مراقبة الأمان والتهديدات والأنشطة المشبوهة</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('security_logs')): ?>
                <a href="security_logs.php" class="nav-card <?php echo !canEdit('security_logs') ? 'read-only' : ''; ?>">
                    <span class="icon">📊</span>
                    <h3>سجلات الأمان</h3>
                    <p>عرض سجلات الأمان والأحداث والتنبيهات</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('security_monitoring')): ?>
                <a href="advanced_security_monitoring.php" class="nav-card <?php echo !canEdit('security_monitoring') ? 'read-only' : ''; ?>">
                    <span class="icon">👁️</span>
                    <h3>المراقبة المباشرة</h3>
                    <p>مراقبة الأنشطة المباشرة والتهديدات الفورية</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('security_features')): ?>
                <a href="security_features.php" class="nav-card <?php echo !canEdit('security_features') ? 'read-only' : ''; ?>">
                    <span class="icon">⚙️</span>
                    <h3>إعدادات الأمان</h3>
                    <p>تفعيل وتعطيل ميزات الأمان</p>
                </a>
            <?php endif; ?>
        </div>

        <!-- Management Section -->
        <h3 class="section-title">📦 إدارة المحتوى</h3>
        <div class="dashboard-nav">
            <?php if (canAccess('products')): ?>
                <a href="products.php" class="nav-card <?php echo !canEdit('products') ? 'read-only' : ''; ?>">
                    <span class="icon">📦</span>
                    <h3>المنتجات</h3>
                    <p>إدارة المنتجات والمخزون والتصنيفات</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('orders')): ?>
                <a href="orders.php" class="nav-card <?php echo !canEdit('orders') ? 'read-only' : ''; ?>">
                    <span class="icon">🛒</span>
                    <h3>الطلبات</h3>
                    <p>إدارة الطلبات والتتبع والحالة</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('reviews')): ?>
                <a href="reviews.php" class="nav-card <?php echo !canEdit('reviews') ? 'read-only' : ''; ?>">
                    <span class="icon">⭐</span>
                    <h3>المراجعات</h3>
                    <p>إدارة مراجعات المنتجات والردود</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('categories')): ?>
                <a href="categories.php" class="nav-card <?php echo !canEdit('categories') ? 'read-only' : ''; ?>">
                    <span class="icon">📂</span>
                    <h3>التصنيفات</h3>
                    <p>إدارة تصنيفات المنتجات والتنظيم</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('bulk_upload')): ?>
                <a href="bulk_upload.php" class="nav-card <?php echo !canEdit('bulk_upload') ? 'read-only' : ''; ?>">
                    <span class="icon">📊</span>
                    <h3>رفع المنتجات بالجملة</h3>
                    <p>استيراد منتجات متعددة من ملف CSV</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('disabled_sellers')): ?>
                <a href="disabled_sellers.php" class="nav-card <?php echo !canEdit('disabled_sellers') ? 'read-only' : ''; ?>">
                    <span class="icon">🌟</span>
                    <h3>البائعون ذوو الإعاقة</h3>
                    <p>إدارة البائعين ذوي الإعاقة ومنتجاتهم</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('returns')): ?>
                <a href="returns.php" class="nav-card <?php echo !canEdit('returns') ? 'read-only' : ''; ?>">
                    <span class="icon">🔄</span>
                    <h3>إدارة الإرجاعات</h3>
                    <p>مراجعة وإدارة طلبات الإرجاع من العملاء</p>
                </a>
            <?php endif; ?>
        </div>

        <!-- Analytics Section -->
        <h3 class="section-title">📈 التحليلات والتقارير</h3>
        <div class="dashboard-nav">
            <?php if (canAccess('seller_analytics')): ?>
                <a href="seller_analytics.php" class="nav-card <?php echo !canEdit('seller_analytics') ? 'read-only' : ''; ?>">
                    <span class="icon">📊</span>
                    <h3>تحليلات البائعين</h3>
                    <p>إحصائيات وتحليلات أداء البائعين</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('payment_analytics')): ?>
                <a href="payment_analytics.php" class="nav-card <?php echo !canEdit('payment_analytics') ? 'read-only' : ''; ?>">
                    <span class="icon">💰</span>
                    <h3>تحليلات المدفوعات</h3>
                    <p>إحصائيات المدفوعات والمعاملات</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('automated_reports')): ?>
                <a href="automated_reports.php" class="nav-card <?php echo !canEdit('automated_reports') ? 'read-only' : ''; ?>">
                    <span class="icon">📋</span>
                    <h3>التقارير التلقائية</h3>
                    <p>التقارير المجدولة والتحديثات التلقائية</p>
                </a>
            <?php endif; ?>
        </div>

        <!-- System Section -->
        <h3 class="section-title">⚙️ إعدادات النظام</h3>
        <div class="dashboard-nav">
            <?php if (canAccess('payment_settings')): ?>
                <a href="payment_settings.php" class="nav-card <?php echo !canEdit('payment_settings') ? 'read-only' : ''; ?>">
                    <span class="icon">💳</span>
                    <h3>إعدادات المدفوعات</h3>
                    <p>إدارة طرق الدفع والبوابات</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('admins')): ?>
                <a href="admins.php" class="nav-card <?php echo !canEdit('admins') ? 'read-only' : ''; ?>">
                    <span class="icon">👥</span>
                    <h3>إدارة المشرفين</h3>
                    <p>إدارة حسابات المشرفين والصلاحيات</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('activity')): ?>
                <a href="activity.php" class="nav-card <?php echo !canEdit('activity') ? 'read-only' : ''; ?>">
                    <span class="icon">📝</span>
                    <h3>سجل النشاط</h3>
                    <p>عرض سجل الأنشطة والإجراءات</p>
                </a>
            <?php endif; ?>
        </div>

        <!-- Communication Section -->
        <h3 class="section-title">📧 التواصل والرسائل</h3>
        <div class="dashboard-nav">
            <?php if (canAccess('newsletter')): ?>
                <a href="newsletter.php" class="nav-card <?php echo !canEdit('newsletter') ? 'read-only' : ''; ?>">
                    <span class="icon">📧</span>
                    <h3>النشرة الإخبارية</h3>
                    <p>إدارة النشرات الإخبارية والرسائل</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('email_campaigns')): ?>
                <a href="email_campaigns.php" class="nav-card <?php echo !canEdit('email_campaigns') ? 'read-only' : ''; ?>">
                    <span class="icon">📨</span>
                    <h3>حملات البريد الإلكتروني</h3>
                    <p>إدارة حملات البريد الإلكتروني والتسويق</p>
                </a>
            <?php endif; ?>
            
            <?php if (canAccess('seller_tips')): ?>
                <a href="seller_tips.php" class="nav-card <?php echo !canEdit('seller_tips') ? 'read-only' : ''; ?>">
                    <span class="icon">💡</span>
                    <h3>نصائح البائعين</h3>
                    <p>نصائح وإرشادات للبائعين</p>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 