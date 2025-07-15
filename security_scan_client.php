<?php
/**
 * Security Scanner Client - API Version
 * Đặt file này trên mỗi website cần quét
 * Author: Hiệp Nguyễn
 * Version: 1.0 Client API
 */

// ==================== CẤU HÌNH CLIENT ====================
class SecurityClientConfig {
    // API Security - THAY ĐỔI API KEY NÀY
    const API_KEY = 'hiep-security-client-2025-change-this-key';
    const CLIENT_NAME = 'xemay365'; // Tên website này
    const CLIENT_VERSION = '1.0';
    
    // Giới hạn quét cho client
    const MAX_SCAN_FILES = 10000;
    const MAX_SCAN_TIME = 300; // 5 phút
    const MAX_MEMORY = '256M';
    
    // Bảo mật
    const ALLOWED_IPS = []; // Để trống = cho phép tất cả, hoặc ['IP1', 'IP2']
    const RATE_LIMIT = 10; // Số request/phút
}

// ==================== BẢO MẬT VÀ VALIDATION ====================
function validateApiRequest() {
    // Kiểm tra API key
    $apiKey = getApiKey();
    if (!$apiKey || $apiKey !== SecurityClientConfig::API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
    
    // Kiểm tra IP (nếu cấu hình)
    if (!empty(SecurityClientConfig::ALLOWED_IPS)) {
        $clientIP = getClientIP();
        if (!in_array($clientIP, SecurityClientConfig::ALLOWED_IPS)) {
            http_response_code(403);
            echo json_encode(['error' => 'IP not allowed']);
            exit;
        }
    }
    
    // Rate limiting
    if (!checkRateLimit()) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
}

function getApiKey() {
    // Lấy API key từ header hoặc parameter
    $headers = getallheaders();
    
    if (isset($headers['X-API-Key'])) {
        return $headers['X-API-Key'];
    }
    
    if (isset($headers['Authorization'])) {
        return str_replace('Bearer ', '', $headers['Authorization']);
    }
    
    return $_GET['api_key'] ?? $_POST['api_key'] ?? null;
}

function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
    }
    return 'unknown';
}

function checkRateLimit() {
    $ip = getClientIP();
    $rateFile = './logs/rate_limit_' . md5($ip) . '.txt';
    
    if (!file_exists('./logs')) {
        mkdir('./logs', 0755, true);
    }
    
    $currentTime = time();
    $requests = [];
    
    if (file_exists($rateFile)) {
        $content = file_get_contents($rateFile);
        $requests = $content ? json_decode($content, true) : [];
    }
    
    // Lọc request trong 1 phút qua
    $requests = array_filter($requests, function($time) use ($currentTime) {
        return ($currentTime - $time) < 60;
    });
    
    // Kiểm tra giới hạn
    if (count($requests) >= SecurityClientConfig::RATE_LIMIT) {
        return false;
    }
    
    // Thêm request hiện tại
    $requests[] = $currentTime;
    file_put_contents($rateFile, json_encode($requests));
    
    return true;
}

// ==================== CORE SCANNING ENGINE ====================
class SecurityScanner {
    private $scannedFiles = 0;
    private $suspiciousFiles = [];
    private $criticalFiles = [];
    private $scanStartTime;
    private $scanResults = [];
    
    public function __construct() {
        $this->scanStartTime = time();
        
        // Cấu hình PHP cho scanning
        set_time_limit(SecurityClientConfig::MAX_SCAN_TIME);
        ini_set('memory_limit', SecurityClientConfig::MAX_MEMORY);
        ini_set('max_execution_time', SecurityClientConfig::MAX_SCAN_TIME);
    }
    
    public function performScan($options = []) {
        try {
            // Khởi tạo
            $this->scannedFiles = 0;
            $this->suspiciousFiles = [];
            $this->criticalFiles = [];
            
            // Threat patterns
            $criticalPatterns = [
                'eval(' => 'Code execution vulnerability',
                'base64_decode(' => 'Encoded payload execution',
                'system(' => 'Direct system call',
                'exec(' => 'System command execution',
                'shell_exec(' => 'Shell command execution',
                'passthru(' => 'Command output bypass',
                'gzinflate(' => 'Compressed malware payload',
                'str_rot13(' => 'String obfuscation technique'
            ];
            
            $suspiciousPatterns = [
                'move_uploaded_file(' => 'File upload without validation',
                'file_get_contents(' => 'File read operation',
                'file_put_contents(' => 'File write operation',
                'fopen(' => 'File handle creation',
                'curl_exec(' => 'HTTP request execution',
                'unlink(' => 'File deletion'
            ];
            
            // Bắt đầu quét
            $this->scanDirectory('./', $criticalPatterns, $suspiciousPatterns);
            
            // Tạo kết quả
            $this->generateScanResults();
            
            return [
                'success' => true,
                'client_info' => [
                    'name' => SecurityClientConfig::CLIENT_NAME,
                    'version' => SecurityClientConfig::CLIENT_VERSION,
                    'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                    'scan_time' => time() - $this->scanStartTime
                ],
                'scan_results' => $this->scanResults,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'client_info' => [
                    'name' => SecurityClientConfig::CLIENT_NAME,
                    'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown'
                ]
            ];
        }
    }
    
