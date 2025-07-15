<?php
/**
 * Multi-Website Security Scanner - Enterprise Edition
 * Author: Hiệp Nguyễn
 * Facebook: https://www.facebook.com/G.N.S.L.7/
 * Version: 4.0 Enterprise Multi-Site
 * Date: 2025
 */

// PHP 5.6+ Compatibility
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    die('This scanner requires PHP 5.6 or higher. Current version: ' . PHP_VERSION);
}

// ========== MULTI-WEBSITE SCANNER CONFIGURATION ==========
class MultiWebsiteConfig {
    // Cấu hình hosting paths - CẤU HÌNH CHO HOSTINGER
    const HOSTING_PATHS = array(
        '/domains/',              // Hostinger domains directory
        '/public_html/',          // Hostinger main domain
        '/home/*/domains/',       // Hostinger full path pattern
        '/home/*/public_html/',   // Hostinger full path pattern
        './domains/',             // Relative domains từ thư mục hiện tại
        './public_html/',         // Relative public_html từ thư mục hiện tại
    );
    
    // Patterns để detect website directories - HOSTINGER
    const WEBSITE_PATTERNS = array(
        '*.com',
        '*.net', 
        '*.org',
        '*.info',
        '*.vn',
        '*.edu',
        '*.gov',
        '*.xyz',
        '*.online',
        '*.store',
        '*.tech',
        '*.site',
        'minhtanphat.vn'  // Domain chính từ hình ảnh
    );
    
    // Exclude directories - KHÔNG QUÉT - HOSTINGER OPTIMIZED
    const EXCLUDE_DIRS = array(
        'cache',        // Hostinger cache
        'config',       // Hostinger config
        'local',        // Hostinger local
        'logs',         // Hostinger logs
        'nvm',          // Node Version Manager
        'ssh',          // SSH keys
        'subversion',   // SVN
        'trash',        // Trash bin
        'wp-cli',       // WordPress CLI
        'cgi-bin',
        'tmp',
        'temp',
        'backup',
        'mail',
        'ftp',
        'phpmyadmin',
        'webmail',
        'cpanel',
        'error_logs',
        '_vti_cnf',
        '_vti_pvt',
        '.git',
        '.svn',
        'node_modules',
        '.well-known'
    );
    
    // Email configuration
    const EMAIL_TO = 'nguyenvanhiep0711@gmail.com'; // THAY ĐỔI EMAIL
    const EMAIL_FROM = 'multi-scanner@yourdomain.com';
    const EMAIL_FROM_NAME = 'Multi-Site Security Scanner';
    
    // Limits để tránh timeout
    const MAX_WEBSITES_PER_SCAN = 20;
    const MAX_FILES_PER_WEBSITE = 10000;
    const SCAN_TIMEOUT = 300; // 5 minutes per website
}

// ========== WEBSITE DETECTOR ==========
class WebsiteDetector {
    private $websites = array();
    private $logFile = '';
    
