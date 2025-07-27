<?php
/**
 * WeBuy Test Runner
 * Executes all automated tests for the WeBuy application
 */

require_once '../db.php';
require_once 'test_framework.php';
require_once 'performance_test.php';

class WeBuyTestRunner {
    private $pdo;
    private $functionalTest;
    private $performanceTest;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->functionalTest = new WeBuyTestFramework($pdo);
        $this->performanceTest = new WeBuyPerformanceTest($pdo);
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeBuy Automated Testing Suite</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .nav {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .nav button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        .nav button:hover {
            background: #0056b3;
        }
        .nav button.active {
            background: #28a745;
        }
        .content {
            padding: 30px;
        }
        .test-section {
            margin-bottom: 40px;
        }
        .test-section h2 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .test-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .success {
            color: #28a745;
        }
        .warning {
            color: #ffc107;
        }
        .danger {
            color: #dc3545;
        }
        .info {
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 WeBuy Testing Suite</h1>
            <p>Comprehensive automated testing for WeBuy e-commerce platform</p>
        </div>
        
        <div class="nav">
            <button onclick="showSection('functional')" class="active">Functional Tests</button>
            <button onclick="showSection('performance')">Performance Tests</button>
            <button onclick="showSection('summary')">Test Summary</button>
            <button onclick="runAllTestsNow()" style="background: #dc3545;">🔄 Run All Tests Now</button>
        </div>
        
        <div class="content">
            <div id="functional" class="test-section">
                <h2>🔧 Functional Tests</h2>
                <p>Testing critical user flows and application functionality</p>
                <div id="functional-output" class="test-output">
                    <div class="loading">Click 'Run All Tests Now' to execute functional tests...</div>
                </div>
            </div>
            
            <div id="performance" class="test-section" style="display: none;">
                <h2>⚡ Performance Tests</h2>
                <p>Testing search, filter, and database query performance</p>
                <div id="performance-output" class="test-output">
                    <div class="loading">Click 'Run All Tests Now' to execute performance tests...</div>
                </div>
            </div>
            
            <div id="summary" class="test-section" style="display: none;">
                <h2>📊 Test Summary</h2>
                <div id="summary-output">
                    <div class="loading">Run tests to see summary...</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let testResults = { functional: null, performance: null };
        
        function showSection(section) {
            try {
                // Hide all sections
                const sections = document.querySelectorAll('.test-section');
                sections.forEach(el => el.style.display = 'none');
                
                // Remove active class from all buttons
                const buttons = document.querySelectorAll('.nav button');
                buttons.forEach(el => el.classList.remove('active'));
                
                // Show selected section
                const targetSection = document.getElementById(section);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
                
                // Add active class to clicked button
                event.target.classList.add('active');
                
            } catch (error) {
                console.error('Error in showSection:', error);
            }
        }
        
        function runAllTestsNow() {
            // Show loading state
            document.getElementById('functional-output').innerHTML = '<div class="loading">Running functional tests...</div>';
            document.getElementById('performance-output').innerHTML = '<div class="loading">Running performance tests...</div>';
            document.getElementById('summary-output').innerHTML = '<div class="loading">Generating summary...</div>';
            
            // Run functional tests
            fetch('run_functional_tests.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('functional-output').innerHTML = data;
                    testResults.functional = data;
                    updateSummary();
                })
                .catch(error => {
                    document.getElementById('functional-output').innerHTML = '<div style="color: #dc3545;">Error running functional tests: ' + error.message + '</div>';
                });
            
            // Run performance tests
            fetch('run_performance_tests.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('performance-output').innerHTML = data;
                    testResults.performance = data;
                    updateSummary();
                })
                .catch(error => {
                    document.getElementById('performance-output').innerHTML = '<div style="color: #dc3545;">Error running performance tests: ' + error.message + '</div>';
                });
        }
        
        function updateSummary() {
            if (testResults.functional && testResults.performance) {
                let summary = '<div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">';
                summary += '<h3>🎯 Overall Test Results</h3>';
                summary += '<p>Both functional and performance tests have been completed.</p>';
                summary += '<p>Check the individual test sections for detailed results.</p>';
                summary += '</div>';
                document.getElementById('summary-output').innerHTML = summary;
            }
        }
    </script>
</body>
</html>
        <?php
    }
    
    /**
     * Run only functional tests
     */
    public function runFunctionalTests() {
        echo "<h1>🔧 WeBuy Functional Tests</h1>";
        $this->functionalTest->runAllTests();
    }
    
    /**
     * Run only performance tests
     */
    public function runPerformanceTests() {
        echo "<h1>⚡ WeBuy Performance Tests</h1>";
        $this->performanceTest->runPerformanceTests();
    }
}

// Run tests based on parameters
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testRunner = new WeBuyTestRunner($pdo);
    
    $testType = $_GET['type'] ?? 'all';
    
    switch ($testType) {
        case 'functional':
            $testRunner->runFunctionalTests();
            break;
        case 'performance':
            $testRunner->runPerformanceTests();
            break;
        default:
            $testRunner->runAllTests();
            break;
    }
}
?> 