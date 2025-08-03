<?php

/**
 * Security Scanner Client - Standalone Version
 * File độc lập chứa tất cả cấu hình và chức năng
 * Chỉ cần copy file này để deploy client mới
 * Author: Hiệp Nguyễn
 * Version: 2.0 Standalone
 */

// ==================== EMBEDDED CONFIGURATION ====================
class SecurityScannerConfig {
    // API Security
    const DEFAULT_API_KEY = 'hiep-security-client-2025-change-this-key';
    const CLIENT_VERSION = '2.0';
    
    // Scan Limits
    const MAX_SCAN_FILES = 15000;
    const MAX_SCAN_TIME = 6000; // 100 phút
    const MAX_MEMORY = '512M';
    const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    
    // Security
    const RATE_LIMIT = 20; // Số request/phút
    const SESSION_TIMEOUT = 3600; // 1 giờ
    
    // Directories to exclude from scanning
    const EXCLUDE_DIRS = array(
        '.git', '.svn', '.hg', '.bzr',
        'node_modules', 'vendor', 'bower_components',
        'cache', 'logs', 'tmp', 'temp', 'uploads',
        'quarantine', 'backup', 'backups'
    );
    
    // Files to exclude from scanning
    const EXCLUDE_FILES = array(
        'security_scan_client.php',
        'security_scan_client_standalone.php',
        'security_scan_server.php',
        'scanner_config.php',
        '.htaccess', '.htpasswd',
        'robots.txt', 'sitemap.xml'
    );
    
    // File extensions to scan
    const SCAN_EXTENSIONS = array(
        'php', 'php3', 'php4', 'php5', 'php7', 'php8',
        'phtml', 'phps', 'pht', 'phar',
        'inc', 'class', 'module'
    );
    
    // Suspicious file extensions
    const SUSPICIOUS_EXTENSIONS = array(
        'suspected', 'bak', 'backup', 'old', 'orig', 'save', 'tmp',
        'log', 'txt', 'dat', 'cfg', 'conf', 'ini'
    );
    
