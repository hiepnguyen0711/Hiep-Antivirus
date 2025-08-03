# 🛡️ Hướng Dẫn Cài Đặt Security Scanner System

## 📋 Tổng Quan Hệ Thống

Security Scanner System là giải pháp quét bảo mật tập trung cho nhiều website, bao gồm:
- **Server**: Quản lý tập trung, dashboard, remediation
- **Client**: Đặt trên mỗi website cần quét
- **Scheduler**: Tự động quét và báo cáo email

## 🏗️ Kiến Trúc Hệ Thống

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Server        │    │   Website 1     │    │   Website 2     │
│   (Quản lý)     │◄──►│   + Client      │    │   + Client      │
│                 │    │                 │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │
         ▼
┌─────────────────┐
│   Email Reports │
│   (Tự động)     │
└─────────────────┘
```

---

## 🖥️ PHẦN 1: CÀI ĐẶT SERVER (Máy Quản Lý)

### 📁 Files Cần Copy

Copy các files sau vào server quản lý:

```
server-folder/
├── security_scan_server.php      # File chính của server
├── daily_security_scan.php       # Cron job cho scheduler
├── data/                          # Thư mục dữ liệu (tự tạo)
│   ├── clients.json              # Danh sách clients
│   ├── logs/                     # Logs hệ thống
│   └── backups/                  # Backup files
└── config/                       # Cấu hình (tùy chọn)
```

### ⚙️ Cấu Hình Server

#### 1. Tạo thư mục và phân quyền:
```bash
mkdir -p data/logs data/backups config
chmod 755 data data/logs data/backups config
chmod 644 security_scan_server.php daily_security_scan.php
```

#### 2. Cấu hình email (trong `security_scan_server.php`):
```php
class EmailConfig
{
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'your-email@gmail.com';     // Email gửi
    const SMTP_PASSWORD = 'your-app-password';        // App Password
    const SMTP_ENCRYPTION = 'tls';
    
    const REPORT_EMAIL = 'nguyenvanhiep0711@gmail.com'; // Email nhận
    const FROM_EMAIL = 'security-scanner@yourdomain.com';
    const FROM_NAME = 'Security Scanner System';
}
```

#### 3. Tạo file clients.json:
```json
{
    "clients": [
        {
            "id": "client_website1",
            "name": "Website 1",
            "url": "https://website1.com/security_scan_client.php",
            "api_key": "hiep-security-client-2025-change-this-key",
            "status": "active",
            "created_at": "2025-01-01 00:00:00"
        }
    ]
}
```

### 🌐 Truy Cập Dashboard

Mở trình duyệt và truy cập:
```
http://your-server.com/path/to/security_scan_server.php
```

---

## 💻 PHẦN 2: CÀI ĐẶT CLIENT (Website Cần Quét)

### 🎯 Lựa Chọn Client

**Option 1: Standalone Client (Khuyến nghị)**
- ✅ Chỉ 1 file duy nhất
- ✅ Không cần dependencies
- ✅ Dễ deploy và maintain

**Option 2: Regular Client**
- ⚠️ Cần nhiều files
- ⚠️ Cần config folder
- ✅ Có thể customize nhiều hơn

### 📦 Cài Đặt Standalone Client

#### 1. Copy file:
```bash
# Copy file standalone
cp security_scan_client_standalone.php /path/to/website/security_scan_client.php
```

#### 2. Cấu hình API Key:
```php
class SecurityClientConfig
{
    const API_KEY = 'hiep-security-client-2025-change-this-key'; // Thay đổi
    const CLIENT_NAME = 'website-name';                          // Tên website
    const CLIENT_VERSION = '2.0';
    
    // Cấu hình quét
    const MAX_SCAN_FILES = 999999999;
    const MAX_SCAN_TIME = 600;
    const MAX_MEMORY = '512M';
}
```

#### 3. Test client:
```bash
curl "http://your-website.com/security_scan_client.php?action=health&api_key=your-api-key"
```

### 📁 Cài Đặt Regular Client

#### 1. Copy files:
```
website-folder/
├── security_scan_client.php
└── config/
    └── scanner_config.php
