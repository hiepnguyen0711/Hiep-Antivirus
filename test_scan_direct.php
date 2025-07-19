<?php
/**
 * Direct Scanner Test - Test the scanning logic directly
 */

// Define SecurityClientConfig if not exists
if (!class_exists('SecurityClientConfig')) {
    class SecurityClientConfig {
        const MAX_SCAN_FILES = 999999999;
        const CLIENT_NAME = 'test-client';
        const CLIENT_VERSION = '1.0';
        const API_KEY = 'test-key';
    }
}

echo "=== DIRECT SCANNER TEST ===\n";
echo "Testing scan logic from security_scan.php...\n\n";

// Test patterns - EXACT from security_scan.php
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

// Function to scan file with line numbers - EXACT from security_scan.php
function scanFileWithLineNumbers($filePath, $patterns) {
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
                echo "PATTERN FOUND in {$filePath}: {$pattern} at line " . ($lineNumber + 1) . "\n";
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

// Test files to scan
$testFiles = ['23.php', 'test_malware_sample.php'];

foreach ($testFiles as $testFile) {
    echo "Testing file: {$testFile}\n";
    echo "File exists: " . (file_exists($testFile) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($testFile)) {
        $content = file_get_contents($testFile);
        echo "File size: " . strlen($content) . " bytes\n";
        echo "First 200 chars: " . substr($content, 0, 200) . "...\n";
        
        $issues = scanFileWithLineNumbers($testFile, $criticalPatterns);
        
        echo "Issues found: " . count($issues) . "\n";
        
        if (!empty($issues)) {
            echo "=== ISSUES DETECTED ===\n";
            foreach ($issues as $issue) {
                echo "  - Pattern: " . $issue['pattern'] . "\n";
                echo "    Description: " . $issue['description'] . "\n";
                echo "    Line: " . $issue['line'] . "\n";
                echo "    Context: " . $issue['context'] . "\n";
                echo "    --------\n";
            }
        } else {
            echo "  No issues found!\n";
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "Test completed!\n";
?> 