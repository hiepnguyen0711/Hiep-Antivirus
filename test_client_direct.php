<?php
/**
 * Test Client Scanner API directly
 */

echo "=== TESTING CLIENT SCANNER API ===\n";

$clientUrl = 'http://localhost/2025/Hiep-Antivirus/security_scan_client.php?endpoint=scan';
$apiKey = 'hiep-security-client-2025-change-this-key';

// Test scan endpoint - endpoint goes in URL, data in POST body
$testData = [
    'api_key' => $apiKey,
    'priority_files' => ['23.php', 'test_malware_sample.php']
];

echo "Testing scan endpoint...\n";
echo "URL: {$clientUrl}\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $clientUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($error) {
    echo "CURL Error: {$error}\n";
}

echo "Response:\n";
echo $response . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "=== SCAN SUCCESSFUL ===\n";
        
        if (isset($data['scan_results']['suspicious_files'])) {
            $suspiciousFiles = $data['scan_results']['suspicious_files'];
            echo "Suspicious files found: " . count($suspiciousFiles) . "\n\n";
            
            foreach ($suspiciousFiles as $file) {
                echo "File: " . $file['path'] . "\n";
                echo "Category: " . ($file['category'] ?? 'Unknown') . "\n";
                echo "Issues: " . count($file['issues'] ?? []) . "\n";
                
                if (!empty($file['issues'])) {
                    foreach ($file['issues'] as $issue) {
                        echo "  - Pattern: " . $issue['pattern'] . "\n";
                        echo "    Line: " . ($issue['line'] ?? 'Unknown') . "\n";
                        echo "    Description: " . ($issue['description'] ?? 'No description') . "\n";
                    }
                }
                echo str_repeat("-", 40) . "\n";
            }
        } else {
            echo "No suspicious files found in scan results.\n";
        }
        
        // Display scan stats
        if (isset($data['scan_results'])) {
            $stats = $data['scan_results'];
            echo "\n=== SCAN STATS ===\n";
            echo "Files scanned: " . ($stats['scanned_files'] ?? 0) . "\n";
            echo "Suspicious count: " . ($stats['suspicious_count'] ?? 0) . "\n";
            echo "Critical count: " . ($stats['critical_count'] ?? 0) . "\n";
        }
    } else {
        echo "SCAN FAILED: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "HTTP ERROR: {$httpCode}\n";
}

echo "\nTest completed!\n";
?> 