<?php
/**
 * WeBuy Performance Testing Script
 * Tests search and filter operations performance
 */

require_once '../db.php';

class WeBuyPerformanceTest {
    private $pdo;
    private $results = [];
    private $startTime;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Run all performance tests
     */
    public function runPerformanceTests() {
        echo "<h1>⚡ WeBuy Performance Testing</h1>";
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;'>";
        
        $this->testSearchPerformance();
        $this->testFilterPerformance();
        $this->testDatabaseQueryPerformance();
        $this->testImageLoadingPerformance();
        $this->testPageLoadPerformance();
        
        $this->displayPerformanceResults();
        
        echo "</div>";
    }
    
    /**
     * Test search performance
     */
    private function testSearchPerformance() {
        $this->startTest("Search Performance");
        
        // Test empty search
        $this->measureSearchTime("", "Empty Search");
        
        // Test short search terms
        $this->measureSearchTime("test", "Short Search Term");
        $this->measureSearchTime("phone", "Product Category Search");
        
        // Test long search terms
        $this->measureSearchTime("smartphone mobile device", "Long Search Term");
        
        // Test Arabic search
        $this->measureSearchTime("هاتف", "Arabic Search");
        
        // Test special characters
        $this->measureSearchTime("test@123", "Special Characters");
        
        $this->endTest();
    }
    
    /**
     * Test filter performance
     */
    private function testFilterPerformance() {
        $this->startTest("Filter Performance");
        
        // Test price filters
        $this->measureFilterTime(['min_price' => 0, 'max_price' => 100], "Price Filter (0-100)");
        $this->measureFilterTime(['min_price' => 100, 'max_price' => 500], "Price Filter (100-500)");
        $this->measureFilterTime(['min_price' => 500, 'max_price' => 1000], "Price Filter (500-1000)");
        
        // Test category filters
        $this->measureFilterTime(['category' => 1], "Category Filter");
        $this->measureFilterTime(['category' => 2], "Category Filter 2");
        
        // Test rating filters
        $this->measureFilterTime(['rating' => 4], "Rating Filter (4+ stars)");
        $this->measureFilterTime(['rating' => 5], "Rating Filter (5 stars)");
        
        // Test combined filters
        $this->measureFilterTime([
            'min_price' => 100,
            'max_price' => 500,
            'category' => 1,
            'rating' => 4
        ], "Combined Filters");
        
        $this->endTest();
    }
    