    // Malware patterns - embedded directly
    const MALWARE_PATTERNS = array(
        // Shell patterns
        'eval(' => 'Eval function usage',
        'exec(' => 'Command execution',
        'system(' => 'System command execution',
        'shell_exec(' => 'Shell command execution',
        'passthru(' => 'Passthru execution',
        'base64_decode(' => 'Base64 decoding',
        'gzinflate(' => 'Gzip inflation',
        'str_rot13(' => 'ROT13 encoding',
        'gzuncompress(' => 'Gzip decompression',
        'gzdecode(' => 'Gzip decoding',
        'bzdecompress(' => 'Bzip2 decompression',
        
        // File operations
        'file_get_contents(' => 'File read operation',
        'file_put_contents(' => 'File write operation',
        'fopen(' => 'File handle creation',
        'fwrite(' => 'File write operation',
        'include(' => 'File inclusion',
        'require(' => 'Required file inclusion',
        'include_once(' => 'Single file inclusion',
        'require_once(' => 'Single required inclusion',
        '$_REQUEST' => 'User input handling',
        '$_GET' => 'GET parameter usage',
        '$_POST' => 'POST parameter usage',
        '__FILE__' => 'File path reference',
        '__DIR__' => 'Directory path reference',
        
        // Obfuscation patterns
        'chr(' => 'Character conversion',
        'ord(' => 'ASCII value conversion',
        'hexdec(' => 'Hexadecimal decoding',
        'dechex(' => 'Decimal to hex conversion',
        'pack(' => 'Binary data packing',
        'unpack(' => 'Binary data unpacking',
        'create_function(' => 'Dynamic function creation',
        'call_user_func(' => 'Dynamic function call',
        'call_user_func_array(' => 'Dynamic function call with array',
        'preg_replace(' => 'Regular expression replacement',
        'assert(' => 'Assertion execution',
        
        // Network operations
        'curl_exec(' => 'CURL execution',
        'curl_init(' => 'CURL initialization',
        'fsockopen(' => 'Socket connection',
        'pfsockopen(' => 'Persistent socket connection',
        'socket_create(' => 'Socket creation',
        'stream_socket_client(' => 'Stream socket client',
        'stream_context_create(' => 'Stream context creation',
        
        // Database operations
        'mysql_query(' => 'MySQL query execution',
        'mysqli_query(' => 'MySQLi query execution',
        'pg_query(' => 'PostgreSQL query execution',
        'sqlite_query(' => 'SQLite query execution',
        'mssql_query(' => 'MSSQL query execution',
        'oci_execute(' => 'Oracle query execution',
        
        // Dangerous functions
        'phpinfo(' => 'PHP information disclosure',
        'show_source(' => 'Source code disclosure',
        'highlight_file(' => 'File highlighting',
        'readfile(' => 'File reading',
        'readdir(' => 'Directory reading',
        'scandir(' => 'Directory scanning',
        'opendir(' => 'Directory opening',
        'glob(' => 'File pattern matching',
        'parse_ini_file(' => 'INI file parsing',
        'move_uploaded_file(' => 'File upload handling',
        'copy(' => 'File copying',
        'rename(' => 'File renaming',
        'unlink(' => 'File deletion',
        'rmdir(' => 'Directory removal',
        'mkdir(' => 'Directory creation',
        'chmod(' => 'Permission modification',
        'chown(' => 'Owner modification',
        'chgrp(' => 'Group modification',
        
        // Process control
        'pcntl_exec(' => 'Process execution',
        'proc_open(' => 'Process opening',
        'proc_close(' => 'Process closing',
        'proc_terminate(' => 'Process termination',
        'proc_get_status(' => 'Process status',
        'popen(' => 'Process pipe opening',
        'pclose(' => 'Process pipe closing',
        
        // Error suppression
        '@eval(' => 'Suppressed eval',
        '@exec(' => 'Suppressed exec',
        '@system(' => 'Suppressed system',
        '@shell_exec(' => 'Suppressed shell_exec',
        '@passthru(' => 'Suppressed passthru',
        '@file_get_contents(' => 'Suppressed file read',
        '@file_put_contents(' => 'Suppressed file write',
        '@fopen(' => 'Suppressed file open',
        '@fwrite(' => 'Suppressed file write',
        '@include(' => 'Suppressed include',
        '@require(' => 'Suppressed require',
        
        // Specific malware signatures
        'c99' => 'C99 shell signature',
        'r57' => 'R57 shell signature',
        'wso' => 'WSO shell signature',
        'b374k' => 'B374K shell signature',
        'adminer' => 'Adminer database tool',
        'shell_exec' => 'Shell execution',
        'backdoor' => 'Backdoor signature',
        'rootkit' => 'Rootkit signature',
        'FilesMan' => 'File manager signature',
        'Sec-Info' => 'Security info signature',
        'Safe-Mode' => 'Safe mode bypass',
        'mysql_connect' => 'MySQL connection',
        'base64_encode' => 'Base64 encoding',
        'gzdeflate' => 'Gzip deflation',
        'str_replace' => 'String replacement',
        
        // Suspicious strings
        '$GLOBALS[' => 'Global variable access',
        '$_SERVER[' => 'Server variable access',
        '$_SESSION[' => 'Session variable access',
        '$_COOKIE[' => 'Cookie variable access',
        '$_FILES[' => 'File upload variable access',
        '$_ENV[' => 'Environment variable access',
        'DOCUMENT_ROOT' => 'Document root access',
        'HTTP_USER_AGENT' => 'User agent access',
        'REQUEST_URI' => 'Request URI access',
        'QUERY_STRING' => 'Query string access',
        'PATH_INFO' => 'Path info access',
        'SCRIPT_NAME' => 'Script name access',
        'SERVER_NAME' => 'Server name access',
        'HTTP_HOST' => 'HTTP host access',
        'REMOTE_ADDR' => 'Remote address access',
        'HTTP_REFERER' => 'HTTP referer access',
        
        // Encoding patterns
        '\\x' => 'Hexadecimal encoding',
        '\\' => 'Escape sequence',
        '%' => 'URL encoding',
        '&#' => 'HTML entity encoding',
        '&amp;' => 'HTML ampersand encoding',
        '&lt;' => 'HTML less-than encoding',
        '&gt;' => 'HTML greater-than encoding',
        '&quot;' => 'HTML quote encoding',
        '&#x' => 'Hexadecimal HTML entity',
        
        // Suspicious patterns
        'error_reporting(0)' => 'Error reporting disabled',
        'set_time_limit(0)' => 'Time limit disabled',
        'ignore_user_abort' => 'User abort ignored',
        'register_shutdown_function' => 'Shutdown function registered',
        'ob_start()' => 'Output buffering started',
        'ob_get_contents()' => 'Output buffer contents',
        'ob_end_clean()' => 'Output buffer cleaned',
        'ob_get_clean()' => 'Output buffer retrieved and cleaned',
        'ini_get(' => 'INI value retrieval',
        'ini_set(' => 'INI value modification',
        'ini_restore(' => 'INI value restoration',
        'get_cfg_var(' => 'Configuration variable retrieval',
        'extension_loaded(' => 'Extension check',
        'function_exists(' => 'Function existence check',
        'class_exists(' => 'Class existence check',
        'method_exists(' => 'Method existence check',
        'is_callable(' => 'Callable check',
        'defined(' => 'Constant definition check',
        'constant(' => 'Constant value retrieval'
    );
    