    private function scanDirectory($dir, $criticalPatterns, $suspiciousPatterns) {
        if (!is_dir($dir) || $this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
            return;
        }
        
        $excludeDirs = ['.git', '.svn', 'node_modules', 'vendor', 'cache'];
        $excludeFiles = ['security_scan_client.php']; // Bỏ qua chính file này
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
                    break;
                }
                
                if ($file->isFile()) {
                    $filePath = $file->getPathname();
                    $fileName = basename($filePath);
                    $extension = strtolower($file->getExtension());
                    
                    // Bỏ qua file không cần thiết
                    if (in_array($fileName, $excludeFiles)) {
                        continue;
                    }
                    
                    // Bỏ qua thư mục không cần thiết
                    $shouldSkip = false;
                    foreach ($excludeDirs as $excludeDir) {
                        if (strpos($filePath, '/' . $excludeDir . '/') !== false) {
                            $shouldSkip = true;
                            break;
                        }
                    }
                    
                    if ($shouldSkip) {
                        continue;
                    }
                    
                    // Quét file PHP
                    if ($extension === 'php' || strpos($fileName, '.php.') !== false) {
                        $this->scanFile($filePath, $criticalPatterns, $suspiciousPatterns);
                        $this->scannedFiles++;
                    }
                }
            }
            
        } catch (Exception $e) {
            // Tiếp tục quét nếu có lỗi
            error_log("Scan error in directory $dir: " . $e->getMessage());
        }
    }
    
    private function scanFile($filePath, $criticalPatterns, $suspiciousPatterns) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }
        
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return;
        }
        
        $issues = [];
        
        // Kiểm tra critical patterns
        foreach ($criticalPatterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                $issues[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'severity' => 'critical'
                ];
            }
        }
        
        // Kiểm tra suspicious patterns
        foreach ($suspiciousPatterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                $issues[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'severity' => 'suspicious'
                ];
            }
        }
        
        // Kiểm tra file rỗng/nghi ngờ
        if ($this->isSuspiciousFile($filePath, $content)) {
            $issues[] = [
                'pattern' => 'suspicious_file',
                'description' => 'Empty or suspicious PHP file',
                'severity' => 'critical'
            ];
        }
        
        // Lưu kết quả
        if (!empty($issues)) {
            $fileInfo = [
                'path' => $filePath,
                'issues' => $issues,
                'file_size' => filesize($filePath),
                'modified_time' => filemtime($filePath),
                'md5' => md5_file($filePath)
            ];
            
            $this->suspiciousFiles[] = $fileInfo;
            
            // Kiểm tra xem có critical không
            $hasCritical = false;
            foreach ($issues as $issue) {
                if ($issue['severity'] === 'critical') {
                    $hasCritical = true;
                    break;
                }
            }
            
            if ($hasCritical) {
                $this->criticalFiles[] = $filePath;
            }
        }
    }
    
    private function isSuspiciousFile($filePath, $content) {
        $fileName = basename($filePath);
        $fileSize = strlen($content);
        
        // File rỗng hoặc quá nhỏ
        if ($fileSize < 10) {
            return true;
        }
        
        // Tên file nghi ngờ
        $suspiciousNames = [
            'app.php', 'style.php', 'cache.php', 'config.php',
            'wp.php', 'test.php', 'shell.php', 'hack.php'
        ];
        
        if (in_array(strtolower($fileName), $suspiciousNames)) {
            return true;
        }
        
        // Extension nghi ngờ
        if (strpos($fileName, '.php.') !== false) {
            return true;
        }
        
        return false;
    }
    
    private function generateScanResults() {
        $criticalCount = count($this->criticalFiles);
        $suspiciousCount = count($this->suspiciousFiles);
        
        $this->scanResults = [
            'scanned_files' => $this->scannedFiles,
            'suspicious_count' => $suspiciousCount,
            'critical_count' => $criticalCount,
            'scan_time' => time() - $this->scanStartTime,
            'threats' => $this->suspiciousFiles,
            'critical_files' => $this->criticalFiles,
            'status' => $criticalCount > 0 ? 'critical' : ($suspiciousCount > 0 ? 'warning' : 'clean')
        ];
    }
    
    public function getStatus() {
        $phpInfo = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'extensions' => get_loaded_extensions()
        ];
        
        $diskInfo = [
            'free_space' => disk_free_space('.'),
            'total_space' => disk_total_space('.'),
            'used_space' => disk_total_space('.') - disk_free_space('.')
        ];
        
        return [
            'success' => true,
            'client_info' => [
                'name' => SecurityClientConfig::CLIENT_NAME,
                'version' => SecurityClientConfig::CLIENT_VERSION,
                'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
            ],
            'system_info' => [
                'php' => $phpInfo,
                'disk' => $diskInfo,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
            ],
            'last_scan' => $this->getLastScanInfo(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getLastScanInfo() {
        $logFile = './logs/last_scan_client.json';
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            return json_decode($content, true);
        }
        
        return null;
    }
    
    public function saveLastScan($scanResult) {
        if (!file_exists('./logs')) {
            mkdir('./logs', 0755, true);
        }
        
        $logFile = './logs/last_scan_client.json';
        file_put_contents($logFile, json_encode($scanResult, JSON_PRETTY_PRINT));
    }
}

