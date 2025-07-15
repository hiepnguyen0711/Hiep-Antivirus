<?php
/**
 * Multi-Website Security Scanner - Cron Job
 * Author: Hiệp Nguyễn
 * Facebook: https://www.facebook.com/G.N.S.L.7/
 * Version: 4.0 Multi-Site Cron
 * Date: 2025
 * 
 * Cách sử dụng:
 * 1. Cron job mỗi 2 giờ: 0 [asterisk]/2 [asterisk] [asterisk] [asterisk] /usr/bin/php /path/to/multi_website_cron.php
 * 2. Cron job mỗi 6 giờ: 0 [asterisk]/6 [asterisk] [asterisk] [asterisk] /usr/bin/php /path/to/multi_website_cron.php
 * 3. Cron job qua curl: 0 [asterisk]/2 [asterisk] [asterisk] [asterisk] curl -s "http://yourdomain.com/multi_website_cron.php?key=your_secret_key"
 */

// Security check - chỉ cho phép CLI hoặc với secret key
if (php_sapi_name() !== 'cli') {
    $secretKey = 'hiep_security_2025'; // THAY ĐỔI SECRET KEY
    if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
        die('Access denied. Invalid key.');
    }
}

// Set execution limits
set_time_limit(3600); // 1 hour
ini_set('memory_limit', '2048M');

// Change to scanner directory
$scannerDir = dirname(__FILE__);
chdir($scannerDir);

// Include the multi-website scanner
if (!file_exists('multi_website_scanner.php')) {
    die("Error: multi_website_scanner.php not found!\n");
}

// Log file
$logFile = $scannerDir . '/logs/multi_website_cron.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also output to console if CLI
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

// Check if another scan is running
$lockFile = $scannerDir . '/logs/multi_scan.lock';
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    $currentTime = time();
    
    // If lock is older than 2 hours, remove it (stale lock)
    if (($currentTime - $lockTime) > 7200) {
        unlink($lockFile);
        logMessage("Removed stale lock file");
    } else {
        logMessage("Another scan is already running. Exiting.");
        exit;
    }
}

// Create lock file
file_put_contents($lockFile, getmypid());

logMessage("=== MULTI-WEBSITE CRON SCAN STARTED ===");

try {
    // Include and run the scanner
    require_once 'multi_website_scanner.php';
    
    logMessage("Initializing Multi-Website Scanner...");
    
    $scanner = new MultiWebsiteScanner();
    $results = $scanner->scanAllWebsites();
    
    // Process results
    $totalWebsites = count($results);
    $totalThreats = 0;
    $criticalSites = 0;
    
    foreach ($results as $result) {
        if (isset($result['stats']['threats_found'])) {
            $totalThreats += $result['stats']['threats_found'];
            if ($result['stats']['threats_found'] > 0) {
                $criticalSites++;
            }
        }
    }
    
    logMessage("Scan completed successfully!");
    logMessage("Total websites scanned: $totalWebsites");
    logMessage("Total threats found: $totalThreats");
    logMessage("Critical sites: $criticalSites");
    
    // Save results to JSON for dashboard
    $resultsFile = $scannerDir . '/logs/latest_scan_results.json';
    $resultsData = array(
        'timestamp' => time(),
        'total_websites' => $totalWebsites,
        'total_threats' => $totalThreats,
        'critical_sites' => $criticalSites,
        'results' => $results
    );
    
    file_put_contents($resultsFile, json_encode($resultsData, JSON_PRETTY_PRINT));
    logMessage("Results saved to $resultsFile");
    
    // Clean up old log files (keep last 30 days)
    cleanupOldLogs();
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    
    // Send error email
    sendErrorEmail($e->getMessage());
    
} finally {
    // Remove lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    
    logMessage("=== MULTI-WEBSITE CRON SCAN COMPLETED ===");
}

function cleanupOldLogs() {
    global $logDir;
    
    if (!is_dir($logDir)) return;
    
    $files = glob($logDir . '/*.log');
    $cutoffTime = time() - (30 * 24 * 60 * 60); // 30 days ago
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
            logMessage("Cleaned up old log file: " . basename($file));
        }
    }
}

function sendErrorEmail($error) {
    $to = 'nguyenvanhiep0711@gmail.com'; // THAY ĐỔI EMAIL
    $subject = '🚨 Multi-Website Scanner Error';
    $message = "
    <html>
    <body>
        <h2>Multi-Website Scanner Error</h2>
        <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Error:</strong> " . htmlspecialchars($error) . "</p>
        <p><strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "</p>
        <p>Please check the logs and fix the issue.</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Multi-Website Scanner <scanner@yourdomain.com>" . "\r\n";
    
    mail($to, $subject, $message, $headers);
}

// Performance monitoring
function getMemoryUsage() {
    return round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB';
}

function getExecutionTime() {
    static $startTime = null;
    if ($startTime === null) {
        $startTime = microtime(true);
        return 0;
    }
    return round(microtime(true) - $startTime, 2);
}

logMessage("Memory usage: " . getMemoryUsage());
logMessage("Execution time: " . getExecutionTime() . " seconds");
?> 