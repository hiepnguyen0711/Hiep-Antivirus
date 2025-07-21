# 🚨 EIVALVE.COM DEBUGGING STEPS

## 📋 Tình trạng hiện tại
- ✅ Website PHP hoạt động bình thường: `http://eivalve.com`
- ❌ File `security_scan_client.php` gây **TIMEOUT** (không phải HTTP 500)
- ❌ Không thể truy cập endpoint nào

## 🔍 Nguyên nhân có thể

### 1. **File chưa được upload**
- File `security_scan_client.php` không tồn tại trên server

### 2. **File có lỗi syntax gây infinite loop**
- PHP 5.6 compatibility issues
- Code gây vòng lặp vô tận → timeout

### 3. **Server security restriction**
- Firewall block file có tên `security_`
- .htaccess redirect hoặc block pattern

## ✅ GIẢI PHÁP TỪNG BƯỚC

### Bước 1: **Kiểm tra file có tồn tại không**
Tạo file test đơn giản: `test_php.php`
```php
<?php
echo json_encode(array(
    'status' => 'ok',
    'message' => 'PHP works',
    'php_version' => PHP_VERSION,
    'server_time' => date('Y-m-d H:i:s')
));
?>
```

Test: `http://eivalve.com/test_php.php`

### Bước 2: **Nếu test_php.php hoạt động** → Upload simple client
Đổi tên `security_scan_client_simple.php` thành `client.php` để tránh security restrictions:

```php
<?php
// Simplified client - renamed to avoid security restrictions
define('API_KEY', 'hiep-security-client-2025-change-this-key');

header('Content-Type: application/json');
echo json_encode(array(
    'status' => 'healthy',
    'client' => 'EI Valve Client',
    'version' => 'test-1.0',
    'php_version' => PHP_VERSION,
    'server' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown'
));
?>
```

Test: `http://eivalve.com/client.php`

### Bước 3: **Nếu client.php hoạt động** → Test với parameters
Test: `http://eivalve.com/client.php?endpoint=health&api_key=hiep-security-client-2025-change-this-key`

### Bước 4: **Nếu tất cả đều OK** → Upload full simple client
Rename `security_scan_client_simple.php` → `client.php` và upload full version.

## 🧪 DEBUGGING SCRIPTS

### A. Create test_debug.php:
```php
<?php
echo "=== SERVER DEBUG INFO ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown') . "\n";
echo "Document Root: " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : 'unknown') . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Files in directory:\n";
$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "- $file\n";
    }
}
echo "\nPHP Extensions:\n";
$extensions = get_loaded_extensions();
foreach (['curl', 'json', 'openssl', 'fileinfo'] as $ext) {
    echo "- $ext: " . (in_array($ext, $extensions) ? 'OK' : 'MISSING') . "\n";
}
?>
```

### B. Check if security_scan_client.php exists:
```bash
# SSH vào server và check:
ls -la | grep security
# hoặc
find . -name "*security*" -type f
```

## 📊 KẾT QUẢ MONG ĐỢI

| Test | Kết quả mong đợi | Nếu FAIL |
|------|------------------|----------|
| `test_php.php` | `{"status":"ok","message":"PHP works"...}` | Server PHP có vấn đề |
| `client.php` | `{"status":"healthy","client":"EI Valve Client"...}` | File upload có vấn đề |
| `client.php?endpoint=health` | Same as above | Parameter parsing issues |

## 🚀 GIẢI PHÁP NGAY LẬP TỨC

1. **Upload `test_php.php` trước** để confirm PHP works
2. **Upload simple client as `client.php`** để tránh security restrictions
3. **Test từng bước** một cách có hệ thống
4. **Sau khi OK** thì rename lại thành `security_scan_client.php`

## ⚠️ LƯU Ý

- **Đừng upload file lớn** cho đến khi simple client hoạt động
- **Sử dụng tên file khác** nếu `security_` bị block
- **Check file permissions** (chmod 644 hoặc 755)
- **Check .htaccess rules** có thể block pattern

---
*Debugging steps for eivalve.com - 21/07/2025* 