# 🚨 Hướng Dẫn Cấu Hình Tự Động Quét Bảo Mật

## 📋 Tổng Quan
Hệ thống tự động quét bảo mật của Hiệp Nguyễn có thể:
- ✅ Quét tự động theo lịch (mỗi giờ/ngày)
- ✅ Gửi email cảnh báo khi phát hiện threats nghiêm trọng
- ✅ Tối ưu hóa cho hosting có nhiều files
- ✅ Backup tự động trước khi xử lý

---

## 🔧 Bước 1: Cấu Hình Email

### 1.1 Chỉnh sửa file `security_scan.php`
Mở file `security_scan.php` và tìm class `SecurityScanConfig` (dòng ~15):

```php
class SecurityScanConfig {
    // Cấu hình email
    const EMAIL_TO = 'your-email@gmail.com'; // ⚠️ THAY ĐỔI EMAIL CỦA BẠN
    const EMAIL_FROM = 'security-scanner@yourdomain.com';
    const EMAIL_FROM_NAME = 'Hiệp Security Scanner';
    
    // Cấu hình SMTP (nếu cần)
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'your-email@gmail.com';
    const SMTP_PASSWORD = 'your-app-password'; // ⚠️ App password cho Gmail
    const SMTP_SECURE = 'tls';
}
```