    // High-risk patterns with higher severity
    const HIGH_RISK_PATTERNS = array(
        'eval(' => 10,
        'exec(' => 10,
        'system(' => 10,
        'shell_exec(' => 10,
        'passthru(' => 10,
        'base64_decode(' => 8,
        'gzinflate(' => 8,
        'str_rot13(' => 7,
        'create_function(' => 9,
        'call_user_func(' => 7,
        'assert(' => 8,
        'preg_replace(' => 6,
        'file_get_contents(' => 5,
        'file_put_contents(' => 6,
        'fopen(' => 4,
        'fwrite(' => 5,
        'include(' => 4,
        'require(' => 4,
        'curl_exec(' => 6,
        'fsockopen(' => 7,
        'mysql_query(' => 5,
        'phpinfo(' => 6,
        'show_source(' => 7,
        'readfile(' => 5,
        'unlink(' => 6,
        'chmod(' => 5,
        'proc_open(' => 9,
        'popen(' => 8,
        'c99' => 10,
        'r57' => 10,
        'wso' => 10,
        'b374k' => 10,
        'backdoor' => 9,
        'rootkit' => 10,
        'error_reporting(0)' => 6,
        'set_time_limit(0)' => 5,
        '@eval(' => 10,
        '@exec(' => 10,
        '@system(' => 10
    );
}

// ==================== CLIENT CONFIGURATION ====================
class SecurityClientConfig
{
    // API Security - THAY ĐỔI API KEY NÀY
    const API_KEY = 'hiep-security-client-2025-change-this-key';
    const CLIENT_NAME = 'standalone-client'; // Tên website này
    const CLIENT_VERSION = '2.0';

    // Giới hạn quét cho client - UNLIMITED SCAN
    const MAX_SCAN_FILES = 999999999; // Virtually unlimited
    const MAX_SCAN_TIME = 600; // 10 phút
    const MAX_MEMORY = '512M'; // Tăng memory

    // API Patterns Configuration
    const PATTERNS_API_URL = 'https://hiepcodeweb.com/api/security_patterns.php';
    const API_CACHE_DURATION = 3600; // 1 hour cache
    const ENABLE_API_PATTERNS = true;

    // Logging Configuration
    const ENABLE_LOGGING = false; // Disable log file creation

