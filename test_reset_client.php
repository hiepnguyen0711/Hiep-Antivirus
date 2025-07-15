<?php
/**
 * Test file để reset client
 */

// Xóa client cũ
$deleteUrl = 'https://hiepcodeweb.com/security_scan_server.php?api=delete_client';
$deleteData = http_build_query([
    'id' => '687686d8c36d8'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $deleteUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $deleteData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$deleteResponse = curl_exec($ch);
echo "Delete Response: " . $deleteResponse . "\n";
curl_close($ch);

// Thêm client mới
$addUrl = 'https://hiepcodeweb.com/security_scan_server.php?api=add_client';
$addData = http_build_query([
    'name' => 'xemay365',
    'url' => 'https://xemay365.com.vn',
    'api_key' => 'hiep-security-client-2025-change-this-key'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $addUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $addData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$addResponse = curl_exec($ch);
echo "Add Response: " . $addResponse . "\n";
curl_close($ch);

// Kiểm tra client mới
$checkUrl = 'https://hiepcodeweb.com/security_scan_server.php?api=get_clients';
$checkResponse = file_get_contents($checkUrl);
echo "Clients List: " . $checkResponse . "\n";

?> 