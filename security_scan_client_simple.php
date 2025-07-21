<?php

/**
 * Security Scanner Client - Simplified Version for Testing
 * Version: 1.0 Simple
 */

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Basic configuration
define('API_KEY', 'hiep-security-client-2025-change-this-key');
define('CLIENT_NAME', 'Simple Client');

// Simple API key validation
function validateRequest() {
    // PHP 5.6 compatible - no null coalescing operator
    $apiKey = null;
    if (isset($_GET['api_key'])) {
        $apiKey = $_GET['api_key'];
    } elseif (isset($_POST['api_key'])) {
        $apiKey = $_POST['api_key'];
    }
    
    if (!$apiKey || $apiKey !== API_KEY) {
        http_response_code(401);
        echo json_encode(array('error' => 'Invalid API key'));
        exit;
    }
}

// Main handler
try {
    validateRequest();
    
    // PHP 5.6 compatible
    $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'health';
    
    switch ($endpoint) {
        case 'health':
            echo json_encode(array(
                'status' => 'healthy',
                'client' => CLIENT_NAME,
                'version' => '1.0-simple-php56',
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'server' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown'
            ));
            break;
            
        case 'info':
            echo json_encode(array(
                'client_name' => CLIENT_NAME,
                'version' => '1.0-simple-php56',
                'php_version' => PHP_VERSION,
                'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
                'document_root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'unknown',
                'extensions' => get_loaded_extensions(),
                'timestamp' => date('Y-m-d H:i:s')
            ));
            break;
            
        case 'status':
            echo json_encode(array(
                'success' => true,
                'client_info' => array(
                    'name' => CLIENT_NAME,
                    'version' => '1.0-simple-php56',
                    'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown',
                    'server_ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'unknown'
                ),
                'system_info' => array(
                    'php' => array(
                        'version' => PHP_VERSION,
                        'memory_limit' => ini_get('memory_limit'),
                        'max_execution_time' => ini_get('max_execution_time')
                    ),
                    'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown'
                ),
                'timestamp' => date('Y-m-d H:i:s')
            ));
            break;
            
        case 'scan':
            // Simple scan simulation
            echo json_encode(array(
                'success' => true,
                'client_info' => array(
                    'name' => CLIENT_NAME,
                    'version' => '1.0-simple-php56',
                    'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown'
                ),
                'scan_results' => array(
                    'scanned_files' => 0,
                    'suspicious_count' => 0,
                    'critical_count' => 0,
                    'scan_time' => 0,
                    'status' => 'clean',
                    'message' => 'Simple client - scan functionality not implemented'
                ),
                'timestamp' => date('Y-m-d H:i:s')
            ));
            break;
            
        default:
            http_response_code(404);
            echo json_encode(array('error' => 'Endpoint not found: ' . $endpoint));
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ));
}

?> 