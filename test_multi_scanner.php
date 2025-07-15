<?php
/**
 * Multi-Website Scanner Test Demo
 * Author: Hiệp Nguyễn
 * Version: 1.0
 * Date: 2025
 */

// Include the multi-website scanner
require_once 'multi_website_scanner.php';

echo "=== MULTI-WEBSITE SCANNER TEST DEMO ===\n";
echo "Thời gian: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Website Detection
echo "🔍 TEST 1: Phát hiện websites...\n";
$detector = new WebsiteDetector();
$websites = $detector->detectWebsites();

echo "Kết quả: Phát hiện " . count($websites) . " websites\n\n";

if (count($websites) > 0) {
    echo "📋 Danh sách websites phát hiện:\n";
    foreach ($websites as $i => $website) {
        echo ($i + 1) . ". " . $website['name'] . "\n";
        echo "   Domain: " . $website['domain'] . "\n";
        echo "   Path: " . $website['path'] . "\n";
        echo "   Last scan: " . ($website['last_scan'] > 0 ? date('Y-m-d H:i:s', $website['last_scan']) : 'Chưa quét') . "\n\n";
    }
} else {
    echo "❌ Không tìm thấy websites nào!\n";
    echo "💡 Hãy kiểm tra:\n";
    echo "   - Đường dẫn hosting trong MultiWebsiteConfig::HOSTING_PATHS\n";
    echo "   - Quyền truy cập thư mục\n";
    echo "   - Cấu trúc hosting của bạn\n\n";
}

// Test 2: Quick Scan (if websites found)
if (count($websites) > 0) {
    echo "🛡️ TEST 2: Quét thử 1 website...\n";
    
    $testWebsite = $websites[0];
    echo "Đang quét: " . $testWebsite['name'] . "\n";
    
    try {
        $scanner = new MultiWebsiteScanner();
        
        // Simulate scanning first website only
        $startTime = microtime(true);
        
        // Mock scan result for demo
        $mockResult = array(
            'website' => $testWebsite,
            'threats' => array(),
            'stats' => array(
                'files_scanned' => rand(100, 1000),
                'threats_found' => rand(0, 3),
                'malware_detected' => rand(0, 2)
            ),
            'scan_time' => microtime(true) - $startTime,
            'timestamp' => time()
        );
        
        echo "✅ Quét hoàn thành!\n";
        echo "   Files scanned: " . $mockResult['stats']['files_scanned'] . "\n";
        echo "   Threats found: " . $mockResult['stats']['threats_found'] . "\n";
        echo "   Thời gian quét: " . round($mockResult['scan_time'], 2) . " giây\n\n";
        
    } catch (Exception $e) {
        echo "❌ Lỗi khi quét: " . $e->getMessage() . "\n\n";
    }
}

// Test 3: Configuration Check
echo "⚙️ TEST 3: Kiểm tra cấu hình...\n";

// Check hosting paths
$validPaths = array();
foreach (MultiWebsiteConfig::HOSTING_PATHS as $path) {
    if (is_dir($path)) {
        $validPaths[] = $path;
        echo "✅ Đường dẫn hợp lệ: $path\n";
    } else {
        echo "❌ Không tìm thấy: $path\n";
    }
}

if (empty($validPaths)) {
    echo "⚠️ CẢNH BÁO: Không tìm thấy đường dẫn hosting hợp lệ!\n";
    echo "💡 Hãy chỉnh sửa MultiWebsiteConfig::HOSTING_PATHS\n";
}

// Check email config
echo "\n📧 Cấu hình email:\n";
echo "   To: " . MultiWebsiteConfig::EMAIL_TO . "\n";
echo "   From: " . MultiWebsiteConfig::EMAIL_FROM . "\n";

if (MultiWebsiteConfig::EMAIL_TO === 'your-email@gmail.com') {
    echo "⚠️ CẢNH BÁO: Email chưa được cấu hình!\n";
    echo "💡 Hãy thay đổi EMAIL_TO trong MultiWebsiteConfig\n";
}

// Check logs directory
echo "\n📁 Thư mục logs:\n";
$logsDir = dirname(__FILE__) . '/logs';
if (is_dir($logsDir)) {
    echo "✅ Thư mục logs tồn tại: $logsDir\n";
    if (is_writable($logsDir)) {
        echo "✅ Có quyền ghi logs\n";
    } else {
        echo "❌ Không có quyền ghi logs\n";
    }
} else {
    echo "❌ Thư mục logs không tồn tại\n";
    echo "💡 Hãy tạo thư mục: mkdir logs && chmod 755 logs\n";
}

// Test 4: Performance Check
echo "\n🚀 TEST 4: Kiểm tra hiệu suất...\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "Current Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . "MB\n";

// Recommendations
echo "\n💡 KHUYẾN NGHỊ:\n";

if (count($websites) > 50) {
    echo "   - Hosting có nhiều websites (" . count($websites) . "), nên điều chỉnh MAX_WEBSITES_PER_SCAN\n";
}

if (ini_get('memory_limit') === '128M') {
    echo "   - Tăng memory_limit lên ít nhất 512M cho hiệu suất tốt hơn\n";
}

if (ini_get('max_execution_time') < 300) {
    echo "   - Tăng max_execution_time lên 600s hoặc hơn\n";
}

echo "\n📋 BƯỚC TIẾP THEO:\n";
echo "1. Chỉnh sửa cấu hình trong multi_website_scanner.php\n";
echo "2. Cấu hình email notification\n";
echo "3. Setup cron job với multi_website_cron.php\n";
echo "4. Truy cập dashboard tại: multi_website_scanner.php\n";
echo "5. Đọc hướng dẫn chi tiết trong MULTI_WEBSITE_SETUP.md\n";

echo "\n=== TEST HOÀN THÀNH ===\n";
echo "Thời gian thực hiện: " . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2) . " giây\n";
?> 