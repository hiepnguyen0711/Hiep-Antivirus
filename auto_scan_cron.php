<?php
/**
 * Auto Security Scan - Cron Job
 * Author: Hiệp Nguyễn
 * Version: 1.0
 * Date: 2025
 * 
 * Cách sử dụng:
 * 1. Cron job mỗi giờ: 0 [asterisk] [asterisk] [asterisk] [asterisk] /usr/bin/php /path/to/auto_scan_cron.php
 * 2. Cron job mỗi 6 giờ: 0 [asterisk]/6 [asterisk] [asterisk] [asterisk] /usr/bin/php /path/to/auto_scan_cron.php
 * 3. Cron job qua curl: 0 [asterisk] [asterisk] [asterisk] [asterisk] curl -s "http://yourdomain.com/auto_scan_cron.php"
 */

// Chỉ cho phép chạy từ command line hoặc localhost
if (php_sapi_name() !== 'cli') {
    if (!isset($_SERVER['REMOTE_ADDR']) || 
        ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1')) {
        die('Access denied. Only CLI or localhost allowed.');
    }
}

// Đặt thời gian chạy và memory limit
set_time_limit(600); // 10 phút
ini_set('memory_limit', '512M');

// Thay đổi thư mục làm việc về thư mục chứa security_scan.php
$scannerDir = dirname(__FILE__);
chdir($scannerDir);

// Tạo thư mục logs nếu chưa có
if (!file_exists('./logs')) {
    mkdir('./logs', 0755, true);
}

// Ghi log bắt đầu
$logFile = './logs/auto_scan_cron_' . date('Y-m-d') . '.log';
$startTime = date('Y-m-d H:i:s');
file_put_contents($logFile, "[$startTime] Auto scan cron job started\n", FILE_APPEND | LOCK_EX);

try {
    // Kiểm tra xem file security_scan.php có tồn tại không
    if (!file_exists('./security_scan.php')) {
        throw new Exception('security_scan.php not found in directory: ' . $scannerDir);
    }
    
    // Gọi auto scan bằng cách simulate HTTP request
    $url = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/security_scan.php?auto_scan=1';
    
    // Sử dụng curl nếu có
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 phút timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Auto Security Scanner Cron');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Cache-Control: no-cache',
            'X-Requested-With: XMLHttpRequest'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Curl error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP error: ' . $httpCode);
        }
        
    } else {
        // Fallback: file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 300,
                'header' => [
                    'User-Agent: Auto Security Scanner Cron',
                    'Cache-Control: no-cache',
                    'X-Requested-With: XMLHttpRequest'
                ]
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to fetch URL: ' . $url);
        }
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON response: ' . substr($response, 0, 100) . '...');
    }
    
    if ($data['success']) {
        $result = $data['result'];
        $scanTime = $data['scan_time'];
        
        $message = "Auto scan completed successfully:\n" .
                  "- Files scanned: " . $result['scanned_files'] . "\n" .
                  "- Threats found: " . $result['suspicious_count'] . "\n" .
                  "- Critical threats: " . $result['critical_count'] . "\n" .
                  "- Scan time: " . $scanTime . " seconds\n";
        
        file_put_contents($logFile, "[$startTime] $message", FILE_APPEND | LOCK_EX);
        
        // Ghi kết quả vào file riêng để dashboard có thể đọc
        $statusFile = './logs/last_cron_result.json';
        file_put_contents($statusFile, json_encode([
            'success' => true,
            'timestamp' => time(),
            'scan_time' => $scanTime,
            'result' => $result,
            'message' => 'Auto scan completed via cron job'
        ], JSON_PRETTY_PRINT));
        
        // Gửi email nếu có critical threats
        if ($result['critical_count'] > 0) {
            $endTime = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$endTime] Email alert sent for {$result['critical_count']} critical threats\n", FILE_APPEND | LOCK_EX);
        }
        
        echo "Auto scan completed successfully!\n";
        echo "Files scanned: " . $result['scanned_files'] . "\n";
        echo "Threats found: " . $result['suspicious_count'] . "\n";
        echo "Critical threats: " . $result['critical_count'] . "\n";
        
    } else {
        throw new Exception('Auto scan failed: ' . ($data['error'] ?? 'Unknown error'));
    }
    
} catch (Exception $e) {
    $errorTime = date('Y-m-d H:i:s');
    $errorMessage = "[$errorTime] ERROR: " . $e->getMessage() . "\n";
    
    file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
    
    // Ghi lỗi vào status file
    $statusFile = './logs/last_cron_result.json';
    file_put_contents($statusFile, json_encode([
        'success' => false,
        'timestamp' => time(),
        'error' => $e->getMessage(),
        'message' => 'Auto scan failed via cron job'
    ], JSON_PRETTY_PRINT));
    
    echo "Auto scan failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup old logs (keep only 7 days)
$logDir = './logs';
$cutoffTime = time() - (7 * 24 * 3600); // 7 days ago

if (is_dir($logDir)) {
    $files = glob($logDir . '/auto_scan_cron_*.log');
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
        }
    }
    
    // Cleanup other old log files
    $otherLogs = glob($logDir . '/security_*');
    foreach ($otherLogs as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
        }
    }
}

$endTime = date('Y-m-d H:i:s');
file_put_contents($logFile, "[$endTime] Auto scan cron job completed\n", FILE_APPEND | LOCK_EX);

echo "Auto scan cron job completed successfully!\n";
?> 