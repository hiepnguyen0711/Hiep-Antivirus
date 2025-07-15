<?php
/**
 * Server Cron Job - Automated Daily Security Scan
 * Chạy file này qua cron job 1 ngày/lần
 * Author: Hiệp Nguyễn
 * Version: 1.0
 */

// Chỉ cho phép chạy từ command line hoặc localhost
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Access denied. This script can only run from command line or localhost.');
}

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình
$CRON_LOG_FILE = './logs/cron_scan_' . date('Y-m-d') . '.log';
$CRON_LOCK_FILE = './logs/cron_scan.lock';
$MAX_EXECUTION_TIME = 1800; // 30 phút

// Tạo thư mục logs nếu chưa có
if (!file_exists('./logs')) {
    mkdir('./logs', 0755, true);
}

// Kiểm tra lock file để tránh chạy đồng thời
if (file_exists($CRON_LOCK_FILE)) {
    $lockTime = filemtime($CRON_LOCK_FILE);
    $currentTime = time();
    
    // Nếu lock file tồn tại hơn 2 tiếng, xóa nó (có thể process trước bị treo)
    if (($currentTime - $lockTime) > 7200) {
        unlink($CRON_LOCK_FILE);
        logCronActivity("Removed stale lock file");
    } else {
        logCronActivity("Another scan is already running. Exiting.");
        exit(0);
    }
}

// Tạo lock file
file_put_contents($CRON_LOCK_FILE, date('Y-m-d H:i:s'));

// Set execution time limit
set_time_limit($MAX_EXECUTION_TIME);

// Bắt đầu cron job
logCronActivity("=== STARTING AUTOMATED DAILY SCAN ===");
logCronActivity("Cron job started at " . date('Y-m-d H:i:s'));

try {
    // Load các class cần thiết
    require_once(__DIR__ . '/security_scan_server.php');
    
    // Khởi tạo managers
    $clientManager = new ClientManager();
    $scannerManager = new ScannerManager();
    $emailManager = new EmailManager();
    
    // Lấy danh sách clients
    $clients = $clientManager->getClients();
    logCronActivity("Found " . count($clients) . " clients to scan");
    
    if (empty($clients)) {
        logCronActivity("No clients found. Exiting.");
        cleanup();
        exit(0);
    }
    
    // Quét từng client
    $scanResults = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($clients as $client) {
        logCronActivity("Scanning client: " . $client['name'] . " (" . $client['url'] . ")");
        
        try {
            // Kiểm tra health trước
            $healthCheck = $scannerManager->checkClientHealth($client);
            
            if (!$healthCheck) {
                logCronActivity("Client " . $client['name'] . " is offline. Skipping scan.");
                $scanResults[] = [
                    'client' => $client,
                    'scan_result' => [
                        'success' => false,
                        'error' => 'Client offline or not responding',
                        'client_info' => [
                            'name' => $client['name'],
                            'domain' => $client['url']
                        ]
                    ]
                ];
                $errorCount++;
                continue;
            }
            
            // Thực hiện quét
            $scanResult = $scannerManager->scanClient($client);
            
            if ($scanResult['success']) {
                $results = $scanResult['scan_results'];
                logCronActivity("Client " . $client['name'] . " scan completed: " . 
                             $results['scanned_files'] . " files, " . 
                             $results['suspicious_count'] . " threats, " . 
                             $results['critical_count'] . " critical");
                $successCount++;
            } else {
                logCronActivity("Client " . $client['name'] . " scan failed: " . ($scanResult['error'] ?? 'Unknown error'));
                $errorCount++;
            }
            
            $scanResults[] = [
                'client' => $client,
                'scan_result' => $scanResult
            ];
            
            // Ngủ 5 giây giữa các lần quét để tránh overload
            sleep(5);
            
        } catch (Exception $e) {
            logCronActivity("Error scanning client " . $client['name'] . ": " . $e->getMessage());
            $errorCount++;
            
            $scanResults[] = [
                'client' => $client,
                'scan_result' => [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'client_info' => [
                        'name' => $client['name'],
                        'domain' => $client['url']
                    ]
                ]
            ];
        }
    }
    
    // Tổng hợp kết quả
    $totalClients = count($clients);
    $totalScanned = $successCount;
    $totalErrors = $errorCount;
    
    logCronActivity("=== SCAN SUMMARY ===");
    logCronActivity("Total clients: " . $totalClients);
    logCronActivity("Successfully scanned: " . $totalScanned);
    logCronActivity("Errors: " . $totalErrors);
    
    // Phân tích threats
    $criticalClients = 0;
    $warningClients = 0;
    $cleanClients = 0;
    $totalThreats = 0;
    $totalCriticalThreats = 0;
    
    foreach ($scanResults as $result) {
        if ($result['scan_result']['success']) {
            $scanResult = $result['scan_result']['scan_results'];
            $status = $scanResult['status'] ?? 'unknown';
            
            $totalThreats += $scanResult['suspicious_count'] ?? 0;
            $totalCriticalThreats += $scanResult['critical_count'] ?? 0;
            
            switch ($status) {
                case 'critical':
                    $criticalClients++;
                    break;
                case 'warning':
                    $warningClients++;
                    break;
                case 'clean':
                    $cleanClients++;
                    break;
            }
        }
    }
    
    logCronActivity("=== THREAT ANALYSIS ===");
    logCronActivity("Clean clients: " . $cleanClients);
    logCronActivity("Warning clients: " . $warningClients);
    logCronActivity("Critical clients: " . $criticalClients);
    logCronActivity("Total threats: " . $totalThreats);
    logCronActivity("Total critical threats: " . $totalCriticalThreats);
    
    // Gửi email báo cáo
    logCronActivity("=== SENDING EMAIL REPORT ===");
    
    try {
        $emailSent = $emailManager->sendDailyReport($scanResults);
        
        if ($emailSent) {
            logCronActivity("Email report sent successfully to " . SecurityServerConfig::ADMIN_EMAIL);
        } else {
            logCronActivity("Failed to send email report");
        }
    } catch (Exception $e) {
        logCronActivity("Email sending error: " . $e->getMessage());
    }
    
    // Lưu kết quả scan vào file
    $resultsFile = './data/daily_scan_results_' . date('Y-m-d') . '.json';
    $resultsData = [
        'scan_date' => date('Y-m-d H:i:s'),
        'total_clients' => $totalClients,
        'successful_scans' => $totalScanned,
        'errors' => $totalErrors,
        'clean_clients' => $cleanClients,
        'warning_clients' => $warningClients,
        'critical_clients' => $criticalClients,
        'total_threats' => $totalThreats,
        'total_critical_threats' => $totalCriticalThreats,
        'scan_results' => $scanResults
    ];
    
    file_put_contents($resultsFile, json_encode($resultsData, JSON_PRETTY_PRINT));
    logCronActivity("Scan results saved to: " . $resultsFile);
    
    // Cleanup old results (giữ lại 30 ngày)
    cleanupOldResults();
    
    logCronActivity("=== CRON JOB COMPLETED SUCCESSFULLY ===");
    logCronActivity("Execution time: " . (time() - strtotime(date('Y-m-d H:i:s'))) . " seconds");
    
} catch (Exception $e) {
    logCronActivity("=== CRON JOB FAILED ===");
    logCronActivity("Fatal error: " . $e->getMessage());
    logCronActivity("Stack trace: " . $e->getTraceAsString());
    
    // Gửi email cảnh báo lỗi
    sendErrorAlert($e->getMessage());
    
} finally {
    // Cleanup
    cleanup();
}

