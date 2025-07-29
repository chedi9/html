<?php
/**
 * Fix WAF Patterns Table
 * Checks and fixes the waf_patterns table structure
 */

require_once 'db.php';

echo "<h1>🔧 Fix WAF Patterns Table</h1>";

// Check if waf_patterns table exists and get its structure
try {
    $stmt = $pdo->query("DESCRIBE waf_patterns");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📋 Current Table Structure:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding: 5px;'>{$column['Field']}</td>";
        echo "<td style='padding: 5px;'>{$column['Type']}</td>";
        echo "<td style='padding: 5px;'>{$column['Null']}</td>";
        echo "<td style='padding: 5px;'>{$column['Key']}</td>";
        echo "<td style='padding: 5px;'>{$column['Default']}</td>";
        echo "<td style='padding: 5px;'>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if pattern_type column exists
    $has_pattern_type = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'pattern_type') {
            $has_pattern_type = true;
            break;
        }
    }
    
    if (!$has_pattern_type) {
        echo "<h2>⚠️ Missing pattern_type Column</h2>";
        echo "<p>The <strong>pattern_type</strong> column is missing from the waf_patterns table.</p>";
        
        // Try to add the missing column
        try {
            $pdo->exec("ALTER TABLE waf_patterns ADD COLUMN pattern_type VARCHAR(50) NOT NULL AFTER id");
            echo "<p>✅ Successfully added <strong>pattern_type</strong> column</p>";
            
            // Now try to insert the patterns again
            echo "<h2>🛡️ Adding WAF Patterns...</h2>";
            
            $basic_patterns = [
                ['sql_injection', "('|'')|(\\b(union|select|insert|update|delete|drop|create|alter)\\b)", 'Basic SQL injection patterns'],
                ['xss', "(<script|javascript:|onload=|onerror=)", 'Basic XSS patterns'],
                ['path_traversal', "(\\.\\./|\\.\\.\\\\)", 'Path traversal patterns'],
                ['command_injection', "(;|\\||`|\\$\\()", 'Command injection patterns']
            ];
            
            foreach ($basic_patterns as $pattern) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO waf_patterns (pattern_type, pattern, description) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE pattern = VALUES(pattern)
                    ");
                    $stmt->execute($pattern);
                    echo "<p>✅ Added WAF pattern: <strong>{$pattern[0]}</strong></p>";
                } catch (Exception $e) {
                    echo "<p>❌ Error adding WAF pattern <strong>{$pattern[0]}</strong>: " . $e->getMessage() . "</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Error adding pattern_type column: " . $e->getMessage() . "</p>";
            
            // Alternative: Drop and recreate the table
            echo "<h3>🔄 Attempting to recreate table...</h3>";
            try {
                $pdo->exec("DROP TABLE IF EXISTS waf_patterns");
                $pdo->exec("
                    CREATE TABLE waf_patterns (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        pattern_type VARCHAR(50) NOT NULL,
                        pattern TEXT NOT NULL,
                        description TEXT,
                        is_active TINYINT(1) DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_pattern_type (pattern_type),
                        INDEX idx_is_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                echo "<p>✅ Successfully recreated waf_patterns table</p>";
                
                // Now insert the patterns
                echo "<h3>🛡️ Adding WAF Patterns to New Table...</h3>";
                foreach ($basic_patterns as $pattern) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO waf_patterns (pattern_type, pattern, description) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute($pattern);
                        echo "<p>✅ Added WAF pattern: <strong>{$pattern[0]}</strong></p>";
                    } catch (Exception $e) {
                        echo "<p>❌ Error adding WAF pattern <strong>{$pattern[0]}</strong>: " . $e->getMessage() . "</p>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p>❌ Error recreating table: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<h2>✅ Table Structure is Correct</h2>";
        echo "<p>The waf_patterns table has the correct structure. Let's try adding the patterns again:</p>";
        
        $basic_patterns = [
            ['sql_injection', "('|'')|(\\b(union|select|insert|update|delete|drop|create|alter)\\b)", 'Basic SQL injection patterns'],
            ['xss', "(<script|javascript:|onload=|onerror=)", 'Basic XSS patterns'],
            ['path_traversal', "(\\.\\./|\\.\\.\\\\)", 'Path traversal patterns'],
            ['command_injection', "(;|\\||`|\\$\\()", 'Command injection patterns']
        ];
        
        foreach ($basic_patterns as $pattern) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO waf_patterns (pattern_type, pattern, description) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE pattern = VALUES(pattern)
                ");
                $stmt->execute($pattern);
                echo "<p>✅ Added WAF pattern: <strong>{$pattern[0]}</strong></p>";
            } catch (Exception $e) {
                echo "<p>❌ Error adding WAF pattern <strong>{$pattern[0]}</strong>: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error checking table structure: " . $e->getMessage() . "</p>";
}

echo "<h2>🎉 WAF Patterns Fix Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. Test the security features: <a href='test_security_features.php'>test_security_features.php</a></li>";
echo "<li>2. View the demo: <a href='demo_security_features.php'>demo_security_features.php</a></li>";
echo "<li>3. Access admin security features: <a href='admin/security_features.php'>admin/security_features.php</a></li>";
echo "<li>4. Test admin login: <a href='admin/login.php'>admin/login.php</a></li>";
echo "</ul>";
?> 