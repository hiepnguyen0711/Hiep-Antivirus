# 🌐 Hướng Dẫn Cài Đặt Multi-Website Scanner Cho HOSTINGER

## 📋 Phân Tích Cấu Trúc Hosting Hostinger

Từ hình ảnh bạn cung cấp, tôi thấy cấu trúc Hostinger của bạn:
```
📁 Home Directory
├── 📁 cache/
├── 📁 config/
├── 📁 domains/           ← QUAN TRỌNG: Chứa tất cả websites
├── 📁 local/
├── 📁 logs/
├── 📁 nvm/
├── 📁 ssh/
├── 📁 subversion/
├── 📁 trash/
├── 📁 wp-cli/
├── 📄 public_html → domains/minhtanphat.vn  ← Symbolic link
├── 📄 api_token
├── 📄 bash_history
├── 📄 profile
├── 📄 wget-hsts
├── 📄 composer.json
├── 📄 composer.lock
└── 📄 error_log
```

## 🎯 Vị Trí Tối Ưu Để Đặt Scanner

### ✅ VỊ TRÍ KHUYẾN NGHỊ: **Home Directory** (cùng cấp với thư mục `domains/`)

**Lý do:**
- Có thể truy cập tất cả websites trong `domains/`
- Có thể quét cả domain chính qua `public_html/`
- Quyền đọc/ghi tối ưu
- Không bị ảnh hưởng bởi cập nhật website

---

## 🚀 BƯỚC 1: Upload Files Vào Hostinger

### 1.1 Sử dụng File Manager của Hostinger
1. Đăng nhập **Hostinger Control Panel**
2. Vào **File Manager**
3. Đi đến **Home Directory** (cùng cấp với thư mục `domains/`)
4. Upload 4 files này:
   - `multi_website_scanner.php`
   - `multi_website_cron.php`
   - `HOSTINGER_SETUP_GUIDE.md`
   - `test_multi_scanner.php`

### 1.2 Hoặc Sử dụng FTP/SFTP
```bash
# Kết nối SFTP
sftp your_username@your_domain.com

# Upload files
put multi_website_scanner.php
put multi_website_cron.php
put HOSTINGER_SETUP_GUIDE.md
put test_multi_scanner.php

# Thoát
exit
```

---

## 🔧 BƯỚC 2: Tạo Thư Mục Logs

### 2.1 Trong File Manager:
1. Tại **Home Directory**
2. Tạo thư mục mới: `logs`
3. Set quyền: `755`

### 2.2 Hoặc qua SSH:
```bash
mkdir logs
chmod 755 logs
```

---

## 🧪 BƯỚC 3: Test Hệ Thống

### 3.1 Chạy Test Demo
Truy cập: `http://your_domain.com/test_multi_scanner.php`

**Kết quả mong đợi:**
```
=== MULTI-WEBSITE SCANNER TEST DEMO ===
🔍 TEST 1: Phát hiện websites...
Kết quả: Phát hiện X websites

📋 Danh sách websites phát hiện:
1. minhtanphat.vn
   Domain: minhtanphat.vn
   Path: /domains/minhtanphat.vn
   Last scan: Chưa quét

2. [các domain khác nếu có]
```

### 3.2 Nếu Không Phát Hiện Websites:
```bash
# Kiểm tra quyền truy cập
ls -la domains/
ls -la public_html/

# Kiểm tra đường dẫn
pwd
```

---

## 🎯 BƯỚC 4: Truy Cập Dashboard

### 4.1 Mở Dashboard
URL: `http://your_domain.com/multi_website_scanner.php`

### 4.2 Các Bước Trong Dashboard:
1. **Nhấn "Phát Hiện Websites"** → Xem danh sách
2. **Nhấn "Quét Tất Cả Websites"** → Bắt đầu quét
3. **Theo dõi Live Stats** → Thống kê real-time
4. **Kiểm tra Live Logs** → Tiến trình chi tiết

---

## 🤖 BƯỚC 5: Cấu Hình Cron Job Trên Hostinger

### 5.1 Truy Cập Cron Jobs:
1. **Hostinger Control Panel**
2. **Advanced** → **Cron Jobs**
3. **Create New Cron Job**

### 5.2 Cấu Hình Cron Job:
```bash
# Chạy mỗi 2 giờ
0 */2 * * * /usr/bin/php /home/your_username/multi_website_cron.php

# Hoặc chạy mỗi 6 giờ
0 */6 * * * /usr/bin/php /home/your_username/multi_website_cron.php
```

**Lưu ý:** Thay `your_username` bằng username Hostinger của bạn