```

#### 2. Cấu hình tương tự standalone client

---

## 🔗 PHẦN 3: KẾT NỐI SERVER VÀ CLIENT

### 1. Thêm Client Vào Server

Truy cập dashboard server và click "**+ Thêm Client**":

```
Client Name: Website ABC
Client URL: https://website-abc.com/security_scan_client.php
API Key: hiep-security-client-2025-change-this-key
```

### 2. Test Kết Nối

Click "**Health**" để kiểm tra kết nối:
- ✅ **Online**: Kết nối thành công
- ❌ **Offline**: Kiểm tra URL và API key

### 3. Chạy Scan Đầu Tiên

Click "**Quét**" để chạy scan đầu tiên và kiểm tra kết quả.

---

## 🛠️ PHẦN 4: SỬ DỤNG REMEDIATION

### 🎯 Tính Năng Khắc Phục Tự Động

Remediation cho phép tự động sửa các lỗ hỏng bảo mật:

#### 1. Truy cập Remediation:
- Click "**Khắc phục**" cho client cần sửa
- Hệ thống hiển thị danh sách lỗ hỏng có thể khắc phục

#### 2. Các Loại Khắc Phục:

| Loại | Mô tả | Mức độ |
|------|-------|--------|
| **Enhanced Shell Detection** | Nâng cấp phát hiện malware với 55+ patterns | Critical |
| **HiepSecurity Class** | Thêm class bảo mật tổng hợp | Critical |
| **CSRF Protection** | Bảo mật .htaccess chống CSRF | Critical |
| **PHP Compatibility** | Sửa lỗi tương thích PHP 7.x+ | Warning |
| **Admin Security** | Bảo mật admin panel | Warning |
| **File Upload Security** | Bảo mật upload files | Critical |

#### 3. Quy Trình Khắc Phục:

```
1. Chọn fixes cần áp dụng
2. Click "Thực hiện khắc phục"
3. Hệ thống tự động:
   ├── Tạo backup file gốc
   ├── Áp dụng fixes
   ├── Validate kết quả
   └── Rollback nếu có lỗi
4. Hiển thị kết quả chi tiết
```

#### 4. Backup & Recovery:

- **Backup tự động**: `filename.hiep_bk_YYYYMMDD_HHMMSS.ext`
- **Vị trí**: Cùng thư mục với file gốc
- **Rollback**: Tự động nếu có lỗi
- **Manual restore**: Copy backup file về tên gốc

---

## 📧 PHẦN 5: CÀI ĐẶT EMAIL SCHEDULER

### 🕙 Tự Động Quét Hàng Ngày

#### 1. Cấu hình Gmail App Password:
```
1. Truy cập: https://myaccount.google.com/
2. Security → 2-Step Verification
3. App passwords → Mail
4. Copy password vào EmailConfig::SMTP_PASSWORD
```

#### 2. Cài đặt Cron Job:
```bash
# Mở crontab
crontab -e

# Thêm dòng (quét lúc 22:00 hàng ngày):
0 22 * * * /usr/bin/php /path/to/daily_security_scan.php >> /path/to/logs/cron.log 2>&1
```

#### 3. Test Email:
```bash
curl "http://your-server.com/security_scan_server.php?api=test_email&admin_key=hiep-admin-test-2025"
```

### 📧 Format Email Báo Cáo

Email được gửi khi:
- ✅ Phát hiện threats mức độ ≥ 8 (critical)
- ✅ File được cập nhật trong ngày
- ✅ Có ít nhất 1 threat nghiêm trọng

Nội dung email:
- 📊 Thống kê tổng quan
- 📁 Danh sách files bị hack
- ⚠️ Mức độ nguy hiểm
- 🕒 Thời gian phát hiện
- 🔗 Đường dẫn chi tiết

---

## 🔧 PHẦN 6: TROUBLESHOOTING

### ❌ Lỗi Thường Gặp

#### 1. Client không kết nối được:
```bash
# Kiểm tra:
- URL client có đúng không?
- API key có khớp không?
- File client có tồn tại không?
- Permissions có đúng không?

# Test:
curl "http://website.com/security_scan_client.php?action=health&api_key=your-key"
```

#### 2. Email không gửi được:
```bash
# Kiểm tra:
- Gmail App Password có đúng không?
- SMTP settings có chính xác không?
- Email có bị block không?

# Log:
tail -f data/logs/scheduler.log
```

#### 3. Remediation thất bại:
```bash
# Kiểm tra:
- File có quyền write không?
- Backup có được tạo không?
- Syntax có lỗi không?

# Rollback thủ công:
cp file.hiep_bk_YYYYMMDD_HHMMSS.ext file.ext
```

#### 4. Cron job không chạy:
```bash
# Kiểm tra cron service:
sudo service cron status

