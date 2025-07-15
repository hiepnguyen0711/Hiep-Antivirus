# 🔒 Security Scanner Client-Server System
## Hướng Dẫn Setup Chi Tiết

**Phát triển bởi:** [Hiệp Nguyễn](https://www.facebook.com/G.N.S.L.7/)  
**Version:** 1.0  
**Ngày:** 15/01/2025  

---

## 📋 Tổng Quan Hệ Thống

Hệ thống Client-Server cho phép bạn:
- ✅ Quản lý bảo mật tập trung cho **nhiều website**
- ✅ Tự động quét **1 ngày/lần** qua cron job
- ✅ Gửi **email báo cáo** tự động
- ✅ Dashboard đẹp để **điều khiển từ xa**
- ✅ API bảo mật với **API key**
- ✅ Real-time monitoring

---

## 🏗️ Kiến Trúc Hệ Thống

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY SERVER                          │
│                (Website trung tâm)                         │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  security_scan_server.php (Dashboard)              │    │
│  │  server_cron.php (Cron Job)                       │    │
│  │  data/clients.json (Danh sách clients)            │    │
│  │  logs/ (Logs & reports)                           │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                               │
│                       API CALLS                           │
│                            │                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   CLIENT 1  │    │   CLIENT 2  │    │   CLIENT 3  │     │
│  │ Website A   │    │ Website B   │    │ Website C   │     │
│  │ (client.php)│    │ (client.php)│    │ (client.php)│     │
│  └─────────────┘    └─────────────┘    └─────────────┘     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Files Cần Thiết

### 🔹 **Files Server** (Đặt trên website trung tâm)
- `security_scan_server.php` - Dashboard điều khiển
- `server_cron.php` - Cron job tự động quét
- `smtp/` - Thư mục PHPMailer (nếu có)

### 🔹 **Files Client** (Đặt trên từng website cần quét)
- `security_scan_client.php` - API endpoints

---

## 🚀 Bước 1: Setup Server (Website Trung Tâm)

### 1.1 Upload Files
```bash
# Upload lên website trung tâm (VD: admin.yourdomain.com)
security_scan_server.php
server_cron.php
smtp/ (folder PHPMailer nếu có)
```

### 1.2 Cấu Hình Email
Chỉnh sửa trong `security_scan_server.php`:

```php
class SecurityServerConfig {
    // Email nhận báo cáo
    const ADMIN_EMAIL = 'nguyenvanhiep0711@gmail.com';
    
    // SMTP Settings
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'nguyenvanhiep0711@gmail.com';
    const SMTP_PASSWORD = 'flnd neoz lhqw yzmd'; // App password
    const SMTP_SECURE = 'tls';
    
    // Server Settings
    const SERVER_NAME = 'Hiệp Security Center';
    const DEFAULT_API_KEY = 'hiep-security-2025-change-this-key';
}
```

### 1.3 Phân Quyền
```bash
chmod 755 security_scan_server.php
chmod 755 server_cron.php
mkdir data logs
chmod 755 data logs
```

### 1.4 Truy Cập Dashboard
```
https://admin.yourdomain.com/security_scan_server.php
```

---

## 🔧 Bước 2: Setup Client (Từng Website)

### 2.1 Upload Client File
```bash
# Upload lên từng website cần quét
security_scan_client.php
```

### 2.2 Cấu Hình Client
Chỉnh sửa trong `security_scan_client.php`:

```php
class SecurityClientConfig {
    // API Key (phải giống với server)
    const API_KEY = 'hiep-security-2025-change-this-key';
    
    // Tên website này
    const CLIENT_NAME = 'Website ABC';
    
    // Giới hạn quét
    const MAX_SCAN_FILES = 10000;
    const MAX_SCAN_TIME = 300; // 5 phút
    
    // Bảo mật (IP Server được phép truy cập)
    const ALLOWED_IPS = ['123.45.67.89']; // IP của server
}
```

### 2.3 Phân Quyền
```bash
chmod 755 security_scan_client.php
mkdir logs
chmod 755 logs
```

### 2.4 Test Client
```bash
# Test endpoints
curl -H "X-API-Key: your-api-key" "https://website.com/security_scan_client.php?endpoint=health"
curl -H "X-API-Key: your-api-key" "https://website.com/security_scan_client.php?endpoint=status"
```

---

## ⚙️ Bước 3: Thêm Clients Vào Server

### 3.1 Truy Cập Dashboard
```
https://admin.yourdomain.com/security_scan_server.php
```

### 3.2 Thêm Client
1. Click **"Thêm Client"**
2. Điền thông tin:
   - **Tên:** Website ABC
   - **URL:** https://website.com
   - **API Key:** hiep-security-2025-change-this-key
3. Click **"Lưu Client"**

### 3.3 Test Kết Nối
1. Click nút **❤️** (Health Check)
2. Click nút **🔍** (Scan Test)

---

## 📅 Bước 4: Setup Cron Job (Tự Động Quét)

### 4.1 Cron Job Hàng Ngày
```bash
# Mở crontab
crontab -e

# Thêm dòng sau (quét lúc 2:00 AM mỗi ngày)
0 2 * * * /usr/bin/php /path/to/server_cron.php >> /path/to/logs/cron.log 2>&1

# Hoặc qua URL (nếu không có CLI)
0 2 * * * curl -s "https://admin.yourdomain.com/server_cron.php" >> /path/to/logs/cron.log 2>&1
```

### 4.2 Test Cron Job
```bash
# Test thủ công
php server_cron.php

# Hoặc qua trình duyệt
https://admin.yourdomain.com/server_cron.php
```

---

## 🔍 Các Tính Năng Chính

### 📊 Dashboard Server
- **Dashboard tổng quan** - Thống kê real-time
- **Quản lý clients** - Thêm/xóa/sửa clients
- **Quét tức thì** - Quét 1 client hoặc tất cả
- **Báo cáo email** - Gửi báo cáo ngay lập tức
- **Logs chi tiết** - Theo dõi hoạt động

### 🔌 API Endpoints Client
- `?endpoint=health` - Kiểm tra sức khỏe
- `?endpoint=status` - Thông tin chi tiết
- `?endpoint=scan` - Thực hiện quét
- `?endpoint=info` - Thông tin API

### 🤖 Cron Job Tự Động
- **Quét hàng ngày** - Tự động quét tất cả clients
- **Email báo cáo** - Gửi báo cáo tổng hợp
- **Cleanup logs** - Xóa logs cũ (30 ngày)
- **Error alerts** - Cảnh báo lỗi qua email

---

## 🔐 Bảo Mật

### API Key Security
```php
// Server và tất cả clients phải dùng cùng API key
const API_KEY = 'hiep-security-2025-change-this-key';
```

### IP Whitelist
```php
// Chỉ cho phép IP server truy cập client
const ALLOWED_IPS = ['123.45.67.89'];
```

### Rate Limiting
```php
// Giới hạn 10 requests/phút
const RATE_LIMIT = 10;
```

---

## 📧 Cấu Hình Email

### Gmail Setup
1. Bật **2-Factor Authentication**
2. Tạo **App Password**:
   - Google Account → Security → App passwords
   - Tạo password mới cho "Mail"
3. Sử dụng App Password trong config

### Email Templates
- **Báo cáo hàng ngày** - Tổng hợp tất cả clients
- **Cảnh báo critical** - Khi phát hiện threats nghiêm trọng
- **Lỗi cron job** - Khi cron job gặp lỗi

---

## 🐛 Troubleshooting

### ❌ Client Không Kết Nối
1. Kiểm tra **API Key** giống nhau
2. Kiểm tra **IP whitelist**
3. Kiểm tra **file permissions**
4. Kiểm tra **logs** client

### ❌ Cron Job Không Chạy
1. Kiểm tra **crontab** syntax
2. Kiểm tra **PHP path**
3. Kiểm tra **file permissions**
4. Kiểm tra **logs** cron

### ❌ Email Không Gửi
1. Kiểm tra **SMTP settings**
2. Kiểm tra **PHPMailer** files
3. Kiểm tra **firewall** port 587
4. Kiểm tra **App password**

---

## 📂 Cấu Trúc Thư Mục

```
📦 SERVER (Website trung tâm)
├── 📄 security_scan_server.php
├── 📄 server_cron.php
├── 📁 data/
│   ├── 📄 clients.json
│   └── 📄 daily_scan_results_2025-01-15.json
├── 📁 logs/
│   ├── 📄 cron_scan_2025-01-15.log
│   └── 📄 cron_scan.lock
└── 📁 smtp/
    ├── 📄 class.phpmailer.php
    └── 📄 class.smtp.php

📦 CLIENT (Từng website)
├── 📄 security_scan_client.php
└── 📁 logs/
    ├── 📄 client_scan_2025-01-15.log
    └── 📄 last_scan_client.json
```

---

## 🚀 Ví Dụ Thực Tế

### Scenario: Quản lý 5 websites
1. **Server**: `https://admin.mydomain.com/security_scan_server.php`
2. **Clients**:
   - Website A: `https://site1.com/security_scan_client.php`
   - Website B: `https://site2.com/security_scan_client.php`
   - Website C: `https://site3.com/security_scan_client.php`
   - Website D: `https://site4.com/security_scan_client.php`
   - Website E: `https://site5.com/security_scan_client.php`

### Cron Job
```bash
# Quét tất cả 5 websites lúc 2:00 AM mỗi ngày
0 2 * * * php /path/to/server_cron.php
```

### Email Báo Cáo
```
Subject: 🔒 Báo Cáo Bảo Mật Hàng Ngày - 15/01/2025
- 4 websites an toàn
- 1 website có cảnh báo
- 0 website nghiêm trọng
```

---

## 🔗 API Reference

### Health Check
```bash
curl -H "X-API-Key: your-key" "https://site.com/security_scan_client.php?endpoint=health"
```

### Status Check
```bash
curl -H "X-API-Key: your-key" "https://site.com/security_scan_client.php?endpoint=status"
```

### Perform Scan
```bash
curl -X POST -H "X-API-Key: your-key" "https://site.com/security_scan_client.php?endpoint=scan"
```

---

## 📞 Support

- **Developer**: [Hiệp Nguyễn](https://www.facebook.com/G.N.S.L.7/)
- **Email**: nguyenvanhiep0711@gmail.com
- **Facebook**: https://www.facebook.com/G.N.S.L.7/

---

## 🏆 Kết Luận

Hệ thống Client-Server này cho phép bạn:
- ✅ **Quản lý bảo mật tập trung** cho nhiều website
- ✅ **Tự động hóa** quét hàng ngày
- ✅ **Theo dõi real-time** tình trạng bảo mật
- ✅ **Nhận cảnh báo** kịp thời qua email
- ✅ **Tiết kiệm thời gian** quản lý

**Chúc bạn thành công! 🚀** 