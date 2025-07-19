<?php
/**
 * Security Patterns API - Blacklist & Whitelist
 * URL: hiepcodeweb.com/api/security_patterns.php
 * 
 * Cung cấp danh sách blacklist và whitelist patterns 
 * để ưu tiên quét bảo mật cho nhiều website
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get action parameter
$action = $_GET['action'] ?? 'get_patterns';

switch ($action) {
    case 'get_patterns':
        echo json_encode(getSecurityPatterns());
        break;
    
    case 'get_blacklist':
        echo json_encode(getBlacklistPatterns());
        break;
        
    case 'get_whitelist':
        echo json_encode(getWhitelistPatterns());
        break;
        
    case 'update_patterns':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(updatePatterns());
        } else {
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Lấy tất cả patterns (blacklist + whitelist)
 */
function getSecurityPatterns() {
    return [
        'status' => 'success',
        'last_updated' => date('Y-m-d H:i:s'),
        'version' => '1.2.0',
        'blacklist' => getBlacklistPatterns()['patterns'],
        'whitelist' => getWhitelistPatterns()['patterns']
    ];
}

/**
 * Danh sách Blacklist - Files/Patterns cần ưu tiên quét
 */
function getBlacklistPatterns() {
    return [
        'status' => 'success',
        'type' => 'blacklist',
        'description' => 'High priority files/patterns for security scanning',
        'patterns' => [
            // File Extensions nguy hiểm
            'file_extensions' => [
                '*.php.suspected',
                '*.php.bak',
                '*.php.old',
                '*.php.tmp',
                '*.phtml',
                '*.php3',
                '*.php4',
                '*.php5',
                '*.php7',
                '*.inc',
                '*.txt.php',
                '*.gif.php',
                '*.jpg.php',
                '*.png.php'
            ],
            
            // File Names nguy hiểm
            'file_names' => [
                'shell.php',
                'backdoor.php',
                'webshell.php',
                'c99.php',
                'r57.php',
                'wso.php',
                'b374k.php',
                'adminer.php',
                'phpinfo.php',
                'test.php',
                'info.php',
                'upload.php',
                'uploader.php',
                'file_upload.php',
                'cmd.php',
                'eval.php',
                'execute.php',
                'bypass.php',
                'hack.php',
                'exploit.php',
                'malware.php',
                'virus.php',
                'trojan.php',
                'keylogger.php',
                'rootkit.php'
            ],
            
            // Directory Patterns nguy hiểm
            'directory_patterns' => [
                '*/temp/*',
                '*/tmp/*',
                '*/cache/temp/*',
                '*/uploads/temp/*',
                '*/.git/*',
                '*/.svn/*',
                '*/backup/*',
                '*/backups/*',
                '*/bak/*',
                '*/old/*',
                '*/test/*',
                '*/tests/*',
                '*/dev/*',
                '*/development/*',
                '*/staging/*'
            ],
            
            // Content Patterns nguy hiểm cao
            'content_patterns' => [
                // Webshell signatures
                'c99|r57|wso|b374k|shell|backdoor',
                'FilesMan|SafeMode|File Manager',
                'uname -a|/etc/passwd|/etc/shadow',
                'mysql_connect.*eval|mysqli_connect.*eval',
                
                // Code execution
                'eval\\s*\\(.*\\$_',
                'assert\\s*\\(.*\\$_',
                'system\\s*\\(.*\\$_',
                'exec\\s*\\(.*\\$_',
                'shell_exec\\s*\\(.*\\$_',
                'passthru\\s*\\(.*\\$_',
                'popen\\s*\\(.*\\$_',
                'proc_open\\s*\\(.*\\$_',
                
                // File operations nguy hiểm
                'file_put_contents\\s*\\(.*\\$_',
                'fwrite\\s*\\(.*\\$_',
                'fputs\\s*\\(.*\\$_',
                'move_uploaded_file\\s*\\(.*\\$_',
                
                // Database exploitation
                'union.*select|select.*union',
                'drop\\s+table|truncate\\s+table',
                'delete\\s+from.*where.*1=1',
                'update.*set.*where.*1=1',
                
                // Obfuscation patterns
                'base64_decode\\s*\\(',
                'str_rot13\\s*\\(',
                'gzinflate\\s*\\(',
                'gzuncompress\\s*\\(',
                'hex2bin\\s*\\(',
                'chr\\s*\\(.*\\)\\.',
                
                // Network operations
                'curl_exec\\s*\\(',
                'fsockopen\\s*\\(',
                'socket_create\\s*\\(',
                'mail\\s*\\(.*\\$_'
            ],
            
            // Suspicious file paths
            'suspicious_paths' => [
                '/tmp/',
                '/var/tmp/',
                '/dev/shm/',
                '/.config/',
                '/.cache/',
                '/uploads/',
                '/temp/',
                '/cache/',
                '/backup/',
                '/logs/',
                '/includes/',
                '/inc/',
                '/lib/',
                '/libs/',
                '/vendor/',
                '/node_modules/'
            ]
        ]
    ];
}

/**
 * Danh sách Whitelist - Files/Patterns an toàn, không cần quét
 */