    // Bảo mật
    const ALLOWED_IPS = array(); // Để trống = cho phép tất cả, hoặc array('IP1', 'IP2')
    const RATE_LIMIT = 10; // Số request/phút
}

// ==================== BẢO MẬT VÀ VALIDATION ====================
function validateApiRequest()
{
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

function getApiKey()
{
    // Lấy API key từ header hoặc parameter
    $headers = function_exists('getallheaders') ? getallheaders() : array();

    if (isset($headers['X-API-Key'])) {
        return $headers['X-API-Key'];
    }

    if (isset($headers['Authorization'])) {
        return str_replace('Bearer ', '', $headers['Authorization']);
    }

    // Check GET and POST parameters
    if (isset($_GET['api_key'])) {
        return $_GET['api_key'];
    }

    if (isset($_POST['api_key'])) {
        return $_POST['api_key'];
    }

    // Check JSON POST body
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput) {
        $data = json_decode($jsonInput, true);
        if ($data && isset($data['api_key'])) {
            return $data['api_key'];
        }
    }

    return null;
}

function getClientIP()
{
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
    }
    return 'unknown';
}

function checkRateLimit()
{
    $ip = getClientIP();
    $rateFile = './logs/rate_limit_' . md5($ip) . '.txt';

    if (!file_exists('./logs')) {
        mkdir('./logs', 0755, true);
    }

    $currentTime = time();
    $requests = array();

    if (file_exists($rateFile)) {
        // Rate limiting disabled for standalone version
        // $content = file_get_contents($rateFile);
        // $requests = $content ? json_decode($content, true) : array();
    }

    // Lọc request trong 1 phút qua
    $filteredRequests = array();
    foreach ($requests as $time) {
        if (($currentTime - $time) < 60) {
            $filteredRequests[] = $time;
        }
    }
    $requests = $filteredRequests;

    // Kiểm tra giới hạn
    if (count($requests) >= SecurityClientConfig::RATE_LIMIT) {
        return false;
    }

    // Thêm request hiện tại
    $requests[] = $currentTime;
    // file_put_contents($rateFile, json_encode($requests));

    return true;
}

// ==================== CORE SCANNING ENGINE ====================
class SecurityScanner
{
    private $scannedFiles = 0;
    private $suspiciousFiles = array();
    private $criticalFiles = array();
    private $scanStartTime;
    private $scanResults = array();
    private $apiPatterns = null;

    public function __construct()
    {
        $this->scanStartTime = time();

        // Cấu hình PHP cho scanning
        set_time_limit(SecurityClientConfig::MAX_SCAN_TIME);
        ini_set('memory_limit', SecurityClientConfig::MAX_MEMORY);
        ini_set('max_execution_time', SecurityClientConfig::MAX_SCAN_TIME);

        // Load API patterns if enabled (disabled in standalone)
        if (SecurityClientConfig::ENABLE_API_PATTERNS) {
            $this->loadApiPatterns();
        }
    }

    /**
     * Load blacklist/whitelist patterns from API (disabled in standalone)
     */
    private function loadApiPatterns()
    {
        // API patterns disabled in standalone version
        // Use embedded patterns instead
        $this->apiPatterns = array(
            'status' => 'success',
            'patterns' => SecurityScannerConfig::MALWARE_PATTERNS,
            'high_risk' => SecurityScannerConfig::HIGH_RISK_PATTERNS
        );
    }

