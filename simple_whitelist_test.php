<?php
// Simple whitelist pattern test
echo "=== SIMPLE WHITELIST PATTERN TEST ===\n\n";

// Test patterns
$whitelistPatterns = [
    '\\$_REQUEST\\[.?alias.?\\]',
    'switch\\s*\\(\\s*\\$_REQUEST\\[.?alias.?\\]',
    'if\\s*\\(\\s*isset\\(\\$_REQUEST\\[.?alias.?\\]',
    '\\$source\\s*=.*\\$_REQUEST\\[.?alias.?\\]'
];

$testLines = [
    '$alias = $_REQUEST["alias"];',                    // SHOULD BE WHITELISTED
    'switch ($_REQUEST["alias"]) {',                   // SHOULD BE WHITELISTED  
    'if (isset($_REQUEST["alias"])) {',                // SHOULD BE WHITELISTED
    '$source = "test-" . $_REQUEST["alias"];',         // SHOULD BE WHITELISTED
    'eval($_REQUEST["cmd"]);',                         // SHOULD NOT BE WHITELISTED
];

function isContentWhitelisted($line, $whitelistPatterns) {
    $line = trim($line);
    
    foreach ($whitelistPatterns as $whitelistPattern) {
        // Try regex match first
        if (@preg_match("/$whitelistPattern/i", $line)) {
            return true;
        }
        
        // Fallback to simple string contains
        if (stripos($line, str_replace('\\', '', $whitelistPattern)) !== false) {
            return true;
        }
    }
    
    return false;
}

echo "Testing whitelist patterns:\n\n";

foreach ($testLines as $line) {
    $isWhitelisted = isContentWhitelisted($line, $whitelistPatterns);
    $status = $isWhitelisted ? "✅ WHITELISTED" : "❌ NOT WHITELISTED";
    echo "$status: $line\n";
}

echo "\n=== EXPECTED RESULTS ===\n";
echo "✅ Lines 1-4 should be WHITELISTED (safe router patterns)\n";
echo "❌ Line 5 should be NOT WHITELISTED (malicious eval)\n";
?> 