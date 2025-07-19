# 🔐 Hướng Dẫn Deploy Security Patterns API

## 📋 Tổng Quan

API Security Patterns cung cấp danh sách **blacklist** và **whitelist** patterns để tăng cường hiệu quả quét bảo mật cho hệ thống Security Scanner.

**URL API:** `https://hiepcodeweb.com/api/security_patterns.php`

---

## 🚀 Cách Deploy Lên Hosting

### Bước 1: Tạo Thư Mục API
```bash
# Trên hosting hiepcodeweb.com
mkdir -p /public_html/api
```

### Bước 2: Upload File API
Upload file `api/security_patterns.php` lên thư mục `/public_html/api/` trên hosting.

### Bước 3: Thiết Lập Permissions
```bash
chmod 755 /public_html/api/
chmod 644 /public_html/api/security_patterns.php
```

### Bước 4: Tạo Cache Directory (Tuỳ Chọn)
```bash
mkdir -p /public_html/api/cache
chmod 755 /public_html/api/cache
```

---

## 🔗 API Endpoints

| Endpoint | Method | Mô Tả |
|----------|--------|--------|
| `?action=get_patterns` | GET | Lấy tất cả patterns (blacklist + whitelist) |
| `?action=get_blacklist` | GET | Chỉ lấy blacklist patterns |
| `?action=get_whitelist` | GET | Chỉ lấy whitelist patterns |
| `?action=update_patterns` | POST | Cập nhật patterns (cần auth) |

---

## 📝 Ví Dụ Sử Dụng

### Lấy Tất Cả Patterns
```bash
curl "https://hiepcodeweb.com/api/security_patterns.php?action=get_patterns"
```

### Lấy Chỉ Blacklist
```bash
curl "https://hiepcodeweb.com/api/security_patterns.php?action=get_blacklist"
```

### Response Mẫu
```json
{
  "status": "success",
  "last_updated": "2025-01-11 10:30:00",
  "version": "1.2.0",
  "blacklist": {
    "file_names": ["shell.php", "backdoor.php", "webshell.php"],
    "content_patterns": ["eval\\s*\\(.*\\$_", "system\\s*\\(.*\\$_"],
    "directory_patterns": ["*/temp/*", "*/tmp/*"]
  },
  "whitelist": {
    "framework_files": ["wp-config.php", "composer.json"],
    "safe_directories": ["wp-admin", "wp-includes", "vendor"]
  }
}
```

---

## ⚙️ Cấu Hình Security Scanner Client

Để sử dụng API patterns, đảm bảo cấu hình sau trong `security_scan_client.php`:

```php
class SecurityClientConfig {
    // API Patterns Configuration
    const PATTERNS_API_URL = 'https://hiepcodeweb.com/api/security_patterns.php';
    const API_CACHE_DURATION = 3600; // 1 hour cache
    const ENABLE_API_PATTERNS = true;
}
```

---

## 🛡️ Tính Năng Bảo Mật

### Cache System
- API patterns được cache 1 giờ để giảm tải server
- Cache file: `cache/api_patterns.json`
- Tự động fallback về patterns cơ bản nếu API không khả dụng

### CORS Headers
```php
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

### Authentication (cho Update)
```php
// Chỉ cho phép update với auth key
const AUTH_KEY = 'hiep_security_2025_update_key';
```

---

## 📊 Blacklist Patterns

### File Extensions Nguy Hiểm
```
*.php.suspected, *.php.bak, *.php.old, *.php.tmp, *.phtml,
*.php3, *.php4, *.php5, *.php7, *.inc, *.txt.php, *.gif.php
```

### File Names Nguy Hiểm
```
shell.php, backdoor.php, webshell.php, c99.php, r57.php,
wso.php, b374k.php, adminer.php, phpinfo.php, test.php
```

### Content Patterns Nguy Hiểm
```php
// Code execution
eval\s*\(.*\$_, assert\s*\(.*\$_, system\s*\(.*\$_,
exec\s*\(.*\$_, shell_exec\s*\(.*\$_

// File operations
file_put_contents\s*\(.*\$_, fwrite\s*\(.*\$_

// Webshell signatures
c99|r57|wso|b374k|shell|backdoor
```

---

## ✅ Whitelist Patterns

### Framework Files An Toàn
```
wp-config.php, wp-load.php, wp-settings.php, composer.json,
package.json, artisan, configuration.php, settings.php
```

### Safe Directories
```
wp-admin, wp-includes, wp-content/themes, wp-content/plugins,
vendor, node_modules, assets, public, storage/framework
```

### Safe Extensions
```
*.css, *.js, *.json, *.txt, *.md, *.yml, *.xml, *.pdf,
*.doc, *.zip, *.tar, *.gz
```

---

## 🔄 Cách Hoạt Động

1. **Client khởi tạo** → Tự động load API patterns
2. **Cache check** → Kiểm tra cache (1 giờ)
3. **API call** → Lấy patterns mới nếu cần
4. **Scanning** → Ưu tiên quét blacklist, bỏ qua whitelist
5. **Enhanced detection** → Sử dụng API content patterns

### Workflow Priority
```
1. Scanner_config.php (excluded)
2. API Whitelist (skip)
3. API Blacklist (priority scan)
4. Priority Patterns (normal)
5. Regular Patterns (normal)
```

---

## 🚨 Lưu Ý Quan Trọng

### Hiệu Suất
- Cache patterns để tránh gọi API liên tục
- Fallback patterns nếu API không khả dụng
- Timeout 10 giây cho API calls

### Bảo Mật
- Validate tất cả input từ API
- Escape HTML content trong patterns
- Log errors nhưng không hiển thị chi tiết

### Monitoring
```bash
# Kiểm tra API hoạt động
curl -I "https://hiepcodeweb.com/api/security_patterns.php"

# Kiểm tra response time
time curl "https://hiepcodeweb.com/api/security_patterns.php?action=get_patterns"
```

---

## 📈 Update Patterns

### Thủ Công (POST Request)
```bash
curl -X POST "https://hiepcodeweb.com/api/security_patterns.php?action=update_patterns" \
  -d "auth_key=hiep_security_2025_update_key" \
  -d "type=blacklist" \
  -d "patterns={...json_data...}"
```

### Qua Admin Interface (Tương Lai)
- Web interface để quản lý patterns
- Real-time validation
- Version control cho patterns

---

## ✅ Checklist Deploy

- [ ] Upload `security_patterns.php` lên `/api/`
- [ ] Set permissions 644 cho file
- [ ] Tạo cache directory nếu cần
- [ ] Test API endpoints
- [ ] Kiểm tra CORS headers
- [ ] Verify cache functionality
- [ ] Test fallback patterns
- [ ] Monitor error logs

---

## 🆘 Troubleshooting

### API Không Hoạt Động
```php
// Kiểm tra error log
tail -f /var/log/apache2/error.log

// Test API trực tiếp
php -f /public_html/api/security_patterns.php
```

### Cache Issues
```bash
# Xóa cache để force reload
rm -f /public_html/api/cache/api_patterns.json
```

### Permission Errors
```bash
# Fix permissions
chmod 755 /public_html/api/
chmod 644 /public_html/api/security_patterns.php
```

---

**🎯 Mục Tiêu:** Tăng hiệu quả quét bảo mật lên **80%** với API patterns động và intelligent priority scanning! 