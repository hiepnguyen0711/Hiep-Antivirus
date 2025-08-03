# 🛡️ Security Scanner System - Hệ Thống Quét Bảo Mật Tập Trung

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com/your-repo)
[![PHP](https://img.shields.io/badge/PHP-5.6%2B-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> **Hệ thống quét bảo mật chuyên nghiệp cho nhiều website với khả năng tự động khắc phục, báo cáo email và quản lý tập trung.**

## 📋 Mục Lục

- [🎯 Tổng Quan](#-tổng-quan)
- [🏗️ Kiến Trúc Hệ Thống](#️-kiến-trúc-hệ-thống)
- [✨ Tính Năng Chính](#-tính-năng-chính)
- [🚀 Hướng Dẫn Cài Đặt](#-hướng-dẫn-cài-đặt)
- [🎛️ Sử Dụng Hệ Thống](#️-sử-dụng-hệ-thống)
- [🔧 Cấu Hình Nâng Cao](#-cấu-hình-nâng-cao)
- [📧 Báo Cáo Email](#-báo-cáo-email)
- [🛠️ Khắc Phục Tự Động](#️-khắc-phục-tự-động)
- [🔍 Troubleshooting](#-troubleshooting)
- [📞 Hỗ Trợ](#-hỗ-trợ)

---

## 🎯 Tổng Quan

**Security Scanner System** là giải pháp bảo mật toàn diện được thiết kế để quét và bảo vệ nhiều website từ một điểm quản lý trung tâm. Hệ thống sử dụng kiến trúc client-server với khả năng tự động hóa hoàn toàn.

### 🎪 Điểm Nổi Bật

- 🏢 **Quản lý tập trung**: Điều khiển nhiều website từ một dashboard duy nhất
- 🤖 **Tự động hóa**: Quét định kỳ, khắc phục tự động và báo cáo email
- 🛡️ **Bảo mật nâng cao**: Phát hiện 200+ patterns malware, shell, backdoor
- 📧 **Báo cáo thông minh**: Chỉ gửi email khi có threats nghiêm trọng
- 🔧 **Khắc phục tự động**: Sửa lỗ hỏng bảo mật mà không cần can thiệp thủ công
- 📱 **Responsive UI**: Giao diện hiện đại, hoạt động trên mọi thiết bị

---

## 🏗️ Kiến Trúc Hệ Thống

```
┌─────────────────────────────────────────────────────────────────┐
│                    SECURITY SCANNER SYSTEM                      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   SERVER LAYER  │    │  CLIENT LAYER   │    │ SCHEDULER LAYER │
│   (Quản lý)     │◄──►│  (Website)      │    │ (Tự động hóa)   │
│                 │    │                 │    │                 │
│ • Dashboard     │    │ • Scanner       │    │ • Daily Scan    │
│ • Remediation   │    │ • API Endpoints │    │ • Email Report  │
│ • Client Mgmt   │    │ • File Ops      │    │ • Cron Jobs     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 ▼
                    ┌─────────────────┐
                    │  EMAIL LAYER    │
                    │  (Báo cáo)      │
                    │                 │
                    │ • SMTP Gmail    │
                    │ • HTML Template │
                    │ • Smart Filter  │
                    └─────────────────┘
```

### 🔗 Các Thành Phần Chính

#### 1. **Server Layer** (Lớp Quản Lý)
- **File chính**: `security_scan_server.php`
- **Chức năng**: Dashboard, quản lý clients, remediation, báo cáo
- **Vị trí**: Website quản lý trung tâm

#### 2. **Client Layer** (Lớp Website)
- **Files**: `security_scan_client.php` hoặc `security_scan_client_standalone.php`
- **Chức năng**: Quét malware, API endpoints, file operations
- **Vị trí**: Mỗi website cần bảo vệ

#### 3. **Scheduler Layer** (Lớp Tự Động Hóa)
- **File**: `daily_security_scan.php`
- **Chức năng**: Quét tự động, lên lịch, trigger email
- **Vị trí**: Cron job trên server

#### 4. **Email Layer** (Lớp Báo Cáo)
- **Tích hợp**: SMTP Gmail với SSL/TLS
- **Template**: HTML responsive với Bootstrap
- **Logic**: Chỉ gửi khi có critical threats mới

---

## ✨ Tính Năng Chính

### 🔍 **Quét Bảo Mật Nâng Cao**
- ✅ **200+ Malware Patterns**: Shell, backdoor, webshell, obfuscated code
- ✅ **Smart Detection**: Phân tích nội dung file, không chỉ dựa vào tên
- ✅ **Risk Scoring**: Tính điểm rủi ro từ 1-100 cho mỗi threat
- ✅ **False Positive Reduction**: Whitelist và safe pattern filtering
- ✅ **Real-time Scanning**: Quét ngay lập tức hoặc theo lịch

### 🎛️ **Dashboard Quản Lý**
- ✅ **Multi-Client Management**: Quản lý unlimited websites
- ✅ **Real-time Status**: Online/offline status của từng client
- ✅ **Scan History**: Lịch sử quét chi tiết với timeline
- ✅ **Statistics Dashboard**: Biểu đồ thống kê threats và trends
- ✅ **Bulk Operations**: Quét tất cả clients cùng lúc

### 🛠️ **Remediation Engine**
- ✅ **Auto-Fix**: 6 loại khắc phục tự động
- ✅ **Smart Backup**: Backup tự động trước khi sửa
- ✅ **Rollback Support**: Khôi phục nếu có lỗi
- ✅ **Content Validation**: Kiểm tra syntax trước khi apply
- ✅ **Progress Tracking**: Theo dõi tiến độ real-time

### 📧 **Email Intelligence**
- ✅ **Smart Filtering**: Chỉ gửi email khi có threats nghiêm trọng mới
- ✅ **Professional Template**: HTML responsive với color-coding
- ✅ **Detailed Reports**: File paths, severity, timestamps
- ✅ **Multiple SMTP**: PHPMailer và mail() function fallback
- ✅ **Schedule Delivery**: Gửi vào 22:00 hàng ngày

### 🔐 **Bảo Mật & Performance**
- ✅ **API Authentication**: Unique API keys cho mỗi client
- ✅ **Rate Limiting**: Chống spam và abuse
- ✅ **Memory Management**: Tối ưu cho large websites
- ✅ **Timeout Handling**: Xử lý timeout gracefully
- ✅ **Error Recovery**: Auto-retry và fallback mechanisms

---

## 🚀 Hướng Dẫn Cài Đặt

### 📋 Yêu Cầu Hệ Thống

- **PHP**: 5.6+ (khuyến nghị 7.4+)
- **Extensions**: `curl`, `json`, `openssl`, `mbstring`
- **Memory**: Tối thiểu 128MB (khuyến nghị 512MB)
- **Disk Space**: 50MB cho logs và backups
- **Network**: HTTPS support cho secure communication

### 🏗️ Bước 1: Cài Đặt Server (Website Quản Lý)

#### 1.1 Tải và Upload Files

```bash
# Tạo thư mục cho hệ thống
mkdir security-scanner
cd security-scanner

# Upload các files chính
security_scan_server.php          # Dashboard và API server
daily_security_scan.php           # Cron job cho scheduler
test_email.php                    # Test email functionality
```

#### 1.2 Tạo Cấu Trúc Thư Mục

```bash
# Tạo thư mục cần thiết
mkdir -p data/logs data/backups config

# Phân quyền
chmod 755 data data/logs data/backups config
chmod 644 *.php
```

#### 1.3 Cấu Hình Email

Mở file `security_scan_server.php` và cập nhật:

```php
class EmailConfig
{
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 465;
    const SMTP_USERNAME = 'your-email@gmail.com';        // Email gửi
    const SMTP_PASSWORD = 'your-app-password';           // App Password
    const SMTP_ENCRYPTION = 'ssl';
    
    const REPORT_EMAIL = 'nguyenvanhiep0711@gmail.com';  // Email nhận
    const FROM_EMAIL = 'your-email@gmail.com';
    const FROM_NAME = 'Security Scanner System';
}
```

#### 1.4 Tạo File Clients

Tạo file `data/clients.json`:

```json
{
    "clients": []
}
```

#### 1.5 Test Server

Truy cập: `https://your-domain.com/security-scanner/security_scan_server.php`

### 🖥️ Bước 2: Cài Đặt Client (Website Cần Bảo Vệ)

#### 2.1 Lựa Chọn Client Type

**Option A: Standalone Client (Khuyến nghị)**
```bash
# Copy 1 file duy nhất
cp security_scan_client_standalone.php /path/to/website/security_scan_client.php
```

**Option B: Regular Client**
```bash
# Copy nhiều files
cp security_scan_client.php /path/to/website/
cp -r config/ /path/to/website/
```

#### 2.2 Cấu Hình Client

Chỉnh sửa trong file client:

```php
class SecurityClientConfig
{
    const API_KEY = 'unique-key-for-this-website-2025';  // Unique cho mỗi site
    const CLIENT_NAME = 'website-name';                   // Tên website
    const CLIENT_VERSION = '2.0';
    
    // Cấu hình quét
    const MAX_SCAN_FILES = 999999999;  // Unlimited
    const MAX_SCAN_TIME = 600;         // 10 phút
    const MAX_MEMORY = '512M';
}
```

#### 2.3 Test Client

```bash
curl "https://your-website.com/security_scan_client.php?action=health&api_key=your-api-key"
```

Kết quả mong đợi:
```json
{
    "success": true,
    "status": "online",
    "client_name": "website-name",
    "version": "2.0",
    "timestamp": "2025-01-03 10:30:00"
}
```

### 🔗 Bước 3: Kết Nối Server và Client

#### 3.1 Thêm Client Vào Server

1. Truy cập dashboard server
2. Click **"+ Thêm Client"**
3. Điền thông tin:
   ```
   Tên Client: Website ABC
   URL Client: https://website-abc.com/security_scan_client.php
   API Key: unique-key-for-this-website-2025
   ```
4. Click **"Lưu"**

#### 3.2 Kiểm Tra Kết Nối

1. Click **"Health"** bên cạnh client vừa thêm
2. Kết quả:
   - ✅ **Online**: Kết nối thành công
   - ❌ **Offline**: Kiểm tra URL và API key

#### 3.3 Chạy Scan Đầu Tiên

1. Click **"Quét"** để test scanning
2. Xem kết quả trong dashboard
3. Kiểm tra logs nếu có lỗi

---

## 🎛️ Sử Dụng Hệ Thống

### 📊 Dashboard Chính

#### Giao Diện Tổng Quan
```
┌─────────────────────────────────────────────────────────────┐
│  🛡️ Security Scanner Dashboard                              │
├─────────────────────────────────────────────────────────────┤
│  📊 Thống Kê:  [5 Clients] [3 Online] [12 Threats] [2 Critical] │
├─────────────────────────────────────────────────────────────┤
│  Client Name        Status    Last Scan    Threats  Actions │
│  ─────────────────────────────────────────────────────────  │
│  Website A         🟢 Online   2 phút trước    3      [Quét] [Khắc phục] │
│  Website B         🔴 Offline  1 giờ trước     0      [Health] [Quét]     │
│  Website C         🟢 Online   5 phút trước    8      [Quét] [Khắc phục] │
└─────────────────────────────────────────────────────────────┘
```

#### Các Chức Năng Chính

**1. Quét Đơn Lẻ**
- Click **"Quét"** bên cạnh client
- Xem progress bar real-time
- Kết quả hiển thị ngay sau khi hoàn thành

**2. Quét Tất Cả**
- Click **"Quét Tất Cả"** ở header
- Hệ thống quét tuần tự từng client
- Email báo cáo tự động nếu có threats

**3. Health Check**
- Click **"Health"** để kiểm tra kết nối
- Cập nhật status real-time
- Hiển thị thông tin client (PHP version, memory, etc.)

### 🔍 Xem Chi Tiết Scan

#### Kết Quả Scan
```json
{
    "scan_id": "scan_20250103_103000",
    "client": "Website A",
    "start_time": "2025-01-03 10:30:00",
    "duration": 45,
    "total_files": 1250,
    "scanned_files": 1200,
    "threats": [
        {
            "file": "/wp-content/uploads/shell.php",
            "threat_level": 9,
            "risk_score": 85,
            "patterns": ["eval(", "base64_decode(", "shell_exec("],
            "size": 2048,
            "modified": "2025-01-03 09:15:00"
        }
    ]
}
```

#### Phân Loại Threats

| Mức Độ | Màu Sắc | Mô Tả | Hành Động |
|---------|----------|-------|-----------|
| **9-10** | 🔴 Critical | Shell, backdoor, malware | Xóa ngay lập tức |
| **7-8** | 🟠 High | Suspicious code, obfuscated | Kiểm tra và xử lý |
| **4-6** | 🟡 Medium | Potentially unwanted | Theo dõi |
| **1-3** | 🔵 Low | False positive possible | Whitelist nếu cần |

---

## 🔧 Cấu Hình Nâng Cao

### ⚙️ Server Configuration

#### Tùy Chỉnh Scan Settings
```php
class SecurityServerConfig
{
    const MAX_CONCURRENT_SCANS = 10;    // Số scan đồng thời
    const SCAN_TIMEOUT = 300;           // Timeout (giây)
    const DEFAULT_API_KEY = 'your-default-key';
    
    // Email settings
    const ADMIN_EMAIL = 'admin@domain.com';
    const EMAIL_FROM_NAME = 'Security Center';
}
```

#### Database Configuration (Tùy chọn)
```php
// Nếu muốn dùng database thay vì JSON files
class DatabaseConfig
{
    const DB_HOST = 'localhost';
    const DB_NAME = 'security_scanner';
    const DB_USER = 'username';
    const DB_PASS = 'password';
}
```

### 🎯 Client Configuration

#### Scan Optimization
```php
class SecurityClientConfig
{
    // Performance tuning
    const MAX_SCAN_FILES = 50000;      // Giới hạn files
    const MAX_SCAN_TIME = 600;         // 10 phút
    const MAX_MEMORY = '512M';         // Memory limit
    const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    
    // Exclusions
    const EXCLUDE_DIRS = [
        '.git', 'node_modules', 'vendor', 'cache'
    ];
    
    const EXCLUDE_EXTENSIONS = [
        'jpg', 'png', 'gif', 'pdf', 'zip'
    ];
}
```

#### Custom Patterns
```php
// Thêm patterns tùy chỉnh
const CUSTOM_PATTERNS = [
    'your_malware_signature' => 'Custom malware description',
    'suspicious_function(' => 'Suspicious function call',
    'backdoor_marker' => 'Known backdoor marker'
];
```

### 🕐 Scheduler Configuration

#### Cron Job Setup
```bash
# Quét hàng ngày lúc 22:00
0 22 * * * /usr/bin/php /path/to/daily_security_scan.php

# Hoặc sử dụng wget
0 22 * * * wget -q -O - "https://domain.com/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +\%Y-\%m-\%d)"

# Cleanup logs hàng tuần
0 0 * * 0 find /path/to/logs -name "*.log" -mtime +7 -delete
```

#### Email Schedule
```php
class SchedulerConfig
{
    const SCAN_TIME = '22:00';          // Giờ quét (UTC+7)
    const EMAIL_THRESHOLD = 8;          // Chỉ gửi email nếu threat_level >= 8
    const MAX_EMAIL_PER_DAY = 3;        // Tối đa 3 email/ngày
    const TIMEZONE = 'Asia/Ho_Chi_Minh';
}
```

---

## 📧 Báo Cáo Email

### 📮 Cấu Hình Gmail SMTP

#### Bước 1: Tạo App Password
1. Truy cập [Google Account Settings](https://myaccount.google.com/)
2. **Security** → **2-Step Verification** (bật nếu chưa có)
3. **App passwords** → **Mail** → **Generate**
4. Copy password và paste vào config

#### Bước 2: Cập nhật EmailConfig
```php
class EmailConfig
{
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 465;                              // SSL port
    const SMTP_USERNAME = 'your-email@gmail.com';
    const SMTP_PASSWORD = 'abcd efgh ijkl mnop';        // App password (16 ký tự)
    const SMTP_ENCRYPTION = 'ssl';

    const REPORT_EMAIL = 'nguyenvanhiep0711@gmail.com';
    const FROM_EMAIL = 'your-email@gmail.com';
    const FROM_NAME = 'Security Scanner System';
}
```

### 📧 Email Template

#### Cấu Trúc Email
```html
🚨 BÁO CÁO BẢO MẬT KHẨN CẤP - 03/01/2025 22:00

┌─────────────────────────────────────────┐
│  📊 THỐNG KÊ TỔNG QUAN                   │
├─────────────────────────────────────────┤
│  • Websites bị ảnh hưởng: 2             │
│  • Mối đe dọa nghiêm trọng: 5           │
│  • Thời gian phát hiện: 22:00           │
└─────────────────────────────────────────┘

🌐 Website A (https://website-a.com)
├── 📁 /wp-content/uploads/shell.php
│   ├── Mức độ: CỰC KỲ NGUY HIỂM (9/10)
│   ├── Kích thước: 2,048 bytes
│   ├── Cập nhật: 03/01/2025 09:15:00
│   └── Threats: eval(), base64_decode(), shell_exec()
│
└── 📁 /admin/backdoor.php
    ├── Mức độ: NGUY HIỂM CAO (8/10)
    ├── Kích thước: 1,024 bytes
    ├── Cập nhật: 03/01/2025 10:30:00
    └── Threats: system(), passthru()
```

#### Logic Gửi Email
```php
// Chỉ gửi email khi:
if ($threat_level >= 8 && $file_modified_today && $critical_count > 0) {
    sendEmail($threats);
}
```

### 🧪 Test Email

#### Manual Test
```bash
# Test qua browser
https://your-domain.com/test_email.php

# Test qua API
curl "https://domain.com/security_scan_server.php?api=test_email&admin_key=hiep-admin-test-2025"
```

#### Troubleshooting Email
```php
// Enable debug mode
$mail->SMTPDebug = 2;  // Hiển thị SMTP debug info

// Check logs
tail -f data/logs/scheduler.log
```

---

## 🛠️ Khắc Phục Tự Động (Remediation)

### 🎯 Các Loại Khắc Phục

#### 1. Enhanced Shell Detection
```php
// Nâng cấp hàm check_shell() với 55+ patterns
'enhanced_shell_detection' => [
    'title' => 'Nâng Cấp Phát Hiện Shell & Malware',
    'file' => 'admin/lib/function.php',
    'severity' => 'critical',
    'description' => 'Thêm 55+ patterns phát hiện shell, webshell, backdoor'
]
```

#### 2. HiepSecurity Class
```php
// Thêm class bảo mật tổng hợp
'hiep_security_class' => [
    'title' => 'Thêm HiepSecurity Class Bảo Mật',
    'file' => 'admin/lib/class.php',
    'severity' => 'critical',
    'description' => 'Input sanitization, XSS protection, rate limiting'
]
```

#### 3. CSRF Protection
```php
// Bảo mật .htaccess
'htaccess_csrf_protection' => [
    'title' => 'Bảo Mật .htaccess với CSRF Protection',
    'file' => '.htaccess',
    'severity' => 'critical',
    'description' => 'Security headers, CSRF protection, clickjacking prevention'
]
```

#### 4. PHP Compatibility
```php
// Sửa lỗi PHP 7.x+
'php_compatibility_fixes' => [
    'title' => 'Sửa Lỗi Tương Thích PHP 7.x+',
    'file' => 'admin/lib/class.php',
    'severity' => 'warning',
    'description' => 'Thay thế deprecated syntax, fix each() function'
]
```

#### 5. Admin Security
```php
// Bảo mật admin panel
'admin_htaccess_balanced' => [
    'title' => 'Bảo Mật Admin Panel Cân Bằng',
    'file' => 'admin/.htaccess',
    'severity' => 'warning',
    'description' => 'Bot blocking, rate limiting, file protection'
]
```

#### 6. File Upload Security
```php
// Bảo mật upload
'file_upload_security' => [
    'title' => 'Bảo Mật Upload File Nâng Cao',
    'file' => 'admin/filemanager/security_config.php',
    'severity' => 'critical',
    'description' => 'MIME validation, malicious content detection'
]
```

### 🔄 Quy Trình Remediation

#### Workflow Tự Động
```
1. User chọn fixes → 2. Tạo backup → 3. Apply fixes → 4. Validate → 5. Success/Rollback
```

#### Chi Tiết Từng Bước

**Bước 1: Backup**
```php
// Format: filename.hiep_bk_YYYYMMDD_HHMMSS.ext
$backupPath = 'admin/lib/function.hiep_bk_20250103_220000.php';
```

**Bước 2: Apply Fix**
```php
// Tìm và thay thế nội dung cụ thể
$pattern = '/function check_shell\([^{]*\{[^}]*\}/s';
$newContent = preg_replace($pattern, $enhancedFunction, $originalContent);
```

**Bước 3: Validation**
```php
// Kiểm tra syntax PHP
if (php_check_syntax($newContent)) {
    saveFile($filePath, $newContent);
} else {
    rollback($filePath, $originalContent);
}
```

**Bước 4: Results**
```json
{
    "enhanced_shell_detection": {
        "success": true,
        "backup_path": "admin/lib/function.hiep_bk_20250103_220000.php",
        "fixes_applied": 1,
        "file_status": "Đã cập nhật file hiện có"
    }
}
```

### 🎛️ Sử Dụng Remediation

#### Từ Dashboard
1. Click **"Khắc phục"** bên cạnh client
2. Chọn fixes cần áp dụng
3. Click **"Thực hiện khắc phục"**
4. Xem progress và kết quả

#### Qua API
```bash
curl -X POST "https://domain.com/security_scan_server.php?api=execute_remediation&client_id=client_123" \
  -H "Content-Type: application/json" \
  -d '{"selected_fixes":["enhanced_shell_detection","csrf_protection"]}'
```
