<?php
/**
 * Enterprise Security Scanner - Professional Version (PHP 5.6+ Compatible)
 * Author: Hiệp Nguyễn
 * Facebook: https://www.facebook.com/G.N.S.L.7/
 * Version: 3.0 Enterprise - PHP 5.6+ Compatible
 * Date: June 24, 2025
 */

// PHP 5.6+ Compatibility check
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    die('This scanner requires PHP 5.6 or higher. Current version: ' . PHP_VERSION);
}

// Compatibility function for getting client IP
function getClientIP() {
    if (isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'unknown';
}

// Test JSON endpoint
if (isset($_GET['test']) && $_GET['test'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    
    // Check if JSON_UNESCAPED_UNICODE is available (PHP 5.4+)
    $json_flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $json_flags = JSON_UNESCAPED_UNICODE;
    }
    
    echo json_encode(array('status' => 'ok', 'message' => 'JSON test successful'), $json_flags);
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
        
        // Check if JSON_UNESCAPED_UNICODE is available
        $json_flags = 0;
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $json_flags = JSON_UNESCAPED_UNICODE;
        }
        
        echo json_encode(array(
            'success' => true,
            'deleted_files' => $deleteResults['deleted_files'],
            'failed_files' => $deleteResults['failed_files'],
            'backup_created' => $deleteResults['backup_created'],
            'details' => $deleteResults['details']
        ), $json_flags);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ), $json_flags);
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
        
        // Check if JSON_UNESCAPED_UNICODE is available
        $json_flags = 0;
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $json_flags = JSON_UNESCAPED_UNICODE;
        }
        
        echo json_encode(array(
            'success' => true,
            'fixed_files' => $fixResults['fixed_files'],
            'fixes_applied' => $fixResults['fixes_applied'],
            'deleted_files' => $fixResults['deleted_files'],
            'backup_created' => $fixResults['backup_created'],
            'details' => $fixResults['details']
        ), $json_flags);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ), $json_flags);
    }
    
    exit;
}

// Real-time scan progress endpoint
if (isset($_GET['scan_progress']) && $_GET['scan_progress'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Read progress from temp file (more reliable than session for real-time)
    $progress_file = './logs/scan_progress.json';
    $progress = array(
        'current_file' => '',
        'scanned_count' => 0,
        'total_estimate' => 100,
        'is_scanning' => false,
        'percentage' => 0
    );
    
    if (file_exists($progress_file)) {
        $progress_data = @file_get_contents($progress_file);
        if ($progress_data) {
            $decoded = json_decode($progress_data, true);
            if ($decoded) {
                $progress = $decoded;
            }
        }
    }
    
    // Check if JSON_UNESCAPED_UNICODE is available
    $json_flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $json_flags = JSON_UNESCAPED_UNICODE;
    }
    
    echo json_encode($progress, $json_flags);
    exit;
}

