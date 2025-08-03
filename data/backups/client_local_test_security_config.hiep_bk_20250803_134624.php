<?php
/**
 * ==========================================
 * HIEP SECURITY - FILEMANAGER SECURITY CONFIG
 * ==========================================
 * File: security_config.php
 * Mục đích: Cấu hình bảo mật nâng cao cho File Manager
 * ==========================================
 */

// Include security patches
require_once '../security_patches.php';

// Kiểm tra session và quyền truy cập
if (!isset($_SESSION['id_user'])) {
    http_response_code(403);
    die('Access Denied - Hiep Security Protection');
}

// Kiểm tra referer để ngăn chặn direct access
$allowedReferers = [
    $_SERVER['HTTP_HOST'] . '/admin/',
    'localhost/admin/',
    '127.0.0.1/admin/'
];

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$isValidReferer = false;

foreach ($allowedReferers as $allowed) {
    if (strpos($referer, $allowed) !== false) {
        $isValidReferer = true;
        break;
    }
}

if (!$isValidReferer && !empty($referer)) {
    http_response_code(403);
    die('Invalid Referer - Hiep Security Protection');
}

// Cấu hình bảo mật cho File Manager
class FileManagerSecurity {
    
    // Các loại file được phép upload
    public static $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',  // Images
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',  // Documents
        'txt', 'csv',  // Text files
        'zip', 'rar', '7z',  // Archives
        'mp3', 'wav', 'ogg',  // Audio
        'mp4', 'avi', 'mov', 'wmv'  // Video
    ];
    
    // Các loại file bị cấm tuyệt đối
    public static $forbiddenExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phps',
        'asp', 'aspx', 'jsp', 'jspx',
        'pl', 'py', 'rb', 'sh', 'bat', 'cmd',
        'exe', 'com', 'scr', 'msi',
        'js', 'vbs', 'jar', 'war',
        'htaccess', 'htpasswd'
    ];
    
    // Kích thước file tối đa (20MB)
    public static $maxFileSize = 20971520;
    
    // Thư mục upload được phép
    public static $allowedUploadDirs = [
        'source/images/',
        'source/documents/',
        'source/media/'
    ];
    
    /**
     * Validate file upload
     */
    public static function validateUpload($filename, $filesize, $filepath) {
        global $hiepSecurity;
        
        // Kiểm tra kích thước
        if ($filesize > self::$maxFileSize) {
            $hiepSecurity->logSecurity('File too large: ' . $filesize . ' bytes for file: ' . $filename, 'WARNING');
            return false;
        }
        
        // Kiểm tra extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, self::$forbiddenExtensions)) {
            $hiepSecurity->logSecurity('Forbidden file extension: ' . $extension . ' for file: ' . $filename, 'CRITICAL');
            return false;
        }
        
        if (!in_array($extension, self::$allowedExtensions)) {
            $hiepSecurity->logSecurity('Disallowed file extension: ' . $extension . ' for file: ' . $filename, 'WARNING');
            return false;
        }
        
        // Kiểm tra double extension
        if (preg_match('/\.(php|phtml|php3|php4|php5)\./i', $filename)) {
            $hiepSecurity->logSecurity('Double extension detected: ' . $filename, 'CRITICAL');
            return false;
        }
        
        // Kiểm tra null byte
        if (strpos($filename, "\0") !== false) {
            $hiepSecurity->logSecurity('Null byte detected in filename: ' . $filename, 'CRITICAL');
            return false;
        }
        
        // Kiểm tra path traversal
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            $hiepSecurity->logSecurity('Path traversal attempt: ' . $filename, 'CRITICAL');
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        // Loại bỏ các ký tự nguy hiểm
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Loại bỏ multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Loại bỏ dots ở đầu và cuối
        $filename = trim($filename, '.');
        
        return $filename;
    }
    
    /**
     * Check if directory is allowed for upload
     */
    public static function isAllowedDirectory($dir) {
        $dir = rtrim($dir, '/') . '/';
        
        foreach (self::$allowedUploadDirs as $allowedDir) {
            if (strpos($dir, $allowedDir) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate secure upload path
     */
    public static function generateSecurePath($originalPath, $filename) {
        global $hiepSecurity;
        
        // Sanitize path
        $path = str_replace(['../', '..\\', './'], '', $originalPath);
        $path = rtrim($path, '/') . '/';
        
        // Kiểm tra path có được phép không
        if (!self::isAllowedDirectory($path)) {
            $path = 'source/images/'; // Default safe directory
        }
        
        // Generate secure filename
        $secureFilename = $hiepSecurity->generateSecureFilename($filename);
        
        return $path . $secureFilename;
    }
}

// Hook vào File Manager để apply security
if (isset($_POST) && !empty($_POST)) {
    // Kiểm tra CSRF token cho POST requests
    if (!$hiepSecurity->validateCSRFToken()) {
        http_response_code(403);
        die('CSRF Token Invalid - Hiep Security Protection');
    }
    
    // Rate limiting cho upload
    if (!$hiepSecurity->checkRateLimit('filemanager_upload', 10, 60)) {
        http_response_code(429);
        die('Rate Limit Exceeded - Hiep Security Protection');
    }
}

// Override File Manager upload function
function hiep_secure_upload_handler($file, $path) {
    if (!FileManagerSecurity::validateUpload($file['name'], $file['size'], $file['tmp_name'])) {
        return false;
    }
    
    $securePath = FileManagerSecurity::generateSecurePath($path, $file['name']);
    $secureFilename = basename($securePath);
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $securePath)) {
        // Log successful upload
        global $hiepSecurity;
        $hiepSecurity->logSecurity('File uploaded successfully: ' . $secureFilename, 'INFO');
        return $securePath;
    }
    
    return false;
}

// Security headers for File Manager
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Disable error reporting in production
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

?>
