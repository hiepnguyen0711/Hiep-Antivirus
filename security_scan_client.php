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
            
            // Lấy priority files từ options
            $priorityFiles = $options['priority_files'] ?? [];

            // Enhanced Critical Threat Patterns
            $criticalPatterns = [
                // Code Execution
                'eval(' => 'Direct code execution vulnerability',
                'assert(' => 'Code assertion execution',
                'system(' => 'Direct system command execution',
                'exec(' => 'System command execution',
                'shell_exec(' => 'Shell command execution',
                'passthru(' => 'Command output bypass execution',
                'popen(' => 'Process pipe execution',
                'proc_open(' => 'Process execution with pipes',

                // Encoding/Obfuscation
                'base64_decode(' => 'Base64 encoded payload execution',
                'gzinflate(' => 'Compressed malware payload',
                'gzuncompress(' => 'Compressed data decompression',
                'str_rot13(' => 'ROT13 string obfuscation',
                'convert_uudecode(' => 'UU encoding obfuscation',
                'hex2bin(' => 'Hexadecimal to binary conversion',

                // File Operations (Critical)
                'file_put_contents(' => 'Potentially malicious file writing',
                'fwrite(' => 'Direct file writing operation',
                'fputs(' => 'File output operation',

                // Network Operations
                'curl_exec(' => 'HTTP request execution',
                'fsockopen(' => 'Socket connection establishment',
                'socket_create(' => 'Raw socket creation',

                // Webshell Indicators
                '$_GET[' => 'GET parameter processing (webshell indicator)',
                '$_POST[' => 'POST parameter processing (webshell indicator)',
                '$_REQUEST[' => 'REQUEST parameter processing (webshell indicator)',
                '$_COOKIE[' => 'Cookie parameter processing',

                // Common Webshell Functions
                'move_uploaded_file(' => 'File upload processing',
                'copy(' => 'File copying operation',
                'rename(' => 'File renaming operation',
                'unlink(' => 'File deletion operation',
                'rmdir(' => 'Directory removal operation',
                'mkdir(' => 'Directory creation operation',

                // Database Operations (Suspicious)
                'mysql_query(' => 'Direct MySQL query execution',
                'mysqli_query(' => 'MySQLi query execution',
                'pg_query(' => 'PostgreSQL query execution'
            ];

            $suspiciousPatterns = [
                // File Operations
                'file_get_contents(' => 'File reading operation',
                'fopen(' => 'File handle creation',
                'fread(' => 'File reading operation',
                'readfile(' => 'Direct file output',
                'include(' => 'File inclusion',
                'require(' => 'File requirement',
                'include_once(' => 'Single file inclusion',
                'require_once(' => 'Single file requirement',

                // String Operations
                'preg_replace(' => 'Regular expression replacement',
                'str_replace(' => 'String replacement operation',
                'substr(' => 'String substring operation',
                'chr(' => 'Character generation',
                'ord(' => 'Character code conversion',

                // Array Operations
                'array_map(' => 'Array mapping function',
                'array_filter(' => 'Array filtering function',
                'call_user_func(' => 'Dynamic function calling',
                'call_user_func_array(' => 'Dynamic function calling with arrays',

                // Variable Operations
                'extract(' => 'Variable extraction from array',
                'parse_str(' => 'String parsing to variables',
                'variable_get(' => 'Variable retrieval',

                // HTTP Operations
                'header(' => 'HTTP header manipulation',
                'setcookie(' => 'Cookie setting operation',
                'session_start(' => 'Session initialization'
            ];
            
            // Webshell Detection Patterns
            $webshellPatterns = $this->getWebshellPatterns();

            // Bắt đầu quét - ưu tiên priority files trước
            $this->scanDirectoryWithPriority('./', $criticalPatterns, $suspiciousPatterns, $webshellPatterns, $priorityFiles);

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
    
    private function scanDirectory($dir, $criticalPatterns, $suspiciousPatterns, $webshellPatterns = []) {
        if (!is_dir($dir) || $this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
            return;
        }

        $excludeDirs = ['.git', '.svn', 'node_modules', 'vendor', 'cache', 'logs', 'tmp', 'temp'];
        $excludeFiles = ['security_scan_client.php', 'security_scan_server.php']; // Bỏ qua chính file này
        
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
                    
                    // Quét file PHP và các file nghi ngờ
                    if ($extension === 'php' || strpos($fileName, '.php.') !== false ||
                        $this->isSuspiciousFileExtension($fileName)) {
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
    
    private function scanDirectoryWithPriority($dir, $criticalPatterns, $suspiciousPatterns, $webshellPatterns = [], $priorityFiles = []) {
        if (!is_dir($dir) || $this->scannedFiles >= SecurityClientConfig::MAX_SCAN_FILES) {
            return;
        }

        $excludeDirs = ['.git', '.svn', 'node_modules', 'vendor', 'cache', 'logs', 'tmp', 'temp'];
        $excludeFiles = ['security_scan_client.php', 'security_scan_server.php']; // Bỏ qua chính file này
        
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
                    
                    // Bỏ qua file không cần thiết
                    if (in_array($fileName, $excludeFiles)) {
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
                    if ($extension === 'php' || strpos($fileName, '.php.') !== false ||
                        $this->isSuspiciousFileExtension($fileName)) {
                        
                        // Check if file matches priority patterns
                        $isPriority = false;
                        foreach ($priorityFiles as $pattern) {
                            if ($this->matchesPattern($filePath, $pattern)) {
                                $isPriority = true;
                                break;
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
    
    private function matchesPattern($filePath, $pattern) {
        // Convert wildcard pattern to regex
        $pattern = str_replace(['*', '?'], ['.*', '.'], $pattern);
        $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/i';
        
        // Check if filename matches pattern
        $fileName = basename($filePath);
        return preg_match($pattern, $fileName) || preg_match($pattern, $filePath);
    }
    
    private function scanFile($filePath, $criticalPatterns, $suspiciousPatterns, $webshellPatterns = [], $isPriority = false) {
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
                $lineNumber = $this->findPatternLineNumber($content, $pattern);
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

    private function categorizeFile($filePath, $issues) {
        $fileName = basename($filePath);
        
        // Kiểm tra nếu là file manager
        if (strpos($fileName, 'filemanager') !== false || 
            strpos($fileName, 'file_manager') !== false ||
            strpos($fileName, 'upload') !== false) {
            return 'filemanager';
        }
        
        // Kiểm tra nếu là suspicious file extension
        if (strpos($fileName, '.php.') !== false || 
            $this->isSuspiciousFileExtension($fileName)) {
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

    private function getWebshellPatterns() {
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

    private function isSuspiciousFileExtension($fileName) {
        $suspiciousExtensions = [
            '.php.txt', '.php.bak', '.php.old', '.php.tmp', '.php.backup',
            '.phtml', '.php3', '.php4', '.php5', '.phps', '.pht', '.phar'
        ];

        foreach ($suspiciousExtensions as $ext) {
            if (stripos($fileName, $ext) !== false) {
                return true;
            }
        }

        return false;
    }

    private function findPatternLineNumber($content, $pattern) {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, $pattern) !== false) {
                return $lineNum + 1;
            }
        }
        return 0;
    }

    private function getContextAroundPattern($content, $pattern) {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, $pattern) !== false) {
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

    private function calculateRiskScore($criticalCount, $suspiciousCount, $webshellCount) {
        $score = 0;
        $score += $criticalCount * 10;
        $score += $webshellCount * 20;
        $score += $suspiciousCount * 2;

        return min(100, $score);
    }

    private function determineStatus($criticalCount, $webshellCount, $suspiciousCount) {
        if ($webshellCount > 0) return 'infected';
        if ($criticalCount > 0) return 'critical';
        if ($suspiciousCount > 5) return 'warning';
        if ($suspiciousCount > 0) return 'suspicious';
        return 'clean';
    }

    private function generateRecommendations($criticalCount, $webshellCount, $suspiciousCount) {
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
    
    file_put_contents('./logs/delete_requests.log', json_encode($debugInfo) . "\n", FILE_APPEND);
    
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
            file_put_contents('./logs/delete_success.log', json_encode($result) . "\n", FILE_APPEND);
            
            echo json_encode($result);
        } else {
            $result = [
                'success' => false,
                'error' => 'Failed to delete file',
                'file_path' => $filePath
            ];
            
            // Log failure
            file_put_contents('./logs/delete_failure.log', json_encode($result) . "\n", FILE_APPEND);
            
            echo json_encode($result);
        }
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => 'Error deleting file: ' . $e->getMessage(),
            'file_path' => $filePath
        ];
        
        // Log exception
        file_put_contents('./logs/delete_exception.log', json_encode($result) . "\n", FILE_APPEND);
        
        echo json_encode($result);
    }
}

function handleQuarantineFileRequest() {
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
                file_put_contents('./logs/quarantine.log', $logEntry, FILE_APPEND | LOCK_EX);

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

function handleScanHistoryRequest() {
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
    usort($history, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    // Giới hạn số lượng
    $history = array_slice($history, 0, $limit);

    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
}

function handleGetFileContentRequest() {
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

function handleWhitelistFileRequest() {
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

function handleGetFileRequest() {
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

function handleSaveFileRequest() {
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
        // Backup original file
        $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
        if (file_exists($filePath)) {
            copy($filePath, $backupPath);
        }
        
        // Write new content
        $bytesWritten = file_put_contents($filePath, $content);
        
        if ($bytesWritten === false) {
            throw new Exception('Failed to write file');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'File saved successfully',
            'file_path' => $filePath,
            'size' => $bytesWritten,
            'backup_path' => $backupPath
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?> 