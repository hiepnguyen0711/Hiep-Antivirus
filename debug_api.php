<?php

echo "=== DEBUGGING SECURITY SCANNER API ===\n\n";

$apiKey = 'hiep-security-client-2025-change-this-key';

// Test 1: Health check
echo "--- Test 1: Health Check ---\n";
$url = "http://localhost/2025/Hiep-Antivirus/security_scan_client.php?endpoint=health&api_key={$apiKey}";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = file_get_contents($url, false, $context);
echo "Health Response: " . $response . "\n\n";

// Test 2: Status check
echo "--- Test 2: Status Check ---\n";
$url = "http://localhost/2025/Hiep-Antivirus/security_scan_client.php?endpoint=status&api_key={$apiKey}";

$response = file_get_contents($url, false, $context);
echo "Status Response: " . $response . "\n\n";

// Test 3: Info check
echo "--- Test 3: Info Check ---\n";
$url = "http://localhost/2025/Hiep-Antivirus/security_scan_client.php?endpoint=info&api_key={$apiKey}";

$response = file_get_contents($url, false, $context);
echo "Info Response: " . $response . "\n\n";

// Test 4: Direct include and test
echo "--- Test 4: Direct Test ---\n";

// Include the scanner class
require_once './security_scan_client.php';

// Test API patterns loading
$scanner = new SecurityScanner();
$result = $scanner->performScan([]);

if ($result['success']) {
    echo "✅ Direct scanner test successful\n";
    echo "Files scanned: " . $result['scan_results']['scanned_files'] . "\n";
    echo "Threats found: " . $result['scan_results']['suspicious_count'] . "\n";
    
    if (isset($result['safe_content_patterns'])) {
        echo "Safe content patterns loaded: " . count($result['safe_content_patterns']) . "\n";
        foreach ($result['safe_content_patterns'] as $pattern) {
            echo "  - {$pattern}\n";
        }
    } else {
        echo "No safe_content_patterns found\n";
    }
} else {
    echo "❌ Direct scanner test failed: " . $result['error'] . "\n";
}

?> 