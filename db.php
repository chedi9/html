<?php
// Database connection settings
// users table will be used for client login/signup
// For InfinityFree hosting, use localhost instead of external hostname
$host = 'localhost';
$db   = 'if0_38059826_if0_38059826_db'; // Change to your database name
$user = 'if0_38059826'; // Change to your database user
$pass = 'asZ4rPSDF180wSJ'; // Change to your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 30, // 30 second timeout for slow connections
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set timezone to Tunisia (UTC+1)
    $pdo->exec("SET time_zone = '+01:00'");
    
    // Also set PHP timezone
    date_default_timezone_set('Africa/Tunis');
    
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly error message
    echo "<!DOCTYPE html><html><head><title>Database Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:40px;color:#333;}";
    echo ".error{background:#fff3cd;border:1px solid #ffeaa7;padding:20px;border-radius:5px;}";
    echo ".error h1{color:#856404;margin-top:0;}";
    echo "</style></head><body>";
    echo "<div class='error'>";
    echo "<h1>Database Connection Error</h1>";
    echo "<p>We're experiencing technical difficulties. Please try again later.</p>";
    echo "<p><strong>Error details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Possible causes:</strong></p>";
    echo "<ul>";
    echo "<li>Database server is temporarily unavailable</li>";
    echo "<li>Network connectivity issues</li>";
    echo "<li>Incorrect database credentials</li>";
    echo "</ul>";
    echo "</div></body></html>";
    exit;
}

// Define SITE_EMAIL only if it hasn't been defined already
if (!defined('SITE_EMAIL')) {
    define('SITE_EMAIL', 'webuytn0@gmail.com');
} 