    /**
     * Quét toàn bộ website
     */
    public function scanWebsite($scanPath = '.', $options = array())
    {
        $this->scanResults = array(
            'scan_id' => uniqid('scan_'),
            'start_time' => date('Y-m-d H:i:s', $this->scanStartTime),
            'scan_path' => $scanPath,
            'total_files' => 0,
            'scanned_files' => 0,
            'suspicious_files' => 0,
            'critical_files' => 0,
            'clean_files' => 0,
            'threats' => array(),
            'errors' => array(),
            'scan_duration' => 0,
            'memory_usage' => 0,
            'status' => 'running'
        );

        try {
            // Scan files
            $this->scanDirectory($scanPath, $options);

            // Finalize results
            $this->scanResults['scan_duration'] = time() - $this->scanStartTime;
            $this->scanResults['memory_usage'] = memory_get_peak_usage(true);
            $this->scanResults['status'] = 'completed';
            $this->scanResults['scanned_files'] = $this->scannedFiles;
            $this->scanResults['suspicious_files'] = count($this->suspiciousFiles);
            $this->scanResults['critical_files'] = count($this->criticalFiles);
            $this->scanResults['clean_files'] = $this->scannedFiles - count($this->suspiciousFiles) - count($this->criticalFiles);

        } catch (Exception $e) {
            $this->scanResults['status'] = 'error';
            $this->scanResults['errors'][] = $e->getMessage();
        }

        return $this->scanResults;
    }

    /**
     * Quét thư mục
     */
    private function scanDirectory($path, $options = array())
    {
        // Only exclude version control and package manager dirs - be more selective
        $excludeDirs = ['.git', '.svn', 'node_modules', 'vendor'];
        $excludeFiles = [
            'security_scan_client.php',
            'security_scan_client_standalone.php',
            'security_scan_server.php',
            'scanner_config.php'
        ]; // Bỏ qua các file này

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                // Kiểm tra timeout
                if ((time() - $this->scanStartTime) > SecurityClientConfig::MAX_SCAN_TIME) {
                    throw new Exception('Scan timeout exceeded');
                }

                // Kiểm tra memory
                if (memory_get_usage(true) > (512 * 1024 * 1024)) { // 512MB
                    throw new Exception('Memory limit exceeded');
                }

                if ($file->isFile()) {
                    $filePath = $file->getPathname();
                    $fileName = $file->getFilename();

                    // Skip excluded files
                    if (in_array($fileName, $excludeFiles)) {
                        continue;
                    }

                    // Skip excluded directories
                    $skip = false;
                    foreach ($excludeDirs as $excludeDir) {
                        if (strpos($filePath, DIRECTORY_SEPARATOR . $excludeDir . DIRECTORY_SEPARATOR) !== false) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip) continue;

                    // Scan file
                    $this->scanFile($filePath);

                    // Kiểm tra giới hạn files
                    if ($this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->scanResults['errors'][] = "Directory scan error: " . $e->getMessage();
        }
    }

    /**
     * Quét một file cụ thể
     */
    private function scanFile($filePath)
    {
        try {
            $this->scannedFiles++;

            // Kiểm tra extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($extension, SecurityScannerConfig::SCAN_EXTENSIONS) &&
                !in_array($extension, SecurityScannerConfig::SUSPICIOUS_EXTENSIONS)) {
                return;
            }

            // Kiểm tra file size
            $fileSize = filesize($filePath);
            if ($fileSize > SecurityScannerConfig::MAX_FILE_SIZE) {
                return;
            }

            // Đọc nội dung file
            $content = file_get_contents($filePath);
            if ($content === false) {
                return;
            }

            // Phân tích nội dung
            $threats = $this->analyzeContent($content, $filePath);

            if (!empty($threats)) {
                $threatLevel = $this->calculateThreatLevel($threats);

                $fileInfo = array(
                    'file' => $filePath,
                    'size' => $fileSize,
                    'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'threats' => $threats,
                    'threat_level' => $threatLevel,
                    'risk_score' => $this->calculateRiskScore($threats)
                );

                if ($threatLevel >= 8) {
                    $this->criticalFiles[] = $fileInfo;
                } else {
                    $this->suspiciousFiles[] = $fileInfo;
                }

                $this->scanResults['threats'][] = $fileInfo;
            }

        } catch (Exception $e) {
            $this->scanResults['errors'][] = "File scan error ($filePath): " . $e->getMessage();
        }
    }