// ==================== API ENDPOINTS ====================
function handleApiRequest() {
    // Chỉ cho phép POST và GET
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'])) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Validate API request
    validateApiRequest();
    
    // Xử lý endpoint
    $endpoint = $_GET['endpoint'] ?? 'health';
    
    switch ($endpoint) {
        case 'health':
            handleHealthCheck();
            break;
            
        case 'status':
            handleStatusCheck();
            break;
            
        case 'scan':
            handleScanRequest();
            break;
            
        case 'info':
            handleInfoRequest();
            break;
            
        case 'delete_file':
            handleDeleteFileRequest();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
    }
}

function handleHealthCheck() {
    $response = [
        'status' => 'healthy',
        'client' => SecurityClientConfig::CLIENT_NAME,
        'version' => SecurityClientConfig::CLIENT_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'uptime' => time()
    ];
    
    echo json_encode($response);
}

function handleStatusCheck() {
    $scanner = new SecurityScanner();
    $status = $scanner->getStatus();
    
    echo json_encode($status);
}

function handleScanRequest() {
    $scanner = new SecurityScanner();
    
    // Lấy options từ request
    $options = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Thực hiện scan
    $result = $scanner->performScan($options);
    
    // Lưu kết quả
    if ($result['success']) {
        $scanner->saveLastScan($result);
        
        // Log scan activity
        $logEntry = date('Y-m-d H:i:s') . " - Client scan completed. IP: " . getClientIP() . "\n";
        if (!file_exists('./logs')) {
            mkdir('./logs', 0755, true);
        }
        file_put_contents('./logs/client_scan_' . date('Y-m-d') . '.log', $logEntry, FILE_APPEND);
    }
    
    echo json_encode($result);
}

function handleInfoRequest() {
    $info = [
        'client_name' => SecurityClientConfig::CLIENT_NAME,
        'client_version' => SecurityClientConfig::CLIENT_VERSION,
        'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'php_version' => PHP_VERSION,
        'max_scan_files' => SecurityClientConfig::MAX_SCAN_FILES,
        'max_scan_time' => SecurityClientConfig::MAX_SCAN_TIME,
        'api_endpoints' => [
            'health' => 'Kiểm tra sức khỏe client',
            'status' => 'Thông tin chi tiết client',
            'scan' => 'Thực hiện quét bảo mật',
            'info' => 'Thông tin API',
            'delete_file' => 'Xóa file nguy hiểm'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($info);
}

function handleDeleteFileRequest() {
    // Chỉ cho phép POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $filePath = $_POST['file_path'] ?? '';
    
    if (empty($filePath)) {
        http_response_code(400);
        echo json_encode(['error' => 'File path required']);
        exit;
    }
    
    // Kiểm tra file có tồn tại không
    if (!file_exists($filePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'File not found',
            'file_path' => $filePath
        ]);
        exit;
    }
    
    // Kiểm tra file có phải trong thư mục hiện tại không (bảo mật)
    $realPath = realpath($filePath);
    $currentDir = realpath('.');
    
    if ($realPath === false || strpos($realPath, $currentDir) !== 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid file path or access denied',
            'file_path' => $filePath
        ]);
        exit;
    }
    
    // Thực hiện xóa file
    try {
        if (unlink($filePath)) {
            echo json_encode([
                'success' => true,
                'message' => 'File deleted successfully',
                'file_path' => $filePath,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to delete file',
                'file_path' => $filePath
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error deleting file: ' . $e->getMessage(),
            'file_path' => $filePath
        ]);
    }
}

// ==================== MAIN EXECUTION ====================
// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Xử lý request
try {
    handleApiRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?> 