if (isset($_GET['scan']) && $_GET['scan'] === '1') {
    // Initialize progress file
    if (!file_exists('./logs')) {
        @mkdir('./logs', 0755, true);
    }
    
    $progress_file = './logs/scan_progress.json';
    $initial_progress = array(
        'current_file' => 'Khởi tạo scanner...',
        'scanned_count' => 0,
        'total_estimate' => 100,
        'is_scanning' => true,
        'percentage' => 0,
        'start_time' => time()
    );
    
    // Check if JSON_UNESCAPED_UNICODE is available
    $json_flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $json_flags = JSON_UNESCAPED_UNICODE;
    }
    
    @file_put_contents($progress_file, json_encode($initial_progress, $json_flags));
    
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
        $critical_malware_patterns = array(
            'eval(' => 'Code execution vulnerability',
            'goto ' => 'Control flow manipulation',
            'base64_decode(' => 'Encoded payload execution',
            'gzinflate(' => 'Compressed malware payload',
            'str_rot13(' => 'String obfuscation technique',
            '$_F=__FILE__;' => 'File system manipulation',
            'readdir(' => 'Directory traversal attempt',
            '<?php eval' => 'Direct PHP code injection'
        );

        // Suspicious file extensions and empty files
        $suspicious_file_patterns = array(
            '.php.jpg' => 'Disguised PHP file with image extension',
            '.php.png' => 'Disguised PHP file with image extension',
            '.php.gif' => 'Disguised PHP file with image extension',
            '.php.jpeg' => 'Disguised PHP file with image extension',
            '.phtml' => 'Alternative PHP extension',
            '.php3' => 'Legacy PHP extension',
            '.php4' => 'Legacy PHP extension',
            '.php5' => 'Legacy PHP extension'
        );

        // Severe patterns for uploads and dangerous functions
        $severe_patterns = array(
            'move_uploaded_file(' => 'File upload without validation',
            'exec(' => 'System command execution',
            'system(' => 'Direct system call',
            'shell_exec(' => 'Shell command execution',
            'passthru(' => 'Command output bypass',
            'proc_open(' => 'Process creation',
            'popen(' => 'Pipe command execution'
        );

        // Warning patterns for filemanager and normal functions (MEDIUM SEVERITY - ORANGE/YELLOW)
        $warning_patterns = array(
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
        );

        // Directories to scan (including filemanager now)
        $directories = array('./sources', './admin', './uploads', './virus-files', './');
        $suspicious_files = array();
        $critical_files = array();
        $severe_files = array();
        $warning_files = array();
        $filemanager_files = array();
        $scanned_files = 0;
        $max_files = 2000; // Increased limit

        function scanFileWithLineNumbers($file_path, $patterns) {
            if (!file_exists($file_path) || !is_readable($file_path)) {
                return array();
            }
            
            $content = @file_get_contents($file_path);
            if ($content === false) {
                return array();
            }
            
            $lines = explode("\n", $content);
            $issues = array();
            
            foreach ($patterns as $pattern => $description) {
                foreach ($lines as $lineNumber => $line) {
                    if (stripos($line, $pattern) !== false) {
                        $issues[] = array(
                            'pattern' => $pattern,
                            'description' => $description,
                            'line' => $lineNumber + 1,
                            'code_snippet' => trim($line)
                        );
                    }
                }
            }
            
            return $issues;
        }

        function checkSuspiciousFile($file_path, $suspicious_patterns) {
            $issues = array();
            $filename = basename($file_path);
            
            // Check for suspicious file extensions
            foreach ($suspicious_patterns as $pattern => $description) {
                if (stripos($filename, $pattern) !== false) {
                    $issues[] = array(
                        'pattern' => $pattern,
                        'description' => $description,
                        'line' => 0,
                        'code_snippet' => 'Suspicious filename: ' . $filename
                    );
                }
            }
            
            // Check for empty or near-empty PHP files
            if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'php') {
                $content = @file_get_contents($file_path);
                if ($content !== false) {
                    $content_trimmed = trim($content);
                    $content_no_php_tags = str_replace(array('<?php', '<?', '?>'), '', $content_trimmed);
                    $content_clean = trim($content_no_php_tags);
                    
                    // If file is empty or only contains PHP tags
                    if (empty($content_clean) || strlen($content_clean) < 10) {
                        $issues[] = array(
                            'pattern' => 'empty_php_file',
                            'description' => 'Empty or suspicious PHP file',
                            'line' => 1,
                            'code_snippet' => 'File content: ' . substr($content_trimmed, 0, 50) . '...'
                        );
                    }
                }
            }
            
            return $issues;
        }

        function scanDirectory($dir, $critical_patterns, $severe_patterns, $warning_patterns) {
            global $suspicious_files, $scanned_files, $critical_files, $severe_files, $warning_files, $filemanager_files, $max_files, $suspicious_file_patterns, $current_scanning_file;
            
            if (!is_dir($dir)) {
                return;
            }
            
            // Only exclude security_scan.php
            $exclude_files = array(
                'security_scan.php'
            );
            
            try {
                // PHP 5.6+ compatible directory iteration
                if (class_exists('RecursiveIteratorIterator') && class_exists('RecursiveDirectoryIterator')) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                } else {
                    // Fallback for older PHP versions
                    $iterator = new DirectoryIterator($dir);
                }
                
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
                                      in_array($extension, array('phtml', 'php3', 'php4', 'php5'));
                        
                        if ($should_scan) {
                            $scanned_files++;
                            
                            // Update real-time progress
                            $current_scanning_file = $file_path;
                            $percentage = min(100, ($scanned_files / $max_files) * 100);
                            
                            // Update progress file for real-time display
                            $progress_file = './logs/scan_progress.json';
                            $progress_data = array(
                                'current_file' => $file_path,
                                'scanned_count' => $scanned_files,
                                'total_estimate' => $max_files,
                                'is_scanning' => true,
                                'percentage' => round($percentage, 1),
                                'directory' => basename($dir),
                                'last_update' => time()
                            );
                            
                            // Check if JSON_UNESCAPED_UNICODE is available
                            $json_flags = 0;
                            if (defined('JSON_UNESCAPED_UNICODE')) {
                                $json_flags = JSON_UNESCAPED_UNICODE;
                            }
                            
                            @file_put_contents($progress_file, json_encode($progress_data, $json_flags));
                            
                            // Determine file category
                            $is_virus_file = strpos($file_path, 'virus-files') !== false;
                            $is_filemanager = strpos($file_path, 'admin/filemanager') !== false;
                            
                            // Check for suspicious file extensions and empty files FIRST
                            $suspicious_issues = checkSuspiciousFile($file_path, $suspicious_file_patterns);
                            if (!empty($suspicious_issues)) {
                                $suspicious_files[] = array(
                                    'path' => $file_path,
                                    'issues' => $suspicious_issues,
                                    'severity' => 'critical',
                                    'priority' => 1,
                                    'category' => 'suspicious_file'
                                );
                                $critical_files[] = $file_path;
                            } else {
                                // Check for critical malware patterns (HIGHEST PRIORITY)
                                $critical_issues = scanFileWithLineNumbers($file_path, $critical_patterns);
                                if (!empty($critical_issues)) {
                                    $suspicious_files[] = array(
                                        'path' => $file_path,
                                        'issues' => $critical_issues,
                                        'severity' => 'critical',
                                        'priority' => 1,
                                        'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system')
                                    );
                                    $critical_files[] = $file_path;
                                } else {
                                    // Check for severe patterns
                                    $severe_issues = scanFileWithLineNumbers($file_path, $severe_patterns);
                                    if (!empty($severe_issues)) {
                                        $severity = $is_virus_file ? 'critical' : 'severe';
                                        $priority = $is_virus_file ? 1 : 2;
                                        
                                        $suspicious_files[] = array(
                                            'path' => $file_path,
                                            'issues' => $severe_issues,
                                            'severity' => $severity,
                                            'priority' => $priority,
                                            'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system')
                                        );
                                        
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
                                            
                                            $suspicious_files[] = array(
                                                'path' => $file_path,
                                                'issues' => $warning_issues,
                                                'severity' => $severity,
                                                'priority' => $priority,
                                                'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system')
                                            );
                                            
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

        // Mark scan as completed
        $progress_file = './logs/scan_progress.json';
        $final_progress = array(
            'current_file' => 'Hoàn tất scan!',
            'scanned_count' => $scanned_files,
            'total_estimate' => $scanned_files,
            'is_scanning' => false,
            'percentage' => 100,
            'completed' => true,
            'end_time' => time()
        );
        
        // Check if JSON_UNESCAPED_UNICODE is available
        $json_flags = 0;
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $json_flags = JSON_UNESCAPED_UNICODE;
        }
        
        @file_put_contents($progress_file, json_encode($final_progress, $json_flags));

        // Log scan results
        $client_ip = getClientIP();
        $log_data = date('Y-m-d H:i:s') . " - Enterprise Security scan completed. Scanned: $scanned_files files, Found: " . count($suspicious_files) . " threats. IP: " . $client_ip . "\n";
        if (!file_exists('./logs')) {
            @mkdir('./logs', 0755, true);
        }
        @file_put_contents('./logs/security_scan_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);

        // Return results
        $response = array(
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
        );
        
        // Clean output buffer and send JSON
        ob_clean();
        
        // Check if JSON_UNESCAPED_UNICODE is available
        $json_flags = 0;
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $json_flags = JSON_UNESCAPED_UNICODE;
        }
        
        echo json_encode($response, $json_flags);
        
    } catch (Exception $e) {
        // Clean output buffer for error response
        ob_clean();
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => 'Scan error: ' . $e->getMessage()
        ), $json_flags);
    }
    
    exit;
}

