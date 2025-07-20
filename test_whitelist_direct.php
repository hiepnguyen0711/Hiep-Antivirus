<?php
// Test whitelist patterns directly
echo "=== TESTING WHITELIST DIRECTLY ===\n\n";

// Create test file with safe $_REQUEST['alias'] pattern
$testContent = '<?php
// Safe router pattern - SHOULD BE WHITELISTED
$alias = $_REQUEST["alias"];
switch ($_REQUEST["alias"]) {
    case "san-pham":
        $source = "product-detail";
        break;
}
include "sources/$source.php";
?>';

file_put_contents('./test_router_safe.php', $testContent);
echo "✅ Created test_router_safe.php with safe \$_REQUEST['alias'] pattern\n";

// Include client scanner directly
require_once 'security_scan_client.php';

try {
    $scanner = new SecurityScanClient();
    
    // Test whitelist function directly
    $line = '$alias = $_REQUEST["alias"];';
    $whitelistPatterns = [
        '\\$_REQUEST\\[.?alias.?\\]',
        'switch\\s*\\(\\s*\\$_REQUEST\\[.?alias.?\\]',
    ];
    
    $reflection = new ReflectionClass($scanner);
    $method = $reflection->getMethod('isContentWhitelisted');
    $method->setAccessible(true);
    
    $result = $method->invoke($scanner, $line, $whitelistPatterns);
    
    if ($result) {
        echo "✅ SUCCESS: \$_REQUEST['alias'] pattern is properly whitelisted!\n";
    } else {
        echo "❌ FAILED: \$_REQUEST['alias'] pattern NOT whitelisted\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing: " . $e->getMessage() . "\n";
}

// Clean up
@unlink('./test_router_safe.php');
echo "\n✅ Test completed\n";
?> 