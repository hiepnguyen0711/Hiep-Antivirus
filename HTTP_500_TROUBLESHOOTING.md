# 🚨 HTTP 500 ERROR TROUBLESHOOTING

## 📋 Vấn đề hiện tại
Client `egreenpower.com.vn` đang trả về **HTTP 500 Internal Server Error** khi truy cập `security_scan_client.php`.

## 🔍 Nguyên nhân có thể
1. **File chưa được upload đúng cách**
2. **PHP syntax error hoặc compatibility issues**
3. **Missing PHP extensions**
4. **File permissions không đúng**
5. **PHP version quá cũ**

## ✅ GIẢI PHÁP

### 1. **Upload Simple Client để test**
```bash
# Upload file security_scan_client_simple.php lên server
# Đổi tên thành security_scan_client.php
```

### 2. **Kiểm tra PHP version**
Yêu cầu: **PHP 7.0 trở lên**
```bash
php -v
```

### 3. **Kiểm tra PHP extensions required**
```php
// Tạo file check_php.php với nội dung:
<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Extensions:\n";
$required = ['curl', 'json', 'openssl', 'fileinfo'];
foreach ($required as $ext) {
    echo "- {$ext}: " . (extension_loaded($ext) ? 'OK' : 'MISSING') . "\n";
}
?>
```

### 4. **Kiểm tra File permissions**
```bash
# Set đúng permissions
chmod 644 security_scan_client.php
# hoặc
chmod 755 security_scan_client.php
```

### 5. **Kiểm tra PHP error logs**
```bash
# Check error logs
tail -f /var/log/php_errors.log
# hoặc
tail -f /var/log/apache2/error.log
```

## 🧪 TEST SIMPLE CLIENT

### Thay thế client hiện tại:
1. Backup file cũ: `mv security_scan_client.php security_scan_client_backup.php`
2. Upload `security_scan_client_simple.php`
3. Rename: `mv security_scan_client_simple.php security_scan_client.php`

### Test endpoints:
```bash
# Test health check
curl "http://egreenpower.com.vn/security_scan_client.php?endpoint=health&api_key=hiep-security-client-2025-change-this-key"

# Test info
curl "http://egreenpower.com.vn/security_scan_client.php?endpoint=info&api_key=hiep-security-client-2025-change-this-key"
```

## 🔧 PHIÊN BẢN SIMPLE CLIENT

Simple client chỉ có:
- ✅ Basic endpoint handling
- ✅ API key validation
- ✅ JSON responses
- ✅ Error handling
- ❌ Không có full scanning features
- ❌ Không có complex dependencies

## 📞 NEXT STEPS

1. **Upload simple client** để xác nhận server hoạt động
2. **Fix môi trường server** (PHP version, extensions, permissions)
3. **Sau đó upload full client** với scanning features

## 🚀 KHI SIMPLE CLIENT HOẠT ĐỘNG

Update trong server interface:
1. Client sẽ hiển thị status **ONLINE**
2. Health check sẽ **PASS**
3. Có thể test các basic endpoints
4. Sau đó upgrade lên full client

## ⚠️ LƯU Ý QUAN TRỌNG

- Simple client **không thể scan** thực sự
- Chỉ dùng để **test connectivity**
- Phải **upgrade lên full client** để có scanning features
- **Backup file cũ** trước khi thay thế

---
*Troubleshooting guide - Tạo lúc: 21/07/2025* 