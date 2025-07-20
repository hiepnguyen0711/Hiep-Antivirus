<?php
echo "=== TESTING CLIENT SCANNER WITH WHITELIST (FIXED) ===\n\n";

// Test với file test_safe_router.php
$url = 'http://localhost/2025/Hiep-Antivirus/security_scan_client.php?endpoint=scan';
$data = json_encode([
    'api_key' => 'hiep-security-client-2025-change-this-key',
    'options' => [
        'priority_files' => ['test_safe_router.php']
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

echo "Scanning test_safe_router.php (contains safe \$_REQUEST['alias'] patterns)...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Raw response (first 500 chars): " . substr($response, 0, 500) . "\n\n";
    
    $decoded = json_decode($response, true);
    if ($decoded && $decoded['success']) {
        $threats = $decoded['scan_results']['suspicious_files'] ?? [];
        
        echo "Total threats found: " . count($threats) . "\n";
        
        // Check if our test file was flagged
        $testFileThreats = array_filter($threats, function($threat) {
            return strpos($threat['path'], 'test_safe_router.php') !== false;
        });
        
        if (empty($testFileThreats)) {
            echo "✅ SUCCESS: test_safe_router.php was NOT flagged (whitelist working!)\n";
        } else {
            echo "❌ ISSUE: test_safe_router.php was flagged:\n";
            foreach ($testFileThreats as $threat) {
                echo "  Category: " . ($threat['category'] ?? 'unknown') . "\n";
                foreach ($threat['issues'] as $issue) {
                    echo "    - Pattern: " . ($issue['pattern'] ?? 'unknown') . "\n";
                    echo "    - Line: " . ($issue['line'] ?? 'unknown') . "\n";
                    echo "    - Context: " . ($issue['context'] ?? 'unknown') . "\n\n";
                }
            }
        }
        
    } else {
        echo "❌ Scan failed or invalid response\n";
    }
}
?> 