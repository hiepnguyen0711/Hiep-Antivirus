<?php
/**
 * Debug file để test server gọi client
 */

// Test URL construction
$client = [
    'id' => '68768b5bae5db',
    'name' => 'xemay365',
    'url' => 'https://xemay365.com.vn',
    'api_key' => 'hiep-security-client-2025-change-this-key'
];

// Construct URL như server
$url = rtrim($client['url'], '/');
if (strpos($url, 'security_scan_client.php') === false) {
    $url .= '/security_scan_client.php';
}
$url .= '?endpoint=health&api_key=' . urlencode($client['api_key']);

echo "Server sẽ gọi URL: " . $url . "\n";

// Test gọi URL này
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Hiep Security Server/1.0');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Error: " . ($error ?: 'None') . "\n";
echo "Response: " . $response . "\n";

// Parse response
$decodedResponse = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON parsed successfully\n";
    echo "Response status: " . ($decodedResponse['status'] ?? 'unknown') . "\n";
    echo "Response client: " . ($decodedResponse['client'] ?? 'unknown') . "\n";
} else {
    echo "JSON parse error: " . json_last_error_msg() . "\n";
}

?> 