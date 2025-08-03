# 🛡️ Hiệp Antivirus - Hệ Thống Quét Bảo Mật Tự Động

> **Hệ thống quét bảo mật chuyên nghiệp cho nhiều website** - Phát hiện và xử lý malware, webshell, backdoor tự động với dashboard trung tâm và cảnh báo email thời gian thực.

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com/hiepcodeweb/hiep-antivirus)
[![PHP](https://img.shields.io/badge/PHP-5.6%2B-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## 📋 Mục Lục

- [🎯 Tổng Quan Hệ Thống](#-tổng-quan-hệ-thống)
- [🏗️ Kiến Trúc & Thành Phần](#️-kiến-trúc--thành-phần)
- [⚡ Tính Năng Chính](#-tính-năng-chính)
- [🚀 Hướng Dẫn Cài Đặt](#-hướng-dẫn-cài-đặt)
- [📖 Hướng Dẫn Sử Dụng](#-hướng-dẫn-sử-dụng)
- [⚙️ Cấu Hình Nâng Cao](#️-cấu-hình-nâng-cao)
- [🔄 Tự Động Hóa & Scheduler](#-tự-động-hóa--scheduler)
- [🔧 Troubleshooting](#-troubleshooting)
- [📚 API Documentation](#-api-documentation)
- [🔒 Bảo Mật](#-bảo-mật)
- [📞 Hỗ Trợ](#-hỗ-trợ)

---

## 🎯 Tổng Quan Hệ Thống

**Hiệp Antivirus** là hệ thống quét bảo mật tự động được thiết kế để bảo vệ nhiều website từ một dashboard trung tâm. Hệ thống sử dụng kiến trúc client-server với khả năng mở rộng cao, tự động hóa hoàn toàn và cảnh báo thời gian thực.

### 🎯 Mục Tiêu Chính
- **Bảo mật tập trung**: Quản lý bảo mật cho nhiều website từ một điểm duy nhất
- **Phát hiện sớm**: Tự động phát hiện malware, webshell, backdoor
- **Phản ứng nhanh**: Cảnh báo email tức thì khi phát hiện threats
- **Tự động hóa**: Quét định kỳ và báo cáo không cần can thiệp thủ công

---

## 🏗️ Kiến Trúc & Thành Phần

### 📊 Sơ Đồ Kiến Trúc Tổng Quan

```
┌─────────────────────────────────────────────────────────────────┐
│                    🌐 SERVER LAYER (Lớp Quản Lý)                │
├─────────────────────────────────────────────────────────────────┤
│  📊 Dashboard      │  👥 Client Manager  │  📧 Email Manager    │
│  🔍 Scanner Mgr    │  🛡️ Security API    │  📈 Report System   │
└─────────────────────────────────────────────────────────────────┘
                                    │
                            🌐 HTTPS/API Communication
                                    │
┌─────────────────────────────────────────────────────────────────┐
│                   💻 CLIENT LAYER (Lớp Website)                 │
├─────────────────────────────────────────────────────────────────┤
│  🔍 Scanner Engine │  📁 File Manager    │  🔒 Quarantine Sys  │
│  ✅ Whitelist Mgr  │  📊 Health Monitor  │  🔧 API Endpoints   │
└─────────────────────────────────────────────────────────────────┘
                                    │
                            ⏰ Scheduled Tasks
                                    │
┌─────────────────────────────────────────────────────────────────┐
│                 ⏰ SCHEDULER LAYER (Lớp Tự Động)                │
├─────────────────────────────────────────────────────────────────┤
│  🕐 Cron Manager   │  📧 Auto Reports    │  🧹 Log Cleanup     │
│  🔄 Auto Scanner   │  📊 Health Checks   │  💾 Backup System   │
└─────────────────────────────────────────────────────────────────┘
```

### 🔗 Các Thành Phần Chính

#### 1. **Server Layer** (Lớp Quản Lý)
- **File chính**: `security_scan_server.php`
- **Chức năng**: Dashboard, quản lý clients, remediation, báo cáo
- **Vị trí**: Website quản lý trung tâm
- **Thành phần**:
  - `ClientManager`: Quản lý danh sách clients
  - `ScannerManager`: Điều phối quét bảo mật
  - `EmailManager`: Xử lý gửi email cảnh báo
  - `DashboardManager`: Giao diện web và API

#### 2. **Client Layer** (Lớp Website)
- **Files**: `security_scan_client.php`, `security_scan_client_standalone.php`
- **Chức năng**: Quét malware, API endpoints, file operations
- **Vị trí**: Mỗi website cần bảo vệ
- **Thành phần**:
  - `SecurityScanner`: Engine quét chính
  - `FileManager`: Quản lý file operations
  - `QuarantineManager`: Hệ thống cách ly
  - `WhitelistManager`: Quản lý whitelist

#### 3. **Scheduler Layer** (Lớp Tự Động Hóa)
- **File**: `daily_security_scan.php`
- **Chức năng**: Quét tự động, lên lịch, trigger email
- **Vị trí**: Cron job trên server
- **Thành phần**:
  - `CronManager`: Quản lý lịch trình
  - `AutoScanner`: Quét tự động
  - `ReportGenerator`: Tạo báo cáo

#### 4. **Email System** (Hệ Thống Email)
- **Files**: `smtp/class.phpmailer.php`, email templates
- **Chức năng**: Gửi cảnh báo và báo cáo
- **Cấu hình**: Gmail SMTP với SSL port 465
- **Templates**: HTML email với styling chuyên nghiệp

#### 5. **Data Layer** (Lớp Dữ Liệu)
- **Database**: JSON files (không cần MySQL)
- **Files**: `data/clients.json`, logs, cache
- **Backup**: Tự động backup cấu hình

---

## ⚡ Tính Năng Chính

### 🔍 **Quét Bảo Mật Nâng Cao**
- **Pattern Matching**: Hơn 500+ patterns malware được cập nhật
- **Priority Files Scanner**: Ưu tiên quét files nghi ngờ
- **Deep Scanning**: Quét nội dung file với line-by-line analysis
- **False Positive Reduction**: Whitelist thông minh giảm báo sai

### 🌐 **Quản Lý Nhiều Website**
- **Centralized Dashboard**: Điều khiển tất cả từ một giao diện
- **Real-time Status**: Theo dõi trạng thái clients thời gian thực
- **Bulk Operations**: Quét tất cả clients cùng lúc
- **Client Health Monitoring**: Kiểm tra kết nối và hiệu suất

### 📧 **Hệ Thống Cảnh Báo Thông Minh**
- **Instant Alerts**: Email tức thì khi phát hiện critical threats
- **Daily Reports**: Báo cáo tổng hợp hàng ngày
- **Threat Classification**: Phân loại mức độ nguy hiểm
- **Email Templates**: Giao diện email chuyên nghiệp

### 🔄 **Tự Động Hóa Hoàn Toàn**
- **Scheduled Scans**: Quét theo lịch trình tự động
- **Auto Remediation**: Tự động xử lý threats
- **Log Management**: Tự động dọn dẹp logs cũ
- **Backup & Recovery**: Sao lưu tự động

### 🛡️ **Remediation & Recovery**
- **Quarantine System**: Cách ly files nguy hiểm
- **File Restoration**: Khôi phục files từ quarantine
- **Whitelist Management**: Quản lý danh sách an toàn
- **Manual Review**: Xem xét thủ công files nghi ngờ

### 📊 **Dashboard & Reporting**
- **Modern UI**: Giao diện hiện đại, responsive
- **Real-time Charts**: Biểu đồ thống kê thời gian thực
- **Detailed Reports**: Báo cáo chi tiết với metadata
- **Export Functions**: Xuất báo cáo PDF/Excel

---

## 🚀 Hướng Dẫn Cài Đặt

### 📋 Yêu Cầu Hệ Thống

- **PHP**: 5.6+ (khuyến nghị 7.4+)
- **Web Server**: Apache/Nginx
- **Extensions**: `curl`, `json`, `openssl`, `fileinfo`
- **Permissions**: Quyền ghi thư mục `data/`, `logs/`
- **Email**: SMTP server (Gmail khuyến nghị)

### 🔧 Bước 1: Cài Đặt Server (Website Quản Lý Trung Tâm)

**Bước 1.1: Upload Files**
```bash
# Upload file chính
security_scan_server.php

# Upload thư mục hỗ trợ
smtp/                    # PHPMailer library
api/                     # API patterns
assets/                  # CSS, JS, images
```

**Bước 1.2: Tạo Cấu Trúc Thư Mục**
```bash
mkdir -p data/logs
mkdir -p data/backups
chmod 755 data/
chmod 755 data/logs/
chmod 755 data/backups/
```

**Bước 1.3: Cấu Hình Email SMTP**
Chỉnh sửa trong `security_scan_server.php`:
```php
class SecurityServerConfig
{
    // Email Settings - QUAN TRỌNG: Thay đổi thông tin này
    const ADMIN_EMAIL = 'your-admin@gmail.com';
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 465;                    // SSL port
    const SMTP_USERNAME = 'your-email@gmail.com';
    const SMTP_PASSWORD = 'your-app-password'; // Gmail App Password
    const SMTP_SECURE = 'ssl';

    // Server Settings
    const SERVER_NAME = 'Your Security Center';
    const DEFAULT_API_KEY = 'your-unique-api-key-2025';
}
```

**Bước 1.4: Truy Cập Dashboard**
```
https://your-control-website.com/security_scan_server.php
```

### 🖥️ Bước 2: Cài Đặt Client (Trên Từng Website Cần Bảo Vệ)

**Bước 2.1: Upload Client File**
```bash
# Upload lên root directory của website
security_scan_client.php
```

**Bước 2.2: Cấu Hình Client**
Chỉnh sửa trong `security_scan_client.php`:
```php
class SecurityClientConfig
{
    // API Key - PHẢI GIỐNG VỚI SERVER
    const API_KEY = 'your-unique-api-key-2025';
    const CLIENT_NAME = 'website-name';

    // Scan Settings
    const MAX_SCAN_FILES = 999999999;
    const MAX_SCAN_TIME = 600;
    const MAX_MEMORY = '512M';
}
```

**Bước 2.3: Tạo Thư Mục Client**
```bash
mkdir -p logs/
mkdir -p quarantine/
chmod 755 logs/
chmod 755 quarantine/
```

**Bước 2.4: Test Kết Nối**
```bash
# Test health check
curl "https://your-website.com/security_scan_client.php?endpoint=health&api_key=your-api-key"
```

### ⏰ Bước 3: Cấu Hình Scheduler (Tùy Chọn)

**Bước 3.1: Upload Scheduler**
```bash
# Upload file scheduler
daily_security_scan.php
```

**Bước 3.2: Cấu Hình Cron Job**

**Linux/Unix:**
```bash
# Mở crontab editor
crontab -e

# Thêm dòng sau (quét hàng ngày lúc 2:00 AM)
0 2 * * * /usr/bin/php /path/to/daily_security_scan.php daily_scan >/dev/null 2>&1

# Dọn dẹp logs hàng tuần (Chủ nhật 3:00 AM)
0 3 * * 0 /usr/bin/php /path/to/daily_security_scan.php cleanup >/dev/null 2>&1
```

**Windows Task Scheduler:**
```cmd
# Tạo task mới
schtasks /create /tn "Security Daily Scan" /tr "php.exe C:\path\to\daily_security_scan.php daily_scan" /sc daily /st 02:00
```

**Hoặc sử dụng Web Cron:**
```bash
# Quét hàng ngày
0 2 * * * curl -s "https://your-domain.com/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +\%Y-\%m-\%d)" >/dev/null 2>&1
```

---

## 📖 Hướng Dẫn Sử Dụng

### 🎛️ Dashboard Server

#### **Bước 1: Thêm Client Mới**
1. Truy cập dashboard server
2. Click nút **"Thêm Client"**
3. Điền thông tin:
   - **Tên Client**: Tên website (VD: "Website ABC")
   - **URL**: Đường dẫn đầy đủ (VD: "https://website.com")
   - **API Key**: Key giống với client
4. Click **"Lưu"**
5. Kiểm tra trạng thái kết nối

#### **Bước 2: Quét Malware**

**Quét Một Client:**
1. Tìm client trong danh sách
2. Click nút **"Quét"** bên cạnh tên client
3. Chờ quá trình quét hoàn tất
4. Xem kết quả trong popup

**Quét Tất Cả Clients:**
1. Click nút **"Quét Tất Cả"** ở thanh điều khiển
2. Theo dõi tiến trình quét
3. Nhận email báo cáo tổng hợp

**Priority Files Scanner:**
1. Click mở **"Priority Files Scanner"**
2. Nhập patterns cần ưu tiên (VD: `shell.php`, `*.php.txt`)
3. Click **Enter** hoặc chọn từ suggestions
4. Các files này sẽ được quét trước tiên

#### **Bước 3: Xử Lý Threats**

**Xem Chi Tiết Threat:**
1. Click vào file trong danh sách kết quả
2. Xem thông tin chi tiết: patterns, line numbers, metadata
3. Đánh giá mức độ nguy hiểm

**Các Hành Động Có Thể:**
- **🗑️ Xóa File**: Xóa vĩnh viễn (cẩn thận!)
- **🔒 Quarantine**: Cách ly file an toàn
- **✅ Whitelist**: Thêm vào danh sách an toàn
- **📝 Edit**: Chỉnh sửa nội dung file
- **📥 Download**: Tải file về để phân tích

#### **Bước 4: Quản Lý Whitelist**
1. Vào phần **"Whitelist Management"**
2. Thêm patterns an toàn:
   - File paths: `/wp-content/themes/theme-name/functions.php`
   - Content patterns: `wp_enqueue_script`
3. Áp dụng cho tất cả clients hoặc từng client riêng

### 📧 Hệ Thống Email

#### **Cấu Hình Gmail SMTP**
1. Bật 2-Factor Authentication cho Gmail
2. Tạo App Password:
   - Vào Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Chọn "Mail" và "Other"
   - Copy password được tạo
3. Sử dụng App Password trong cấu hình SMTP

#### **Loại Email Được Gửi**
- **🚨 Critical Alerts**: Khi phát hiện webshell/backdoor
- **📊 Daily Reports**: Báo cáo tổng hợp hàng ngày
- **⚠️ Warning Notifications**: Khi có nhiều suspicious files
- **📈 Weekly Summaries**: Tóm tắt tuần (nếu cấu hình)

---

## ⚙️ Cấu Hình Nâng Cao

### 🔧 Server Configuration

#### **Tùy Chỉnh Scan Settings**
```php
class SecurityServerConfig
{
    const MAX_CONCURRENT_SCANS = 10;    // Số scan đồng thời
    const SCAN_TIMEOUT = 300;           // Timeout (giây)
    const DEFAULT_API_KEY = 'your-default-key';

    // Email settings
    const ADMIN_EMAIL = 'admin@domain.com';
    const EMAIL_FROM_NAME = 'Security Center';

    // Advanced settings
    const ENABLE_DEBUG_LOGS = false;
    const AUTO_QUARANTINE_CRITICAL = true;
    const WHITELIST_AUTO_LEARN = true;
}
```

#### **Database Configuration**
```php
// File: data/clients.json
{
    "clients": [
        {
            "id": "unique-client-id",
            "name": "Website Name",
            "url": "https://website.com/security_scan_client.php",
            "api_key": "client-specific-api-key",
            "status": "online|offline|error",
            "last_scan": "2025-01-16 10:30:00",
            "scan_frequency": "daily|weekly|manual",
            "auto_remediation": true,
            "whitelist_enabled": true
        }
    ]
}
```

### 🖥️ Client Configuration

#### **Advanced Client Settings**
```php
class SecurityClientConfig
{
    // Scan Limits
    const MAX_SCAN_FILES = 999999999;
    const MAX_SCAN_TIME = 600;
    const MAX_MEMORY = '512M';

    // API Settings
    const PATTERNS_API_URL = 'https://your-server.com/api/security_patterns.php';
    const API_CACHE_DURATION = 3600;
    const ENABLE_API_PATTERNS = true;

    // Security Settings
    const ENABLE_QUARANTINE = true;
    const AUTO_BACKUP_BEFORE_DELETE = true;
    const STRICT_MODE = false;

    // Logging
    const ENABLE_LOGGING = true;
    const LOG_LEVEL = 'INFO'; // DEBUG, INFO, WARNING, ERROR
}
```

#### **Whitelist Configuration**
```json
// File: config/whitelist.json
{
    "file_patterns": [
        "/wp-content/themes/*/functions.php",
        "/wp-content/plugins/*/plugin-name.php"
    ],
    "content_patterns": [
        "wp_enqueue_script",
        "add_action",
        "apply_filters"
    ],
    "directories": [
        "/wp-admin/",
        "/wp-includes/"
    ]
}
```

---

## 🔄 Tự Động Hóa & Scheduler

### ⏰ Scheduler Configuration

#### **Cấu Hình Lịch Trình**
```php
class SchedulerConfig
{
    const DAILY_SCAN_TIME = '02:00';       // Quét lúc 2:00 AM
    const WEEKLY_SCAN_DAY = 'sunday';      // Quét tổng hợp Chủ nhật
    const MONTHLY_REPORT_DAY = 1;          // Báo cáo tháng ngày 1

    const EMAIL_ON_CRITICAL = true;        // Email ngay khi có critical
    const EMAIL_DAILY_SUMMARY = true;      // Email tóm tắt hàng ngày
    const EMAIL_WEEKLY_REPORT = true;      // Email báo cáo tuần

    const KEEP_LOGS_DAYS = 30;             // Giữ logs 30 ngày
    const AUTO_CLEANUP = true;             // Tự động dọn dẹp
}
```

#### **Cron Job Setup Chi Tiết**

**Linux/Unix:**
```bash
# Mở crontab editor
crontab -e

# Quét bảo mật hàng ngày lúc 2:00 AM
0 2 * * * /usr/bin/php /path/to/daily_security_scan.php daily_scan >/dev/null 2>&1

# Báo cáo tổng hợp hàng tuần (Chủ nhật 3:00 AM)
0 3 * * 0 /usr/bin/php /path/to/daily_security_scan.php cleanup >/dev/null 2>&1

# Dọn dẹp logs cũ (hàng tháng)
0 4 1 * * find /path/to/data/logs -name "*.log" -mtime +30 -delete >/dev/null 2>&1

# Backup cấu hình (hàng tuần)
0 5 * * 0 cp /path/to/data/clients.json /path/to/data/backups/clients_$(date +\%Y\%m\%d).json
```

**Windows Task Scheduler:**
```cmd
# Tạo task quét hàng ngày
schtasks /create /tn "Security Daily Scan" /tr "php.exe C:\path\to\daily_security_scan.php daily_scan" /sc daily /st 02:00

# Tạo task cleanup hàng tuần
schtasks /create /tn "Security Weekly Cleanup" /tr "php.exe C:\path\to\daily_security_scan.php cleanup" /sc weekly /d SUN /st 03:00
```

**Web-based Cron (Hosting providers):**
```bash
# URL cho daily scan
https://your-domain.com/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +%Y-%m-%d)

# URL cho cleanup
https://your-domain.com/daily_security_scan.php?action=cleanup&key=your-secret-key
```

### 📊 Monitoring & Alerts

#### **Email Alert Triggers**
- **Critical Threats**: Threat level ≥ 9
- **Multiple Threats**: Hơn 10 suspicious files
- **Client Offline**: Client không phản hồi > 1 giờ
- **Scan Failures**: Lỗi quét liên tiếp > 3 lần
- **Disk Space**: Logs/quarantine > 1GB

#### **Log Management**
```php
// Auto cleanup configuration
const LOG_RETENTION_DAYS = 30;
const MAX_LOG_SIZE_MB = 100;
const COMPRESS_OLD_LOGS = true;
const EMAIL_LOG_ERRORS = true;
```

---

## 🔧 Troubleshooting

### ❗ Lỗi Thường Gặp

#### **1. Client Không Kết Nối Được**

**Triệu chứng:**
- Dashboard hiển thị "Client offline"
- Lỗi "Connection timeout"
- Status "error" trong danh sách clients

**Giải pháp:**

**Bước 1: Kiểm tra API Key**
```php
// Trong security_scan_client.php
const API_KEY = 'hiep-security-client-2025-change-this-key';

// Phải giống với server
const DEFAULT_API_KEY = 'hiep-security-client-2025-change-this-key';
```

**Bước 2: Verify URL Client**
```bash
# Test trực tiếp
curl "https://your-website.com/security_scan_client.php?endpoint=health&api_key=your-api-key"

# Kết quả mong đợi:
{"status":"ok","client_name":"website-name","version":"1.0"}
```

**Bước 3: Kiểm tra Firewall/Security Plugins**
- Whitelist IP server trong firewall
- Tắt tạm security plugins (Wordfence, etc.)
- Kiểm tra .htaccess rules

#### **2. Email Không Gửi Được**

**Triệu chứng:**
- Không nhận được email alerts
- Lỗi "SMTP connection failed"
- Email vào spam folder

**Giải pháp:**

**Bước 1: Kiểm tra Gmail App Password**
```php
// Đảm bảo sử dụng App Password, không phải password thường
const SMTP_PASSWORD = 'abcd efgh ijkl mnop'; // 16 ký tự App Password
```

**Bước 2: Test Email Function**
```bash
# Test email qua API
curl "https://your-server.com/security_scan_server.php?api=test_email&admin_key=hiep-admin-test-2025"
```

#### **3. Quét Không Hoạt Động**

**Triệu chứng:**
- Quét bị timeout
- Không tìm thấy files
- Memory limit exceeded

**Giải pháp:**

**Bước 1: Kiểm tra Quyền Thư Mục**
```bash
# Đảm bảo quyền đúng
chmod 755 data/
chmod 755 logs/
chmod 755 quarantine/
```

**Bước 2: Tăng PHP Limits**
```php
// Trong security_scan_client.php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);
set_time_limit(600);
```

---

## 📚 API Documentation

### 🌐 Server API Endpoints

#### **GET /security_scan_server.php?api=get_clients**
Lấy danh sách tất cả clients

**Response:**
```json
{
    "success": true,
    "clients": [
        {
            "id": "client_id",
            "name": "Website Name",
            "url": "https://website.com",
            "status": "online",
            "last_scan": "2025-01-16 10:30:00"
        }
    ]
}
```

#### **POST /security_scan_server.php?api=scan_client&id={client_id}**
Quét một client cụ thể

**Request Body:**
```json
{
    "priority_files": ["shell.php", "*.php.txt", "config.*"]
}
```

**Response:**
```json
{
    "success": true,
    "scan_results": {
        "scanned_files": 1250,
        "suspicious_count": 3,
        "critical_count": 1,
        "threats": [
            {
                "file": "/suspicious/file.php",
                "threat_level": 9,
                "patterns": ["eval(", "base64_decode"],
                "size": 2048,
                "modified": "2025-01-15 14:30:00"
            }
        ]
    }
}
```

### 💻 Client API Endpoints

#### **GET /security_scan_client.php?endpoint=health&api_key={key}**
Kiểm tra trạng thái client

**Response:**
```json
{
    "status": "ok",
    "client_name": "website-name",
    "version": "1.0",
    "last_scan": "2025-01-16 10:30:00",
    "disk_space": "2.5GB",
    "memory_usage": "128MB"
}
```

#### **POST /security_scan_client.php?endpoint=scan&api_key={key}**
Thực hiện quét bảo mật

**Request Body:**
```json
{
    "priority_files": ["shell.php", "*.php.txt"],
    "scan_options": {
        "deep_scan": true,
        "include_archives": false,
        "max_file_size": "10MB"
    }
}
```

---

## 🔒 Bảo Mật

### 🛡️ Security Best Practices

#### **1. API Key Management**
```php
// Sử dụng API keys mạnh
const DEFAULT_API_KEY = 'hiep-security-' . hash('sha256', 'your-secret-salt' . date('Y-m'));

// Rotate keys định kỳ
const KEY_ROTATION_DAYS = 90;
```

#### **2. HTTPS Enforcement**
```php
// Force HTTPS cho tất cả communications
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectURL");
    exit();
}
```

#### **3. Input Validation**
```php
// Validate tất cả inputs
function validateInput($input, $type) {
    switch ($type) {
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'filename':
            return preg_match('/^[a-zA-Z0-9._\-\/]+$/', $input);
    }
    return false;
}
```

---

## 📞 Hỗ Trợ

### 👨‍💻 Thông Tin Liên Hệ

- **👤 Author**: Hiệp Nguyễn
- **📧 Email**: nguyenvanhiep0711@gmail.com
- **🌐 Website**: https://hiepcodeweb.com
- **📱 Phone**: +84 xxx xxx xxx
- **💬 Telegram**: @hiepcodeweb

### 📋 Thông Tin Phiên Bản

- **🔢 Version**: 2.0
- **📅 Release Date**: 2025-01-16
- **🔄 Last Updated**: 2025-08-03
- **📜 License**: MIT License
- **🐛 Bug Reports**: GitHub Issues

### 🆘 Hỗ Trợ Kỹ Thuật

#### **📞 Hỗ Trợ Trực Tiếp**
- **Thời gian**: 8:00 - 22:00 (GMT+7)
- **Phản hồi**: Trong vòng 2-4 giờ
- **Ngôn ngữ**: Tiếng Việt, English

#### **📚 Tài Liệu Bổ Sung**
- `INSTALLATION_GUIDE.md` - Hướng dẫn cài đặt chi tiết
- `CRON_SETUP_GUIDE.md` - Cấu hình cron jobs
- `SECURITY_SCANNER_GUIDE.md` - Hướng dẫn sử dụng nâng cao
- `API_DEPLOY_GUIDE.md` - Triển khai API

#### **🐛 Báo Lỗi**
Khi báo lỗi, vui lòng cung cấp:
1. **Mô tả lỗi**: Chi tiết vấn đề gặp phải
2. **Steps to reproduce**: Các bước tái hiện lỗi
3. **Environment**: PHP version, OS, web server
4. **Log files**: Error logs liên quan
5. **Screenshots**: Ảnh chụp màn hình nếu có

---

## 📈 Changelog & Roadmap

### 🔄 Version History

#### **v2.0 (2025-01-16) - Current**
✅ **New Features:**
- Priority Files Scanner với pattern matching
- Modern responsive dashboard UI
- Real-time client status monitoring
- Enhanced email templates với HTML styling
- API patterns caching system
- Bulk operations cho multiple clients

✅ **Improvements:**
- Tối ưu hóa performance scanning engine
- Giảm false positives với whitelist thông minh
- Cải thiện error handling và logging
- PHP 5.6+ compatibility
- Better memory management

✅ **Bug Fixes:**
- Sửa lỗi SMTP connection timeout
- Fix JSON parsing errors
- Resolve file permission issues
- Correct timezone handling

#### **v1.0 (2024-12-01) - Initial Release**
- Basic malware scanning
- Client-server architecture
- JSON-based client management
- Command-line interface

### 🚀 Roadmap v2.1 (Q2 2025)

#### **🎯 Planned Features:**
- **🔐 Advanced Authentication**: Multi-factor authentication, role-based access
- **📊 Enhanced Analytics**: Detailed charts, trend analysis, threat intelligence
- **🤖 AI-Powered Detection**: Machine learning patterns, behavioral analysis
- **🌍 Multi-language Support**: English, Vietnamese, Chinese interfaces
- **📱 Mobile App**: iOS/Android companion app

---

## 📄 License & Legal

### 📜 MIT License

```
MIT License

Copyright (c) 2025 Hiệp Nguyễn

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

**🎯 Kết Luận**

Hiệp Antivirus là giải pháp bảo mật toàn diện, dễ sử dụng và có thể mở rộng cho việc quản lý bảo mật nhiều website. Với kiến trúc modular, tự động hóa hoàn toàn và hỗ trợ kỹ thuật chuyên nghiệp, hệ thống này sẽ giúp bạn bảo vệ website một cách hiệu quả và tiết kiệm thời gian.

**🚀 Bắt đầu ngay hôm nay và bảo vệ website của bạn!**