function getWhitelistPatterns() {
    return [
        'status' => 'success',
        'type' => 'whitelist',
        'description' => 'Safe files/patterns to skip during security scanning',
        'patterns' => [
            // Framework files an toàn
            'framework_files' => [
                'wp-config.php',
                'wp-config-sample.php',
                'wp-load.php',
                'wp-blog-header.php',
                'wp-settings.php',
                'index.php', // WordPress root
                'xmlrpc.php',
                
                // Laravel
                'artisan',
                'server.php',
                'composer.json',
                'composer.lock',
                'package.json',
                'package-lock.json',
                
                // Joomla
                'configuration.php',
                'web.config.txt',
                
                // Drupal
                'settings.php',
                'default.settings.php'
            ],
            
            // Thư mục framework an toàn
            'safe_directories' => [
                'wp-admin',
                'wp-includes',
                'wp-content/themes',
                'wp-content/plugins',
                'vendor',
                'node_modules',
                'bower_components',
                'assets',
                'public',
                'storage/framework',
                'storage/logs',
                'app/Console',
                'app/Http/Middleware',
                'app/Providers',
                'bootstrap',
                'config',
                'database/migrations',
                'resources/views',
                'resources/assets'
            ],
            
            // File extensions an toàn
            'safe_extensions' => [
                '*.css',
                '*.js',
                '*.json',
                '*.txt',
                '*.md',
                '*.yml',
                '*.yaml',
                '*.xml',
                '*.sql',
                '*.log',
                '*.pdf',
                '*.doc',
                '*.docx',
                '*.xls',
                '*.xlsx',
                '*.ppt',
                '*.pptx',
                '*.zip',
                '*.tar',
                '*.gz',
                '*.rar',
                '*.7z'
            ],
            
            // Known safe content patterns
            'safe_content_patterns' => [
                // WordPress core functions
                'wp_enqueue_script|wp_enqueue_style',
                'wp_head\\(\\)|wp_footer\\(\\)',
                'get_header\\(\\)|get_footer\\(\\)',
                'have_posts\\(\\)|the_post\\(\\)',
                'wp_redirect\\(|wp_safe_redirect\\(',
                
                // Laravel/Framework patterns
                'namespace App\\\\',
                'use Illuminate\\\\',
                'class.*extends.*Controller',
                'Route::|@extends|@include',
                
                // Common safe functions
                'htmlspecialchars\\(|htmlentities\\(',
                'esc_attr\\(|esc_html\\(',
                'sanitize_text_field\\(',
                'wp_kses\\(|wp_kses_post\\('
            ],
            
            // Specific safe files
            'specific_safe_files' => [
                'readme.txt',
                'readme.md',
                'license.txt',
                'license.md',
                'changelog.txt',
                'changelog.md',
                'robots.txt',
                'sitemap.xml',
                'favicon.ico',
                '.htaccess',
                '.gitignore',
                '.gitattributes',
                'humans.txt',
                'manifest.json'
            ]
        ]
    ];
}

/**
 * Cập nhật patterns (cho admin)
 */
function updatePatterns() {
    // Kiểm tra authentication
    $auth_key = $_POST['auth_key'] ?? '';
    if ($auth_key !== 'hiep_security_2025_update_key') {
        return [
            'status' => 'error',
            'message' => 'Unauthorized access'
        ];
    }
    
    $type = $_POST['type'] ?? '';
    $patterns = $_POST['patterns'] ?? '';
    
    if (!in_array($type, ['blacklist', 'whitelist'])) {
        return [
            'status' => 'error',
            'message' => 'Invalid pattern type'
        ];
    }
    
    if (empty($patterns)) {
        return [
            'status' => 'error',
            'message' => 'Patterns data required'
        ];
    }
    
    // Validate JSON
    $decoded = json_decode($patterns, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'status' => 'error',
            'message' => 'Invalid JSON format'
        ];
    }
    
    // Lưu patterns vào file (ví dụ)
    $filename = __DIR__ . "/security_patterns_{$type}.json";
    $saved = file_put_contents($filename, $patterns);
    
    if ($saved !== false) {
        return [
            'status' => 'success',
            'message' => "Patterns updated successfully",
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to save patterns'
        ];
    }
}

/**
 * Lấy thống kê API
 */
function getApiStats() {
    return [
        'status' => 'success',
        'api_version' => '1.2.0',
        'total_blacklist_patterns' => countPatterns('blacklist'),
        'total_whitelist_patterns' => countPatterns('whitelist'),
        'last_updated' => date('Y-m-d H:i:s'),
        'endpoints' => [
            'get_patterns' => '/api/security_patterns.php?action=get_patterns',
            'get_blacklist' => '/api/security_patterns.php?action=get_blacklist',
            'get_whitelist' => '/api/security_patterns.php?action=get_whitelist',
            'update_patterns' => '/api/security_patterns.php?action=update_patterns'
        ]
    ];
}

/**
 * Đếm số lượng patterns
 */
function countPatterns($type) {
    if ($type === 'blacklist') {
        $patterns = getBlacklistPatterns()['patterns'];
    } else {
        $patterns = getWhitelistPatterns()['patterns'];
    }
    
    $count = 0;
    foreach ($patterns as $category) {
        if (is_array($category)) {
            $count += count($category);
        }
    }
    
    return $count;
}

?> 