<?php
/**
 * Test script for delete file function
 */

// Test client URL - local
$clientUrl = 'http://localhost/2025/Hiep-Antivirus/security_scan_client.php';
$apiKey = 'hiep-security-client-2025-change-this-key';

// Test file to delete (relative path)
$testFile = './test_file.txt';

// Create test file
file_put_contents($testFile, 'This is a test file for deletion');

// Test delete request
$url = $clientUrl . '?endpoint=delete_file&api_key=' . urlencode($apiKey);

$data = [
    'file_path' => $testFile
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Error: " . $error . "\n";
echo "Response: " . $response . "\n";

// Check if file was deleted
if (file_exists($testFile)) {
    echo "File still exists - deletion failed\n";
} else {
    echo "File deleted successfully\n";
}
?> 