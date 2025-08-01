<?php
// Fallback database connection for InfinityFree hosting
// This file tries multiple connection methods to ensure connectivity

$host_options = [
    'localhost',
    'sql211.byetcluster.com',
    '127.0.0.1'
];

$db   = 'if0_38059826_if0_38059826_db';
$user = 'if0_38059826';
$pass = 'asZ4rPSDF180wSJ';
$charset = 'utf8mb4';

$pdo = null;
$connection_error = '';

foreach ($host_options as $host) {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 15,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Test the connection
        $pdo->query("SELECT 1");
        
        // Set timezone to Tunisia (UTC+1)
        $pdo->exec("SET time_zone = '+01:00'");
        
        // Also set PHP timezone
        date_default_timezone_set('Africa/Tunis');
        
        // Connection successful, break the loop
        break;
        
    } catch (PDOException $e) {
        $connection_error .= "Failed to connect with host '$host': " . $e->getMessage() . "\n";
        $pdo = null;
        continue;
    }
}

// If no connection was successful
if ($pdo === null) {
    // Log the error for debugging
    error_log("All database connection attempts failed: " . $connection_error);
    
    // Display user-friendly error message
    echo "<!DOCTYPE html><html><head><title>Database Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:40px;color:#333;}";
    echo ".error{background:#fff3cd;border:1px solid #ffeaa7;padding:20px;border-radius:5px;}";
    echo ".error h1{color:#856404;margin-top:0;}";
    echo ".retry{background:#d1ecf1;border:1px solid #bee5eb;padding:15px;border-radius:5px;margin-top:20px;}";
    echo "</style></head><body>";
    echo "<div class='error'>";
    echo "<h1>Database Connection Error</h1>";
    echo "<p>We're unable to connect to the database at this time.</p>";
    echo "<div class='retry'>";
    echo "<p><strong>This might be temporary. Please try:</strong></p>";
    echo "<ul>";
    echo "<li>Refreshing the page in a few minutes</li>";
    echo "<li>Checking if your hosting account is active</li>";
    echo "<li>Verifying database credentials in hosting control panel</li>";
    echo "</ul>";
    echo "</div>";
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<details style='margin-top:20px;'>";
        echo "<summary>Debug Information (click to expand)</summary>";
        echo "<pre style='background:#f8f9fa;padding:10px;border-radius:3px;'>";
        echo htmlspecialchars($connection_error);
        echo "</pre>";
        echo "</details>";
    }
    echo "</div></body></html>";
    exit;
}

// Define SITE_EMAIL only if it hasn't been defined already
if (!defined('SITE_EMAIL')) {
    define('SITE_EMAIL', 'webuytn0@gmail.com');
}
?>