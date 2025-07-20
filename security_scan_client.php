<?php

/**
 * Security Scanner Client - API Version
 * Đặt file này trên mỗi website cần quét
 * Author: Hiệp Nguyễn
 * Version: 1.0 Client API
 */

// ==================== CẤU HÌNH CLIENT ====================
class SecurityClientConfig
{
    // API Security - THAY ĐỔI API KEY NÀY
    const API_KEY = 'hiep-security-client-2025-change-this-key';
    const CLIENT_NAME = 'xemay365'; // Tên website này
    const CLIENT_VERSION = '1.0';

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
    const ALLOWED_IPS = []; // Để trống = cho phép tất cả, hoặc ['IP1', 'IP2']
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
    $headers = getallheaders();

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
    $requests = [];

    if (file_exists($rateFile)) {
        // $content = file_get_contents($rateFile);
        // $requests = $content ? json_decode($content, true) : [];
    }

    // Lọc request trong 1 phút qua
    $requests = array_filter($requests, function ($time) use ($currentTime) {
        return ($currentTime - $time) < 60;
    });

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
    private $suspiciousFiles = [];
    private $criticalFiles = [];
    private $scanStartTime;
    private $scanResults = [];
    private $apiPatterns = null;

    public function __construct()
    {
        $this->scanStartTime = time();

        // Cấu hình PHP cho scanning
        set_time_limit(SecurityClientConfig::MAX_SCAN_TIME);
        ini_set('memory_limit', SecurityClientConfig::MAX_MEMORY);
        ini_set('max_execution_time', SecurityClientConfig::MAX_SCAN_TIME);

        // Load API patterns if enabled
        if (SecurityClientConfig::ENABLE_API_PATTERNS) {
            $this->loadApiPatterns();
        }
    }

