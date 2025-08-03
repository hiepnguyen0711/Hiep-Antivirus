<?php
/**
 * Daily Security Scan Cron Job
 * Chạy tự động hàng ngày để quét bảo mật và gửi email báo cáo
 *
 * Cách cài đặt cron job:
 *
 * Linux/Unix:
 * # Quét hàng ngày lúc 2:00 AM
 * 0 2 * * * /usr/bin/php /path/to/daily_security_scan.php daily_scan >/dev/null 2>&1
 *
 * # Dọn dẹp hàng tháng
 * 0 4 1 * * /usr/bin/php /path/to/daily_security_scan.php cleanup >/dev/null 2>&1
 *
 * Windows Task Scheduler:
 * php.exe "C:\path\to\daily_security_scan.php" daily_scan
 *
 * Hoặc sử dụng wget/curl:
 * 0 2 * * * curl -s "http://yourdomain.com/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +\%Y-\%m-\%d)" >/dev/null 2>&1
 *
 * Author: Hiệp Nguyễn
 * Version: 2.0
 */

// Thiết lập timezone Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình
$CONFIG = [
    'server_url' => 'http://localhost/2025/Hiep-Antivirus/security_scan_server.php',
    'log_file' => __DIR__ . '/data/logs/cron_daily.log',
    'allowed_hours' => [2, 3, 4], // Cho phép chạy từ 2-4 AM hoặc manual
    'timeout' => 1800, // 30 phút
    'force_run' => false // Set true để chạy bất kỳ lúc nào
];

// Tạo thư mục logs nếu chưa có
if (!is_dir(__DIR__ . '/data/logs')) {
    mkdir(__DIR__ . '/data/logs', 0755, true);
}

/**
 * Ghi log
 */
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

    // Hiển thị nếu chạy từ CLI
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

/**
 * Kiểm tra thời gian cho phép chạy
 */
function isAllowedTime($allowedHours, $forceRun = false) {
    if ($forceRun) return true;

    $currentHour = (int)date('H');
    return in_array($currentHour, $allowedHours);
}

/**
 * Chạy daily scan qua API
 */
function runDailyScan($config) {
    writeLog("=== BẮT ĐẦU DAILY SECURITY SCAN ===", $config['log_file']);

    // Tạo cron key cho ngày hôm nay
    $cronKey = 'hiep-security-cron-2025-' . date('Y-m-d');

    // Tạo URL với cron key
    $scanUrl = $config['server_url'] . '?api=run_daily_scan&cron_key=' . urlencode($cronKey);

    writeLog("URL: $scanUrl", $config['log_file']);

    // Gọi API để chạy daily scan
    $context = stream_context_create([
        'http' => [
            'timeout' => $config['timeout'],
            'method' => 'GET',
            'header' => [
                'User-Agent: Security-Scanner-Cron/2.0',
                'Accept: application/json'
            ]
        ]
    ]);

    $response = file_get_contents($scanUrl, false, $context);

    if ($response === false) {
        writeLog("❌ Lỗi: Không thể kết nối đến server", $config['log_file']);
        return false;
    }

    $result = json_decode($response, true);

    if ($result && $result['success']) {
        writeLog("✅ Quét bảo mật hoàn thành thành công", $config['log_file']);
        writeLog("=== KẾT THÚC DAILY SECURITY SCAN ===", $config['log_file']);
        return true;
    } else {
        $error = $result['error'] ?? 'Unknown error';
        writeLog("❌ Lỗi quét bảo mật: $error", $config['log_file']);
        return false;
    }
}

/**
 * Dọn dẹp files cũ
 */
function runCleanup($config) {
    writeLog("=== BẮT ĐẦU CLEANUP ===", $config['log_file']);

    $cleanupUrl = $config['server_url'] . '?api=run_daily_scan&action=cleanup&cron_key=hiep-security-cron-2025-' . date('Y-m-d');

    $context = stream_context_create([
        'http' => [
            'timeout' => 300,
            'method' => 'GET',
            'header' => [
                'User-Agent: Security-Scanner-Cleanup/2.0',
                'Accept: application/json'
            ]
        ]
    ]);

    $response = file_get_contents($cleanupUrl, false, $context);

    if ($response !== false) {
        writeLog("✅ Cleanup hoàn thành", $config['log_file']);
    } else {
        writeLog("❌ Lỗi cleanup", $config['log_file']);
    }

    writeLog("=== KẾT THÚC CLEANUP ===", $config['log_file']);
}

// ==================== MAIN EXECUTION ====================

// Lấy action từ command line hoặc GET parameter
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'daily_scan';
    $forceRun = in_array('--force', $argv);
} else {
    $action = $_GET['action'] ?? 'daily_scan';
    $forceRun = isset($_GET['force']);
}

// Cập nhật config nếu có force
$CONFIG['force_run'] = $forceRun;

// Kiểm tra thời gian cho phép
if (!isAllowedTime($CONFIG['allowed_hours'], $CONFIG['force_run'])) {
    $currentTime = date('H:i');
    $allowedTimes = implode(', ', $CONFIG['allowed_hours']);
    writeLog("⏰ Chỉ chạy vào các giờ: $allowedTimes. Hiện tại: $currentTime", $CONFIG['log_file']);
    writeLog("💡 Sử dụng --force hoặc ?force=1 để chạy bất kỳ lúc nào", $CONFIG['log_file']);
    exit(0);
}

// Thực hiện action
switch ($action) {
    case 'daily_scan':
        $success = runDailyScan($CONFIG);
        exit($success ? 0 : 1);

    case 'cleanup':
        runCleanup($CONFIG);
        exit(0);

    case 'test':
        writeLog("🧪 Test cron job script - Hoạt động bình thường", $CONFIG['log_file']);
        writeLog("⏰ Thời gian: " . date('Y-m-d H:i:s'), $CONFIG['log_file']);
        writeLog("🔧 PHP version: " . PHP_VERSION, $CONFIG['log_file']);
        writeLog("📁 Working directory: " . __DIR__, $CONFIG['log_file']);
        exit(0);

    default:
        writeLog("❌ Action không hợp lệ: $action", $CONFIG['log_file']);
        writeLog("💡 Sử dụng: daily_scan, cleanup, hoặc test", $CONFIG['log_file']);
        exit(1);
}

// Trả về JSON nếu chạy từ web
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Cron job executed successfully'
    ]);
}
?>
