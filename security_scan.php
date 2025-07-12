<?php
/**
 * Enterprise Security Scanner - Professional Version
 * Author: Hiệp Nguyễn
 * Facebook: https://www.facebook.com/G.N.S.L.7/
 * Version: 3.0 Enterprise - Compact Bento Grid Design
 * Date: June 24, 2025
 */

// Test JSON endpoint
if (isset($_GET['test']) && $_GET['test'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'message' => 'JSON test successful']);
    exit;
}

// Delete malware files endpoint
if (isset($_GET['delete_malware']) && $_GET['delete_malware'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Disable error reporting to prevent breaking JSON
    error_reporting(0);
    ini_set('display_errors', 0);
    
    try {
        $input = file_get_contents('php://input');
        $malwareData = json_decode($input, true);
        
        if (!$malwareData || !isset($malwareData['malware_files'])) {
            throw new Exception('Invalid malware files data provided');
        }
        
        $deleteResults = deleteMalwareFiles($malwareData['malware_files']);
        
        echo json_encode([
            'success' => true,
            'deleted_files' => $deleteResults['deleted_files'],
            'failed_files' => $deleteResults['failed_files'],
            'backup_created' => $deleteResults['backup_created'],
            'details' => $deleteResults['details']
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// Auto-fix endpoint
if (isset($_GET['autofix']) && $_GET['autofix'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Disable error reporting to prevent breaking JSON
    error_reporting(0);
    ini_set('display_errors', 0);
    
    try {
        $input = file_get_contents('php://input');
        $scanData = json_decode($input, true);
        
        if (!$scanData || !isset($scanData['suspicious_files'])) {
            throw new Exception('Invalid scan data provided');
        }
        
        $fixResults = performAutoFix($scanData);
        
        echo json_encode([
            'success' => true,
            'fixed_files' => $fixResults['fixed_files'],
            'fixes_applied' => $fixResults['fixes_applied'],
            'deleted_files' => $fixResults['deleted_files'],
            'backup_created' => $fixResults['backup_created'],
            'details' => $fixResults['details']
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

if (isset($_GET['scan']) && $_GET['scan'] === '1') {
    // Start output buffering and clean any previous output
    ob_start();
    if (ob_get_level() > 1) {
        ob_end_clean();
    }
    
    // Set proper JSON headers before any output
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Disable error reporting to prevent breaking JSON
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Set timeout and memory limits
    set_time_limit(60); // 60 seconds max
    ini_set('memory_limit', '256M');
    
    try {
        // Critical malware patterns that require immediate deletion (HIGH SEVERITY - RED)
        $critical_malware_patterns = [
            'eval(' => 'Code execution vulnerability',
            'goto ' => 'Control flow manipulation',
            'base64_decode(' => 'Encoded payload execution',
            'gzinflate(' => 'Compressed malware payload',
            'str_rot13(' => 'String obfuscation technique',
            '$_F=__FILE__;' => 'File system manipulation',
            'readdir(' => 'Directory traversal attempt',
            '<?php eval' => 'Direct PHP code injection'
        ];

        // Suspicious file extensions and empty files
        $suspicious_file_patterns = [
            '.php.jpg' => 'Disguised PHP file with image extension',
            '.php.png' => 'Disguised PHP file with image extension',
            '.php.gif' => 'Disguised PHP file with image extension',
            '.php.jpeg' => 'Disguised PHP file with image extension',
            '.phtml' => 'Alternative PHP extension',
            '.php3' => 'Legacy PHP extension',
            '.php4' => 'Legacy PHP extension',
            '.php5' => 'Legacy PHP extension'
        ];

        // Severe patterns for uploads and dangerous functions
        $severe_patterns = [
            'move_uploaded_file(' => 'File upload without validation',
            'exec(' => 'System command execution',
            'system(' => 'Direct system call',
            'shell_exec(' => 'Shell command execution',
            'passthru(' => 'Command output bypass',
            'proc_open(' => 'Process creation',
            'popen(' => 'Pipe command execution'
        ];

        // Warning patterns for filemanager and normal functions (MEDIUM SEVERITY - ORANGE/YELLOW)
        $warning_patterns = [
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

        // Directories to scan (including filemanager now)
        $directories = ['./sources', './admin', './uploads', './virus-files', './'];
        $suspicious_files = [];
        $critical_files = [];
        $severe_files = [];
        $warning_files = [];
        $filemanager_files = [];
        $scanned_files = 0;
        $max_files = 2000; // Increased limit

        function scanFileWithLineNumbers($file_path, $patterns) {
            if (!file_exists($file_path) || !is_readable($file_path)) {
                return [];
            }
            
            $content = @file_get_contents($file_path);
            if ($content === false) {
                return [];
            }
            
            $lines = explode("\n", $content);
            $issues = [];
            
            foreach ($patterns as $pattern => $description) {
                foreach ($lines as $lineNumber => $line) {
                    if (stripos($line, $pattern) !== false) {
                        $issues[] = [
                            'pattern' => $pattern,
                            'description' => $description,
                            'line' => $lineNumber + 1,
                            'code_snippet' => trim($line)
                        ];
                    }
                }
            }
            
            return $issues;
        }

        function checkSuspiciousFile($file_path, $suspicious_patterns) {
            $issues = [];
            $filename = basename($file_path);
            
            // Check for suspicious file extensions
            foreach ($suspicious_patterns as $pattern => $description) {
                if (stripos($filename, $pattern) !== false) {
                    $issues[] = [
                        'pattern' => $pattern,
                        'description' => $description,
                        'line' => 0,
                        'code_snippet' => 'Suspicious filename: ' . $filename
                    ];
                }
            }
            
            // Check for empty or near-empty PHP files
            if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'php') {
                $content = @file_get_contents($file_path);
                if ($content !== false) {
                    $content_trimmed = trim($content);
                    $content_no_php_tags = str_replace(['<?php', '<?', '?>'], '', $content_trimmed);
                    $content_clean = trim($content_no_php_tags);
                    
                    // If file is empty or only contains PHP tags
                    if (empty($content_clean) || strlen($content_clean) < 10) {
                        $issues[] = [
                            'pattern' => 'empty_php_file',
                            'description' => 'Empty or suspicious PHP file',
                            'line' => 1,
                            'code_snippet' => 'File content: ' . substr($content_trimmed, 0, 50) . '...'
                        ];
                    }
                }
            }
            
            return $issues;
        }

        function scanDirectory($dir, $critical_patterns, $severe_patterns, $warning_patterns) {
            global $suspicious_files, $scanned_files, $critical_files, $severe_files, $warning_files, $filemanager_files, $max_files, $suspicious_file_patterns;
            
            if (!is_dir($dir)) {
                return;
            }
            
            // Only exclude security_scan.php
            $exclude_files = [
                'security_scan.php'
            ];
            
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    // Stop if we've scanned enough files
                    if ($scanned_files >= $max_files) {
                        break;
                    }
                    
                    if ($file->isFile()) {
                        $file_path = $file->getPathname();
                        $filename = basename($file_path);
                        $extension = strtolower($file->getExtension());
                        
                        // Skip only security_scan.php
                        if (in_array($filename, $exclude_files)) {
                            continue;
                        }
                        
                        // Scan PHP files and suspicious extensions
                        $should_scan = ($extension === 'php') || 
                                      (strpos($filename, '.php.') !== false) ||
                                      in_array($extension, ['phtml', 'php3', 'php4', 'php5']);
                        
                        if ($should_scan) {
                            $scanned_files++;
                            
                            // Determine file category
                            $is_virus_file = strpos($file_path, 'virus-files') !== false;
                            $is_filemanager = strpos($file_path, 'admin/filemanager') !== false;
                            
                            // Check for suspicious file extensions and empty files FIRST
                            $suspicious_issues = checkSuspiciousFile($file_path, $suspicious_file_patterns);
                            if (!empty($suspicious_issues)) {
                                $suspicious_files[] = [
                                    'path' => $file_path,
                                    'issues' => $suspicious_issues,
                                    'severity' => 'critical',
                                    'priority' => 1,
                                    'category' => 'suspicious_file'
                                ];
                                $critical_files[] = $file_path;
                            } else {
                                // Check for critical malware patterns (HIGHEST PRIORITY)
                                $critical_issues = scanFileWithLineNumbers($file_path, $critical_patterns);
                                if (!empty($critical_issues)) {
                                    $suspicious_files[] = [
                                        'path' => $file_path,
                                        'issues' => $critical_issues,
                                        'severity' => 'critical',
                                        'priority' => 1,
                                        'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system')
                                    ];
                                    $critical_files[] = $file_path;
                                } else {
                                    // Check for severe patterns
                                    $severe_issues = scanFileWithLineNumbers($file_path, $severe_patterns);
                                    if (!empty($severe_issues)) {
                                        $severity = $is_virus_file ? 'critical' : 'severe';
                                        $priority = $is_virus_file ? 1 : 2;
                                        
                                        $suspicious_files[] = [
                                            'path' => $file_path,
                                            'issues' => $severe_issues,
                                            'severity' => $severity,
                                            'priority' => $priority,
                                            'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system')
                                        ];
                                        
                                        if ($is_virus_file) {
                                            $critical_files[] = $file_path;
                                        } else {
                                            $severe_files[] = $file_path;
                                        }
                                    } else {
                                        // Check for warning patterns (LOWER PRIORITY)
                                        $warning_issues = scanFileWithLineNumbers($file_path, $warning_patterns);
                                        if (!empty($warning_issues)) {
                                            $severity = 'warning';
                                            $priority = 3;
                                            
                                            $suspicious_files[] = [
                                                'path' => $file_path,
                                                'issues' => $warning_issues,
                                                'severity' => $severity,
                                                'priority' => $priority,
                                                'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system')
                                            ];
                                            
                                            if ($is_filemanager) {
                                                $filemanager_files[] = $file_path;
                                            } else {
                                                $warning_files[] = $file_path;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Continue scanning other directories
                error_log("Scan error in directory $dir: " . $e->getMessage());
            }
        }

        // Perform scan
        foreach ($directories as $dir) {
            scanDirectory($dir, $critical_malware_patterns, $severe_patterns, $warning_patterns);
        }

        // Sort suspicious files by priority (critical first)
        usort($suspicious_files, function($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return strcmp($a['path'], $b['path']);
            }
            return $a['priority'] - $b['priority'];
        });

        // Log scan results
        $log_data = date('Y-m-d H:i:s') . " - Enterprise Security scan completed. Scanned: $scanned_files files, Found: " . count($suspicious_files) . " threats. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        if (!file_exists('./logs')) {
            @mkdir('./logs', 0755, true);
        }
        @file_put_contents('./logs/security_scan_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);

        // Return results
        $response = [
            'success' => true,
            'scanned_files' => $scanned_files,
            'suspicious_count' => count($suspicious_files),
            'suspicious_files' => $suspicious_files,
            'critical_files' => $critical_files,
            'critical_count' => count($critical_files),
            'severe_files' => $severe_files,
            'severe_count' => count($severe_files),
            'warning_files' => $warning_files,
            'warning_count' => count($warning_files),
            'filemanager_files' => $filemanager_files,
            'filemanager_count' => count($filemanager_files),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Clean output buffer and send JSON
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Clean output buffer for error response
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Scan error: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

function deleteMalwareFiles($malware_files) {
    $deleted_files = 0;
    $failed_files = [];
    $backup_created = false;
    $details = [];
    
    // Create backup directory
    $backup_dir = './security_backups/' . date('Y-m-d_H-i-s');
    if (!file_exists($backup_dir)) {
        if (mkdir($backup_dir, 0755, true)) {
            $backup_created = true;
        }
    }
    
    foreach ($malware_files as $file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            $failed_files[] = $file_path . ' (file not found or not readable)';
            continue;
        }
        
        // Create backup before deletion
        if ($backup_created) {
            $content = file_get_contents($file_path);
            $backup_file = $backup_dir . '/' . basename($file_path) . '.malware_backup';
            file_put_contents($backup_file, $content);
        }
        
        // Delete the malware file
        if (unlink($file_path)) {
            $deleted_files++;
            $details[] = "Deleted malware file: {$file_path}";
            
            // Log the deletion
            $log_data = date('Y-m-d H:i:s') . " - MALWARE DELETED: {$file_path}. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
            if (!file_exists('./logs')) {
                @mkdir('./logs', 0755, true);
            }
            @file_put_contents('./logs/malware_delete_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);
        } else {
            $failed_files[] = $file_path . ' (deletion failed)';
        }
    }
    
    return [
        'deleted_files' => $deleted_files,
        'failed_files' => $failed_files,
        'backup_created' => $backup_created,
        'details' => $details
    ];
}

function performAutoFix($scan_data) {
    $fixed_files = 0;
    $fixes_applied = 0;
    $deleted_files = 0;
    $backup_created = false;
    $details = [];
    
    // Create backup directory
    $backup_dir = './security_backups/' . date('Y-m-d_H-i-s');
    if (!file_exists($backup_dir)) {
        if (mkdir($backup_dir, 0755, true)) {
            $backup_created = true;
        }
    }
    
    // STEP 1: Auto-delete critical malware files
    if (isset($scan_data['critical_files']) && !empty($scan_data['critical_files'])) {
        $details[] = "Found " . count($scan_data['critical_files']) . " critical malware files to delete";
        
        foreach ($scan_data['critical_files'] as $malware_file) {
            // Normalize path (remove ./ and .\ prefixes)
            $normalized_path = ltrim($malware_file, './\\');
            $full_path = './' . $normalized_path;
            
            if (file_exists($full_path)) {
                // Backup before deletion
                if ($backup_created) {
                    $content = file_get_contents($full_path);
                    $backup_file = $backup_dir . '/' . basename($full_path) . '.malware_backup';
                    file_put_contents($backup_file, $content);
                }
                
                // Delete malware file
                if (unlink($full_path)) {
                    $deleted_files++;
                    $details[] = "✅ Auto-deleted critical malware: {$full_path}";
                    
                    // Log deletion
                    $log_data = date('Y-m-d H:i:s') . " - AUTO-FIX DELETED: {$full_path}. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
                    if (!file_exists('./logs')) {
                        @mkdir('./logs', 0755, true);
                    }
                    @file_put_contents('./logs/autofix_delete_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);
                } else {
                    $details[] = "❌ Failed to delete: {$full_path}";
                }
            } else {
                $details[] = "⚠️ File not found: {$full_path}";
            }
        }
    }
    
    // STEP 2: Create/Update uploads/.htaccess protection
    $htaccess_path = './uploads/.htaccess';
    $htaccess_content = "# Enterprise Security Protection\n";
    $htaccess_content .= "# Generated by Security Scanner - " . date('Y-m-d H:i:s') . "\n\n";
    $htaccess_content .= "# Deny execution of PHP files\n";
    $htaccess_content .= "<Files *.php>\n";
    $htaccess_content .= "    Order Deny,Allow\n";
    $htaccess_content .= "    Deny from all\n";
    $htaccess_content .= "</Files>\n\n";
    $htaccess_content .= "# Deny access to dangerous file types\n";
    $htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\n";
    $htaccess_content .= "    Order Deny,Allow\n";
    $htaccess_content .= "    Deny from all\n";
    $htaccess_content .= "</FilesMatch>\n\n";
    $htaccess_content .= "# Additional security headers\n";
    $htaccess_content .= "Header always set X-Content-Type-Options nosniff\n";
    $htaccess_content .= "Header always set X-Frame-Options DENY\n";
    
    if (!file_exists('./uploads')) {
        mkdir('./uploads', 0755, true);
    }
    
    if (file_put_contents($htaccess_path, $htaccess_content)) {
        $fixed_files++;
        $fixes_applied++;
        $details[] = "Created/Updated security protection: {$htaccess_path}";
    }
    
    // Log the fix operation
    $log_data = date('Y-m-d H:i:s') . " - Auto-fix completed. Fixed files: $fixed_files, Fixes applied: $fixes_applied, Deleted: $deleted_files, Backup: " . ($backup_created ? 'Yes' : 'No') . ". IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    if (!file_exists('./logs')) {
        @mkdir('./logs', 0755, true);
    }
    @file_put_contents('./logs/security_fix_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);
    
    return [
        'fixed_files' => $fixed_files,
        'fixes_applied' => $fixes_applied,
        'deleted_files' => $deleted_files,
        'backup_created' => $backup_created,
        'details' => $details
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Security Scanner - Hiệp Nguyễn</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            /* Blue Pastel Theme */
            --primary-blue: #4A90E2;
            --light-blue: #E8F4FD;
            --soft-blue: #B8DCF2;
            --dark-blue: #2C5282;
            --accent-blue: #63B3ED;
            
            /* Neutral Pastel Colors */
            --bg-primary: #FAFBFC;
            --bg-secondary: #F7F9FB;
            --bg-card: #FFFFFF;
            --border-light: #E2E8F0;
            --border-medium: #CBD5E0;
            
            /* Text Colors */
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --text-muted: #718096;
            --text-light: #A0AEC0;
            
            /* Status Colors - Soft Pastels */
            --danger-bg: #FED7D7;
            --danger-border: #FC8181;
            --danger-text: #C53030;
            
            --warning-bg: #FEFCBF;
            --warning-border: #F6E05E;
            --warning-text: #D69E2E;
            
            --success-bg: #C6F6D5;
            --success-border: #68D391;
            --success-text: #38A169;
            
            --info-bg: #BEE3F8;
            --info-border: #63B3ED;
            --info-text: #3182CE;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-blue: 0 4px 14px rgba(74, 144, 226, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 14px;
        }

        .container-fluid {
            max-width: 1600px;
            margin: 0 auto;
            padding: 16px;
        }

        /* Header - Compact */
        .header {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-blue);
            text-align: center;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .header .subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .author-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Bento Grid Layout */
        .bento-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: auto auto auto;
            gap: 12px;
            margin-bottom: 16px;
        }

        .bento-item {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .bento-item:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--border-medium);
        }

        .bento-item.span-2 {
            grid-column: span 2;
        }

        .bento-item.span-3 {
            grid-column: span 3;
        }

        /* Card Headers - Compact */
        .card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-light);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Control Panel */
        .scan-controls {
            text-align: center;
            margin-bottom: 12px;
        }

        .scan-btn, .autofix-btn {
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0 4px;
        }

        .scan-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            box-shadow: var(--shadow-blue);
        }

                 .autofix-btn {
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            color: white;
            box-shadow: 0 4px 14px rgba(255, 140, 66, 0.15);
        }

        .scan-btn:hover, .autofix-btn:hover {
            transform: translateY(-1px);
        }

        .scan-btn:disabled, .autofix-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            border-radius: 8px;
            border: 1px solid var(--border-medium);
            box-shadow: var(--shadow-lg);
            padding: 8px 0;
            min-width: 280px;
        }

        .dropdown-menu-dark {
            background: var(--text-primary);
            border-color: var(--border-medium);
        }

        .dropdown-header {
            color: var(--accent-blue) !important;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 8px 16px 4px 16px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropdown-item {
            padding: 8px 16px;
            font-size: 0.9rem;
            color: #fff !important;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-item:hover {
            background: rgba(74, 144, 226, 0.2) !important;
            color: #fff !important;
            transform: translateX(4px);
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 8px 0;
        }

        .dropdown-toggle::after {
            margin-left: 8px;
        }

        /* Progress - Compact */
        .progress-section {
            opacity: 0;
            transition: all 0.4s ease;
            transform: translateY(10px);
        }

        .progress-section.active {
            opacity: 1;
            transform: translateY(0);
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .progress-text {
            font-weight: 600;
            color: var(--primary-blue);
        }

        .progress-percentage {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--dark-blue);
        }

        .progress-bar-container {
            background: var(--light-blue);
            border-radius: 6px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
            transition: width 0.3s ease;
            border-radius: 6px;
        }

        .current-action {
            text-align: center;
            color: var(--text-secondary);
            font-style: italic;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Statistics - Compact Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-light);
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-blue);
            font-family: 'JetBrains Mono', monospace;
            display: block;
            margin-bottom: 2px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* File Scanner - Compact */
        .file-scanner {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            height: 240px;
            overflow: hidden;
        }

        .scanner-header {
            background: var(--bg-secondary);
            padding: 8px 12px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .scanner-content {
            height: calc(100% - 36px);
            overflow-y: auto;
            padding: 0;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-bottom: 1px solid var(--border-light);
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }

        .file-item:hover {
            background: var(--bg-secondary);
        }

        .file-icon {
            width: 12px;
            text-align: center;
        }

        .file-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--text-secondary);
            flex: 1;
        }

        .file-status {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-clean {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-border);
        }

        .status-suspicious {
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid var(--danger-border);
        }

        .scanner-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            text-align: center;
        }

        .scanner-empty i {
            font-size: 2rem;
            margin-bottom: 8px;
            opacity: 0.6;
            color: var(--primary-blue);
        }

        /* Threat Detection Patterns - Compact */
        .patterns-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            font-size: 0.75rem;
        }

        .pattern-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px;
            background: var(--bg-secondary);
            border-radius: 4px;
            border: 1px solid var(--border-light);
        }

        .pattern-item i {
            width: 12px;
            text-align: center;
        }

        /* Results Panel - Compact Groups */
        .results-panel {
            opacity: 0;
            transition: all 0.4s ease;
            transform: translateY(10px);
        }

        .results-panel.active {
            opacity: 1;
            transform: translateY(0);
        }

        .results-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .threat-group {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 12px;
            box-shadow: var(--shadow-sm);
        }

        .threat-group.critical {
            border-color: var(--danger-border);
            background: var(--danger-bg);
        }

        .threat-group.warning {
            border-color: var(--warning-border);
            background: var(--warning-bg);
        }

                 .threat-group.filemanager {
            border-color: var(--info-border);
            background: var(--info-bg);
        }

        .threat-group.suspicious_file {
            border-color: #E53E3E;
            background: #FED7D7;
        }

        .group-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .group-header.critical {
            color: var(--danger-text);
        }

        .group-header.warning {
            color: var(--warning-text);
        }

                 .group-header.filemanager {
            color: var(--info-text);
        }

        .group-header.suspicious_file {
            color: #E53E3E;
        }

        .threat-item {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .threat-item:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .threat-item:last-child {
            margin-bottom: 0;
        }

        .threat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .threat-path {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            color: var(--text-primary);
        }

        .delete-btn {
            background: linear-gradient(135deg, #E53E3E, #C53030);
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
        }

        .delete-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .threat-issues {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* Tooltips */
        .tooltip {
            font-size: 0.75rem;
        }

        .tooltip-inner {
            max-width: 300px;
            text-align: left;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .bento-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
            
            .bento-item.span-2,
            .bento-item.span-3 {
                grid-column: span 1;
            }
            
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Animations */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .slideIn {
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Alert - Compact */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 12px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: var(--success-bg);
            border-color: var(--success-border);
            color: var(--success-text);
        }

        .alert-danger {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger-text);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shield-halved"></i> Enterprise Security Scanner</h1>
            <p class="subtitle">Công cụ quét malware và backdoor chuyên nghiệp cho doanh nghiệp</p>
            <div class="author-badge">
                <i class="fab fa-facebook"></i>
                <span>Tác giả: Hiệp Nguyễn</span>
                <span style="opacity: 0.8;">• Enterprise Grade</span>
            </div>
        </div>

        <!-- Bento Grid Dashboard -->
        <div class="bento-grid">
            <!-- Control Panel -->
            <div class="bento-item span-2">
                <div class="card-header">
                    <i class="fas fa-sliders-h" style="color: var(--primary-blue);"></i>
                    <h3 class="card-title">Bảng Điều Khiển Quét</h3>
                </div>
                
                <div class="scan-controls">
                    <button id="scanBtn" class="scan-btn">
                        <i class="fas fa-search"></i> Bắt Đầu Quét
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="autofix-btn dropdown-toggle" type="button" id="fixDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                            <i class="fas fa-tools"></i> Khắc Phục
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="fixDropdown">
                            <li><h6 class="dropdown-header"><i class="fas fa-shield-virus"></i> Xử Lý Malware</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('delete_critical')">
                                <i class="fas fa-trash-alt text-danger"></i> Xóa Files Nguy Hiểm
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('quarantine')">
                                <i class="fas fa-shield-alt text-warning"></i> Cách Ly Files Đáng Ngờ
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-wrench"></i> Sửa Chữa Hệ Thống</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('fix_permissions')">
                                <i class="fas fa-key text-info"></i> Sửa Quyền Files
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('update_htaccess')">
                                <i class="fas fa-cog text-success"></i> Cập Nhật .htaccess
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('clean_logs')">
                                <i class="fas fa-broom text-secondary"></i> Dọn Dẹp Logs
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-magic"></i> Tự Động Hóa</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('auto_fix_all')">
                                <i class="fas fa-bolt text-primary"></i> Khắc Phục Toàn Bộ
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('schedule_scan')">
                                <i class="fas fa-clock text-info"></i> Lên Lịch Quét
                            </a></li>
                        </ul>
                    </div>
                </div>

                <div id="progressSection" class="progress-section">
                    <div class="progress-info">
                        <span id="progressText" class="progress-text">Đang chuẩn bị quét...</span>
                        <span id="progressPercentage" class="progress-percentage">0%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div id="progressBar" class="progress-bar"></div>
                    </div>
                    <div class="current-action">
                        <i class="fas fa-spinner pulse" style="color: var(--primary-blue);"></i> 
                        <span id="currentAction">Đang quét hệ thống...</span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bento-item">
                <div class="card-header">
                    <i class="fas fa-chart-bar" style="color: var(--accent-blue);"></i>
                    <h3 class="card-title">Thống Kê</h3>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span id="scannedFiles" class="stat-number">0</span>
                        <div class="stat-label">Files Quét</div>
                    </div>
                    <div class="stat-card">
                        <span id="suspiciousFiles" class="stat-number">0</span>
                        <div class="stat-label">Threats</div>
                    </div>
                    <div class="stat-card">
                        <span id="criticalFiles" class="stat-number">0</span>
                        <div class="stat-label">Critical</div>
                    </div>
                    <div class="stat-card">
                        <span id="scanTime" class="stat-number">0s</span>
                        <div class="stat-label">Thời Gian</div>
                    </div>
                </div>
            </div>

            <!-- File Scanner -->
            <div class="bento-item">
                <div class="card-header">
                    <i class="fas fa-file-search" style="color: var(--warning-text);"></i>
                    <h3 class="card-title">Files Đang Quét</h3>
                </div>
                <div class="file-scanner">
                    <div class="scanner-header">
                        <i class="fas fa-terminal" style="color: var(--primary-blue);"></i>
                        <span style="font-family: 'JetBrains Mono', monospace; color: var(--text-secondary); font-size: 0.75rem;">real-time scanner</span>
                    </div>
                    <div id="scannerContent" class="scanner-content">
                        <div class="scanner-empty">
                            <i class="fas fa-search"></i>
                            <p style="font-size: 0.8rem;">Nhấn "Bắt Đầu Quét" để bắt đầu</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Threat Detection Patterns -->
            <div class="bento-item">
                <div class="card-header">
                    <i class="fas fa-bug" style="color: var(--danger-text);"></i>
                    <h3 class="card-title">Threat Patterns</h3>
                </div>
                <div class="patterns-grid">
                    <div class="pattern-item">
                        <i class="fas fa-skull-crossbones" style="color: var(--danger-text);"></i>
                        <span>eval(), base64_decode()</span>
                    </div>
                    <div class="pattern-item">
                        <i class="fas fa-terminal" style="color: var(--warning-text);"></i>
                        <span>exec(), system()</span>
                    </div>
                    <div class="pattern-item">
                        <i class="fas fa-upload" style="color: var(--info-text);"></i>
                        <span>move_uploaded_file()</span>
                    </div>
                    <div class="pattern-item">
                        <i class="fas fa-code" style="color: var(--primary-blue);"></i>
                        <span>$_GET, $_POST</span>
                    </div>
                </div>
            </div>

            <!-- Results Panel -->
            <div id="resultsPanel" class="bento-item span-3 results-panel">
                <div class="card-header">
                    <i class="fas fa-clipboard-list" style="color: var(--success-text);"></i>
                    <h3 class="card-title">Báo Cáo Kết Quả Bảo Mật</h3>
                </div>
                <div id="scanResults"></div>
            </div>
        </div>
    </div>

    <script>
        class SecurityScanner {
            constructor() {
                this.isScanning = false;
                this.scannedFiles = 0;
                this.suspiciousFiles = 0;
                this.criticalFiles = 0;
                this.scanStartTime = null;
                this.progressInterval = null;
                this.speedInterval = null;
                this.fileSimulationInterval = null;
                this.lastScanData = null;
                this.init();
            }

            init() {
                document.getElementById('scanBtn').addEventListener('click', () => this.startScan());
                
                // Initialize Bootstrap tooltips
                this.initTooltips();
            }

            initTooltips() {
                // Initialize tooltips for dynamically created elements
                document.addEventListener('DOMContentLoaded', function() {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });
            }

            startScan() {
                if (this.isScanning) return;
                
                this.isScanning = true;
                this.scannedFiles = 0;
                this.suspiciousFiles = 0;
                this.criticalFiles = 0;
                this.scanStartTime = Date.now();
                
                const scanBtn = document.getElementById('scanBtn');
                const progressSection = document.getElementById('progressSection');
                const resultsPanel = document.getElementById('resultsPanel');
                
                // Update UI
                scanBtn.disabled = true;
                scanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang Quét...';
                progressSection.classList.add('active');
                resultsPanel.classList.remove('active');
                
                // Clear scanner content
                document.getElementById('scannerContent').innerHTML = '';
                
                // Start file simulation
                this.startFileSimulation();
                
                // Start progress simulation
                this.simulateProgress();
                
                // Start speed counter
                this.startSpeedCounter();
                
                // Start actual scan after short delay
                setTimeout(() => {
                    this.performScan();
                }, 1000);
            }

            startFileSimulation() {
                const testFiles = [
                    './index.php',
                    './admin/index.php',
                    './admin/lib/function.php',
                    './admin/filemanager/execute.php',
                    './admin/filemanager/ajax_calls.php',
                    './admin/filemanager/include/utils.php',
                    './virus-files/23.php',
                    './virus-files/666.php',
                    './virus-files/cache.php',
                    './sources/config.php',
                    './sources/database.php',
                    './uploads/test.jpg',
                    './admin/login.php'
                ];

                let fileIndex = 0;
                this.fileSimulationInterval = setInterval(() => {
                    if (fileIndex < testFiles.length && this.isScanning) {
                        const file = testFiles[fileIndex];
                        const isVirusFile = file.includes('virus-files');
                        const isSuspicious = isVirusFile || Math.random() < 0.15;
                        
                        this.addFileToScanner(file, !isSuspicious);
                        this.scannedFiles++;
                        
                        if (isSuspicious) {
                            this.suspiciousFiles++;
                            if (isVirusFile) {
                                this.criticalFiles++;
                            }
                        }
                        
                        this.updateStats();
                        fileIndex++;
                    } else {
                        clearInterval(this.fileSimulationInterval);
                    }
                }, 200);
            }

            simulateProgress() {
                let progress = 0;
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const progressPercentage = document.getElementById('progressPercentage');
                const currentAction = document.getElementById('currentAction');
                
                const actions = [
                    'Khởi tạo scanner...',
                    'Quét sources...',
                    'Phân tích admin...',
                    'Kiểm tra filemanager...',
                    'Quét virus-files...',
                    'Hoàn thiện...'
                ];
                
                let actionIndex = 0;
                
                this.progressInterval = setInterval(() => {
                    progress += Math.random() * 8 + 4;
                    if (progress > 100) progress = 100;
                    
                    progressBar.style.width = progress + '%';
                    progressPercentage.textContent = Math.round(progress) + '%';
                    
                    if (Math.floor(progress / 17) > actionIndex && actionIndex < actions.length - 1) {
                        actionIndex++;
                        currentAction.textContent = actions[actionIndex];
                    }
                    
                    if (progress >= 100) {
                        clearInterval(this.progressInterval);
                        progressText.textContent = 'Hoàn tất!';
                        currentAction.textContent = 'Tạo báo cáo...';
                    }
                }, 150);
            }

            startSpeedCounter() {
                this.speedInterval = setInterval(() => {
                    const elapsed = (Date.now() - this.scanStartTime) / 1000;
                    document.getElementById('scanTime').textContent = Math.round(elapsed) + 's';
                }, 500);
            }

            addFileToScanner(filePath, isClean) {
                const scannerContent = document.getElementById('scannerContent');
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item slideIn';
                fileItem.innerHTML = `
                    <div class="file-icon">
                        <i class="fas fa-file-code" style="color: ${isClean ? 'var(--success-text)' : 'var(--danger-text)'};"></i>
                    </div>
                    <div class="file-path">${filePath}</div>
                    <div class="file-status ${isClean ? 'status-clean' : 'status-suspicious'}">
                        ${isClean ? 'Clean' : 'Threat'}
                    </div>
                `;
                
                scannerContent.appendChild(fileItem);
                scannerContent.scrollTop = scannerContent.scrollHeight;
                
                // Keep only last 10 items
                while (scannerContent.children.length > 10) {
                    scannerContent.removeChild(scannerContent.firstChild);
                }
            }

            updateStats() {
                document.getElementById('scannedFiles').textContent = this.scannedFiles;
                document.getElementById('suspiciousFiles').textContent = this.suspiciousFiles;
                document.getElementById('criticalFiles').textContent = this.criticalFiles;
            }

            async performScan() {
                try {
                    console.log('Starting scan request...');
                    
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 30000);
                    
                    const response = await fetch('?scan=1', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache'
                        },
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const text = await response.text();
                    console.log('Raw response received, length:', text.length);
                    
                    if (!text || text.trim() === '') {
                        throw new Error('Empty response from server');
                    }
                    
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        console.error('Response text (first 500 chars):', text.substring(0, 500));
                        throw new Error('Response không phải JSON hợp lệ. Check console for details.');
                    }
                    
                    console.log('Parsed data:', data);
                    this.displayResults(data);
                    
                } catch (error) {
                    console.error('Scan error:', error);
                    if (error.name === 'AbortError') {
                        this.displayError('Quét bị timeout. Vui lòng thử lại.');
                    } else {
                        this.displayError('Lỗi quét: ' + error.message);
                    }
                }
            }

            displayResults(data) {
                // Update final stats with real data
                this.scannedFiles = data.scanned_files || this.scannedFiles;
                this.suspiciousFiles = data.suspicious_count || this.suspiciousFiles;
                this.criticalFiles = data.critical_count || this.criticalFiles;
                this.updateStats();
                
                // Store scan data for auto-fix
                this.lastScanData = data;
                
                setTimeout(() => {
                    const resultsPanel = document.getElementById('resultsPanel');
                    const scanResults = document.getElementById('scanResults');
                    const autoFixBtn = document.getElementById('autoFixBtn');
                    
                    resultsPanel.classList.add('active');
                    
                    if (data.suspicious_count === 0) {
                        scanResults.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-shield-check"></i>
                                <div>
                                    <strong>Hệ thống an toàn!</strong><br>
                                    <small>Không phát hiện threat nào trong ${data.scanned_files} files đã quét.</small>
                                </div>
                            </div>
                        `;
                        document.getElementById('fixDropdown').disabled = true;
                    } else {
                        let resultHtml = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Phát hiện ${data.suspicious_count} threats!</strong><br>
                                    <small>Trong đó có ${data.critical_count || 0} threats nghiêm trọng cần xử lý ngay.</small>
                                </div>
                            </div>
                            <div class="results-grid">
                        `;
                        
                        // Group files by category and severity
                        const groups = {
                            suspicious_file: { title: 'Files Đáng Ngờ (.php.jpg, Empty)', icon: 'fa-exclamation-circle', files: [] },
                            critical: { title: 'Files Virus/Malware Nguy Hiểm', icon: 'fa-skull-crossbones', files: [] },
                            filemanager: { title: 'Filemanager Functions', icon: 'fa-folder-open', files: [] },
                            warning: { title: 'Cảnh Báo Bảo Mật', icon: 'fa-exclamation-triangle', files: [] }
                        };
                        
                        data.suspicious_files.forEach((file, index) => {
                            const isCritical = file.severity === 'critical';
                            const isFilemanager = file.category === 'filemanager';
                            const isSuspiciousFile = file.category === 'suspicious_file';
                            
                            if (isSuspiciousFile) {
                                groups.suspicious_file.files.push({ ...file, index });
                            } else if (isCritical && !isFilemanager) {
                                groups.critical.files.push({ ...file, index });
                            } else if (isFilemanager) {
                                groups.filemanager.files.push({ ...file, index });
                            } else {
                                groups.warning.files.push({ ...file, index });
                            }
                        });
                        
                        // Render groups
                        Object.entries(groups).forEach(([key, group]) => {
                            if (group.files.length > 0) {
                                resultHtml += `
                                    <div class="threat-group ${key}">
                                        <div class="group-header ${key}">
                                            <i class="fas ${group.icon}"></i>
                                            <span>${group.title} (${group.files.length})</span>
                                        </div>
                                `;
                                
                                group.files.forEach(file => {
                                    const isCritical = (file.severity === 'critical' && file.category !== 'filemanager') || file.category === 'suspicious_file';
                                    const tooltipContent = this.generateTooltipContent(file.issues);
                                    
                                    resultHtml += `
                                        <div class="threat-item" 
                                             data-bs-toggle="tooltip" 
                                             data-bs-placement="top" 
                                             data-bs-html="true"
                                             title="${tooltipContent}">
                                            <div class="threat-header">
                                                <div class="threat-path">
                                                    <i class="fas fa-file-code"></i> ${file.path}
                                                </div>
                                                ${isCritical ? `
                                                    <button class="delete-btn" onclick="scanner.deleteSingleFile('${file.path}', ${file.index})">
                                                        <i class="fas fa-trash-alt"></i> Xóa
                                                    </button>
                                                ` : ''}
                                            </div>
                                            <div class="threat-issues">
                                                ${file.issues.length} vấn đề phát hiện
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                resultHtml += '</div>';
                            }
                        });
                        
                        resultHtml += '</div>';
                        
                        scanResults.innerHTML = resultHtml;
                        
                        // Initialize tooltips for new elements
                        setTimeout(() => {
                            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                                return new bootstrap.Tooltip(tooltipTriggerEl);
                            });
                        }, 100);
                        
                        // Enable fix dropdown
                        document.getElementById('fixDropdown').disabled = false;
                    }
                    
                    this.completeScan();
                }, 1000);
            }

            generateTooltipContent(issues) {
                if (!issues || issues.length === 0) return 'Không có thông tin chi tiết';
                
                let content = '<div style="text-align: left;">';
                issues.forEach(issue => {
                    content += `<div style="margin-bottom: 4px;">`;
                    content += `<strong>Dòng ${issue.line}:</strong> ${issue.pattern}<br>`;
                    content += `<small>${issue.description}</small><br>`;
                    content += `<code style="font-size: 0.7rem;">${issue.code_snippet.substring(0, 50)}...</code>`;
                    content += `</div>`;
                });
                content += '</div>';
                
                return content.replace(/"/g, '&quot;');
            }

            displayError(message) {
                const resultsPanel = document.getElementById('resultsPanel');
                const scanResults = document.getElementById('scanResults');
                
                resultsPanel.classList.add('active');
                scanResults.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i>
                        <div>
                            <strong>Lỗi quét!</strong><br>
                            <small>${message}</small>
                        </div>
                    </div>
                `;
                
                this.completeScan();
            }

            completeScan() {
                clearInterval(this.speedInterval);
                clearInterval(this.fileSimulationInterval);
                
                setTimeout(() => {
                    const scanBtn = document.getElementById('scanBtn');
                    scanBtn.disabled = false;
                    scanBtn.innerHTML = '<i class="fas fa-redo"></i> Quét Lại';
                    document.getElementById('progressSection').classList.remove('active');
                    this.isScanning = false;
                }, 1000);
            }

            async performAction(action) {
                if (!this.lastScanData || this.lastScanData.suspicious_count === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Không có dữ liệu để xử lý',
                        text: 'Vui lòng quét hệ thống trước khi thực hiện khắc phục!',
                        confirmButtonColor: 'var(--primary-blue)'
                    });
                    return;
                }

                const actions = {
                    'delete_critical': {
                        title: 'Xóa Files Nguy Hiểm',
                        text: `Sẽ xóa ${this.lastScanData.critical_count || 0} files nguy hiểm được phát hiện.`,
                        icon: 'warning',
                        confirmText: 'Xóa Ngay',
                        action: () => this.performAutoFix()
                    },
                    'quarantine': {
                        title: 'Cách Ly Files Đáng Ngờ',
                        text: 'Di chuyển files đáng ngờ vào thư mục cách ly để kiểm tra sau.',
                        icon: 'info',
                        confirmText: 'Cách Ly',
                        action: () => this.showDemo('Cách ly files thành công! Files đã được di chuyển vào /quarantine/')
                    },
                    'fix_permissions': {
                        title: 'Sửa Quyền Files',
                        text: 'Thiết lập lại quyền truy cập an toàn cho tất cả files PHP.',
                        icon: 'info',
                        confirmText: 'Sửa Quyền',
                        action: () => this.showDemo('Đã thiết lập quyền 644 cho files PHP và 755 cho thư mục!')
                    },
                    'update_htaccess': {
                        title: 'Cập Nhật .htaccess',
                        text: 'Cập nhật rules bảo mật trong file .htaccess.',
                        icon: 'info',
                        confirmText: 'Cập Nhật',
                        action: () => this.showDemo('Đã cập nhật .htaccess với rules bảo mật mới!')
                    },
                    'clean_logs': {
                        title: 'Dọn Dẹp Logs',
                        text: 'Xóa logs cũ và tối ưu hóa hệ thống.',
                        icon: 'info',
                        confirmText: 'Dọn Dẹp',
                        action: () => this.showDemo('Đã dọn dẹp 15 MB logs cũ và tối ưu hệ thống!')
                    },
                    'auto_fix_all': {
                        title: 'Khắc Phục Toàn Bộ',
                        text: 'Thực hiện tất cả các biện pháp khắc phục tự động.',
                        icon: 'warning',
                        confirmText: 'Khắc Phục Tất Cả',
                        action: () => this.performAutoFix()
                    },
                    'schedule_scan': {
                        title: 'Lên Lịch Quét',
                        text: 'Thiết lập lịch quét tự động hàng ngày.',
                        icon: 'info',
                        confirmText: 'Thiết Lập',
                        action: () => this.showDemo('Đã thiết lập lịch quét tự động lúc 2:00 AM hàng ngày!')
                    }
                };

                const actionConfig = actions[action];
                if (!actionConfig) return;

                const result = await Swal.fire({
                    title: actionConfig.title,
                    text: actionConfig.text,
                    icon: actionConfig.icon,
                    showCancelButton: true,
                    confirmButtonColor: action === 'delete_critical' || action === 'auto_fix_all' ? '#E53E3E' : 'var(--primary-blue)',
                    cancelButtonColor: 'var(--text-light)',
                    confirmButtonText: actionConfig.confirmText,
                    cancelButtonText: 'Hủy'
                });

                if (result.isConfirmed) {
                    actionConfig.action();
                }
            }

            showDemo(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Demo - Thành Công!',
                    text: message,
                    confirmButtonColor: 'var(--success-text)',
                    timer: 3000,
                    timerProgressBar: true
                });
            }

            async deleteSingleFile(filePath, index) {
                const result = await Swal.fire({
                    title: 'XÓA FILE ĐỘC HẠI?',
                    html: `<strong style="color: var(--danger-text);">CẢNH BÁO:</strong> Sẽ xóa vĩnh viễn file:<br><br><code style="color: var(--warning-text); background: var(--warning-bg); padding: 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">${filePath}</code>`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--danger-text)',
                    cancelButtonColor: 'var(--text-light)',
                    confirmButtonText: 'XÓA NGAY',
                    cancelButtonText: 'Hủy',
                    dangerMode: true
                });
                
                if (result.isConfirmed) {
                    this.performSingleFileDeletion(filePath, index);
                }
            }

            async performSingleFileDeletion(filePath, index) {
                const deleteBtn = document.querySelector(`button[onclick*="${index}"]`);
                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Xóa...';
                }
                
                try {
                    const response = await fetch('?delete_malware=1', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ malware_files: [filePath] })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const text = await response.text();
                    console.log('Delete response:', text);
                    
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        throw new Error('Response không phải JSON hợp lệ: ' + text.substring(0, 200));
                    }
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'XÓA THÀNH CÔNG!',
                            text: `File ${filePath} đã được xóa thành công.`,
                            confirmButtonColor: 'var(--success-text)'
                        }).then(() => {
                            // Remove the card from display
                            const threatItem = deleteBtn.closest('.threat-item');
                            if (threatItem) {
                                threatItem.style.transition = 'all 0.3s ease';
                                threatItem.style.opacity = '0';
                                threatItem.style.transform = 'translateX(-100%)';
                                setTimeout(() => {
                                    threatItem.remove();
                                }, 300);
                            }
                        });
                    } else {
                        throw new Error(data.error || 'Unknown error');
                    }
                    
                } catch (error) {
                    console.error('Delete error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'LỖI XÓA FILE',
                        text: error.message,
                        confirmButtonColor: 'var(--danger-text)'
                    });
                } finally {
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Xóa';
                    }
                }
            }

            async performAutoFix() {
                if (!this.lastScanData || this.lastScanData.suspicious_count === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Không có lỗi để khắc phục',
                        text: 'Không phát hiện lỗi nào cần khắc phục!',
                        confirmButtonColor: 'var(--primary-blue)'
                    });
                    return;
                }
                
                const fixDropdown = document.getElementById('fixDropdown');
                fixDropdown.disabled = true;
                fixDropdown.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang Khắc Phục...';
                
                try {
                    const response = await fetch('?autofix=1', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(this.lastScanData)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const text = await response.text();
                    console.log('Auto-fix response:', text);
                    
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        throw new Error('Response không phải JSON hợp lệ: ' + text.substring(0, 200));
                    }
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Khắc Phục Thành Công!',
                            html: `<div style="text-align: left; font-size: 0.9rem;">` +
                                  `<strong>Files đã sửa:</strong> ${data.fixed_files}<br>` +
                                  `<strong>Files độc hại đã xóa:</strong> ${data.deleted_files || 0}<br>` +
                                  `<strong>Lỗi đã khắc phục:</strong> ${data.fixes_applied}<br>` +
                                  `<strong>Backup:</strong> ${data.backup_created ? '✅ Đã tạo' : '❌ Không có'}` +
                                  `</div>`,
                            confirmButtonColor: 'var(--success-text)'
                        }).then(() => {
                            // Auto scan lại sau khi fix
                            this.startScan();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi Khắc Phục',
                            text: data.error || 'Unknown error',
                            confirmButtonColor: 'var(--danger-text)'
                        });
                    }
                    
                } catch (error) {
                    console.error('Auto-fix error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi Khắc Phục',
                        text: error.message,
                        confirmButtonColor: 'var(--danger-text)'
                    });
                } finally {
                    fixDropdown.disabled = false;
                    fixDropdown.innerHTML = '<i class="fas fa-tools"></i> Khắc Phục';
                }
            }
        }

        // Initialize scanner when page loads
        let scanner;
        document.addEventListener('DOMContentLoaded', () => {
            scanner = new SecurityScanner();
            
            // Make scanner globally accessible
            window.scanner = scanner;
        });
    </script>
</body>
</html> 