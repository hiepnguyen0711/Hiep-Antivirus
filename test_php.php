<?php
// Test file để kiểm tra server PHP cơ bản
echo json_encode(array(
    'status' => 'ok',
    'message' => 'PHP works on server',
    'php_version' => PHP_VERSION,
    'server_time' => date('Y-m-d H:i:s'),
    'server_info' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown'
));
?> 