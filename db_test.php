<?php
// Database connection test for InfinityFree hosting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Test different connection methods
$configs = [
    'original' => [
        'host' => 'sql211.byetcluster.com',
        'db' => 'if0_38059826_if0_38059826_db',
        'user' => 'if0_38059826',
        'pass' => 'asZ4rPSDF180wSJ'
    ],
    'localhost' => [
        'host' => 'localhost',
        'db' => 'if0_38059826_if0_38059826_db',
        'user' => 'if0_38059826',
        'pass' => 'asZ4rPSDF180wSJ'
    ],
    'alt_host' => [
        'host' => 'sql211.byethost.com',
        'db' => 'if0_38059826_if0_38059826_db',
        'user' => 'if0_38059826',
        'pass' => 'asZ4rPSDF180wSJ'
    ]
];

foreach ($configs as $name => $config) {
    echo "<h2>Testing configuration: $name</h2>";
    echo "<p>Host: {$config['host']}</p>";
    echo "<p>Database: {$config['db']}</p>";
    echo "<p>User: {$config['user']}</p>";
    
    $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        echo "<p style='color: green;'>✅ <strong>Connection successful!</strong></p>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ Query test successful: " . $result['test'] . "</p>";
        
        // Check available tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Available tables: " . (count($tables) > 0 ? implode(', ', $tables) : 'No tables found') . "</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ <strong>Connection failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "<hr>";
}

echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>MySQL Extensions:</p>";
echo "<ul>";
echo "<li>PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Available' : 'Not available') . "</li>";
echo "<li>MySQLi: " . (extension_loaded('mysqli') ? 'Available' : 'Not available') . "</li>";
echo "</ul>";

echo "<h2>Network Test</h2>";
$hosts_to_test = ['sql211.byetcluster.com', 'sql211.byethost.com', 'localhost'];
foreach ($hosts_to_test as $host) {
    echo "<p>Testing connectivity to $host...</p>";
    $fp = @fsockopen($host, 3306, $errno, $errstr, 10);
    if ($fp) {
        echo "<p style='color: green;'>✅ Port 3306 is reachable on $host</p>";
        fclose($fp);
    } else {
        echo "<p style='color: red;'>❌ Cannot reach port 3306 on $host (Error: $errno - $errstr)</p>";
    }
}
?>