# 🌐 Hướng Dẫn Cấu Hình Multi-Website Security Scanner

## 📋 Tổng Quan
Hệ thống **Multi-Website Security Scanner** của Hiệp Nguyễn có thể:
- ✅ Tự động phát hiện TẤT CẢ websites trên hosting
- ✅ Quét bảo mật đồng thời multiple websites
- ✅ Gửi email tổng hợp khi có threats nghiêm trọng
- ✅ Dashboard trung tâm quản lý tất cả websites
- ✅ Cron job tự động với khóa bảo mật
- ✅ Tối ưu hóa cho hosting có hàng nghìn websites

---

## 🔧 Bước 1: Cấu Hình Đường Dẫn Hosting

### 1.1 Chỉnh sửa file `multi_website_scanner.php`
Mở file và tìm class `MultiWebsiteConfig` (dòng ~15):

```php
class MultiWebsiteConfig {
    // Cấu hình hosting paths - ĐIỀU CHỈNH CHO HOSTING CỦA BẠN
    const HOSTING_PATHS = array(
        '/public_html/',           // cPanel standard
        '/htdocs/',               // XAMPP/Local
        '/domains/',              // Some hosting providers
        '/var/www/',              // Linux hosting
        '/home/user/public_html/', // User-specific path
    );
```

### 1.2 Điều chỉnh theo hosting của bạn:

**Hosting cPanel:**
```php
const HOSTING_PATHS = array(
    '/public_html/',
    '/home/username/public_html/',
);
```

**Hosting DirectAdmin:**
```php
const HOSTING_PATHS = array(
    '/domains/',
    '/home/username/domains/',
);
```

**VPS/Dedicated Server:**
```php
const HOSTING_PATHS = array(
    '/var/www/',
    '/var/www/html/',
    '/usr/share/nginx/html/',
);
```

**Localhost (XAMPP/WAMP):**
```php
const HOSTING_PATHS = array(
    '/xampp/htdocs/',
    '/wamp/www/',
    '/mamp/htdocs/',
);
```

---

## 📧 Bước 2: Cấu Hình Email Notifications

### 2.1 Email cơ bản
```php
const EMAIL_TO = 'admin@yourdomain.com';    // EMAIL NHẬN CẢNH BÁO
const EMAIL_FROM = 'scanner@yourdomain.com'; // EMAIL GỬI
const EMAIL_FROM_NAME = 'Multi-Site Security Scanner';
```

### 2.2 Email SMTP (tùy chọn)
Nếu hosting hỗ trợ SMTP:
```php
const SMTP_HOST = 'mail.yourdomain.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'scanner@yourdomain.com';
const SMTP_PASSWORD = 'your_password';
```

---

## 🤖 Bước 3: Cấu Hình Cron Job

### 3.1 Cron job cơ bản (mỗi 2 giờ)
```bash
0 */2 * * * /usr/bin/php /path/to/multi_website_cron.php
```

### 3.2 Cron job qua HTTP (với secret key)
```bash
0 */2 * * * curl -s "http://yourdomain.com/multi_website_cron.php?key=hiep_security_2025"
```

### 3.3 Cấu hình secret key
Mở file `multi_website_cron.php` và đổi:
```php
$secretKey = 'your_secret_key_here'; // THAY ĐỔI SECRET KEY
```

---

## 🚀 Bước 4: Triển Khai

### 4.1 Upload files
Upload các file sau lên hosting:
- `multi_website_scanner.php`
- `multi_website_cron.php`
- `security_scan.php` (nếu chưa có)

### 4.2 Tạo thư mục logs
```bash
mkdir logs
chmod 755 logs
```

### 4.3 Chạy thử nghiệm
```bash
php multi_website_cron.php
```

---

## 🎯 Bước 5: Sử Dụng Dashboard

### 5.1 Truy cập Dashboard
Mở trình duyệt: `http://yourdomain.com/multi_website_scanner.php`

### 5.2 Các chức năng chính:
1. **Phát Hiện Websites**: Tìm tất cả websites trên hosting
2. **Quét Tất Cả**: Quét bảo mật toàn bộ websites
3. **Live Stats**: Theo dõi thống kê real-time
4. **Live Logs**: Xem log quét trực tiếp
5. **Emergency Stop**: Dừng quét khẩn cấp

---

## ⚙️ Bước 6: Tối Ưu Hóa Hiệu Suất

### 6.1 Giới hạn quét
```php
const MAX_WEBSITES_PER_SCAN = 20;      // Tối đa 20 websites/lần
const MAX_FILES_PER_WEBSITE = 10000;   // Tối đa 10,000 files/website
const SCAN_TIMEOUT = 300;              // 5 phút/website
```