# Kiểm tra crontab:
crontab -l

# Kiểm tra log:
tail -f /var/log/cron.log
```

### 🔍 Debug Mode

Bật debug trong `security_scan_server.php`:
```php
// Thêm vào đầu file:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### 📊 Monitoring

Kiểm tra logs định kỳ:
```bash
# Scheduler logs:
tail -f data/logs/scheduler.log

# Daily scan results:
ls -la data/logs/daily_scan_*.json

# Cron logs:
tail -f /var/log/cron.log
```

---

## 🛡️ PHẦN 7: SECURITY BEST PRACTICES

### 🔐 Bảo Mật API Keys

1. **Thay đổi default keys**:
```php
// Thay đổi trong mỗi client:
const API_KEY = 'unique-key-for-each-client-2025';
```

2. **Sử dụng HTTPS**:
```
https://your-website.com/security_scan_client.php
```

3. **Restrict IP access** (tùy chọn):
```php
const ALLOWED_IPS = array('server-ip-address');
```

### 📁 File Permissions

```bash
# Server files:
chmod 644 security_scan_server.php
chmod 644 daily_security_scan.php
chmod 755 data/ data/logs/ data/backups/

# Client files:
chmod 644 security_scan_client.php
chmod 755 config/ (nếu có)
```

### 🔄 Backup Strategy

1. **Tự động backup**: Hệ thống tự tạo backup trước mỗi remediation
2. **Manual backup**: Backup định kỳ toàn bộ website
3. **Test restore**: Test khôi phục backup định kỳ

---

## 🖥️ HƯỚNG DẪN CÀI ĐẶT CHO CÁC HOSTING PANEL

### 🔧 DirectAdmin Hosting Panel

DirectAdmin là hosting panel phổ biến với giao diện đơn giản và dễ sử dụng.

#### **Bước 1: Truy Cập File Manager**
1. Đăng nhập vào DirectAdmin panel
2. Tìm và click vào **"File Manager"** trong phần **"Advanced Features"**
3. Chọn domain cần cài đặt từ dropdown list
4. Click **"File Manager"** để mở

#### **Bước 2: Upload Files Server (Nếu Là Server Quản Lý)**

**Bước 2.1: Tạo Thư Mục**
1. Trong File Manager, navigate đến thư mục `public_html`
2. Click **"Create Folder"**
3. Tạo thư mục `security-center` (hoặc tên bạn muốn)
4. Vào thư mục vừa tạo

**Bước 2.2: Upload Files**
1. Click **"Upload Files"**
2. Chọn và upload các files:
   - `security_scan_server.php`
   - `daily_security_scan.php`
3. Click **"Upload"**

**Bước 2.3: Tạo Thư Mục Dữ Liệu**
1. Trong thư mục security-center, click **"Create Folder"**
2. Tạo thư mục `data`
3. Vào thư mục `data`, tạo thêm:
   - Thư mục `logs`
   - Thư mục `backups`

**Bước 2.4: Set Permissions**
1. Click chuột phải vào thư mục `data` → **"Change Permissions"**
2. Set permission: `755` (rwxr-xr-x)
3. Check **"Apply to subdirectories"**
4. Click **"Change"**

#### **Bước 3: Upload Files Client (Trên Website Cần Bảo Vệ)**

**Bước 3.1: Upload Client File**
1. Truy cập File Manager của website cần bảo vệ
2. Navigate đến thư mục `public_html` (root directory)
3. Upload file `security_scan_client.php`

**Bước 3.2: Tạo Thư Mục Client**
1. Tạo thư mục `logs` với permission `755`
2. Tạo thư mục `quarantine` với permission `755`
3. Tạo thư mục `config` với permission `755` (tùy chọn)

#### **Bước 4: Cấu Hình Email SMTP**

**Bước 4.1: Cấu Hình Gmail SMTP**
1. Mở file `security_scan_server.php` bằng **"Edit"** trong File Manager
2. Tìm phần cấu hình email:
```php
const ADMIN_EMAIL = 'your-admin@gmail.com';
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 465;
const SMTP_USERNAME = 'your-email@gmail.com';
const SMTP_PASSWORD = 'your-app-password';
```
3. Thay đổi thông tin email của bạn
4. Save file