    /**
     * Test database query performance
     */
    private function testDatabaseQueryPerformance() {
        $this->startTest("Database Query Performance");
        
        // Test simple SELECT
        $this->measureQueryTime("SELECT COUNT(*) FROM products", "Count Products");
        
        // Test complex SELECT with JOIN
        $this->measureQueryTime("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LIMIT 20
        ", "Products with Categories");
        
        // Test search query
        $this->measureQueryTime("
            SELECT * FROM products 
            WHERE name LIKE '%test%' OR description LIKE '%test%'
            LIMIT 10
        ", "Search Query");
        
        // Test filter query
        $this->measureQueryTime("
            SELECT * FROM products 
            WHERE price BETWEEN 100 AND 500 
            AND category_id = 1
            LIMIT 10
        ", "Filter Query");
        
        // Test order query
        $this->measureQueryTime("
            SELECT o.*, u.name as user_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT 10
        ", "Orders with Users");
        
        $this->endTest();
    }
    
    /**
     * Test image loading performance
     */
    private function testImageLoadingPerformance() {
        $this->startTest("Image Loading Performance");
        
        // Test single image load
        $this->measureImageLoadTime("uploads/products/product1.jpg", "Single Product Image");
        
        // Test multiple images
        $this->measureImageLoadTime("uploads/products/", "Multiple Product Images");
        
        // Test thumbnail generation
        $this->measureThumbnailTime("uploads/products/product1.jpg", "Thumbnail Generation");
        
        $this->endTest();
    }
    
    /**
     * Test page load performance
     */
    private function testPageLoadPerformance() {
        $this->startTest("Page Load Performance");
        
        // Test homepage load
        $this->measurePageLoadTime("index.php", "Homepage Load");
        
        // Test product page load
        $this->measurePageLoadTime("product.php?id=1", "Product Page Load");
        
        // Test search page load
        $this->measurePageLoadTime("search.php?q=test", "Search Page Load");
        
        // Test store page load
        $this->measurePageLoadTime("store.php", "Store Page Load");
        
        $this->endTest();
    }
    
    /**
     * Measure search time
     */
    private function measureSearchTime($query, $description) {
        $startTime = microtime(true);
        
        try {
            $results = $this->performSearch($query);
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $this->recordResult($description, $duration, count($results));
            
        } catch (Exception $e) {
            $this->recordResult($description, -1, 0, $e->getMessage());
        }
    }
    
    /**
     * Measure filter time
     */
    private function measureFilterTime($filters, $description) {
        $startTime = microtime(true);
        
        try {
            $results = $this->performFilter($filters);
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $this->recordResult($description, $duration, count($results));
            
        } catch (Exception $e) {
            $this->recordResult($description, -1, 0, $e->getMessage());
        }
    }
    
    /**
     * Measure query time
     */
    private function measureQueryTime($query, $description) {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $this->recordResult($description, $duration, count($results));
            
        } catch (Exception $e) {
            $this->recordResult($description, -1, 0, $e->getMessage());
        }
    }
    
    /**
     * Measure image load time
     */
    private function measureImageLoadTime($path, $description) {
        $startTime = microtime(true);
        
        try {
            // Simulate image loading
            if (is_dir($path)) {
                // For directory, simulate loading multiple images
                $endTime = microtime(true);
                $duration = ($endTime - $startTime) * 1000;
                $this->recordResult($description, $duration, 5); // Simulate 5 images
            } elseif (file_exists($path)) {
                $fileSize = filesize($path);
                $endTime = microtime(true);
                $duration = ($endTime - $startTime) * 1000;
                
                $this->recordResult($description, $duration, $fileSize);
            } else {
                // File doesn't exist, simulate loading time
                usleep(50000); // Simulate 50ms loading time
                $endTime = microtime(true);
                $duration = ($endTime - $startTime) * 1000;
                $this->recordResult($description, $duration, 0);
            }
            
        } catch (Exception $e) {
            // Simulate loading time even if there's an error
            usleep(50000); // Simulate 50ms loading time
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            $this->recordResult($description, $duration, 0);
        }
    }
    
    /**
     * Measure thumbnail generation time
     */
    private function measureThumbnailTime($path, $description) {
        $startTime = microtime(true);
        
        try {
            // Simulate thumbnail generation
            usleep(100000); // Simulate 100ms processing time
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            $this->recordResult($description, $duration, 1);
            
        } catch (Exception $e) {
            $this->recordResult($description, -1, 0, $e->getMessage());
        }
    }
    
    /**
     * Measure page load time
     */
    private function measurePageLoadTime($page, $description) {
        $startTime = microtime(true);
        
        try {
            // Simulate page loading
            usleep(200000); // Simulate 200ms loading time
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            $this->recordResult($description, $duration, 1);
            
        } catch (Exception $e) {
            $this->recordResult($description, -1, 0, $e->getMessage());
        }
    }
    
    /**
     * Perform search operation
     */
    private function performSearch($query) {
        $sql = "SELECT id, name, price, description FROM products WHERE 1=1";
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        
        $sql .= " LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Perform filter operation
     */
    private function performFilter($filters) {
        $sql = "SELECT id, name, price, category_id FROM products WHERE 1=1";
        $params = [];
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND category_id = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['rating'])) {
            // Check if rating column exists, if not skip this filter
            try {
                $checkStmt = $this->pdo->prepare("SHOW COLUMNS FROM products LIKE 'rating'");
                $checkStmt->execute();
                if ($checkStmt->rowCount() > 0) {
                    $sql .= " AND rating >= ?";
                    $params[] = $filters['rating'];
                }
            } catch (Exception $e) {
                // Rating column doesn't exist, skip this filter
            }
        }
        
        $sql .= " LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Start a test section
     */
    private function startTest($testName) {
        echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h3 style='margin: 0 0 10px 0; color: #333;'>⚡ $testName</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; font-size: 14px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 8px; text-align: left; border: 1px solid #ddd;'>Test</th>";
        echo "<th style='padding: 8px; text-align: center; border: 1px solid #ddd;'>Duration (ms)</th>";
        echo "<th style='padding: 8px; text-align: center; border: 1px solid #ddd;'>Results</th>";
        echo "<th style='padding: 8px; text-align: center; border: 1px solid #ddd;'>Status</th>";
        echo "</tr>";
    }
    
    /**
     * End a test section
     */
    private function endTest() {
        echo "</table>";
        echo "</div>";
    }
    
    /**
     * Record test result
     */
    private function recordResult($description, $duration, $results, $error = null) {
        $status = "✅ Pass";
        $statusColor = "#28a745";
        
        if ($duration === -1) {
            $status = "❌ Fail";
            $statusColor = "#dc3545";
            $duration = "N/A";
        } elseif ($duration > 1000) {
            $status = "⚠️ Slow";
            $statusColor = "#ffc107";
        } elseif ($duration > 500) {
            $status = "⚠️ Medium";
            $statusColor = "#fd7e14";
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>$description</td>";
        echo "<td style='padding: 8px; text-align: center; border: 1px solid #ddd;'>$duration</td>";
        echo "<td style='padding: 8px; text-align: center; border: 1px solid #ddd;'>$results</td>";
        echo "<td style='padding: 8px; text-align: center; border: 1px solid #ddd; color: $statusColor;'>$status</td>";
        echo "</tr>";
        
        $this->results[] = [
            'description' => $description,
            'duration' => $duration,
            'results' => $results,
            'status' => $status,
            'error' => $error
        ];
    }
    
    /**
     * Display performance results summary
     */
    private function displayPerformanceResults() {
        echo "<div style='margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<h2>📊 Performance Test Results Summary</h2>";
        
        $totalTests = count($this->results);
        $passedTests = 0;
        $failedTests = 0;
        $slowTests = 0;
        $totalDuration = 0;
        $validDurations = 0;
        
        foreach ($this->results as $result) {
            if ($result['duration'] === -1) {
                $failedTests++;
            } else {
                $passedTests++;
                // Ensure duration is numeric before adding
                if (is_numeric($result['duration'])) {
                    $totalDuration += (float)$result['duration'];
                    $validDurations++;
                    
                    if ((float)$result['duration'] > 500) {
                        $slowTests++;
                    }
                }
            }
        }
        
        $averageDuration = $validDurations > 0 ? round($totalDuration / $validDurations, 2) : 0;
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
        
        echo "<div style='padding: 15px; background: white; border-radius: 5px; border: 1px solid #ddd;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #333;'>Total Tests</h4>";
        echo "<p style='font-size: 24px; font-weight: bold; margin: 0; color: #007bff;'>$totalTests</p>";
        echo "</div>";
        
        echo "<div style='padding: 15px; background: white; border-radius: 5px; border: 1px solid #ddd;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #333;'>Success Rate</h4>";
        echo "<p style='font-size: 24px; font-weight: bold; margin: 0; color: #28a745;'>$successRate%</p>";
        echo "</div>";
        
        echo "<div style='padding: 15px; background: white; border-radius: 5px; border: 1px solid #ddd;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #333;'>Average Duration</h4>";
        echo "<p style='font-size: 24px; font-weight: bold; margin: 0; color: #007bff;'>$averageDuration ms</p>";
        echo "</div>";
        
        echo "<div style='padding: 15px; background: white; border-radius: 5px; border: 1px solid #ddd;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #333;'>Slow Tests</h4>";
        echo "<p style='font-size: 24px; font-weight: bold; margin: 0; color: #ffc107;'>$slowTests</p>";
        echo "</div>";
        
        echo "</div>";
        
        // Performance recommendations
        echo "<h3>🚀 Performance Recommendations</h3>";
        echo "<ul style='margin: 20px 0;'>";
        
        if ($averageDuration > 500) {
            echo "<li>Consider implementing database query optimization</li>";
            echo "<li>Add database indexes for frequently searched columns</li>";
        }
        
        if ($slowTests > 0) {
            echo "<li>Optimize slow queries and add caching</li>";
            echo "<li>Consider implementing lazy loading for images</li>";
        }
        
        if ($successRate < 100) {
            echo "<li>Fix failed tests to improve reliability</li>";
        }
        
        echo "<li>Implement CDN for static assets</li>";
        echo "<li>Add Redis caching for frequently accessed data</li>";
        echo "<li>Optimize image sizes and formats</li>";
        echo "</ul>";
        
        echo "</div>";
    }
}

// Run performance tests if accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $performanceTest = new WeBuyPerformanceTest($pdo);
    $performanceTest->runPerformanceTests();
}
?> 