    public function __construct() {
        $this->logFile = dirname(__FILE__) . '/logs/multi_website_scan.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                error_log("Failed to create log directory: $logDir");
            }
        }
    }
    
    public function detectWebsites() {
        $this->logMessage("Starting website detection...");
        
        foreach (MultiWebsiteConfig::HOSTING_PATHS as $basePath) {
            if (is_dir($basePath)) {
                $this->logMessage("Scanning path: $basePath");
                $this->scanDirectory($basePath);
            }
        }
        
        $this->logMessage("Detected " . count($this->websites) . " websites");
        return $this->websites;
    }
    
    private function scanDirectory($path) {
        $handle = opendir($path);
        if (!$handle) return;
        
        while (($item = readdir($handle)) !== false) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = rtrim($path, '/') . '/' . $item;
            
            if (is_dir($fullPath) && !in_array($item, MultiWebsiteConfig::EXCLUDE_DIRS)) {
                // Check if it's a website directory
                if ($this->isWebsiteDirectory($fullPath, $item)) {
                    $this->websites[] = array(
                        'name' => $item,
                        'path' => $fullPath,
                        'domain' => $this->guessDomain($item, $fullPath),
                        'last_scan' => $this->getLastScanTime($fullPath),
                        'status' => 'pending'
                    );
                    $this->logMessage("Found website: $item at $fullPath");
                }
                
                // Hostinger specific: Check for subdirectories in domains/
                if ($item === 'domains' || strpos($path, 'domains') !== false) {
                    $this->scanDirectory($fullPath);
                }
            }
            
            // Hostinger: Check if public_html is a symlink to a domain
            if ($item === 'public_html' && is_link($fullPath)) {
                $target = readlink($fullPath);
                if ($target && is_dir($target)) {
                    $domainName = basename($target);
                    if (!$this->websiteExists($domainName)) {
                        $this->websites[] = array(
                            'name' => $domainName . ' (main)',
                            'path' => $target,
                            'domain' => $domainName,
                            'last_scan' => $this->getLastScanTime($target),
                            'status' => 'pending'
                        );
                        $this->logMessage("Found main website: $domainName at $target");
                    }
                }
            }
        }
        
        closedir($handle);
    }
    
    private function websiteExists($name) {
        foreach ($this->websites as $website) {
            if ($website['name'] === $name || $website['domain'] === $name) {
                return true;
            }
        }
        return false;
    }
    
    private function isWebsiteDirectory($path, $name) {
        // Check for common website indicators
        $indicators = array(
            'index.php',
            'index.html',
            'index.htm',
            'wp-config.php',      // WordPress
            'configuration.php',   // Joomla
            'settings.php',       // Drupal
            'admin/',
            'css/',
            'js/',
            'images/',
            'uploads/'
        );
        
        foreach ($indicators as $indicator) {
            if (file_exists($path . '/' . $indicator)) {
                return true;
            }
        }
        
        // Check domain patterns
        foreach (MultiWebsiteConfig::WEBSITE_PATTERNS as $pattern) {
            if (fnmatch($pattern, $name)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function guessDomain($name, $path) {
        // Try to find domain from directory name
        if (strpos($name, '.') !== false) {
            return $name;
        }
        
        // Try to find from config files
        $configFiles = array(
            'wp-config.php',
            'configuration.php',
            'config.php',
            '.htaccess'
        );
        
        foreach ($configFiles as $file) {
            if (file_exists($path . '/' . $file)) {
                $domain = $this->extractDomainFromFile($path . '/' . $file);
                if ($domain) return $domain;
            }
        }
        
        return $name;
    }
    
    private function extractDomainFromFile($file) {
        $content = file_get_contents($file);
        if (!$content) return null;
        
        // WordPress
        if (preg_match('/define\s*\(\s*[\'"]WP_HOME[\'"].*[\'"]https?:\/\/([^\/\'"]+)/', $content, $matches)) {
            return $matches[1];
        }
        
        // General domain patterns
        if (preg_match('/https?:\/\/([^\/\s\'"]+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function getLastScanTime($path) {
        $scanFile = $path . '/.security_scan_last';
        if (file_exists($scanFile)) {
            return filemtime($scanFile);
        }
        return 0;
    }
    
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function getWebsites() {
        return $this->websites;
    }
}

// ========== MULTI-WEBSITE SCANNER ENGINE ==========
class MultiWebsiteScanner {
    private $detector;
    private $websites = array();
    private $scanResults = array();
    private $logFile = '';
    
    public function __construct() {
        $this->detector = new WebsiteDetector();
        $this->logFile = dirname(__FILE__) . '/logs/multi_website_scan.log';
        
        // Set time and memory limits
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
    }
    
    public function scanAllWebsites() {
        $this->logMessage("=== MULTI-WEBSITE SCAN STARTED ===");
        
        // Detect all websites
        $this->websites = $this->detector->detectWebsites();
        
        if (empty($this->websites)) {
            $this->logMessage("No websites detected!");
            return array();
        }
        
        $totalWebsites = count($this->websites);
        $this->logMessage("Found $totalWebsites websites to scan");
        
        // Limit websites per scan to avoid timeout
        $websitesToScan = array_slice($this->websites, 0, MultiWebsiteConfig::MAX_WEBSITES_PER_SCAN);
        
        foreach ($websitesToScan as $website) {
            $this->scanWebsite($website);
        }
        
        $this->logMessage("=== MULTI-WEBSITE SCAN COMPLETED ===");
        
        // Send consolidated email report
        $this->sendConsolidatedReport();
        
        return $this->scanResults;
    }
    
    private function scanWebsite($website) {
        $this->logMessage("Scanning website: {$website['name']} at {$website['path']}");
        
        $startTime = microtime(true);
        
        // Set timeout for this website
        set_time_limit(MultiWebsiteConfig::SCAN_TIMEOUT);
        
        try {
            // Use the existing security scanner logic
            $scanResult = $this->performSecurityScan($website);
            
            $scanResult['website'] = $website;
            $scanResult['scan_time'] = microtime(true) - $startTime;
            $scanResult['timestamp'] = time();
            
            $this->scanResults[] = $scanResult;
            
            // Update last scan time
            $this->updateLastScanTime($website['path']);
            
            $this->logMessage("Completed scan for {$website['name']} in " . 
                             round($scanResult['scan_time'], 2) . " seconds");
            
        } catch (Exception $e) {
            $this->logMessage("Error scanning {$website['name']}: " . $e->getMessage());
            
            $this->scanResults[] = array(
                'website' => $website,
                'error' => $e->getMessage(),
                'scan_time' => microtime(true) - $startTime,
                'timestamp' => time(),
                'threats' => array(),
                'stats' => array('files_scanned' => 0, 'threats_found' => 0)
            );
        }
    }
    
    private function performSecurityScan($website) {
        $scanPath = $website['path'];
        $threats = array();
        $stats = array(
            'files_scanned' => 0,
            'threats_found' => 0,
            'suspicious_files' => 0,
            'malware_detected' => 0
        );
        
        // Scan files in the website directory
        $this->scanDirectory($scanPath, $threats, $stats);
        
        return array(
            'threats' => $threats,
            'stats' => $stats
        );
    }
    
    private function scanDirectory($path, &$threats, &$stats) {
        $handle = opendir($path);
        if (!$handle) return;
        
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $path . '/' . $file;
            
            if (is_dir($fullPath)) {
                // Skip excluded directories
                if (!in_array($file, MultiWebsiteConfig::EXCLUDE_DIRS)) {
                    $this->scanDirectory($fullPath, $threats, $stats);
                }
            } else {
                // Check file limit
                if ($stats['files_scanned'] >= MultiWebsiteConfig::MAX_FILES_PER_WEBSITE) {
                    break;
                }
                
                $this->scanFile($fullPath, $threats, $stats);
                $stats['files_scanned']++;
            }
        }
        
        closedir($handle);
    }
    
    private function scanFile($filePath, &$threats, &$stats) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Only scan potentially dangerous files
        $dangerousExts = array('php', 'js', 'html', 'htm', 'pl', 'cgi', 'asp', 'aspx', 'jsp');
        if (!in_array($extension, $dangerousExts)) {
            return;
        }
        
        $content = file_get_contents($filePath);
        if (!$content) return;
        
        // Basic malware patterns
        $malwarePatterns = array(
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/shell_exec\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/file_get_contents\s*\(\s*[\'"]https?:\/\//i',
            '/curl_exec\s*\(/i',
            '/\$_POST\s*\[\s*[\'"].*[\'"].*eval/i',
            '/\$_GET\s*\[\s*[\'"].*[\'"].*eval/i',
            '/\$_REQUEST\s*\[\s*[\'"].*[\'"].*eval/i',
            '/\$_COOKIE\s*\[\s*[\'"].*[\'"].*eval/i',
            '/assert\s*\(/i',
            '/preg_replace\s*\(.*\/e/i',
            '/\$\$[a-zA-Z_]/i',
            '/\$\{[^}]*\}/i'
        );
        
        foreach ($malwarePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $threats[] = array(
                    'file' => $filePath,
                    'type' => 'malware',
                    'severity' => 'high',
                    'pattern' => $pattern,
                    'timestamp' => time()
                );
                $stats['malware_detected']++;
                $stats['threats_found']++;
                break;
            }
        }
    }
    
    private function updateLastScanTime($path) {
        $scanFile = $path . '/.security_scan_last';
        file_put_contents($scanFile, time());
    }
    
    private function sendConsolidatedReport() {
        $totalWebsites = count($this->scanResults);
        $totalThreats = 0;
        $criticalSites = array();
        
        foreach ($this->scanResults as $result) {
            if (isset($result['stats']['threats_found'])) {
                $totalThreats += $result['stats']['threats_found'];
                
                if ($result['stats']['threats_found'] > 0) {
                    $criticalSites[] = $result;
                }
            }
        }
        
        // Only send email if there are threats
        if ($totalThreats > 0) {
            $this->sendEmailAlert($totalWebsites, $totalThreats, $criticalSites);
        }
        
        $this->logMessage("Email report sent. Total threats: $totalThreats");
    }
    
    private function sendEmailAlert($totalWebsites, $totalThreats, $criticalSites) {
        $subject = "🚨 MULTI-SITE SECURITY ALERT: $totalThreats threats detected across $totalWebsites websites";
        
        $message = $this->buildEmailMessage($totalWebsites, $totalThreats, $criticalSites);
        
        $headers = "From: " . MultiWebsiteConfig::EMAIL_FROM_NAME . " <" . MultiWebsiteConfig::EMAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . MultiWebsiteConfig::EMAIL_FROM . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        mail(MultiWebsiteConfig::EMAIL_TO, $subject, $message, $headers);
    }
    
    private function buildEmailMessage($totalWebsites, $totalThreats, $criticalSites) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Multi-Site Security Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { background: #dc3545; color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .critical { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .website-name { font-weight: bold; color: #dc3545; }
        .threat-count { font-size: 24px; font-weight: bold; color: #dc3545; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Multi-Site Security Alert</h1>
            <p>Security scan completed on ' . date('Y-m-d H:i:s') . '</p>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="threat-count">' . $totalWebsites . '</div>
                <div>Websites Scanned</div>
            </div>
            <div class="stat">
                <div class="threat-count">' . $totalThreats . '</div>
                <div>Total Threats</div>
            </div>
            <div class="stat">
                <div class="threat-count">' . count($criticalSites) . '</div>
                <div>Affected Sites</div>
            </div>
        </div>
        
        <h2>🔴 Critical Websites</h2>';
        
        foreach ($criticalSites as $site) {
            $html .= '<div class="critical">
                <div class="website-name">' . htmlspecialchars($site['website']['name']) . '</div>
                <div><strong>Domain:</strong> ' . htmlspecialchars($site['website']['domain']) . '</div>
                <div><strong>Path:</strong> ' . htmlspecialchars($site['website']['path']) . '</div>
                <div><strong>Threats Found:</strong> ' . $site['stats']['threats_found'] . '</div>
                <div><strong>Files Scanned:</strong> ' . $site['stats']['files_scanned'] . '</div>
            </div>';
        }
        
        $html .= '
        <div class="footer">
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Access your Multi-Website Security Dashboard</li>
                <li>Review and quarantine detected threats</li>
                <li>Update all website security measures</li>
                <li>Consider implementing additional security layers</li>
            </ol>
            <p>Generated by Hiệp Nguyễn Multi-Site Security Scanner</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function getScanResults() {
        return $this->scanResults;
    }
}

// ========== API ENDPOINTS ==========
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'detect_websites':
            $detector = new WebsiteDetector();
            $websites = $detector->detectWebsites();
            echo json_encode(array('success' => true, 'websites' => $websites));
            break;
            
        case 'scan_all_websites':
            $scanner = new MultiWebsiteScanner();
            $results = $scanner->scanAllWebsites();
            echo json_encode(array('success' => true, 'results' => $results));
            break;
            
        case 'get_scan_status':
            $logFile = dirname(__FILE__) . '/logs/multi_website_scan.log';
            $status = array(
                'running' => false,
                'last_log' => '',
                'log_size' => 0
            );
            
            if (file_exists($logFile)) {
                $status['log_size'] = filesize($logFile);
                $lines = file($logFile);
                $status['last_log'] = end($lines);
            }
            
            echo json_encode($status);
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Unknown action'));
    }
    exit;
}

// ========== HTML DASHBOARD ==========
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Website Security Scanner - Hiệp Nguyễn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .dashboard-header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .dashboard-header p {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
        }
        .card {
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            border-bottom: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .website-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .website-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-safe { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-danger { background: #f8d7da; color: #721c24; }
        .status-pending { background: #d1ecf1; color: #0c5460; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4facfe;
            margin-bottom: 10px;
        }
        .log-container {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .progress-bar {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            height: 8px;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .alert-custom {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .floating-action {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            color: white;
            border: none;
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.4);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .floating-action:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(255, 107, 107, 0.6);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-shield-alt"></i> Multi-Website Security Scanner</h1>
            <p>Quét bảo mật tự động cho tất cả websites trên hosting - Phát triển bởi Hiệp Nguyễn</p>
        </div>

        <!-- Alert -->
        <div class="alert-custom">
            <i class="fas fa-info-circle"></i>
            <strong>Lưu ý:</strong> Hệ thống sẽ tự động phát hiện và quét tất cả websites trong hosting. 
            Vui lòng cấu hình email trong file để nhận thông báo.
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="totalWebsites">0</div>
                <div class="stat-label">Websites Phát Hiện</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="scannedWebsites">0</div>
                <div class="stat-label">Đã Quét</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalThreats">0</div>
                <div class="stat-label">Threats Phát Hiện</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="criticalSites">0</div>
                <div class="stat-label">Sites Nguy Hiểm</div>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-sliders-h"></i> Bảng Điều Khiển</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-primary w-100" onclick="detectWebsites()">
                            <i class="fas fa-search"></i> Phát Hiện Websites
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-danger w-100" onclick="scanAllWebsites()">
                            <i class="fas fa-shield-alt"></i> Quét Tất Cả Websites
                        </button>
                    </div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="scanProgress"></div>
                </div>
                <div id="scanStatus" class="mt-2 text-muted">Sẵn sàng quét...</div>
            </div>
        </div>

        <!-- Websites List -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-globe"></i> Danh Sách Websites</h4>
            </div>
            <div class="card-body">
                <div id="websitesList">
                    <div class="text-center text-muted">
                        <i class="fas fa-globe-americas fa-3x mb-3"></i>
                        <p>Nhấn "Phát Hiện Websites" để bắt đầu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scan Results -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-line"></i> Kết Quả Quét</h4>
            </div>
            <div class="card-body">
                <div id="scanResults">
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-pie fa-3x mb-3"></i>
                        <p>Kết quả quét sẽ hiển thị ở đây</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Logs -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-terminal"></i> Live Logs</h4>
            </div>
            <div class="card-body">
                <div class="log-container" id="liveLogs">
                    Waiting for scan logs...
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-action" onclick="emergencyStop()" title="Emergency Stop">
        <i class="fas fa-stop"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let scanningInProgress = false;
        let logUpdateInterval;
        let detectedWebsites = [];
        let scanResults = [];

        // Detect websites
        function detectWebsites() {
            if (scanningInProgress) return;
            
            updateStatus('Đang phát hiện websites...');
            
            fetch('multi_website_scanner.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=detect_websites'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    detectedWebsites = data.websites;
                    displayWebsites(data.websites);
                    updateStats();
                    updateStatus('Đã phát hiện ' + data.websites.length + ' websites');
                } else {
                    updateStatus('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                updateStatus('Lỗi kết nối: ' + error.message);
            });
        }

        // Display websites
        function displayWebsites(websites) {
            const container = document.getElementById('websitesList');
            
            if (websites.length === 0) {
                container.innerHTML = '<div class="text-center text-muted">Không tìm thấy websites nào</div>';
                return;
            }

            let html = '';
            websites.forEach(website => {
                const lastScan = website.last_scan > 0 ? 
                    new Date(website.last_scan * 1000).toLocaleDateString('vi-VN') : 
                    'Chưa quét';
                
                html += `
                    <div class="website-card">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5><i class="fas fa-globe"></i> ${website.name}</h5>
                                <small class="text-muted">${website.domain}</small>
                            </div>
                            <div class="col-md-4">
                                <div><strong>Path:</strong> ${website.path}</div>
                                <div><strong>Quét cuối:</strong> ${lastScan}</div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="status-badge status-${website.status}">${website.status}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Scan all websites
        function scanAllWebsites() {
            if (scanningInProgress) return;
            
            scanningInProgress = true;
            updateStatus('Đang quét tất cả websites...');
            updateProgress(0);
            
            // Start live log updates
            startLogUpdates();
            
            fetch('multi_website_scanner.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=scan_all_websites'
            })
            .then(response => response.json())
            .then(data => {
                scanningInProgress = false;
                stopLogUpdates();
                
                if (data.success) {
                    scanResults = data.results;
                    displayScanResults(data.results);
                    updateStats();
                    updateProgress(100);
                    updateStatus('Hoàn thành quét tất cả websites');
                } else {
                    updateStatus('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                scanningInProgress = false;
                stopLogUpdates();
                updateStatus('Lỗi kết nối: ' + error.message);
            });
        }

        // Display scan results
        function displayScanResults(results) {
            const container = document.getElementById('scanResults');
            
            if (results.length === 0) {
                container.innerHTML = '<div class="text-center text-muted">Chưa có kết quả quét</div>';
                return;
            }

            let html = '';
            results.forEach(result => {
                const threatsCount = result.stats ? result.stats.threats_found : 0;
                const statusClass = threatsCount > 0 ? 'danger' : 'safe';
                const statusText = threatsCount > 0 ? 'Có Threats' : 'An Toàn';
                
                html += `
                    <div class="website-card border-${statusClass === 'danger' ? 'danger' : 'success'}">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5><i class="fas fa-shield-alt"></i> ${result.website.name}</h5>
                                <small class="text-muted">${result.website.domain}</small>
                            </div>
                            <div class="col-md-4">
                                <div><strong>Files quét:</strong> ${result.stats ? result.stats.files_scanned : 0}</div>
                                <div><strong>Threats:</strong> ${threatsCount}</div>
                                <div><strong>Thời gian:</strong> ${result.scan_time ? result.scan_time.toFixed(2) : 0}s</div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="status-badge status-${statusClass}">${statusText}</span>
                            </div>
                        </div>
                        ${result.error ? `<div class="alert alert-danger mt-2">${result.error}</div>` : ''}
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Update stats
        function updateStats() {
            document.getElementById('totalWebsites').textContent = detectedWebsites.length;
            document.getElementById('scannedWebsites').textContent = scanResults.length;
            
            let totalThreats = 0;
            let criticalSites = 0;
            
            scanResults.forEach(result => {
                if (result.stats && result.stats.threats_found > 0) {
                    totalThreats += result.stats.threats_found;
                    criticalSites++;
                }
            });
            
            document.getElementById('totalThreats').textContent = totalThreats;
            document.getElementById('criticalSites').textContent = criticalSites;
        }

        // Update status
        function updateStatus(message) {
            document.getElementById('scanStatus').textContent = message;
        }

        // Update progress
        function updateProgress(percentage) {
            document.getElementById('scanProgress').style.width = percentage + '%';
        }

        // Start log updates
        function startLogUpdates() {
            logUpdateInterval = setInterval(() => {
                fetch('multi_website_scanner.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_scan_status'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.last_log) {
                        const logContainer = document.getElementById('liveLogs');
                        logContainer.innerHTML = data.last_log;
                        logContainer.scrollTop = logContainer.scrollHeight;
                    }
                });
            }, 2000);
        }

        // Stop log updates
        function stopLogUpdates() {
            if (logUpdateInterval) {
                clearInterval(logUpdateInterval);
            }
        }

        // Emergency stop
        function emergencyStop() {
            if (confirm('Bạn có chắc muốn dừng quét khẩn cấp?')) {
                scanningInProgress = false;
                stopLogUpdates();
                updateStatus('Đã dừng quét khẩn cấp');
                updateProgress(0);
            }
        }

        // Auto-detect websites when page loads
        document.addEventListener('DOMContentLoaded', function() {
            detectWebsites();
        });
    </script>
</body>
</html> 