**Bước 4.2: Tạo Gmail App Password**
1. Vào [Google Account Settings](https://myaccount.google.com/)
2. Security → 2-Step Verification → App passwords
3. Chọn "Mail" và "Other (Custom name)"
4. Nhập "Security Scanner" làm tên
5. Copy password 16 ký tự được tạo
6. Paste vào `SMTP_PASSWORD` trong config

#### **Bước 5: Cấu Hình Cron Jobs**

**Bước 5.1: Truy Cập Cron Jobs**
1. Trong DirectAdmin panel, tìm **"Cron Jobs"**
2. Click vào **"Cron Jobs"** trong phần **"Advanced Features"**

**Bước 5.2: Tạo Daily Scan Job**
1. Click **"Create Cron Job"**
2. Điền thông tin:
   - **Minute**: `0`
   - **Hour**: `2` (2:00 AM)
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**: `/usr/bin/php /home/username/domains/domain.com/public_html/security-center/daily_security_scan.php daily_scan`
3. Click **"Create"**

**Bước 5.3: Tạo Cleanup Job**
1. Tạo cron job thứ 2:
   - **Minute**: `0`
   - **Hour**: `3`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `0` (Chủ nhật)
   - **Command**: `/usr/bin/php /home/username/domains/domain.com/public_html/security-center/daily_security_scan.php cleanup`

#### **Bước 6: Test Hệ Thống**

**Bước 6.1: Test Server**
1. Truy cập: `https://your-domain.com/security-center/security_scan_server.php`
2. Kiểm tra dashboard hiển thị đúng

**Bước 6.2: Test Client**
1. Truy cập: `https://client-website.com/security_scan_client.php?endpoint=health&api_key=your-api-key`
2. Kết quả mong đợi: `{"status":"ok","client_name":"website-name"}`

**Bước 6.3: Test Email**
1. Trong dashboard server, click **"Test Email"**
2. Kiểm tra email có nhận được không

#### **🔧 Troubleshooting DirectAdmin**

**Lỗi Permission Denied:**
```bash
# Kiểm tra ownership
ls -la /home/username/domains/domain.com/public_html/

# Nếu cần, thay đổi owner
chown -R username:username /path/to/files
```

**Lỗi Cron Job Không Chạy:**
1. Kiểm tra đường dẫn PHP: `/usr/bin/php` hoặc `/usr/local/bin/php`
2. Test command trực tiếp trong SSH:
```bash
/usr/bin/php /full/path/to/daily_security_scan.php daily_scan
```

**Lỗi Email Không Gửi:**
1. Kiểm tra PHP mail function: `php -m | grep mail`
2. Kiểm tra firewall có block port 465 không
3. Test SMTP connection:
```bash
telnet smtp.gmail.com 465
```

---

### 🎛️ cPanel Hosting Panel

cPanel là hosting panel phổ biến nhất với giao diện thân thiện.

#### **Bước 1: Truy Cập File Manager**
1. Đăng nhập vào cPanel
2. Tìm **"File Manager"** trong phần **"Files"**
3. Click **"File Manager"**
4. Chọn **"Web Root (public_html/www)"**
5. Click **"Go"**

#### **Bước 2: Upload Files Server**

**Bước 2.1: Tạo Thư Mục Server**
1. Trong File Manager, click **"+ Folder"**
2. Tạo thư mục `security-center`
3. Double-click vào thư mục để mở

**Bước 2.2: Upload Files**
1. Click **"Upload"** trong toolbar
2. Drag & drop hoặc click **"Select File"**:
   - `security_scan_server.php`
   - `daily_security_scan.php`
3. Đợi upload hoàn tất, click **"Go Back to..."**

**Bước 2.3: Tạo Cấu Trúc Thư Mục**
1. Tạo thư mục `data`
2. Vào thư mục `data`, tạo:
   - Thư mục `logs`
   - Thư mục `backups`

**Bước 2.4: Set Permissions**
1. Click chuột phải vào thư mục `data`
2. Chọn **"Change Permissions"**
3. Set: `755` (Owner: Read+Write+Execute, Group: Read+Execute, World: Read+Execute)
4. Check **"Recurse into subdirectories"**
5. Click **"Change Permissions"**

#### **Bước 3: Upload Files Client**

**Bước 3.1: Upload Client File**
1. Truy cập cPanel của website client
2. Mở File Manager → public_html
3. Upload `security_scan_client.php` vào root

**Bước 3.2: Tạo Thư Mục Client**
1. Tạo thư mục `logs` (permission 755)
2. Tạo thư mục `quarantine` (permission 755)
3. Tạo thư mục `config` (permission 755)

#### **Bước 4: Cấu Hình Email**

**Bước 4.1: Edit Server Config**
1. Click chuột phải vào `security_scan_server.php`
2. Chọn **"Edit"** hoặc **"Code Editor"**
3. Tìm và sửa phần email config:
```php
const ADMIN_EMAIL = 'admin@your-domain.com';
const SMTP_HOST = 'mail.your-domain.com';  // Hoặc smtp.gmail.com
const SMTP_PORT = 587;                      // Hoặc 465 cho SSL
const SMTP_USERNAME = 'admin@your-domain.com';
const SMTP_PASSWORD = 'your-email-password';
```
4. Click **"Save Changes"**

**Bước 4.2: Cấu Hình Email Account (Nếu Dùng Email Hosting)**
1. Trong cPanel, tìm **"Email Accounts"**
2. Click **"Create"** để tạo email mới
3. Tạo email: `security@your-domain.com`
4. Set password mạnh
5. Sử dụng thông tin này trong config

#### **Bước 5: Cấu Hình Cron Jobs**

**Bước 5.1: Truy Cập Cron Jobs**
1. Trong cPanel, tìm **"Cron Jobs"** trong phần **"Advanced"**
2. Click **"Cron Jobs"**

**Bước 5.2: Tạo Daily Scan**
1. Trong **"Add New Cron Job"**:
   - **Common Settings**: Chọn **"Once Per Day (0 0 * * *)"**
   - Sửa Hour thành `2` → `0 2 * * *`
   - **Command**: `/usr/local/bin/php /home/cpanel-username/public_html/security-center/daily_security_scan.php daily_scan`
2. Click **"Add New Cron Job"**

**Bước 5.3: Tạo Weekly Cleanup**
1. Tạo cron job thứ 2:
   - **Minute**: `0`
   - **Hour**: `3`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `0`
   - **Command**: `/usr/local/bin/php /home/cpanel-username/public_html/security-center/daily_security_scan.php cleanup`

#### **Bước 6: Test & Verify**

**Bước 6.1: Test PHP Path**
1. Tạo file test.php:
```php
<?php
echo "PHP Path: " . PHP_BINARY . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
phpinfo();
?>
```
2. Chạy qua browser để xem PHP path chính xác

**Bước 6.2: Test Cron Job**
1. Trong Cron Jobs, tìm job vừa tạo
2. Click **"Run Now"** (nếu có)
3. Hoặc đợi đến giờ chạy và kiểm tra logs

**Bước 6.3: Test Email Function**
1. Tạo file test_email.php:
```php
<?php
$to = 'your-email@gmail.com';
$subject = 'Test Email from cPanel';
$message = 'This is a test email.';
$headers = 'From: security@your-domain.com';

if (mail($to, $subject, $message, $headers)) {
    echo 'Email sent successfully';
} else {
    echo 'Email failed to send';
}
?>
```

#### **🔧 Troubleshooting cPanel**

**Lỗi PHP Path:**
- Thử các path khác: `/usr/bin/php`, `/opt/cpanel/ea-php74/root/usr/bin/php`
- Kiểm tra trong cPanel → **"Select PHP Version"**

**Lỗi Permission:**
```bash
# Kiểm tra qua File Manager hoặc SSH
ls -la /home/username/public_html/

# Fix permissions
find /home/username/public_html/security-center -type d -exec chmod 755 {} \;
find /home/username/public_html/security-center -type f -exec chmod 644 {} \;
```

**Lỗi Email:**
1. Kiểm tra **"Email Deliverability"** trong cPanel
2. Kiểm tra SPF, DKIM records
3. Test với external SMTP (Gmail) nếu hosting email có vấn đề

**Lỗi Cron Job:**
1. Kiểm tra **"Cron Jobs"** → **"Current Cron Jobs"**
2. Xem logs trong `/home/username/logs/` hoặc `/var/log/cron`
3. Test command trực tiếp qua SSH

---

## 📞 Hỗ Trợ

### 📧 Liên Hệ
- **Email**: nguyenvanhiep0711@gmail.com
- **Documentation**: Xem file này và CRON_SETUP.md

### 🔗 Resources
- **GitHub**: (Link repository nếu có)
- **Documentation**: installation_guide.html (phiên bản HTML)

---

**🎉 Chúc bạn triển khai thành công Security Scanner System!**
