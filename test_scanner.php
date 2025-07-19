<?php
/**
 * Quick Scanner Test - Direct testing of security scanner
 */

// Include the scanner client
require_once 'security_scan_client.php';

echo "<h1>Security Scanner Test</h1>\n";
echo "<p>Testing scanner directly...</p>\n";

try {
    $scanner = new SecurityScanner();
    
    echo "<p>Scanner initialized successfully.</p>\n";
    
    // Perform scan with debug
    $options = [
        'priority_files' => ['23.php', 'test_malware_sample.php']
    ];
    
    echo "<p>Starting scan...</p>\n";
    
    $result = $scanner->performScan($options);
    
    echo "<h2>Scan Results:</h2>\n";
    echo "<pre>";
    print_r($result);
    echo "</pre>\n";
    
    if ($result['success']) {
        $scanResults = $result['scan_results'];
        echo "<h3>Summary:</h3>\n";
        echo "<ul>\n";
        echo "<li>Files scanned: " . ($scanResults['scanned_files'] ?? 0) . "</li>\n";
        echo "<li>Suspicious files: " . ($scanResults['suspicious_count'] ?? 0) . "</li>\n"; 
        echo "<li>Critical files: " . ($scanResults['critical_count'] ?? 0) . "</li>\n";
        echo "</ul>\n";
        
        if (!empty($scanResults['suspicious_files'])) {
            echo "<h3>Suspicious Files Found:</h3>\n";
            foreach ($scanResults['suspicious_files'] as $file) {
                echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
                echo "<strong>File:</strong> " . htmlspecialchars($file['path']) . "<br>\n";
                echo "<strong>Category:</strong> " . ($file['category'] ?? 'Unknown') . "<br>\n";
                echo "<strong>Issues:</strong> " . count($file['issues'] ?? []) . "<br>\n";
                
                if (!empty($file['issues'])) {
                    echo "<ul>\n";
                    foreach ($file['issues'] as $issue) {
                        echo "<li>";
                        echo "<strong>" . htmlspecialchars($issue['pattern']) . "</strong> ";
                        echo "(" . ($issue['severity'] ?? 'Unknown') . ") ";
                        if (isset($issue['line'])) {
                            echo "- Line " . $issue['line'];
                        }
                        if (isset($issue['description'])) {
                            echo "<br><em>" . htmlspecialchars($issue['description']) . "</em>";
                        }
                        echo "</li>\n";
                    }
                    echo "</ul>\n";
                }
                echo "</div>\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<p>Test completed. Check error_log for detailed scan logs.</p>\n";
?>
