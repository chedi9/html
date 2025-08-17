<?php
// Simple direct API test
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['page'] = 1;
$_GET['lang'] = 'en';

// Start session before any output
session_start();
$_SESSION['lang'] = 'en';

// Capture API output
ob_start();
include 'api/featured-products.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";