    /**
     * Phân tích nội dung file
     */
    private function analyzeContent($content, $filePath)
    {
        $threats = array();
        $patterns = SecurityScannerConfig::MALWARE_PATTERNS;

        // Merge API patterns if available
        if ($this->apiPatterns && isset($this->apiPatterns['patterns'])) {
            $patterns = array_merge($patterns, $this->apiPatterns['patterns']);
        }

        foreach ($patterns as $pattern => $description) {
            if (stripos($content, $pattern) !== false) {
                $threats[] = array(
                    'pattern' => $pattern,
                    'description' => $description,
                    'severity' => $this->getPatternSeverity($pattern),
                    'line' => $this->findPatternLine($content, $pattern)
                );
            }
        }

        return $threats;
    }

    /**
     * Tính toán mức độ nguy hiểm
     */
    private function calculateThreatLevel($threats)
    {
        $maxLevel = 0;
        foreach ($threats as $threat) {
            if ($threat['severity'] > $maxLevel) {
                $maxLevel = $threat['severity'];
            }
        }
        return $maxLevel;
    }

    /**
     * Tính toán điểm rủi ro
     */
    private function calculateRiskScore($threats)
    {
        $score = 0;
        foreach ($threats as $threat) {
            $score += $threat['severity'];
        }
        return min($score, 100); // Max 100
    }

    /**
     * Lấy mức độ nghiêm trọng của pattern
     */
    private function getPatternSeverity($pattern)
    {
        $highRisk = SecurityScannerConfig::HIGH_RISK_PATTERNS;

        if (isset($highRisk[$pattern])) {
            return $highRisk[$pattern];
        }

        return 3; // Default severity
    }

    /**
     * Tìm dòng chứa pattern
     */
    private function findPatternLine($content, $pattern)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (stripos($line, $pattern) !== false) {
                return $lineNum + 1;
            }
        }
        return 0;
    }

    /**
     * Lấy kết quả scan
     */
    public function getResults()
    {
        return $this->scanResults;
    }

    /**
     * Lấy danh sách file nghi ngờ
     */
    public function getSuspiciousFiles()
    {
        return $this->suspiciousFiles;
    }

    /**
     * Lấy danh sách file nguy hiểm
     */
    public function getCriticalFiles()
    {
        return $this->criticalFiles;
    }
}

// ==================== API HANDLERS ====================

// Validate API request
validateApiRequest();

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'scan':
        handleScanRequest();
        break;

    case 'health':
        handleHealthCheck();
        break;

    case 'info':
        handleInfoRequest();
        break;

    case 'get_file_content':
        handleGetFileContent();
        break;

    case 'save_file_content':
        handleSaveFileContent();
        break;

    case 'delete_file':
        handleDeleteFile();
        break;

    case 'quarantine_file':
        handleQuarantineFile();
        break;

    case 'view_file':
        handleViewFile();
        break;

    case 'whitelist_file':
        handleWhitelistFile();
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// ==================== API HANDLER FUNCTIONS ====================

