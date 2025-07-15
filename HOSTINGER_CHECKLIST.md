# ✅ HOSTINGER SETUP CHECKLIST

## 🚀 BƯỚC 1: Upload Files
- [ ] **Đăng nhập Hostinger Control Panel**
- [ ] **Vào File Manager**
- [ ] **Đi tới Home Directory** (cùng cấp với `domains/`)
- [ ] **Upload 5 files này:**
  - [ ] `multi_website_scanner.php` ✅ (Đã cấu hình cho Hostinger)
  - [ ] `multi_website_cron.php` ✅ (Đã cấu hình email)
  - [ ] `test_multi_scanner.php` ✅ (Test scanner)
  - [ ] `test_email_hostinger.php` ✅ (Test email)
  - [ ] `HOSTINGER_SETUP_GUIDE.md` ✅ (Hướng dẫn chi tiết)

## 🔧 BƯỚC 2: Tạo Thư Mục
- [ ] **Tạo thư mục `logs/` trong Home Directory**
- [ ] **Set quyền 755 cho thư mục logs**

## 🧪 BƯỚC 3: Test Hệ Thống
- [ ] **Chạy:** `http://your_domain.com/test_multi_scanner.php`
- [ ] **Kiểm tra phát hiện websites** (phải thấy minhtanphat.vn)
- [ ] **Chạy:** `http://your_domain.com/test_email_hostinger.php`
- [ ] **Kiểm tra email trong hộp thư** (bao gồm spam folder)

## 📊 BƯỚC 4: Dashboard
- [ ] **Truy cập:** `http://your_domain.com/multi_website_scanner.php`
- [ ] **Nhấn "Phát Hiện Websites"**
- [ ] **Nhấn "Quét Tất Cả Websites"**
- [ ] **Xem kết quả trong Dashboard**

## 🤖 BƯỚC 5: Cron Job
- [ ] **Hostinger Control Panel → Advanced → Cron Jobs**
- [ ] **Tạo Cron Job:**
  ```
  0 */2 * * * /usr/bin/php /home/YOUR_USERNAME/multi_website_cron.php
  ```
- [ ] **Thay YOUR_USERNAME bằng username Hostinger của bạn**

## 📧 BƯỚC 6: Email Notifications
- [ ] **Email TO:** `nguyenvanhiep0711@gmail.com` ✅ (Đã cấu hình)
- [ ] **Test email hoạt động** ✅ (Dùng test_email_hostinger.php)

## 🎯 KẾT QUẢ MONG ĐỢI:
- ✅ Phát hiện được `minhtanphat.vn` và các domain khác
- ✅ Dashboard hiển thị websites
- ✅ Email notifications hoạt động
- ✅ Cron job chạy tự động
- ✅ Logs ghi đầy đủ hoạt động

## 🚨 NẾU GẶP LỖI:
1. **Không phát hiện websites** → Kiểm tra quyền thư mục `domains/`
2. **Email không gửi được** → Chạy `test_email_hostinger.php`
3. **Cron job không chạy** → Kiểm tra đường dẫn username
4. **Dashboard lỗi** → Kiểm tra PHP version và memory limit

## 📱 LIÊN HỆ HỖ TRỢ:
- **Email:** nguyenvanhiep0711@gmail.com
- **Facebook:** https://www.facebook.com/G.N.S.L.7/

**🔥 Sau khi hoàn thành, hosting Hostinger của bạn sẽ được bảo vệ 24/7!** 