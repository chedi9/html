<?php
// Database connection settings
// users table will be used for client login/signup

// Load .env if present (simple parser) so getenv works on shared hosts
$__envPath = __DIR__ . '/.env';
if (is_file($__envPath) && is_readable($__envPath)) {
    $lines = @file($__envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $ln) {
            if ($ln === '' || $ln[0] === '#') continue;
            $parts = explode('=', $ln, 2);
            if (count($parts) !== 2) continue;
            $k = trim($parts[0]);
            $v = trim($parts[1]);
            $v = trim($v, "\"' ");
            if ($k !== '') {
                putenv("$k=$v");
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }
    }
}

// Defaults (fallbacks)
$host = 'sql211.byetcluster.com';
$db   = 'if0_38059826_if0_38059826_db'; // Change to your database name
$user = 'if0_38059826'; // Change to your database user
$pass = 'asZ4rPSDF180wSJ';
$charset = 'utf8mb4';

// Override from environment if provided
$host = getenv('DB_HOST') ?: $host;
$db   = getenv('DB_NAME') ?: $db;
$user = getenv('DB_USER') ?: $user;
$envPass = getenv('DB_PASSWORD');
if ($envPass !== false && is_string($envPass) && $envPass !== '') { $pass = $envPass; }

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

// Define SITE_EMAIL only if it hasn't been defined already
if (!defined('SITE_EMAIL')) {
    define('SITE_EMAIL', 'webuytn0@gmail.com');
} 