function handleScanRequest()
{
    try {
        $scanner = new SecurityScanner();
        $scanPath = isset($_GET['path']) ? $_GET['path'] : '.';
        $options = isset($_GET['options']) ? json_decode($_GET['options'], true) : array();

        $results = $scanner->scanWebsite($scanPath, $options);

        echo json_encode([
            'success' => true,
            'data' => $results
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleHealthCheck()
{
    echo json_encode([
        'success' => true,
        'status' => 'online',
        'client_name' => SecurityClientConfig::CLIENT_NAME,
        'version' => SecurityClientConfig::CLIENT_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ]
    ]);
}

function handleInfoRequest()
{
    echo json_encode([
        'success' => true,
        'client_info' => [
            'name' => SecurityClientConfig::CLIENT_NAME,
            'version' => SecurityClientConfig::CLIENT_VERSION,
            'api_key_hash' => md5(SecurityClientConfig::API_KEY),
            'max_scan_files' => SecurityClientConfig::MAX_SCAN_FILES,
            'max_scan_time' => SecurityClientConfig::MAX_SCAN_TIME,
            'max_memory' => SecurityClientConfig::MAX_MEMORY,
            'rate_limit' => SecurityClientConfig::RATE_LIMIT
        ],
        'server_info' => [
            'php_version' => PHP_VERSION,
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'current_time' => date('Y-m-d H:i:s')
        ]
    ]);
}

function handleGetFileContent()
{
    $filePath = isset($_GET['file_path']) ? $_GET['file_path'] : '';

    if (empty($filePath)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File path required']);
        exit;
    }

    // Security check - prevent directory traversal
    $realPath = realpath($filePath);
    $basePath = realpath('.');

    if (!$realPath || strpos($realPath, $basePath) !== 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }

    if (!is_readable($filePath)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'File not readable']);
        exit;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to read file']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'content' => $content,
        'file_info' => [
            'path' => $filePath,
            'size' => filesize($filePath),
            'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
            'permissions' => substr(sprintf('%o', fileperms($filePath)), -4)
        ]
    ]);
}

function handleSaveFileContent()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $filePath = isset($data['file_path']) ? $data['file_path'] : '';
    $content = isset($data['content']) ? $data['content'] : '';

    if (empty($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File path required']);
        exit;
    }

    // Security check - prevent directory traversal
    $realPath = realpath(dirname($filePath));
    $basePath = realpath('.');

    if ($realPath && strpos($realPath, $basePath) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Create directory if not exists
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $result = file_put_contents($filePath, $content);
    if ($result === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'File saved successfully',
        'bytes_written' => $result
    ]);
}

function handleDeleteFile()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $filePath = isset($data['file_path']) ? $data['file_path'] : '';

    if (empty($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File path required']);
        exit;
    }

    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }

    if (unlink($filePath)) {
        echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
    }
}

function handleQuarantineFile()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $filePath = isset($data['file_path']) ? $data['file_path'] : '';

    if (empty($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File path required']);
        exit;
    }

    // Create quarantine directory
    $quarantineDir = './quarantine';
    if (!is_dir($quarantineDir)) {
        mkdir($quarantineDir, 0755, true);
    }

    $fileName = basename($filePath);
    $quarantinePath = $quarantineDir . '/' . date('Y-m-d_H-i-s') . '_' . $fileName;

    if (rename($filePath, $quarantinePath)) {
        echo json_encode([
            'success' => true,
            'message' => 'File quarantined successfully',
            'quarantine_path' => $quarantinePath
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to quarantine file']);
    }
}

function handleViewFile()
{
    $filePath = isset($_GET['file_path']) ? $_GET['file_path'] : '';
    $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 50;

    if (empty($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File path required']);
        exit;
    }

    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File not found or not readable']);
        exit;
    }

    $content = file_get_contents($filePath);
    $fileLines = explode("\n", $content);
    $totalLines = count($fileLines);

    // Limit lines if requested
    if ($lines > 0 && $totalLines > $lines) {
        $fileLines = array_slice($fileLines, 0, $lines);
    }

    echo json_encode([
        'success' => true,
        'content' => implode("\n", $fileLines),
        'total_lines' => $totalLines,
        'displayed_lines' => count($fileLines),
        'file_info' => [
            'path' => $filePath,
            'size' => filesize($filePath),
            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
        ]
    ]);
}

function handleWhitelistFile()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $filePath = isset($data['file_path']) ? $data['file_path'] : '';
    $reason = isset($data['reason']) ? $data['reason'] : 'Manual whitelist';

    if (empty($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File path required']);
        exit;
    }

    // Create whitelist file (embedded in memory for standalone)
    $whitelistData = [
        'files' => [$filePath],
        'reason' => $reason,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode([
        'success' => true,
        'message' => 'File whitelisted successfully (session only)',
        'whitelist_data' => $whitelistData
    ]);
}

// ==================== END OF STANDALONE CLIENT ====================
?>
