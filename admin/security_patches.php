<?php
/**
 * ==========================================
 * HIEP SECURITY - CMS ADMIN SECURITY PATCHES
 * ==========================================
 * File: security_patches.php
 * Tác giả: Hiep Security System
 * Ngày: 2025-01-03
 * Mục đích: Khắc phục các lỗ hỏng bảo mật trong CMS Admin
 * ==========================================
 */

// Bắt đầu session nếu chưa có
if (session_id() == '') {
    session_start();
}

class HiepSecurityPatches {
    
    private $config;
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/logs/security_patches.log';
        $this->ensureLogDirectory();
    }
    
    /**
     * Tạo thư mục log nếu chưa có
     */
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Ghi log bảo mật
     */
    private function logSecurity($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = "[{$timestamp}] [{$level}] IP: {$ip} | {$message} | User-Agent: {$userAgent}\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Kiểm tra CSRF Token
     */
    public function validateCSRFToken($token = null) {
        if (!isset($_SESSION['csrf_token'])) {
            $this->generateCSRFToken();
            return false;
        }
        
        $token = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            $this->logSecurity('CSRF token validation failed', 'WARNING');
            return false;
        }
        
        return true;
    }
    
    /**
     * Tạo CSRF Token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Lấy CSRF Token HTML input
     */
    public function getCSRFTokenInput() {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->logSecurity('File upload error: ' . $file['error'], 'WARNING');
            return ['success' => false, 'error' => 'Upload failed'];
        }
        
        // Kiểm tra kích thước
        if ($file['size'] > $maxSize) {
            $this->logSecurity('File too large: ' . $file['size'] . ' bytes', 'WARNING');
            return ['success' => false, 'error' => 'File too large'];
        }
        
        // Kiểm tra loại file
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            $this->logSecurity('Invalid file type: ' . $extension, 'WARNING');
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Kiểm tra MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (isset($allowedMimes[$extension]) && $mimeType !== $allowedMimes[$extension]) {
            $this->logSecurity('MIME type mismatch: ' . $mimeType . ' for extension: ' . $extension, 'CRITICAL');
            return ['success' => false, 'error' => 'Invalid file format'];
        }
        
        // Kiểm tra nội dung file có chứa code độc hại
        if ($this->containsMaliciousCode($file['tmp_name'])) {
            $this->logSecurity('Malicious code detected in file: ' . $file['name'], 'CRITICAL');
            return ['success' => false, 'error' => 'Malicious file detected'];
        }
        
        return ['success' => true];
    }
    
    /**
     * Kiểm tra code độc hại trong file
     */
    private function containsMaliciousCode($filePath) {
        $content = file_get_contents($filePath);
        
        // Patterns nguy hiểm
        $maliciousPatterns = [
            '/\<\?php/i',
            '/\<\?=/i',
            '/\<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/base64_decode\s*\(/i',
            '/file_get_contents\s*\(/i',
            '/file_put_contents\s*\(/i',
            '/fopen\s*\(/i',
            '/fwrite\s*\(/i',
            '/curl_exec\s*\(/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize input
     */
    public function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'int':
                return (int) $input;
            case 'float':
                return (float) $input;
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            default:
                return trim(strip_tags($input));
        }
    }
    
    /**
     * Kiểm tra quyền truy cập
     */
    public function checkAccess($requiredLevel = 1) {
        if (!isset($_SESSION['id_user']) || !isset($_SESSION['user_hash'])) {
            $this->logSecurity('Unauthorized access attempt', 'WARNING');
            header('Location: login.php');
            exit;
        }
        
        $userLevel = $_SESSION['quyen'] ?? 0;
        if ($userLevel < $requiredLevel) {
            $this->logSecurity('Insufficient privileges: required ' . $requiredLevel . ', has ' . $userLevel, 'WARNING');
            return false;
        }
        
        return true;
    }
    
    /**
     * Rate limiting
     */
    public function checkRateLimit($action, $limit = 10, $timeWindow = 60) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $action . '_' . $ip;
        
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        
        // Xóa các entry cũ
        foreach ($_SESSION['rate_limit'] as $k => $data) {
            if ($now - $data['time'] > $timeWindow) {
                unset($_SESSION['rate_limit'][$k]);
            }
        }
        
        // Kiểm tra rate limit
        $count = 0;
        foreach ($_SESSION['rate_limit'] as $k => $data) {
            if (strpos($k, $key) === 0) {
                $count++;
            }
        }
        
        if ($count >= $limit) {
            $this->logSecurity('Rate limit exceeded for action: ' . $action, 'WARNING');
            return false;
        }
        
        // Thêm request hiện tại
        $_SESSION['rate_limit'][$key . '_' . $now] = ['time' => $now];
        
        return true;
    }
    
    /**
     * Tạo secure filename
     */
    public function generateSecureFilename($originalName) {
        $pathInfo = pathinfo($originalName);
        $extension = isset($pathInfo['extension']) ? '.' . strtolower($pathInfo['extension']) : '';
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $pathInfo['filename']);
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        
        return $basename . '_' . $timestamp . '_' . $random . $extension;
    }
}

// Khởi tạo security patches
$hiepSecurity = new HiepSecurityPatches();

// Auto-include trong các file cần bảo mật
if (!defined('HIEP_SECURITY_LOADED')) {
    define('HIEP_SECURITY_LOADED', true);
    
    // Kiểm tra session cho tất cả requests
    if (!isset($_SESSION['id_user']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}
?>
