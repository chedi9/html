<?php
/**
 * Download CSV Template
 * Provides a CSV template for bulk product upload
 */

// Security and compatibility headers
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role
$user_role = $_SESSION['admin_role'] ?? 'admin';

// Check if user has permission
if ($user_role !== 'superadmin' && $user_role !== 'admin') {
    header('Location: unified_dashboard.php');
    exit();
}

// Handle CSV download
if (isset($_GET['download'])) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="product_template.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Create CSV content
    $csv_content = "اسم_المنتج,الوصف,السعر,الكمية,التصنيف,الصورة,الحالة\n";
    $csv_content .= "منتج تجريبي,وصف المنتج التجريبي,100.00,50,إلكترونيات,image1.jpg,متوفر\n";
    $csv_content .= "منتج آخر,وصف المنتج الآخر,75.50,25,ملابس,image2.jpg,متوفر\n";
    
    // Output CSV content
    echo $csv_content;
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تحميل قالب CSV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../beta333.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            direction: rtl;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-size: 1.2em;
            font-weight: 600;
            margin: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border: 2px solid rgba(33, 150, 243, 0.2);
        }
        
        .info-box h3 {
            color: #1565c0;
            margin-bottom: 15px;
        }
        
        .info-box ul {
            margin: 0;
            padding-right: 20px;
        }
        
        .info-box li {
            margin-bottom: 8px;
            color: #1976d2;
        }
        
        .csv-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        
        .csv-preview pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 تحميل قالب CSV</h1>
            <p>قالب لرفع المنتجات بالجملة</p>
        </div>
        
        <a href="bulk_upload.php" class="back-btn">← العودة لرفع المنتجات</a>
        
        <div class="info-box">
            <h3>📋 معلومات القالب</h3>
            <ul>
                <li><strong>اسم_المنتج:</strong> اسم المنتج باللغة العربية</li>
                <li><strong>الوصف:</strong> وصف تفصيلي للمنتج</li>
                <li><strong>السعر:</strong> سعر المنتج بالأرقام فقط (مثال: 100.50)</li>
                <li><strong>الكمية:</strong> عدد المنتجات المتوفرة</li>
                <li><strong>التصنيف:</strong> تصنيف المنتج</li>
                <li><strong>الصورة:</strong> اسم ملف الصورة (مثال: product1.jpg)</li>
                <li><strong>الحالة:</strong> متوفر أو غير متوفر</li>
            </ul>
        </div>
        
        <div class="csv-preview">
            <h3>📄 معاينة القالب</h3>
            <pre>اسم_المنتج,الوصف,السعر,الكمية,التصنيف,الصورة,الحالة
منتج تجريبي,وصف المنتج التجريبي,100.00,50,إلكترونيات,image1.jpg,متوفر
منتج آخر,وصف المنتج الآخر,75.50,25,ملابس,image2.jpg,متوفر</pre>
        </div>
        
        <div style="text-align: center;">
            <a href="?download=1" class="download-btn">⬇️ تحميل قالب CSV</a>
        </div>
        
        <div class="info-box">
            <h3>⚠️ ملاحظات مهمة</h3>
            <ul>
                <li>تأكد من أن جميع الحقول مملوءة</li>
                <li>استخدم الفاصلة (,) كفاصل بين الأعمدة</li>
                <li>لا تستخدم فواصل في النصوص</li>
                <li>تأكد من صحة أسماء الصور</li>
                <li>الحالة يجب أن تكون "متوفر" أو "غير متوفر"</li>
            </ul>
        </div>
    </div>
</body>
</html> 