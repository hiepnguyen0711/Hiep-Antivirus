<?php
// Test file to check if $_REQUEST['alias'] is properly whitelisted

if (isset($_REQUEST['alias'])) {
    $alias = $_REQUEST['alias'];
    echo "Alias: " . htmlspecialchars($alias);
}

// This should be flagged as safe by the API whitelist
$test_var = $_REQUEST['alias'];

// This should also be safe
echo htmlspecialchars($test_var);

// Test WordPress functions (should be safe)
wp_enqueue_script('test-script');
wp_head();

// Test sanitization functions (should be safe)  
$sanitized = sanitize_text_field($_POST['input']);
echo esc_html($sanitized);

?> 