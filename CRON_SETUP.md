# Hướng Dẫn Cài Đặt Cron Job Cho Security Scanner

## 📋 Tổng Quan
Hệ thống Security Scanner có thể tự động quét tất cả websites mỗi ngày lúc 22:00 (giờ Việt Nam) và gửi email báo cáo khi phát hiện threats nghiêm trọng.

## 🔧 Cách 1: Sử dụng File PHP Cron Job

### Bước 1: Cấu hình Email
Mở file `security_scan_server.php` và cập nhật thông tin email:

```php
class EmailConfig
{
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'your-email@gmail.com'; // Email gửi
    const SMTP_PASSWORD = 'your-app-password';    // App Password của Gmail
    const SMTP_ENCRYPTION = 'tls';
    
    const REPORT_EMAIL = 'nguyenvanhiep0711@gmail.com'; // Email nhận báo cáo
    const FROM_EMAIL = 'security-scanner@yourdomain.com';
    const FROM_NAME = 'Security Scanner System';
}
```

### Bước 2: Tạo App Password cho Gmail
1. Truy cập [Google Account Settings](https://myaccount.google.com/)
2. Chọn "Security" → "2-Step Verification"
3. Tạo "App passwords" cho "Mail"
4. Copy password và paste vào `SMTP_PASSWORD`

### Bước 3: Cài đặt Cron Job
```bash
# Mở crontab
crontab -e

# Thêm dòng sau (thay đổi đường dẫn cho phù hợp):
0 22 * * * /usr/bin/php /path/to/your/website/daily_security_scan.php >> /path/to/logs/cron.log 2>&1
```

## 🌐 Cách 2: Sử dụng HTTP Request

### Cài đặt với wget:
```bash
# Thêm vào crontab:
0 22 * * * wget -q -O - "http://yourdomain.com/path/to/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +\%Y-\%m-\%d)" >> /path/to/logs/cron.log 2>&1
```

### Cài đặt với curl:
```bash
# Thêm vào crontab:
0 22 * * * curl -s "http://yourdomain.com/path/to/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +\%Y-\%m-\%d)" >> /path/to/logs/cron.log 2>&1
```

## 🧪 Test Chức Năng

### Test Email:
```bash
# Gửi test email:
curl "http://yourdomain.com/security_scan_server.php?api=test_email&admin_key=hiep-admin-test-2025"
```

### Test Manual Scan:
```bash
# Chạy scan thủ công:
php daily_security_scan.php
```

## 📁 Cấu Trúc Files

```
your-website/
├── security_scan_server.php     # Server chính
├── daily_security_scan.php      # Cron job file
├── data/
│   ├── logs/
│   │   ├── scheduler.log         # Log của scheduler
│   │   ├── daily_scan_*.json     # Kết quả scan hàng ngày
│   │   └── cron.log             # Log của cron job
│   └── backups/                 # Backup files
└── config/
    └── clients.json             # Danh sách clients
```

## ⚠️ Lưu Ý Bảo Mật

1. **Cron Key**: Key thay đổi mỗi ngày để bảo mật
2. **Admin Key**: Chỉ dùng cho testing, thay đổi trong production
3. **Email Password**: Sử dụng App Password, không dùng password chính
4. **File Permissions**: Đảm bảo files có quyền phù hợp (644 cho PHP files)

## 🔍 Troubleshooting

### Cron job không chạy:
```bash
# Kiểm tra cron service:
sudo service cron status

# Kiểm tra log:
tail -f /var/log/cron.log
```

### Email không gửi được:
1. Kiểm tra App Password Gmail
2. Kiểm tra SMTP settings
3. Xem log trong `data/logs/scheduler.log`

### Timezone không đúng:
```php
// Thêm vào đầu file:
date_default_timezone_set('Asia/Ho_Chi_Minh');
```

## 📧 Format Email Báo Cáo

Email sẽ được gửi khi:
- Phát hiện threats với mức độ >= 8 (critical)
- File được cập nhật trong ngày hôm nay
- Có ít nhất 1 threat nghiêm trọng

Email bao gồm:
- Tổng số websites bị ảnh hưởng
- Danh sách files bị hack
- Mức độ nguy hiểm
- Thời gian phát hiện
- Đường dẫn file chi tiết
