# 🛡️ Hệ Thống Quét Malware Nâng Cao

## 📋 Tổng Quan

Hệ thống quét malware client-server hoàn chỉnh với khả năng:
- ✅ Quét malware, webshell, backdoor
- ✅ Dashboard điều khiển trung tâm
- ✅ Quarantine file nguy hiểm
- ✅ Whitelist management
- ✅ Real-time monitoring
- ✅ Email alerts
- ✅ Scan history tracking

## 🏗️ Kiến Trúc Hệ Thống

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Website 1     │    │   Website 2     │    │   Website N     │
│ (Client Files)  │    │ (Client Files)  │    │ (Client Files)  │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          └──────────────────────┼──────────────────────┘
                                 │
                    ┌─────────────▼─────────────┐
                    │    Central Server         │
                    │   (Dashboard & Control)   │
                    └───────────────────────────┘
```

## 📁 Cấu Trúc Files

### 🔹 Files Client (Đặt trên từng website)
```
security_scan_client.php    # API client chính
config/
├── scanner_config.php      # Cấu hình
└── whitelist.json         # Danh sách whitelist
logs/
├── client_scan_*.log      # Log quét
├── quarantine.log         # Log quarantine
└── security_events_*.log  # Log bảo mật
quarantine/                # Thư mục cách ly files
```

### 🔹 Files Server (Đặt trên website trung tâm)
```
security_scan_server.php   # Dashboard điều khiển
config/
└── scanner_config.php     # Cấu hình server
data/
└── clients.json          # Danh sách clients
logs/
└── server_*.log          # Log server
```

## 🚀 Hướng Dẫn Cài Đặt

### Bước 1: Setup Client (Trên từng website cần quét)

1. **Upload files:**
```bash
# Upload lên thư mục gốc website
security_scan_client.php
config/scanner_config.php
```

2. **Cấu hình Client:**
Chỉnh sửa trong `security_scan_client.php`:
```php
const API_KEY = 'your-unique-api-key-here';
const CLIENT_NAME = 'website-name'; // Tên website
```

3. **Tạo thư mục cần thiết:**
```bash
mkdir -p logs quarantine config
chmod 755 logs quarantine config
```

4. **Test client:**
```
https://yourwebsite.com/security_scan_client.php?endpoint=health&api_key=your-api-key
```

### Bước 2: Setup Server (Website trung tâm)

1. **Upload files:**
```bash
# Upload lên website điều khiển trung tâm
security_scan_server.php
config/scanner_config.php
```

2. **Cấu hình Email:**
Chỉnh sửa trong `config/scanner_config.php`:
```php
const ADMIN_EMAIL = 'your-email@domain.com';
const SMTP_USERNAME = 'your-smtp-username';
const SMTP_PASSWORD = 'your-smtp-password';
```

3. **Tạo thư mục:**
```bash
mkdir -p data logs
chmod 755 data logs
```

4. **Truy cập Dashboard:**
```
https://your-control-website.com/security_scan_server.php
```

## 🎛️ Sử Dụng Dashboard

### 1. Thêm Client Mới
- Click "Thêm Client"
- Nhập tên website
- Nhập URL (VD: https://website.com)
- Nhập API Key (phải giống với client)
- Click "Lưu"

### 2. Quét Malware
- **Quét 1 client:** Click "Quét" bên cạnh tên client
- **Quét tất cả:** Click "Quét Tất Cả" ở thanh điều khiển
- **Quét tự động:** Thiết lập cron job

### 3. Xử Lý Threats
- **Xem chi tiết:** Click vào kết quả quét
- **Quarantine:** Click "Cách ly" để di chuyển file nguy hiểm
- **Xóa file:** Click "Xóa" để xóa hoàn toàn
- **Whitelist:** Click "Bỏ qua" để thêm vào whitelist

### 4. Monitoring
- Dashboard tự động refresh mỗi 30 giây
- Xem thống kê real-time
- Theo dõi trạng thái clients
- Nhận email alerts

## 🔧 Tính Năng Nâng Cao

### 1. Patterns Malware Toàn Diện
- **Critical Patterns:** eval(), system(), exec(), base64_decode()
- **Webshell Detection:** Phát hiện webshell phổ biến
- **Obfuscation Detection:** Phát hiện mã hóa/che giấu
- **File Signature:** Kiểm tra MD5 hash

### 2. Quarantine System
```php
// File nguy hiểm được di chuyển vào thư mục quarantine
./quarantine/2025-01-15_14-30-25_malicious_file.php
```

### 3. Whitelist Management
```json
{
    "./admin/config.php": {
        "reason": "Admin configuration file",
        "added_at": "2025-01-15 14:30:25",
        "md5": "5d41402abc4b2a76b9719d911017c592"
    }
}
```

### 4. API Endpoints

#### Client API:
```
GET  /security_scan_client.php?endpoint=health
GET  /security_scan_client.php?endpoint=status
POST /security_scan_client.php?endpoint=scan
POST /security_scan_client.php?endpoint=quarantine_file
GET  /security_scan_client.php?endpoint=scan_history
```

#### Server API:
```
GET  /security_scan_server.php?api=get_clients
POST /security_scan_server.php?api=add_client
POST /security_scan_server.php?api=scan_client
GET  /security_scan_server.php?api=get_dashboard_stats
```

## 📊 Monitoring & Alerts

### 1. Email Alerts
Tự động gửi email khi:
- Phát hiện webshell
- Số threats > 10
- Client offline > 1 giờ

### 2. Log Files
```
logs/security_events_2025-01-15.log
logs/client_scan_2025-01-15.log
logs/quarantine.log
```

### 3. Dashboard Stats
- Clients online/offline
- Active threats
- Infected sites
- Last scan time

## 🔒 Bảo Mật

### 1. API Security
- API Key authentication
- IP whitelist (tùy chọn)
- Rate limiting
- HTTPS recommended

### 2. File Security
- Path traversal protection
- File size limits
- Extension validation
- Quarantine isolation

### 3. Access Control
- Admin-only dashboard
- Secure file operations
- Audit logging

## 🛠️ Troubleshooting

### Lỗi Thường Gặp:

1. **"Invalid API key"**
   - Kiểm tra API key trong client và server
   - Đảm bảo API key giống nhau

2. **"Client offline"**
   - Kiểm tra URL client
   - Kiểm tra file security_scan_client.php có tồn tại
   - Kiểm tra permissions

3. **"Permission denied"**
   - Chmod 755 cho thư mục logs, quarantine
   - Kiểm tra owner/group của files

4. **"Memory limit exceeded"**
   - Tăng memory_limit trong PHP
   - Giảm MAX_SCAN_FILES trong config

### Debug Mode:
```php
// Thêm vào đầu file để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Performance Tuning

