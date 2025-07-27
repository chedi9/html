<?php
// Automated Reports System for WeBuy
// This file can be called by cron jobs to send automated reports

// Prevent direct access from web
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    die('Access denied');
}

// Simple security key for web access
$cron_key = 'webuy_automated_reports_2024';
if (isset($_GET['cron_key']) && $_GET['cron_key'] !== $cron_key) {
    die('Invalid cron key');
}

require_once '../db.php';
require_once 'email_helper.php';

// Include analytics functions
require_once 'seller_analytics.php';

// Configuration
$config = [
    'daily' => [
        'enabled' => true,
        'time' => '09:00', // Send daily reports at 9 AM
        'sellers' => 'all' // Send to all sellers
    ],
    'weekly' => [
        'enabled' => true,
        'day' => 'monday', // Send weekly reports on Monday
        'time' => '10:00',
        'sellers' => 'all'
    ],
    'monthly' => [
        'enabled' => true,
        'day' => 1, // Send monthly reports on 1st of month
        'time' => '11:00',
        'sellers' => 'all'
    ],
    'yearly' => [
        'enabled' => true,
        'month' => 1, // Send yearly reports in January
        'day' => 1,
        'time' => '12:00',
        'sellers' => 'all'
    ]
];

function shouldSendReport($report_type) {
    global $config;
    
    $now = new DateTime();
    $current_time = $now->format('H:i');
    $current_day = strtolower($now->format('l')); // monday, tuesday, etc.
    $current_date = $now->format('j'); // 1, 2, 3, etc.
    $current_month = $now->format('n'); // 1, 2, 3, etc.
    
    switch ($report_type) {
        case 'daily':
            return $config['daily']['enabled'] && $current_time === $config['daily']['time'];
            
        case 'weekly':
            return $config['weekly']['enabled'] && 
                   $current_day === $config['weekly']['day'] && 
                   $current_time === $config['weekly']['time'];
                   
        case 'monthly':
            return $config['monthly']['enabled'] && 
                   $current_date == $config['monthly']['day'] && 
                   $current_time === $config['monthly']['time'];
                   
        case 'yearly':
            return $config['yearly']['enabled'] && 
                   $current_month == $config['yearly']['month'] && 
                   $current_date == $config['yearly']['day'] && 
                   $current_time === $config['yearly']['time'];
                   
        default:
            return false;
    }
}

function sendAutomatedReports() {
    global $pdo, $config;
    
    $reports_sent = 0;
    $errors = [];
    
    // Get all active sellers
    $sellers = $pdo->query("SELECT s.*, u.name, u.email FROM sellers s JOIN users u ON s.user_id = u.id WHERE s.id > 0 ORDER BY s.store_name")->fetchAll();
    
    if (empty($sellers)) {
        logMessage("No sellers found for automated reports");
        return ['sent' => 0, 'errors' => ['No sellers found']];
    }
    
    // Check which reports should be sent
    $report_types = ['daily', 'weekly', 'monthly', 'yearly'];
    
    foreach ($report_types as $report_type) {
        if (shouldSendReport($report_type)) {
            logMessage("Sending $report_type reports to " . count($sellers) . " sellers");
            
            foreach ($sellers as $seller) {
                try {
                    // Generate analytics for this seller
                    $analytics_data = generateSellerAnalytics($seller['id'], $report_type);
                    
                    if ($analytics_data) {
                        // Add automated message based on report type
                        $automated_message = getAutomatedMessage($report_type);
                        
                        // Send the report
                        if (sendAnalyticsReport($analytics_data, $automated_message)) {
                            $reports_sent++;
                            
                            // Log to email_campaigns table
                            $stmt = $pdo->prepare("INSERT INTO email_campaigns (type, promo_message, sent_count, created_at) VALUES (?, ?, ?, NOW())");
                            $stmt->execute(['analytics_report', "Automated $report_type report", 1]);
                            
                            logMessage("Sent $report_type report to " . $seller['store_name']);
                        } else {
                            $errors[] = "Failed to send $report_type report to " . $seller['store_name'];
                            logMessage("Failed to send $report_type report to " . $seller['store_name']);
                        }
                    } else {
                        $errors[] = "Could not generate analytics for " . $seller['store_name'];
                        logMessage("Could not generate analytics for " . $seller['store_name']);
                    }
                    
                    // Small delay to avoid overwhelming the email server
                    sleep(1);
                    
                } catch (Exception $e) {
                    $errors[] = "Error sending $report_type report to " . $seller['store_name'] . ": " . $e->getMessage();
                    logMessage("Error sending $report_type report to " . $seller['store_name'] . ": " . $e->getMessage());
                }
            }
        }
    }
    
    return ['sent' => $reports_sent, 'errors' => $errors];
}

function getAutomatedMessage($report_type) {
    switch ($report_type) {
        case 'daily':
            return "تقريرك اليومي جاهز! راجع أداء متجرك اليوم وخطط لليوم التالي.";
            
        case 'weekly':
            return "تقريرك الأسبوعي جاهز! حلل أداء متجرك هذا الأسبوع وخطط للأسبوع القادم.";
            
        case 'monthly':
            return "تقريرك الشهري جاهز! راجع إنجازاتك هذا الشهر وحدد أهدافك للشهر القادم.";
            
        case 'yearly':
            return "تقريرك السنوي جاهز! راجع رحلتك هذا العام وخطط للنجاح في العام القادم.";
            
        default:
            return "تقرير تحليلات WeBuy جاهز لك!";
    }
}

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    
    // Log to file
    $log_file = __DIR__ . '/automated_reports.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Also log to database if possible
    try {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO activity_log (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([1, 'automated_report', $message]);
    } catch (Exception $e) {
        // Ignore database logging errors
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Command line execution
    echo "Starting automated reports...\n";
    $result = sendAutomatedReports();
    echo "Reports sent: " . $result['sent'] . "\n";
    if (!empty($result['errors'])) {
        echo "Errors: " . implode(", ", $result['errors']) . "\n";
    }
    echo "Completed at " . date('Y-m-d H:i:s') . "\n";
} else {
    // Web execution (for testing)
    header('Content-Type: application/json');
    $result = sendAutomatedReports();
    echo json_encode([
        'success' => true,
        'reports_sent' => $result['sent'],
        'errors' => $result['errors'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 