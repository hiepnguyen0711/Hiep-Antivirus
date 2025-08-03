# Hướng Dẫn Cài Đặt Cron Job - Security Scanner System

## Tổng Quan

Hệ thống Security Scanner hỗ trợ lên lịch tự động quét bảo mật và gửi email báo cáo thông qua cron job. Tài liệu này hướng dẫn chi tiết cách cài đặt và cấu hình.

## Bước 1: Cấu Hình Email

### Gmail SMTP (Khuyến nghị)

1. **Bật 2-Factor Authentication** cho tài khoản Gmail
2. **Tạo App Password:**
   - Truy cập Google Account → Security
   - Chọn "App passwords" 
   - Chọn "Mail" và tạo password
   - Copy password 16 ký tự

3. **Cập nhật cấu hình trong `security_scan_server.php`:**

```php
class EmailConfig
{
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 465;
    const SMTP_USERNAME = 'your-email@gmail.com';
    const SMTP_PASSWORD = 'your-16-char-app-password';
    const SMTP_ENCRYPTION = 'ssl';
    
    const REPORT_EMAIL = 'admin@company.com';
    const FROM_EMAIL = 'security@company.com';
    const FROM_NAME = 'Security Scanner System';
    
    // Danh sách email nhận báo cáo
    const ADDITIONAL_EMAILS = [
        'admin@company.com',
        'security@company.com'
    ];
}
```

## Bước 2: Cấu Hình Scheduler

Tùy chỉnh thời gian quét trong `SchedulerConfig`:

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

## Bước 3: Cài Đặt Cron Job

### Linux/Unix

1. **Mở crontab editor:**
```bash
crontab -e
```

2. **Thêm các dòng sau:**
```bash
# Quét bảo mật hàng ngày lúc 2:00 AM
0 2 * * * /usr/bin/php /path/to/your/daily_security_scan.php daily_scan >/dev/null 2>&1

# Báo cáo tổng hợp hàng tuần (Chủ nhật 3:00 AM)
0 3 * * 0 /usr/bin/php /path/to/your/daily_security_scan.php cleanup >/dev/null 2>&1

# Dọn dẹp logs cũ (hàng tháng)
0 4 1 * * find /path/to/your/data/logs -name "*.log" -mtime +30 -delete >/dev/null 2>&1
```

3. **Hoặc sử dụng curl/wget:**
```bash
# Quét hàng ngày
0 2 * * * curl -s "http://yourdomain.com/security_scan_server.php?api=run_daily_scan&cron_key=hiep-security-cron-2025-$(date +\%Y-\%m-\%d)" >/dev/null 2>&1

# Test email
0 3 * * 0 curl -s "http://yourdomain.com/security_scan_server.php?api=test_email&admin_key=hiep-admin-test-2025" >/dev/null 2>&1
```

### Windows Task Scheduler

1. **Tạo file batch `daily_scan.bat`:**
```batch
@echo off
cd /d "C:\xampp\htdocs\2025\Hiep-Antivirus"
php daily_security_scan.php daily_scan
```

2. **Tạo task trong Task Scheduler:**
   - Tên: Security Daily Scan
   - Trigger: Daily at 2:00 AM
   - Action: Start program "C:\path\to\daily_scan.bat"

## Bước 4: Test Cron Job

### Test Manual

```bash
# Test daily scan
php daily_security_scan.php daily_scan --force

# Test cleanup
php daily_security_scan.php cleanup --force

# Test script
php daily_security_scan.php test
```

### Test qua Web

```bash
# Test daily scan
curl "http://your-domain.com/daily_security_scan.php?action=daily_scan&force=1"

# Test email
curl "http://your-domain.com/security_scan_server.php?api=test_email&admin_key=hiep-admin-test-2025"
```

### Kiểm tra Logs

```bash
# Xem logs realtime
tail -f data/logs/cron_daily.log

# Xem scheduler logs
tail -f data/logs/scheduler.log

# Kiểm tra cron job status (Linux)
systemctl status cron
grep CRON /var/log/syslog
```

## Bước 5: Monitoring

### Cấu trúc Logs

```
data/
├── logs/
│   ├── cron_daily.log      # Logs của daily_security_scan.php
│   ├── scheduler.log       # Logs của SecurityScheduler
│   └── email.log          # Logs email (nếu có)
├── scan_results/          # Kết quả scan hàng ngày
└── backups/              # Backup files
```

### Lịch Trình Mặc Định

| Loại | Thời Gian | Mô Tả | Cron Expression |
|------|-----------|-------|-----------------|
| Daily Scan | 2:00 AM hàng ngày | Quét tất cả clients, gửi email nếu có critical | `0 2 * * *` |
| Weekly Report | 3:00 AM Chủ nhật | Báo cáo tổng hợp tuần | `0 3 * * 0` |
| Monthly Cleanup | 4:00 AM ngày 1 | Dọn dẹp logs cũ | `0 4 1 * *` |
| Critical Alert | Realtime | Email ngay khi có critical threats | Triggered |

## Troubleshooting

### Lỗi Thường Gặp

1. **Cron job không chạy:**
   - Kiểm tra crontab syntax: `crontab -l`
   - Kiểm tra đường dẫn PHP: `which php`
   - Kiểm tra permissions: `ls -la daily_security_scan.php`

2. **Email không gửi được:**
   - Kiểm tra SMTP settings
   - Kiểm tra App Password Gmail
   - Kiểm tra firewall port 465/587

3. **Timeout errors:**
   - Tăng timeout trong config
   - Giảm số clients quét đồng thời
   - Kiểm tra server resources

### Debug Commands

```bash
# Kiểm tra PHP CLI
php -v

# Test cron job manual
php daily_security_scan.php test --force

# Kiểm tra permissions
ls -la data/
ls -la data/logs/

# Test email function
php -r "mail('test@example.com', 'Test', 'Test message');"
```

## Bảo Mật

### Quan Trọng

1. **Thay đổi API keys mặc định**
2. **Sử dụng HTTPS** cho tất cả connections
3. **Bảo vệ data folder:**
```bash
echo "Deny from all" > data/.htaccess
chmod 755 data/
chmod 644 data/clients.json
```

4. **Backup định kỳ:**
```bash
cp security_scan_server.php security_scan_server.backup.php
tar -czf backup_$(date +%Y%m%d).tar.gz data/ *.php
```

## Liên Hệ Hỗ Trợ

- **Email:** nguyenvanhiep0711@gmail.com
- **Facebook:** [Hiệp Nguyễn](https://www.facebook.com/G.N.S.L.7/)

---

**Lưu ý:** Thay đổi tất cả `/path/to/your/` thành đường dẫn thực tế của project.