### 1.2 Cấu hình Gmail (Khuyến nghị)
1. Truy cập [Google Account Settings](https://myaccount.google.com/)
2. Bật **2-Step Verification**
3. Tạo **App Password** cho ứng dụng
4. Sử dụng App Password thay vì mật khẩu thường

### 1.3 Cấu hình SMTP khác
```php
// Hostinger
const SMTP_HOST = 'smtp.hostinger.com';
const SMTP_PORT = 587;

// cPanel
const SMTP_HOST = 'mail.yourdomain.com';
const SMTP_PORT = 587;
```

---

## ⚙️ Bước 2: Cấu Hình Tự Động Quét

### 2.1 Tùy chỉnh cài đặt quét
```php
// Trong class SecurityScanConfig
const AUTO_SCAN_ENABLED = true;              // Bật/tắt auto scan
const AUTO_SCAN_INTERVAL = 3600;             // 1 giờ (3600 giây)
const AUTO_SCAN_CRITICAL_ONLY = true;        // Chỉ gửi email khi có critical threats
const AUTO_SCAN_MAX_FILES = 50000;           // Giới hạn số file quét mỗi lần
const SCAN_HISTORY_DAYS = 30;                // Lưu lịch sử quét 30 ngày
const LOG_RETENTION_DAYS = 7;                // Lưu log 7 ngày
```

### 2.2 Các tùy chọn interval phổ biến
```php
const AUTO_SCAN_INTERVAL = 1800;    // 30 phút
const AUTO_SCAN_INTERVAL = 3600;    // 1 giờ
const AUTO_SCAN_INTERVAL = 21600;   // 6 giờ
const AUTO_SCAN_INTERVAL = 86400;   // 24 giờ
```

---

## 🤖 Bước 3: Thiết Lập Cron Job

### 3.1 Qua cPanel (Khuyến nghị)
1. Truy cập **cPanel** → **Cron Jobs**
2. Thêm cron job mới:
   - **Minute**: 0
   - **Hour**: * (mỗi giờ) hoặc */6 (mỗi 6 giờ)
   - **Day**: *
   - **Month**: *
   - **Weekday**: *
   - **Command**: `/usr/bin/php /home/username/public_html/auto_scan_cron.php`

### 3.2 Qua SSH/Terminal
```bash
# Mở crontab
crontab -e

# Thêm dòng sau:
# Quét mỗi giờ
0 * * * * /usr/bin/php /path/to/auto_scan_cron.php

# Quét mỗi 6 giờ
0 */6 * * * /usr/bin/php /path/to/auto_scan_cron.php

# Quét hàng ngày lúc 2:00 AM
0 2 * * * /usr/bin/php /path/to/auto_scan_cron.php
```

### 3.3 Qua URL (Nếu không có SSH)
```bash
# Quét mỗi giờ qua curl
0 * * * * curl -s "http://yourdomain.com/auto_scan_cron.php"

# Với timeout
0 * * * * timeout 300 curl -s "http://yourdomain.com/auto_scan_cron.php"
```

---

## 🧪 Bước 4: Kiểm Tra Hoạt Động

### 4.1 Test email
1. Truy cập `http://yourdomain.com/security_scan.php`
2. Nhấn **"Quét Tự Động"**
3. Kiểm tra email nhận được

### 4.2 Test cron job
```bash
# Chạy thử cron job
php auto_scan_cron.php

# Kiểm tra log
tail -f logs/auto_scan_cron_2025-01-15.log
```

### 4.3 Kiểm tra trạng thái
- Mở dashboard: `http://yourdomain.com/security_scan.php`
- Phần **"Tự động quét"** sẽ hiển thị trạng thái
- Xem **"Lần quét cuối"** và **"Lần quét tiếp"**

---

## 📊 Bước 5: Giám Sát & Bảo Trì

### 5.1 Thư mục logs
```
logs/
├── auto_scan_cron_2025-01-15.log    # Log cron job
├── security_events_2025-01-15.log   # Log security events
├── scan_history.json                 # Lịch sử quét
├── last_auto_scan.txt               # Timestamp quét cuối
└── last_cron_result.json            # Kết quả cron cuối
```

### 5.2 Cleanup tự động
Hệ thống tự động xóa:
- Logs cũ hơn 7 ngày
- Scan history cũ hơn 30 ngày
- Backup files cũ hơn 30 ngày

### 5.3 Monitoring
```bash
# Kiểm tra log errors
grep "ERROR" logs/auto_scan_cron_*.log

# Kiểm tra email đã gửi
grep "Email alert sent" logs/security_events_*.log

# Kiểm tra số threats
grep "critical threats" logs/auto_scan_cron_*.log
```

---

## 🚨 Bước 6: Xử Lý Khi Có Cảnh Báo

### 6.1 Khi nhận email cảnh báo
1. **Đừng hoảng sợ** - Kiểm tra chi tiết
2. **Truy cập dashboard** ngay lập tức
3. **Xem danh sách files** nguy hiểm
4. **Backup** trước khi xử lý
5. **Xóa hoặc cách ly** files độc hại

### 6.2 Auto-fix nhanh
```php
// Trong dashboard, nhấn "Khắc Phục" → "Khắc Phục Toàn Bộ"
// Hệ thống sẽ:
// - Xóa files critical
// - Cập nhật .htaccess
// - Tạo backup
// - Ghi log
```

### 6.3 Cách ly files đáng ngờ
```php
// Thay vì xóa, có thể cách ly:
// - Di chuyển vào thư mục /quarantine/
// - Đổi tên file thành .quarantine
// - Backup trước khi xử lý
```

---

## 🔧 Bước 7: Tùy Chỉnh Nâng Cao

### 7.1 Whitelist directories
```php
// Trong hàm performLimitedScan(), thêm:
$excludeDirs = [
    './vendor',
    './node_modules', 
    './backups',
    './your_safe_directory'
];
```

### 7.2 Custom threat patterns
```php
// Thêm patterns riêng:
$customPatterns = [
    'your_malware_signature' => 'Your custom malware description',
    'suspicious_function()' => 'Suspicious function call'
];
```

### 7.3 Multiple email recipients
```php
// Gửi đến nhiều email:
const EMAIL_TO = 'admin@domain.com,security@domain.com';

// Hoặc trong sendEmailSMTP():
$mail->addAddress('admin@domain.com');
$mail->addAddress('security@domain.com');
$mail->addCC('backup@domain.com');
```

---

## 📋 Checklist Hoàn Thành

- [ ] ✅ Cấu hình email trong `SecurityScanConfig`
- [ ] ✅ Test email thành công
- [ ] ✅ Upload file `auto_scan_cron.php`
- [ ] ✅ Thiết lập cron job
- [ ] ✅ Test cron job chạy thành công
- [ ] ✅ Kiểm tra dashboard hiển thị trạng thái
- [ ] ✅ Xem logs có ghi đúng
- [ ] ✅ Test nhận email cảnh báo
- [ ] ✅ Cấu hình whitelist nếu cần
- [ ] ✅ Backup định kỳ

---

## 🆘 Troubleshooting

### Email không gửi được
```bash
# Kiểm tra log
grep "Email error" logs/security_events_*.log

# Kiểm tra SMTP config
# Thử với Gmail App Password
# Kiểm tra firewall port 587
```

### Cron job không chạy
```bash
# Kiểm tra cron service
service cron status

# Kiểm tra cron logs
tail -f /var/log/cron

# Test manual run
php auto_scan_cron.php
```

### Quá nhiều false positives
```php
// Tăng threshold
const AUTO_SCAN_CRITICAL_ONLY = true;

// Thêm whitelist
$whitelistFiles = [
    './legitimate_file.php',
    './admin/safe_uploader.php'
];
```

---

## 🔗 Liên Hệ Hỗ Trợ

- **Facebook**: https://www.facebook.com/G.N.S.L.7/
- **Email**: hiepnguyen@example.com
- **Telegram**: @hiepnguyen_security

---

*Phát triển bởi **Hiệp Nguyễn** - Enterprise Security Expert*
*Version: 3.0 - 2025* 