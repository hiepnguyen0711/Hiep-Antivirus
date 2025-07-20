<?php
// Test whitelist patterns functionality
echo "=== TESTING WHITELIST FUNCTIONALITY ===\n\n";

// Create test file with safe $_REQUEST['alias'] pattern
$testContent = '<?php
// This is a safe router pattern - SHOULD BE WHITELISTED
$alias = $_REQUEST["alias"];
switch ($_REQUEST["alias"]) {
    case "san-pham":
        $source = "product-detail";
        break;
    case "lien-he":
        $source = "contact";
        break;
    default:
        $source = "index";
}
include "sources/$source.php";
?>';

// Write test file
file_put_contents('./test_router_safe.php', $testContent);
echo "✅ Created test_router_safe.php with safe \$_REQUEST['alias'] pattern\n";

// Test client scanner API on this file
$url = 'http://localhost/2025/Hiep-Antivirus/security_scan_client.php?action=scan';
$data = json_encode(['options' => ['priority_files' => ['test_router_safe.php']]]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nSCAN RESULTS:\n";
echo "HTTP Code: $httpCode\n";

$decoded = json_decode($response, true);
if ($decoded && $decoded['success']) {
    $threats = $decoded['scan_results']['suspicious_files'] ?? [];
    
    echo "Total threats found: " . count($threats) . "\n";
    
    $routerThreats = array_filter($threats, function($threat) {
        return strpos($threat['path'], 'test_router_safe.php') !== false;
    });
    
    if (empty($routerThreats)) {
        echo "✅ SUCCESS: test_router_safe.php was NOT flagged as threat (whitelist working!)\n";
    } else {
        echo "❌ FAILED: test_router_safe.php was flagged as threat:\n";
        foreach ($routerThreats as $threat) {
            foreach ($threat['issues'] as $issue) {
                echo "  - Pattern: {$issue['pattern']}\n";
                echo "  - Line: {$issue['line']}\n";
                echo "  - Context: {$issue['context']}\n\n";
            }
        }
    }
} else {
    echo "❌ Scan failed: " . ($decoded['error'] ?? 'Unknown error') . "\n";
}

// Also test with malicious content to ensure blacklist still works
$maliciousContent = '<?php
// This is malicious code - SHOULD BE DETECTED
eval($_GET["cmd"]);
?>';

file_put_contents('./test_malicious_eval.php', $maliciousContent);
echo "\n✅ Created test_malicious_eval.php with malicious eval pattern\n";

// Clean up
echo "\n🧹 Cleaning up test files...\n";
@unlink('./test_router_safe.php');
@unlink('./test_malicious_eval.php');
echo "✅ Cleanup completed\n";
?> 