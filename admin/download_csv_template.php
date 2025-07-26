<?php
// Security check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="product_upload_template.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'اسم المنتج (عربي)',
    'اسم المنتج (فرنسي)',
    'اسم المنتج (إنجليزي)',
    'الوصف',
    'السعر',
    'المخزون',
    'التصنيف',
    'اسم البائع',
    'قصة البائع',
    'معرف البائع ذو الإعاقة',
    'منتج ذو أولوية'
];

// Write headers
fputcsv($output, $headers);

// Sample data rows
$sample_rows = [
    [
        'منتج تجريبي 1',
        'Produit Test 1',
        'Test Product 1',
        'وصف المنتج التجريبي الأول',
        '25.50',
        '10',
        'الإلكترونيات',
        'أحمد محمد',
        'بائع متميز يقدم منتجات عالية الجودة',
        '1',
        'yes'
    ],
    [
        'منتج تجريبي 2',
        'Produit Test 2',
        'Test Product 2',
        'وصف المنتج التجريبي الثاني',
        '15.75',
        '5',
        'الملابس',
        'فاطمة علي',
        'صاحبة مشروع صغير تقدم منتجات يدوية',
        '2',
        'no'
    ],
    [
        'منتج تجريبي 3',
        'Produit Test 3',
        'Test Product 3',
        'وصف المنتج التجريبي الثالث',
        '45.00',
        '20',
        'المنزل والحديقة',
        'محمد أحمد',
        'بائع محترف في مجال الديكور',
        '',
        'yes'
    ]
];

// Write sample rows
foreach ($sample_rows as $row) {
    fputcsv($output, $row);
}

// Close the file
fclose($output);
exit();
?> 