// ==================== FUNCTIONS ====================

function logCronActivity($message) {
    global $CRON_LOG_FILE;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    
    // Ghi vào file log
    file_put_contents($CRON_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Nếu chạy từ command line, cũng hiển thị ra màn hình
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

function cleanup() {
    global $CRON_LOCK_FILE;
    
    // Xóa lock file
    if (file_exists($CRON_LOCK_FILE)) {
        unlink($CRON_LOCK_FILE);
        logCronActivity("Lock file removed");
    }
}

function cleanupOldResults() {
    logCronActivity("Cleaning up old scan results...");
    
    $dataDir = './data';
    $logsDir = './logs';
    $cutoffDate = date('Y-m-d', strtotime('-30 days'));
    
    // Cleanup data files
    if (is_dir($dataDir)) {
        $files = glob($dataDir . '/daily_scan_results_*.json');
        foreach ($files as $file) {
            if (preg_match('/daily_scan_results_(\d{4}-\d{2}-\d{2})\.json$/', $file, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDate) {
                    unlink($file);
                    logCronActivity("Deleted old results file: " . basename($file));
                }
            }
        }
    }
    
    // Cleanup log files
    if (is_dir($logsDir)) {
        $files = glob($logsDir . '/cron_scan_*.log');
        foreach ($files as $file) {
            if (preg_match('/cron_scan_(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDate) {
                    unlink($file);
                    logCronActivity("Deleted old log file: " . basename($file));
                }
            }
        }
    }
}

function sendErrorAlert($errorMessage) {
    try {
        // Sử dụng PHPMailer nếu có
        if (file_exists('./smtp/class.phpmailer.php')) {
            require_once('./smtp/class.phpmailer.php');
            require_once('./smtp/class.smtp.php');
            
            $mail = new PHPMailer();
            
            $mail->isSMTP();
            $mail->Host = SecurityServerConfig::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SecurityServerConfig::SMTP_USERNAME;
            $mail->Password = SecurityServerConfig::SMTP_PASSWORD;
            $mail->SMTPSecure = SecurityServerConfig::SMTP_SECURE;
            $mail->Port = SecurityServerConfig::SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom(SecurityServerConfig::EMAIL_FROM, SecurityServerConfig::EMAIL_FROM_NAME);
            $mail->addAddress(SecurityServerConfig::ADMIN_EMAIL);
            $mail->isHTML(true);
            $mail->Subject = "🚨 CRON JOB ERROR - " . date('d/m/Y H:i:s');
            
            $mail->Body = "
                <h2>🚨 Cron Job Error Alert</h2>
                <p><strong>Thời gian:</strong> " . date('d/m/Y H:i:s') . "</p>
                <p><strong>Lỗi:</strong> " . htmlspecialchars($errorMessage) . "</p>
                <p><strong>Server:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</p>
                <p><strong>Script:</strong> " . __FILE__ . "</p>
                <hr>
                <p>Vui lòng kiểm tra và khắc phục lỗi.</p>
            ";
            
            $mail->send();
            logCronActivity("Error alert email sent successfully");
        }
    } catch (Exception $e) {
        logCronActivity("Failed to send error alert email: " . $e->getMessage());
    }
}

// Xử lý signal để cleanup khi script bị kill
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function() {
        logCronActivity("Received SIGTERM. Cleaning up...");
        cleanup();
        exit(0);
    });
    
    pcntl_signal(SIGINT, function() {
        logCronActivity("Received SIGINT. Cleaning up...");
        cleanup();
        exit(0);
    });
}

// Memory usage logging
register_shutdown_function(function() {
    $memoryUsage = memory_get_peak_usage(true);
    $memoryMB = round($memoryUsage / 1024 / 1024, 2);
    logCronActivity("Peak memory usage: " . $memoryMB . " MB");
});

?> 