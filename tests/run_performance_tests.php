<?php
/**
 * WeBuy Performance Tests Runner
 * Called via AJAX to run performance tests
 */

require_once '../db.php';
require_once 'performance_test.php';

// Set content type for AJAX response
header('Content-Type: text/html; charset=utf-8');

try {
    $performanceTest = new WeBuyPerformanceTest($pdo);
    $performanceTest->runPerformanceTests();
} catch (Exception $e) {
    echo "<div style='color: #dc3545; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Error Running Performance Tests</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "</div>";
}
?> 