    /**
     * Load blacklist/whitelist patterns from API
     */
    private function loadApiPatterns()
    {
        try {
            $cacheFile = __DIR__ . '/cache/api_patterns.json';
            $cacheDir = dirname($cacheFile);

            // Create cache directory if not exists
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            // Check cache validity
            if (file_exists($cacheFile)) {
                $cacheAge = time() - filemtime($cacheFile);
                if ($cacheAge < SecurityClientConfig::API_CACHE_DURATION) {
                    $cached = file_get_contents($cacheFile);
                    $this->apiPatterns = json_decode($cached, true);
                    if ($this->apiPatterns && isset($this->apiPatterns['status']) && $this->apiPatterns['status'] === 'success') {
                        return;
                    }
                }
            }

            // Fetch from API
            $url = SecurityClientConfig::PATTERNS_API_URL . '?action=get_patterns';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'SecurityScanner/' . SecurityClientConfig::CLIENT_VERSION
                ]
            ]);

            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                throw new Exception('Failed to fetch API patterns');
            }

            $patterns = json_decode($response, true);
            if (!$patterns || !isset($patterns['status']) || $patterns['status'] !== 'success') {
                throw new Exception('Invalid API response');
            }

            $this->apiPatterns = $patterns;

            // Cache the patterns
            file_put_contents($cacheFile, $response);
        } catch (Exception $e) {
            error_log("API Patterns Error: " . $e->getMessage());
            // Use fallback patterns
            $this->apiPatterns = $this->getFallbackPatterns();
        }
    }

    /**
     * Get fallback patterns if API is unavailable
     */
    private function getFallbackPatterns()
    {
        return [
            'status' => 'success',
            'critical_malware_patterns' => [
                'eval(' => 'Code execution vulnerability',
                'goto ' => 'Control flow manipulation',
                'base64_decode(' => 'Encoded payload execution',
                'gzinflate(' => 'Compressed malware payload',
                'str_rot13(' => 'String obfuscation technique',
                '$_F=__FILE__;' => 'File system manipulation',
                'readdir(' => 'Directory traversal attempt',
                '<?php eval' => 'Direct PHP code injection'
            ],
            'suspicious_file_patterns' => [
                '.php.jpg' => 'Disguised PHP file with image extension',
                '.php.png' => 'Disguised PHP file with image extension',
                '.php.gif' => 'Disguised PHP file with image extension',
                '.php.jpeg' => 'Disguised PHP file with image extension',
                '.phtml' => 'Alternative PHP extension',
                '.php3' => 'Legacy PHP extension',
                '.php4' => 'Legacy PHP extension',
                '.php5' => 'Legacy PHP extension'
            ],
            'severe_patterns' => [
                'move_uploaded_file(' => 'File upload without validation',
                'exec(' => 'System command execution',
                'system(' => 'Direct system call',
                'shell_exec(' => 'Shell command execution',
                'passthru(' => 'Command output bypass',
                'proc_open(' => 'Process creation',
                'popen(' => 'Pipe command execution'
            ],
            'warning_patterns' => [
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
                'curl_exec(' => 'HTTP request execution',
                'unlink(' => 'File deletion',
                'rmdir(' => 'Directory removal',
                'mkdir(' => 'Directory creation'
            ],
            'blacklist' => [
                'file_names' => ['shell.php', 'backdoor.php', 'webshell.php', 'c99.php', 'r57.php'],
                'content_patterns' => ['eval\\s*\\(.*\\$_', 'system\\s*\\(.*\\$_', 'exec\\s*\\(.*\\$_']
            ],
            'whitelist' => [
                'framework_files' => ['wp-config.php', 'composer.json', 'package.json'],
                'safe_directories' => ['wp-admin', 'wp-includes', 'vendor', 'node_modules']
            ]
        ];
    }

    /**
     * Check if file should be skipped (whitelist)
     */
    private function isWhitelistedFile($filePath, $fileName)
    {
        if (!$this->apiPatterns || !isset($this->apiPatterns['whitelist'])) {
            return false;
        }

        $whitelist = $this->apiPatterns['whitelist'];

        // Check framework files
        if (isset($whitelist['framework_files']) && in_array($fileName, $whitelist['framework_files'])) {
            return true;
        }

        // Check safe directories
        if (isset($whitelist['safe_directories'])) {
            foreach ($whitelist['safe_directories'] as $safeDir) {
                if (strpos($filePath, '/' . $safeDir . '/') !== false) {
                    return true;
                }
            }
        }

        // Check safe extensions
        if (isset($whitelist['safe_extensions'])) {
            $extension = '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            foreach ($whitelist['safe_extensions'] as $safeExt) {
                if (fnmatch($safeExt, $fileName) || fnmatch($safeExt, $extension)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if file is blacklisted (high priority)
     */
    private function isBlacklistedFile($filePath, $fileName)
    {
        if (!$this->apiPatterns || !isset($this->apiPatterns['blacklist'])) {
            return false;
        }

        $blacklist = $this->apiPatterns['blacklist'];

        // Check dangerous file names
        if (isset($blacklist['file_names']) && in_array($fileName, $blacklist['file_names'])) {
            return true;
        }

        // Check dangerous file extensions
        if (isset($blacklist['file_extensions'])) {
            foreach ($blacklist['file_extensions'] as $dangerExt) {
                if (fnmatch($dangerExt, $fileName)) {
                    return true;
                }
            }
        }

        // Check suspicious paths
        if (isset($blacklist['suspicious_paths'])) {
            foreach ($blacklist['suspicious_paths'] as $suspPath) {
                if (strpos($filePath, $suspPath) !== false) {
                    return true;
                }
            }
        }

        // Check directory patterns
        if (isset($blacklist['directory_patterns'])) {
            foreach ($blacklist['directory_patterns'] as $pattern) {
                if (fnmatch($pattern, $filePath)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get additional content patterns from API
     */
    private function getApiContentPatterns()
    {
        if (!$this->apiPatterns || !isset($this->apiPatterns['blacklist']['content_patterns'])) {
            return [];
        }

        return $this->apiPatterns['blacklist']['content_patterns'];
    }

    public function performScan($options = [])
    {
        try {
            // Khởi tạo
            $this->scannedFiles = 0;
            $this->suspiciousFiles = [];
            $this->criticalFiles = [];

            // Lấy priority files từ options
            $priorityFiles = $options['priority_files'] ?? [];
            $whitelist = $this->apiPatterns['whitelist'];
            // Add API content patterns to enhance detection
            $apiContentPatterns = $this->getApiContentPatterns();


            // Get patterns from API or fallback
            $patterns = $this->apiPatterns;

            // Use EXACT patterns from security_scan.php - PROVEN TO WORK

            // Critical malware patterns (HIGH SEVERITY - RED) - FROM security_scan.php
            $criticalPatterns = [
                'eval(' => 'Code execution vulnerability',
                'goto ' => 'Control flow manipulation',
                'base64_decode(' => 'Encoded payload execution',
                'gzinflate(' => 'Compressed malware payload',
                'str_rot13(' => 'String obfuscation technique',
                '$_F=__FILE__;' => 'File system manipulation',
                'readdir(' => 'Directory traversal attempt',
                '<?php eval' => 'Direct PHP code injection'
            ];

            // Severe patterns - FROM security_scan.php  
            $severePatterns = [
                'move_uploaded_file(' => 'File upload without validation',
                'exec(' => 'System command execution',
                'system(' => 'Direct system call',
                'shell_exec(' => 'Shell command execution',
                'passthru(' => 'Command output bypass',
                'proc_open(' => 'Process creation',
                'popen(' => 'Pipe command execution'
            ];

            // Warning patterns - FROM security_scan.php
            $warningPatterns = [
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
                'curl_exec(' => 'HTTP request execution',
                'unlink(' => 'File deletion',
                'rmdir(' => 'Directory removal',
                'mkdir(' => 'Directory creation'
            ];

            // Suspicious file patterns - FROM security_scan.php
            $suspiciousFilePatterns = [
                '.php.jpg' => 'Disguised PHP file with image extension',
                '.php.png' => 'Disguised PHP file with image extension',
                '.php.gif' => 'Disguised PHP file with image extension',
                '.php.jpeg' => 'Disguised PHP file with image extension',
                '.phtml' => 'Alternative PHP extension',
                '.php3' => 'Legacy PHP extension',
                '.php4' => 'Legacy PHP extension',
                '.php5' => 'Legacy PHP extension'
            ];

            // Webshell Detection Patterns
            $webshellPatterns = $this->getWebshellPatterns();

            // Merge API patterns into detection patterns
            foreach ($apiContentPatterns as $pattern) {
                $criticalPatterns[$pattern] = 'API Blacklist Pattern';
            }


            // Bắt đầu quét - ưu tiên priority files trước - sử dụng patterns từ security_scan.php
            $this->scanDirectoryAdvanced('./', $criticalPatterns, $severePatterns, $warningPatterns, $suspiciousFilePatterns, $priorityFiles, $whitelist);

            // Debug logging
            error_log("Security scan completed - Files scanned: {$this->scannedFiles}, Suspicious found: " . count($this->suspiciousFiles));

            // Tạo kết quả
            $this->generateScanResults();

            return [
                'success' => true,
                'client_info' => [
                    'id' => SecurityClientConfig::CLIENT_NAME, // Use client name as ID
                    'name' => SecurityClientConfig::CLIENT_NAME,
                    'version' => SecurityClientConfig::CLIENT_VERSION,
                    'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                    'url' => 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME'] ?? ''),
                    'client_url' => 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['SCRIPT_NAME'],
                    'api_key' => SecurityClientConfig::API_KEY,
                    'scan_time' => time() - $this->scanStartTime,
                    'timestamp' => time()
                ],
                'scan_results' => $this->scanResults,
                'timestamp' => date('Y-m-d H:i:s'),
                'api_patterns' => $whitelist
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

    private function scanDirectory($dir, $criticalPatterns, $suspiciousPatterns, $webshellPatterns = [])
    {
        if (!is_dir($dir) || $this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
            return;
        }

        // Only exclude version control and package manager dirs - be more selective
        $excludeDirs = ['.git', '.svn', 'node_modules', 'vendor'];
        $excludeFiles = [
            'security_scan_client.php',
            'security_scan_server.php',
            './config/scanner_config.php',
            'config/scanner_config.php',
            'scanner_config.php'
        ]; // Bỏ qua các file này

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

                    // Bỏ qua file không cần thiết - Enhanced check
                    if (
                        in_array($fileName, $excludeFiles) ||
                        in_array($filePath, $excludeFiles) ||
                        strpos($filePath, 'scanner_config.php') !== false ||
                        strpos($fileName, 'scanner_config') !== false
                    ) {
                        continue;
                    }

                    // Check API whitelist - skip safe files
                    if ($this->isWhitelistedFile($filePath, $fileName)) {
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

                    // Enhanced file scanning - scan more file types
                    $shouldScanFile = false;

                    // Always scan PHP files
                    if ($extension === 'php' || strpos($fileName, '.php.') !== false) {
                        $shouldScanFile = true;
                    }

                    // Scan suspicious extensions
                    if ($this->isSuspiciousFileExtension($fileName)) {
                        $shouldScanFile = true;
                    }

                    // Scan files with no extension (could be malware)
                    if (empty($extension) && filesize($filePath) > 0) {
                        $shouldScanFile = true;
                    }

                    // Scan text-based files that could contain malware
                    $textExtensions = ['txt', 'inc', 'conf', 'config', 'log', 'dat', 'cache'];
                    if (in_array($extension, $textExtensions)) {
                        $shouldScanFile = true;
                    }

                    // Scan files with suspicious names regardless of extension
                    $suspiciousNames = ['shell', 'hack', 'backdoor', 'c99', 'r57', 'wso', 'b374k', 'adminer'];
                    foreach ($suspiciousNames as $suspName) {
                        if (stripos($fileName, $suspName) !== false) {
                            $shouldScanFile = true;
                            break;
                        }
                    }

                    if ($shouldScanFile) {
                        // Debug logging for file scanning
                        error_log("Scanning file: {$filePath}");
                        $this->scanFile($filePath, $criticalPatterns, $suspiciousPatterns, $webshellPatterns);
                        $this->scannedFiles++;
                    }
                }
            }
        } catch (Exception $e) {
            // Tiếp tục quét nếu có lỗi
            error_log("Scan error in directory $dir: " . $e->getMessage());
        }
    }

    private function scanDirectoryWithPriority($dir, $criticalPatterns, $suspiciousPatterns, $webshellPatterns = [], $priorityFiles = [])
    {
        if (!is_dir($dir) || $this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
            return;
        }

        // Only exclude version control and package manager dirs - be more selective  
        $excludeDirs = ['.git', '.svn', 'node_modules', 'vendor'];
        $excludeFiles = [
            'security_scan_client.php',
            'security_scan_server.php',
            './config/scanner_config.php',
            'config/scanner_config.php',
            'scanner_config.php'
        ]; // Bỏ qua các file này

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            // Collect all files first
            $allFiles = [];
            $priorityMatches = [];

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getPathname();
                    $fileName = basename($filePath);
                    $extension = strtolower($file->getExtension());

                    // Bỏ qua file không cần thiết - Enhanced check
                    if (
                        in_array($fileName, $excludeFiles) ||
                        in_array($filePath, $excludeFiles) ||
                        strpos($filePath, 'scanner_config.php') !== false ||
                        strpos($fileName, 'scanner_config') !== false
                    ) {
                        continue;
                    }

                    // Check API whitelist - skip safe files
                    if ($this->isWhitelistedFile($filePath, $fileName)) {
                        continue;
                    }

                    // Bỏ qua thư mục loại trừ
                    $skip = false;
                    foreach ($excludeDirs as $excludeDir) {
                        if (strpos($filePath, $excludeDir) !== false) {
                            $skip = true;
                            break;
                        }
                    }

                    if ($skip) {
                        continue;
                    }

                    // Chỉ quét file PHP và một số file cấu hình
                    if (
                        $extension === 'php' || strpos($fileName, '.php.') !== false ||
                        $this->isSuspiciousFileExtension($fileName)
                    ) {

                        // Check API blacklist first (highest priority)
                        $isPriority = $this->isBlacklistedFile($filePath, $fileName);

                        // If not blacklisted, check priority patterns
                        if (!$isPriority) {
                            foreach ($priorityFiles as $pattern) {
                                if ($this->matchesPattern($filePath, $pattern)) {
                                    $isPriority = true;
                                    break;
                                }
                            }
                        }

                        if ($isPriority) {
                            $priorityMatches[] = $filePath;
                        } else {
                            $allFiles[] = $filePath;
                        }
                    }
                }
            }

            // Scan priority files first
            foreach ($priorityMatches as $filePath) {
                if ($this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
                    break;
                }

                $this->scanFile($filePath, $criticalPatterns, $suspiciousPatterns, $webshellPatterns, true);
                $this->scannedFiles++;
            }

            // Then scan regular files
            foreach ($allFiles as $filePath) {
                if ($this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
                    break;
                }

                $this->scanFile($filePath, $criticalPatterns, $suspiciousPatterns, $webshellPatterns, false);
                $this->scannedFiles++;
            }
        } catch (Exception $e) {
            // Tiếp tục quét nếu có lỗi
            error_log("Scan error in directory $dir: " . $e->getMessage());
        }
    }

    private function matchesPattern($filePath, $pattern)
    {
        // Convert wildcard pattern to regex
        $pattern = str_replace(['*', '?'], ['.*', '.'], $pattern);
        $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/i';

        // Check if filename matches pattern
        $fileName = basename($filePath);
        return preg_match($pattern, $fileName) || preg_match($pattern, $filePath);
    }

    /**
     * Advanced scanning method using logic from security_scan.php - PROVEN TO WORK
     */
    private function scanDirectoryAdvanced($dir, $criticalPatterns, $severePatterns, $warningPatterns, $suspiciousFilePatterns, $priorityFiles = [], $whitelist = [])
    {
        if (!is_dir($dir) || $this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
            return;
        }

        // Minimal exclusions - SAME AS security_scan.php
        $excludeDirs = ['.git', '.svn', '.hg'];
        $excludeFiles = ['security_scan_client.php', 'security_scan_server.php', 'scanner_config.php', 'server_cron.php'];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
                    break;
                }

                // Performance optimization - yield control every 50 files
                if ($this->scannedFiles % 50 === 0) {
                    usleep(500); // 0.5ms pause to prevent blocking
                }

                if ($file->isFile()) {
                    $filePath = $file->getPathname();
                    $fileName = basename($filePath);
                    $extension = strtolower($file->getExtension());

                    // Skip excluded files
                    if (in_array($fileName, $excludeFiles)) {
                        continue;
                    }

                    // Skip excluded directories
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


                    // 3. Skip whitelist patterns
                    if (!empty($whitelist)) {
                        foreach ($whitelist as $pat) {
                            // fnmatch hỗ trợ wildcard, hoặc đơn giản strpos
                            if (
                                (@fnmatch($pat, $fileName)   === true) ||
                                (@fnmatch($pat, $filePath)   === true) ||
                                (strpos($filePath, $pat) !== false)
                            ) {
                                // bỏ qua file này, tiếp vòng lặp ngoài
                                continue 2;
                            }
                        }
                    }



                    // EXACT scanning logic from security_scan.php
                    $shouldScan = ($extension === 'php') ||
                        (strpos($fileName, '.php.') !== false) ||
                        in_array($extension, ['phtml', 'php3', 'php4', 'php5']);

                    if ($shouldScan) {
                        // Additional check: Only scan files within current project directory
                        $realPath = realpath($filePath);
                        $projectRoot = realpath('./');

                        if (!$realPath || !$projectRoot || strpos($realPath, $projectRoot) !== 0) {
                            continue; // Skip files outside project directory
                        }

                        $this->scannedFiles++;
                        error_log("SCANNING FILE: {$filePath}");

                        // Get file metadata
                        $fileMetadata = $this->getFileMetadata($filePath);

                        // Check for suspicious file extensions and empty files FIRST (HIGHEST PRIORITY)
                        $suspiciousIssues = $this->checkSuspiciousFile($filePath, $suspiciousFilePatterns);
                        if (!empty($suspiciousIssues)) {
                            $this->suspiciousFiles[] = [
                                'path' => $filePath,
                                'issues' => $suspiciousIssues,
                                'file_size' => filesize($filePath),
                                'modified_time' => filemtime($filePath),
                                'md5' => md5_file($filePath),
                                'category' => 'suspicious_file',
                                'is_priority' => in_array($fileName, $priorityFiles),
                                'metadata' => $fileMetadata
                            ];
                            $this->criticalFiles[] = $filePath;
                        } else {
                            // Check for critical malware patterns (HIGHEST PRIORITY)
                            $criticalIssues = $this->scanFileWithLineNumbers($filePath, $criticalPatterns);
                            if (!empty($criticalIssues)) {
                                $this->suspiciousFiles[] = [
                                    'path' => $filePath,
                                    'issues' => $criticalIssues,
                                    'file_size' => filesize($filePath),
                                    'modified_time' => filemtime($filePath),
                                    'md5' => md5_file($filePath),
                                    'category' => 'critical',
                                    'is_priority' => in_array($fileName, $priorityFiles),
                                    'metadata' => $fileMetadata
                                ];
                                $this->criticalFiles[] = $filePath;
                            } else {
                                // Check for severe patterns
                                $severeIssues = $this->scanFileWithLineNumbers($filePath, $severePatterns);
                                if (!empty($severeIssues)) {
                                    $this->suspiciousFiles[] = [
                                        'path' => $filePath,
                                        'issues' => $severeIssues,
                                        'file_size' => filesize($filePath),
                                        'modified_time' => filemtime($filePath),
                                        'md5' => md5_file($filePath),
                                        'category' => 'severe',
                                        'is_priority' => in_array($fileName, $priorityFiles),
                                        'metadata' => $fileMetadata
                                    ];
                                } else {
                                    // Check for warning patterns (LOWER PRIORITY)
                                    $warningIssues = $this->scanFileWithLineNumbers($filePath, $warningPatterns);
                                    if (!empty($warningIssues)) {
                                        $this->suspiciousFiles[] = [
                                            'path' => $filePath,
                                            'issues' => $warningIssues,
                                            'file_size' => filesize($filePath),
                                            'modified_time' => filemtime($filePath),
                                            'md5' => md5_file($filePath),
                                            'category' => 'warning',
                                            'is_priority' => in_array($fileName, $priorityFiles),
                                            'metadata' => $fileMetadata
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("SCAN DIRECTORY ERROR: " . $e->getMessage());
        }
    }

    /**
     * Scan file with line numbers - EXACT copy from security_scan.php
     */
    private function scanFileWithLineNumbers($filePath, $patterns)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $lines = explode("\n", $content);
        $issues = [];

        foreach ($patterns as $pattern => $description) {
            foreach ($lines as $lineNumber => $line) {
                // Use stripos for case-insensitive matching - SAME AS security_scan.php
                if (stripos($line, $pattern) !== false) {
                    error_log("PATTERN FOUND in {$filePath}: {$pattern} at line " . ($lineNumber + 1));
                    $issues[] = [
                        'pattern' => $pattern,
                        'description' => $description,
                        'line' => $lineNumber + 1,
                        'severity' => 'critical',
                        'context' => trim($line)
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Check suspicious file - EXACT copy from security_scan.php
     */
    private function checkSuspiciousFile($filePath, $suspiciousPatterns)
    {
        $issues = [];
        $fileName = basename($filePath);

        // Check for suspicious file extensions
        foreach ($suspiciousPatterns as $pattern => $description) {
            if (stripos($fileName, $pattern) !== false) {
                $issues[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'line' => 0,
                    'severity' => 'critical',
                    'context' => 'Suspicious filename: ' . $fileName
                ];
            }
        }

        // Enhanced empty file detection - EXACTLY from security_scan.php
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'php') {
            $content = @file_get_contents($filePath);
            if ($content !== false) {
                $contentTrimmed = trim($content);
                $contentNoPhpTags = str_replace(['<?php', '<?', '?>'], '', $contentTrimmed);
                $contentClean = trim($contentNoPhpTags);
                $fileSize = filesize($filePath);

                $isSuspiciousEmpty = false;
                $description = '';

                // Case 1: Completely empty file (0 bytes)
                if ($fileSize === 0 || empty($contentTrimmed)) {
                    $isSuspiciousEmpty = true;
                    $description = 'EMPTY PHP FILE (0 bytes) - Definitely planted by hacker';
                }
                // Case 2: Only PHP tags with no content (very small files)
                elseif (empty($contentClean) || strlen($contentClean) < 3) {
                    $isSuspiciousEmpty = true;
                    $description = 'Nearly empty PHP file - Contains only PHP tags or whitespace';
                }
                // Case 3: Very small file anywhere (common hacker pattern)
                elseif ($fileSize < 50) {
                    $isSuspiciousEmpty = true;
                    $description = 'Extremely small PHP file (' . $fileSize . ' bytes) - Very suspicious';
                }
                // Case 4: Common hacker filenames
                elseif (in_array(strtolower($fileName), ['app.php', 'style.php', 'config.php', 'db.php', 'wp.php', 'test.php', 'shell.php', 'hack.php', 'backdoor.php', 'upload.php'])) {
                    $isSuspiciousEmpty = true;
                    $description = 'Common hacker filename "' . $fileName . '" - EXTREMELY SUSPICIOUS';
                }

                if ($isSuspiciousEmpty) {
                    $issues[] = [
                        'pattern' => 'suspicious_empty_file',
                        'description' => $description,
                        'line' => 1,
                        'severity' => 'critical',
                        'context' => 'File size: ' . $fileSize . ' bytes. Content: ' . substr($contentTrimmed, 0, 100) . '...'
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Get file metadata - from security_scan.php
     */
    private function getFileMetadata($filePath)
    {
        $metadata = [
            'modified_time' => 0,
            'size' => 0,
            'is_recent' => false,
            'age_category' => 'old'
        ];

        if (file_exists($filePath)) {
            $modifiedTime = filemtime($filePath);
            $metadata['modified_time'] = $modifiedTime;
            $metadata['size'] = filesize($filePath);

            $now = time();
            $hours24 = 24 * 3600;
            $days7 = 7 * 24 * 3600;
            $months5 = 5 * 30 * 24 * 3600;

            if (($now - $modifiedTime) < $hours24) {
                $metadata['age_category'] = 'very_recent';
                $metadata['is_recent'] = true;
            } elseif (($now - $modifiedTime) < $days7) {
                $metadata['age_category'] = 'recent';
                $metadata['is_recent'] = true;
            } elseif (($now - $modifiedTime) < $months5) {
                $metadata['age_category'] = 'medium';
            } else {
                $metadata['age_category'] = 'old';
            }
        }

        return $metadata;
    }

    private function scanFile($filePath, $criticalPatterns, $suspiciousPatterns, $webshellPatterns = [], $isPriority = false)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            error_log("SCAN ERROR: File not exists/readable: {$filePath}");
            return;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            error_log("SCAN ERROR: Cannot read file content: {$filePath}");
            return;
        }

        // Debug: Log file scanning with basic content info
        $contentLength = strlen($content);
        error_log("SCANNING FILE: {$filePath} (Size: {$contentLength} bytes)");

        $issues = [];

        // Kiểm tra critical patterns
        foreach ($criticalPatterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                $lineNumber = $this->findPatternLineNumber($content, $pattern);

                // Debug logging for pattern detection
                error_log("CRITICAL PATTERN FOUND in {$filePath}: {$pattern} at line {$lineNumber}");

                $issues[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'severity' => 'critical',
                    'line' => $lineNumber,
                    'context' => $this->getContextAroundPattern($content, $pattern)
                ];
            }
        }

        // Kiểm tra suspicious patterns
        foreach ($suspiciousPatterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                $lineNumber = $this->findPatternLineNumber($content, $pattern);
                $issues[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'severity' => 'warning',
                    'line' => $lineNumber,
                    'context' => $this->getContextAroundPattern($content, $pattern)
                ];
            }
        }

        // Kiểm tra webshell patterns
        foreach ($webshellPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $lineNumber = $this->findPatternLineNumber($content, $pattern);
                $issues[] = [
                    'pattern' => $pattern,
                    'description' => $description,
                    'severity' => 'critical',
                    'type' => 'webshell',
                    'line' => $lineNumber,
                    'context' => $this->getContextAroundPattern($content, $pattern)
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
                'md5' => md5_file($filePath),
                'category' => $this->categorizeFile($filePath, $issues),
                'is_priority' => $isPriority
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

    private function categorizeFile($filePath, $issues)
    {
        $fileName = basename($filePath);

        // Kiểm tra nếu là file manager
        if (
            strpos($fileName, 'filemanager') !== false ||
            strpos($fileName, 'file_manager') !== false ||
            strpos($fileName, 'upload') !== false
        ) {
            return 'filemanager';
        }

        // Kiểm tra nếu là suspicious file extension
        if (
            strpos($fileName, '.php.') !== false ||
            $this->isSuspiciousFileExtension($fileName)
        ) {
            return 'suspicious_file';
        }

        // Kiểm tra nếu có webshell patterns
        foreach ($issues as $issue) {
            if (isset($issue['type']) && $issue['type'] === 'webshell') {
                return 'webshell';
            }
        }

        // Kiểm tra nếu có critical issues
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'critical') {
                return 'critical';
            }
        }

        return 'warning';
    }

    private function getWebshellPatterns()
    {
        return [
            // Common webshell signatures
            '/\$_(GET|POST|REQUEST)\s*\[\s*[\'"][^\'\"]*[\'"]\s*\]\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Dynamic function execution via HTTP parameters',
            '/eval\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i' => 'Direct eval() with user input',
            '/system\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'System command execution via HTTP',
            '/exec\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Command execution via HTTP parameters',
            '/shell_exec\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Shell execution via HTTP parameters',
            '/passthru\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Passthru execution via HTTP parameters',
            '/base64_decode\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Base64 decode of user input',
            '/gzinflate\s*\(\s*base64_decode/i' => 'Compressed and encoded payload',
            '/str_rot13\s*\(\s*base64_decode/i' => 'ROT13 and Base64 obfuscation',

            // File operation webshells
            '/file_put_contents\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'File writing via HTTP parameters',
            '/fwrite\s*\(\s*.*\$_(GET|POST|REQUEST)/i' => 'File writing with user input',
            '/move_uploaded_file\s*\(\s*\$_FILES/i' => 'File upload processing',

            // Common webshell names and patterns
            '/c99|r57|wso|b374k|adminer|shell|backdoor/i' => 'Known webshell signature',
            '/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*\$_(GET|POST|REQUEST).*eval/i' => 'Variable assignment and eval pattern',

            // Obfuscated patterns
            '/chr\s*\(\s*\d+\s*\)\s*\.\s*chr\s*\(\s*\d+\s*\)/i' => 'Character concatenation obfuscation',
            '/\\\\x[0-9a-f]{2}/i' => 'Hexadecimal character encoding',

            // PHP webshell specific
            '/assert\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Assert function with user input',
            '/preg_replace.*\/e.*\$_(GET|POST|REQUEST)/i' => 'Preg_replace with eval modifier',
            '/create_function\s*\(.*\$_(GET|POST|REQUEST)/i' => 'Dynamic function creation with user input'
        ];
    }

    private function isSuspiciousFileExtension($fileName)
    {
        $suspiciousExtensions = [
            '.php.txt',
            '.php.bak',
            '.php.old',
            '.php.tmp',
            '.php.backup',
            '.phtml',
            '.php3',
            '.php4',
            '.php5',
            '.phps',
            '.pht',
            '.phar'
        ];

        foreach ($suspiciousExtensions as $ext) {
            if (stripos($fileName, $ext) !== false) {
                return true;
            }
        }

        return false;
    }

    private function findPatternLineNumber($content, $pattern)
    {
        $lines = explode("\n", $content);

        // Check if pattern is a regex (starts with / and contains regex flags)
        $isRegex = (strpos($pattern, '/') === 0 && preg_match('/\/[imsxADSUXJu]*$/', $pattern));

        foreach ($lines as $lineNum => $line) {
            $found = false;

            if ($isRegex) {
                // Use preg_match for regex patterns
                $found = @preg_match($pattern, $line);
            } else {
                // Use strpos for simple string patterns
                $found = (strpos($line, $pattern) !== false);
            }

            if ($found) {
                return $lineNum + 1;
            }
        }
        return 0;
    }

    private function getContextAroundPattern($content, $pattern)
    {
        $lines = explode("\n", $content);

        // Check if pattern is a regex (starts with / and contains regex flags)
        $isRegex = (strpos($pattern, '/') === 0 && preg_match('/\/[imsxADSUXJu]*$/', $pattern));

        foreach ($lines as $lineNum => $line) {
            $found = false;

            if ($isRegex) {
                // Use preg_match for regex patterns
                $found = @preg_match($pattern, $line);
            } else {
                // Use strpos for simple string patterns
                $found = (strpos($line, $pattern) !== false);
            }

            if ($found) {
                $start = max(0, $lineNum - 2);
                $end = min(count($lines) - 1, $lineNum + 2);
                $context = [];
                for ($i = $start; $i <= $end; $i++) {
                    $context[] = ($i + 1) . ': ' . trim($lines[$i]);
                }
                return implode("\n", $context);
            }
        }
        return '';
    }

    private function isSuspiciousFile($filePath, $content)
    {
        $fileName = basename($filePath);
        $fileSize = strlen($content);

        // File rỗng hoặc quá nhỏ
        if ($fileSize < 10) {
            return true;
        }

        // Tên file nghi ngờ
        $suspiciousNames = [
            'app.php',
            'style.php',
            'cache.php',
            'config.php',
            'wp.php',
            'test.php',
            'shell.php',
            'hack.php'
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

    private function generateScanResults()
    {
        $criticalCount = count($this->criticalFiles);
        $suspiciousCount = count($this->suspiciousFiles);

        // Phân loại threats theo category
        $criticalThreats = [];
        $warningThreats = [];
        $webshellThreats = [];

        foreach ($this->suspiciousFiles as $threat) {
            $category = $threat['category'] ?? 'warning';

            if ($category === 'webshell') {
                $webshellThreats[] = $threat;
            } elseif ($category === 'critical') {
                $criticalThreats[] = $threat;
            } else {
                $warningThreats[] = $threat;
            }
        }

        // Tính toán risk score
        $riskScore = $this->calculateRiskScore($criticalCount, $suspiciousCount, count($webshellThreats));

        $this->scanResults = [
            'scanned_files' => $this->scannedFiles,
            'suspicious_count' => $suspiciousCount,
            'critical_count' => $criticalCount,
            'webshell_count' => count($webshellThreats),
            'scan_time' => time() - $this->scanStartTime,
            'memory_used' => memory_get_peak_usage(true),
            'threats' => [
                'critical' => $criticalThreats,
                'webshells' => $webshellThreats,
                'warnings' => $warningThreats,
                'all' => $this->suspiciousFiles
            ],
            'critical_files' => $this->criticalFiles,
            'risk_score' => $riskScore,
            'status' => $this->determineStatus($criticalCount, count($webshellThreats), $suspiciousCount),
            'recommendations' => $this->generateRecommendations($criticalCount, count($webshellThreats), $suspiciousCount)
        ];
    }

    private function calculateRiskScore($criticalCount, $suspiciousCount, $webshellCount)
    {
        $score = 0;
        $score += $criticalCount * 10;
        $score += $webshellCount * 20;
        $score += $suspiciousCount * 2;

        return min(100, $score);
    }

    private function determineStatus($criticalCount, $webshellCount, $suspiciousCount)
    {
        if ($webshellCount > 0) return 'infected';
        if ($criticalCount > 0) return 'critical';
        if ($suspiciousCount > 5) return 'warning';
        if ($suspiciousCount > 0) return 'suspicious';
        return 'clean';
    }

    private function generateRecommendations($criticalCount, $webshellCount, $suspiciousCount)
    {
        $recommendations = [];

        if ($webshellCount > 0) {
            $recommendations[] = 'URGENT: Webshells detected! Immediately quarantine and remove infected files.';
            $recommendations[] = 'Change all passwords and review access logs.';
            $recommendations[] = 'Update all software and plugins to latest versions.';
        }

        if ($criticalCount > 0) {
            $recommendations[] = 'Critical vulnerabilities found. Review and fix immediately.';
            $recommendations[] = 'Implement input validation and sanitization.';
        }

        if ($suspiciousCount > 10) {
            $recommendations[] = 'High number of suspicious files detected. Consider code review.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'System appears clean. Continue regular monitoring.';
        }

        return $recommendations;
    }

    public function getStatus()
    {
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

    private function getLastScanInfo()
    {
        $logFile = './logs/last_scan_client.json';

        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            return json_decode($content, true);
        }

        return null;
    }

    public function saveLastScan($scanResult)
    {
        if (!file_exists('./logs')) {
            mkdir('./logs', 0755, true);
        }

        $logFile = './logs/last_scan_client.json';
        if (SecurityClientConfig::ENABLE_LOGGING) {
            // file_put_contents($logFile, json_encode($scanResult, JSON_PRETTY_PRINT));
        }
    }
}

// ==================== API ENDPOINTS ====================
function handleApiRequest()
{
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

        case 'quarantine_file':
            handleQuarantineFileRequest();
            break;

        case 'scan_history':
            handleScanHistoryRequest();
            break;

        case 'get_file_content':
            handleGetFileContentRequest();
            break;

        case 'get_file':
            handleGetFileRequest();
            break;

        case 'save_file':
            handleSaveFileRequest();
            break;

        case 'whitelist_file':
            handleWhitelistFileRequest();
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
    }
}

function handleHealthCheck()
{
    $response = [
        'status' => 'healthy',
        'client' => SecurityClientConfig::CLIENT_NAME,
        'version' => SecurityClientConfig::CLIENT_VERSION,
        'timestamp' => date('Y-m-d H:i:s'),
        'uptime' => time()
    ];

    echo json_encode($response);
}

function handleStatusCheck()
{
    $scanner = new SecurityScanner();
    $status = $scanner->getStatus();

    echo json_encode($status);
}

function handleScanRequest()
{
    $scanner = new SecurityScanner();

    // Lấy options từ request
    $options = json_decode(file_get_contents('php://input'), true) ?: [];

    // Thực hiện scan với priority files
    $result = $scanner->performScan($options);

    // Lưu kết quả
    if ($result['success']) {
        $scanner->saveLastScan($result);

        // Log scan activity
        $logEntry = date('Y-m-d H:i:s') . " - Client scan completed. IP: " . getClientIP() . "\n";
        if (!file_exists('./logs')) {
            mkdir('./logs', 0755, true);
        }
        if (SecurityClientConfig::ENABLE_LOGGING) {
            file_put_contents('./logs/client_scan_' . date('Y-m-d') . '.log', $logEntry, FILE_APPEND);
        }
    }

    echo json_encode($result);
}

function handleInfoRequest()
{
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

function handleDeleteFileRequest()
{
    // Debug logging
    if (!file_exists('./logs')) {
        mkdir('./logs', 0755, true);
    }

    // Chỉ cho phép POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Parse JSON data từ php://input
    $input = file_get_contents('php://input');
    $data = null;

    // Try to parse as JSON first
    if (!empty($input)) {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = null;
        }
    }

    // Fallback to $_POST if JSON decode fails
    if (!$data && !empty($_POST)) {
        $data = $_POST;
    }

    // Final fallback - check if it's form data
    if (!$data) {
        parse_str($input, $data);
    }

    $filePath = $data['file_path'] ?? '';

    // More debug info
    $debugInfo = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => getallheaders(),
        'post_data' => $_POST,
        'php_input' => $input,
        'parsed_data' => $data,
        'file_path' => $filePath,
        'json_error' => json_last_error_msg()
    ];

    if (SecurityClientConfig::ENABLE_LOGGING) {
        file_put_contents('./logs/delete_requests.log', json_encode($debugInfo) . "\n", FILE_APPEND);
    }

    if (empty($filePath)) {
        http_response_code(400);
        echo json_encode(['error' => 'File path required', 'debug' => $debugInfo]);
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
            $result = [
                'success' => true,
                'message' => 'File deleted successfully',
                'file_path' => $filePath,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Log successful deletion
            if (SecurityClientConfig::ENABLE_LOGGING) {
                // file_put_contents('./logs/delete_success.log', json_encode($result) . "\n", FILE_APPEND);
            }
            echo json_encode($result);
        } else {
            $result = [
                'success' => false,
                'error' => 'Failed to delete file',
                'file_path' => $filePath
            ];

            // Log failure
            if (SecurityClientConfig::ENABLE_LOGGING) {
                // file_put_contents('./logs/delete_failure.log', json_encode($result) . "\n", FILE_APPEND);
            }
            header('Content-Type: application/json');

            echo json_encode($result);
        }
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => 'Error deleting file: ' . $e->getMessage(),
            'file_path' => $filePath
        ];

        // Log exception
        if (SecurityClientConfig::ENABLE_LOGGING) {
            // file_put_contents('./logs/delete_exception.log', json_encode($result) . "\n", FILE_APPEND);
        }
        header('Content-Type: application/json');

        echo json_encode($result);
    }
}

function handleQuarantineFileRequest()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Parse JSON data từ php://input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Fallback to $_POST if JSON decode fails
    if (!$data) {
        $data = $_POST;
    }

    $filePath = $data['file_path'] ?? '';

    if (empty($filePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'File path is required'
        ]);
        exit;
    }

    // Tạo thư mục quarantine nếu chưa có
    $quarantineDir = './quarantine';
    if (!file_exists($quarantineDir)) {
        mkdir($quarantineDir, 0755, true);
    }

    // Tạo tên file quarantine với timestamp
    $fileName = basename($filePath);
    $quarantineFile = $quarantineDir . '/' . date('Y-m-d_H-i-s') . '_' . $fileName;

    try {
        if (file_exists($filePath)) {
            if (rename($filePath, $quarantineFile)) {
                // Ghi log quarantine
                $logEntry = date('Y-m-d H:i:s') . " - Quarantined: $filePath -> $quarantineFile\n";
                if (SecurityClientConfig::ENABLE_LOGGING) {
                    file_put_contents('./logs/quarantine.log', $logEntry, FILE_APPEND | LOCK_EX);
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'File quarantined successfully',
                    'original_path' => $filePath,
                    'quarantine_path' => $quarantineFile,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to quarantine file'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'File not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error quarantining file: ' . $e->getMessage()
        ]);
    }
}

function handleScanHistoryRequest()
{
    $limit = $_GET['limit'] ?? 10;
    $historyFile = './logs/scan_history.json';

    if (!file_exists($historyFile)) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }

    $history = json_decode(file_get_contents($historyFile), true) ?: [];

    // Sắp xếp theo thời gian mới nhất
    usort($history, function ($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    // Giới hạn số lượng
    $history = array_slice($history, 0, $limit);

    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
}

function handleGetFileContentRequest()
{
    $filePath = $_GET['file_path'] ?? '';
    $lines = $_GET['lines'] ?? 50; // Số dòng hiển thị

    if (empty($filePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'File path is required'
        ]);
        exit;
    }

    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'File not found or not readable'
        ]);
        exit;
    }

    try {
        $content = file_get_contents($filePath);
        $fileLines = explode("\n", $content);

        // Giới hạn số dòng để tránh quá tải
        if (count($fileLines) > $lines) {
            $fileLines = array_slice($fileLines, 0, $lines);
            $truncated = true;
        } else {
            $truncated = false;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'content' => implode("\n", $fileLines),
                'lines' => $fileLines,
                'total_lines' => count(explode("\n", $content)),
                'truncated' => $truncated,
                'file_size' => filesize($filePath),
                'last_modified' => date('Y-m-d H:i:s', filemtime($filePath))
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error reading file: ' . $e->getMessage()
        ]);
    }
}

function handleWhitelistFileRequest()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $filePath = $_POST['file_path'] ?? '';
    $reason = $_POST['reason'] ?? 'Manual whitelist';

    if (empty($filePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'File path is required'
        ]);
        exit;
    }

    $whitelistFile = './config/whitelist.json';

    // Tạo thư mục config nếu chưa có
    if (!file_exists('./config')) {
        mkdir('./config', 0755, true);
    }

    // Load whitelist hiện tại
    $whitelist = [];
    if (file_exists($whitelistFile)) {
        $whitelist = json_decode(file_get_contents($whitelistFile), true) ?: [];
    }

    // Thêm file vào whitelist
    $whitelist[$filePath] = [
        'reason' => $reason,
        'added_at' => date('Y-m-d H:i:s'),
        'md5' => file_exists($filePath) ? md5_file($filePath) : null
    ];

    // Lưu whitelist
    if (file_put_contents($whitelistFile, json_encode($whitelist, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => 'File added to whitelist successfully',
            'file_path' => $filePath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save whitelist'
        ]);
    }
}

// ==================== MAIN EXECUTION ====================
// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Access-Control-Max-Age: 86400');

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

function handleGetFileRequest()
{
    // Validate API request first
    validateApiRequest();

    // Chỉ cho phép POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed - Got: ' . $_SERVER['REQUEST_METHOD']]);
        exit;
    }

    // Parse JSON data từ php://input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['file_path'])) {
        echo json_encode(['success' => false, 'error' => 'Missing file_path']);
        exit;
    }

    $filePath = $data['file_path'];

    // Validate file path
    if (!file_exists($filePath) || !is_readable($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File not found or not readable']);
        exit;
    }

    try {
        $content = file_get_contents($filePath);
        $fileSize = filesize($filePath);

        echo json_encode([
            'success' => true,
            'content' => $content,
            'size' => $fileSize,
            'file_path' => $filePath
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleSaveFileRequest()
{
    // Validate API request first
    validateApiRequest();

    // Chỉ cho phép POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Parse JSON data từ php://input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['file_path']) || !isset($data['content'])) {
        echo json_encode(['success' => false, 'error' => 'Missing file_path or content']);
        exit;
    }

    $filePath = $data['file_path'];
    $content = $data['content'];

    // Validate file path
    if (!file_exists($filePath) || !is_writable($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File not found or not writable']);
        exit;
    }

    try {
        // Write new content directly (no backup needed)
        $bytesWritten = file_put_contents($filePath, $content);

        if ($bytesWritten === false) {
            throw new Exception('Failed to write file');
        }

        echo json_encode([
            'success' => true,
            'message' => 'File saved successfully',
            'file_path' => $filePath,
            'size' => $bytesWritten
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