### 5.3 Hoặc Cron Job Qua HTTP:
```bash
# Với secret key
0 */2 * * * curl -s "http://your_domain.com/multi_website_cron.php?key=hiep_security_2025"
```

---

## 📧 BƯỚC 6: Test Email Notifications

### 6.1 Kiểm tra Email đã được cấu hình:
- Email TO: `nguyenvanhiep0711@gmail.com` ✅
- Email FROM: `multi-scanner@yourdomain.com`

### 6.2 Test Email:
```php
// Tạo file test_email.php
<?php
$to = 'nguyenvanhiep0711@gmail.com';
$subject = 'Test Email từ Hostinger';
$message = 'Email test từ Multi-Website Scanner';
$headers = 'From: scanner@' . $_SERVER['SERVER_NAME'];

if (mail($to, $subject, $message, $headers)) {
    echo "Email gửi thành công!";
} else {
    echo "Lỗi gửi email!";
}
?>
```

---

## 🛠️ BƯỚC 7: Tối Ưu Hóa Cho Hostinger

### 7.1 Cấu Hình PHP (nếu cần):
Tạo file `.htaccess` trong thư mục chứa scanner:
```apache
php_value memory_limit 1024M
php_value max_execution_time 600
php_value max_input_time 300
```

### 7.2 Exclude Thư Mục Không Cần:
Trong file đã được cấu hình sẵn:
```php
const EXCLUDE_DIRS = array(
    'cache', 'config', 'logs', 'tmp', 'nvm', 'ssh', 
    'subversion', 'trash', 'wp-cli', 'cgi-bin',
    'mail', 'ftp', 'phpmyadmin', 'webmail'
);
```

---

## 🔍 BƯỚC 8: Monitoring & Kiểm Tra

### 8.1 Kiểm Tra Logs:
```bash
# Xem log scanner
tail -f logs/multi_website_scan.log

# Xem log cron
tail -f logs/multi_website_cron.log
```

### 8.2 Kiểm Tra Kết Quả:
File: `logs/latest_scan_results.json`
```json
{
  "timestamp": 1735123456,
  "total_websites": 5,
  "total_threats": 2,
  "critical_sites": 1,
  "results": [...]
}
```

---

## 🚨 TROUBLESHOOTING

### ❌ Lỗi: "Không phát hiện websites"
**Giải pháp:**
```bash
# Kiểm tra đường dẫn
ls -la domains/
ls -la public_html/

# Kiểm tra quyền
chmod 755 domains/
chmod 755 public_html/
```

### ❌ Lỗi: "Permission denied"
**Giải pháp:**
```bash
# Set quyền cho scanner
chmod 755 multi_website_scanner.php
chmod 755 multi_website_cron.php
chmod 755 logs/
```

### ❌ Lỗi: "Email không gửi được"
**Giải pháp:**
- Kiểm tra Hostinger có block email functions không
- Thử dùng SMTP thay vì mail() function
- Kiểm tra spam folder

### ❌ Lỗi: "Cron job không chạy"
**Giải pháp:**
- Kiểm tra đường dẫn PHP: `/usr/bin/php`
- Kiểm tra đường dẫn file: `/home/username/multi_website_cron.php`
- Kiểm tra log cron trong Hostinger Control Panel

---

## 📊 Kết Quả Mong Đợi

### Sau Khi Hoàn Thành:
- ✅ Dashboard hiển thị tất cả websites
- ✅ Quét tự động theo lịch
- ✅ Email cảnh báo khi có threats
- ✅ Logs chi tiết mọi hoạt động
- ✅ Thống kê real-time

### Ví Dụ Cho Hosting Hostinger:
```
📊 Scan Results:
- Websites detected: 3
  1. minhtanphat.vn
  2. subdomain.minhtanphat.vn
  3. [domain khác nếu có]
- Scan time: 5 minutes
- Threats found: 0
- Status: ALL CLEAN ✅
```

---

## 🎉 HOÀN THÀNH!

Bây giờ bạn có:
- 🌐 **Multi-Website Scanner** cho tất cả websites
- 📊 **Professional Dashboard** 
- 🤖 **Auto Scan** mỗi 2-6 giờ
- 📧 **Email Alerts** tự động
- 🔍 **Real-time Monitoring**

**🔥 Hostinger của bạn giờ được bảo vệ 24/7!**

---

## 📞 Hỗ Trợ

- **Developer**: Hiệp Nguyễn  
- **Email**: nguyenvanhiep0711@gmail.com
- **Facebook**: https://www.facebook.com/G.N.S.L.7/
- **Hostinger Version**: Optimized for Hostinger 2025

---

*Được tối ưu hóa đặc biệt cho Hostinger hosting* 