<?php
// Database connection settings
// users table will be used for client login/signup
$host = 'sql211.byetcluster.com';
$db   = 'if0_38059826_if0_38059826_db'; // Change to your database name
$user = 'if0_38059826'; // Change to your database user
$pass = 'asZ4rPSDF180wSJ'; // Change to your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set timezone to Tunisia (UTC+1)
    $pdo->exec("SET time_zone = '+01:00'");
    
    // Also set PHP timezone
    date_default_timezone_set('Africa/Tunis');
    
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

define('SITE_EMAIL', 'webuytn0@gmail.com'); 