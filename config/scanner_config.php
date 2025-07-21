<?php
/**
 * Security Scanner Configuration
 * Cấu hình tập trung cho hệ thống quét malware
 * Author: Hiệp Nguyễn
 * Version: 2.0
 */

// ==================== CLIENT CONFIGURATION ====================
class SecurityScannerConfig {
    // API Security
    const DEFAULT_API_KEY = 'hiep-security-client-2025-change-this-key';
    const CLIENT_VERSION = '2.0';
    
    // Scan Limits
    const MAX_SCAN_FILES = 15000;
    const MAX_SCAN_TIME = 600; // 10 phút
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
        '.php.txt', '.php.bak', '.php.old', '.php.tmp',
        '.php.backup', '.php.orig', '.php.save',
        '.suspected', '.virus', '.malware'
    );
}

// ==================== SERVER CONFIGURATION ====================
class SecurityServerConfig {
    // Email Settings
    const ADMIN_EMAIL = 'nguyenvanhiep0711@gmail.com';
    const EMAIL_FROM = 'security-server@yourdomain.com';
    const EMAIL_FROM_NAME = 'Hiệp Security Server';
    
    // SMTP Settings
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'nguyenvanhiep0711@gmail.com';
    const SMTP_PASSWORD = 'flnd neoz lhqw yzmd';
    const SMTP_SECURE = 'tls';
    
    // Server Settings
    const SERVER_NAME = 'Hiệp Security Center';
    const SERVER_VERSION = '2.0';
    const MAX_CONCURRENT_SCANS = 15;
    const SCAN_TIMEOUT = 600; // 10 phút
    
    // Dashboard Settings
    const AUTO_REFRESH_INTERVAL = 30; // seconds
    const MAX_SCAN_HISTORY = 100;
    const ALERT_THRESHOLD_CRITICAL = 1;
    const ALERT_THRESHOLD_SUSPICIOUS = 10;
}

// ==================== MALWARE PATTERNS ====================
class MalwarePatterns {
    
    public static function getCriticalPatterns() {
        return array(
            // Code Execution - Critical
            'eval(' => 'Direct code execution vulnerability',
            'assert(' => 'Code assertion execution',
            'system(' => 'Direct system command execution',
            'exec(' => 'System command execution',
            'shell_exec(' => 'Shell command execution',
            'passthru(' => 'Command output bypass execution',
            'popen(' => 'Process pipe execution',
            'proc_open(' => 'Process execution with pipes',
            
            // Encoding/Obfuscation - Critical
            'base64_decode(' => 'Base64 encoded payload execution',
            'gzinflate(' => 'Compressed malware payload',
            'gzuncompress(' => 'Compressed data decompression',
            'str_rot13(' => 'ROT13 string obfuscation',
            'convert_uudecode(' => 'UU encoding obfuscation',
            'hex2bin(' => 'Hexadecimal to binary conversion',
            
            // File Operations - Critical
            'file_put_contents(' => 'Potentially malicious file writing',
            'fwrite(' => 'Direct file writing operation',
            'fputs(' => 'File output operation',
            
            // Network Operations - Critical
            'fsockopen(' => 'Socket connection establishment',
            'socket_create(' => 'Raw socket creation',
            'curl_exec(' => 'HTTP request execution',
            
            // Webshell Indicators - Critical
            'move_uploaded_file(' => 'File upload processing',
            'copy(' => 'File copying operation',
            'rename(' => 'File renaming operation',
            'unlink(' => 'File deletion operation',
            'rmdir(' => 'Directory removal operation',
            'mkdir(' => 'Directory creation operation',
            
            // Database Operations - Critical
            'mysql_query(' => 'Direct MySQL query execution',
            'mysqli_query(' => 'MySQLi query execution',
            'pg_query(' => 'PostgreSQL query execution'
        );
    }
    
    public static function getSuspiciousPatterns() {
        return array(
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
            'session_start(' => 'Session initialization',
            
            // User Input
            '$_GET[' => 'GET parameter processing',
            '$_POST[' => 'POST parameter processing',
            '$_REQUEST[' => 'REQUEST parameter processing',
            '$_COOKIE[' => 'Cookie parameter processing'
        );
    }
    
    public static function getWebshellPatterns() {
        return array(
            // Common webshell signatures
            '/\$_(GET|POST|REQUEST)\s*\[\s*[\'\"][^\'\"]*[\'\"]\s*\]\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Dynamic function execution via HTTP parameters',
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
            '/\\\x[0-9a-f]{2}/i' => 'Hexadecimal character encoding',
            
            // PHP webshell specific
            '/assert\s*\(\s*\$_(GET|POST|REQUEST)/i' => 'Assert function with user input',
            '/preg_replace.*/e.*\$_(GET|POST|REQUEST)/i' => 'Preg_replace with eval modifier',
            '/create_function\s*\(.*\$_(GET|POST|REQUEST)/i' => 'Dynamic function creation with user input'
        );
    }
    
    public static function getKnownMalwareSignatures() {
        return array(
            // Known malware MD5 hashes
            'md5_hashes' => array(
                '5d41402abc4b2a76b9719d911017c592' => 'Known malware sample',
                // Thêm các hash MD5 của malware đã biết
            ),
            
            // Known malware file names
            'file_names' => array(
                'c99.php', 'r57.php', 'wso.php', 'b374k.php',
                'adminer.php', 'shell.php', 'backdoor.php',
                'webshell.php', 'cmd.php', 'bypass.php'
            ),
            
            // Suspicious file patterns
            'file_patterns' => array(
                '/^[a-f0-9]{32}\.php$/', // MD5 named files
                '/^[0-9]+\.php$/', // Numeric named files
                '/^\..*\.php$/', // Hidden PHP files
                '/^[a-zA-Z]{1,3}\.php$/' // Very short named files
            )
        );
    }
}

// ==================== UTILITY FUNCTIONS ====================
class ScannerUtils {
    
    public static function isWhitelisted($filePath) {
        $whitelistFile = './config/whitelist.json';
        if (!file_exists($whitelistFile)) {
            return false;
        }
        
        $whitelist = json_decode(file_get_contents($whitelistFile), true);
        if (!$whitelist) $whitelist = array();
        return isset($whitelist[$filePath]);
    }
    
    public static function logSecurityEvent($message, $level = 'INFO') {
        $logFile = './logs/security_events_' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . " [$level] $message\n";
        
        if (!file_exists('./logs')) {
            mkdir('./logs', 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function formatFileSize($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public static function getClientIP() {
        $ipKeys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
}
