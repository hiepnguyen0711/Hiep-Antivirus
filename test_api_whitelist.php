<?php

echo "=== TESTING SECURITY SCANNER WHITELIST FUNCTIONALITY ===\n\n";

// Test 1: Create a file with safe $_REQUEST['alias'] pattern
$testContent1 = '<?php
// This should be SAFE according to API whitelist
if (isset($_REQUEST["alias"])) {
    $alias = $_REQUEST["alias"];
    switch ($alias) {
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
}
?>';

file_put_contents('./test_safe_router.php', $testContent1);
echo "✅ Created test_safe_router.php with \$_REQUEST['alias'] pattern\n";

// Test 2: Create a file with malicious eval (should still be detected)
$testContent2 = '<?php
// This should be DANGEROUS and detected
eval($_GET["malicious_cmd"]);
system($_POST["cmd"]);
?>';

file_put_contents('./test_malicious.php', $testContent2);
echo "✅ Created test_malicious.php with dangerous patterns\n";

// Test the scanner API
function testScanner($endpoint = 'scan') {
    $url = "http://localhost/2025/Hiep-Antivirus/security_scan_client.php?endpoint={$endpoint}&api_key=hiep-security-client-2025-change-this-key";
    
    $data = json_encode([
        'priority_files' => ['test_safe_router.php', 'test_malicious.php']
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $data,
            'timeout' => 30
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to connect to scanner API\n";
        return false;
    }
    
    return json_decode($response, true);
}

echo "\n--- Testing Scanner API ---\n";

$result = testScanner('scan');

if ($result && $result['success']) {
    echo "✅ Scanner API responded successfully\n";
    echo "Scan time: " . ($result['scan_results']['scan_time'] ?? 'unknown') . " seconds\n";
    echo "Files scanned: " . ($result['scan_results']['scanned_files'] ?? 0) . "\n";
    echo "Threats found: " . ($result['scan_results']['suspicious_count'] ?? 0) . "\n\n";
    
    $threats = $result['scan_results']['threats']['all'] ?? [];
    
    // Check if safe file was flagged
    $safeFileThreats = array_filter($threats, function($threat) {
        return strpos($threat['path'], 'test_safe_router.php') !== false;
    });
    
    if (empty($safeFileThreats)) {
        echo "✅ SUCCESS: test_safe_router.php was NOT flagged (whitelist working!)\n";
    } else {
        echo "❌ FAILED: test_safe_router.php was still flagged as threat:\n";
        foreach ($safeFileThreats as $threat) {
            foreach ($threat['issues'] as $issue) {
                echo "  - Pattern: {$issue['pattern']} (Line: {$issue['line']})\n";
                echo "  - Context: {$issue['context']}\n";
            }
        }
        echo "\n";
    }
    
    // Check if malicious file was detected
    $maliciousFileThreats = array_filter($threats, function($threat) {
        return strpos($threat['path'], 'test_malicious.php') !== false;
    });
    
    if (!empty($maliciousFileThreats)) {
        echo "✅ SUCCESS: test_malicious.php was properly flagged as threat\n";
        foreach ($maliciousFileThreats as $threat) {
            foreach ($threat['issues'] as $issue) {
                echo "  - Pattern: {$issue['pattern']} (Line: {$issue['line']})\n";
            }
        }
    } else {
        echo "❌ WARNING: test_malicious.php was NOT flagged (scanner may be too lenient)\n";
    }
    
} else {
    echo "❌ Scanner API failed:\n";
    echo $result['error'] ?? 'Unknown error';
    echo "\n";
}

// Show API patterns for debugging
echo "\n--- API Patterns Info ---\n";
if (isset($result['api_patterns'])) {
    echo "API patterns loaded: " . count($result['api_patterns']) . " items\n";
    
    if (isset($result['safe_content_patterns'])) {
        echo "Safe content patterns found: " . count($result['safe_content_patterns']) . "\n";
        echo "Safe patterns:\n";
        foreach ($result['safe_content_patterns'] as $pattern) {
            echo "  - {$pattern}\n";
        }
    } else {
        echo "No safe_content_patterns found in API response\n";
    }
} else {
    echo "No API patterns info in response\n";
}

// Cleanup
echo "\n--- Cleanup ---\n";
@unlink('./test_safe_router.php');
@unlink('./test_malicious.php');
echo "✅ Cleaned up test files\n";

echo "\n=== TEST COMPLETED ===\n";

?> 