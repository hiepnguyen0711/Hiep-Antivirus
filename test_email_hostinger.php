<?php
/**
 * Test Email Function for Hostinger
 * Author: Hiệp Nguyễn
 * Version: 1.0
 * Date: 2025
 */

echo "=== TEST EMAIL HOSTINGER ===\n";
echo "Thời gian: " . date('Y-m-d H:i:s') . "\n\n";

// Test email configuration
$to = 'nguyenvanhiep0711@gmail.com';
$subject = '🚨 Test Email từ Hostinger - Multi-Website Scanner';
$message = '
<html>
<head>
    <title>Test Email</title>
</head>
<body>
    <h2>🚨 Test Email từ Hostinger</h2>
    <p><strong>Thời gian:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>
    <p><strong>IP:</strong> ' . $_SERVER['SERVER_ADDR'] . '</p>
    <p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>
    <p><strong>Trạng thái:</strong> Email function hoạt động bình thường!</p>
    <p>Phát triển bởi Hiệp Nguyễn - Multi-Website Security Scanner</p>
</body>
</html>
';

// Email headers
$headers = array(
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: Multi-Website Scanner <scanner@' . $_SERVER['SERVER_NAME'] . '>',
    'Reply-To: nguyenvanhiep0711@gmail.com',
    'X-Mailer: PHP/' . phpversion()
);

echo "📧 Đang gửi email test...\n";
echo "   To: $to\n";
echo "   From: scanner@" . $_SERVER['SERVER_NAME'] . "\n";
echo "   Subject: $subject\n\n";

// Send email
$result = mail($to, $subject, $message, implode("\r\n", $headers));

if ($result) {
    echo "✅ EMAIL GỬI THÀNH CÔNG!\n";
    echo "   Vui lòng kiểm tra hộp thư (bao gồm spam folder)\n";
    echo "   Nếu nhận được email, hệ thống email hoạt động tốt!\n";
} else {
    echo "❌ EMAIL GỬI THẤT BẠI!\n";
    echo "   Có thể:\n";
    echo "   - Hostinger chặn mail() function\n";
    echo "   - Cần cấu hình SMTP\n";
    echo "   - Kiểm tra DNS domain\n";
}

echo "\n=== THÔNG TIN HỆ THỐNG ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Script Path: " . __FILE__ . "\n";

// Test mail function availability
echo "\n=== KIỂM TRA MAIL FUNCTION ===\n";
if (function_exists('mail')) {
    echo "✅ Mail function có sẵn\n";
} else {
    echo "❌ Mail function không có sẵn\n";
}

// Check for sendmail
if (ini_get('sendmail_path')) {
    echo "✅ Sendmail path: " . ini_get('sendmail_path') . "\n";
} else {
    echo "❌ Sendmail path không được cấu hình\n";
}

// Check SMTP settings
echo "\n=== CÀI ĐẶT SMTP ===\n";
echo "SMTP Server: " . ini_get('SMTP') . "\n";
echo "SMTP Port: " . ini_get('smtp_port') . "\n";

echo "\n=== TEST HOÀN THÀNH ===\n";
echo "Nếu email gửi thành công, Multi-Website Scanner sẽ hoạt động tốt!\n";
?> 