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
function getClientIP()
{
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

// Real-time file list endpoint
if (isset($_GET['scan_files']) && $_GET['scan_files'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');

    // Read scanned files list
    $files_file = './logs/scanned_files.json';
    $files_data = array(
        'files' => array(),
        'last_update' => 0
    );

    if (file_exists($files_file)) {
        $file_content = @file_get_contents($files_file);
        if ($file_content) {
            $decoded = json_decode($file_content, true);
            if ($decoded) {
                $files_data = $decoded;
            }
        }
    }

    // Check if JSON_UNESCAPED_UNICODE is available
    $json_flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $json_flags = JSON_UNESCAPED_UNICODE;
    }

    echo json_encode($files_data, $json_flags);
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
    // Initialize progress and files tracking
    if (!file_exists('./logs')) {
        @mkdir('./logs', 0755, true);
    }

    // Check if JSON_UNESCAPED_UNICODE is available
    $json_flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $json_flags = JSON_UNESCAPED_UNICODE;
    }

    // Initialize progress file
    $progress_file = './logs/scan_progress.json';
    $initial_progress = array(
        'current_file' => 'Khởi tạo scanner...',
        'scanned_count' => 0,
        'total_estimate' => 100,
        'is_scanning' => true,
        'percentage' => 0,
        'start_time' => time()
    );
    @file_put_contents($progress_file, json_encode($initial_progress, $json_flags));

    // Initialize scanned files list
    $files_file = './logs/scanned_files.json';
    $initial_files = array(
        'files' => array(),
        'last_update' => time()
    );
    @file_put_contents($files_file, json_encode($initial_files, $json_flags));

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

    // Set unlimited execution time and high memory for comprehensive scan
    set_time_limit(0); // No time limit - scan everything
    ini_set('memory_limit', '1024M');
    ini_set('max_execution_time', 0); // No execution time limit

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

        // Scan everything in current project only (unlimited comprehensive scan)
        $directories = array('./'); // Scan chỉ trong thư mục dự án hiện tại
        $suspicious_files = array();
        $critical_files = array();
        $severe_files = array();
        $warning_files = array();
        $filemanager_files = array();
        $scanned_files = 0;
        $max_files = 999999; // No limit - scan everything
        $start_time = time();

        function scanFileWithLineNumbers($file_path, $patterns)
        {
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

        function getFileMetadata($file_path)
        {
            $metadata = array(
                'modified_time' => 0,
                'created_time' => 0,
                'size' => 0,
                'is_recent' => false,
                'age_category' => 'old'
            );

            if (file_exists($file_path)) {
                $modified_time = filemtime($file_path);
                $metadata['modified_time'] = $modified_time;
                $metadata['size'] = filesize($file_path);

                // Determine if file is recently modified (potential shell)
                $now = time();
                $hours_24 = 24 * 3600;
                $days_7 = 7 * 24 * 3600;
                $days_30 = 30 * 24 * 3600;
                $months_5  = 5  * 30 * 24 * 3600;  // xấp xỉ 5 tháng

                if (($now - $modified_time) < $hours_24) {
                    $metadata['age_category'] = 'very_recent'; // < 24h
                    $metadata['is_recent'] = true;
                } elseif (($now - $modified_time) < $days_7) {
                    $metadata['age_category'] = 'recent'; // < 7 days
                    $metadata['is_recent'] = true;
                } elseif (($now - $modified_time) < $months_5) {
                    $metadata['age_category'] = 'medium'; // < 30 days
                } else {
                    $metadata['age_category'] = 'old'; // > 30 days
                }
            }

            return $metadata;
        }

        function checkSuspiciousFile($file_path, $suspicious_patterns)
        {
            $issues = array();
            $filename = basename($file_path);
            $file_dir = dirname($file_path);

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

            // Enhanced empty file detection - ESPECIALLY for root directory and ANY directory
            if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'php') {
                $content = @file_get_contents($file_path);
                if ($content !== false) {
                    $content_trimmed = trim($content);
                    $content_no_php_tags = str_replace(array('<?php', '<?', '?>'), '', $content_trimmed);
                    $content_clean = trim($content_no_php_tags);
                    $file_size = filesize($file_path);

                    // Stricter detection for potentially planted files
                    $is_suspicious_empty = false;

                    // Case 1: Completely empty file (0 bytes)
                    if ($file_size === 0 || empty($content_trimmed)) {
                        $is_suspicious_empty = true;
                        $description = 'EMPTY PHP FILE (0 bytes) - Definitely planted by hacker';
                    }
                    // Case 2: Only PHP tags with no content (very small files)
                    elseif (empty($content_clean) || strlen($content_clean) < 3) {
                        $is_suspicious_empty = true;
                        $description = 'Nearly empty PHP file - Contains only PHP tags or whitespace';
                    }
                    // Case 3: Very small file anywhere (common hacker pattern) - lowered threshold
                    elseif ($file_size < 50) {
                        $is_suspicious_empty = true;
                        $description = 'Extremely small PHP file (' . $file_size . ' bytes) - Very suspicious';
                    }
                    // Case 4: Common hacker filenames in ANY directory (not just root)
                    elseif (in_array(strtolower($filename), array('app.php', 'style.php', 'config.php', 'db.php', 'wp.php', 'wp-config.php', 'connect.php', 'connection.php', 'test.php', 'shell.php', 'hack.php', 'backdoor.php', 'upload.php'))) {
                        $is_suspicious_empty = true;
                        $description = 'Common hacker filename "' . $filename . '" - EXTREMELY SUSPICIOUS';
                    }
                    // Case 5: Files with suspicious single character content
                    elseif ($file_size < 20 && preg_match('/^[\s<?php]*[a-z0-9]{1,3}[\s?>]*$/i', $content_trimmed)) {
                        $is_suspicious_empty = true;
                        $description = 'PHP file with suspicious minimal content - Likely shell stub';
                    }

                    if ($is_suspicious_empty) {
                        $issues[] = array(
                            'pattern' => 'suspicious_empty_file',
                            'description' => $description,
                            'line' => 1,
                            'code_snippet' => 'File size: ' . $file_size . ' bytes. Content: ' . substr($content_trimmed, 0, 100) . '...'
                        );
                    }
                }
            }

            return $issues;
        }

        function scanDirectory($dir, $critical_patterns, $severe_patterns, $warning_patterns)
        {
            global $suspicious_files, $scanned_files, $critical_files, $severe_files, $warning_files, $filemanager_files, $max_files, $suspicious_file_patterns, $current_scanning_file, $start_time;

            if (!is_dir($dir)) {
                return;
            }

            // No timeout - scan everything regardless of time

            // Only exclude our scanner
            $exclude_files = array(
                'security_scan.php' // Only our scanner
            );

            // Minimal exclusions - only truly dangerous or unnecessary directories
            $exclude_dirs = array(
                '.git',
                '.svn',
                '.hg'
            );

            try {
                // PHP 5.6+ compatible directory iteration with filtering
                if (class_exists('RecursiveIteratorIterator') && class_exists('RecursiveDirectoryIterator')) {
                    $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
                    $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, function ($current, $key, $iterator) use ($exclude_dirs, $dir) {
                        // Get absolute path and check if it's within project directory
                        $currentPath = $current->getRealPath();
                        $projectPath = realpath($dir);

                        // Only scan within current project directory
                        if ($currentPath && $projectPath && strpos($currentPath, $projectPath) !== 0) {
                            return false;
                        }

                        // Skip excluded directories
                        if ($current->isDir()) {
                            $dirName = $current->getBasename();
                            return !in_array($dirName, $exclude_dirs);
                        }
                        return true;
                    });
                    $iterator = new RecursiveIteratorIterator($filterIterator);
                } else {
                    // Fallback for older PHP versions
                    $iterator = new DirectoryIterator($dir);
                }

                foreach ($iterator as $file) {
                    // No limits - scan everything
                    if ($scanned_files >= $max_files) {
                        break;
                    }

                    // Performance optimization - yield control every 50 files
                    if ($scanned_files % 50 === 0) {
                        usleep(500); // 0.5ms pause to prevent blocking
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
                            // Additional check: Only scan files within current project directory
                            $realPath = realpath($file_path);
                            $projectRoot = realpath('./');

                            if (!$realPath || !$projectRoot || strpos($realPath, $projectRoot) !== 0) {
                                continue; // Skip files outside project directory
                            }

                            $scanned_files++;

                            // Update real-time progress
                            $current_scanning_file = $file_path;
                            $percentage = min(100, ($scanned_files / $max_files) * 100);

                            // Check if JSON_UNESCAPED_UNICODE is available
                            $json_flags = 0;
                            if (defined('JSON_UNESCAPED_UNICODE')) {
                                $json_flags = JSON_UNESCAPED_UNICODE;
                            }

                            // Add file to scanned files list
                            $files_file = './logs/scanned_files.json';
                            $files_data = array('files' => array(), 'last_update' => 0);

                            if (file_exists($files_file)) {
                                $file_content = @file_get_contents($files_file);
                                if ($file_content) {
                                    $decoded = json_decode($file_content, true);
                                    if ($decoded) {
                                        $files_data = $decoded;
                                    }
                                }
                            }

                            // Determine file status based on scanning results
                            $is_suspicious = false;
                            $threat_type = 'Clean';

                            // Quick check for obvious threats
                            if (strpos($file_path, 'virus-files') !== false) {
                                $is_suspicious = true;
                                $threat_type = 'Virus';
                            } else {
                                // Check for suspicious patterns in filename
                                $suspicious_patterns = array('.php.jpg', '.php.png', '.php.gif', 'cache.php', 'eval', 'base64');
                                foreach ($suspicious_patterns as $pattern) {
                                    if (strpos($file_path, $pattern) !== false) {
                                        $is_suspicious = true;
                                        $threat_type = 'Suspicious';
                                        break;
                                    }
                                }
                            }

                            // Add file to list
                            $files_data['files'][] = array(
                                'path' => $file_path,
                                'status' => $threat_type,
                                'is_suspicious' => $is_suspicious,
                                'scan_number' => $scanned_files,
                                'timestamp' => time()
                            );

                            // Keep only last 50 files for performance
                            if (count($files_data['files']) > 50) {
                                $files_data['files'] = array_slice($files_data['files'], -50);
                            }

                            $files_data['last_update'] = time();
                            @file_put_contents($files_file, json_encode($files_data, $json_flags));

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

                            @file_put_contents($progress_file, json_encode($progress_data, $json_flags));

                            // Determine file category
                            $is_virus_file = strpos($file_path, 'virus-files') !== false;
                            $is_filemanager = strpos($file_path, 'admin/filemanager') !== false;

                            // Get file metadata for hacker detection
                            $file_metadata = getFileMetadata($file_path);

                            // Check for suspicious file extensions and empty files FIRST (HIGHEST PRIORITY)
                            $suspicious_issues = checkSuspiciousFile($file_path, $suspicious_file_patterns);
                            if (!empty($suspicious_issues)) {
                                // Determine severity - empty files in root are CRITICAL
                                $is_root_file = (dirname($file_path) === '.' || dirname($file_path) === './');
                                $has_empty_pattern = false;
                                foreach ($suspicious_issues as $issue) {
                                    if (strpos($issue['pattern'], 'empty') !== false) {
                                        $has_empty_pattern = true;
                                        break;
                                    }
                                }

                                $severity = ($is_root_file && $has_empty_pattern) ? 'critical' : 'critical';
                                $category = ($is_root_file && $has_empty_pattern) ? 'hacker_planted' : 'suspicious_file';

                                $suspicious_files[] = array(
                                    'path' => $file_path,
                                    'issues' => $suspicious_issues,
                                    'severity' => $severity,
                                    'priority' => 1,
                                    'category' => $category,
                                    'metadata' => $file_metadata
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
                                        'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system'),
                                        'metadata' => $file_metadata
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
                                            'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system'),
                                            'metadata' => $file_metadata
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
                                                'category' => $is_virus_file ? 'virus' : ($is_filemanager ? 'filemanager' : 'system'),
                                                'metadata' => $file_metadata
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

        // Perform comprehensive unlimited scan
        foreach ($directories as $dir) {
            // Scan everything - no time limits
            scanDirectory($dir, $critical_malware_patterns, $severe_patterns, $warning_patterns);

            // Update progress after each directory
            $progress_file = './logs/scan_progress.json';
            $progress_data = array(
                'current_file' => 'Đã quét xong thư mục: ' . $dir,
                'scanned_count' => $scanned_files,
                'total_estimate' => $max_files,
                'is_scanning' => true,
                'percentage' => min(100, ($scanned_files / 50000) * 100), // Dynamic estimate
                'directory' => basename($dir),
                'last_update' => time()
            );

            // Check if JSON_UNESCAPED_UNICODE is available
            $json_flags = 0;
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $json_flags = JSON_UNESCAPED_UNICODE;
            }

            @file_put_contents($progress_file, json_encode($progress_data, $json_flags));
        }

        // Sort suspicious files by priority (critical first)
        usort($suspicious_files, function ($a, $b) {
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

function deleteMalwareFiles($malware_files)
{
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

function performAutoFix($scan_data)
{
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
    <title>Enterprise Security Scanner - Unlimited Scan - Hiệp Nguyễn (PHP 5.6+)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="style.css">
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
                    <i class="fas fa-shield-halved"></i> Enterprise Security Scanner - Unlimited
                </h1>
                <p class="hero-subtitle">
                    Quét toàn bộ dự án không giới hạn - Phát hiện shells và files rỗng<br>
                    Tìm kiếm các file như app.php, style.php mà hacker đã chèn vào
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
                            <li>
                                <h6 class="dropdown-header"><i class="fas fa-shield-virus"></i> Xử Lý Malware</h6>
                            </li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('delete_critical')">
                                    <i class="fas fa-trash-alt text-danger"></i> Xóa Files Nguy Hiểm
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('quarantine')">
                                    <i class="fas fa-shield-alt text-warning"></i> Cách Ly Files Đáng Ngờ
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header"><i class="fas fa-wrench"></i> Sửa Chữa Hệ Thống</h6>
                            </li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('fix_permissions')">
                                    <i class="fas fa-key text-info"></i> Sửa Quyền Files
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('update_htaccess')">
                                    <i class="fas fa-cog text-success"></i> Cập Nhật .htaccess
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scanner.performAction('clean_logs')">
                                    <i class="fas fa-broom text-secondary"></i> Dọn Dẹp Logs
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header"><i class="fas fa-magic"></i> Tự Động Hóa</h6>
                            </li>
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

                    <!-- Filter Controls -->
                    <div id="filterControls" class="filter-controls" style="display: none;">
                        <div class="filter-group">
                            <label><i class="fas fa-sort"></i> Sắp xếp:</label>
                            <select id="sortBy" class="filter-select">
                                <option value="date">📅 Ngày mới nhất</option>
                                <option value="threat">⚠️ Mức độ nguy hiểm</option>
                                <option value="name">📁 Tên file</option>
                                <option value="size">📊 Kích thước</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-calendar-alt"></i> Lọc theo khoảng thời gian:</label>
                            <input type="text" id="dateRangePicker" class="date-picker" placeholder="Chọn khoảng thời gian..." readonly>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-filter"></i> Quick Filters:</label>
                            <select id="filterByAge" class="filter-select">
                                <option value="all">🔍 Tất cả files</option>
                                <option value="very_recent">🚨 24 giờ qua (Shell mới)</option>
                                <option value="recent">⚡ 7 ngày qua</option>
                                <option value="medium">📅 30 ngày qua</option>
                                <option value="old">📂 Cũ hơn 30 ngày</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <button id="showRecentOnly" class="quick-filter-btn">
                                🚨 Chỉ Files Nghi Ngờ Shell
                            </button>
                            <button id="resetFilters" class="quick-filter-btn secondary">
                                🔄 Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div id="scanResults"></div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>