### 1. Scan Optimization
```php
// Trong config/scanner_config.php
const MAX_SCAN_FILES = 10000;    // Giảm nếu server yếu
const MAX_SCAN_TIME = 300;       // Giảm timeout
const MAX_FILE_SIZE = 10485760;  // 10MB limit
```

### 2. Exclude Directories
```php
const EXCLUDE_DIRS = [
    'cache', 'logs', 'tmp', 'uploads',
    'node_modules', 'vendor'
];
```

## 🔄 Cron Jobs (Tự Động)

### 1. Auto Scan (Hàng giờ)
```bash
0 * * * * /usr/bin/php /path/to/security_scan_server.php?api=scan_all
```

### 2. Daily Report (Hàng ngày)
```bash
0 8 * * * /usr/bin/php /path/to/security_scan_server.php?api=send_report
```

### 3. Cleanup Logs (Hàng tuần)
```bash
0 0 * * 0 find /path/to/logs -name "*.log" -mtime +7 -delete
```

## 📞 Hỗ Trợ

- **Author:** Hiệp Nguyễn
- **Email:** nguyenvanhiep0711@gmail.com
- **Version:** 2.0
- **Last Updated:** 2025-01-15

---

## 🎯 Kết Luận

Hệ thống quét malware này cung cấp:
- ✅ Bảo mật toàn diện cho nhiều websites
- ✅ Dashboard điều khiển trực quan
- ✅ Tự động hóa hoàn toàn
- ✅ Khả năng mở rộng cao
- ✅ Dễ dàng triển khai và sử dụng

**Lưu ý quan trọng:** Thay đổi API keys mặc định và cấu hình email trước khi sử dụng trong production!