function deleteMalwareFiles($malware_files) {
    $deleted_files = 0;
    $failed_files = array();
    $backup_created = false;
    $details = array();
    
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
            $client_ip = getClientIP();
            $log_data = date('Y-m-d H:i:s') . " - MALWARE DELETED: {$file_path}. IP: " . $client_ip . "\n";
            if (!file_exists('./logs')) {
                @mkdir('./logs', 0755, true);
            }
            @file_put_contents('./logs/malware_delete_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);
        } else {
            $failed_files[] = $file_path . ' (deletion failed)';
        }
    }
    
    return array(
        'deleted_files' => $deleted_files,
        'failed_files' => $failed_files,
        'backup_created' => $backup_created,
        'details' => $details
    );
}

function performAutoFix($scan_data) {
    $fixed_files = 0;
    $fixes_applied = 0;
    $deleted_files = 0;
    $backup_created = false;
    $details = array();
    
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
                    $client_ip = getClientIP();
                    $log_data = date('Y-m-d H:i:s') . " - AUTO-FIX DELETED: {$full_path}. IP: " . $client_ip . "\n";
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
    $client_ip = getClientIP();
    $log_data = date('Y-m-d H:i:s') . " - Auto-fix completed. Fixed files: $fixed_files, Fixes applied: $fixes_applied, Deleted: $deleted_files, Backup: " . ($backup_created ? 'Yes' : 'No') . ". IP: " . $client_ip . "\n";
    if (!file_exists('./logs')) {
        @mkdir('./logs', 0755, true);
    }
    @file_put_contents('./logs/security_fix_' . date('Y-m-d') . '.log', $log_data, FILE_APPEND | LOCK_EX);
    
    return array(
        'fixed_files' => $fixed_files,
        'fixes_applied' => $fixes_applied,
        'deleted_files' => $deleted_files,
        'backup_created' => $backup_created,
        'details' => $details
    );
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Security Scanner - Hiệp Nguyễn (PHP 5.6+)</title>
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

        /* Hero Section - Futuristic */
        .hero-section {
            background: linear-gradient(135deg, 
                #667eea 0%, 
                #4fc3f7 25%, 
                #29b6f6 50%, 
                #1976d2 75%, 
                #0d47a1 100%);
            position: relative;
            border-radius: 20px;
            padding: 50px 20px;
            margin-bottom: 25px;
            overflow: hidden;
           
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }

        .hero-section:hover {
            transform: translateY(-2px);
            
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="hexagon" width="30" height="26" patternUnits="userSpaceOnUse"><polygon points="15,2 25,8 25,18 15,24 5,18 5,8" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23hexagon)"/></svg>');
            opacity: 0.6;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { 
                background-position: 0% 0%, 100% 100%, 0% 0%;
            }
            50% { 
                background-position: 100% 100%, 0% 0%, 50% 50%;
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #ffffff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 20px;
            font-weight: 400;
            line-height: 1.6;
        }

        .hero-features {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .feature-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .feature-item:hover::before {
            left: 100%;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px) scale(1.05);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.2),
                0 0 20px rgba(255, 255, 255, 0.1);
        }

        .feature-item:active {
            transform: translateY(-1px) scale(1.02);
        }

        .feature-icon {
            font-size: 1.2rem;
        }

        .author-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .author-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.12);
            padding: 15px 25px;
            border-radius: 60px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .author-profile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, 
                rgba(255, 255, 255, 0.1), 
                rgba(255, 255, 255, 0.05), 
                rgba(255, 255, 255, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .author-profile:hover::before {
            opacity: 1;
        }

        .author-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px) scale(1.02);
            color: white;
            text-decoration: none;
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.3),
                0 0 25px rgba(255, 255, 255, 0.1);
        }

        .author-profile:active {
            transform: translateY(-1px) scale(1.01);
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.4);
            object-fit: cover;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 2;
        }

        .author-avatar::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            background: linear-gradient(45deg, #00bcd4, #2196f3, #3f51b5, #9c27b0);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
            animation: borderRotate 3s linear infinite;
        }

        .author-profile:hover .author-avatar::before {
            opacity: 1;
        }

        .author-profile:hover .author-avatar {
            transform: scale(1.1) rotate(5deg);
            border-color: rgba(255, 255, 255, 0.6);
        }

        @keyframes borderRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .author-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .author-info p {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .tech-badges {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .tech-badge {
            background: rgba(255, 255, 255, 0.12);
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .tech-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .tech-badge:hover::before {
            left: 100%;
        }

        .tech-badge:hover {
            transform: translateY(-2px) scale(1.05);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .php-version {
            background: linear-gradient(45deg, #667eea, #764ba2);
            box-shadow: 0 0 15px rgba(102, 126, 234, 0.3);
        }

        .php-version:hover {
            box-shadow: 0 0 25px rgba(102, 126, 234, 0.5);
        }

        .enterprise-badge {
            background: linear-gradient(45deg, #fd79a8, #e84393);
            animation: pulseGlowAdvanced 3s ease-in-out infinite;
            box-shadow: 0 0 15px rgba(253, 121, 168, 0.4);
        }

        .enterprise-badge:hover {
            animation-duration: 1s;
            box-shadow: 0 0 30px rgba(253, 121, 168, 0.7);
        }

        @keyframes pulseGlowAdvanced {
            0%, 100% { 
                box-shadow: 
                    0 0 15px rgba(253, 121, 168, 0.4),
                    0 0 30px rgba(253, 121, 168, 0.2);
                transform: scale(1);
            }
            50% { 
                box-shadow: 
                    0 0 25px rgba(253, 121, 168, 0.7),
                    0 0 50px rgba(253, 121, 168, 0.4),
                    0 0 80px rgba(253, 121, 168, 0.2);
                transform: scale(1.02);
            }
        }

        /* Floating animations */
        .floating-icons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-icon {
            position: absolute;
            font-size: 1.8rem;
            color: rgba(255, 255, 255, 0.15);
            animation: floatNeon 8s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .floating-icon:nth-child(1) { 
            top: 15%; left: 8%; 
            animation-delay: -1s; 
            color: rgba(0, 188, 212, 0.3);
        }
        .floating-icon:nth-child(2) { 
            top: 65%; left: 88%; 
            animation-delay: -3s; 
            color: rgba(33, 150, 243, 0.3);
        }
        .floating-icon:nth-child(3) { 
            top: 35%; left: 15%; 
            animation-delay: -2s; 
            color: rgba(63, 81, 181, 0.3);
        }
        .floating-icon:nth-child(4) { 
            top: 85%; left: 75%; 
            animation-delay: -4s; 
            color: rgba(156, 39, 176, 0.3);
        }
        .floating-icon:nth-child(5) { 
            top: 8%; left: 75%; 
            animation-delay: -0.5s; 
            color: rgba(76, 175, 80, 0.3);
        }

        @keyframes floatNeon {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1);
                opacity: 0.15;
                filter: blur(0px);
            }
            25% {
                opacity: 0.4;
                filter: blur(1px);
                text-shadow: 0 0 10px currentColor;
            }
            50% { 
                transform: translateY(-25px) rotate(180deg) scale(1.1);
                opacity: 0.6;
                filter: blur(0px);
                text-shadow: 0 0 20px currentColor, 0 0 30px currentColor;
            }
            75% {
                opacity: 0.3;
                filter: blur(1px);
                text-shadow: 0 0 15px currentColor;
            }
        }

        .hero-section:hover .floating-icon {
            animation-duration: 4s;
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
            position: relative;
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
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-status {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .scan-number {
            position: absolute;
            top: 2px;
            right: 4px;
            font-size: 0.6rem;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.7);
            padding: 1px 4px;
            border-radius: 6px;
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
            position: relative;
        }

        .threat-item:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-medium);
        }

        .file-item:hover .file-path {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .file-item.slideIn {
            animation: slideInFromRight 0.3s ease-out;
        }

        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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
                gap: 8px;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 30px 15px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .hero-features {
                gap: 15px;
            }
            
            .feature-item {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .author-section {
                flex-direction: column;
                gap: 15px;
            }
            
            .author-profile {
                padding: 10px 16px;
            }
            
            .author-avatar {
                width: 40px;
                height: 40px;
            }
            
            .tech-badges {
                gap: 8px;
            }
            
            .bento-grid {
                grid-template-columns: 1fr;
            }
            
            .bento-item.span-2,
            .bento-item.span-3 {
                grid-column: span 1;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .results-grid {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            
            .threat-item {
                padding: 6px;
                margin-bottom: 4px;
            }
            
            .threat-path {
                font-size: 0.7rem;
            }
            
            .delete-btn {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                padding: 25px 10px;
            }
            
            .hero-title {
                font-size: 1.7rem;
                margin-bottom: 10px;
            }
            
            .hero-subtitle {
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            
            .hero-features {
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .feature-item {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
            
            .feature-icon {
                font-size: 1rem;
            }
            
            .author-profile {
                padding: 8px 12px;
            }
            
            .author-avatar {
                width: 35px;
                height: 35px;
            }
            
            .author-info h4 {
                font-size: 0.9rem;
            }
            
            .author-info p {
                font-size: 0.75rem;
            }
            
            .tech-badge {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
            
            .container-fluid {
                padding: 8px;
            }
            
            .bento-grid {
                gap: 8px;
            }
            
            .bento-item {
                padding: 12px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            
            .threat-group {
                padding: 8px;
            }
            
            .threat-path {
                font-size: 0.65rem;
                flex-wrap: wrap;
            }
            
            .delete-btn {
                font-size: 0.6rem;
                padding: 2px 4px;
            }
            
            .scan-number {
                display: none; /* Hide scan number on mobile */
            }
            
            .file-path {
                max-width: 150px;
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
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="floating-icons">
                <div class="floating-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="floating-icon"><i class="fas fa-lock"></i></div>
                <div class="floating-icon"><i class="fas fa-bug"></i></div>
                <div class="floating-icon"><i class="fas fa-virus-slash"></i></div>
                <div class="floating-icon"><i class="fas fa-server"></i></div>
            </div>
            
            <div class="hero-content">
                <h1 class="hero-title">
                    <i class="fas fa-shield-halved"></i> Enterprise Security Scanner
                </h1>
                <p class="hero-subtitle">
                    Công cụ quét malware và backdoor chuyên nghiệp cho doanh nghiệp<br>
                    Phát hiện và loại bỏ các mối đe dọa bảo mật một cách hiệu quả
                </p>
                
                <div class="hero-features">
                    <div class="feature-item">
                        <i class="feature-icon fas fa-search-plus"></i>
                        <span>Deep Scan</span>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon fas fa-bolt"></i>
                        <span>Real-time</span>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon fas fa-brain"></i>
                        <span>AI Detection</span>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon fas fa-tools"></i>
                        <span>Auto-Fix</span>
                    </div>
                </div>
                
                <div class="author-section">
                    <a href="https://www.facebook.com/G.N.S.L.7/" target="_blank" class="author-profile">
                        <img src="https://scontent.fsgn5-5.fna.fbcdn.net/v/t39.30808-6/467745352_8564281790291501_4763340932413705788_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=6ee11a&_nc_ohc=YVJLGzP9HhEQ7kNvwHF5z1y&_nc_oc=AdmgFUyetOC2rMzv2OXAs1lWuzlomsOxNuE2EqvVsc6UcbSMdGDuegCYG89180aIibs&_nc_zt=23&_nc_ht=scontent.fsgn5-5.fna&_nc_gid=QtnVEWmkr6Ws8U65q_KqFA&oh=00_AfQ2LJYK7P2O1iPsn_Gf84p_x3SKl5rp4C_C52N8_NrcWw&oe=6879BA95" 
                             alt="Hiệp Nguyễn Avatar" 
                             class="author-avatar"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2240%22 fill=%22%234A90E2%22/><text x=%2250%22 y=%2258%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2232%22 font-weight=%22bold%22>H</text></svg>'">
                        <div class="author-info">
                            <h4>🚀 Hiệp Nguyễn</h4>
                            <p><i class="fab fa-facebook-f"></i> Security Expert & Futuristic Developer</p>
                        </div>
                    </a>
                    
                    <div class="tech-badges">
                        <div class="tech-badge php-version">
                            <i class="fab fa-php"></i>
                            <span>PHP <?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="tech-badge enterprise-badge">
                            <i class="fas fa-crown"></i>
                            <span>Enterprise</span>
                        </div>
                        <div class="tech-badge">
                            <i class="fas fa-calendar-alt"></i>
                            <span>2025</span>
                        </div>
                    </div>
                </div>
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
        // PHP 5.6+ Compatible JavaScript
        var SecurityScanner = function() {
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
        };

        SecurityScanner.prototype.init = function() {
            var self = this;
            document.getElementById('scanBtn').addEventListener('click', function() {
                self.startScan();
            });
            
            // Initialize Bootstrap tooltips
            this.initTooltips();
        };

        SecurityScanner.prototype.initTooltips = function() {
            // Initialize tooltips for dynamically created elements
            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        };

        SecurityScanner.prototype.startScan = function() {
            if (this.isScanning) return;
            
            this.isScanning = true;
            this.scannedFiles = 0;
            this.suspiciousFiles = 0;
            this.criticalFiles = 0;
            this.scanStartTime = Date.now();
            
            var scanBtn = document.getElementById('scanBtn');
            var progressSection = document.getElementById('progressSection');
            var resultsPanel = document.getElementById('resultsPanel');
            
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
            var self = this;
            setTimeout(function() {
                self.performScan();
            }, 1000);
        };

        SecurityScanner.prototype.startFileSimulation = function() {
            // Start real-time progress polling
            var self = this;
            console.log('Starting real-time scanner polling...'); // Debug log
            this.progressPollingInterval = setInterval(function() {
                self.checkScanProgress();
            }, 500); // Poll every 500ms for better performance
        };

        SecurityScanner.prototype.checkScanProgress = function() {
            var self = this;
            
            if (!this.isScanning) {
                clearInterval(this.progressPollingInterval);
                return;
            }
            
            // Create XMLHttpRequest for progress check
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?scan_progress=1&t=' + Date.now(), true); // Add timestamp to prevent caching
            xhr.setRequestHeader('Cache-Control', 'no-cache');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var progress = JSON.parse(xhr.responseText);
                            console.log('Progress update:', progress); // Debug log
                            self.updateRealTimeProgress(progress);
                        } catch (e) {
                            console.log('Progress parse error:', e, xhr.responseText.substring(0, 100));
                        }
                    } else {
                        console.log('Progress request failed:', xhr.status, xhr.statusText);
                    }
                }
            };
            
            xhr.onerror = function() {
                console.log('Progress request error');
            };
            
            xhr.send();
        };

        SecurityScanner.prototype.updateRealTimeProgress = function(progress) {
            // Update scanner content with current file
            if (progress.current_file && progress.is_scanning) {
                this.addRealTimeFile(progress.current_file, progress.scanned_count);
            }
            
            // Update stats
            this.scannedFiles = progress.scanned_count || this.scannedFiles;
            this.updateStats();
            
            // Update progress bar
            var progressBar = document.getElementById('progressBar');
            var progressPercentage = document.getElementById('progressPercentage');
            var currentAction = document.getElementById('currentAction');
            
            if (progressBar && progress.percentage !== undefined) {
                progressBar.style.width = progress.percentage + '%';
                progressPercentage.textContent = Math.round(progress.percentage) + '%';
            }
            
            if (currentAction && progress.current_file) {
                var fileName = progress.current_file.split('/').pop();
                currentAction.innerHTML = '<i class="fas fa-spinner pulse" style="color: var(--primary-blue);"></i> ' + 
                                        'Quét: ' + fileName;
            }
            
            // Check if scan completed
            if (progress.completed || !progress.is_scanning) {
                clearInterval(this.progressPollingInterval);
            }
        };

        SecurityScanner.prototype.addRealTimeFile = function(filePath, scanCount) {
            var scannerContent = document.getElementById('scannerContent');
            
            // Clear "empty" state if exists
            if (scannerContent.querySelector('.scanner-empty')) {
                scannerContent.innerHTML = '';
            }
            
            // Create file item
            var fileItem = document.createElement('div');
            fileItem.className = 'file-item slideIn';
            
            // Determine if file is suspicious based on path patterns
            var isSuspicious = this.isFileSuspicious(filePath);
            var statusClass = isSuspicious ? 'status-suspicious' : 'status-clean';
            var statusText = isSuspicious ? 'Checking...' : 'Clean';
            var iconColor = isSuspicious ? 'var(--warning-text)' : 'var(--success-text)';
            
            fileItem.innerHTML = 
                '<div class="file-icon">' +
                    '<i class="fas fa-file-code" style="color: ' + iconColor + ';"></i>' +
                '</div>' +
                '<div class="file-path">' + filePath + '</div>' +
                '<div class="file-status ' + statusClass + '">' +
                    statusText +
                '</div>' +
                '<div class="scan-number" style="font-size: 0.6rem; color: var(--text-muted);">' +
                    '#' + scanCount +
                '</div>';
            
            scannerContent.appendChild(fileItem);
            scannerContent.scrollTop = scannerContent.scrollHeight;
            
            // Keep only last 15 items for performance
            while (scannerContent.children.length > 15) {
                scannerContent.removeChild(scannerContent.firstChild);
            }
        };

        SecurityScanner.prototype.isFileSuspicious = function(filePath) {
            // Check for suspicious patterns
            var suspiciousPatterns = [
                'virus-files',
                '.php.jpg',
                '.php.png', 
                '.php.gif',
                'cache.php',
                'eval',
                'base64',
                'shell_exec',
                'admin/filemanager'
            ];
            
            for (var i = 0; i < suspiciousPatterns.length; i++) {
                if (filePath.indexOf(suspiciousPatterns[i]) !== -1) {
                    return true;
                }
            }
            
            return false;
        };

        SecurityScanner.prototype.simulateProgress = function() {
            var progress = 0;
            var progressBar = document.getElementById('progressBar');
            var progressText = document.getElementById('progressText');
            var progressPercentage = document.getElementById('progressPercentage');
            var currentAction = document.getElementById('currentAction');
            
            var actions = [
                'Khởi tạo scanner...',
                'Quét sources...',
                'Phân tích admin...',
                'Kiểm tra filemanager...',
                'Quét virus-files...',
                'Hoàn thiện...'
            ];
            
            var actionIndex = 0;
            var self = this;
            
            this.progressInterval = setInterval(function() {
                progress += Math.random() * 8 + 4;
                if (progress > 100) progress = 100;
                
                progressBar.style.width = progress + '%';
                progressPercentage.textContent = Math.round(progress) + '%';
                
                if (Math.floor(progress / 17) > actionIndex && actionIndex < actions.length - 1) {
                    actionIndex++;
                    currentAction.textContent = actions[actionIndex];
                }
                
                if (progress >= 100) {
                    clearInterval(self.progressInterval);
                    progressText.textContent = 'Hoàn tất!';
                    currentAction.textContent = 'Tạo báo cáo...';
                }
            }, 150);
        };

        SecurityScanner.prototype.startSpeedCounter = function() {
            var self = this;
            this.speedInterval = setInterval(function() {
                var elapsed = (Date.now() - self.scanStartTime) / 1000;
                document.getElementById('scanTime').textContent = Math.round(elapsed) + 's';
            }, 500);
        };

        SecurityScanner.prototype.addFileToScanner = function(filePath, isClean) {
            var scannerContent = document.getElementById('scannerContent');
            
            var fileItem = document.createElement('div');
            fileItem.className = 'file-item slideIn';
            fileItem.innerHTML = 
                '<div class="file-icon">' +
                    '<i class="fas fa-file-code" style="color: ' + (isClean ? 'var(--success-text)' : 'var(--danger-text)') + ';"></i>' +
                '</div>' +
                '<div class="file-path">' + filePath + '</div>' +
                '<div class="file-status ' + (isClean ? 'status-clean' : 'status-suspicious') + '">' +
                    (isClean ? 'Clean' : 'Threat') +
                '</div>';
            
            scannerContent.appendChild(fileItem);
            scannerContent.scrollTop = scannerContent.scrollHeight;
            
            // Keep only last 10 items
            while (scannerContent.children.length > 10) {
                scannerContent.removeChild(scannerContent.firstChild);
            }
        };

        SecurityScanner.prototype.updateStats = function() {
            document.getElementById('scannedFiles').textContent = this.scannedFiles;
            document.getElementById('suspiciousFiles').textContent = this.suspiciousFiles;
            document.getElementById('criticalFiles').textContent = this.criticalFiles;
        };

        SecurityScanner.prototype.performScan = function() {
            var self = this;
            
            // Create XMLHttpRequest for PHP 5.6+ compatibility
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?scan=1', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Cache-Control', 'no-cache');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var text = xhr.responseText;
                            console.log('Raw response received, length:', text.length);
                            
                            if (!text || text.trim() === '') {
                                throw new Error('Empty response from server');
                            }
                            
                            var data = JSON.parse(text);
                            console.log('Parsed data:', data);
                            self.displayResults(data);
                            
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            self.displayError('Response không phải JSON hợp lệ. Check console for details.');
                        }
                    } else {
                        self.displayError('Lỗi HTTP: ' + xhr.status + ' ' + xhr.statusText);
                    }
                }
            };
            
            xhr.onerror = function() {
                self.displayError('Lỗi kết nối mạng');
            };
            
            xhr.ontimeout = function() {
                self.displayError('Quét bị timeout. Vui lòng thử lại.');
            };
            
            xhr.timeout = 30000; // 30 second timeout
            xhr.send();
        };

        SecurityScanner.prototype.displayResults = function(data) {
            var self = this;
            
            // Update final stats with real data
            this.scannedFiles = data.scanned_files || this.scannedFiles;
            this.suspiciousFiles = data.suspicious_count || this.suspiciousFiles;
            this.criticalFiles = data.critical_count || this.criticalFiles;
            this.updateStats();
            
            // Show real scanned files
            if (data.suspicious_files && data.suspicious_files.length > 0) {
                // Show last few files scanned
                var lastFiles = data.suspicious_files.slice(-5);
                for (var i = 0; i < lastFiles.length; i++) {
                    var file = lastFiles[i];
                    var isClean = file.severity === 'warning' || file.category === 'filemanager';
                    self.addFileToScanner(file.path, isClean);
                }
            }
            
            // Store scan data for auto-fix
            this.lastScanData = data;
            
            setTimeout(function() {
                var resultsPanel = document.getElementById('resultsPanel');
                var scanResults = document.getElementById('scanResults');
                
                resultsPanel.classList.add('active');
                
                if (data.suspicious_count === 0) {
                    scanResults.innerHTML = 
                        '<div class="alert alert-success">' +
                            '<i class="fas fa-shield-check"></i>' +
                            '<div>' +
                                '<strong>Hệ thống an toàn!</strong><br>' +
                                '<small>Không phát hiện threat nào trong ' + data.scanned_files + ' files đã quét.</small>' +
                            '</div>' +
                        '</div>';
                    document.getElementById('fixDropdown').disabled = true;
                } else {
                    var resultHtml = 
                        '<div class="alert alert-danger">' +
                            '<i class="fas fa-exclamation-triangle"></i>' +
                            '<div>' +
                                '<strong>Phát hiện ' + data.suspicious_count + ' threats!</strong><br>' +
                                '<small>Trong đó có ' + (data.critical_count || 0) + ' threats nghiêm trọng cần xử lý ngay.</small>' +
                            '</div>' +
                        '</div>' +
                        '<div class="results-grid">';
                    
                    // Group files by category and severity
                    var groups = {
                        suspicious_file: { title: 'Files Đáng Ngờ (.php.jpg, Empty)', icon: 'fa-exclamation-circle', files: [] },
                        critical: { title: 'Files Virus/Malware Nguy Hiểm', icon: 'fa-skull-crossbones', files: [] },
                        filemanager: { title: 'Filemanager Functions', icon: 'fa-folder-open', files: [] },
                        warning: { title: 'Cảnh Báo Bảo Mật', icon: 'fa-exclamation-triangle', files: [] }
                    };
                    
                    for (var i = 0; i < data.suspicious_files.length; i++) {
                        var file = data.suspicious_files[i];
                        file.index = i;
                        
                        var isCritical = file.severity === 'critical';
                        var isFilemanager = file.category === 'filemanager';
                        var isSuspiciousFile = file.category === 'suspicious_file';
                        
                        if (isSuspiciousFile) {
                            groups.suspicious_file.files.push(file);
                        } else if (isCritical && !isFilemanager) {
                            groups.critical.files.push(file);
                        } else if (isFilemanager) {
                            groups.filemanager.files.push(file);
                        } else {
                            groups.warning.files.push(file);
                        }
                    }
                    
                    // Render groups
                    for (var groupKey in groups) {
                        var group = groups[groupKey];
                        if (group.files.length > 0) {
                            resultHtml += 
                                '<div class="threat-group ' + groupKey + '">' +
                                    '<div class="group-header ' + groupKey + '">' +
                                        '<i class="fas ' + group.icon + '"></i>' +
                                        '<span>' + group.title + ' (' + group.files.length + ')</span>' +
                                    '</div>';
                            
                            for (var j = 0; j < group.files.length; j++) {
                                var file = group.files[j];
                                var isCritical = (file.severity === 'critical' && file.category !== 'filemanager') || file.category === 'suspicious_file';
                                var tooltipContent = self.generateTooltipContent(file.issues);
                                var firstIssue = file.issues && file.issues.length > 0 ? file.issues[0] : null;
                                
                                resultHtml += 
                                    '<div class="threat-item" ' +
                                         'data-bs-toggle="tooltip" ' +
                                         'data-bs-placement="top" ' +
                                         'data-bs-html="true" ' +
                                         'title="' + tooltipContent + '">' +
                                        '<div class="threat-header">' +
                                            '<div class="threat-path">' +
                                                '<i class="fas fa-file-code"></i> ' + file.path +
                                                (firstIssue ? ' <span style="color: var(--warning-text); font-size: 0.7rem;">(dòng ' + firstIssue.line + ')</span>' : '') +
                                            '</div>' +
                                            (isCritical ? 
                                                '<button class="delete-btn" onclick="scanner.deleteSingleFile(\'' + file.path + '\', ' + file.index + ')">' +
                                                    '<i class="fas fa-trash-alt"></i> Xóa' +
                                                '</button>' 
                                                : '') +
                                        '</div>' +
                                        '<div class="threat-issues">' +
                                            file.issues.length + ' vấn đề phát hiện' +
                                            (firstIssue ? ' - <span style="color: var(--danger-text); font-weight: 600;">' + firstIssue.pattern + '</span>' : '') +
                                        '</div>' +
                                    '</div>';
                            }
                            
                            resultHtml += '</div>';
                        }
                    }
                    
                    resultHtml += '</div>';
                    
                    scanResults.innerHTML = resultHtml;
                    
                    // Initialize tooltips for new elements
                    setTimeout(function() {
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    }, 100);
                    
                    // Enable fix dropdown
                    document.getElementById('fixDropdown').disabled = false;
                }
                
                self.completeScan();
            }, 1000);
        };

        SecurityScanner.prototype.generateTooltipContent = function(issues) {
            if (!issues || issues.length === 0) return 'Không có thông tin chi tiết';
            
            var content = '<div style="text-align: left;">';
            for (var i = 0; i < issues.length; i++) {
                var issue = issues[i];
                content += '<div style="margin-bottom: 4px;">';
                content += '<strong>Dòng ' + issue.line + ':</strong> ' + issue.pattern + '<br>';
                content += '<small>' + issue.description + '</small><br>';
                content += '<code style="font-size: 0.7rem;">' + issue.code_snippet.substring(0, 50) + '...</code>';
                content += '</div>';
            }
            content += '</div>';
            
            return content.replace(/"/g, '&quot;');
        };

        // Editor functions removed - only show warning tooltip now

        SecurityScanner.prototype.displayError = function(message) {
            var resultsPanel = document.getElementById('resultsPanel');
            var scanResults = document.getElementById('scanResults');
            
            resultsPanel.classList.add('active');
            scanResults.innerHTML = 
                '<div class="alert alert-danger">' +
                    '<i class="fas fa-times-circle"></i>' +
                    '<div>' +
                        '<strong>Lỗi quét!</strong><br>' +
                        '<small>' + message + '</small>' +
                    '</div>' +
                '</div>';
            
            this.completeScan();
        };

        SecurityScanner.prototype.completeScan = function() {
            var self = this;
            
            clearInterval(this.speedInterval);
            clearInterval(this.fileSimulationInterval);
            clearInterval(this.progressPollingInterval); // Stop real-time polling
            
            setTimeout(function() {
                var scanBtn = document.getElementById('scanBtn');
                scanBtn.disabled = false;
                scanBtn.innerHTML = '<i class="fas fa-redo"></i> Quét Lại';
                document.getElementById('progressSection').classList.remove('active');
                self.isScanning = false;
                
                // Update final scanner message
                var currentAction = document.getElementById('currentAction');
                if (currentAction) {
                    currentAction.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success-text);"></i> ' + 
                                            'Scan hoàn tất!';
                }
            }, 1000);
        };

        SecurityScanner.prototype.performAction = function(action) {
            var self = this;
            
            if (!this.lastScanData || this.lastScanData.suspicious_count === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Không có dữ liệu để xử lý',
                    text: 'Vui lòng quét hệ thống trước khi thực hiện khắc phục!',
                    confirmButtonColor: 'var(--primary-blue)'
                });
                return;
            }

            var actions = {
                'delete_critical': {
                    title: 'Xóa Files Nguy Hiểm',
                    text: 'Sẽ xóa ' + (this.lastScanData.critical_count || 0) + ' files nguy hiểm được phát hiện.',
                    icon: 'warning',
                    confirmText: 'Xóa Ngay',
                    action: function() { self.performAutoFix(); }
                },
                'quarantine': {
                    title: 'Cách Ly Files Đáng Ngờ',
                    text: 'Di chuyển files đáng ngờ vào thư mục cách ly để kiểm tra sau.',
                    icon: 'info',
                    confirmText: 'Cách Ly',
                    action: function() { self.showDemo('Cách ly files thành công! Files đã được di chuyển vào /quarantine/'); }
                },
                'fix_permissions': {
                    title: 'Sửa Quyền Files',
                    text: 'Thiết lập lại quyền truy cập an toàn cho tất cả files PHP.',
                    icon: 'info',
                    confirmText: 'Sửa Quyền',
                    action: function() { self.showDemo('Đã thiết lập quyền 644 cho files PHP và 755 cho thư mục!'); }
                },
                'update_htaccess': {
                    title: 'Cập Nhật .htaccess',
                    text: 'Cập nhật rules bảo mật trong file .htaccess.',
                    icon: 'info',
                    confirmText: 'Cập Nhật',
                    action: function() { self.showDemo('Đã cập nhật .htaccess với rules bảo mật mới!'); }
                },
                'clean_logs': {
                    title: 'Dọn Dẹp Logs',
                    text: 'Xóa logs cũ và tối ưu hóa hệ thống.',
                    icon: 'info',
                    confirmText: 'Dọn Dẹp',
                    action: function() { self.showDemo('Đã dọn dẹp 15 MB logs cũ và tối ưu hệ thống!'); }
                },
                'auto_fix_all': {
                    title: 'Khắc Phục Toàn Bộ',
                    text: 'Thực hiện tất cả các biện pháp khắc phục tự động.',
                    icon: 'warning',
                    confirmText: 'Khắc Phục Tất Cả',
                    action: function() { self.performAutoFix(); }
                },
                'schedule_scan': {
                    title: 'Lên Lịch Quét',
                    text: 'Thiết lập lịch quét tự động hàng ngày.',
                    icon: 'info',
                    confirmText: 'Thiết Lập',
                    action: function() { self.showDemo('Đã thiết lập lịch quét tự động lúc 2:00 AM hàng ngày!'); }
                }
            };

            var actionConfig = actions[action];
            if (!actionConfig) return;

            Swal.fire({
                title: actionConfig.title,
                text: actionConfig.text,
                icon: actionConfig.icon,
                showCancelButton: true,
                confirmButtonColor: action === 'delete_critical' || action === 'auto_fix_all' ? '#E53E3E' : 'var(--primary-blue)',
                cancelButtonColor: 'var(--text-light)',
                confirmButtonText: actionConfig.confirmText,
                cancelButtonText: 'Hủy'
            }).then(function(result) {
                if (result.isConfirmed) {
                    actionConfig.action();
                }
            });
        };

        SecurityScanner.prototype.showDemo = function(message) {
            Swal.fire({
                icon: 'success',
                title: 'Demo - Thành Công!',
                text: message,
                confirmButtonColor: 'var(--success-text)',
                timer: 3000,
                timerProgressBar: true
            });
        };

        SecurityScanner.prototype.deleteSingleFile = function(filePath, index) {
            var self = this;
            
            Swal.fire({
                title: 'XÓA FILE ĐỘC HẠI?',
                html: '<strong style="color: var(--danger-text);">CẢNH BÁO:</strong> Sẽ xóa vĩnh viễn file:<br><br><code style="color: var(--warning-text); background: var(--warning-bg); padding: 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">' + filePath + '</code>',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: 'var(--danger-text)',
                cancelButtonColor: 'var(--text-light)',
                confirmButtonText: 'XÓA NGAY',
                cancelButtonText: 'Hủy',
                dangerMode: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    self.performSingleFileDeletion(filePath, index);
                }
            });
        };

        SecurityScanner.prototype.performSingleFileDeletion = function(filePath, index) {
            var self = this;
            var deleteBtn = document.querySelector('button[onclick*="' + index + '"]');
            
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Xóa...';
            }
            
            // Use XMLHttpRequest for PHP 5.6+ compatibility
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '?delete_malware=1', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'XÓA THÀNH CÔNG!',
                                    text: 'File ' + filePath + ' đã được xóa thành công.',
                                    confirmButtonColor: 'var(--success-text)'
                                }).then(function() {
                                    // Remove the card from display
                                    var threatItem = deleteBtn.closest('.threat-item');
                                    if (threatItem) {
                                        threatItem.style.transition = 'all 0.3s ease';
                                        threatItem.style.opacity = '0';
                                        threatItem.style.transform = 'translateX(-100%)';
                                        setTimeout(function() {
                                            threatItem.remove();
                                        }, 300);
                                    }
                                });
                            } else {
                                throw new Error(data.error || 'Unknown error');
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'LỖI XÓA FILE',
                                text: e.message,
                                confirmButtonColor: 'var(--danger-text)'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'LỖI XÓA FILE',
                            text: 'HTTP Error: ' + xhr.status,
                            confirmButtonColor: 'var(--danger-text)'
                        });
                    }
                    
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Xóa';
                    }
                }
            };
            
            xhr.send(JSON.stringify({ malware_files: [filePath] }));
        };

        SecurityScanner.prototype.performAutoFix = function() {
            var self = this;
            
            if (!this.lastScanData || this.lastScanData.suspicious_count === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Không có lỗi để khắc phục',
                    text: 'Không phát hiện lỗi nào cần khắc phục!',
                    confirmButtonColor: 'var(--primary-blue)'
                });
                return;
            }
            
            var fixDropdown = document.getElementById('fixDropdown');
            fixDropdown.disabled = true;
            fixDropdown.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang Khắc Phục...';
            
            // Use XMLHttpRequest for PHP 5.6+ compatibility
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '?autofix=1', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Khắc Phục Thành Công!',
                                    html: '<div style="text-align: left; font-size: 0.9rem;">' +
                                          '<strong>Files đã sửa:</strong> ' + data.fixed_files + '<br>' +
                                          '<strong>Files độc hại đã xóa:</strong> ' + (data.deleted_files || 0) + '<br>' +
                                          '<strong>Lỗi đã khắc phục:</strong> ' + data.fixes_applied + '<br>' +
                                          '<strong>Backup:</strong> ' + (data.backup_created ? '✅ Đã tạo' : '❌ Không có') +
                                          '</div>',
                                    confirmButtonColor: 'var(--success-text)'
                                }).then(function() {
                                    // Auto scan lại sau khi fix
                                    self.startScan();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi Khắc Phục',
                                    text: data.error || 'Unknown error',
                                    confirmButtonColor: 'var(--danger-text)'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi Khắc Phục',
                                text: e.message,
                                confirmButtonColor: 'var(--danger-text)'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi Khắc Phục',
                            text: 'HTTP Error: ' + xhr.status,
                            confirmButtonColor: 'var(--danger-text)'
                        });
                    }
                    
                    fixDropdown.disabled = false;
                    fixDropdown.innerHTML = '<i class="fas fa-tools"></i> Khắc Phục';
                }
            };
            
            xhr.send(JSON.stringify(this.lastScanData));
        };

        // Quick fix functions removed

        // Initialize scanner when page loads
        var scanner;
        document.addEventListener('DOMContentLoaded', function() {
            scanner = new SecurityScanner();
            
            // Make scanner globally accessible
            window.scanner = scanner;
        });
    </script>
</body>
</html> 