### 6.2 Exclude thư mục không cần thiết
```php
const EXCLUDE_DIRS = array(
    'cgi-bin', 'logs', 'tmp', 'cache', 'backup',
    'mail', 'ftp', 'phpmyadmin', 'webmail'
);
```

### 6.3 Tối ưu hosting
- Tăng `memory_limit` lên 2GB
- Tăng `max_execution_time` lên 3600s
- Sử dụng SSD cho tốc độ đọc file

---

## 📊 Bước 7: Monitoring & Báo Cáo

### 7.1 Email Reports
Hệ thống tự động gửi email khi:
- Phát hiện threats nghiêm trọng
- Có lỗi trong quá trình quét
- Hoàn thành quét tất cả websites

### 7.2 Log Files
```
logs/multi_website_scan.log      - Log quét chính
logs/multi_website_cron.log      - Log cron job
logs/latest_scan_results.json    - Kết quả quét mới nhất
```

### 7.3 Dashboard Stats
- Tổng số websites phát hiện
- Số websites đã quét
- Tổng threats phát hiện
- Websites có vấn đề

---

## 🛡️ Bước 8: Bảo Mật

### 8.1 Bảo vệ files
```apache
# .htaccess
<Files "multi_website_cron.php">
    Order Deny,Allow
    Deny from all
    Allow from 127.0.0.1
</Files>

<Files "*.log">
    Order Deny,Allow
    Deny from all
</Files>
```

### 8.2 Firewall rules
- Chỉ cho phép truy cập cron job từ localhost
- Hạn chế IP có thể truy cập dashboard
- Sử dụng HTTPS cho dashboard

---

## 🔧 Bước 9: Troubleshooting

### 9.1 Không phát hiện websites
```php
// Kiểm tra đường dẫn
$testPaths = array('/public_html/', '/htdocs/');
foreach ($testPaths as $path) {
    if (is_dir($path)) {
        echo "Found: $path\n";
    }
}
```

### 9.2 Lỗi timeout
```php
// Tăng limits
set_time_limit(7200);  // 2 hours
ini_set('memory_limit', '4096M');
```

### 9.3 Email không gửi được
```php
// Test email
$test = mail('test@domain.com', 'Test', 'Test message');
var_dump($test);
```

---

## 📱 Bước 10: Mobile Dashboard (Tùy chọn)

### 10.1 Responsive Design
Dashboard đã được tối ưu cho mobile với:
- Bootstrap 5 responsive grid
- Touch-friendly buttons
- Mobile-optimized charts

### 10.2 Push Notifications
Có thể tích hợp:
- Telegram Bot API
- Slack Webhooks
- Discord Webhooks

---

## 🎉 Ví Dụ Thực Tế

### 10.1 Hosting có 50 websites
```
📊 Scan Results:
- Websites detected: 50
- Scan time: 15 minutes
- Threats found: 3
- Critical sites: 2
- Email sent: ✅
```

### 10.2 Hosting có 500 websites
```
📊 Scan Results:
- Websites detected: 500
- Batches: 25 (20 sites/batch)
- Total scan time: 3 hours
- Threats found: 12
- Critical sites: 8
- Email sent: ✅
```

---

## 🔄 Bước 11: Cập Nhật & Bảo Trì

### 11.1 Cập nhật patterns
Định kỳ cập nhật malware patterns trong:
```php
$malwarePatterns = array(
    '/eval\s*\(/i',
    '/base64_decode\s*\(/i',
    // Thêm patterns mới
);
```

### 11.2 Backup & Recovery
```bash
# Backup results
cp logs/latest_scan_results.json backups/scan_$(date +%Y%m%d).json

# Restore từ backup
cp backups/scan_20250101.json logs/latest_scan_results.json
```

---

## 💡 Pro Tips

1. **Chạy lần đầu**: Phát hiện websites trước khi setup cron
2. **Test trước**: Chạy thử với 1-2 websites trước
3. **Monitor resources**: Theo dõi CPU/RAM khi quét
4. **Phân tích logs**: Kiểm tra logs thường xuyên
5. **Backup định kỳ**: Lưu trữ kết quả quét quan trọng

---

## 📞 Hỗ Trợ

- **Developer**: Hiệp Nguyễn
- **Facebook**: https://www.facebook.com/G.N.S.L.7/
- **Version**: 4.0 Multi-Site Enterprise
- **Last Updated**: 2025

---

**🚨 LƯU Ý**: Hệ thống này được thiết kế để xử lý hosting có hàng trăm websites. Nếu bạn có hàng nghìn websites, hãy liên hệ để được tư vấn tối ưu hóa thêm!

---

*Phát triển bởi Hiệp Nguyễn - Chuyên gia bảo mật web* 